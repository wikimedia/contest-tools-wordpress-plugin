<?php
/**
 * Scoring results for submissions.
 *
 * Scoring results will be stored in the comments table using the 'workflow'
 * comment type. We use the 'comment_agent' field in that table to mark these comments as scoring
 *
 * @package wikimedia-contest;
 */

namespace Wikimedia_Contest\Scoring;

use Wikimedia_Contest\Post_Type;
use Wikimedia_Contest\Scoring_Queue_List_Table;

/**
 * User role for scoreers.
 *
 * @var string
 */
const USER_ROLE = 'scoring_panel';

/**
 * Comment type used to store editorial comments.
 *
 * @var string
 */
const COMMENT_TYPE = 'workflow';

/**
 * Name used in the comment agent to identify these comments.
 *
 * @var string
 */
const COMMENT_AGENT = 'scoring_comment';

/**
 * Statuses used in the scoring process.
 *
 * @var array
 */
const SCORING_STATUSES = [
	'scoring_phase_1',
	'scoring_phase_2',
	'scoring_phase_3',
];

/**
 * Abstracting the score categories, weights and criteria.
 *
 * @var array
 */
const SCORING_CRITERIA = [
	'conceptual' => [
		'weight'   => 0.5,
		'criteria' => [
			'represent_spirit' => 'To what extent does the sound represent the spirit of the Wikimedia movement',
			'closely_communicate' => 'How closely does the sound communicate one of the creative prompts',
			'feel_human' => 'To what extent does it feel human, inspired, smart and warm',
		]
	],
	'originality' => [
		'weight'   => 0.25,
		'criteria' => [
			'original_unique' => 'To what extent does the sound feel original and unique',
			'stand_out' => 'How much does it stand out compared to other sound logos',
		]
	],
	'recallability' => [
		'weight'   => 0.25,
		'criteria' => [
			'feel_recall' => 'How easily do you feel you could recall the sound logo',
			'easy_replicate' => 'How easily do you feel you would be able to replicate (sing / hum / tap) the sound logo',
		]
	],
];

/**
 * Bootstrap scoring results related functionality.
 *
 * @return void
 */
function bootstrap() : void {
	add_action( 'init', __NAMESPACE__ . '\\register_scoring_panel' );
	add_action( 'init', __NAMESPACE__ . '\\support_editorial_comments' );
	add_action( 'admin_menu', __NAMESPACE__ . '\\register_scoring_menu_pages' );
}

/**
 * Register scoring_panel role.
 *
 * @return void
 */
function register_scoreer_role() : void {
	$roles = get_option( 'wikimedia_contest_roles' ) ?: [];

	if ( ! $roles[ USER_ROLE ] !== null ) {
		wpcom_vip_add_role(
			USER_ROLE,
			__( 'Scoring Panel', 'wikimedia-contest-admin' ),
			array_merge(
				get_role( 'contributor' )->capabilities,
				[
					'score_submissions' => true,
					'view_scored_submissions' => false,
				]
			)
		);

		$roles[ USER_ROLE ] = 1;
		update_option( 'wikimedia_contest_roles', $roles );
	}
}

/**
 * Add the Score Pages to the admin menu.
 *
 * If the user can edit submissions, this page will be a submenu page under the
 * Submissions header. Otherwise, it's a top-level menu item.
 *
 * @return void
 */
function register_scoring_menu_pages() : void {

	if ( current_user_can( 'edit_submissions' ) ) {
		add_submenu_page(
			'edit.php?post_type=submission',
			__( 'Scoring Queue', 'wikimedia-contest-admin' ),
			__( 'Scoring Queue', 'wikimedia-contest-admin' ),
			'score_submissions',
			'scoring-queue',
			__NAMESPACE__ . '\\render_scoring_queue',
			5
		);
		add_submenu_page(
			'edit.php?post_type=submission',
			__( 'Score Submission', 'wikimedia-contest-admin' ),
			__( 'Score Submission', 'wikimedia-contest-admin' ),
			'score_submissions',
			'score-submission',
			__NAMESPACE__ . '\\render_scoring_interface',
			5
		);
	} else {
		add_menu_page(
			__( 'Scoring Queue', 'wikimedia-contest-admin' ),
			__( 'Scoring Queue', 'wikimedia-contest-admin' ),
			'score_submissions',
			'scoring-queue',
			__NAMESPACE__ . '\\render_scoring_queue',
			'dashicons-yes-alt',
			5
		);
		add_submenu_page(
			'scoring-queue',
			__( 'Score Submission', 'wikimedia-contest-admin' ),
			__( 'Score Submission', 'wikimedia-contest-admin' ),
			'score_submissions',
			'score-submission',
			__NAMESPACE__ . '\\render_scoring_interface',
			5
		);
	}
}

/**
 * Render the Scoring Queue page.
 *
 * @return void
 */
function render_scoring_queue() : void {
	require_once __DIR__ . '/class-scoring-queue-list-table.php';
	$list_table = new Scoring_Queue_List_Table();
	$list_table->prepare_items();

	echo '<div id="scoring-queue" class="wrap">';
	echo '<h1 class="wp-heading-inline">' . esc_html__( 'Scoring Queue', 'wikimedia-contest-admin' ) . '</h1>';
	echo '<hr class="wp-header-end">';

	$list_table->display();

	echo '</div>';
}

/**
 * Render the Scoring interface.
 *
 * @return void
 */
function render_scoring_interface() : void {
	$post_id = $_REQUEST['post'] ?? null;

	if ( ! $post_id ) {
		wp_safe_redirect( admin_url( 'edit.php?post_type=submission&page=scoring-queue' ) );
	}

	if ( ! empty( $_POST['_score_submission_nonce'] ) ) {
		handle_scoring_results();
	}

	require_once dirname( __DIR__ ) . '/templates/scoring-interface.php';
}

/**
 * Get a link to edit a submission post.
 *
 * @param int $submission_id Submission ID.
 * @return string Scoring interface URL for this post.
 */
function get_scoring_link( $submission_id ) {
	return add_query_arg(
		[
			'post_type' => 'submission',
			'page' => 'score-submission',
			'post' => $submission_id,
		],
		admin_url( 'edit.php' )
	);
}

/**
 * Handle user-submitted scoring results.
 *
 * @return void
 */
function handle_scoring_results() : void {
	check_admin_referer( 'score-submission', '_score_submission_nonce' );

	$post_id = $_REQUEST['post'];

	if ( ! current_user_can( 'score-submissions' ) ) {
		return;
	}

	// Iterate over $_POST and save fields that starts with 'scoring_criteria_' string.
	$scoring_criteria = [];
	foreach ( $_POST as $key => $value ) {
		if ( strpos( $key, 'scoring_criteria_' ) === 0 ) {
			$scoring_criteria[ $key ] = $value;
		}
	}
	$additional_comment = sanitize_text_field( $_POST['additional_scoring_comment'] );

	$results = [
		'scoring' => $scoring_criteria,
		'additional_comment' => $additional_comment,
	];

	add_scoring_comment( $post_id, $results, get_current_user_id() );
	wp_safe_redirect( admin_url( 'edit.php?post_type=submission&page=scoring-queue' ) );
}

/**
 * Ensure that the post type registers support for editorial comments.
 *
 * In order to allow for easier access to an edit trail for scoring and
 * scoring on these posts, we'll reuse the 'workflow' comment_type, which
 * enables the Editorial Comments metabox.
 *
 * @return void
 */
function support_editorial_comments() : void {
	add_post_type_support( Post_Type\SLUG, 'editorial-comments' );
}

/**
 * Insert a new scoring comment.
 *
 * @param int   $submission_id Post ID of submission being scoreed.
 * @param array $results Scoring result fields.
 *   @var array  'scoring'            All scores given by user to each scoring fields
 *   @var string 'additional_comment' Free-text message field for additional comments.
 * @param int   $user_id User ID for the user who is scoring.
 *
 * @return void
 */
function add_scoring_comment( int $submission_id, array $results, $user_id ) : void {

	// Inactivate any existing scoring comments from this user.
	inactivate_user_scoring_comments( $submission_id, $user_id );

	// Invalid values should not come through, but it's better to be safe than sorry.
	$results['scoring'] = array_map( 'absint', $results['scoring'] );

	$scoring_content = wp_json_encode( $results['scoring'] );

	wp_insert_comment( [
		'comment_post_ID' => $submission_id,
		'comment_type' => COMMENT_TYPE,
		'comment_agent' => COMMENT_AGENT,
		'comment_author' => get_userdata( $user_id )->user_nicename ?? get_bloginfo( 'name' ),
		'comment_content' => $scoring_content,
		'comment_meta' => [
			'additional_comment' => $results['additional_comment'] ?? null,
		],
		'comment_status' => 'approve',
		'user_id' => $user_id,
	] );
}

/**
 * Get user assigned score on the specified post.
 *
 * @param int $submission_id Post ID of the submission to retrieve results for.
 * @param int $user_id_id User ID which inserted scoring comments.
 *
 * @return array Array of result data.
 */
function get_user_score( $submission_id, $user_id ) : array {
	$comments = get_comments( [
		'post_id' => $submission_id,
		'type' => COMMENT_TYPE,
		'agent' => COMMENT_AGENT,
		'status' => 'approve',
		'user_id' => $user_id,
	] );

	$comment = end( $comments );
	if ( ! $comment ) {
		return null;
	}

	$scoring_content['criteria'] = json_decode( $comment->comment_content, true );

	// not natural values should not be here, but we never know.
	$scoring_content['criteria'] = array_map( 'absint', $scoring_content['criteria'] );

	$scoring_content['additional_comment'] = get_comment_meta( $comment->comment_ID, 'additional_comment', true );

	return $scoring_content ?? null;
}

/**
 * Inativating all past comments from the user, so we don't contabilize them but keep the record.
 *
 * @param int $submission_id Post ID of the submission to retrieve results for.
 * @param int $user_id User ID which inserted scoring comments.
 *
 * @return void
  */
function inactivate_user_scoring_comments( $submission_id, $user_id ) : void {
	$comments = get_comments( [
		'post_id' => $submission_id,
		'type' => COMMENT_TYPE,
		'agent' => COMMENT_AGENT,
		'status' => 'approve',
		'user_id' => $user_id,
	] );

	foreach ( $comments as $comment ) {
		wp_set_comment_status( $comment->comment_ID, 'hold' );
	}
}

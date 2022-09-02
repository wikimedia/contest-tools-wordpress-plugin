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
 * User role for scorers.
 *
 * @var string
 */
const PANELIST_USER_ROLE = 'scoring_panel';

/**
 * User role for scoring panel leads.
 *
 * @var string
 */
const PANEL_LEAD_USER_ROLE = 'scoring_panel_lead';

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
 * How many scorers are needed for each of scoring phase.
 *
 * @var array
 */
const SCORERS_NEEDED_EACH_PHASE = [
	'scoring_phase_1' => 2,
	'scoring_phase_2' => 10,
	'scoring_phase_3' => 12,
];

/**
 * Abstracting the score categories, weights and criteria.
 *
 * @var array
 */
const SCORING_CRITERIA = [
	'conceptual' => [
		'weight'   => 0.5,
		'label'    => 'Conceptual Match (%d%% weighting)',
		'criteria' => [
			'represent_spirit' => 'To what extent does the sound logo represent the spirit of the Wikimedia movement?',
			'closely_communicate' => 'How closely does the sound logo communicate one of the creative prompts?',
			'feel_human' => 'To what extent does it feel human, inspired, smart and warm?',
		]
	],
	'originality' => [
		'weight'   => 0.25,
		'label'    => 'Originality / Uniqueness (%s%% weighting)',
		'criteria' => [
			'original_unique' => 'To what extent does the sound logo feel original and unique?',
			'stand_out' => 'How much does it stand out compared to other sound logos?',
		]
	],
	'recallability' => [
		'weight'   => 0.25,
		'label'    => 'Recallability (%s%% weighting)',
		'criteria' => [
			'feel_recall' => 'How easily do you feel you could recall the sound logo?',
			'easy_replicate' => 'How easily do you feel you would be able to replicate (sing / hum / tap) the sound logo?',
		]
	],
];

/**
 * Bootstrap scoring results related functionality.
 *
 * @return void
 */
function bootstrap() : void {
	add_action( 'init', __NAMESPACE__ . '\\register_scorer_roles' );
	add_action( 'init', __NAMESPACE__ . '\\support_editorial_comments' );
	add_action( 'admin_menu', __NAMESPACE__ . '\\register_scoring_menu_pages' );
	add_action( 'pre_get_posts', __NAMESPACE__ . '\\register_meta_orderby' );
}

/**
 * Register scoring panelist and panelist lead roles.
 *
 * @return void
 */
function register_scorer_roles() : void {
	$roles = get_option( 'wikimedia_contest_roles' ) ?: [];

	if ( ! isset( $roles[ PANELIST_USER_ROLE ] ) ) {
		wpcom_vip_add_role(
			PANELIST_USER_ROLE,
			__( 'Scoring Panel', 'wikimedia-contest-admin' ),
			array_merge(
				get_role( 'contributor' )->capabilities,
				[
					'score_submissions' => true,
					'view_scored_submissions' => false,
				]
			)
		);
		$roles[ PANELIST_USER_ROLE ] = 1;
		update_option( 'wikimedia_contest_roles', $roles );
	}

	if ( ! isset( $roles[ PANEL_LEAD_USER_ROLE ] ) ) {
		wpcom_vip_add_role(
			PANEL_LEAD_USER_ROLE,
			__( 'Scoring Panelist Lead', 'wikimedia-contest-admin' ),
			array_merge(
				get_role( 'editor' )->capabilities,
				[
					'score_submissions' => true,
					'assign_scorers' => true,
					'promote_submissions' => true,
				]
			)
		);

		$roles[ PANEL_LEAD_USER_ROLE ] = 1;
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

	$current_contest_phase_option = get_site_option( 'contest_status' );

	// Don't show the Scoring menu item if the contest phase isn't a scoring phase.
	if ( ! in_array( $current_contest_phase_option, \Wikimedia_Contest\Scoring\SCORING_STATUSES ) ) {
		return;
	}

	add_menu_page(
		sprintf( '<b>%s</b> Queue', \Wikimedia_Contest\Network_Settings\CONTEST_PHASES[ $current_contest_phase_option ] ),
		sprintf( '<b>%s</b> Queue', \Wikimedia_Contest\Network_Settings\CONTEST_PHASES[ $current_contest_phase_option ] ),
		'score_submissions',
		'scoring-queue',
		__NAMESPACE__ . '\\render_scoring_queue',
		'dashicons-yes-alt',
		3
	);

	add_submenu_page(
		'scoring-queue',
		__( 'Score Submission', 'wikimedia-contest-admin' ),
		__( 'Score Submission', 'wikimedia-contest-admin' ),
		'score_submissions',
		'score-submission',
		__NAMESPACE__ . '\\render_scoring_interface',
	);
}

/**
 * Render the Scoring Queue page.
 *
 * @return void
 */
function render_scoring_queue() : void {

	$current_contest_phase_option = get_site_option( 'contest_status' );

	// Don't render the Scoring Queue if the contest phase isn't a scoring phase.
	if ( ! in_array( $current_contest_phase_option, \Wikimedia_Contest\Scoring\SCORING_STATUSES ) ) {
		return;
	}

	require_once __DIR__ . '/class-scoring-queue-list-table.php';

	$list_table = new Scoring_Queue_List_Table();
	$list_table->prepare_items();

	$current_contest_phase_option = get_site_option( 'contest_status' );
	$custom_post_statuses = get_post_stati( [
		'_builtin' => false,
		'internal' => false,
	], 'objects' );

	// Hook into the list-tables API for running actions.
	$current_action = $list_table->current_action();
	$current_screen = $list_table->screen;
	if ( ! empty( $current_action )  ) {

		// Run through the handle_bulk_actions filter; this can be used to add
		// messages to the redirect url.
		$return_url = apply_filters(
			"handle_bulk_actions-{$current_screen->id}",
			admin_url( 'admin.php?page=scoring-queue' ),
			$current_action,
			array_map( 'intval', (array) $_REQUEST['post'] ),
		);

		wp_safe_redirect( $return_url );
	}

	echo '<div id="scoring-queue" class="wrap">';
	echo '<h1 class="wp-heading-inline">' . esc_html__( 'Scoring Queue - Contest Phase:', 'wikimedia-contest-admin' ) . ' <b>' . $custom_post_statuses[ $current_contest_phase_option ]->label  . '</b></h1>';
	echo '<hr class="wp-header-end">';

	echo '<form action="" method="GET">';
	echo '<input type="hidden" name="page" value="scoring-queue" />';

	$list_table->display();

	echo '</form>';
	echo '</div>';
}

/**
 * Render the Scoring interface.
 *
 * @return void
 */
function render_scoring_interface() : void {
	$post_id = sanitize_text_field( $_REQUEST['post'] ?? null );
	if ( ! $post_id ) {
		wp_safe_redirect( admin_url( 'admin.php?page=scoring-queue' ) );
		exit;
	}

	// Do not render Scoring Interface if the submission isn't assigned to the current user.
	$assignees = get_post_meta( $post_id, 'assignees' );
	if ( ! in_array( get_current_user_id(), $assignees ) ) {
		wp_safe_redirect( admin_url( 'admin.php?page=scoring-queue' ) );
		exit;
	}

	if ( ! empty( sanitize_text_field( $_POST['_score_submission_nonce'] ?? '' ) ) ) {
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
			'page' => 'score-submission',
			'post' => $submission_id,
		],
		admin_url( 'admin.php' )
	);
}

/**
 * Handle user-submitted scoring results.
 *
 * @return void
 */
function handle_scoring_results() : void {
	check_admin_referer( 'score-submission', '_score_submission_nonce' );

	$post_id = sanitize_text_field( $_REQUEST['post'] ?? null );

	if ( ! current_user_can( 'score_submissions' ) ) {
		return;
	}

	// Iterate over $_POST and save fields that starts with 'scoring_criteria_' string.
	$scoring_criteria = [];
	foreach ( $_POST as $key => $value ) {
		if ( strpos( $key, 'scoring_criteria_' ) === 0 ) {
			$scoring_criteria[ $key ] = $value;
		}
	}
	$additional_comment = sanitize_text_field( $_POST['additional_scoring_comment'] ?? '' );

	$results = [
		'scoring' => $scoring_criteria,
		'additional_comment' => $additional_comment,
	];

	Post_Type\update_translations( $post_id, $_POST['translated_fields'] );
	add_scoring_comment( $post_id, $results, get_current_user_id() );
	wp_safe_redirect( admin_url( 'admin.php?page=scoring-queue' ) );
	exit;
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
		'comment_content' => null,
		'comment_meta' => [
			'given_score' => $scoring_content,
			'additional_comment' => $results['additional_comment'] ?? null,
			'scoring_phase' => get_site_option( 'contest_status' ),
		],
		'comment_status' => 'approve',
		'user_id' => $user_id,
	] );

	// Update the submission overall weighted score for current contest phase.
	$submission_current_phase_score = get_submission_score( $submission_id );
	$current_contest_phase = get_site_option( 'contest_status' );
	update_post_meta( $submission_id, 'score_' . $current_contest_phase, $submission_current_phase_score['submission_score'] );
}

/**
 * Get user assigned score on the specified post, for current contest phase.
 *
 * @param int $submission_id Post ID of the submission to retrieve results for.
 * @param int $user_id_id User ID which inserted scoring comments.
 *
 * @return array Scoring results given by user
 *  @var array  'criteria'           All scores given by user to each scoring fields.
 *  @var string 'additional_comment' Free-text message field for additional comments.
  */
function get_submission_score_given_by_user( $submission_id, $user_id ) : ?array {
	$comments = get_comments( [
		'post_id' => $submission_id,
		'type' => COMMENT_TYPE,
		'agent' => COMMENT_AGENT,
		'status' => 'approve',
		'user_id' => $user_id,
		'meta_query' => [
			[
				'key' => 'scoring_phase',
				'value' => get_site_option( 'contest_status' ),
			],
		],
	] );

	$comment = end( $comments );
	if ( ! $comment ) {
		return null;
	}

	$given_score = [];
	$given_score['criteria'] = json_decode( get_comment_meta( $comment->comment_ID, 'given_score', true ), true );

	// not natural values should not be here, but we never know.
	$given_score['criteria'] = array_map( 'absint', $given_score['criteria'] );

	$given_score['additional_comment'] = get_comment_meta( $comment->comment_ID, 'additional_comment', true );

	return $given_score;
}

/**
 * Get the overall (phase-specific) score for the provided post.
 *
 * @param int $submission_id Post ID of the submission to retrieve results for.
 * @param int $user_id User ID which inserted scoring comments.
 *
 * @return array|null Scoring results, or null if no results found.
 */
function get_submission_score( $submission_id, $user_id = null ) {

	$comment_search_args = [
		'post_id' => $submission_id,
		'type' => COMMENT_TYPE,
		'agent' => COMMENT_AGENT,
		'status' => 'approve',
		'meta_query' => [
			[
				'key' => 'scoring_phase',
				'value' => get_site_option( 'contest_status' ),
			],
		],
	];

	if ( $user_id !== null ) {
		$comment_search_args['user_id'] = $user_id;
	}

	$comments = get_comments( $comment_search_args );
	if ( empty( $comments ) ) {
		return null;
	}

	/*
		Using the comments fetched to update the number of scorers that
		already scored this submission, and the completion for the phase.
	*/
	if ( $user_id === null ) {
		$current_contest_phase = get_site_option( 'contest_status' );

		// Storing scorers count.
		update_post_meta( $submission_id, "scorer_count_{$current_contest_phase}", count( $comments ) );

		// Storing the reason between scorers count and needed scorers to allow sorting by this column.
		update_post_meta( $submission_id, "score_completion_{$current_contest_phase}", count( $comments ) / SCORERS_NEEDED_EACH_PHASE[ $current_contest_phase ] );
	}

	$total_submission_score = 0;

	foreach ( $comments as $comment ) {
		$comment_score_content = json_decode( get_comment_meta( $comment->comment_ID, 'given_score', true ), true );
		$total_submission_score += calculate_weighted_score( $comment_score_content );
	}

	$weighted_score = [
		'submission_score' => $total_submission_score / count( $comments ),
		'overall' => 0,
		'by_category' => [],
	];

	if ( $user_id ) {
		$weighted_score['overall'] = $weighted_score['submission_score'];

		foreach ( SCORING_CRITERIA as $category_id => $value ) {
			$weighted_score['by_category'][ $category_id ] = calculate_score_for_category( $comment_score_content, $category_id );
		}
	}

	return $weighted_score;
}

/**
 * Calculate the weighted sum of all factors in a scoring comment.
 *
 * @param [] $given_score Scoring details, as saved as comment content.
 * @return float Weighted score, from 1-10.
 */
function calculate_weighted_score( $given_score ) {
	$weighted_score = 0;

	foreach ( SCORING_CRITERIA as $category_id => $value ) {
		$weighted_score += calculate_score_for_category( $given_score, $category_id ) * $value['weight'];
	}

	return $weighted_score;
}

/**
 * Calculate the average score for a category in a score comment.
 *
 * @param [] $given_score Full score details, as sabed to comment meta.
 * @param string $category_key Key for the category being queried.
 * @return float Average of the scores in the requested category.
 */
function calculate_score_for_category( $given_score, $category_key ) {
	$category = SCORING_CRITERIA[ $category_key ];

	// Get the values which make up this category's score.
	$values = array_map(
		function ( $criteria_key ) use ( $given_score, $category_key ) {
			return $given_score[ "scoring_criteria_{$category_key}_{$criteria_key}" ] ?? 0;
		},
		array_keys( $category['criteria'] )
	);

	if ( empty( $values ) ) {
		return 0;
	}

	return array_sum( $values ) / count( $values );
}

/**
 * Inactivate all past comments from the user for current phase, so we don't sum them but keep the record.
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
		'meta_query' => [
			[
				'key' => 'scoring_phase',
				'value' => get_site_option( 'contest_status' ),
			],
		],
	] );

	foreach ( $comments as $comment ) {
		wp_set_comment_status( $comment->comment_ID, 'hold' );
	}
}

/**
 * Get all members of the scoring panel.
 *
 * @return WP_User[] All users with the scoring panel or panelist lead roles.
 */
function get_scoring_panel_members() {
	return get_users( [ 'role__in' => [ PANELIST_USER_ROLE, PANEL_LEAD_USER_ROLE ] ] );
}

/*
 * Register meta_orderby for the phase queue list table.
 *
 * @param WP_Query $wp_query The WP_Query object.
 *
 * @return void
 */
function register_meta_orderby( $query ) : void {
	$orderby = $query->get( 'orderby');

	$custom_post_statuses = get_post_stati( [
		'_builtin' => false,
		'internal' => false,
	], 'objects' );

	foreach ( $custom_post_statuses as $custom_post_status ) {
		if ( $orderby === "col_{$custom_post_status->name}_score" ) {
			$query->set( 'meta_key', 'score_' . $custom_post_status->name );
			$query->set( 'orderby', 'meta_value_num' );
		} elseif ( $orderby === "col_{$custom_post_status->name}_completion" ) {
			$query->set( 'meta_key', 'score_completion_' . $custom_post_status->name );
			$query->set( 'orderby', 'meta_value_num' );
		}
	}
}

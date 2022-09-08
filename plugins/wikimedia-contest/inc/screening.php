<?php
/**
 * Screening results for submissions.
 *
 * Screening results will be stored in the comments table using the 'workflow'
 * comment type. We use the 'comment_agent' field in that table to mark these comments as screening
 *
 * @package wikimedia-contest;
 */

namespace Wikimedia_Contest\Screening;

use Wikimedia_Contest\Post_Type;
use Wikimedia_Contest\Screening_Queue_List_Table;

/**
 * User role for screeners.
 *
 * @var string
 */
const USER_ROLE = 'screener';

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
const COMMENT_AGENT = 'screening_result';

/**
 * Bootstrap screening results related functionality.
 */
function bootstrap() {
	add_action( 'init', __NAMESPACE__ . '\\register_screener_role' );
	add_action( 'init', __NAMESPACE__ . '\\support_editorial_comments' );
	add_action( 'admin_menu', __NAMESPACE__ . '\\register_screening_queue_menu_pages' );
	add_action( 'comments_clauses', __NAMESPACE__ . '\\add_agent_fields_to_query', 10, 2 );
	add_action( 'wikimedia_contest_inserted_submission', __NAMESPACE__ . '\\inserted_submission', 10, 2 );
	add_action( 'wikimedia_contest_added_screening_result', __NAMESPACE__ . '\\maybe_update_submission_status' );
	add_filter( 'rest_comment_query', __NAMESPACE__ . '\\allow_custom_statuses_in_workflows_query' );
	add_filter( 'pre_comment_approved', __NAMESPACE__ . '\\handle_custom_comment_approved_status', 10, 2 );
}

/**
 * Register screener role.
 */
function register_screener_role() {
	$roles = get_option( 'wikimedia_contest_roles' ) ?: [];

	if ( ! isset( $roles['screener'] ) || $roles['screener'] < 2 ) {
		wpcom_vip_add_role(
			USER_ROLE,
			__( 'Screener', 'wikimedia-contest-admin' ),
			array_merge(
				get_role( 'subscriber' )->capabilities,
				[
					'screen_submissions' => true,
					'view_screening_results' => false,
				]
			)
		);

		foreach ( [ 'administrator', 'scoring_panel_lead' ] as $role ) {
			$role_object = get_role( $role );

			if ( $role_object ) {
				$role_object->add_cap( 'view_screening_results' );
			}
		}

		$roles['screener'] = 2;
		update_option( 'wikimedia_contest_roles', $roles );
	}
}

/**
 * Add the Screening Queue to the admin menu.
 *
 * If the user can edit submissions, this page will be a submenu page under the
 * Submissions header. Otherwise, it's a top-level menu item.
 */
function register_screening_queue_menu_pages() {
	add_menu_page(
		__( 'Screening Queue', 'wikimedia-contest-admin' ),
		__( 'Screening Queue', 'wikimedia-contest-admin' ),
		'screen_submissions',
		'screening-queue',
		__NAMESPACE__ . '\\render_screening_queue',
		'dashicons-yes-alt',
		3
	);
	add_submenu_page(
		'screening-queue',
		__( 'Screen Submission', 'wikimedia-contest-admin' ),
		__( 'Screen Submission', 'wikimedia-contest-admin' ),
		'screen_submissions',
		'screen-submission',
		__NAMESPACE__ . '\\render_screening_interface',
	);
}

/**
 * Render the Screening Queue page.
 */
function render_screening_queue() {
	require_once __DIR__ . '/class-screening-queue-list-table.php';
	$list_table = new Screening_Queue_List_Table();
	$list_table->prepare_items();

	echo '<div id="screening-queue" class="wrap">';
	echo '<h1 class="wp-heading-inline">' . esc_html__( 'Screening Queue', 'wikimedia-contest-admin' ) . '</h1>';
	echo '<hr class="wp-header-end">';

	$list_table->display();

	echo '</div>';
}

/**
 * Render the Screening interface.
 */
function render_screening_interface() {
	$post_id = $_REQUEST['post'] ?? null;

	if ( ! $post_id ) {
		wp_safe_redirect( admin_url( 'admin.php?page=screening-queue' ) );
	}

	if ( ! empty( $_POST['_screen_submission_nonce'] ) ) {
		handle_screening_results();
	}

	require_once dirname( __DIR__ ) . '/templates/screening-interface.php';
}

/**
 * Get a link to edit a submission post.
 *
 * The screening link is different depending on whether the user can edit or not.
 *
 * @param int $submission_id Submission ID.
 * @return string Screening interface URL for this post.
 */
function get_screening_link( $submission_id ) {
	return add_query_arg(
		[
			'page' => 'screen-submission',
			'post' => $submission_id,
		],
		admin_url( 'admin.php' )
	);
}

/**
 * Handle user-submitted screening results.
 */
function handle_screening_results() {
	check_admin_referer( 'screen-submission', '_screen_submission_nonce' );

	$post_id = $_REQUEST['post'];

	if ( ! current_user_can( 'screen_submissions' ) ) {
		return;
	}

	$flags = array_intersect_key( $_POST['moderation-flags'] ?? [], get_moderation_flags() );
	$is_invalid = ! empty( $_POST['moderation-invalid'] ) || count( $flags );

	$results = [
		'status' => $is_invalid ? 'ineligible' : 'eligible',
		'flags' => array_keys( $flags ),
	];

	add_screening_comment( $post_id, $results, get_current_user_id() );
	wp_safe_redirect( admin_url( 'admin.php?page=screening-queue' ) );
}

/**
 * Ensure that the post type registers support for editorial comments.
 *
 * In order to allow for easier access to an edit trail for screening and
 * scoring on these posts, we'll reuse the 'workflow' comment_type, which
 * enables the Editorial Comments metabox.
 */
function support_editorial_comments() {
	add_post_type_support( Post_Type\SLUG, 'editorial-comments' );
}

/**
 * Define screening flags available to screening results.
 *
 * @return string[] Key-value array of screening flags to human-readable values.
 */
function get_available_flags() {
	return [
		// Automatic checks.
		'sound_too_short' => __( '< 1s duration', 'wikimedia-contest-admin' ),
		'sound_too_long' => __( '> 4s duration', 'wikimedia-contest-admin' ),
		'bitrate_too_low' => __( 'Bitrate too low', 'wikimedia-contest-admin' ),
	];
}

/**
 * Define moderation flags available in screeners' interface.
 *
 * @return string[] Key-value array of screening flags to hu/**
 */
function get_moderation_flags() {
	// Flags which are set by screeners.
	return [
		'sound_too_long' => __( 'More than four seconds', 'wikimedia-contest-admin' ),
		'sound_too_short' => __( 'Less than one second', 'wikimedia-contest-admin' ),
		'single_layer' => __( 'Single layer', 'wikimedia-contest-admin' ),
		'includes_spoken_words' => __( 'Includes spoken words ', 'wikimedia-contest-admin' ),
		'unacceptable_file_type' => __( 'Unacceptable file type (not OGG, WAV, MP3)', 'wikimedia-contest-admin' ),
		'unacceptable_quality' => __( 'Unacceptable quality', 'wikimedia-contest-admin' ),
		'suspect_copyright_infringment' => __( 'Suspected of copyright infringement', 'wikimedia-contest-admin' ),
		'suspect_license_infringement' => __( 'Suspected of license infringement', 'wikimedia-contest-admin' ),
		'vandalism' => __( 'Vandalism', 'wikimedia-contest-admin' ),
		'related_violence' => __( 'Related to violence', 'wikimedia-contest-admin' ),
		'related_gambling' => __( 'Related to gambling', 'wikimedia-contest-admin' ),
		'related_crime' => __( 'Related to crime', 'wikimedia-contest-admin' ),
		'related_sexual_behavior' => __( 'Related to sexual behaviour', 'wikimedia-contest-admin' ),
		'threatening_behavior' => __( 'Threatening behavior', 'wikimedia-contest-admin' ),
		'related_drugs' => __( 'Related to illicit drugs', 'wikimedia-contest-admin' ),
	];
}

/**
 * Insert a new screening result.
 *
 * @param int $submission_id Post ID of submission being screened.
 * @param array $results Screening result fields.
 *   @var string 'status'  Status recommended by screener ('eligible'/'ineligible'/null for no decision).
 *   @var array  'flags'   Moderation flags assigned to post.
 * @param int $user_id User ID for screener (0 for automatic flags).
 */
function add_screening_comment( int $submission_id, array $results, $user_id = 0 ) {

	// Validate the flags specified against the allowed list.
	$allowed_flags = array_merge(
		get_available_flags(),
		get_moderation_flags()
	);
	$flags = array_intersect( $results['flags'], array_keys( $allowed_flags ) );

	$comment_content = wp_json_encode( $results );

	wp_insert_comment( [
		'comment_post_ID' => $submission_id,
		'comment_type' => COMMENT_TYPE,
		'comment_agent' => COMMENT_AGENT,
		'comment_approved' => $results['status'],
		'comment_author' => get_userdata( $user_id )->user_nicename ?? get_bloginfo( 'name' ),
		'comment_content' => $comment_content,
		'comment_meta' => [
			'flags' => $flags,
		],
		'user_id' => $user_id,
	] );

	do_action( 'wikimedia_contest_added_screening_result', $submission_id );
}

/**
 * Get all screening comments on post.
 *
 * @param int $submission_id Submission ID.
 * @return WP_Comment[] Array of screening comments.
 */
function get_screening_comments( $submission_id ) {
	return get_comments( [
		'post_id' => $submission_id,
		'type' => COMMENT_TYPE,
		'agent' => COMMENT_AGENT,
		'status' => 'any',
	] );
}

/**
 * Get all screening results on the specified post.
 *
 * @param int $submission_id Post ID of the submission to retrieve results for.
 * @return array Array of result data.
 */
function get_screening_results( $submission_id ) {
	$comments = get_screening_comments( $submission_id );

	$results_format = [
		'decision' => [],
		'flags' => [],
	];

	$screening_results = array_reduce(
		$comments,
		function ( $results, $comment ) {

			// Push any 'eligible' / 'ineligible' votes into the decision field.
			if ( in_array( $comment->comment_approved, [ 'eligible', 'ineligible' ] ) ) {
				array_push( $results['decision'], $comment->comment_approved );
			}

			// Add all flags on the post to an array.
			$flags = get_comment_meta( $comment->comment_ID, 'flags', true ) ?: [];
			$results['flags'] = array_unique( array_merge( $results['flags'], $flags ) );
			return $results;
		},
		$results_format,
	);

	return $screening_results;
}

/**
 * Get details about screening comments.
 *
 * Returns an array, padded to 3 items, with 'name', 'date', 'status', and
 * 'reason' fields. Used for reporting and logs.
 *
 * @param int $submission_id Submission ID to query.
 * @return [] Details about screeners, time, and judgement.
 */
function get_screening_details( $submission_id ) {
	$screeners_comments = wp_list_filter(
		get_screening_comments( $submission_id ),
		[ 'user_id' => 0 ],
		'NOT'
	);

	// Structure of return value. Includes null for empty fields.
	$required_fields = [
		'comment_author' => null,
		'comment_date_gmt' => null,
		'comment_approved' => null,
		'comment_content' => null,
	];

	return array_pad(
		array_map(
			function ( $comment ) use ( $required_fields ) {
				return array_intersect_key( (array) $comment, $required_fields );
			},
			$screeners_comments
		),
		3,
		$required_fields
	);
}

/**
 * Add the "agent" field to the SQL query.
 *
 * @param string[] $sql_pieces Pieces of the WP Comment Query
 * @param WP_Comment_Query $comment_query Comment query.
 * @return string[] Updated comment query fields.
 */
function add_agent_fields_to_query( $sql_pieces, $comment_query ) {
	global $wpdb;

	if ( ! empty( $comment_query->query_vars['agent'] ) ) {
		$sql_pieces['where'] .= $wpdb->prepare( ' AND comment_agent = %s', $comment_query->query_vars['agent'] );
	}

	return $sql_pieces;
}

/**
 * Assign screening flags to newly inserted entry, if needed.
 *
 * @param [] $post_data Post data of newly inserted submission.
 * @param in $post_id ID of new submission.
 */
function inserted_submission( $post_data, $post_id ) {
	$audio_meta = $post_data['meta_input']['audio_file_meta'];

	if ( ! $audio_meta ) {
		return;
	}

	$flags = [];

	if ( $audio_meta['duration'] < 1 ) {
		$flags[] = 'sound_too_short';
	}

	if ( $audio_meta['duration'] > 4 ) {
		$flags[] = 'sound_too_long';
	}

	// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	if ( $audio_meta['sampleRate'] * 32 < 192 * 1024 ) {
		$flags[] = 'bitrate_too_low';
	}

	if ( $flags ) {
		add_screening_comment( $post_id, [ 'flags' => $flags ] );
	}
}

/**
 * Update the submission status after two reviewers have screened it.
 *
 * Once there are two reviews in agreement, the submission should be
 * automatically moved to the next stage: either ineligible or into the first
 * scoring phase.
 *
 * @param int $submission_id Submission post ID.
 */
function maybe_update_submission_status( $submission_id ) {
	$results = get_screening_results( $submission_id );
	$counts = array_count_values( $results['decision'] );

	switch ( array_search( 2, $counts ) ) {
	case 'ineligible':
		return wp_update_post( [
			'ID' => $submission_id,
			'post_status' => 'ineligible',
		] );
	case 'eligible':
		return wp_update_post( [
			'ID' => $submission_id,
			'post_status' => 'scoring_phase_1',
		] );
	}
}

/**
 * Allow our custom statuses here to show up in the admin meta box.
 *
 * @param [] $query_args Args array passed to REST workflows query.
 * @return [] Updated query args.
 */
function allow_custom_statuses_in_workflows_query( $query_args ) {
	if ( $query_args['type'] === COMMENT_TYPE ) {
		$query_args['status'] = 'any';
	}

	return $query_args;
}

/**
 * Support custom values for "comment_approved".
 *
 * By default wp_insert_comment sets all positive values to 1. We want to
 * support "eligible" and "ineligible" here.
 *
 * @param int|string $approved Comment approved status.
 * @param [] $commentdata Comment data array.
 * @return int|string The updated comment approved status.
 */
function handle_custom_comment_approved_status( $approved, $commentdata ) {
	if ( $commentdata['comment_type'] === COMMENT_TYPE && $commentdata['comment_agent'] === COMMENT_AGENT ) {
		$approved = $commentdata['comment_approved'];
	}

	return $approved;
}

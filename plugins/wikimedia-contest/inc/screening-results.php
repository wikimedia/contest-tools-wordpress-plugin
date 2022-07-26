<?php
/**
 * Screening results for submissions.
 *
 * Screening results will be stored in the comments table using the 'workflow'
 * comment type. We use the 'comment_agent' field in that table to mark these comments as screening
 *
 * @package wikimedia-contest;
 */

namespace Wikimedia_Contest\Screening_Results;

use Wikimedia_Contest\Post_Type;

/**
 * Comment type used to store editorial comments.
 * @var string
 */
const COMMENT_TYPE = 'workflow';

/**
 * Name used in the comment agent to identify these comments.
 * @var string
 */
const COMMENT_AGENT = 'screening_result';

/**
 * Bootstrap screening results related functionality.
 */
function bootstrap() {
	add_action( 'init', __NAMESPACE__ . '\\support_editorial_comments' );
	add_action( 'comments_clauses', __NAMESPACE__ . '\\add_agent_fields_to_query', 10, 2 );
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
 * Insert a new screening result.
 *
 * @param int $submission_id Post ID of submission being screened.
 * @param string? $status Status recommended by screener ('eligible'/'ineligible'/null for no decision)
 * @param array $flags Flags to assign to post.
 */
function add_screening_comment( int $submission_id, $status = 'none', array $flags = [] ) {
	// Validate the flags specified against the allowed list.
	$allowed_flags = get_available_flags();

	$flags = array_intersect( $flags, array_keys( $allowed_flags ) );

	$comment_content = wp_json_encode( [
		'status' => $status,
		'flags' => $flags,
	] );

	wp_insert_comment( [
		'comment_post_ID' => $submission_id,
		'comment_type' => COMMENT_TYPE,
		'comment_agent' => COMMENT_AGENT,
		'comment_approved' => $status,
		'comment_content' => $comment_content,
		'comment_meta' => [
			'flags' => $flags,
		],
	] );
}

/**
 * Get all screening results on the specified post.
 *
 * @param int $submission_id Post ID of the submission to retrieve results for.
 * @return array Array of result data.
 */
function get_screening_results( $submission_id ) {
	$comments = get_comments( [
		'post_id' => $submission_id,
		'type' => COMMENT_TYPE,
		'agent' => COMMENT_AGENT,
		'status' => [ 'eligible', 'ineligible', null ],
	] );

	$results_format = [
		'decision' => [],
		'flags' => [],
	];

	$screening_results = array_reduce(
		$comments,
		function ( $results, $comment ) {
			if ( in_array( $comment->comment_approved, [ 'eligible', 'ineligible' ] ) ) {
				array_push( $results['decision'], $comment->comment_approved );
			}

			$flags = get_comment_meta( $comment->comment_ID, 'flags', true ) ?: [];
			$results['flags'] = array_unique( array_merge( $results['flags'], $flags ) );

			return $results;
		},
		$results_format,
	);

	return $screening_results;
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
		$sql_pieces['where'] .= $wpdb->prepare_sql( ' AND comment_agent = %s', $comment_query->query_vars['agent'] );
	}

	return $sql_pieces;
}

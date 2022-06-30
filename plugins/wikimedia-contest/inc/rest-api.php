<?php
/**
 * REST API for Wikimedia Contests
 *
 * @package wikimedia-contest
 */

namespace Wikimedia_Contest\Rest_Api;

use function Wikimedia_Contest\wc_sanitize_phone_number;
use Wikimedia_Contest\Network_Library;

// REST API constants.
const SUBMISSION_API_NAMESPACE = 'wikimedia-contest/v1';
const SUBMISSION_API_ROUTE = 'submission';

/**
 * Bootstrap REST API functionality.
 */
function bootstrap() {
	add_action( 'rest_api_init', __NAMESPACE__ . '\\register_submission_api_routes' );
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_submission_form_scripts' );
}

/**
 * Build a success message regarding to the submission saving.
 *
 * @param int $main_blog_id ID of main blog where the submission were inserted.
 * @param int $post_id Post ID of submission being saved.
 *
 * @return array Message including submission information.
 */
function submission_success_message( int $main_blog_id, int $post_id ) : array {

	// Switch to the main site to retreive the submission post data.
	switch_to_blog( $main_blog_id );
	$submission_post = get_post( $post_id );
	$submission_date = $submission_post->post_date;
	$submission_code = get_post_meta( $post_id, 'unique_code', true );
	restore_current_blog();

	return [
		'status'                  => 'success',
		'message'                 => __( 'Submission saved successfully.', 'wikimedia-contest' ),
		'submission_date_message' => sprintf( __( 'Submission date: %s', 'wikimedia-contest' ), $submission_date ),
		'submission_code_message' => sprintf( __( 'Submission unique code: %s', 'wikimedia-contest' ), $submission_code ),
	];
}

/**
 * Build an error message regarding to the submission parsing.
 *
 * @return array Error message information.
 */
function submission_error_message() {
	return [
		'status'  => 'error',
		'message' => __( 'Error processing the submission. Submission insert error.', 'wikimedia-contest' ),
	];
}

/**
 * Process submission form, handle uploaded file and save submission
 * to database.
 *
 * @param \WP_REST_Request $request Request object.
 *
 * @return false|void
 */
function process_submission_form( \WP_REST_Request $request ) {

	// Submission nonce check.
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $request->get_param( '_submissionnonce' ) ) ), 'wp_rest' ) ) {
		return rest_ensure_response( __( 'Error processing the submission, please try again. Submission nonce error.', 'wikimedia-contest' ) );
	}

	// File upload nonce check.
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $request->get_param( '_filesuploadnonce' ) ) ), 'files_upload' ) ) {
		return rest_ensure_response( __( 'Error processing the submission, please try again. File upload nonce error.', 'wikimedia-contest' ) );
	}

	// Placeholder for submission unique code - TBD.
	$submission_unique_code = md5( microtime( true ) );

	// File upload.
	$uploaded_files = $request->get_file_params();
	$upload_dir = wp_upload_dir()['basedir'];
	$file_location = $upload_dir . '/' . $submission_unique_code;

	if ( isset( $uploaded_files['audio_file'] ) ) {
		if ( move_uploaded_file( sanitize_text_field( wp_unslash( $uploaded_files['audio_file']['tmp_name'] ?? '' ) ), $file_location ) ) {
			$audio_path = wp_upload_dir()['baseurl'] . '/' . $submission_unique_code;
		}
	}

	$submission_post = [
		'post_title'  => sprintf( 'Submission %s', $submission_unique_code ),
		'post_status' => 'draft',
		'post_author' => 1,
		'post_type'   => 'submission',
		'meta_input'  => [
			'unique_code'             => $submission_unique_code,
			'wiki_username'           => sanitize_text_field( wp_unslash( $request->get_param( 'wiki_username' ) ?? '' ) ),
			'legal_name'              => sanitize_text_field( wp_unslash( $request->get_param( 'legal_name' ) ?? '' ) ),
			'date_birth'              => sanitize_text_field( wp_unslash( $request->get_param( 'date_birth' ) ?? '' ) ),
			'participant_email'       => sanitize_email( wp_unslash( $request->get_param( 'participant_email' ) ?? '' ) ),
			'phone_number'            => wc_sanitize_phone_number( sanitize_text_field( wp_unslash( $request->get_param( 'phone_number' ) ?? '' ) ) ),
			'audio_file_path'         => sanitize_text_field( wp_unslash( $audio_path ?? '' ) ),
			'authors_contributed'     => sanitize_textarea_field( wp_unslash( $request->get_param( 'authors_contributed' ) ?? '' ) ),
			'explanation_creation'    => sanitize_textarea_field( wp_unslash( $request->get_param( 'explanation_creation' ) ?? '' ) ),
			'explanation_inspiration' => sanitize_textarea_field( wp_unslash( $request->get_param( 'explanation_inspiration' ) ?? '' ) ),
		],
	];

	$post_data = Network_Library\insert_submission( $submission_post );
	if ( empty( $post_data ) ) {
		return rest_ensure_response( submission_error_message() );
	} else {
		return rest_ensure_response( submission_success_message( $post_data['blog_id'], $post_data['post_id'] ) );
	}
}

/**
 * Register REST API route to create a submission.
 *
 * @return void
 */
function register_submission_api_routes() {
	register_rest_route(
		SUBMISSION_API_NAMESPACE,
		SUBMISSION_API_ROUTE,
		[
			'methods'             => \WP_REST_Server::EDITABLE,
			'callback'            => __NAMESPACE__ . '\\process_submission_form',
			'permission_callback' => '__return_true',
		]
	);
}

/**
 * Enqueue ajax scripts for submission form.
 *
 * @return void
 */
function enqueue_submission_form_scripts() {
	wp_enqueue_script( 'submission-form', plugins_url( 'assets/js/submission-form.js', __FILE__ ), [ 'jquery' ], '1.0.0', true );
	wp_localize_script( 'submission-form', 'submission_form_ajax_object', [
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'files_upload_nonce' => wp_create_nonce( 'files_upload' ),
		'api_url'  => get_rest_url() . SUBMISSION_API_NAMESPACE . '/' . SUBMISSION_API_ROUTE,
	] );
}

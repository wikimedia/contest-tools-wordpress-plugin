<?php
/**
 * WordPress Backend Admin Ajax Functions
 *
 * @package wikimedia-contest
 */

namespace Wikimedia_Contest\Admin_Ajax;

/**
 * Bootstrap post-type related functionality.
 */
function bootstrap() {
	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_admin_cpt_status_change_scripts' );
	add_action( 'wp_ajax_update_submission_cpt_status', __NAMESPACE__ . '\\update_submission_status' );
}

/**
 * Enqueue Submission CPT Admin scripts.
 *
 * @return void
 */
function enqueue_admin_cpt_status_change_scripts() : void {
	wp_enqueue_script( 'submission_cpt_scripts', plugins_url( 'assets/js/submission-cpt.js', __FILE__ ), [ 'jquery' ], '1.0.0', true );
	wp_localize_script( 'submission_cpt_scripts', 'submission_cpt_scripts_ajax_object', [
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'security' => wp_create_nonce( 'status-change-nonce' ),
	] );
}

/**
 * Update Submission CPT status.
 *
 * @return void
 */
function update_submission_status() : void {

	// Theoretically shouldn't be possible to reach this on other sites, but just in case.
	$site_id = get_current_blog_id();
	if ( ! is_main_site( $site_id ) ) {
		return;
	}

	// Nonce check.
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_status_change_nonce'] ?? '' ) ), 'status-change-nonce' ) ) {
		wp_send_json_error( [
			'message' => 'Error processing the request, nonce error.',
		] );
	}

	if ( ! empty( $_POST['post_id'] ) ) {
		$submission_post = get_post( sanitize_text_field( wp_unslash( $_POST['post_id'] ) ) );
		if ( ! $submission_post ) {
			wp_send_json_error( [
				'message' => 'Error processing the request.',
			] );
		}
	} else {
		wp_send_json_error( [
			'message' => 'Error processing the request, nonce error.',
		] );
	}

	$allowed_status = [
		'draft',
		'eligible',
		'ineligible',
	];

	if ( ! empty( $_POST['new_post_status'] ) && in_array( sanitize_text_field( wp_unslash( $_POST['new_post_status'] ) ), $allowed_status, true ) ) {
		$new_post_status = sanitize_text_field( wp_unslash( $_POST['new_post_status'] ) );

		$submission_post->post_status = $new_post_status;

		if ( wp_update_post( $submission_post ) ) {
			wp_send_json_success( [
				'message' => 'Success updating post status.',
			] );
		} else {
			wp_send_json_error( [
				'message' => 'Error updating post status.',
			] );
		}
	} else {
		wp_send_json_error( [
			'message' => 'Error processing the request, nonce error.',
		] );
	}
}

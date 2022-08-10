<?php
/**
 * Logic for tracking contest entrants.
 *
 * Each contest entrant is only allowed to enter once, but there are a few
 * exceptions. When first submitting a contest entry, the user should be able to
 * enter three different submissions. And contest admins need a way to clear an
 * entrant's record, so that they can resubmit in case of technical
 * difficulties.
 *
 * @package wikimedia-contest;
 */

namespace Wikimedia_Contest\Submitters;

const OPTION_NAME = 'submitter_email_addresses';

/**
 * Attach all required functionality.
 */
function bootstrap() {
	add_action( 'gform_pre_render', __NAMESPACE__ . '\\identify_submitter_email_field' );
	add_action( 'wikimedia_contest_inserted_submission', __NAMESPACE__ . '\\record_submitter_email' );
	add_action( 'wp_ajax_check_email_address', __NAMESPACE__ . '\\ajax_check_email_address' );
	add_action( 'wp_ajax_nopriv_check_email_address', __NAMESPACE__ . '\\ajax_check_email_address' );
}

/**
 * Define a JS variable with the field name of the submitter_email input field.
 *
 * @param Form $form The form being rendered.
 * @return Form (unchanged)
 */
function identify_submitter_email_field( $form ) {
	$field = current( wp_list_filter( $form['fields'], [ 'adminLabel' => 'submitter_email' ] ) );

	if ( $field ) {
		$field_id = "input_{$field->formId}_{$field->id}";
		$ajax_url = network_site_url( 'wp-admin/admin-ajax.php' );

		echo "\r\n" . '<script type="text/javascript">var submitterEmailField = ' .
			json_encode( [
				'ajaxurl' => $ajax_url,
				'field_id' => $field_id,
			] ) . ';</script>';
	}

	return $form;
}
/**
 * Record the submitter email address so it can be used to validate future submissions.
 *
 * @param WP_Post $submission_post
 */
function record_submitter_email( $submission_post ) {
	$used_email_addresses = get_site_option( OPTION_NAME );

	if ( ! $used_email_addresses ) {
		add_site_option( OPTION_NAME, [] );
		$used_email_addresses = [];
	}

	$used_email_addresses[] = $submission_post['meta_input']['submitter_email'];

	update_site_option( OPTION_NAME, array_filter( array_unique( $used_email_addresses ) ) );
}

/**
 * Ajax query to see if an email address is already used.
 *
 * @return void Outputs a JSON response and exits.
 */
function ajax_check_email_address() {

	$email_address = sanitize_text_field( wp_unslash( $_REQUEST['email'] ) );

	$validated = is_email( $email_address );

	// Validate that the email address looks like a valid email.
	if ( empty( $validated ) ) {
		wp_send_json_error( __( 'Invalid email address.', 'wikimedia-contest' ), 403 );
	}

	// Check if the email address has already been used.
	if ( is_email_used( $email_address ) ) {
		wp_send_json_error( __( 'That email address is already used.', 'wikimedia-contest' ), 403 );
	}

	wp_send_json_success();
}

/**
 * Check if an email address is already used.
 *
 * @param string $email_address Email to look up.
 * @return bool True if the email address has already been used.
 */
function is_email_used( $email_address ) {
	$used_email_addresses = get_site_option( OPTION_NAME, [] );

	return in_array( $email_address, $used_email_addresses, true );
}

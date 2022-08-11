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
	add_action( 'admin_menu', __NAMESPACE__ . '\\add_email_reset_page' );
	add_filter( 'gform_field_validation', __NAMESPACE__ . '\\validate_email_not_used', 10, 4 );
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
 * @param [] $submission_post Post data as saved.
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

/**
 * Add a menu page for administrators to reset used email addresses.
 */
function add_email_reset_page() {
	add_submenu_page(
		'edit.php?post_type=submission',
		esc_html__( 'Submitter email addresses', 'wikimedia-contest-admin' ),
		esc_html__( 'Submitters', 'wikimedia-contest-admin' ),
		'manage_options',
		'submitter-emails',
		__NAMESPACE__ . '\\render_submitter_emails_page'
	);

	add_allowed_options( [ 'options' => [ OPTION_NAME ] ] );
}

/**
 * Render the admin page to manage used email addresses.
 */
function render_submitter_emails_page() {
	$used_email_addresses = get_site_option( OPTION_NAME, [] );

	// The form submits to the same page, so handle changes here.
	if ( ! empty( $_REQUEST['update_submitter_emails'] ) ) {
		handle_admin_list_updates();
	}

	echo '<div class="wrap">';

	echo '<h1 class="wp-heading-inline">' . esc_html__( 'Submitter Email Addresses', 'wikimedia-contest-admin' ) . '</h1>';

	if ( $used_email_addresses ) {

		?>
		<form action="">
			<?php wp_nonce_field( 'manage_submitter_emails' ); ?>
			<input type="hidden" name="post_type" value="submission" />
			<input type="hidden" name="page" value="submitter-emails" />
			<input type="hidden" name="update_submitter_emails" value="true" />

			<ul>
				<?php
				foreach ( $used_email_addresses as $email_address ) {
					?>
					<li>
						<label>
							<input
								type="checkbox"
								name="<?php echo OPTION_NAME; ?>[]"
								value="<?php echo esc_attr( $email_address ); ?>"
								<?php checked( is_email_used( $email_address ) ); ?>
							>
							<?php echo esc_html( $email_address ); ?>
						</label>
					</li>
					<?php
				}
				?>
			</ul>

			<input type="submit" class="button button-primary" value="<?php echo esc_attr__( 'Save Changes' ); ?>" />
		</form>
		<?php

	} else {
		echo '<p>' . esc_html__(
			'There are no email addresses recorded as having submitted their contest entry.',
			'wikimedia-contest-admin'
		) . '</p>';
	}
}

/**
 * Update the used emails option in response to an admin form submission.
 */
function handle_admin_list_updates() {

	// Ensure that user is submitting this form from the admin page.
	check_admin_referer( 'manage_submitter_emails' );

	if ( ! current_user_can( 'publish_submissions' ) ) {
		echo 'nuh-uh!';
		return;
	}

	$email_addresses = $_REQUEST[ OPTION_NAME ];

	$updated = update_site_option(
		OPTION_NAME,
		array_filter(
			array_map(
				'is_email',
				$email_addresses
			)
		)
	);

	if ( $updated ) {
		echo '<div class="notice notice-warning is-dismissable"><p>' .
			esc_html__( 'Updated list of used email addresses saved.', 'wikimedia-contest-admin' ) .
			'</p></div>';
	}
}

/**
 * Server-side validation for email form field.
 *
 * Marks the email field as invalid on form submission is address is already
 * used.
 *
 * @param [] $result Validation result being filtered.
 * @param string $value User input for the field.
 * @param Form $form Form object.
 * @param Field $field Current field object.
 */
function validate_email_not_used( $result, $value, $form, $field ) {
	if ( $field->adminLabel !== 'submitter_email' ) {
		return $result;
	}

	if ( is_email_used( $value ) ) {
		$result['is_valid'] = false;
		$result['message'] = __( 'This email address has already been used for a submission', 'wikimedia-contest-admin' );
	}

	return $result;
}

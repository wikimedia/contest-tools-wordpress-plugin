<?php
/**
 * Wikimedia Contest Network Settings
 *
 * @package wikimedia-contest
 */

namespace Wikimedia_Contest\Network_Settings;

/**
 * Bootstrap network functionality.
 */
function bootstrap() {
	add_action( 'network_admin_menu', __NAMESPACE__ . '\\add_menu_and_fields' );
	add_action( 'network_admin_edit_contest_settings_page', __NAMESPACE__ . '\\contest_settings_page_update' );
}

/**
 * Creates the sub-menu page and register the contest settings.
 */
function add_menu_and_fields() {
	add_submenu_page(
		'settings.php',
		__( 'Contest Settings', 'wikimedia-contest-admin' ),
		__( 'Contest', 'wikimedia-contest-admin' ),
		'manage_network_options',
		'contest_settings_page',
		__NAMESPACE__ . '\\render_settings_page'
	);

	add_settings_section(
		'contest_status_section',
		__( 'Contest phase', 'wikimedia-contest-admin' ),
		__NAMESPACE__ . '\\contest_status_section_content',
		'contest_settings_page'
	);

	register_setting( 'contest_settings_page', 'contest_status' );

	add_settings_field(
		'contest_status_field',
		__( 'Select phase', 'wikimedia-contest-admin' ),
		__NAMESPACE__ . '\\contest_status_field_content',
		'contest_settings_page',
		'contest_status_section'
	);
}

/**
 * Render the settings page.
 */
function render_settings_page() {
	?>

	<?php if ( isset( $_GET['updated'] ) && sanitize_text_field( wp_unslash( $_GET['updated'] ) ) ) : ?>
		<div id="message" class="updated notice is-dismissible">
			<p><?php esc_html_e( 'Contest options were saved.', 'wikimedia-contest-admin' ); ?></p>
		</div>
	<?php endif; ?>

	<div class="wrap">

		<h1><?php esc_html_e( 'Contest Settings', 'wikimedia-contest-admin' ); ?></h1>

		<form method="post" action="edit.php?action=contest_settings_page">
			<?php
			settings_fields( 'contest_settings_page' );
			do_settings_sections( 'contest_settings_page' );
			submit_button();
			?>
		</form>

	</div>
	<?php
}

/**
 * Contest status section content.
 */
function contest_status_section_content() {
	esc_html_e( 'Use the options below to select the current phase of the contest', 'wikimedia-contest-admin' );
}

/**
 * Contest status field content.
 */
function contest_status_field_content() {
	$contest_status = get_site_option( 'contest_status' );

	$custom_post_statuses = get_post_stati( [
		'_builtin' => false,
	], 'objects' );
	foreach ( $custom_post_statuses as $key => $custom_status ) {
		?>
		<label>
			<input
				type="radio"
				name="contest_status"
				value="<?php echo esc_attr( $key ); ?>"
				<?php checked( $contest_status, $custom_status->name ); ?>
			>
			<?php echo esc_html( $custom_status->label ); ?>
		</label>
		<br/>
		<?php
	}
}

/**
 * Update the contest settings.
 */
function contest_settings_page_update() {
	check_admin_referer( 'contest_settings_page-options' );

	$contest_status = sanitize_text_field( $_POST['contest_status'] );
	if ( $contest_status ) {
		update_site_option( 'contest_status', $contest_status );
	}

	wp_safe_redirect( add_query_arg( 'updated', 'true', network_admin_url( 'settings.php?page=contest_settings_page' ) ) );
	exit;
}

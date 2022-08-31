<?php
/**
 * Plugin Name: Wikimedia Contest
 * Description: Manage the Wikimedia Contests
 * Version:     1.0.0
 * Author:      Human Made
 * Author URI:  https://github.com/humanmade
 * Text Domain: wikimedia-contest-admin
 * Domain Path: /languages
 *
 * @package wikimedia-contest
 * @version 1.0.0
 */

namespace Wikimedia_Contest;

require_once __DIR__ . '/inc/functions.php';

require_once __DIR__ . '/inc/workflows-triggers.php';
Workflows_Triggers\bootstrap();

require_once __DIR__ . '/inc/network-library.php';
Network_Library\bootstrap();

require_once __DIR__ . '/inc/post-type.php';
Post_Type\bootstrap();

require_once __DIR__ . '/inc/submitters.php';
Submitters\bootstrap();

require_once __DIR__ . '/inc/screening.php';
Screening\bootstrap();

require_once __DIR__ . '/inc/scoring.php';
Scoring\bootstrap();

require_once __DIR__ . '/inc/assets.php';
Assets\bootstrap();

require_once __DIR__ . '/inc/editor.php';
Editor\bootstrap();

require_once __DIR__ . '/inc/gravity-forms.php';
Gravity_Forms\bootstrap();

require_once __DIR__ . '/inc/languages.php';
Languages\bootstrap();

require_once __DIR__ . '/inc/reporting.php';
Reporting\bootstrap();

require_once __DIR__ . '/inc/network-settings.php';
Network_Settings\bootstrap();

require_once __DIR__ . '/inc/bulk-actions.php';
Bulk_Actions\bootstrap();

// Require core files in the admin that may not be loaded by default.
if ( is_admin() ) {
	if ( ! class_exists( 'WP_Post_List_Table' ) ) {
		require_once ABSPATH . 'wp-admin/includes/screen.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-posts-list-table.php';
	}
}

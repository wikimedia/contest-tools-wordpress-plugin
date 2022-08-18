<?php
/**
 * Editor-related functionality for the contest.
 *
 * @package wikimedia-contest
 */

namespace Wikimedia_Contest\Assets;

use Asset_Loader;
use Asset_Loader\Manifest;

/**
 * Bootstrap all editor functionality.
 */
function bootstrap() {
	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_assets' );
}

/**
 * Enqueue block editor assets.
 *
 * @return void
 */
function enqueue_assets() : void {
	$manifest = Manifest\get_active_manifest( [
		dirname( __DIR__ ) . '/build/development-asset-manifest.json',
		dirname( __DIR__ ) . '/build/production-asset-manifest.json',
	] );

	Asset_Loader\enqueue_asset(
		$manifest,
		'adminStyles.css',
		[
			'handle' => 'contest-admin-styles',
		]
	);
}

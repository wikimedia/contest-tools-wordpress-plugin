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
	add_action( 'init', __NAMESPACE__ . '\\register_assets' );
	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_assets' );
}

/**
 * Register dependency assets used in frontend and backend scripts.
 */
function register_assets() {
	// Dependency.
	wp_register_script(
		'dompurify',
		plugin_dir_url( __DIR__ ) . "assets/purify.min.js",
		[],
		'2.4.3',
		true
	);
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
		'admin.js',
		[
			'handle' => 'contest-admin-scripts',
			'dependencies' => [ 'dompurify' ],
		]
	);

	Asset_Loader\enqueue_asset(
		$manifest,
		'adminStyles.css',
		[
			'handle' => 'contest-admin-styles',
		]
	);
}

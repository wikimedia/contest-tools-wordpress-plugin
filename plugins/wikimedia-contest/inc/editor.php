<?php
/**
 * Editor-related functionality for the contest.
 *
 * @package wikimedia-contest
 */

namespace Wikimedia_Contest\Editor;

use Asset_Loader;
use Asset_Loader\Manifest;

/**
 * Bootstrap all editor functionality.
 */
function bootstrap() {
	add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\enqueue_block_editor_assets' );
}

/**
 * Enqueue block editor assets.
 * @return void
 */
function enqueue_block_editor_assets() : void {

	$manifest = Manifest\get_active_manifest( [
		dirname( __DIR__ ) . '/build/development-asset-manifest.json',
		dirname( __DIR__ ) . '/build/production-asset-manifest.json',
	] );

	Asset_Loader\enqueue_asset(
		$manifest,
		'editor.js',
		[
			'dependencies' => [
				'wp-dom-ready',
				'wp-i18n',
				'wp-blocks',
				'wp-block-editor',
				'wp-components',
				'wp-compose',
				'wp-element',
				'wp-hooks',
				'wp-token-list',
			],
			'handle' => 'wikimedia_contest_editor',
		]
	);
}

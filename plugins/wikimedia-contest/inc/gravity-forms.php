<?php
/**
 * Gravity Forms integration
 *
 * @package wikimedia-contest
 */

namespace Wikimedia_Contest\Gravity_Forms;

use Asset_Loader;
use Asset_Loader\Manifest;

/**
 * Bootstrap form functionality.
 */
function bootstrap() {
	add_filter( 'allowed_block_types', __NAMESPACE__ . '\\filter_blocks', 20, 2 ); // After shiro theme defines the allowed blocks.
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_form_scripts' );
}

/**
 * Allow Gform blocks to be inserted in the editor.
 *
 * @param bool|string[] $allowed_blocks Array of allowed blocks.
 * @param \WP_Post      $post The post being edited.
 *
 * @return string[]|bool
 */
function filter_blocks( $allowed_blocks, \WP_Post $post ) {

	if ( $post->post_type === 'page' && is_array( $allowed_blocks ) ) {
		$allowed_blocks[] = 'gravityforms/form';
	}

	return $allowed_blocks;
}

/**
 * Enqueue submission form scripts.
 *
 * TODO: only enqueue on the page containing the submission form.
 */
function enqueue_form_scripts() {

	$manifest = Manifest\get_active_manifest( [
		dirname( __DIR__ ) . '/build/development-asset-manifest.json',
		dirname( __DIR__ ) . '/build/production-asset-manifest.json',
	] );

	Asset_Loader\enqueue_asset(
		$manifest,
		'submissionForm.js',
		[
			'dependencies' => [
				'gform_gravityforms',
				'wp-a11y',
				'wp-i18n',
			],
			'handle' => 'wikimedia_contest_submission_form',
		]
	);
}

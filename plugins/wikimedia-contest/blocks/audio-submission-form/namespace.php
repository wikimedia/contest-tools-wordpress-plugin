<?php
/**
 * Custom Block for Audio Submissions
 *
 * @package wikimedia-contest
 */

namespace Wikimedia_Contest\Blocks\Audio_Submission_Form;

/**
 * Bootstrap all block functionality.
 */
function bootstrap() {
	add_action( 'init', __NAMESPACE__ . '\\register_audio_submission_form', 10, 0 );
 	add_filter( 'allowed_block_types', __NAMESPACE__ . '\\filter_blocks_wikimedia_contest', 20, 2 );
}

/**
 * Register the audio submission form custom block.
 *
 * @return void
 */
function register_audio_submission_form() : void {
	register_block_type(
		__DIR__,
		[
			'render_callback' => __NAMESPACE__ . '\\render_block_audio_submission_form',
		]
	);
}

/**
 * Reenders the audio submission form custom block.
 *
 * @return string
 */
function render_block_audio_submission_form() : string {
	return '<h1>Audio Submission Form</h1>';
}

/**
 * Include in the array of allowed blocks the custom blocks
 * inserted by the Wikimedia Contest plugin.
 *
 * @param bool|string[] $allowed_blocks Array of allowed blocks.
 * @param \WP_Post      $post The post being edited.
 *
 * @return string[]
 */
function filter_blocks_wikimedia_contest( $allowed_blocks, \WP_Post $post ) : array {

	if ( $post->post_type === 'page' ) {
		$allowed_blocks[] = 'wikimedia-contest/audio-submission-form';
	}

	return $allowed_blocks;
}

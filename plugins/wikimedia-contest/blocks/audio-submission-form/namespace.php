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
}

/**
 * Register the block type from JSON.
 */
function register_audio_submission_form() {
	register_block_type(
		__DIR__,
		array(
			'render_callback' => __NAMESPACE__ . '\\render_block_audio_submission_form',
		)
	);
}

/**
 * Render the block output.
 */
function render_block_audio_submission_form() {
	return "<h1>Audio Submission Form</h1>";
}

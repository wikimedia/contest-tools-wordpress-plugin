<?php
/**
 * Custom Block for Audio Submissions
 *
 * @package wikimedia-contest
 */

namespace WikimediaContest\CustomBlocks\AudioSubmissionForm;

function register_audio_submission_form() {
	register_block_type(
		__DIR__,
		array(
			'render_callback' => __NAMESPACE__ . '\\render_block_audio_submission_form',
		)
	);
}
add_action( 'init', __NAMESPACE__ . '\\register_audio_submission_form', 10, 0 );

function render_block_audio_submission_form() {
	return "<h1>Audio Submission Form</h1>";
}

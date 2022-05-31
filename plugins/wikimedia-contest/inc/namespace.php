<?php
/**
 * Plugin initialization.
 *
 * @package wikimedia-contest
 */

namespace WikimediaContest;

function bootstrap() {
	add_action( 'init', __NAMESPACE__ . '\PostType\register_submission_custom_post_type', 0 );
	add_action( 'init', __NAMESPACE__ . '\PostType\register_submission_custom_post_statuses', 0);
	add_action( 'add_meta_boxes', __NAMESPACE__ . '\PostType\add_submission_box' );
	add_action( 'save_post_submission', __NAMESPACE__ . '\PostType\submission_save_meta', 10, 2 );
}

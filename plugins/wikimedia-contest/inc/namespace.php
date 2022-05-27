<?php
/**
 * Plugin initialization.
 *
 * @package wikimedia-contest
 */

namespace WikimediaContest;

function bootstrap() {
	add_action( 'add_meta_boxes', __NAMESPACE__ . '\\add_submission_box' );
	add_filter( 'wp_insert_post_data', __NAMESPACE__ . '\\submission_insert_post', 10, 2 );
	add_action( 'save_post_submission', __NAMESPACE__ . '\\submission_save_meta', 10, 2 );
	add_action( 'init', __NAMESPACE__ . '\\register_submission_custom_post_type', 0 );
	add_action( 'init', __NAMESPACE__ . '\\register_submission_custom_post_statuses', 0);
	add_filter( 'manage_submission_posts_columns', __NAMESPACE__ . '\\submission_filter_posts_columns' );
}

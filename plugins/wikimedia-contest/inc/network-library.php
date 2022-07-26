<?php
/**
 * Network Library for Submissions
 *
 * phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.switch_to_blog_switch_to_blog
 *
 * @package wikimedia-contest
 */

namespace Wikimedia_Contest\Network_Library;

use Inpsyde\MultilingualPress;

/**
 * Bootstrap network functionality.
 */
function bootstrap() {
	add_action( 'transition_post_status', __NAMESPACE__ . '\\transition_post_status', 10, 3 );
}

/**
 * Insert a submission on the main site.
 *
 * @param [] $post_data Post data to insert.
 * @return [] Array containing keys:
 *   @var int blog_id Site ID of main site.
 *   @var int post_id Post ID of inserted post on network site.
 */
function insert_submission( $post_data ) {
	$main_site_id = get_main_site_id();
	$current_site_id = get_current_blog_id();

	// Record the current site ID on the post.
	$post_data['meta_input']['_submission_site'] = $current_site_id;

	// Switch to the main site to insert the post.
	switch_to_blog( $main_site_id );
	$post_id = wp_insert_post( $post_data );

	/*
	 * Action fired immediately after a submission is inserted into the database.
	 *
	 * @param [] $post_data All data passed to wp_insert_post
	 * @param int $post_id ID of the submitted post.
	 */
	do_action( 'wikimedia_contest_inserted_submission', $post_data, $post_id );

	restore_current_blog();

	if ( ! $post_id ) {
		return [];
	}

	// Return information about the post created.
	return [
		'blog_id' => $main_site_id,
		'post_id' => $post_id,
	];
}

/**
 * Get a submission post, potentially translated into the current site's language.
 *
 * @param int $post_id ID (on the main site) of submission to fetch.
 * @return WP_Post Post: either source post on main site or it's translation on current site.
 */
function get_submission( $post_id ) {
	$current_site_id = get_current_blog_id();
	$main_site_id = get_main_site_id();
	$translations = MultilingualPress\translationIds( $post_id, 'post', $main_site_id );

	if ( array_key_exists( $current_site_id, $translations ) ) {
		return get_post( $translations[ $current_site_id ] );
	}

	$is_subsite = switch_to_blog( get_main_site_id() );
	$post = get_post( $post_id );

	if ( $is_subsite ) {
		restore_current_blog();
	}

	return $post;
}

/**
 * Fire appropriate hooks when a submisstion post transitions status.
 *
 * This hook is also used for new submission creation, where the old status is
 * "new" and the new status is "draft".
 *
 * The event is always fired from the main site, which is the canonical
 * location for submissions. But it schedules a follow-up event on the site a
 * post was initially created from, in order to send any notifications required
 * in the submitter's chosen language.
 *
 * @param string $new_status New post status.
 * @param string $old_status Previous status.
 * @param WP_Post $post Post being transitioned.
 */
function transition_post_status( $new_status, $old_status, $post ) {
	/*
	 * Only listen for status transitions on the main site. On localized sites,
	 * this could be a translation or other edit, and not something that requires
	 * an email notification.
	 */
	if ( ! is_main_site() ) {
		return;
	}

	// Only fire hooks for changing the status of submission posts.
	if ( $post->post_type !== 'submission' || $old_status === $new_status ) {
		return;
	}

	// Switch to the blog the submission originated from.
	switch_to_blog( $post->_submission_site );

	wp_schedule_single_event(
		time(),
		"transition_submission_status_{$new_status}",
		[ $post->ID ]
	);

	restore_current_blog();
}

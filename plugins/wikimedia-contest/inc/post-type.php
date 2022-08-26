<?php
/**
 * Custom Post Type for Submissions
 *
 * @package wikimedia-contest
 */

namespace Wikimedia_Contest\Post_Type;

use Wikimedia_Contest\Screening_Results;

const SLUG = 'submission';

/**
 * Bootstrap post-type related functionality.
 */
function bootstrap() {
	add_action( 'init', __NAMESPACE__ . '\\register_submission_custom_post_type', 0 );
	add_action( 'init', __NAMESPACE__ . '\\register_submission_custom_post_statuses', 0 );
	add_action( 'add_meta_boxes', __NAMESPACE__ . '\\add_submission_box' );
	add_action( 'save_post_submission', __NAMESPACE__ . '\\submission_save_meta', 10, 2 );
	add_filter( 'manage_submission_posts_columns', __NAMESPACE__ . '\\set_custom_edit_submission_columns' );
	add_action( 'manage_submission_posts_custom_column', __NAMESPACE__ . '\\custom_submission_column', 10, 2 );
}

/**
 * Register the "submission" post type.
 *
 * @return void
 */
function register_submission_custom_post_type() {

	$labels = [
		'name'                => _x( 'Submissions', 'Post Type General Name', 'wikimedia-contest' ),
		'singular_name'       => _x( 'Submission', 'Post Type Singular Name', 'wikimedia-contest' ),
		'menu_name'           => __( 'Submissions', 'wikimedia-contest' ),
		'all_items'           => __( 'All Submissions', 'wikimedia-contest' ),
		'view_item'           => __( 'View Submission', 'wikimedia-contest' ),
	];

	$args = [
		'label'               => __( 'Submissions', 'wikimedia-contest' ),
		'description'         => __( 'Audio submission from a contest participant', 'wikimedia-contest' ),
		'labels'              => $labels,
		'hierarchical'        => false,
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_nav_menus'   => false,
		'show_in_admin_bar'   => true,
		'menu_position'       => 3,
		'supports'            => [
			'title' => false,
			'editor' => false,
			'author' => false,
			'custom-fields' => true,
		],
		'can_export'          => true,
		'has_archive'         => false,
		'exclude_from_search' => false,
		'publicly_queryable'  => true,
		'show_in_rest' => true,
	];

	register_post_type( SLUG, $args );
}

/**
 * Register custom statuses available to the "submission" post type.
 *
 * The initial state for submissions will be "draft".
 *
 * @return void
 */
function register_submission_custom_post_statuses() {

	// Rename "draft" to "screening".
	global $wp_post_statuses;
	$wp_post_statuses['draft']->label = __( 'Screening', 'wikimedia-contest-admin' );
	$wp_post_statuses['draft']->label_count = _n_noop(
		'Screening <span class="count">(%s)</span>',
		'Screening <span class="count">(%s)</span>',
		'wikimedia-contest-admin'
	);

	// Ineligible.
	register_post_status( 'ineligible', [
		'label'                     => _x( 'Ineligible', 'post' ),
		'public'                    => true,
		'internal'                  => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Ineligible <span class="count">(%s)</span>', 'Ineligible <span class="count">(%s)</span>' ),
	] );

	// Scoring phase 1.
	register_post_status( 'scoring_phase_1', [
		'label'                     => _x( 'Scoring phase 1', 'post' ),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Scoring phase 1 <span class="count">(%s)</span>', 'Scoring phase 1 <span class="count">(%s)</span>' ),
	] );

	// Scoring phase 2.
	register_post_status( 'scoring_phase_2', [
		'label'                     => _x( 'Scoring phase 2', 'post' ),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Scoring phase 2 <span class="count">(%s)</span>', 'Scoring phase 2 <span class="count">(%s)</span>' ),
	] );

	// Scoring phase 3.
	register_post_status( 'scoring_phase_3', [
		'label'                     => _x( 'Scoring phase 3', 'post' ),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Scoring phase 3 <span class="count">(%s)</span>', 'Scoring phase 3 <span class="count">(%s)</span>' ),
	] );

	// Finalist.
	register_post_status( 'finalist', [
		'label'                     => _x( 'Finalist', 'post' ),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Finalist <span class="count">(%s)</span>', 'Finalist <span class="count">(%s)</span>' ),
	] );
}

/**
 * Register meta box for viewing/editing submission data.
 *
 * @return void
 */
function add_submission_box() : void {
	add_meta_box(
		'submission_box',
		__( 'Submission Details', 'wikimedia-contest' ),
		__NAMESPACE__ . '\\submission_metabox_html',
		SLUG,
		'normal',
		'high'
	);
}

/**
 * Include Audio column for Submission CPT.
 *
 * @param array $columns Current columns of submission CPT list.
 * @return array $columns Updated columns to display.
 */
function set_custom_edit_submission_columns( $columns ) : array {
	$columns['audio_file'] = 'Audio file';
	$columns['screening_results'] = 'Screening Results';
	return $columns;
}

/**
 * Customize Audio column for Submission CPT.
 *
 * @param string $column Column name.
 * @param int $post_id Post ID.
 * @return void
 */
function custom_submission_column( $column, $post_id ) : void {
	switch ( $column ) {

		case 'audio_file':
			echo sprintf( '<audio controls><source src="%s"></audio>', esc_attr( get_post_meta( $post_id, 'audio_file', true ) ) );
			break;

		case 'screening_results':
			$results = Screening_Results\get_screening_results( $post_id );

			if ( ! empty( $results['decision'] ) ) {
				foreach ( $results['decision'] as $decision ) {
					echo '<span class="moderation-flag screening-result">' . esc_html( $decision ) . '</span>';
				}
			}
			break;
	}
}

/**
 * Render the editor interface for the submission post type.
 *
 * @param WP_Post $post Current post object.
 *
 * @return void
 */
function submission_metabox_html( $post ) : void {

	echo '<div class="carded_content_container">';
	include __DIR__ . '/../templates/sound-info.php';
	echo '</div>';

	echo '<div class="carded_content_container">';
	include __DIR__ . '/../templates/submission-details.php';
	echo '</div>';
}

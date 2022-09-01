<?php
/**
 * Custom Post Type for Submissions
 *
 * @package wikimedia-contest
 */

namespace Wikimedia_Contest\Post_Type;

use Wikimedia_Contest\Screening;

const SLUG = 'submission';

/**
 * Bootstrap post-type related functionality.
 */
function bootstrap() {
	add_action( 'init', __NAMESPACE__ . '\\register_submission_custom_post_type', 0 );
	add_action( 'init', __NAMESPACE__ . '\\register_submission_custom_post_statuses', 0 );
	add_action( 'add_meta_boxes', __NAMESPACE__ . '\\add_submission_box' );
	add_filter( 'display_post_states', __NAMESPACE__ . '\\display_post_states_in_list_table', 10, 2 );
	add_filter( 'manage_edit-submission_columns', __NAMESPACE__ . '\\set_custom_edit_submission_columns' );
	add_action( 'manage_submission_posts_custom_column', __NAMESPACE__ . '\\custom_submission_column', 10, 2 );
	add_filter( 'manage_edit-submission_sortable_columns', __NAMESPACE__ . '\\custom_sortable_columns' );
	add_action( 'admin_footer-edit.php', __NAMESPACE__ . '\\custom_inline_edit');
	add_action( 'admin_menu', __NAMESPACE__ . '\\remove_unused_boxes');
	add_filter( 'post_row_actions', __NAMESPACE__ . '\\customize_row_actions', 10, 1 );
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
		'edit_item'           => __( 'Submission Details', 'wikimedia-contest' ),
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
 * Update the display of the post status in the "All Submissions" list.
 *
 * If filtering by a status, then there's no reason to show this state.
 * Otherwise, showing the state in the title column makes it easier to see what
 * the table is showing.
 *
 * @param [] $post_states Array of key => display text.
 * @param WP_Post $post Post being displayed.
 * @return [] Updated array of post states to display.
 */
function display_post_states_in_list_table( $post_states, $post ) {
	if ( $post->post_type !== SLUG ) {
		return $post_states;
	}

	if ( ! empty ( $_GET['post_status'] ) ) {
		return [];
	}

	$post_status = get_post_status( $post );
	return [ $post_status => get_post_status_object( $post_status )->label ];
}

/**
 * Set custom sortable columns for the "All Submissions" list.
 *
 * @param [] $columns Array of sortable columns.
 *
 * @return [] Updated array of sortable columns.
 */
function custom_sortable_columns( $columns ) {
	$columns['col_phase_score'] = [ 'col_' . get_site_option( 'contest_status' ) . '_score', true ];
	$columns["col_scoring_completion"] = [ 'col_' . get_site_option( 'contest_status' ) . '_completion', true ];
	return $columns;
}

/**
 * Include Audio column for Submission CPT.
 *
 * @param array $columns Current columns of submission CPT list.
 * @return array $columns Updated columns to display.
 */
function set_custom_edit_submission_columns( $columns ) : array {
	// Remove unused column.
	unset( $columns['translations'] );

	// Add Screening Results column.
	$columns['screening_results'] = 'Screening Results';

	$custom_post_statuses = get_post_stati( [
		'_builtin' => false,
		'internal' => false,
	], 'objects' );

	// Add Phase Scoring Results column.
	$columns["col_phase_score"] = '"' . $custom_post_statuses[ get_site_option( 'contest_status' ) ]->label . "\" Phase Score";

	// Add Scoring Phase Completion column.
	$columns["col_scoring_completion"] = '"' . $custom_post_statuses[ get_site_option( 'contest_status' ) ]->label . "\" Completion";

	return $columns;
}

/**
 * Customize columns for Submission CPT.
 *
 * @param string $column Column name.
 * @param int $post_id Post ID.
 * @return void
 */
function custom_submission_column( $column, $post_id ) : void {
	switch ( $column ) {

		case 'screening_results':
			$results = Screening\get_screening_results( $post_id );

			if ( ! empty( $results['decision'] ) ) {
				foreach ( $results['decision'] as $decision ) {
					echo '<span class="moderation-flag screening-result">' . esc_html( $decision ) . '</span>';
				}
			}
			break;

		case 'col_phase_score':
			$phase_score = get_post_meta( $post_id, 'score_' . get_site_option( 'contest_status' ), true );
			$scoring_phase_completion = get_post_meta( $post_id, 'score_completion_' . get_site_option( 'contest_status' ), true );
			if ( is_numeric( $phase_score ) ) {
				if ( (int) $scoring_phase_completion !== 1 ) {
					echo '*';
				}
				echo round( $phase_score, 2) . " / 10";
			} else {
				echo '-';
			}
			break;

		case 'col_scoring_completion':
			$scorer_count = get_post_meta( $post_id, 'scorer_count_' . get_site_option( 'contest_status' ), true );
			$scoring_phase_completion = get_post_meta( $post_id, 'score_completion_' . get_site_option( 'contest_status' ), true );
			echo sprintf( '%s complete ( %d / %s scorers )',
				round( $scoring_phase_completion * 100, 2 ) . "%",
				$scorer_count,
				\Wikimedia_Contest\Scoring\SCORERS_NEEDED_EACH_PHASE[ get_site_option( 'contest_status' ) ]
			);
			break;
	}
}

/**
 * Update the "translated content" meta field in response to user input.
 *
 * @param int $post_id Post ID being updated.
 * @param [] $translation_submission Submitted translations.
 * @return bool Whether updates were made.
 */
function update_translations( $post_id, $translation_submission ) {
	$translation_submission = array_intersect_key(
		array_map( 'sanitize_textarea_field', $translation_submission ),
		array_flip( [ 'creation', 'inspiration' ] )
	);

	return update_post_meta( $post_id, 'translated_fields', $translation_submission );
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

/**
 * Remove unused boxes to avoid confusing the users.
 *
 * @return void
 */
function remove_unused_boxes() : void {
	remove_meta_box( 'submitdiv', 'submission', 'side' );
	remove_meta_box( 'commentsdiv', 'submission', 'side' );
}

/**
 * Add custom status as options on inline edit.
 *
 * @return void
 */
function custom_inline_edit() : void {
?>
	<script>
		jQuery(document).ready( function() {

			// Remove fields that are not needed on inline edit.
			jQuery('span:contains("Password")').each(function (i) {
				jQuery(this).parent().parent().remove();
			});
			jQuery('span:contains("Date")').each(function (i) {
				jQuery(this).parent().remove();
			});
			jQuery('.inline-edit-date').each(function (i) {
				jQuery(this).remove();
			});

			// Remove all status options.
			jQuery('select[name="_status"]').find('option').remove();

			// Manually add Screening status.
			jQuery('select[name="_status"]').append("<option value='draft'>Screening</option>");

			<?php
				// Adding the custom statuses to the status list.
				$custom_statuses = get_post_stati( [
					'_builtin' => false,
				], 'objects' );
			?>

			<?php foreach ( $custom_statuses as $status ) : ?>
					jQuery('select[name="_status"]').append("<option value='<?php echo esc_attr( $status->name ) ?>'><?php echo esc_html( $status->label ) ?></option>");
			<?php endforeach; ?>

		});
	</script>

<?php
}

/**
 * Customize the row actions for the submission post type.
 *
 * @param array $actions
 *
 * @return array
 */
function customize_row_actions( $actions ) : array {
	if ( get_post_type() === 'submission' ) {
		$actions['edit'] = '<a href="' . get_edit_post_link() . '">'. __( 'View Submission', 'wikimedia-contest' ) .'</a>';
		unset( $actions['view'] );
		unset( $actions['trash'] );
	}

	return $actions;
}

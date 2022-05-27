<?php
/**
 * Custom Post Type for Submissions
 *
 * @package wikimedia-contest
 */

namespace WikimediaContest;

function register_submission_custom_post_type() {

	$labels = array(
		'name'                => _x( 'Submissions', 'Post Type General Name', 'wikimedia-contest' ),
		'singular_name'       => _x( 'Submission', 'Post Type Singular Name', 'wikimedia-contest' ),
		'menu_name'           => __( 'Submissions', 'wikimedia-contest' ),
		'all_items'           => __( 'All Submissions', 'wikimedia-contest' ),
		'view_item'           => __( 'View Submission', 'wikimedia-contest' ),
	);

	$args = array(
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
	);

	register_post_type( 'submission', $args );
}

function add_submission_box() {
	add_meta_box(
		'submission_box',
		__( 'Submission Details', 'wikimedia-contest' ),
		__NAMESPACE__ . '\\submission_metabox_html',
		'submission',
		'normal',
		'high'
	);
}

// Function to insert custom logic when inserting submissions
function submission_insert_post( $post_data, $postarr ) {

	if ( $post_data['post_type'] !== 'submission' ) {
		return $post_data;
	}

	// If the post is being updated, we don't need to do anything
	if ( $post_data['post_status'] === 'private' ) { // New post

		// Setting all new submissions to New status
		$post_data[ 'post_status' ] = 'new';

		// Creating a placeholder code for the submission as the patten is TBD
		$post_data['post_title'] = sprintf( "Submission %s", md5( $post_data['post_date'] ));
	}

	return $post_data;
}

function submission_metabox_html( $post ) {

	$wiki_username = get_post_meta( $post->ID, 'wiki_username', true );
	$legal_name = get_post_meta( $post->ID, 'legal_name', true );
	$date_birth = get_post_meta( $post->ID, 'date_birth', true );
	$participant_email = get_post_meta( $post->ID, 'participant_email', true );
	$phone_number = get_post_meta( $post->ID, 'phone_number', true );
	$audio_path = get_post_meta( $post->ID, 'audio_path', true );
	$authors_contributed = get_post_meta( $post->ID, 'authors_contributed', true );
	$explanation_creation = get_post_meta( $post->ID, 'explanation_creation', true );
	$explanation_inspiration = get_post_meta( $post->ID, 'explanation_inspiration', true );

	wp_nonce_field( 'random-string', '_mishanonce' );

	echo '<table class="form-table">
		<tbody>

			<tr>
				<th><label for="wiki_username">Participant Wikimedia Username</label></th>
				<td><input type="text" id="wiki_username" name="wiki_username" value="' . esc_attr($wiki_username) . '"></td>
			</tr>

			<tr>
				<th><label for="legal_name">Participant Legal Name</label></th>
				<td><input type="text" id="legal_name" name="legal_name" value="' . esc_attr($legal_name) . '"></td>
			</tr>

			<tr>
				<th><label for="date_birth">Participant Date of Birth</label></th>
				<td><input type="text" id="date_birth" name="date_birth" value="' . esc_attr($date_birth) . '"></td>
			</tr>

			<tr>
				<th><label for="participant_email">Participant Email</label></th>
				<td><input type="text" id="participant_email" name="participant_email" value="' . esc_attr($participant_email) . '"></td>
			</tr>

			<tr>
				<th><label for="phone_number">Participant Phone Number</label></th>
				<td><input type="text" id="phone_number" name="phone_number" value="' . esc_attr($phone_number) . '"></td>
			</tr>

			<tr>
				<th><label for="audio_path">Audio file path</label></th>
				<td><input type="text" id="audio_path" name="audio_path" value="' . esc_attr($audio_path) . '"></td>
			</tr>

			<tr>
				<th><label for="authors_contributed">List all of the authors who contributed</label></th>
				<td><textarea id="authors_contributed" name="authors_contributed">' . esc_attr($authors_contributed) . '</textarea></td>
			</tr>

			<tr>
				<th><label for="explanation_creation">Brief explanation of how the sound was created logo</label></th>
				<td><textarea id="explanation_creation" name="explanation_creation">' . esc_attr($explanation_creation) . '</textarea></td>
			</tr>

			<tr>
				<th><label for="explanation_inspiration">Brief explanation about meaning and inspiration</label></th>
				<td><textarea id="explanation_inspiration" name="explanation_inspiration">' . esc_attr($explanation_inspiration) . '</textarea></td>
			</tr>

		</tbody>
	</table>';
}

function submission_save_meta( $post_id, $post ) {

	// nonce check
	if ( ! isset( $_POST[ '_mishanonce' ] ) || ! wp_verify_nonce( $_POST[ '_mishanonce' ], 'random-string' ) ) {
		return $post_id;
	}

	// check current user permissions
	$post_type = get_post_type_object( $post->post_type );

	if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) ) {
		return $post_id;
	}

	// Do not save the data if autosave
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
		return $post_id;
	}

	// define your own post type here
	if ( 'submission' !== $post->post_type ) {
		return $post_id;
	}

	// wiki_username
	if ( isset( $_POST['wiki_username'] ) ) {
		update_post_meta( $post_id, 'wiki_username', sanitize_text_field( $_POST['wiki_username'] ) );
	} else {
		delete_post_meta( $post_id, 'wiki_username' );
	}

	// legal_name
	if ( isset( $_POST['legal_name'] ) ) {
		update_post_meta( $post_id, 'legal_name', sanitize_text_field( $_POST['legal_name'] ) );
	} else {
		delete_post_meta( $post_id, 'legal_name' );
	}

	// date_birth
	if ( isset( $_POST['date_birth'] ) ) {
		update_post_meta( $post_id, 'date_birth', sanitize_text_field( $_POST['date_birth'] ) );
	} else {
		delete_post_meta( $post_id, 'date_birth' );
	}

	// participant_email
	if ( isset( $_POST['participant_email'] ) ) {
		update_post_meta( $post_id, 'participant_email', sanitize_text_field( $_POST['participant_email'] ) );
	} else {
		delete_post_meta( $post_id, 'participant_email' );
	}

	// phone_number
	if ( isset( $_POST['phone_number'] ) ) {
		update_post_meta( $post_id, 'phone_number', sanitize_text_field( $_POST['phone_number'] ) );
	} else {
		delete_post_meta( $post_id, 'phone_number' );
	}

	// audio_path
	if ( isset( $_POST['audio_path'] ) ) {
		update_post_meta( $post_id, 'audio_path', sanitize_text_field( $_POST['audio_path'] ) );
	} else {
		delete_post_meta( $post_id, 'audio_path' );
	}

	// authors_contributed
	if ( isset( $_POST['authors_contributed'] ) ) {
		update_post_meta( $post_id, 'authors_contributed', sanitize_text_field( $_POST['authors_contributed'] ) );
	} else {
		delete_post_meta( $post_id, 'authors_contributed' );
	}

	// explanation_creation
	if ( isset( $_POST['explanation_creation'] ) ) {
		update_post_meta( $post_id, 'explanation_creation', sanitize_text_field( $_POST['explanation_creation'] ) );
	} else {
		delete_post_meta( $post_id, 'explanation_creation' );
	}

	// explanation_inspiration
	if ( isset( $_POST['explanation_inspiration'] ) ) {
		update_post_meta( $post_id, 'explanation_inspiration', sanitize_text_field( $_POST['explanation_inspiration'] ) );
	} else {
		delete_post_meta( $post_id, 'explanation_inspiration' );
	}

	return $post_id;
}

function register_submission_custom_post_statuses() {

	// New submission status
    register_post_status( 'new', array(
        'label'                     => _x( 'New', 'post' ),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'New <span class="count">(%s)</span>', 'New <span class="count">(%s)</span>' ),
    ) );

	// Ineligible submission status
    register_post_status( 'ineligible', array(
        'label'                     => _x( 'Ineligible', 'post' ),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Ineligible <span class="count">(%s)</span>', 'Ineligible <span class="count">(%s)</span>' ),
    ) );

	// Eligible submission status
    register_post_status( 'eligible', array(
        'label'                     => _x( 'Eligible', 'post' ),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Eligible <span class="count">(%s)</span>', 'Eligible <span class="count">(%s)</span>' ),
    ) );

	// Selected submission status
    register_post_status( 'selected', array(
        'label'                     => _x( 'Selected', 'post' ),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Selected <span class="count">(%s)</span>', 'Selected <span class="count">(%s)</span>' ),
    ) );

	// Finalist submission status
    register_post_status( 'finalist', array(
        'label'                     => _x( 'Finalist', 'post' ),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Finalists <span class="count">(%s)</span>', 'Finalists <span class="count">(%s)</span>' ),
    ) );
}

function submission_filter_posts_columns( $columns ) {
	var_dump(1);exit;
	$columns['image'] = __( 'Image' );
	$columns['price'] = __( 'Price', 'smashing' );
	$columns['area'] = __( 'Area', 'smashing' );
	return $columns;
  }

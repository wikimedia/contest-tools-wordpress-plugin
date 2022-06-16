<?php
/**
 * Custom Post Type for Submissions
 *
 * @package wikimedia-contest
 */

namespace Wikimedia_Contest\Post_Type;

/**
 * Bootstrap post-type related functionality.
 */
function bootstrap() {
	add_action( 'init', __NAMESPACE__ . '\\register_submission_custom_post_type', 0 );
	add_action( 'init', __NAMESPACE__ . '\\register_submission_custom_post_statuses', 0 );
	add_action( 'add_meta_boxes', __NAMESPACE__ . '\\add_submission_box' );
	add_action( 'save_post_submission', __NAMESPACE__ . '\\submission_save_meta', 10, 2 );
	add_action( 'wp_loaded', __NAMESPACE__ . '\\process_submission_form' );
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

	register_post_type( 'submission', $args );
}

/**
 * Register custom statuses available to the "submission" post type.
 *
 * The initial state for submissions will be "draft".
 *
 * @return void
 */
function register_submission_custom_post_statuses() {

	// Ineligible submission status.
	register_post_status( 'ineligible', [
		'label'                     => _x( 'Ineligible', 'post' ),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Ineligible <span class="count">(%s)</span>', 'Ineligible <span class="count">(%s)</span>' ),
	] );

	// Eligible submission status.
	register_post_status( 'eligible', [
		'label'                     => _x( 'Eligible', 'post' ),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Eligible <span class="count">(%s)</span>', 'Eligible <span class="count">(%s)</span>' ),
	] );

	// Selected submission status.
	register_post_status( 'selected', [
		'label'                     => _x( 'Selected', 'post' ),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Selected <span class="count">(%s)</span>', 'Selected <span class="count">(%s)</span>' ),
	] );

	// Finalist submission status.
	register_post_status( 'finalist', [
		'label'                     => _x( 'Finalist', 'post' ),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Finalists <span class="count">(%s)</span>', 'Finalists <span class="count">(%s)</span>' ),
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
		'submission',
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
			echo sprintf( '<audio controls><source src="%s"></audio>', esc_attr( get_post_meta( $post_id, 'audio_path', true ) ) );
			break;
	}
}

/**
 * Render the editor interface for the submission post type.
 *
 * Temporary interface for proof of concept only.
 *
 * @param WP_Post $post Current post object.
 * @return void
 */
function submission_metabox_html( $post ) : void {

	$wiki_username = get_post_meta( $post->ID, 'wiki_username', true );
	$legal_name = get_post_meta( $post->ID, 'legal_name', true );
	$date_birth = get_post_meta( $post->ID, 'date_birth', true );
	$participant_email = get_post_meta( $post->ID, 'participant_email', true );
	$phone_number = get_post_meta( $post->ID, 'phone_number', true );
	$audio_path = get_post_meta( $post->ID, 'audio_path', true );
	$authors_contributed = get_post_meta( $post->ID, 'authors_contributed', true );
	$explanation_creation = get_post_meta( $post->ID, 'explanation_creation', true );
	$explanation_inspiration = get_post_meta( $post->ID, 'explanation_inspiration', true );

	wp_nonce_field( 'save_post_submission', '_submissionnonce' );

	echo '<table class="form-table">
	<tbody>

		<tr>
			<th><label for="wiki_username">Participant Wikimedia Username</label></th>
			<td><input type="text" id="wiki_username" name="wiki_username" maxlength="100" value="' . esc_attr( $wiki_username ) . '"></td>
		</tr>

		<tr>
			<th><label for="legal_name">Participant Legal Name</label></th>
			<td><input type="text" id="legal_name" name="legal_name" maxlength="100" value="' . esc_attr( $legal_name ) . '"></td>
		</tr>

		<tr>
			<th><label for="date_birth">Participant Date of Birth</label></th>
			<td><input type="date" id="date_birth" name="date_birth" value="' . esc_attr( $date_birth ) . '"></td>
		</tr>

		<tr>
			<th><label for="participant_email">Participant Email</label></th>
			<td><input type="email" id="participant_email" name="participant_email" value="' . esc_attr( $participant_email ) . '"></td>
		</tr>

		<tr>
			<th><label for="phone_number">Participant Phone Number</label></th>
			<td><input type="tel" id="phone_number" name="phone_number" maxlength="15" value="' . esc_attr( $phone_number ) . '"></td>
		</tr>

		<tr>
			<th><label for="audio_path">Audio file</label></th>
			<td>
				<audio controls>
					<source src="' . esc_url( $audio_path ) . '">
				</audio>
			</td>
		</tr>

		<tr>
			<th><label for="authors_contributed">List all of the authors who contributed</label></th>
			<td><textarea id="authors_contributed" name="authors_contributed" rows="6" cols="100">' . esc_attr( $authors_contributed ) . '</textarea></td>
		</tr>

		<tr>
			<th><label for="explanation_creation">Brief explanation of how the sound was created logo</label></th>
			<td><textarea id="explanation_creation" name="explanation_creation" rows="6" cols="100">' . esc_attr( $explanation_creation ) . '</textarea></td>
		</tr>

		<tr>
			<th><label for="explanation_inspiration">Brief explanation about meaning and inspiration</label></th>
			<td><textarea id="explanation_inspiration" name="explanation_inspiration" rows="6" cols="100">' . esc_attr( $explanation_inspiration ) . '</textarea></td>
		</tr>

	</tbody>
</table>';
}

/**
 * Update submission meta fields on saving new submission.
 *
 * @param int $post_id Post ID of post being saved.
 * @param WP_Post $post Post being inserted or updated.
 *
 * @return int Post ID, unchanged.
 */
function submission_save_meta( $post_id, $post ) : int {

	// Nonce check.
	if ( ! isset( $_POST['_submissionnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_submissionnonce'] ) ), 'save_post_submission' ) ) {
		return $post_id;
	}

	// Check current user permissions.
	$post_type = get_post_type_object( $post->post_type );

	if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) ) {
		return $post_id;
	}

	// Do not save the data if autosave.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return $post_id;
	}

	// Check that post is of the correct type.
	if ( 'submission' !== $post->post_type ) {
		return $post_id;
	}

	if ( isset( $_POST['wiki_username'] ) ) {
		update_post_meta( $post_id, 'wiki_username', sanitize_text_field( wp_unslash( $_POST['wiki_username'] ) ) );
	} else {
		delete_post_meta( $post_id, 'wiki_username' );
	}

	if ( isset( $_POST['legal_name'] ) ) {
		update_post_meta( $post_id, 'legal_name', sanitize_text_field( wp_unslash( $_POST['legal_name'] ) ) );
	} else {
		delete_post_meta( $post_id, 'legal_name' );
	}

	if ( isset( $_POST['date_birth'] ) ) {
		update_post_meta( $post_id, 'date_birth', sanitize_text_field( wp_unslash( $_POST['date_birth'] ) ) );
	} else {
		delete_post_meta( $post_id, 'date_birth' );
	}

	if ( isset( $_POST['participant_email'] ) ) {
		update_post_meta( $post_id, 'participant_email', sanitize_text_field( wp_unslash( $_POST['participant_email'] ) ) );
	} else {
		delete_post_meta( $post_id, 'participant_email' );
	}

	if ( isset( $_POST['phone_number'] ) ) {
		update_post_meta( $post_id, 'phone_number', sanitize_text_field( wp_unslash( $_POST['phone_number'] ) ) );
	} else {
		delete_post_meta( $post_id, 'phone_number' );
	}

	if ( isset( $_POST['audio_path'] ) ) {
		update_post_meta( $post_id, 'audio_path', sanitize_text_field( wp_unslash( $_POST['audio_path'] ) ) );
	} else {
		delete_post_meta( $post_id, 'audio_path' );
	}

	if ( isset( $_POST['authors_contributed'] ) ) {
		update_post_meta( $post_id, 'authors_contributed', sanitize_text_field( wp_unslash( $_POST['authors_contributed'] ) ) );
	} else {
		delete_post_meta( $post_id, 'authors_contributed' );
	}

	if ( isset( $_POST['explanation_creation'] ) ) {
		update_post_meta( $post_id, 'explanation_creation', sanitize_text_field( wp_unslash( $_POST['explanation_creation'] ) ) );
	} else {
		delete_post_meta( $post_id, 'explanation_creation' );
	}

	if ( isset( $_POST['explanation_inspiration'] ) ) {
		update_post_meta( $post_id, 'explanation_inspiration', sanitize_text_field( wp_unslash( $_POST['explanation_inspiration'] ) ) );
	} else {
		delete_post_meta( $post_id, 'explanation_inspiration' );
	}

	return $post_id;
}

/**
 * Process submission form, handle uploaded file and save submission
 * to database.
 *
 * @return false|void
 */
function process_submission_form() {

	// Nonce check.
	if ( ! isset( $_POST['_submissionnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_submissionnonce'] ) ), 'save_post_submission' ) ) {
		return false;
	}

	if ( sanitize_text_field( wp_unslash( $_POST['action'] ?? '' ) ) === 'submit_contest_submission' ) {

		// Placeholder for submission unique code - TBD.
		$submission_unique_code = md5( microtime( true ) );

		// File upload.
		$upload_dir = wp_upload_dir()['basedir'];
		$file_location = $upload_dir . '/' . $submission_unique_code;
		if ( isset( $_FILES['audio_file'] ) ) {
			if ( move_uploaded_file( sanitize_text_field( wp_unslash( $_FILES['audio_file']['tmp_name'] ?? '' ) ), $file_location ) ) {
				$audio_path = wp_upload_dir()['baseurl'] . '/' . $submission_unique_code;
			}
		}

		$submission_post = [
			'post_title'  => sprintf( 'Submission %s', $submission_unique_code ),
			'post_status' => 'draft',
			'post_author' => 1,
			'post_type'   => 'submission',
			'meta_input'  => [
				'wiki_username'           => sanitize_text_field( wp_unslash( $_POST['wiki_username'] ?? '' ) ),
				'legal_name'              => sanitize_text_field( wp_unslash( $_POST['legal_name'] ?? '' ) ),
				'date_birth'              => sanitize_text_field( wp_unslash( $_POST['date_birth'] ?? '' ) ),
				'participant_email'       => sanitize_email( wp_unslash( $_POST['participant_email'] ?? '' ) ),
				'phone_number'            => wc_sanitize_phone_number( sanitize_text_field( wp_unslash( $_POST['phone_number'] ?? '' ) ) ),
				'audio_path'              => sanitize_text_field( wp_unslash( $audio_path ?? '' ) ),
				'authors_contributed'     => sanitize_textarea_field( wp_unslash( $_POST['authors_contributed'] ?? '' ) ),
				'explanation_creation'    => sanitize_textarea_field( wp_unslash( $_POST['explanation_creation'] ?? '' ) ),
				'explanation_inspiration' => sanitize_textarea_field( wp_unslash( $_POST['explanation_inspiration'] ?? '' ) ),
			],
		];

		wp_insert_post( $submission_post );
	}
}

/**
 * Sanitize phone number.
 *
 * @param string $phone Input phone number.
 * @return string Sanitized phone number.
 */
function wc_sanitize_phone_number( $phone ) : string {
	return preg_replace( '/[^\d+]/', '', $phone );
}

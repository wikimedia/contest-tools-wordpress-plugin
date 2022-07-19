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

	return '<form method="post" id="submission-form" enctype="multipart/form-data">
	<table class="form-table">
		<tbody>

			' . wp_nonce_field( 'wp_rest', '_submissionnonce', true, false ) . '

			<tr>
				<th><label for="wiki_username">' . __( 'Participant Wikimedia Username', 'wikimedia-contest' ) . '</label></th>
				<td><input type="text" id="wiki_username" name="wiki_username"></td>
			</tr>

			<tr>
				<th><label for="legal_name">' . __( 'Participant Legal Name', 'wikimedia-contest' ) . '</label></th>
				<td><input type="text" id="legal_name" name="legal_name"></td>
			</tr>

			<tr>
				<th><label for="date_birth">' . __( 'Participant Date of Birth', 'wikimedia-contest' ) . '</label></th>
				<td><input type="date" id="date_birth" name="date_birth"></td>
			</tr>

			<tr>
				<th><label for="participant_email">' . __( 'Participant Email', 'wikimedia-contest' ) . '</label></th>
				<td><input type="text" id="participant_email" name="participant_email"></td>
			</tr>

			<tr>
				<th><label for="phone_number">' . __( 'Participant Phone Number', 'wikimedia-contest' ) . '</label></th>
				<td><input type="text" id="phone_number" name="phone_number"></td>
			</tr>

			<tr>
				<th><label for="audio_file">' . __( 'Audio file', 'wikimedia-contest' ) . '</label></th>
				<td><input type="file" id="audio_file" name="audio_file"></td>
			</tr>

			<tr>
				<th><label for="authors_contributed">' . __( 'List all of the authors who contributed', 'wikimedia-contest' ) . '</label></th>
				<td><textarea id="authors_contributed" name="authors_contributed"></textarea></td>
			</tr>

			<tr>
				<th><label for="explanation_creation">' . __( 'Brief explanation of how the sound was created logo', 'wikimedia-contest' ) . '</label></th>
				<td><textarea id="explanation_creation" name="explanation_creation"></textarea></td>
			</tr>

			<tr>
				<th><label for="explanation_inspiration">' . __( 'Brief explanation about meaning and inspiration', 'wikimedia-contest' ) . '</label></th>
				<td><textarea id="explanation_inspiration" name="explanation_inspiration"></textarea></td>
			</tr>

			<tr>
				<th>Submit</th>
				<td><input type="submit" value="Submit"></td>
			</tr>

			<input type="hidden" name="action" value="submit_contest_submission">

		</tbody>
	</table>
	</form>
	<div id="submission_return_message"></div>';
}

/**
 * Include in the array of allowed blocks the custom blocks
 * inserted by the Wikimedia Contest plugin.
 *
 * @param bool|string[] $allowed_blocks Array of allowed blocks.
 * @param \WP_Post      $post The post being edited.
 *
 * @return string[]|bool
 */
function filter_blocks_wikimedia_contest( $allowed_blocks, \WP_Post $post ) {

	if ( $post->post_type === 'page' && is_array( $allowed_blocks ) ) {
		$allowed_blocks[] = 'wikimedia-contest/audio-submission-form';
	}

	return $allowed_blocks;
}

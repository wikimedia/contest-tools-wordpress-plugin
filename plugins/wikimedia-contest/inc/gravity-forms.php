<?php
/**
 * Gravity Forms integration
 *
 * @package wikimedia-contest
 */

namespace Wikimedia_Contest\Gravity_Forms;

use Asset_Loader;
use Asset_Loader\Manifest;
use Wikimedia_Contest\Network_Library;

/**
 * Bootstrap form functionality.
 */
function bootstrap() {
	add_filter( 'allowed_block_types', __NAMESPACE__ . '\\filter_blocks', 20, 2 ); // After shiro theme defines the allowed blocks.
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_form_scripts' );
	add_filter( 'gform_pre_render', __NAMESPACE__ . '\\identify_audio_meta_field', 10, 3 );
	add_filter( 'gform_field_input', __NAMESPACE__ . '\\render_accesible_select_field', 10, 3 );
	add_action( 'gform_entry_created', __NAMESPACE__ . '\\handle_entry_submission', 10, 2 );
}

/**
 * Allow Gform blocks to be inserted in the editor.
 *
 * @param bool|string[] $allowed_blocks Array of allowed blocks.
 * @param \WP_Post      $post The post being edited.
 *
 * @return string[]|bool
 */
function filter_blocks( $allowed_blocks, \WP_Post $post ) {

	if ( $post->post_type === 'page' && is_array( $allowed_blocks ) ) {
		$allowed_blocks[] = 'gravityforms/form';
	}

	return $allowed_blocks;
}

/**
 * Enqueue submission form scripts.
 *
 * TODO: only enqueue on the page containing the submission form.
 */
function enqueue_form_scripts() {

	$manifest = Manifest\get_active_manifest( [
		dirname( __DIR__ ) . '/build/development-asset-manifest.json',
		dirname( __DIR__ ) . '/build/production-asset-manifest.json',
	] );

	Asset_Loader\enqueue_asset(
		$manifest,
		'submissionForm.js',
		[
			'dependencies' => [
				'gform_gravityforms',
				'wp-a11y',
				'wp-i18n',
			],
			'handle' => 'wikimedia_contest_submission_form',
		]
	);
}

/**
 * Define a JS variable with the field name of the audio_file_meta hidden field.
 *
 * Using a Gravity Forms hidden field gives us a lot of the behavior we want
 * for free, like persisting meta content on form refreshes. Unfortunately,
 * there isn't a hook available to filter the output of that field and add an
 * attribute which can be used to target it in javascript. So we look it up and
 * save its field ID as a variable for use in targeting the field.
 *
 * @param Form $form The form being rendered.
 * @return Form (unchanged)
 */
function identify_audio_meta_field( $form ) {
	$field = current( wp_list_filter( $form['fields'], [ 'label' => 'audio_file_meta' ] ) );

	if ( $field ) {
		$field_id = "input_{$field->formId}_{$field->id}";
		echo "\r\n" . '<script type="text/javascript">var audioFileMetaField = "' . esc_js( $field_id ) . '";</script>';
	}

	return $form;
}

/**
 * Replace the GF select field with a custom dropdown that can be styled.
 *
 * @param string $field_content The input tag to be filtered.
 * @param Field $field The field that this input tag applies to.
 * @param string $value Current field value (or default).
 * @return string Updated markup for this form field.
 */
function render_accesible_select_field( $field_input, $field, $value ) {
	if ( $field->type !== 'select' ) {
		return $field_content;
	}

	$id = sanitize_key( "input_{$field->id}" );
	ob_start();
?>
<div class="ginput_container">
	<div class="gfield_label gfield_required" id="<?php echo esc_attr( $id ); ?>">
		<?php echo esc_html( $field->label ); ?>
	</div>
	<div class="gfield_custom_select">
		<button type="button" class="gfield_toggle" aria-haspopup="listbox" aria-labelledby="<?php echo esc_attr( $id ); ?>">
		</button>
		<ul class="gfield_listbox" role="listbox" id="<?php echo esc_attr( "{$id}_list" ); ?>" tabindex="-1">
			<?php
			foreach ( $field->choices as $option ) {
				echo '<li class="gfield_option' .
					( $option['isSelected'] ? ' is-selected' : '' ) . '" ' .
					'tabindex="0" ' .
					'data-value="' . esc_attr( $option['value'] ) . '" ' .
					'role="option">' .
					esc_html( $option['text'] ) . '</li>';
			}
			?>
		</ul>
		<input type="hidden" class="gfield_hidden_input" name="<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( $value ); ?>" >
	</div>
</div>
<?php

	return ob_get_clean();
}

/**
 * Handle a form submitted through Gravity Forms.
 *
 * This is fired after all validations are performed, so we assume that the
 * submission should be allowed to continue.
 *
 * @param Entry $entry The current submission.
 * @param Form $form Form being submitted.
 */
function handle_entry_submission( $entry, $form ) {
	$formatted_entry = process_entry_fields( $entry, $form );

	// Sanitize the audio file meta field.
	$audio_file_meta = sanitize_audio_file_meta_field( $formatted_entry['audio_file_meta'] ?? '' );

	// For fields that contain an "other" option, merge that option into the list.
	$submitter_gender = ( $formatted_entry['submitter_gender'] === 'other' ) ?
		( $formatted_entry['submitter_gender_other'] ?? 'other' ) :
		$formatted_entry['submitter_gender'] ?? '';

	$submitter_country = ( $formatted_entry['submitter_country'] === 'other' ) ?
		( $formatted_entry['submitter_country_other'] ?? 'other' ) :
		$formatted_entry['submitter_country'] ?? '';

	// Placeholder for submission unique code - TBD.
	$submission_unique_code = md5( microtime( true ) );

	// Contributing authors: any data in fields matching this label format.
	$contributing_authors = array_filter(
		array_values(
			array_intersect_key(
				$formatted_entry,
				array_flip( [
					'contributor_1',
					'contributor_2',
					'contributor_3',
					'contributor_4',
					'contributor_5',
					'contributor_6',
					'contributor_7',
					'contributor_8',
					'contributor_9',
				] )
			)
		)
	);

	$creation_process = [
		'all_original_sounds' => $formatted_entry['all_original_sounds'],
		'cc0_or_public_domain' => $formatted_entry['cc0_or_public_domain'],
		'used_prerecorded_sounds' => $formatted_entry['used_prerecorded_sounds'],
		'used_soundpack_library' => $formatted_entry['used_soundpack_library'],
		'used_samples' => $formatted_entry['used_samples'],
		'source_urls' => $formatted_entry['source_urls'],
	];

	$submission_post = [
		'post_title'                  => sprintf( 'Submission %s', $submission_unique_code ),
		'post_status'                 => 'draft',
		'post_author'                 => 1,
		'post_type'                   => 'submission',
		'meta_input'                  => [
			'unique_code'             => $submission_unique_code,
			'submitter_name'          => $formatted_entry['submitter_name'] ?? '',
			'submitter_email'         => $formatted_entry['submitter_email'] ?? '',
			'submitter_wiki_user'     => $formatted_entry['submitter_wiki_user'] ?? '',
			'submitter_phone'         => $formatted_entry['submitter_phone'] ?? '',
			'submitter_country'       => $submitter_country,
			'submitter_gender'        => $submitter_gender,
			'creation_process'        => $creation_process,
			'contributing_authors'    => $contributing_authors,
			'explanation_creation'    => $formatted_entry['explanation_creation'] ?? '',
			'explanation_inspiration' => $formatted_entry['explanation_inspiration'] ?? '',
			'audio_file'              => $formatted_entry['audio_file'] ?? null,
			'audio_file_meta'         => $audio_file_meta,
		],
	];

	$post_data = Network_Library\insert_submission( $submission_post );
}

/**
 * Sanitize the values being saved for audio file meta.
 *
 * @param string $raw_input_value JSON-encoded string value submitted with form.
 * @return [] Sanitized values for only allow-listed keys.
 */
function sanitize_audio_file_meta_field( $raw_input_value = '' ) {

	$audio_file_meta_raw = json_decode( $raw_input_value, true );

	// Define allowed field values for audio file meta array.
	$audio_meta_allowed_values = [
		'name' => 'sanitize_text_field',
		'type' => 'sanitize_text_field',
		'size' => 'absint',
		'sampleRate' => 'absint',
		'numberOfChannels' => 'absint',
		'duration' => 'floatval',
	];

	$audio_file_meta = [];

	foreach ( $audio_meta_allowed_values as $key => $sanitize_function ) {
		if ( isset( $audio_file_meta_raw[ $key ] ) ) {
			$audio_file_meta[ $key ] = call_user_func( $sanitize_function, $audio_file_meta_raw[ $key ] );
		}
	}

	return $audio_file_meta;
}

/**
 * Turn raw form submission data into the structure we need to handle it.
 *
 * @param Entry $entry The current submission.
 * @param Form $form Form being submitted.
 * @return [] Array of admin field labels to entry values.
 */
function process_entry_fields( $entry, $form ) {
	$formatted_entry = [];
	$entry_fields = [];

	/*
	 * Get an id=>label array from the form fields.
	 *
	 * Use the adminLabel from the field if it's set, but fall back to the
	 * field's label if not set. Note: because of array key implicit
	 * conversion, we have to loop through the arrays and get both numeric keys
	 * and string keys that look like numbers, like "21.3".
	 */
	foreach ( $form['fields'] as $field ) {
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$entry_fields[ (string) $field->id ] = $field->adminLabel ?: $field->label;

		// If the field has multiple inputs (for example first and last name),
		// get the labels from each of them.
		if ( is_array( $field['inputs'] ) ) {
			foreach ( $field['inputs'] as $input ) {
				// Skip fields without a key, they can't hold values.
				if ( empty( $input['key'] ) ) {
					continue;
				}
				$entry_fields[ $input['key'] ] = $input['label'];
			}
		}
	}

	foreach ( $entry_fields as $key => $admin_label ) {
		if ( array_key_exists( $key, $entry ) ) {
			$formatted_entry[ (string) $admin_label ] = $entry[ $key ];
		}
	}

	return $formatted_entry;
}

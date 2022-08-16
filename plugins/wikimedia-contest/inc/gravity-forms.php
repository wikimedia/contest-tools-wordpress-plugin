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
	add_filter( 'gform_field_input', __NAMESPACE__ . '\\render_accessible_select_field', 10, 3 );
	add_action( 'gform_entry_created', __NAMESPACE__ . '\\handle_entry_submission', 10, 2 );
	add_filter( 'gform_custom_merge_tags', __NAMESPACE__ . '\\custom_merge_tags', 10, 4);
	add_filter( 'gform_replace_merge_tags', __NAMESPACE__ . '\\replace_merge_tags', 10, 7 );
	add_filter( 'render_block', __NAMESPACE__ . '\\replace_submission_count_confirmation_page', 10, 2 );
}

/**
 * Manually replace submission count merge tag on confirmation page.
 *
 * @param string $block_content The rendered block.
 * @param array  $block         The block being rendered.
 *
 * @return string The rendered block content.
  */
function replace_submission_count_confirmation_page( $block_content, $block ) : string {

	if ( 'core/paragraph' !== $block['blockName'] ) {
		return $block_content;
	}

	if ( empty(  $block_content ) ) {
		return $block_content;
	}

	if ( strpos( $block_content, '{user_submission_count}' ) === false ) {
		return $block_content;
	}

	if ( ! is_page() ) {
		return $block_content;
	}

	// Check if current page ID matches any of GF confirmation pages.
	$current_page_id_found = false;
	$forms = \GFAPI::get_forms();
	foreach ( $forms as $form ) {
		$confirmations = $form['confirmations'];
		foreach ( $confirmations as $confirmation ) {
			if ( $confirmation['type'] === 'page' && absint( $confirmation['pageId'] ) === get_the_ID() ) {
				$current_page_id_found = true;
				break;
			}
		}
	}
	if ( ! $current_page_id_found ) {
		return $block_content;
	}

	// Last submission post.
	$last_submission_post = \Wikimedia_Contest\Network_Library\get_last_submission_post();
	if ( ! $last_submission_post ) {
		return $block_content;
	}

	// Last submission post meta.
	$submission_post_meta = get_post_meta( $last_submission_post->ID ) ?? [];
	if ( empty( $submission_post_meta ) ) {
		return $block_content;
	}

	// Counting posts based on submitter_email meta key.
	$number_of_posts = \Wikimedia_Contest\Network_Library\count_posts_by_submitter_email_meta( $submission_post_meta['submitter_email'][0] );
	if ( ! $number_of_posts ) {
		return $block_content;
	}

	$block_content = str_replace( '{user_submission_count}', $number_of_posts, $block_content );

	return $block_content;
}

/**
 * Add custom merge tags.
 *
 * @param array $merge_tags Merge tags.
 * @param int $form_id Form ID.
 * @param array $fields Form Fields.
 * @param int $element_id Element ID.
 *
 * @return array
 */
function custom_merge_tags( $merge_tags, $form_id, $fields, $element_id ) : array {
	$merge_tags[] = [ 'label' => 'User submission count', 'tag' => '{user_submission_count}' ];
	$merge_tags[] = [ 'label' => 'Formatted uploaded audio info', 'tag' => '{formatted_audio_info}' ];
	$merge_tags[] = [ 'label' => 'Submission unique ID', 'tag' => '{submission_unique_id}' ];
	return $merge_tags;
}

/**
 * Replace custom merge tags.
 *
 * @param string $text Text.
 * @param array $form Form.
 * @param array $entry Entry.
 * @param bool $url_encode URL encode.
 * @param bool $esc_html Escape HTML.
 * @param bool $nl2br Newline to break.
 * @param string $format Format.
 *
 * @return string Text
 */
function replace_merge_tags( $text, $form, $entry, $url_encode, $esc_html, $nl2br, $format ) : string {

	if ( $entry ) {
		$tag_list = [
			'{user_submission_count}',
			'{formatted_audio_info}',
			'{submission_unique_id}',
		];

		// Check if the text contains any of the merge tags.
		$has_tag = false;
		foreach ( $tag_list as $tag ) {
			$has_tag = ( strpos( $text, $tag ) !== false ) ? true : $has_tag;
		}
		if ( ! $has_tag ) {
			return $text;
		}

		$merge_tags = [];

		// Getting last submission post.
		$last_submission_post = \Wikimedia_Contest\Network_Library\get_last_submission_post();
		if ( ! $last_submission_post ) {
			return $text;
		}

		// Getting last submission post meta.
		$submission_post_meta = get_post_meta( $last_submission_post->ID ) ?? [];
		if ( empty( $submission_post_meta ) ) {
			return $text;
		}

		// Counting posts based on submitter_email meta key.
		$number_of_posts = \Wikimedia_Contest\Network_Library\count_posts_by_submitter_email_meta( $submission_post_meta['submitter_email'][0] );
		if ( ! $number_of_posts ) { // It should have one, at least.
			return $text;
		}

		// Formatting audio meta data.
		$audio_file_meta = unserialize( $submission_post_meta['audio_file_meta'][0] );
		$audio_size_bytes = $audio_file_meta['size'];
		$size = [ 'B','KB','MB' ];
		$factor = floor( ( strlen( $audio_size_bytes ) - 1 ) / 3 );
		$audio_size = sprintf( "%.2f", $audio_size_bytes / pow( 1024, $factor ) ) . $size[$factor];
		$audio_info = sprintf(
			'%s (%s %s) - %s seconds / %s ch @ %sHz',
			$audio_file_meta['name'],
			$audio_size,
			$audio_file_meta['type'],
			round( $audio_file_meta['duration'], 2 ),
			$audio_file_meta['numberOfChannels'],
			$audio_file_meta['sampleRate'],
		);

		$merge_tags = [
			'{submission_unique_id}'  => $submission_post_meta['unique_code'][0],
			'{user_submission_count}' => $number_of_posts,
			'{formatted_audio_info}'  => $audio_info,
		];

		foreach ( $merge_tags as $tag => $value ) {
			$text = str_replace( $tag, $value, $text );
		}
	}

	return $text;
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
 * @param string $field_input The input tag to be filtered.
 * @param Field $field The field that this input tag applies to.
 * @param string $value Current field value (or default).
 * @return string Updated markup for this form field.
 */
function render_accessible_select_field( $field_input, $field, $value ) {
	if ( $field->type !== 'select' || is_admin() ) {
		return $field_input;
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
			<div class="gfield_current_value"><?php echo esc_html( $value ) ; ?></div>
			<?php wmf_show_icon( 'down' ); ?>
		</button>
			<ul class="gfield_listbox" role="listbox" id="<?php echo esc_attr( "{$id}_list" ); ?>" tabindex="-1">
				<?php
				foreach ( $field->choices as $option ) {
					echo '<li class="gfield_option' .
						( $option['isSelected'] ? ' is-selected' : '' ) . '" ' .
						'data-value="' . esc_attr( $option['value'] ) . '" ' .
						'role="option">' .
						'<button type="button">' .  esc_html( $option['text'] ) . '</button>' .
						'</li>';
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


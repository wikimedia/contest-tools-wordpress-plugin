<?php
/**
 * Template for displaying audio submission information.
 *
 * @package wikimedia-contest
 */

use Wikimedia_Contest\Screening_Results;

$post_id = $post_id ?? get_the_ID();

// Gravity Forms Entry ID stored on post meta.
$gf_entry_id = get_post_meta( $post_id, '_gf_entry_id', true );

/**
 * Technical explanation provided by submitter in entry form.
 *
 * @var string
 */
$explanation_creation = get_post_meta( $post_id, 'explanation_creation', true ) ?: '';

/**
 * Explanation of the inspiration behind the submission, provided by submitter in entry form.
 *
 * @var string
 */
$explanation_inspiration = get_post_meta( $post_id, 'explanation_inspiration', true ) ?: '';

/**
 * Yes/no answers from the creation process.
 *
 * @var [] Key => value for all fields.
 */
$creation_process = get_post_meta( $post_id, 'creation_process', true ) ?: [];

/**
 * Audio file.
 *
 * @var string
 */
$audio_file =  get_post_meta( $post_id, 'audio_file', true ) ?: '';

/**
 * Audio file meta details.
 *
 * @var string
 */
$audio_file_meta =  get_post_meta( $post_id, 'audio_file_meta', true ) ?: '';

/**
 * Create more user-friendly labels for audio file metadata.
 */
$audio_file_meta_labels = [
	'name' => '<b>File Name</b>:</b> %s',
	'type' => '<b>Audio Format</b>: %s',
	'size' => '<b>File size</b>: %u B',
	'sampleRate' => '<b>Sample Rate</b>: %u Hz',
	'numberOfChannels' => '<b>Audio Channels Found</b>: %u',
	'duration' => '<b>Audio Duration</b>: %.2fs',
];

/**
 * Screening results, including automatically assigned "yellow flags".
 *
 * @var []
 */
$screening_results = Screening_Results\get_screening_results( $post_id );
$available_flags = Screening_Results\get_available_flags();

if ( $screening_results['flags'] !== null ) {
	$flags = array_intersect( $screening_results['flags'], array_keys( $available_flags )  );
}

// More readable labels for each flag.
$flag_labels = array(
	'all_original_sounds'     => __( 'Completely original work', 'wikimedia-contest-admin' ),
	'cc0_or_public_domain'    => __( 'Used sounds are CC0 or public domain', 'wikimedia-contest-admin' ),
	'used_prerecorded_sounds' => __( 'Used prerecorded sounds', 'wikimedia-contest-admin' ),
	'used_soundpack_library'  => __( 'Work from sound pack or a sample library', 'wikimedia-contest-admin' ),
	'used_samples'            => __( 'Used one or more samples', 'wikimedia-contest-admin' ),
	'source_urls'             => __( 'Source URLs of not created sounds', 'wikimedia-contest-admin' ),
);
?>

<div class="card">
	<h3>Entry ID: <?php echo esc_html( is_numberic( $gf_entry_id ) ? $gf_entry_id : '-' ); ?></h3>
	<audio controls><source src="<?php echo esc_attr( $audio_file ); ?>"></audio>

	<br><br><hr>

	<h3>Audio Metadata</h3>
	<ul>
		<?php
			foreach ( $audio_file_meta as $key => $value ) {
				echo '<li>' . sprintf( $audio_file_meta_labels[ $key ], $value ) . '</li>';
			}
		?>
	</ul>
</div>

<?php
	if ( count( $flags ?? [] ) ) {
		echo '<div class="card">';
		echo '<h3>' . esc_html__( 'Automated flags', 'wikimedia-contest-admin' ) . '</h3>';
		foreach ( $flags as $flag ) {
			echo '<span class="moderation-flag moderation-flag--yellow">' . $available_flags[ $flag ] . '</span>';
		}
		echo '</div>';
	}
?>

<div class="card">
	<h3><?php esc_html_e( 'Creation Process (answers from the submission form)', 'wikimedia-contest-admin' ); ?></h3>
	<dl>
		<?php foreach ( $creation_process as $key => $value ) : ?>
			<dt><?php echo "<b>" . esc_html( $flag_labels[ $key ] ) . "</b>"; ?></dt>
			<dd><?php echo empty( $value ) ? '<i>-</i>' : esc_html( $value ); ?></dd>
		<?php endforeach; ?>
	</dl>
</div>

<div class="card">
	<h3><?php esc_html_e( 'Brief explanation of how the sound logo was created', 'wikimedia-contest-admin' ); ?></h3>
	<div class="sound-details-textarea">
		<?php echo wpautop( $explanation_creation ); ?>
	</div>
</div>

<div class="card">
	<h3><?php esc_html_e( 'Brief explanation of what the sound logo means', 'wikimedia-contest-admin' ); ?></h3>
	<div class="sound-details-textarea">
		<?php echo wpautop( $explanation_inspiration ); ?>
	</div>
</div>

<?php
/**
 * Submission screening admin panel.
 *
 * @package wikimedia-contest
 */

use Wikimedia_Contest\Screening_Results;

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
 * Screening results, including automatically assigned "yellow flags".
 *
 * @var []
 */
$screening_results = Screening_Results\get_screening_results( $post_id );

$available_flags = Screening_Results\get_available_flags();
$flags = array_intersect( $screening_results['flags'], array_keys( $available_flags )  );

?>
<div id="screening-interface" class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Screen Submission', 'wikimedia-contest-admin' ); ?></h1>
	<hr class="wp-header-end" />

	<div class="sound">
		<div class="sound__col sound__col--title">
			<h2><?php echo get_the_title( $post_id ); ?></h2>


			<audio controls><source src="<?php echo esc_attr( $audio_file ); ?>"></audio>

			<?php
			if ( count( $flags ) ) {
				echo '<h3>' . esc_html__( 'Automated flags', 'wikimedia-contest-admin' ) . '</h3>';

				foreach ( $flags as $flag ) {
					echo '<span class="moderation-flag moderation-flag--yellow">' . $available_flags[ $flag ] . '</span>';
				}
			}
			?>

			<h3><?php esc_html_e( 'Creation Process:', 'wikimedia-contest-admin' ); ?></h3>
			<dl>
				<?php foreach ( $creation_process as $key => $value ) { ?>
					<dt><?php echo esc_html( $key ); ?></dt>
					<dd><?php echo esc_html( $value ); ?></dd>
				<?php } ?>
			</dl>
		</div>
		<div class="sound__col sound__col--technical-explanation">
			<h2><?php esc_html_e( 'Technical explanation of sound creation', 'wikimedia-contest-admin' ); ?></h2>
			<div class="sound-details-textarea">
				<?php echo wpautop( $explanation_creation ); ?>
			</div>
		</div>
		<div class="sound__col sound__col--explanation-inpiration">
			<h2><?php esc_html_e( 'Brief explanation of the inspiration behind this entry', 'wikimedia-contest-admin' ); ?></h2>
			<div class="sound-details-textarea">
				<?php echo wpautop( $explanation_inspiration ); ?>
			</div>
		</div>
	</div>
	<form method="POST">
		<div class="moderation">
			<div class="moderation__col moderation__col--flags">
				<?php wp_nonce_field( 'screen-submission', '_screen_submission_nonce' ); ?>
				<h3><?php esc_html_e( 'Moderation flags', 'wikimedia-contest-admin' ); ?></h3>
				<p class="description">
					<?php esc_html_e(
						'If any of these flags are checked, the submission is INELIGIBLE.',
						'wikimedia-contest-admin'
					); ?>
				</p>
				<ul>
				<?php
				foreach ( Screening_Results\get_moderation_flags() as $key => $value ) {
					$id = sanitize_key( "option_{$key}" );
					?>
						<li>
							<label for="<?php echo esc_attr( $id ); ?>">
								<input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="moderation-flags[<?php echo sanitize_key( $key ); ?>]" />
								<?php echo esc_html( $value ); ?>
							</label>
						</li>
					<?php
				}
				?>
				</ul>
			</div>
			<div class="moderation__col moderation__col--other">
				<div>
					<h3><?php esc_html_e( 'Other reasons for your decision', 'wikimedia-contest-admin' ); ?></h3>
					<textarea class="moderation__other widefat" name="moderation-other" cols="30" rows="10"></textarea>
					<p>
						<label>
							<input type="checkbox" name="moderation-invalid" />
							<?php esc_html_e( 'Check here if the submission should be ineligible for another reason than the flags listed.', 'wikimedia-contest-admin' ); ?>
						</label>
					</p>
				</div>
				<input class="button button-primary" type="submit" value ="<?php esc_attr_e( 'Record screening decision', 'wikimedia-contest-admin' ); ?>" />
			</div>
		</div>
	</form>
</div>

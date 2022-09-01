<?php
/**
 * Submission screening admin panel.
 *
 * @package wikimedia-contest
 */

use Wikimedia_Contest\Screening;

/**
 * Yes/no answers from the creation process.
 *
 * @var [] Key => value for all fields.
 */
$creation_process = get_post_meta( $post_id, 'creation_process', true ) ?: [];

?>
<div id="screening-interface" class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Screen Submission', 'wikimedia-contest-admin' ); ?></h1>
	<hr class="wp-header-end" />

	<form method="POST">
		<div class="carded_content_container">
			<?php include( __DIR__ . '/sound-info.php' ); ?>
		</div>

		<div class="carded_content_container">
			<div class="card">
				<?php wp_nonce_field( 'screen-submission', '_screen_submission_nonce' ); ?>
				<h3><?php esc_html_e( 'Screening flags', 'wikimedia-contest-admin' ); ?></h3>
				<p class="description">
					<?php esc_html_e(
						'If any of these flags are checked, the submission is INELIGIBLE.',
						'wikimedia-contest-admin'
					); ?>
				</p>
				<ul>
				<?php
				foreach ( Screening\get_moderation_flags() as $key => $value ) {
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
			<div class="card moderation__other">
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

				<br><br><hr>

				<button type="submit" class="moderation__submit button button-primary">
					<span class="button__text button__text--ineligible"><?php esc_html_e( 'Mark submission ineligible', 'wikimedia-contest-admin' ); ?></span>
					<span class="button__text button__text--eligible"><?php esc_html_e( 'Mark submission eligible', 'wikimedia-contest-admin' ); ?></span>
				</button>
			</div>
		</div>
	</form>
</div>

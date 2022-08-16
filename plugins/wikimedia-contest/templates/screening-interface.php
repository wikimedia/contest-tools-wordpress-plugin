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

?>
<div id="screening-interface" class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Screen Submission', 'wikimedia-contest-admin' ); ?></h1>
	<hr class="wp-header-end" />

	<div class="sound">
		<div class="sound__header">
			<div class="sound__title">
				<h2><?php echo get_the_title( $post_id ); ?></h2>
				<h3><?php esc_html_e( 'Creation Process:', 'wikimedia-contest-admin' ); ?></h3>
				<dl>
					<?php foreach ( $creation_process as $key => $value ) { ?>
						<dt><?php echo esc_html( $key ); ?></dt>
						<dd><?php echo esc_html( $value ); ?></dd>
					<?php } ?>
				</dl>
			</div>
			<div class="sound__technical-explanation">
				<h2><?php esc_html_e( 'Technical explanation of sound creation', 'wikimedia-contest-admin' ); ?></h2>
				<?php echo wpautop( $explanation_creation ); ?>
			</div>
			<div class="sound__explanation-inpiration">
				<h2><?php esc_html_e( 'Brief explanation of the inspiration behind this entry', 'wikimedia-contest-admin' ); ?></h2>
				<?php echo wpautop( $explanation_inspiration ); ?>
			</div>
		</div>
	</div>
	<div class="moderation">
		<form method="POST">
			<div class="moderation__flags">
				<?php wp_nonce_field( 'screen-submission' ); ?>
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
								<input type="checkbox" name="moderation_flags[<?php echo sanitize_key( $key ); ?>]" />
								<?php echo esc_html( $value ); ?>
							</label>
						</li>
					<?php
				}
				?>
				</ul>
			</div>
			<div class="moderation__other">
				<h3><?php esc_html_e( 'Other reasons for your decision', 'wikimedia-contest-admin' ); ?></h3>
				<textarea class="moderation__other" name="moderation__other" cols="30" rows="10"></textarea>
				<p class="description"></p>
			</div>
		</form>
	</div>
</div>

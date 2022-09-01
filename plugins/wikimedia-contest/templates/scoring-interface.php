<?php
/**
 * Submission screening admin panel.
 *
 * @package wikimedia-contest
 */

use Wikimedia_Contest\Scoring;
use const Wikimedia_Contest\Scoring\SCORING_CRITERIA;

// Retrieve, if it exists, past scoring results for the current user.
$score_given = Scoring\get_submission_score_given_by_user( $post_id, get_current_user_id() );
$score_given_weighted = Scoring\get_submission_score( $post_id, get_current_user_id() );

?>

<div id="scoring-interface" class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Score Submission', 'wikimedia-contest-admin' ); ?></h1>
	<hr class="wp-header-end" />

	<form method="POST">
		<div class="carded_content_container">
			<?php include( __DIR__ . '/sound-info.php' ); ?>
		</div>

		<div class="carded_content_container">

			<div class="card">
				<?php wp_nonce_field( 'score-submission', '_score_submission_nonce' ); ?>

				<h3><?php esc_html_e( 'Scoring criteria', 'wikimedia-contest-admin' ); ?></h3>

				<p class="description" id="scoring-instructions">
					<?php esc_html_e( 'Fill in each of the scoring criteria with a natural number, between 0 and 10.', 'wikimedia-contest-admin' ); ?>
				</p><br/>

				<table class="scoring_table">

					<?php foreach ( SCORING_CRITERIA as $category_id => $value ) : ?>

					<thead>
						<tr>
							<th class="col--a"><?php echo esc_html( sprintf( $value['label'], $value['weight'] * 100 ) ); ?></th>
							<th class="col--b">
								<?php
									if ( $score_given_weighted !== null ) {
										esc_html_e( round($score_given_weighted['by_category'][ $category_id ], 2) . " / 10" );
									} else {
										esc_html_e( '-' );
									}
								?>
							</th>
						</tr>
					</thead>


					<tbody>
						<?php foreach ( $value['criteria'] as $criteria_id => $text ) : ?>
						<tr>
							<td class="col--a"><?php echo esc_html( $text ); ?></td>
							<td class="col--b">
								<input
									class="scoring-field"
									name='scoring_criteria_<?php echo esc_attr( "{$category_id}_{$criteria_id}"); ?>'
									type='number'
									min='0'
									max='10'
									value='<?php echo esc_attr( $score_given['criteria']["scoring_criteria_{$category_id}_{$criteria_id}"] ); ?>'
								>
								&nbsp;/10
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>

					<?php endforeach; ?>

					<thead>
						<tr>
							<th class="col--a"><?php esc_html_e( 'Weighted Score' ); ?></th>
							<th class="col--b">
								<?php
									if ( $score_given_weighted !== null ) {
										esc_html_e( round( $score_given_weighted['overall'], 2) . " / 10" );
									} else {
										esc_html_e( '-' );
									}
								?>
							</th>
						</tr>
					</thead>

				</table>

			</div>

			<div class="card">

				<h3><?php esc_html_e( 'Any additional thoughts about this sound logo', 'wikimedia-contest-admin' ); ?></h3>

				<textarea class="widefat" name="additional_scoring_comment" cols="30" rows="10"><?php esc_html_e( $score_given['additional_comment'] ); ?></textarea>

				<br/><br/><hr/><br/>

				<button type="submit" class="button-primary" id="scoring-submit" disabled>
					<?php esc_html_e( 'Assign score to the submission', 'wikimedia-contest-admin' ); ?>
				</button>

			</div>

		</div>

	</form>
</div>

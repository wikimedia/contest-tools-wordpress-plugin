<?php
/**
 * Submission screening admin panel.
 *
 * @package wikimedia-contest
 */

use Wikimedia_Contest\Scoring;
use const Wikimedia_Contest\Scoring\SCORING_CRITERIA;

// Retrieve, if it exists, past scoring results for the current user.
$user_score = Scoring\get_user_score( $post_id, get_current_user_id() );
if ( $user_score !== null ) {
	// Calculate score sum for each category.
	foreach ( SCORING_CRITERIA as $category_id => $value ) {
		foreach ( $value['criteria'] as $criteria_id => $criteria_label ) {
			$current_score = $user_score['criteria']["scoring_criteria_{$category_id}_{$criteria_id}"];
			$user_score['category_sum'][ $category_id ]['sum'] += $current_score;
			$user_score['category_sum'][ $category_id ]['item_count']++;
		}
	}
}

?>

<div id="scoring-interface" class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Score Submission', 'wikimedia-contest-admin' ); ?></h1>
	<hr class="wp-header-end" />

	<div class="carded_content_container">
		<?php include( __DIR__ . '/sound-info.php' ); ?>
	</div>

	<form method="POST">

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
									if ( $user_score !== null ) {
										$category_score = round( $user_score['category_sum'][ $category_id ]['sum'] / $user_score['category_sum'][ $category_id ]['item_count'], 1 );
										esc_html_e( "{$category_score} / 10" );
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
									value='<?php echo esc_attr( $user_score['criteria']["scoring_criteria_{$category_id}_{$criteria_id}"] ); ?>'
								>
								&nbsp;/10
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>

					<?php endforeach; ?>

					<thead>
						<tr>
							<th class="col--a"><?php esc_html_e( 'Weighted Score', 'wikimedia-contest-admin' ); ?></th>
							<th class="col--b">
								<?php

									if ( $user_score !== null ) {

										// Calculate the weighted score here as it's only related to user scoring and does not affect overall sorting by score.
										foreach ( SCORING_CRITERIA as $category_id => $value ) {
											$weighted_sum += ( $user_score['category_sum'][ $category_id ]['sum'] / $user_score['category_sum'][ $category_id ]['item_count'] ) * $value['weight'];
										}

										// Displaying it rounded, as this value only reflects this specific user scoring.
										esc_html_e( round( $weighted_sum, 1) . " / 10" );
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

				<textarea class="widefat" name="additional_scoring_comment" cols="30" rows="10"><?php esc_html_e( $user_score['additional_comment'] ); ?></textarea>

				<br/><br/><hr/><br/>

				<button type="submit" class="button-primary" id="scoring-submit">
					<?php esc_html_e( 'Assign score to the submission', 'wikimedia-contest-admin' ); ?>
				</button>

			</div>

		</div>

	</form>
</div>

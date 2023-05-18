<?php
/**
 * Template for displaying submission additional information.
 *
 * @package wikimedia-contest
 */

use Wikimedia_Contest\Screening;

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
}

$submission_id = get_the_ID();
$submission_post = get_post($submission_id);
$submission_meta = get_post_meta( $submission_id );

$custom_post_statuses = get_post_stati( [
	'_builtin' => false,
	'internal' => false,
], 'objects' );

$fields_labels = [
	'participant_info' => [
		'submitter_name' => "Submitter Name",
		'submitter_email' => "Submitter Email",
		'submitter_phone' => "Submitter Phone",
		'submitter_wiki_user' => "Submitter Wiki Username",
		'submitter_country' => "Submitter Country",
		'submitter_gender' => "Submitter Gender"
	],
];

$screening_comments = Screening\get_screening_details( $submission_id );

$scoring_comments = get_comments( [
	'post_id' => $submission_id,
	'type' => 'workflow',
	'agent' => 'scoring_comment',
]);

?>
<div class="card">
	<h3>Participant info</h3>
	<ul>
		<?php
			foreach ( $fields_labels['participant_info'] as $key => $label ) {
				if ( isset( $submission_meta[$key] ) ) {
					echo "<li><b>$label</b>: {$submission_meta[$key][0]}</li>";
				}
			}

			$contributing_authors = (array) get_post_meta( $submission_id, 'contributing_authors', true );

			echo wp_sprintf(
				__( '<li><b>Contributing Authors</b>: %l</li>', 'wikimedia-contest-admin' ),
				$contributing_authors
			);
		?>
	</ul>
</div>

<div class="card">
	<h3>Additional info</h3>
	<ul>
		<?php
			global $wp_post_statuses;
			echo '<li><b>Submission status</b>: ' . $wp_post_statuses[ $submission_post->post_status ]->label . '</li>';
			echo "<li><b>Date</b>: {$submission_post->post_date}</li>";
		?>
	</ul>
</div>

<div class="card">
	<h3>Score by phase</h3>
	<ul>
		<?php
			foreach ( $custom_post_statuses as $status ) {
				echo "<li><b>{$status->label}</b>: ";
				echo ( isset( $submission_meta["score_{$status->name}"] ) ) ? array_shift( $submission_meta["score_{$status->name}"] ) : "-";
				echo "</li>";
			}
		?>
	</ul>
</div>

<div class="card fullcard">
	<h3>Screening History</h3>
	<?php
	foreach ( $screening_comments as $comment ) {

		if ( ! $comment['comment_author'] ) {
			continue;
		}

		$comment_data = json_decode( $comment['comment_content'] );
		$reasons = array_filter( array_merge( $comment_data->flags, [ $comment_data->message ] ) );
		?>
		<div class="card fullcard">
			<ul>
				<li><b>Date</b>: <?php echo esc_html( $comment['comment_date_gmt'] ); ?></li>
				<li><b>User</b>: <?php echo esc_html( $comment['comment_author'] ); ?></li>
				<li><b>Status</b>: <?php echo esc_html( $comment['comment_approved'] ); ?></li>
				<?php
				if ( count( $reasons ) ) {
					echo wp_sprintf(
						'<li><b>Reason</b>: %l</li>',
						array_map(
							function ( $flag ) {
								return Screening\get_moderation_flags()[ $flag ] ?? '"' . $flag . '"';
							},
							$reasons
						)
					);
				}
				?>
			</ul>
		</div>
		<?php
	}
	?>
</div>

<div class="card">
	<h3>Scoring History</h3>
	<?php
		foreach ( $scoring_comments as $comment ) {
			$comment_meta = get_comment_meta( $comment->comment_ID );
			$given_score = json_decode( array_shift( $comment_meta['given_score'] ), true);
			$weighted_score = \Wikimedia_Contest\Scoring\calculate_weighted_score( $given_score );

			echo "<div class='card fullcard'>";
			echo "<ul>";
			echo "<li><b>Date</b>: {$comment->comment_date}</li>";
			echo "<li><b>User</b>: {$comment->comment_author}</li>";
			echo "<li><b>Contest Phase</b>: " . array_shift( $comment_meta['scoring_phase'] ) ."</li>";
			printf( '<li><b>Weighted Score</b>: %.1f</li>', $weighted_score );
			echo "<li><b>Score by Criteria</b>:";
			echo "<ul>";

			foreach ( \Wikimedia_Contest\Scoring\SCORING_CRITERIA as $category_id => $value ) {
				foreach ( $value['criteria'] as $criteria_id => $text ) {
					echo "<li>- {$text}: ";
					echo $given_score["scoring_criteria_{$category_id}_{$criteria_id}"];
					echo "</li>";
				}
			}

			echo "</ul></li>";
			echo "<li><b>Additional Comment</b>: " . array_shift( $comment_meta['additional_comment'] ) ."</li>";
			echo "</ul>";
			echo "</div>";
		}
	?>
</div>

<?php
/**
 * Template for displaying submission additional information.
 *
 * @package wikimedia-contest
 */

$submission_id = get_the_ID();
$submission_post = get_post($submission_id);
$submission_meta = get_post_meta( $submission_id );

$custom_post_statuses = get_post_stati( [
	'_builtin' => false,
	'internal' => false,
], 'objects' );

$submission_details_keys = [
	'Identifiers' => [
		'unique_code' => "Unique Code",
		'unique_number' => "Unique Number",
	],
	'Participant Info' => [
		'submitter_name' => "Submitter Name",
		'submitter_email' => "Submitter Email",
		'submitter_phone' => "Submitter Phone",
		'submitter_wiki_user' => "Submitter Wiki Username",
		'submitter_country' => "Submitter Country",
		'submitter_gender' => "Submitter gender"
	],
	'Additional Audio Info' => [
		'audio_file_meta' => "Audio File Metadata",
	],
];

$screening_comments = get_comments( [
	'post_id' => $submission_id,
	'orderby' => 'comment_date',
	'order' => 'ASC',
	'type' => 'workflow',
	'agent' => 'screening_comment',
]);

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
			foreach ( $submission_details_keys['Participant Info'] as $key => $label ) {
				if ( isset( $submission_meta[$key] ) ) {
					echo "<li><b>$label</b>: {$submission_meta[$key][0]}</li>";
				}
			}
		?>
	</ul>
</div>

<div class="card">
	<h3>Additional info</h3>
	<ul>
		<?php
			echo "<li><b>Submission status</b>: {$submission_post->post_status}</li>";
			echo "<li><b>Date</b>: {$submission_post->post_date}</li>";
			foreach ( $submission_details_keys['Identifiers'] as $key => $label ) {
				if ( isset( $submission_meta[$key] ) ) {
					echo "<li><b>$label</b>: {$submission_meta[$key][0]}</li>";
				}
			}
		?>
	</ul>
</div>

<div class="card">
	<h3>Audio Metadata</h3>
	<ul>
		<?php
			foreach ( $submission_details_keys['Additional Audio Info'] as $key => $label ) {
				$audio_meta = unserialize( $submission_meta[$key][0] );
				foreach ( $audio_meta as $key => $value ) {
					echo "<li><b>$key</b>: {$value}</li>";
				}
			}
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
	<h3>Screening Log</h3>
	<?php
		foreach ( $screening_comments as $comment ) {
			$comment_meta = get_comment_meta( $comment->comment_ID );

			echo "<div class='card fullcard'>";
			echo "<ul>";
			echo "<li><b>Date</b>: {$comment->comment_date}</li>";
			echo "<li><b>User</b>: {$comment->comment_author}</li>";
			echo "</ul>";
			echo "</div>";
		}
	?>
</div>

<div class="card">
	<h3>Scoring Log</h3>
	<?php
		foreach ( $scoring_comments as $comment ) {
			$comment_meta = get_comment_meta( $comment->comment_ID );

			echo "<div class='card fullcard'>";
			echo "<ul>";
			echo "<li><b>Date</b>: {$comment->comment_date}</li>";
			echo "<li><b>User</b>: {$comment->comment_author}</li>";
			echo "<li><b>Contest Phase</b>: " . array_shift( $comment_meta['scoring_phase'] ) ."</li>";
			echo "<li><b>Given Score</b>: ";
			echo "<ul>";

			$given_score = json_decode( array_shift( $comment_meta['given_score'] ), true);
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

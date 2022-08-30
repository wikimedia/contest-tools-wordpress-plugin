<?php
/**
 * Functionality for providing data exports for contest admins to analyse and report on.
 *
 * @package wikimedia-contest
 */

namespace Wikimedia_Contest\Reporting;

use Wikimedia_Contest\Scoring;
use Wikimedia_Contest\Screening;
use WP_Query;

function bootstrap() {
	add_action( 'admin_menu', __NAMESPACE__ . '\\add_report_download_page' );
	add_action( 'wp_ajax_submission_report', __NAMESPACE__ . '\\output_submissions_report' );
}

/**
 * Register a page where site admins can download reports.
 *
 */
function add_report_download_page() {
	add_submenu_page(
		'edit.php?post_type=submission',
		esc_html__( 'Reports', 'wikimedia-contest-admin' ),
		esc_html__( 'Reports', 'wikimedia-contest-admin' ),
		'manage_options',
		'report-downloads',
		__NAMESPACE__ . '\\render_report_download_page'
	);
}

/**
 * Render the report download page.
 */
function render_report_download_page() {

	echo '<div class="wrap">';

	echo '<h1 class="wp-heading-inline">' . esc_html__( 'Reporting', 'wikimedia-contest-admin' ) . '</h1>';


	echo '<p><a class="button button-primary" href="' .
		wp_nonce_url( admin_url( 'admin-ajax.php?action=submission_report' ), 'submission_reports' ) .
		'">' . esc_html__( 'Download Submission Report CSV', 'wikimedia-contest-admin' ) . '</a></p>';

	echo '</div>';
}

/**
 * Output a CSV file with all submissions.
 *
 * Opens an output stream and writes a log of all submissions to it.
 *
 * @return void
 */
function output_submissions_report() {

	check_admin_referer( 'submission_reports' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You are not authorize to view reports' );
	}

	$output = fopen( 'php://output', 'w');
	$filename = 'Sound Logo Submission report ' . date( 'Y-m-d His' ) . '.csv';
	header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );

	/*
	 * Get a single post, just to properly format the headers row.
	 */
	$headers_query = get_posts( [
		'post_type' => 'submission',
		'post_status' => 'any',
		'numberposts' => 1,
	] );

	$paged = 1;

	$headers = array_keys( format_submission_for_csv( $headers_query[0] ) );
	fputcsv( $output, $headers );

	do {
		$next_page = new WP_Query( [
			'post_type' => 'submission',
			'posts_per_page' => 10,
			'paged' => $paged++,
			'post_status' => 'any',
		] );

		foreach ( $next_page->posts as $submission_post ) {
			fputcsv( $output, format_submission_for_csv( $submission_post ) );
		}
	} while ( $next_page->have_posts() );

	fclose( $output );
	exit;
}

/**
 * Format a submission entry into a line to output to CSV.
 *
 * @param WP_Post $submission Submission post object.
 * @return [] Submission fields to output.
 */
function format_submission_for_csv( $submission ) {
	$output_row = [
		'Entry ID' => $submission->post_title,
		'Submission Date' => $submission->post_modified_gmt,
		'Submitter Name' => $submission->submitter_name,
		'Submitter Email' => $submission->submitter_email,
		'Gender' => $submission->submitter_gender,
		'Country' => $submission->submitter_country,
	];

	foreach ( array_slice( Screening\get_screening_details( $submission->ID ), 0, 3 ) as $i => $comment ) {
		$index = $i + 1;
		$flags = json_decode( $comment['comment_content'] )->flags ?? [];

		$output_row = $output_row + [
			"Screener {$index} Name" => $comment['comment_author'],
			"Screener {$index} Date" => $comment['comment_date_gmt'],
			"Screener {$index} Approval" => $comment['comment_approved'],
			"Screener {$index} Reasons" => wp_sprintf( '%l', $flags ),
		];
	}

	foreach ( Scoring\SCORING_STATUSES as $scoring_status ) {
		$label = get_post_status_object( $scoring_status )->label;
		$score = get_post_meta( $submission->ID, "score_{$scoring_status}", true );
		$output_row[ "$label Avg. Weighted Score" ] = $score ? sprintf( '%.2f / 10', $score ) : '-';
	}

	return $output_row;
}

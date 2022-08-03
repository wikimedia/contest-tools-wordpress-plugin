<?php
/**
 * List table for rendering the Screening Queue.
 *
 * @extends WP_PostsList_Table
 *
 * phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
 */

namespace Wikimedia_Contest;

use WP_Posts_List_Table;

/**
 * List table for displaying all submissions awaiting screening.
 *
 * Will be a top-level menu item for screeners, and a submenu item under
 * "Submissions" for admins.
 */
class Screening_Queue_List_Table extends WP_Posts_List_Table {

	/**
	 * Override some base controls from the parent class.
	 *
	 * @param [] $args Instantiation args (ignored).
	 */
	function __construct( $args = [] ) {
		parent::__construct( [
			'singular' => __( 'Sound Logo Entry', 'wikimedia-contest-admin' ),
			'plural' => __( 'Sound Logo Entries', 'wikimedia-contest-admin' ),
			'screen' => [
				'base' => 'edit-submission',
				'post_type' => 'submission',
			],
			'ajax' => false,
		] );
	}

	/**
	 * Filter submission queries for the screening queue.
	 *
	 * Users should only be able to view submissions
	 * (a) in draft status, (b) which they have not yet judged.
	 *
	 * @param [] $sql_pieces Clauses for the WP Query request.
	 * @return [] Updated SQL clauses.
	 */
	function filter_posts_clauses( $sql_pieces ) {
		global $wpdb;
		$user_id = get_current_user_id();

		$sql_pieces['join'] .= $wpdb->prepare( "
			LEFT OUTER JOIN {$wpdb->comments}
			ON (
				{$wpdb->posts}.ID = {$wpdb->comments}.comment_post_ID
				AND {$wpdb->comments}.comment_agent = 'screening_result'
				AND {$wpdb->comments}.user_id = %d
			)
			",
			$user_id
		);

		$sql_pieces['where'] .= "
			AND {$wpdb->comments}.comment_ID IS NULL
		";

		return $sql_pieces;
	}

	/**
	 * Prepare the current query for display.
	 */
	function prepare_items() {
		global $wpdb, $wp_query, $per_page;

		// phpcs:disable HM.Security.NonceVerification.Recommended
		// phpcs:disable HM.Security.ValidatedSanitizedInput.MissingUnslash
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$per_page = $this->get_items_per_page( 'edit_submissions_per_page', 20 );
		$paged = absint( $_REQUEST['paged'] ?? 1 );

		// Add comment filters to only show posts user can screen.
		add_filter( 'posts_clauses', [ $this, 'filter_posts_clauses' ] );

		// Set up WP_Query vars.
		wp_edit_posts_query( [
			'post_type' => 'submission',
			'post_status' => 'draft',
			'per_page' => $per_page ?? 20,
			'orderby' => $_REQUEST['orderby'] ?? 'date',
			'order' => $_REQUEST['order'] ?? 'desc',
			'paged' => $_REQUEST['paged'] ?? 1,
			'cache_results' => false,
		] );

		// phpcs:enable HM.Security.NonceVerification.Recommended
		// phpcs:enable HM.Security.ValidatedSanitizedInput.MissingUnslash
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		// Ensure not to mess with any other queries on the page.
		remove_filter( 'posts_clauses', [ $this, 'filter_posts_clauses' ] );

		$this->set_pagination_args( [
			'total_items' => $wp_query->found_posts,
			'total_pages' => $wp_query->max_num_pages,
			'per_page' => $per_page,
		] );

		$this->items = $wp_query->posts;
	}

	/**
	 * Get a list of columns.
	 *
	 * @return [] Array of column slugs to titles.
	 */
	function get_columns() {
		return [
			'col_submission_id' => __( 'Submission ID', 'wikimedia-contest-admin' ),
			'col_submission_date' => __( 'Submission Date', 'wikimedia-contest-admin' ),
			'col_screening_results' => __( 'Screening Results', 'wikimedia-contest-admin' ),
		];
	}

	/**
	 * Define the columns which should be sortable.
	 *
	 * @return [] Array of column slugs to query arg.
	 */
	function get_sortable_columns() {
		return [
			'col_submission_id' => 'title',
			'col_submission_date' => [ 'date', true ],
		];
	}

	/**
	 * Render the "row actions" available to a user.
	 *
	 * @param WP_Post $item Post being output.
	 * @param string $column_name Column being output.
	 * @param string $primary Primary column in table.
	 */
	function handle_row_actions( $item, $column_name, $primary ) {
		if ( $primary !== $column_name ) {
			return;
		}

		$actions = [
			'screen' => '<a href="' . get_edit_post_link( $item->ID ) . '">' .
				esc_html__( 'Screen sound logo submission' ) .
				'</a>',
		];

		return $this->row_actions( $actions );
	}

	/**
	 * Remove bulk actions.
	 *
	 * @return [] Empty array - no bulk actions available in this view.
	 */
	function get_bulk_actions() {
		return [];
	}

	/**
	 * Render the submission ID column.
	 */
	function column_col_submission_id() {
		the_title();
	}

	/**
	 * Render the submission date column.
	 */
	function column_col_submission_date() {
		// Use core's date format strings for proper localization.
		// phpcs:disable HM.Security.EscapeOutput.OutputNotEscaped
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo sprintf(
			__( '%1$s at %2$s' ),
			get_the_time( __( 'Y/m/d' ), $post ),
			get_the_time( __( 'g:i a' ), $post )
		);
		// phpcs:enable HM.Security.EscapeOutput.OutputNotEscaped
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render the "Screening Results" column.
	 *
	 * @param WP_Post $item Item being output.
	 */
	function column_col_screening_results( $item ) {
		$screening_results = Screening_Results\get_screening_results( $item->ID );
		$available_flags = Screening_Results\get_available_flags();

		if ( $screening_results['flags'] ) {
			foreach ( $screening_results['flags'] as $flag ) {
				if ( isset( $available_flags[ $flag ] ) ) {
					echo '<span class="screening-flag">' . esc_html( $available_flags[ $flag ] ) . '</span>';
				}
			}
		}
	}
}

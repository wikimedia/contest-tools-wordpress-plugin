<?php
/**
 * List table for rendering the Scoring Queue.
 *
 * @extends WP_PostsList_Table
 *
 * phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
 */

namespace Wikimedia_Contest;

use HM\Workflows;
use Wikimedia_Contest\Scoring;
use WP_Posts_List_Table;

/**
 * List table for displaying all submissions awaiting scoring.
 *
 * Will be a top-level menu item for score panel, and a submenu item under
 * "Submissions" for admins.
 */
class Scoring_Queue_List_Table extends WP_Posts_List_Table {
	/**
	 * This property stores the phase of submissions to be displayed.
	 * @var string
	 */
	public $scoring_phase;

	/**
	 * Override some base controls from the parent class.
	 *
	 * @param [] $args Instantiation args (ignored).
	 */
	function __construct( $args = [] ) {
		parent::__construct( [
			'singular' => __( 'Sound Logo Entry', 'wikimedia-contest-admin' ),
			'plural' => __( 'Sound Logo Entries', 'wikimedia-contest-admin' ),
			'screen' => 'edit-submission-scoring-queue',
			'post_type' => 'submission',
			'ajax' => false,
		] );

		$this->scoring_phase = get_site_option( 'contest_status' ) ?: 'scoring_phase_1';
		$this->screen->post_type = 'submission';
	}

	/**
	 * Prepare the current query for display.
	 */
	function prepare_items() {
		global $wpdb, $wp_query, $per_page, $avail_post_stati;

		// phpcs:disable HM.Security.NonceVerification.Recommended
		// phpcs:disable HM.Security.ValidatedSanitizedInput.MissingUnslash
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$per_page = $this->get_items_per_page( 'edit_submissions_per_page', 20 );
		$paged = absint( $_REQUEST['paged'] ?? 1 );

		add_filter( 'pre_get_posts', [ $this, '_add_assignees_meta_query' ] );

		// Set up global WP_Query vars.
		$avail_post_stati = wp_edit_posts_query( [
			'post_type' => 'submission',
			'post_status' => $this->scoring_phase,
			'per_page' => $per_page ?? 20,
			'orderby' => $_REQUEST['orderby'] ?? 'date',
			'order' => $_REQUEST['order'] ?? 'desc',
			'paged' => $_REQUEST['paged'] ?? 1,
			'cache_results' => false,
		] );

		remove_filter( 'pre_get_posts', [ $this, '_add_assignees_meta_query' ] );

		// phpcs:enable HM.Security.NonceVerification.Recommended
		// phpcs:enable HM.Security.ValidatedSanitizedInput.MissingUnslash
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$this->set_pagination_args( [
			'total_items' => $wp_query->found_posts,
			'total_pages' => $wp_query->max_num_pages,
			'per_page' => $per_page,
		] );

		$this->items = $wp_query->posts;
	}

	/**
	 * Return only the submissions which the current users is assigned to.
	 *
	 * @param WP_Query $wp_query Main query on the scoring queue list table.
	 */
	function _add_assignees_meta_query( $wp_query ) {

		// Admins and scoring panel leads can see all posts.
		if ( current_user_can( 'assign_scorers' ) ) {
			return;
		}

		$wp_query->set( 'meta_query', [
			[
				'key' => 'assignees',
				'value' => get_current_user_id(),
			]
		] );
	}

	/**
	 * Get a list of columns.
	 *
	 * @return [] Array of column slugs to titles.
	 */
	function get_columns() {
		$columns = [
			'cb' => '<input type="checkbox">',
			'col_submission_id' => __( 'Submission ID', 'wikimedia-contest-admin' ),
			'col_submission_date' => __( 'Submission Date', 'wikimedia-contest-admin' ),
			'col_user_score' => __( 'Your scoring results	', 'wikimedia-contest-admin' ),
		];

		$custom_post_statuses = get_post_stati( [
			'_builtin' => false,
			'internal' => false,
		], 'objects' );

		if ( current_user_can( 'assign_scorers' ) ) {
			$columns["col_phase_score"] = '"' . $custom_post_statuses[ $this->scoring_phase ]->label . "\" Phase Score";
			$columns['assignees'] =  __( 'Assignees', 'wikimedia-contest-admin' );
		}

		return $columns;
	}

	/**
	 * Define the columns which should be sortable.
	 *
	 * @return [] Array of column slugs to query arg.
	 */
	function get_sortable_columns() {
		$columns = [
			'col_submission_id' => 'title',
			'col_submission_date' => [ 'date', true ],
		];

		if ( current_user_can( 'assign_scorers' ) ) {
			$columns['col_phase_score'] = [ "col_{$this->scoring_phase}_score", true ];
		}

		return $columns;
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
			'screen' => '<a href="' . esc_url( Scoring\get_scoring_link( $item->ID ) ) . '">' .
				esc_html__( 'Score sound logo submission' ) .
				'</a>',
		];

		return $this->row_actions( $actions );
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
	function column_col_submission_date( $item ) {
		// Use core's date format strings for proper localization.
		// phpcs:disable HM.Security.EscapeOutput.OutputNotEscaped
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo sprintf(
			__( '%1$s at %2$s' ),
			get_the_time( __( 'Y/m/d' ), $item ),
			get_the_time( __( 'g:i a' ), $item )
		);
		// phpcs:enable HM.Security.EscapeOutput.OutputNotEscaped
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render the score given by user column on the phase.
	 */
	function column_col_user_score( $item ) {
		$score = Scoring\get_submission_score( $item->ID, get_current_user_id() )['overall'];
		echo is_numeric( $score ) ? round( $score, 2) : '-';
	}

	/**
	 * Render the phase score column.
	 */
	function column_col_phase_score( $item ) {
		$score = get_post_meta( $item->ID, "score_{$this->scoring_phase}", true );
		echo is_numeric( $score ) ? round( $score, 2) : '-';
	}
}

<?php
/**
 * Functionality to enable scoring panelists to rate submissions.
 *
 * @package wikimedia-contest.
 */

namespace Wikimedia_Contest\Scoring_Panel;

/**
 * Post statuses corresponding to scoring phases.
 *
 * @var string[]
 */
const SCORING_PHASES = [
	'scoring_phase_1',
	'scoring_phase_2',
	'scoring_phase_3',
];

/**
 * Bootstrap scoring functionality.
 */
function bootstrap() {
	add_action( 'init', __NAMESPACE__ . '\\register_scoring_panelist_roles' );
	add_action( 'bulk_actions-edit-submission', __NAMESPACE__ . '\\add_bulk_assignment_controls' );
	add_action( 'handle_bulk_actions-edit-submission', __NAMESPACE__ . '\\handle_bulk_assignment_controls', 10, 3 );
	add_filter( 'wp_list_table_show_post_checkbox', __NAMESPACE__ . '\\show_bulk_actions_cb_for_panelist_leads', 10, 2 );
}

/**
 * Register the user roles for "scoring panelist" and "panelist lead".
 */
function register_scoring_panelist_roles() {
	$roles = get_option( 'wikimedia_contest_roles' ) ?: [];

	if ( ! isset( $roles['scoring_panel'] ) ) {
		wpcom_vip_add_role(
			'scoring_panel',
			__( 'Scoring Panelist', 'wikimedia-contest-admin' ),
			array_merge(
				get_role( 'subscriber' )->capabilities,
				[
					'score_submissions' => true,
				]
			)
		);

		wpcom_vip_add_role(
			'scoring_panel_lead',
			__( 'Scoring Panelist Lead', 'wikimedia-contest-admin' ),
			array_merge(
				get_role( 'editor' )->capabilities,
				[
					'score_submissions' => true,
					'assign_scorers' => true,
					'promote_submissions' => true,
				]
			)
		);

		$roles['scoring_panel'] = 1;
		update_option( 'wikimedia_contest_roles', $roles );
	}
}

/**
 * Update bulk actions available for scorers in the submissions list table.
 *
 * @param [] $bulk_actions Items available in the bulk actions dropdown.
 * @return [] Updated bulk actions array.
 */
function add_bulk_assignment_controls( $bulk_actions ) {

	if ( current_user_can( 'assign_scorers' ) && in_array( get_post_status(), SCORING_PHASES, true ) ) {
		$scoring_panel =
		$assignment_dropdown = [];

		foreach ( get_scoring_panel_members() as $user ) {
			$assignment_dropdown[ "assign-{$user->ID}" ] = sprintf(
				__( 'Assign %s', 'wikimedia-contest-admin' ),
				$user->display_name
			);
		}

		$bulk_actions[ __( 'Assign to', 'wikimedia-contest-admin' ) ] = $assignment_dropdown;

		$bulk_actions['remove-assignees'] = __( 'Remove assignees', 'wikimedia-contest-admin' );
	}

	if ( ! current_user_can( 'edit_submissions' ) ) {
		unset( $bulk_actions['edit'] );
		unset( $bulk_actions['trash'] );
	}

	return $bulk_actions;
}

/**
 * Allow panelist leads to see the bulk action checkboxes.
 *
 * The bulk actions functionality is normally only exposed to users with the
 * edit-posts cap. This ensures that even users who can't edit posts, but can
 * assign scorers, can use these controls.
 *
 * @param bool $show Whether to show the bulk actions checkbox.
 * @param WP_Post $post Post being rendered.
 * @return bool Whether to show the bulk checkbox.
 */
function show_bulk_actions_cb_for_panelist_leads( $show, $post ) {
	if ( in_array( $post->post_status, SCORING_PHASES, true ) && current_user_can( 'assign_scorers' ) ) {
		$show = true;
	}

	return $show;
}

/**
 * Handle user-initiated bustom bulk actions.
 *
 * @param string $return_url URL of the page to return to after completion.
 * @param string $action Name of action being performed.
 * @param int[] $post_ids Array of IDs of posts checked.
 */
function handle_bulk_assignment_controls( $return_url, $action, $post_ids ) {

	if ( strpos( $action, 'assign-' ) === 0 ) {
		$user_id = intval( substr( $action, 7 ) );

		if ( in_array( $user_id, wp_list_pluck( get_scoring_panel_members(), 'ID' ), true ) ) {
			foreach ( $post_ids as $post_id ) {
				$assignees = get_post_meta( $post_id, 'assignees' ) ?: [];
				$assignees[] = $user_id;

				delete_post_meta( $post_id, 'assignees' );
				foreach ( array_unique( array_filter( $assignees ) ) as $assignee ) {
					add_post_meta( $post_id, 'assignees', $assignee );
				}
			}
		}
	}

	if ( $action === 'remove-assignees' ) {
		foreach ( $post_ids as $post_id ) {
			delete_post_meta( $post_id, 'assignees' );
		}
	}

	wp_safe_redirect( $return_url );
}

/**
 * Get all members of the scoring panel.
 *
 * @return WP_User[] All users with the scoring panel or panelist lead roles.
 */
function get_scoring_panel_members() {
	return get_users( [ 'role__in' => [ 'scoring_panel', 'scoring_panel_lead' ] ] );
}

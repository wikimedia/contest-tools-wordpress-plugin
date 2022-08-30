<?php
/**
 * Control bulk actions for Edit Submission and Scoring Queue pages.
 *
 * @package wikimedia-contest;
 */

namespace Wikimedia_Contest\Bulk_Actions;

/**
 * Bootstrap post-type related functionality.
 */
function bootstrap() {
	add_action( 'bulk_actions-edit-submission', __NAMESPACE__ . '\\add_bulk_actions' );
	add_action( 'bulk_actions-edit-submission-scoring-queue', __NAMESPACE__ . '\\add_bulk_actions' );
	add_action( 'handle_bulk_actions-edit-submission', __NAMESPACE__ . '\\handle_bulk_actions', 10, 3 );
	add_action( 'handle_bulk_actions-edit-submission-scoring-queue', __NAMESPACE__ . '\\handle_bulk_actions', 10, 3 );
	add_filter( 'wp_list_table_show_post_checkbox', __NAMESPACE__ . '\\show_bulk_actions_cb_for_panelist_leads', 10, 2 );
	add_action( 'admin_notices', __NAMESPACE__ . '\\bulk_actions_notification' );
}

/**
 * Update bulk actions available for scorers in the submissions list table.
 *
 * @param [] $bulk_actions Items available in the bulk actions dropdown.
 * @return [] Updated bulk actions array.
 */
function add_bulk_actions( $bulk_actions ) {

	if ( current_user_can( 'assign_scorers' ) ) {

		// Assignment bulk action.
		$assignment_dropdown = [];
		foreach ( \Wikimedia_Contest\Scoring\get_scoring_panel_members() as $user ) {
			$assignment_dropdown[ "assign-{$user->ID}" ] = sprintf(
				__( 'Assign %s', 'wikimedia-contest-admin' ),
				$user->display_name
			);
		}
		$bulk_actions[ __( 'Assign to', 'wikimedia-contest-admin' ) ] = $assignment_dropdown;
		$bulk_actions['remove-assignees'] = __( 'Remove assignees', 'wikimedia-contest-admin' );

		// Status change bulk action.

		// Adding screening manually.
		$bulk_actions[ __( 'Submission status', 'wikimedia-contest-admin' ) ]["change-status-draft"] = __( 'Change to Screening', 'wikimedia-contest-admin' );

		// Adding other custom statuses.
		$custom_statuses = get_post_stati( [
			'_builtin' => false,
		], 'objects' );
		foreach ( $custom_statuses as $status ) {
			$bulk_actions[ __( 'Submission status', 'wikimedia-contest-admin' ) ]["change-status-{$status->name}"] = sprintf(
				__( 'Change to %s', 'wikimedia-contest-admin' ),
				$status->label
			);
		}
	}

	$bulk_actions['edit'] = 'Bulk Edit';
	unset( $bulk_actions['trash'] );

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
	if ( in_array( $post->post_status, \Wikimedia_Contest\Scoring\SCORING_STATUSES, true ) && current_user_can( 'assign_scorers' ) ) {
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
function handle_bulk_actions( $return_url, $action, $post_ids ) {

	if ( ! current_user_can( 'assign_scorers' ) ) {
		return;
	}

	if ( strpos( $action, 'assign-' ) === 0 ) {
		$user_id = intval( substr( $action, 7 ) );

		if ( in_array( $user_id, wp_list_pluck( \Wikimedia_Contest\Scoring\get_scoring_panel_members(), 'ID' ), true ) ) {
			foreach ( $post_ids as $post_id ) {
				$assignees = get_post_meta( $post_id, 'assignees' ) ?: [];
				$assignees[] = $user_id;

				delete_post_meta( $post_id, 'assignees' );
				foreach ( array_unique( array_filter( $assignees ) ) as $assignee ) {
					add_post_meta( $post_id, 'assignees', $assignee );
				}
			}
		}

		$return_url = add_query_arg(
			[
				'success' => 'assign',
				'user' => $user_id,
				'count' => count( $post_ids )
			],
			$return_url
		);
	}

	if ( $action === 'remove-assignees' ) {
		foreach ( $post_ids as $post_id ) {
			delete_post_meta( $post_id, 'assignees' );
		}

		$return_url = add_query_arg(
			[
				'success' => 'remove-assignees',
				'count' => count( $post_ids )
			],
			$return_url
		);
	}

	if ( strpos( $action, 'change-status' ) === 0 ) {
		$new_status = substr( $action, strlen( 'change-status-' ) );

		$possible_statuses = get_post_stati( [
			'_builtin' => false,
		], 'objects' );

		// Adding draft/screening status.
		$possible_statuses['draft'] = (object) [
			'name' => 'draft',
			'label' => __( 'Screening', 'wikimedia-contest-admin' ),
		];

		if ( array_key_exists( $new_status, $possible_statuses ) ) {

			// Updating posts status.
			foreach ( $post_ids as $post_id ) {
				wp_update_post( [
					'ID' => $post_id,
					'post_status' => $new_status,
				] );
			}

			// Checking success.
			$all_posts_have_new_status = true;
			foreach ( $post_ids as $post_id ) {
				if ( get_post_status( $post_id ) !== $new_status ) {
					$all_posts_have_new_status = false;
				}
			}

			if ( $all_posts_have_new_status ) {
				$return_url = add_query_arg(
					[
						'success' => 'change-status',
						'new-status' => $possible_statuses[ $new_status ]->label,
						'count' => count( $post_ids )
					],
					$return_url
				);
			} else {
				$return_url = add_query_arg(
					[
						'error' => 'change-status',
					],
					$return_url
				);
			}
		}
	}

	wp_safe_redirect( $return_url );
}

/**
 * Handle bulk actions success and error messages.
 *
 * @return void
 */
function bulk_actions_notification() : void {

	// Check if current page is eligible to display custom admin notices.
	$screen = get_current_screen();
	$custom_admin_notice_pages = [
		'toplevel_page_scoring-queue',
		'edit-submission',
	];
    if ( ! in_array( $screen->id, $custom_admin_notice_pages ) ) {
		return;
	}

	if ( ! empty( $_REQUEST['success'] ) ) {
		$count = intval( $_REQUEST['count'] );

		switch( $_REQUEST['success'] ):

			case 'assign':
				$user = get_user_by( 'id', $_REQUEST['user'] );
				$message = sprintf(
					/* translators: 1. number of submissions affected, 2. scoring panelist's name. */
					__( 'Assigned %d submissions to %s', 'wikimedia-contest-admin' ),
					$count,
					$user->display_name ?? 'a scorer'
				);
				break;

			case 'remove-assignees':
				$message = sprintf(
					/* translators: number of submissions affected. */
					__( 'Removed all assignees from %d submissions', 'wikimedia-contest-admin' ),
					$count
				);
				break;

			case 'change-status':
				$custom_post_statuses = get_post_stati( [
					'_builtin' => false,
					'internal' => false,
				], 'objects' );
				foreach ( $custom_post_statuses as $status ) {
					$possible_statuses[] = $status->label;
				}
				$possible_statuses[] = 'Screening';

				if ( in_array( $_REQUEST['new-status'], $possible_statuses ) ) {
					$message = sprintf(
						/* translators: 1: number of submissions affected, 2: new status for the submission. */
						__( 'Changed status of %d submissions to %s', 'wikimedia-contest-admin' ),
						$count,
						$_REQUEST['new-status']
					);
				} else {
					$message = sprintf(
						/* translators: invalid status for a submision. */
						__( 'Error: <b>%s</b> is not a valid status', 'wikimedia-contest-admin' ),
						$_REQUEST['new-status']
					);
				}
				break;

		endswitch;

		if ( ! empty( $message ) ) {
			echo '<div id="message" class="updated notice is-dismissible"><p>' .  $message . '</p></div>';
		}

	} elseif ( ! empty( $_REQUEST['error'] ) ) {

		switch( $_REQUEST['success'] ):

			case 'change-status':
				$message = sprintf(
					__( 'Error changing submissions status', 'wikimedia-contest-admin' ),
				);
				break;

		endswitch;

		if ( ! empty( $message ) ) {
			echo '<div id="message" class="error notice is-dismissible"><p>' .  esc_html( $message ) . '</p></div>';
		}
	}
}

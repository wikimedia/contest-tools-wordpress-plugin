<?php
/**
 * Setting triggers for Workflows plugin.
 *
 * @package wikimedia-contest
 */

namespace Wikimedia_Contest\Workflows_Triggers;

use HM\Workflows\Event;
use HM\Workflows\Workflow;
use Wikimedia_Contest\Network_Library;

/**
 * Bootstrap post-type related functionality.
 */
function bootstrap() {
	add_action( 'init', __NAMESPACE__ . '\\register_workflows_triggers', 0 );
	add_action( 'hm.workflows.init', __NAMESPACE__ . '\\register_network_events' );
}

/**
 * Register the triggers for Workflows plugin.
 *
 * @return void
 */
function register_workflows_triggers() : void {
	\HM\Workflows\Workflow::register( 'notify_participant_on_eligible' )
	->when( 'draft_to_eligible' )
	->what( 'Your submission is eligible' )
	->who( 'miguel@humanmade.com' )
	->where( 'email' );

	\HM\Workflows\Workflow::register( 'notify_participant_on_ineligible' )
	->when( 'draft_to_ineligible' )
	->what( 'Your submission is ineligible' )
	->who( 'miguel@humanmade.com' )
	->where( 'email' );

	\HM\Workflows\Workflow::register( 'notify_participant_on_selected' )
	->when( 'draft_to_selected' )
	->what( 'Your submission is selected' )
	->who( 'miguel@humanmade.com' )
	->where( 'email' );

	\HM\Workflows\Workflow::register( 'notify_participant_on_finalist' )
	->when( 'draft_to_finalist' )
	->what( 'Your submission is finalist' )
	->who( 'miguel@humanmade.com' )
	->where( 'email' );
}

/**
 * Register Workflows events with UI for admin notice creation.
 *
 * Registers events which can be triggered when submissions change status or
 * other reasons. Includes some cross-site lookup in case the user posts a
 * submission on a site which is not the network main site.
 *
 * @return void
 */
function register_network_events() {

	// Register a Workflows event which triggers when a new entry is submitted.
	Event::register( 'transition_submission_status_draft' )
		->add_ui( __( 'When a new entry is submitted', 'wikimedia-contest-admin' ) )
		->add_recipient_handler(
			'entrant',
			function ( $post_id ) {
				return [
					get_user_by( 'id', get_submission_value( $post_id, 'post_author' ) )
				];
			},
			__( 'Sound Logo Creator', 'wikimedia-contest-admin' )
		)
		->add_message_tags( [
			'link' => function ( $post_id ) {
				return get_submission_value( $post_id, 'guid' );
			},
			'entry_key' => function ( $post_id ) {
				return get_submission_value( $post_id, 'post_title' );
			},
		] );
}

/**
 * Get a value from a submission post object by ID.
 *
 * @param int $post_id Submission ID.
 * @param string $post_field Post field to retrieve.
 * @return mixed
 */
function get_submission_value( $post_id, $post_field ) {
	$post = Network_Library\get_submission( $post_id );

	return $post->$post_field ?? '';
}

<?php
/**
 * Setting triggers for Workflows plugin.
 *
 * @package wikimedia-contest
 */

namespace Wikimedia_Contest\Workflows_Triggers;

/**
 * Bootstrap post-type related functionality.
 */
function bootstrap() {
	add_action( 'init', __NAMESPACE__ . '\\register_workflows_triggers', 0 );
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

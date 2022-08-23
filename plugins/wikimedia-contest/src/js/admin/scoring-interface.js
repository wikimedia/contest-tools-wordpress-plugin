/**
 * Interface feedback for Submission Scoring interface.
 *
 * @package
 */

/**
 * The Screening interface.
 *
 * @member {HTMLElement}
 */
const scoringInterface = document.getElementById( 'scoring-interface' );

/**
 * Handle changes to any scoring fields
 *
 * Includes error visual feedback if user includes an invalid score.
 *
 * @param {Event} "Input" event, captured within scoring form.
 */
const checkValidScore = ( { currentTarget } ) => {
	const scoringSubmitButton = document.getElementById( 'scoring-submit' );
	const scoringInstructions = document.getElementById( 'scoring-instructions' );

	if ( ! currentTarget.value.match( /^\d+$/ ) || currentTarget.value < 0 || currentTarget.value > 10 ) {
		currentTarget.classList.add( 'scoring-error__field' );
		scoringInstructions.classList.add( 'scoring-error__message' );
		scoringSubmitButton.disabled = true;
	} else {
		currentTarget.classList.remove( 'scoring-error__field' );
		checkAllFields();
	}
};

/**
 * Check if all fields are filled out
 * If so, enable the submit button
 *
 * @returns {void}
 *
 */
const checkAllFields = () => {
	const scoringFields = document.querySelectorAll( '.scoring-field' );
	const scoringSubmitButton = document.getElementById( 'scoring-submit' );
	const scoringInstructions = document.getElementById( 'scoring-instructions' );

	let allFieldsValid = true;

	scoringFields.forEach( field => {
		if ( field.classList.contains( 'scoring-error__field' ) ) {
			allFieldsValid = false;
		}
	} );

	if ( allFieldsValid ) {
		scoringInstructions.classList.remove( 'scoring-error__message' );
		scoringSubmitButton.disabled = false;
	}
};

/**
 * Add listeners to scoring interface scoring fields.
 *
 * @returns {void}
 */
const init = () => {

	if ( ! scoringInterface ) {
		return;
	}

	scoringInterface.querySelectorAll( '.scoring-field' ).forEach( input => {
		input.addEventListener( 'input', checkValidScore );
		input.addEventListener( 'focusout', checkValidScore );
	} );
};

document.addEventListener( 'DOMContentLoaded', init );

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
 *
 * @param {string} givenScore - The score to check.
 * @returns {boolean} - Whether the score is valid.
 */
const checkValidScore = givenScore => {
	if ( ! givenScore.match( /^\d+$/ ) || givenScore < 0 || givenScore > 10 ) {
		return false;
	} else {
		return true;
	}
};

/**
 * Handle changes to any scoring fields
 *
 * Includes error visual feedback if user includes an invalid score.
 *
 * @param {Event} "Input" event, captured within scoring form.
 */
const checkValidField = ( { currentTarget } ) => {
	const scoringSubmitButton = document.getElementById( 'scoring-submit' );
	const scoringInstructions = document.getElementById( 'scoring-instructions' );

	if ( ! checkValidScore( currentTarget.value ) ) {
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
			return;
		}

		if ( ! checkValidScore( field.value ) ) {
			allFieldsValid = false;
			return;
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
		input.addEventListener( 'input', checkValidField );
		input.addEventListener( 'focusout', checkValidField );
	} );
};

document.addEventListener( 'DOMContentLoaded', init );

/**
 * Screeners' interface.
 *
 * @package
 */

/**
 * The display housing the screening interface.
 *
 * @member {HTMLElement}
 */
const screeningInterface = document.getElementById( 'screening-interface' );

/**
 * Handle changes to input state.
 *
 * Updates the submit button state to indicate whether the post will be marked
 * "eligible" or "ineligible".
 *
 * @param {Event} "Input" event, captured within screener's form.
 */
const updateSubmitButtonState = ( { currentTarget } ) => {
	// If any checkboxes are selected, the submission is ineligible.
	const isIneligible = [ ...currentTarget.querySelectorAll( 'input[type="checkbox"]:checked' ) ].length;

	currentTarget.querySelector( '.moderation__submit' ).classList.toggle( 'ineligible', !! isIneligible );
};

/**
 * Attach listeners, if necessary.
 */
const init = () => {

	if ( ! screeningInterface ) {
		return;
	}

	screeningInterface.addEventListener( 'input', updateSubmitButtonState );
};

document.addEventListener( 'DOMContentLoaded', init );

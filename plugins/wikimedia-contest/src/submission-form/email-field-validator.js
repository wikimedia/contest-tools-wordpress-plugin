/**
 * Ensure email addresses are unique per submission.
 *
 * @package
 */

const { ajaxurl, field_id } = window.submitterEmailField;

/**
 * Cached DOM sselector to hold email input field.
 *
 * @member {HTMLElement}
 */
let emailField;

/**
 * Look up the email address on change to see if it's been used.
 *
 * @param {Event} Blur event on input field.
 */
const checkEmailAddress = ( { target } ) => {
	const { value } = target;

	if ( ! value ) {
		return;
	}

	const checkRequest = fetch(
		ajaxurl,
		{
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: new URLSearchParams( [
				[ 'action', 'check_email_address' ],
				[ 'email', value ],
			] ),
		}
	);

	checkRequest
		.then( response => response.json() )
		.then( ( { success, data } ) => {
			const field = target.closest( '.gfield' );
			field.classList.toggle( 'gfield_error', ! success );
			field.querySelector( '.gfield_validation_message' ).innerHTML = data || '';
			target.toggleAttribute( 'aria-invalid', ! success );
		} )
		/* eslint-disable no-console */
		.catch( console.error );
};

/**
 * Attach event listeners.
 */
const init = () => {
	emailField = document.getElementById( field_id );
	emailField.addEventListener( 'blur', checkEmailAddress );
};

window.addEventListener( 'DOMContentLoaded', init );

if ( module.hot ) {
	module.hot.accept();
	module.hot.dispose(
		() => emailField.removeEventListener( 'blur', checkEmailAddress )
	);
	init();
}

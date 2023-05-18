/**
 * Interface for saving translations on submissions
 *
 * @package
 */

const { DOMPurify } = window;
const translationForms = [ ...document.querySelectorAll( 'form.translation' ) ];

/**
 * Handle a submission of the translation field form.
 *
 * @param {Event} event Submit event.
 */
const handleTranslationSubmit = event => {
	event.preventDefault();

	const { target, submitter } = event;

	submitter.setAttribute( 'disabled', 'disabled' );

	fetch(
		target.getAttribute( 'action' ),
		{
			method: 'POST',
			body: new URLSearchParams( new FormData( target ) ),
			credentials: 'same-origin',
			redirect: 'manual',
		}
	).then( response => response.json() )
		.then( response => {
			submitter.nextElementSibling.innerHTML = DOMPurify.sanitize( response.data );
			setTimeout( () => {
				submitter.removeAttribute( 'disabled' );
				submitter.nextElementSibling.innerHTML = '';
			}, 1500 );
		} );
};

translationForms.forEach( form => {
	form.addEventListener( 'submit', handleTranslationSubmit );
} );


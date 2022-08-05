/**
 * Controls for styled select listbox.
 *
 * @package
 */

/**
 * Cached DOM selectors to attach and remove functionality.
 *
 * @member DOMElement[]
 */
let customSelects = [];

/**
 * Initialize this functionality.
 */
const init = () => {
	customSelects = [ ...document.querySelectorAll( '.gfield_custom_select' ) ];

	console.log( customSelects );
	customSelects.forEach( setupListeners );
};

/**
 * Attach select functionality to elements within a custom field.
 *
 * @param {HTMLElement} customSelect Div containing a custom select element.
 */
const setupListeners = customSelect => {
	const button = customSelect.querySelector( '.gfield_toggle' );
	const listbox = customSelect.querySelector( '.gfield_listbox' );
	const options = listbox.querySelectorAll( '.gfield_option' );

	const hiddenInput = customSelect.querySelector( '.gfield_hidden_input' );

	/**
	 * Handle click on toggle button.
	 */
	button.handleClick = () => {
		listbox.setAttribute( 'tabindex', 1 );
		listbox.querySelector( '.gfield_option' ).focus();
	};

	button.addEventListener( 'click', button.handleClick );

	options.forEach( option => {

		/**
		 * Handle option selection.
		 */
		option.handleClick = () => {
			console.log( option );
			hiddenInput.value = option.id;
			options.forEach( opt => opt.classList.remove( 'is-selected' ) );
			option.classList.add( 'is-selected' );
		};

		option.addEventListener( 'click', option.handleCLick );
	} );
};

/**
 * Remove listeners in preparation for a hot update.
 */
const cleanup = () => customSelects.forEach(
	customSelect => {
		const button = customSelect.querySelector( '.gfield_toggle' );
		const options = customSelect.querySelectorAll( '.gfield_option' );

		button.removeEventListener( 'click', button.handleClick );

		options.forEach(
			option => option.removeEventListener( 'click', option.handleClick )
		);

	}
);

window.addEventListener( 'DOMContentLoaded', init );

if ( module.hot ) {
	module.hot.dispose( cleanup );
	module.hot.accept();
	console.log( 'hot update' );
	init();
}

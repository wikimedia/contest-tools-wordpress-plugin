/**
 * Controls for styled select listbox.
 *
 * @package
 */

const { gf_input_change } = window;

/**
 * Cached DOM selectors to attach and remove functionality.
 *
 * @member DOMElement[]
 */
let customSelects = [];

/**
 * Cached DOM selectors to attach and remove functionality.
 *
 * @member DOMElement[]
 */
let toggleButtons = [];

/**
 * Cached DOM selectors for options in each list.
 *
 * @member DOMElement[]
 */
let fieldOptions = [];

/**
 * Initialize this functionality.
 */
const init = () => {
	customSelects = [ ...document.querySelectorAll( '.gfield_custom_select' ) ];
	toggleButtons = [ ...document.querySelectorAll( '.gfield_custom_select .gfield_toggle' ) ];
	fieldOptions = [ ...document.querySelectorAll( '.gfield_custom_select .gfield_option' ) ];

	toggleButtons.forEach( toggle => toggle.addEventListener( 'click', toggleListboxVisibility ) );
	fieldOptions.forEach( option => option.addEventListener( 'click', selectOption ) );
	customSelects.forEach( select => select.addEventListener( 'focusout', handleLeaveInputField ) );
};

/**
 * Close an open listbox when focus leaves its containing div.
 *
 * @param {Event} Blur event, bubbled from any element inside the div.
 */
const handleLeaveInputField = ( { target } ) => {
	if ( ! target.closest( '.gfield_custom_select:focus-within' ) ) {
		closeListbox( target );
	}
};

/**
 * Close the listbox on selecting
 *
 * @param {HTMLElement} customSelect Div containing a custom select element.
 */
const closeListbox = customSelect => {
	const listbox = getField( customSelect, '.gfield_listbox' );
	listbox.classList.remove( 'is-opened' );
	listbox.removeEventListener( 'keydown', handleKeyboardNavigation );
	listbox.setAttribute( 'tabindex', '-1' );
	document.activeElement.scrollIntoView();
};

/**
 * Open the listbox on clicking the dropdown button.
 *
 * @param {Event} Button click event.
 */
const toggleListboxVisibility = ( { target } ) => {
	const listbox = getField( target, '.gfield_listbox' );
	listbox.setAttribute( 'tabindex', 1 );
	listbox.classList.toggle( 'is-opened' );
	listbox.addEventListener( 'keydown', handleKeyboardNavigation );

	// If an item is selected, focus that one; otherwise focus the first option.
	if ( listbox.querySelector( '.gfield_option.is-selected' ) ) {
		listbox.querySelector( '.gfield_option.is-selected button' ).focus();
	} else {
		listbox.querySelector( '.gfield_option button' ).focus();
	}
};

/**
 * Handle keyboard navigation inside the listbox.
 *
 * @param {Event} event Keypress event, captured by the listbox ul.
 * @returns mixed
 */
const handleKeyboardNavigation = event => {
	const { currentTarget, target, key } = event;
	const currentItem = document.activeElement.closest( '.gfield_option' );

	console.log( { currentTarget, currentItem, target, key } ); /* eslint-disable-line */

	switch ( key ) {
		case 'Down':
		case 'ArrowDown':
			event.preventDefault();
			return currentItem.nextElementSibling.querySelector( 'button' ).focus();
		case 'Up':
		case 'ArrowUp':
			event.preventDefault();
			return currentItem.previousElementSibling.querySelector( 'button' ).focus();
		case 'Esc':
		case 'Escape':
			return closeListbox( currentTarget );
		default:
			/* eslint-disable no-case-declarations */
			let searchPointer = currentItem;
			// for any alphabetic input, look for the next element node
			// matching that character and select it, if found.
			/* eslint-disable no-cond-assign */
			while ( searchPointer = searchPointer.nextElementSibling ) {
				if ( searchPointer.innerText.toUpperCase().startsWith( key ) ) {
					searchPointer.querySelector( 'button' ).focus();
					searchPointer.scrollIntoView( false );
				}
			}
	}
};

/**
 * Update the field selection when choosing one of the options.
 *
 * @param {Event} List item click event.
 */
const selectOption = ( { target } ) => {
	const hiddenInput = getField( target, '.gfield_hidden_input' );
	const option = target.closest( '.gfield_option' );
	const options = getFields( target, '.gfield_option' );
	const { text, value } = option.dataset;

	options.forEach( opt => opt.classList.remove( 'is-selected' ) );
	option.classList.add( 'is-selected' );

	hiddenInput.value = value;
	getField( target, '.gfield_current_value' ).innerHTML = text;

	/* eslint-disable no-unused-vars */
	const [ id, formId, fieldId ] = hiddenInput.id.match( /input_([0-9]*)_([0-9]*)/ );
	gf_input_change( hiddenInput, formId, fieldId );
	target.closest( '.gfield' ).classList.toggle( 'has-value', !! value );
	closeListbox( target );
};

/**
 * Get another field in the current select element.
 *
 * @param {HTMLElement} elt Event source.
 * @param {string} target DOM selector string indicating field to find.
 * @returns {HTMLElement} Matching element (could be null).
 */
const getField = ( elt, target ) =>
	elt.classList.contains( 'gfield_custom_select' ) ?
		elt.querySelector( target ) :
		elt.closest( '.gfield_custom_select' ).querySelector( target );

/**
 * Get other fields matching a selector in the current select element.
 *
 * @param {HTMLElement} elt Event source.
 * @param {string} target DOM selector string indicating field to find.
 * @returns {HTMLElement[]} Matching elements.
 */
const getFields = ( elt, target ) =>
	elt.classList.contains( 'gfield_custom_select' ) ?
		[ ...elt.querySelectorAll( target ) ] :
		[ ...elt.closest( '.gfield_custom_select' ).querySelectorAll( target ) ];

/**
 * Remove listeners in preparation for a hot update.
 */
const cleanup = () => {
	customSelects.forEach( select => select.removeEventListener( 'focusout', handleLeaveInputField ) );
	toggleButtons.forEach( toggle => toggle.removeEventListener( 'click', toggleListboxVisibility ) );
	fieldOptions.forEach( option => option.removeEventListener( 'click', selectOption ) );
};

window.addEventListener( 'DOMContentLoaded', init );

if ( module.hot ) {
	module.hot.dispose( cleanup );
	module.hot.accept();
	init();
}

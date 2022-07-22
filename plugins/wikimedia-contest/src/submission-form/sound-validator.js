// global: jQuery
/**
 * Functionality for validating uploaded sound files.
 *
 * Includes checks that the uploaded files:
 * - are no more than 100MB in size.
 * - are between 1 and 10s in duration.
 * - are one of the allowed file formats.
 * - are at least the required bitrate for submission.
 *
 * @package
 */

import jQuery from 'jquery';

import { speak } from '@wordpress/a11y';
import { __ } from '@wordpress/i18n';

/**
 * Reference to the browser's AudioContext.
 *
 * @member AudioContext
 */
const audioContext = new AudioContext();

/**
 * Allowed file types.
 *
 * @member {string[]}
 */
const ALLOWED_TYPES = [ 'audio/mpeg', 'video/ogg', 'audio/x-wav' ];

/**
 * Maximum upload file size: 100MB.
 */
const MAX_FILE_SIZE = 100 * 1024 * 1024;

/**
 * The file upload field on the form.
 *
 * @member {HTMLElement}
 */
const fileUploadField = document.querySelector( 'input[type="file"]' );

/**
 * Mark a validation error or message.
 *
 * @param {HTMLElement} field Upload field being checked.
 * @param {object} messageObjects Messages object, array of values each with 'error' and 'message' fields.
 */
const markValidation = ( field, messageObjects ) => {
	const messageElement = getValidationMessageElement( field );
	const isError = messageObjects.map( ( { error } ) => error ).includes( true );
	const messages = messageObjects.map( ( { message } ) => message );

	messageElement[0].innerHTML = '<ul>' +
		messageObjects.reduce( ( list, { error, message } ) => `${ list }<li class="${ error ? 'error' : 'warning' }">${ message }</li>`, '' ) +
		'</ul>';
	speak( messages.join( ', ' ) );

	field.setCustomValidity( isError ? messages.join( ', ' ) : '' );
	field.reportValidity();
};

/**
 * Get the closest validation message holder for a form field.
 *
 * This is copypasta from gravityforms.js, but there's no clear way to access
 * this directly.
 *
 * @param {HTMLElement} field Form field.
 * @returns {HTMLElement} The matching .validation_message node.
 */
const getValidationMessageElement = field => {
	if ( jQuery( field ).closest( 'div' ).siblings( '.validation_message' ).length > 0 ) {
		return jQuery( field ).closest( 'div' ).siblings( '.validation_message' );
	} else if ( jQuery( field ).siblings( '.validation_message' ).length > 0 ) {
		return jQuery( field ).siblings( '.validation_message' );
	} else {
		return jQuery( '<div class="validation_message"></div>' ).insertAfter( field );
	}
};

/**
 * Process the uploaded file.
 *
 * @param {Event} Change event on the file upload field.
 */
const validateSoundFile = async ( { target } ) => {
	const file = target.files[0];
	const validations = [];

	const { name, size, type } = file;

	// Validate file type.
	if ( ! ALLOWED_TYPES.includes( type ) ) {
		validations.push( {
			error: true,
			message: __( 'File must be one of the allowed types.', 'wikimedia-contest' ),
		} );
	}

	// Validate file size.
	if ( size > MAX_FILE_SIZE ) {
		validations.push( {
			error: true,
			message: __( 'File must be less than 100MB.', 'wikimedia-contest' ),
		} );
	}

	const fileBuffer = await file.arrayBuffer();
	const buffer = await audioContext.decodeAudioData( fileBuffer );
	const { sampleRate, numberOfChannels, duration } = buffer;

	// Validate sound duration.
	if ( duration > 10 ) {
		validations.push( {
			error: true,
			message: __( 'Sound must be less than 10s.', 'wikimedia-contest' ),
		} );
	} else if ( duration < 1 ) {
		validations.push( {
			error: true,
			message: __( 'Sound must be at least 1s.', 'wikimedia-contest' ),
		} );
	} else if ( duration > 4 ) {
		validations.push( {
			error: false,
			message: __( 'Sound should be less than 4s.', 'wikimedia-contest' ),
		} );
	}

	// TODO: Confirm that this is the correct way of getting bitrate.
	const bitRate = sampleRate * 32;

	// Validate bitrate.
	if ( type === 'audio/mp3' && bitRate < ( 192 * 1024 ) ) {
		validations.push( {
			error: false,
			message: __( 'MP3 files should be at least 192kbps.', 'wikimedia-contest' ),
		} );
	} else if ( type === 'video/ogg' && bitRate < ( 160 * 1024 ) ) {
		validations.push( {
			error: false,
			message: __( 'OGG files should be at least 160kbps.', 'wikimedia-contest' ),
		} );
	}

	// Save soundfile meta in hidden field.
	target.soundMeta = {
		name,
		type,
		size,
		sampleRate,
		numberOfChannels,
		duration,
	};

	// Mark the file validity in the field.
	markValidation( target, validations );
};

/**
 * Initialize listeners.
 */
const init = () => {
	if ( fileUploadField ) {
		fileUploadField.addEventListener( 'change', validateSoundFile );
	}
};

document.addEventListener( 'DOMContentLoaded', init );

if ( module.hot ) {
	module.hot.dispose( () => fileUploadField.removeEventListener( 'change', validateSoundFile ) );

	init();
}

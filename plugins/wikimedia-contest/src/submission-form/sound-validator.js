/**
 * Functionality for validating uploaded sound files.
 *
 * Includes checks that the uploaded files:
 * - are no more than 100MB in size.
 * - are between 1 and 10s in duration.
 * - are one of the allowed file formats.
 * - are at least the required bitrate for submission.
 *
 * @package wikimedia-contest
 */

const fileUploadField = document.querySelector( 'input[type="file"]' );

/**
 * Reference to the browser's AudioContext.
 *
 * @var AudioContext
 */
const audioContext = new AudioContext();

/**
 * Process the uploaded file.
 *
 * @param {DOMEvent} Change event on the file upload field.
 */
const validateSoundFile = async ( { target } ) => {
	const fileBuffer = await target.files[0].arrayBuffer();
	const audioBuffer = await audioContext.decodeAudioData( fileBuffer );

	console.log( audioBuffer );
};

/**
 * Initialize listeners.
 */
const init = () => {
	if ( fileUploadField ) {
		fileUploadField.addEventListener( 'change', validateSoundFile );
	}
};

document.addEventListener( 'DOMReady', 'init' );

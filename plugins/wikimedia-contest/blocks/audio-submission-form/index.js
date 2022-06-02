/**
 * Audio Submission Form block.
 */

// Start with the same config used on the server side registration.
import { __ } from '@wordpress/i18n';

import blockConfig from './block.json';

export const name = blockConfig.name;

export const settings = {
	...blockConfig,

	/**
	 * Block edit component.
	 *
	 * @returns {React.Component} Block editor render component
	 */
	edit: () => (
		<div className="audio-submission-form">
			{ __( 'Submission Form', 'wikimedia-contest' ) }
		</div>
	),

	/**
	 * Markup saved to post content.
	 *
	 * @returns {React.Component} Markup to save to post content.
	 */
	save: () => null,

};


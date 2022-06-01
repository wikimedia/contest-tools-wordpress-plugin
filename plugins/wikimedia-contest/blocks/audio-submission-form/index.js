/**
 * Audio Submission Form block.
 */

// Start with the same config used on the server side registration.
import blockConfig from './block.json';

export const name = blockConfig.name;

export const settings = {
	...blockConfig,

	edit: () => '',
	save: () => null,

};


/**
 * Audio Submission Form block.
 */

import React from 'react';

import { useBlockProps } from '@wordpress/block-editor';
import { Disabled } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

import './style.scss';

// Start with the same config used on the server side registration.
import blockConfig from './block.json';

export const name = blockConfig.name;

export const settings = {
	...blockConfig,

	/**
	 * Block edit component.
	 *
	 * @returns {React.Component} Block editor render component
	 */
	edit: () => {
		const blockProps = useBlockProps();

		return (
			<div { ...blockProps }>
				<Disabled>
					<ServerSideRender block={ name } />
				</Disabled>
			</div>
		);
	},

	/**
	 * Markup saved to post content.
	 *
	 * @returns {React.Component} Markup to save to post content.
	 */
	save: () => null,

};


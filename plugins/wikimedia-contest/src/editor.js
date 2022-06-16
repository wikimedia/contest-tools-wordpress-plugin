/**
 * Dynamically locate, load & register all Gutenberg blocks.
 */
import { autoloadBlocks } from 'block-editor-hmr';

// Load all block index files.
autoloadBlocks(
	{
		/**
		 * Block script assets are colocated with config and server-side registration in /blocks.
		 *
		 * @returns {object} Webpack context object containing entrypoints for all blocks.
		 */
		getContext: () => require.context( '../blocks', true, /index\.js$/ ),
	},
	( context, loadModules ) => {
		if ( module.hot ) {
			module.hot.accept( context.id, loadModules );
		}
	}
);

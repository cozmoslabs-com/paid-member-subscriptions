/**
 * Registers a new block provided a unique name and an object defining its behavior.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * All files containing `style` keyword are bundled together. The code used
 * gets applied both to the front of your site and to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './style.scss';

/**
 * Internal dependencies
 */
import Edit from './edit';
import metadata from './block.json';

/**
 * Every block starts by registering a new block type definition.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
registerBlockType( metadata.name, {
	icon: {
		src: <svg>
			<g>
				<path d="M12 3.5a4 4 0 1 1 0 8 4 4 0 0 1 0-8Zm0 1.25a2.75 2.75 0 1 0 0 5.5 2.75 2.75 0 0 0 0-5.5ZM5 18.25c0-2.62 2.37-4.75 5.29-4.75h2.17c.35 0 .63.28.63.63s-.28.62-.63.62h-2.17c-2.23 0-4.04 1.57-4.04 3.5v1h6.21c.35 0 .63.28.63.63s-.28.62-.63.62H5.63A.63.63 0 0 1 5 19.88v-1.63Zm13.83-4.08c.24-.24.64-.24.88 0l1.12 1.12c.24.24.24.64 0 .88l-3.96 3.96c-.07.07-.16.12-.26.15l-1.75.5a.63.63 0 0 1-.78-.78l.5-1.75c.03-.1.08-.19.15-.26l4.1-3.82Zm.44 1.33-3.52 3.52-.2.7.7-.2 3.52-3.52-.5-.5Z" />
			</g>
		</svg>
	},
	attributes: {
		is_preview: {
			type: 'boolean',
			default: false,
		},
		is_editor: {
			type: 'boolean',
			default: true,
		},
	},
	/**
	 * @see ./edit.js
	 */
	edit: Edit,
} );

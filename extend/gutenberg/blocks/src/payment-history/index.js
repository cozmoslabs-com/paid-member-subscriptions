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
				<path d="M3 5.5C3 4.67 3.67 4 4.5 4h15c.83 0 1.5.67 1.5 1.5v13c0 .83-.67 1.5-1.5 1.5h-15C3.67 20 3 19.33 3 18.5v-13Zm1.5-.25a.25.25 0 0 0-.25.25V8h15.5V5.5a.25.25 0 0 0-.25-.25h-15Zm15.25 4H4.25v9.25c0 .14.11.25.25.25h15c.14 0 .25-.11.25-.25V9.25ZM6 11h4v1.25H6V11Zm5.5 0H18v1.25h-6.5V11ZM6 14h4v1.25H6V14Zm5.5 0H18v1.25h-6.5V14Z" />
			</g>
		</svg>
	},
	attributes: {
		number_per_page: {
			type: 'string',
			default: '10',
		},
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

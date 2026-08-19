/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit( props ) {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Payment History Settings', 'paid-member-subscriptions' ) } icon="more" initialOpen={ true }>
					<TextControl
						label={ __( 'Payments per page', 'paid-member-subscriptions' ) }
						help={ __( 'Set how many payments are displayed on each page.', 'paid-member-subscriptions' ) }
						type="number"
						min="1"
						value={ props.attributes.number_per_page }
						onChange={ ( value ) => {
							props.setAttributes( {
								number_per_page: value,
							} );
						} }
					/>
				</PanelBody>
			</InspectorControls>
			<ServerSideRender
				block="pms/payment-history"
				attributes={ props.attributes }
			/>
		</div>
	);
}

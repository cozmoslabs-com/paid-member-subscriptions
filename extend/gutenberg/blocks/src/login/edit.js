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
import { useBlockProps, InspectorControls, InspectorAdvancedControls } from '@wordpress/block-editor';
import { Panel, PanelBody, PanelRow, SelectControl, TextControl }      from "@wordpress/components";
import ServerSideRender                                                from '@wordpress/server-side-render';

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
	console.log("Hello World! (from paid-member-subscriptions login block)");
	console.log(window.pmsLoginBlockConfig);

	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Redirects', 'paid-member-subscriptions' ) } icon="more" initialOpen={true}>
					<SelectControl
						label={ __( 'After Login', 'paid-member-subscriptions' ) }
						help={ __( 'Select a page for an After Login Redirect', 'paid-member-subscriptions' ) }
						value={ props.attributes.redirect_url }
						options={ window.pmsLoginBlockConfig.url_options }
						onChange={(value) => {
							props.setAttributes({
													redirect_url: value,
												});
						}}
					/>
					<SelectControl
						label={ __( 'After Logout', 'paid-member-subscriptions' ) }
						help={ __( 'Select a page for an After Logout Redirect', 'paid-member-subscriptions' ) }
						value={ props.attributes.logout_redirect_url }
						options={ window.pmsLoginBlockConfig.url_options }
						onChange={(value) => {
							props.setAttributes({
													logout_redirect_url: value,
												});
						}}
					/>
				</PanelBody>
			</InspectorControls>
			<InspectorAdvancedControls>
				<TextControl
					label={ __( 'After Registration', 'paid-member-subscriptions' ) }
					help={ __( 'Manually type in an After Login Redirect URL', 'paid-member-subscriptions' ) }
					value={ props.attributes.redirect_url }
					onChange={(value) => {
						props.setAttributes({
												redirect_url: value,
											});
					}}
				/>
				<TextControl
					label={ __( 'After Logout', 'paid-member-subscriptions' ) }
					help={ __( 'Manually type in an After Logout Redirect URL', 'paid-member-subscriptions' ) }
					value={ props.attributes.logout_redirect_url }
					onChange={(value) => {
						props.setAttributes({
												logout_redirect_url: value,
											});
					}}
				/>
			</InspectorAdvancedControls>
			<ServerSideRender
				block="pms/login"
				attributes={ props.attributes }
			/>
		</div>
	);
}

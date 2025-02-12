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
import { useBlockProps, InspectorControls, InspectorAdvancedControls }                   from '@wordpress/block-editor';
import { Button, Panel, PanelBody, PanelRow, SelectControl, TextControl, ToggleControl } from "@wordpress/components";
import { __experimentalText as Text }                                                    from '@wordpress/components';
import ServerSideRender                                                                  from '@wordpress/server-side-render';

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
	console.log("Hello World! (from paid-member-subscriptions register block)");
	console.log(window.pmsRegisterBlockConfig);

	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Form Settings', 'paid-member-subscriptions' ) } icon="more" initialOpen={true}>
					<ToggleControl
						label={__( 'Show Subscription Plans', 'paid-member-subscriptions' )}
						help={__( 'Include Subscription Plans in the form', 'paid-member-subscriptions' )}
						checked={ window.pmsRegisterBlockConfig.show_subscription_plans ? props.attributes.show_subscription_plans : false }
						disabled={ !window.pmsRegisterBlockConfig.show_subscription_plans }
						onChange={( value ) => {
							props.setAttributes( {
													 show_subscription_plans : value
												 } )
						}}
					/>
					{ !window.pmsRegisterBlockConfig.show_subscription_plans ?
						<Text>
							{__( 'To do this you need to have at least one active Subscription Plan. You may activate or create one ', 'paid-member-subscriptions' )}
							<Button
								variant="link"
								target="_blank"
								href={ window.pmsRegisterBlockConfig.button}
							>
								{__( 'here', 'paid-member-subscriptions' )}
							</Button>
							{__( '.', 'paid-member-subscriptions' )}
						</Text>
						: ''}
					{ props.attributes.show_subscription_plans === true ?
						<ToggleControl
							label={__( 'Include or Exclude', 'paid-member-subscriptions' )}
							help={__( 'Toggle to either include Subscription Plans or exclude them from the form', 'paid-member-subscriptions' )}
							checked={props.attributes.include}
							onChange={( value ) => {
								props.setAttributes( {
														 include : value
													 } )
							}}
						/>
						: ''}
					{ props.attributes.show_subscription_plans === true && props.attributes.include === true ?
						<SelectControl
							label={ __( 'Include Subscription Plans', 'paid-member-subscriptions' ) }
							help={ __( 'Select the Subscription Plans to be included in the form', 'paid-member-subscriptions' ) }
							className={ 'pms-block-select-multiple' }
							multiple={ true }
							value={ props.attributes.subscription_plans }
							options={ window.pmsRegisterBlockConfig.plans }
							onChange={(value) => {
								props.setAttributes({
														subscription_plans: value,
													});
							}}
						/>
						: ''}
					{ props.attributes.show_subscription_plans === true && props.attributes.include !== true ?
						<SelectControl
							label={ __( 'Exclude Subscription Plans', 'paid-member-subscriptions' ) }
							help={ __( 'Select the Subscription Plans to be excluded in the form', 'paid-member-subscriptions' ) }
							className={ 'pms-block-select-multiple' }
							multiple={ true }
							value={ props.attributes.exclude_subscription_plans }
							options={ window.pmsRegisterBlockConfig.plans }
							onChange={(value) => {
								props.setAttributes({
														exclude_subscription_plans: value,
													});
							}}
						/>
						: ''}
					{ props.attributes.show_subscription_plans === true ?
						<SelectControl
							label={ __( 'Selected Plan', 'paid-member-subscriptions' ) }
							help={ __( 'Choose the Subscription Plan that will be selected by default', 'paid-member-subscriptions' ) }
							value={ props.attributes.selected }
							options={ [
									{
										label: __( '' , 'paid-member-subscriptions' ),
										value: ''
									}
								].concat( window.pmsRegisterBlockConfig.plans ) }
							onChange={(value) => {
								props.setAttributes({
														selected: value,
													});
							}}
						/>
						: ''}
					{ props.attributes.show_subscription_plans === true ?
						<ToggleControl
							label={__( 'Subscription Plans at the Top', 'paid-member-subscriptions' )}
							help={__( 'Determine the position of the Subscription Plans in the form', 'paid-member-subscriptions' )}
							checked={props.attributes.plans_position}
							onChange={( value ) => {
								props.setAttributes( {
														 plans_position : value
													 } )
							}}
						/>
						: ''}
				</PanelBody>
			</InspectorControls>
			<InspectorAdvancedControls>
				{ props.attributes.show_subscription_plans === true && props.attributes.include === true ?
					<TextControl
						label={ __( 'Include Subscription Plans', 'paid-member-subscriptions' ) }
						help={ __( 'Manually type in the IDs for the Subscription Plans to be included in the form', 'paid-member-subscriptions' ) }
						value={ props.attributes.subscription_plans }
						onChange={(value) => {
							props.setAttributes({
													subscription_plans: value,
												});
						}}
					/>
					: ''}
				{ props.attributes.show_subscription_plans === true && props.attributes.include !== true ?
					<TextControl
						label={ __( 'Exclude Subscription Plans', 'paid-member-subscriptions' ) }
						help={ __( 'Manually type in the IDs for the Subscription Plans to be excluded from the form', 'paid-member-subscriptions' ) }
						value={ props.attributes.exclude_subscription_plans }
						onChange={(value) => {
							props.setAttributes({
													exclude_subscription_plans: value,
												});
						}}
					/>
					: ''}
				{ props.attributes.show_subscription_plans === true ?
					<TextControl
						label={ __( 'Selected Plan', 'paid-member-subscriptions' ) }
						help={ __( 'Manually type in the ID for a Subscription Plan that will be selected by default', 'paid-member-subscriptions' ) }
						value={ props.attributes.selected }
						onChange={(value) => {
							props.setAttributes({
													selected: value,
												});
						}}
					/>
					: ''}
			</InspectorAdvancedControls>
			<ServerSideRender
				block="pms/register"
				attributes={ props.attributes }
			/>
		</div>
	);
}

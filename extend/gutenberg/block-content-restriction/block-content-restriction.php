<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function pms_render_blocks( $block_content, $block ) {
    $block_attrs = isset( $block['attrs']['pmsContentRestriction'] ) ? $block['attrs']['pmsContentRestriction'] : null;

    // Abort if:
    // the block does not have the content restriction settings attribute or
    // the block is to be displayed to all users or
    // the current block is the Content Restriction Start block
    if ( !isset( $block_attrs ) || $block_attrs['display_to'] === 'all' || $block['blockName'] === 'pms/content-restriction-start' ) {
        return $block_content;
    }

	if ( is_array( $block_attrs['subscription_plans'] ) ){
		$block_attrs['subscription_plans'] = implode(",", $block_attrs['subscription_plans']);
	}

    // Map the block content restriction settings to the pms-restrict shortcode parameters
    $atts = array(
            'subscription_plans'    => !empty( $block_attrs['subscription_plans'] ) ? $block_attrs['subscription_plans'] : '',
            'display_to'            => $block_attrs['not_subscribed'] ? 'not_subscribed' : $block_attrs['display_to'],
            'message'               => $block_attrs['display_to'] === 'not_logged_in'
                ? ( $block_attrs['enable_message_logged_out'] ? $block_attrs['message_logged_out'] : '' )
                : ( $block_attrs['enable_message_logged_in']  ? $block_attrs['message_logged_in']  : '' ),
        );

    return '<div>' . PMS_Shortcodes::restrict_content( $atts, $block_content ) . '</div>';
}
add_filter( 'render_block', 'pms_render_blocks', 10, 2 );

/**
 * Adds the `pmsContentRestriction` attribute to all blocks
 */
add_action( 'wp_loaded', 'pms_add_custom_attributes_to_blocks', 199 );
function pms_add_custom_attributes_to_blocks() {

	$registered_blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();

	foreach( $registered_blocks as $name => $block ) {

		$block->attributes['pmsContentRestriction'] = array(
			'type'    => 'object',
            'properties' => array(
                'subscription_plans' => array(
                    'type' => 'array',
                ),
                'display_to' => array(
                    'type' => 'string',
                ),
                'not_subscribed' => array(
                    'type' => 'boolean',
                ),
                'enable_message_logged_in' => array(
                    'type' => 'boolean',
                ),
                'enable_message_logged_out' => array(
                    'type' => 'boolean',
                ),
                'message_logged_in' => array(
                    'type' => 'string',
                ),
                'message_logged_out' => array(
                    'type' => 'string',
                ),
                'panel_open' => array(
                    'type' => 'boolean',
                ),
            ),
			'default' => array(
                'subscription_plans'       => array(),
                'display_to'               => 'all',
                'not_subscribed'           => false,
                'enable_message_logged_in' => false,
                'enable_message_logged_out'=> false,
                'message_logged_in'        => '',
                'message_logged_out'       => '',
                'panel_open'               => false,
            ),
		);
	}

}

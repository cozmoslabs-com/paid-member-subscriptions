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

    return PMS_Shortcodes::restrict_content( $atts, $block_content );
}
add_filter( 'render_block', 'pms_render_blocks', 10, 2 );

<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Enables the shared Order Summary
 *
 * - call this from an add-on or core feature that needs the summary rendered on PMS forms
 * - safe to call multiple times; the first call wins
 *
 */
function pms_enable_order_summary() {

    if( class_exists( 'PMS_Order_Summary' ) )
        PMS_Order_Summary::enable();

}


/**
 * Returns the Order Summary HTML
 *
 * @param array $args
 *
 * @return string
 *
 */
function pms_get_output_order_summary( $args = array() ) {

    if( !class_exists( 'PMS_Order_Summary' ) )
        return '';

    return PMS_Order_Summary::get_output( $args );

}


/**
 * Outputs the Order Summary
 *
 * @param array $args
 *
 */
function pms_output_order_summary( $args = array() ) {

    if( !class_exists( 'PMS_Order_Summary' ) )
        return;

    PMS_Order_Summary::output( $args );

}

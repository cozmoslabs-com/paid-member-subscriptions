<?php

add_filter( 'pms_retry_payment_count', 'pms_misc_retry_payment_count' );
function pms_misc_retry_payment_count( $count ){

    $misc_settings = get_option( 'pms_misc_settings', array() );

    if( !empty( $misc_settings['payments']['payment_retry_max_retry_amount'] ) )
        return $misc_settings['payments']['payment_retry_max_retry_amount'];

    return $count;

}
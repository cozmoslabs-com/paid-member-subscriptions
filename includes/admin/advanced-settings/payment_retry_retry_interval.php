<?php

add_filter( 'pms_retry_payment_interval', 'pms_misc_retry_payment_interval' );
function pms_misc_retry_payment_interval( $count ){

    $misc_settings = get_option( 'pms_misc_settings', array() );

    if( !empty( $misc_settings['payments']['payment_retry_retry_interval'] ) )
        return $misc_settings['payments']['payment_retry_retry_interval'];

    return $count;

}
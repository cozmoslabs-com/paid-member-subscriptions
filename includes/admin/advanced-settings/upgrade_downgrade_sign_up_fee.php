<?php

add_filter( 'pms_checkout_signup_fee_form_locations', 'pmsc_enable_sign_up_fee_for_upgrade_downgrade' );
function pmsc_enable_sign_up_fee_for_upgrade_downgrade( $form_locations ){

    $form_locations[] = 'upgrade_subscription';
    $form_locations[] = 'downgrade_subscription';

    return $form_locations;

}
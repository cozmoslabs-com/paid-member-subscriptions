<?php
    if ( !empty( $settings->subscription_plans ) && is_array( $settings->subscription_plans ) )
        $plans = 'subscription_plans="'.esc_attr( implode( ',', $settings->subscription_plans ) ).'"';
    else if ( !empty( $settings->subscription_plans ) )
        $plans = 'subscription_plans="' . esc_attr( $settings->subscription_plans ) .'"';
    else
        $plans = '';

    echo do_shortcode( '[pms-register '.$plans.' selected="'.esc_attr( $settings->selected_subscription ).'" plans_position="'.esc_attr( $settings->plans_position ).'"]' );
?>

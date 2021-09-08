<?php

    if ( !empty( $instance['subscription_plans'] ) && is_array( $instance['subscription_plans'] ))
        $plans = 'subscription_plans="'.esc_attr( implode( ',', $instance['subscription_plans'] ) ).'"';
    else if ( !empty( $instance['subscription_plans'] ) )
        $plans = 'subscription_plans="' . esc_attr( $instance['subscription_plans'] ) .'"';
    else
        $plans = '';

    echo do_shortcode( '[pms-register '.$plans.' selected="'.esc_attr( $instance['selected_plan'] ).'" plans_position="'. esc_attr( $instance['plans_position'] ).'"]' );

?>

<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

Class PMS_Meta_Box_Subscription_Extra_Options extends PMS_Meta_Box {


    /*
     * Method to hook the output and save data methods
     *
     */
    public function init() {

        // Hook the output method to the parent's class action for output instead of overwriting the
        // output_content method
        add_action( 'pms_output_content_meta_box_' . $this->post_type . '_' . $this->id, array( $this, 'output' ) );

    }


    /*
     * Method to output the HTML for this meta-box
     *
     */
    public function output( $post ) {

        $subscription_plan = pms_get_subscription_plan( $post );

        include_once 'views/view-meta-box-subscription-extra-options.php';

    }

}

function pms_init_subscription_plan_extra_options_meta_box() {

    $pms_meta_box_subscription_extra_options = new PMS_Meta_Box_Subscription_Extra_Options( 'pms_subscription_extra_options', esc_html__( 'Advanced Options', 'paid-member-subscriptions' ), 'pms-subscription', 'normal' );
    $pms_meta_box_subscription_extra_options->init();

}
add_action( 'init', 'pms_init_subscription_plan_extra_options_meta_box', 2 );


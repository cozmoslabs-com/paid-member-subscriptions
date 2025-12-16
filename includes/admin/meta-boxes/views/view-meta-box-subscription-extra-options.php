<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * HTML output for subscription plan extra options meta-box
 */
?>
<div class="extra-options-wrapper">
    <?php do_action( 'pms_view_meta_box_subscription_extra_options_top', $subscription_plan ); ?>

        <?php
        
        $output = '';

        if( ( !defined( 'PMS_PAID_PLUGIN_DIR' ) || !class_exists( 'PMS_IN_ExtraSubsDiscOptions' ) ) && !class_exists('PMS_IN_PS') ) {

            // Upsell message
            $image   = '<img src="' . esc_url( PMS_PLUGIN_DIR_URL ) . 'assets/images/pms-advanced-subscription-toolkit-upsell.png" alt="Advanced Subscription Toolkit" class="pms-addon-upsell-image" />';
            $message = '';

            if ( !defined( 'PMS_PAID_PLUGIN_DIR' ) ) {
                // Upsell message
                $message = sprintf( esc_html__( 'Advanced Subscription Plan options are available only with a %1$sBasic%2$s, %1$sPro%2$s or %1$sAgency%2$s license. %3$sBuy now%4$s', 'paid-member-subscriptions' ), '<strong>', '</strong>', '<a class="button-primary" href="https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=pms-subscription-plans&utm_medium=client-site&utm_campaign=pms-advanced-subscription-toolkit-addon#pricing" target="_blank">', '</a>' );
            } elseif ( !class_exists( 'PMS_IN_ExtraSubsDiscOptions' ) ) {
                // Activate Add-On message
                $message = sprintf( esc_html__( 'Please %3$sactivate%4$s the %1$sAdvanced Subscription Toolkit%2$s Add-On to use this functionality.', 'paid-member-subscriptions' ), '<strong>', '</strong>', '<a href="'.admin_url( 'admin.php?page=pms-addons-page' ).'">', '</a>' );
            }

            $output .= '<div class="pms-addon-upsell-wrapper">';
            $output .= $image;
            $output .= '<p class="cozmoslabs-description-upsell">' . $message . '</p>';
            $output .= '</div>';

        }

        echo $output; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        ?>
    <?php do_action( 'pms_view_meta_box_subscription_extra_options_bottom', $subscription_plan );
     wp_nonce_field( 'pms_extra_subs_options_nonce', 'pms_esdo_nonce' );
     wp_nonce_field( 'pms_extra_subs_options_nonce', 'pms_ps_nonce' ); ?>
</div>
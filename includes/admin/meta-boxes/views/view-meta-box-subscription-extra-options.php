<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * HTML output for subscription plan extra options meta-box
 */
?>
<div class="extra-options-wrapper">
    <?php
        ob_start();
        do_action( 'pms_view_meta_box_subscription_extra_options_top', $subscription_plan );
        $top_output = ob_get_clean();

        ob_start();
        do_action( 'pms_view_meta_box_subscription_extra_options_bottom', $subscription_plan );
        $bottom_output = ob_get_clean();

        $upsell_output = '';

        if( !defined( 'PMS_PAID_PLUGIN_DIR' ) || !class_exists( 'PMS_IN_ExtraSubsDiscOptions' ) ) {
            $license_status      = pms_get_serial_number_status();
            $button_label        = __( 'Upgrade to Pro', 'paid-member-subscriptions' );
            $button_url          = 'https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=pms-subscription-plans&utm_medium=client-site&utm_campaign=pms-advanced-subscription-toolkit-addon#pricing';
            $title               = __( 'Upgrade to pro to use Advanced Subscription Toolkit Addon', 'paid-member-subscriptions' );
            $upsell_state        = 'license_gated';
            $button_target_attrs = ' target="_blank" rel="noopener noreferrer"';

            if ( ! class_exists( 'PMS_IN_ExtraSubsDiscOptions' ) && $license_status === 'valid' && defined( 'PMS_PAID_PLUGIN_DIR' ) ) {
                $button_label        = __( 'Activate Add-On', 'paid-member-subscriptions' );
                $button_url          = admin_url( 'admin.php?page=pms-addons-page' );
                $title               = __( 'Activate Advanced Subscription Toolkit Addon', 'paid-member-subscriptions' );
                $upsell_state        = 'inactive_addon';
                $button_target_attrs = '';
            }

            ob_start();
            ?>
            <div class="pms-gcr-upsell pms-ast-upsell" data-upsell-state="<?php echo esc_attr( $upsell_state ); ?>">
                <div class="pms-gcr-upsell__content">
                    <p class="pms-gcr-upsell__eyebrow"><?php esc_html_e( 'Premium Addon', 'paid-member-subscriptions' ); ?></p>
                    <h3 class="pms-gcr-upsell__title"><?php echo esc_html( $title ); ?></h3>
                    <p class="pms-gcr-upsell__description"><?php esc_html_e( 'Extend each subscription plan with member limits, automatic downgrades, invite codes, and purchase availability windows for more flexible membership flows.', 'paid-member-subscriptions' ); ?></p>
                    <div class="pms-gcr-upsell__actions">
                        <a class="button-primary pms-gcr-upsell__button" href="<?php echo esc_url( $button_url ); ?>"<?php echo $button_target_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $button_label ); ?></a>
                        <a class="button-secondary pms-gcr-upsell__button pms-gcr-upsell__button-secondary" href="<?php echo esc_url( 'https://www.cozmoslabs.com/add-ons/advanced-subscription-toolkit/' ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Learn More', 'paid-member-subscriptions' ); ?></a>
                    </div>
                </div>
                <div class="pms-gcr-upsell__media">
                    <div class="pms-gcr-upsell__artwork">
                        <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL . 'assets/images/addons/pms-add-on-extra-subscription-and-discount-options-logo.png' ); ?>" alt="<?php esc_attr_e( 'Advanced Subscription Toolkit', 'paid-member-subscriptions' ); ?>" class="pms-gcr-upsell__image" />
                    </div>
                </div>
            </div>
            <?php
            $upsell_output = ob_get_clean();
        }
    ?>

    <?php if ( ! empty( trim( $top_output . $bottom_output . $upsell_output ) ) ) : ?>
        <div class="pms-subscription-plan-extra-options-section">
            <?php echo $top_output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php echo $upsell_output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php echo $bottom_output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
    <?php endif; ?>
        <?php wp_nonce_field( 'pms_subscription_plan_extra_options_nonce', 'pms_subscription_plan_extra_options_nonce' ); ?>
</div>

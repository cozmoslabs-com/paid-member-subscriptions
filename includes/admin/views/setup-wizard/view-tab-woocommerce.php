<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<h3><?php esc_html_e( 'Supercharge your WooCommerce experience', 'paid-member-subscriptions' ); ?></h3>

<div class="pms-setup-next">
    <h4><?php esc_html_e( 'Offer Discounted Product Prices to Members', 'paid-member-subscriptions' ); ?></h4>

    <div class="pms-setup-line-wrap">
        <p><?php esc_html_e( 'Give your members exclusive discounts to Products. Setup individual product discounts or target categories directly.', 'paid-member-subscriptions' ); ?></p>

        <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/integration-with-other-plugins/woocommerce/#Offer_Discounted_Product_Prices_to_Members/?utm_source=wpbackend&utm_medium=pms-setup-wizard&utm_campaign=PMSFreeWoo" target="_blank" class="button secondary button-secondary button-hero">
            <?php esc_html_e( 'Learn More', 'paid-member-subscriptions' ); ?>
        </a>
    </div>
</div>

<div class="pms-setup-next">
    <h4><?php esc_html_e( 'Restrict product view and purchase', 'paid-member-subscriptions' ); ?></h4>

    <div class="pms-setup-line-wrap">
        <p><?php esc_html_e( 'Create Members-only products or restrict product purchasing, offering your different ways of presenting your products.', 'paid-member-subscriptions' ); ?></p>

        <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/integration-with-other-plugins/woocommerce/#Restrict_Product_Viewing/?utm_source=wpbackend&utm_medium=pms-setup-wizard&utm_campaign=PMSFreeWoo" target="_blank" class="button secondary button-secondary button-hero">
            <?php esc_html_e( 'Learn More', 'paid-member-subscriptions' ); ?>
        </a>
    </div>
</div>

<div class="pms-setup-next">
    <h4><?php esc_html_e( 'Sell Subscription Plans as WooCommerce Products', 'paid-member-subscriptions' ); ?></h4>

    <div class="pms-setup-line-wrap">
        <p><?php esc_html_e( 'Do you want to use another payment gateway or want to offer your customers a Subscription Plan with a Product purchase? Easily associate plans with products and start selling them through the WooCommerce Checkout.', 'paid-member-subscriptions' ); ?></p>

        <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/integration-with-other-plugins/woocommerce/#Sell_Subscription_Plans_as_WooCommerce_Products/?utm_source=wpbackend&utm_medium=pms-setup-wizard&utm_campaign=PMSFreeWoo" target="_blank" class="button secondary button-secondary button-hero">
            <?php esc_html_e( 'Learn More', 'paid-member-subscriptions' ); ?>
        </a>
    </div>
</div>

<form class="pms-setup-form" method="post">
    <div class="pms-setup-form-button">
        <input type="submit" class="button primary button-primary button-hero" value="<?php esc_html_e( 'Continue', 'paid-member-subscriptions' ); ?>" />
    </div>

    <?php wp_nonce_field( 'pms-setup-wizard-nonce', 'pms_setup_wizard_nonce' ); ?>
</form>
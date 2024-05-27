<?php
/**
 * HTML Output for the settings page, WooCommerce Integration tab
 */
?>

<div id="pms-settings-woocommerce" class="pms-tab <?php echo ( isset( $active_tab ) && $active_tab === 'woocommerce' ? 'tab-active' : '' ); ?>">

    <?php
    if ( isset( $options ))
        do_action( 'pms-settings-page_tab_woocommerce_before_content', $options );
    ?>

    <div class="cozmoslabs-form-subsection-wrapper" id="woocommerce-products">

        <h3 class="cozmoslabs-subsection-title">
            <?php esc_html_e( 'Products', 'paid-member-subscriptions' ); ?>
            <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/integration-with-other-plugins/woocommerce/?utm_source=wpbackend&utm_medium=pms-documentation&utm_campaign=PMSDocs#Allow_cumulative_discounts" target="_blank" data-code="f223" class="pms-docs-link dashicons dashicons-editor-help"></a>
        </h3>

        <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
            <label class="cozmoslabs-form-field-label" for="woocommerce-cumulative-discounts"><?php esc_html_e( 'Allow cumulative discounts', 'paid-member-subscriptions' ) ?></label>

            <div class="cozmoslabs-toggle-container">
                <input type="checkbox" id="woocommerce-cumulative-discounts" name="pms_woocommerce_settings[cumulative_discounts]" value="1" <?php echo ( isset( $options['cumulative_discounts'] ) ? checked($options['cumulative_discounts'], '1', false) : '' ); ?> />
                <label class="cozmoslabs-toggle-track" for="woocommerce-cumulative-discounts"></label>
            </div>

            <div class="cozmoslabs-toggle-description">
                <label for="woocommerce-cumulative-discounts" class="cozmoslabs-description"><?php echo wp_kses_post( __( 'By enabling this option we will cumulate all discounts that apply to a specific product.', 'paid-member-subscriptions' )); ?></label>
            </div>

            <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php echo wp_kses_post( sprintf( __( '%sBy default we\'re applying only the highest discount.%s', 'paid-member-subscriptions' ), '<strong>', '</strong>' ) ); ?></p>
        </div>

        <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
            <label class="cozmoslabs-form-field-label" for="woocommerce-exclude-on-sale"><?php esc_html_e( 'Exclude products on sale ', 'paid-member-subscriptions' ) ?></label>

            <div class="cozmoslabs-toggle-container">
                <input type="checkbox" id="woocommerce-exclude-on-sale" name="pms_woocommerce_settings[exclude_on_sale]" value="1" <?php echo ( isset( $options['exclude_on_sale'] ) ? checked($options['exclude_on_sale'], '1', false) : '' ); ?> />
                <label class="cozmoslabs-toggle-track" for="woocommerce-exclude-on-sale"></label>
            </div>

            <div class="cozmoslabs-toggle-description">
                <label for="woocommerce-exclude-on-sale" class="cozmoslabs-description"><?php esc_html_e( 'Do not apply any member discounts to products that are currently on sale.', 'paid-member-subscriptions' ); ?></label>
            </div>
        </div>

        <div class="cozmoslabs-form-field-wrapper">
            <label class="cozmoslabs-form-field-label" for="woocommerce-discount-badge"><?php esc_html_e( 'Discount Badge Text', 'paid-member-subscriptions' ) ?></label>
            <input type="text" id="woocommerce-discount-badge" class="widefat" name="pms_woocommerce_settings[discount_badge]" value="<?php echo ( isset( $options['discount_badge'] ) ? esc_attr( $options['discount_badge'] ) : esc_html__( 'Member Discount!', 'paid-member-subscriptions' ) );  ?>">
            <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'Customize your Discount Badge Message', 'paid-member-subscriptions' ); ?></p>
        </div>

        <?php do_action( 'pms-settings-page_woocommerce_products_after_content', $options ); ?>

    </div>

    <div class="cozmoslabs-form-subsection-wrapper cozmoslabs-wysiwyg-container" id="woocommerce-product-messages">

        <h3 class="cozmoslabs-subsection-title">
            <?php esc_html_e( 'Product Messages', 'paid-member-subscriptions' ); ?>
            <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/integration-with-other-plugins/woocommerce/?utm_source=wpbackend&utm_medium=pms-documentation&utm_campaign=PMSDocs#Product_Discounted_8211_Membership_Required_Custom_Message" target="_blank" data-code="f223" class="pms-docs-link dashicons dashicons-editor-help"></a>
        </h3>

        <div class="cozmoslabs-form-field-wrapper cozmoslabs-wysiwyg-wrapper cozmoslabs-wysiwyg-indented">
            <label class="cozmoslabs-form-field-label" for="woocommerce-product-discounted-message"><?php esc_html_e( 'Product Discounted - Membership Required', 'paid-member-subscriptions' ) ?></label>
            <?php wp_editor( ( isset($options['product_discounted_message']) ? wp_kses_post($options['product_discounted_message']) : __( 'Want a discount? Become a member, sign up for a subscription plan.' ,'paid-member-subscriptions') ), 'woocommerce-product-discounted-message', array( 'textarea_name' => 'pms_woocommerce_settings[product_discounted_message]', 'editor_height' => 180 ) ); ?>
            <p class="cozmoslabs-description"> <?php esc_html_e('Message displayed to non-members if the product has a membership discount. Displays below add to cart buttons. Leave blank to disable.','paid-member-subscriptions') ?></p>
        </div>

        <?php do_action( 'pms-settings-page_woocommerce_product_messages_after_content', $options ); ?>

    </div>

    <div class="cozmoslabs-form-subsection-wrapper" id="woocommerce-product-memberships">

        <h3 class="cozmoslabs-subsection-title">
            <?php esc_html_e( 'Product Memberships', 'paid-member-subscriptions' ); ?>
            <a href=" https://www.cozmoslabs.com/docs/paid-member-subscriptions/integration-with-other-plugins/woocommerce/?utm_source=wpbackend&utm_medium=pms-documentation&utm_campaign=PMSDocs#Sell_a_subscription_plan_thorough_WooCommerce" target="_blank" data-code="f223" class="pms-docs-link dashicons dashicons-editor-help"></a>
        </h3>

        <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
            <label class="cozmoslabs-form-field-label" for="woocommerce-product-subscriptions"><?php esc_html_e( 'Activate product subscriptions', 'paid-member-subscriptions' ) ?></label>

            <div class="cozmoslabs-toggle-container">
                <input type="checkbox" id="woocommerce-product-subscriptions" name="pms_woocommerce_settings[woo_product_subscriptions]" value="yes" <?php echo ( isset( $options['woo_product_subscriptions'] ) ? checked($options['woo_product_subscriptions'], 'yes', false) : '' ); ?> />
                <label class="cozmoslabs-toggle-track" for="woocommerce-product-subscriptions"></label>
            </div>

            <div class="cozmoslabs-toggle-description">
                <label for="woocommerce-product-subscriptions" class="cozmoslabs-description"><?php echo wp_kses_post( __( 'By enabling this option we will activate the <strong>Subscription Plan</strong> Tab.', 'paid-member-subscriptions' ) ); ?></label>
            </div>

            <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php echo wp_kses_post( __( 'To link a Subscription Plan to a Product go to: <strong>Administration Panel</strong> --> <strong>Products</strong>.', 'paid-member-subscriptions' ) ); ?></p>
            <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php echo wp_kses_post( __( '<strong>Edit</strong> an existing product or click on <strong>Add New</strong> to add a new product.', 'paid-member-subscriptions' ) ); ?></p>
            <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php echo wp_kses_post( __( 'After you are redirected to your product options, scroll down to <strong>Product data</strong> Section and click on <strong>Subscription Plan</strong> Tab.', 'paid-member-subscriptions' ) ); ?></p>
        </div>

    </div>

    <div class="cozmoslabs-form-subsection-wrapper" id="woocommerce-billing-details">

        <h3 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Billing Details', 'paid-member-subscriptions' ); ?></h3>

        <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
            <label class="cozmoslabs-form-field-label" for="woocommerce-add-pms-billing-details"><?php esc_html_e( 'Synchronize Billing Details', 'paid-member-subscriptions' ) ?></label>

            <div class="cozmoslabs-toggle-container">
                <input type="checkbox" id="woocommerce-add-pms-billing-details" name="pms_woocommerce_settings[sync_woo_pms_billing_details]" value="yes" <?php echo ( isset( $options['sync_woo_pms_billing_details'] ) ? checked($options['sync_woo_pms_billing_details'], 'yes', false) : '' ); ?> />
                <label class="cozmoslabs-toggle-track" for="woocommerce-add-pms-billing-details"></label>
            </div>

            <div class="cozmoslabs-toggle-description">
                <label for="woocommerce-add-pms-billing-details" class="cozmoslabs-description"><?php echo wp_kses_post( __( 'By enabling this option the PMS Billing Details and WooCommerce Billing Details will be synchronized.', 'paid-member-subscriptions' ) ); ?></label>
            </div>
        </div>


    </div>

</div>

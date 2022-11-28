<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Elementor widget for restricted product purchase message
 */
class PMS_Elementor_Product_Messages_Widget extends \Elementor\Widget_Base {

    /**
     * Get widget name.
     *
     */
    public function get_name() {
        return 'pms-product-messages';
    }

    /**
     * Get widget title.
     *
     */
    public function get_title() {
        return __( 'Product Messages', 'paid-member-subscriptions' );
    }

    /**
     * Get widget icon.
     *
     */
    public function get_icon() {
        return 'eicon-product-info';
    }

    /**
     * Get widget categories.
     *
     */
    public function get_categories() {
        return array( 'woocommerce-elements-single' );
    }

    /**
     * Register widget controls
     *
     */
    protected function register_controls() {

        // Restricted Product Message TAB
        $this->start_controls_section(
            'pms_restricted_section',
            array(
                'label' => __( 'Restricted Product Message', 'paid-member-subscriptions' ),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );

        $this->add_control(
            'pms_restricted_product_message',
            array(
                'label'       => __( 'Message for restricted product purchase', 'paid-member-subscriptions' ),
                'type'        => \Elementor\Controls_Manager::WYSIWYG,
                'description' => sprintf(__( 'This message will be displayed when the product purchase is restricted and the <strong>Add to Cart</strong> button is hidden.<br><br>If you leave this <strong>empty</strong>, the %1$sCustom Message%3$s for restricted product purchase will be displayed.<br><br>If Custom Messages are <strong>disabled</strong> or <strong>empty</strong>, the %2$sDefault Message%3$s for restricted product purchase will be displayed.', 'paid-member-subscriptions' ),
                                 '<a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/integration-with-other-plugins/woocommerce/#Restrict_Product_Purchasing" target="_blank">', '<a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/content-restriction/#Using_a_Message" target="_blank">', '</a>' ),
            )
        );

        $this->end_controls_section();


        // Membership Discount Message TAB
        $this->start_controls_section(
            'pms_discounted_section',
            array(
                'label' => __( 'Membership Discount Message', 'paid-member-subscriptions' ),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );

        $this->add_control(
            'pms_membership_discount_message',
            array(
                'label'       => __( 'Message for restricted product purchase', 'paid-member-subscriptions' ),
                'type'        => \Elementor\Controls_Manager::WYSIWYG,
                'description' => sprintf(__( 'This message will be displayed to <strong>logged out</strong> or <strong>non-member</strong> users if a product has a Membership Discount.<br><br>If you leave this <strong>empty</strong>, the %1$sProduct Discounted - Membership Required Custom Message%2$s will be displayed.', 'paid-member-subscriptions' ),
                    '<a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/integration-with-other-plugins/woocommerce/#Product_Discounted_8211_Membership_Required_Custom_Message" target="_blank">', '</a>' ),
            )
        );

        $this->end_controls_section();

    }

    /**
     * Render widget output in the front-end
     *
     */
    protected function render() {

        $settings = $this->get_settings_for_display();

        $product_id = get_the_ID();
        $pms_woo_subscription_discounts = new PMS_WOO_Subscription_Discounts();

        if ( !pms_is_product_purchasable() ) {

            if ( !empty( $settings['pms_restricted_product_message'] ))
                echo '<div class="woocommerce"> <div class="woocommerce-info pms-woo-product-restricted-message">'. $settings['pms_restricted_product_message'] . '</div> </div>'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            else echo '<div class="woocommerce"> <div class="woocommerce-info pms-woo-product-restricted-message">'. pms_get_restricted_post_message() . '</div> </div>'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        }


        if ( !$pms_woo_subscription_discounts->is_product_excluded_from_member_discounts( $product_id ) && $pms_woo_subscription_discounts->product_has_member_discounts( $product_id ) && !$pms_woo_subscription_discounts->get_user_membership_discounts( $product_id ) ) {

            if (!empty( $settings['pms_membership_discount_message'] ))
                echo '<div class="woocommerce"> <div class="woocommerce-info pms-woo-product-discounted-membership-required">'. $settings['pms_membership_discount_message'] . '</div> </div>'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            else $pms_woo_subscription_discounts->product_discounted_membership_required_message();

        }

    }

}

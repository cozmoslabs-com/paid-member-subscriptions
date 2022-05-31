<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Elementor widget for restricted product purchase message
 */
class PMS_Elementor_Product_Purchase_Restricted_Message_Widget extends \Elementor\Widget_Base {

    /**
     * Get widget name.
     *
     */
    public function get_name() {
        return 'pms-restricted-message';
    }

    /**
     * Get widget title.
     *
     */
    public function get_title() {
        return __( 'Product Restricted Message', 'paid-member-subscriptions' );
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
    protected function _register_controls() {

        $this->start_controls_section(
            'pms_content_section',
            array(
                'label' => __( 'Restricted Message', 'paid-member-subscriptions' ),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );

        $this->add_control(
            'pms_product_restricted_message',
            array(
                'label'       => __( 'Message for restricted product purchase', 'paid-member-subscriptions' ),
                'type'        => \Elementor\Controls_Manager::WYSIWYG,
                'description' => sprintf(__( 'This message will be displayed when the product purchase is restricted and the <strong>Add to Cart</strong> button is hidden.<br><br>If you leave this <strong>empty</strong>, the %1$sCustom Message%3$s for restricted product purchase will be displayed.<br><br>If Custom Messages are <strong>disabled</strong> or <strong>empty</strong>, the %2$sDefault Message%3$s for restricted product purchase will be displayed.', 'paid-member-subscriptions' ),
                                 '<a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/integration-with-other-plugins/woocommerce/#Restrict_Product_Purchasing" target="_blank">', '<a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/content-restriction/#Using_a_Message" target="_blank">', '</a>' ),
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

        if ( pms_is_product_purchasable() )
            return;

        if (!empty($settings['pms_product_restricted_message']))
            $message = $settings['pms_product_restricted_message'];
        else $message = pms_get_restricted_post_message();

        echo $message; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

    }

}

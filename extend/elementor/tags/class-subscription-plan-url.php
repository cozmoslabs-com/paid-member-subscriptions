<?php

use Elementor\Controls_Manager;
use ElementorPro\Modules\DynamicTags\Tags\Base\Data_Tag;
use ElementorPro\Modules\DynamicTags\Module;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Elementor Dynamic Tag --> subscription_plan
 * - returns the Registration URL for the selected Subscription Plan
 *
 */
class PMS_Elementor_Dynamic_Tag_Subscription_Plan extends Data_Tag {

    /**
     * Get dynamic_tag name
     */
    public function get_name() {
        return 'subscription-plan-url';
    }

    /**
     * Get dynamic_tag group
     */
    public function get_group() {
        return [ 'subscription-plans' ];
    }

    /**
     * Get dynamic_tag categories
     */
    public function get_categories() {
        return [ Module::URL_CATEGORY ];
    }

    /**
     * Get dynamic_tag title
     */
    public function get_title() {
        return esc_html__( 'Subscription Plan URL', 'paid-member-subscriptions' );
    }

    /**
     * Display selected Subscription Plan next to the dynamic_tag name
     */
    public function get_panel_template_setting_key() {
        return 'pms_subscription_plan_id';
    }

    /**
     * Generate the Registration URL for the selected Subscription Plan
     */
    public function get_value( array $options = [] ) {
        $value = '';
        $subscription_plan_id = $this->get_settings( 'pms_subscription_plan_id' );
        $single_plan = $this->get_settings( 'pms_single_plan' );
        $registration_url = $this->get_settings( 'pms_registration_url' );

        if ( !empty( $registration_url ) && !empty( $subscription_plan_id ) ) {
            $value = $registration_url . '?subscription_plan=' . $subscription_plan_id;

            if ( $single_plan == 'yes' )
                $value .= '&single_plan=yes';
        }

        return $value;
    }

    /**
     * Register dynamic_tag Controls
     */
    protected function register_controls() {
        $pms_general_settings = get_option( 'pms_general_settings', array() );
        $registration_url = get_permalink( $pms_general_settings['register_page'] );

        $this->add_control(
            'pms_registration_url',
            [
                'type' => Controls_Manager::HIDDEN,
                'default' => $registration_url,
            ]
        );

        $this->add_control(
            'pms_subscription_plan_id',
            [
                'label' => esc_html__( 'Subscription Plan', 'paid-member-subscriptions' ),
                'label_block' => 'true',
                'type' => Controls_Manager::SELECT2,
                'options' => pms_get_subscription_plans_list(),
                'condition' => [
                    'pms_registration_url!' => false,
                ],
            ]
        );

        $this->add_control(
            'pms_single_plan',
            [
                'label' => esc_html__( 'Single Plan', 'paid-member-subscriptions' ),
                'type' => Controls_Manager::SWITCHER,
                'description' => sprintf( __( '%1$sDisplay only the selected Subscription Plan on the Registration Form.%2$s', 'paid-member-subscriptions' ), '<em>', '</em>' ),
                'default' => 'yes',
                'separator' => 'before',
                'condition' => [
                    'pms_registration_url!' => false,
                ],
            ]
        );

        $this->add_control(
            'pms_subscription_plan_url_heading',
            [
                'label' => esc_html__( 'NOTICE:', 'paid-member-subscriptions' ),
                'label_block' => 'true',
                'type' => Controls_Manager::HEADING,
                'condition' => [
                    'pms_registration_url' => false,
                ],
            ]
        );

        $this->add_control(
            'pms_subscription_plan_url_notice',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => '<p>' . sprintf( __( 'The %1$sRegister Page%2$s is not selected in  %3$sPaid Member Subscriptions --> Settings%4$s', 'paid-member-subscriptions' ), '<strong>', '</strong>','<a href="'. admin_url('admin.php?page=pms-settings-page') .'" target="_blank">', '</a>' ) . '</p>',
                'condition' => [
                    'pms_registration_url' => false,
                ],
            ]
        );

    }

}
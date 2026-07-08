<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class PMS_Meta_Box_Subscription_Section extends PMS_Meta_Box {

    /**
     * View file relative to the meta-boxes/views directory.
     *
     * @var string
     */
    public $view_file = '';

    public function __construct( $id = '', $title = '', $post_type = '', $context = 'advanced', $view_file = '' ) {
        $this->view_file = $view_file;

        parent::__construct( $id, $title, $post_type, $context );
    }

    public function init() {
        add_action( 'pms_output_content_meta_box_' . $this->post_type . '_' . $this->id, array( $this, 'output' ) );
    }

    public function output( $post ) {
        $subscription_plan = pms_get_subscription_plan( $post );

        include PMS_PLUGIN_DIR_PATH . 'includes/admin/meta-boxes/views/' . $this->view_file;
    }

}

class PMS_Meta_Box_Subscription_Content_Restriction_Upsell extends PMS_Meta_Box {

    public function init() {
        add_action( 'pms_output_content_meta_box_' . $this->post_type . '_' . $this->id, array( $this, 'output' ) );
    }

    public function output( $post ) {
        $subscription_plan      = pms_get_subscription_plan( $post );
        $is_gcr_addon_active    = apply_filters( 'pms_add_on_is_active', false, 'pms-add-on-global-content-restriction/index.php' );
        $pms_gcr_upsell_context = $is_gcr_addon_active ? 'license_gated' : 'inactive_addon';

        include PMS_PLUGIN_DIR_PATH . 'includes/admin/meta-boxes/views/view-meta-box-subscription-content-restriction-upsell.php';
    }

}

function pms_init_subscription_plan_section_meta_boxes() {

    $meta_boxes = array(
        array(
            'id'        => 'pms_subscription_price',
            'title'     => esc_html__( 'Price', 'paid-member-subscriptions' ),
            'view_file' => 'view-meta-box-subscription-price.php',
        ),
        array(
            'id'        => 'pms_subscription_access_behavior',
            'title'     => esc_html__( 'Access & Membership Behavior', 'paid-member-subscriptions' ),
            'view_file' => 'view-meta-box-subscription-access.php',
        ),
        array(
            'id'        => 'pms_subscription_integrations',
            'title'     => esc_html__( 'Integrations', 'paid-member-subscriptions' ),
            'view_file' => 'view-meta-box-subscription-integrations.php',
        ),
        array(
            'id'        => 'pms_subscription_pause',
            'title'     => esc_html__( 'Pause Subscription', 'paid-member-subscriptions' ),
            'view_file' => 'view-meta-box-subscription-pause.php',
        ),
    );

    foreach ( $meta_boxes as $meta_box ) {
        $section_meta_box = new PMS_Meta_Box_Subscription_Section(
            $meta_box['id'],
            $meta_box['title'],
            'pms-subscription',
            'normal',
            $meta_box['view_file']
        );

        $section_meta_box->init();
    }

    if ( ! apply_filters( 'pms_add_on_is_active', false, 'pms-add-on-global-content-restriction/index.php' ) ) {
        $content_restriction_meta_box = new PMS_Meta_Box_Subscription_Content_Restriction_Upsell(
            'pms_subscription_content_restriction',
            esc_html__( 'Global Content Restriction', 'paid-member-subscriptions' ),
            'pms-subscription',
            'normal'
        );

        $content_restriction_meta_box->init();
    }

}
add_action( 'init', 'pms_init_subscription_plan_section_meta_boxes', 3 );

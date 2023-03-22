<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

use Elementor\Controls_Manager;

class PMS_Elementor {
    private static $_instance = null;
    public $locations = array(
        array(
            'element' => 'common',
            'action'  => '_section_style',
        ),
        array(
            'element' => 'section',
            'action'  => 'section_advanced',
        ),
        array(
            'element' => 'container',
            'action'  => 'section_layout',
        )
    );
    public $section_name = 'pms_section_visibility_settings';

	/**
	 * Register plugin action hooks and filters
	 */
	public function __construct() {
        // Add category
        add_action( 'elementor/elements/categories_registered', array( $this, 'add_category' ) );

		// Register widgets
		add_action( 'elementor/widgets/widgets_registered', array( $this, 'register_widgets' ) );

        // Register dynamic_tag Groups
		add_action( 'elementor/dynamic_tags/register', array( $this, 'register_dynamic_tag_groups' ) );

        // Register Dynamic Tags
		add_action( 'elementor/dynamic_tags/register', array( $this, 'register_dynamic_tags' ) );

        // Load Elements restriction class
        if( apply_filters( 'pms_elementor_enable_content_restriction', true ) )
            require_once( __DIR__ . '/class-elementor-elements-restriction.php' );

        // Register new section to display restriction controls
        $this->register_sections();

        $this->content_restriction();
	}

    /**
     *
     * Ensures only one instance of the class is loaded or can be loaded.
     *
     * @return PMS_Elementor An instance of the class.
     */
    public static function instance() {
        if ( is_null( self::$_instance ) )
            self::$_instance = new self();

        return self::$_instance;
    }

	/**
	 * Include Widgets files
	 */
	private function include_widgets_files() {
		require_once( __DIR__ . '/widgets/class-widget-account.php' );
		require_once( __DIR__ . '/widgets/class-widget-login.php' );
		require_once( __DIR__ . '/widgets/class-widget-recover-password.php' );
		require_once( __DIR__ . '/widgets/class-widget-register.php' );
		require_once( __DIR__ . '/widgets/class-widget-product-purchase-restricted-message.php' );
	}

	/**
	 * Register Widgets
	 */
	public function register_widgets() {
		$this->include_widgets_files();

        if( version_compare( ELEMENTOR_VERSION, '3.5,', '>=' ) ){
            \Elementor\Plugin::instance()->widgets_manager->register( new PMS_Elementor_Account_Widget() );
            \Elementor\Plugin::instance()->widgets_manager->register( new PMS_Elementor_Login_Widget() );
            \Elementor\Plugin::instance()->widgets_manager->register( new PMS_Elementor_Recover_Password_Widget() );
            \Elementor\Plugin::instance()->widgets_manager->register( new PMS_Elementor_Register_Widget() );
            \Elementor\Plugin::instance()->widgets_manager->register( new PMS_Elementor_Product_Messages_Widget() );
        } else {
            \Elementor\Plugin::instance()->widgets_manager->register_widget_type( new PMS_Elementor_Account_Widget() );
            \Elementor\Plugin::instance()->widgets_manager->register_widget_type( new PMS_Elementor_Login_Widget() );
            \Elementor\Plugin::instance()->widgets_manager->register_widget_type( new PMS_Elementor_Recover_Password_Widget() );
            \Elementor\Plugin::instance()->widgets_manager->register_widget_type( new PMS_Elementor_Register_Widget() );
            \Elementor\Plugin::instance()->widgets_manager->register_widget_type( new PMS_Elementor_Product_Messages_Widget() );
        }
	}

    private function register_sections() {
        foreach( $this->locations as $where ) {
            add_action( 'elementor/element/'.$where['element'].'/'.$where['action'].'/after_section_end', array( $this, 'add_section' ), 10, 2 );
        }
    }

    public function add_category( $elements_manager ) {
        $elements_manager->add_category(
            'paid-member-subscriptions',
            array(
                'title' => __( 'Paid Member Subscriptions Shortcodes', 'paid-member-subscriptions' ),
                'icon'  => 'fa fa-plug',
            )
        );
    }

    public function add_section( $element, $args ) {
        $exists = \Elementor\Plugin::instance()->controls_manager->get_control_from_stack( $element->get_unique_name(), $this->section_name );

        if( !is_wp_error( $exists ) )
            return false;

        $element->start_controls_section(
            $this->section_name, array(
                'tab'   => Controls_Manager::TAB_ADVANCED,
                'label' => __( 'Content Restriction', 'paid-member-subscriptions' )
            )
        );

        $element->end_controls_section();
    }

    protected function content_restriction(){}

    /**
     * Register dynamic_tag Groups
     */
    public function register_dynamic_tag_groups( $dynamic_tags_manager ) {
        $dynamic_tags_manager->register_group(
            'subscription-plans',
            [
                'title' => esc_html__( 'Subscription Plans', 'paid-member-subscriptions' )
            ]
        );
    }

    /**
     * Include Dynamic Tags files
     */
    private function include_dynamic_tags_files() {
        require_once( __DIR__ . '/tags/class-subscription-plan-url.php' );
    }

    /**
     * Register Dynamic Tags
     */
    public function register_dynamic_tags( $dynamic_tags_manager ) {
        if ( class_exists ( 'ElementorPro\Modules\DynamicTags\Tags\Base\Data_Tag' ) ) {
            $this->include_dynamic_tags_files();

            $dynamic_tags_manager->register(new \PMS_Elementor_Dynamic_Tag_Subscription_Plan);
        }
    }
}

// Instantiate Plugin Class
PMS_Elementor::instance();

<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handles Order Summary frontend placement
 *
 */
class PMS_Order_Summary {

    /**
     * Whether any add-on or core feature has enabled the shared Order Summary
     *
     * @var bool
     *
     */
    private static $enabled = false;


    /**
     * Initializes the Order Summary placement controller
     *
     */
    public function __construct() {

        // register placement after add-ons have had a chance to enable the summary
        add_action( 'plugins_loaded', array( $this, 'register_placement_hooks' ), 20 );

        // enqueue the shared frontend script when the summary is active
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_order_summary_script' ), 15 );

    }


    /**
     * Marks the shared Order Summary as enabled
     *
     * - add-ons call this from their constructor (via the pms_enable_order_summary() helper) to declare they need the summary rendered
     *
     */
    public static function enable() {

        self::$enabled = true;

    }


    /**
     * Registers the frontend placement hooks
     *
     */
    public function register_placement_hooks() {

        if( !self::is_enabled() )
            return;

        // Different placement when form designs are active
        if( function_exists( 'pms_get_active_form_design' ) && in_array( pms_get_active_form_design(), array( 'form-style-1', 'form-style-2', 'form-style-3' ) ) ){

            add_action( 'pms_get_output_payment_gateways_after_paygates', array( $this, 'output_payment_gateways_order_summary' ), 60, 2 );
            add_action( 'pms_pb_add_form_extra_fields_after_output', array( $this, 'output_order_summary' ), 60 );

        } else {

            add_action( 'pms_register_form_bottom',                  array( $this, 'output_order_summary' ), 60 );
            add_action( 'pms_new_subscription_form_bottom',          array( $this, 'output_order_summary' ), 60 );
            add_action( 'pms_upgrade_subscription_form_bottom',      array( $this, 'output_order_summary' ), 60 );
            add_action( 'pms_renew_subscription_form_bottom',        array( $this, 'output_order_summary' ), 60 );
            add_action( 'pms_change_subscription_form_bottom',       array( $this, 'output_order_summary' ), 60 );
            add_action( 'pms_retry_payment_form_bottom',             array( $this, 'output_order_summary' ), 60 );
            add_action( 'pms_gift_subscription_form_bottom',         array( $this, 'output_order_summary' ), 60 );
            add_action( 'pms_pb_add_form_extra_fields_after_output', array( $this, 'output_order_summary' ), 60 );

        }

    }


    /**
     * Checks if the shared Order Summary is enabled
     *
     * @return bool
     *
     */
    public static function is_enabled() {

        $misc_settings = get_option( 'pms_misc_settings', array() );

        return self::$enabled || !empty( $misc_settings['payments']['order_summary'] );

    }


    /**
     * Enqueues the shared Order Summary frontend script
     *
     */
    public function enqueue_order_summary_script() {

        if( !self::is_enabled() )
            return;

        if( !pms_should_load_scripts() )
            return;

        $payments_settings = get_option( 'pms_payments_settings', array() );
        $active_currency   = pms_get_active_currency();

        wp_enqueue_script( 'pms-order-summary', PMS_PLUGIN_DIR_URL . 'assets/js/order-summary.js', array( 'jquery' ), PMS_VERSION, true );

        /**
         * Filters the data localized for the Order Summary frontend script
         *
         * - addons that change the rendering currency at request time (Multiple Currencies in particular) hook this to swap `currency`, `currency_symbol` and `currency_position`
         * - keeps the summary's static currency context in sync with the dynamically-computed per-row amounts the addon's contributors emit
         *
         * @param array $data Localized data passed to wp_localize_script
         */
        $localized_data = apply_filters( 'pms_order_summary_localize_data', array(
            'currency'                => $active_currency,
            'currency_symbol'         => pms_get_currency_symbol( $active_currency ),
            'currency_position'       => pms_get_currency_position(),
            'zero_decimal_currencies' => pms_get_zero_decimal_currencies(),
            'locale'                  => str_replace( '_', '-', get_locale() ),
            'price_trim_zeroes'       => ( !isset( $payments_settings['price-display-format'] ) || $payments_settings['price-display-format'] == 'without_insignificant_zeroes' ) ? 'true' : 'false',
            'default_item_label'      => __( 'Subscription Plan', 'paid-member-subscriptions' ),
        ) );

        wp_localize_script( 'pms-order-summary', 'pms_order_summary', $localized_data );

    }


    /**
     * Returns the Order Summary HTML
     *
     * @param array $args
     *
     * @return string
     *
     */
    public static function get_output( $args = array() ) {

        $defaults = array(
            'heading' => __( 'Your Purchase', 'paid-member-subscriptions' ),
        );

        $args = wp_parse_args( $args, $defaults );

        ob_start();

        include PMS_PLUGIN_DIR_PATH . 'includes/views/view-order-summary.php';

        return ob_get_clean();

    }


    /**
     * Outputs the Order Summary
     *
     * @param array $args
     *
     */
    public static function output( $args = array() ) {

        //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo self::get_output( $args );

    }


    /**
     * Outputs the Order Summary as a placement-hook callback
     *
     * - signature adapter for the WP actions registered in register_placement_hooks (pms_register_form_bottom, pms_new_subscription_form_bottom, etc.)
     * - those actions pass form atts and other args to their callbacks; this wrapper discards them so output() runs with a clean empty $args and the defaults apply
     * - external callers that want to render the summary should call the static output() / get_output() directly instead of going through this method
     *
     */
    public function output_order_summary() {

        self::output();

    }


    /**
     * Outputs the Order Summary after payment gateways
     *
     * - signature adapter for the pms_get_output_payment_gateways_after_paygates action, which fires inside the form-design rendering path
     * - that action passes ($settings, $form_location); we read $form_location to skip Profile Builder's wppb_register flow (which has its own placement)
     * - for every other form_location, delegates to the static output() with clean empty $args
     *
     * @param array  $settings
     * @param string $form_location
     *
     */
    public function output_payment_gateways_order_summary( $settings, $form_location ) {

        // Profile Builder has a separate placement hook in this flow
        if( $form_location == 'wppb_register' )
            return;

        self::output();

    }

}

$pms_order_summary = new PMS_Order_Summary();

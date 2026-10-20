<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

/**
 * Records what a payment was actually composed of, in the payment's own currency
 *
 * - invoices are generated on demand, so re-deriving the lines at render time reports today's plan prices and rates rather than the ones charged
 * - the amount passes through several add-ons, so the components are captured as deltas between snapshots of the live checkout rather than recomputed
 * - a breakdown is stored only when its parts add back up to the recorded total, so an unmodelled change to the amount leaves no breakdown instead of a wrong one
 *
 */
class PMS_Payment_Breakdown {

    /**
     * Amount snapshots taken while the checkout filters run, keyed by payment id
     *
     * @var array
     *
     */
    private static $snapshots = array();


    public function __construct() {

        // three points on the same filter: before the discount is applied, after it, and once tax has settled the amount
        // - the first sits after Pay What You Want replaces the amount at 10, so a buyer's chosen price counts as the plan portion rather than as a discount
        add_filter( 'pms_register_payment_data', array( $this, 'snapshot_before_discount' ), 15, 2 );
        add_filter( 'pms_register_payment_data', array( $this, 'snapshot_after_discount' ), 25, 2 );
        add_filter( 'pms_register_payment_data', array( $this, 'record_checkout_breakdown' ), 70, 2 );

        // renewals never reach the checkout filters, so they are recorded where their payment data is built
        add_action( 'pms_payment_insert', array( $this, 'record_renewal_breakdown' ), 10, 2 );

    }


    /**
     * The amount a checkout stage is working with
     *
     * - the initial charge tracks sign_up_amount once something sets it, and a recurring plan with a non-recurring discount leaves amount undiscounted
     * - null sign_up_amount means no stage has claimed it yet, so the plain amount is the live figure
     *
     */
    private static function effective_amount( $payment_gateway_data ) {

        if( isset( $payment_gateway_data['sign_up_amount'] ) && !is_null( $payment_gateway_data['sign_up_amount'] ) && $payment_gateway_data['sign_up_amount'] !== '' )
            return (float) $payment_gateway_data['sign_up_amount'];

        return isset( $payment_gateway_data['amount'] ) ? (float) $payment_gateway_data['amount'] : 0.0;
    }


    private static function store_snapshot( $payment_id, $key, $value ) {

        if( empty( $payment_id ) )
            return;

        if( !isset( self::$snapshots[ $payment_id ] ) )
            self::$snapshots[ $payment_id ] = array();

        self::$snapshots[ $payment_id ][ $key ] = $value;
    }


    /**
     * The amount before any discount, already converted and already net of a pro-rate credit
     *
     */
    public function snapshot_before_discount( $payment_gateway_data, $payment_settings ) {

        if( !empty( $payment_gateway_data['payment_id'] ) )
            self::store_snapshot( $payment_gateway_data['payment_id'], 'pre_discount', self::effective_amount( $payment_gateway_data ) );

        return $payment_gateway_data;
    }


    /**
     * The amount once the discount code has been applied and before tax
     *
     */
    public function snapshot_after_discount( $payment_gateway_data, $payment_settings ) {

        if( !empty( $payment_gateway_data['payment_id'] ) )
            self::store_snapshot( $payment_gateway_data['payment_id'], 'post_discount', self::effective_amount( $payment_gateway_data ) );

        return $payment_gateway_data;
    }


    /**
     * The sign-up fee this checkout charged, in the payment's currency
     *
     * - resolved through the same filter the charge used, so Multiple Currencies returns the per-currency or converted value rather than the base one
     * - returns 0 where the gateway or the form location does not charge one, which is what keeps the fee out of renewals and upgrades
     *
     */
    private static function get_charged_sign_up_fee( $payment_gateway_data, $subscription_plan ) {

        if( empty( $subscription_plan->sign_up_fee ) )
            return 0.0;

        $form_location = isset( $payment_gateway_data['form_location'] ) ? $payment_gateway_data['form_location'] : '';

        $fee_form_locations = apply_filters( 'pms_checkout_signup_fee_form_locations', array( 'register', 'new_subscription', 'retry_payment', 'register_email_confirmation', 'change_subscription', 'wppb_register' ) );

        if( !in_array( $form_location, $fee_form_locations ) )
            return 0.0;

        $payment_gateway = PMS_Form_Handler::checkout_get_payment_gateway();

        if( is_null( $payment_gateway ) || !$payment_gateway->supports( 'subscription_sign_up_fee' ) )
            return 0.0;

        $request_data = array( 'form_location' => $form_location );

        if( !empty( $payment_gateway_data['currency'] ) )
            $request_data['currency'] = $payment_gateway_data['currency'];

        return (float) apply_filters( 'pms_calculate_signup_fee_amount', (float) $subscription_plan->sign_up_fee, $subscription_plan, $request_data );
    }


    /**
     * Assemble and store the breakdown once tax has settled the amount
     *
     */
    public function record_checkout_breakdown( $payment_gateway_data, $payment_settings ) {

        if( empty( $payment_gateway_data['payment_id'] ) )
            return $payment_gateway_data;

        $payment_id = (int) $payment_gateway_data['payment_id'];

        if( empty( self::$snapshots[ $payment_id ]['pre_discount'] ) )
            return $payment_gateway_data;

        $payment = pms_get_payment( $payment_id );

        if( empty( $payment->id ) )
            return $payment_gateway_data;

        // a bundle already carries a per-item record of what was charged, and one plan/fee pair cannot describe several items
        // - leaving it alone keeps a single authoritative structure per payment, so a renderer cannot pick the wrong one
        if( self::has_order_items( $payment_id ) )
            return $payment_gateway_data;

        $subscription_plan = pms_get_subscription_plan( $payment_gateway_data['subscription_plan_id'] );

        $pre_discount  = (float) self::$snapshots[ $payment_id ]['pre_discount'];
        $post_discount = isset( self::$snapshots[ $payment_id ]['post_discount'] ) ? (float) self::$snapshots[ $payment_id ]['post_discount'] : $pre_discount;
        $final         = self::effective_amount( $payment_gateway_data );

        /**
         * Filter the pro-rate credit deducted from a payment, in the payment's currency
         *
         * - the credit is subtracted before the payment row exists, so it cannot be captured as a delta between checkout stages
         * - returns a positive amount, or 0 when the payment was not prorated
         *
         * @param float $credit
         * @param array $payment_gateway_data
         *
         */
        $prorate_credit = (float) apply_filters( 'pms_payment_breakdown_prorate_credit', 0.0, $payment_gateway_data );

        $sign_up_fee = self::get_charged_sign_up_fee( $payment_gateway_data, $subscription_plan );

        // the plan portion is what is left of the pre-discount amount once the fee is removed and the credit is added back
        // - derived rather than resolved so it stays correct for every pricing model without restating per-seat, pay-what-you-want or installment maths
        $plan = $pre_discount + $prorate_credit - $sign_up_fee;

        $breakdown = array(
            'plan'           => round( $plan, 2 ),
            'sign_up_fee'    => round( $sign_up_fee, 2 ),
            'discount'       => round( $pre_discount - $post_discount, 2 ),
            'prorate_credit' => round( $prorate_credit, 2 ),
            'tax'            => round( $final - $post_discount, 2 ),
            'total'          => round( $final, 2 ),
            'currency'       => !empty( $payment_gateway_data['currency'] ) ? $payment_gateway_data['currency'] : pms_get_active_currency(),
        );

        self::maybe_store( $payment_id, $breakdown, $payment );

        unset( self::$snapshots[ $payment_id ] );

        return $payment_gateway_data;
    }


    /**
     * Record a renewal's breakdown, which is the recurring amount plus whatever tax was added to it
     *
     * - renewals are built by the cron rather than the checkout filters, so none of the snapshots exist
     * - no sign-up fee and no pro-rate credit apply to a renewal, so the plan portion is the whole pre-tax amount
     *
     */
    public function record_renewal_breakdown( $payment_id, $data ) {

        if( empty( $payment_id ) || empty( $data['type'] ) )
            return;

        $renewal_types = array( 'subscription_recurring_payment', 'subscription_retry_payment', 'subscription_installment_payment' );

        if( !in_array( $data['type'], apply_filters( 'pms_payment_breakdown_renewal_types', $renewal_types ) ) )
            return;

        $payment = pms_get_payment( $payment_id );

        if( empty( $payment->id ) || empty( $payment->amount ) )
            return;

        if( self::has_order_items( $payment_id ) )
            return;

        $total = (float) $payment->amount;
        $rate  = self::get_subscription_tax_rate( $payment_id );

        // tax is applied as a percentage on top, so the pre-tax amount is the recurring figure the subscription holds
        $plan = $rate > 0 ? $total / ( 1 + $rate / 100 ) : $total;

        $breakdown = array(
            'plan'           => round( $plan, 2 ),
            'sign_up_fee'    => 0.0,
            'discount'       => 0.0,
            'prorate_credit' => 0.0,
            'tax'            => round( $total - $plan, 2 ),
            'total'          => round( $total, 2 ),
            'currency'       => !empty( $payment->currency ) ? $payment->currency : pms_get_active_currency(),
        );

        self::maybe_store( $payment_id, $breakdown, $payment );
    }


    /**
     * Whether the payment already holds a per-item record written by Order Bumps
     *
     */
    private static function has_order_items( $payment_id ) {

        $order_items = pms_get_payment_meta( $payment_id, '_pms_order_items', true );

        return !empty( $order_items['items'] ) && is_array( $order_items['items'] );
    }


    /**
     * The tax rate a renewal was charged at, taken from the subscription's first taxed payment
     *
     * - a renewal charges the stored billing_amount, which was made tax-inclusive at checkout, so the original payment's rate is the one baked into it
     * - renewals carry no tax meta of their own, and reading the current setting would re-tax old subscriptions at a rate they were never charged
     * - returns 0 when no earlier payment was taxed, which is the right answer for a subscription sold before tax was enabled
     *
     */
    private static function get_subscription_tax_rate( $payment_id ) {

        $subscription_id = (int) pms_get_payment_meta( $payment_id, 'subscription_id', true );

        if( empty( $subscription_id ) )
            return 0.0;

        $payments = pms_get_payments( array( 'member_subscription_id' => $subscription_id, 'number' => 50, 'order' => 'ASC' ) );

        if( empty( $payments ) )
            return 0.0;

        foreach( $payments as $earlier ) {

            if( (int) $earlier->id === (int) $payment_id )
                continue;

            $rate = pms_get_payment_meta( $earlier->id, 'pms_tax_rate', true );

            if( $rate !== '' && $rate !== false )
                return (float) $rate;

        }

        return 0.0;
    }


    /**
     * Store the breakdown only when its parts reconcile with the recorded total
     *
     * - the payment row is the one figure known to match what the gateway was asked for, so it is the reference everything else is checked against
     * - an add-on that changes the amount outside the captured stages breaks the sum, and recording nothing leaves the invoice rendering from live data instead of from a wrong breakdown
     *
     */
    private static function maybe_store( $payment_id, $breakdown, $payment ) {

        foreach( array( 'plan', 'sign_up_fee', 'discount', 'prorate_credit', 'tax', 'total' ) as $key ) {
            if( !is_numeric( $breakdown[ $key ] ) || !is_finite( (float) $breakdown[ $key ] ) )
                return false;
        }

        if( $breakdown['plan'] < 0 || $breakdown['sign_up_fee'] < 0 || $breakdown['total'] <= 0 )
            return false;

        $composed = $breakdown['plan'] + $breakdown['sign_up_fee'] - $breakdown['discount'] - $breakdown['prorate_credit'] + $breakdown['tax'];

        if( abs( $composed - $breakdown['total'] ) > 0.01 )
            return false;

        if( abs( (float) $payment->amount - $breakdown['total'] ) > 0.01 )
            return false;

        pms_update_payment_meta( $payment_id, 'pms_payment_breakdown', $breakdown );

        return true;
    }

}

new PMS_Payment_Breakdown();

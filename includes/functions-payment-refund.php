<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Valid refund source slugs stored in payment meta.
 *
 * @return string[]
 */
function pms_get_payment_refund_sources() {

    return apply_filters( 'pms_payment_refund_sources', array(
        'admin_refund',
        'manual_status_change',
        'webhook',
    ) );

}

/**
 * Persist refund date, amount, and source on a payment (write-once).
 *
 * @param int         $payment_id
 * @param float       $amount
 * @param string      $source     admin_refund|manual_status_change|webhook
 * @param string|null $date       MySQL datetime; defaults to current site time.
 *
 * @return bool
 */
function pms_record_payment_refund_meta( $payment_id, $amount, $source, $date = null ) {

    if ( empty( $payment_id ) || pms_get_payment_refund_date( $payment_id ) )
        return false;

    if ( ! in_array( $source, pms_get_payment_refund_sources(), true ) )
        return false;

    if ( $date === null )
        $date = current_time( 'mysql' );

    $date_written   = pms_add_payment_meta( $payment_id, 'refund_date', $date, true );
    $amount_written = pms_add_payment_meta( $payment_id, 'refund_amount', (float) $amount, true );
    $source_written = pms_add_payment_meta( $payment_id, 'refund_source', $source, true );

    return $date_written !== false && $amount_written !== false && $source_written !== false;

}

/**
 * Record refund meta and set payment status to refunded.
 *
 * @param int         $payment_id
 * @param float       $amount
 * @param string      $source
 * @param string|null $date
 *
 * @return bool
 */
function pms_refund_payment( $payment_id, $amount, $source, $date = null ) {

    if ( empty( $payment_id ) )
        return false;

    $payment = pms_get_payment( $payment_id );

    if ( empty( $payment->id ) )
        return false;

    if ( ! pms_get_payment_refund_date( $payment_id ) ) {
        if ( ! pms_record_payment_refund_meta( $payment_id, $amount, $source, $date ) )
            return false;
    }

    if ( $payment->status === 'refunded' )
        return true;

    return (bool) $payment->update( array( 'status' => 'refunded' ) );

}

/**
 * Returns the refund date for a payment.
 *
 * @param int $payment_id
 *
 * @return string|false
 */
function pms_get_payment_refund_date( $payment_id ) {

    return pms_get_payment_meta( $payment_id, 'refund_date', true );

}

/**
 * Returns the refund amount for a payment.
 *
 * @param int   $payment_id
 * @param array $payment Optional prefetched payment row (refund_amount_meta).
 *
 * @return float
 */
function pms_get_payment_refund_amount( $payment_id, $payment = array() ) {

    if ( ! empty( $payment['refund_amount_meta'] ) && $payment['refund_amount_meta'] !== '' )
        return (float) $payment['refund_amount_meta'];

    $amount = pms_get_payment_meta( $payment_id, 'refund_amount', true );

    if ( $amount === '' || $amount === false )
        return 0.0;

    return (float) $amount;

}

/**
 * Returns the refund source for a payment.
 *
 * @param int $payment_id
 *
 * @return string|false
 */
function pms_get_payment_refund_source( $payment_id ) {

    return pms_get_payment_meta( $payment_id, 'refund_source', true );

}

/**
 * Payment log entries as an associative array.
 *
 * @param object $payment PMS_Payment instance.
 * @return array
 */
function pms_get_payment_logs_array( $payment ) {

    if ( ! empty( $payment->logs ) && is_array( $payment->logs ) )
        return $payment->logs;

    if ( empty( $payment->id ) )
        return array();

    global $wpdb;

    $logs_json = $wpdb->get_var( $wpdb->prepare(
        "SELECT logs FROM {$wpdb->prefix}pms_payments WHERE id = %d",
        $payment->id
    ) );

    if ( empty( $logs_json ) )
        return array();

    $logs = json_decode( $logs_json, true );

    return is_array( $logs ) ? $logs : array();

}

/**
 * Earliest refund-related log entry for a payment.
 *
 * @param object $payment PMS_Payment instance.
 * @return array|null
 */
function pms_parse_refund_data_from_payment_logs( $payment ) {

    $logs = pms_get_payment_logs_array( $payment );

    if ( empty( $logs ) )
        return null;

    $payment_amount = (float) $payment->amount;
    $best           = null;

    foreach ( $logs as $log ) {
        if ( empty( $log['type'] ) || empty( $log['date'] ) )
            continue;

        $entry = null;

        switch ( $log['type'] ) {
            case 'payment_refunded':
                $amount = $payment_amount;
                if ( ! empty( $log['data']['data']['amount'] ) )
                    $amount = (float) $log['data']['data']['amount'];

                $entry = array(
                    'date'   => $log['date'],
                    'amount' => $amount,
                    'source' => 'admin_refund',
                );
                break;

            case 'status_changed':
                if ( ! empty( $log['data']['new_data']['status'] ) && $log['data']['new_data']['status'] === 'refunded' ) {
                    $entry = array(
                        'date'   => $log['date'],
                        'amount' => $payment_amount,
                        'source' => 'manual_status_change',
                    );
                }
                break;

            case 'stripe_charge_refunded':
            case 'paypal_transaction_refunded':
            case 'authorize_net_charge_refunded':
                $entry = array(
                    'date'   => $log['date'],
                    'amount' => $payment_amount,
                    'source' => 'webhook',
                );
                break;
        }

        if ( $entry === null )
            continue;

        if ( $best === null || strtotime( $entry['date'] ) < strtotime( $best['date'] ) )
            $best = $entry;
    }

    return $best;

}

/**
 * Refunded payments whose refund_date meta falls within the given range.
 *
 * @param string $start_date MySQL datetime.
 * @param string $end_date   MySQL datetime.
 * @param array  $args {
 *     @type int|int[] $subscription_plan_id Optional plan filter.
 * }
 *
 * @return array[] Payment rows with refund_date_meta and refund_amount_meta.
 */
function pms_get_refunded_payments_in_period( $start_date, $end_date, $args = array() ) {

    global $wpdb;

    $defaults = array(
        'subscription_plan_id' => '',
        'return_array'         => true,
    );

    $args = apply_filters( 'pms_reports_get_refunded_payments_in_period_args', wp_parse_args( $args, $defaults ), $start_date, $end_date );

    $start_date = sanitize_text_field( $start_date );
    $end_date   = sanitize_text_field( $end_date );

    $query = "SELECT p.*,
            rd.meta_value AS refund_date_meta,
            ra.meta_value AS refund_amount_meta
        FROM {$wpdb->prefix}pms_payments p
        INNER JOIN {$wpdb->prefix}pms_paymentmeta rd ON p.id = rd.payment_id AND rd.meta_key = 'refund_date'
        LEFT JOIN {$wpdb->prefix}pms_paymentmeta ra ON p.id = ra.payment_id AND ra.meta_key = 'refund_amount'
        WHERE p.status = 'refunded'
        AND rd.meta_value BETWEEN %s AND %s";

    if ( ! empty( $args['subscription_plan_id'] ) ) {
        if ( is_array( $args['subscription_plan_id'] ) ) {
            $plan_ids = implode( ',', array_map( 'absint', $args['subscription_plan_id'] ) );
            $query   .= " AND p.subscription_plan_id IN ({$plan_ids})";
        } else {
            $query .= ' AND p.subscription_plan_id = ' . absint( $args['subscription_plan_id'] );
        }
    }

    $query .= ' GROUP BY p.id ORDER BY rd.meta_value ASC';

    $data_array = $wpdb->get_results( $wpdb->prepare( $query, $start_date, $end_date ), ARRAY_A );

    $payments = array();

    if ( ! empty( $data_array ) ) {
        foreach ( $data_array as $data ) {
            if ( ! empty( $data['subscription_plan_id'] ) )
                $data['subscription_id'] = $data['subscription_plan_id'];

            $payments[] = $data;
        }
    }

    return apply_filters( 'pms_get_refunded_payments_in_period', $payments, $start_date, $end_date, $args );

}

/**
 * Record refund meta when payment status is changed to refunded.
 *
 * @param int   $payment_id
 * @param array $data
 * @param array $old_data
 */
function pms_maybe_record_manual_payment_refund_meta( $payment_id, $data, $old_data ) {

    if ( empty( $data['status'] ) || $data['status'] !== 'refunded' )
        return;

    if ( ! empty( $old_data['status'] ) && $old_data['status'] === 'refunded' )
        return;

    if ( pms_get_payment_refund_date( $payment_id ) )
        return;

    $payment = pms_get_payment( $payment_id );

    if ( empty( $payment->id ) )
        return;

    pms_record_payment_refund_meta( $payment_id, $payment->amount, 'manual_status_change' );

}
add_action( 'pms_payment_update', 'pms_maybe_record_manual_payment_refund_meta', 15, 3 );

/**
 * Record refund meta when a payment is inserted as refunded.
 *
 * @param int   $payment_id
 * @param array $data
 */
function pms_maybe_record_refund_meta_on_insert( $payment_id, $data ) {

    if ( empty( $data['status'] ) || $data['status'] !== 'refunded' )
        return;

    $amount = ! empty( $data['amount'] ) ? (float) $data['amount'] : 0;

    if ( empty( $amount ) ) {
        $payment = pms_get_payment( $payment_id );
        $amount  = ! empty( $payment->amount ) ? (float) $payment->amount : 0;
    }

    pms_record_payment_refund_meta( $payment_id, $amount, 'manual_status_change' );

}
add_action( 'pms_payment_insert', 'pms_maybe_record_refund_meta_on_insert', 20, 2 );

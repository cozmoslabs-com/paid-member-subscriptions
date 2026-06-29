<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Bulk-load base_currency_amount meta for a set of payment IDs.
 *
 * @param int[] $payment_ids
 * @return array
 */
function pms_reports_fetch_base_currency_amount_meta_map( $payment_ids ) {

    global $wpdb;

    $payment_ids = array_filter( array_map( 'absint', $payment_ids ) );

    if ( empty( $payment_ids ) )
        return array();

    $chunk_size = (int) apply_filters( 'pms_reports_base_currency_meta_chunk_size', 500 );
    $meta_map   = array();

    foreach ( array_chunk( $payment_ids, max( 1, $chunk_size ) ) as $chunk ) {
        $ids_in = implode( ',', $chunk );

        $rows = $wpdb->get_results(
            "SELECT payment_id, meta_value FROM {$wpdb->prefix}pms_paymentmeta
            WHERE meta_key = 'base_currency_amount' AND payment_id IN ({$ids_in})",
            ARRAY_A
        );

        if ( empty( $rows ) )
            continue;

        foreach ( $rows as $row ) {
            $meta_map[ (int) $row['payment_id'] ] = $row['meta_value'];
        }
    }

    return $meta_map;

}

/**
 * Attach prefetched base_currency_amount meta to payment rows used in reports.
 *
 * @param array $payments
 * @return array
 */
function pms_reports_prefetch_base_currency_amounts_for_payments( $payments ) {

    if ( empty( $payments ) )
        return $payments;

    $payment_ids = array();

    foreach ( $payments as $payment ) {
        if ( ! empty( $payment['id'] ) )
            $payment_ids[] = (int) $payment['id'];
    }

    $meta_map = pms_reports_fetch_base_currency_amount_meta_map( $payment_ids );

    if ( empty( $meta_map ) )
        return $payments;

    foreach ( $payments as $key => $payment ) {
        $payment_id = (int) $payment['id'];

        if ( isset( $meta_map[ $payment_id ] ) )
            $payments[ $key ]['base_currency_amount_meta'] = $meta_map[ $payment_id ];
    }

    return $payments;

}

/**
 * Returns base_currency_amount meta for a payment row.
 *
 * @param array $payment
 * @return mixed
 */
function pms_reports_get_payment_base_currency_amount( $payment ) {

    if ( isset( $payment['base_currency_amount_meta'] ) && $payment['base_currency_amount_meta'] !== '' )
        return $payment['base_currency_amount_meta'];

    if ( empty( $payment['id'] ) )
        return '';

    return pms_get_payment_meta( $payment['id'], 'base_currency_amount', true );

}

/**
 * Convert a payment line amount to the site's default currency for reports.
 *
 * @param float      $amount
 * @param array      $payment
 * @param array|null $args {
 *     @type string|null $convert_date Date string for pms_convert_currency.
 * }
 *
 * @return float
 */
function pms_reports_convert_amount_to_default_currency( $amount, $payment, $args = array() ) {

    $defaults = array(
        'convert_date' => null,
    );

    $args             = wp_parse_args( $args, $defaults );
    $default_currency = pms_get_active_currency();
    $currency = ! empty( $payment['currency'] ) ? $payment['currency'] : $default_currency;
    $currency = apply_filters( 'pms_reports_payment_currency', $currency, $payment );

    $base_currency_amount = pms_reports_get_payment_base_currency_amount( $payment );

    if ( ! empty( $base_currency_amount ) )
        return (float) $base_currency_amount;

    if ( $currency === $default_currency )
        return (float) $amount;

    $convert_date = $args['convert_date'];
    if ( empty( $convert_date ) )
        $convert_date = date( 'Y-m-d', strtotime( $payment['date'] ) );

    if ( function_exists( 'pms_convert_currency' ) )
        return pms_convert_currency( $amount, $currency, $default_currency, $convert_date );

    return (float) $amount;

}

/**
 * Subscription plan filter args from the reports request.
 *
 * @return array
 */
function pms_reports_get_subscription_plan_filter_args() {

    $args = array();

    if ( isset( $_REQUEST['pms-filter-subscription-plans'] ) && ! empty( $_GET['pms-filter-subscription-plans'] ) )
        $args['subscription_plan_id'] = array_map( 'absint', $_GET['pms-filter-subscription-plans'] );

    return apply_filters( 'pms_reports_subscription_plan_filter_args', $args );

}

/**
 * Refunded payments for a reports date range.
 *
 * @param string $start_date
 * @param string $end_date
 * @param array  $filter_args Optional; defaults to current request filter.
 * @return array[]
 */
function pms_reports_get_refunded_payments_for_range( $start_date, $end_date, $filter_args = null ) {

    if ( $filter_args === null )
        $filter_args = pms_reports_get_subscription_plan_filter_args();

    $end = $end_date;
    if ( strpos( $end, ' ' ) === false )
        $end .= ' 23:59:59';

    return pms_get_refunded_payments_in_period( $start_date, $end, $filter_args );

}

/**
 * Aggregate refund totals for a set of refunded payment rows.
 *
 * @param array $refunded_payments
 * @return array
 */
function pms_reports_aggregate_refunds( $refunded_payments ) {

    $default_currency = pms_get_active_currency();
    $refunded_amount  = array();
    $refunded_count   = array();

    $default_currency_totals = array(
        'refunded_amount' => 0,
        'refunded_count'  => 0,
    );

    if ( empty( $refunded_payments ) ) {
        return array(
            'refunded_amount'         => $refunded_amount,
            'refunded_count'          => $refunded_count,
            'default_currency'        => $default_currency,
            'default_currency_totals' => $default_currency_totals,
        );
    }

    $refunded_payments = pms_reports_prefetch_base_currency_amounts_for_payments( $refunded_payments );

    foreach ( $refunded_payments as $payment ) {
        $currency      = ! empty( $payment['currency'] ) ? $payment['currency'] : $default_currency;
        $currency      = apply_filters( 'pms_reports_payment_currency', $currency, $payment );
        $refund_amount = pms_get_payment_refund_amount( $payment['id'], $payment );

        if ( empty( $refund_amount ) )
            $refund_amount = (float) $payment['amount'];

        $convert_date = ! empty( $payment['refund_date_meta'] ) ? date( 'Y-m-d', strtotime( $payment['refund_date_meta'] ) ) : date( 'Y-m-d', strtotime( $payment['date'] ) );

        $default_currency_refund_amount = pms_reports_convert_amount_to_default_currency(
            $refund_amount,
            $payment,
            array(
                'convert_date' => $convert_date,
            )
        );

        $default_currency_totals['refunded_amount'] += $default_currency_refund_amount;
        $default_currency_totals['refunded_count']++;

        if ( isset( $refunded_amount[ $currency ] ) )
            $refunded_amount[ $currency ] += $refund_amount;
        else
            $refunded_amount[ $currency ] = $refund_amount;

        if ( isset( $refunded_count[ $currency ] ) )
            $refunded_count[ $currency ]++;
        else
            $refunded_count[ $currency ] = 1;
    }

    return array(
        'refunded_amount'         => $refunded_amount,
        'refunded_count'          => $refunded_count,
        'default_currency'        => $default_currency,
        'default_currency_totals' => $default_currency_totals,
    );

}

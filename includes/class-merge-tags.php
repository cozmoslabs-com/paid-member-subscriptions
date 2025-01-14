<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * Merge Tags Class contains the deafult merge tags and methods how to handle them
 *
 */
Class PMS_Merge_Tags{

    public function __construct() {

        add_filter( 'pms_merge_tag_subscription_name',            array( $this, 'pms_tag_subscription_name' ), 10, 3 );
        add_filter( 'pms_merge_tag_display_name',                 array( $this, 'pms_tag_display_name' ), 10, 2 );
        add_filter( 'pms_merge_tag_user_id',                      array( $this, 'pms_tag_user_id' ), 10, 2 );
        add_filter( 'pms_merge_tag_payment_id',                   array( $this, 'pms_tag_payment_id' ), 10, 4 );
        add_filter( 'pms_merge_tag_subscription_status',          array( $this, 'pms_tag_subscription_status' ), 10, 3 );
        add_filter( 'pms_merge_tag_subscription_start_date',      array( $this, 'pms_tag_subscription_start_date' ), 10, 3 );
        add_filter( 'pms_merge_tag_subscription_expiration_date', array( $this, 'pms_tag_subscription_expiration_date' ), 10, 3 );
        add_filter( 'pms_merge_tag_subscription_price',           array( $this, 'pms_tag_subscription_price' ), 10, 4 );
        add_filter( 'pms_merge_tag_subscription_plan_price',      array( $this, 'pms_tag_subscription_plan_price' ), 10, 3 );
        add_filter( 'pms_merge_tag_subscription_plan_id',         array( $this, 'pms_tag_subscription_plan_id' ), 10, 3 );
        add_filter( 'pms_merge_tag_total_payment_amount',         array( $this, 'pms_tag_total_payment_amount' ), 10, 4 );
        add_filter( 'pms_merge_tag_subscription_duration',        array( $this, 'pms_tag_subscription_duration' ), 10, 3 );
        add_filter( 'pms_merge_tag_username',                     array( $this, 'pms_tag_username' ), 10, 2 );
        add_filter( 'pms_merge_tag_first_name',                   array( $this, 'pms_tag_firstname' ), 10, 2 );
        add_filter( 'pms_merge_tag_last_name',                    array( $this, 'pms_tag_lastname' ), 10, 2 );
        add_filter( 'pms_merge_tag_user_email',                   array( $this, 'pms_tag_user_email' ), 10, 2 );
        add_filter( 'pms_merge_tag_site_name',                    array( $this, 'pms_tag_site_name' ), 10 );
        add_filter( 'pms_merge_tag_site_url',                     array( $this, 'pms_tag_site_url' ), 10 );
        add_filter( 'pms_merge_tag_automatic_retry_message',      array( $this, 'pms_tag_automatic_retry_message' ), 10, 5 );
        add_filter( 'pms_merge_tag_account_page_url',             array( $this, 'pms_tag_account_page_url' ), 10 );
        add_filter( 'pms_merge_tag_reset_key',                    array( $this, 'pms_tag_reset_key' ), 10, 6 );
        add_filter( 'pms_merge_tag_reset_url',                    array( $this, 'pms_tag_reset_url' ), 10, 6 );
        add_filter( 'pms_merge_tag_reset_link',                   array( $this, 'pms_tag_reset_link' ), 10, 6 );

    }

    /**
     * Function that searches and replaces merge tags with their values
     *
     * @param $text                 the text to search
     * @param $user_info            used for merge tags related to the user
     * @param $subscription_id      used for merge tags related to the subscription
     * @param $payment_id           used for merge tags related to the payment
     *
     * @return mixed text with merge tags replaced
     */
    static function process_merge_tags( $text, $user_info, $subscription_id = 0, $payment_id = 0, $action = '', $data = array() ){

        $merge_tags = PMS_Merge_Tags::get_merge_tags();

        if( !empty( $merge_tags ) ){
            foreach( $merge_tags as $merge_tag ){
                $tag_value = apply_filters( 'pms_merge_tag_' . $merge_tag, '', $user_info, $subscription_id, $payment_id, $action, $data );

                if( $tag_value != null && !is_wp_error( $tag_value ) )
                    $text = str_replace( '{{'.$merge_tag.'}}', $tag_value, $text );
                else
                    $text = str_replace( '{{'.$merge_tag.'}}', '', $text );
            }
        }

        return $text;

    }

    /**
     * Function that returns the available merge tags
     */
    static function get_merge_tags(){

        $available_merge_tags = array(
            'display_name',
            'subscription_name',
            'user_id',
            'payment_id',
            'subscription_status',
            'subscription_start_date',
            'subscription_expiration_date',
            'subscription_price',
            'subscription_plan_price',
            'subscription_plan_id',
            'total_payment_amount',
            'subscription_duration',
            'first_name',
            'last_name',
            'username',
            'user_email',
            'site_name',
            'site_url',
            'automatic_retry_message',
            'account_page_url',
            'reset_key',
            'reset_url',
            'reset_link'
        );

        $available_merge_tags = apply_filters( 'pms_merge_tags', $available_merge_tags );

        return array_unique( $available_merge_tags );

    }

    /**
     * Replace the {{subscription_name}} tag
     */
    function pms_tag_subscription_name( $value, $user_info, $subscription_id ) {

        $subscription_plan = isset( $user_info->subscription_plan_id ) ? pms_get_subscription_plan( $user_info->subscription_plan_id ) : '';

        if( !empty( $subscription_id ) ){
            $subscription = pms_get_member_subscription( $subscription_id );

            if( !empty( $subscription->subscription_plan_id ) )
                $subscription_plan = pms_get_subscription_plan( $subscription->subscription_plan_id );
        }

        if( !empty( $subscription_plan->name ) )
            return $subscription_plan->name;

        return '';

    }

    /**
     * Replace the {{display_name}} tag
     */
    function pms_tag_display_name( $value, $user_info ){

        if( !empty( $user_info->display_name ) )
            return $user_info->display_name;
        else if( !empty( $user_info->user_login ) )
            return $user_info->user_login;
        else
            return '';

    }

    function pms_tag_user_id( $value, $user_info ){

        if( !empty( $user_info->ID ) )
            return $user_info->ID;
        else
            return '';

    }

    /**
     * Replace the {{payment_id}} tag
     */
    function pms_tag_payment_id( $value, $user_info, $subscription_id, $payment_id){
        return $payment_id;
    }

    /**
     * Replace the {{subscription_status}} tag
     */
    public function pms_tag_subscription_status( $value, $user_info, $subscription_id ){

        if( !empty( $subscription_id ) ){

            $subscription = pms_get_member_subscription( $subscription_id );

            if( !empty( $subscription->id ) )
                return $subscription->status;
            else
                return __( 'abandoned', 'paid-member-subscriptions' );

        }

    }

    /**
     * Replace the {{subscription_start_date}} tag
     */
    public function pms_tag_subscription_start_date( $value, $user_info, $subscription_id ){

        if ( !empty( $subscription_id ) ){
            $subscription = pms_get_member_subscription( $subscription_id );

            if( !empty( $subscription->start_date ) )
                return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $subscription->start_date ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );
        }

    }

    /**
     * Replace the {{subscription_expiration_date}} tag
     */
    public function pms_tag_subscription_expiration_date( $value, $user_info, $subscription_id ){

        if( !empty( $subscription_id ) ){
            $subscription = pms_get_member_subscription( $subscription_id );

            $subscription_plan = pms_get_subscription_plan( $subscription->subscription_plan_id );

            if ( !empty( $subscription->expiration_date ) )
                return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $subscription->expiration_date ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );
            // If Expiration Date is empty, return Billing Next Payment if available
            else if( !empty( $subscription->billing_next_payment ) )
                return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $subscription->billing_next_payment ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );
            else if( empty( $subscription->expiration_date ) && $subscription_plan->duration == 0 )
                return esc_html__( 'Unlimited', 'paid-member-subscriptions' );

        }

    }

    /**
     * Replace the {{subscription_price}} tag
     */
    public function pms_tag_subscription_price( $value, $user_info, $subscription_id, $payment_id ){

        $amount = false;

        if( !empty( $payment_id ) ){

            $payment = pms_get_payment( $payment_id );

            if( !empty( $payment->id ) )
                $amount = $payment->amount;
            
        } else if( !empty( $user_info->ID ) ){

            $payments = pms_get_payments( array( 'user_id' => $user_info->ID ) );

            // If the website is doing cron we don't want the price of the last payment
            if ( empty( $payments ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {

                if( !empty( $subscription_id ) ){
                    $subscription = pms_get_member_subscription( $subscription_id );

                    if( !empty( $subscription ) && !empty( $subscription->subscription_plan_id ) ){
                        $subscription_plan = pms_get_subscription_plan( $subscription->subscription_plan_id );

                        if ( !empty( $_POST['discount_code'] ) && !empty( $subscription_plan->price ) )
                            $amount = pms_in_calculate_discounted_amount( $subscription_plan->price, pms_in_get_discount_by_code( sanitize_text_field( $_POST['discount_code'] ) ) );
                        else
                            $amount = $subscription_plan->price;
                    }
                }

            } else
                $amount = $payments[0]->amount;

        }
        
        
        if( $amount === false ){

            return __( 'Free', 'paid-member-subscriptions' );

        } else {

            $currency = apply_filters( 'pms_merge_tag_subscription_price_currency', pms_get_active_currency(), $subscription_id );

            return pms_format_price( $amount, $currency );

        }

    }

    /**
     * Replace the {{subscription_plan_price}} tag
     */
    public function pms_tag_subscription_plan_price( $value, $user_info, $subscription_id ){

        $amount = false;

        if( !empty( $subscription_id ) ){
            $subscription = pms_get_member_subscription( $subscription_id );

            if( !empty( $subscription ) && !empty( $subscription->subscription_plan_id ) )
                $subscription_plan = pms_get_subscription_plan( $subscription->subscription_plan_id );
        }
        elseif ( !empty( $user_info ) && !empty( $user_info->data ) && !empty( $user_info->data->subscription_plan_id )) {
            $subscription_plan = pms_get_subscription_plan( $user_info->data->subscription_plan_id );
        }


        if ( isset( $subscription_plan ) && !empty( $subscription_plan->price ) ) {

            if ( !empty( $_POST['discount_code'] ) )
                $amount = pms_in_calculate_discounted_amount( $subscription_plan->price, pms_in_get_discount_by_code( sanitize_text_field( $_POST['discount_code'] ) ) );
            else
                $amount = $subscription_plan->price;

        }

        if( $amount !== false ){
            $currency = apply_filters( 'pms_merge_tag_subscription_plan_price_currency', pms_get_active_currency(), $subscription_id );
            return pms_format_price( $amount, $currency );
        }
        else return apply_filters( 'pms_merge_tag_no_subscription_plan_price_message', __( 'Free', 'paid-member-subscriptions' ));

    }

    /**
     * Replace the {{subscription_plan_id}} tag
     */
    public function pms_tag_subscription_plan_id( $value, $user_info, $subscription_id ){

        $subscription_plan_id = '';

        if( !empty( $subscription_id ) ){
            $subscription = pms_get_member_subscription( $subscription_id );

            if( !empty( $subscription ) && !empty( $subscription->subscription_plan_id ) )
                $subscription_plan_id = $subscription->subscription_plan_id;
        }
        elseif ( !empty( $user_info ) && !empty( $user_info->data ) && !empty( $user_info->data->subscription_plan_id )) {
            $subscription_plan_id = $user_info->data->subscription_plan_id;
        }

        return $subscription_plan_id;

    }

    /**
     * Replace the {{total_payment_amount}} tag
     */
    public function pms_tag_total_payment_amount( $value, $user_info, $subscription_id, $payment_id ){

        if ( empty( $payment_id ) )
            return;

        $amount = false;

        $payment = pms_get_payment( $payment_id );

        if ( !empty( $payment->id ) )
            $amount = $payment->amount;

        $currency = apply_filters( 'pms_merge_tag_subscription_price_currency', pms_get_active_currency(), $subscription_id );

        if ( class_exists( 'PMS_IN_Tax' ) ) {
            $pms_tax = new PMS_IN_Tax;
            $amount = apply_filters( 'pms_merge_tag_subscription_price_amount', $pms_tax->calculate_tax_rate( $amount ), $amount, $payment_id );
        }

        return pms_format_price( $amount, $currency );

    }

    /**
     * Replace the {{subscription_duration}} tag
     */
    public function pms_tag_subscription_duration( $value, $user_info, $subscription_id ){

        if( !empty( $subscription_id ) ){
            $subscription = pms_get_member_subscription( $subscription_id );

            if( !empty( $subscription->subscription_plan_id ) ){
                $plan = pms_get_subscription_plan( $subscription->subscription_plan_id );

                if( $plan->is_fixed_period_membership() ){
                    return sprintf( esc_html__( 'until %s', 'paid-member-subscriptions' ), esc_html( date_i18n( get_option( 'date_format' ) , strtotime( $plan->get_expiration_date() ) ) ) );
                } else{
                    if ( $plan->duration == 0 )
                        return __( 'Unlimited', 'paid-member-subscriptions' );
                    else
                        return $plan->duration . ' ' . $plan->duration_unit . '(s)';
                }
            }
        }

    }

    /**
     * Replace the {{username}} tag
     */
    public function pms_tag_username( $value, $user_info ){

        if ( is_object( $user_info ) && !empty( $user_info->user_login ) )

            return $user_info->user_login;

    }

    /**
     * Replace the {{first_name}} tag
     */
    public function pms_tag_firstname( $value, $user_info ){

        if ( is_object( $user_info ) && !empty( $user_info->ID ) ) {
            $first_name = get_user_meta( $user_info->ID, 'first_name', true );

            if ( !empty( $first_name ) )
                return $first_name;
        }

    }

    /**
     * Replace the {{last_name}} tag
     */
    public function pms_tag_lastname( $value, $user_info ){

        if ( is_object( $user_info ) && !empty( $user_info->ID ) ) {
            $last_name = get_user_meta( $user_info->ID, 'last_name', true );

            if ( !empty( $last_name ) )
                return $last_name;
        }

    }

    /**
     * Replace the {{user_email}} tag
     */
    public function pms_tag_user_email( $value, $user_info ){

        if ( is_object( $user_info ) && !empty( $user_info->user_email ) )

            return $user_info->user_email;

    }

    /**
     * Replace the {{site_name}} tag
     */
    public function pms_tag_site_name( $value ){

        return html_entity_decode( get_bloginfo( 'name' ) );

    }

    /**
     * Replace the {{site_url}} tag
     */
    public function pms_tag_site_url( $value ){

        return get_bloginfo( 'url' );
    }

    /**
     * Replace the {{automatic_retry_message}} tag
     */
    public function pms_tag_automatic_retry_message( $value, $user_info, $subscription_id, $payment_id, $action ){

        if( !pms_is_payment_retry_enabled() )
            return $value;

        if( $action == 'payment_failed' && !empty( $subscription_id ) ){

            $subscription = pms_get_member_subscription( $subscription_id );

            if( !empty( $subscription->id ) ){
                $retry_count = pms_get_subscription_payments_retry_count( $subscription->id );

                if( $retry_count < apply_filters( 'pms_retry_payment_count', 3 ) )
                    return sprintf( __( 'The payment will be automatically retried on %s. After %s more attempts, the subscription will remain expired.', 'paid-member-subscriptions' ), '<strong>' . $subscription->billing_next_payment . '</strong>', '<strong>' . ( (int)apply_filters( 'pms_retry_payment_count', 3 ) - $retry_count ) . '</strong>' );
            }

        }

        return $value;

    }

    public function pms_tag_account_page_url( $value ){

        $settings = get_option( 'pms_general_settings', false );

        if( empty( $settings ) || !isset( $settings['account_page'] ) || $settings['account_page'] == '-1' )
            return home_url();
        else
            return get_permalink( $settings['account_page'] );

    }

    /**
     * Replace the {{reset_key}} tag
     */
    public function pms_tag_reset_key( $value, $user_info, $subscription_id, $payment_id, $action, $data ){

        if( is_object( $user_info ) && !empty( $data['password_reset_key'] ) ){
            $key = $data['password_reset_key'];

            return $key;
        }
    }

    /**
     * Replace the {{reset_url}} tag
     */
    public function pms_tag_reset_url( $value, $user_info, $subscription_id, $payment_id, $action, $data ){

        if( is_object( $user_info ) && !empty( $data['password_reset_key'] ) ){
            $key = $data['password_reset_key'];
            $requestedUserLogin = $user_info->user_login;
            $url = esc_url(add_query_arg(array('loginName' => urlencode( $requestedUserLogin ), 'key' => $key), pms_get_current_page_url()));

            return $url;
        }
    }/**
     * Replace the {{reset_link}} tag
     */
    public function pms_tag_reset_link( $value, $user_info, $subscription_id, $payment_id, $action, $data ){

        if( is_object( $user_info ) && !empty( $data['password_reset_key'] ) ){
            $key = $data['password_reset_key'];
            $requestedUserLogin = $user_info->user_login;
            $link = '<a href="' . esc_url(add_query_arg(array('loginName' => urlencode( $requestedUserLogin ), 'key' => $key), pms_get_current_page_url())) . '">' . esc_url(add_query_arg(array('loginName' => urlencode( $requestedUserLogin ), 'key' => $key), pms_get_current_page_url())) . '</a>';

            return $link;
        }
    }
}


$merge_tags = new PMS_Merge_Tags();

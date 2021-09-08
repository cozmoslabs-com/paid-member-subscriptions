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
        add_filter( 'pms_merge_tag_subscription_duration',        array( $this, 'pms_tag_subscription_duration' ), 10, 3 );
        add_filter( 'pms_merge_tag_username',                     array( $this, 'pms_tag_username' ), 10, 2 );
        add_filter( 'pms_merge_tag_first_name',                   array( $this, 'pms_tag_firstname' ), 10, 2 );
        add_filter( 'pms_merge_tag_last_name',                    array( $this, 'pms_tag_lastname' ), 10, 2 );
        add_filter( 'pms_merge_tag_user_email',                   array( $this, 'pms_tag_user_email' ), 10, 2 );
        add_filter( 'pms_merge_tag_site_name',                    array( $this, 'pms_tag_site_name' ), 10 );
        add_filter( 'pms_merge_tag_site_url',                     array( $this, 'pms_tag_site_url' ), 10 );
        add_filter( 'pms_merge_tag_automatic_retry_message',      array( $this, 'pms_tag_automatic_retry_message' ), 10, 5 );
        add_filter( 'pms_merge_tag_account_page_url',             array( $this, 'pms_tag_account_page_url' ), 10 );

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
                $text = str_replace( '{{'.$merge_tag.'}}', apply_filters( 'pms_merge_tag_'.$merge_tag, '', $user_info, $subscription_id, $payment_id, $action, $data ), $text );
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
            'subscription_duration',
            'first_name',
            'last_name',
            'username',
            'user_email',
            'site_name',
            'site_url',
            'automatic_retry_message',
            'account_page_url'
        );

        $available_merge_tags = apply_filters( 'pms_merge_tags', $available_merge_tags );

        return array_unique( $available_merge_tags );

    }

    /**
     * Replace the {{subscription_name}} tag
     */
    function pms_tag_subscription_name( $value, $user_info, $subscription_id ) {

        if( !empty( $subscription_id ) ){
            $subscription = pms_get_member_subscription( $subscription_id );

            if( !empty( $subscription->subscription_plan_id ) ){
                $plan = pms_get_subscription_plan( $subscription->subscription_plan_id );

                return $plan->name;
            }
        }

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

            if ( !empty( $subscription->expiration_date ) )
                return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $subscription->expiration_date ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );
            // If Expiration Date is empty, return Billing Next Payment if available
            else if( !empty( $subscription->billing_next_payment ) )
                return date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $subscription->billing_next_payment ) );

        }

    }

    /**
     * Replace the {{subscription_price}} tag
     */
    public function pms_tag_subscription_price( $value, $user_info, $subscription_id, $payment_id ){

        if( !empty( $payment_id ) ){
            $payment = pms_get_payment( $payment_id );

            if( !empty( $payment->id ) ){

                $currency = pms_get_active_currency();
                $settings = get_option( 'pms_payments_settings', false );


                $price = ( $payment->amount == 0 ) ? __( 'Free', 'paid-member-subscriptions' ) : $payment->amount;


                if( !empty( $settings ) && isset( $settings['currency_position'] ) && $settings['currency_position'] == 'before' )
                    return $currency . ' ' . $price;
                else
                    return $price . ' ' . $currency;

            }
        }

    }

    /**
     * Replace the {{subscription_duration}} tag
     */
    public function pms_tag_subscription_duration( $value, $user_info, $subscription_id ){

        if( !empty( $subscription_id ) ){
            $subscription = pms_get_member_subscription( $subscription_id );

            if( !empty( $subscription->subscription_plan_id ) ){
                $plan = pms_get_subscription_plan( $subscription->subscription_plan_id );

                if ( $plan->duration == 0 )
                    return __( 'unlimited', 'paid-member-subscriptions' );
                else
                    return $plan->duration . ' ' . $plan->duration_unit . '(s)';
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

}


$merge_tags = new PMS_Merge_Tags();

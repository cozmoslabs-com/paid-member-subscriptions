<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Payment class stores and handles data about a certain payment
 *
 */
Class PMS_Payment {

    /**
     * Payment id
     *
     * @access public
     * @var int
     */
    public $id;

    /**
     * User id
     *
     * @access public
     * @var int
     */
    public $user_id;

    /**
     * Subscription plan id
     *
     * @access public
     * @var int
     */
    public $subscription_id;

    /**
     * Member Subscription ID
     *
     * @access public
     * @var int
     */
    public $member_subscription_id;

    /**
     * Payment status
     *
     * @access public
     * @var string
     */
    public $status;

    /**
     * Payment date
     *
     * @access public
     * @var datetime
     */
    public $date;

    /**
     * Payment amount
     *
     * @access public
     * @var float
     */
    public $amount;

    /**
     * Payment currency
     *
     * @access public
     * @var string
     */
    public $currency;

    /**
     * The payment type
     *
     * @access public
     * @var string
     *
     */
    public $type;

    /**
     * The payment gateway used to make the payment
     *
     * @access public
     * @var string
     *
     */
    public $payment_gateway;

    /**
     * The transaction id returned by the payment gateway
     *
     * @access public
     * @var string
     *
     */
    public $transaction_id;

    /**
     * The profile id returned by a payment gateway for a recurring profile/subscription
     *
     * @access public
     * @var string
     *
     */
    public $profile_id;

    /**
     * Error logs saved for the payment
     *
     * @access public
     * @var array
     *
     */
    public $logs;

    /**
     * User IP address
     *
     * @access public
     * @var string
     */
    public $ip_address;

    /**
     * Discount code
     *
     * @access public
     * @var int
     */
    public $discount_code;


    /**
     * Constructor
     *
     */
    public function __construct( $id = 0 ) {

        // Return if no id provided
        if( $id == 0 ) {
            $this->id = 0;
            return;
        }

        // Get payment data from the db
        $data = $this->get_data( $id );

        // Return if data is not in the db
        if( is_null( $data ) ) {
            $this->id = 0;
            return;
        }

        // Populate the data
        $this->set_instance( $data );

    }


    /**
     * Sets the object properties given an array of corresponding data
     *
     * Note: This method is not intended to be used outside of the plugin's core
     *
     * @param array $data
     *
     */
    public function set_instance( $data = array() ) {

        // Inconsistency fix between the db table row name and
        // the PMS_Payment property
        if( empty( $data['subscription_id'] ) && !empty( $data['subscription_plan_id'] ) )
            $data['subscription_id'] = $data['subscription_plan_id'];

        // Grab all properties and populate them
        foreach( get_object_vars( $this ) as $property => $value ) {

            if( isset( $data[$property] ) ) {

                // The logs are saved as json in the db, we want them as an associative array
                if( $property == 'logs' )
                    $data[$property] = !empty( $data[$property] ) ? json_decode( $data[$property], ARRAY_A ) : '';

                // Empty dates overwrite
                if( $data[$property] == '0000-00-00 00:00:00' )
                    $data[$property] = '';

                $this->$property = $data[$property];

            }

        }

        $this->member_subscription_id = pms_get_payment_meta( $this->id, 'subscription_id', true );

    }


    /**
     * Retrieve the row data for a given id
     *
     * @param int $id   - the id of the payment we wish to get
     *
     * @return array
     *
     */
    public function get_data( $id ) {

        global $wpdb;

        $result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}pms_payments WHERE id = %d", absint( $id ) ), ARRAY_A );

        return $result;

    }


    /**
     * Inserts payment data into the database
     *
     * @param array $data
     *
     * @return mixed        - int $payment_id or false if the row could not be added
     *
     */
    public function insert( $data = array() ) {

        global $wpdb;

        $defaults = array(
            'date'       => date('Y-m-d H:i:s'),
            'amount'     => 0,
            'status'     => 'pending',
            'ip_address' => pms_get_user_ip_address()
        );

        $data = wp_parse_args( $data, $defaults );

        // User ID and subscription plan ID are needed
        if( empty( $data['user_id'] ) || empty( $data['subscription_plan_id'] ) )
            return false;

        // This is stored in payment_meta so we cache it and save it after the payment has been inserted if present
        // Usually, code will save it separately, but it can be refactored to use this option
        if( !empty( $data['member_subscription_id'] ) ){
            $member_subscription_id = $data['member_subscription_id'];

            // it gets removed here manually because it's part of the object properties and it won't be removed below
            unset( $data['member_subscription_id'] );
        }

        // Eliminate all values that are not a part of the object
        $object_vars = array_keys( get_object_vars( $this ) );

        foreach( $data as $key => $val ) {

            // Inconsistency fix between the db table row name and
            // the PMS_Payment property
            if( $key == 'subscription_plan_id' )
                $key = 'subscription_id';

            if( !in_array( $key, $object_vars ) )
                unset( $data[$key] );

        }


        // Insert payment
        $insert_result = $wpdb->insert( $wpdb->prefix . 'pms_payments', $data );

        if( $insert_result ) {

            // Populate current object
            $this->id = $wpdb->insert_id;
            $this->set_instance( $data );

            // Set Member Subscription ID if present
            if( isset( $member_subscription_id ) )
                pms_update_payment_meta( $this->id, 'subscription_id', $member_subscription_id );

            /**
             * Fires right after the Payment db entry was inserted
             *
             * @param int   $id   - the id of the new payment
             * @param array $data - the provided data for the current payment
             *
             */
            do_action( 'pms_payment_insert', $this->id, $data );

            return $this->id;

        }

        return false;

    }


    /**
     * Method to update any data of the payment
     *
     * @param array $data    - the new data
     *
     * @return bool
     *
     */
    public function update( $data = array() ) {

        global $wpdb;

        $update_result = $wpdb->update( $wpdb->prefix . 'pms_payments', $data, array( 'id' => $this->id ) );

        // Can return 0 if no rows are affected
        if( $update_result !== false )
            $update_result = true;

        if( $update_result ) {

            /**
             * Fires right after the Payment db entry was updated
             *
             * @param int   $id            - the id of the payment that was updated
             * @param array $data          - the provided data to be changed for the payment
             * @param array $old_data      - the array of values representing the payment before the update
             *
             */
            do_action( 'pms_payment_update', $this->id, $data, $this->to_array() );

        }

        return $update_result;

    }


    /**
     * Removes the payment from the database
     *
     */
    public function remove() {

        if( !$this->is_valid() )
            return false;

        global $wpdb;

        $remove_result = $wpdb->delete( $wpdb->prefix . 'pms_payments', array( 'id' => $this->id ) );

        // Can return 0 if no rows are affected
        if( $remove_result !== false )
            $remove_result = true;

        if( $remove_result ) {

            /**
             * Fires right after a payment has been deleted
             *
             * @param int $id   - the id of the payment that has just been deleted from the db
             *
             */
            do_action( 'pms_payment_delete', $this->id );

        }

        return $remove_result;

    }


    /**
     * Check to see if payment is saved in the db
     *
     */
    public function is_valid() {

        if( empty($this->id) )
            return false;
        else
            return true;

    }


    /**
     * Returns the array representation of the current object instance
     *
     */
    public function to_array() {

        return get_object_vars( $this );

    }


    /**
     * Add a log entry to the payment
     *
     * @param string $type         - the type of the log
     * @param array $data          - extra data relevant to the type of error
     * @param string $error_code   - an error code that can be transformed to a human readable message
     *
     * @return bool
     *
     */
    public function log_data( $type, $data = array(), $error_code = '' ) {

        if( empty( $type ) )
            return false;

        global $wpdb;

        $payment_logs = $wpdb->get_var( "SELECT logs FROM {$wpdb->prefix}pms_payments WHERE id LIKE {$this->id}" );

        if( $payment_logs == null )
            $payment_logs = array();
        else
            $payment_logs = json_decode( $payment_logs );

        $payment_logs[] = array(
            'date'       => date( 'Y-m-d H:i:s' ),
            'type'       => $type,
            'error_code' => $error_code,
            'data'       => ( !empty( $data ) ? $data : '' )
        );

        $update_result = $wpdb->update( $wpdb->prefix . 'pms_payments', array( 'logs' => json_encode( $payment_logs ) ), array( 'id' => $this->id ) );

        if( $update_result !== false )
            $update_result = true;

        return $update_result;

    }

}

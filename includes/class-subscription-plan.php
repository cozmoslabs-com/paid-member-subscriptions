<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

Class PMS_Subscription_Plan {

    /**
     * Subscription plan id
     *
     * @access public
     * @var int
     */
    public $id;

    /**
     * Subscription plan name
     *
     * @access public
     * @var string
     */
    public $name;

    /**
     * Subscription plan description
     *
     * @access public
     * @var string
     */
    public $description;

    /**
     * Subscription plan price
     *
     * @access public
     * @var int
     */
    public $price;

    /**
     * Subscription plan status
     *
     * @access public
     * @var string
     */
    public $status;

    /**
     * Subscription plan duration
     *
     * @access public
     * @var int
     */
    public $duration;

    /**
     * Subscription plan duration unit
     *
     * @access public
     * @var string
     */
    public $duration_unit;

    /**
     * Subscription plan user role
     *
     * @access public
     * @var string
     */
    public $user_role;

    /**
     * Parent subscription plan
     *
     * @access public
     * @var string
     */
    public $top_parent;


    public $sign_up_fee;

    public $trial_duration;

    public $trial_duration_unit;

    public $recurring;

    public $type;

    public $fixed_membership;

    public $fixed_expiration_date;

    public $allow_renew;

    /**
     * Subscription Plan Installments
     *
     * @access public
     */
    public $limit_payment_cycles;

    public $number_of_payments;

    public $status_after_last_cycle;

    public $expire_after;

    public $expire_after_unit;


    public function __construct( $id_or_post ) {

        if( !is_object( $id_or_post ) )
            $id_or_post = (int)$id_or_post;

        // Abort if id is not an integer
        if( !is_object( $id_or_post ) && !is_int( $id_or_post ) )
            return;

        $this->init( $id_or_post );

    }


    public function init( $id_or_post ) {

        /*
         * Set subscription plan data from the post itself
         *
         */
        if( is_object( $id_or_post ) ) {

            $id = $id_or_post->ID;
            $post_subscription = $id_or_post;

        } else {

            $id = $id_or_post;
            $post_subscription = get_post( $id );

        }

        if( !$post_subscription || ( isset( $post_subscription->post_type ) && $post_subscription->post_type != 'pms-subscription' ) )
            return null;

        $this->id   = (int)$post_subscription->ID;
        $this->name = $post_subscription->post_title;

        /*
         * Set subscription plan data from the post meta data
         *
         */
        $post_meta_subscription = get_post_meta( $id );

        // Subscription plan description
        $this->description =  isset( $post_meta_subscription['pms_subscription_plan_description'] ) ? esc_attr( $post_meta_subscription['pms_subscription_plan_description'][0] ) : '';

        // Subscription plan price
        $this->price =  isset( $post_meta_subscription['pms_subscription_plan_price'] ) ? $post_meta_subscription['pms_subscription_plan_price'][0] : 0;

        // Subscription plan status
        $this->status =  isset( $post_meta_subscription['pms_subscription_plan_status'] ) ? $post_meta_subscription['pms_subscription_plan_status'][0] : '';

        // Subscription plan duration and duration unit
        $this->duration = ( isset( $post_meta_subscription['pms_subscription_plan_duration'] ) && !empty( $post_meta_subscription['pms_subscription_plan_duration'][0] ) ) ? $post_meta_subscription['pms_subscription_plan_duration'][0] : 0;
        $this->duration_unit = isset( $post_meta_subscription['pms_subscription_plan_duration_unit'] ) ? $post_meta_subscription['pms_subscription_plan_duration_unit'][0] : '';

        // Subscription plan sign-up fee
        $this->sign_up_fee = ( isset( $post_meta_subscription['pms_subscription_plan_sign_up_fee'] ) && !empty( $post_meta_subscription['pms_subscription_plan_sign_up_fee'][0] ) ) ? $post_meta_subscription['pms_subscription_plan_sign_up_fee'][0] : 0;

        // Subscription plan trial duration and duration unit
        $this->trial_duration = ( isset( $post_meta_subscription['pms_subscription_plan_trial_duration'] ) && !empty( $post_meta_subscription['pms_subscription_plan_trial_duration'][0] ) ) ? $post_meta_subscription['pms_subscription_plan_trial_duration'][0] : 0;
        $this->trial_duration_unit = isset( $post_meta_subscription['pms_subscription_plan_trial_duration_unit'] ) ? $post_meta_subscription['pms_subscription_plan_trial_duration_unit'][0] : '';

        // Subscription plan recurring
        $this->recurring = ( isset( $post_meta_subscription['pms_subscription_plan_recurring'] ) && !empty( $post_meta_subscription['pms_subscription_plan_recurring'][0] ) ) ? $post_meta_subscription['pms_subscription_plan_recurring'][0] : 0;

        // Set default user role
        $this->user_role = ( isset( $post_meta_subscription['pms_subscription_plan_user_role'] ) && !empty( $post_meta_subscription['pms_subscription_plan_user_role'][0] ) ) ? $post_meta_subscription['pms_subscription_plan_user_role'][0] : '';

        // Set top parent of the group
        $this->top_parent = isset( $post_meta_subscription['pms_subscription_plan_top_parent'] ) ? $post_meta_subscription['pms_subscription_plan_top_parent'][0] : '';

        // Subscription Plan Type
        $this->type = !empty( $post_meta_subscription['pms_subscription_plan_type'][0] ) ? $post_meta_subscription['pms_subscription_plan_type'][0] : 'regular';

        // Subscription Plan Fixed Membership
        $this->fixed_membership = !empty( $post_meta_subscription['pms_subscription_plan_fixed_membership'][0] ) ? $post_meta_subscription['pms_subscription_plan_fixed_membership'][0] : '';

        // Subscription Plan Fixed Expiration Date
        $this->fixed_expiration_date = !empty( $post_meta_subscription['pms_subscription_plan_expiration_date'][0] ) ? $post_meta_subscription['pms_subscription_plan_expiration_date'][0] : '';

        // Subscription Plan Allow Renew
        $this->allow_renew = !empty( $post_meta_subscription['pms_subscription_plan_allow_renew'][0] ) ? $post_meta_subscription['pms_subscription_plan_allow_renew'][0] : '';

        // Subscription Plan Installments
        $this->limit_payment_cycles = !empty( $post_meta_subscription['pms_subscription_plan_limit_payment_cycles'][0] ) ? $post_meta_subscription['pms_subscription_plan_limit_payment_cycles'][0] : '';
        $this->number_of_payments = !empty( $post_meta_subscription['pms_subscription_plan_number_of_payments'][0] ) ? $post_meta_subscription['pms_subscription_plan_number_of_payments'][0] : '';
        $this->status_after_last_cycle = !empty( $post_meta_subscription['pms_subscription_plan_status_after_last_cycle'][0] ) ? $post_meta_subscription['pms_subscription_plan_status_after_last_cycle'][0] : '';
        $this->expire_after = !empty( $post_meta_subscription['pms_subscription_plan_expire_after'][0] ) ? $post_meta_subscription['pms_subscription_plan_expire_after'][0] : '';
        $this->expire_after_unit = !empty( $post_meta_subscription['pms_subscription_plan_expire_after_unit'][0] ) ? $post_meta_subscription['pms_subscription_plan_expire_after_unit'][0] : '';

    }


    /*
     * Method that checks if the subscription plan is active
     *
     */
    public function is_active() {

        if( $this->status == 'active' )
            return true;
        elseif( $this->status == 'inactive' )
            return false;

    }

    /*
     * Method that checks if the subscription plan has signup fee
     *
     */
    public function has_sign_up_fee(){
        return ( isset( $this->sign_up_fee ) && $this->sign_up_fee != '0' );
    }

    /*
     * Method that checks if the subscription plan has trial set
     *
     */
    public function has_trial(){
        return ( isset( $this->trial_duration ) && $this->trial_duration != '0' );
    }

    /*
     * Method that checks if the subscription plan is a fixed period membership
     *
     */
    public function is_fixed_period_membership(){

        if( $this->fixed_membership == 'on' )
            return true;
        else
            return false;

    }

    /*
     * Method that checks if the subscription plan has payment installments activated
     *
     */
    public function has_installments(){

        return ( isset( $this->limit_payment_cycles ) && $this->limit_payment_cycles == 'yes' );

    }

    /*
     * Method that checks if the subscription plan allows renew (for fixed period memberships)
     *
     */
    public function fixed_period_renewal_allowed(){

        if( $this->allow_renew == 'on' )
            return true;
        else
            return false;

    }


    /*
     * Activate the subscription plan
     *
     * @param $post_id
     *
     */
    public static function activate( $post_id ) {

        if( !is_int( $post_id ) )
            return;

        update_post_meta( $post_id, 'pms_subscription_plan_status', 'active' );

        // Change the post status to "active" as well
        $post = array(
            'ID'          => $post_id,
            'post_status' => 'active',
        );
        wp_update_post( $post );

    }


    /*
     * Deactivate the Subscription Plan
     *
     * @param $post_id
     *
     */
    public static function deactivate( $post_id ) {

        if( !is_int( $post_id ) )
            return;

        update_post_meta( $post_id, 'pms_subscription_plan_status', 'inactive' );

        // Change the post status to "inactive" as well
        $post = array(
            'ID'          => $post_id,
            'post_status' => 'inactive',
        );
        wp_update_post( $post );

    }


    /*
     * Delete Subscription Plan
     *
     * @param $post_id
     *
     */
    public static function remove( $post_id ) {

        $subscription_plan_post = get_post( $post_id );

        // If the post doesn't exist just skip everything
        if( !$subscription_plan_post )
            return;


        wp_delete_post( $post_id );

    }

    /**
     * Duplicate Subscription Plan
     *
     * @param $post_id
     */
    public static function duplicate( $post_id ) {
        $post = get_post( $post_id );

        if( empty( $post ) || $post->post_type != 'pms-subscription' )
            return;

        //default fields that are copied over
        $fields = array( 'post_author', 'post_content', 'post_title', 'post_excerpt', 'post_status', 'comment_status', 'ping_status', 'post_password', 'post_name', 'post_content_filtered', 'menu_order', 'post_type', 'post_mime_type', 'comment_count' );
        $new_post = array();

        foreach( $fields as $field )
            $new_post[$field] = $post->$field;

        //set the plan as the last one from the current group
        $plan_group = array_reverse( pms_get_subscription_plans_group( $post_id ) );

        if( count( $plan_group ) > 1 ) {
            $new_post['post_parent'] = $plan_group[0]->id;
        }
        else{
            $new_post['post_parent'] = $post->post_parent;
        }

        //copy post meta data
        $new_post['meta_input'] = array();

        $meta_keys = get_post_custom_keys( $post_id );
        $to_skip = array( '_edit_lock', '_edit_last' );

        foreach( $meta_keys  as $key ){
            if( in_array( $key, $to_skip ) )
                continue;

            $meta_values = get_post_custom_values( $key, $post_id );

            foreach( $meta_values as $value ){
                $value = maybe_unserialize( $value );

                $new_post['meta_input'][$key] = $value;
            }
        }

        $new_id = wp_insert_post( wp_slash( $new_post ) );

        //redirect to post edit screen
        wp_redirect( admin_url( 'post.php?action=edit&post=' . $new_id ) );
        die();
    }

    /*
     * Check to see if subscription plan exists
     *
     */
    public function is_valid() {

        if( empty($this->id) )
            return false;
        else
            return true;

    }


    /**
     * Returns the expiration date of the subscription plan
     *
     * @param bool $timestamp
     *
     * @return string
     *
     */
    public function get_expiration_date( $timestamp = false ) {

        if ( $this->is_fixed_period_membership() ){

            if( $this->fixed_period_renewal_allowed() && strtotime( $this->fixed_expiration_date ) < time() ){

                $fixed_expiration_date = date_create( date( 'Y-m-d', strtotime( $this->fixed_expiration_date ) ) );
                $current_date          = date_create( date( 'Y-m-d', time() ) );
                $difference            = date_diff( $fixed_expiration_date, $current_date );

                if( isset( $difference ) && isset( $difference->y ) ){

                    $years = (int)$difference->y + 1;
                    $date = strtotime( $this->fixed_expiration_date . '+ ' . $years . ' years' );

                }

            } else {
                $date = strtotime( $this->fixed_expiration_date . ' 23:59:59' );
            }
            
        } else {
            if( $this->duration != 0 ) {
                $duration      = $this->duration;
                $duration_unit = $this->duration_unit;
                $date          = strtotime( "+" . $duration . ' ' . $duration_unit );
            } else
                $date = '';
        }

        $return_date = '';

        if( $timestamp )
            $return_date = $date;
        elseif( $date != '' )
            $return_date = date( 'Y-m-d H:i:s', $date );

        return apply_filters( 'pms_subscription_plan_get_expiration_date', $return_date, $this->id );

    }


    /**
     * Returns the expiration date of the trial period
     *
     * @param bool $timestamp
     *
     * @return string
     *
     */
    public function get_trial_expiration_date( $timestamp = false ) {

        if( empty( $this->trial_duration ) )
            return '';

        $time = strtotime( "+" . $this->trial_duration . ' ' . $this->trial_duration_unit );

        if( $this->is_fixed_period_membership() && strtotime( $this->get_expiration_date() ) < $time )
            $time = strtotime( $this->get_expiration_date() );

        if( $timestamp )
            return $time;
        else
            return date( 'Y-m-d H:i:s', $time );

    }


    /*
     * Returns the user role associated with the subscription plan
     *
     */
    public function get_user_role() {

        $user_role = get_post_meta( $this->id, 'pms_subscription_plan_user_role', true );

        if( empty($user_role) )
            $user_role = 'subscriber';

        return $user_role;

    }

}

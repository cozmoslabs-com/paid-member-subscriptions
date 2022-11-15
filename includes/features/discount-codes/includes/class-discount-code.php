<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;


Class PMS_IN_Discount_Code {

    public $id;

    public $name;

    public $code;

    public $type;

    public $amount;

    public $subscriptions;

    public $uses;

    public $max_uses;

    public $max_uses_per_user;

    public $start_date;

    public $expiration_date;

    public $status;

    public $recurring_payments;

    public $new_users_only;


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
         * Set Discount code data from the post itself
         *
         */
        if( is_object( $id_or_post ) ) {

            $id = $id_or_post->ID;
            $post_discount = $id_or_post;

        } else {

            $id = $id_or_post;
            $post_discount = get_post( $id );

        }


        if( !$post_discount )
            return null;

        $this->id = $post_discount->ID;
        $this->name = $post_discount->post_title;


        /*
         * Set discount code data from the post meta data (metabox)
         *
         */
        $post_meta_discount = get_post_meta( $id );

        // Discount code
        $this->code =  isset( $post_meta_discount['pms_discount_code'] ) ? $post_meta_discount['pms_discount_code'][0] : '';

        // Discount type
        $this->type =  isset( $post_meta_discount['pms_discount_type'] ) ? $post_meta_discount['pms_discount_type'][0] : '';

        // Discount amount
        $this->amount =  isset( $post_meta_discount['pms_discount_amount'] ) ? $post_meta_discount['pms_discount_amount'][0] : 0;

        // Discount subscriptions
        $this->subscriptions = isset( $post_meta_discount['pms_discount_subscriptions'] ) ? $post_meta_discount['pms_discount_subscriptions'][0] : '';

        // Discount uses
        $this->uses =  isset( $post_meta_discount['pms_discount_uses'] ) ? $post_meta_discount['pms_discount_uses'][0] : 0;

        // Discount maximum uses
        $this->max_uses =  isset( $post_meta_discount['pms_discount_max_uses'] ) ? $post_meta_discount['pms_discount_max_uses'][0] : 0;

        // Discount maximum uses per user
        $this->max_uses_per_user =  isset( $post_meta_discount['pms_discount_max_uses_per_user'] ) ? $post_meta_discount['pms_discount_max_uses_per_user'][0] : 0;

        // Discount start date
        $this->start_date =  isset( $post_meta_discount['pms_discount_start_date'] ) ? $post_meta_discount['pms_discount_start_date'][0] : '';

        //Discount expiration date
        $this->expiration_date =  isset( $post_meta_discount['pms_discount_expiration_date'] ) ? $post_meta_discount['pms_discount_expiration_date'][0] : '';

        // Discount status
        $this->status =  isset( $post_meta_discount['pms_discount_status'] ) ? $post_meta_discount['pms_discount_status'][0] : '';

        //Apply to all recurring payments?
        $this->recurring_payments =  isset( $post_meta_discount['pms_discount_recurring_payments'] )  ? $post_meta_discount['pms_discount_recurring_payments'][0] : '';

        // Apply Discount Code only for new users
        $this->new_users_only = isset( $post_meta_discount['pms_discount_new_users_only'] )  ? $post_meta_discount['pms_discount_new_users_only'][0] : '';

    }


    /*
     * Method that checks if the discount code is active
     *
     */
    public function is_active() {

        if( $this->status == 'active' )
            return true;
        elseif( ( $this->status == 'inactive' ) || ( $this->status == 'expired') )
            return false;

    }

    /*
     * Method that checks if the discount code is expired
     *
     */
    public function is_expired() {

        if( $this->status == 'expired' )
            return true;
        else
            return false;
    }


    /*
     * Activate the discount code
     *
     * @param $post_id
     *
     */
    public static function activate( $post_id ) {

        if( !is_int( $post_id ) )
            return;

        update_post_meta( $post_id, 'pms_discount_status', 'active' );

        // Change the post status to "active" as well
        $post = array(
                'ID'           => $post_id,
                'post_status'   => 'active',
                 );
        wp_update_post( $post );
    }


    /*
     * Deactivate the discount code
     *
     * @param $post_id
     *
     */
    public static function deactivate( $post_id ) {

        if( !is_int( $post_id ) )
            return;

        update_post_meta( $post_id, 'pms_discount_status', 'inactive' );

        // Change the post status to "inactive" as well
        $post = array(
            'ID'           => $post_id,
            'post_status'   => 'inactive',
        );
        wp_update_post( $post );

    }

    /**
     * Duplicate Subscription Plan
     *
     * @param $post_id
     */
    public static function duplicate( $post_id ) {
        $post = get_post( $post_id );

        if( empty( $post ) || $post->post_type != 'pms-discount-codes' )
            return;

        //default fields that are copied over
        $fields = array( 'post_author', 'post_content', 'post_title', 'post_excerpt', 'post_status', 'comment_status', 'ping_status', 'post_parent', 'post_password', 'post_name', 'post_content_filtered', 'menu_order', 'post_type', 'post_mime_type', 'comment_count' );
        $new_post = array();

        foreach( $fields as $field )
            $new_post[$field] = $post->$field;

        //copy post meta data
        $new_post['meta_input'] = array();

        $meta_keys = get_post_custom_keys( $post_id );
        $to_skip = array( '_edit_lock', '_edit_last', 'pms_discount_uses' );

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
     * Delete Discount code
     *
     * @param $post_id
     *
     */
    public static function remove( $post_id ) {

        $discount_code_post = get_post( $post_id );

        // If the post doesn't exist just skip everything
        if( !$discount_code_post )
            return;


        wp_delete_post( $post_id );

    }


    /*
     * Check to see if discount code exists
     *
     */
    public function is_valid() {

        if( empty($this->id) )
            return false;
        else
            return true;

    }

}

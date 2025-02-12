<?php
/**
 * Class for adding the Discount Codes metabox details
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;


if ( class_exists('PMS_Meta_Box') ){

    Class PMS_IN_Discount_Codes_Meta_Box extends PMS_Meta_Box {

     /*
     * Method to hook the output and save data methods
     *
     */
        public function init() {

            // Hook the output method to the parent's class action for output instead of overwriting the
            // output_content method
            add_action( 'pms_output_content_meta_box_' . $this->post_type . '_' . $this->id, array( $this, 'output' ) );

            // Hook the save_data method to the parent's class action for saving data instead of overwriting the
            // save_meta_box method
            add_action( 'pms_save_meta_box_pms-discount-codes', array( $this, 'save_data' ) );

            // Add admin notices for validating the entered discount data
            add_action( 'admin_notices', array( $this, 'add_admin_notices' ) );

        }

     /*
     * Method to output the HTML for this meta-box
     *
     */
        public function output( $post ) {

            $discount = new PMS_IN_Discount_Code( $post );

            include_once PMS_IN_DC_PLUGIN_DIR_PATH. '/views/view-meta-box-discount-codes.php';

        }

     /*
     * Method to verify if a discount "promotion code" is unique
     *
     */
     static function is_unique_code( $code ){
         global $post;

         if ( !empty( $post->ID ) ) {
             $discount_ID = PMS_IN_Discount_Codes_Meta_Box::get_discount_ID_by_code($code, $post->ID);

             if ( !empty( $discount_ID ) )//discount 'promotion code' already exists
                return false;
         }

         return true;
     }

     /*
     * Method to verify if a valid date of a certain format was entered
     *
     */
     static function is_valid_date($date, $format){

         $d = DateTime::createFromFormat($format, $date);
         return $d && $d->format($format) == $date;
     }

     /*
     * Method that returns discount meta given a discount "code"
     *
     * */
     static function get_discount_meta_by_code( $code ) {
         $discount_ID = PMS_IN_Discount_Codes_Meta_Box::get_discount_ID_by_code( $code );

         if ( !empty($discount_ID) ) { // discount exists and is active
             $discount_meta = get_post_meta($discount_ID);
             return $discount_meta;
         }
         return '';
     }

     /*
     * Method that returns the discount ID given a discount "code"
     *
     * */
     static function get_discount_ID_by_code( $code , $exclude_id = '' ){

         $discount_codes = get_posts(array(
             'post_type'   => 'pms-discount-codes',
             'post_status' => 'any',
             'meta_key'    => 'pms_discount_code',
             'meta_value'  => $code,
             'exclude'     => $exclude_id )          // used to exclude current discount when updating discount data
         );

         if ( !empty($discount_codes) && ( $discount_codes[0]->post_status == 'active') ) { // discount code exists and is active
            return $discount_codes[0]->ID;
         }

         return '';
     }


     /*
     * Method to validate the data and save it for this meta-box
     *
     */
     public function save_data( $post_id ) {

        if( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'pms-discount-codes-bulk-add' )
            return;

         $validation_errors = array(); // here we'll store all the validation errors

         // Update discount code if entered promotion code is unique
         if( !empty( $_POST['pms_discount_code'] ) ) {

            $discount_code = sanitize_text_field( $_POST['pms_discount_code'] );

            // Check for unique promotion code
             if ( PMS_IN_Discount_Codes_Meta_Box::is_unique_code( $discount_code ) )
                 update_post_meta( $post_id, 'pms_discount_code', $discount_code );
             else
                 $validation_errors[] = __('The promotion code you entered already exists. Please choose a different code.', 'paid-member-subscriptions');
         }


         // Update discount type
         if( isset( $_POST['pms_discount_type'] ) )
             update_post_meta( $post_id, 'pms_discount_type', sanitize_text_field( $_POST['pms_discount_type'] ) );


         // Update discount amount
         if( isset( $_POST['pms_discount_amount'] ) ) {

             $discount_amount = sanitize_text_field( $_POST['pms_discount_amount'] );

             // Set max amount to 100 when discount type is 'percent'
             if ( (isset( $_POST['pms_discount_type'] )) && ($_POST['pms_discount_type'] == 'percent') && ( intval($discount_amount) > 100 ) )
                 $discount_amount = '100';

             // Update only if amount is a positive integer.
             if( is_numeric( $discount_amount ) && ( intval($discount_amount) >= 0 ) )
                 update_post_meta( $post_id, 'pms_discount_amount', $discount_amount );
             else
                 $validation_errors[] = __('Amount needs to be a positive number.','paid-member-subscriptions');
         }


         // Update discount subscription(s)
         if( !empty( $_POST['pms_discount_subscriptions'] ) && is_array($_POST['pms_discount_subscriptions']) ){
             $discount_subscriptions = implode(',', array_map( 'sanitize_text_field', $_POST['pms_discount_subscriptions'] ) );
             update_post_meta( $post_id, 'pms_discount_subscriptions', $discount_subscriptions );
         }



         // Update discount maximum uses
         if( isset( $_POST['pms_discount_max_uses'] ) ){

             $max_uses = sanitize_text_field( $_POST['pms_discount_max_uses'] );

             if( is_numeric($max_uses) && (intval($max_uses) >= 0) && ($max_uses == round($max_uses)) )
                 update_post_meta( $post_id, 'pms_discount_max_uses', $max_uses );
             else
                 $validation_errors[] = __('Maximum uses needs to be a positive integer.','paid-member-subscriptions');
         }


         // Update discount maximum uses per user
         if( isset( $_POST['pms_discount_max_uses_per_user'] ) ){

             $max_uses_per_user = sanitize_text_field( $_POST['pms_discount_max_uses_per_user'] );

             if( is_numeric($max_uses_per_user) && (intval($max_uses_per_user) >= 0) && ($max_uses_per_user == round($max_uses_per_user)) )
                 update_post_meta( $post_id, 'pms_discount_max_uses_per_user', $max_uses_per_user );
             else
                 $validation_errors[] = __('Maximum discount uses per user needs to be a positive integer.','paid-member-subscriptions');
         }


         // Update discount start date
         if( !empty( $_POST['pms_discount_start_date'] ) ) {

            $start_date = sanitize_text_field( $_POST['pms_discount_start_date'] );

             //check if start date is valid
             if ( PMS_IN_Discount_Codes_Meta_Box::is_valid_date( $start_date, 'Y-m-d') )
                 update_post_meta( $post_id, 'pms_discount_start_date', $start_date );
             else
                 $validation_errors[] = __('Please enter a valid discount start date in the format of yyyy-mm-dd.', 'paid-member-subscriptions');
         }

         // Update discount expiration date
         if( !empty( $_POST['pms_discount_expiration_date'] ) ) {

            $expiration_date = sanitize_text_field( $_POST['pms_discount_expiration_date'] );

             //check if expiration date is valid
             if ( PMS_IN_Discount_Codes_Meta_Box::is_valid_date( $expiration_date ,'Y-m-d') )

                 if ( strtotime( $expiration_date ) < time() ) // discount is expired
                     $validation_errors[] = __('The discount code has already expired. Please enter a different expiration date.','paid-member-subscriptions');
                 else
                    update_post_meta($post_id, 'pms_discount_expiration_date', $expiration_date );

             else
                 $validation_errors[] = __('Please enter a valid discount expiration date in the format of yyyy-mm-dd.','paid-member-subscriptions');
         }


        // Update discount status
         if( isset( $_POST['pms_discount_status'] ) ) {

             $status = sanitize_text_field( $_POST['pms_discount_status'] );

             update_post_meta($post_id, 'pms_discount_status', $status );

             if ( ! wp_is_post_revision( $post_id ) ){

                 // unhook this function so it doesn't loop infinitely
                 remove_action('pms_save_meta_box_pms-discount-codes', array( $this, 'save_data' ));

                 // Change the post status as the discount status
                 $post = array(
                     'ID'           => $post_id,
                     'post_status'   => $status,
                 );
                 wp_update_post( $post );

                 // re-hook this function
                 add_action('pms_save_meta_box_pms-discount-codes', array( $this, 'save_data' ) );

             }

         }

         // Update discount recurring payments
         if( isset( $_POST['pms_discount_recurring_payments'] ) )
             update_post_meta( $post_id, 'pms_discount_recurring_payments', 'checked' );
         else
             update_post_meta( $post_id, 'pms_discount_recurring_payments', '' );

         // Update discount new users only checkbox
         if( isset( $_POST['pms_discount_new_users_only'] ) )
             update_post_meta( $post_id, 'pms_discount_new_users_only', 'checked' );
         else
             update_post_meta( $post_id, 'pms_discount_new_users_only', '' );

         delete_transient( 'pms_discounts_are_defined_data' );

         if ( !empty($validation_errors) ) {  // If we have validation errors, save them in a transient
             set_transient( 'pms_dc_metabox_validation_errors', $validation_errors, 60 );
         }

     }


     /*
     * Method for displaying validation errors using admin_notices hook
     *
     */
     public function add_admin_notices() {

            $validation_errors = get_transient('pms_dc_metabox_validation_errors');

            if ( !empty( $validation_errors ) ){

                delete_transient( 'pms_dc_metabox_validation_errors' );

                foreach ( $validation_errors as $error )
                    echo '<div class="error">
                            <p>' . esc_html( $error ) . '</p>
                         </div>';
            }
     }


    } // end class PMS_IN_Discount_Codes_Meta_Box

    $pms_meta_box_discount_code_details = new PMS_IN_Discount_Codes_Meta_Box( 'pms_discount_codes', __( 'Discount Code Details', 'paid-member-subscriptions' ), 'pms-discount-codes', 'normal' );
    $pms_meta_box_discount_code_details->init();

} // end class_exists

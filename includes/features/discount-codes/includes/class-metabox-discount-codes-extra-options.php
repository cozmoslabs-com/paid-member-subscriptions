<?php
/**
 * Class for adding the Discount Codes metabox details
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;


if ( class_exists('PMS_Meta_Box') ){

    Class PMS_IN_Discount_Codes_Extra_Options_Meta_Box extends PMS_Meta_Box {

     /*
     * Method to hook the output
     *
     */
        public function init() {

            // Hook the output method to the parent's class action for output instead of overwriting the
            // output_content method
            add_action( 'pms_output_content_meta_box_' . $this->post_type . '_' . $this->id, array( $this, 'output' ) );

        }

     /*
     * Method to output the HTML for this meta-box
     *
     */
        public function output( $post ) {

            $discount = new PMS_IN_Discount_Code( $post );

            include_once PMS_IN_DC_PLUGIN_DIR_PATH. '/views/view-meta-box-discount-codes-extra-options.php';

        }


    } // end class PMS_IN_Discount_Codes_Meta_Box


    function pms_init_discount_code_extra_options_meta_box() {

        $pms_meta_box_discount_code_details = new PMS_IN_Discount_Codes_Extra_Options_Meta_Box( 'pms_discount_codes_extra_options', __( 'Advanced Options', 'paid-member-subscriptions' ), 'pms-discount-codes', 'normal' );
        $pms_meta_box_discount_code_details->init();
    
    }
    add_action( 'init', 'pms_init_discount_code_extra_options_meta_box', 2 );

}

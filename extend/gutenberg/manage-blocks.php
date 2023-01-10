<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function pms_block_editor_enqueue()
{
    wp_enqueue_script('pms_block_frontend_js', PMS_PLUGIN_DIR_URL . 'assets/js/front-end.js', array('jquery'), PMS_VERSION);
    wp_enqueue_style('pms_block_frontend_stylesheet_css', PMS_PLUGIN_DIR_URL . 'assets/css/style-front-end.css', array('wp-edit-blocks'), PMS_VERSION);

    wp_enqueue_style('pms_block_stylesheet_css', plugin_dir_url(__FILE__) . 'assets/css/gutenberg-blocks.css', array(), PMS_VERSION);


    //Group Memberships
    if ( defined( 'PMS_IN_GM_PLUGIN_DIR_URL' ) ) {
        wp_enqueue_script('pms_block_group-memberships', PMS_IN_GM_PLUGIN_DIR_URL . 'assets/js/front-end.js', array('jquery'), PMS_VERSION);
        wp_enqueue_style('pms_block_group-memberships_css', PMS_IN_GM_PLUGIN_DIR_URL . 'assets/css/style-front-end.css', array(), PMS_VERSION);
    }

    //Discount Codes
    if ( defined( 'PMS_IN_DC_PLUGIN_DIR_URL' ) ) {
        wp_enqueue_script('pms_block_discount-codes', PMS_IN_DC_PLUGIN_DIR_URL . 'assets/js/frontend-discount-code.js', array('jquery'), PMS_VERSION);
        wp_enqueue_style('pms_block_discount-codes_css', PMS_IN_DC_PLUGIN_DIR_URL . 'assets/css/style-front-end.css', array(), PMS_VERSION);
    }

    //Pay What You Want
    if ( defined( 'PMS_IN_PWYW_PLUGIN_DIR_URL' ) ) {
        wp_enqueue_script('pms_block_pay-what-you-want', PMS_IN_PWYW_PLUGIN_DIR_URL . 'assets/js/front-end.js', array('jquery'), PMS_VERSION);
    }

    //Invoices
    if ( defined( 'PMS_IN_INV_PLUGIN_DIR_URL' ) ) {
        wp_enqueue_style('pms_block_discount-codes_css', PMS_IN_INV_PLUGIN_DIR_URL . 'assets/css/style-front-end.css', array(), PMS_VERSION);
    }

    //Tax
    if ( defined( 'PMS_IN_TAX_PLUGIN_DIR_URL' ) ) {
        wp_enqueue_style('pms_block_tax_css', PMS_IN_TAX_PLUGIN_DIR_URL . 'assets/css/front-end.css', array(), PMS_VERSION);
    }
}
add_action( 'enqueue_block_editor_assets', 'pms_block_editor_enqueue' );

function pms_register_layout_category($categories ) {

    $categories[] = array(
        'slug'  => 'pms-block',
        'title' => 'Paid Member Subscriptions'
    );

    return $categories;
}

if ( version_compare( get_bloginfo( 'version' ), '5.8', '>=' ) ) {
    add_filter( 'block_categories_all', 'pms_register_layout_category' );
} else {
    add_filter( 'block_categories', 'pms_register_layout_category' );
}

include_once(PMS_PLUGIN_DIR_PATH . 'extend/gutenberg/account/account.php');
include_once(PMS_PLUGIN_DIR_PATH . 'extend/gutenberg/login/login.php');
include_once(PMS_PLUGIN_DIR_PATH . 'extend/gutenberg/recover-password/recover-password.php');
include_once(PMS_PLUGIN_DIR_PATH . 'extend/gutenberg/register/register.php');
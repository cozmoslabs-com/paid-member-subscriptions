<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action(
    'enqueue_block_assets',
    function () {

        if ( !is_admin() ) {
            return;
        }

        // Enqueue necessary assets for the Editor interface
        wp_enqueue_script('pms_block_frontend_js', PMS_PLUGIN_DIR_URL . 'assets/js/front-end.js', array('jquery'), PMS_VERSION);
        wp_enqueue_style('pms_block_frontend_stylesheet_css', PMS_PLUGIN_DIR_URL . 'assets/css/style-front-end.css', array('wp-edit-blocks'), PMS_VERSION);

        // Load stylesheet for the Default Form Style if the active WP Theme is a Block Theme (Block Themes were introduced in WordPress since the 5.9 release)
        if ( version_compare( get_bloginfo( 'version' ), '5.9', '>=' ) && function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
            $active_design = function_exists( 'pms_get_active_form_design' ) ? pms_get_active_form_design() : 'form-style-default';

            // Load stylesheet only if the active Form Design is the Default Style
            if ( $active_design === 'form-style-default' && file_exists( PMS_PLUGIN_DIR_PATH . 'assets/css/style-block-themes-front-end.css' ) )
                wp_enqueue_style('pms_block_themes_front_end_stylesheet', PMS_PLUGIN_DIR_URL . 'assets/css/style-block-themes-front-end.css', array(), PMS_VERSION );
        }

        wp_enqueue_style('pms_block_stylesheet_css', plugin_dir_url( __FILE__ ) . 'blocks/assets/css/gutenberg-blocks.css', array(), PMS_VERSION);

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
);

add_action(
	'enqueue_block_editor_assets',
	function () {

		global $pagenow;

		$arrDeps = ($pagenow === 'widgets.php') ?
			array( 'wp-blocks', 'wp-dom', 'wp-dom-ready', 'wp-edit-widgets', 'lodash' )
			: array( 'wp-blocks', 'wp-dom', 'wp-dom-ready', 'wp-edit-post', 'lodash', );

		$subscription_plans = pms_get_subscription_plans();
		$settings_pages     = get_option( 'pms_general_settings' );


		// Register the Link Generator assets
		wp_register_script(
			'pms-block-editor-assets-link-generator',
			PMS_PLUGIN_DIR_URL . 'extend/gutenberg/link-generator/build/index.js',
			$arrDeps,
			PMS_VERSION
		);
		wp_enqueue_script( 'pms-block-editor-assets-link-generator' );

		$vars_array_link_generator = array(
			'subscriptionPlans' => $subscription_plans,
			'registerPageID'    => ( isset( $settings_pages['register_page'] ) && $settings_pages['register_page'] !== -1 ) ? $settings_pages['register_page'] : false,
		);

		wp_localize_script( 'pms-block-editor-assets-link-generator', 'pmsBlockEditorDataLinkGenerator', $vars_array_link_generator );


		// Register the Block Content Restriction assets
		wp_register_script(
			'pms-block-editor-assets-block-content-restriction',
			PMS_PLUGIN_DIR_URL . 'extend/gutenberg/block-content-restriction/build/index.js',
			$arrDeps,
			PMS_VERSION
		);
		wp_enqueue_script('pms-block-editor-assets-block-content-restriction');

		if (!function_exists('get_editable_roles')) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}

		$vars_array_block_content_restriction = array(
			'subscriptionPlans' => $subscription_plans,
			'registerPageID'    => ( isset( $settings_pages['register_page'] ) && $settings_pages['register_page'] !== -1 ) ? $settings_pages[ 'register_page' ] : false,
		);

		wp_localize_script('pms-block-editor-assets-block-content-restriction', 'pmsBlockEditorDataBlockContentRestriction', $vars_array_block_content_restriction);


		wp_register_style('pms_block_editor_stylesheet_css', PMS_PLUGIN_DIR_URL . 'extend/gutenberg/style-block-editor.css', array(), PMS_VERSION);
		wp_enqueue_style( 'pms_block_editor_stylesheet_css' );
	}
);

add_action(
	'init',
	function () {
		global $wp_version;

		// Register the Content Restriction Start and Content Restriction End blocks
		if ( version_compare( $wp_version, "5.0.0", ">=" ) ) {
			if( file_exists( PMS_PLUGIN_DIR_PATH . 'extend/gutenberg/blocks/build/content-restriction-start' ) )
				register_block_type( PMS_PLUGIN_DIR_PATH . 'extend/gutenberg/blocks/build/content-restriction-start' );
			if( file_exists( PMS_PLUGIN_DIR_PATH . 'extend/gutenberg/blocks/build/content-restriction-end' ) )
				register_block_type( PMS_PLUGIN_DIR_PATH . 'extend/gutenberg/blocks/build/content-restriction-end' );
		}
        //Register the shortcode blocks
        if ( version_compare( $wp_version, "5.0.0", ">=" ) ) {
            if( file_exists( PMS_PLUGIN_DIR_PATH . 'extend/gutenberg/blocks/account.php' ) )
                include_once PMS_PLUGIN_DIR_PATH . 'extend/gutenberg/blocks/account.php' ;
            if( file_exists( PMS_PLUGIN_DIR_PATH . 'extend/gutenberg/blocks/login.php' ) )
                include_once PMS_PLUGIN_DIR_PATH . 'extend/gutenberg/blocks/login.php' ;
            if( file_exists( PMS_PLUGIN_DIR_PATH . 'extend/gutenberg/blocks/recover-password.php' ) )
                include_once PMS_PLUGIN_DIR_PATH . 'extend/gutenberg/blocks/recover-password.php' ;
            if( file_exists( PMS_PLUGIN_DIR_PATH . 'extend/gutenberg/blocks/register.php' ) )
                include_once PMS_PLUGIN_DIR_PATH . 'extend/gutenberg/blocks/register.php' ;
        }
	}
);

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

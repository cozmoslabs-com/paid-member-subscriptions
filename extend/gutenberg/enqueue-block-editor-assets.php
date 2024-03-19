<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

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
	}
);

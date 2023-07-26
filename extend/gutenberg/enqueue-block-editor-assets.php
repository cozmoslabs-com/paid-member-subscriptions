<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action(
	'enqueue_block_editor_assets',
	function () {
		wp_register_script(
			'pms-block-editor-assets',
			PMS_PLUGIN_DIR_URL . 'extend/gutenberg/build/index.js',
			[ 'wp-blocks', 'wp-dom', 'wp-dom-ready', 'wp-edit-post', 'lodash' ],
			PMS_VERSION
		);
		wp_enqueue_script( 'pms-block-editor-assets' );

		$subscription_plans = pms_get_subscription_plans();
		$settings_pages = get_option( 'pms_general_settings' );

		$vars_array = array(
			'subscriptionPlans' => json_encode( $subscription_plans ),
			'registerPageID'    => json_encode( ( $settings_pages && $settings_pages[ 'register_page' ] !==-1 ) ? $settings_pages[ 'register_page' ] : false ),
		);

		wp_localize_script( 'pms-block-editor-assets', 'pmsBlockEditorData', $vars_array );

		wp_register_style('pms_block_editor_stylesheet_css', PMS_PLUGIN_DIR_URL . 'extend/gutenberg/css/style-block-editor.css', array(), PMS_VERSION);
		wp_enqueue_style( 'pms_block_editor_stylesheet_css' );
	}
);
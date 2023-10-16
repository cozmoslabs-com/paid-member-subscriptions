<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action(
	'enqueue_block_editor_assets',
	function () {

		// Register the Link Generator assets
		wp_register_script(
			'pms-block-editor-assets-link-generator',
			PMS_PLUGIN_DIR_URL . 'extend/gutenberg/link-generator/build/index.js',
			[ 'wp-blocks', 'wp-dom', 'wp-dom-ready', 'wp-edit-post', 'lodash' ],
			PMS_VERSION
		);
		wp_enqueue_script( 'pms-block-editor-assets-link-generator' );

		$subscription_plans = pms_get_subscription_plans();
		$settings_pages     = get_option( 'pms_general_settings' );

		$vars_array_link_generator = array(
			'subscriptionPlans' => json_encode( $subscription_plans ),
			'registerPageID'    => json_encode( ( isset( $settings_pages['register_page'] ) && $settings_pages['register_page'] !== -1 ) ? $settings_pages['register_page'] : false ),
		);

		wp_localize_script( 'pms-block-editor-assets-link-generator', 'pmsBlockEditorDataLinkGenerator', $vars_array_link_generator );


		// Register the Block Content Restriction assets
		wp_register_script(
			'pms-block-editor-assets-block-content-restriction',
			PMS_PLUGIN_DIR_URL . 'extend/gutenberg/block-content-restriction/build/index.js',
			['wp-blocks', 'wp-dom', 'wp-dom-ready', 'wp-edit-post', 'lodash'],
			PMS_VERSION
		);
		wp_enqueue_script('pms-block-editor-assets-block-content-restriction');

		if (!function_exists('get_editable_roles')) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}

		$user_roles_initial = get_editable_roles();

		foreach ($user_roles_initial as $key => $role) {
			$user_roles[] = [
				"slug" => $key,
				"name" => $role['name'],
			];
		}


		$vars_array_block_content_restriction = array(
			'subscriptionPlans' => json_encode( $subscription_plans ),
			'userRoles' => json_encode($user_roles),
			'registerPageID'    => json_encode( ( $settings_pages && $settings_pages[ 'register_page' ] !==-1 ) ? $settings_pages[ 'register_page' ] : false ),
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

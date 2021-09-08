<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

Class PMS_Uninstaller {

	/**
	 * Paid Member Subscriptions default prefix for the custom tables.
	 * It should match with the one from the plugin's main class
	 *
	 * @access private
	 * @var string
	 *
	 */
	private $db_prefix = 'pms_';

	/**
	 * @access private
	 * @var string
	 *
	 */
	private $nonce_name = 'pms_uninstall_nonce';


	/**
	 * @access private
	 * @var bool
	 *
	 */
	private $has_permission = false;


	/**
	 * Constructor
	 *
	 * @param string $nonce
	 *
	 */
	public function __construct( $nonce = '' ) {

		if( current_user_can( 'manage_options' ) && !empty( $nonce ) && wp_verify_nonce( $nonce, $this->nonce_name ) )
			$this->has_permission = true;

	}


	/**
	 * Will run every method needed to remove all data from the DB
	 * 
	 *
	 */
	public function run() {

		if( !$this->has_permission )
			return null;

		if( $this->has_permission ) {

			// Remove data
			$this->remove_user_roles();
			$this->remove_options();
			$this->remove_tables();
			$this->remove_postmeta();

			// Deactivate the plugin
			if( defined( 'PMS_PLUGIN_BASENAME' ) )
				deactivate_plugins( PMS_PLUGIN_BASENAME );

			return true;

		}

	}


	/*
	 * Removes all custom user roles
	 *
	 */
	private function remove_user_roles() {

		global $wp_roles;

		$custom_roles = array();

		// Grab all custom roles created by the plugin
		if( !empty( $wp_roles->roles ) ) {
			foreach( $wp_roles->roles as $role_slug => $role_details ) {
				if( strpos( $role_slug, 'pms_subscription_plan_' ) !== false )
					$custom_roles[] = $role_slug;
			}
		}

		// Get all members
		$users = get_users();

		// Remove the custom user role from the user
		if( !empty( $users ) && !empty( $custom_roles ) ) {
			foreach( $users as $user ) {
				foreach( $custom_roles as $custom_role )
					pms_remove_user_role( $user->ID, $custom_role );
			}
		}

		
		// Remove the user role altogether
		if( !empty( $custom_roles ) ) {
			foreach( $custom_roles as $custom_role )
				remove_role( $custom_role );
		}

	}


	/**
	 * Removes all options prefixed with "pms_" from the options table
	 *
	 */
	private function remove_options() {

		global $wpdb;

		// Grab all options prefixed by "pms_"
		$options = $wpdb->get_results( "SELECT * FROM {$wpdb->options} WHERE option_name LIKE '%pms_%'", ARRAY_A );

		// If there are options, remove them
		if( !empty( $options ) ) {
			foreach( $options as $option ) {
				delete_option( $option['option_name'] );
			}
		}

	}


	/**
	 * Removes all the custom tables we create
	 * At the moment there are only two: pms_payments, pms_member_subscriptions
	 *
	 */
	private function remove_tables() {

		global $wpdb;

		$tables = array( 'payments', 'member_subscriptions', 'paymentmeta', 'member_subscriptionmeta' );

		foreach( $tables as $table )
			$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$this->db_prefix}{$table}" );

	}

	/*
	 * Removes everything related to PMS from the wp_posts and wp_postmeta tables
	 *
	 */
	private function remove_postmeta(){

	    global $wpdb;

        $delete_posts = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->posts} WHERE post_type LIKE %s", 'pms\-%' ) );
        $delete_postmeta = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s OR meta_key LIKE %s", 'pms\_%', 'pms\-%' ) );

    }

}
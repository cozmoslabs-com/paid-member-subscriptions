<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 *
 *
 */
function pms_dismiss_admin_notifications() {

	if( ! empty( $_GET['pms_dismiss_admin_notification'] ) ) {

		$notifications = PMS_Plugin_Notifications::get_instance();
		$notifications->dismiss_notification( sanitize_text_field( $_GET['pms_dismiss_admin_notification'] ) );

	}

}
add_action( 'admin_init', 'pms_dismiss_admin_notifications' );


/**
 *
 *
 */
function pms_add_admin_menu_notification_counts() {

	global $menu, $submenu;

	$notifications = PMS_Plugin_Notifications::get_instance();

	/**
	 *
	 *
	 */
	if( ! empty( $menu ) ) {

		foreach( $menu as $menu_position => $menu_data ) {

			if( ! empty( $menu_data[2] ) && $menu_data[2] == 'paid-member-subscriptions' ) {

				$menu_count = $notifications->get_count_in_menu();

				if( ! empty( $menu_count ) )
					$menu[$menu_position][0] .= '<span class="update-plugins pms-update-plugins"><span class="plugin-count">' . $menu_count . '</span></span>';

			}

		}

	}


	/**
	 *
	 *
	 */
	if( ! empty( $submenu['paid-member-subscriptions'] ) ) {

		foreach( $submenu['paid-member-subscriptions'] as $menu_position => $menu_data ) {

			$menu_count = $notifications->get_count_in_submenu( $menu_data[2] );

			if( ! empty( $menu_count ) )
				$submenu['paid-member-subscriptions'][$menu_position][0] .= '<span class="update-plugins pms-update-plugins"><span class="plugin-count">' . $menu_count . '</span></span>';

		}

	}

}
add_action( 'admin_init', 'pms_add_admin_menu_notification_counts', 1000 );


/**
 *
 *
 */
function pms_add_plugin_notification( $notification_id = '', $notification_message = '', $notification_class = 'update-nag', $count_in_menu = true, $count_in_submenu = array() ) {

	$notifications = PMS_Plugin_Notifications::get_instance();
	$notifications->add_notification( $notification_id, $notification_message, $notification_class, $count_in_menu, $count_in_submenu );

}


function pms_add_plugin_notification_new_add_on() {

	$notification_id = 'pms_new_free_version_features';
	$message = '<img style="float: left; margin: 20px 12px 10px 0; max-width: 80px;" src="' . PMS_PLUGIN_DIR_URL . 'assets/images/pms_logo.png" />';
	$message .= '<p style="margin-top: 16px;">' . wp_kses_post( __( '<strong>New free version features!</strong><br><br><strong>Recurring Payments for PayPal Standard</strong> is now available in the free version. Go to <strong>PMS -> Settings -> Payments</strong> to control the default settings.<br> <strong>Discount Codes</strong> are now available in the free version. Go to <strong>PMS -> Discount Codes</strong> to create one.', 'paid-member-subscriptions' ) ) . '</p>';
	//$message .= '<p><a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/add-ons/stripe-payment-gateway/#Update_Credit_Card_Details?utm_source=wp-backend&utm_medium=addon-notification&utm_campaign=PMSFree" class="button-primary">' . esc_html__( 'Learn More', 'paid-member-subscriptions' ) . '</a></p>';
	$message .= '<a href="' . esc_url( add_query_arg( array( 'pms_dismiss_admin_notification' => $notification_id ) ) ) . '#pms-addons-title" type="button" class="notice-dismiss"><span class="screen-reader-text">' . esc_html__( 'Dismiss this notice.', 'paid-member-subscriptions' ) . '</span></a>';

	pms_add_plugin_notification( $notification_id, $message, 'pms-notice pms-narrow notice notice-info', true, array( 'pms-addons-page' ) );

}
add_action( 'admin_init', 'pms_add_plugin_notification_new_add_on' );

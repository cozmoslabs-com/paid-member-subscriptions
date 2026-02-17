<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 *
 *
 */
function pms_dismiss_admin_notifications() {

	if( ! empty( $_GET['pms_dismiss_admin_notification'] ) ) {

		if( empty( $_GET['_wpnonce'] ) || !wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'pms_plugin_notice_dismiss' ) )
			return;

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
function pms_add_plugin_notification( $notification_id = '', $notification_message = '', $notification_class = 'update-nag', $count_in_menu = true, $count_in_submenu = array(), $show_in_all_backend = false ) {

	$notifications = PMS_Plugin_Notifications::get_instance();
	$notifications->add_notification( $notification_id, $notification_message, $notification_class, $count_in_menu, $count_in_submenu, $show_in_all_backend );

}


function pms_add_plugin_notification_new_add_on() {

	// if( pms_get_active_stripe_gateway() == false ){
	// 	$notification_id = 'pms_free_stripe_connect';
	// 	$message = '<img style="float: right; margin: 20px 12px 10px 0; max-width: 80px;" src="' . PMS_PLUGIN_DIR_URL . 'assets/images/pms-stripe.png" />';
	// 	$message .= '<p style="margin-top: 16px;">' . wp_kses_post( __( '<strong>New payment gateway!</strong><br><br><strong>Stripe</strong> payment gateway is now available in the free version. <br>Your users can pay using credit and debit cards without leaving your website and you can also offer them additional payment methods like Bancontact, iDeal, Giropay and more. <br><br>Get started now by going to <strong>Paid Member Subscriptions -> Settings -> Payments</strong>!', 'paid-member-subscriptions' ) ) . '</p>';
	// 	$message .= '<p><a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/payment-gateways/stripe-connect/?utm_source=wp-backend&utm_medium=addon-notification&utm_campaign=PMSFree" class="button-primary" target="_blank">' . esc_html__( 'Learn More', 'paid-member-subscriptions' ) . '</a></p>';
	// 	$message .= '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms_dismiss_admin_notification' => $notification_id ) ), 'pms_plugin_notice_dismiss' ) ) . '#pms-addons-title" type="button" class="notice-dismiss"><span class="screen-reader-text">' . esc_html__( 'Dismiss this notice.', 'paid-member-subscriptions' ) . '</span></a>';

	// 	pms_add_plugin_notification( $notification_id, $message, 'pms-notice pms-narrow notice notice-info', true, array( 'pms-settings-page' ) );
	// }

	// $notification_id = 'pms_addon_release_files_restriction';
	// $message = '<img style="float: left; margin: 20px 12px 10px 0; max-width: 80px;" src="' . PMS_PLUGIN_DIR_URL . 'assets/images/addons/pms-add-on-pro-files-restriction-logo.png" />';
	// $message .= '<p style="margin-top: 16px;">' . wp_kses_post( '<strong>Files Restriction add-on!</strong><br><br>Lock down direct access to files and allow only paying members to access them using the new Files Restriction add-on. <br>The add-on is available with an <strong>Agency</strong> or <strong>Pro</strong> license.<br> Don\'t have a license? <a href="https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=wpbackend&utm_medium=plugin-notification-files&utm_campaign=PMSFree#pricing" target="_blank">Buy now!</a>' ) . '</p>';
	// $message .= '<p><a href="'. admin_url( 'admin.php?page=pms-addons-page' ) .'" class="button-primary" target="_blank">' . esc_html__( 'Add-ons Page', 'paid-member-subscriptions' ) . '</a><a href="https://www.cozmoslabs.com/add-ons/paid-member-subscriptions-files-restriction/?utm_source=wpbackend&utm_medium=clientsite&utm_content=plugin-notification-files&utm_campaign=PMSFree" class="button-secondary" target="_blank">' . esc_html__( 'Learn More', 'paid-member-subscriptions' ) . '</a></p>';
	// $message .= '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms_dismiss_admin_notification' => $notification_id ) ), 'pms_plugin_notice_dismiss' ) ) . '#pms-addons-title" type="button" class="notice-dismiss"><span class="screen-reader-text">' . esc_html__( 'Dismiss this notice.', 'paid-member-subscriptions' ) . '</span></a>';

	// pms_add_plugin_notification( $notification_id, $message, 'pms-notice pms-narrow notice notice-info', true, array( 'pms-settings-page' ) );

	// $notification_id = 'pms_addon_release_multiple_currencies';
	// $message = '<img style="float: left; margin: 20px 12px 10px 0; max-width: 80px;" src="' . PMS_PLUGIN_DIR_URL . 'assets/images/addons/pms-add-on-multiple-currencies-logo.png" />';
	// $message .= '<p style=""><strong>Multiple Currencies add-on now available!</strong>Enable visitors to pay in their local currency, either through automatic location detection or by manually selecting their preferred currency. <br>The add-on is available with an <strong>Agency</strong> or <strong>Pro</strong> license. Don\'t have a license? <strong><a href="https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=wpbackend&utm_medium=plugin-notification-multiple-currencies&utm_campaign=PMSFree#pricing" target="_blank">Buy now!</a></strong></p>';
	// $message .= '<p><a href="'. admin_url( 'admin.php?page=pms-addons-page' ) .'" class="button-primary" target="_blank">' . esc_html__( 'Add-ons Page', 'paid-member-subscriptions' ) . '</a><a href="https://www.cozmoslabs.com/add-ons/multiple-currencies/?utm_source=wpbackend&utm_medium=clientsite&utm_content=plugin-notification-files&utm_campaign=PMSFree" class="button-secondary" target="_blank">' . esc_html__( 'Learn More', 'paid-member-subscriptions' ) . '</a></p>';
	// $message .= '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms_dismiss_admin_notification' => $notification_id ) ), 'pms_plugin_notice_dismiss' ) ) . '#pms-addons-title" type="button" class="notice-dismiss"><span class="screen-reader-text">' . esc_html__( 'Dismiss this notice.', 'paid-member-subscriptions' ) . '</span></a>';
	
	// $notification_id = 'pms_feature_installment_payments';
	// $message = '<img style="float: left; margin: 20px 12px 10px 0; max-width: 60px; width: 100%;" src="' . PMS_PLUGIN_DIR_URL . 'assets/images/pms-logo.svg" />';
	// $message .= sprintf( '<p style=""><strong>New Feature: Pay in Installments</strong><br>Make your memberships more accessible and flexible by offering installment payments — perfect for increasing conversions and catering to more customers. <br>Go to the <a href="%s">Subscription Plans -> Add New</a> page to create your first installment plan.</p>', admin_url( 'post-new.php?post_type=pms-subscription' ) );
	// $message .= '<p><a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/subscription-plans/?utm_source=wpbackend&utm_medium=clientsite&utm_content=plugin-notification-files&utm_campaign=PMSFree#Limit_Payment_Cycles" class="button-primary" target="_blank">' . esc_html__( 'Learn More', 'paid-member-subscriptions' ) . '</a></p>';
	// $message .= '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms_dismiss_admin_notification' => $notification_id ) ), 'pms_plugin_notice_dismiss' ) ) . '#pms-addons-title" type="button" class="notice-dismiss"><span class="screen-reader-text">' . esc_html__( 'Dismiss this notice.', 'paid-member-subscriptions' ) . '</span></a>';

	// pms_add_plugin_notification( $notification_id, $message, 'pms-notice pms-narrow notice notice-info', true, array( 'pms-settings-page' ) );

	// $notification_id = 'pms_addon_advanced_subscription_toolkit';
	// $message = '<img style="float: left; margin: 20px 12px 10px 0; max-width: 60px; width: 100%;" src="' . PMS_PLUGIN_DIR_URL . 'assets/images/pms-logo.svg" />';
	// $message .= sprintf( '<p style=""><strong>New Addon: Advanced Subscription Toolkit</strong><br>Make your memberships more accessible and flexible by offering installment payments — perfect for increasing conversions and catering to more customers. <br>Go to the <a href="%s">Subscription Plans -> Add New</a> page to create your first installment plan.</p>', admin_url( 'post-new.php?post_type=pms-subscription' ) );
	// $message .= '<p><a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/add-ons/advanced-subscription-toolkit/?utm_source=pms-plugin-notification&utm_medium=client-site&utm_campaign=pms-advanced-subscription-toolkit-addon" class="button-primary" target="_blank">' . esc_html__( 'Learn More', 'paid-member-subscriptions' ) . '</a></p>';
	// $message .= '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms_dismiss_admin_notification' => $notification_id ) ), 'pms_plugin_notice_dismiss' ) ) . '#pms-addons-title" type="button" class="notice-dismiss"><span class="screen-reader-text">' . esc_html__( 'Dismiss this notice.', 'paid-member-subscriptions' ) . '</span></a>';

    // $notification_id = 'pms_addon_advanced_subscription_toolkit';
	// $message = '<img style="float: left; margin: 20px 12px 10px 0; max-width: 80px;" src="' . PMS_PLUGIN_DIR_URL . 'assets/images/addons/pms-add-on-extra-subscription-and-discount-options-logo.png" />';
	// $message .= '<p style="margin-top: 16px;">' . wp_kses_post( '<strong>New Addon: Advanced Subscription Toolkit</strong><br><br>Extend your Subscription Plans and Discount Codes with extra options like automatic downgrade after expiration, limit member count, schedule availability for subscription plans and more.<br><em>The add-on is available with a <strong>Basic</strong>, <strong>Agency</strong> or <strong>Pro</strong> license. Don\'t have a license? <a href="https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=pms-plugin-notification&utm_medium=client-site&utm_campaign=pms-advanced-subscription-toolkit-addon#pricing" target="_blank">Buy now!</a></em>' ) . '</p>';
	// $message .= '<p><a href="'. admin_url( 'admin.php?page=pms-addons-page' ) .'" class="button-primary">' . esc_html__( 'Add-ons Page', 'paid-member-subscriptions' ) . '</a><a href="https://www.cozmoslabs.com/add-ons/advanced-subscription-toolkit/?utm_source=pms-plugin-notification&utm_medium=client-site&utm_campaign=pms-advanced-subscription-toolkit-addon" class="button-secondary" target="_blank">' . esc_html__( 'Learn More', 'paid-member-subscriptions' ) . '</a></p>';
	// $message .= '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms_dismiss_admin_notification' => $notification_id ) ), 'pms_plugin_notice_dismiss' ) ) . '#pms-addons-title" type="button" class="notice-dismiss"><span class="screen-reader-text">' . esc_html__( 'Dismiss this notice.', 'paid-member-subscriptions' ) . '</span></a>';

	// pms_add_plugin_notification( $notification_id, $message, 'pms-notice pms-narrow notice notice-info', true, array( 'pms-settings-page' ) );


    // Email Marketing Add-ons notice
    // $notification_id = 'pms_addon_email_marketing';
    // $message = '<img style="float: left; margin: 20px 12px 10px 0; max-width: 80px;" src="' . PMS_PLUGIN_DIR_URL . 'assets/images/addons/pms-add-on-email-marketing-logo.png" />';
    // $message .= '<p style="margin-top: 16px;">' . wp_kses_post( '<strong>New add-ons: Mailchimp & Brevo</strong><br><br>Connect your membership site with leading email marketing platforms like <strong>Mailchimp</strong> and <strong>Brevo</strong>. <br> Automatically subscribe and update members, enable Double Opt-In for compliance, and sync custom fields to keep your contact lists accurate and up to date.<br><br><em>The add-ons are available with an <strong>Agency</strong> or <strong>Pro</strong> license. Don\'t have a license? <a href="https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=pms-plugin-notification&utm_medium=client-site&utm_campaign=pms-email-marketing-addon#pricing" target="_blank">Buy now!</a></em>' ) . '</p>';
    // $message .= '<p><a href="'. admin_url( 'admin.php?page=pms-addons-page' ) .'" class="button-primary">' . esc_html__( 'Add-ons Page', 'paid-member-subscriptions' ) . '</a><a href="https://www.cozmoslabs.com/add-ons/mailchimp-paid-member-subscriptions/?utm_source=pms-plugin-notification&utm_medium=client-site&utm_campaign=pms-mailchimp-addon" class="button-secondary" style="margin-right: 12px;" target="_blank">' . esc_html__( 'Mailchimp Details', 'paid-member-subscriptions' ) . '</a><a href="https://www.cozmoslabs.com/add-ons/brevo/?utm_source=pms-plugin-notification&utm_medium=client-site&utm_campaign=pms-brevo-addon" class="button-secondary" target="_blank">' . esc_html__( 'Brevo Details', 'paid-member-subscriptions' ) . '</a></p>';
    // $message .= '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms_dismiss_admin_notification' => $notification_id ) ), 'pms_plugin_notice_dismiss' ) ) . '#pms-addons-title" type="button" class="notice-dismiss"><span class="screen-reader-text">' . esc_html__( 'Dismiss this notice.', 'paid-member-subscriptions' ) . '</span></a>';

    // pms_add_plugin_notification( $notification_id, $message, 'pms-notice pms-narrow notice notice-info', true, array( 'pms-settings-page' ) );

    // Gift Subscriptions Add-ons notice
    // $notification_id = 'pms_addon_gift_subscriptions';
    // $message = '<img style="float: left; margin: 20px 12px 10px 0; max-width: 80px;" src="' . PMS_PLUGIN_DIR_URL . 'assets/images/addons/pms-add-on-gift-subscriptions-logo.png" />';
    // $message .= '<p style="margin-top: 16px;">' . wp_kses_post( '<strong>New add-on: Gift Subscriptions</strong><br><br>This holiday season, allow your members to purchase Gift Subscriptions for their friends and family. <br> The add-on is available with an <strong>Agency</strong> or <strong>Pro</strong> license. Don\'t have a license? <a href="https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=pms-plugin-notification&utm_medium=client-site&utm_campaign=pms-gift-subscriptions-addon#pricing" target="_blank">Buy now!</a></em>' ) . '</p>';
    // $message .= '<p><a href="'. admin_url( 'admin.php?page=pms-addons-page' ) .'" class="button-primary">' . esc_html__( 'Add-ons Page', 'paid-member-subscriptions' ) . '</a><a href="https://www.cozmoslabs.com/add-ons/gift-subscriptions/?utm_source=pms-plugin-notification&utm_medium=client-site&utm_campaign=pms-gift-subscriptions-addon" class="button-secondary" target="_blank">' . esc_html__( 'Learn More', 'paid-member-subscriptions' ) . '</a></p>';
    // $message .= '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms_dismiss_admin_notification' => $notification_id ) ), 'pms_plugin_notice_dismiss' ) ) . '#pms-addons-title" type="button" class="notice-dismiss"><span class="screen-reader-text">' . esc_html__( 'Dismiss this notice.', 'paid-member-subscriptions' ) . '</span></a>';

    // pms_add_plugin_notification( $notification_id, $message, 'pms-notice pms-narrow notice notice-info', true, array( 'pms-addons-page' ) );

    // Pause Subscriptions Add-on notice
    $notification_id = 'pms_addon_pause_subscriptions';
    $message = '<img style="float: left; margin: 20px 12px 10px 0; max-width: 80px;" src="' . PMS_PLUGIN_DIR_URL . 'assets/images/addons/pms-add-on-pause-subscriptions-logo.png" />';
    $message .= '<p style="margin-top: 16px;">' . wp_kses_post( '<strong>New add-on: Pause Subscriptions</strong><br>Members can now pause and resume their subscriptions with ease — helping reduce cancellations and improve retention. <br> The add-on is available with an <strong>Agency</strong> or <strong>Pro</strong> license. Don\'t have a license? <a href="https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=pms-plugin-notification&utm_medium=client-site&utm_campaign=pms-pause-subscriptions-addon#pricing" target="_blank">Buy now!</a></em>' ) . '</p>';
    $message .= '<p><a href="'. admin_url( 'admin.php?page=pms-addons-page' ) .'" class="button-primary">' . esc_html__( 'Add-ons Page', 'paid-member-subscriptions' ) . '</a><a href="https://www.cozmoslabs.com/add-ons/pause-subscriptions/?utm_source=pms-plugin-notification&utm_medium=client-site&utm_campaign=pms-pause-subscriptions-addon" class="button-secondary" target="_blank">' . esc_html__( 'Learn More', 'paid-member-subscriptions' ) . '</a></p>';
    $message .= '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms_dismiss_admin_notification' => $notification_id ) ), 'pms_plugin_notice_dismiss' ) ) . '#pms-addons-title" type="button" class="notice-dismiss"><span class="screen-reader-text">' . esc_html__( 'Dismiss this notice.', 'paid-member-subscriptions' ) . '</span></a>';

    pms_add_plugin_notification( $notification_id, $message, 'pms-notice pms-narrow notice notice-info', true, array( 'pms-addons-page' ) );
    
	/**
	 * LearnDash integration notices
	 */
	// if( is_plugin_active( 'sfwd-lms/sfwd_lms.php' ) ){
	// 	// paid version
	// 	if( defined( 'PMS_PAID_PLUGIN_DIR' ) ){

	// 		$notification_id = 'pms_learndash_addon2';
	// 		$message = '<img style="float: left; margin: 20px 12px 10px 0; max-width: 100px;" src="' . PMS_PLUGIN_DIR_URL . 'assets/images/addons/pms-add-on-learndash.png" />';
	// 		$message .= '<p style="margin-top: 16px;">' . wp_kses_post( '<strong>LearnDash Integration</strong> for <strong>Paid Member Subscriptions</strong> is now available!<br><br>Sell access to courses, create beautiful front-end register, login and reset password forms and restrict access to your Courses, Lessons and Quizzes.<br>Activate from the <a href="'.admin_url( 'admin.php?page=pms-addons-page' ).'">add-ons</a> page.' ) . '</p>';
	// 		$message .= '<p><a href="https://www.cozmoslabs.com/add-ons/learndash/?utm_source=wpbackend&utm_medium=addon-notification&utm_campaign=PMSPaid" class="button-primary" target="_blank">' . esc_html__( 'Learn More', 'paid-member-subscriptions' ) . '</a></p>';
	// 		$message .= '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms_dismiss_admin_notification' => $notification_id ) ), 'pms_plugin_notice_dismiss' ) ) . '" type="button" class="notice-dismiss"><span class="screen-reader-text" target="_blank">' . esc_html__( 'Dismiss this notice.', 'paid-member-subscriptions' ) . '</span></a>';

	// 		pms_add_plugin_notification( $notification_id, $message, 'pms-notice pms-narrow notice notice-success', true, array( 'pms-addons-page' ) );

	// 	// free version
	// 	} else {

	// 		$notification_id = 'pms_learndash_addon2';
	// 		$message = '<img style="float: left; margin: 20px 12px 10px 0; max-width: 100px;" src="' . PMS_PLUGIN_DIR_URL . 'assets/images/addons/pms-add-on-learndash.png" />';
	// 		$message .= '<p style="margin-top: 16px;">' . wp_kses_post( '<strong>LearnDash Integration</strong> for <strong>Paid Member Subscriptions</strong> is now available!<br><br>Sell access to courses, create beautiful front-end register, login and reset password forms and restrict access to your Courses, Lessons and Quizzes.<br><strong>Buy a license to continue</strong>.' ) . '</p>';
	// 		$message .= '<p><a style="min-width: auto !important;height:30px; margin-right: 12px;" href="https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=wpbackend&utm_medium=addon-notification&utm_campaign=PMSFree#pricing" class="button-primary" target="_blank">' . esc_html__( 'Buy now', 'paid-member-subscriptions' ) . '</a>';
	// 		$message .= '<a href="https://www.cozmoslabs.com/add-ons/learndash/?utm_source=wpbackend&utm_medium=addon-notification&utm_campaign=PMSFree" class="button button-secondary"  target="_blank">' . esc_html__( 'Learn More', 'paid-member-subscriptions' ) . '</a></p>';
	// 		$message .= '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms_dismiss_admin_notification' => $notification_id ) ), 'pms_plugin_notice_dismiss' ) ) . '" type="button" class="notice-dismiss"><span class="screen-reader-text">' . esc_html__( 'Dismiss this notice.', 'paid-member-subscriptions' ) . '</span></a>';

	// 		pms_add_plugin_notification( $notification_id, $message, 'pms-notice pms-narrow notice notice-success', true, array( 'pms-addons-page' ) );

	// 	}
	// }

}
add_action( 'admin_init', 'pms_add_plugin_notification_new_add_on' );

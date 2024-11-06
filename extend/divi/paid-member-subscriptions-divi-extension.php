<?php
/*
Plugin Name: Paid Member Subscriptions Divi Extension
Plugin URI:  https://wordpress.org/plugins/paid-member-subscriptions/
Description: Paid Member Subscriptions is the #1 WordPress membership plugin focused on growing recurring revenue.
Version:     1.0.0
Author:      Cozmoslabs
Author URI:  https://www.cozmoslabs.com/
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: pms-paid-member-subscriptions-divi-extension
Domain Path: /languages

Paid Member Subscriptions Divi Extension is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

Paid Member Subscriptions Divi Extension is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Paid Member Subscriptions Divi Extension. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/


if ( ! function_exists( 'pms_initialize_extension' ) ):
/**
 * Creates the extension's main class instance.
 *
 * @since 1.0.0
 */
function pms_initialize_extension() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/PaidMemberSubscriptionsDiviExtension.php';
}
add_action( 'divi_extensions_init', 'pms_initialize_extension' );

add_action( 'wp_ajax_pms_divi_extension_ajax', 'pms_divi_extension_ajax' );
function pms_divi_extension_ajax(){
	check_ajax_referer( 'pms_divi_render_form', 'pms_nonce' );

	if( !current_user_can( 'manage_options' ) )
		die();
		
	if ( is_array( $_POST ) && array_key_exists( 'form_type', $_POST ) && $_POST['form_type'] !== '' ) {
		switch ($_POST['form_type']) {
			case 'rf':

				if ( array_key_exists( 'toggle_show', $_POST ) && $_POST['toggle_show'] === 'on' ) {

					$atts = [
						'selected_plan'         => array_key_exists( 'selected_plan', $_POST )         && $_POST['selected_plan']         !== 'default' ? 'selected="'. absint( $_POST['selected_plan'] ) .'" ' : '',
						'toggle_plans_position' => array_key_exists( 'toggle_plans_position', $_POST ) && $_POST['toggle_plans_position'] === 'on'      ? 'plans_position="top" '                               : '',
						'subscription_plans'    => '',
					];

					if ( array_key_exists('toggle_include', $_POST) && $_POST['toggle_include'] === 'on' &&
					     array_key_exists('toggle_exclude', $_POST) && $_POST['toggle_exclude'] === 'off' &&
					     array_key_exists('include_plans', $_POST) && $_POST['include_plans'] !== 'undefined' ){

						$subscription_plans = pms_divi_parse_subscription_plans( $_POST['include_plans'] );

						$atts['subscription_plans'] = 'subscription_plans="' . $subscription_plans . '" ';

					} elseif ( array_key_exists('toggle_exclude', $_POST) && $_POST['toggle_exclude'] === 'on' &&
					           array_key_exists('toggle_include', $_POST) && $_POST['toggle_include'] === 'off' &&
					           array_key_exists('exclude_plans', $_POST) && $_POST['exclude_plans'] !== 'undefined' ){

						$subscription_plans = pms_divi_parse_subscription_plans( $_POST['exclude_plans'] );

						$atts['subscription_plans'] = 'exclude="' . $subscription_plans . '" ';

					}

					$output =
						'<div class="pms-divi-editor-container">' .
						do_shortcode( '[pms-register '. $atts['subscription_plans'] . $atts['toggle_plans_position'] . $atts['selected_plan'] .' block="true"]') .
						'</div>';
				} else {
					$output =
						'<div class="pms-divi-editor-container">' .
						do_shortcode( '[pms-register subscription_plans="none" block="true"]') .
						'</div>';
				}

				break;

			case 'af':
				$atts = [
					'hide_tabs'    => array_key_exists('hide_tabs', $_POST)    && $_POST['hide_tabs']    === 'on'      ? 'show_tabs="no" '                 : '',
					'redirect_url' => array_key_exists('redirect_url', $_POST) && $_POST['redirect_url'] !== 'default' ? pms_divi_parse_redirect_url( $_POST['redirect_url'] )  : '',

				];

				$output =
					'<div class="pms-divi-editor-container">' .
					do_shortcode( '[pms-account '. $atts['hide_tabs'] .'logout_redirect_url='. $atts['redirect_url'] .']') .
					'</div>';

				break;

			case 'l':
				$atts = [
					'redirect_url'        => array_key_exists('redirect_url', $_POST)         && $_POST['redirect_url']        !== 'default' ? pms_divi_parse_redirect_url( $_POST['redirect_url'] )        : '',
					'logout_redirect_url' => array_key_exists('logout_redirect_url', $_POST)  && $_POST['logout_redirect_url'] !== 'default' ? pms_divi_parse_redirect_url( $_POST['logout_redirect_url'] ) : '',
					'register_url'        => array_key_exists('register_url', $_POST)         && $_POST['register_url']        !== 'default' ? pms_divi_parse_redirect_url( $_POST['register_url'] )        : '',
					'lostpassword_url'    => array_key_exists('lostpassword_url', $_POST)     && $_POST['lostpassword_url']    !== 'default' ? pms_divi_parse_redirect_url( $_POST['lostpassword_url'] )    : '',
				];

				$output =
					'<div class="pms-divi-editor-container">' .
					do_shortcode( '[pms-login redirect_url="'. $atts['redirect_url'] .'" logout_redirect_url="'. $atts['logout_redirect_url'] .'" register_url ="'. $atts['register_url'] .'" lostpassword_url ="'. $atts['lostpassword_url'] .'" block="true"]') .
					'</div>';

				break;

			case 'rp':
				$atts = [
					'redirect_url' => array_key_exists('redirect_url', $_POST) && $_POST['redirect_url'] !== 'default' ? pms_divi_parse_redirect_url( $_POST['redirect_url'] ) : '',
				];

				$output =
					'<div class="pms-divi-editor-container">' .
					do_shortcode( '[pms-recover-password redirect_url='. $atts['redirect_url'] .'" block="true"]') .
					'</div>';

				break;
		}

		$output .=
			'<style type="text/css">' .
			file_get_contents( PMS_PLUGIN_DIR_PATH . 'assets/css/style-front-end.css' ) .
			'</style>';

		// Load stylesheet for the Default Form Style if the active WP Theme is a Block Theme (Block Themes were introduced in WordPress since the 5.9 release)
		if ( version_compare( get_bloginfo( 'version' ), '5.9', '>=' ) && function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
			$active_design = function_exists( 'pms_get_active_form_design' ) ? pms_get_active_form_design() : 'form-style-default';

			// Load stylesheet only if the active Form Design is the Default Style
			if ( $active_design === 'form-style-default' && file_exists( PMS_PLUGIN_DIR_PATH . 'assets/css/style-block-themes-front-end.css' ) )
				$output .=
					'<style type="text/css">' .
					file_get_contents( PMS_PLUGIN_DIR_PATH . 'assets/css/style-block-themes-front-end.css' ) .
					'</style>';
		}

		$output .=
			'<style type="text/css">' .
			file_get_contents( PMS_PLUGIN_DIR_PATH . 'extend/gutenberg-blocks/assets/css/gutenberg-blocks.css' ) .
			'</style>';


		//Group Memberships
		if ( defined( 'PMS_IN_GM_PLUGIN_DIR_PATH' ) ) {
			$output .=
				'<script type="text/javascript">' .
				file_get_contents( PMS_IN_GM_PLUGIN_DIR_PATH . 'assets/js/front-end.js' ) .
				'</script>';
			$output .=
				'<style type="text/css">' .
				file_get_contents( PMS_IN_GM_PLUGIN_DIR_PATH . 'assets/css/style-front-end.css' ) .
				'</style>';
		}

		//Discount Codes
		if ( defined( 'PMS_IN_DC_PLUGIN_DIR_PATH' ) ) {
			$output .=
				'<script type="text/javascript">' .
				file_get_contents( PMS_IN_DC_PLUGIN_DIR_PATH . 'assets/js/frontend-discount-code.js' ) .
				'</script>';
			$output .=
				'<style type="text/css">' .
				file_get_contents( PMS_IN_DC_PLUGIN_DIR_PATH . 'assets/css/style-front-end.css' ) .
				'</style>';
		}

		//Pay What You Want
		if ( defined( 'PMS_IN_PWYW_PLUGIN_DIR_PATH' ) ) {
			$output .=
				'<script type="text/javascript">' .
				file_get_contents( PMS_IN_PWYW_PLUGIN_DIR_PATH . 'assets/js/front-end.js' ) .
				'</script>';
		}

		//Invoices
		if ( defined( 'PMS_IN_INV_PLUGIN_DIR_PATH' ) ) {
			$output .=
				'<style type="text/css">' .
				file_get_contents( PMS_IN_INV_PLUGIN_DIR_PATH . 'assets/css/front-end.css' ) .
				'</style>';
		}

		//Tax
		if ( defined( 'PMS_IN_TAX_PLUGIN_DIR_PATH' ) ) {
			$output .=
				'<style type="text/css">' .
				file_get_contents( PMS_IN_TAX_PLUGIN_DIR_PATH . 'assets/css/front-end.css' ) .
				'</style>';
		}

		echo json_encode( $output );// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		die();
	}

	die();
}

add_filter( 'et_builder_get_parent_modules', 'pms_divi_content_restriction_extend_modules' );
add_filter( 'et_module_shortcode_output', 'pms_divi_content_restriction_render_section', 10, 3 );

/**
 * Add the content restriction toggle and fields on all modules
 */
function pms_divi_content_restriction_extend_modules( $modules ) {

	static $is_applied = false;
	if ( $is_applied ) {
		return $modules;
	}

	if ( empty( $modules ) ) {
		return $modules;
	}

	foreach ( $modules as $module_slug => $module ) {
		if ( ! isset( $module->settings_modal_toggles ) ||
		     ! isset( $module->fields_unprocessed ) ||
		     in_array( $module_slug, array( 'pms_content_restriction_start', 'pms_content_restriction_end' ) ) ) {
			continue;
		}

		$toggles_list = $module->settings_modal_toggles;
		// Add a 'PMS Content Restriction' toggle on the 'Advanced' tab
		if ( isset( $toggles_list['custom_css'] ) && ! empty( $toggles_list['custom_css']['toggles'] ) ) {
			$toggles_list['custom_css']['toggles']['pms_content_restriction_toggle'] = array(
				'title'    => esc_html__( 'PMS Content Restriction', 'paid-member-subscriptions' ),
				'priority' => 220,
			);
			$module->settings_modal_toggles = $toggles_list;
		}

		$fields_list = $module->fields_unprocessed;
		// Add content restriction options in the toggle
		if ( ! empty( $fields_list ) ) {
			$module->fields_unprocessed = pms_divi_content_restriction_get_fields_list ( $fields_list );
		}
	}
	$is_applied = true;
	return $modules;
}

function pms_divi_content_restriction_render_section( $output, $render_slug, $module ) {

	if ( is_array( $output ) ) {
		return $output;
	}

	if ('et_pb_column' === $render_slug) {
		return $output;
	}

	static $show_message = false;

	if ( !isset( $content_restriction_module_pair_active ) ) {
		static $content_restriction_module_pair_active = false;
	}
	if ( !isset( $content_restriction_module_pair_settings ) ) {
		static $content_restriction_module_pair_settings = array();
	}

	if ( $render_slug === 'pms_content_restriction_start' ) {
		$content_restriction_module_pair_active = true;
		$content_restriction_module_pair_settings = $module->get_attrs_unprocessed();
		return;
	}
	if ( $render_slug === 'pms_content_restriction_end' ) {
		if ( $content_restriction_module_pair_active ) {
			$content_restriction_module_pair_active = false;
			$aux = $content_restriction_module_pair_settings;
			$content_restriction_module_pair_settings = array();
			return pms_divi_content_restriction_process_shortcode( pms_divi_content_restriction_get_attrs( $aux ), '', isset( $aux['pms_toggle_message'] ) && $aux['pms_toggle_message'] === 'on' );
		} else {
			$content_restriction_module_pair_active = false;
			$content_restriction_module_pair_settings = array();
			return;
		}
	}

	if ( $content_restriction_module_pair_active ) {
		return pms_divi_content_restriction_process_shortcode( pms_divi_content_restriction_get_attrs( $content_restriction_module_pair_settings ), $output, false );
	}

	$attrs_unprocessed = $module->get_attrs_unprocessed();

	if ( isset( $attrs_unprocessed['pms_display_to'] ) && $attrs_unprocessed['pms_display_to'] !== 'all' ) {
		return pms_divi_content_restriction_process_shortcode( pms_divi_content_restriction_get_attrs( $attrs_unprocessed ), $output, isset( $attrs_unprocessed['pms_toggle_message'] ) && $attrs_unprocessed['pms_toggle_message'] === 'on' );
	}

	return $output;
}

function pms_divi_content_restriction_process_shortcode ( $attrs, $output, $show_message = true ) {

//	if ( file_exists( PMS_PLUGIN_DIR_PATH . 'includes/class-shortcodes.php' ) )
//		include_once( PMS_PLUGIN_DIR_PATH . 'includes/class-shortcodes.php' );

	if ( $show_message ){
		$output = PMS_Shortcodes::restrict_content( $attrs, $output );
	} else {
		add_filter( 'pms_restrict_content_message', 'pms_divi_content_restriction_filter_no_message');
		$output = PMS_Shortcodes::restrict_content( $attrs, $output );
		remove_filter( 'pms_restrict_content_message', 'pms_divi_content_restriction_filter_no_message');
	}

	return $output;
}

function pms_divi_content_restriction_filter_no_message () {
	return;
}

function pms_divi_content_restriction_get_attrs ( $attrs_unprocessed ) {

	$attrs_unprocessed['pms_display_to']            = isset( $attrs_unprocessed['pms_display_to'] ) ? $attrs_unprocessed['pms_display_to'] : '';
	$attrs_unprocessed['pms_subscriptions']         = isset( $attrs_unprocessed['pms_subscriptions'] ) ? $attrs_unprocessed['pms_subscriptions'] : '';
	$attrs_unprocessed['pms_toggle_not_subscribed'] = isset( $attrs_unprocessed['pms_toggle_not_subscribed'] ) ? $attrs_unprocessed['pms_toggle_not_subscribed'] : '';
	$attrs_unprocessed['pms_toggle_custom_message'] = isset( $attrs_unprocessed['pms_toggle_custom_message'] ) ? $attrs_unprocessed['pms_toggle_custom_message'] : '';
	$attrs_unprocessed['pms_message_logged_in']     = isset( $attrs_unprocessed['pms_message_logged_in'] ) ? $attrs_unprocessed['pms_message_logged_in'] : '';
	$attrs_unprocessed['pms_message_logged_out']    = isset( $attrs_unprocessed['pms_message_logged_out'] ) ? $attrs_unprocessed['pms_message_logged_out'] : '';


	return array(
		'subscription_plans' => $attrs_unprocessed['pms_subscriptions'],
		'display_to'         => $attrs_unprocessed['pms_toggle_not_subscribed'] === 'on' ? 'not_subscribed' : $attrs_unprocessed['pms_display_to'],
		'message'            => $attrs_unprocessed['pms_toggle_custom_message'] === 'on'
			? ( $attrs_unprocessed['pms_display_to'] === 'not_logged_in' ? $attrs_unprocessed['pms_message_logged_out'] : $attrs_unprocessed['pms_message_logged_in'] )
			: '',
	);
}

function pms_divi_content_restriction_get_fields_list ( $fields_list = array() ) {
	$plans = array();

	$plan_ids = get_posts( array( 'post_type' => 'pms-subscription', 'meta_key' => 'pms_subscription_plan_status', 'meta_value' => 'active', 'numberposts' => -1, 'post_status' => 'any', 'fields' => 'ids' ) );

	if( !empty( $plan_ids ) ) {
		foreach ($plan_ids as $plan_id)
			$plans[$plan_id] = get_the_title($plan_id);
	}

	$fields_list['pms_display_to'] = array(
		'label'              => esc_html__( 'Show content to', 'paid-member-subscriptions' ),
		'description'        => esc_html__( 'The users you wish to see the content.', 'paid-member-subscriptions' ),
		'type'               => 'select',
		'options'            => array(
			'all'            => esc_html__( 'All', 'paid-member-subscriptions' ),
			'logged_in'      => esc_html__( 'Logged in', 'paid-member-subscriptions' ),
			'not_logged_in'  => esc_html__( 'Not logged in', 'paid-member-subscriptions' ),
		),
		'default'            => 'all',
		'toggle_slug'        => 'pms_content_restriction_toggle',
		'tab_slug'           => 'custom_css',
	);
	$fields_list['pms_subscriptions'] = array(
		'label'              => esc_html__( 'Required Subscriptions', 'paid-member-subscriptions' ),
		'description'        => esc_html__( 'The desired valid subscriptions. Select none to display the content to all logged in users.', 'paid-member-subscriptions' ),
		'type'               => 'pms_multiple_checkboxes_with_ids',
		'options'            => $plans,
		'toggle_slug'        => 'pms_content_restriction_toggle',
		'tab_slug'           => 'custom_css',
		'show_if'            => array(
			'pms_display_to'     => 'logged_in',
		),
	);
	$fields_list['pms_toggle_not_subscribed'] = array(
		'label'              => esc_html__( 'Show to Not Subscribed', 'paid-member-subscriptions' ),
		'description'        => esc_html__( 'Show the content only to users that do not have an active subscription.', 'paid-member-subscriptions' ),
		'type'               => 'yes_no_button',
		'options'            => array(
			'on'             => esc_html__( 'Yes', 'paid-member-subscriptions'),
			'off'            => esc_html__( 'No', 'paid-member-subscriptions'),
		),
		'toggle_slug'        => 'pms_content_restriction_toggle',
		'tab_slug'           => 'custom_css',
		'show_if_not'        => array(
			'pms_display_to'     => 'all',
		),
	);
	$fields_list['pms_toggle_message'] = array(
		'label'              => esc_html__( 'Enable Message', 'paid-member-subscriptions' ),
		'description'        => esc_html__( 'Show the Message defined in the Paid Member Subscriptions Settings.', 'paid-member-subscriptions' ),
		'type'               => 'yes_no_button',
		'options'            => array(
			'on'             => esc_html__( 'Yes', 'paid-member-subscriptions'),
			'off'            => esc_html__( 'No', 'paid-member-subscriptions'),
		),
		'toggle_slug'        => 'pms_content_restriction_toggle',
		'tab_slug'           => 'custom_css',
		'show_if_not'        => array(
			'pms_display_to'     => 'all',
		),
	);
	$fields_list['pms_toggle_custom_message'] = array(
		'label'              => esc_html__( 'Custom Message', 'paid-member-subscriptions' ),
		'description'        => esc_html__( 'Enable Custom Message.', 'paid-member-subscriptions' ),
		'type'               => 'yes_no_button',
		'options'            => array(
			'on'             => esc_html__( 'Yes', 'paid-member-subscriptions'),
			'off'            => esc_html__( 'No', 'paid-member-subscriptions'),
		),
		'toggle_slug'        => 'pms_content_restriction_toggle',
		'tab_slug'           => 'custom_css',
		'show_if_not'        => array(
			'pms_display_to'     => 'all',
		),
		'show_if'            => array(
			'pms_toggle_message' => 'on',
		),
	);
	$fields_list['pms_message_logged_in'] = array(
		'label'              => esc_html__( 'Custom message', 'paid-member-subscriptions' ),
		'description'        => esc_html__( 'Enter the custom message you wish the restricted users to see.', 'paid-member-subscriptions' ),
		'type'               => 'text',
		'toggle_slug'        => 'pms_content_restriction_toggle',
		'tab_slug'           => 'custom_css',
		'show_if'            => array(
			'pms_toggle_message'        => 'on',
			'pms_toggle_custom_message' => 'on',
			'pms_display_to'            => 'logged_in',
		),
	);
	$fields_list['pms_message_logged_out'] = array(
		'label'              => esc_html__( 'Custom message', 'paid-member-subscriptions' ),
		'description'        => esc_html__( 'Custom message for logged-out users.', 'paid-member-subscriptions' ),
		'type'               => 'text',
		'toggle_slug'        => 'pms_content_restriction_toggle',
		'tab_slug'           => 'custom_css',
		'show_if'            => array(
			'pms_toggle_message'        => 'on',
			'pms_toggle_custom_message' => 'on',
			'pms_display_to'            => 'not_logged_in',
		),
	);
	return $fields_list;
}

function pms_divi_parse_subscription_plans( $subscription_plans ){

	$subscription_plans = explode( ',', $subscription_plans );

	if( empty( $subscription_plans ) )
		return '';

	$result = '';

	foreach( $subscription_plans as $subscription_plan_id ){
		$result .= absint( $subscription_plan_id ) . ',';
	}

	return rtrim( $result, ',' );

}

function pms_divi_parse_redirect_url( $redirect_url ){

	if( $redirect_url === esc_url_raw( $redirect_url ) )
		return esc_url_raw( $redirect_url );

	return '';

}

endif;

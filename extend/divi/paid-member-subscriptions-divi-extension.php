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

add_action( 'wp_ajax_nopriv_pms_divi_extension_ajax', 'pms_divi_extension_ajax' );
add_action( 'wp_ajax_pms_divi_extension_ajax', 'pms_divi_extension_ajax' );
function pms_divi_extension_ajax(){

	if ( is_array( $_POST ) && array_key_exists( 'form_type', $_POST ) && $_POST['form_type'] !== '' ) {
		switch ($_POST['form_type']) {
			case 'rf':

				if ( array_key_exists('toggle_show', $_POST) && $_POST['toggle_show'] === 'on' ) {

					$atts = [
						'selected_plan'         => array_key_exists('selected_plan', $_POST)         && $_POST['selected_plan']         !== 'default' ? 'selected="'. esc_attr($_POST['selected_plan']) .'" ' : '',
						'toggle_plans_position' => array_key_exists('toggle_plans_position', $_POST) && $_POST['toggle_plans_position'] === 'on'      ? 'plans_position="top" '                               : '',
						'subscription_plans'    => '',
					];
					if ( array_key_exists('toggle_include', $_POST) && $_POST['toggle_include'] === 'on' &&
					     array_key_exists('toggle_exclude', $_POST) && $_POST['toggle_exclude'] === 'off' &&
					     array_key_exists('include_plans', $_POST) && $_POST['include_plans'] !== 'undefined' ){
						$atts[ 'subscription_plans' ] = 'subscription_plans="' . esc_attr($_POST['include_plans']) . '" ';
					} elseif ( array_key_exists('toggle_exclude', $_POST) && $_POST['toggle_exclude'] === 'on' &&
					           array_key_exists('toggle_include', $_POST) && $_POST['toggle_include'] === 'off' &&
					           array_key_exists('exclude_plans', $_POST) && $_POST['exclude_plans'] !== 'undefined' ){
						$atts[ 'subscription_plans' ] = 'exclude="' . esc_attr($_POST['exclude_plans']) . '" ';
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
					'redirect_url' => array_key_exists('redirect_url', $_POST) && $_POST['redirect_url'] !== 'default' ? esc_attr($_POST['redirect_url'])  : '',

				];

				$output =
					'<div class="pms-divi-editor-container">' .
					do_shortcode( '[pms-account '. $atts['hide_tabs'] .'logout_redirect_url='. $atts['redirect_url'] .']') .
					'</div>';

				break;

			case 'l':
				$atts = [
					'redirect_url'        => array_key_exists('redirect_url', $_POST)         && $_POST['redirect_url']        !== 'default' ? esc_attr($_POST['redirect_url'])        : '',
					'logout_redirect_url' => array_key_exists('logout_redirect_url', $_POST)  && $_POST['logout_redirect_url'] !== 'default' ? esc_attr($_POST['logout_redirect_url']) : '',
					'register_url'        => array_key_exists('register_url', $_POST)         && $_POST['register_url']        !== 'default' ? esc_attr($_POST['register_url'])        : '',
					'lostpassword_url'    => array_key_exists('lostpassword_url', $_POST)     && $_POST['lostpassword_url']    !== 'default' ? esc_attr($_POST['lostpassword_url'])    : '',
				];

				$output =
					'<div class="pms-divi-editor-container">' .
					do_shortcode( '[pms-login redirect_url="'. $atts['redirect_url'] .'" logout_redirect_url="'. $atts['logout_redirect_url'] .'" register_url ="'. $atts['register_url'] .'" lostpassword_url ="'. $atts['lostpassword_url'] .'" block="true"]') .
					'</div>';

				break;

			case 'rp':
				$atts = [
					'redirect_url' => array_key_exists('redirect_url', $_POST) && $_POST['redirect_url'] !== 'default' ? esc_attr($_POST['redirect_url']) : '',
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
				file_get_contents( PMS_IN_INV_PLUGIN_DIR_PATH . 'assets/css/style-front-end.css' ) .
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
		wp_die();
	}
}

endif;

<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

function pms_stripe_connect_apple_pay_rewrite_rule() {

	add_rewrite_rule( '^\.well-known\/apple-developer-merchantid-domain-association$', 'index.php?pms_stripe_apple_pay=true', 'top' );

}
add_action( 'init', 'pms_stripe_connect_apple_pay_rewrite_rule', 1 );

function pms_stripe_connect_apple_pay_add_query_vars( $qvars ) {

	$qvars[] = 'pms_stripe_apple_pay';
	return $qvars;

}
add_filter( 'query_vars', 'pms_stripe_connect_apple_pay_add_query_vars' );

function pms_stripe_connect_apple_pay_controller() {

	global $wp_filesystem;

	if ( empty( get_query_var( 'pms_stripe_apple_pay' ) ) )
		return;

	require_once ( ABSPATH . '/wp-admin/includes/file.php' );
	WP_Filesystem();

	echo $wp_filesystem->get_contents( PMS_PLUGIN_DIR_PATH . '/includes/gateways/stripe/apple-pay/apple-developer-merchantid-domain-association' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit;

}
add_action( 'template_redirect', 'pms_stripe_connect_apple_pay_controller' );

function pms_stripe_connect_apple_pay_redirect_canonical_filter( $redirect, $request ) {

	if ( ! empty( get_query_var( 'pms_stripe_apple_pay' ) ) )
		return false;

	return $redirect;
	
}
add_filter( 'redirect_canonical', 'pms_stripe_connect_apple_pay_redirect_canonical_filter', 10, 2 );
<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Content restriction for Kadence Pro Hooked Elements
 *
 * Kadence renders hooked elements through ktp_the_content (and ktp_code_the_content
 * for script elements), not the_content. The element post object is also not the
 * global $post while it renders on a host page, so PMS must stash the active
 * element and pass it into pms_filter_content explicitly.
 */

/**
 * Remember hooked elements that Kadence will display on this request
 *
 * @param bool     $display Whether the element should display
 * @param WP_Post  $post    The kadence_element post
 * @param array    $meta    Element display meta
 * @return bool
 */
function pms_kadence_track_active_element( $display, $post, $meta ) {

	if ( ! $display || empty( $post->ID ) || empty( $post->post_content ) )
		return $display;

	$GLOBALS['pms_kadence_active_elements'][ (int) $post->ID ] = $post;

	return $display;

}
add_filter( 'kadence_element_display', 'pms_kadence_track_active_element', 10, 3 );


/**
 * Match raw element content to a tracked kadence_element before blocks run
 *
 * @param string     $content Element post content
 * @param array|null $meta    Element meta from Kadence
 * @return string
 */
function pms_kadence_stash_rendering_element( $content, $meta = null ) {

	if ( empty( $GLOBALS['pms_kadence_active_elements'] ) || ! is_array( $GLOBALS['pms_kadence_active_elements'] ) )
		return $content;

	foreach ( $GLOBALS['pms_kadence_active_elements'] as $element ) {
		if ( ! empty( $element->post_content ) && $element->post_content === $content ) {
			$GLOBALS['pms_kadence_rendering_element'] = $element;
			break;
		}
	}

	return $content;

}
add_filter( 'ktp_the_content', 'pms_kadence_stash_rendering_element', 0, 2 );
add_filter( 'ktp_code_the_content', 'pms_kadence_stash_rendering_element', 0, 2 );


/**
 * Apply PMS content restriction to Kadence Hooked Element output
 *
 * @param string     $content Rendered element content
 * @param array|null $meta    Element meta from Kadence
 * @return string
 */
function pms_kadence_filter_element_content( $content, $meta = null ) {

	if ( empty( $GLOBALS['pms_kadence_rendering_element'] ) || ! ( $GLOBALS['pms_kadence_rendering_element'] instanceof WP_Post ) )
		return $content;

	$element = $GLOBALS['pms_kadence_rendering_element'];
	$GLOBALS['pms_kadence_rendering_element'] = null;

	return pms_filter_content( $content, $element );

}
add_filter( 'ktp_the_content', 'pms_kadence_filter_element_content', 11, 2 );
add_filter( 'ktp_code_the_content', 'pms_kadence_filter_element_content', 11, 2 );

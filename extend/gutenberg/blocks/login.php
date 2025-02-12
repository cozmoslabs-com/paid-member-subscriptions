<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
register_block_type( __DIR__ . '/build/login',
    [
        'render_callback' => function( $attributes, $content ) {
            ob_start();
            do_action( 'pms/login/render_callback', $attributes, $content );
            return ob_get_clean();
        },
    ]
);

add_action(
    'admin_enqueue_scripts',
    function () {
        $args = array(
            'post_type'         => 'page',
            'posts_per_page'    => -1
        );

        if( function_exists( 'wc_get_page_id' ) )
            $args['exclude'] = wc_get_page_id( 'shop' );

        $all_pages = get_posts( $args );

        $url_options[] = [ "label" => "", "value" => "" ];
        if( !empty( $all_pages ) ) {
            foreach ( $all_pages as $page ) {
                $url_options[] = [ "label" => esc_html( $page->post_title ) , "value" => esc_url( get_page_link( $page->ID ) ) ];
            }
        }

        // Add pre-loaded data for my-namespace/my-block
        wp_add_inline_script('pms-login-editor-script', 'window.pmsLoginBlockConfig = ' . json_encode(array(
                'url_options' => $url_options,
            )), 'before');
    }
);

/**
 * Render: PHP.
 *
 * @param array  $attributes Optional. Block attributes. Default empty array.
 * @param string $content    Optional. Block content. Default empty string.
 */
add_action(
    'pms/login/render_callback',
    function( $attributes, $content ) {
        if ( isset($attributes['is_preview']) && $attributes['is_preview'] ) {
            echo '
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 230 130"
                    style="width: "100%";"
                >
                    <title>Paid Member Subscriptions Login Block Preview</title>
                    <rect
                       width="35.065807"
                       height="4.06675"
                       x="33.038757"
                       y="103.87562"
                       rx="1.909605"
                       id="rect4-5"
                       style="fill:#a0a5aa;stroke-width:0.76554722" />
                    <rect
                       width="32.748241"
                       height="15"
                       x="28.485535"
                       y="0.6767953"
                       rx="2.6073439"
                       id="rect6-3"
                       style="fill:#a0a5aa;stroke-width:0.72212797" />
                    <rect
                       width="4.535502"
                       height="4.535502"
                       x="28.485535"
                       y="103.64124"
                       rx="2.2027693"
                       id="rect38-5"
                       style="fill:#a0a5aa;stroke-width:0.73425645" />
                    <rect
                       width="65.961594"
                       height="3.9698973"
                       x="28.485535"
                       y="45.359035"
                       rx="3.5921197"
                       id="rect4-3-6"
                       style="fill:#a0a5aa;stroke-width:1.03738868" />
                    <rect
                       width="28.646679"
                       height="12.727157"
                       x="28.485535"
                       y="118.54874"
                       rx="2.2807865"
                       id="rect6-7-2"
                       style="fill:#a0a5aa;stroke-width:0.62212461" />
                    <rect
                       width="174.22192"
                       height="12.620098"
                       x="28.485535"
                       y="51.580578"
                       rx="9.4877319"
                       id="rect4-3-5-9"
                       style="fill:#a0a5aa;stroke-width:3.00600076" />
                    <rect
                       width="174.22192"
                       height="12.620098"
                       x="28.485535"
                       y="80.928543"
                       rx="9.4877319"
                       id="rect4-3-5-6"
                       style="fill:#a0a5aa;stroke-width:3.00600076" />
                    <rect
                       width="23.093245"
                       height="3.9698973"
                       x="28.485535"
                       y="74.605255"
                       rx="1.257606"
                       id="rect4-3-7"
                       style="fill:#a0a5aa;stroke-width:0.61381632" />
                </svg>';
        } else {
            $atts['redirect_url'] = $attributes['redirect_url'] !== '' ? ' redirect_url="' . esc_url( $attributes['redirect_url'] ) . '"' : '';
            $atts['logout_redirect_url'] = $attributes['logout_redirect_url'] !== '' ? ' logout_redirect_url="' . esc_url( $attributes['logout_redirect_url'] ) . '"' : '';
            $atts['block'] = $attributes['is_editor'] ? ' block="true"' : '';

            echo '<div class="pms-block-container">' . do_shortcode( '[pms-login'. $atts['redirect_url'] . $atts['logout_redirect_url'] . $atts['block'] . ' ]' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    }
, 10, 2 );

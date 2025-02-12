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
register_block_type( __DIR__ . '/build/recover-password',
    [
        'render_callback' => function( $attributes, $content ) {
            ob_start();
            do_action( 'pms/recover_password/render_callback', $attributes, $content );
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
        wp_add_inline_script('pms-recover-password-editor-script', 'window.pmsRecoverPasswordBlockConfig = ' . json_encode(array(
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
    'pms/recover_password/render_callback',
    function( $attributes, $content ) {
        if ( isset($attributes['is_preview']) && $attributes['is_preview'] ) {
            echo '
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 230 130"
                    style="width: "100%";"
                >
                    <title>Paid Member Subscriptions Recover Password Block Preview</title>
                    <rect
                       width="97.682266"
                       height="8.75"
                       x="28.485535"
                       y="30.200914"
                       rx="7.7772508"
                       id="rect6"
                       style="fill:#a0a5aa;stroke-width:0.95254701" />
                    <rect
                       width="142.98218"
                       height="4.0700259"
                       x="28.485535"
                       y="64.617226"
                       rx="7.7864871"
                       id="rect4-3-5-7"
                       style="fill:#a0a5aa;stroke-width:1.54648554" />
                    <rect
                       width="47.609749"
                       height="4.2183123"
                       x="28.34981"
                       y="81.495705"
                       rx="2.5927198"
                       id="rect4-6"
                       style="fill:#a0a5aa;stroke-width:0.90849721" />
                    <rect
                       width="51.716038"
                       height="13.143845"
                       x="28.485535"
                       y="109.69728"
                       rx="4.1175189"
                       id="rect6-7"
                       style="fill:#a0a5aa;stroke-width:0.84947067" />
                    <rect
                       width="112.54496"
                       height="4.0700259"
                       x="28.485535"
                       y="56.377056"
                       rx="6.1289449"
                       id="rect4-3-5"
                       style="fill:#a0a5aa;stroke-width:1.37204361" />
                    <rect
                       width="178.67697"
                       height="12.859241"
                       x="28.544706"
                       y="88.151329"
                       rx="9.7303457"
                       id="rect4-3-5-5"
                       style="fill:#a0a5aa;stroke-width:3.07289886" />
                </svg>';
        } else {
            $atts['block'] = $attributes['is_editor'] ? ' block="true"' : '';
            $atts['redirect_url'] = $attributes['redirect_url'] !== '' ? ' redirect_url="' . esc_url( $attributes['redirect_url'] ) . '"' : '';

            echo '<div class="pms-block-container">' . do_shortcode( '[pms-recover-password' . $atts['redirect_url'] . $atts['block'] . ' ]' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    }
, 10, 2 );

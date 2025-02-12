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
register_block_type( __DIR__ . '/build/account',
    [
        'render_callback' => function( $attributes, $content ) {
            ob_start();
            do_action( 'pms/account/render_callback', $attributes, $content );
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
        wp_add_inline_script('pms-account-editor-script', 'window.pmsAccountBlockConfig = ' . json_encode(array(
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
    'pms/account/render_callback',
    function( $attributes, $content ) {
        if ( isset($attributes['is_preview']) && $attributes['is_preview'] ) {
            echo '
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 230 160"
                    style="width: "100%";"
                >
                    <title>Paid Member Subscriptions Account Block Preview</title>
                    <rect
                       width="45.006325"
                       height="7.1709166"
                       x="9.7501535"
                       y="9.3020382"
                       rx="3.5833063"
                       id="rect6"
                       style="fill:#a0a5aa;stroke-width:0.58532703" />
                    <rect
                       width="27.7237"
                       height="4.6558123"
                       x="10.251101"
                       y="30.76709"
                       rx="1.5097702"
                       id="rect4-3-5-9"
                       style="fill:#a0a5aa;stroke-width:0.7283324" />
                    <rect
                       width="36.972"
                       height="5.0727963"
                       x="11.313601"
                       y="42.55061"
                       rx="2.013412"
                       id="rect4-3-1"
                       style="fill:#a0a5aa;stroke-width:0.87794334" />
                    <rect
                       width="22.64451"
                       height="4.6558123"
                       x="42.467419"
                       y="30.76709"
                       rx="1.2331688"
                       id="rect4-3-5-9-92"
                       style="fill:#a0a5aa;stroke-width:0.65824181" />
                    <rect
                       width="42.281887"
                       height="4.6558123"
                       x="69.348198"
                       y="30.76709"
                       rx="2.3025763"
                       id="rect4-3-5-9-0"
                       style="fill:#a0a5aa;stroke-width:0.89945877" />
                    <rect
                       width="13.986893"
                       height="4.6558123"
                       x="115.77351"
                       y="30.76709"
                       rx="0.76169461"
                       id="rect4-3-5-9-23"
                       style="fill:#a0a5aa;stroke-width:0.51732641" />
                    <rect
                       width="13.902642"
                       height="5.0727963"
                       x="11.313601"
                       y="53.392792"
                       rx="0.7571066"
                       id="rect4-3-1-7"
                       style="fill:#a0a5aa;stroke-width:0.53836733" />
                    <rect
                       width="21.857594"
                       height="5.0727963"
                       x="11.313601"
                       y="64.234955"
                       rx="1.1903154"
                       id="rect4-3-1-5"
                       style="fill:#a0a5aa;stroke-width:0.67504263" />
                    <rect
                       width="32.906136"
                       height="5.0727963"
                       x="11.313601"
                       y="75.077126"
                       rx="1.7919942"
                       id="rect4-3-1-9"
                       style="fill:#a0a5aa;stroke-width:0.8282634" />
                    <rect
                       width="16.023962"
                       height="5.0727963"
                       x="11.313601"
                       y="85.919296"
                       rx="0.87262899"
                       id="rect4-3-1-2"
                       style="fill:#a0a5aa;stroke-width:0.5779829" />
                    <rect
                       width="36.795223"
                       height="5.0204363"
                       x="11.313601"
                       y="99.910347"
                       rx="2.0037851"
                       id="rect4-3-1-28"
                       style="fill:#a0a5aa;stroke-width:0.87131011" />
                    <rect
                       width="13.549088"
                       height="5.0204363"
                       x="11.313601"
                       y="110.64062"
                       rx="0.73785293"
                       id="rect4-3-1-97"
                       style="fill:#a0a5aa;stroke-width:0.52872771" />
                    <rect
                       width="20.973709"
                       height="5.0204363"
                       x="11.313601"
                       y="121.37088"
                       rx="1.142181"
                       id="rect4-3-1-3"
                       style="fill:#a0a5aa;stroke-width:0.65783149" />
                    <rect
                       width="32.906136"
                       height="5.0204363"
                       x="11.313601"
                       y="132.10115"
                       rx="1.7919942"
                       id="rect4-3-1-6"
                       style="fill:#a0a5aa;stroke-width:0.82397771" />
                    <rect
                       width="16.023962"
                       height="5.0204363"
                       x="11.313601"
                       y="142.83141"
                       rx="0.87262899"
                       id="rect4-3-1-1"
                       style="fill:#a0a5aa;stroke-width:0.57499224" />
                    <rect
                       width="14.056709"
                       height="5.0204363"
                       x="117.55927"
                       y="99.910347"
                       rx="0.76549679"
                       id="rect4-3-1-28-2"
                       style="fill:#a0a5aa;stroke-width:0.53854108" />
                    <rect
                       width="12.056709"
                       height="5.0204363"
                       x="117.55927"
                       y="110.64062"
                       rx="0.65658128"
                       id="rect4-3-1-97-9"
                       style="fill:#a0a5aa;stroke-width:0.49875978" />
                    <rect
                       width="36.681709"
                       height="5.0204363"
                       x="117.55927"
                       y="121.37088"
                       rx="1.9976034"
                       id="rect4-3-1-3-3"
                       style="fill:#a0a5aa;stroke-width:0.86996502" />
                    <rect
                       width="37.619209"
                       height="5.0204363"
                       x="117.55927"
                       y="132.60545"
                       rx="2.0486577"
                       id="rect4-3-1-6-1"
                       style="fill:#a0a5aa;stroke-width:0.88101208" />
                    <rect
                       width="16.619209"
                       height="5.0204363"
                       x="117.55927"
                       y="143.30228"
                       rx="0.90504479"
                       id="rect4-3-1-1-9"
                       style="fill:#a0a5aa;stroke-width:0.58557457" />
                    <rect
                       width="10.720661"
                       height="5.0727963"
                       x="116.5272"
                       y="42.55061"
                       rx="0.58382314"
                       id="rect4-3-1-4"
                       style="fill:#a0a5aa;stroke-width:0.47276011" />
                    <rect
                       width="17.791729"
                       height="5.0727963"
                       x="116.5272"
                       y="53.392792"
                       rx="0.96889758"
                       id="rect4-3-1-7-7"
                       style="fill:#a0a5aa;stroke-width:0.6090306" />
                    <rect
                       width="37.590717"
                       height="5.0727963"
                       x="116.5272"
                       y="64.234955"
                       rx="2.047106"
                       id="rect4-3-1-5-8"
                       style="fill:#a0a5aa;stroke-width:0.88525897" />
                    <rect
                       width="33.613243"
                       height="5.0727963"
                       x="116.5272"
                       y="75.077126"
                       rx="1.8305017"
                       id="rect4-3-1-9-4"
                       style="fill:#a0a5aa;stroke-width:0.83711523" />
                    <rect
                       width="30.696428"
                       height="5.0727963"
                       x="116.5272"
                       y="85.919296"
                       rx="1.6716585"
                       id="rect4-3-1-2-5"
                       style="fill:#a0a5aa;stroke-width:0.79997045" />
                    <rect
                       width="18.151564"
                       height="5.2536931"
                       x="149.76123"
                       y="85.82885"
                       rx="0.98849344"
                       id="rect4-3-1-2-5-0"
                       style="fill:#a0a5aa;stroke-width:0.62603074" />
                    <rect
                       width="14.244209"
                       height="5.0204363"
                       x="136.0654"
                       y="143.30228"
                       rx="0.7757076"
                       id="rect4-3-1-1-9-3"
                       style="fill:#a0a5aa;stroke-width:0.54212093" />
                    <rect
                       width="12.744209"
                       height="5.0204363"
                       x="152.8779"
                       y="143.30228"
                       rx="0.69402099"
                       id="rect4-3-1-1-9-6"
                       style="fill:#a0a5aa;stroke-width:0.51278281" />
                    <rect
                       width="17.931709"
                       height="5.0204363"
                       x="168.5654"
                       y="143.30228"
                       rx="0.97652054"
                       id="rect4-3-1-1-9-1"
                       style="fill:#a0a5aa;stroke-width:0.60825807" />
                </svg>';
        } else {
            $atts['logout_redirect_url'] = $attributes['logout_redirect_url'] !== '' ? ' logout_redirect_url="' . esc_attr( $attributes['logout_redirect_url'] ) . '"' : '';
            $atts['hide_tabs'] = $attributes['hide_tabs'] ? ' show_tabs="no"' : '';

            echo '<div class="pms-block-container">' . do_shortcode( '[pms-account' . $atts['hide_tabs'] . $atts['logout_redirect_url'] . ' ]' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    }
, 10, 2 );

<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register: PHP.
 */
add_action(
    'init',
    function() {
        wp_register_script(
            'pms-block-account',
            add_query_arg( [ 'action' => 'pms-block-account.js', ], admin_url( 'admin-ajax.php' ) ),
            [ 'wp-blocks', 'wp-element', 'wp-editor' ],
            microtime(),
            true
        );
        register_block_type(
            __DIR__,
            [
                'render_callback' => function( $attributes, $content ) {
                    ob_start();
                    do_action( 'pms/account/render_callback', $attributes, $content );
                    return ob_get_clean();
                },
            ]
        );
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
        if ( $attributes['is_preview'] ) {
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
    },
    10,
    2
);

/**
 * Register: JavaScript.
 */
add_action(
    'wp_ajax_pms-block-account.js',
    function() {
        header( 'Content-Type: text/javascript' );

        $args = array(
            'post_type'         => 'page',
            'posts_per_page'    => -1
        );

        if( function_exists( 'wc_get_page_id' ) )
            $args['exclude'] = wc_get_page_id( 'shop' );

        $all_pages = get_posts( $args );
        ?>
        ( function ( blocks, i18n, element, serverSideRender, blockEditor, components ) {
            var { __ } = i18n;
            var el = element.createElement;
            var PanelBody = components.PanelBody;
            var SelectControl = components.SelectControl;
            var ToggleControl = components.ToggleControl;
            var TextControl = components.TextControl;
            var InspectorControls = wp.editor.InspectorControls;

            blocks.registerBlockType( 'pms/account', {
                icon:
                    el('svg', {},
                        el( 'path',
                            {
                                d: "m 6.0748388,9.5111184 c -0.099,-0.038 -0.3519,-0.2294 -0.4445,-0.3021 -0.2808,-0.2202 -0.5499,-0.4486 -0.7896,-0.7139 -0.3274,-0.3624 -0.6189,-0.7523 -0.8327,-1.1938 -0.2727,-0.563 -0.4316,-1.1673 -0.4744,-1.7907 0,0 -0.011,-0.1397 -0.011,-0.1397 0,0 0,-0.5715 0,-0.5715 0,0 0.011,-0.127 0.011,-0.127 0.041,-0.5995 0.1724,-1.1913 0.4242,-1.7399 0.5843,-1.2728 1.7588,-2.28950004 3.0949,-2.70480004 0.5504,-0.1709 1.0894,-0.2356 1.6637,-0.2289 0,0 0.1397,0.012 0.1397,0.012 1.1751002,0.051 2.3266002,0.5475 3.1877002,1.34560004 0,0 0.2027,0.1916 0.2027,0.1916 0.5567,0.5821 0.9719,1.2857 1.2034,2.0574 0.089,0.2952 0.1506,0.5954 0.1834,0.9017 0,0 0.023,0.2413 0.023,0.2413 0,0 0.013,0.2667 0.013,0.2667 0,0 -0.013,0.3683 -0.013,0.3683 0,0.2138 -0.075,0.6249 -0.1255,0.8382 -0.3294,1.3871 -1.2433,2.4909 -2.3891,3.302 0.6849,0.2905 1.494,0.5944996 2.0955,1.0250996 0,0 0.2413,0.177 0.2413,0.177 0.1102,0.065 0.3181,0.1248 0.3358,0.2711 0.014,0.1159 -0.085,0.2251 -0.1545,0.3048 -0.1628,0.1872 -0.3068,0.3483 -0.575,0.2835 -0.051,-0.012 -0.093,-0.034 -0.1397,-0.058 0,0 -0.5588,-0.3592 -0.5588,-0.3592 -0.3912,-0.2347 -0.8643,-0.4613 -1.2954,-0.611 -2.1439002,-0.7439996 -4.5605002,-0.4705 -6.4897002,0.7249 -1.6213,1.0045 -2.8241,2.5675 -3.3528,4.401501 0,0 -0.097,0.3683 -0.097,0.3683 -0.063,0.2544 -0.1152,0.5525 -0.1463,0.8128 0,0 -0.034,0.3683 -0.034,0.3683 0.01,0.1204 0.027,0.2455 0.10599999,0.3424 0.1026,0.1269 0.3208,0.1654 0.4759,0.1656 0,0 4.9911,0 4.9911,0 0,0 2.7686,0 2.7686,0 0,0 0.1905,-0.013 0.1905,-0.013 0,0 1.3208002,0 1.3208002,0 0,0 0.1905,-0.013 0.1905,-0.013 0,0 0.6477,0 0.6477,0 0,0 0.2159,-0.013 0.2159,-0.013 0.2658,-3e-4 0.552,-0.016 0.8128,0.031 0.4383,0.079 0.8052,0.3029 1.1557,0.5678 0,0 0.3175,0.2498 0.3175,0.2498 0.053,0.042 0.119,0.083 0.1524,0.1422 0,0 -10.9855002,0 -10.9855002,0 0,0 -1.8669,0 -1.8669,0 0,0 -0.1397,-0.012 -0.1397,-0.012 -0.40049999,-0.027 -0.86169999,-0.085 -1.12069999,-0.433 -0.2141002,-0.2879 -0.20410019896,-0.6506 -0.2001002,-0.9906 0,-0.1616 0.055,-0.4918 0.084,-0.6604 0.06,-0.3549 0.1319,-0.7072 0.2284,-1.0541 0.1803,-0.6484 0.4123,-1.286301 0.73319999,-1.879601 0.5931,-1.0966 1.3661,-1.9912 2.371,-2.732 0.4948,-0.3647 1.1427,-0.7539 1.7018,-1.0104996 0,0 0.635,-0.278 0.635,-0.278 0,0 0.3175,-0.1324 0.3175,-0.1324 z m 2.2225,-8.54580004 c 0,0 -0.2413,0.028 -0.2413,0.028 -0.3083,0.042 -0.6099,0.11870004 -0.9017,0.22570004 -0.9628,0.353 -1.7756,1.0837 -2.2446,1.9932 -0.2473,0.4795 -0.4289,1.1231 -0.4351,1.6637 0,0 -0.013,0.2032 -0.013,0.2032 0,0 0.013,0.2286 0.013,0.2286 0,0.1545 0.04,0.3921 0.071,0.5461 0.095,0.4748 0.2701,0.9329 0.5234,1.3462 0.3003,0.4903 0.678,0.8906 1.1454,1.2246 0.3045,0.2177 0.6732,0.4094 1.0287,0.528 0.3581,0.1195 0.6406,0.1781 1.016,0.2154 0,0 0.127,0 0.127,0 0,0 0.127,0.013 0.127,0.013 0,0 0.3429,-0.013 0.3429,-0.013 0.1312,0 0.3877,-0.042 0.5207,-0.068 0.3128,-0.062 0.6662002,-0.1686 0.9525002,-0.3094 0,0 0.4191,-0.2243 0.4191,-0.2243 0.8838,-0.5502 1.5388,-1.4014 1.8142,-2.4077 0.066,-0.2429 0.1412,-0.6399 0.1416,-0.889 0,0 0,-0.4191 0,-0.4191 0,0 -0.012,-0.127 -0.012,-0.127 -0.016,-0.2261 -0.056,-0.4407 -0.1107,-0.6604 -0.3201,-1.2801 -1.3029,-2.3887 -2.5447,-2.8441 -0.3424002,-0.1255 -0.8554002,-0.25420004 -1.2192002,-0.25340004 0,0 -0.4572,0 -0.4572,0 0,0 -0.063,0 -0.063,0 z M 21.124339,6.8514184 c 0.13,-0.023 0.169,0.047 0.254,0.1324 0,0 0.3937,0.3937 0.3937,0.3937 0,0 1.7653,1.7653 1.7653,1.7653 0,0 0.4826,0.4826 0.4826,0.4826 0.065,0.065 0.1575,0.1366 0.1176,0.2413 -0.02,0.053 -0.1364,0.1584996 -0.1811,0.2031996 0,0 -0.3175,0.317 -0.3175,0.317 0,0 -0.127,0.115 -0.127,0.115 0,0 -1.5113,1.5111 -1.5113,1.5111 0,0 -2.3241,2.3241 -2.3241,2.3241 0,0 -0.4313,0.431801 -0.4313,0.431801 0,0 -0.2926,0.3048 -0.2926,0.3048 -0.095,0.095 -0.2788,0.2909 -0.381,0.3564 -0.089,0.057 -0.1807,0.081 -0.2794,0.1135 0,0 -0.4191,0.1397 -0.4191,0.1397 0,0 -1.3462,0.453 -1.3462,0.453 0,0 -0.8382,0.2794 -0.8382,0.2794 -0.3029,0.101 -0.5243,0.1858 -0.8509,0.182 -0.1179,0 -0.2656,-0.06 -0.381,-0.094 -0.057,-0.017 -0.1162,-0.032 -0.1343,-0.097 -0.022,-0.078 0.03,-0.1904 0.053,-0.2665 0,0 0.2001,-0.6477 0.2001,-0.6477 0,0 0.6986,-2.260601 0.6986,-2.260601 0,0 0.1777,-0.5715 0.1777,-0.5715 0.019,-0.057 0.056,-0.1991 0.083,-0.2413 0.026,-0.041 0.1157,-0.1263 0.1545,-0.1651 0,0 0.3048,-0.3048 0.3048,-0.3048 0,0 0.9525,-0.9525 0.9525,-0.9525 0,0 2.5781,-2.5780996 2.5781,-2.5780996 0,0 1.1049,-1.1049 1.1049,-1.1049 0,0 0.3048,-0.3048 0.3048,-0.3048 0.058,-0.058 0.1125,-0.1273 0.1905,-0.1578 z m 1.6891,2.9518 c 0,0 -1.2192,-1.2192 -1.2192,-1.2192 0,0 -0.3048,-0.3048 -0.3048,-0.3048 -0.027,-0.026 -0.089,-0.099 -0.127,-0.099 -0.032,0 -0.068,0.04 -0.089,0.061 0,0 -0.1905,0.1905 -0.1905,0.1905 0,0 -0.7747,0.7747 -0.7747,0.7747 0,0 -2.1463,2.1462996 -2.1463,2.1462996 0,0 -0.6858,0.6858 -0.6858,0.6858 -0.086,0.086 -0.3089,0.298 -0.3683,0.381 0,0 1.2192,1.2192 1.2192,1.2192 0,0 0.3048,0.3048 0.3048,0.3048 0.026,0.027 0.089,0.099 0.127,0.099 0.032,0 0.068,-0.04 0.089,-0.061 0,0 0.1905,-0.1905 0.1905,-0.1905 0,0 0.7747,-0.7747 0.7747,-0.7747 0,0 2.1463,-2.1463 2.1463,-2.1463 0,0 0.6858,-0.6858 0.6858,-0.6858 0.086,-0.086 0.3088,-0.2978996 0.3683,-0.3809996 z m -6.4897,3.3908996 c -0.051,0.059 -0.061,0.1437 -0.085,0.2159 0,0 -0.1493,0.4826 -0.1493,0.4826 0,0 -0.334,1.079501 -0.334,1.079501 0,0 -0.1305,0.4318 -0.1305,0.4318 0,0 0.381,-0.1228 0.381,-0.1228 0,0 0.6858,-0.2286 0.6858,-0.2286 0,0 0.7112,-0.237 0.7112,-0.237 0,0 0.3937,-0.135501 0.3937,-0.135501 0,0 -0.4953,-0.508 -0.4953,-0.508 0,0 -0.9779,-0.9779 -0.9779,-0.9779 z"
                            }
                        )
                    ),
                title: __( 'PMS Account' , 'paid-member-subscriptions' ),
                attributes: {
                    hide_tabs : {
                        type: 'boolean',
                        default: false,
                    },
                    logout_redirect_url : {
                        type: 'string',
                        default: '',
                    },
                    is_preview : {
                        type: 'boolean',
                        default: false,
                    },
                },
        
                edit: function ( props ) {
                    return [
                        el(
                            'div',
                            Object.assign( blockEditor.useBlockProps(), { key: 'pms/account/render' } ),
                            el( serverSideRender,
                                {
                                    block: 'pms/account',
                                    attributes: props.attributes,
                                }
                            )
                        ),
                        el( InspectorControls, { key: 'pms/account/inspector' },
                            [
                                el( PanelBody,
                                    {
                                        title: __( 'Form Settings' , 'paid-member-subscriptions' ),
                                        key: 'pms/account/inspector/form-settings'
                                    },
                                    el( ToggleControl,
                                        {
                                            label: __( 'Hide tabs' , 'paid-member-subscriptions' ),
                                            key: 'pms/account/inspector/form-settings/hide_tabs',
                                            help: __( 'Select whether to hide the Account form tabs' , 'paid-member-subscriptions' ),
                                            checked: props.attributes.hide_tabs,
                                            onChange: ( value ) => { props.setAttributes( { hide_tabs: !props.attributes.hide_tabs } ); }
                                        }
                                    ),
                                ),
                                el( PanelBody,
                                    {
                                        title: __( 'Redirect' , 'paid-member-subscriptions' ),
                                        key: 'pms/account/inspector/redirect'
                                    },
                                    el( SelectControl,
                                        {
                                            label: __( 'After Logout' , 'paid-member-subscriptions' ),
                                            key: 'pms/account/inspector/redirect/logout_redirect_url',
                                            help: __( 'Select a page for an After Logout Redirect' , 'paid-member-subscriptions' ),
                                            value: props.attributes.logout_redirect_url,
                                            options: [
                                                {
                                                    label: __( '' , 'paid-member-subscriptions' ),
                                                    value: ''
                                                },
        <?php
        if( !empty( $all_pages ) ){
            foreach ( $all_pages as $page ){
                ?>
                                                {
                                                    label: '<?php echo esc_html( $page->post_title ) ?>',
                                                    value: '<?php echo esc_url( get_page_link( $page->ID ) ) ?>'
                                                },
                <?php
            }
        }
        ?>
                                            ],
                                            onChange: ( value ) => { props.setAttributes( { logout_redirect_url: value } ); }
                                        }
                                    )
                                )
                            ]
                        ),
                        el( blockEditor.InspectorAdvancedControls, { key: 'pms/account/inspector_advanced' },
                            el( TextControl,
                                {
                                    label: __( 'After Logout' , 'paid-member-subscriptions' ),
                                    key: 'pms/account/inspector_advanced/logout_redirect_url',
                                    help: __( 'Manually type in an After Logout Redirect URL' , 'paid-member-subscriptions' ),
                                    value: props.attributes.logout_redirect_url,
                                    onChange: ( value ) => { props.setAttributes( { logout_redirect_url: value } ); }
                                }
                            )
                        ),
                    ];
                }
            } );
        } )(
            window.wp.blocks,
            window.wp.i18n,
            window.wp.element,
            window.wp.serverSideRender,
            window.wp.blockEditor,
            window.wp.components
        );
        <?php
        exit;
    }
);

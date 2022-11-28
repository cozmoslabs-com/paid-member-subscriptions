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
            'pms-block-register',
            add_query_arg( [ 'action' => 'pms-block-register.js', ], admin_url( 'admin-ajax.php' ) ),
            [ 'wp-blocks', 'wp-element', 'wp-editor' ],
            microtime(),
            true
        );
        register_block_type(
            'pms/register',
            [
                'api_version'                      => 2,
                'title'                            => __( 'PMS Register' , 'paid-member-subscriptions' ),
                'description'                      => __( 'Displays the Paid Member Subscriptions Registration Form.' , 'paid-member-subscriptions' ),
                'category'                         => 'pms-block',
                'keywords'                         => [
                    __( 'Register' , 'paid-member-subscriptions' ),
                    __( 'Form' , 'paid-member-subscriptions' ),
                    __( 'PMS' , 'paid-member-subscriptions' ),
                    __( 'Paid Member Subscriptions' , 'paid-member-subscriptions' ),
                ],
                'example'                          => [
                    'attributes'                   => [
                        'include'                  => true,
                        'show_subscription_plans'  => true,
                        'subscription_plans'       => [],
                        'exclude_subscription_plans'=> [],
                        'selected'                 => '',
                        'plans_position'           => false,
                        'is_preview'               => true,
                        'is_editor'                => true,
                    ],
                ],
                'editor_script'                    => 'pms-block-register',
                'attributes'                       => [
                    'show_subscription_plans'      => [
                        'type'                     => 'boolean',
                        'default'                  => true,
                    ],
                    'include'                      => [
                        'type'                     => 'boolean',
                        'default'                  => true,
                    ],
                    'subscription_plans'           => [
                        'type'                     => 'array',
                        'default'                  => [],
                    ],
                    'exclude_subscription_plans'   => [
                        'type'                     => 'array',
                        'default'                  => [],
                    ],
                    'selected'                     => [
                        'type'                     => 'string',
                        'default'                  => '',
                    ],
                    'plans_position'               => [
                        'type'                     => 'boolean',
                        'default'                  => false,
                    ],
                    'is_preview'                   => [
                        'type'                     => 'boolean',
                        'default'                  => false,
                    ],
                    'is_editor'                    => [
                        'type'                     => 'boolean',
                        'default'                  => false,
                    ],
                ],

                'render_callback' => function( $attributes, $content ) {
                    ob_start();
                    do_action( 'pms/register/render_callback', $attributes, $content );
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
    'pms/register/render_callback',
    function( $attributes, $content ) {
        if ( $attributes['is_preview'] ) {
            echo '
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 230 300"
                    style="width: "100%";"
                >
                    <title>Paid Member Subscriptions Register Block Preview</title>
                    <rect
                       width="42.631325"
                       height="11.108417"
                       x="28.187654"
                       y="30.427038"
                       rx="3.3942139"
                       id="rect6"
                       style="fill:#a0a5aa;stroke-width:0.70903063" />
                    <rect
                       width="27.766592"
                       height="4.6558123"
                       x="28.187654"
                       y="64.185104"
                       rx="1.5121059"
                       id="rect4-3-5-9"
                       style="fill:#a0a5aa;stroke-width:0.7288956" />
                    <rect
                       width="173.49646"
                       height="12.084113"
                       x="28.187654"
                       y="70.811058"
                       rx="9.4482269"
                       id="rect4-3-1"
                       style="fill:#a0a5aa;stroke-width:2.93534398" />
                    <rect
                       width="19.016592"
                       height="4.6558123"
                       x="28.187654"
                       y="90.144966"
                       rx="1.0356008"
                       id="rect4-3-5-9-3"
                       style="fill:#a0a5aa;stroke-width:0.60321254" />
                    <rect
                       width="173.49646"
                       height="12.084113"
                       x="28.187654"
                       y="96.77092"
                       rx="9.4482269"
                       id="rect4-3-1-6"
                       style="fill:#a0a5aa;stroke-width:2.93534398" />
                    <rect
                       width="26.016592"
                       height="4.6558123"
                       x="28.187654"
                       y="117.14497"
                       rx="1.4168049"
                       id="rect4-3-5-9-7"
                       style="fill:#a0a5aa;stroke-width:0.70555234" />
                    <rect
                       width="173.49646"
                       height="12.084113"
                       x="28.187654"
                       y="123.77092"
                       rx="9.4482269"
                       id="rect4-3-1-5"
                       style="fill:#a0a5aa;stroke-width:2.93534398" />
                    <rect
                       width="24.766592"
                       height="4.6558123"
                       x="28.187654"
                       y="142.14497"
                       rx="1.3487327"
                       id="rect4-3-5-9-35"
                       style="fill:#a0a5aa;stroke-width:0.68839413" />
                    <rect
                       width="173.49646"
                       height="12.084113"
                       x="28.187654"
                       y="148.77092"
                       rx="9.4482269"
                       id="rect4-3-1-62"
                       style="fill:#a0a5aa;stroke-width:2.93534398" />
                    <rect
                       width="26.766592"
                       height="4.6558123"
                       x="28.187654"
                       y="167.14497"
                       rx="1.4576483"
                       id="rect4-3-5-9-9"
                       style="fill:#a0a5aa;stroke-width:0.71564984" />
                    <rect
                       width="173.49646"
                       height="12.084113"
                       x="28.187654"
                       y="173.77092"
                       rx="9.4482269"
                       id="rect4-3-1-1"
                       style="fill:#a0a5aa;stroke-width:2.93534398" />
                    <rect
                       width="43.89159"
                       height="4.6558123"
                       x="28.187654"
                       y="194.14497"
                       rx="2.3902371"
                       id="rect4-3-5-9-2"
                       style="fill:#a0a5aa;stroke-width:0.91642028" />
                    <rect
                       width="173.49646"
                       height="12.084113"
                       x="28.187654"
                       y="200.77092"
                       rx="9.4482269"
                       id="rect4-3-1-7"
                       style="fill:#a0a5aa;stroke-width:2.93534398" />
                    <rect
                       width="3.4369612"
                       height="3.4369612"
                       x="28.187654"
                       y="264.76764"
                       rx="1.6692381"
                       id="rect38-9"
                       style="fill:#a0a5aa;stroke-width:0.5564127" />
                    <rect
                       width="33.425518"
                       height="12.68294"
                       x="28.187654"
                       y="286.12128"
                       rx="2.6612675"
                       id="rect6-3"
                       style="fill:#a0a5aa;stroke-width:0.67084712" />
                    <rect
                       width="18.850863"
                       height="2.2531145"
                       x="34.715397"
                       y="272.95929"
                       rx="1.500865"
                       id="rect6-3-6"
                       style="fill:#a0a5aa;stroke-width:0.21234" />
                    <rect
                       width="25.288363"
                       height="4.1281142"
                       x="33.54332"
                       y="264.37344"
                       rx="2.0134048"
                       id="rect6-3-6-0"
                       style="fill:#a0a5aa;stroke-width:0.33289769" />
                    <rect
                       width="3.4369612"
                       height="3.4369612"
                       x="28.187654"
                       y="243.09972"
                       rx="1.6692381"
                       id="rect38-9-6"
                       style="fill:#a0a5aa;stroke-width:0.5564127" />
                    <rect
                       width="21.414125"
                       height="2.2531145"
                       x="34.715397"
                       y="251.29137"
                       rx="1.7049464"
                       id="rect6-3-6-2"
                       style="fill:#a0a5aa;stroke-width:0.22631657" />
                    <rect
                       width="50.213875"
                       height="4.1281142"
                       x="33.54332"
                       y="242.70552"
                       rx="3.9979205"
                       id="rect6-3-6-0-6"
                       style="fill:#a0a5aa;stroke-width:0.46909663" />
                    <rect
                       width="3.4369612"
                       height="3.4369612"
                       x="28.187654"
                       y="220.97472"
                       rx="1.6692381"
                       id="rect38-9-1"
                       style="fill:#a0a5aa;stroke-width:0.5564127" />
                    <rect
                       width="18.850863"
                       height="2.2531145"
                       x="34.715397"
                       y="229.16637"
                       rx="1.500865"
                       id="rect6-3-6-8"
                       style="fill:#a0a5aa;stroke-width:0.21234" />
                    <rect
                       width="42.170536"
                       height="4.1281142"
                       x="33.54332"
                       y="220.58052"
                       rx="3.357527"
                       id="rect6-3-6-0-7"
                       style="fill:#a0a5aa;stroke-width:0.42988768" />
                </svg>';
        } else {
            if( $attributes['show_subscription_plans'] ) {
                if ( $attributes['include'] ) {
                    $atts['subscription_plans'] = $attributes['subscription_plans'] !== '' ? ' subscription_plans="' . esc_attr( implode( ",", $attributes['subscription_plans'] ) ) . '"' : '';
                } else {
                    $atts['subscription_plans'] = $attributes['exclude_subscription_plans'] !== '' ? ' exclude="' . esc_attr( implode( ",", $attributes['exclude_subscription_plans'] ) ) . '"' : '';
                }
                $atts['selected'] = $attributes['selected'] !== '' ? ' selected="' . esc_attr($attributes['selected']) . '"' : '';
                $atts['plans_position'] = $attributes['plans_position'] ? ' plans_position="top"' : '';
            } else {
                $atts['subscription_plans'] = ' subscription_plans="none"';
                $atts['selected'] = '';
                $atts['plans_position'] = '';
            }
            $atts['block'] = $attributes['is_editor'] ? ' block="true"' : '';

            wp_register_script( 'dummy-handle-header', '' );
            wp_enqueue_script( 'dummy-handle-header' );
            wp_add_inline_script( 'dummy-handle-header', 'console.log( "header" );' );


            echo '<div class="pms-block-container">' . do_shortcode( '[pms-register' . $atts['subscription_plans'] . $atts['selected'] . $atts['plans_position'] . $atts['block'] . ' ]') . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    },
    10,
    2
);

/**
 * Register: JavaScript.
 */
add_action(
    'wp_ajax_pms-block-register.js',
    function() {
        header( 'Content-Type: text/javascript' );

        $plans = array();

        $plan_ids = get_posts( array( 'post_type' => 'pms-subscription', 'meta_key' => 'pms_subscription_plan_status', 'meta_value' => 'active', 'numberposts' => -1, 'post_status' => 'any', 'fields' => 'ids' ) );

        if( !empty( $plan_ids ) ) {
            foreach ($plan_ids as $plan_id)
                $plans[$plan_id] = get_the_title($plan_id);
        }

        ?>
        ( function ( blocks, i18n, element, serverSideRender, blockEditor, components ) {
            var { __ } = i18n;
            var el = element.createElement;
            var PanelBody = components.PanelBody;
            var SelectControl = components.SelectControl;
            var ToggleControl = components.ToggleControl;
            var TextControl = components.TextControl;
            var Text = components.__experimentalText;
            var Button = components.Button;
            var InspectorControls = wp.editor.InspectorControls;

            var subscription_plans_names = [ 'Africa', 'America', 'Antarctica', 'Asia', 'Europe', 'Oceania' ];


            blocks.registerBlockType( 'pms/register', {
                icon:
                    el('svg', {},
                        el( 'path',
                            {
                                d: "m 6.060947,9.5388969 c -0.049,-0.058 -0.1159,-0.093 -0.1778,-0.1361 0,0 -0.2921,-0.215 -0.2921,-0.215 -0.271,-0.2089 -0.5275,-0.4398 -0.7608,-0.6903 -0.6133,-0.6582 -1.0671,-1.5116 -1.2277,-2.4003 -0.043,-0.2401 -0.081,-0.5187 -0.082,-0.762 0,0 -0.013,-0.2794 -0.013,-0.2794 0,0 0.012,-0.127 0.012,-0.127 0,0 0,-0.1651 0,-0.1651 0,0 0.012,-0.127 0.012,-0.127 0,0 0.049,-0.4318 0.049,-0.4318 0.1148,-0.7792 0.4319,-1.5308 0.9068,-2.159 0.5129,-0.6785 1.2601,-1.27849998 2.043,-1.61639998 0.5894,-0.2544 1.2241,-0.4081 1.8669,-0.4156 0,0 0.1651,-0.0130000022 0.1651,-0.0130000022 0,0 0.127,0.0120000021 0.127,0.0120000021 0,0 0.1651,0 0.1651,0 0,0 0.127,0.012 0.127,0.012 0.3658,0.025 0.729,0.096 1.0795,0.2041 1.3113,0.4033 2.4651,1.33699998 3.0701,2.57829998 0.6277,1.2874 0.7121,2.7812 0.1735,4.1148 -0.2399,0.5938 -0.5806,1.1109 -1.0063,1.5875 -0.2369,0.2653 -0.499,0.5094 -0.7768,0.7316 -0.089,0.072 -0.2831,0.2336 -0.381,0.2717 0,0 0,0.025 0,0.025 0,0 0.7112,0.2940999 0.7112,0.2940999 0.6331,0.2717002 1.0023,0.4378002 1.5621,0.8586002 0,0 0.3173,0.185 0.3173,0.185 0.213,0.1813 -0.071,0.4368 -0.203,0.5673 -0.041,0.041 -0.066,0.065 -0.1143,0.097 -0.05,0.034 -0.093,0.051 -0.1524,0.062 -0.1604,0.029 -0.3036,-0.053 -0.4318,-0.138 0,0 -0.381,-0.2499 -0.381,-0.2499 -0.5649,-0.3397 -1.0933,-0.5842 -1.7272,-0.7693 -0.7370002,-0.2153 -1.5072,-0.3063 -2.2733,-0.2975 0,0 -0.127,0.012 -0.127,0.012 0,0 -0.127,0 -0.127,0 0,0 -0.127,0.012 -0.127,0.012 0,0 -0.7366,0.087 -0.7366,0.087 -0.907,0.1512 -1.7832,0.4743 -2.5781,0.9356 -1.6257,0.9436 -2.9202,2.547 -3.4635,4.3507 -0.1191,0.3957 -0.1818,0.6592 -0.2501,1.0668 0,0 -0.0710001,0.6223 -0.0710001,0.6223 0,0.1503 0.042,0.3048 0.16490005,0.4015 0.1042,0.082 0.2529,0.1012 0.381,0.1071 0,0 0.254,0.012 0.254,0.012 0,0 0.2159,-0.013 0.2159,-0.013 0,0 5.4737,0 5.4737,0 0,0 2.4891998,0 2.4891998,0 0,0 0.1905002,-0.013 0.1905002,-0.013 0,0 0.9906,0 0.9906,0 0,0 0.1905,-0.013 0.1905,-0.013 0,0 0.5969,0 0.5969,0 0,0 0.1905,-0.013 0.1905,-0.013 0.2018,-2e-4 0.3568,0 0.5588,0.031 0.4647,0.078 0.8372,0.3136 1.2065,0.5931 0.096,0.073 0.3827,0.2873 0.4445,0.3666 0,0 -10.9728,0 -10.9728,0 0,0 -1.9431,0 -1.9431,0 0,0 -0.1524,-0.012 -0.1524,-0.012 -0.38190005,-0.027 -0.82500005,-0.1105 -1.05950005,-0.4457 -0.206,-0.2942 -0.189,-0.6494 -0.1851,-0.9906 0,0 0.071,-0.5842 0.071,-0.5842 0,0 0.1974,-0.9525 0.1974,-0.9525 0.3729,-1.4916 1.08010005,-2.8205 2.15750005,-3.9243 0,0 0.1776,-0.1656 0.1776,-0.1656 0.4393,-0.4201 0.9205,-0.7823 1.4351,-1.105 0.437,-0.2741 0.9107,-0.5223002 1.3843,-0.7268002 0.1284,-0.055 0.5215,-0.2298999 0.635,-0.2504999 z m 2.2987,-8.57070002 c 0,0 -0.2667,0.027 -0.2667,0.027 -0.2706,0.036 -0.5407,0.092 -0.8001,0.17870002 -1.0421,0.3475 -1.9575,1.1324 -2.4285,2.1291 -0.2717,0.5754 -0.4109,1.2057 -0.4036,1.8415 0,0 0.012,0.1397 0.012,0.1397 0.053,1.2058 0.6753,2.3521 1.6388,3.0748 0.3587,0.269 0.7435,0.4708 1.1684,0.6124 0.3321,0.1108 0.6673,0.1762 1.016,0.2002 0,0 0.127,0.012 0.127,0.012 0,0 0.2794,0 0.2794,0 0,0 0.1524,-0.012 0.1524,-0.012 0.5485,-0.038 1.0816998,-0.1768 1.5748,-0.4234 0.4468,-0.2234 0.8984,-0.5618 1.2284,-0.9367 0.3764,-0.4277 0.6455,-0.8846 0.833,-1.4224 0.1068,-0.3064 0.2114,-0.8199 0.2119,-1.143 0,0 0,-0.3683 0,-0.3683 0,-0.2176 -0.062,-0.561 -0.1159,-0.7747 -0.223,-0.8925 -0.7155,-1.6606 -1.4335,-2.2377 -0.3838,-0.3085 -0.8161,-0.5404 -1.2827002,-0.696 -0.3125998,-0.1042 -0.6365998,-0.1686 -0.9651998,-0.19160002 0,0 -0.1524,-0.01 -0.1524,-0.01 0,0 -0.3937,0 -0.3937,0 z m 7.4295,13.09190012 c 0,0 0,-2.8194 0,-2.8194 0,0 0,-0.7112 0,-0.7112 0,-0.1319 0.062,-0.1395 0.1778,-0.1397 0,0 0.635,0 0.635,0 0.123,0 0.1518,0.049 0.1524,0.1651 0,0 0,0.6858 0,0.6858 0,0 0,2.8194 0,2.8194 0,0 2.8194,0 2.8194,0 0,0 0.6985,0 0.6985,0 0.1339,0 0.1522,0.058 0.1524,0.1778 0,0 0,0.6477 0,0.6477 0,0.129 -0.05,0.1396 -0.1651,0.1397 0,0 -3.5052,0 -3.5052,0 0,0 0,2.8194 0,2.8194 0,0 0,0.7112 0,0.7112 0,0.129 -0.05,0.1396 -0.1651,0.1397 0,0 -0.6604,0 -0.6604,0 -0.1227,-8e-4 -0.1394,-0.037 -0.1397,-0.1524 0,0 0,-3.5179 0,-3.5179 0,0 -2.8194,0 -2.8194,0 0,0 -0.7112,0 -0.7112,0 -0.1299,-8e-4 -0.1395,-0.048 -0.1397,-0.1651 0,0 0,-0.6604 0,-0.6604 0,-0.113 0.043,-0.1381 0.1524,-0.1397 0,0 0.6985,0 0.6985,0 0,0 2.8194,0 2.8194,0 z"
                            }
                        )
                    ),
                title: __( 'PMS Register' , 'paid-member-subscriptions' ),
                attributes: {
                    show_subscription_plans : {
                        type: 'boolean',
        <?php
        if( empty( $plans ) ){
            ?>
                        default: false
            <?php
        } else {
            ?>
                        default: true
            <?php
        }
        ?>
                    },
                    include : {
                        type: 'boolean',
                        default: true,
                    },
                    subscription_plans : {
                        type: 'array',
                        default: []
                    },
                    exclude_subscription_plans : {
                        type: 'array',
                        default: []
                    },
                    selected : {
                        type: 'string',
                        default: ''
                    },
                    plans_position : {
                        type: 'boolean',
                        default: false
                    },
                    is_preview : {
                        type: 'boolean',
                        default: false,
                    },
                    is_editor : {
                        type: 'boolean',
                        default: true,
                    },
                },

                edit: function ( props ) {
                    const subscription_plansOptions = [
        <?php
        if( !empty( $plans ) ){
            foreach ( $plans as $plan_id => $plan_title ){
                ?>
                        {
                        label: '<?php echo esc_html( $plan_title ) ?>',
                        value: '<?php echo esc_html( $plan_id ) ?>'
                        },
                <?php
            }
        }
        ?>
                    ];

                    return [
                        el(
                            'div',
                            Object.assign( blockEditor.useBlockProps(), { key: 'pms/register/render' } ),
                            el( serverSideRender,
                                {
                                    block: 'pms/register',
                                    attributes: props.attributes,
                                }
                            )
                        ),
                        el( InspectorControls, { key: 'pms/register/inspector' },
                            [
                                el( PanelBody,
                                    {
                                        title: __( 'Form Settings' , 'paid-member-subscriptions' ),
                                        key: 'pms/register/inspector/form_settings',
                                    },
                                    [
                                        el( ToggleControl,
                                            {
                                                label: __( 'Show Subscription Plans' , 'paid-member-subscriptions' ),
                                                key: 'pms/register/inspector/form_settings/show_subscription_plans',
                                                help: __( 'Include Subscription Plans in the form' , 'paid-member-subscriptions' ),
        <?php
        if( !empty( $plans ) ){
            ?>
                                                checked: props.attributes.show_subscription_plans,
                                                onChange: ( value ) => { props.setAttributes( { show_subscription_plans: !props.attributes.show_subscription_plans } ); }
            <?php
        } else {
            ?>
                                                checked: false,
                                                disabled: true
            <?php
        }
        ?>
                                            }
                                        ),
        <?php
        if( empty( $plans ) ){
            ?>
                                        el( Text,
                                            {
                                                key: 'pms/register/inspector/form_settings/notice'
                                            },
                                            [
                                                __( 'To do this you need to have at least one active Subscription Plan. You may activate or create one ' , 'paid-member-subscriptions' ),
                                                el( Button,
                                                    {
                                                        key: 'pms/register/inspector/form_settings/notice/notice_button',
                                                        href: '<?php echo esc_url( admin_url( 'edit.php?post_type=pms-subscription' ) ); ?>',
                                                        target: '_blank',
                                                        text: __( 'here' , 'paid-member-subscriptions' ),
                                                        variant: 'link'
                                                    }
                                                )
                                            ]
                                        ),
            <?php
        }
        ?>
                                        props.attributes.show_subscription_plans === true ?
                                            el( ToggleControl,
                                                {
                                                    label: __( 'Include or Exclude' , 'paid-member-subscriptions' ),
                                                    key: 'pms/register/inspector/form_settings/include',
                                                    help: __( 'Toggle to either include Subscription Plans or exclude them from the form' , 'paid-member-subscriptions' ),
                                                    checked: props.attributes.include,
                                                    onChange: ( value ) => { props.setAttributes( { include: !props.attributes.include } ); }
                                                }
                                            ) :
                                            '',
                                        props.attributes.show_subscription_plans === true && props.attributes.include === true ?
                                            el( SelectControl,
                                                {
                                                    label: __( 'Include Subscription Plans' , 'paid-member-subscriptions' ),
                                                    className: 'pms-block-select-multiple',
                                                    key: 'pms/register/inspector/form_settings/subscription_plans',
                                                    help: __( 'Select the Subscription Plans to be included in the form' , 'paid-member-subscriptions' ),
                                                    multiple: true,
                                                    value: props.attributes.subscription_plans,
                                                    options: subscription_plansOptions,
                                                    onChange: ( value ) => { props.setAttributes( { subscription_plans: value } ); }
                                                }
                                            ) :
                                            '',
                                        props.attributes.show_subscription_plans === true && props.attributes.include !== true ?
                                            el( SelectControl,
                                                {
                                                    label: __( 'Exclude Subscription Plans' , 'paid-member-subscriptions' ),
                                                    className: 'pms-block-select-multiple',
                                                    key: 'pms/register/inspector/form_settings/exclude_subscription_plans',
                                                    help: __( 'Select the Subscription Plans to be excluded from the form' , 'paid-member-subscriptions' ),
                                                    multiple: true,
                                                    value: props.attributes.exclude_subscription_plans,
                                                    options: subscription_plansOptions,
                                                    onChange: ( value ) => { props.setAttributes( { exclude_subscription_plans: value } ); }
                                                }
                                            ) :
                                            '',
                                        props.attributes.show_subscription_plans === true ?
                                            el( SelectControl,
                                                {
                                                    label: __( 'Selected Plan' , 'paid-member-subscriptions' ),
                                                    key: 'pms/register/inspector/form_settings/selected',
                                                    help: __( 'Choose the Subscription Plan that will be selected by default' , 'paid-member-subscriptions' ),
                                                    value: props.attributes.selected,
                                                    options: [
                                                        {
                                                            label: __( '' , 'paid-member-subscriptions' ),
                                                            value: ''
                                                        }
                                                    ].concat( subscription_plansOptions ),
                                                    onChange: ( value ) => { props.setAttributes( { selected: value } ); }
                                                }
                                            ) :
                                            '',
                                        props.attributes.show_subscription_plans === true ?
                                            el( ToggleControl,
                                                {
                                                    label: __( 'Subscription Plans at the Top' , 'paid-member-subscriptions' ),
                                                    key: 'pms/register/inspector/form_settings/plans_position',
                                                    help: __( 'Determine the position of the Subscription Plans in the form' , 'paid-member-subscriptions' ),
                                                    checked: props.attributes.plans_position,
                                                    onChange: ( value ) => { props.setAttributes( { plans_position: !props.attributes.plans_position } ); }
                                                }
                                            ) :
                                            '',
                                    ]
                                )
                            ]
                        ),
                        el( blockEditor.InspectorAdvancedControls, { key: 'pms/register/inspector_advanced' },
                            [
                                props.attributes.show_subscription_plans === true && props.attributes.include === true ?
                                    el( TextControl,
                                        {
                                            label: __( 'Include Subscription Plans' , 'paid-member-subscriptions' ),
                                            key: 'pms/register/inspector_advanced/subscription_plans',
                                            help: __( 'Manually type in the IDs for the Subscription Plans to be included in the form' , 'paid-member-subscriptions' ),
                                            value: props.attributes.subscription_plans,
                                            onChange: ( value ) => { props.setAttributes( { subscription_plans: value.split( ',' ).map( element => element.trim() ) } ); }
                                        }
                                    ) :
                                    '',
                                props.attributes.show_subscription_plans === true && props.attributes.include !== true ?
                                    el( TextControl,
                                        {
                                            label: __( 'Exclude Subscription Plans' , 'paid-member-subscriptions' ),
                                            key: 'pms/register/inspector_advanced/exclude_subscription_plans',
                                            help: __( 'Manually type in the IDs for the Subscription Plans to be excluded from the form' , 'paid-member-subscriptions' ),
                                            value: props.attributes.exclude_subscription_plans,
                                            onChange: ( value ) => { props.setAttributes( { exclude_subscription_plans: value.split( ',' ).map( element => element.trim() ) } ); }
                                        }
                                    ) :
                                    '',
                                props.attributes.show_subscription_plans === true ?
                                    el( TextControl,
                                        {
                                            label: __( 'Selected Plan' , 'paid-member-subscriptions' ),
                                            key: 'pms/register/inspector_advanced/selected',
                                            help: __( 'Manually type in the ID for a Subscription Plan that will be selected by default' , 'paid-member-subscriptions' ),
                                            value: props.attributes.selected,
                                            onChange: ( value ) => { props.setAttributes( { selected: value } ); }
                                        }
                                    ) :
                                    '',
                            ]
                        )
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
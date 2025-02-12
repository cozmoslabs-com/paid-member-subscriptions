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
register_block_type( __DIR__ . '/build/register',
    [
        'render_callback' => function( $attributes, $content ) {
            ob_start();
            do_action( 'pms/register/render_callback', $attributes, $content );
            return ob_get_clean();
        },
    ]
);

add_action(
    'admin_enqueue_scripts',
    function () {
        $plans = array();

        $plan_ids = get_posts( array( 'post_type' => 'pms-subscription', 'meta_key' => 'pms_subscription_plan_status', 'meta_value' => 'active', 'numberposts' => -1, 'post_status' => 'any', 'fields' => 'ids' ) );

        if( !empty( $plan_ids ) ) {
            foreach ($plan_ids as $plan_id)
                $plans[] = [ "label" => esc_html( get_the_title($plan_id) ) , "value" => esc_html( $plan_id ) ];
        }

        if( empty( $plans ) ){
            $show_subscription_plans = false;
        } else {
            $show_subscription_plans = true;
        }

        // Add pre-loaded data for my-namespace/my-block
        wp_add_inline_script('pms-register-editor-script', 'window.pmsRegisterBlockConfig = ' . json_encode(array(
                'plans' => $plans,
                'show_subscription_plans' => $show_subscription_plans,
                'button' => esc_url( admin_url( 'edit.php?post_type=pms-subscription' ) ),
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
    'pms/register/render_callback',
    function( $attributes, $content ) {
        if ( isset($attributes['is_preview']) && $attributes['is_preview'] ) {
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
    }
, 10, 2 );

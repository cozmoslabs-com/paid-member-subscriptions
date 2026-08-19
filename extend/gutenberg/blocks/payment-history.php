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
register_block_type( __DIR__ . '/build/payment-history/block.json',
    [
        'render_callback' => function( $attributes, $content ) {
            ob_start();
            do_action( 'pms/payment_history/render_callback', $attributes, $content );
            return ob_get_clean();
        },
    ]
);

/**
 * Render: PHP.
 *
 * @param array  $attributes Optional. Block attributes. Default empty array.
 * @param string $content    Optional. Block content. Default empty string.
 */
add_action(
    'pms/payment_history/render_callback',
    function( $attributes, $content ) {
        if ( isset( $attributes['is_preview'] ) && $attributes['is_preview'] ) {
            echo '
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 230 130"
                    style="width: 100%;"
                >
                    <title>Paid Member Subscriptions Payment History Block Preview</title>
                    <rect width="178" height="10" x="26" y="22" rx="3" style="fill:#a0a5aa;stroke-width:1" />
                    <rect width="178" height="1.5" x="26" y="40" rx="0.75" style="fill:#c3c4c7;stroke-width:1" />
                    <rect width="22" height="5" x="26" y="51" rx="2" style="fill:#a0a5aa;stroke-width:1" />
                    <rect width="48" height="5" x="60" y="51" rx="2" style="fill:#a0a5aa;stroke-width:1" />
                    <rect width="28" height="5" x="119" y="51" rx="2" style="fill:#a0a5aa;stroke-width:1" />
                    <rect width="38" height="5" x="161" y="51" rx="2" style="fill:#a0a5aa;stroke-width:1" />
                    <rect width="22" height="5" x="26" y="71" rx="2" style="fill:#c3c4c7;stroke-width:1" />
                    <rect width="48" height="5" x="60" y="71" rx="2" style="fill:#c3c4c7;stroke-width:1" />
                    <rect width="28" height="5" x="119" y="71" rx="2" style="fill:#c3c4c7;stroke-width:1" />
                    <rect width="38" height="5" x="161" y="71" rx="2" style="fill:#c3c4c7;stroke-width:1" />
                    <rect width="22" height="5" x="26" y="91" rx="2" style="fill:#c3c4c7;stroke-width:1" />
                    <rect width="48" height="5" x="60" y="91" rx="2" style="fill:#c3c4c7;stroke-width:1" />
                    <rect width="28" height="5" x="119" y="91" rx="2" style="fill:#c3c4c7;stroke-width:1" />
                    <rect width="38" height="5" x="161" y="91" rx="2" style="fill:#c3c4c7;stroke-width:1" />
                </svg>';
        } else {
            $number_per_page = ( ! empty( $attributes['number_per_page'] ) && is_numeric( $attributes['number_per_page'] ) ) ? absint( $attributes['number_per_page'] ) : 10;

            echo '<div class="pms-block-container">' . do_shortcode( '[pms-payment-history number_per_page="' . esc_attr( $number_per_page ) . '"]' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    }
, 10, 2 );

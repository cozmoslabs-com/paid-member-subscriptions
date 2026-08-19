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
register_block_type( __DIR__ . '/build/edit-profile/block.json',
    [
        'render_callback' => function( $attributes, $content ) {
            ob_start();
            do_action( 'pms/edit_profile/render_callback', $attributes, $content );
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
    'pms/edit_profile/render_callback',
    function( $attributes, $content ) {
        if ( isset( $attributes['is_preview'] ) && $attributes['is_preview'] ) {
            echo '
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 230 180"
                    style="width: 100%;"
                >
                    <title>Paid Member Subscriptions Edit Profile Block Preview</title>
                    <rect width="62" height="9" x="28" y="22" rx="3" style="fill:#a0a5aa;stroke-width:1" />
                    <rect width="34" height="5" x="28" y="50" rx="2" style="fill:#a0a5aa;stroke-width:1" />
                    <rect width="174" height="13" x="28" y="59" rx="6" style="fill:#c3c4c7;stroke-width:1" />
                    <rect width="28" height="5" x="28" y="83" rx="2" style="fill:#a0a5aa;stroke-width:1" />
                    <rect width="174" height="13" x="28" y="92" rx="6" style="fill:#c3c4c7;stroke-width:1" />
                    <rect width="36" height="5" x="28" y="116" rx="2" style="fill:#a0a5aa;stroke-width:1" />
                    <rect width="174" height="13" x="28" y="125" rx="6" style="fill:#c3c4c7;stroke-width:1" />
                    <rect width="58" height="15" x="28" y="152" rx="3" style="fill:#a0a5aa;stroke-width:1" />
                </svg>';
        } else {
            echo '<div class="pms-block-container">' . do_shortcode( '[pms-edit-profile]' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    }
, 10, 2 );

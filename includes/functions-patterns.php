<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function pms_patterns_register() {

    register_block_pattern_category( 'pms-patterns', array(
		'label' => __( 'Paid Member Subscriptions', 'paid-member-subscriptions' )
	) );

    register_block_pattern(
        'paid-member-subscriptions/pricing-table',
        array(
            'title'      => __( 'Pricing Table', 'paid-member-subscriptions' ),
            'content'    => pms_patterns_pricing_table(),
            'keywords'   => array( 'pms' ),
            'source'     => 'plugin',
            'categories' => array( 'pms-patterns' ),
        )
    );

}
add_action( 'init', 'pms_patterns_register' );

function pms_patterns_pricing_table(){
    return '<!-- wp:columns {"align":"wide","style":{"spacing":{"margin":{"bottom":"0"}}}} -->
    <div class="wp-block-columns alignwide" style="margin-bottom:0"><!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","right":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30"}},"border":{"color":"#7a838b","width":"2px"}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-column has-border-color" style="border-color:#7a838b;border-width:2px;padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)"><!-- wp:heading {"textAlign":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"500"},"color":{"text":"#7a838b"}}} -->
    <h2 class="wp-block-heading has-text-align-center has-text-color" style="color:#7a838b;font-style:normal;font-weight:500">Silver</h2>
    <!-- /wp:heading -->
    
    <!-- wp:heading {"textAlign":"center","level":3,"style":{"color":{"text":"#7a838b"}}} -->
    <h3 class="wp-block-heading has-text-align-center has-text-color" style="color:#7a838b">29$ / month</h3>
    <!-- /wp:heading -->
    
    <!-- wp:separator {"className":"is-style-dots"} -->
    <hr class="wp-block-separator has-alpha-channel-opacity is-style-dots"/>
    <!-- /wp:separator -->
    
    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center">First featured item<br>Second featured item<br><br><br></p>
    <!-- /wp:paragraph -->
    
    <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center","verticalAlignment":"bottom"}} -->
    <div class="wp-block-buttons"><!-- wp:button {"textAlign":"center","width":75,"style":{"border":{"radius":"0px"}},"className":"is-style-outline"} -->
    <div class="wp-block-button has-custom-width wp-block-button__width-75 is-style-outline"><a class="wp-block-button__link has-text-align-center wp-element-button" href="1" style="border-radius:0px"><strong>Buy now</strong></a></div>
    <!-- /wp:button --></div>
    <!-- /wp:buttons --></div>
    <!-- /wp:column -->
    
    <!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","right":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30"}},"border":{"color":"#d7b045","width":"2px"}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-column has-border-color" style="border-color:#d7b045;border-width:2px;padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)"><!-- wp:heading {"textAlign":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"500"},"color":{"text":"#d7b045"}}} -->
    <h2 class="wp-block-heading has-text-align-center has-text-color" style="color:#d7b045;font-style:normal;font-weight:500">Gold</h2>
    <!-- /wp:heading -->
    
    <!-- wp:heading {"textAlign":"center","level":3,"style":{"color":{"text":"#d7b045"}}} -->
    <h3 class="wp-block-heading has-text-align-center has-text-color" style="color:#d7b045">49$ / month</h3>
    <!-- /wp:heading -->
    
    <!-- wp:separator {"className":"is-style-dots"} -->
    <hr class="wp-block-separator has-alpha-channel-opacity is-style-dots"/>
    <!-- /wp:separator -->
    
    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center">First featured item<br>Second featured item<br>Third featured item<br><br></p>
    <!-- /wp:paragraph -->
    
    <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center","verticalAlignment":"bottom"}} -->
    <div class="wp-block-buttons"><!-- wp:button {"textAlign":"center","width":75,"style":{"border":{"radius":"0px"}},"className":"is-style-outline"} -->
    <div class="wp-block-button has-custom-width wp-block-button__width-75 is-style-outline"><a class="wp-block-button__link has-text-align-center wp-element-button" href="2" style="border-radius:0px"><strong>Buy now</strong></a></div>
    <!-- /wp:button --></div>
    <!-- /wp:buttons --></div>
    <!-- /wp:column -->
    
    <!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","right":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30"}},"border":{"color":"#6eb096","width":"2px"}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-column has-border-color" style="border-color:#6eb096;border-width:2px;padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)"><!-- wp:heading {"textAlign":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"500"},"color":{"text":"#6eb096"}}} -->
    <h2 class="wp-block-heading has-text-align-center has-text-color" style="color:#6eb096;font-style:normal;font-weight:500">Platinum</h2>
    <!-- /wp:heading -->
    
    <!-- wp:heading {"textAlign":"center","level":3,"style":{"color":{"text":"#6eb096"}}} -->
    <h3 class="wp-block-heading has-text-align-center has-text-color" style="color:#6eb096">89$ / month</h3>
    <!-- /wp:heading -->
    
    <!-- wp:separator {"className":"is-style-dots"} -->
    <hr class="wp-block-separator has-alpha-channel-opacity is-style-dots"/>
    <!-- /wp:separator -->
    
    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center">First featured item<br>Second featured item<br>Third featured item<br>Fourth featured item</p>
    <!-- /wp:paragraph -->
    
    <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
    <div class="wp-block-buttons"><!-- wp:button {"textAlign":"center","width":75,"style":{"border":{"radius":"0px"}},"className":"is-style-outline"} -->
    <div class="wp-block-button has-custom-width wp-block-button__width-75 is-style-outline"><a class="wp-block-button__link has-text-align-center wp-element-button" href="3" style="border-radius:0px"><strong>Buy now</strong></a></div>
    <!-- /wp:button --></div>
    <!-- /wp:buttons --></div>
    <!-- /wp:column --></div>
    <!-- /wp:columns -->';
}
function pms_patterns_pricing_table_two_columns(){
    return '<!-- wp:columns {"align":"wide","style":{"spacing":{"margin":{"bottom":"0"}}}} -->
    <div class="wp-block-columns alignwide" style="margin-bottom:0"><!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","right":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30"}},"border":{"color":"#7a838b","width":"2px"}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-column has-border-color" style="border-color:#7a838b;border-width:2px;padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)"><!-- wp:heading {"textAlign":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"500"},"color":{"text":"#7a838b"}}} -->
    <h2 class="wp-block-heading has-text-align-center has-text-color" style="color:#7a838b;font-style:normal;font-weight:500">Silver</h2>
    <!-- /wp:heading -->
    
    <!-- wp:heading {"textAlign":"center","level":3,"style":{"color":{"text":"#7a838b"}}} -->
    <h3 class="wp-block-heading has-text-align-center has-text-color" style="color:#7a838b">29$ / month</h3>
    <!-- /wp:heading -->
    
    <!-- wp:separator {"className":"is-style-dots"} -->
    <hr class="wp-block-separator has-alpha-channel-opacity is-style-dots"/>
    <!-- /wp:separator -->
    
    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center">First featured item<br>Second featured item<br><br><br></p>
    <!-- /wp:paragraph -->
    
    <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center","verticalAlignment":"bottom"}} -->
    <div class="wp-block-buttons"><!-- wp:button {"textAlign":"center","width":75,"style":{"border":{"radius":"0px"}},"className":"is-style-outline"} -->
    <div class="wp-block-button has-custom-width wp-block-button__width-75 is-style-outline"><a class="wp-block-button__link has-text-align-center wp-element-button" href="1" style="border-radius:0px"><strong>Buy now</strong></a></div>
    <!-- /wp:button --></div>
    <!-- /wp:buttons --></div>
    <!-- /wp:column -->
    
    <!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","right":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30"}},"border":{"color":"#d7b045","width":"2px"}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-column has-border-color" style="border-color:#d7b045;border-width:2px;padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)"><!-- wp:heading {"textAlign":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"500"},"color":{"text":"#d7b045"}}} -->
    <h2 class="wp-block-heading has-text-align-center has-text-color" style="color:#d7b045;font-style:normal;font-weight:500">Gold</h2>
    <!-- /wp:heading -->
    
    <!-- wp:heading {"textAlign":"center","level":3,"style":{"color":{"text":"#d7b045"}}} -->
    <h3 class="wp-block-heading has-text-align-center has-text-color" style="color:#d7b045">49$ / month</h3>
    <!-- /wp:heading -->
    
    <!-- wp:separator {"className":"is-style-dots"} -->
    <hr class="wp-block-separator has-alpha-channel-opacity is-style-dots"/>
    <!-- /wp:separator -->
    
    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center">First featured item<br>Second featured item<br>Third featured item<br><br></p>
    <!-- /wp:paragraph -->
    
    <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center","verticalAlignment":"bottom"}} -->
    <div class="wp-block-buttons"><!-- wp:button {"textAlign":"center","width":75,"style":{"border":{"radius":"0px"}},"className":"is-style-outline"} -->
    <div class="wp-block-button has-custom-width wp-block-button__width-75 is-style-outline"><a class="wp-block-button__link has-text-align-center wp-element-button" href="2" style="border-radius:0px"><strong>Buy now</strong></a></div>
    <!-- /wp:button --></div>
    <!-- /wp:buttons --></div>
    <!-- /wp:column -->';

}

function pms_patterns_pricing_table_one_column(){
    return '<!-- wp:columns {"align":"wide","style":{"spacing":{"margin":{"bottom":"0"}}}} -->
    <div class="wp-block-columns alignwide" style="margin-bottom:0"><!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","right":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30"}},"border":{"color":"#7a838b","width":"2px"}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-column has-border-color" style="border-color:#7a838b;border-width:2px;padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)"><!-- wp:heading {"textAlign":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"500"},"color":{"text":"#7a838b"}}} -->
    <h2 class="wp-block-heading has-text-align-center has-text-color" style="color:#7a838b;font-style:normal;font-weight:500">Silver</h2>
    <!-- /wp:heading -->
    
    <!-- wp:heading {"textAlign":"center","level":3,"style":{"color":{"text":"#7a838b"}}} -->
    <h3 class="wp-block-heading has-text-align-center has-text-color" style="color:#7a838b">29$ / month</h3>
    <!-- /wp:heading -->
    
    <!-- wp:separator {"className":"is-style-dots"} -->
    <hr class="wp-block-separator has-alpha-channel-opacity is-style-dots"/>
    <!-- /wp:separator -->
    
    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center">First featured item<br>Second featured item<br><br><br></p>
    <!-- /wp:paragraph -->
    
    <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center","verticalAlignment":"bottom"}} -->
    <div class="wp-block-buttons"><!-- wp:button {"textAlign":"center","width":75,"style":{"border":{"radius":"0px"}},"className":"is-style-outline"} -->
    <div class="wp-block-button has-custom-width wp-block-button__width-75 is-style-outline"><a class="wp-block-button__link has-text-align-center wp-element-button" href="1" style="border-radius:0px"><strong>Buy now</strong></a></div>
    <!-- /wp:button --></div>
    <!-- /wp:buttons --></div>
    <!-- /wp:column -->';
}
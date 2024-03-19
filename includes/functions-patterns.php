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
    <div class="wp-block-columns alignwide pms-pt-gap" style="margin-bottom:0"><!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","right":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30"}},"border":{"color":"#7a838b","width":"2px"}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-column has-border-color pms-pt-border-1 pms-pt-card-1" style="border-color:#7a838b;border-width:2px;padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)"><!-- wp:heading {"textAlign":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"500"},"color":{"text":"#7a838b"}}} -->
    <h2 class="pms-pt-text-title wp-block-heading has-text-align-center has-text-color" style="color:#7a838b;font-style:normal;font-weight:500">Silver</h2>
    <!-- /wp:heading -->
    
    <!-- wp:heading {"textAlign":"center","level":3,"style":{"color":{"text":"#7a838b"}}} -->
    <h3 class="pms-pt-text-price wp-block-heading has-text-align-center has-text-color" style="color:#7a838b">29$ / month</h3>
    <!-- /wp:heading -->
    
    <!-- wp:paragraph {"align":"center"} -->
     <span class="pms-pt-text-price pms-pt-text-duration">Free trial 1</span> 
    <!-- /wp:paragraph -->
    
    <!-- wp:paragraph {"align":"center"} -->
     <span class="pms-pt-text-price pms-pt-text-duration">Sign-up fee 1</span> 
    <!-- /wp:paragraph -->
    
    <!-- wp:paragraph {"align":"left"} -->
     <span class="pms-pt-text-description">The description of the tier list will go here, it should be concise and impactful.</span> 
    <!-- /wp:paragraph -->
    
    <!-- wp:separator {"className":"is-style-dots"} -->
    <hr class="pms-pt-separator wp-block-separator has-alpha-channel-opacity is-style-dots"/>
    <!-- /wp:separator -->

    <!-- wp:list -->
    <ul class="pms-pt-list"><!-- wp:list-item -->
    <li>Amazing feature one</li>
    <!-- /wp:list-item -->
    
    <!-- wp:list-item -->
    <li>Wonderful feature two</li>
    <!-- /wp:list-item -->
    
    <!-- wp:list-item -->
    <li>Priceless feature three</li>
    <!-- /wp:list-item -->
    
    <!-- wp:list-item -->
    <li>Splendid feature four</li>
    <!-- /wp:list-item --></ul>
    <!-- /wp:list -->
    
    <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center","verticalAlignment":"bottom"}} -->
    <div class="wp-block-buttons pms-pt-button-column-1"><!-- wp:button {"textAlign":"center","width":75,"style":{"border":{"radius":"0px"}},"className":"is-style-outline"} -->
    <div class="wp-block-button has-custom-width wp-block-button__width-75 is-style-outline"><a class="wp-block-button__link has-text-align-center wp-element-button" href="1" style="border-radius:0px"><strong>Buy Now</strong></a></div>
    <!-- /wp:button --></div>
    <!-- /wp:buttons --></div>
    <!-- /wp:column -->
    
    <!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","right":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30"}},"border":{"color":"#d7b045","width":"2px"}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-column has-border-color pms-pt-border-2 pms-pt-card-2" style="border-color:#d7b045;border-width:2px;padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)"><!-- wp:heading {"textAlign":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"500"},"color":{"text":"#d7b045"}}} -->
    <h2 class="pms-pt-text-title wp-block-heading has-text-align-center has-text-color" style="color:#d7b045;font-style:normal;font-weight:500">Gold</h2>
    <!-- /wp:heading -->
    
    <!-- wp:heading {"textAlign":"center","level":3,"style":{"color":{"text":"#d7b045"}}} -->
    <h3 class="pms-pt-text-price wp-block-heading has-text-align-center has-text-color" style="color:#d7b045">49$ / month</h3>
    <!-- /wp:heading -->
    
    <!-- wp:paragraph {"align":"center"} -->
     <span class="pms-pt-text-price pms-pt-text-duration">Free trial 2</span> 
    <!-- /wp:paragraph -->
    
    <!-- wp:paragraph {"align":"center"} -->
     <span class="pms-pt-text-price pms-pt-text-duration">Sign-up fee 2</span> 
    <!-- /wp:paragraph -->
    
    <!-- wp:paragraph {"align":"left"} -->
     <span class="pms-pt-text-description">The description of the tier list will go here, it should be concise and impactful.</span> 
    <!-- /wp:paragraph -->
    
    <!-- wp:separator {"className":"is-style-dots"} -->
    <hr class="pms-pt-separator wp-block-separator has-alpha-channel-opacity is-style-dots"/>
    <!-- /wp:separator -->
    
    <!-- wp:paragraph {"align":"left"} -->
     <span class="pms-pt-text-description">Everything in the Gold plan, plus</span> 
    <!-- /wp:paragraph -->
    
    <!-- wp:list -->
    <ul class="pms-pt-list pms-pt-list-extra"><!-- wp:list-item -->
    <li>Amazing feature one</li>
    <!-- /wp:list-item -->
    
    <!-- wp:list-item -->
    <li>Wonderful feature two</li>
    <!-- /wp:list-item -->
    
    <!-- wp:list-item -->
    <li>Priceless feature three</li>
    <!-- /wp:list-item -->
    
    <!-- wp:list-item -->
    <li>Splendid feature four</li>
    <!-- /wp:list-item -->
    
    <!-- wp:list-item -->
    <li>Delightful feature five</li>
    <!-- /wp:list-item -->
    
    <!-- wp:list-item -->
    <li>Stunning feature five</li>
    <!-- /wp:list-item --></ul>  
    <!-- /wp:list -->
    
    <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center","verticalAlignment":"bottom"}} -->
    <div class="wp-block-buttons pms-pt-button-column-2"><!-- wp:button {"textAlign":"center","width":75,"style":{"border":{"radius":"0px"}},"className":"is-style-outline"} -->
    <div class="wp-block-button has-custom-width wp-block-button__width-75 is-style-outline"><a class="wp-block-button__link has-text-align-center wp-element-button" href="2" style="border-radius:0px"><strong>Buy Now</strong></a></div>
    <!-- /wp:button --></div>
    <!-- /wp:buttons --></div>
    <!-- /wp:column -->
    
    <!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","right":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30"}},"border":{"color":"#6eb096","width":"2px"}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-column has-border-color pms-pt-border-1 pms-pt-card-3" style="border-color:#6eb096;border-width:2px;padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)"><!-- wp:heading {"textAlign":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"500"},"color":{"text":"#6eb096"}}} -->
    <h2 class="pms-pt-text-title wp-block-heading has-text-align-center has-text-color" style="color:#6eb096;font-style:normal;font-weight:500">Platinum</h2>
    <!-- /wp:heading -->
    
    <!-- wp:heading {"textAlign":"center","level":3,"style":{"color":{"text":"#6eb096"}}} -->
    <h3 class="pms-pt-text-price wp-block-heading has-text-align-center has-text-color" style="color:#6eb096">89$ / month</h3>
    <!-- /wp:heading -->
    
    <!-- wp:paragraph {"align":"center"} -->
     <span class="pms-pt-text-price pms-pt-text-duration">Free trial 3</span> 
    <!-- /wp:paragraph -->
    
    <!-- wp:paragraph {"align":"center"} -->
     <span class="pms-pt-text-price pms-pt-text-duration">Sign-up fee 3</span> 
    <!-- /wp:paragraph -->
    
    <!-- wp:paragraph {"align":"left"} -->
     <span class="pms-pt-text-description">The description of the tier list will go here, it should be concise and impactful.</span> 
    <!-- /wp:paragraph -->
    
    <!-- wp:separator {"className":"is-style-dots"} -->
    <hr class="pms-pt-separator wp-block-separator has-alpha-channel-opacity is-style-dots"/>
    <!-- /wp:separator -->
    
    <!-- wp:paragraph {"align":"left"} -->
     <span class="pms-pt-text-description">Everything in the Platinum plan, plus</span> 
    <!-- /wp:paragraph -->
   
    <!-- wp:list -->
    <ul class="pms-pt-list"><!-- wp:list-item -->
    <li>Amazing feature one</li>
    <!-- /wp:list-item -->
    
    <!-- wp:list-item -->
    <li>Wonderful feature two</li>
    <!-- /wp:list-item -->
    
    <!-- wp:list-item -->
    <li>Priceless feature three</li>
    <!-- /wp:list-item -->
    
    <!-- wp:list-item -->
    <li>Splendid feature four</li>
    <!-- /wp:list-item --></ul>
    <!-- /wp:list -->
    
    <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
    <div class="wp-block-buttons pms-pt-button-column-1"><!-- wp:button {"textAlign":"center","width":75,"style":{"border":{"radius":"0px"}},"className":"is-style-outline"} -->
    <div class="wp-block-button has-custom-width wp-block-button__width-75 is-style-outline"><a class="wp-block-button__link has-text-align-center wp-element-button" href="3" style="border-radius:0px"><strong>Buy Now</strong></a></div>
    <!-- /wp:button --></div>
    <!-- /wp:buttons --></div>
    <!-- /wp:column --></div>
    <!-- /wp:columns -->';
}
function pms_patterns_pricing_table_two_columns(){
    return '<!-- wp:columns {"align":"wide","style":{"spacing":{"margin":{"bottom":"0"}}}} -->
    <div class="wp-block-columns alignwide pms-pt-gap" style="margin-bottom:0"><!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","right":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30"}},"border":{"color":"#7a838b","width":"2px"}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-column has-border-color pms-pt-border-1 pms-pt-card-1" style="border-color:#7a838b;border-width:2px;padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)"><!-- wp:heading {"textAlign":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"500"},"color":{"text":"#7a838b"}}} -->
    <h2 class="pms-pt-text-title wp-block-heading has-text-align-center has-text-color" style="color:#7a838b;font-style:normal;font-weight:500">Silver</h2>
    <!-- /wp:heading -->
    
    <!-- wp:heading {"textAlign":"center","level":3,"style":{"color":{"text":"#7a838b"}}} -->
    <h3 class="pms-pt-text-price wp-block-heading has-text-align-center has-text-color" style="color:#7a838b">29$ / month</h3>
    <!-- /wp:heading -->
    
    <!-- wp:paragraph {"align":"center"} -->
     <span class="pms-pt-text-price pms-pt-text-duration">Free trial 1</span> 
    <!-- /wp:paragraph -->
    
    <!-- wp:paragraph {"align":"center"} -->
     <span class="pms-pt-text-price pms-pt-text-duration">Sign-up fee 1</span> 
    <!-- /wp:paragraph -->
    
    <!-- wp:paragraph {"align":"left"} -->
     <span class="pms-pt-text-description">The description of the tier list will go here, it should be concise and impactful.</span> 
    <!-- /wp:paragraph -->
    
    <!-- wp:separator {"className":"is-style-dots"} -->
    <hr class="pms-pt-separator wp-block-separator has-alpha-channel-opacity is-style-dots"/>
    <!-- /wp:separator -->
 
     <!-- wp:list -->
    <ul class="pms-pt-list"><!-- wp:list-item -->
    <li>Amazing feature one</li>
    <!-- /wp:list-item -->
    
    <!-- wp:list-item -->
    <li>Wonderful feature two</li>
    <!-- /wp:list-item -->
    
    <!-- wp:list-item -->
    <li>Priceless feature three</li>
    <!-- /wp:list-item -->
    
    <!-- wp:list-item -->
    <li>Splendid feature four</li>
    <!-- /wp:list-item --></ul>
    <!-- /wp:list -->
    
    <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center","verticalAlignment":"bottom"}} -->
    <div class="wp-block-buttons pms-pt-button-column-1"><!-- wp:button {"textAlign":"center","width":75,"style":{"border":{"radius":"0px"}},"className":"is-style-outline"} -->
    <div class="wp-block-button has-custom-width wp-block-button__width-75 is-style-outline"><a class="wp-block-button__link has-text-align-center wp-element-button" href="1" style="border-radius:0px"><strong>Buy Now</strong></a></div>
    <!-- /wp:button --></div>
    <!-- /wp:buttons --></div>
    <!-- /wp:column -->
    
    <!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","right":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30"}},"border":{"color":"#d7b045","width":"2px"}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-column has-border-color pms-pt-border-2 pms-pt-card-2" style="border-color:#d7b045;border-width:2px;padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)"><!-- wp:heading {"textAlign":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"500"},"color":{"text":"#d7b045"}}} -->
    <h2 class="pms-pt-text-title wp-block-heading has-text-align-center has-text-color" style="color:#d7b045;font-style:normal;font-weight:500">Gold</h2>
    <!-- /wp:heading -->
    
    <!-- wp:heading {"textAlign":"center","level":3,"style":{"color":{"text":"#d7b045"}}} -->
    <h3 class="pms-pt-text-price wp-block-heading has-text-align-center has-text-color" style="color:#d7b045">49$ / month</h3>
    <!-- /wp:heading -->
    
    <!-- wp:paragraph {"align":"center"} -->
     <span class="pms-pt-text-price pms-pt-text-duration">Free trial 2</span> 
    <!-- /wp:paragraph -->
    
    <!-- wp:paragraph {"align":"center"} -->
     <span class="pms-pt-text-price pms-pt-text-duration">Sign-up fee 2</span> 
    <!-- /wp:paragraph -->
    
    <!-- wp:paragraph {"align":"left"} -->
     <span class="pms-pt-text-description">The description of the tier list will go here, it should be concise and impactful.</span> 
    <!-- /wp:paragraph -->
    
    <!-- wp:separator {"className":"is-style-dots"} -->
    <hr class="pms-pt-separator wp-block-separator has-alpha-channel-opacity is-style-dots"/>
    <!-- /wp:separator -->
    
    <!-- wp:paragraph {"align":"left"} -->
     <span class="pms-pt-text-description">Everything in the Gold plan, plus</span> 
    <!-- /wp:paragraph -->
  
    <!-- wp:list -->
    <ul class="pms-pt-list pms-pt-list-extra"><!-- wp:list-item -->
    <li>Amazing feature one</li>
    <!-- /wp:list-item -->
    
    <!-- wp:list-item -->
    <li>Wonderful feature two</li>
    <!-- /wp:list-item -->
    
    <!-- wp:list-item -->
    <li>Priceless feature three</li>
    <!-- /wp:list-item -->
    
    <!-- wp:list-item -->
    <li>Splendid feature four</li>
    <!-- /wp:list-item -->
    
    <!-- wp:list-item -->
    <li>Delightful feature five</li>
    <!-- /wp:list-item --></ul> 
    <!-- /wp:list -->
    
    <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center","verticalAlignment":"bottom"}} -->
    <div class="wp-block-buttons pms-pt-button-column-2"><!-- wp:button {"textAlign":"center","width":75,"style":{"border":{"radius":"0px"}},"className":"is-style-outline"} -->
    <div class="wp-block-button has-custom-width wp-block-button__width-75 is-style-outline"><a class="wp-block-button__link has-text-align-center wp-element-button" href="2" style="border-radius:0px"><strong>Buy Now</strong></a></div>
    <!-- /wp:button --></div>
    <!-- /wp:buttons --></div>
    <!-- /wp:column --></div>
    <!-- /wp:columns -->';

}

function pms_patterns_pricing_table_one_column(){
    return '<!-- wp:columns {"align":"wide","style":{"spacing":{"margin":{"bottom":"0"}}}} -->
    <div class="wp-block-columns alignwide pms-pt-gap" style="margin-bottom:0"><!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","right":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30"}},"border":{"color":"#7a838b","width":"2px"}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-column has-border-color pms-pt-border-1 pms-pt-card-1" style="border-color:#7a838b;border-width:2px;padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)"><!-- wp:heading {"textAlign":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"500"},"color":{"text":"#7a838b"}}} -->
    <h2 class="pms-pt-text-title wp-block-heading has-text-align-center has-text-color" style="color:#7a838b;font-style:normal;font-weight:500">Silver</h2>
    <!-- /wp:heading -->
    
    <!-- wp:heading {"textAlign":"center","level":3,"style":{"color":{"text":"#7a838b"}}} -->
    <h3 class="pms-pt-text-price wp-block-heading has-text-align-center has-text-color" style="color:#7a838b">29$ / month</h3>
    <!-- /wp:heading -->
    
    <!-- wp:paragraph {"align":"center"} -->
     <span class="pms-pt-text-price pms-pt-text-duration">Free trial 1</span> 
    <!-- /wp:paragraph -->
    
    <!-- wp:paragraph {"align":"center"} -->
     <span class="pms-pt-text-price pms-pt-text-duration">Sign-up fee 1</span> 
    <!-- /wp:paragraph -->
    
    <!-- wp:paragraph {"align":"left"} -->
     <span class="pms-pt-text-description">The description of the tier list will go here, it should be concise and impactful.</span> 
    <!-- /wp:paragraph -->
    
    <!-- wp:separator {"className":"is-style-dots"} -->
    <hr class="pms-pt-separator wp-block-separator has-alpha-channel-opacity is-style-dots"/>
    <!-- /wp:separator -->
    
     <!-- wp:list -->
    <ul class="pms-pt-list"><!-- wp:list-item -->
    <li>Amazing feature one</li>
    <!-- /wp:list-item -->
    
    <!-- wp:list-item -->
    <li>Wonderful feature two</li>
    <!-- /wp:list-item -->
    
    <!-- wp:list-item -->
    <li>Priceless feature three</li>
    <!-- /wp:list-item -->
    
    <!-- wp:list-item -->
    <li>Splendid feature four</li>
    <!-- /wp:list-item --></ul>
    <!-- /wp:list -->
    
    <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center","verticalAlignment":"bottom"}} -->
    <div class="wp-block-buttons pms-pt-button-column-1"><!-- wp:button {"textAlign":"center","width":75,"style":{"border":{"radius":"0px"}},"className":"is-style-outline"} -->
    <div class="wp-block-button has-custom-width wp-block-button__width-75 is-style-outline"><a class="wp-block-button__link has-text-align-center wp-element-button" href="1" style="border-radius:0px"><strong>Buy Now</strong></a></div>
    <!-- /wp:button --></div>
    <!-- /wp:buttons --></div>
    <!-- /wp:column --></div>
    <!-- /wp:columns -->';
}
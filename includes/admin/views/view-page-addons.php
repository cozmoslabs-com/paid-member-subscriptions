<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * HTML Output for the Add-ons page
 */
?>

<div id="pms-addons-page" class="wrap">

    <?php
    //initialize the object
    $pms_addons_listing = new PMS_Addons_List_Table();
    $pms_addons_listing->images_folder = PMS_PLUGIN_DIR_URL.'assets/images/addons/';
    $pms_addons_listing->text_domain = 'paid-member-subscriptions';
    $pms_addons_listing->header = array( 'title' => __('Addons', 'paid-member-subscriptions' ) );
    if( defined( 'PAID_MEMBER_SUBSCRIPTIONS' ) )
        $pms_addons_listing->current_version = PAID_MEMBER_SUBSCRIPTIONS;
    else
        $pms_addons_listing->current_version = 'Paid Member Subscriptions';//in free version we do not define the constant as free version needs to be active always
    $pms_addons_listing->tooltip_header = __( 'Paid Member Subscriptions Add-ons', 'paid-member-subscriptions' );
    $pms_addons_listing->tooltip_content = sprintf( __( 'You must first purchase this version to have access to the addon %1$shere%2$s', 'paid-member-subscriptions' ), '<a target="_blank" href="'. 'https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PMSFree#pricing'.'">', '</a>' );


    //Add Basic section
    $pms_addons_listing->section_header      = array( 'title' => __('Basic Add-ons', 'paid-member-subscriptions' ), 'description' => __( 'These addons extend Paid Member Subscriptions and are available in the Basic, Pro and Agency plans.', 'paid-member-subscriptions' )  );
    $pms_addons_listing->section_header_free = array( 'title' => __('Basic Add-ons', 'paid-member-subscriptions' ), 'description' => sprintf( __( 'Get access to these Add-ons with a Basic, Pro or Agency license. %sBuy now%s', 'paid-member-subscriptions' ), '<a href="https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page-notice&utm_campaign=PMS#pricing" taget="_blank">', '</a>' )  );
    $pms_addons_listing->section_versions    = array( 'Paid Member Subscriptions - Basic', 'Paid Member Subscriptions - Pro', 'Paid Member Subscriptions - Agency', 'Paid Member Subscriptions - Dev', 'Paid Member Subscriptions - Unlimited', 'Paid Member Subscriptions Basic', 'Paid Member Subscriptions Pro', 'Paid Member Subscriptions Agency', 'Paid Member Subscriptions Dev', 'Paid Member Subscriptions Unlimited' );
    $pms_addons_listing->items               = array(
        array(  'slug' => 'pms-add-on-learndash/index.php',
            'type'        => 'add-on',
            'name'        => __( 'LearnDash', 'paid-member-subscriptions' ),
            'description' => __( 'Generate revenue from your LMS website by selling access to courses through single or recurring payments. Restrict content of courses, lessons and quizzes to members.', 'paid-member-subscriptions' ),
            'icon'        => 'pms-add-on-learndash.png',
            'doc_url'     => 'https://www.cozmoslabs.com/docs/paid-member-subscriptions/learndash/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PMS',
        ),
        array(  'slug' => 'pms-add-on-global-content-restriction/index.php',
            'type'        => 'add-on',
            'name'        => __( 'Global Content Restriction', 'paid-member-subscriptions' ),
            'description' => __( 'Easy way to add global content restriction rules to subscription plans, based on post type, taxonomy and terms.', 'paid-member-subscriptions' ),
            'icon'        => 'pms-add-on-global-content-restriction-logo.png',
            'doc_url'     => 'https://www.cozmoslabs.com/docs/paid-member-subscriptions/add-ons/global-content-restriction/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PMS',
        ),
        array(  'slug' => 'pms-add-on-email-reminders/index.php',
            'type'        => 'add-on',
            'name'        => __( 'Email Reminders', 'paid-member-subscriptions' ),
            'description' => __( 'Create multiple automated email reminders that are sent to members before or after certain events take place (subscription expires, subscription activated etc.)', 'paid-member-subscriptions' ),
            'icon'        => 'pms-add-on-email-reminders-logo.png',
            'doc_url'     => 'https://www.cozmoslabs.com/docs/paid-member-subscriptions/add-ons/email-reminders/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PMS',
        ),
        array(  'slug' => 'pms-add-on-pay-what-you-want/index.php',
            'type'        => 'add-on',
            'name'        => __( 'Pay What You Want', 'paid-member-subscriptions' ),
            'description' => __( 'Let subscribers pay what they want by offering a variable pricing option when they purchase a membership plan.', 'paid-member-subscriptions' ),
            'icon'        => 'pms-add-on-pay-what-you-want.png',
            'doc_url'     => 'https://www.cozmoslabs.com/docs/paid-member-subscriptions/add-ons/pay-what-you-want-variable-pricing/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PMS',
        ),
        array(  'slug' => 'pms-add-on-bbpress/index.php',
            'type'        => 'add-on',
            'name'        => __( 'bbPress', 'paid-member-subscriptions' ),
            'description' => __( 'Integrate Paid Member Subscriptions with the popular forums plugin, bbPress. Restrict your forums and topics and allow only premium members to have access to them.', 'paid-member-subscriptions' ),
            'icon'        => 'pms-add-on-bbpress-logo.jpg',
            'doc_url'     => 'https://www.cozmoslabs.com/docs/paid-member-subscriptions/add-ons/bbpress/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PMS',
        ),
        array(  'slug' => 'pms-add-on-member-subscription-fixed-period/index.php',
            'type'        => 'add-on',
            'name'        => __( 'Fixed Period Membership', 'paid-member-subscriptions' ),
            'description' => __( 'The Fixed Period Membership Add-On allows your Subscriptions to end at a specific date, no matter when a client subscribes to it.', 'paid-member-subscriptions' ),
            'icon'        => 'pms-add-on-fixed-period-logo.png',
            'doc_url'     => 'https://www.cozmoslabs.com/docs/paid-member-subscriptions/add-ons/fixed-period-membership/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PMS',
        ),
        array(  'slug' => 'pms-add-on-navigation-menu-filtering/index.php',
            'type'        => 'add-on',
            'name'        => __( 'Navigation Menu Filtering', 'paid-member-subscriptions' ),
            'description' => __( 'Dynamically display menu items based on logged-in status as well as selected subscription plans.', 'paid-member-subscriptions' ),
            'icon'        => 'pms-add-on-navigation-menu-filter-logo.png',
            'doc_url'     => 'https://www.cozmoslabs.com/docs/paid-member-subscriptions/add-ons/navigation-menu-filtering/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PMS',
        ),
    );
    $pms_addons_listing->add_section();

    //Add Pro Section
    $pms_addons_listing->section_header = array( 'title' => __('Pro Add-ons', 'paid-member-subscriptions' ), 'description' => __('These addons extend Paid Member Subscriptions and are available in the Pro and Agency plans.', 'paid-member-subscriptions')  );
    $pms_addons_listing->section_header_free = array( 'title' => __('Pro Add-ons', 'paid-member-subscriptions' ), 'description' => sprintf( __( 'Get access to these Add-ons with a Pro or Agency license. %sBuy now%s', 'paid-member-subscriptions' ), '<a href="https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page-notice&utm_campaign=PMS#pricing" taget="_blank">', '</a>' )  );
    $pms_addons_listing->section_versions = array( 'Paid Member Subscriptions - Pro', 'Paid Member Subscriptions - Agency', 'Paid Member Subscriptions - Dev', 'Paid Member Subscriptions - Unlimited', 'Paid Member Subscriptions Pro', 'Paid Member Subscriptions Agency', 'Paid Member Subscriptions Dev', 'Paid Member Subscriptions Unlimited' );
    $pms_addons_listing->items = array(
        array(  'slug' => 'pms-add-on-multiple-currencies/index.php',
            'type'        => 'add-on',
            'name'        => __( 'Multiple Currencies', 'paid-member-subscriptions' ),
            'description' => __( 'Enable visitors to pay in their local currency, either through automatic location detection or by manually selecting their preferred currency.', 'paid-member-subscriptions' ),
            'icon'        => 'pms-add-on-multiple-currencies-logo.png',
            'doc_url'     => 'https://www.cozmoslabs.com/docs/paid-member-subscriptions/add-ons/multiple-currencies/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PMS',
        ),
        array(  'slug' => 'pms-add-on-files-restriction/index.php',
            'type'        => 'add-on',
            'name'        => __( 'Files Restriction', 'paid-member-subscriptions' ),
            'description' => __( 'Restrict direct access to media files based on subscription plans making sure only paying members can view them.', 'paid-member-subscriptions' ),
            'icon'        => 'pms-add-on-pro-files-restriction-logo.png',
            'doc_url'     => 'https://www.cozmoslabs.com/docs/paid-member-subscriptions/add-ons/files-restriction/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PMS',
        ),
        array(  'slug' => 'pms-add-on-pro-rate/index.php',
            'type'        => 'add-on',
            'name'        => __( 'Pro-Rate', 'paid-member-subscriptions' ),
            'description' => __( 'Pro-rate subscription plan Upgrades and Downgrades, offering users a discount based on the remaining time for the current subscription.', 'paid-member-subscriptions' ),
            'icon'        => 'pms-add-on-pro-rate-logo.png',
            'doc_url'     => 'https://www.cozmoslabs.com/docs/paid-member-subscriptions/add-ons/pro-rate/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PMS',
        ),
        array(  'slug' => 'pms-add-on-content-dripping/index.php',
            'type'        => 'add-on',
            'name'        => __( 'Content Dripping', 'paid-member-subscriptions' ),
            'description' => __( 'Create schedules for your content, making posts or categories available for your members only after a certain time has passed since they signed up for a subscription plan.', 'paid-member-subscriptions' ),
            'icon'        => 'pms-add-on-content-dripping-logo.png',
            'doc_url'     => 'https://www.cozmoslabs.com/docs/paid-member-subscriptions/add-ons/content-dripping/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PMS',
        ),
        array(  'slug' => 'pms-add-on-group-memberships/index.php',
            'type'        => 'add-on',
            'name'        => __( 'Group Memberships', 'paid-member-subscriptions' ),
            'description' => __( 'Sell umbrella memberships that contain multiple member seats but are managed and purchased by a single account.', 'paid-member-subscriptions' ),
            'icon'        => 'pms-add-on-group-memberships-logo.png',
            'doc_url'     => 'https://www.cozmoslabs.com/docs/paid-member-subscriptions/add-ons/group-memberships/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PMS',
        ),
        array(  'slug' => 'pms-add-on-invoices/index.php',
            'type'        => 'add-on',
            'name'        => __( 'Invoices', 'paid-member-subscriptions' ),
            'description' => __( 'Automatically generate PDF invoices for each subscription payment using the new Invoices add-on.', 'paid-member-subscriptions' ),
            'icon'        => 'pms-add-on-invoices-logo.png',
            'doc_url'     => 'https://www.cozmoslabs.com/docs/paid-member-subscriptions/add-ons/invoices/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PMS',
        ),
        array(  'slug' => 'pms-add-on-multiple-subscriptions-per-user/index.php',
            'type'        => 'add-on',
            'name'        => __( 'Multiple Subscriptions Per User', 'paid-member-subscriptions' ),
            'description' => __( 'Setup multiple subscription level blocks and allow members to sign up for more than one subscription plan (one per block).', 'paid-member-subscriptions' ),
            'icon'        => 'pms-add-on-multiple-subscriptions-per-users-logo.png',
            'doc_url'     => 'https://www.cozmoslabs.com/docs/paid-member-subscriptions/add-ons/multiple-subscriptions-per-user/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PMS',
        ),
        array(  'slug' => 'pms-add-on-paypal-express-pro/index.php',
            'type'        => 'add-on',
            'name'        => __( 'PayPal Express (deprecated)', 'paid-member-subscriptions' ),
            'description' => __( 'Old PayPal Express implementation not available for new users.', 'paid-member-subscriptions' ),
            'icon'        => 'pms-add-on-paypal-express-logo.png',
            'doc_url'     => 'https://www.cozmoslabs.com/docs/paid-member-subscriptions/add-ons/paypal-pro-and-express-checkout/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PMS',
        ),
        array(  'slug' => 'pms-add-on-stripe/index.php',
            'type'        => 'add-on',
            'name'        => __( 'Stripe (deprecated)', 'paid-member-subscriptions' ),
            'description' => __( 'Old Stripe implementation not available for new users.', 'paid-member-subscriptions' ),
            'icon'        => 'pms-add-on-stripe-logo.png',
            'doc_url'     => 'https://www.cozmoslabs.com/docs/paid-member-subscriptions/add-ons/stripe-payment-gateway/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PMS',
        ),
        array(  'slug' => 'pms-add-on-tax/index.php',
            'type'        => 'add-on',
            'name'        => __( 'Tax & EU VAT', 'paid-member-subscriptions' ),
            'description' => __( 'Helps you collect tax or vat from your users depending on their location, with full control over tax rates and who to charge.', 'paid-member-subscriptions' ),
            'icon'        => 'pms-add-on-tax-logo.png',
            'doc_url'     => 'https://www.cozmoslabs.com/docs/paid-member-subscriptions/add-ons/tax-eu-vat/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PMS',
        ),
    );

    // The Stripe and PayPal Express add-ons were deprecated. We need to only show them to users that were using them before the deprecation.
    $pms_deprecated_addons = get_option( 'pms_used_deprecated_addons', false );
    
    if( empty( $pms_deprecated_addons ) ) {

        $stripe_addon_active   = apply_filters( 'pms_add_on_is_active', false, 'pms-add-on-stripe/index.php' );
        $paypal_addon_active   = apply_filters( 'pms_add_on_is_active', false, 'pms-add-on-paypal-express-pro/index.php' );

        if( $stripe_addon_active ) {

            $pms_deprecated_addons[] = 'pms-add-on-stripe/index.php';  

        } else {

            $pms_addons_listing->items = array_filter( $pms_addons_listing->items, function( $item ) {
                return $item['slug'] !== 'pms-add-on-stripe/index.php';
            });

        }
    
        if( $paypal_addon_active ) {
            
            $pms_deprecated_addons[] = 'pms-add-on-paypal-express-pro/index.php';
            
        } else {

            $pms_addons_listing->items = array_filter( $pms_addons_listing->items, function( $item ) {
                return $item['slug'] !== 'pms-add-on-paypal-express-pro/index.php';
            });

        }
        
        update_option( 'pms_used_deprecated_addons', $pms_deprecated_addons, false );

    } else {

        if( !in_array( 'pms-add-on-stripe/index.php', $pms_deprecated_addons ) ) {
            $pms_addons_listing->items = array_filter( $pms_addons_listing->items, function( $item ) {
                return $item['slug'] !== 'pms-add-on-stripe/index.php';
            } );
        }

        if( !in_array( 'pms-add-on-paypal-express-pro/index.php', $pms_deprecated_addons ) ) {
            $pms_addons_listing->items = array_filter( $pms_addons_listing->items, function( $item ) {
                return $item['slug'] !== 'pms-add-on-paypal-express-pro/index.php';
            } );
        }

    }

    $pms_addons_listing->add_section();

    //Display the whole listing
    $pms_addons_listing->display_addons();

    ?>


</div>

<?php
$pms_get_all_plugins    = get_plugins();
$pms_get_active_plugins = get_option( 'active_plugins' );
$ajax_nonce             = wp_create_nonce( 'pms-activate-addon' );
?>
<div class="wrap pms-wrap pms-add-ons-wrap">


    <h2><?php esc_html_e( 'Recommended Plugins', 'paid-member-subscriptions' ) ?></h2>
    <div class="pms-recommended-plugins">

        <?php
        $trp_add_on_exists = 0;
        $trp_add_on_is_active = 0;
        $trp_add_on_is_network_active = 0;
        // Check to see if add-on is in the plugins folder
        foreach ($pms_get_all_plugins as $pms_plugin_key => $pms_plugin) {
            if( strtolower($pms_plugin['Name']) == strtolower( 'TranslatePress - Multilingual' ) && strpos(strtolower($pms_plugin['AuthorName']), strtolower('Cozmoslabs')) !== false) {
                $trp_add_on_exists = 1;
                if (in_array($pms_plugin_key, $pms_get_active_plugins)) {
                    $trp_add_on_is_active = 1;
                }
                // Consider the add-on active if it's network active
                if (is_plugin_active_for_network($pms_plugin_key)) {
                    $trp_add_on_is_network_active = 1;
                    $trp_add_on_is_active = 1;
                }
                $plugin_file = $pms_plugin_key;
            }
        }
        ?>
        <div class="plugin-card pms-recommended-plugin pms-add-on">
            <div class="plugin-card-top">
                <a target="_blank" class="pms-recommended-plugin-logo pms-tp-logo" href="https://wordpress.org/plugins/translatepress-multilingual/">
                    <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL . 'assets/images/translate-press-logo.png' ); ?>" width="100%">
                </a>
                <h3 class="pms-add-on-title">
                    <a target="_blank" href="https://wordpress.org/plugins/translatepress-multilingual/">TranslatePress</a>
                </h3>
                <h3 class="pms-add-on-price"><?php  esc_html_e( 'Free', 'paid-member-subscriptions' ) ?></h3>
                <p class="pms-add-on-description">
                    <?php esc_html_e( 'Translate your Paid Member Subscriptions checkout with a WordPress translation plugin that anyone can use. It offers a simpler way to translate WordPress sites, with full support for WooCommerce and site builders.', 'paid-member-subscriptions' ) ?>
                    <a href="<?php echo esc_url( admin_url() . 'plugin-install.php?tab=plugin-information&plugin=translatepress-multilingual&TB_iframe=true&width=772&height=875' ); ?>" class="thickbox" aria-label="More information about TranslatePress - Multilingual" data-title="TranslatePress - Multilingual"><?php esc_html_e( 'More Details', 'paid-member-subscriptions' ); ?></a>
                </p>
            </div>
            <div class="plugin-card-bottom pms-add-on-compatible">
                <?php
                if ($trp_add_on_exists) {

                    // Display activate/deactivate buttons
                    if (!$trp_add_on_is_active) {
                        echo '<a class="pms-add-on-activate right button button-secondary" href="' . esc_attr( $plugin_file ) . '" data-nonce="' . esc_attr( $ajax_nonce ) . '">' . esc_html__('Activate', 'paid-member-subscriptions') . '</a>';

                        // If add-on is network activated don't allow deactivation
                    } elseif (!$trp_add_on_is_network_active) {
                        echo '<a class="pms-add-on-deactivate right button button-secondary" href="' . esc_attr( $plugin_file ) . '" data-nonce="' . esc_attr( $ajax_nonce ) . '">' . esc_html__('Deactivate', 'paid-member-subscriptions') . '</a>';
                    }

                    // Display message to the user
                    if( !$trp_add_on_is_active ){
                        echo '<span class="dashicons dashicons-no-alt"></span><span class="pms-add-on-message">' . wp_kses_post( __('Plugin is <strong>inactive</strong>', 'paid-member-subscriptions') ) . '</span>';
                    } else {
                        echo '<span class="dashicons dashicons-yes"></span><span class="pms-add-on-message">' . wp_kses_post( __('Plugin is <strong>active</strong>', 'paid-member-subscriptions') ) . '</span>';
                    }

                } else {
                    // handles the in-page download
                    $pms_paid_link_text = esc_html__('Install Now', 'paid-member-subscriptions');

                    echo '<a class="right install-now button button-secondary" href="'. esc_url( wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=translatepress-multilingual'), 'install-plugin_translatepress-multilingual') ) .'" data-add-on-slug="translatepress-multilingual" data-add-on-name="TranslatePress - Multilingual" data-nonce="' . esc_attr( $ajax_nonce ) . '">' . esc_html( $pms_paid_link_text ) . '</a>';
                    echo '<span class="dashicons dashicons-yes"></span><span class="pms-add-on-message">' . esc_html__('Compatible with Paid Member Subscriptions.', 'paid-member-subscriptions') . '</span>';

                }
                ?>
                <div class="spinner"></div>
                <span class="pms-add-on-user-messages pms-error-manual-install"><?php printf(esc_html__('Could not install plugin. Retry or <a href="%s" target="_blank">install manually</a>.', 'paid-member-subscriptions'), esc_url( 'https://www.wordpress.org/plugins/translatepress-multilingual' )) ?></a>.</span>
            </div>
        </div>


        <?php
        $pb_add_on_exists = 0;
        $pb_add_on_is_active = 0;
        $pb_add_on_is_network_active = 0;
        // Check to see if add-on is in the plugins folder
        foreach ($pms_get_all_plugins as $pms_plugin_key => $pms_plugin) {
            if( in_array( strtolower($pms_plugin['Name']), array( strtolower( 'Profile Builder' ), strtolower( 'Profile Builder Hobbyist' ), strtolower( 'Profile Builder Pro' ) ) ) && strpos(strtolower($pms_plugin['AuthorName']), strtolower('Cozmoslabs')) !== false) {
                $pb_add_on_exists = 1;
                if (in_array($pms_plugin_key, $pms_get_active_plugins)) {
                    $pb_add_on_is_active = 1;
                }
                // Consider the add-on active if it's network active
                if (is_plugin_active_for_network($pms_plugin_key)) {
                    $pb_add_on_is_network_active = 1;
                    $pb_add_on_is_active = 1;
                }
                $plugin_file = $pms_plugin_key;

                if( $pb_add_on_is_active )
                    break;
            }
        }
        ?>
        <div class="plugin-card pms-recommended-plugin pms-add-on">
            <div class="plugin-card-top">
                <a target="_blank" class="pms-recommended-plugin-logo pms-pb-logo" href="http://wordpress.org/plugins/profile-builder/">
                    <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL . 'assets/images/pb-banner.svg' ); ?>">
                </a>
                <h3 class="pms-add-on-title">
                    <a target="_blank" href="http://wordpress.org/plugins/profile-builder/">Profile Builder</a>
                </h3>
                <h3 class="pms-add-on-price"><?php  esc_html_e( 'Free', 'paid-member-subscriptions' ) ?></h3>
                <p class="pms-add-on-description">
                    <?php esc_html_e( "Capture more user information on the registration form with the help of Profile Builder's custom user profile fields and/or add an Email Confirmation process to verify your customers accounts.", 'paid-member-subscriptions' ) ?>
                    <a href="<?php echo esc_url( admin_url() . 'plugin-install.php?tab=plugin-information&plugin=profile-builder&TB_iframe=true&width=772&height=875' );?>" class="thickbox" aria-label="More information about Profile Builder" data-title="Profile Builder"><?php esc_html_e( 'More Details', 'paid-member-subscriptions' ); ?></a>
                </p>
            </div>
            <div class="plugin-card-bottom pms-add-on-compatible">
                <?php
                if ($pb_add_on_exists) {

                    // Display activate/deactivate buttons
                    if (!$pb_add_on_is_active) {
                        echo '<a class="pms-add-on-activate right button button-secondary" href="' . esc_attr( $plugin_file ) . '" data-nonce="' . esc_attr( $ajax_nonce ) . '">' . esc_html__('Activate', 'paid-member-subscriptions') . '</a>';

                        // If add-on is network activated don't allow deactivation
                    } elseif (!$pb_add_on_is_network_active) {
                        echo '<a class="pms-add-on-deactivate right button button-secondary" href="' . esc_attr( $plugin_file ) . '" data-nonce="' . esc_attr( $ajax_nonce ) . '">' . esc_html__('Deactivate', 'paid-member-subscriptions') . '</a>';
                    }

                    // Display message to the user
                    if( !$pb_add_on_is_active ){
                        echo '<span class="dashicons dashicons-no-alt"></span><span class="pms-add-on-message">' . wp_kses_post( __('Plugin is <strong>inactive</strong>', 'paid-member-subscriptions') ) . '</span>';
                    } else {
                        echo '<span class="dashicons dashicons-yes"></span><span class="pms-add-on-message">' . wp_kses_post( __('Plugin is <strong>active</strong>', 'paid-member-subscriptions') ) . '</span>';
                    }

                } else {
                    // handles the in-page download
                    $pms_paid_link_text = esc_html__('Install Now', 'paid-member-subscriptions');

                    echo '<a class="right install-now button button-secondary" href="'. esc_url( wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=profile-builder'), 'install-plugin_profile-builder') ) .'" data-add-on-slug="profile-builder" data-add-on-name="Profile Builder" data-nonce="' . esc_attr( $ajax_nonce ) . '">' . esc_html( $pms_paid_link_text ) . '</a>';
                    echo '<span class="dashicons dashicons-yes"></span><span class="pms-add-on-message">' . esc_html__('Compatible with Paid Member Subscriptions.', 'paid-member-subscriptions') . '</span>';

                }
                ?>
                <div class="spinner"></div>
                <span class="pms-add-on-user-messages pms-error-manual-install"><?php printf(esc_html__('Could not install plugin. Retry or <a href="%s" target="_blank">install manually</a>.', 'paid-member-subscriptions'), esc_url( 'http://www.wordpress.org/plugins/profile-builder' )) ?></a>.</span>
            </div>
        </div>


        <?php
        $wp_webhook_add_on_exists = 0;
        $wp_webhook_add_on_is_active = 0;
        $wp_webhook_add_on_is_network_active = 0;
        // Check to see if add-on is in the plugins folder
        foreach ($pms_get_all_plugins as $pms_plugin_key => $pms_plugin) {
            if( strtolower($pms_plugin['Name']) == strtolower( 'Wp Webhooks' ) && strpos(strtolower($pms_plugin['AuthorName']), strtolower('Ironikus')) !== false) {
                $wp_webhook_add_on_exists = 1;
                if (in_array($pms_plugin_key, $pms_get_active_plugins)) {
                    $wp_webhook_add_on_is_active = 1;
                }
                // Consider the add-on active if it's network active
                if (is_plugin_active_for_network($pms_plugin_key)) {
                    $wp_webhook_add_on_is_network_active = 1;
                    $wp_webhook_add_on_is_active = 1;
                }
                $plugin_file = $pms_plugin_key;
            }
        }
        ?>
        <div class="plugin-card pms-recommended-plugin pms-add-on">
            <div class="plugin-card-top">
                <a target="_blank" class="pms-recommended-plugin-logo pms-webhooks-logo" href="https://wordpress.org/plugins/wp-webhooks/" style="background: #00dc9e;">
                    <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL . 'assets/images/addons/wp-webhooks-banner.svg' ); ?>" style="max-height: 80px; ">
                </a>
                <h3 class="pms-add-on-title">
                    <a target="_blank" href="https://wordpress.org/plugins/WP Webhooks/">WP Webhooks Automations</a>
                </h3>
                <h3 class="pms-add-on-price"><?php  esc_html_e( 'Free', 'paid-member-subscriptions' ) ?></h3>
                <p class="pms-add-on-description">
                    <?php esc_html_e( 'Easily create powerful no-code automations that connect your WordPress plugins, sites and apps together.', 'paid-member-subscriptions' ) ?>
                    <a href="<?php echo esc_url( admin_url() . 'plugin-install.php?tab=plugin-information&plugin=wp-webhooks&TB_iframe=true&width=772&height=875' ); ?>" class="thickbox" aria-label="More information about WP Webhooks" data-title="Wp Webhooks"><?php esc_html_e( 'More Details', 'paid-member-subscriptions' ); ?></a>
                </p>
            </div>
            <div class="plugin-card-bottom pms-add-on-compatible">
                <?php
                if ($wp_webhook_add_on_exists) {

                    // Display activate/deactivate buttons
                    if (!$wp_webhook_add_on_is_active) {
                        echo '<a class="pms-add-on-activate right button button-secondary" href="' . esc_attr( $plugin_file ) . '" data-nonce="' . esc_attr( $ajax_nonce ) . '">' . esc_html__('Activate', 'paid-member-subscriptions') . '</a>';

                        // If add-on is network activated don't allow deactivation
                    } elseif (!$trp_add_on_is_network_active) {
                        echo '<a class="pms-add-on-deactivate right button button-secondary" href="' . esc_attr( $plugin_file ) . '" data-nonce="' . esc_attr( $ajax_nonce ) . '">' . esc_html__('Deactivate', 'paid-member-subscriptions') . '</a>';
                    }

                    // Display message to the user
                    if( !$wp_webhook_add_on_is_active ){
                        echo '<span class="dashicons dashicons-no-alt"></span><span class="pms-add-on-message">' . wp_kses_post( __('Plugin is <strong>inactive</strong>', 'paid-member-subscriptions') ) . '</span>';
                    } else {
                        echo '<span class="dashicons dashicons-yes"></span><span class="pms-add-on-message">' . wp_kses_post( __('Plugin is <strong>active</strong>', 'paid-member-subscriptions') ) . '</span>';
                    }

                } else {
                    // handles the in-page download
                    $pms_paid_link_text = esc_html__('Install Now', 'paid-member-subscriptions');

                    echo '<a class="right install-now button button-secondary" href="'. esc_url( wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=wp-webhooks'), 'install-plugin_wp-webhooks') ) .'" data-add-on-slug="wp-webhooks" data-add-on-name="WP Webhooks" data-nonce="' . esc_attr( $ajax_nonce ) . '">' . esc_html( $pms_paid_link_text ) . '</a>';
                    echo '<span class="dashicons dashicons-yes"></span><span class="pms-add-on-message">' . esc_html__('Compatible with Paid Member Subscriptions.', 'paid-member-subscriptions') . '</span>';

                }
                ?>
                <div class="spinner"></div>
                <span class="pms-add-on-user-messages pms-error-manual-install"><?php printf(esc_html__('Could not install plugin. Retry or <a href="%s" target="_blank">install manually</a>.', 'paid-member-subscriptions'), esc_url( 'https://www.wordpress.org/plugins/translatepress-multilingual' )) ?></a>.</span>
            </div>
        </div>

    </div>

    <div class="clear"></div>

    <span id="pms-add-on-activate-button-text" class="pms-add-on-user-messages"><?php echo esc_html__( 'Activate', 'paid-member-subscriptions' ); ?></span>

    <span id="pms-add-on-downloading-message-text" class="pms-add-on-user-messages"><?php echo esc_html__( 'Downloading and installing...', 'paid-member-subscriptions' ); ?></span>
    <span id="pms-add-on-download-finished-message-text" class="pms-add-on-user-messages"><?php echo esc_html__( 'Installation complete', 'paid-member-subscriptions' ); ?></span>

    <span id="pms-add-on-activated-button-text" class="pms-add-on-user-messages"><?php echo esc_html__( 'Add-On is Active', 'paid-member-subscriptions' ); ?></span>
    <span id="pms-add-on-activated-message-text" class="pms-add-on-user-messages"><?php echo esc_html__( 'Add-On has been activated', 'paid-member-subscriptions' ) ?></span>
    <span id="pms-add-on-activated-error-button-text" class="pms-add-on-user-messages"><?php echo esc_html__( 'Retry Install', 'paid-member-subscriptions' ) ?></span>

    <span id="pms-add-on-is-active-message-text" class="pms-add-on-user-messages"><?php echo wp_kses_post( __( 'Add-On is <strong>active</strong>', 'paid-member-subscriptions' ) ); ?></span>
    <span id="pms-add-on-is-not-active-message-text" class="pms-add-on-user-messages"><?php echo wp_kses_post( __( 'Add-On is <strong>inactive</strong>', 'paid-member-subscriptions' ) ); ?></span>

    <span id="pms-add-on-deactivate-button-text" class="pms-add-on-user-messages"><?php echo esc_html__( 'Deactivate', 'paid-member-subscriptions' ) ?></span>
    <span id="pms-add-on-deactivated-message-text" class="pms-add-on-user-messages"><?php echo esc_html__( 'Add-On has been deactivated.', 'paid-member-subscriptions' ) ?></span>



</div>

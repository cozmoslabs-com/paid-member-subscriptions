<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * HTML Output for the basic information page
 */
?>

<div class="wrap pms-wrap pms-info-wrap">

    <div class="pms-badge ">
        <span><?php echo esc_html__( 'Version', 'paid-member-subscriptions' ) . ' ' . esc_html( PMS_VERSION ); ?></span>
    </div>

    <h1><?php echo esc_html__( 'Paid Member Subscriptions', 'paid-member-subscriptions' ); ?></h1>
    <p class="pms-info-text"><?php printf( esc_html__( 'Accept payments, create subscription plans and restrict content on your website.', 'paid-member-subscriptions' ) ); ?></p>
    <hr />

    <h2 class="pms-callout"><?php esc_html_e( 'Membership Made Easy', 'paid-member-subscriptions' ); ?></h2>
    <div class="pms-row pms-3-col">
        <div>
            <h3><?php esc_html_e( 'Register', 'paid-member-subscriptions'  ); ?></h3>
            <p><?php printf( esc_html__( 'Add basic registration forms where members can sign-up for a subscription plan using the %s shortcode.', 'paid-member-subscriptions' ), '<strong class="nowrap">[pms-register]</strong>' ); ?></p>
            <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/shortcodes/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info#Member_Registration_form" target="_blank"><?php esc_html_e( 'Learn more', 'paid-member-subscriptions' ); ?></a>
        </div>
        <div>
            <h3><?php esc_html_e( 'Login', 'paid-member-subscriptions' ); ?></h3>
            <p><?php printf( esc_html__( 'Allow members to login using %s shortcode.', 'paid-member-subscriptions' ), '<strong class="nowrap">[pms-login]</strong>' ); ?></p>
            <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/shortcodes/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info#Login_Form" target="_blank"><?php esc_html_e( 'Learn more', 'paid-member-subscriptions' ); ?></a>
        </div>
        <div>
            <h3><?php esc_html_e( 'Account', 'paid-member-subscriptions' ); ?></h3>
            <p><?php printf( esc_html__( 'Allow members to edit their account information and manage their subscription plans using the %s shortcode.', 'paid-member-subscriptions' ), '<strong class="nowrap">[pms-account]</strong>' ); ?></p>
            <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/shortcodes/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info#Member_Account_form" target="_blank"><?php esc_html_e( 'Learn more', 'paid-member-subscriptions' ); ?></a>
        </div>
        <div>
            <h3><?php esc_html_e( 'Restrict Content', 'paid-member-subscriptions' ); ?></h3>
            <p><?php printf( esc_html__( 'Restrict content using the %s shortcode or directly from individual posts and pages.', 'paid-member-subscriptions' ), '<br/><strong class="nowrap">[pms-restrict subscription_plans="9,10"]</strong> &nbsp;&nbsp;&nbsp; <em>' . esc_html__( 'Special content for members subscribed to the subscription plans that have the ID 9 and 10!', 'paid-member-subscriptions' ) . '</em><strong class="nowrap">[/pms-restrict]</strong><br/>' ); ?></p>
            <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/content-restriction/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank"><?php esc_html_e( 'Learn more', 'paid-member-subscriptions' ); ?></a>
        </div>
        <div>
            <h3><?php esc_html_e( 'Recover Password', 'paid-member-subscriptions' ); ?></h3>
            <p><?php printf( esc_html__( 'Add a recover password form for your members using %s shortcode.', 'paid-member-subscriptions' ), '<strong class="nowrap">[pms-recover-password]</strong>' ); ?></p>
            <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/shortcodes/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info#Recover_Password" target="_blank"><?php esc_html_e( 'Learn more', 'paid-member-subscriptions' ); ?></a>
        </div>
    </div>

    <a href="<?php echo esc_url( admin_url( 'index.php?page=pms-setup' ) ) ?>" class="pms-setup-wizard-button button primary button-primary button-hero"><?php esc_html_e( 'Open Setup Wizard', 'paid-member-subscriptions' ); ?></a>

    <hr/>
    <div>
        <h2 class="pms-callout"><?php esc_html_e( 'Membership Modules', 'paid-member-subscriptions' );?></h2>
    </div>

    <div class="pms-row pms-2-col">
        <div>
            <div class="pms-row pms-2-col">
                <div>
                    <h3><?php esc_html_e( 'Subscription Plans', 'paid-member-subscriptions' ); ?></h3>
                    <p><?php esc_html_e( 'Create hierarchical subscription plans allowing your members to upgrade from an existing subscription. Shortcode based, offering many options to customize your subscriptions listing.', 'paid-member-subscriptions' ); ?></p>
                    <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/subscription-plans/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank"><?php esc_html_e( 'Learn more', 'paid-member-subscriptions' ); ?></a>
                </div>
                <div>
                    <h3><?php esc_html_e( 'Members', 'paid-member-subscriptions' ); ?></h3>
                    <p><?php esc_html_e( 'Overview of all your members and their subscription plans. Easily add/remove members or edit their subscription details. ', 'paid-member-subscriptions' ); ?></p>
                    <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/member-management/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank"><?php esc_html_e( 'Learn more', 'paid-member-subscriptions' ); ?></a>
                </div>

            </div>
            <div class="pms-row pms-2-col">
                <div>
                    <h3><?php esc_html_e( 'Payments', 'paid-member-subscriptions' ); ?></h3>
                    <p><?php esc_html_e( 'Keep track of all member payments, payment statuses, purchased subscription plans but also figure out why a Payment failed.', 'paid-member-subscriptions' ); ?></p>
                    <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/member-payments/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank"><?php esc_html_e( 'Learn more', 'paid-member-subscriptions' ); ?></a>
                </div>
                <div>
                    <h3><?php esc_html_e( 'Settings', 'paid-member-subscriptions' ); ?></h3>
                    <p><?php esc_html_e( 'Set the payment gateway used to accept payments, select messages seen by users when accessing a restricted content page or customize default member emails. Everything is just a few clicks away. ', 'paid-member-subscriptions' ); ?></p>
                    <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/settings/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank"><?php esc_html_e( 'Learn more', 'paid-member-subscriptions' ); ?></a>
                </div>
            </div>
        </div>

        <div class="">
            <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ); ?>assets/images/pms_members_multiple.png" alt="Paid Member Subscriptions Members Page" />
        </div>
    </div>
    <hr/>

    <div>
        <h2 class="pms-callout"><?php esc_html_e( 'WooCommerce Integration', 'paid-member-subscriptions' );?></h2>
        <p>
            <?php esc_html_e( 'Integrates beautifully with WooCommerce, for extended functionality.', 'paid-member-subscriptions' ); ?>
            <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/integration-with-other-plugins/woocommerce/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank"><?php esc_html_e( 'Learn more', 'paid-member-subscriptions' ); ?></a>
        </p>
    </div>

    <div class="pms-row pms-2-col">
        <div>
            <div class="pms-row pms-2-col">
                <div>
                    <h3><?php esc_html_e( 'Restrict Product Viewing & Purchasing', 'paid-member-subscriptions' ); ?></h3>
                    <p><?php esc_html_e( 'Control who can see or purchase a WooCommerce product based on logged in status and subscription plan. Easily create products available to members only.', 'paid-member-subscriptions' ); ?></p>
                </div>
                <div>
                    <h3><?php esc_html_e( 'Offer Membership Discounts', 'paid-member-subscriptions' ); ?></h3>
                    <p><?php esc_html_e( 'Offer product discounts to members based on their active subscription. Set discounts globally per subscription plan, or individually per product.', 'paid-member-subscriptions' ); ?></p>
                </div>

            </div>
            <div class="pms-row pms-2-col">
                <div>
                    <h3><?php esc_html_e( 'Settings', 'paid-member-subscriptions' ); ?></h3>
                    <p><?php esc_html_e( 'Make use of the extra flexibility by setting custom restriction messages per product, excluding products on sale from membership discounts, allowing cumulative discounts & more. ', 'paid-member-subscriptions' ); ?></p>
                </div>
            </div>
        </div>

        <div class="">
            <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ); ?>assets/images/pms_woo_member_discount.png" alt="Paid Member Subscriptions WooCommerce Product Member Discount" />
        </div>
    </div>
    <hr/>

    <div>
        <h2 class="pms-callout"><?php esc_html_e( 'Featured Add-ons', 'paid-member-subscriptions' );?></h2>
        <p><?php esc_html_e( 'Get more functionality by using dedicated Add-ons and tailor Paid Member Subscriptions to your project needs.', 'paid-member-subscriptions' ); ?></p>
    </div>
    <br />
    <div>
        <h3><?php esc_html_e( 'Basic Add-ons', 'paid-member-subscriptions' );?></h3>
        <p><?php printf( wp_kses_post( __( 'These addons extend your WordPress Membership Plugin and are available with the <a href="%s">Hobbyist and PRO</a> versions.', 'paid-member-subscriptions' ) ), 'https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=wpbackend&utm_medium=clientsite&utm_content=basicinfo-addons-basic-link&utm_campaign=PMSFree' ); ?></p>
    </div>
    <div class="pms-row pms-4-col pms-addons">
        <div>
            <a href="https://www.cozmoslabs.com/add-ons/global-content-restriction/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank">
                <h4 class="pms-add-on-name"><?php esc_html_e( 'Global Content Restriction', 'paid-member-subscriptions' ); ?></h4>
            </a>

            <a href="https://www.cozmoslabs.com/add-ons/global-content-restriction/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank">
                <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ); ?>assets/images/add-on-global-content-restriction.png" alt="Global Content Restriction" class="pms-addon-image" />
            </a>

            <p><?php esc_html_e( 'Easy way to add global content restriction rules to subscription plans, based on post type, taxonomy and terms.', 'paid-member-subscriptions' ); ?></p>
        </div>
        <div>
            <a href="https://www.cozmoslabs.com/add-ons/discount-codes/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank">
                <h4 class="pms-add-on-name"><?php esc_html_e( 'Discount Codes', 'paid-member-subscriptions' ); ?></h4>
            </a>

            <a href="https://www.cozmoslabs.com/add-ons/discount-codes/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank">
                <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ); ?>assets/images/add-on-discount-codes.png" alt="Discount Codes" class="pms-addon-image" />
            </a>

            <p><?php esc_html_e( 'Friction-less discount code creation for running promotions, making price reductions or simply rewarding your users.', 'paid-member-subscriptions' ); ?></p>
        </div>
        <div>
            <a href="https://www.cozmoslabs.com/add-ons/email-reminders/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank">
                <h4 class="pms-add-on-name"><?php esc_html_e( 'Email Reminders', 'paid-member-subscriptions' ); ?></h4>
            </a>

            <a href="https://www.cozmoslabs.com/add-ons/email-reminders/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank">
                <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ); ?>assets/images/add-on-email-reminders.png" alt="PayPal Pro and PayPal Express" class="pms-addon-image" />
            </a>

            <p><?php esc_html_e( 'Create multiple automated email reminders that are sent to members before or after certain events take place (subscription expires, subscription activated etc.)', 'paid-member-subscriptions' ); ?></p>
        </div>
        <div>
            <a href="https://www.cozmoslabs.com/add-ons/navigation-menu-filtering/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank">
                <h4 class="pms-add-on-name"><?php esc_html_e( 'Navigation Menu Filtering', 'paid-member-subscriptions' ); ?></h4>
            </a>

            <a href="https://www.cozmoslabs.com/add-ons/navigation-menu-filtering/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank">
                <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ); ?>assets/images/add-on-navigation-menu-filtering.png" alt="Navigation Menu Filtering" class="pms-addon-image" />
            </a>

            <p><?php esc_html_e( 'Dynamically display menu items based on logged-in status as well as selected subscription plans.', 'paid-member-subscriptions' ); ?></p>
        </div>
        <div style="clear:left;">
            <a href="https://www.cozmoslabs.com/add-ons/paid-member-subscriptions-bbpress/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank">
                <h4 class="pms-add-on-name"><?php esc_html_e( 'bbPress', 'paid-member-subscriptions' ); ?></h4>
            </a>
            <a href="https://www.cozmoslabs.com/add-ons/paid-member-subscriptions-bbpress/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank">
                <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ); ?>assets/images/pms-addon-bbpress.png" alt="bbPress" class="pms-addon-image" />
            </a>

            <p><?php esc_html_e( 'Integrate Paid Member Subscriptions with the popular forums plugin, bbPress.', 'paid-member-subscriptions' ); ?></p>
        </div>
        <div>
            <a href="https://www.cozmoslabs.com/add-ons/fixed-period-membership/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank">
                <h4 class="pms-add-on-name"><?php esc_html_e( 'Fixed Period Membership', 'paid-member-subscriptions' ); ?></h4>
            </a>

            <a href="https://www.cozmoslabs.com/add-ons/fixed-period-membership/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank">
                <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ); ?>assets/images/add-on-fixed-period.png" alt="Fixed Period Membership" class="pms-addon-image" />
            </a>

            <p><?php esc_html_e( 'The Fixed Period Membership Add-On allows your Subscriptions to end at a specific date.', 'paid-member-subscriptions' ); ?></p>
        </div>
        <div>
            <a href="https://www.cozmoslabs.com/add-ons/pms-labels-edit/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank">
                <h4 class="pms-add-on-name"><?php esc_html_e( 'Labels Edit', 'paid-member-subscriptions' ); ?></h4>
            </a>

            <a href="https://www.cozmoslabs.com/add-ons/pms-labels-edit/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank">
                <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ); ?>assets/images/add-on-labels-edit.png" alt="Labels Edit" class="pms-addon-image" />
            </a>

            <p><?php esc_html_e( 'Edit and change any Paid Member Subscriptions label or string in just a few clicks.', 'paid-member-subscriptions' ); ?></p>
        </div>
        <div>
            <a href="https://www.cozmoslabs.com/add-ons/pay-what-you-want/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank">
                <h4 class="pms-add-on-name"><?php esc_html_e( 'Pay What You Want', 'paid-member-subscriptions' ); ?></h4>
            </a>

            <a href="https://www.cozmoslabs.com/add-ons/pay-what-you-want/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank">
                <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ); ?>assets/images/add-on-pay-what-you-want.png" alt="Pay What You Want" class="pms-addon-image" />
            </a>

            <p><?php esc_html_e( 'Let subscribers pay what they want by offering a variable pricing option when they purchase a membership plan.', 'paid-member-subscriptions' ); ?></p>
        </div>
    </div>
    <div class="pms-row">
        <p><a href="https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=wpbackend&utm_medium=clientsite&utm_content=basicinfo-addons-basic-btn&utm_campaign=PMSFree" class="button-primary pms-cta"><?php esc_html_e( 'Get Basic Add-ons', 'paid-member-subscriptions' ); ?></a></p>
    </div>

    <br />

    <div>
        <h3><?php esc_html_e( 'Pro Add-ons', 'paid-member-subscriptions' );?></h3>
        <p><?php printf( wp_kses_post( __( 'These addons extend your WordPress Membership Plugin and are available with the <a href="%s">PRO version</a> only.', 'paid-member-subscriptions' ) ), 'https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=wpbackend&utm_medium=clientsite&utm_content=basicinfo-addons-pro-link&utm_campaign=PMSFree' ); ?></p>
    </div>
    <div class="pms-row pms-4-col pms-addons">
        <div>
            <a href="https://www.cozmoslabs.com/add-ons/recurring-payments-for-paypal-standard/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank">
                <h4 class="pms-add-on-name"><?php esc_html_e( 'Recurring Payments - PayPal Standard', 'paid-member-subscriptions' ); ?></h4>
            </a>

            <a href="https://www.cozmoslabs.com/add-ons/recurring-payments-for-paypal-standard/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank">
                <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ); ?>assets/images/add-on-paypal-recurring.png" alt="Recurring Payments PayPal Standard" class="pms-addon-image" />
            </a>

            <p><?php esc_html_e( 'Accept recurring payments from your members through PayPal Standard.', 'paid-member-subscriptions' ); ?></p>
        </div>
        <div>
            <a href="https://www.cozmoslabs.com/add-ons/paypal-pro-paypal-express/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank">
                <h4 class="pms-add-on-name"><?php esc_html_e( 'PayPal Pro and PayPal Express', 'paid-member-subscriptions' ); ?></h4>
            </a>

            <a href="https://www.cozmoslabs.com/add-ons/paypal-pro-paypal-express/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank">
                <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ); ?>assets/images/add-on-paypal-pro.png" alt="PayPal Pro and PayPal Express" class="pms-addon-image" />
            </a>

            <p><?php esc_html_e( 'Accept one time or recurring payments through PayPal Pro (credit card) and/or Express Checkout.', 'paid-member-subscriptions' ); ?></p>
        </div>
        <div>
            <a href="https://www.cozmoslabs.com/add-ons/stripe/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank">
                <h4 class="pms-add-on-name"><?php esc_html_e( 'Stripe', 'paid-member-subscriptions' ); ?></h4>
            </a>

            <a href="https://www.cozmoslabs.com/add-ons/stripe/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank">
                <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ); ?>assets/images/add-on-stripe.png" alt="Navigation Menu Filtering" class="pms-addon-image" />
            </a>

            <p><?php esc_html_e( 'Accept credit card payments, both one-time and recurring, directly on your website via Stripe.', 'paid-member-subscriptions' ); ?></p>
        </div>
        <div>
            <a href="https://www.cozmoslabs.com/add-ons/content-dripping/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank">
                <h4 class="pms-add-on-name"><?php esc_html_e( 'Content Dripping', 'paid-member-subscriptions' ); ?></h4>
            </a>

            <a href="https://www.cozmoslabs.com/add-ons/content-dripping/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank">
                <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ); ?>assets/images/add-on-content-dripping.png" alt="PayPal Pro and PayPal Express" class="pms-addon-image" />
            </a>

            <p><?php esc_html_e( 'Create schedules for your content, making posts or categories available for your members only after a certain time has passed since they signed up for a subscription plan.', 'paid-member-subscriptions' ); ?></p>
        </div>
        <div>
            <a href="https://www.cozmoslabs.com/add-ons/multiple-subscriptions-per-user/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank">
                <h4 class="pms-add-on-name"><?php esc_html_e( 'Multiple Subscriptions / User', 'paid-member-subscriptions' ); ?></h4>
            </a>

            <a href="https://www.cozmoslabs.com/add-ons/multiple-subscriptions-per-user/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank">
                <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ); ?>assets/images/add-on-multiple-subscriptions.png" alt="Multiple Subscriptions per User" class="pms-addon-image" />
            </a>

            <p><?php esc_html_e( 'Setup multiple subscription level blocks and allow members to sign up for more than one subscription plan (one per block).', 'paid-member-subscriptions' ); ?></p>
        </div>
        <div>
            <a href="https://www.cozmoslabs.com/add-ons/invoices/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank">
                <h4 class="pms-add-on-name"><?php esc_html_e( 'Invoices', 'paid-member-subscriptions' ); ?></h4>
            </a>

            <a href="https://www.cozmoslabs.com/add-ons/invoices/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank">
                <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ); ?>assets/images/add-on-invoices.png" alt="Invoices" class="pms-addon-image" />
            </a>

            <p><?php esc_html_e( 'This add-on allows you and your members to download PDF invoices for each payment that has been completed.', 'paid-member-subscriptions' ); ?></p>
        </div>
        <div>

            <a href="https://www.cozmoslabs.com/add-ons/group-memberships/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank">
                <h4 class="pms-add-on-name"><?php esc_html_e( 'Group Memberships', 'paid-member-subscriptions' ); ?></h4>
            </a>

            <a href="https://www.cozmoslabs.com/add-ons/group-memberships/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank">
                <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ); ?>assets/images/add-on-group-memberships.png" alt="Group Memberships" class="pms-addon-image" />
            </a>

            <p><?php esc_html_e( 'Sell group subscriptions that contain multiple member seats but are managed and purchased by a single account.', 'paid-member-subscriptions' ); ?></p>
        </div>
        <div>
            <a href="https://www.cozmoslabs.com/add-ons/tax-eu-vat/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank">
                <h4 class="pms-add-on-name"><?php esc_html_e( 'Tax & EU VAT', 'paid-member-subscriptions' ); ?></h4>
            </a>

            <a href="https://www.cozmoslabs.com/add-ons/tax-eu-vat/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSfree&utm_content=basic-info" target="_blank">
                <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ); ?>assets/images/add-on-tax.png" alt="Tax & EU VAT" class="pms-addon-image" />
            </a>

            <p><?php esc_html_e( 'Collect tax or vat from your users depending on their location, with full control over tax rates and who to charge.', 'paid-member-subscriptions' ); ?></p>
        </div>
    </div>
    <div class="pms-row">
        <p><a href="https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=wpbackend&utm_medium=clientsite&utm_content=basicinfo-addons-pro-btn&utm_campaign=PMSFree" class="button-primary pms-cta"><?php esc_html_e( 'Get Pro Add-ons', 'paid-member-subscriptions' ); ?></a></p>
    </div>

    <hr/>

    <div class="pms-1-3-col">
        <div>
            <a href="https://wordpress.org/plugins/translatepress-multilingual/" target="_blank"><img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ) . 'assets/images/pms-trp-cross-promotion.png'; ?>" alt="TranslatePress Logo"/></a>
        </div>
        <div>
            <h3>Easily translate your entire WordPress website</h3>
            <p>Translate your Paid Member Subscriptions checkout with a WordPress translation plugin that anyone can use.</p>
            <p>It offers a simpler way to translate WordPress sites, with full support for WooCommerce and site builders.</p>
            <p><a href="https://wordpress.org/plugins/translatepress-multilingual/" class="button" target="_blank">Find out how</a></p>
        </div>
    </div>

    <hr/>

    <p><i><?php printf( wp_kses_post( __( 'Paid Member Subscriptions comes with an <a href="%s">extensive documentation</a> to assist you.', 'paid-member-subscriptions' ) ),'http://www.cozmoslabs.com/docs/paid-member-subscriptions/' ); ?></i></p>
</div>

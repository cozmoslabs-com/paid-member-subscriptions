<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * HTML Output for the basic information page
 */
?>

<div class="wrap pms-wrap pms-info-wrap cozmoslabs-wrap">

    <div id="cozmoslabs-basic-info-header" class="cozmoslabs-page-header">
        <div>
            <h1 class="cozmoslabs-page-title"><?php echo esc_html__( 'Paid Member Subscriptions', 'paid-member-subscriptions' ); ?></h1>
            <p class="cozmoslabs-description"><?php printf( esc_html__( 'Accept payments, create subscription plans and restrict content on your website.', 'paid-member-subscriptions' ) ); ?></p>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=pms-dashboard-page&subpage=pms-setup' ) ) ?>" class="pms-setup-wizard-button button primary button-primary button-hero"><?php esc_html_e( 'Open Setup Wizard', 'paid-member-subscriptions' ); ?></a>
        </div>

        <div class="pms-badge">
            <span><?php echo esc_html__( 'Version', 'paid-member-subscriptions' ) . ' ' . esc_html( PMS_VERSION ); ?></span>
        </div>
    </div>

    <div class="cozmoslabs-form-subsection-wrapper">
        <h2 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Membership Made Easy', 'paid-member-subscriptions' ); ?></h2>
        <div>
            <div class="cozmoslabs-form-field-wrapper">
                <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Register', 'paid-member-subscriptions'  ); ?></label>
                <div title='Click to copy' class="pms-shortcode_copy-text"><strong >[pms-register]</strong></div>
                <span style='display: none; margin-left: 10px' class='pms-copy-message'><?php echo esc_html__('Shortcode copied', 'paid-member-subscriptions'); ?></span>
                <p class="cozmoslabs-description cozmoslabs-description-space-left">
                    <?php esc_html_e( 'Add basic registration forms where members can sign-up for a subscription plan. ', 'paid-member-subscriptions' ); ?>
                    <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/shortcodes/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info#Member_Registration_form" target="_blank"><?php esc_html_e( 'Learn more', 'paid-member-subscriptions' ); ?></a>
                </p>
            </div>

            <div class="cozmoslabs-form-field-wrapper">
                <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Login', 'paid-member-subscriptions' ); ?></label>
                <div title='Click to copy' class="pms-shortcode_copy-text"><strong>[pms-login]</strong></div>
                <span style='display: none; margin-left: 10px' class='pms-copy-message'><?php echo esc_html__('Shortcode copied', 'paid-member-subscriptions'); ?></span>
                <p class="cozmoslabs-description cozmoslabs-description-space-left">
                    <?php esc_html_e( 'Allow members to login.', 'paid-member-subscriptions' ); ?>
                    <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/shortcodes/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info#Login_Form" target="_blank"><?php esc_html_e( 'Learn more', 'paid-member-subscriptions' ); ?></a>
                </p>
            </div>

            <div class="cozmoslabs-form-field-wrapper">
                <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Account', 'paid-member-subscriptions' ); ?></label>
                <div title='Click to copy' class="pms-shortcode_copy-text"><strong>[pms-account]</strong></div>
                <span style='display: none; margin-left: 10px' class='pms-copy-message'><?php echo esc_html__('Shortcode copied', 'paid-member-subscriptions'); ?></span>
                <p class="cozmoslabs-description cozmoslabs-description-space-left">
                    <?php esc_html_e( 'Allow members to edit their account information and manage their subscription plans.', 'paid-member-subscriptions' ); ?>
                    <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/shortcodes/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info#Member_Account_form" target="_blank"><?php esc_html_e( 'Learn more', 'paid-member-subscriptions' ); ?></a>
                </p>
            </div>

            <div class="cozmoslabs-form-field-wrapper">
                <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Restrict Content', 'paid-member-subscriptions' ); ?></label>
                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <div title='Click to copy' class="pms-shortcode_copy-text"><strong>[pms-restrict subscription_plans="9,10"]</strong></div>
                    <span style='display: none; margin-left: 10px' class='pms-copy-message'><?php echo esc_html__('Shortcode copied', 'paid-member-subscriptions'); ?></span>
                    <em style="padding-left: 5px;"><?php  esc_html_e( 'Special content for members subscribed to the subscription plans that have the ID 9 and 10!', 'paid-member-subscriptions' ) ?></em>
                    <div title='Click to copy' class="pms-shortcode_copy-text"><strong>[/pms-restrict]</strong></div>
                    <span style='display: none; margin-left: 10px' class='pms-copy-message'><?php echo esc_html__('Shortcode copied', 'paid-member-subscriptions'); ?></span>
                </div>
                <p class="cozmoslabs-description cozmoslabs-description-space-left">
                    <?php esc_html_e( 'Restrict content using the shortcode or directly from individual posts and pages.', 'paid-member-subscriptions' ); ?>
                    <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/content-restriction/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank"><?php esc_html_e( 'Learn more', 'paid-member-subscriptions' ); ?></a>
                </p>
            </div>

            <div class="cozmoslabs-form-field-wrapper">
                <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Recover Password', 'paid-member-subscriptions' ); ?></label>
                <div title='Click to copy' class="pms-shortcode_copy-text"><strong>[pms-recover-password]</strong></div>
                <span style='display: none; margin-left: 10px' class='pms-copy-message'><?php echo esc_html__('Shortcode copied', 'paid-member-subscriptions'); ?></span>
                <p class="cozmoslabs-description cozmoslabs-description-space-left">
                    <?php esc_html_e( 'Add a recover password form for your members.', 'paid-member-subscriptions' ); ?>
                    <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/shortcodes/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info#Recover_Password" target="_blank"><?php esc_html_e( 'Learn more', 'paid-member-subscriptions' ); ?></a>
                </p>
            </div>

        </div>
    </div>

    <div class="cozmoslabs-form-subsection-wrapper">
        <div>
            <h2 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Membership Modules', 'paid-member-subscriptions' );?></h2>
        </div>

        <div>
            <div>
                <div>
                    <div class="cozmoslabs-form-field-wrapper">
                        <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Subscription Plans', 'paid-member-subscriptions' ); ?></label>
                        <p class="cozmoslabs-description cozmoslabs-description-align-right">
                            <?php esc_html_e( 'Create hierarchical subscription plans allowing your members to upgrade from an existing subscription. Shortcode based, offering many options to customize your subscriptions listing.', 'paid-member-subscriptions' ); ?>
                            <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/subscription-plans/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank"><?php esc_html_e( 'Learn more', 'paid-member-subscriptions' ); ?></a>
                        </p>
                    </div>

                    <div class="cozmoslabs-form-field-wrapper">
                        <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Members', 'paid-member-subscriptions' ); ?></label>
                        <p class="cozmoslabs-description cozmoslabs-description-align-right">
                            <?php esc_html_e( 'Overview of all your members and their subscription plans. Easily add/remove members or edit their subscription details.', 'paid-member-subscriptions' ); ?>
                            <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/member-management/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank"><?php esc_html_e( 'Learn more', 'paid-member-subscriptions' ); ?></a>
                        </p>
                    </div>

                </div>

                <div>
                    <div class="cozmoslabs-form-field-wrapper">
                        <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Payments', 'paid-member-subscriptions' ); ?></label>
                        <p class="cozmoslabs-description cozmoslabs-description-align-right">
                            <?php esc_html_e( 'Keep track of all member payments, payment statuses, purchased subscription plans but also figure out why a Payment failed.', 'paid-member-subscriptions' ); ?>
                            <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/member-payments/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank"><?php esc_html_e( 'Learn more', 'paid-member-subscriptions' ); ?></a>
                        </p>
                    </div>

                    <div class="cozmoslabs-form-field-wrapper">
                        <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Settings', 'paid-member-subscriptions' ); ?></label>
                        <p class="cozmoslabs-description cozmoslabs-description-align-right">
                            <?php esc_html_e( 'Set the payment gateway used to accept payments, select messages seen by users when accessing a restricted content page or customize default member emails. Everything is just a few clicks away.', 'paid-member-subscriptions' ); ?>
                            <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/settings/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank"><?php esc_html_e( 'Learn more', 'paid-member-subscriptions' ); ?></a>
                        </p>
                    </div>
                </div>

                <div>
                    <div class="cozmoslabs-form-field-wrapper">
                        <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Recurring Payments', 'paid-member-subscriptions' ); ?></label>
                        <p class="cozmoslabs-description cozmoslabs-description-align-right">
                            <?php esc_html_e( 'Setup recurring payments for your subscription plans.', 'paid-member-subscriptions' ); ?>
                            <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/member-payments/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank"><?php esc_html_e( 'Learn more', 'paid-member-subscriptions' ); ?></a>
                        </p>
                    </div>

                    <div class="cozmoslabs-form-field-wrapper">
                        <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Discount Codes', 'paid-member-subscriptions' ); ?></label>
                        <p class="cozmoslabs-description cozmoslabs-description-align-right">
                            <?php esc_html_e( 'Friction-less discount code creation for running promotions, making price reductions or simply rewarding your users.', 'paid-member-subscriptions' ); ?>
                            <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/add-ons/discount-codes/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank"><?php esc_html_e( 'Learn more', 'paid-member-subscriptions' ); ?></a>
                        </p>
                    </div>
                </div>
            </div>

            <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ); ?>assets/images/pms_members_multiple.png" alt="Paid Member Subscriptions Members Page" class="cozmoslabs-section-image" />

        </div>
    </div>

    <div class="cozmoslabs-form-subsection-wrapper">
        <div>
            <h2 class="cozmoslabs-subsection-title"><?php esc_html_e( 'WooCommerce Integration', 'paid-member-subscriptions' );?></h2>
            <p class="cozmoslabs-description">
                <?php esc_html_e( 'Integrates beautifully with WooCommerce, for extended functionality.', 'paid-member-subscriptions' ); ?>
                <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/integration-with-other-plugins/woocommerce/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank"><?php esc_html_e( 'Learn more', 'paid-member-subscriptions' ); ?></a>
            </p>
        </div>

        <div>
            <div>
                <div>
                    <div class="cozmoslabs-form-field-wrapper">
                        <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Restrict Product Viewing & Purchasing', 'paid-member-subscriptions' ); ?></label>
                        <p class="cozmoslabs-description cozmoslabs-description-align-right">
                            <?php esc_html_e( 'Control who can see or purchase a WooCommerce product based on logged in status and subscription plan. Easily create products available to members only.', 'paid-member-subscriptions' ); ?>
                        </p>
                    </div>

                    <div class="cozmoslabs-form-field-wrapper">
                        <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Offer Membership Discounts', 'paid-member-subscriptions' ); ?></label>
                        <p class="cozmoslabs-description cozmoslabs-description-align-right">
                            <?php esc_html_e( 'Offer product discounts to members based on their active subscription. Set discounts globally per subscription plan, or individually per product.', 'paid-member-subscriptions' ); ?>
                        </p>
                    </div>
                </div>

                <div>
                    <div class="cozmoslabs-form-field-wrapper">
                        <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Settings', 'paid-member-subscriptions' ); ?></label>
                        <p class="cozmoslabs-description cozmoslabs-description-align-right">
                            <?php esc_html_e( 'Make use of the extra flexibility by setting custom restriction messages per product, excluding products on sale from membership discounts, allowing cumulative discounts & more.', 'paid-member-subscriptions' ); ?>
                        </p>
                    </div>

                    <div class="cozmoslabs-form-field-wrapper">
                        <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Product Memberships', 'paid-member-subscriptions' ); ?></label>
                        <p class="cozmoslabs-description cozmoslabs-description-align-right">
                            <?php esc_html_e( 'You can associate Subscription Plans with Products in order to sell them through WooCommerce.', 'paid-member-subscriptions' ); ?>
                        </p>
                    </div>
                </div>
            </div>

            <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ); ?>assets/images/pms_woo_member_discount.png" alt="Paid Member Subscriptions WooCommerce Product Member Discount" class="cozmoslabs-section-image" />

        </div>
    </div>

    <div class="cozmoslabs-form-subsection-wrapper">
        <div>
            <h2 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Featured Add-ons', 'paid-member-subscriptions' );?></h2>
            <p class="cozmoslabs-description"><?php esc_html_e( 'Get more functionality by using dedicated Add-ons and tailor Paid Member Subscriptions to your project needs.', 'paid-member-subscriptions' ); ?></p>
        </div>
        <br />
        <div class="cozmoslabs-form-field-wrapper" style="margin-bottom:12px;">
            <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Basic Add-ons', 'paid-member-subscriptions' );?></label>
        </div>
        <?php if( !pms_is_paid_version_active() ) : ?>
            <p class="cozmoslabs-description cozmoslabs-description-upsell" style="width: auto;"><?php printf( wp_kses_post( __( 'These addons extend your WordPress Membership Plugin and are available with the <a href="%s">Basic and PRO</a> versions.', 'paid-member-subscriptions' ) ), 'https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=wpbackend&utm_medium=clientsite&utm_content=basicinfo-addons-basic-link&utm_campaign=PMSFree#pricing' ); ?></p>
        <?php endif; ?>

        <div class="cozmoslabs-basic-info-addons">
            <div>
                <a href="https://www.cozmoslabs.com/add-ons/learndash/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank">
                    <h4 class="pms-add-on-name"><?php esc_html_e( 'LearnDash', 'paid-member-subscriptions' ); ?></h4>
                </a>

                <a href="https://www.cozmoslabs.com/add-ons/learndash/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank" class="pms-addon-image-container">
                    <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ); ?>assets/images/add-on-learndash.png" alt="Learndash" class="pms-addon-image" />
                </a>

                <p class="cozmoslabs-description"><?php esc_html_e( 'Generate revenue from your LMS website by selling access to courses through single or recurring payments. Restrict content of courses, lessons and quizzes to members.', 'paid-member-subscriptions' ); ?></p>
            </div>
            <div>
                <a href="https://www.cozmoslabs.com/add-ons/global-content-restriction/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank">
                    <h4 class="pms-add-on-name"><?php esc_html_e( 'Global Content Restriction', 'paid-member-subscriptions' ); ?></h4>
                </a>

                <a href="https://www.cozmoslabs.com/add-ons/global-content-restriction/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank" class="pms-addon-image-container">
                    <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ); ?>assets/images/add-on-global-content-restriction.png" alt="Global Content Restriction" class="pms-addon-image" />
                </a>

                <p class="cozmoslabs-description"><?php esc_html_e( 'Easy way to add global content restriction rules to subscription plans, based on post type, taxonomy and terms.', 'paid-member-subscriptions' ); ?></p>
            </div>
            <div>
                <a href="https://www.cozmoslabs.com/add-ons/email-reminders/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank">
                    <h4 class="pms-add-on-name"><?php esc_html_e( 'Email Reminders', 'paid-member-subscriptions' ); ?></h4>
                </a>

                <a href="https://www.cozmoslabs.com/add-ons/email-reminders/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank" class="pms-addon-image-container">
                    <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ); ?>assets/images/add-on-email-reminders.png" alt="PayPal Pro and PayPal Express" class="pms-addon-image" />
                </a>

                <p class="cozmoslabs-description"><?php esc_html_e( 'Create multiple automated email reminders that are sent to members before or after certain events take place (subscription expires, subscription activated etc.)', 'paid-member-subscriptions' ); ?></p>
            </div>
            <div>
                <a href="https://www.cozmoslabs.com/add-ons/navigation-menu-filtering/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank">
                    <h4 class="pms-add-on-name"><?php esc_html_e( 'Navigation Menu Filtering', 'paid-member-subscriptions' ); ?></h4>
                </a>

                <a href="https://www.cozmoslabs.com/add-ons/navigation-menu-filtering/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank" class="pms-addon-image-container">
                    <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ); ?>assets/images/add-on-navigation-menu-filtering.png" alt="Navigation Menu Filtering" class="pms-addon-image" />
                </a>

                <p class="cozmoslabs-description"><?php esc_html_e( 'Dynamically display menu items based on logged-in status as well as selected subscription plans.', 'paid-member-subscriptions' ); ?></p>
            </div>
            <div style="clear:left;">
                <a href="https://www.cozmoslabs.com/add-ons/paid-member-subscriptions-bbpress/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank">
                    <h4 class="pms-add-on-name"><?php esc_html_e( 'bbPress', 'paid-member-subscriptions' ); ?></h4>
                </a>
                <a href="https://www.cozmoslabs.com/add-ons/paid-member-subscriptions-bbpress/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank" class="pms-addon-image-container">
                    <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ); ?>assets/images/pms-addon-bbpress.png" alt="bbPress" class="pms-addon-image" />
                </a>

                <p class="cozmoslabs-description"><?php esc_html_e( 'Integrate Paid Member Subscriptions with the popular forums plugin, bbPress.', 'paid-member-subscriptions' ); ?></p>
            </div>
            <div>
                <a href="https://www.cozmoslabs.com/add-ons/fixed-period-membership/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank">
                    <h4 class="pms-add-on-name"><?php esc_html_e( 'Fixed Period Membership', 'paid-member-subscriptions' ); ?></h4>
                </a>

                <a href="https://www.cozmoslabs.com/add-ons/fixed-period-membership/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank" class="pms-addon-image-container">
                    <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ); ?>assets/images/add-on-fixed-period.png" alt="Fixed Period Membership" class="pms-addon-image" />
                </a>

                <p class="cozmoslabs-description"><?php esc_html_e( 'The Fixed Period Membership Add-On allows your Subscriptions to end at a specific date.', 'paid-member-subscriptions' ); ?></p>
            </div>
            <div>
                <a href="https://www.cozmoslabs.com/add-ons/pay-what-you-want/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank">
                    <h4 class="pms-add-on-name"><?php esc_html_e( 'Pay What You Want', 'paid-member-subscriptions' ); ?></h4>
                </a>

                <a href="https://www.cozmoslabs.com/add-ons/pay-what-you-want/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank" class="pms-addon-image-container">
                    <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ); ?>assets/images/add-on-pay-what-you-want.png" alt="Pay What You Want" class="pms-addon-image" />
                </a>

                <p class="cozmoslabs-description"><?php esc_html_e( 'Let subscribers pay what they want by offering a variable pricing option when they purchase a membership plan.', 'paid-member-subscriptions' ); ?></p>
            </div>
        </div>
        <div>
            <?php if( pms_is_paid_version_active() ) : ?>
                <p><a href="<?php echo esc_url( admin_url( 'admin.php?page=pms-addons-page' ) ); ?>" class="button-primary pms-cta"><?php esc_html_e( 'Activate Basic Add-ons', 'paid-member-subscriptions' ); ?></a></p>
            <?php else : ?>
                <p><a href="https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=wpbackend&utm_medium=clientsite&utm_content=basicinfo-addons-basic-btn&utm_campaign=PMSFree#pricing" class="button-primary pms-cta"><?php esc_html_e( 'Get Basic Add-ons', 'paid-member-subscriptions' ); ?></a></p>
            <?php endif; ?>
        </div>

        <br />

        <div class="cozmoslabs-form-field-wrapper" style="margin-bottom:12px;">
            <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Pro Add-ons', 'paid-member-subscriptions' );?></label>
        </div>
        <?php if( !pms_is_paid_version_active() ) : ?>
            <p class="cozmoslabs-description cozmoslabs-description-upsell" style="width: auto;"><?php printf( wp_kses_post( __( 'These addons extend your WordPress Membership Plugin and are available with the <a href="%s">PRO version</a> only.', 'paid-member-subscriptions' ) ), 'https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=wpbackend&utm_medium=clientsite&utm_content=basicinfo-addons-pro-link&utm_campaign=PMSFree#pricing' ); ?></p>
        <?php endif; ?>

        <div class="cozmoslabs-basic-info-addons">
            <div>
                <a href="https://www.cozmoslabs.com/add-ons/paypal-pro-paypal-express/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank">
                    <h4 class="pms-add-on-name"><?php esc_html_e( 'PayPal Express', 'paid-member-subscriptions' ); ?></h4>
                </a>

                <a href="https://www.cozmoslabs.com/add-ons/paypal-pro-paypal-express/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank" class="pms-addon-image-container">
                    <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ); ?>assets/images/add-on-paypal-pro.png" alt="PayPal Express" class="pms-addon-image" />
                </a>

                <p class="cozmoslabs-description"><?php esc_html_e( 'Accept one time or recurring payments through PayPal Express Checkout.', 'paid-member-subscriptions' ); ?></p>
            </div>

            <div>
                <a href="https://www.cozmoslabs.com/add-ons/stripe/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank">
                    <h4 class="pms-add-on-name"><?php esc_html_e( 'Stripe (deprecated)', 'paid-member-subscriptions' ); ?></h4>
                </a>

                <a href="https://www.cozmoslabs.com/add-ons/stripe/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank" class="pms-addon-image-container">
                    <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ); ?>assets/images/add-on-stripe.png" alt="Stripe" class="pms-addon-image" />
                </a>

                <p class="cozmoslabs-description"><?php esc_html_e( 'Accept credit card payments, both one-time and recurring, directly on your website via Stripe.', 'paid-member-subscriptions' ); ?></p>
            </div>

            <div>
                <a href="https://www.cozmoslabs.com/add-ons/content-dripping/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank">
                    <h4 class="pms-add-on-name"><?php esc_html_e( 'Content Dripping', 'paid-member-subscriptions' ); ?></h4>
                </a>

                <a href="https://www.cozmoslabs.com/add-ons/content-dripping/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank" class="pms-addon-image-container">
                    <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ); ?>assets/images/add-on-content-dripping.png" alt="Content Dripping" class="pms-addon-image" />
                </a>

                <p class="cozmoslabs-description"><?php esc_html_e( 'Create schedules for your content, making posts or categories available for your members only after a certain time has passed since they signed up for a subscription plan.', 'paid-member-subscriptions' ); ?></p>
            </div>

            <div>
                <a href="https://www.cozmoslabs.com/add-ons/multiple-subscriptions-per-user/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank">
                    <h4 class="pms-add-on-name"><?php esc_html_e( 'Multiple Subscriptions / User', 'paid-member-subscriptions' ); ?></h4>
                </a>

                <a href="https://www.cozmoslabs.com/add-ons/multiple-subscriptions-per-user/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank" class="pms-addon-image-container">
                    <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ); ?>assets/images/add-on-multiple-subscriptions.png" alt="Multiple Subscriptions per User" class="pms-addon-image" />
                </a>

                <p class="cozmoslabs-description"><?php esc_html_e( 'Setup multiple subscription level blocks and allow members to sign up for more than one subscription plan (one per block).', 'paid-member-subscriptions' ); ?></p>
            </div>

            <div>
                <a href="https://www.cozmoslabs.com/add-ons/invoices/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank">
                    <h4 class="pms-add-on-name"><?php esc_html_e( 'Invoices', 'paid-member-subscriptions' ); ?></h4>
                </a>

                <a href="https://www.cozmoslabs.com/add-ons/invoices/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank" class="pms-addon-image-container">
                    <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ); ?>assets/images/add-on-invoices.png" alt="Invoices" class="pms-addon-image" />
                </a>

                <p class="cozmoslabs-description"><?php esc_html_e( 'This add-on allows you and your members to download PDF invoices for each payment that has been completed.', 'paid-member-subscriptions' ); ?></p>
            </div>

            <div>

                <a href="https://www.cozmoslabs.com/add-ons/group-memberships/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank">
                    <h4 class="pms-add-on-name"><?php esc_html_e( 'Group Memberships', 'paid-member-subscriptions' ); ?></h4>
                </a>

                <a href="https://www.cozmoslabs.com/add-ons/group-memberships/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank" class="pms-addon-image-container">
                    <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ); ?>assets/images/add-on-group-memberships.png" alt="Group Memberships" class="pms-addon-image" />
                </a>

                <p class="cozmoslabs-description"><?php esc_html_e( 'Sell group subscriptions that contain multiple member seats but are managed and purchased by a single account.', 'paid-member-subscriptions' ); ?></p>
            </div>

            <div>
                <a href="https://www.cozmoslabs.com/add-ons/tax-eu-vat/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank">
                    <h4 class="pms-add-on-name"><?php esc_html_e( 'Tax & EU VAT', 'paid-member-subscriptions' ); ?></h4>
                </a>

                <a href="https://www.cozmoslabs.com/add-ons/tax-eu-vat/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank" class="pms-addon-image-container">
                    <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ); ?>assets/images/add-on-tax.png" alt="Tax & EU VAT" class="pms-addon-image" />
                </a>

                <p class="cozmoslabs-description"><?php esc_html_e( 'Collect tax or vat from your users depending on their location, with full control over tax rates and who to charge.', 'paid-member-subscriptions' ); ?></p>
            </div>

            <div>
                <a href="https://www.cozmoslabs.com/add-ons/tax-eu-vat/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank">
                    <h4 class="pms-add-on-name"><?php esc_html_e( 'Pro Rate', 'paid-member-subscriptions' ); ?></h4>
                </a>

                <a href="https://www.cozmoslabs.com/add-ons/pro-rate/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=basic-info" target="_blank" class="pms-addon-image-container">
                    <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ); ?>assets/images/add-on-pro-rate.png" alt="Pro Rate" class="pms-addon-image" />
                </a>

                <p class="cozmoslabs-description"><?php esc_html_e( 'Pro-rate subscription plan Upgrades and Downgrades, offering users a discount based on the remaining time for the current subscription.', 'paid-member-subscriptions' ); ?></p>
            </div>
        </div>

        <div>
            <?php if( pms_is_paid_version_active() ) : ?>
                <p><a href="<?php echo esc_url( admin_url( 'admin.php?page=pms-addons-page' ) ); ?>" class="button-primary pms-cta"><?php esc_html_e( 'Activate Pro Add-ons', 'paid-member-subscriptions' ); ?></a></p>
            <?php else : ?>
                <p><a href="https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=wpbackend&utm_medium=clientsite&utm_content=basicinfo-addons-pro-btn&utm_campaign=PMSFree#pricing" class="button-primary pms-cta"><?php esc_html_e( 'Get Pro Add-ons', 'paid-member-subscriptions' ); ?></a></p>
            <?php endif; ?>
        </div>

    </div>

    <div class="cozmoslabs-form-subsection-wrapper">
        <h2 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Recommended Plugins', 'paid-member-subscriptions' );?></h2>
        <div class="pms-1-3-col cozmoslabs-basic-info-recommended" id="pms-recommended-translate-press">
            <div class="cozmoslabs-basic-info-recommended-img">
                <a href="https://wordpress.org/plugins/translatepress-multilingual/" target="_blank"><img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ) . 'assets/images/pms-trp-cross-promotion.svg'; ?>" alt="TranslatePress Logo"/></a>
            </div>
            <div class="cozmoslabs-basic-info-recommended-info">
                <div class="cozmoslabs-form-field-wrapper">
                    <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Easily translate your entire WordPress website', 'paid-member-subscriptions' ); ?></label>
                </div>

                <p class="cozmoslabs-description"><?php esc_html_e( 'Translate your Paid Member Subscriptions checkout with a WordPress translation plugin that anyone can use.', 'paid-member-subscriptions' ); ?></p>
                <p class="cozmoslabs-description"><?php esc_html_e( 'It offers a simpler way to translate WordPress sites, with full support for WooCommerce and site builders.', 'paid-member-subscriptions' ); ?></p>
                <p><a href="https://wordpress.org/plugins/translatepress-multilingual/" class="button" target="_blank">Find out how</a></p>
            </div>
        </div>

        <div class="pms-1-3-col cozmoslabs-basic-info-recommended" id="pms-recommended-profile-builder">
            <div class="cozmoslabs-basic-info-recommended-img">
                <a href="https://wordpress.org/plugins/profile-builder/" target="_blank"><img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ) . 'assets/images/pb-banner.svg'; ?>" alt="TranslatePress Logo"/></a>
            </div>
            <div class="cozmoslabs-basic-info-recommended-info">
                <div class="cozmoslabs-form-field-wrapper">
                    <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'All in one user profile and user registration plugin for WordPress', 'paid-member-subscriptions' ); ?></label>
                </div>

                <p class="cozmoslabs-description"><?php esc_html_e( 'Capture more user information on the registration form with the help of Profile Builder\'s custom user profile fields.', 'paid-member-subscriptions' ); ?></p>
                <p class="cozmoslabs-description"><?php esc_html_e( 'Add an Email Confirmation process to verify your customers accounts.', 'paid-member-subscriptions' ); ?></p>
                <p><a href="https://wordpress.org/plugins/profile-builder/" class="button" target="_blank">Find out how</a></p>
            </div>
        </div>

        <div class="pms-1-3-col cozmoslabs-basic-info-recommended" id="pms-recommended-wp-webhooks">
            <div class="cozmoslabs-basic-info-recommended-img">
                <a href="https://www.wp-webhooks.com/" target="_blank"><img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ) . 'assets/images/addons/wp-webhooks-banner.svg'; ?>" alt="TranslatePress Logo"/></a>
            </div>
            <div class="cozmoslabs-basic-info-recommended-info">
                <div class="cozmoslabs-form-field-wrapper">
                    <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Save time and money using automations', 'paid-member-subscriptions' ); ?></label>
                </div>

                <p class="cozmoslabs-description"><?php esc_html_e( 'Create no-code automations and workflows on your WordPress site.', 'paid-member-subscriptions' ); ?></p>
                <p class="cozmoslabs-description"><?php esc_html_e( 'Integrates with Profile Builder or Paid Member Subscriptions, depending on which plugin it\'s for.', 'paid-member-subscriptions' ); ?></p>
                <p><a href="https://www.wp-webhooks.com/" class="button" target="_blank">Find out how</a></p>
            </div>
        </div>
    </div>

    <p class="cozmoslabs-notice-message"><i><?php printf( wp_kses_post( __( 'Paid Member Subscriptions comes with an <a href="%s">extensive documentation</a> to assist you.', 'paid-member-subscriptions' ) ),'http://www.cozmoslabs.com/docs/paid-member-subscriptions/' ); ?></i></p>
</div>

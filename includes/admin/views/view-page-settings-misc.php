<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * HTML Output for the Misc tab
 */

?>

<?php $active_sub_tab = ( ! empty( $_GET['nav_sub_tab'] ) ? sanitize_text_field( $_GET['nav_sub_tab'] ) : 'misc_gdpr' ); ?>

    <!-- Sub-tab navigation -->
    <ul class="subsubsub cozmoslabs-nav-sub-tab-wrapper">
        <li class="subsubsub-sub-tab"><a data-sub-tab-slug="misc_gdpr"  href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'pms-settings-page', 'tab' => 'misc', 'nav_sub_tab' => 'misc_gdpr' ), 'admin.php' ) ) ); ?>" class="nav-sub-tab <?php echo ( $active_sub_tab == 'misc_gdpr' ? 'current' : '' ) ?>"><?php esc_html_e( 'GDPR', 'paid-member-subscriptions' ); ?></a></li>
        <li class="subsubsub-sub-tab"><a data-sub-tab-slug="misc_others" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'pms-settings-page', 'tab' => 'misc', 'nav_sub_tab' => 'misc_others' ), 'admin.php' ) ) ); ?>" class="nav-sub-tab <?php echo ( $active_sub_tab == 'misc_others' ? 'current' : '' ) ?>"><?php esc_html_e( 'Others', 'paid-member-subscriptions' ); ?></a></li>
        <li class="subsubsub-sub-tab"><a data-sub-tab-slug="misc_recaptcha" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'pms-settings-page', 'tab' => 'misc', 'nav_sub_tab' => 'misc_recaptcha' ), 'admin.php' ) ) ); ?>" class="nav-sub-tab <?php echo ( $active_sub_tab == 'misc_recaptcha' ? 'current' : '' ) ?>"><?php esc_html_e( 'reCaptcha', 'paid-member-subscriptions' ); ?></a></li>
        <li class="subsubsub-sub-tab"><a data-sub-tab-slug="misc_payments" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'pms-settings-page', 'tab' => 'misc', 'nav_sub_tab' => 'misc_payments' ), 'admin.php' ) ) ); ?>" class="nav-sub-tab <?php echo ( $active_sub_tab == 'misc_payments' ? 'current' : '' ) ?>"><?php esc_html_e( 'Payments', 'paid-member-subscriptions' ); ?></a></li>

        <?php do_action( $this->menu_slug . '_misc_sub_tab_navigation_items', $this->options ); ?>

    </ul>

    <!-- GDPR Sub Tab -->
    <div data-sub-tab-slug="misc_gdpr" class="cozmoslabs-sub-tab-gdpr cozmoslabs-sub-tab <?php echo ( $active_sub_tab == 'misc_gdpr' ? 'tab-active' : '' ); ?>">
        <div class="cozmoslabs-form-subsection-wrapper" id="cozmoslabs-subsection-gdpr-settings">

            <h4 class="cozmoslabs-subsection-title">
                <?php esc_html_e( 'GDPR Settings', 'paid-member-subscriptions' ); ?>
                <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/settings/misc/?utm_source=wpbackend&utm_medium=pms-documentation&utm_campaign=PMSDocs#GDPR" target="_blank" data-code="f223" class="pms-docs-link dashicons dashicons-editor-help"></a>
            </h4>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="gdpr-checkbox"><?php esc_html_e( 'GDPR checkbox on Forms', 'paid-member-subscriptions' ) ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="gdpr-checkbox" name="pms_misc_settings[gdpr][gdpr_checkbox]" value="enabled" <?php echo (isset($this->options['gdpr']['gdpr_checkbox']) && $this->options['gdpr']['gdpr_checkbox'] === 'enabled') ? 'checked' : ''; ?> />
                    <label class="cozmoslabs-toggle-track" for="gdpr-checkbox"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="gdpr-checkbox" class="cozmoslabs-description"><?php esc_html_e( 'Select whether to show a GDPR checkbox on our forms.', 'paid-member-subscriptions' ); ?></label>
                </div>
            </div>

            <div class="cozmoslabs-form-field-wrapper">
                <label class="cozmoslabs-form-field-label" for="gdpr-checkbox-text"><?php esc_html_e( 'GDPR Checkbox Text', 'paid-member-subscriptions' ) ?></label>
                <input type="text" id="gdpr-checkbox-text" class="widefat" name="pms_misc_settings[gdpr][gdpr_checkbox_text]" value="<?php echo ( isset($this->options['gdpr']['gdpr_checkbox_text']) ? esc_attr( $this->options['gdpr']['gdpr_checkbox_text'] ) : esc_html__( 'I allow the website to collect and store the data I submit through this form. *', 'paid-member-subscriptions' ) ); ?>">
                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Text for the GDPR checkbox. You can use {{privacy_policy}} to generate a link for the Privacy policy page.', 'paid-member-subscriptions' ); ?></p>
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="gdpr-delete-button"><?php esc_html_e( 'GDPR Delete Button on Forms', 'paid-member-subscriptions' ) ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="gdpr-delete-button" name="pms_misc_settings[gdpr][gdpr_delete]" value="enabled" <?php echo (isset($this->options['gdpr']['gdpr_delete']) && $this->options['gdpr']['gdpr_delete'] === 'enabled') ? 'checked' : ''; ?> />
                    <label class="cozmoslabs-toggle-track" for="gdpr-delete-button"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="gdpr-delete-button" class="cozmoslabs-description"><?php esc_html_e( 'Select whether to show a GDPR Delete button on our forms.', 'paid-member-subscriptions' ); ?></label>
                </div>
            </div>

            <?php do_action( $this->menu_slug . '_misc_after_gdpr_tab_content', $this->options ); ?>
        </div>

    </div>


    <!-- Others Sub Tab -->
    <div data-sub-tab-slug="misc_others" class="cozmoslabs-sub-tab-others cozmoslabs-sub-tab <?php echo ( $active_sub_tab == 'misc_others' ? 'tab-active' : '' ); ?>">
        <div class="cozmoslabs-form-subsection-wrapper" id="cozmoslabs-subsection-other-settings">
            <h4 class="cozmoslabs-subsection-title">
                <?php esc_html_e( 'Other Settings', 'paid-member-subscriptions' ); ?>
                <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/settings/misc/?utm_source=wpbackend&utm_medium=pms-documentation&utm_campaign=PMSDocs#Others" target="_blank" data-code="f223" class="pms-docs-link dashicons dashicons-editor-help"></a>
            </h4>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="pms-plugin-optin"><?php esc_html_e( 'Marketing Optin' , 'paid-member-subscriptions' ) ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="pms-plugin-optin" name="pms_misc_settings[plugin-optin]" value="yes" <?php echo ( isset( $this->options['plugin-optin'] ) && $this->options['plugin-optin'] == 'yes' ? 'checked' : '' ); ?> />
                    <label class="cozmoslabs-toggle-track" for="pms-plugin-optin"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="pms-plugin-optin" class="cozmoslabs-description"><?php esc_html_e( 'Opt in to our security and feature updates notifications, and non-sensitive diagnostic tracking.', 'paid-member-subscriptions' ); ?></label>
                </div>
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="hide-admin-bar"><?php esc_html_e( 'Admin Bar' , 'paid-member-subscriptions' ) ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="hide-admin-bar" name="pms_misc_settings[hide-admin-bar]" value="1" <?php echo ( isset( $this->options['hide-admin-bar'] ) ? 'checked' : '' ); ?> />
                    <label class="cozmoslabs-toggle-track" for="hide-admin-bar"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="hide-admin-bar" class="cozmoslabs-description"><?php esc_html_e( 'Remove the admin bar from all logged in users except Administrators.', 'paid-member-subscriptions' ); ?></label>
                </div>
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="honeypot-field"><?php esc_html_e( 'Honeypot Field' , 'paid-member-subscriptions' ) ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="honeypot-field" name="pms_misc_settings[honeypot-field]" value="1" <?php echo ( isset( $this->options['honeypot-field'] ) ? 'checked' : '' ); ?> />
                    <label class="cozmoslabs-toggle-track" for="honeypot-field"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="honeypot-field" class="cozmoslabs-description"><?php esc_html_e( 'Add the honeypot field to the PMS Registration form to prevent spambot attacks.', 'paid-member-subscriptions' ); ?></label>
                </div>
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="labels-edit-checkbox"><?php esc_html_e( 'Labels Edit', 'paid-member-subscriptions' ) ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="labels-edit-checkbox" name="pms_misc_settings[labels-edit]" value="enabled" <?php echo ( isset( $this->options['labels-edit'] ) ? 'checked' : '' ); ?> />
                    <label class="cozmoslabs-toggle-track" for="labels-edit-checkbox"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="labels-edit-checkbox" class="cozmoslabs-description"><?php echo wp_kses_post( __( 'Enable the <strong>Labels Edit</strong> functionality in order to change any string that is shown by the plugin.', 'paid-member-subscriptions' ) ); ?></label>
                </div>
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="disable-dashboard-redirect"><?php esc_html_e( 'Dashboard redirect' , 'paid-member-subscriptions' ) ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="disable-dashboard-redirect" name="pms_misc_settings[disable-dashboard-redirect]" value="1" <?php echo ( isset( $this->options['disable-dashboard-redirect'] ) ? 'checked' : '' ); ?> />
                    <label class="cozmoslabs-toggle-track" for="disable-dashboard-redirect"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="disable-dashboard-redirect" class="cozmoslabs-description"><?php esc_html_e( 'By default, regular users cannot access the admin dashboard. This option disables that redirect.', 'paid-member-subscriptions' ); ?></label>
                </div>
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="match-wp-date-format"><?php esc_html_e( 'WordPress Date Format' , 'paid-member-subscriptions' ) ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="match-wp-date-format" name="pms_misc_settings[match-wp-date-format]" value="1" <?php echo ( isset( $this->options['match-wp-date-format'] ) ? 'checked' : '' ); ?> />
                    <label class="cozmoslabs-toggle-track" for="match-wp-date-format"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="match-wp-date-format" class="cozmoslabs-description"><?php esc_html_e( 'The date format selected in WordPress Settings --> General will be used for displaying dates.', 'paid-member-subscriptions' ); ?></label>
                </div>
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="force-subscriptions-expiration-date"><?php esc_html_e( 'Subscriptions Expiration Date' , 'paid-member-subscriptions' ) ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="force-subscriptions-expiration-date" name="pms_misc_settings[force-subscriptions-expiration-date]" value="1" <?php echo ( isset( $this->options['force-subscriptions-expiration-date'] ) ? 'checked' : '' ); ?> />
                    <label class="cozmoslabs-toggle-track" for="force-subscriptions-expiration-date"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="force-subscriptions-expiration-date" class="cozmoslabs-description"><?php esc_html_e( 'Always show Subscriptions Expiration Date.', 'paid-member-subscriptions' ); ?></label>
                </div>

                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'By default, in certain cases, the Expiration Date when editing a Subscription is hidden. Check this option to make it always appear.', 'paid-member-subscriptions' ); ?></p>
                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'You should only enable this option if you are following the advice of our support team or you are sure that you know what you are doing.', 'paid-member-subscriptions' ); ?></p>
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="functions-password-strength-checkbox" ><?php esc_html_e( 'Enable Password Strength', 'paid-member-subscriptions' ) ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="functions-password-strength-checkbox" name="pms_misc_settings[functions-password-strength]" value="enabled" <?php echo ( isset( $this->options['functions-password-strength'] ) ? 'checked' : '' ); ?> />
                    <label class="cozmoslabs-toggle-track" for="functions-password-strength-checkbox"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="functions-password-strength-checkbox" class="cozmoslabs-description"><?php echo wp_kses_post( __( 'Enable the <strong>Password Strength</strong> functionality in order to choose the strength (very weak, weak, good, strong).', 'paid-member-subscriptions' ) ); ?></label>
                </div>
            </div>

            <div class="functions-password-strength-checkbox cozmoslabs-form-field-wrapper" style="<?php echo !isset( $this->options['functions-password-strength'] ) ? 'display:none' : '' ?>">
                <label class="cozmoslabs-form-field-label" for="minimumPasswordLength"><?php esc_html_e( 'Minimum Password Length', 'paid-member-subscriptions' ); ?></label>
                <input type="text" name="pms_misc_settings[minimum_password_length]" class="wppb-text" id="minimumPasswordLength" value="<?php if( !empty( $this->options['minimum_password_length'] ) ) echo esc_attr( $this->options['minimum_password_length'] ); ?>"/>

                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Enter the minimum characters the password should have. Leave empty for no minimum limit', 'paid-member-subscriptions' ); ?></p>
            </div>

            <div class="functions-password-strength-checkbox cozmoslabs-form-field-wrapper" style="<?php echo !isset( $this->options['functions-password-strength'] ) ? 'display:none' : '' ?>">
                <label class="cozmoslabs-form-field-label" for="minimumPasswordStrength"><?php esc_html_e( 'Minimum Password Strength', 'paid-member-subscriptions' ); ?></label>

                <select name="pms_misc_settings[minimum_password_strength]" class="wppb-select" id="minimumPasswordStrength">
                    <option value=""><?php esc_html_e( 'Disabled', 'paid-member-subscriptions' ); ?></option>
                    <option value="short" <?php if ( !empty( $this->options['minimum_password_strength'] ) && $this->options['minimum_password_strength'] == 'short' ) echo 'selected'; ?>><?php esc_html_e( 'Very weak', 'paid-member-subscriptions' ); ?></option>
                    <option value="bad" <?php if ( !empty( $this->options['minimum_password_strength'] ) && $this->options['minimum_password_strength'] == 'bad' ) echo 'selected'; ?>><?php esc_html_e( 'Weak', 'paid-member-subscriptions' ); ?></option>
                    <option value="good" <?php if ( !empty( $this->options['minimum_password_strength'] ) && $this->options['minimum_password_strength'] == 'good' ) echo 'selected'; ?>><?php esc_html_e( 'Medium', 'paid-member-subscriptions' ); ?></option>
                    <option value="strong" <?php if ( !empty( $this->options['minimum_password_strength'] ) && $this->options['minimum_password_strength'] == 'strong' ) echo 'selected'; ?>><?php esc_html_e( 'Strong', 'paid-member-subscriptions' ); ?></option>
                </select>

                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'A stronger password strength will probably force the user to not reuse passwords from other websites.', 'paid-member-subscriptions' ); ?></p>
            </div>

            <div class="cozmoslabs-form-field-wrapper">
                <label class="cozmoslabs-form-field-label" for="cron-jobs"><?php esc_html_e( 'Cron Jobs' , 'paid-member-subscriptions' ) ?></label>

                <a href="<?php echo esc_url( admin_url( wp_nonce_url( 'admin.php?page=pms-settings-page&tab=misc&pms_reset_cron_jobs=true', 'pms_reset_cron_jobs' ) ) ); ?>" class="button-secondary"><?php esc_html_e( 'Reset cron jobs' , 'paid-member-subscriptions' ) ?></a>

                <p class="cozmoslabs-description cozmoslabs-description-align-right">
                    <?php esc_html_e( 'The plugin will try to register the cron jobs that it uses again.', 'paid-member-subscriptions' ); ?>
                </p>
            </div>


        </div>

        <div class="cozmoslabs-form-subsection-wrapper" id="cozmoslabs-subsection-scripts-settings">
            <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Scripts', 'paid-member-subscriptions' ); ?></h4>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="scripts-on-specific-pages"><?php esc_html_e( 'Load Scripts only on specific pages' , 'paid-member-subscriptions' ) ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="scripts-on-specific-pages" name="pms_misc_settings[scripts-on-specific-pages-enabled]" value="1" <?php echo ( isset( $this->options['scripts-on-specific-pages-enabled'] ) ? 'checked' : '' ); ?> />
                    <label class="cozmoslabs-toggle-track" for="scripts-on-specific-pages"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="scripts-on-specific-pages" class="cozmoslabs-description"><?php esc_html_e( 'Optimize the loading of scripts that are coming from Paid Member Subscriptions by only adding them on pages that actually use them in order to improve performance.', 'paid-member-subscriptions' ); ?></label>
                </div>
            </div>

            <div class="pms-scripts-on-specific-pages cozmoslabs-form-field-wrapper" style="<?php echo !isset( $this->options['scripts-on-specific-pages-enabled'] ) ? 'display:none' : '' ?>">
                <label class="cozmoslabs-form-field-label" for="scripts-on-specific-pages"><?php esc_html_e( 'Specific Pages', 'paid-member-subscriptions' ) ?></label>

                <select id="scripts-on-specific-pages" class="pms-chosen" name="pms_misc_settings[scripts-on-specific-pages][]" multiple style="width:200px" data-placeholder="<?php esc_html_e( 'Select pages', 'paid-member-subscriptions' ) ?>">
                    <?php
                    $pages = get_pages();
                    $pages = apply_filters( 'pms_scripts_potential_pages_list', $pages );

                    foreach( $pages as $page )
                        echo '<option value="' . esc_attr( $page->ID ) . '"' . ( !empty( $this->options['scripts-on-specific-pages'] ) && in_array( $page->ID, $this->options['scripts-on-specific-pages'] ) ? ' selected' : '') . '>' . esc_html( $page->post_title ) . ' ( ID: ' . esc_attr( $page->ID ) . ')' . '</option>';
                    ?>
                </select>

                <p class="cozmoslabs-description cozmoslabs-description-space-left">
                    <?php esc_html_e( 'Select the pages where scripts should be loaded. You must select every page that contains a shortcode from Paid Member Subscriptions.', 'paid-member-subscriptions' ); ?>
                </p>
            </div>
        </div>

        <?php do_action( $this->menu_slug . '_misc_after_others_tab_content', $this->options ); ?>

    </div>


    <!-- reCaptcha Sub Tab -->
    <div data-sub-tab-slug="misc_recaptcha" class="cozmoslabs-sub-tab-recaptcha cozmoslabs-sub-tab <?php echo ( $active_sub_tab == 'misc_recaptcha' ? 'tab-active' : '' ); ?>">
        <?php do_action( $this->menu_slug . '_misc_after_recaptcha_tab_content', $this->options ); ?>
    </div>


    <!-- Payments Sub Tab -->
    <div data-sub-tab-slug="misc_payments" class="cozmoslabs-sub-tab-payments cozmoslabs-sub-tab <?php echo ( $active_sub_tab == 'misc_payments' ? 'tab-active' : '' ); ?>">

        <div class="cozmoslabs-form-subsection-wrapper" id="cozmoslabs-subsection-payment-settings">
            <h4 class="cozmoslabs-subsection-title">
                <?php esc_html_e( 'Payment Settings', 'paid-member-subscriptions' ); ?>
                <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/settings/misc/?utm_source=wpbackend&utm_medium=pms-documentation&utm_campaign=PMSDocs#Payments" target="_blank" data-code="f223" class="pms-docs-link dashicons dashicons-editor-help"></a>
            </h4>

            <div class="cozmoslabs-form-field-wrapper">
                <label class="cozmoslabs-form-field-label" for="payment-renew-button-delay"><?php esc_html_e( 'Modify renew button output time', 'paid-member-subscriptions' ) ?></label>
                <input type="text" id="payment-renew-button-delay" class="widefat" name="pms_misc_settings[payments][payment_renew_button_delay]" value="<?php echo ( isset($this->options['payments']['payment_renew_button_delay']) ? esc_attr( $this->options['payments']['payment_renew_button_delay'] ) : '15' ); ?>">
                <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'Insert how many days before the subscription expires, should the renewal button be displayed inside the [pms-account] shortcode.', 'paid-member-subscriptions' ); ?></p>
            </div>

            <div class="cozmoslabs-form-field-wrapper">
                <label class="cozmoslabs-form-field-label" for="redirect-after-manual-payment"><?php esc_html_e( 'Redirect after a manual payment', 'paid-member-subscriptions' ) ?></label>
                <input type="text" id="redirect-after-manual-payment" class="widefat" name="pms_misc_settings[payments][redirect_after_manual_payment]" value="<?php echo ( isset($this->options['payments']['redirect_after_manual_payment']) ? esc_url( $this->options['payments']['redirect_after_manual_payment'] ) : '' ); ?>">
                <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php echo sprintf( esc_html__( 'Insert an URL to redirect the user after a manual payment is made. ( e.g. %s )', 'paid-member-subscriptions' ), esc_url( home_url( '/manual-payment-details' )) );  ?></p>
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="upgrade-downgrade-sign-up-fee"><?php esc_html_e( 'Apply sign-up fees to Upgrades and Downgrades' , 'paid-member-subscriptions' ) ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="upgrade-downgrade-sign-up-fee" name="pms_misc_settings[payments][upgrade_downgrade_sign_up_fee]" value="1" <?php echo ( isset( $this->options['payments']['upgrade_downgrade_sign_up_fee'] ) ? 'checked' : '' ); ?> />
                    <label class="cozmoslabs-toggle-track" for="upgrade-downgrade-sign-up-fee"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="upgrade-downgrade-sign-up-fee" class="cozmoslabs-description"><?php esc_html_e( 'Charge users sign-up fees for Subscription Upgrades and Downgrades.', 'paid-member-subscriptions' ); ?></label>
                </div>
            </div>
        </div>

        <?php if( pms_payment_gateways_support( pms_get_payment_gateways( true ), 'plugin_scheduled_payments' ) ) : ?>

            <div class="cozmoslabs-form-subsection-wrapper" id="cozmoslabs-subsection-payment-retry">
                <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Disabled Subscription Actions' , 'paid-member-subscriptions' ) ?></h4>
                <p class="cozmoslabs-description"><?php esc_html_e( 'Select which subscription actions should be disabled on the [pms-account] shortcode.', 'paid-member-subscriptions' ); ?></p>

                <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                    <label class="cozmoslabs-form-field-label" for="disable-change-button"><?php esc_html_e( 'Change', 'paid-member-subscriptions' ); ?></label>

                    <div class="cozmoslabs-toggle-container">
                        <input type="checkbox" id="disable-change-button" name="pms_misc_settings[disable-change-button]" value="1" <?php echo ( isset( $this->options['disable-change-button'] ) ? 'checked' : '' ); ?> />
                        <label class="cozmoslabs-toggle-track" for="disable-change-button"></label>
                    </div>

                    <div class="cozmoslabs-toggle-description">
                        <label for="disable-change-button" class="cozmoslabs-description"><?php esc_html_e( 'Disable CHANGE Subscription Action.', 'paid-member-subscriptions' ); ?></label>
                    </div>
                </div>

                <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                    <label class="cozmoslabs-form-field-label" for="disable-renew-button"><?php esc_html_e( 'Renew', 'paid-member-subscriptions' ); ?></label>

                    <div class="cozmoslabs-toggle-container">
                        <input type="checkbox" id="disable-renew-button" name="pms_misc_settings[disable-renew-button]" value="1" <?php echo ( isset( $this->options['disable-renew-button'] ) ? 'checked' : '' ); ?> />
                        <label class="cozmoslabs-toggle-track" for="disable-renew-button"></label>
                    </div>

                    <div class="cozmoslabs-toggle-description">
                        <label for="disable-renew-button" class="cozmoslabs-description"><?php esc_html_e( 'Disable RENEW Subscription Action.', 'paid-member-subscriptions' ); ?></label>
                    </div>
                </div>

                <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                    <label class="cozmoslabs-form-field-label" for="disable-cancel-button"><?php esc_html_e( 'Cancel', 'paid-member-subscriptions' ); ?></label>

                    <div class="cozmoslabs-toggle-container">
                        <input type="checkbox" id="disable-cancel-button" name="pms_misc_settings[disable-cancel-button]" value="1" <?php echo ( isset( $this->options['disable-cancel-button'] ) ? 'checked' : '' ); ?> />
                        <label class="cozmoslabs-toggle-track" for="disable-cancel-button"></label>
                    </div>

                    <div class="cozmoslabs-toggle-description">
                        <label for="disable-cancel-button" class="cozmoslabs-description"><?php esc_html_e( 'Disable CANCEL Subscription Action.', 'paid-member-subscriptions' ); ?></label>
                    </div>
                </div>

                <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                    <label class="cozmoslabs-form-field-label" for="disable-abandon-button"><?php esc_html_e( 'Abandon', 'paid-member-subscriptions' ); ?></label>

                    <div class="cozmoslabs-toggle-container">
                        <input type="checkbox" id="disable-abandon-button" name="pms_misc_settings[disable-abandon-button]" value="1" <?php echo ( isset( $this->options['disable-abandon-button'] ) ? 'checked' : '' ); ?> />
                        <label class="cozmoslabs-toggle-track" for="disable-abandon-button"></label>
                    </div>

                    <div class="cozmoslabs-toggle-description">
                        <label for="disable-abandon-button" class="cozmoslabs-description"><?php esc_html_e( 'Disable ABANDON Subscription Action.', 'paid-member-subscriptions' ); ?></label>
                    </div>
                </div>

            </div>

            <div class="cozmoslabs-form-subsection-wrapper" id="cozmoslabs-subsection-payment-retry">
                <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Payment Retry', 'paid-member-subscriptions' ); ?></h4>

                <div class="cozmoslabs-form-field-wrapper">
                    <label class="cozmoslabs-form-field-label" for="payment-retry-max-retry-amount"><?php esc_html_e( 'Maximum number of retries', 'paid-member-subscriptions' ) ?></label>
                    <input type="text" id="payment-retry-max-retry-amount" class="widefat" name="pms_misc_settings[payments][payment_retry_max_retry_amount]" value="<?php echo ( isset($this->options['payments']['payment_retry_max_retry_amount']) ? esc_attr( $this->options['payments']['payment_retry_max_retry_amount'] ) : esc_attr( apply_filters( 'pms_retry_payment_count', 3, '' ) ) ); ?>">
                    <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'Enter how many retries the payment retry functionality should attempt.', 'paid-member-subscriptions' ); ?></p>
                </div>

                <div class="cozmoslabs-form-field-wrapper">
                    <label class="cozmoslabs-form-field-label" for="payment-retry-retry-interval"><?php esc_html_e( 'Retry Interval', 'paid-member-subscriptions' ) ?></label>
                    <input type="text" id="payment-retry-retry-interval" class="widefat" name="pms_misc_settings[payments][payment_retry_retry_interval]" value="<?php echo ( isset($this->options['payments']['payment_retry_retry_interval']) ? esc_attr( $this->options['payments']['payment_retry_retry_interval'] ) : esc_attr( apply_filters( 'pms_retry_payment_interval', 3, '' ) ) ); ?>">
                    <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'Enter the interval between retries for the payment retry functionality.', 'paid-member-subscriptions' ); ?></p>
                </div>
            </div>

        <?php endif; ?>

        <?php do_action( $this->menu_slug . '_misc_after_payments_tab_content', $this->options ); ?>
    </div>


<?php do_action( $this->menu_slug . '_misc_after_subtabs', $this->options ); ?>
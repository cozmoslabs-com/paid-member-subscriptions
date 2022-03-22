<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * HTML Output for the Misc tab
 */

?>

<?php $active_sub_tab = ( ! empty( $_GET['nav_sub_tab'] ) ? sanitize_text_field( $_GET['nav_sub_tab'] ) : 'misc_gdpr' ); ?>

<!-- Sub-tab navigation -->
<ul class="pms-nav-sub-tab-wrapper subsubsub">
    <li class="subsubsub-sub-tab"><a data-sub-tab-slug="misc_gdpr"  href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'pms-settings-page', 'tab' => 'misc', 'nav_sub_tab' => 'misc_gdpr' ), 'admin.php' ) ) ); ?>" class="nav-sub-tab <?php echo ( $active_sub_tab == 'misc_gdpr' ? 'current' : '' ) ?>"><?php esc_html_e( 'GDPR', 'paid-member-subscriptions' ); ?></a></li>
    <li class="subsubsub-sub-tab"><a data-sub-tab-slug="misc_others" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'pms-settings-page', 'tab' => 'misc', 'nav_sub_tab' => 'misc_others' ), 'admin.php' ) ) ); ?>" class="nav-sub-tab <?php echo ( $active_sub_tab == 'misc_others' ? 'current' : '' ) ?>"><?php esc_html_e( 'Others', 'paid-member-subscriptions' ); ?></a></li>
    <li class="subsubsub-sub-tab"><a data-sub-tab-slug="misc_recaptcha" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'pms-settings-page', 'tab' => 'misc', 'nav_sub_tab' => 'misc_recaptcha' ), 'admin.php' ) ) ); ?>" class="nav-sub-tab <?php echo ( $active_sub_tab == 'misc_recaptcha' ? 'current' : '' ) ?>"><?php esc_html_e( 'reCaptcha', 'paid-member-subscriptions' ); ?></a></li>
    <li class="subsubsub-sub-tab"><a data-sub-tab-slug="misc_payments" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'pms-settings-page', 'tab' => 'misc', 'nav_sub_tab' => 'misc_payments' ), 'admin.php' ) ) ); ?>" class="nav-sub-tab <?php echo ( $active_sub_tab == 'misc_payments' ? 'current' : '' ) ?>"><?php esc_html_e( 'Payments', 'paid-member-subscriptions' ); ?></a></li>

    <?php do_action( $this->menu_slug . '_misc_sub_tab_navigation_items', $this->options ); ?>

</ul>

<!-- Divider -->
<hr style="margin-top: 9px;" />

<!-- GDPR Sub Tab -->
<div data-sub-tab-slug="misc_gdpr" class="pms-sub-tab pms-sub-tab-gdpr <?php echo ( $active_sub_tab == 'misc_gdpr' ? 'tab-active' : '' ); ?>">

    <div class="pms-form-field-wrapper">
        <label class="pms-form-field-label" for="gdpr-checkbox"><?php esc_html_e( 'GDPR checkbox on Forms', 'paid-member-subscriptions' ) ?></label>

        <select id="gdpr-checkbox" name="pms_misc_settings[gdpr][gdpr_checkbox]">
            <option value="disabled" <?php ( isset( $this->options['gdpr']['gdpr_checkbox'] ) ? selected( $this->options['gdpr']['gdpr_checkbox'], 'disabled', true ) : ''); ?>><?php esc_html_e( 'Disabled', 'paid-member-subscriptions' ); ?></option>
            <option value="enabled" <?php ( isset( $this->options['gdpr']['gdpr_checkbox'] ) ? selected( $this->options['gdpr']['gdpr_checkbox'], 'enabled', true ) : ''); ?>><?php esc_html_e( 'Enabled', 'paid-member-subscriptions' ); ?></option>
        </select>

        <p class="description"><?php esc_html_e( 'Select whether to show a GDPR checkbox on our forms.', 'paid-member-subscriptions' ); ?></p>
    </div>

    <div class="pms-form-field-wrapper">
        <label class="pms-form-field-label" for="gdpr-checkbox-text"><?php esc_html_e( 'GDPR Checkbox Text', 'paid-member-subscriptions' ) ?></label>
        <input type="text" id="gdpr-checkbox-text" class="widefat" name="pms_misc_settings[gdpr][gdpr_checkbox_text]" value="<?php echo ( isset($this->options['gdpr']['gdpr_checkbox_text']) ? esc_attr( $this->options['gdpr']['gdpr_checkbox_text'] ) : esc_html__( 'I allow the website to collect and store the data I submit through this form. *', 'paid-member-subscriptions' ) ); ?>">
        <p class="description"><?php esc_html_e( 'Text for the GDPR checkbox. You can use {{privacy_policy}} to generate a link for the Privacy policy page.', 'paid-member-subscriptions' ); ?></p>
    </div>

    <div class="pms-form-field-wrapper">
        <label class="pms-form-field-label" for="gdpr-delete-button"><?php esc_html_e( 'GDPR Delete Button on Forms', 'paid-member-subscriptions' ) ?></label>

        <select id="gdpr-delete-button" name="pms_misc_settings[gdpr][gdpr_delete]">
            <option value="disabled" <?php ( isset( $this->options['gdpr']['gdpr_delete'] ) ? selected( $this->options['gdpr']['gdpr_delete'], 'disabled', true ) : ''); ?>><?php esc_html_e( 'Disabled', 'paid-member-subscriptions' ); ?></option>
            <option value="enabled" <?php ( isset( $this->options['gdpr']['gdpr_delete'] ) ? selected( $this->options['gdpr']['gdpr_delete'], 'enabled', true ) : ''); ?>><?php esc_html_e( 'Enabled', 'paid-member-subscriptions' ); ?></option>
        </select>

        <p class="description"><?php esc_html_e( 'Select whether to show a GDPR Delete button on our forms.', 'paid-member-subscriptions' ); ?></p>
    </div>

    <?php do_action( $this->menu_slug . '_misc_after_gdpr_tab_content', $this->options ); ?>

</div>


<!-- Others Sub Tab -->
<div data-sub-tab-slug="misc_others" class="pms-sub-tab pms-sub-tab-others <?php echo ( $active_sub_tab == 'misc_others' ? 'tab-active' : '' ); ?>">

    <div class="pms-form-field-wrapper">
        <label class="pms-form-field-label" for="allow-usage-tracking"><?php esc_html_e( 'Usage Tracking' , 'paid-member-subscriptions' ) ?></label>

        <p class="description">
            <input type="checkbox" id="allow-usage-tracking" name="pms_misc_settings[allow-usage-tracking]" value="1" <?php echo ( isset( $this->options['allow-usage-tracking'] ) ? 'checked' : '' ); ?> /><?php printf( esc_html__( 'Allow Paid Member Subscriptions to anonymously track the plugin\'s usage. Data provided by this tracking helps us improve the plugin.<br> No sensitive data is shared. %sLearn More%s', 'paid-member-subscriptions' ), '<a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/usage-tracking/" target="_blank">', '</a>' ); ?>
        </p>
    </div>

    <div class="pms-form-field-wrapper">
        <label class="pms-form-field-label" for="hide-admin-bar"><?php esc_html_e( 'Admin Bar' , 'paid-member-subscriptions' ) ?></label>

        <p class="description">
            <input type="checkbox" id="hide-admin-bar" name="pms_misc_settings[hide-admin-bar]" value="1" <?php echo ( isset( $this->options['hide-admin-bar'] ) ? 'checked' : '' ); ?> /><?php esc_html_e( 'Hide admin bar', 'paid-member-subscriptions' ); ?>
        </p>
        <p class="description">
            <?php esc_html_e( 'By checking this option, the admin bar will be removed from all logged in users except Administrators.', 'paid-member-subscriptions' ); ?>
        </p>
    </div>

    <div class="pms-form-field-wrapper">
        <label class="pms-form-field-label" for="cron-jobs"><?php esc_html_e( 'Cron Jobs' , 'paid-member-subscriptions' ) ?></label>

        <p class="description">
            <a href="<?php echo esc_url( admin_url( wp_nonce_url( 'admin.php?page=pms-settings-page&tab=misc&pms_reset_cron_jobs=true', 'pms_reset_cron_jobs' ) ) ); ?>" class="button-primary"><?php esc_html_e( 'Reset cron jobs' , 'paid-member-subscriptions' ) ?></a>
        </p>
        <p class="description">
            <?php esc_html_e( 'By clicking this button, the plugin will try to register the cron jobs that it uses again.', 'paid-member-subscriptions' ); ?>
        </p>
    </div>

    <div class="pms-form-field-wrapper">
        <label class="pms-form-field-label" for="honeypot-field"><?php esc_html_e( 'Honeypot Field' , 'paid-member-subscriptions' ) ?></label>

        <p class="description">
            <input type="checkbox" id="honeypot-field" name="pms_misc_settings[honeypot-field]" value="1" <?php echo ( isset( $this->options['honeypot-field'] ) ? 'checked' : '' ); ?> /><?php esc_html_e( 'Enable honeypot field to prevent spambot attacks', 'paid-member-subscriptions' ); ?>
        </p>
        <p class="description">
            <?php esc_html_e( 'By checking this option, the honeypot field will be added to the PMS Registration form.', 'paid-member-subscriptions' ); ?>
        </p>
    </div>

    <div class="pms-form-field-wrapper">
        <label class="pms-form-field-label" for="labels-edit-checkbox"><?php esc_html_e( 'Labels Edit', 'paid-member-subscriptions' ) ?></label>

        <select id="labels-edit-checkbox" name="pms_misc_settings[labels-edit]">
            <option value="disabled" <?php ( isset( $this->options['labels-edit'] ) ? selected( $this->options['labels-edit'], 'disabled', true ) : ''); ?>><?php esc_html_e( 'Disabled', 'paid-member-subscriptions' ); ?></option>
            <option value="enabled" <?php ( isset( $this->options['labels-edit'] ) ? selected( $this->options['labels-edit'], 'enabled', true ) : ''); ?>><?php esc_html_e( 'Enabled', 'paid-member-subscriptions' ); ?></option>
        </select>

        <p class="description"><?php echo wp_kses_post( __( 'Enable the <strong>Labels Edit</strong> functionality in order to change any string that is shown by the plugin.', 'paid-member-subscriptions' ) ); ?></p>
    </div>

    <div class="pms-form-field-wrapper">
        <label class="pms-form-field-label" for="disable-dashboard-redirect"><?php esc_html_e( 'Dashboard redirect' , 'paid-member-subscriptions' ) ?></label>

        <p class="description">
            <input type="checkbox" id="disable-dashboard-redirect" name="pms_misc_settings[disable-dashboard-redirect]" value="1" <?php echo ( isset( $this->options['disable-dashboard-redirect'] ) ? 'checked' : '' ); ?> /><?php esc_html_e( 'Disable dashboard redirect', 'paid-member-subscriptions' ); ?>
        </p>
        <p class="description">
            <?php esc_html_e( 'By default, regular users cannot access the admin dashboard. This option disables that redirect.', 'paid-member-subscriptions' ); ?>
        </p>
    </div>

    <?php do_action( $this->menu_slug . '_misc_after_others_tab_content', $this->options ); ?>

</div>


<!-- reCaptcha Sub Tab -->
<div data-sub-tab-slug="misc_recaptcha" class="pms-sub-tab pms-sub-tab-recaptcha <?php echo ( $active_sub_tab == 'misc_recaptcha' ? 'tab-active' : '' ); ?>">
    <?php do_action( $this->menu_slug . '_misc_after_recaptcha_tab_content', $this->options ); ?>
</div>


<!-- Payments Sub Tab -->
<div data-sub-tab-slug="misc_payments" class="pms-sub-tab pms-sub-tab-payments <?php echo ( $active_sub_tab == 'misc_payments' ? 'tab-active' : '' ); ?>">

    <div class="pms-form-field-wrapper">
        <label class="pms-form-field-label" for="payment-renew-button-delay"><?php esc_html_e( 'Modify renew button output time', 'paid-member-subscriptions' ) ?></label>
        <input type="text" id="payment-renew-button-delay" class="widefat" name="pms_misc_settings[payments][payment_renew_button_delay]" value="<?php echo ( isset($this->options['payments']['payment_renew_button_delay']) ? esc_attr( $this->options['payments']['payment_renew_button_delay'] ) : '15' ); ?>">
        <p class="description"><?php esc_html_e( 'Insert how many days before the subscription expires, should the renewal button be displayed inside the [pms-account] shortcode.', 'paid-member-subscriptions' ); ?></p>
    </div>

    <div class="pms-form-field-wrapper">
        <label class="pms-form-field-label" for="redirect-after-manual-payment"><?php esc_html_e( 'Redirect after a manual payment', 'paid-member-subscriptions' ) ?></label>
        <input type="text" id="redirect-after-manual-payment" class="widefat" name="pms_misc_settings[payments][redirect_after_manual_payment]" value="<?php echo ( isset($this->options['payments']['redirect_after_manual_payment']) ? esc_url( $this->options['payments']['redirect_after_manual_payment'] ) : '' ); ?>">
        <p class="description"><?php echo sprintf( esc_html__( 'Insert an URL to redirect the user after a manual payment is made. ( e.g. %s )', 'paid-member-subscriptions' ), esc_url( home_url( '/manual-payment-details' )) );  ?></p>
    </div>

    <div class="pms-form-field-wrapper">
        <label class="pms-form-field-label" for="upgrade-downgrade-sign-up-fee"><?php esc_html_e( 'Apply sign-up fees to Upgrades and Downgrades' , 'paid-member-subscriptions' ) ?></label>

        <p class="description"><input type="checkbox" id="upgrade-downgrade-sign-up-fee" name="pms_misc_settings[payments][upgrade_downgrade_sign_up_fee]" value="1" <?php echo ( isset( $this->options['payments']['upgrade_downgrade_sign_up_fee'] ) ? 'checked' : '' ); ?> /><?php esc_html_e( 'Charge users sign-up fees for Subscription Upgrades and Downgrades.', 'paid-member-subscriptions' ); ?></p>
    </div>

    <?php do_action( $this->menu_slug . '_misc_after_payments_tab_content', $this->options ); ?>
</div>


<?php do_action( $this->menu_slug . '_misc_after_subtabs', $this->options ); ?>
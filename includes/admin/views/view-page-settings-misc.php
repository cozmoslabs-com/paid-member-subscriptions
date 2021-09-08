<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * HTML Output for the Misc tab
 */

?>

<div id="gdpr-general">

    <h3><?php esc_html_e( 'GDPR', 'paid-member-subscriptions' ); ?></h3>

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

    <h3><?php esc_html_e( 'Others', 'paid-member-subscriptions' ); ?></h3>

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

    <?php do_action( $this->menu_slug . '_misc_after_content', $this->options ); ?>

</div>

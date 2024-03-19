<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<h3><?php esc_html_e( 'Design & User Experience Settings', 'paid-member-subscriptions' ); ?></h3>
<p class="cozmoslabs-description pms-setup-payment-settings__description"><?php esc_html_e( 'Customize the way your users interact with the website!', 'paid-member-subscriptions' ); ?></p>

<form class="pms-setup-form" method="post">

    <div class="pms-setup-form-styles">
        <?php
            if ( ( defined( 'PMS_PAID_PLUGIN_DIR' ) && file_exists( PMS_PAID_PLUGIN_DIR . '/add-ons-basic/form-designs/form-designs.php' ) ) || ( PAID_MEMBER_SUBSCRIPTIONS === 'Paid Member Subscriptions Dev' && file_exists( PMS_PLUGIN_DIR_PATH . '/add-ons-basic/form-designs/form-designs.php' ) ) ) {
                echo pms_render_forms_design_selector(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                ?>

                <p class="info">
                    <?php echo wp_kses_post( __( 'Choose a style that better suits your website.<br>The default style is there to let you customize the CSS and in general will receive the look and feel from your own themes styling.', 'paid-member-subscriptions' ) ); ?>
                </p>
                <?php
            } elseif ( PAID_MEMBER_SUBSCRIPTIONS === 'Paid Member Subscriptions' ) {
                echo pms_display_form_designs_preview(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                printf( esc_html__( '%3$sYou can now beautify your forms using pre-made templates. Enable Form Designs by upgrading to %1$sBasic or PRO versions%2$s.%4$s', 'paid-member-subscriptions' ),'<a href="https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=wpbackend&utm_medium=clientsite&utm_content=setup-wizard-form-designs&utm_campaign=PMSFree#pricing" target="_blank">', '</a>', '<p class="pms-setup-form-styles__upsell">', '</p>' );
            }
        ?>
    </div>

    <strong class="pms-setup-general-settings__heading"><?php esc_html_e( 'Optimize the login and registration flow for your members!', 'paid-member-subscriptions' ); ?></strong>

    <div class="pms-setup-general-settings" title="<?php esc_html_e( 'Login users automatically after registration.', 'paid-member-subscriptions' ); ?>">
        <div class="pms-setup-general-settings__item">
            <div class="cozmoslabs-toggle-switch">
                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="pms_automatically_login" name="pms_automatically_login" value="1" <?php echo $this->check_value( 'automatically_log_in' ) ? 'checked' : '' ?> />
                    <label class="cozmoslabs-toggle-track" for="pms_automatically_login"></label>
                </div>
            </div>
            <label for="pms_automatically_login"><?php esc_html_e( 'Automatically log users in after registration', 'paid-member-subscriptions' ); ?></label>
        </div>

        <div class="pms-setup-general-settings__item" title="<?php esc_html_e( 'The WordPress Admin Bar will only be visible for administrators.', 'paid-member-subscriptions' ); ?>">
            <div class="cozmoslabs-toggle-switch">
                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="pms_hide_admin_bar" name="pms_hide_admin_bar" value="1" <?php echo $this->check_value( 'hide-admin-bar' ) ? 'checked' : '' ?> />
                    <label class="cozmoslabs-toggle-track" for="pms_hide_admin_bar"></label>
                </div>
            </div>
            <label for="pms_hide_admin_bar"><?php esc_html_e( 'Hide the admin bar for members', 'paid-member-subscriptions' ); ?></label>
        </div>

        <div class="pms-setup-general-settings__item" title="<?php esc_html_e( 'If you enable this option you must log in via the Front-End Login Form as an Admin.', 'paid-member-subscriptions' ); ?>">
            <div class="cozmoslabs-toggle-switch">
                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="pms_redirect_default" name="pms_redirect_default" value="1" <?php echo $this->check_value( 'redirect_default_wp' ) ? 'checked' : '' ?> />
                    <label class="cozmoslabs-toggle-track" for="pms_redirect_default"></label>
                </div>
            </div>
            <label for="pms_redirect_default"><?php esc_html_e( 'Redirect Default WordPress Login Pages', 'paid-member-subscriptions' ); ?></label>
        </div>

        <div class="pms-setup-general-settings__item" title="<?php esc_html_e( 'If the current user\'s session has been taken over by a newer session, we will log him out and he will have to login again.', 'paid-member-subscriptions' ); ?>">
            <div class="cozmoslabs-toggle-switch">
                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="pms_account_sharing" name="pms_account_sharing" value="1" <?php echo $this->check_value( 'prevent_account_sharing' ) ? 'checked' : '' ?> />
                    <label class="cozmoslabs-toggle-track" for="pms_account_sharing"></label>
                </div>
            </div>
            <label for="pms_account_sharing"><?php esc_html_e( 'Prevent account sharing', 'paid-member-subscriptions' ); ?></label>
        </div>

    </div>

    <div class="pms-setup-form-button">
        <input type="submit" class="button primary button-primary button-hero" value="<?php esc_html_e( 'Continue', 'paid-member-subscriptions' ); ?>" />
    </div>

    <?php wp_nonce_field( 'pms-setup-wizard-nonce', 'pms_setup_wizard_nonce' ); ?>
</form>

<?php

/**
 * Adds content for the reCAPTCHA tab
 *
 * @param string $output     Tab content
 * @param string $active_tab Current active tab
 * @param array $options     The PMS settings options
 *
 */
function pms_recaptcha_settings_tab( $options ) {

    ob_start();

    $display_forms = array(
        'register'                    => esc_html__( 'Register Form', 'paid-member-subscriptions' ),
        'login'                       => esc_html__( 'Login Form', 'paid-member-subscriptions' ),
        'recover_password'            => esc_html__( 'Reset Password Form', 'paid-member-subscriptions' ),
        'default_wp_register'         => esc_html__( 'Default WordPress Register Form', 'paid-member-subscriptions' ),
        'default_wp_login'            => esc_html__( 'Default WordPress Login Form', 'paid-member-subscriptions' ),
        'default_wp_recover_password' => esc_html__( 'Default WordPress Reset Password Form', 'paid-member-subscriptions' ),
    );

    ?>

    <div id="pms-settings-recaptcha" class="pms-tab tab-active">
        <div class="cozmoslabs-form-subsection-wrapper" id="cozmoslabs-subsection-recaptcha">
            <h4 class="cozmoslabs-subsection-title">
                <?php esc_html_e( 'reCaptcha Settings', 'paid-member-subscriptions' ); ?>
                <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/settings/misc/recaptcha/?utm_source=wpbackend&utm_medium=pms-documentation&utm_campaign=PMSDocs" target="_blank" data-code="f223" class="pms-docs-link dashicons dashicons-editor-help"></a>
            </h4>

                <div class="cozmoslabs-form-field-wrapper">
                    <label class="cozmoslabs-form-field-label" for="recaptcha-site-key"><?php esc_html_e( 'Site Key', 'paid-member-subscriptions' ) ?></label>
                    <input id="recaptcha-site-key" type="text" class="widefat" name="pms_misc_settings[recaptcha][site_key]" value="<?php echo ( !empty( $options['recaptcha']['site_key'] ) ? esc_attr( $options['recaptcha']['site_key'] ) : '' ) ?>" />
                    <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php echo wp_kses_post( sprintf( __( 'The site key from %1$sGoogle%2$s', 'paid-member-subscriptions' ), '<a href="https://www.google.com/recaptcha/admin/create" target="_blank">', '</a>' ) ) ?></p>
                </div>

                <div class="cozmoslabs-form-field-wrapper">
                    <label class="cozmoslabs-form-field-label" for="recaptcha-secret-key"><?php esc_html_e( 'Secret Key', 'paid-member-subscriptions' ); ?></label>
                    <input id="recaptcha-secret-key" type="text" class="widefat" name="pms_misc_settings[recaptcha][secret_key]" value="<?php echo ( !empty( $options['recaptcha']['secret_key'] ) ? esc_attr( $options['recaptcha']['secret_key'] ) : '' ) ?>" />
                    <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php echo wp_kses_post( sprintf( __( 'The secret key from %1$sGoogle%2$s', 'paid-member-subscriptions' ), '<a href="https://www.google.com/recaptcha/admin/create" target="_blank">', '</a>' ) ) ?></p>
                </div>
        </div>

        <div class="cozmoslabs-form-subsection-wrapper" id="cozmoslabs-subsection-recaptcha-forms">
            <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'reCaptcha Visibility', 'paid-member-subscriptions' ); ?></h4>

                <?php foreach( $display_forms as $key => $value ) : ?>

                    <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                        <label class="cozmoslabs-form-field-label" for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value ); ?></label>

                        <div class="cozmoslabs-toggle-container">
                            <input type="checkbox" id="<?php echo esc_attr( $key ); ?>" name="pms_misc_settings[recaptcha][display_form][]" value="<?php echo esc_attr( $key ) ?>" <?php echo ( !empty( $options['recaptcha']['display_form'] ) && in_array( $key, $options['recaptcha']['display_form'] ) ? 'checked="checked"' : '' ); ?>>
                            <label class="cozmoslabs-toggle-track" for="<?php echo esc_attr( $key ); ?>"></label>
                        </div>

                        <div class="cozmoslabs-toggle-description">
                            <label for="<?php echo esc_attr( $key ); ?>" class="cozmoslabs-description"><?php echo wp_kses_post( sprintf( __( 'Display reCaptcha on %s', 'paid-member-subscriptions' ), '<strong>' . esc_html( $value ) . '</strong>' ) ); ?></label>
                        </div>
                    </div>

                <?php endforeach; ?>
        </div>

    </div>

    <?php
    $output = ob_get_clean();

    echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'pms-settings-page_misc_after_recaptcha_tab_content', 'pms_recaptcha_settings_tab' );

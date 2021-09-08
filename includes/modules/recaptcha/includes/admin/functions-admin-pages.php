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

        <h3><?php esc_html_e( 'reCAPTCHA V2', 'paid-member-subscriptions' ); ?></h3>

        <div class="pms-form-field-wrapper">
            <label class="pms-form-field-label" for="recaptcha-site-key"><?php esc_html_e( 'Site Key', 'paid-member-subscriptions' ) ?></label>
            <input id="recaptcha-site-key" type="text" class="widefat" name="pms_misc_settings[recaptcha][site_key]" value="<?php echo ( !empty( $options['recaptcha']['site_key'] ) ? esc_attr( $options['recaptcha']['site_key'] ) : '' ) ?>" />
            <p class="description"><?php echo wp_kses_post( sprintf( __( 'The site key from %1$sGoogle%2$s', 'paid-member-subscriptions' ), '<a href="https://www.google.com/recaptcha/admin/create" target="_blank">', '</a>' ) ) ?></p>
        </div>

        <div class="pms-form-field-wrapper">
            <label class="pms-form-field-label" for="recaptcha-secret-key"><?php esc_html_e( 'Secret Key', 'paid-member-subscriptions' ); ?></label>
            <input id="recaptcha-secret-key" type="text" class="widefat" name="pms_misc_settings[recaptcha][secret_key]" value="<?php echo ( !empty( $options['recaptcha']['secret_key'] ) ? esc_attr( $options['recaptcha']['secret_key'] ) : '' ) ?>" />
            <p class="description"><?php echo wp_kses_post( sprintf( __( 'The secret key from %1$sGoogle%2$s', 'paid-member-subscriptions' ), '<a href="https://www.google.com/recaptcha/admin/create" target="_blank">', '</a>' ) ) ?></p>
        </div>

        <div class="pms-form-field-wrapper">
            <label class="pms-form-field-label"><?php esc_html_e( 'Display on', 'paid-member-subscriptions' ); ?></label>

            <?php foreach( $display_forms as $key => $value ) : ?>

                <label>
                    <input type="checkbox" name="pms_misc_settings[recaptcha][display_form][]" value="<?php echo esc_attr( $key ) ?>" <?php echo ( !empty( $options['recaptcha']['display_form'] ) && in_array( $key, $options['recaptcha']['display_form'] ) ? 'checked="checked"' : '' ); ?>><?php echo esc_html( $value ); ?>
                </label>
                <br>

            <?php endforeach; ?>
        </div>

    </div>

    <?php
    $output = ob_get_clean();

    echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'pms-settings-page_misc_after_content', 'pms_recaptcha_settings_tab' );

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
                <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/settings/misc/recaptcha/?utm_source=pms-misc-settings&utm_medium=client-site&utm_campaign=pms-recaptcha-docs" target="_blank" data-code="f223" class="pms-docs-link dashicons dashicons-editor-help"></a>
            </h4>
            
                <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                    <label class="cozmoslabs-form-field-label" for="recaptcha-site-v3"><?php esc_html_e( 'Use reCAPTCHA v3' , 'paid-member-subscriptions' ) ?></label>
    
                    <div class="cozmoslabs-toggle-container">
                        <input type="checkbox" id="recaptcha-site-v3" name="pms_misc_settings[recaptcha][v3]" value="yes" <?php echo ( !empty( $options['recaptcha']['v3'] ) && $options['recaptcha']['v3'] == 'yes' ? 'checked' : '' ); ?> />
                        <label class="cozmoslabs-toggle-track" for="recaptcha-site-v3"></label>
                    </div>
    
                    <div class="cozmoslabs-toggle-description">
                        <label for="recaptcha-site-v3" class="cozmoslabs-description"><?php esc_html_e( 'Enable the use of the newer reCAPTCHA v3 rather than the older v2.', 'paid-member-subscriptions' ); ?></label>
                    </div>
                </div>

                <div class="recaptchav2-fields" <?php echo ( !empty( $options['recaptcha']['v3'] ) && $options['recaptcha']['v3'] == 'yes' ? 'style="display: none;"' : '' ); ?>>

                    <div class="cozmoslabs-form-field-wrapper">
                        <label class="cozmoslabs-form-field-label" for="recaptcha-site-key"><?php esc_html_e( 'V2 Site Key', 'paid-member-subscriptions' ) ?></label>
                        <input id="recaptcha-site-key" type="text" class="widefat" name="pms_misc_settings[recaptcha][site_key]" value="<?php echo ( !empty( $options['recaptcha']['site_key'] ) ? esc_attr( $options['recaptcha']['site_key'] ) : '' ) ?>" />
                        <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php echo wp_kses_post( sprintf( __( 'The site key from %1$sGoogle%2$s', 'paid-member-subscriptions' ), '<a href="https://www.google.com/recaptcha/admin/create" target="_blank">', '</a>' ) ) ?></p>
                    </div>

                    <div class="cozmoslabs-form-field-wrapper">
                        <label class="cozmoslabs-form-field-label" for="recaptcha-secret-key"><?php esc_html_e( 'V2 Secret Key', 'paid-member-subscriptions' ); ?></label>
                        <input id="recaptcha-secret-key" type="text" class="widefat" name="pms_misc_settings[recaptcha][secret_key]" value="<?php echo ( !empty( $options['recaptcha']['secret_key'] ) ? esc_attr( $options['recaptcha']['secret_key'] ) : '' ) ?>" />
                        <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php echo wp_kses_post( sprintf( __( 'The secret key from %1$sGoogle%2$s', 'paid-member-subscriptions' ), '<a href="https://www.google.com/recaptcha/admin/create" target="_blank">', '</a>' ) ) ?></p>
                    </div>

                </div>

                <div class="recaptchav3-fields" <?php echo ( !empty( $options['recaptcha']['v3'] ) && $options['recaptcha']['v3'] == 'yes' ? '' : 'style="display: none;"' ); ?>>

                    <div class="cozmoslabs-form-field-wrapper">
                        <label class="cozmoslabs-form-field-label" for="recaptcha-v3-site-key"><?php esc_html_e( 'V3 Site Key', 'paid-member-subscriptions' ) ?></label>
                        <input id="recaptcha-v3-site-key" type="text" class="widefat" name="pms_misc_settings[recaptcha][v3_site_key]" value="<?php echo ( !empty( $options['recaptcha']['v3_site_key'] ) ? esc_attr( $options['recaptcha']['v3_site_key'] ) : '' ) ?>" />
                        <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php echo wp_kses_post( sprintf( __( 'The site key from %1$sGoogle%2$s', 'paid-member-subscriptions' ), '<a href="https://www.google.com/recaptcha/admin/create" target="_blank">', '</a>' ) ) ?></p>
                    </div>

                    <div class="cozmoslabs-form-field-wrapper">
                        <label class="cozmoslabs-form-field-label" for="recaptcha-v3-secret-key"><?php esc_html_e( 'V3 Secret Key', 'paid-member-subscriptions' ); ?></label>
                        <input id="recaptcha-v3-secret-key" type="text" class="widefat" name="pms_misc_settings[recaptcha][v3_secret_key]" value="<?php echo ( !empty( $options['recaptcha']['v3_secret_key'] ) ? esc_attr( $options['recaptcha']['v3_secret_key'] ) : '' ) ?>" />
                        <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php echo wp_kses_post( sprintf( __( 'The secret key from %1$sGoogle%2$s', 'paid-member-subscriptions' ), '<a href="https://www.google.com/recaptcha/admin/create" target="_blank">', '</a>' ) ) ?></p>
                    </div>

                    <div class="cozmoslabs-form-field-wrapper">
                        <label class="cozmoslabs-form-field-label" for="recaptcha-v3-score-threshold"><?php esc_html_e( 'Score Threshold', 'paid-member-subscriptions' ); ?></label>
                        <input id="recaptcha-v3-score-threshold" type="text" class="widefat" name="pms_misc_settings[recaptcha][v3_score_threshold]" value="<?php echo ( !empty( $options['recaptcha']['v3_score_threshold'] ) ? esc_attr( $options['recaptcha']['v3_score_threshold'] ) : '0.5' ) ?>" />
                        <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'The required score threshold: 1.0 is very likely a good interaction, 0.0 is very likely a bot. If not specified or out of these bounds, defaults to 0.5.', 'paid-member-subscriptions' ); ?></p>
                    </div>

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

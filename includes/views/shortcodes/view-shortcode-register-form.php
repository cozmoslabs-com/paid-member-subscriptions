<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

    /*
     * HTML output for register form
     *
     * @param $atts     - is available from parent file, in the register_form method of the PMS_Shortcodes class
     */
    $form_name = 'register';
    $extra_classes = apply_filters( 'pms_add_extra_form_classes', ( $atts['plans_position'] == 'top' ? ' pms-register-form-top-plans' : '' ), 'register_form' );

?>

<form id="pms_<?php echo esc_attr( $form_name ); ?>-form" class="pms-form <?php echo esc_attr( $extra_classes ) ?>" method="POST">

    <?php do_action( 'pms_' . $form_name . '_form_top', $atts ); ?>

    <?php wp_nonce_field( 'pms_' . $form_name . '_form_nonce', 'pmstkn' ); ?>

    <ul class="pms-form-fields-wrapper">

        <?php

        // Start catching the subscription plan fields
        ob_start();

        $field_errors = pms_errors()->get_error_messages('subscription_plans');
        echo '<li class="pms-field pms-field-subscriptions ' . (!empty($field_errors) ? 'pms-field-error' : '') . '">';

            do_action( 'pms_' . $form_name . '_form_subscription_plans_field_before', $atts );

            $subscription_plans = pms_get_subscription_plans();

            // Add nonce field when subscription_plans='none' (to allow users to register without becoming members, selecting a subscription plan)
            if( empty( $subscription_plans ) || ( isset( $atts['subscription_plans'][0] ) && ( strtolower($atts['subscription_plans'][0]) == 'none' ) ) )

                wp_nonce_field( 'pms_register_user_no_subscription_nonce','pmstkn2');

            else

                echo pms_output_subscription_plans( $atts['subscription_plans'], $atts['exclude'], false, (isset($atts['selected']) ? trim($atts['selected']) : ''), 'register' );//phpcs:ignore  WordPress.Security.EscapeOutput.OutputNotEscaped

            do_action( 'pms_' . $form_name . '_form_subscription_plans_field_after', $atts );

        echo '</li>';

        // Get the contents and clean
        $subscription_plans_field = ob_get_contents();
        ob_end_clean();

        // Display subscription plans at the bottom
        if( $atts['plans_position'] == 'top' )
            echo $subscription_plans_field; //phpcs:ignore  WordPress.Security.EscapeOutput.OutputNotEscaped

        ?>


        <?php
            // Start catching the register form fields
            ob_start();
        ?>

        <?php do_action( 'pms_register_form_before_fields', $atts ); ?>

        <?php $field_errors = pms_errors()->get_error_messages('user_login'); ?>
        <li class="pms-field pms-user-login-field <?php echo ( !empty( $field_errors ) ? 'pms-field-error' : '' ); ?>">
            <label for="pms_user_login"><?php echo esc_html( apply_filters( 'pms_register_form_label_user_login', __( 'Username *', 'paid-member-subscriptions' ) ) ); ?></label>
            <input id="pms_user_login" name="user_login" type="text" value="<?php echo esc_attr( apply_filters( 'pms_' . $form_name . '_form_value_user_login', isset( $_POST['user_login'] ) ? sanitize_text_field( $_POST['user_login'] ) : '' ) ); ?>" />

            <?php pms_display_field_errors( $field_errors ); ?>
        </li>

        <?php $field_errors = pms_errors()->get_error_messages('user_email'); ?>
        <li class="pms-field pms-user-email-field <?php echo ( !empty( $field_errors ) ? 'pms-field-error' : '' ); ?>">
            <label for="pms_user_email"><?php echo esc_html( apply_filters( 'pms_register_form_label_user_email', __( 'E-mail *', 'paid-member-subscriptions' ) ) ); ?></label>
            <input id="pms_user_email" name="user_email" type="text" value="<?php echo esc_attr( apply_filters( 'pms_' . $form_name . '_form_value_user_email', isset( $_POST['user_email'] ) ? sanitize_text_field( $_POST['user_email'] ) : '' ) ); ?>" <?php echo esc_html( apply_filters( 'pms_register_form_attributes_user_email', '' ) ); ?>/>

            <?php pms_display_field_errors( $field_errors ); ?>
        </li>

        <?php $field_errors = pms_errors()->get_error_messages('first_name'); ?>
        <li class="pms-field pms-first-name-field <?php echo ( !empty( $field_errors ) ? 'pms-field-error' : '' ); ?>">
            <label for="pms_first_name"><?php echo esc_html( apply_filters( 'pms_register_form_label_first_name', __( 'First Name', 'paid-member-subscriptions' ) ) ); ?></label>
            <input id="pms_first_name" name="first_name" type="text" value="<?php echo esc_attr( isset( $_POST['first_name'] ) ? sanitize_text_field( $_POST['first_name'] ) : '' ); ?>" />

            <?php pms_display_field_errors( $field_errors ); ?>
        </li>

        <?php $field_errors = pms_errors()->get_error_messages('last_name'); ?>
        <li class="pms-field pms-last-name-field <?php echo ( !empty( $field_errors ) ? 'pms-field-error' : '' ); ?>">
            <label for="pms_last_name"><?php echo esc_html( apply_filters( 'pms_register_form_label_last_name', __( 'Last Name', 'paid-member-subscriptions' ) ) ); ?></label>
            <input id="pms_last_name" name="last_name" type="text" value="<?php echo esc_attr( isset( $_POST['last_name'] ) ? sanitize_text_field( $_POST['last_name'] ) : '' ); ?>" />

            <?php pms_display_field_errors( $field_errors ); ?>
        </li>

        <?php $field_errors = pms_errors()->get_error_messages('pass1'); ?>
        <li class="pms-field pms-pass1-field <?php echo ( !empty( $field_errors ) ? 'pms-field-error' : '' ); ?>">
            <label for="pms_pass1"><?php echo esc_html( apply_filters( 'pms_register_form_label_pass1', __( 'Password *', 'paid-member-subscriptions' ) ) ); ?></label>
            <input id="pms_pass1" name="pass1" type="password" />

            <?php pms_display_field_errors( $field_errors ); ?>

            <?php

            do_action( 'pms_register_form_pass1_extra_content' );

            ?>
        </li>



        <?php $field_errors = pms_errors()->get_error_messages('pass2'); ?>
        <li class="pms-field pms-pass2-field <?php echo ( !empty( $field_errors ) ? 'pms-field-error' : '' ); ?>">
            <label for="pms_pass2"><?php echo esc_html( apply_filters( 'pms_register_form_label_pass2', __( 'Repeat Password *', 'paid-member-subscriptions' ) ) ); ?></label>
            <input id="pms_pass2" name="pass2" type="password" />

            <?php pms_display_field_errors( $field_errors ); ?>
        </li>

        <?php do_action( 'pms_register_form_after_fields', $atts ); ?>

        <?php
            // Get form fields and clean the buffer
            $register_form_fields = ob_get_contents();
            ob_end_clean();

            if( $form_name == 'register' )
                echo $register_form_fields;//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        ?>

        <?php
            // Display subscription plans at the bottom
            if( $atts['plans_position'] == 'bottom' )
                echo $subscription_plans_field; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        ?>

    </ul>

    <?php do_action( 'pms_' . $form_name . '_form_bottom', $atts ); ?>

    <input class="pms-form-submit" name="pms_<?php echo esc_attr( $form_name ); ?>" type="submit" value="<?php echo esc_attr( apply_filters( 'pms_' . $form_name . '_form_submit_text', __( 'Register', 'paid-member-subscriptions' ) ) ); ?>" />

</form>

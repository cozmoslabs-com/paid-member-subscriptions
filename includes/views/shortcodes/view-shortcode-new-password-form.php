<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * HTML output for recover password - enter new password form
 *
 */
$form_name = 'new_password';
$extra_classes = apply_filters( 'pms_add_extra_form_classes', '' , 'recover_password_form' );
?>

<form id="pms_new_password_form" class="pms-form <?php echo esc_attr( $extra_classes ) ?>" method="post" action="<?php echo esc_url( apply_filters( 'pms_' . $form_name . '_form_action_attribute', '' ) ); ?>">

    <?php wp_nonce_field( 'pms_new_password_form_nonce', 'pmstkn' ); ?>

    <?php
    $pms_newpass_notification = '<p>' . __( 'Please enter your new password.', 'paid-member-subscriptions' ) . '</p>';
    echo wp_kses_post( apply_filters( 'pms_new_password_message', $pms_newpass_notification ) );
    ?>

    <ul class="pms-form-fields-wrapper">

        <?php do_action( 'pms_new_password_form_before_fields' ); ?>

        <?php $field_errors = pms_errors()->get_error_messages('pms_repeat_password'); ?>
        <?php $pms_error_messages = pms_errors()->get_error_messages('pms_new_password'); ?>

        <li class="pms-field <?php echo ( !empty( $pms_error_messages ) ? 'pms-field-error' : '' ); ?>">
            <label for="pms_new_password"><?php echo esc_html( apply_filters( 'pms_recover_password_form_label_new_password', __( 'Password', 'paid-member-subscriptions' ) ) ); ?></label>
            <input id="pms_new_password" name="pms_new_password" class="password" type="password" value="" autocomplete="off" />

            <?php

            pms_display_field_errors( $pms_error_messages );
            do_action( 'pms_recover_password_form_pass1_extra_content' );
            ?>
        </li>

        <li class="pms-field <?php echo ( !empty( $field_errors ) ? 'pms-field-error' : '' ); ?>">
            <label for="pms_repeat_password"><?php echo esc_html( apply_filters( 'pms_recover_password_form_label_repeat_password', __( 'Repeat Password', 'paid-member-subscriptions' ) ) ); ?></label>
            <input id="pms_repeat_password" name="pms_repeat_password" class="password" type="password" value="" autocomplete="off" />

            <?php pms_display_field_errors( $field_errors ); ?>
        </li>

        <?php do_action( 'pms_new_password_form_after_fields' ); ?>

    </ul>

    <?php do_action( 'pms_new_password_form_bottom' ); ?>

    <input type="submit" name="wp-submit" value="<?php esc_attr_e( 'Reset Password', 'paid-member-subscriptions' ); ?>"/>

</form>

<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

    /*
     * HTML output for new subscription form
     *
     * @param $atts     - is available from parent file, in the register_form method of the PMS_Shortcodes class
     */
    $form_name = 'new_subscription';

    $target_plans = $atts['subscription_plans'];

    if( !empty( $array_dif ) )
        $target_plans = $array_dif;

    $extra_classes = apply_filters( 'pms_add_extra_form_classes', '' , 'new_subscription_form' );

?>

<form id="pms_<?php echo esc_attr( $form_name ); ?>-form" class="pms-form <?php echo esc_attr( $extra_classes ) ?>" method="POST">

    <?php do_action( 'pms_' . $form_name . '_form_top', $atts ); ?>

    <?php

        wp_nonce_field( 'pms_' . $form_name . '_form_nonce', 'pmstkn' );
        pms_display_success_messages( pms_success()->get_messages('subscription_plans') );

    ?>

    <ul class="pms-form-fields-wrapper">

        <?php

            do_action( 'pms_' . $form_name . '_form_subscription_plans_field_before_output', $atts );

            $field_errors = pms_errors()->get_error_messages( 'subscription_plans' );

            echo '<li class="pms-field pms-field-subscriptions ' . ( !empty( $field_errors ) ? 'pms-field-error' : '' ) . '">';

                do_action( 'pms_' . $form_name . '_form_subscription_plans_field_before', $atts );

                echo pms_output_subscription_plans( $target_plans, $atts['exclude'], false, (isset($atts['selected']) ? trim($atts['selected']) : '' ), 'new_subscription' ); //phpcs:ignore  WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '</li>';

            do_action( 'pms_' . $form_name . '_form_subscription_plans_field_after_output', $atts );

        ?>

    </ul>

    <?php do_action( 'pms_' . $form_name . '_form_bottom', $atts ); ?>

    <?php if( apply_filters( 'pms_' . $form_name . '_form_submit_button_enabled', true, $form_name ) ) : ?>
        <input name="pms_<?php echo esc_attr( $form_name ); ?>" type="submit" value="<?php echo esc_attr( apply_filters( 'pms_' . $form_name . '_form_submit_text', __( 'Subscribe', 'paid-member-subscriptions' ) ) ); ?>" />
    <?php endif; ?>

</form>
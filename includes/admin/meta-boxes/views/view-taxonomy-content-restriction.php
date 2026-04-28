<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

$term_id            = ( ! empty( $term ) && ! empty( $term->term_id ) ? $term->term_id : 0 );
$taxonomy_slug      = ! empty( $taxonomy ) ? $taxonomy : ( ! empty( $term->taxonomy ) ? $term->taxonomy : '' );
$taxonomy_object    = ! empty( $taxonomy_slug ) ? get_taxonomy( $taxonomy_slug ) : false;
$content_label      = apply_filters( 'pms_content_restrict_settings_description_taxonomy', ( ! empty( $taxonomy_object->labels->singular_name ) ? strtolower( $taxonomy_object->labels->singular_name ) . ' archive' : 'taxonomy archive' ), $taxonomy_slug, $term );
$content_restrict_types = apply_filters( 'pms_single_post_content_restrict_types', array( 'message' => esc_html__( 'Message', 'paid-member-subscriptions' ), 'redirect' => esc_html__( 'Redirect', 'paid-member-subscriptions' ), 'template' => esc_html__( 'Template', 'paid-member-subscriptions' ) ) );
$content_restrict_type  = ( $term_id ? get_term_meta( $term_id, 'pms-content-restrict-type', true ) : '' );
$user_status            = ( $term_id ? get_term_meta( $term_id, 'pms-content-restrict-user-status', true ) : '' );
$settings               = get_option( 'pms_misc_settings' );
$include_inactive_plans = isset( $settings['cr-metabox-include-inactive-plans'] ) && $settings['cr-metabox-include-inactive-plans'] == 1 ? false : true;
$subscription_plans     = pms_get_subscription_plans( $include_inactive_plans );
$selected_subscription_plans = ( $term_id ? get_term_meta( $term_id, 'pms-content-restrict-subscription-plan' ) : array() );
$all_plans_selected          = ( $term_id ? get_term_meta( $term_id, 'pms-content-restrict-all-subscription-plans', true ) : '' );
$custom_redirect_url_enabled = ( $term_id ? get_term_meta( $term_id, 'pms-content-restrict-custom-redirect-url-enabled', true ) : '' );
$custom_redirect_url         = ( $term_id ? get_term_meta( $term_id, 'pms-content-restrict-custom-redirect-url', true ) : '' );
$custom_non_member_redirect_url = ( $term_id ? get_term_meta( $term_id, 'pms-content-restrict-custom-non-member-redirect-url', true ) : '' );
$custom_messages_enabled        = ( $term_id ? get_term_meta( $term_id, 'pms-content-restrict-messages-enabled', true ) : '' );

if ( ! empty( $subscription_plans ) ) {
    usort( $subscription_plans, 'pms_compare_subscription_plan_objects' );
}

if ( ! function_exists( 'pms_output_taxonomy_restriction_field' ) ) {
    function pms_output_taxonomy_restriction_field( $label, $content, $is_edit_screen = false ) {
        if ( $is_edit_screen ) {
            echo '<tr class="form-field term-pms-content-restriction-wrap"><th scope="row"><label>' . esc_html( $label ) . '</label></th><td>';
            echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '</td></tr>';
        } else {
            echo '<div class="form-field term-pms-content-restriction-wrap">';
            echo '<label>' . esc_html( $label ) . '</label>';
            echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '</div>';
        }
    }
}

$is_edit_screen = ! empty( $term_id );
?>

<?php
ob_start();
?>
<div class="pms-meta-box-fields-wrapper cozmoslabs-form-subsection-wrapper">
    <h4 class="cozmoslabs-subsection-title"><?php echo esc_html__( 'Display Options', 'paid-member-subscriptions' ); ?></h4>

    <div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">
        <div class="cozmoslabs-radio-inputs-row">
            <label class="pms-meta-box-checkbox-label" for="pms-content-restrict-type-default">
                <input type="radio" id="pms-content-restrict-type-default" value="default" <?php checked( empty( $content_restrict_type ) || $content_restrict_type == 'default' ); ?> name="pms-content-restrict-type">
                <?php esc_html_e( 'Settings Default', 'paid-member-subscriptions' ); ?>
            </label>

            <?php foreach ( $content_restrict_types as $type_slug => $type_label ) : ?>
                <label class="pms-meta-box-checkbox-label" for="pms-content-restrict-type-<?php echo esc_attr( $type_slug ); ?>">
                    <input type="radio" id="pms-content-restrict-type-<?php echo esc_attr( $type_slug ); ?>" value="<?php echo esc_attr( $type_slug ); ?>" <?php checked( $content_restrict_type, $type_slug ); ?> name="pms-content-restrict-type">
                    <?php echo esc_html( $type_label ); ?>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper cozmoslabs-checkbox-list-wrapper">
        <div class="cozmoslabs-checkbox-list cozmoslabs-checkbox-multi-col-list">
            <div class="cozmoslabs-chckbox-container">
                <input type="checkbox" value="loggedin" <?php checked( $user_status, 'loggedin' ); ?> name="pms-content-restrict-user-status" id="pms-content-restrict-user-status">
                <label class="pms-meta-box-checkbox-label" for="pms-content-restrict-user-status"><?php echo esc_html__( 'Logged In Users', 'paid-member-subscriptions' ); ?></label>
            </div>

            <?php if ( ! empty( $subscription_plans ) ) : ?>
                <div class="cozmoslabs-chckbox-container">
                    <input type="checkbox" value="all" <?php checked( $all_plans_selected, 'all' ); ?> name="pms-content-restrict-all-subscription-plans" id="pms-content-restrict-all-subscription-plans">
                    <label class="pms-meta-box-checkbox-label" for="pms-content-restrict-all-subscription-plans"><?php echo esc_html__( 'All Subscription Plans', 'paid-member-subscriptions' ); ?></label>
                </div>

                <?php foreach ( $subscription_plans as $subscription_plan ) : ?>
                    <div class="cozmoslabs-chckbox-container">
                        <input type="checkbox" value="<?php echo esc_attr( $subscription_plan->id ); ?>" <?php checked( ( is_array( $selected_subscription_plans ) && in_array( $subscription_plan->id, $selected_subscription_plans ) ) || $all_plans_selected === 'all' ); ?> name="pms-content-restrict-subscription-plan[]" id="pms-content-restrict-subscription-plan-<?php echo esc_attr( $subscription_plan->id ); ?>">
                        <label class="pms-meta-box-checkbox-label" for="pms-content-restrict-subscription-plan-<?php echo esc_attr( $subscription_plan->id ); ?>"><?php echo esc_html( $subscription_plan->name ); ?></label>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <p class="cozmoslabs-description">
            <?php printf( esc_html__( 'Checking only "Logged In Users" will show this %s to all logged in users, regardless of subscription plan.', 'paid-member-subscriptions' ), esc_html( $content_label ) ); ?>
        </p>

        <?php if ( ! empty( $subscription_plans ) ) : ?>
            <p class="cozmoslabs-description">
                <?php printf( esc_html__( 'Checking "All Subscription Plans" will show this %s to users that are subscribed any of the plans.', 'paid-member-subscriptions' ), esc_html( $content_label ) ); ?>
            </p>

            <p class="cozmoslabs-description">
                <?php printf( esc_html__( 'Checking any subscription plan will show this %s only to users that are subscribed to those particular plans.', 'paid-member-subscriptions' ), esc_html( $content_label ) ); ?>
            </p>
        <?php endif; ?>
    </div>
</div>

<div id="pms-meta-box-fields-wrapper-restriction-redirect-url" class="pms-meta-box-fields-wrapper cozmoslabs-form-subsection-wrapper <?php echo ( $content_restrict_type == 'redirect' ? 'pms-enabled' : '' ); ?>">
    <h4 class="cozmoslabs-subsection-title"><?php echo esc_html__( 'Restriction Redirect URL', 'paid-member-subscriptions' ); ?></h4>

    <div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
        <div class="cozmoslabs-toggle-container">
            <input type="checkbox" value="yes" <?php checked( ! empty( $custom_redirect_url_enabled ) ); ?> name="pms-content-restrict-custom-redirect-url-enabled" id="pms-content-restrict-custom-redirect-url-enabled">
            <label class="cozmoslabs-toggle-track" for="pms-content-restrict-custom-redirect-url-enabled"></label>
        </div>
        <div class="cozmoslabs-toggle-description">
            <label for="pms-content-restrict-custom-redirect-url-enabled" class="cozmoslabs-description"><?php printf( esc_html__( 'Check if you wish to add a custom redirect URL for this %s.', 'paid-member-subscriptions' ), esc_html( $content_label ) ); ?></label>
        </div>
    </div>

    <div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper pms-meta-box-field-wrapper-custom-redirect-url <?php echo ( ! empty( $custom_redirect_url_enabled ) ? 'pms-enabled' : '' ); ?>">
        <input type="text" value="<?php echo esc_attr( $custom_redirect_url ); ?>" name="pms-content-restrict-custom-redirect-url" id="pms-content-restrict-custom-redirect-url" class="widefat">
        <p class="cozmoslabs-description"><?php printf( esc_html__( 'Add a URL where you wish to redirect users that do not have access to this %s and try to access it directly.', 'paid-member-subscriptions' ), esc_html( $content_label ) ); ?></p>
    </div>

    <div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper pms-meta-box-field-wrapper-custom-redirect-url <?php echo ( ! empty( $custom_redirect_url_enabled ) ? 'pms-enabled' : '' ); ?>">
        <input type="text" value="<?php echo esc_attr( $custom_non_member_redirect_url ); ?>" name="pms-content-restrict-custom-non-member-redirect-url" id="pms-content-restrict-custom-non-member-redirect-url" class="widefat">
        <p class="cozmoslabs-description"><?php printf( esc_html__( 'Add a URL where you wish to redirect logged-in non-members that do not have access to this %s and try to access it directly.', 'paid-member-subscriptions' ), esc_html( $content_label ) ); ?></p>
        <p class="cozmoslabs-description"><?php esc_html_e( 'Leave this field empty if you want all users to be redirected to the same URL.', 'paid-member-subscriptions' ); ?></p>
    </div>
</div>

<div class="pms-meta-box-fields-wrapper cozmoslabs-form-subsection-wrapper">
    <h4 class="cozmoslabs-subsection-title"><?php echo esc_html__( 'Restriction Messages', 'paid-member-subscriptions' ); ?></h4>

    <div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
        <div class="cozmoslabs-toggle-container">
            <input type="checkbox" value="yes" <?php checked( ! empty( $custom_messages_enabled ) ); ?> name="pms-content-restrict-messages-enabled" id="pms-content-restrict-messages-enabled">
            <label class="cozmoslabs-toggle-track" for="pms-content-restrict-messages-enabled"></label>
        </div>
        <div class="cozmoslabs-toggle-description">
            <label for="pms-content-restrict-messages-enabled" class="cozmoslabs-description"><?php printf( esc_html__( 'Enable if you wish to add custom restriction messages for this %s.', 'paid-member-subscriptions' ), esc_html( $content_label ) ); ?></label>
        </div>
    </div>

    <div class="pms-meta-box-field-wrapper-custom-messages <?php echo ( ! empty( $custom_messages_enabled ) ? 'pms-enabled' : '' ); ?>">
        <div class="cozmoslabs-form-field-wrapper cozmoslabs-wysiwyg-wrapper cozmoslabs-wysiwyg-indented">
            <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Messages for logged-out users', 'paid-member-subscriptions' ); ?></label>
            <?php wp_editor( wp_kses_post( $term_id ? get_term_meta( $term_id, 'pms-content-restrict-message-logged_out', true ) : '' ), 'pms-taxonomy-messages-logged-out-' . $taxonomy_slug . '-' . $term_id, array( 'textarea_name' => 'pms-content-restrict-message-logged_out', 'editor_height' => 180 ) ); ?>
        </div>

        <div class="cozmoslabs-form-field-wrapper cozmoslabs-wysiwyg-wrapper cozmoslabs-wysiwyg-indented">
            <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Messages for logged-in non-member users', 'paid-member-subscriptions' ); ?></label>
            <?php wp_editor( wp_kses_post( $term_id ? get_term_meta( $term_id, 'pms-content-restrict-message-non_members', true ) : '' ), 'pms-taxonomy-messages-non-members-' . $taxonomy_slug . '-' . $term_id, array( 'textarea_name' => 'pms-content-restrict-message-non_members', 'editor_height' => 180 ) ); ?>
        </div>
    </div>
</div>

<?php wp_nonce_field( 'pms_meta_box_single_content_restriction_nonce', 'pmstkn', false ); ?>
<?php
$content = ob_get_clean();

pms_output_taxonomy_restriction_field( esc_html__( 'Content Restriction', 'paid-member-subscriptions' ), $content, $is_edit_screen );

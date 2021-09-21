<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * HTML output for content restriction meta-box
 */
?>

<?php do_action( 'pms_view_meta_box_content_restrict_top', $post->ID ); ?>

<!-- Display Options -->
<div class="pms-meta-box-fields-wrapper">
    <h4><?php echo esc_html__( 'Display Options', 'paid-member-subscriptions' ); ?></h4>

    <!-- Type of protection -->
    <div class="pms-meta-box-field-wrapper">

        <?php
            $content_restrict_types = apply_filters( 'pms_single_post_content_restrict_types', array( 'message' => esc_html__( 'Message', 'paid-member-subscriptions' ), 'redirect' => esc_html__( 'Redirect', 'paid-member-subscriptions' ), 'template' => esc_html__( 'Template', 'paid-member-subscriptions' ) ) );
        ?>

        <?php $content_restrict_type = get_post_meta( $post->ID, 'pms-content-restrict-type', true ); ?>

        <label class="pms-meta-box-field-label"><?php esc_html_e( 'Type of Restriction', 'paid-member-subscriptions' ); ?></label>

        <label class="pms-meta-box-checkbox-label" for="pms-content-restrict-type-default">
             <input type="radio" id="pms-content-restrict-type-default" value="default" <?php if( empty( $content_restrict_type ) || $content_restrict_type == 'default' ) echo 'checked="checked"'; ?> name="pms-content-restrict-type">
             <?php esc_html_e( 'Settings Default', 'paid-member-subscriptions' ); ?>
        </label>

        <?php foreach( $content_restrict_types as $type_slug => $type_label ): ?>
            <label class="pms-meta-box-checkbox-label" for="pms-content-restrict-type-<?php echo esc_attr( $type_slug ); ?>">
                 <input type="radio" id="pms-content-restrict-type-<?php echo esc_attr( $type_slug ); ?>" value="<?php echo esc_attr( $type_slug ); ?>" <?php if( $content_restrict_type == $type_slug ) echo 'checked="checked"'; ?> name="pms-content-restrict-type">
                 <?php echo esc_html( $type_label ); ?>
            </label>
        <?php endforeach; ?>

    </div>

    <!-- Display For options -->
    <div class="pms-meta-box-field-wrapper">
        <label class="pms-meta-box-field-label"><?php esc_html_e( 'Display For', 'paid-member-subscriptions' ); ?></label>

        <?php
        $user_status          = get_post_meta( $post->ID, 'pms-content-restrict-user-status', true );
        $subscription_plans   = pms_get_subscription_plans();
        $selected_subscription_plans = get_post_meta( $post->ID, 'pms-content-restrict-subscription-plan' );
        $all_plans_selected   = get_post_meta( $post->ID, 'pms-content-restrict-all-subscription-plans');
        ?>

        <label class="pms-meta-box-checkbox-label" for="pms-content-restrict-user-status">
            <input type="checkbox" value="loggedin" <?php if( ! empty( $user_status ) && $user_status == 'loggedin' ) echo 'checked="checked"'; ?> name="pms-content-restrict-user-status" id="pms-content-restrict-user-status">
            <?php echo esc_html__( 'Logged In Users', 'paid-member-subscriptions' ); ?>
        </label>

        <?php if( !empty( $subscription_plans ) ): ?>

            <label class="pms-meta-box-checkbox-label" for="pms-content-restrict-all-subscription-plans">
                <input type="checkbox" value="all" <?php if( $all_plans_selected ) echo 'checked="checked"'; ?> name="pms-content-restrict-all-subscription-plans" id="pms-content-restrict-all-subscription-plans">
                <?php echo esc_html__( 'All Subscription Plans', 'paid-member-subscriptions' ); ?>
            </label>

            <?php foreach( $subscription_plans as $subscription_plan ): ?>

                <label class="pms-meta-box-checkbox-label" for="pms-content-restrict-subscription-plan-<?php echo esc_attr( $subscription_plan->id ) ?>">
                    <input type="checkbox" value="<?php echo esc_attr( $subscription_plan->id ); ?>" <?php if( in_array( $subscription_plan->id, $selected_subscription_plans ) || $all_plans_selected ) echo 'checked="checked"'; ?> name="pms-content-restrict-subscription-plan[]" id="pms-content-restrict-subscription-plan-<?php echo esc_attr( $subscription_plan->id ) ?>">
                    <?php echo esc_html( $subscription_plan->name ); ?>
                </label>

            <?php endforeach; ?>
            <p class="description" style="margin-top: 10px;">
                <?php printf( esc_html__( 'Checking only "Logged In Users" will show this %s to all logged in users, regardless of subscription plan.', 'paid-member-subscriptions' ), esc_html( $post->post_type ) ); ?>
            </p>
            <p class="description">
                <?php printf( esc_html__( 'Checking "All Subscription Plans" will show this %s to users that are subscribed any of the plans.', 'paid-member-subscriptions' ), esc_html( $post->post_type ) ); ?>
            </p>
            <p class="description">
                <?php printf( esc_html__( 'Checking any subscription plan will show this %s only to users that are subscribed to those particular plans.', 'paid-member-subscriptions' ), esc_html( $post->post_type ) ); ?>
            </p>
        <?php endif; ?>

    </div>

    <!-- Other display options -->
    <?php do_action( 'pms_view_meta_box_content_restrict_display_options', $post->ID ); ?>

</div>


<!-- Restriction Redirect URL -->
<div id="pms-meta-box-fields-wrapper-restriction-redirect-url" class="pms-meta-box-fields-wrapper <?php echo ( $content_restrict_type == 'redirect' ? 'pms-enabled' : '' ); ?>">
    <h4><?php echo esc_html__( 'Restriction Redirect URL', 'paid-member-subscriptions' ); ?></h4>


    <!-- Custom Redirect URL Enabler -->
    <div class="pms-meta-box-field-wrapper">

        <?php $custom_redirect_url_enabled = get_post_meta( $post->ID, 'pms-content-restrict-custom-redirect-url-enabled', true ); ?>

        <label class="pms-meta-box-field-label"><?php esc_html_e( 'Enable Custom Redirect URL', 'paid-member-subscriptions' ); ?></label>

        <label class="pms-meta-box-checkbox-label" for="pms-content-restrict-custom-redirect-url-enabled">
            <input type="checkbox" value="yes" <?php echo ( ! empty( $custom_redirect_url_enabled ) ? 'checked="checked"' : '' ); ?> name="pms-content-restrict-custom-redirect-url-enabled" id="pms-content-restrict-custom-redirect-url-enabled">
            <span class="description"><?php printf( esc_html__( 'Check if you wish to add a custom redirect URL for this %s.', 'paid-member-subscriptions' ), esc_html( $post->post_type ) ); ?></span>
        </label>
    </div>

    <!-- Custom Redirect URL field -->
    <div class="pms-meta-box-field-wrapper pms-meta-box-field-wrapper-custom-redirect-url <?php echo ( ! empty( $custom_redirect_url_enabled ) ? 'pms-enabled' : '' ); ?>">

        <?php $custom_redirect_url = get_post_meta( $post->ID, 'pms-content-restrict-custom-redirect-url', true ); ?>

        <label class="pms-meta-box-field-label" for="pms-content-restrict-custom-redirect-url"><?php esc_html_e( 'Custom Redirect URL', 'paid-member-subscriptions' ); ?></label>

        <label class="pms-meta-box-checkbox-label pms-meta-box-checkbox-label-custom-redirect">
            <input type="text" value="<?php echo ( ! empty( $custom_redirect_url ) ? esc_attr( $custom_redirect_url ) : '' ); ?>" name="pms-content-restrict-custom-redirect-url" id="pms-content-restrict-custom-redirect-url" class="widefat">
            <p class="description"><?php printf( esc_html__( 'Add a URL where you wish to redirect users that do not have access to this %s and try to access it directly.', 'paid-member-subscriptions' ), esc_html( $post->post_type ) ); ?></p>
        </label>

    </div>

    <div class="pms-meta-box-field-wrapper pms-meta-box-field-wrapper-custom-redirect-url <?php echo ( ! empty( $custom_redirect_url_enabled ) ? 'pms-enabled' : '' ); ?>">

        <?php $custom_non_member_redirect_url = get_post_meta( $post->ID, 'pms-content-restrict-custom-non-member-redirect-url', true ); ?>

        <label class="pms-meta-box-field-label" for="pms-content-restrict-custom-non-member-redirect-url"><?php esc_html_e( 'Custom Non-Member Redirect URL', 'paid-member-subscriptions' ); ?></label>

        <label class="pms-meta-box-checkbox-label pms-meta-box-checkbox-label-custom-redirect">
            <input type="text" value="<?php echo ( ! empty( $custom_non_member_redirect_url ) ? esc_attr( $custom_non_member_redirect_url ) : '' ); ?>" name="pms-content-restrict-custom-non-member-redirect-url" id="pms-content-restrict-custom-non-member-redirect-url" class="widefat">
            <p class="description"><?php printf( esc_html__( 'Add a URL where you wish to redirect logged-in non-members that do not have access to this %s and try to access it directly.', 'paid-member-subscriptions' ), esc_html( $post->post_type ) ); ?></p>
            <p class="description"><?php printf( esc_html__( 'Leave this field empty if you want all users to be redirected to the same URL.', 'paid-member-subscriptions' ) ); ?></p>
        </label>

    </div>
</div>

<!-- Restriction Messages -->
<div class="pms-meta-box-fields-wrapper">
    <h4><?php echo esc_html__( 'Restriction Messages', 'paid-member-subscriptions' ); ?></h4>

    <div class="pms-meta-box-field-wrapper">
        <?php
        $custom_messages_enabled = get_post_meta( $post->ID, 'pms-content-restrict-messages-enabled', true );
        ?>
        <label class="pms-meta-box-field-label"><?php esc_html_e( 'Enable Custom Messages', 'paid-member-subscriptions' ); ?></label>

        <label class="pms-meta-box-checkbox-label" for="pms-content-restrict-messages-enabled">
            <input type="checkbox" value="yes" <?php echo ( ! empty( $custom_messages_enabled ) ? 'checked="checked"' : '' ); ?> name="pms-content-restrict-messages-enabled" id="pms-content-restrict-messages-enabled">
            <span class="description"><?php printf( esc_html__( 'Check if you wish to add custom messages for this %s.', 'paid-member-subscriptions' ), esc_html( $post->post_type ) ); ?></span>
        </label>
    </div>

    <div class="pms-meta-box-field-wrapper-custom-messages <?php echo ( ! empty( $custom_messages_enabled ) ? 'pms-enabled' : '' ); ?>">

        <!-- Other restriction messages -->
        <?php do_action( 'pms_view_meta_box_content_restrict_restriction_messages_top', $post->ID ); ?>

        <p><strong><?php esc_html_e( 'Messages for logged-out users', 'paid-member-subscriptions' ); ?></strong></p>
        <?php wp_editor( wp_kses_post( get_post_meta( $post->ID, 'pms-content-restrict-message-logged_out', true ) ), 'messages_logged_out', array( 'textarea_name' => 'pms-content-restrict-message-logged_out', 'editor_height' => 200 ) ); ?>

        <p><strong><?php esc_html_e( 'Messages for logged-in non-member users', 'paid-member-subscriptions' ); ?></strong></p>
        <?php wp_editor( wp_kses_post( get_post_meta( $post->ID, 'pms-content-restrict-message-non_members', true ) ), 'messages_non_members', array( 'textarea_name' => 'pms-content-restrict-message-non_members', 'editor_height' => 200 ) ); ?>

        <!-- Other restriction messages -->
        <?php do_action( 'pms_view_meta_box_content_restrict_restriction_messages_bottom', $post->ID ); ?>

    </div>
</div>

<?php do_action( 'pms_view_meta_box_content_restrict_bottom', $post->ID ); ?>

<?php wp_nonce_field( 'pms_meta_box_single_content_restriction_nonce', 'pmstkn', false ); ?>

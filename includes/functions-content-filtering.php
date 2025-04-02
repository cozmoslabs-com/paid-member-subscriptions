<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * Hijack the content when restrictions are set on a single post
 *
 */
function pms_filter_content( $content, $post = null ) {

    global $user_ID, $pms_show_content;

    if( is_null( $post ) )
        global $post;

    if( empty( $post ) )
        return $content;

    /*
     * Defining this variable:
     *
     * $pms_show_content can have 3 states: null, true and false
     *
     * - if the state is "null" the $content is showed, but it did not go through any actual filtering
     * - if the state is "true" the $content is showed, but it did go through filters that explicitly said the $content should be shown
     * - if the state is "false" the $content is not showed, it is replaced with a restriction message, thus it explicitly says that it was filtered and access is denied to it
     *
     */
    $pms_show_content = null;

    /**
     * Show content for the `manage_options` and `pms_bypass_content_restriction` capabilities
     */
    if( current_user_can( 'manage_options' ) || current_user_can( apply_filters( 'pms_content_restriction_capability', 'pms_bypass_content_restriction' ) ) )
        return $content;


    // Get subscription plans that have access to this post
    $user_status             = get_post_meta( $post->ID, 'pms-content-restrict-user-status', true );
    $post_subscription_plans = get_post_meta( $post->ID, 'pms-content-restrict-subscription-plan' );
    $all_subscription_plans  = get_post_meta( $post->ID, 'pms-content-restrict-all-subscription-plans', true );

    if ( ( !empty( $post_subscription_plans ) || !empty( $all_subscription_plans ) ) && is_user_logged_in() ) {

        if( $all_subscription_plans == 'all' && pms_is_member() ){
            $pms_show_content = true;

            return $content;
        } elseif ( pms_is_member( $user_ID, $post_subscription_plans ) ) {
            $pms_show_content = true;

            return $content;
        } else {
            $pms_show_content = false;

            $message = pms_process_restriction_content_message( 'non_members', $user_ID, $post->ID );

            return do_shortcode( apply_filters( 'pms_restriction_message_non_members', $message, $content, $post, $user_ID ) );
        }

    } else if( ( !empty( $user_status ) && $user_status == 'loggedin' ) || !empty( $post_subscription_plans ) ) {

        if( !is_user_logged_in() ){

            // If user is not logged in and this is required prompt the correct message
            $pms_show_content = false;

            $message = pms_process_restriction_content_message( 'logged_out', $user_ID, $post->ID );

            return do_shortcode( apply_filters( 'pms_restriction_message_logged_out', $message, $content, $post, $user_ID ) );

        } else {
            $pms_show_content = true;

            return $content;
        }

    }

    return $content;

}
add_filter( 'the_content', 'pms_filter_content', 11 );
add_filter( 'pms_post_restricted_check', 'pms_filter_content', 10, 2 );


/**
 * Checks to see if the attachment image is restricted and returns
 * false instead of the image if it is restricted
 *
 * @param array|false $image
 * @param int         $attachment_id
 *
 * @return array|false
 *
 */
function pms_filter_attachment_image_src( $image, $attachment_id ) {

    if( is_admin() )
        return $image;

    if( pms_is_post_restricted( $attachment_id ) )
        return false;

    return $image;

}
add_filter( 'wp_get_attachment_image_src', 'pms_filter_attachment_image_src', 10, 2 );


/**
 * Checks to see if the attachment is restricted and returns
 * false instead of the metadata if it is restricted
 *
 * @param array|false $data
 * @param int         $attachment_id
 *
 * @return array|false
 *
 */
function pms_filter_attachment_metadata( $data, $attachment_id ) {

    if( is_admin() )
        return $data;

    if( pms_is_post_restricted( $attachment_id ) )
        return false;

    return $data;

}
add_filter( 'wp_get_attachment_metadata', 'pms_filter_attachment_metadata', 10, 2 );


/**
 * Checks to see if the attachment thumb is restricted and returns
 * false instead of the thumb url if it is restricted
 *
 * @param string $url
 * @param int    $attachment_id
 *
 * @return string|false
 *
 */
function pms_filter_attachment_thumb_url( $url, $attachment_id ) {

    if( is_admin() )
        return $url;

    if( pms_is_post_restricted( $attachment_id ) )
        return false;

    return $url;

}
add_filter( 'wp_get_attachment_thumb_url', 'pms_filter_attachment_thumb_url', 10, 2 );


/**
 * Checks to see if the attachment is restricted and returns
 * an empty string instead of the attachment url if it is restricted
 *
 * @param string $url
 * @param int    $attachment_id
 *
 * @return string
 *
 */
function pms_filter_attachment_url( $url, $attachment_id ) {

    if( is_admin() )
        return $url;

    if( pms_is_post_restricted( $attachment_id ) )
        return '';

    return $url;

}
add_filter( 'wp_get_attachment_url', 'pms_filter_attachment_url', 10, 2 );
add_filter( 'attachment_link', 'pms_filter_attachment_url', 10, 2 );


/**
 * Formats the error messages to display accordingly to the WYSIWYG editor
 *
 * @param string $message
 *
 * @return string
 *
 */
function pms_restriction_message_wpautop( $message = '' ) {

    if( !empty( $message ) )
        $message = wpautop( $message );

    return apply_filters('pms_restriction_message_wpautop' ,$message);

}
add_filter( 'pms_restriction_message_non_members', 'pms_restriction_message_wpautop', 30, 1 );
add_filter( 'pms_restriction_message_logged_out', 'pms_restriction_message_wpautop', 30, 1 );


/**
 * Adds a preview of the restricted post before the default restriction messages
 *
 * @param string $message
 * @param string $content
 * @param WP_Post $post
 * @param int $user_ID
 *
 * @return string
 *
 */
function pms_add_restricted_post_preview( $message, $content, $post, $user_ID ) {

    $preview        = '';
    $settings       = get_option( 'pms_content_restriction_settings' );
    $preview_option = ( !empty( $settings['restricted_post_preview']['option'] ) ? $settings['restricted_post_preview']['option'] : '' );

    if( empty( $preview_option ) || $preview_option == 'none' )
        return $message;

    $post_content = $content;

    /**
     * Trim the content
     *
     */
    if( $preview_option == 'trim-content' ) {

        $length = ( !empty( $settings['restricted_post_preview']['trim_content_length'] ) ? (int)$settings['restricted_post_preview']['trim_content_length'] : 0 );

        if( $length !== 0 ) {

            // Do shortcodes on the content
            $post_content = do_shortcode( $post_content );

            // Trim the preview
            $preview = wp_trim_words( $post_content, $length, apply_filters( 'pms_restricted_post_preview_more', __( '&hellip;', 'paid-member-subscriptions' ) ) );

        }

    }

    /**
     * More tag
     *
     */
    if( $preview_option == 'more-tag' ) {
        /* if we don't have a more tag restrict the whole post */
        if ( preg_match('/<!--more(.*?)?-->/', $post->post_content) ) {
            $content_parts = get_extended($post->post_content);
            $preview = $content_parts['main'];
        }
    }

    // Return the preview
    return wpautop( $preview ) . $message;

}
add_filter( 'pms_restriction_message_non_members', 'pms_add_restricted_post_preview', 30, 4 );
add_filter( 'pms_restriction_message_logged_out', 'pms_add_restricted_post_preview', 30, 4 );


/**
 * Filters the content to display an error message if by some chance a payment error
 * has occured and the payment didn't go through
 *
 * @param string $content
 *
 * @return string
 *
 */
function pms_payment_error_message( $content = '' ) {

    if( empty( $_GET['pms_payment_error'] ) || $_GET['pms_payment_error'] != '1' )
        return $content;

    if( ! isset( $_GET['pms_is_register'] ) )
        return $content;

    $payment_id = ( !empty( $_GET['pms_payment_id'] ) ? absint( $_GET['pms_payment_id'] ) : 0 );

    $error = __( 'Something went wrong while trying to process the payment.', 'paid-member-subscriptions' ) . ' ' . pms_payment_error_message_retry( sanitize_text_field( $_GET['pms_is_register'] ), $payment_id );

    return apply_filters( 'pms_payment_error_message', '<div class="pms-payment-error"><p>' . $error . '</p></div>', sanitize_text_field( $_GET['pms_is_register'] ), $payment_id );
}
add_filter( 'pms_account_shortcode_content', 'pms_payment_error_message', 999 );
add_filter( 'pms_member_account_not_logged_in', 'pms_payment_error_message', 999 );
add_filter( 'pms_register_shortcode_content', 'pms_payment_error_message', 999 );
add_filter( 'wppb_register_form_content', 'pms_payment_error_message', 999 );
add_filter( 'wppb_register_pre_form_message', 'pms_payment_error_message', 999 );

/**
 * Generate the retry payment part of the payment error message
 *
 * @since 1.8.5
 * @param  int     $is_register  If we're on the register form or another one
 * @param  int     $payment_id   ID of the payment associated with the error
 * @return string                Formatted message according to location and settings
 */
function pms_payment_error_message_retry( $is_register, $payment_id = 0 ) {

    if ( $is_register == '1' ) {

        if ( !is_user_logged_in() ) {

            $message = __( 'Your account has been created, so please %slog in%s and retry the payment.', 'paid-member-subscriptions' );

            if ( $account_page = esc_url( pms_get_page( 'account', true ) ) )
                $message = sprintf( $message, '<a href="'. $account_page .'">', '</a>' );
            else
                $message = sprintf( $message, '', '' );
        //user was logged in automatically
        } else {

            $account_page = pms_get_page( 'account', true );

            if( !empty( $account_page ) ){
                if ( $payment_id != 0 ) {
                    $payment   = pms_get_payment( $payment_id );
                    $retry_url = pms_get_retry_url( $payment->subscription_id );
                }

                if ( !empty( $retry_url ) ) {
                    $message = sprintf( __( 'You were logged in, please %sclick here%s to try again.', 'paid-member-subscriptions' ), '<a href="'. $retry_url .'">', '</a>' );
                } else
                    $message = sprintf( __( 'You were logged in, please %sclick here%s to try again.', 'paid-member-subscriptions' ), '<a href="'. $account_page .'">', '</a>' );

            } else
                $message = __( 'You were logged in, please try again.', 'paid-member-subscriptions' );

        }
    //user is logged in
    } else {
        $payment   = pms_get_payment( $payment_id );
        $retry_url = pms_get_retry_url( $payment->subscription_id );

        if ( $payment_id != 0 && pms_get_page( 'account' ) != false && !empty( $retry_url ) )
            $message = sprintf( __( 'Please %sclick here%s to try again.', 'paid-member-subscriptions' ), '<a href="'. $retry_url .'">', '</a>' );
        else if( pms_get_page( 'account' ) != false ){
            $message = sprintf( __( 'Please %sclick here%s to try again.', 'paid-member-subscriptions' ), '<a href="'. pms_get_page( 'account', true ) .'">', '</a>' );
        } else
            $message = __( 'Please try again.', 'paid-member-subscriptions' );

    }

    return $message;
}


/*
 * Hijack the content when a member wants to upgrade to a higher subscription plan
 *
 */
function pms_member_upgrade_subscription( $content ) {

    // Do nothing if we cannot validate the nonce
    if( !isset( $_REQUEST['pmstkn'] ) || !( wp_verify_nonce( sanitize_text_field( $_REQUEST['pmstkn'] ), 'pms_member_nonce' ) || wp_verify_nonce( sanitize_text_field( $_REQUEST['pmstkn'] ), 'pms_upgrade_subscription' ) ) )
        return $content;

    $user_id = pms_get_current_user_id();
    $member  = pms_get_member( $user_id );

    // Do nothing if the user is not a member
    if( !$member->is_member() )
        return $content;

    if( !isset( $_REQUEST['pms-action'] ) || ($_REQUEST['pms-action'] !== 'upgrade_subscription') || !isset( $_REQUEST['subscription_plan'] ) )
        return $content;

    if( !in_array( trim( $_REQUEST['subscription_plan'] ), $member->get_subscriptions_ids() ) )
        return $content;

    // If we don't have any upgrades available, return the content
    $subscription_plan_upgrades = pms_get_subscription_plan_upgrades( trim( absint( $_REQUEST['subscription_plan'] ) ) );

    if( empty( $subscription_plan_upgrades ) )
        return $content;

    // If URL parameter is set, only show that plan
    if( isset( $_GET['upgrade_subscription_plan'] ) ){
        $plan = pms_get_subscription_plan( absint( $_GET['upgrade_subscription_plan'] ) );

        if( !empty( $plan->id ) )
            $subscription_plan_upgrades = array( $plan );
    }

    $subscription_plan = pms_get_subscription_plan( trim( absint( $_REQUEST['subscription_plan'] ) ) );

    $extra_classes = apply_filters( 'pms_add_extra_form_classes', '' , 'upgrade_subscription_form' );

    // Output form
    $output = '<form id="pms-upgrade-subscription-form" action="" method="POST" class="pms-form '. $extra_classes .'">';

        // Do actions at the top of the form
        ob_start();

        do_action('pms_upgrade_subscription_form_top');

        $output .= ob_get_contents();
        ob_end_clean();

        // Output tagline
        if( count( $subscription_plan_upgrades ) == 1 )
            $output .= apply_filters( 'pms_upgrade_subscription_before_form', '<div class="pms-upgrade__message">' . sprintf( __( 'Upgrade %1$s to %2$s', 'paid-member-subscriptions' ), '<strong>' . $subscription_plan->name . '</strong>', '<strong>' . $subscription_plan_upgrades[0]->name . '</strong>' ) . '</div>', $subscription_plan, $member );
        else
            $output .= apply_filters( 'pms_upgrade_subscription_before_form', '<div class="pms-upgrade__message">' . sprintf( __( 'Upgrade %s to:', 'paid-member-subscriptions' ), '<strong>' . $subscription_plan->name . '</strong>' ) . '</div>', $subscription_plan, $member );

        // Output subscription plans
        $output .= pms_output_subscription_plans( $subscription_plan_upgrades, array(), false, '', 'upgrade_subscription' );

        // Used to output the Billing Information and Credit Card form
        ob_start();

        do_action( 'pms_upgrade_subscription_form_bottom' );

        $output .= ob_get_contents();

        ob_end_clean();

        // Output nonce field
        $output .= wp_nonce_field( 'pms_upgrade_subscription', 'pmstkn' );

        // Output submit button
        $output .= '<input type="submit" name="pms_upgrade_subscription" value="' . esc_attr( apply_filters( 'pms_upgrade_subscription_button_value', __( 'Upgrade Subscription', 'paid-member-subscriptions' ) ) ) . '" />';
        $output .= '<input type="submit" name="pms_redirect_back" value="' . esc_attr( apply_filters( 'pms_upgrade_subscription_go_back_button_value', __( 'Go back', 'paid-member-subscriptions' ) ) ) . '" />';

    $output .= '</form>';

    return apply_filters( 'pms_the_content_member_upgrade_subscription', $output, $content, $user_id, $subscription_plan );

}
add_filter( 'pms_account_shortcode_content', 'pms_member_upgrade_subscription', 11 );

/**
 * Hijack the content when a member wants to change his subscription plan
 */
function pms_member_change_subscription( $content ){

    // Do nothing if we cannot validate the nonce
    if( !isset( $_REQUEST['pmstkn'] ) || !( wp_verify_nonce( sanitize_text_field( $_REQUEST['pmstkn'] ), 'pms_member_nonce' ) || wp_verify_nonce( sanitize_text_field( $_REQUEST['pmstkn'] ), 'pms_change_subscription' ) ) )
        return $content;

    $user_id = pms_get_current_user_id();
    $member  = pms_get_member( $user_id );

    // Do nothing if the user is not a member
    if( !$member->is_member() )
        return $content;

    if( !isset( $_REQUEST['pms-action'] ) || ( $_REQUEST['pms-action'] !== 'change_subscription' ) || !isset( $_REQUEST['subscription_plan'] ) )
        return $content;

    $current_subscription         = isset( $_REQUEST['subscription_id'] ) ? pms_get_member_subscription( absint( $_REQUEST['subscription_id'] ) ) : '';
    $current_subscription_plan_id = trim( absint( $_REQUEST['subscription_plan'] ) );
    $current_subscription_plan    = pms_get_subscription_plan( $current_subscription_plan_id );

    $payments_settings = get_option( 'pms_payments_settings' );

    // Determine if we have anything to show
    $subscription_plan_upgrades = pms_get_subscription_plan_upgrades( $current_subscription_plan_id );

    if( isset( $payments_settings['allow-downgrades'] ) && $payments_settings['allow-downgrades'] == '1' )
        $subscription_plan_downgrades = pms_get_subscription_plan_downgrades( $current_subscription_plan_id );
    else
        $subscription_plan_downgrades = array();

    if( isset( $payments_settings['allow-change'] ) && $payments_settings['allow-change'] == '1' ){

        // Grab only the Plan IDs from the Upgrades and Downgrades lists and then merge them
        $excluded = array_merge( array_map( function( $plan ) { return $plan->id; }, $subscription_plan_upgrades ), array_map( function( $plan ) { return $plan->id; }, $subscription_plan_downgrades ) );

        // Exclude subscriptions that the user already has
        if( !empty( $member->subscriptions ) ){
            foreach( $member->subscriptions as $member_subscription ){

                // Need to exclude the whole tier if a user is subscribed to another plan
                $plans_from_tier = pms_get_subscription_plans_group( $member_subscription['subscription_plan_id'] );

                foreach( $plans_from_tier as $plan ){
                    $excluded[] = $plan->id;
                }

            }
        }

        $subscription_plan_others = pms_get_subscription_plans( true, array(), $excluded );

    } else
        $subscription_plan_others = array();

    $subscription_plan_upgrades   = apply_filters( 'pms_member_change_subscription_upgrade_plans', $subscription_plan_upgrades, $current_subscription, $current_subscription_plan_id );
    $subscription_plan_downgrades = apply_filters( 'pms_member_change_subscription_downgrade_plans', $subscription_plan_downgrades, $current_subscription, $current_subscription_plan_id );
    $subscription_plan_others     = apply_filters( 'pms_member_change_subscription_other_plans', $subscription_plan_others, $current_subscription, $current_subscription_plan_id );

    ob_start();

        include 'views/actions/view-actions-account-change-subscription-form.php';

    $output = ob_get_contents();
    ob_end_clean();

    return apply_filters('pms_change_subscription_shortcode_content', $output, 'change_subscription_form');

}
add_filter( 'pms_account_shortcode_content', 'pms_member_change_subscription', 11 );

/*
 * Hijack the content when a member wants to renew a subscription plan
 *
 */
function pms_member_renew_subscription( $content ) {

    // Verify nonce
    if( !isset( $_REQUEST['pmstkn'] ) || !( wp_verify_nonce( sanitize_text_field( $_REQUEST['pmstkn'] ), 'pms_member_nonce' ) || wp_verify_nonce( sanitize_text_field( $_REQUEST['pmstkn'] ), 'pms_renew_subscription' ) ) )
        return $content;

    $user_id = pms_get_current_user_id();
    $member  = pms_get_member( $user_id );

    // Do nothing if the user is not a member
    if( !$member->is_member() )
        return $content;

    if( !isset( $_REQUEST['pms-action'] ) || ($_REQUEST['pms-action'] !== 'renew_subscription') || !isset( $_REQUEST['subscription_plan'] ) )
        return $content;

    if( !in_array( trim( $_REQUEST['subscription_plan'] ), $member->get_subscriptions_ids() ) )
        return $content;

    // Get subscription plan and member subscription
    $subscription_plan       = pms_get_subscription_plan( trim( absint( $_REQUEST['subscription_plan'] ) ) );
    $member_subscription     = $member->get_subscription( $subscription_plan->id );

    // If member subscription is not in renewal period,
    // return the default content
    $renewal_display_time = apply_filters( 'pms_output_subscription_plan_action_renewal_time', 15 );
    if( ( $member_subscription['status'] != 'canceled' && strtotime( $member_subscription['expiration_date'] ) - time() > $renewal_display_time * 86400 ) || ( $subscription_plan->is_fixed_period_membership() && !$subscription_plan->fixed_period_renewal_allowed() ) )
        return $content;


    if( ( !$subscription_plan->is_fixed_period_membership() && $subscription_plan->duration !== 0 ) || ( $subscription_plan->is_fixed_period_membership() && $subscription_plan->fixed_period_renewal_allowed() ) ) {

        if( time() > strtotime( $member_subscription['expiration_date'] ) ){
            if( $subscription_plan->is_fixed_period_membership() ){
                $renew_expiration_date = date_i18n( get_option('date_format'), strtotime( '+ 1 year', strtotime( $member_subscription['expiration_date'] ) ) );
            } else{
                $renew_expiration_date = date_i18n( get_option('date_format'), strtotime( '+' . $subscription_plan->duration . ' ' . $subscription_plan->duration_unit, time() ) );
            }
        }
        else{
            if( $subscription_plan->is_fixed_period_membership() ){
                $renew_expiration_date = date_i18n( get_option('date_format'), strtotime( pms_sanitize_date($member_subscription['expiration_date']) . '+ 1 year' ) );
            } else{
                $renew_expiration_date = date_i18n( get_option('date_format'), strtotime( pms_sanitize_date($member_subscription['expiration_date']) . '+' . $subscription_plan->duration . ' ' . $subscription_plan->duration_unit ) );
            }
        }
    } else {
        $renew_expiration_date = $subscription_plan->get_expiration_date(true);
        if( !empty( $renew_expiration_date ) )
            $renew_expiration_date = date_i18n( get_option('date_format'), $renew_expiration_date);
        else
            $renew_expiration_date = __( "Unlimited", 'paid-member-subscriptions' );
    }

    /**
     * Filter the new Expiration Date that is displayed in the Renew Subscription form message
     */
    $renew_expiration_date = apply_filters( 'pms_renew_subscription_display_expiration_date', $renew_expiration_date, $subscription_plan, $member_subscription );

    $extra_classes = apply_filters( 'pms_add_extra_form_classes', '' , 'renew_subscription_form' );

    // Output form
    $output = '<form id="pms-renew-subscription-form" action="" method="POST" class="pms-form '. $extra_classes .'">';

        // Do Actions at the top of the form
        ob_start();

        do_action('pms_renew_subscription_form_top');

        $output .= ob_get_contents();
        ob_end_clean();


        // Output tagline
        $output .= apply_filters( 'pms_renew_subscription_before_form', '<p>' . sprintf( __( 'Renew %s subscription. The subscription will be active until %s', 'paid-member-subscriptions' ), '<strong>' . $subscription_plan->name . '</strong>', '<strong>' . $renew_expiration_date .'</strong>' ) . '</p>', $subscription_plan, $member );

        // Output subscription plans
        $output .= pms_output_subscription_plans( array( $subscription_plan ), array(), false, '', 'renew_subscription' );

        // Used to output the Billing Information and Credit Card form
        ob_start();

        do_action('pms_renew_subscription_form_bottom');

        $output .= ob_get_contents();
        ob_end_clean();

        // Output nonce field
        $output .= wp_nonce_field( 'pms_renew_subscription', 'pmstkn' );

        // Output current subscription id
        $output .= '<input type="hidden" name="pms_current_subscription" value="'. esc_attr( $member_subscription['id'] ) .'" />';

        // Output submit button
        $output .= '<input type="submit" name="pms_renew_subscription" value="' . esc_attr( apply_filters( 'pms_renew_subscription_button_value', esc_html__( 'Renew Subscription', 'paid-member-subscriptions' ) ) ). '" />';
        $output .= '<input type="submit" name="pms_redirect_back" value="' . esc_attr( apply_filters( 'pms_renew_subscription_go_back_button_value', esc_html__( 'Go back', 'paid-member-subscriptions' ) ) ) . '" />';

    $output .= '</form>';

    return apply_filters( 'pms_the_content_member_renew_subscription', $output, $content, $user_id, $subscription_plan );

}
add_filter( 'pms_account_shortcode_content', 'pms_member_renew_subscription', 11 );



/*
 * Hijack the content when a member wants to cancel a subscription
 *
 */
function pms_member_cancel_subscription( $content ) {

    // Verify nonce
    if( ! isset( $_REQUEST['pmstkn'] ) || ! wp_verify_nonce( sanitize_text_field( $_REQUEST['pmstkn'] ), 'pms_member_nonce' ) )
        return $content;

    if( ! isset( $_REQUEST['pms-action'] ) || ( $_REQUEST['pms-action'] !== 'cancel_subscription' ) || ! isset( $_GET['subscription_id'] ) )
        return $content;

    // Get member and the member's subscription
    $member              = pms_get_member( get_current_user_id() );
    $member_subscription = pms_get_member_subscription( absint( $_GET['subscription_id'] ) );

    if( is_null( $member_subscription ) )
        return $content;

    if( $member_subscription->status != 'active' )
        return $content;

    if( ! in_array( $member_subscription->id, $member->get_subscription_ids() ) )
        return $content;

    // Get subscription plan
    $subscription_plan = pms_get_subscription_plan( (int)$member_subscription->subscription_plan_id );

    $extra_classes = apply_filters( 'pms_add_extra_form_classes', '' , 'cancel_subscription_form' );

    // Output form
    $output = '<form id="pms-cancel-subscription-form" action="" method="POST" class="pms-form '. $extra_classes .'">';

        $output .= apply_filters( 'pms_cancel_subscription_confirmation_message', '<p>' . sprintf( __( 'Are you sure you want to cancel your %s subscription? No further payments will be made for this subscription and it will expire.', 'paid-member-subscriptions' ) . '</p>', '<strong>' . $subscription_plan->name . '</strong>' ), $subscription_plan );

        // Hidden subscription id field
        $output .= '<input type="hidden" name="subscription_id" value="' . esc_attr( $member_subscription->id ) . '" />';

        // Output nonce field
        $output .= wp_nonce_field( 'pms_cancel_subscription', 'pmstkn' );

        // Output submit button
        $output .= '<input type="submit" name="pms_confirm_cancel_subscription" value="' . esc_attr( apply_filters( 'pms_cancel_subscription_button_value', esc_html__( 'Confirm', 'paid-member-subscriptions' ) ) ) . '" />';
        $output .= '<input type="submit" name="pms_redirect_back" value="' . esc_attr( apply_filters( 'pms_cancel_subscription_go_back_button_value', esc_html__( 'Go back', 'paid-member-subscriptions' ) ) ) . '" />';


    $output .= '</form>';

    return apply_filters('pms_cancel_subscription_form_content', $output, 'cancel_subscription_form');

}
add_filter( 'pms_account_shortcode_content', 'pms_member_cancel_subscription', 11 );


/*
 * Hijack the content when a member wants to abandon a subscription
 *
 */
function pms_member_abandon_subscription( $content ) {

    // Verify nonce
    if( ! isset( $_REQUEST['pmstkn'] ) || ! wp_verify_nonce( sanitize_text_field( $_REQUEST['pmstkn'] ), 'pms_member_nonce' ) )
        return $content;

    if( ! isset( $_GET['pms-action'] ) || ( $_GET['pms-action'] != 'abandon_subscription' ) || ! isset( $_GET['subscription_id'] ) )
        return $content;

    // Get member and the member's subscription
    $member              = pms_get_member( get_current_user_id() );
    $member_subscription = pms_get_member_subscription( absint( $_GET['subscription_id'] ) );

    if( is_null( $member_subscription ) )
        return $content;

    if( ! in_array( $member_subscription->id, $member->get_subscription_ids() ) )
        return $content;

    // Get subscription plan
    $subscription_plan = pms_get_subscription_plan( (int)$member_subscription->subscription_plan_id );

    $extra_classes = apply_filters( 'pms_add_extra_form_classes', '' , 'abandon_subscription_form' );

    // Output form
    $output = '<form id="pms-abandon-subscription-form" action="" method="POST" class="pms-form '. $extra_classes .'">';

    $output .= apply_filters( 'pms_abandon_subscription_confirmation_message', '<p>' . sprintf( __( 'Are you sure you want to abandon your %s subscription? This subscription will be removed completely from your account.', 'paid-member-subscriptions' ) . '</p>', '<strong>' . $subscription_plan->name . '</strong>' ), $subscription_plan );

    // Hidden subscription id field
    $output .= '<input type="hidden" name="subscription_id" value="' . esc_attr( $member_subscription->id ) . '" />';

    // Output nonce field
    $output .= wp_nonce_field( 'pms_abandon_subscription', 'pmstkn' );

    // Output submit button
    $output .= '<input type="submit" name="pms_confirm_abandon_subscription" value="' . esc_attr( apply_filters( 'pms_abandon_subscription_button_value', esc_html__( 'Abandon Subscription', 'paid-member-subscriptions' ) ) ). '" />';
    $output .= '<input type="submit" name="pms_redirect_back" value="' . esc_attr( apply_filters( 'pms_abandon_subscription_go_back_button_value', esc_html__( 'Go back', 'paid-member-subscriptions' ) ) ) . '" />';

    $output .= '</form>';

    return apply_filters('pms_abandon_subscription_form_content', $output, 'abandon_subscription_form');

}
add_filter( 'pms_account_shortcode_content', 'pms_member_abandon_subscription', 11 );


/*
 * Hijack the content when a member wants to abandon a subscription
 *
 */
function pms_member_update_payment_method( $content ) {

    // Verify nonce
    if( ! isset( $_REQUEST['pmstkn'] ) || ! wp_verify_nonce( sanitize_text_field( $_REQUEST['pmstkn'] ), 'pms_update_payment_method' ) )
        return $content;

    if( ! isset( $_GET['pms-action'] ) || ( $_GET['pms-action'] != 'update_payment_method' ) || ! isset( $_GET['subscription_id'] ) )
        return $content;

    // Get member and the member's subscription
    $member              = pms_get_member( get_current_user_id() );
    $member_subscription = pms_get_member_subscription( absint( $_GET['subscription_id'] ) );

    if( is_null( $member_subscription ) )
        return $content;

    if( ! in_array( $member_subscription->id, $member->get_subscription_ids() ) )
        return $content;

    if( !$member_subscription->is_auto_renewing() || !pms_payment_gateways_support( array( $member_subscription->payment_gateway ), 'update_payment_method' ) )
        return $content;

    // Get subscription plan
    $subscription_plan = pms_get_subscription_plan( (int)$member_subscription->subscription_plan_id );

    $extra_classes = apply_filters( 'pms_add_extra_form_classes', '' , 'update_payment_form' );

    // Output form
    $output = '<form id="pms-update-payment-method-form" action="" method="POST" class="pms-form '. $extra_classes .'">';

        ob_start(); ?>

        <?php pms_display_field_errors( pms_errors()->get_error_messages( 'update_payment_method' ) ); ?>

        <p>
            <?php printf( wp_kses_post( __( 'Update recurring payment details for the %s subscription that will renew on %s.', 'paid-member-subscriptions' ) ), '<strong>' . esc_html( $subscription_plan->name ) . '</strong>', '<strong>' . esc_html( date_i18n( get_option('date_format'), strtotime( $member_subscription->billing_next_payment ) ) ) . '</strong>' ) ?>
        </p>
        
        <?php
        do_action( 'pms_update_payment_method_form_bottom', $member_subscription );

        $output .= ob_get_contents();
        ob_end_clean();

        // Hidden subscription id field
        $output .= '<input type="hidden" name="subscription_id" value="' . esc_attr( $member_subscription->id ) . '" />';

        // Output nonce field
        $output .= wp_nonce_field( 'pms_update_payment_method', 'pmstkn' );

        // Output submit button
        $output .= '<input type="submit" name="pms_update_payment_method" value="' . esc_attr( apply_filters( 'pms_update_payment_method_button_value', esc_html__( 'Update payment method', 'paid-member-subscriptions' ) ) ). '" />';
        $output .= '<input type="submit" name="pms_redirect_back" value="' . esc_attr( apply_filters( 'pms_update_payment_method_go_back_button_value', esc_html__( 'Go back', 'paid-member-subscriptions' ) ) ) . '" />';

    $output .= '</form>';

    return apply_filters( 'pms_update_payment_method_form_content', $output, 'update_payment_method_form' );

}
add_filter( 'pms_account_shortcode_content', 'pms_member_update_payment_method', 11 );


/*
 * Hijack the content when a member wants to retry a payment for a pending subscription
 *
 */
function pms_member_retry_payment_subscription( $content ) {

    // Verify nonce
    if( !isset( $_REQUEST['pmstkn'] ) || !( wp_verify_nonce( sanitize_text_field( $_REQUEST['pmstkn'] ), 'pms_member_nonce' ) || wp_verify_nonce( sanitize_text_field( $_REQUEST['pmstkn'] ), 'pms_retry_payment_subscription' ) ) )
        return $content;

    $user_id = pms_get_current_user_id();
    $member  = pms_get_member( $user_id );

    // Do nothing if the user is not a member
    if( !$member->is_member() )
        return $content;

    if( !isset( $_REQUEST['pms-action'] ) || ($_REQUEST['pms-action'] !== 'retry_payment_subscription') || !isset( $_REQUEST['subscription_plan'] ) )
        return $content;

    if( !in_array( trim( $_REQUEST['subscription_plan'] ), $member->get_subscriptions_ids() ) )
        return $content;

    // Return if the subscription is not pending
    $member_subscription = $member->get_subscription( trim( absint( $_REQUEST['subscription_plan'] ) ) );

    if( $member_subscription['status'] != 'pending' )
        return $content;

    $extra_classes = apply_filters( 'pms_add_extra_form_classes', '' , 'retry_payment_form' );

    // Output form
    $output = '<form id="pms-retry-payment-subscription-form" action="" method="POST" class="pms-form '. $extra_classes .'">';

    // Do Actions at the top of the form
    ob_start();

    do_action('pms_retry_payment_form_top');

    $output .= ob_get_contents();
    ob_end_clean();


    // Get subscription plan
    $subscription_plan = pms_get_subscription_plan( trim( absint( $_REQUEST['subscription_plan'] ) ) );

    $output .= apply_filters( 'pms_retry_payment_subscription_confirmation_message', '<p>' . sprintf( __( 'Your %s subscription is still pending. Do you wish to retry the payment?', 'paid-member-subscriptions' ) . '</p>', '<strong>' . $subscription_plan->name . '</strong>' ), $subscription_plan );

    // Output subscription plans
    $output .= pms_output_subscription_plans( array($subscription_plan), array(), false, '', 'retry_payment' );

    // Used to output the Billing Information and Credit Card form
    ob_start();

    do_action('pms_retry_payment_form_bottom');

    $output .= ob_get_contents();
    ob_end_clean();


    // Output nonce field
    $output .= wp_nonce_field( 'pms_retry_payment_subscription', 'pmstkn' );

    // Output submit button
    $output .= '<input type="submit" name="pms_confirm_retry_payment_subscription" value="' . esc_attr( apply_filters( 'pms_retry_payment_subscription_button_value', __( 'Retry payment', 'paid-member-subscriptions' ) ) ) . '" />';
    $output .= '<input type="submit" name="pms_redirect_back" value="' . esc_attr( apply_filters( 'pms_retry_payment_subscription_go_back_button_value', __( 'Go back', 'paid-member-subscriptions' ) ) ). '" />';

    $output .= '</form>';

    return apply_filters( 'pms_retry_payment_shortcode_content', $output, 'retry_payment_form');

}
add_filter( 'pms_account_shortcode_content', 'pms_member_retry_payment_subscription', 11 );

/**
 * Make sure we do not execute shortcodes in our restrict messages if they are generated on the wp_head hook by SEO plugins like YOAST
 */
function pms_remove_shortcodes_from_messages_in_wp_head( $message ) {

    global $wp_current_filter;
    if( !empty( $wp_current_filter ) && is_array( $wp_current_filter ) ){
        foreach( $wp_current_filter as $filter ){
            if( $filter == 'wp_head' ) {
                $message = strip_shortcodes($message);
            }
        }
    }

    return $message;
}
add_filter( 'pms_restriction_message_logged_out', 'pms_remove_shortcodes_from_messages_in_wp_head', 12 );
add_filter( 'pms_restriction_message_non_members', 'pms_remove_shortcodes_from_messages_in_wp_head', 12 );

function pms_comments_restrict_replying( $open, $post_id ) {
    // Show for administrators
    if( current_user_can( 'manage_options' ) && is_admin() )
        return $open;

    if( pms_is_post_restricted( $post_id ) )
        return false;

    return $open;
}

function pms_comments_change_callback_function( $args ) {
    global $post;

    if ( empty( $post->ID ) ) return $args;

    if( pms_is_post_restricted( $post->ID ) )
        $args['callback'] = 'pms_comments_restrict_view';

    return $args;
}

function pms_comments_restrict_view( $comment, $args, $depth ) {
    static $message_shown = false;

    if ( !$message_shown ) {

        if ( is_user_logged_in() )
            printf( '<p>%s</p>', esc_html( apply_filters( 'pms_comments_restriction_message_non_members', __( 'Comments are restricted for your membership level.', 'paid-member-subscriptions' ) ) ) );
        else
            printf( '<p>%s</p>', esc_html( apply_filters( 'pms_comments_restriction_message_logged_out', __( 'You must be logged in to view the comments.', 'paid-member-subscriptions' ) ) ) );

        $message_shown = true;
    }
}

$pms_cr_settings = get_option( 'pms_content_restriction_settings' );

if ( isset( $pms_cr_settings['comments_restriction']['option'] ) && $pms_cr_settings['comments_restriction']['option'] != 'off' ) {
    add_filter( 'comments_open', 'pms_comments_restrict_replying', 20, 2 );

    if ( $pms_cr_settings['comments_restriction']['option'] == 'restrict-everything' )
        add_filter( 'wp_list_comments_args', 'pms_comments_change_callback_function', 999 );
}

if( !function_exists( 'wppb_exclude_restricted_comments' ) ){
    add_filter( 'the_comments', 'pms_exclude_restricted_comments', 10, 2 );
    function pms_exclude_restricted_comments( $comments, $query ){
        if( !empty( $comments ) && !current_user_can( 'manage_options' ) ){
            $user_id = get_current_user_id();
            foreach ( $comments as $key => $comment ){
                $post = get_post( $comment->comment_post_ID );
                if( ( $post->post_type == 'private-page' && $user_id != (int)$post->post_author ) || ( function_exists( 'wppb_content_restriction_is_post_restricted' ) && wppb_content_restriction_is_post_restricted( $comment->comment_post_ID ) ) || ( function_exists( 'pms_is_post_restricted' ) && pms_is_post_restricted( $comment->comment_post_ID ) ) ){
                    unset( $comments[$key] );
                }
            }
        }
        return $comments;
    }
}

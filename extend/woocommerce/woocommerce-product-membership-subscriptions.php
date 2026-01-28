<?php
/**
 * PMS - WooCommerce Product Membership Subscription
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}


// Add Subscription Plan Tab to Product-Data section (Add New | Edit)
function pms_woo_subscriptions_tab( $tabs ) {
    global $post;

    if(!isset($post->ID))
        return;

    $tabs['pms_subscription'] = array(
        'label'    => __('Subscription Plan', 'paid-member-subscriptions'),
        'target'   => 'pms_membership_subscription',
        'class'    => array()
    );

    return $tabs;
}
add_filter( 'woocommerce_product_data_tabs', 'pms_woo_subscriptions_tab' );


// Add Subscription Plan Tab content
function pms_woo_subscription_tab_content() {
    global $post;

    if( !isset( $post->ID ))
        return;

    $options = array( '0' => __('None', 'paid-member-subscriptions' ));
    $subscription_plans = pms_get_subscription_plans();
    $subscription_plans = apply_filters('pms_woo_subscription_plans', $subscription_plans );
    $existing_subscription = array( 'id' => pms_woo_get_product_subscription_id( $post->ID ));

    foreach( $subscription_plans as $sub ) {
        $pwyw_enabled = get_post_meta( $sub->id, 'pms_subscription_plan_pay_what_you_want', true );
        if ( $pwyw_enabled != '1' && !$sub->is_fixed_period_membership() )
            $options[$sub->id] = $sub->name;
    }

    ?>
    <div id="pms_membership_subscription" class="panel woocommerce_options_panel">
        <p><?php echo esc_html(__('Please select a Subscription Plan you want to associate with this product:', 'paid-member-subscriptions')); ?></p>
        <div class="options_group">
            <?php
            woocommerce_wp_select (
                array(
                    'id'          => 'pms_woo_subscription_id',
                    'label'       => esc_html__('Subscription Plan', 'paid-member-subscriptions'),
                    'description' => '',
                    'value'       => ( !empty($existing_subscription['id']) ? $existing_subscription['id'] : '0'),
                    'options'     => $options
                )
            );
            ?>
        </div>
        <div class="extra_info" style="padding-left: 10px;">
            <h4><em><?php echo esc_html(__('For this Subscription Plan association to work properly we need the following WooCommerce settings to be met:', 'paid-member-subscriptions')); ?></em></h4>
            <ol>
                <li><em><?php echo sprintf(esc_html__('Allow customers to place orders without an account must be %1$sDISABLED%2$s', 'paid-member-subscriptions'), "<strong>", "</strong>"); ?></em></li>
                <li><em><?php echo sprintf(esc_html__('Allow customers to create an account during checkout must be %1$sENABLED%2$s', 'paid-member-subscriptions'), "<strong>", "</strong>"); ?></em></li>
            </ol>
        </div>
    </div>
    <?php
}
add_action( 'woocommerce_product_data_panels', 'pms_woo_subscription_tab_content' );


// Add Subscription options when saving Product information (Add New | Edit)
function pms_woo_add_subscription_meta_to_product( $product_id ) {
    if( isset( $_POST['pms_woo_subscription_id'] )  && (int)$_POST['pms_woo_subscription_id'] >= 0 ) {
        update_post_meta( $product_id, '_pms_woo_subscription_id', (int)$_POST['pms_woo_subscription_id'] );
    }
}
add_action( 'woocommerce_process_product_meta', 'pms_woo_add_subscription_meta_to_product' );


// Check if there is any Subscription in the Cart
function pms_woo_subscription_in_cart() {
    global $woocommerce;

    if( !isset( $woocommerce->cart ))
        return false;

    $products = $woocommerce->cart->get_cart();

    if( empty( $products ))
        return false;

    foreach( $products as $prod_key => $product ) {
        if( isset( $product['product_id'] ) && pms_woo_get_product_subscription_id( $product['product_id'] ))
            return true;
    }

    return false;
}


// Get the Subscription ID (linked to product)
function pms_woo_get_product_subscription_id( $product_id ) {
    if( !$product_id )
        return 0;

    return get_post_meta( $product_id, '_pms_woo_subscription_id', true );
}


// Disable Guest-Checkout if a Subscription is present in the Cart
function pms_woo_guest_checkout_status( $status ) {
    if( pms_woo_subscription_in_cart() ) {
        return 'no';
    }
    return $status;
}
add_filter( 'pre_option_woocommerce_enable_guest_checkout', 'pms_woo_guest_checkout_status', 100, 2 );


// Enable Registration on Checkout if a Subscription is present in the Cart
function pms_woo_enable_registration_on_checkout( $status ) {
    if( pms_woo_subscription_in_cart() )
        $status = true;

    return $status;
}
add_filter( 'woocommerce_checkout_registration_enabled', 'pms_woo_enable_registration_on_checkout', 100 );


// Set the Subscription Status based on WooCommerce Order Status and Product Type (subscription or other)
function pms_woo_set_subscription_status( $order_status, $product_type, $existing_status, $woo_subscription_status ) {

    if ( $product_type != 'subscription' ) {

        if ( $order_status == 'completed' || $order_status == 'processing' )
            $subscription_status = 'active';

        elseif ( $order_status == 'cancelled' && $existing_status == 'active' )
            $subscription_status = 'canceled';

        elseif ( $order_status == 'refunded' || ( $order_status == 'cancelled' && $existing_status != 'active') )
            $subscription_status = 'expired';

        else $subscription_status = 'pending';

    }
    elseif ( !empty( $woo_subscription_status ) ) {

        if ( $woo_subscription_status == 'active' )
            $subscription_status = 'active';

        elseif ( $woo_subscription_status == 'on-hold' || $woo_subscription_status == 'pending' )
            $subscription_status = 'pending';

        elseif ( $woo_subscription_status == 'pending-cancel' )
            $subscription_status = 'canceled';

        elseif ( $woo_subscription_status == 'cancelled' || $woo_subscription_status == 'expired' )
            $subscription_status = 'expired';

        else $subscription_status = $existing_status;

    }
    elseif ( $order_status == 'completed' )
        $subscription_status = 'active';
    elseif ( $order_status == 'cancelled' || $order_status == 'failed' || $order_status == 'refunded' )
        $subscription_status = 'expired';
    else $subscription_status = 'pending';

    return $subscription_status;
}


// Check if there is a WooCommerce Subscription Renewal linked to the product
function pms_woo_is_product_subscription_renewal( $product ) {
    $meta_data = $product->get_meta_data();
    $renewal = false;
    foreach ( $meta_data as $meta_key => $data ) {
        if ( isset( $data->key ) && $data->key == '_cart_item_key_subscription_renewal' )
            $renewal = true;
    }
    return $renewal;
}


// Check if WooCommerce Order Status was changed manually from the administration panel
function pms_woo_is_manual_order_update( $existing_subscription_id, $order_key ) {

    if ( !is_admin() )
        return false;

    $existing_order_key = pms_get_member_subscription_meta( $existing_subscription_id, 'woo_order_key' );
    $manual_order_update = false;
    if ( !empty( $existing_order_key ) )
        foreach ($existing_order_key as $woo_key) {
            if ($woo_key == $order_key)
                $manual_order_update = true;
        }
    return $manual_order_update;
}


// Check if the new Subscription Plan is an Upgrade or a Downgrade and set the new data
function pms_get_subscription_replacement_data( $user_id, $new_subscription_plan_id, $new_subscription_status ) {

    $existing_subscriptions = pms_get_member_subscriptions( array( 'user_id' => $user_id ) );

    if ( empty( $existing_subscriptions ) )
        return array();

    $new_subscription = pms_get_subscription_plan( $new_subscription_plan_id );
    $upgrades = array_map( function( $plan ) { return $plan->id; }, pms_get_subscription_plan_upgrades( $new_subscription_plan_id ) );
    $downgrades = array_map( function( $plan ) { return $plan->id; }, pms_get_subscription_plan_downgrades( $new_subscription_plan_id ) );

    if ( empty( $upgrades ) && empty( $downgrades ) ) {
        return array();
    }

    $new_subscription_plan_name = $new_subscription->name;
    $billing_next_payment = ( !empty( $new_subscription->billing_next_payment ) ) ? $new_subscription->billing_next_payment : '';
    $replacement_data = array();

    foreach ( $existing_subscriptions as $old_subscription ) {

        if ( in_array( $old_subscription->subscription_plan_id, $upgrades ) ) {
            $replacement_type = 'downgraded';
            $existing_subscription_id = $old_subscription->id;
        }
        elseif ( in_array( $old_subscription->subscription_plan_id, $downgrades ) ) {
            $replacement_type = 'upgraded';
            $existing_subscription_id = $old_subscription->id;
        }

    }

    if ( isset( $existing_subscription_id, $replacement_type ) ) {

        $replacement_data = array(
            'id' => $existing_subscription_id,
            'subscription_plan_id' => $new_subscription_plan_id,
            'start_date' => date( 'Y-m-d H:i:s' ),
            'expiration_date' => $new_subscription->get_expiration_date(),
            'trial_end' => pms_sanitize_date( $new_subscription->get_trial_expiration_date() ),
            'billing_next_payment' => $billing_next_payment,
            'status' => $new_subscription_status,
            'payment_gateway' => 'WooCommerce',
            'replacement_type' => $replacement_type,
            'new_name' => $new_subscription_plan_name
        );

    }

    return $replacement_data;

}


// Get the Subscription Data for the Product linked Subscription
function pms_woo_subscription_data( $subscription_plan_id, $order_id, $order_status, $order_payment_method, $order_key, $product_type, $user_email ) {

    $subscription_plan = pms_get_subscription_plan( $subscription_plan_id );
    $member = pms_get_member( email_exists( $user_email ));
    $user_id = $member->user_id;
    $existing_subscription = pms_get_member_subscriptions( array( 'user_id' => $user_id, 'subscription_plan_id' => $subscription_plan_id ) );
    $subscription_data = array();

    if ( is_object( $subscription_plan ) && !empty( $user_id ) ) {

        if ( isset( $existing_subscription['0'] ))
            $existing_subscription_status = $existing_subscription['0']->status;
        else $existing_subscription_status = '';

        if ( $order_status == 'processing' && $order_payment_method !== 'stripe_sepa' && $existing_subscription_status !== 'active' )
            $subscription_status = 'pending';
        else $subscription_status = pms_woo_set_subscription_status( $order_status, $product_type, $existing_subscription_status, '' );

        $replacement_data = pms_get_subscription_replacement_data( $user_id, $subscription_plan_id, $subscription_status );

        if( isset( $existing_subscription['0'] )) {

            // reset the expiration date if the subscription expired
            if ( $existing_subscription_status === 'expired' ) {
                $subscription_expiration_date = '';
                $subscription_next_payment_date = '';
            }
            else {
                $subscription_expiration_date = $existing_subscription['0']->expiration_date;
                $subscription_next_payment_date = $existing_subscription['0']->billing_next_payment;
            }

            if ( $product_type == 'subscription' || $order_status == 'completed' ) {

                if ( pms_woo_is_manual_order_update( $existing_subscription['0']->id, $order_key ) && $subscription_status == 'active' && $product_type != 'subscription' && ( $existing_subscription['0']->status == 'pending' || $existing_subscription['0']->status == 'expired' ) ) { //   update expiration date if subscription status pending or expired (order status changed manually)
                        $subscription_expiration_date = $subscription_plan->get_expiration_date();
                }
                elseif ( $existing_subscription['0']->status != 'abandoned' && ( !pms_woo_is_manual_order_update( $existing_subscription['0']->id, $order_key ) || ( pms_woo_is_manual_order_update( $existing_subscription['0']->id, $order_key ) && $subscription_status == 'active' && $product_type != 'subscription' ) ) ) { // extend expiration date if Subscription is not Abandoned (new/renewal order placed for already subscribed-to Subscription Plan)

                    if ( !empty( $subscription_next_payment_date )) {
                        $old_next_payment_date = strtotime( $existing_subscription['0']->billing_next_payment );
                        $new_next_payment_date = strtotime( "+" . $existing_subscription['0']->billing_duration . " " . $existing_subscription['0']->billing_duration_unit, $old_next_payment_date );
                        $subscription_next_payment_date = date( 'Y-m-d H:i:s', $new_next_payment_date );
                    }
                    elseif ( !empty( $subscription_expiration_date ) ) {
                        $old_expiration_timestamp = strtotime( $existing_subscription['0']->expiration_date );
                        $new_expiration_timestamp = strtotime( "+" . $subscription_plan->duration . ' ' . $subscription_plan->duration_unit, $old_expiration_timestamp );
                        $subscription_expiration_date = date( 'Y-m-d H:i:s', $new_expiration_timestamp );
                    }
                    else $subscription_expiration_date = $subscription_plan->get_expiration_date();

                    if ( $existing_subscription['0']->status == 'active' ) {  // if subscription already Active don't update status
                        $subscription_status = $existing_subscription['0']->status;
                    }
                    elseif ( $product_type == 'subscription' && function_exists( 'wcs_get_subscriptions_for_renewal_order' ) ) { // Sync PMS Subscription and WooCommerce Subscription statuses
                        $renewal_order_related_subscriptions = wcs_get_subscriptions_for_renewal_order( $order_id );

                        foreach ( $renewal_order_related_subscriptions as $woo_subscription ) {
                            $woo_subscription_data = $woo_subscription->get_data();
                            $subscription_status = pms_woo_set_subscription_status( $order_status, $product_type, $existing_subscription_status, $woo_subscription_data['status'] );
                        }

                    }

                }


            }

            $subscription_data = array(
                'id' => $existing_subscription['0']->id,
                'expiration_date' => $subscription_expiration_date,
                'billing_next_payment' => $subscription_next_payment_date,
                'status' => $subscription_status
            );

        }
        elseif ( !empty( $replacement_data )) {
            $subscription_data = $replacement_data;
        }
        else {
            $subscription_data = array(
                'user_id'              => $user_id,
                'subscription_plan_id' => $subscription_plan->id,
                'start_date'           => date( 'Y-m-d H:i:s' ),
                'expiration_date'      => $subscription_plan->get_expiration_date(),
                'status'               => $subscription_status,
                'trial_end'            => pms_sanitize_date( $subscription_plan->get_trial_expiration_date() ),
                'payment_gateway'      => 'WooCommerce'
            );
        }

    }

    return $subscription_data;

}
add_filter( 'pms_woo_get_subscription_data', 'pms_woo_subscription_data', 100, 7 );


// Add new Membership Subscription
function pms_woo_add_new_member_subscription( $subscription_data, $order_id, $order_key ) {

    if ( empty( $subscription_data ) )
        return;

    $subscription = new PMS_Member_Subscription();
    $subscription->insert($subscription_data);

    pms_add_member_subscription_meta( $subscription->id, 'woo_order_key', $order_key );

    if ( file_exists(PMS_PLUGIN_DIR_PATH . 'includes/functions-user-roles.php' )) {
        pms_add_user_role( $subscription->user_id, pms_get_subscription_plan_user_role( $subscription->subscription_plan_id ) );
    }

    if( function_exists( 'pms_add_member_subscription_log' ) ) {
        pms_add_member_subscription_log($subscription->id, 'woocommerce_new_product_subscription', array('order_id' => $order_id));
        if ( $subscription_data['status'] == 'active' )
            pms_add_member_subscription_log( $subscription->id, 'woocommerce_product_subscription_activate', array( 'expiration_date' => $subscription_data['expiration_date'], 'order_id' => $order_id ));
    }
}


// Update existing Membership Subscription
function pms_woo_update_member_subscription( $subscription_data, $subscription_renewal, $user_existing_subscriptions, $order_id, $order_key ) {

    if ( empty( $subscription_data ) || empty( $subscription_data['id'] ) )
        return;

    $subscription = pms_get_member_subscription( $subscription_data['id'] );
    $subscription->update( $subscription_data );

    if ( !pms_woo_is_manual_order_update( $subscription->id, $order_key )) {
        pms_add_member_subscription_meta( $subscription->id, 'woo_order_key', $order_key );
    }

    if( function_exists( 'pms_add_member_subscription_log' ) ) {
        foreach ( $user_existing_subscriptions as $existing_sub) {
            if ( $existing_sub->id == $subscription_data['id'] ) {
                if ( $existing_sub->status != $subscription_data['status'] ) {
                    if ( $subscription_data['status'] == 'active' ) {
                        if ( $subscription_renewal )
                            pms_add_member_subscription_log($subscription_data['id'], 'woocommerce_product_subscription_expiration_renewal', array('new_expire_date' => $subscription_data['expiration_date'], 'order_id' => $order_id));
                        pms_add_member_subscription_log($subscription_data['id'], 'woocommerce_product_subscription_activate', array('expiration_date' => $subscription_data['expiration_date'], 'order_id' => $order_id));
                    }
                    elseif ( !empty( $existing_sub->status ) && $existing_sub->status != $subscription_data['status'] )
                        pms_add_member_subscription_log( $subscription_data['id'], 'woocommerce_product_subscription_status_update', array( 'old_status' => $existing_sub->status, 'new_status' => $subscription_data['status'], 'order_id' => $order_id ));
                    else pms_add_member_subscription_log( $subscription_data['id'], 'woocommerce_product_subscription_status_set', array( 'status' => $subscription_data['status'], 'order_id' => $order_id ));
                }
                elseif ( !empty( $subscription_data['replacement_type'] ) ) {
                    pms_add_member_subscription_log($subscription_data['id'], 'woocommerce_product_subscription_replacement', array('type' => $subscription_data['replacement_type'], 'new_name' => $subscription_data['new_name'], 'order_id' => $order_id));
                }
                elseif ( !empty( $subscription_data['expiration_date'] ) && $existing_sub->expiration_date != $subscription_data['expiration_date'] ) {
                    if ( $subscription_renewal )
                        pms_add_member_subscription_log($subscription_data['id'], 'woocommerce_product_subscription_expiration_renewal', array('new_expire_date' => $subscription_data['expiration_date'], 'order_id' => $order_id));
                    else pms_add_member_subscription_log($subscription_data['id'], 'woocommerce_product_subscription_expiration_update', array('new_expire_date' => $subscription_data['expiration_date'], 'order_id' => $order_id));
                }
                elseif ( !empty( $subscription_data['billing_next_payment'] ) && $existing_sub->billing_next_payment != $subscription_data['billing_next_payment'] )
                    pms_add_member_subscription_log($subscription_data['id'], 'woocommerce_product_subscription_next_payment_update', array('new_payment_date' => $subscription_data['billing_next_payment']));
            }
        }
    }
}


// Update PMS Subscription Status when WooCommerce Subscription Status is updated
function pms_woo_update_pms_subsciption_status( $woo_subscription, $woo_subscription_new_status, $woo_subscription_old_status ) {
    $woo_subscription_id = $woo_subscription->get_id();
    $items = $woo_subscription->get_items();
    $user = $woo_subscription->get_user();
    $user_id = $user->data->ID;

    foreach ( $items as $item ) {
        $product_id = $item->get_product_id();
        $subscription_plan_id = get_post_meta( $product_id, '_pms_woo_subscription_id', true );
        $existing_subscription = pms_get_member_subscriptions( array('user_id' => $user_id, 'subscription_plan_id' => $subscription_plan_id ));

        if ( isset( $existing_subscription['0'] )) {

            $subscription_status = pms_woo_set_subscription_status( '', 'subscription', $existing_subscription['0']->status, $woo_subscription_new_status );

            if ( $woo_subscription_new_status == 'active' )
                $subscription_data = array( 'id' => $existing_subscription['0']->id, 'expiration_date' => $existing_subscription['0']->expiration_date, 'status' => 'active' );
            else $subscription_data = array( 'id' => $existing_subscription['0']->id, 'status' => $subscription_status );

            pms_woo_update_member_subscription( $subscription_data, false, $existing_subscription, $woo_subscription->get_parent_id(), $woo_subscription->get_order_key() );

            if( function_exists( 'pms_add_member_subscription_log' ) && $woo_subscription_new_status == 'cancelled' )
                pms_add_member_subscription_log( $subscription_data['id'], 'woocommerce_product_subscription_canceled', array( 'woo_subscription_id' => $woo_subscription_id ));

        }
    }
}
add_action('woocommerce_subscription_status_updated', 'pms_woo_update_pms_subsciption_status', 10, 3 );


// Handle Member Subscription
function pms_woo_handle_member_subscription( $order_id ) {

    if ( empty( $order_id ) )
        return $order_id;

    $order = wc_get_order( $order_id );
    if ( !$order )
        return $order_id;

    $user = $order->get_user();
    if ( !$user || !is_object( $user->data ) || !isset( $user->data->ID ) )
        return $order_id;

    $order_status                = $order->get_status();
    $order_payment_method        = $order->get_payment_method();
    $order_key                   = $order->get_order_key();
    $order_items                 = $order->get_items('line_item');
    $total_quantity              = $order->get_item_count();
    $user_existing_subscriptions = pms_get_member_subscriptions( array( 'user_id' => $user->data->ID ));

    foreach( $order_items as $item ) {
        $product_id = $item->get_product_id();
        $product = $item->get_product();
        $product_type = ( is_object( $product ) ) ? $product->get_type() : '';
        $subscription_plan_id = get_post_meta( $product_id, '_pms_woo_subscription_id', true );
        $current_subscription_from_tier = pms_get_current_subscription_from_tier( $user->data->ID ,  $subscription_plan_id );

        if ( $total_quantity == 1 && function_exists( 'wcs_order_contains_renewal' ))
            $subscription_renewal = wcs_order_contains_renewal( $order );
        else $subscription_renewal = pms_woo_is_product_subscription_renewal( $item );

        if ( !empty( $subscription_plan_id ) ) {
            $subscription_data = apply_filters( 'pms_woo_get_subscription_data', $subscription_plan_id, $order_id, $order_status, $order_payment_method, $order_key, $product_type, $user->data->user_email );
            if( isset($subscription_data['id'])) {
                pms_woo_update_member_subscription( $subscription_data, $subscription_renewal, $user_existing_subscriptions, $order_id, $order_key );
            }
            elseif ( empty( $current_subscription_from_tier ) && ( empty( $user_existing_subscriptions ) || class_exists( 'PMS_IN_Multiple_Subscriptions_Per_User' ))) {
                $limit_reached = apply_filters( 'pms_woo_subscription_limit_reached', false, $subscription_plan_id );

                if( !$limit_reached )
                    pms_woo_add_new_member_subscription( $subscription_data, $order_id, $order_key );
            }
        }
    }

    return $order_id;
}
add_action('woocommerce_order_status_pending',    'pms_woo_handle_member_subscription');
add_action('woocommerce_order_status_failed',     'pms_woo_handle_member_subscription');
add_action('woocommerce_order_status_on-hold',    'pms_woo_handle_member_subscription');
add_action('woocommerce_order_status_processing', 'pms_woo_handle_member_subscription');
add_action('woocommerce_order_status_completed',  'pms_woo_handle_member_subscription');
add_action('woocommerce_order_status_refunded',   'pms_woo_handle_member_subscription');
add_action('woocommerce_order_status_cancelled',  'pms_woo_handle_member_subscription');


// When PMS Subscription is Canceled from PMS Account also Cancel linked WooCommerce Subscription
/**
 * @throws Exception
 */
function pms_woo_cancel_woocommerce_subscription($member_data, $member_subscription ) {
    $order_key = pms_get_member_subscription_meta( $member_subscription->id, 'woo_order_key', true );
    $order_id = wc_get_order_id_by_order_key( $order_key );
    $order = new WC_Order( $order_id );

    if ( function_exists('wcs_order_contains_subscription') && wcs_order_contains_subscription( $order, array( 'parent', 'renewal' ) ) ) {

        $subscriptions = wcs_get_subscriptions_for_order( $order_id, array( 'order_type' => array( 'parent', 'renewal' ) ) );

        foreach ( $subscriptions as $subscription ) {

            $latest_order = $subscription->get_last_order();

            if ( $order_id == $latest_order && $subscription->can_be_updated_to( 'pending-cancel' ) ) {

                $subscription->update_status('pending-cancel', esc_html__('Subscription canceled from PMS Account.', 'paid-member-subscriptions'));
            }

        }

    }

}
add_action('pms_cancel_member_subscription_successful', 'pms_woo_cancel_woocommerce_subscription', 10, 2);
<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Add extra field properties for the Subscription Plans PB field
 *
 * @param array $manage_fields
 *
 * @return array
 *
 */
function pms_pb_manage_fields( $manage_fields ) {

    // add Subscription Plans PB field properties
    $manage_fields = pms_pb_add_subscription_plans_props( $manage_fields );

    // add PMS Billing Fields PB field properties
    $manage_fields = pms_pb_add_billing_fields_props( $manage_fields );

    return $manage_fields;

}
add_filter( 'wppb_manage_fields', 'pms_pb_manage_fields' );

/**
 * Function that adds extra field properties for the Subscription Plans PB field
 *
 * @param $manage_fields
 * @return mixed
 */
function pms_pb_add_subscription_plans_props( $manage_fields ) {

    // Get all subscription plans
    $subscription_plans = array();
    $subscription_plans[] = '%'. __( 'All', 'paid-member-subscriptions' ) .'%all';

    foreach( pms_get_subscription_plans() as $subscription_plan )
        $subscription_plans[] = '%' . $subscription_plan->name . '%' . $subscription_plan->id;

    // Prepare subscription plans for default select
    $subscription_plans_select = array( '%' . __( 'Choose...', 'paid-member-subscriptions' ) . '%-1' );
    $subscription_plans_select = array_merge( $subscription_plans_select, $subscription_plans );

    // Append field properties
    if ( empty($subscription_plans) )/* translators: %s: url */
        $manage_fields[] = array( 'type' => 'checkbox', 'slug' => 'subscription-plans', 'title' => __( 'Subscription Plans on Register Form', 'paid-member-subscriptions' ), 'options' => $subscription_plans, 'description' => sprintf( __( 'It looks like there are no active subscriptions. <a href="%s">Create one here</a>.', 'paid-member-subscriptions' ), 'edit.php?post_type=pms-subscription' ) );
    else
        $manage_fields[] = array( 'type' => 'checkbox', 'slug' => 'subscription-plans', 'title' => __( 'Subscription Plans on Register Form', 'paid-member-subscriptions' ), 'options' => $subscription_plans, 'description' => __( "Select which Subscription Plans to show to the user on the register forms ( drag and drop to re-order )", 'paid-member-subscriptions' ) );

    $manage_fields[] = array( 'type' => 'text', 'slug' => 'subscription-plans-sort-order', 'title' => __( 'Subscription Plans Order', 'paid-member-subscriptions' ), 'description' => __( "Save the subscription plan order from the subscription plans checkboxes", 'paid-member-subscriptions' ) );

    if( count( $subscription_plans_select ) > 1 ){
        unset( $subscription_plans_select[1] ); // remove the All option
        $manage_fields[] = array( 'type' => 'select', 'slug' => 'subscription-plan-selected', 'title' => __( 'Selected Subscription Plan', 'paid-member-subscriptions' ), 'options' => $subscription_plans_select, 'description' => __( "Select which plan will be by default selected when the front-end form loads.", 'paid-member-subscriptions' ) );
    }

    return $manage_fields;
}


/**
 * Function that adds extra field properties for the PMS Billing Fields PB field
 * - fields from Tax and/or Invoice Add-ons
 *
 * @param $manage_fields
 * @return mixed
 */
function pms_pb_add_billing_fields_props( $manage_fields ) {
    $billing_fields = pms_pb_get_billing_fields();

    $fields = array();
    foreach ( $billing_fields as $field ) {
        $fields[] = '%' . $field['label'] . '%' . $field['name'];
    }

    $manage_fields[] = array(
        'type'        => 'checkbox',
        'slug'        => 'pms-billing-fields',
        'title'       => 'Billing Fields',
        'options'     => $fields,
        'description' => ! empty( $fields )
            ? sprintf(
                esc_html__( 'Select the Billing Fields you want to display on the Edit Profile forms. %s If no fields are selected, all available Billing Fields will be displayed.', 'paid-member-subscriptions' ),
                '<br>'
            )
            : sprintf(
                esc_html__( '%1$sBilling Fields are not available!%2$s %5$s Activate the %1$sTax%2$s and/or %1$sInvoice%2$s add-ons from the %3$sPaid Member Subscriptions Add-ons%4$s section.', 'paid-member-subscriptions' ),
                '<strong>',
                '</strong>',
                '<a href="' . admin_url( 'admin.php?page=pms-addons-page' ) . '">',
                '</a>',
                '<br>'
            ),
    );

    return $manage_fields;
}


/**
 * Include necessary scripts for Profile Builder compatibility
 *
 */
function pms_pb_enqueue_scripts( $hook ) {

    wp_enqueue_script( 'pms-pb-main-js', PMS_PLUGIN_DIR_URL . 'extend/profile-builder/assets/js/main.js', array( 'jquery' ) );

    if( $hook == 'user-edit.php' ){
        wp_enqueue_script( 'pms-wp-edit-user-script', PMS_PLUGIN_DIR_URL . 'assets/js/admin/submenu-page-members-page.js', array('jquery'), PMS_VERSION );
        wp_localize_script( 'pms-wp-edit-user-script', 'PMS_States', pms_get_billing_states() );
    }

}
add_action( 'admin_enqueue_scripts', 'pms_pb_enqueue_scripts', 9 );


/**
 * Function that ads the Subscription Plans field to the fields list
 * and also the list of fields that skip the meta-name check
 *
 * @param array $fields     - The names of all the fields
 *
 * @return array
 *
 */
function pms_pb_manage_field_types( $fields ) {
    $fields[] = 'Subscription Plans';
    $fields[] = 'PMS Billing Fields';

    return $fields;
}
add_filter( 'wppb_manage_fields_types', 'pms_pb_manage_field_types' );
add_filter( 'wppb_skip_check_for_fields', 'pms_pb_manage_field_types' );


/**
 * Function that calls the pms_pb_handle_subscription_plans_field
 *
 * @since v.2.0
 *
 * @param void
 *
 * @return string
 */
function pms_pb_subscription_plans_sortable( $meta_name, $id, $element_id ){
    if ( $meta_name == 'wppb_manage_fields' ) {
        echo "<script type=\"text/javascript\">pms_pb_handle_sorting_subscription_plans_field( '#container_wppb_manage_fields' );</script>";
    }

}
add_action("wck_after_adding_form", "pms_pb_subscription_plans_sortable", 10, 3);

/**
 * Add a notification for either the Username or the Email field letting the user know that, even though it is there, it won't do anything
 *
 * @since v.2.0
 *
 * @param string $form
 * @param integer $id
 * @param string $value
 *
 * @return string $form
 */

 function pms_pb_manage_fields_display_field_title_slug( $form ){
    // add a notice to fields
	global $wppb_results_field;
    switch ($wppb_results_field){
        case 'PMS Billing Fields':
            $form .= '<div id="wppb-pms-billing-fields-nag" class="wppb-backend-notice">' . __( 'PMS Billing Fields - only appears on the Edit Profile page.', 'paid-member-subscriptions' ) . '</div>';
            break;
    }

    return $form;
}

add_filter( 'wck_after_content_element', 'pms_pb_manage_fields_display_field_title_slug' );
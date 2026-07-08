<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Eventually this should hold all the functionality relating to the Billing Details that we display in a form
 * Right now it's used for displaying billing details for admins on the back-end edit member and payment pages
 */
Class PMS_Billing_Details {

    public function init(){

        add_action( 'pms_member_edit_form_field', array( $this, 'admin_display_billing_details' ) );

        add_action( 'pms_submenu_page_enqueue_admin_scripts_pms-members-page', array( $this, 'localize_billing_details' ) );
        add_action( 'pms_submenu_page_enqueue_admin_scripts_pms-payments-page', array( $this, 'localize_payment_billing_details' ), 20 );

        add_action( 'pms_payment_edit_form_field', array( $this, 'admin_display_payment_billing_details' ), 20, 2 );
        add_action( 'pms_payment_add_new_form_field', array( $this, 'admin_display_payment_billing_details' ), 20 );

        add_action( 'wp_ajax_pms_edit_member_billing_details', array( $this, 'edit_member_billing_details' ) );
        add_action( 'wp_ajax_pms_get_user_billing_details', array( $this, 'get_user_billing_details' ) );

        add_action( 'pms_manually_edited_payment_success', array( $this, 'save_payment_billing_details' ), 10, 1 );
        add_action( 'pms_manually_added_payment_success', array( $this, 'save_payment_billing_details' ), 10, 1 );

    }

    public function admin_display_billing_details(){

        if( !isset( $_GET['member_id'] ) )
            return;

        $member_id = absint( $_GET['member_id'] );

        $fields_data = $this->get_billing_fields_data( $member_id );

        if( empty( $fields_data ) || !( count( $fields_data ) > 1 ) )
            return;

        $billing_fields = $this->get_billing_fields( $member_id );

        ?>

        <div class="cozmoslabs-form-subsection-wrapper">

            <h3 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Billing Details', 'paid-member-subscriptions' ); ?></h3>

            <div id="pms-member-billing-details">

                    <div class="billing-details">
                        <div class="billing-details__data">
                            <?php $this->format_billing_details( $fields_data ); ?>
                        </div>

                        <div class="billing-details__action">
                            <a href="" id="edit" class="button button-secondary"><?php esc_html_e( 'Edit', 'paid-member-subscriptions' ); ?></a>
                            <span><?php esc_html_e( 'Member details saved successfully !', 'paid-member-subscriptions' ); ?><span>
                        </div>
                    </div>

                    <div class="form">
                        <?php foreach( $billing_fields as $slug => $field ) : ?>
                            <?php pms_output_form_field( $field ); ?>
                        <?php endforeach; ?>

                        <input type="hidden" name="pms_member_id" value="<?php echo  isset( $_GET['member_id'] ) ? esc_attr( sanitize_text_field( $_GET['member_id'] ) ) : ''; ?>" />

                        <a href="" id="save" class="button button-primary"><?php esc_html_e( 'Save', 'paid-member-subscriptions' ); ?></a>

                    </div>
            </div>
        </div>

        <?php
    }

    /**
     * Billing Details fields
     * @return [type] [description]
     */
    public function get_billing_fields( $user_id = null ){

        if( $user_id == null && is_user_logged_in() )
            $user_id = pms_get_current_user_id();

        if( ! empty( $user_id ) ) {
            $user      = get_userdata( $user_id );
            $user_meta = get_user_meta( $user_id );
        }

        $fields = array(
            'pms_billing_details_heading' => array(
                'section'         => 'billing_details',
                'type'            => 'heading',
                'default'         => '<h3>' . __( 'Billing Details', 'paid-member-subscriptions' ) . '</h3>',
                'element_wrapper' => 'li',
            ),
            'pms_billing_first_name' => array(
                'section'         => 'billing_details',
                'type'            => 'text',
                'name'            => 'pms_billing_first_name',
                'default'         => '',
                'value'           => ( isset( $_POST['pms_billing_first_name'] ) ? sanitize_text_field( $_POST['pms_billing_first_name'] ) : ( !(empty($user_meta['pms_billing_first_name'])) ? $user_meta['pms_billing_first_name'][0] : ( ! empty( $user->first_name ) ? $user->first_name : '' ) ) ),
                'label'           => __( 'Billing First Name', 'paid-member-subscriptions' ),
                'description'     => '',
                'element_wrapper' => 'li',
                'required'        => 1,
                'wrapper_class'   => 'pms-billing-first-name',
            ),
            'pms_billing_last_name' => array(
                'section'         => 'billing_details',
                'type'            => 'text',
                'name'            => 'pms_billing_last_name',
                'default'         => '',
                'value'           => ( isset( $_POST['pms_billing_last_name'] ) ? sanitize_text_field( $_POST['pms_billing_last_name'] ) : ( !(empty($user_meta['pms_billing_last_name'])) ? $user_meta['pms_billing_last_name'][0] : ( ! empty( $user->last_name ) ? $user->last_name : '' ) ) ),
                'label'           => __( 'Billing Last Name', 'paid-member-subscriptions' ),
                'description'     => '',
                'element_wrapper' => 'li',
                'required'        => 1,
                'wrapper_class'   => 'pms-billing-last-name',
            ),
            'pms_billing_email' => array(
                'section'         => 'billing_details',
                'type'            => 'text',
                'name'            => 'pms_billing_email',
                'default'         => '',
                'value'           => ( isset( $_POST['pms_billing_email'] ) ? sanitize_email( $_POST['pms_billing_email'] ) : ( !(empty($user_meta['pms_billing_email'])) ? $user_meta['pms_billing_email'][0] : ( ! empty( $user->user_email ) ? $user->user_email : '' ) ) ),
                'label'           => __( 'Billing Email', 'paid-member-subscriptions' ),
                'description'     => '',
                'element_wrapper' => 'li',
                'required'        => 1,
                'wrapper_class'   => 'pms-billing-email',
            ),
            'pms_billing_company' => array(
                'section'         => 'billing_details',
                'type'            => 'text',
                'name'            => 'pms_billing_company',
                'default'         => '',
                'value'           => ( isset( $_POST['pms_billing_company'] ) ? sanitize_text_field( $_POST['pms_billing_company'] ) : ( !(empty($user_meta['pms_billing_company'])) ? $user_meta['pms_billing_company'][0] : '') ),
                'label'           => __( 'Billing Company', 'paid-member-subscriptions' ),
                'description'     => __( 'If entered, this will appear on the invoice, replacing the First and Last Name.', 'paid-member-subscriptions' ),
                'element_wrapper' => 'li',
                'wrapper_class'   => 'pms-billing-company',
            ),
            'pms_billing_address' => array(
                'section'         => 'billing_details',
                'type'            => 'text',
                'name'            => 'pms_billing_address',
                'default'         => '',
                'value'           => ( isset( $_POST['pms_billing_address'] ) ? sanitize_text_field( $_POST['pms_billing_address'] ) : ( !(empty($user_meta['pms_billing_address'])) ? $user_meta['pms_billing_address'][0] : '') ),
                'label'           => __( 'Billing Address', 'paid-member-subscriptions' ),
                'description'     => '',
                'element_wrapper' => 'li',
                'required'        => 1,
                'wrapper_class'   => 'pms-billing-address',
            ),
            'pms_billing_city' => array(
                'section'         => 'billing_details',
                'type'            => 'text',
                'name'            => 'pms_billing_city',
                'default'         => '',
                'value'           => ( isset( $_POST['pms_billing_city'] ) ? sanitize_text_field( $_POST['pms_billing_city'] ) : ( !(empty($user_meta['pms_billing_city'])) ? $user_meta['pms_billing_city'][0] : '') ),
                'label'           => __( 'Billing City', 'paid-member-subscriptions' ),
                'description'     => '',
                'element_wrapper' => 'li',
                'required'        => 1,
                'wrapper_class'   => 'pms-billing-city',
            ),
            'pms_billing_zip' => array(
                'section'         => 'billing_details',
                'type'            => 'text',
                'name'            => 'pms_billing_zip',
                'default'         => '',
                'value'           => ( isset( $_POST['pms_billing_zip']) ? sanitize_text_field( $_POST['pms_billing_zip'] ) : ( !(empty($user_meta['pms_billing_zip'])) ? $user_meta['pms_billing_zip'][0] : '') ),
                'label'           => __( 'Billing Zip / Postal Code', 'paid-member-subscriptions' ),
                'description'     => '',
                'element_wrapper' => 'li',
                'wrapper_class'   => 'pms-billing-zip',
            ),
            'pms_billing_country' => array(
                'section'         => 'billing_details',
                'type'            => 'select',
                'name'            => 'pms_billing_country',
                'default'         => '',
                'value'           => ( isset( $_POST['pms_billing_country'] ) ? sanitize_text_field( $_POST['pms_billing_country'] ) : ( !(empty($user_meta['pms_billing_country'])) ? $user_meta['pms_billing_country'][0] : '') ),
                'label'           => __( 'Billing Country', 'paid-member-subscriptions' ),
                'options'         => function_exists( 'pms_get_countries' ) ? pms_get_countries() : array(),
                'description'     => '',
                'element_wrapper' => 'li',
                'required'        => 1 ,
                'wrapper_class'   => 'pms-billing-country',
            ),
            'pms_billing_state' => array(
                'section'         => 'billing_details',
                'type'            => 'select_state',
                'name'            => 'pms_billing_state',
                'default'         => '',
                'value'           => ( isset( $_POST['pms_billing_state'] ) ? sanitize_text_field( $_POST['pms_billing_state'] ) : ( !(empty($user_meta['pms_billing_state'])) ? $user_meta['pms_billing_state'][0] : '') ),
                'label'           => __( 'Billing State / Province', 'paid-member-subscriptions' ),
                'description'     => '',
                'element_wrapper' => 'li',
                'wrapper_class'   => 'pms-billing-state',
            )
        );

        $fields = apply_filters( 'pms_billing_details_fields', $fields, $user_id );

        if( is_admin() )
            $fields = array_map( array( $this, 'prepare_fields_for_admin' ), $fields );

        return $fields;

    }

    public function get_billing_fields_data( $user_id ){

        if( empty( $user_id ) )
            return false;

        $fields = $this->get_billing_fields( $user_id );

        $data = array();

        foreach( $fields as $key => $value ){
            if( !empty( $value['value'] ) )
                $data[$key] = $value['value'];
        }

        return $data;

    }

    public function prepare_fields_for_admin( $field ){

        if( isset( $field['type'] ) && $field['type'] == 'heading' )
            return array();

        unset( $field['description'] );
        unset( $field['required'] );

        $field['element_wrapper'] = 'div';
        $field['wrapper_class'] .= ' pms-meta-box-field-wrapper';

        return $field;

    }

    public function admin_display_payment_billing_details( $payment = null ){

        $payment_id = ! empty( $payment->id ) ? $payment->id : 0;
        $user_id    = ! empty( $payment->user_id ) ? $payment->user_id : ( ! empty( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0 );
        $fields     = $this->get_payment_billing_fields( $payment_id, $user_id );

        if( empty( $fields ) )
            return;

        $uses_user_fallback = false;

        foreach( $fields as $field ) {
            if( ! empty( $field['pms_uses_user_fallback'] ) ) {
                $uses_user_fallback = true;
                break;
            }
        }

        ?>
        <details id="pms-payment-billing-details" class="pms-payment-billing-details">
            <summary class="pms-payment-billing-details__toggle">
                <span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
                <?php esc_html_e( 'Billing Details', 'paid-member-subscriptions' ); ?>
            </summary>

            <div id="pms-payment-billing-details-fields" class="pms-payment-billing-details__fields">
                <input type="hidden" name="pms_admin_payment_billing_details" value="1" />

                <?php foreach( $fields as $field ) : ?>
                    <?php pms_output_form_field( $field ); ?>
                    <?php do_action( 'pms_admin_payment_billing_field_after', $field, $payment ); ?>
                <?php endforeach; ?>

                <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                    <label class="cozmoslabs-form-field-label" for="pms-sync-payment-billing-user-meta"><?php esc_html_e( 'Sync with user meta fields', 'paid-member-subscriptions' ); ?></label>

                    <div class="cozmoslabs-toggle-container">
                        <input type="checkbox" id="pms-sync-payment-billing-user-meta" name="pms_sync_payment_billing_user_meta" value="1" />
                        <label class="cozmoslabs-toggle-track" for="pms-sync-payment-billing-user-meta"></label>
                    </div>
                </div>

                <p class="cozmoslabs-description pms-payment-billing-details__fallback-note" <?php echo $uses_user_fallback ? '' : 'hidden'; ?>>
                    <?php esc_html_e( 'Empty payment billing fields were pre-filled with billing details from the user. Saving changes updates only this payment unless Sync with user meta fields is selected.', 'paid-member-subscriptions' ); ?>
                </p>
            </div>
        </details>
        <?php

    }

    public function get_payment_billing_fields( $payment_id = 0, $user_id = 0 ){

        if( ! pms_billing_fields_active() )
            return array();

        $fields = apply_filters( 'pms_admin_payment_billing_fields', array(), $payment_id, $user_id );
        $fields = array_filter( $fields, function( $field ){
            return ! empty( $field['name'] );
        } );

        foreach( $fields as $key => $field ) {
            $name          = $field['name'];
            $payment_value = $payment_id ? pms_get_payment_meta( $payment_id, $name, true ) : '';
            $user_value    = $user_id ? get_user_meta( $user_id, $name, true ) : '';

            if( isset( $_POST['pms_admin_payment_billing_details'], $_POST[$name] ) )
                $value = sanitize_text_field( wp_unslash( $_POST[$name] ) );
            else
                $value = $payment_value !== '' ? $payment_value : $user_value;

            $fields[$key]['value']                  = $value;
            $fields[$key]['default']                = '';
            $fields[$key]['element_wrapper']        = 'div';
            $fields[$key]['required']               = 0;
            $fields[$key]['wrapper_class']          = ! empty( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
            $fields[$key]['pms_uses_user_fallback'] = $payment_value === '' && $user_value !== '';
        }

        return $fields;

    }

    public function save_payment_billing_details( $payment ){

        if( empty( $_POST['pms_admin_payment_billing_details'] ) || empty( $payment->id ) )
            return;

        $fields    = $this->get_payment_billing_fields( $payment->id, $payment->user_id );
        $sync_user = ! empty( $_POST['pms_sync_payment_billing_user_meta'] );

        $changed_old = array();
        $changed_new = array();

        foreach( $fields as $field ) {
            $name  = $field['name'];
            $label = ! empty( $field['label'] ) ? $field['label'] : $name;
            $value = isset( $_POST[$name] ) ? sanitize_text_field( wp_unslash( $_POST[$name] ) ) : '';
            $old   = pms_get_payment_meta( $payment->id, $name, true );

            if( (string) $old !== (string) $value ) {
                $changed_old[ $label ] = $old;
                $changed_new[ $label ] = $value;
            }

            if( $value === '' )
                pms_delete_payment_meta( $payment->id, $name );
            else
                pms_update_payment_meta( $payment->id, $name, $value );

            if( $sync_user ) {
                if( $value === '' )
                    delete_user_meta( $payment->user_id, $name );
                else
                    update_user_meta( $payment->user_id, $name, $value );
            }
        }

        if( ! empty( $changed_new ) )
            $payment->log_data( 'billing_details_updated', array(
                'user'     => get_current_user_id(),
                'new_data' => $changed_new,
                'old_data' => $changed_old,
            ) );

        if( $sync_user && ! empty( $changed_new ) )
            $payment->log_data( 'billing_details_synced', array( 'user' => get_current_user_id() ) );

    }

    public function format_billing_details( $data, $return = false ){
        $billing_details = '';

        // First Name
        $billing_details .= '<div class="cozmoslabs-form-field-wrapper">';
        $billing_details .= '<label class="cozmoslabs-form-field-label">'. esc_html__( 'Billing First Name' , 'paid-member-subscriptions' ) .'</label>';
        $billing_details .= ( ! empty( $data['pms_billing_first_name'] ) ? '<span id="pms_billing_first_name">' . $data['pms_billing_first_name'] . '</span>' : '' ) . ' ';
        $billing_details .= '</div>';

        // Last Name
        $billing_details .= '<div class="cozmoslabs-form-field-wrapper">';
        $billing_details .= '<label class="cozmoslabs-form-field-label">'. esc_html__( 'Billing Last Name' , 'paid-member-subscriptions' ) .'</label>';
        $billing_details .= ( ! empty( $data['pms_billing_last_name'] ) ? '<span id="pms_billing_last_name">' . $data['pms_billing_last_name'] . '</span>' : '' );
        $billing_details .= '</div>';

        // Email
        if( ! empty( $data['pms_billing_email'] ) ) {
            $billing_details .= '<div class="cozmoslabs-form-field-wrapper">';
            $billing_details .= '<label class="cozmoslabs-form-field-label">'. esc_html__( 'Billing Email' , 'paid-member-subscriptions' ) .'</label>';
            $billing_details .= '<span id="pms_billing_email">' . $data['pms_billing_email'] . '</span>';
            $billing_details .= '</div>';
        }

        // Company
        if( ! empty( $data['pms_billing_company'] ) ) {
            $billing_details .= '<div class="cozmoslabs-form-field-wrapper">';
            $billing_details .= '<label class="cozmoslabs-form-field-label">'. esc_html__( 'Billing Company' , 'paid-member-subscriptions' ) .'</label>';
            $billing_details .= '<span id="pms_billing_company">' . $data['pms_billing_company'] . '</span>';
            $billing_details .= '</div>';
        }


        // Address
        if( ! empty( $data['pms_billing_address'] ) ) {
            $billing_details .= '<div class="cozmoslabs-form-field-wrapper">';
            $billing_details .= '<label class="cozmoslabs-form-field-label">'. esc_html__( 'Billing Address' , 'paid-member-subscriptions' ) .'</label>';
            $billing_details .= '<span id="pms_billing_address">' . $data['pms_billing_address'] . '</span>';
            $billing_details .= '</div>';
        }

        // City
        if( ! empty( $data['pms_billing_city'] ) ) {
            $billing_details .= '<div class="cozmoslabs-form-field-wrapper">';
            $billing_details .= '<label class="cozmoslabs-form-field-label">'. esc_html__( 'Billing City' , 'paid-member-subscriptions' ) .'</label>';
            $billing_details .= '<span id="pms_billing_city">' . $data['pms_billing_city'] . '</span>';
            $billing_details .= '</div>';
        }

        // Zip code
        if( ! empty( $data['pms_billing_zip'] ) ) {
            $billing_details .= '<div class="cozmoslabs-form-field-wrapper">';
            $billing_details .= '<label class="cozmoslabs-form-field-label">'. esc_html__( 'Billing Zip / Postal Code' , 'paid-member-subscriptions' ) .'</label>';
            $billing_details .= '<span id="pms_billing_zip">' . $data['pms_billing_zip'] . '</span>';
            $billing_details .= '</div>';
        }

        // Billing country
        if( ! empty( $data['pms_billing_country'] ) ) {

            $countries = pms_get_countries();

            if( ! empty( $countries[$data['pms_billing_country']] ) ) {
                $billing_details .= '<div class="cozmoslabs-form-field-wrapper">';
                $billing_details .= '<label class="cozmoslabs-form-field-label">'. esc_html__( 'Billing Country' , 'paid-member-subscriptions' ) .'</label>';
                $billing_details .= '<span id="pms_billing_country">' . $countries[$data['pms_billing_country']]  . '</span>';
                $billing_details .= '</div>';
            }

        }

        // Billing State
        if( ! empty( $data['pms_billing_country'] ) && ! empty( $data['pms_billing_state'] ) ) {

            $states = pms_get_billing_states();

            if( ! empty( $states[$data['pms_billing_country']][$data['pms_billing_state']] ) ) {
                $billing_details .= '<div class="cozmoslabs-form-field-wrapper">';
                $billing_details .= '<label class="cozmoslabs-form-field-label">'. esc_html__( 'Billing State / Province' , 'paid-member-subscriptions' ) .'</label>';
                $billing_details .= '<span id="pms_billing_state">' . $states[$data['pms_billing_country']][$data['pms_billing_state']]  . '</span>';
                $billing_details .= '</div>';
            }

        }

        if( $return === true )
            return wp_kses_post( $billing_details );
        else
            echo wp_kses_post( $billing_details );

    }

    public function localize_billing_details( $menu_slug ){

        wp_localize_script( $menu_slug . '-js', 'pms_billing_details', array(
            'fields'                    => array_keys( $this->get_billing_fields() ),
            'edit_member_details_nonce' => wp_create_nonce( 'pms_edit_member_details_nonce' )
        ));

        wp_localize_script( $menu_slug . '-js', 'PMS_States', pms_get_billing_states() );

    }

    public function localize_payment_billing_details( $menu_slug ){

        $data = array(
            'nonce' => wp_create_nonce( 'pms_get_user_billing_details' ),
        );

        $data = apply_filters( 'pms_admin_payment_billing_details_js_data', $data );

        wp_localize_script( 'pms-payments-bulk-actions-script', 'pms_payment_billing_details', $data );

        wp_localize_script( 'pms-payments-bulk-actions-script', 'PMS_States', pms_get_billing_states() );

    }

    public function edit_member_billing_details(){

        check_ajax_referer( 'pms_edit_member_details_nonce', 'security' );

        if( !current_user_can( 'manage_options' ) || empty( $_POST['member_id'] ) )
            die( json_encode( array( 'status' => 'error' ) ) );

        $user_id         = absint( $_POST['member_id'] );
        $billing_details = $this->get_billing_fields();

        foreach( $billing_details as $slug => $label ){

            if( isset( $_POST[$slug] ) )
                update_user_meta( $user_id, $slug, sanitize_text_field( $_POST[$slug] ) );

        }

        die( json_encode( array( 'status' => 'success', 'address_output' => $this->format_billing_details( array_map( 'sanitize_text_field', $_POST ), true ) ) ) );

    }

    public function get_user_billing_details(){

        check_ajax_referer( 'pms_get_user_billing_details', 'security' );

        if( ! pms_current_user_can_access_area( 'pms-payments-page' ) || empty( $_POST['user_id'] ) )
            wp_send_json_error();

        $fields = $this->get_payment_billing_fields( 0, absint( $_POST['user_id'] ) );
        $values = array();

        foreach( $fields as $field )
            $values[$field['name']] = $field['value'];

        wp_send_json_success( $values );

    }

}

$pms_billing_details = new PMS_Billing_Details();
$pms_billing_details->init();

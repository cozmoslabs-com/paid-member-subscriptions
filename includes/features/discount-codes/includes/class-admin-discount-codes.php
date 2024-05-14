<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;


if ( class_exists('PMS_Custom_Post_Type') ) {

    class PMS_IN_Custom_Post_Type_Discount_Codes extends PMS_Custom_Post_Type {
        /*
         * Method to add the needed hooks
         *
         */
        public function init() {
            add_action( 'init', array( $this, 'process_data' ) );
            add_action( 'init', array( $this, 'register_custom_discount_code_statuses' ) );

            add_filter( 'manage_' . $this->post_type . '_posts_columns', array(__CLASS__, 'manage_posts_columns'));
            add_action( 'manage_' . $this->post_type . '_posts_custom_column', array( __CLASS__, 'manage_posts_custom_column' ), 10, 2 );

            add_filter('page_row_actions', array($this, 'remove_post_row_actions'), 10, 2);
            add_action('page_row_actions', array($this, 'add_post_row_actions'), 11, 2);

            // Remove "Move to Trash" bulk action
            add_filter('bulk_actions-edit-' . $this->post_type, array($this, 'remove_bulk_actions'));

            // Add a delete button where the move to trash was
            add_action('post_submitbox_start', array($this, 'submitbox_add_delete_button'));

            // Change the default "Enter title here" text
            add_filter('enter_title_here', array($this, 'change_discount_title_prompt_text'));

            // Set custom updated messages
            add_filter('post_updated_messages', array($this, 'set_custom_messages'));

            // Set custom bulk updated messages
            add_filter('bulk_post_updated_messages', array($this, 'set_bulk_custom_messages'), 10, 2);

            // Add `Bulk Create Discount Codes` button
            add_filter( 'admin_footer', array( $this, 'add_bulk_create_discount_codes_button') );

        }

        /*
        * Method that validates data for the discount code cpt
        *
        */
        public function process_data() {

            // Verify nonce before anything
            if( !isset( $_REQUEST['_wpnonce'] ) || !wp_verify_nonce( sanitize_text_field( $_REQUEST['_wpnonce'] ), 'pms_discount_code_nonce' ) )
                return;

            // Activate discount code
            if( isset( $_REQUEST['pms-action'] ) && $_REQUEST['pms-action'] == 'activate_discount_code' && isset( $_REQUEST['post_id'] ) ) {
                PMS_IN_Discount_Code::activate( absint( $_REQUEST['post_id'] ) );
            }

            // Deactivate discount code
            if( isset( $_REQUEST['pms-action'] ) && $_REQUEST['pms-action'] == 'deactivate_discount_code' && isset( $_REQUEST['post_id'] ) ) {
                PMS_IN_Discount_Code::deactivate( absint( $_REQUEST['post_id'] ) );
            }

            // Duplicate discount code
            if( isset( $_REQUEST['pms-action'] ) && $_REQUEST['pms-action'] == 'duplicate_discount_code' && isset( $_REQUEST['post_id'] ) ) {
                PMS_IN_Discount_Code::duplicate( absint( $_REQUEST['post_id'] ) );
            }

            // Delete discount code
            if( isset( $_REQUEST['pms-action'] ) && $_REQUEST['pms-action'] == 'delete_discount_code' && isset( $_REQUEST['post_id'] ) ) {
                PMS_IN_Discount_Code::remove( absint( $_REQUEST['post_id'] ) );
            }

        }

        /**
         * Method for registering custom discount code statuses (active, inactive)
         *
         */
        public function register_custom_discount_code_statuses() {

            // Register custom Discount Code Statuses
            register_post_status( 'active', array(
                'label'                     => _x( 'Active', 'Active status for discount code', 'paid-member-subscriptions' ),
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop( 'Active <span class="count">(%s)</span>', 'Active <span class="count">(%s)</span>', 'paid-member-subscriptions' )
            )  );
            register_post_status( 'inactive', array(
                'label'                     => _x( 'Inactive', 'Inactive status for discount code', 'paid-member-subscriptions' ),
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop( 'Inactive <span class="count">(%s)</span>', 'Inactive <span class="count">(%s)</span>', 'paid-member-subscriptions' )
            )  );
            register_post_status( 'expired', array(
                'label'                     => _x( 'Expired', 'Expired status for discount code', 'paid-member-subscriptions' ),
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop( 'Expired <span class="count">(%s)</span>', 'Expired <span class="count">(%s)</span>', 'paid-member-subscriptions' )
            )  );

        }

        /*
         * Method to add the needed columns in Discount Codes listing.
         *
         */
        public static function manage_posts_columns($columns) {

            // Add new columns for the discount codes
            $new_columns = array_merge($columns, array(
                'code'            => __('Code', 'paid-member-subscriptions'),
                'amount'          => __('Amount', 'paid-member-subscriptions'),
                'uses'            => __('Uses', 'paid-member-subscriptions'),
                'start-date'      => __('Start Date', 'paid-member-subscriptions'),
                'expiration-date' => __('Expiration Date', 'paid-member-subscriptions'),
                'status'          => __('Status', 'paid-member-subscriptions')
            ));

            unset($new_columns['date']);

            return $new_columns;
        }

        /*
         * Method for removing the unnecessary row actions (e.g Quick edit, Trash).
         *
         */
        public function remove_post_row_actions($actions, $post) {

            if ($post->post_type != $this->post_type)
                return $actions;

            if (empty($actions))
                return $actions;

            foreach ($actions as $key => $action) {
                if ($key != 'edit') {
                    unset($actions[$key]);
                }
            }

            return $actions;
        }

        /*
         * Method for adding new row actions (e.g Activate/Deactivate , Delete).
         *
         */
        public function add_post_row_actions($actions, $post) {

            if ($post->post_type != $this->post_type)
                return $actions;

            if (empty($actions))
                return $actions;


            /*
            * Add the option to activate and deactivate a discount code
            */
            $discount_code = new PMS_IN_Discount_Code( $post );

            if( $discount_code->is_active() )
                $activate_deactivate = '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'deactivate_discount_code', 'post_id' => $post->ID ) ), 'pms_discount_code_nonce' ) ) . '">' . esc_html__( 'Deactivate', 'paid-member-subscriptions' ) . '</a>';
            else
                $activate_deactivate = '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'activate_discount_code', 'post_id' => $post->ID ) ), 'pms_discount_code_nonce' ) ) . '">' . esc_html__( 'Activate', 'paid-member-subscriptions' ) . '</a>';

            $actions['change_status'] = $activate_deactivate;

            /*
             * Add the option to duplicate a subscription plan
             */
            $duplicate = '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'duplicate_discount_code', 'post_id' => $post->ID ) ), 'pms_discount_code_nonce' ) ) . '">' . esc_html__( 'Duplicate', 'paid-member-subscriptions' ) . '</a>';

            $actions['duplicate'] = $duplicate;

            /*
             * Add the option to delete a discount code
             */
            $delete = '<span class="trash"><a onclick="return confirm( \'' . esc_html__("Are you sure you want to delete this Discount Code?", "paid-member-subscriptions") . ' \' )" href="' . esc_url(wp_nonce_url(add_query_arg(array('pms-action' => 'delete_discount_code', 'post_id' => $post->ID, 'deleted' => 1)), 'pms_discount_code_nonce')) . '">' . esc_html__('Delete', 'paid-member-subscriptions') . '</a></span>';

            $actions['delete'] = $delete;


            // Return actions
            return $actions;

        }

        /*
         * Method to display values for each Discount Code column
         *
        */
        public static function manage_posts_custom_column( $column, $post_id ) {

            $discount_code = new PMS_IN_Discount_Code( $post_id );

            // Information shown in discount "Code" column
            if ($column == 'code')
                echo '<input type="text" readonly class="pms-discount-code input" value="'.esc_attr( $discount_code->code ) .'">';

            // Information shown in discount "Amount" column
            if ($column == 'amount') {

                $currency_symbol = pms_get_currency_symbol( pms_get_active_currency() );

                if ( $discount_code->type == 'percent' )
                    echo esc_html( $discount_code->amount ) . '%';
                else
                    echo esc_html( $currency_symbol ) . esc_html( $discount_code->amount );
            }

            // Information shown in discount "Uses" column
            if ( $column == 'uses' ){

                echo '<a href="'. esc_url( admin_url( 'admin.php?page=pms-payments-page&s=' . $discount_code->code ) ) . '" title="' . esc_attr( __( 'View payments with this discount code', 'paid-member-subscriptions' ) ) . '">';
                    echo esc_html( $discount_code->uses ) . '/' . ( ! empty( $discount_code->max_uses ) ? esc_html( $discount_code->max_uses ) : '&infin;' );
                echo '</a>';
            }

            // Information shown in discount "Start date" column
            if ($column == 'start-date') {
                if ( !empty( $discount_code->start_date ) )
                    echo esc_html( $discount_code->start_date );
                else
                    echo esc_html__( 'No start date', 'paid-member-subscriptions' );
            }

            // Information shown in discount "Start date" column
            if ($column == 'expiration-date') {
                if ( !empty($discount_code->expiration_date) )
                    echo esc_html( $discount_code->expiration_date );
                else
                    esc_html_e( 'No expiration date', 'paid-member-subscriptions' );
            }

            // Information shown in the status column
            if( $column == 'status' ) {

                $discount_code_status_dot = apply_filters( 'pms-list-table-show-status-dot', '<span class="pms-status-dot ' . esc_attr( $discount_code->status ) . '"></span>' );

                if( $discount_code->is_active() )
                    echo $discount_code_status_dot . '<span>' . esc_html__( 'Active', 'paid-member-subscriptions' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                elseif ( $discount_code->is_expired() )
                    echo $discount_code_status_dot . '<span>' . esc_html__( 'Expired', 'paid-member-subscriptions' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    else
                        echo $discount_code_status_dot . '<span>' . esc_html__( 'Inactive', 'paid-member-subscriptions' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }

        }


        /*
        * Remove "Move to Trash" bulk action
        *
        */
        public function remove_bulk_actions($actions) {

            unset($actions['trash']);
            return $actions;

        }

        /*
        * Add a delete button where the move to trash was
        *
        */
        public function submitbox_add_delete_button() {
            global $post_type;
            global $post;

            if ( $post_type != $this->post_type )
                return false;

            echo '<div id="pms-delete-action">';
                echo '<a class="submitdelete deletion" onclick="return confirm( \'' . esc_html__("Are you sure you want to delete this Discount Code?", "paid-member-subscriptions") . ' \' )" href="' . esc_url(wp_nonce_url(add_query_arg(array('pms-action' => 'delete_discount_code', 'post_id' => $post->ID, 'deleted' => 1), admin_url('edit.php?post_type=' . $this->post_type)), 'pms_discount_code_nonce')) . '">' . esc_html__('Delete Discount', 'paid-member-subscriptions') . '</a>';
            echo '</div>';
        }

        /*
        * Method to change the default title text "Enter title here"
        *
        */
        public function change_discount_title_prompt_text($input) {
            global $post_type;

            if ($post_type == $this->post_type) {
                return __('Enter Discount Code name here', 'paid-member-subscriptions');
            }

            return $input;
        }

        /*
        * Method that set custom updated messages
        *
        */
        function set_custom_messages($messages) {

            global $post;

            $messages['pms-discount-codes'] = array(
                0 => '',
                1 => __('Discount Code updated.', 'paid-member-subscriptions'),
                2 => __('Custom field updated.', 'paid-member-subscriptions'),
                3 => __('Custom field deleted.', 'paid-member-subscriptions'),
                4 => __('Discount Code updated.', 'paid-member-subscriptions'),
                5 => isset($_GET['revision']) ? sprintf( __('Discount Code restored to revision from %s', 'paid-member-subscriptions' ), wp_post_revision_title( absint( $_GET['revision'] ), false ) ) : false,
                6 => __('Discount Code saved.', 'paid-member-subscriptions'),
                7 => __('Discount Code saved.', 'paid-member-subscriptions'),
                8 => __('Discount Code submitted.', 'paid-member-subscriptions'),
                9 => sprintf(__('Discount Code scheduled for: <strong>%1$s</strong>.', 'paid-member-subscriptions'), date_i18n( __( 'M j, Y @ G:i', 'paid-member-subscriptions' ), strtotime( $post->post_date ) ) ),
                10 => __('Discount Code draft updated.', 'paid-member-subscriptions'),
            );

            // If there are validation errors do not display the above messages
            $error = get_transient('pms_dc_metabox_validation_errors');
            if  ( !empty($error) ) // no validation errors
                return array();
            else
                return $messages;

        }

        /*
        * Method that set custom bulk updated messages
        *
        */
        public function set_bulk_custom_messages($bulk_messages, $bulk_counts) {

            $bulk_messages['pms-discount-codes'] = array(
                'updated'   => _n('%s Discount Code updated.', '%s Discount Codes updated.', $bulk_counts['updated'], 'paid-member-subscriptions'),
                'locked'    => _n('%s Discount Code not updated, somebody is editing it.', '%s Discount Codes not updated, somebody is editing them.', $bulk_counts['locked'], 'paid-member-subscriptions'),
                'deleted'   => _n('%s Discount Code permanently deleted.', '%s Discount Codes permanently deleted.', $bulk_counts['deleted'], 'paid-member-subscriptions'),
                'trashed'   => _n('%s Discount Code moved to the Trash.', '%s Discount Codes moved to the Trash.', $bulk_counts['trashed'], 'paid-member-subscriptions'),
                'untrashed' => _n('%s Discount Code restored from the Trash.', '%s Discount Codes restored from the Trash.', $bulk_counts['untrashed'], 'paid-member-subscriptions'),
            );

            return $bulk_messages;

        }

        public function add_bulk_create_discount_codes_button(){
            global $pagenow;

            if( $pagenow != 'edit.php' || !isset( $_GET['post_type'] ) || $_GET['post_type'] != 'pms-discount-codes' )
                return;

            echo '<div id="pms-bulk-add-discounts-wrapper" style="margin-left: 8px;">';
                echo '<a class="add-new-h2 page-title-action" href="' . esc_url( add_query_arg( array( 'page' => 'pms-discount-codes-bulk-add' ), admin_url('admin.php') ) ) . '" style="margin-left: 10px;">' . esc_html__( 'Bulk Import Discount Codes', 'paid-member-subscriptions' ) . '</a>';
            echo '</div>';
        }

    }

    /*
     * Initialize the Discount Codes custom post type
     *
     */

    $args = array(
        'show_ui'         => true,
        'show_in_menu'    => 'paid-member-subscriptions',
        'query_var'       => true,
        'capability_type' => 'post',
        'menu_position'   => null,
        'supports'        => array('title'),
        'hierarchical'    => true
    );

    $pms_cpt_discount_codes = new PMS_IN_Custom_Post_Type_Discount_Codes('pms-discount-codes', __('Discount Code', 'paid-member-subscriptions'), __('Discount Codes', 'paid-member-subscriptions'), $args);
    $pms_cpt_discount_codes->init();
}

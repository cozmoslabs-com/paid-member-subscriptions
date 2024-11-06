<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

    /**
     * Function that returns an array with the user roles slugs and names with the exception
     * of the ones created by the subscription plans
     *
     * @return array
     *
     */
    function pms_get_user_role_names() {

        global $wp_roles;

        // This will be returned at the end
        $role_names = array();
        $wp_roles_names = array_reverse( $wp_roles->role_names );

        foreach( $wp_roles_names as $role_slug => $role_name ) {

            // Evade administrators
            if( $role_slug == 'administrator' )
                continue;

            // Escape user roles created from subscription plans
            if( apply_filters( 'pms_get_user_role_names_exclude_subscription_plans_created_roles', '__return_true' ) && strpos( $role_slug, 'pms_subscription_plan_' ) !== false )
                continue;

            $role_names[ $role_slug ] = $role_name;

        }

        return $role_names;
    }


    /**
     * Return a user role name by its slug
     *
     * @param string $role_slug
     *
     */
    function pms_get_user_role_name( $role_slug = '' ) {

        global $wp_roles;

        return ( isset( $wp_roles->role_names[ $role_slug ] ) ? $wp_roles->role_names[ $role_slug ] : '' );
    }


    /**
     * Function that checks to see if a user role exists
     *
     * @param string $role_slug
     *
     * @return bool
     *
     */
    function pms_user_role_exists( $role_slug = '' ) {

        global $wp_roles;

        if( isset( $wp_roles->role_names[$role_slug] ) )
            return true;
        else
            return false;

    }


    /**
     * Returns the user role assign to a subscription plan,
     *
     * @param mixed int|array $subscription_plan_id_or_ids
     *
     * @return mixed int|array
     *
     */
    function pms_get_user_roles_by_plan_ids( $subscription_plan_id_or_ids ) {

        if( is_array( $subscription_plan_id_or_ids ) ) {

            $return = array();

            foreach( $subscription_plan_id_or_ids as $id )
                $return[$id] = get_post_meta( $id, 'pms_subscription_plan_user_role', true );

        } else {

            $return = pms_get_subscription_plan_user_role( $subscription_plan_id_or_ids );

        }

        return $return;

    }


    /**
     * Add a new user role to an existing user
     *
     * @param int    $user_id
     * @param string $user_role
     *
     */
    function pms_add_user_role( $user_id = 0, $user_role = '' ) {

        if( empty( $user_id ) )
            return;

        if( empty( $user_role ) )
            return;

        global $wp_roles;

        if( ! isset( $wp_roles->role_names[$user_role] ) )
            return;

        $user = new WP_User( $user_id );
        $user->add_role( $user_role );

        do_action( 'pms_add_user_role', $user_id, $user_role );

    }


    /**
     * Remove a new user role from an existing user. If the user remains without a role,
     * the default website role is added
     *
     * @param int    $user_id
     * @param string $user_role
     *
     */
    function pms_remove_user_role( $user_id = 0, $user_role = '' ) {

        if( empty( $user_id ) )
            return;

        if( empty( $user_role ) )
            return;

        $user = new WP_User( $user_id );
        $user->remove_role( $user_role );

        if( empty( $user->roles ) )
            $user->add_role( get_option( 'default_role' ) );

        do_action( 'pms_remove_user_role', $user_id, $user_role );

    }


    /**
     * When a member subscription is being inserted into the database we want the role attached to
     * the subscription plan to be added to the user
     *
     * @param int   $subscription_id
     * @param array $new_data
     *
     */
    function pms_member_add_user_role_subscription_inserted( $subscription_id = 0, $new_data = array() ) {

        if( empty( $subscription_id ) || empty( $new_data ) )
            return;

        if( empty( $new_data['subscription_plan_id'] ) )
            return;

        if( empty( $new_data['status'] ) || $new_data['status'] != 'active' )
            return;

        $member_subscription = pms_get_member_subscription( $subscription_id );

        // Add new subscription plan role
        pms_add_user_role( $member_subscription->user_id, pms_get_subscription_plan_user_role( (int)$new_data['subscription_plan_id'] ) );

    }
    add_action( 'pms_member_subscription_insert', 'pms_member_add_user_role_subscription_inserted', 10, 2 );

    /**
     * When a member subscription is being updated and the subscription plan id is changed we also want
     * this to be reflected in the user role
     *
     * @param int   $subscription_id
     * @param array $new_data
     * @param array $old_data
     *
     */
    function pms_member_add_user_role_subscription_updated( $subscription_id = 0, $new_data = array(), $old_data = array() ) {

        if( empty( $subscription_id ) || empty( $new_data ) || empty( $old_data ) )
            return;

        /**
         * Handle activation of the member subscription
         *
         */
        if( ! empty( $new_data['status'] ) && $new_data['status'] == 'active' ) {

            if( ! empty( $old_data['subscription_plan_id'] ) ) {

                $member_subscription = pms_get_member_subscription( $subscription_id );

                // Add new subscription plan role
                pms_add_user_role( $member_subscription->user_id, pms_get_subscription_plan_user_role( (int)$old_data['subscription_plan_id'] ) );

            }

        }


        /**
         * Handle the change of subscription plan ids of the
         *
         */
        if( ! empty( $new_data['subscription_plan_id'] ) && ! empty( $old_data['subscription_plan_id'] ) ) {

            if( $new_data['subscription_plan_id'] != $old_data['subscription_plan_id'] ) {

                $member_subscription = pms_get_member_subscription( $subscription_id );

                // Remove old subscription plan role
                pms_remove_user_role( $member_subscription->user_id, pms_get_subscription_plan_user_role( (int)$old_data['subscription_plan_id'] ) );

                // Add new subscription plan role
                pms_add_user_role( $member_subscription->user_id, pms_get_subscription_plan_user_role( (int)$new_data['subscription_plan_id'] ) );

            }

        }

    }
    add_action( 'pms_member_subscription_update', 'pms_member_add_user_role_subscription_updated', 10, 3 );


    /**
     * Removes the user role, attached to the subscription plan, from the member when their subscription expires
     *
     * @param int   $subscription_id
     * @param array $new_data
     * @param array $old_data
     *
     */
    function pms_member_remove_user_role_subscription_expire( $subscription_id = 0, $new_data = array(), $old_data = array() ) {

        if( empty( $subscription_id ) || empty( $new_data ) || empty( $old_data ) )
            return;

        if( empty( $new_data['status'] ) )
            return;

        if( $new_data['status'] != 'expired' )
            return;

        $member_subscription         = pms_get_member_subscription( $subscription_id );
        $subscription_plan_user_role = pms_get_subscription_plan_user_role( $member_subscription->subscription_plan_id );

        pms_remove_user_role( $member_subscription->user_id, $subscription_plan_user_role );

    }
    add_action( 'pms_member_subscription_update', 'pms_member_remove_user_role_subscription_expire', 10, 3 );


    /**
     * Removes the user role, attached to the subscription plan, from the member when their subscription is deleted
     * from the database
     *
     * @param int   $subscription_id
     * @param array $old_data
     *
     */
    function pms_member_remove_user_role_subscription_deleted( $subscription_id = 0, $old_data = array() ) {

        if( empty( $subscription_id ) || empty( $old_data ) )
            return;

        if( empty( $old_data['subscription_plan_id'] ) )
            return;

        if( empty( $old_data['user_id'] ) )
            return;

        $subscription_plan_user_role = pms_get_subscription_plan_user_role( $old_data['subscription_plan_id'] );

        pms_remove_user_role( $old_data['user_id'], $subscription_plan_user_role );

    }
    add_action( 'pms_member_subscription_delete', 'pms_member_remove_user_role_subscription_deleted', 10, 2 );

    /**
     * Removes the user role, attached to the subscription plan, from the member when the user is abandoning
     * the subscription plan
     *
     * @param int   $subscription_id
     * @param array $old_data
     *
     */
    function pms_member_remove_user_role_subscription_abandoned( $member_data, $member_subscription ) {

        if( empty( $member_data['user_id'] ) || empty( $member_subscription->subscription_plan_id ) )
            return;

        pms_remove_user_role( $member_data['user_id'], pms_get_subscription_plan_user_role( $member_subscription->subscription_plan_id ) );

    }
    add_action( 'pms_abandon_member_subscription_successful', 'pms_member_remove_user_role_subscription_abandoned', 10, 2 );


/**
 * Multiple Roles Selection
 *
 */
class PMS_Multiple_Roles_Selection {

    function __construct() {

        // Add multiple roles checkbox to back-end Add / Edit User (as admin)
        add_action( 'load-user-new.php', array( $this, 'actions_on_user_new' ) );
        add_action( 'load-user-edit.php', array( $this, 'actions_on_user_edit' ) );

        add_action( 'load-profile.php', array( $this, 'actions_on_user_new' ) );
        add_action( 'load-profile.php', array( $this, 'actions_on_user_edit' ) );

    }

    public function sanitize_role( $role ) {

        $role = strtolower( $role );
        $role = wp_strip_all_tags( $role );
        $role = preg_replace( '/[^a-z0-9_\-\s]/', '', $role );
        $role = str_replace( ' ', '_', $role );
        $role = sanitize_text_field( $role );

        return $role;

    }

    // Add actions on Add User back-end page
    public function actions_on_user_new() {

        $this->scripts_and_styles_actions( 'user_new' );

        add_action( 'user_new_form', array( $this, 'roles_field_user_new' ) );

        add_action( 'user_register', array( $this, 'roles_update_user_new' ) );

    }

    // Add actions on Edit User back-end page
    public function actions_on_user_edit() {

        $this->scripts_and_styles_actions( 'user_edit' );

        add_action( 'personal_options', array( $this, 'roles_field_user_edit' ) );

        add_action( 'profile_update', array( $this, 'roles_update_user_edit' ), 10, 2 );

    }

    // Roles Edit checkboxes for Add User back-end page
    public function roles_field_user_new() {

        if( ! current_user_can( 'promote_users' ) ) {
            return;
        }

        $user_roles = apply_filters( 'pms_default_user_roles', array( get_option( 'default_role' ) ) );

        if( isset( $_POST['createuser'] ) && ! empty( $_POST['pms_re_user_roles'] ) ) {
            $user_roles = array_map( array( $this, 'sanitize_role' ), $_POST['pms_re_user_roles'] );// phpcs:ignore  WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        }

        wp_nonce_field( 'new_user_roles', 'pms_re_new_user_roles_nonce' );

        if( apply_filters( 'pms_backend_allow_multiple_user_roles_selection', true ) ) {
            $this->roles_field_display( $user_roles );
        }

    }

    // Roles Edit checkboxes for Edit User back-end page
    public function roles_field_user_edit( $user ) {

        if( ! current_user_can( 'promote_users' ) || ! current_user_can( 'edit_user', $user->ID ) ) {
            return;
        }

        $user_roles = (array) $user->roles;

        wp_nonce_field( 'new_user_roles', 'pms_re_new_user_roles_nonce' );

        if( apply_filters( 'pms_backend_allow_multiple_user_roles_selection', true ) ) {
            $this->roles_field_display( $user_roles );
        }
    }

    // Output roles edit checkboxes
    public function roles_field_display( $user_roles ) {

        $pms_roles = get_editable_roles();

        ?>
        <table class="form-table">
            <tr class="pms-re-edit-user">
                <th><?php esc_html_e( 'Edit User Roles', 'paid-member-subscriptions' ); ?></th>

                <td>
                    <div>
                        <ul style="margin: 5px 0;">
                            <?php foreach( $pms_roles as $role_slug => $role_details ) { ?>
                                <li>
                                    <label>
                                        <input type="checkbox" name="pms_re_user_roles[]" value="<?php echo esc_attr( $role_slug ); ?>" <?php checked( in_array( $role_slug, $user_roles ) ); ?> />
                                        <?php echo esc_html( translate_user_role( $role_details['name'] ) ); ?>
                                    </label>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                </td>
            </tr>
        </table>

        <?php
    }

    public function roles_update_user_edit( $user_id, $old_user_data ) {

        if( ! current_user_can( 'promote_users' ) || ! current_user_can( 'edit_user', $user_id ) ) {
            return;
        }

        if( ! isset( $_POST['pms_re_new_user_roles_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['pms_re_new_user_roles_nonce'] ), 'new_user_roles' ) ) {
            return;
        }

        $this->roles_update_user_new_and_edit( $old_user_data );

    }

    public function roles_update_user_new( $user_id ) {

        if( ! current_user_can( 'promote_users' ) ) {
            return;
        }

        if( ! isset( $_POST['pms_re_new_user_roles_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['pms_re_new_user_roles_nonce'] ), 'new_user_roles' ) ) {
            return;
        }

        $user = new \WP_User( $user_id );

        $this->roles_update_user_new_and_edit( $user );

    }

    public function roles_update_user_new_and_edit( $user ) {

        if( ! empty( $_POST['pms_re_user_roles'] ) || !empty( $_POST['role'] ) ) {

            $old_roles = (array) $user->roles;

            if( isset( $_POST['pms_re_user_roles'] ) )
                $new_roles = array_map( array( $this, 'sanitize_role' ), $_POST['pms_re_user_roles'] );//phpcs:ignore  WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            else
                $new_roles = array( $this->sanitize_role( $_POST['role'] ) ); //phpcs:ignore  WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

            foreach( $new_roles as $new_role ) {
                if( ! in_array( $new_role, (array) $user->roles ) ) {
                    $user->add_role( $new_role );
                }
            }

            foreach( $old_roles as $old_role ) {
                if( ! in_array( $old_role, $new_roles ) ) {
                    $user->remove_role( $old_role );
                }
            }
        } else {
            foreach( (array) $user->roles as $old_role ) {
                $user->remove_role( $old_role );
            }
        }

    }

    public function scripts_and_styles_actions( $location ) {

        // Enqueue jQuery on both Add User and Edit User back-end pages
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_jquery' ) );

        // Actions for Add User back-end page
        if( $location == 'user_new' && apply_filters( 'pms_backend_allow_multiple_user_roles_selection', true ) ) {
            add_action( 'admin_footer', array( $this, 'print_scripts_user_new' ), 25 );
        }

        // Actions for Edit User back-end page
        if( $location == 'user_edit' && apply_filters( 'pms_backend_allow_multiple_user_roles_selection', true ) ) {
            add_action( 'admin_head', array( $this, 'print_styles_user_edit' ) );
            add_action( 'admin_footer', array( $this, 'print_scripts_user_edit' ), 25 );
        }

    }

    // Enqueue jQuery where needed (use action)
    public function enqueue_jquery() {

        wp_enqueue_script( 'jquery' );

    }

    // Print scripts on Add User back-end page
    public function print_scripts_user_new() {

        ?>
        <script>
            jQuery( document ).ready( function() {
                // Remove WordPress default Role Select
                var roles_dropdown = jQuery( 'select#role' );
                roles_dropdown.closest( 'tr' ).remove();
            } );
        </script>

        <?php
    }

    // Print scripts on Edit User back-end page
    public function print_scripts_user_edit() {

        ?>
        <script>
            jQuery( document ).ready(
                // Remove WordPress default Role Select
                function() {
                    jQuery( '.user-role-wrap' ).remove();
                }
            );
        </script>

        <?php
    }

    // Print scripts on Edit User back-end page
    public function print_styles_user_edit() {

        ?>
        <style type="text/css">
            /* Hide WordPress default Role Select */
            .user-role-wrap {
                display: none !important;
            }
        </style>

        <?php
    }

}

if ( ! function_exists( 'is_plugin_active' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

$wppb_generalSettings = get_option( 'wppb_general_settings', 'not_found' );

    if(  ! is_plugin_active( 'profile-builder/index.php' ) || is_plugin_active('profile-builder-dev/index.php') || $wppb_generalSettings == 'not_found' || empty( $wppb_generalSettings['rolesEditor'] ) || $wppb_generalSettings['rolesEditor'] !== 'yes'  ) {
        $pms_role_editor_instance = new PMS_Multiple_Roles_Selection();
    }
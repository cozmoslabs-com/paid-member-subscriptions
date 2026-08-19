<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Extend the basic functionality of the main custom post type class to the
 * needs of the subpscription
 *
 */
Class PMS_Custom_Post_Type_Subscription extends PMS_Custom_Post_Type {

    /**
     * Custom post type instance
     *
     * @access private
     * @var object
     */
    private static $instance;

    /*
     * Method to add the needed hooks
     *
     */
    public function init() {

        add_action( 'init', array( $this, 'process_data' ) );
        add_action( 'init', array( $this, 'register_custom_subscription_plan_statuses' ) );

        add_filter( 'page_row_actions', array( $this, 'remove_post_row_actions' ), 10, 2 );
        add_action( 'page_row_actions', array( $this, 'add_post_row_actions' ), 11, 2 );

        add_filter( 'manage_' . $this->post_type . '_posts_columns', array( __CLASS__, 'manage_posts_columns' ) );
        add_action( 'manage_' . $this->post_type . '_posts_custom_column', array( __CLASS__, 'manage_posts_custom_column' ), 10, 2 );


        //Create Pricing Page button
        add_action('admin_footer', array( $this, 'admin_footer_add_create_pricing_page_button') );
        add_action('admin_post_pms_create_pricing_table_page', array( $this, 'creating_pricing_table_page' ) );
        add_action('admin_post_pms_update_pricing_table_style', array( $this, 'update_pricing_table_page' ) );


        // Bulk actions
        add_filter( 'bulk_actions-edit-' . $this->post_type, array( $this, 'remove_bulk_actions' ) );

        // Add custom bulk actions through javascript
        add_action( 'admin_footer-edit.php', array( $this, 'add_bulk_actions' ) );

        // Process the data received from the bulk actions
        add_action( 'admin_init', array( $this, 'process_custom_bulk_actions' ) );

        // Add a delete button where the move to trash was
        add_action( 'post_submitbox_start', array( $this, 'submitbox_add_status_field' ) );
        add_action( 'post_submitbox_start', array( $this, 'submitbox_add_delete_button' ));

        // Add "Add Upgrade" and "Add Downgrade" buttons in the submit box, we will move them
        // next to the "Add new" with js
        add_action( 'post_submitbox_start', array( $this, 'submitbox_add_upgrade_downgrade_buttons' ));

        // Add a subtitle to the upgrade / downgrade add new screen
        add_action( 'edit_form_top', array( $this, 'add_upgrade_downgrade_subtitle' ) );

        // Add upgrade/downgrade action in the HTML
        add_action( 'edit_form_top', array( $this, 'add_upgrade_downgrade_action' ) );

        // Add edit-subscription-plan action in the HTML
        add_action( 'edit_form_top', array( $this, 'add_edit_subscription_plan_action' ) );

        // Render Subscription Plan top-level tabs server-side
        add_action( 'edit_form_after_title', array( $this, 'render_subscription_tab_shell' ) );
        add_action( 'edit_form_top', array( $this, 'render_active_subscription_tab_field' ) );

        // Prevent default Subscription Plan metabox rendering, as tabs render them directly.
        add_action( 'add_meta_boxes', array( $this, 'remove_subscription_plan_default_metaboxes' ), 100, 2 );

        // Register default extra metaboxes for Subscription Plan tabs.
        add_filter( 'pms_subscription_plan_advanced_metaboxes', array( $this, 'register_advanced_integrations_metabox' ), 10, 3 );

        // Change the default "Enter title here" text
        add_filter( 'enter_title_here', array( $this, 'change_title_prompt_text' ) );

        // Save as auto-draft draft subscription plans
        add_action( 'save_post', array( $this, 'save_as_auto_draft' ) );

        // Add new subscription plan as a downgrade
        //add_action( 'save_post', array( $this, 'add_new_subscription_plan' ) );

        // Add new subscription plan downgrade
        add_action( 'save_post', array( $this, 'add_new_downgrade' ) );

        // Add new subscription plan upgrade
        add_action( 'save_post', array( $this, 'add_new_upgrade' ) );

        // Add the top parent id to the meta data of each subscription plan
        add_action( 'save_post', array( $this, 'update_subscription_plan_top_parent' ) );

		// Set custom updated messages
		add_filter( 'post_updated_messages', array( $this, 'set_custom_messages' ) );

		// Set custom bulk updated messages
		add_filter( 'bulk_post_updated_messages', array( $this, 'set_bulk_custom_messages' ), 10, 2 );

        // Preserve the active tab after saving the Subscription Plan
        add_filter( 'redirect_post_location', array( $this, 'preserve_active_subscription_tab_on_redirect' ), 10, 2 );

    }

    /**
     * Returns the available Subscription Plan tabs.
     *
     * @return array
     */
    public function get_subscription_tabs( $post = null, $subscription_plan = null ) {

        $tabs = array(
            'subscription_plan_details' => array(
                'label'    => esc_html__( 'Subscription Plan Details', 'paid-member-subscriptions' ),
                'priority' => 10,
            ),
            'content_restriction'       => array(
                'label'    => esc_html__( 'Global Content Restriction', 'paid-member-subscriptions' ),
                'priority' => 20,
            ),
            'advanced'                  => array(
                'label'    => esc_html__( 'Advanced', 'paid-member-subscriptions' ),
                'priority' => 30,
            ),
        );

        $tabs = apply_filters( 'pms_subscription_plan_tabs', $tabs, $post, $subscription_plan );

        return $this->sort_subscription_plan_items_by_priority( $tabs );

    }

    /**
     * Returns the default metaboxes assigned to a Subscription Plan tab.
     *
     * @param string $tab_slug
     *
     * @return array
     */
    public function get_default_subscription_tab_metaboxes( $tab_slug ) {

        $metaboxes = array(
            'subscription_plan_details' => array(
                array(
                    'id'       => 'pms_subscription_details',
                    'title'    => esc_html__( 'General', 'paid-member-subscriptions' ),
                    'priority' => 10,
                ),
                array(
                    'id'       => 'pms_subscription_price',
                    'title'    => esc_html__( 'Price', 'paid-member-subscriptions' ),
                    'priority' => 20,
                ),
            ),
            'content_restriction'       => array(
                array(
                    'id'       => 'pms_subscription_content_restriction',
                    'title'    => esc_html__( 'Global Content Restriction', 'paid-member-subscriptions' ),
                    'priority' => 10,
                ),
            ),
            'advanced'                  => array(
                array(
                    'id'       => 'pms_subscription_access_behavior',
                    'title'    => esc_html__( 'Access & Membership Behavior', 'paid-member-subscriptions' ),
                    'priority' => 10,
                ),
                array(
                    'id'       => 'pms_subscription_pause',
                    'title'    => esc_html__( 'Pause Subscription', 'paid-member-subscriptions' ),
                    'priority' => 20,
                ),
                array(
                    'id'       => 'pms_subscription_extra_options',
                    'title'    => esc_html__( 'Advanced Subscription Options', 'paid-member-subscriptions' ),
                    'priority' => 30,
                ),
            ),
        );

        return isset( $metaboxes[ $tab_slug ] ) ? $metaboxes[ $tab_slug ] : array();

    }

    /**
     * Returns all metaboxes that should be rendered for a Subscription Plan tab.
     *
     * @param string $tab_slug
     * @param WP_Post $post
     * @param PMS_Subscription_Plan $subscription_plan
     *
     * @return array
     */
    public function get_subscription_tab_metaboxes( $tab_slug, $post, $subscription_plan ) {

        $metaboxes = $this->get_default_subscription_tab_metaboxes( $tab_slug );

        $metaboxes = apply_filters( 'pms_subscription_plan_' . $tab_slug . '_default_metaboxes', $metaboxes, $post, $subscription_plan );

        $metaboxes = apply_filters( 'pms_subscription_plan_tab_metaboxes', $metaboxes, $tab_slug, $post, $subscription_plan );
        $metaboxes = apply_filters( 'pms_subscription_plan_' . $tab_slug . '_metaboxes', $metaboxes, $post, $subscription_plan );

        $metaboxes = $this->sort_subscription_plan_items_by_priority( $metaboxes );

        return array_values( array_filter( $metaboxes, array( $this, 'normalize_subscription_tab_metabox' ) ) );

    }

    /**
     * Normalizes a tab metabox configuration.
     *
     * @param array $metabox
     *
     * @return bool
     */
    public function normalize_subscription_tab_metabox( $metabox ) {

        return is_array( $metabox ) && ! empty( $metabox['id'] ) && ! empty( $metabox['title'] );

    }

    /**
     * Returns the active Subscription Plan tab from the request.
     *
     * @return string
     */
    public function get_active_subscription_tab( $tabs = null ) {

        $active_tab = 'subscription_plan_details';
        $tabs       = is_array( $tabs ) ? array_keys( $tabs ) : array_keys( $this->get_subscription_tabs() );

        if ( ! empty( $_GET['subscription_tab'] ) ) {
            $tab = sanitize_text_field( $_GET['subscription_tab'] );

            if ( in_array( $tab, $tabs, true ) ) {
                $active_tab = $tab;
            }
        }

        return $active_tab;

    }

    /**
     * Renders the Subscription Plan tab shell using PHP.
     *
     * @param WP_Post $post
     *
     * @return void
     */
    public function render_subscription_tab_shell( $post ) {

        if ( ! $post instanceof WP_Post || $post->post_type !== $this->post_type ) {
            return;
        }

        $subscription_plan = pms_get_subscription_plan( $post );
        $tabs              = $this->get_subscription_tabs( $post, $subscription_plan );
        $active_tab        = $this->get_active_subscription_tab( $tabs );

        ?>
        <div class="pms-subscription-tabs-shell">
            <div class="cozmoslabs-nav-tab-wrapper pms-subscription-tabs-nav">
                <?php foreach ( $tabs as $tab_slug => $tab_data ) : ?>
                    <a href="<?php echo esc_url( add_query_arg( 'subscription_tab', $tab_slug ) ); ?>" class="nav-tab <?php echo ( $active_tab === $tab_slug ? 'nav-tab-active' : '' ); ?>" data-tab-slug="<?php echo esc_attr( $tab_slug ); ?>">
                        <?php echo esc_html( $tab_data['label'] ); ?>
                        <?php if ( $tab_slug === 'content_restriction' && $this->should_render_content_restriction_pro_badge() ) : ?>
                            <span class="pms-subscription-tab-badge"><?php esc_html_e( 'PRO', 'paid-member-subscriptions' ); ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php foreach ( $tabs as $tab_slug => $tab_data ) : ?>
                <div class="cozmoslabs-sub-tab pms-subscription-tab-panel <?php echo ( $active_tab === $tab_slug ? 'tab-active' : '' ); ?>" data-sub-tab-slug="<?php echo esc_attr( $tab_slug ); ?>">
                    <?php do_action( 'pms_subscription_plan_tab_before_metaboxes', $tab_slug, $post, $subscription_plan ); ?>
                    <?php do_action( 'pms_subscription_plan_' . $tab_slug . '_before_metaboxes', $post, $subscription_plan ); ?>

                    <?php foreach ( $this->get_subscription_tab_metaboxes( $tab_slug, $post, $subscription_plan ) as $metabox ) : ?>
                        <?php if ( $this->should_render_subscription_tab_metabox( $metabox, $post, $subscription_plan ) ) : ?>
                            <?php $this->render_subscription_tab_metabox( $metabox, $post, $subscription_plan ); ?>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <?php do_action( 'pms_subscription_plan_' . $tab_slug . '_after_metaboxes', $post, $subscription_plan ); ?>
                    <?php do_action( 'pms_subscription_plan_tab_after_metaboxes', $tab_slug, $post, $subscription_plan ); ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php

    }

    /**
     * Renders a hidden field that keeps the active subscription tab in the form context.
     *
     * @param WP_Post $post
     *
     * @return void
     */
    public function render_active_subscription_tab_field( $post ) {

        if ( ! $post instanceof WP_Post || $post->post_type !== $this->post_type ) {
            return;
        }

        printf(
            '<input type="hidden" name="pms_active_subscription_tab" value="%s" />',
            esc_attr( $this->get_active_subscription_tab() )
        );

    }

    /**
     * Determines whether the Global Content Restriction tab should display
     * the PRO badge.
     *
     * @return bool
     */
    public function should_render_content_restriction_pro_badge() {

        $is_gcr_addon_active = apply_filters( 'pms_add_on_is_active', false, 'pms-add-on-global-content-restriction/index.php' );

        if ( $is_gcr_addon_active ) {
            return false;
        }

        return ! ( pms_get_serial_number_status() === 'valid' && defined( 'PMS_PAID_PLUGIN_DIR' ) );

    }

    /**
     * Preserves the active Subscription Plan tab in the post-save redirect.
     *
     * @param string $location
     * @param int    $post_id
     *
     * @return string
     */
    public function preserve_active_subscription_tab_on_redirect( $location, $post_id ) {

        if ( get_post_type( $post_id ) !== $this->post_type ) {
            return $location;
        }

        $post              = get_post( $post_id );
        $subscription_plan = $post ? pms_get_subscription_plan( $post ) : null;
        $tabs              = array_keys( $this->get_subscription_tabs( $post, $subscription_plan ) );

        $active_tab = '';

        if ( ! empty( $_POST['pms_active_subscription_tab'] ) ) {
            $active_tab = sanitize_text_field( $_POST['pms_active_subscription_tab'] );
        } elseif ( ! empty( $_GET['subscription_tab'] ) ) {
            $active_tab = sanitize_text_field( $_GET['subscription_tab'] );
        }

        if ( empty( $active_tab ) || ! in_array( $active_tab, $tabs, true ) ) {
            return remove_query_arg( 'subscription_tab', $location );
        }

        return add_query_arg( 'subscription_tab', $active_tab, $location );

    }

    public function remove_subscription_plan_default_metaboxes( $post_type, $post ) {

        if ( $post_type !== $this->post_type || ! $post instanceof WP_Post || $post->post_type !== $this->post_type ) {
            return;
        }

        $subscription_plan = pms_get_subscription_plan( $post );
        $metabox_ids       = array();

        foreach ( array_keys( $this->get_subscription_tabs( $post, $subscription_plan ) ) as $tab_slug ) {
            foreach ( $this->get_subscription_tab_metaboxes( $tab_slug, $post, $subscription_plan ) as $metabox ) {
                if ( ! empty( $metabox['id'] ) ) {
                    $metabox_ids[] = $metabox['id'];
                }
            }
        }

        foreach ( array_unique( $metabox_ids ) as $metabox_id ) {
            remove_meta_box( $metabox_id, $this->post_type, 'normal' );
            remove_meta_box( $metabox_id, $this->post_type, 'advanced' );
            remove_meta_box( $metabox_id, $this->post_type, 'side' );
        }

    }

    public function should_render_subscription_tab_metabox( $metabox, $post, $subscription_plan ) {

        if ( ! empty( $metabox['render_callback'] ) && is_callable( $metabox['render_callback'] ) ) {
            return true;
        }

        if ( empty( $metabox['id'] ) ) {
            return false;
        }

        if ( $metabox['id'] === 'pms_subscription_pause' && ! class_exists( 'PMS_IN_PS' ) ) {
            return false;
        }

        if ( $metabox['id'] !== 'pms_subscription_integrations' ) {
            return true;
        }

        return trim( pms_get_subscription_plan_integrations_markup( $subscription_plan->id ) ) !== '';

    }

    public function render_subscription_tab_metabox( $metabox, $post, $subscription_plan ) {

        if ( empty( $metabox['id'] ) || empty( $metabox['title'] ) ) {
            return;
        }

        echo '<div id="' . esc_attr( $metabox['id'] ) . '" class="postbox">';
        echo '<div class="postbox-header">';
        echo '<h2 class="hndle">' . esc_html( $metabox['title'] ) . '</h2>';
        echo '</div>';
        echo '<div class="inside">';

        if ( ! empty( $metabox['render_callback'] ) && is_callable( $metabox['render_callback'] ) ) {
            call_user_func( $metabox['render_callback'], $post, $subscription_plan, $metabox );
        } else {
            do_action( 'pms_output_content_meta_box_' . $this->post_type . '_' . $metabox['id'] . '_before', $post );
            do_action( 'pms_output_content_meta_box_' . $this->post_type . '_' . $metabox['id'], $post );
            do_action( 'pms_output_content_meta_box_' . $this->post_type . '_' . $metabox['id'] . '_after', $post );
        }

        echo '</div>';
        echo '</div>';

    }

    public function register_advanced_integrations_metabox( $metaboxes, $post, $subscription_plan ) {

        $metaboxes[] = array(
            'id'       => 'pms_subscription_integrations',
            'title'    => esc_html__( 'Integrations', 'paid-member-subscriptions' ),
            'priority' => 15,
        );

        return $metaboxes;

    }

    public function sort_subscription_plan_items_by_priority( $items ) {

        uasort(
            $items,
            function( $left, $right ) {
                $left_priority  = isset( $left['priority'] ) ? absint( $left['priority'] ) : 10;
                $right_priority = isset( $right['priority'] ) ? absint( $right['priority'] ) : 10;

                if ( $left_priority === $right_priority ) {
                    return 0;
                }

                return ( $left_priority < $right_priority ) ? -1 : 1;
            }
        );

        return $items;

    }

    /*
     * Method that validates data for the subscription plan cpt
     *
     */
    public function process_data() {

        if( ! pms_current_user_can_access_area( 'pms-subscription' ) )
            return;

        // Verify nonce before anything
        if( !isset( $_REQUEST['_wpnonce'] ) || !wp_verify_nonce( sanitize_text_field( $_REQUEST['_wpnonce'] ), 'pms_subscription_plan_nonce' ) )
            return;


        // Activate subscription plan
        if( isset( $_REQUEST['pms-action'] ) && $_REQUEST['pms-action'] == 'activate_subscription_plan' && isset( $_REQUEST['post_id'] ) ) {
            PMS_Subscription_Plan::activate( (int)sanitize_text_field( $_REQUEST['post_id'] ) );
        }

        // Deactivate subscription plan
        if( isset( $_REQUEST['pms-action'] ) && $_REQUEST['pms-action'] == 'deactivate_subscription_plan' && isset( $_REQUEST['post_id'] ) ) {
            PMS_Subscription_Plan::deactivate( (int)sanitize_text_field( $_REQUEST['post_id'] ) );
        }

        // Duplicate subscription plan
        if( isset( $_REQUEST['pms-action'] ) && $_REQUEST['pms-action'] == 'duplicate_subscription_plan' && isset( $_REQUEST['post_id'] ) ) {
            PMS_Subscription_Plan::duplicate( (int)sanitize_text_field( $_REQUEST['post_id'] ) );
        }

        // Delete subscription plan
        if( isset( $_REQUEST['pms-action'] ) && $_REQUEST['pms-action'] == 'delete_subscription_plan' && isset( $_REQUEST['post_id'] ) ) {
            $plan_id = (int)sanitize_text_field( $_REQUEST['post_id'] );

            PMS_Subscription_Plan::remove( $plan_id );

            //remove restrictions using this plan
            global $wpdb;

            $wpdb->delete( $wpdb->prefix . 'postmeta', array( 'meta_key' => 'pms-content-restrict-subscription-plan', 'meta_value' => $plan_id ) );

            do_action( 'pms_after_delete_subscription_plan', $plan_id );
        }

        // Move subscription plan up
        if( isset( $_GET['pms-action'] ) && $_GET['pms-action'] == 'move_up_subscription_plan' && isset( $_GET['post_id'] ) ) {

            if( !isset( $_GET['post_type'] ) || $_GET['post_type'] != $this->post_type )
                return;

            $post_id      = (int)sanitize_text_field( $_GET['post_id'] );
            $current_post = get_post( $post_id );

            // If this post doesn't have a parent do nothing
            if( $current_post->post_parent == 0 )
                return;

            $parent_post    = get_post( $current_post->post_parent );
            $children_posts = get_posts( array( 'post_type' => $this->post_type, 'post_status' => 'any', 'numberposts' => 1, 'post_parent' => $post_id ) );

            if( function_exists( 'pms_in_gcr_save_metabox_content' ) )
                remove_action( 'pms_save_meta_box_pms-subscription', 'pms_in_gcr_save_metabox_content' );

            wp_update_post( array( 'ID' => $current_post->ID, 'post_parent' => $parent_post->post_parent ) );
            wp_update_post( array( 'ID' => $parent_post->ID, 'post_parent' => $current_post->ID ) );

            if( !empty( $children_posts ) ) {
                $child_post = $children_posts[0];
                wp_update_post( array( 'ID' => $child_post->ID, 'post_parent' => $parent_post->ID ) );
            }

            do_action( 'pms_after_move_subscription_plan_up', $current_post->ID, $parent_post->ID );

            wp_redirect( esc_url_raw( add_query_arg( array( 'post_type' => $this->post_type ), pms_get_current_page_url(true) ) ) );
            die();

        }

        // Move subscription plan down
        if( isset( $_GET['pms-action'] ) && $_GET['pms-action'] == 'move_down_subscription_plan' && isset( $_GET['post_id'] ) ) {

            if( !isset( $_GET['post_type'] ) || $_GET['post_type'] != $this->post_type )
                return;

            $post_id      = sanitize_text_field( $_GET['post_id'] );
            $current_post = get_post( $post_id );

            $children_posts = get_posts( array( 'post_type' => $this->post_type, 'post_status' => 'any', 'numberposts' => 1, 'post_parent' => $post_id ) );

            // Exit if the post is the last in the group
            if( empty( $children_posts ) )
                return;

            $child_post = $children_posts[0];

            $children_posts = get_posts( array( 'post_type' => $this->post_type, 'post_status' => 'any', 'numberposts' => 1, 'post_parent' => $child_post->ID ) );

            if( function_exists( 'pms_in_gcr_save_metabox_content' ) )
                remove_action( 'pms_save_meta_box_pms-subscription', 'pms_in_gcr_save_metabox_content' );

            wp_update_post( array( 'ID' => $child_post->ID, 'post_parent' => ( !empty( $current_post->post_parent ) ? $current_post->post_parent : 0 ) ) );
            wp_update_post( array( 'ID' => $current_post->ID, 'post_parent' => $child_post->ID ) );

            if( !empty( $children_posts ) ) {
                $child_post = $children_posts[0];
                wp_update_post( array( 'ID' => $child_post->ID, 'post_parent' => $current_post->ID ) );
            }

            do_action( 'pms_after_move_subscription_plan_down', $current_post->ID, $child_post->ID );

            wp_redirect( esc_url_raw( add_query_arg( array( 'post_type' => $this->post_type ), pms_get_current_page_url(true) ) ) );
            die();

        }

    }

    /**
     * Method for registering custom subscription plan statuses (active, inactive)
     *
     */
    public function register_custom_subscription_plan_statuses() {

        // Register custom Subscription Plan Statuses
        register_post_status( 'active', array(
            'label'                     => _x( 'Active', 'Active status for subscription plan', 'paid-member-subscriptions' ),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop( 'Active <span class="count">(%s)</span>', 'Active <span class="count">(%s)</span>', 'paid-member-subscriptions' )
        )  );
        register_post_status( 'inactive', array(
            'label'                     => _x( 'Inactive', 'Inactive status for subscription plan', 'paid-member-subscriptions' ),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop( 'Inactive <span class="count">(%s)</span>', 'Inactive <span class="count">(%s)</span>', 'paid-member-subscriptions' )
        )  );

    }

    /*
     * Method that removes all row actions besides the edit one
     *
     */
    public function remove_post_row_actions( $actions, $post ) {

        if( $post->post_type != $this->post_type )
            return $actions;

        if( empty( $actions ) )
            return $actions;

        foreach( $actions as $key => $action ) {
            if( $key != 'edit' ) {
                unset( $actions[ $key ] );
            }
        }

        return $actions;
    }


    /*
     * Method that adds new actions
     *
     */
    public function add_post_row_actions( $actions, $post ) {

        if( $post->post_type != $this->post_type )
            return $actions;

        if( empty( $actions ) )
            return $actions;


        /*
         * Add the option to activate and deactivate a subscription plan
         */
        $subscription_plan = new PMS_Subscription_Plan( $post );

        if( $subscription_plan->is_active() )
            $activate_deactivate = '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'deactivate_subscription_plan', 'post_id' => $post->ID ) ), 'pms_subscription_plan_nonce' ) ) . '">' . esc_html__( 'Deactivate', 'paid-member-subscriptions' ) . '</a>';
        else
            $activate_deactivate = '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'activate_subscription_plan', 'post_id' => $post->ID ) ), 'pms_subscription_plan_nonce' ) ) . '">' . esc_html__( 'Activate', 'paid-member-subscriptions' ) . '</a>';

        $actions['change_status'] = $activate_deactivate;


        /*
         * Add the option to add a parent to a subscription plan
         */
        $add_upgrade = '<a href="' . esc_url( add_query_arg( array( 'post_type' => $post->post_type, 'plan_id' => $post->ID, 'pms-action' => 'add_upgrade' ), admin_url( 'post-new.php' ) ) ) . '">' . esc_html__( 'Add Upgrade', 'paid-member-subscriptions' ) . '</a>';

        $actions['add_upgrade'] = $add_upgrade;


        /*
         * Add the options to add a child to a subscription plan
         */
        $add_downgrade = '<a href="' . esc_url( add_query_arg( array( 'post_type' => $post->post_type, 'plan_id' => $post->ID, 'pms-action' => 'add_downgrade' ), admin_url( 'post-new.php' ) ) ) . '">' . esc_html__( 'Add Downgrade', 'paid-member-subscriptions' ) . '</a>';

        $actions['add_downgrade'] = $add_downgrade;


        /*
         * Add the option to duplicate a subscription plan
         */
        $duplicate = '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'duplicate_subscription_plan', 'post_id' => $post->ID ) ), 'pms_subscription_plan_nonce' ) ) . '">' . esc_html__( 'Duplicate', 'paid-member-subscriptions' ) . '</a>';

        $actions['duplicate'] = $duplicate;

        /*
         * Add the option to delete a subscription plan
         */
        $delete = '<span class="trash pms-delete-subscription"><a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'delete_subscription_plan', 'post_id' => $post->ID, 'deleted' => 1 ) ), 'pms_subscription_plan_nonce' ) ) . '">' . esc_html__( 'Delete', 'paid-member-subscriptions' ) . '</a></span>';

        $actions['delete'] = $delete;


        // Return actions
        return $actions;

    }


    /*
     * Method that adds new columns on the subscription plan listing
     *
     */
    public static function manage_posts_columns( $columns ) {

        $columns['price']  = esc_html__( 'Price', 'paid-member-subscriptions' );

        if( pms_payment_gateways_support( pms_get_active_payment_gateways(), 'subscription_sign_up_fee' ) ) {
            $columns['sign_up_fee'] = esc_html__( 'Sign Up Fee', 'paid-member-subscriptions' );
        }

        if( pms_payment_gateways_support( pms_get_active_payment_gateways(), 'subscription_free_trial' ) ) {
            $columns['free_trial']  = esc_html__( 'Free Trial', 'paid-member-subscriptions' );
        }

        $columns['status'] = esc_html__( 'Status', 'paid-member-subscriptions' );
        $columns['id']     = esc_html__( 'ID', 'paid-member-subscriptions' );
        $columns['order']  = '';

        // Shift the order column after the checkbox column
        $beginning = array_slice( $columns, 0, 1 );
        $middle    = array_slice( $columns, 1, count( $columns ) - 3 );
        $end       = array_slice( $columns, count( $columns ) - 2, 2 );

        $columns = array_merge( $beginning, $end, $middle );


        // Shift the publishing date column to the end
        $date_column = $columns['date'];
        unset( $columns['date'] );
        $columns['date'] = $date_column;

        return $columns;

    }


    /*
     * Method to display values for each new column
     *
     */
	public static function manage_posts_custom_column( $column, $post_id ) {

        $subscription_plan = new PMS_Subscription_Plan( $post_id );

        // Information shown in the order column
        if( $column == 'order' ) {
            /* get parents */
            $parent_id = wp_get_post_parent_id( $post_id );
            /* get children */
            $post_children = get_children( array( 'post_parent' => $post_id, 'post_type' => 'pms-subscription' ) );

            if( $parent_id != false )
                echo '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'move_up_subscription_plan', 'post_id' => $post_id ) ), 'pms_subscription_plan_nonce' ) ) . '" class="add-new-h2 pms-subscription-plan-order-move-up" title="' . esc_html__( 'Move Subscription Plan Up', 'paid-member-subscriptions' ) . '">&uarr;</a>';
            else
                echo '<span class="pms-subscription-plan-order-move-up pms-subscription-plan-order-placeholder ' . ( !empty( $post_children ) ? 'move-down' : '' ) . '"><span class="pms-inner">&uarr;</span></span>';

            if( !empty( $post_children ) )
                echo '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'move_down_subscription_plan', 'post_id' => $post_id ) ), 'pms_subscription_plan_nonce' ) ) . '" class="add-new-h2 pms-subscription-plan-order-move-down" title="' . esc_html__( 'Move Subscription Plan Down', 'paid-member-subscriptions' ) . '">&darr;</a>';
            else
                echo '<span class="pms-subscription-plan-order-move-down pms-subscription-plan-order-placeholder ' . ( $parent_id != false ? 'move-up' : '' ) . '"><span class="pms-inner">&darr;</span></span>';

        }

        // Information shown in the status column
		if( $column == 'status' ) {

			$subscription_plan_status_dot = apply_filters( 'pms_list_table_subscription_plans_show_status_dot', '<span class="pms-status-dot ' . esc_attr( $subscription_plan->status ) . '"></span>' );

			if( $subscription_plan->is_active() )
				echo wp_kses_post( $subscription_plan_status_dot ) . '<span>' . esc_html__( 'Active', 'paid-member-subscriptions' ) . '</span>';
			else
				echo wp_kses_post( $subscription_plan_status_dot ) . '<span>' . esc_html__( 'Inactive', 'paid-member-subscriptions' ) . '</span>';
		}

        // Information shown in the price column
        if( $column == 'price' ) {
            $duration = '';
            if( $subscription_plan->duration > 0 && !$subscription_plan->is_fixed_period_membership() ) {

                switch ($subscription_plan->duration_unit) {
                    case 'day':
                        $duration = sprintf( _n( '%s Day', '%s Days', $subscription_plan->duration, 'paid-member-subscriptions' ), $subscription_plan->duration );
                        break;
                    case 'week':
                        $duration = sprintf( _n( '%s Week', '%s Weeks', $subscription_plan->duration, 'paid-member-subscriptions' ), $subscription_plan->duration );
                        break;
                    case 'month':
                        $duration = sprintf( _n( '%s Month', '%s Months', $subscription_plan->duration, 'paid-member-subscriptions' ), $subscription_plan->duration );
                        break;
                    case 'year':
                        $duration = sprintf( _n( '%s Year', '%s Years', $subscription_plan->duration, 'paid-member-subscriptions' ), $subscription_plan->duration );
                        break;
                }
                $duration = '<span class="pms-divider"> / </span>' . $duration;

                if ( $subscription_plan->has_installments() )
                    $duration .= ' for '. $subscription_plan->number_of_payments . ' cycles';
            }
            if( $subscription_plan->is_fixed_period_membership() ){

                $duration = '<span class="pms-divider">' . ' ' . esc_html__( 'until', 'paid-member-subscriptions' ) . ' ' . '</span>' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $subscription_plan->get_expiration_date() ) ) );

            }

            if( $subscription_plan->price == 0 )
                $price_output = esc_html__( 'Free', 'paid-member-subscriptions' );

            else
                $price_output = pms_format_price( $subscription_plan->price, pms_get_active_currency() ) . $duration;

            if( $subscription_plan->type == 'group' ){

                $seats = get_post_meta( $subscription_plan->id, 'pms_subscription_plan_seats', true );

                $price_output .= ' ' . sprintf( esc_attr( _n( 'for %d seat', 'for %d seats', $seats, 'paid-member-subscriptions' ) ), esc_html( $seats ) );

            }

            /**
             * Filter the price column of a plan in the subscription plans list table
             *
             * - holds the plan's price and, for a group plan, the number of members it covers
             * - the duration is passed on its own so a plan priced on something other than its flat price can rebuild the whole label without composing the duration again
             *
             * @param string $price_output
             * @param int    $subscription_plan_id
             * @param string $duration
             *
             */
            echo wp_kses_post( apply_filters( 'pms_list_table_subscription_plans_column_price_output', $price_output, $subscription_plan->id, $duration ) );

        }

        // Information shown in the sign-up fee column
        if( $column == 'sign_up_fee' ) {

            if( $subscription_plan->sign_up_fee == 0 )
                echo '-';
            else
                echo esc_html( pms_format_price( $subscription_plan->sign_up_fee, pms_get_active_currency() ) );

        }

        // Information shown in the free trial column
        if( $column == 'free_trial' ) {

            if( $subscription_plan->trial_duration == 0 )
                echo '-';

            else {

                $duration = '';

                switch ( $subscription_plan->trial_duration_unit ) {
                    case 'day':
                        $duration = sprintf( _n( '%s Day', '%s Days', $subscription_plan->trial_duration, 'paid-member-subscriptions' ), $subscription_plan->trial_duration );
                        break;
                    case 'week':
                        $duration = sprintf( _n( '%s Week', '%s Weeks', $subscription_plan->trial_duration, 'paid-member-subscriptions' ), $subscription_plan->trial_duration );
                        break;
                    case 'month':
                        $duration = sprintf( _n( '%s Month', '%s Months', $subscription_plan->trial_duration, 'paid-member-subscriptions' ), $subscription_plan->trial_duration );
                        break;
                    case 'year':
                        $duration = sprintf( _n( '%s Year', '%s Years', $subscription_plan->trial_duration, 'paid-member-subscriptions' ), $subscription_plan->trial_duration );
                        break;
                }

                echo esc_html( $duration );

            }

        }

        // Information shown in the id column
        if( $column == 'id' ) {
            echo esc_html( $post_id );
        }

	}

    public function creating_pricing_table_page(){

            if( !isset( $_POST['pms_nonce'] ) || !wp_verify_nonce( sanitize_text_field( $_POST['pms_nonce'] ), 'pms_create_pricing_table_page' ) || !current_user_can( 'manage_options' ) ){
                wp_redirect( admin_url( 'edit.php?post_type=pms-subscription' ) );
                die();
            }

            $target_plan_keys = array(
                'pms-silver-subscription-plan',
                'pms-gold-subscription-plan',
                'pms-platinum-subscription-plan'
            );

            $id_subscriptions = array();

            foreach( $target_plan_keys as $key ){
                if( isset( $_POST[ $key ] ) && !empty( $_POST[ $key ] ) )
                    $id_subscriptions[] = absint( $_POST[ $key ] );
            }

            $pricing_page_style = isset( $_POST['pms_general_settings']['pricing_tables_design'] ) ? sanitize_text_field( $_POST['pms_general_settings']['pricing_tables_design'] ) : '';

            if( empty( $id_subscriptions ) ){
                wp_redirect( admin_url( 'edit.php?post_type=pms-subscription' ) );
                die();
            }

            $count_ids = count( $id_subscriptions );
            if( $count_ids == 3 ){
                $pattern = pms_patterns_pricing_table();
            }
            elseif ( $count_ids == 2 ){
                $pattern = pms_patterns_pricing_table_two_columns();
            }
            elseif ( $count_ids == 1 ){
                $pattern = pms_patterns_pricing_table_one_column();
            }

            $number_of_subscriptions = 0;

            foreach ( $id_subscriptions as $id ) {

                $plan = pms_get_subscription_plan( $id );

                if ( $number_of_subscriptions == 0 ) {

                    $pattern = str_replace('Silver', $plan->name, $pattern );
                    $currency = pms_get_currency_symbol( pms_get_active_currency() );
                    $price = $plan->price . $currency;
                    $duration = pms_get_output_subscription_plan_duration( $plan );
                    $details = $price . '<span class="pms-pt-text-duration">' . $duration . "</span>";
                    $pattern = str_replace( '29$ / month', $details, $pattern );

                    if( $plan->trial_duration !== 0){

                        $duration_free_trial = pms_get_output_subscription_plan_trial( $plan );
                        $pattern = str_replace( 'Free trial 1', $duration_free_trial, $pattern );
                    }
                    else{
                        $pattern = preg_replace('/<!--\s*wp:paragraph\s*{"align":"center"}\s*-->\s*<span class="pms-pt-text-price pms-pt-text-duration">Free trial 1<\/span>\s*<!--\s*\/wp:paragraph\s*-->/s', '', $pattern);
                    }

                    if( $plan->sign_up_fee !== 0){

                        $price_sign_up_fee = pms_get_output_subscription_plan_sign_up_fee( $plan );
                        $pattern = str_replace( 'Sign-up fee 1', $price_sign_up_fee, $pattern );
                    }
                    else{
                        $pattern = preg_replace('/<!--\s*wp:paragraph\s*{"align":"center"}\s*-->\s*<span class="pms-pt-text-price pms-pt-text-duration">Sign-up fee 1<\/span>\s*<!--\s*\/wp:paragraph\s*-->/s', '', $pattern);
                    }

                    $url = 'href="' .  esc_url( add_query_arg( 'subscription_plan', $plan->id, get_permalink( pms_get_page('register') ) ) ) .'&single_plan=yes"';
                    $pattern = str_replace( 'href="1"', $url, $pattern );

                } elseif ( $number_of_subscriptions == 1 ) {

                    $pattern = str_replace( 'Gold', $plan->name, $pattern );
                    $currency = pms_get_currency_symbol( pms_get_active_currency() );
                    $price = $plan->price . $currency;
                    $duration = pms_get_output_subscription_plan_duration( $plan );
                    $details = $price . '<span class="pms-pt-text-duration">' . $duration . "</span>";
                    $pattern = str_replace( '49$ / month', $details, $pattern );

                    if( $plan->trial_duration !== 0){

                        $duration_free_trial = pms_get_output_subscription_plan_trial( $plan );
                        $pattern = str_replace( 'Free trial 2', $duration_free_trial, $pattern );
                    }
                    else{
                        $pattern = preg_replace('/<!--\s*wp:paragraph\s*{"align":"center"}\s*-->\s*<span class="pms-pt-text-price pms-pt-text-duration">Free trial 2<\/span>\s*<!--\s*\/wp:paragraph\s*-->/s', '', $pattern);
                    }

                    if( $plan->sign_up_fee !== 0){

                        $price_sign_up_fee = pms_get_output_subscription_plan_sign_up_fee( $plan );
                        $pattern = str_replace( 'Sign-up fee 2', $price_sign_up_fee, $pattern );
                    }
                    else{
                        $pattern = preg_replace('/<!--\s*wp:paragraph\s*{"align":"center"}\s*-->\s*<span class="pms-pt-text-price pms-pt-text-duration">Sign-up fee 2<\/span>\s*<!--\s*\/wp:paragraph\s*-->/s', '', $pattern);
                    }

                    $url = 'href="' .  esc_url( add_query_arg( 'subscription_plan', $plan->id, get_permalink( pms_get_page('register') ) ) ) .'&single_plan=yes"';
                    $pattern = str_replace( 'href="2"', $url, $pattern );

                } elseif ( $number_of_subscriptions == 2 ) {

                    $pattern = str_replace( 'Platinum', $plan->name, $pattern );
                    $currency = pms_get_currency_symbol( pms_get_active_currency() );
                    $price = $plan->price . $currency;
                    $duration = pms_get_output_subscription_plan_duration( $plan );
                    $details = $price . '<span class="pms-pt-text-duration">' . $duration . "</span>";
                    $pattern = str_replace( '89$ / month', $details, $pattern );

                    if( $plan->trial_duration !== 0){

                        $duration_free_trial = pms_get_output_subscription_plan_trial( $plan );
                        $pattern = str_replace( 'Free trial 3', $duration_free_trial, $pattern );
                    }
                    else{
                        $pattern = preg_replace('/<!--\s*wp:paragraph\s*{"align":"center"}\s*-->\s*<span class="pms-pt-text-price pms-pt-text-duration">Free trial 3<\/span>\s*<!--\s*\/wp:paragraph\s*-->/s', '', $pattern);
                    }

                    if( $plan->sign_up_fee !== 0){

                        $price_sign_up_fee = pms_get_output_subscription_plan_sign_up_fee( $plan );
                        $pattern = str_replace( 'Sign-up fee 3', $price_sign_up_fee, $pattern );
                    }
                    else{
                        $pattern = preg_replace('/<!--\s*wp:paragraph\s*{"align":"center"}\s*-->\s*<span class="pms-pt-text-price pms-pt-text-duration">Sign-up fee 3<\/span>\s*<!--\s*\/wp:paragraph\s*-->/s', '', $pattern);
                    }

                    $url = 'href="' .  esc_url( add_query_arg( 'subscription_plan', $plan->id, get_permalink( pms_get_page('register') ) ) ) .'&single_plan=yes"';
                    $pattern = str_replace( 'href="3"', $url, $pattern );

                }
                $number_of_subscriptions++;
            }

            $new_post = array(
                'post_title'   => 'Pricing',
                'post_content' => $pattern,
                'post_type'    => 'page',
                'post_status'  => 'publish',
                'post_author'  => 1
            );

            $id_page = wp_insert_post( $new_post );
            $link_page = htmlspecialchars_decode( esc_url( add_query_arg( array( 'post' => $id_page, 'action' => 'edit' ), admin_url( 'post.php' ) ) ) );

            update_option('pms_create_pricing_page_complete', 'pricing_page_exist');
            if( empty( $pricing_page_style )){
                $pricing_page_style = 'pricing-table-style-default';
            }

            add_metadata( 'post', $id_page, 'pms_pricing_page_design', $pricing_page_style );

            wp_redirect( $link_page );
            exit;
    }

    public function update_pricing_table_page(){

        $post_id = isset( $_POST['post_id'] ) ? sanitize_text_field( $_POST['post_id'] ) : '';

        if( !isset( $_POST['pms_nonce'] ) || !wp_verify_nonce( sanitize_text_field( $_POST['pms_nonce'] ), 'pms_update_pricing_table_style' ) || !current_user_can( 'manage_options' ) || empty( $post_id ) ){
            $link_page = htmlspecialchars_decode( esc_url( add_query_arg( array( 'post' => $post_id, 'action' => 'edit' ), admin_url( 'post.php' ) ) ) );
            wp_redirect( $link_page );
            die();
        }

        $selected_design = isset( $_POST['pms_general_settings']['pricing_tables_design'] ) ? sanitize_text_field( $_POST['pms_general_settings']['pricing_tables_design'] ): 'pricing-table-style-default';

        update_post_meta( $post_id, 'pms_pricing_page_design', $selected_design );

        $link_page = htmlspecialchars_decode( esc_url( add_query_arg( array( 'post' => $post_id, 'action' => 'edit' ), admin_url( 'post.php' ) ) ) );
        wp_redirect( $link_page );
        die();
    }

    public function admin_footer_add_create_pricing_page_button(){
        global $pagenow;
        $post_id = isset( $_POST['post_id'] ) ? sanitize_text_field( $_POST['post_id'] ) : '';
        $post_content = get_post_field( 'post_content', $post_id );
        $logo = PMS_PLUGIN_DIR_URL.'assets/images/pms-wp-menu-icon-white.svg';

        if( $pagenow === 'edit.php' && isset( $_GET['post_type'] ) && $_GET['post_type'] === 'pms-subscription' ) {
            echo '<div id="pms-create-pricing-page-button-wrapper">';
            echo '<a class="add-new-h2 page-title-action" id="pms-popup1" href="#" style="margin-left:10px;">' . esc_html__('Create Pricing Page', 'paid-member-subscriptions') . '</a>';
            echo '</div>';
            pms_output_modal_create_pricing_page();
        }

        if( $pagenow === 'post.php' && isset( $_GET['action'] ) && $_GET['action'] === 'edit' && $this->pms_has_pricing_table( $post_content ) ) {
            echo '<div id="pms-create-pricing-page-style-wrapper" style="display: none;">';
            echo '<a class="button button-primary" id="pms-popup-style" href="#"><img class="pms-icon-style" src="' . esc_html( $logo ) . '">' . esc_html__('Pricing Page Style', 'paid-member-subscriptions') . '</a>';
            echo '</div>';
            pms_output_modal_style_pricing_page();
        }
    }

    public function pms_has_pricing_table( $content ){

        return strpos( $content, 'pms-pt-gap' ) !== false ? true : false;
    }


    /*
     * Remove bulk actions
     *
     */
    public function remove_bulk_actions( $actions ) {

        // Remove unneeded actions
        unset( $actions['trash'] );

        return $actions;

    }


    /*
     * Returns an array with custom bulk actions for subscription plans
     *
     */
    public function get_custom_bulk_action() {

        return apply_filters( 'get_custom_bulk_actions_' . $this->post_type, array(
            'activate'   => esc_html__( 'Activate', 'paid-member-subscriptions' ),
            'deactivate' => esc_html__( 'Deactivate', 'paid-member-subscriptions' )
        ));

    }


    /*
     * Add bulk actions
     */
    public function add_bulk_actions() {

        global $post_type;

        if( $post_type == $this->post_type ) {

            echo '<script type="text/javascript">';
                echo 'jQuery(document).ready(function(){';

                    echo 'jQuery("#bulk-action-selector-top option[value=edit]").remove();';
                    echo 'jQuery("#bulk-action-selector-bottom option[value=edit]").remove();';

                    foreach( $this->get_custom_bulk_action() as $action_slug => $action_name ) {
                        echo 'jQuery("<option>").val("' . esc_attr( $action_slug ) . '").text("' . esc_html( $action_name ) . '").appendTo("#bulk-action-selector-top");';
                        echo 'jQuery("<option>").val("' . esc_attr( $action_slug ) . '").text("' . esc_html( $action_name ) . '").appendTo("#bulk-action-selector-bottom");';
                    }

                echo '});';
            echo '</script>';

        }

    }


    /*
     * Process what happens when a custom bulk action is applied.
     *
     */
    public function process_custom_bulk_actions() {

        if( !isset( $_REQUEST['post_type'] ) || sanitize_text_field( $_REQUEST['post_type'] ) != $this->post_type )
            return;

        if( ! pms_current_user_can_access_area( 'pms-subscription' ) )
            return;

        // Verify nonce before anything
        if( !isset( $_REQUEST['_wpnonce'] ) || !wp_verify_nonce( sanitize_text_field( $_REQUEST['_wpnonce'] ), 'bulk-posts' ) )
            return;

        if( !isset( $_REQUEST['action'] ) && !isset( $_REQUEST['action2'] ) )
            return;

        $action = ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] != '-1' ? sanitize_text_field( $_REQUEST['action'] ) : sanitize_text_field( $_REQUEST['action2'] ) );

        // Return if the action is not one of our custom actions
        if( !array_key_exists( $action, $this->get_custom_bulk_action() ) )
            return;


        // Subscription plan activation and deactivation
        if( $action == 'activate' || $action == 'deactivate' ) {

            if( isset( $_REQUEST['post'] ) && !empty( $_REQUEST['post'] ) ) {
                $subscription_plan_ids = array_map( 'sanitize_text_field', $_REQUEST['post'] );

                foreach( $subscription_plan_ids as $subscription_plan_id ) {

                    if( $action == 'activate' )
                        PMS_Subscription_Plan::activate( (int)$subscription_plan_id );

                    if( $action == 'deactivate' )
                        PMS_Subscription_Plan::deactivate( (int)$subscription_plan_id );

                }

                // Redirect arguments
                $redirect_args = array(
                    'post_type'       => $this->post_type,
                    'bulk_' . $action => count( $_REQUEST['post'] ),
                    'paged'           => ( isset($_REQUEST['paged']) ? (int)$_REQUEST['paged'] : 1 )
                );

                $redirect = add_query_arg( $redirect_args, pms_get_current_page_url( true ) );

                wp_redirect( esc_url_raw( $redirect ) );
                exit;

            }

        }


    }


    /*
     * Add the subscription status field in the submit box
     *
     */
    public function submitbox_add_status_field() {
        global $post_type;
        global $post;

        if ( $post_type != $this->post_type ) {
            return false;
        }

        $subscription_plan = pms_get_subscription_plan( $post );

        pms_render_submitbox_status_field( array(
            'wrapper_class'  => 'pms-subscription-submitbox-status',
            'label'          => __( 'Subscription Status', 'paid-member-subscriptions' ),
            'select_id'      => 'pms-subscription-sidebar-status',
            'select_name'    => 'pms_subscription_plan_status',
            'current_status' => $subscription_plan->status,
            'description'    => __( 'Only active subscription plans will be displayed to the user.', 'paid-member-subscriptions' ),
            'after_callback' => function() use ( $subscription_plan ) {
                do_action( 'pms_view_meta_box_subscription_details_status_bottom', $subscription_plan->id );
            },
        ) );
    }


    /*
     * Add a delete button where the move to trash was
     *
     */
    public function submitbox_add_delete_button() {
        global $post_type;
        global $post;

        if( $post_type != $this->post_type )
            return false;

        echo '<div id="pms-delete-action">';
            echo '<a class="submitdelete deletion" onclick="return confirm( \'' . esc_html__( "Are you sure you want to delete this Subscription Plan?", "paid-member-subscriptions" ) . ' \' )" href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'delete_subscription_plan', 'post_id' => $post->ID, 'deleted' => 1 ), admin_url( 'edit.php?post_type=' . $this->post_type ) ), 'pms_subscription_plan_nonce' ) ) . '">' . esc_html__( 'Delete Plan', 'paid-member-subscriptions' ) . '</a>';
        echo '</div>';

    }


    /*
     * Add "Add Upgrade" and "Add downgrade" on the edit page of a Subscription Plan
     * This buttons will be moved from the submit box next to the default "Add New" button from
     * the page title
     *
     */
    public function submitbox_add_upgrade_downgrade_buttons() {
        global $post_type;
        global $post;

        if( $post_type != $this->post_type )
            return false;

        if( !isset( $_GET['post'] ) )
            return false;

        echo '<div id="pms-upgrade-downgrade-buttons-wrapper">';
            echo '<a class="add-new-h2 page-title-action" href="' . esc_url( add_query_arg( array( 'post_type' => $this->post_type, 'plan_id' => $post->ID, 'pms-action' => 'add_upgrade' ), admin_url('post-new.php') ) ) . '">' . esc_html__( 'Add Upgrade', 'paid-member-subscriptions' ) . '</a>';
            echo '<a class="add-new-h2 page-title-action" href="' . esc_url( add_query_arg( array( 'post_type' => $this->post_type, 'plan_id' => $post->ID, 'pms-action' => 'add_downgrade' ), admin_url('post-new.php') ) ) . '">' . esc_html__( 'Add Downgrade', 'paid-member-subscriptions' ) . '</a>';
        echo '</div>';

    }


    /**
     * Adds a subtitle to the "Add New Subscription Plan" page so that the user
     * know what the new subscription plan will be
     *
     */
    public function add_upgrade_downgrade_subtitle() {

        if( empty( $_GET['post_type'] ) )
            return;

        if( $_GET['post_type'] != $this->post_type )
            return;

        if( empty( $_GET['pms-action'] ) || !isset( $_GET['plan_id'] ) )
            return;

        if( $_GET['pms-action'] != 'add_downgrade' && $_GET['pms-action'] != 'add_upgrade' )
            return;

        // Set the subscription plan
        $subscription_plan = pms_get_subscription_plan( (int)$_GET['plan_id'] );

        // Set the string in case of downgrade / upgrade
        $action = ( $_GET['pms-action'] == 'add_downgrade' ? __( 'a downgrade', 'paid-member-subscriptions' ) : __( 'an upgrade', 'paid-member-subscriptions' ) );

        echo '<div id="pms-add-subscription-plan-subtitle">' . sprintf( esc_html__( 'This will be %s for the %s subscription plan.', 'paid-member-subscriptions' ), '<strong>' . esc_html( $action ) . '</strong>', '<strong>' . esc_html( $subscription_plan->name ) . '</strong>' ) . '</div>';

    }


    /**
     * Add the upgrade/downgrade action in the HTML so that we can handle it on post save
     *
     */
    public function add_upgrade_downgrade_action() {

        if( empty( $_GET['pms-action'] ) )
            return;

        if( empty( $_GET['plan_id'] ) )
            return;

        $pms_action = sanitize_text_field( $_GET['pms-action'] );
        $plan_id    = (int)$_GET['plan_id'];

        echo '<input type="hidden" name="pms-action" value="' . esc_attr( $pms_action ) . '" />';
        echo '<input type="hidden" name="pms-subscription-plan-id" value="' . esc_attr( $plan_id ) . '" />';

    }


    /**
     * Add the edit subscription plan action in the HTML so that we can handle it on post save
     *
     */
    public function add_edit_subscription_plan_action() {

        if( empty( $_GET['action'] ) || $_GET['action'] != 'edit' )
            return;

        if( empty( $_GET['post'] ) )
            return;

        $post_type = get_post_type( (int)$_GET['post'] );

        if( $this->post_type != $post_type )
            return;

        $pms_action = 'edit-subscription-plan';

        echo '<input type="hidden" name="pms-action" value="' . esc_attr( $pms_action ) . '" />';

    }


    /*
     * Method to change the default title text "Enter title here"
     *
     */
    public function change_title_prompt_text( $input ) {
        global $post_type;

        if( $post_type == $this->post_type ) {
            return esc_html__( 'Enter Subscription Plan name here', 'paid-member-subscriptions' );
        }

        return $input;
    }


    /**
     * If for some reason this subscription plan is saved as a draft
     * re-save it as an auto draft, because we don't want it to appear
     * in our subscriptions plan list
     *
     */
    public function save_as_auto_draft( $post_id ) {

        $current_post = get_post( $post_id );

        if( is_null( $current_post ) )
            return;

        if( $current_post->post_type != $this->post_type )
            return;

        if( $current_post->post_status != 'draft' )
            return;

        // Remove action hook and add it again later for no infinite loop
        remove_action( 'save_post', array( $this, 'save_as_auto_draft' ) );

        $_args = array(
            'ID'          => $post_id,
            'post_status' => 'auto-draft'
        );

        wp_update_post( $_args );

        // Add action hook again for no infinite loop
        add_action( 'save_post', array( $this, 'save_as_auto_draft' ) );

    }

    /*
     * Method that adds a new subscription plan as a downgrade
     *
     */
    public function add_new_subscription_plan( $post_id ) {

        $current_post = get_post( $post_id );

        if( is_null( $current_post ) )
            return;

        if( $current_post->post_type != $this->post_type )
            return;

        if( $current_post->post_status == 'auto-draft' )
            return;

        if( $current_post->post_status == 'draft' )
            return;


        $pms_action = apply_filters( 'pms_action_add_new_subscription_plan', ( isset( $_POST['pms-action'] ) ? sanitize_text_field( $_POST['pms-action'] ) : ( isset( $_GET['pms-action'] ) ? sanitize_text_field( $_GET['pms-action'] ) : '' ) ) );

        // Exit if there is a custom action going on
        if( ! empty( $pms_action ) )
            return;

        // Remove action hook and add it again later for no infinite loop
        remove_action( 'save_post', array( $this, 'add_new_subscription_plan' ) );

        $top_level_plan = get_posts( array( 'post_type' => $this->post_type, 'numberposts' => 1, 'post_status' => 'any', 'post_parent' => 0, 'order' => 'ASC' ) );

        // Exit if we don't have any subscription plans yet
        if( empty($top_level_plan) )
            return;

        $top_level_plan = $top_level_plan[0];

        // Get all children and add the top level plan at the beginning of the array
        $children_plans = get_page_children( $top_level_plan->ID, get_posts( array( 'post_type' => $this->post_type, 'numberposts' => -1, 'post_status' => 'any' )) );
        array_unshift( $children_plans, $top_level_plan );

        $last_child_plan = $children_plans[ count($children_plans) - 1 ];

        $args = array(
            'ID'          => $post_id,
            'post_parent' => $last_child_plan->ID
        );

        wp_update_post( $args );

    }


    /**
     * Method that adds a new subscription plan downgrade
     *
     */
    public function add_new_downgrade( $post_id ) {

        $current_post = get_post( $post_id );

        if( is_null( $current_post ) )
            return;

        if( $current_post->post_type != $this->post_type )
            return;

        if( $current_post->post_status == 'auto-draft' )
            return;

        // Check if plan is added by add downgrade row actions
        $pms_action = isset( $_POST['pms-action'] ) ? sanitize_text_field( $_POST['pms-action'] ) : '';

        if( $pms_action != 'add_downgrade' )
            return;

        if( empty( $_POST['pms-subscription-plan-id'] ) )
            return;

        // Remove action hook so that it gets executed only one time
        remove_action( 'save_post', array( $this, 'add_new_downgrade' ) );

        // The post for which the curent post will become a downgrade
        $reference_post_id = (int)$_POST['pms-subscription-plan-id'];

        /**
         * Get the current subscription plan downgrade and make it as a downgrade to the
         * current post
         *
         */
        $current_downgrade_args = array(
            'post_type'   => 'pms-subscription',
            'post_parent' => $reference_post_id,
            'post_status' => 'any'
        );

        $current_downgrades   = get_posts( $current_downgrade_args );
        $current_downgrade_id = isset( $current_downgrades[0]->ID ) ? $current_downgrades[0]->ID : 0;

        if( ! empty( $current_downgrade_id ) ) {

            $current_downgrade_update_args = array(
                'ID'          => $current_downgrade_id,
                'post_parent' => $post_id
            );

            wp_update_post( $current_downgrade_update_args );

        }

        /**
         * Make the reference post the parent of the curent post
         *
         */
        $current_post_update_args = array(
            'ID'          => $post_id,
            'post_parent' => $reference_post_id
        );

        wp_update_post( $current_post_update_args );

    }


    /**
     * Method that adds a new subscription plan upgrade
     *
     */
    public function add_new_upgrade( $post_id ) {

        $current_post = get_post( $post_id );

        if( is_null( $current_post ) )
            return;

        if( $current_post->post_type != $this->post_type )
            return;

        if( $current_post->post_status == 'auto-draft' )
            return;

        // Check if plan is added by add upgrade row actions
        $pms_action = isset( $_POST['pms-action'] ) ? sanitize_text_field( $_POST['pms-action'] ) : '';

        if( $pms_action != 'add_upgrade' )
            return;

        if( empty( $_POST['pms-subscription-plan-id'] ) )
            return;

        // Remove action hook so that it gets executed only one time
        remove_action( 'save_post', array( $this, 'add_new_upgrade' ) );

        // The post for which the curent post will become an upgrade
        $reference_post_id = (int)$_POST['pms-subscription-plan-id'];
        $reference_post    = get_post( $reference_post_id );

        if( ! empty( $reference_post->post_parent ) ) {

            $current_post_update_args = array(
                'ID'          => $post_id,
                'post_parent' => $reference_post->post_parent
            );

            wp_update_post( $current_post_update_args );

        }

        $reference_post_update_args = array(
            'ID'          => $reference_post->ID,
            'post_parent' => $post_id
        );

        wp_update_post( $reference_post_update_args );

        do_action( 'pms_after_add_upgrade', $post_id, $reference_post_id );

    }


    /*
     * Method that sets the top parent id of each subscription plan as meta data
     *
     */
    public function update_subscription_plan_top_parent( $post_id ) {

        global $post_type;

        if( $post_type != $this->post_type )
            return;

        $subscription_plans = pms_get_subscription_plans( false );

        foreach( $subscription_plans as $subscription_plan ) {

            $top_parent_id = pms_get_subscription_plans_group_parent_id( $subscription_plan->id );

            update_post_meta( $subscription_plan->id, 'pms_subscription_plan_top_parent', $top_parent_id );

        }

    }


	/*
     * Method that set custom updated messages
     *
     */
	function set_custom_messages( $messages ) {

		global $post;

		$messages['pms-subscription'] = array(
			0  => 	'',
			1  => 	esc_html__( 'Subscription Plan updated.', 'paid-member-subscriptions' ),
			2  => 	esc_html__( 'Custom field updated.', 'paid-member-subscriptions' ),
			3  => 	esc_html__( 'Custom field deleted.', 'paid-member-subscriptions' ),
			4  => 	esc_html__( 'Subscription Plan updated.', 'paid-member-subscriptions' ),
			5  => 	isset( $_GET['revision'] ) ? sprintf( esc_html__( 'Subscription Plan restored to revision from %s', 'paid-member-subscriptions' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => 	esc_html__( 'Subscription Plan saved.', 'paid-member-subscriptions' ),
			7  => 	esc_html__( 'Subscription Plan saved.', 'paid-member-subscriptions' ),
			8  => 	esc_html__( 'Subscription Plan submitted.', 'paid-member-subscriptions' ),
			9  => 	sprintf( esc_html__( 'Subscription Plan scheduled for: <strong>%1$s</strong>.', 'paid-member-subscriptions' ), date_i18n( esc_html__( 'M j, Y @ G:i', 'paid-member-subscriptions' ), strtotime( $post->post_date ) ) ),
			10 =>	esc_html__( 'Subscription Plan draft updated.', 'paid-member-subscriptions' ),
		);

		return $messages;

	}


	/*
     * Method that set custom bulk updated messages
     *
     */
	public function set_bulk_custom_messages( $bulk_messages, $bulk_counts ) {

		$bulk_messages['pms-subscription'] = array(
			'updated'   => _n( '%s Subscription Plan updated.', '%s Subscription Plans updated.', $bulk_counts['updated'], 'paid-member-subscriptions' ),
			'locked'    => _n( '%s Subscription Plan not updated, somebody is editing it.', '%s Subscription Plans not updated, somebody is editing them.', $bulk_counts['locked'], 'paid-member-subscriptions' ),
			'deleted'   => _n( '%s Subscription Plan permanently deleted.', '%s Subscription Plans permanently deleted.', $bulk_counts['deleted'], 'paid-member-subscriptions' ),
			'trashed'   => _n( '%s Subscription Plan moved to the Trash.', '%s Subscription Plans moved to the Trash.', $bulk_counts['trashed'], 'paid-member-subscriptions' ),
			'untrashed' => _n( '%s Subscription Plan restored from the Trash.', '%s Subscription Plans restored from the Trash.', $bulk_counts['untrashed'], 'paid-member-subscriptions' ),
		);

		return $bulk_messages;

	}


    /*
     * Add the query args we wish WP to remove from the URL
     *
     * @param array $query_args     - the arguments WP will remove by default
     *
     * @return array $query_args    - the argumnets WP will remove by default alongside the ones we wish to remove for this CPT
     *
     */
    public function removable_query_args( $query_args ) {

        global $post_type;

        if( $post_type != $this->post_type )
            return $query_args;

        $new_query_args = array();

        foreach( array_keys( $this->get_custom_bulk_action() ) as $bulk_action ) {
            $new_query_args[] = 'bulk_' . $bulk_action;
        }

        $query_args = array_merge( $query_args, $new_query_args );

        return $query_args;

    }

    /*
     * Display admin notices
     *
     */
    public function admin_notices() {

        global $post_type;

        if( $post_type != $this->post_type )
            return;

        $message = '';

        if( isset( $_REQUEST['bulk_activate'] ) && $_REQUEST['bulk_activate'] == true )
            $message = sprintf( _n( '%d subscription plan has been successfully activated', '%d subscription plans have been successfully activated', (int)$_REQUEST['bulk_activate'], 'paid-member-subscriptions' ), (int)$_REQUEST['bulk_activate'] );

        if( isset( $_REQUEST['bulk_deactivate'] ) && $_REQUEST['bulk_deactivate'] == true )
            $message = sprintf( _n( '%d subscription plan has been successfully deactivated', '%d subscription plans have been successfully deactivated', (int)$_REQUEST['bulk_deactivate'], 'paid-member-subscriptions' ), (int)$_REQUEST['bulk_deactivate'] );

        if( !empty( $message ) )
            echo '<div class="updated"><p>' . esc_html( $message ) . '</p></div>';

        $messages = $this->get_admin_notices();

        if( isset( $_GET['pms-subscription-error'] ) && isset( $messages[ (int)$_GET['pms-subscription-error'] ] ) ){
            echo '<div class="error pms-admin-notice">';
                echo '<p>' . esc_html( $messages[ (int)$_GET['pms-subscription-error'] ] ) . '</p>';
            echo '</div>';
        }

    }


}

function pms_init_subscription_plans_cpt() {

    /*
    * Initialize the subscription custom post type
    *
    */

    $args = array(
        'show_ui'            => true,
        'show_in_menu'       => 'paid-member-subscriptions',
        'query_var'          => true,
        'capability_type'    => 'post',
        'menu_position'      => null,
        'supports'           => array( 'title' ),
        'hierarchical'		 => true
    );

    $pms_cpt_subscribtion = new PMS_Custom_Post_Type_Subscription( 'pms-subscription', esc_html__( 'Subscription Plan', 'paid-member-subscriptions' ), esc_html__( 'Subscription Plans', 'paid-member-subscriptions' ), $args );
    $pms_cpt_subscribtion->init();

}
add_action( 'init', 'pms_init_subscription_plans_cpt', 1 );

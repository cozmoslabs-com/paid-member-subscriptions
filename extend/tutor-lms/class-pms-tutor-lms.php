<?php

/** PMS - TutorLMS integration */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

class PMS_IN_TutorLMS {

    public function __construct() {

        define( 'PMS_IN_TUTOR_LMS_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
        define( 'PMS_IN_TUTOR_LMS_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );

        $this->init();

    }

    private function init() {

        // Add PMS Settings TutorLMS Tab
        add_action( 'pms-settings-page_tabs', array( $this, 'add_settings_tab' ) );

        // Add PMS to TutorLMS monetization engines list
        add_action( 'tutor_monetization_options', array( $this, 'add_monetization_option' ));

        $monetization_engine = $this->get_monetization_engine();

        if ( $monetization_engine === 'pms' ) {

            // Add PMS Settings TutorLMS Tab settings
            add_action( 'pms_settings_tab_content', array( $this, 'add_settings_tab_content' ), 20, 3 );
            add_action( 'admin_init', array( $this, 'save_settings' ) );

            // Add TutorLMS settings to Subscription Plan settings
            add_action( 'pms_view_meta_box_subscription_details_bottom', array( $this, 'add_subscription_plan_settings' ) );
            add_action( 'pms_save_meta_box_pms-subscription', array( $this, 'save_subscription_plan_settings' ), 10, 2 );

            // Add the PMS subscribe box to single Course view
            add_filter( 'tutor/course/single/entry-box/free', array( $this, 'course_subscribe_box' ), 10, 2 );
            add_filter( 'tutor/course/single/entry-box/is_enrolled', array( $this, 'course_subscribe_box' ), 10, 2 );

            // Disable "message" type Content Restriction for Courses (it only restricts the Course description, the access is restricted by TutorLMS)
            add_filter( 'pms_restriction_message_non_members', array( $this, 'disable_course_message_content_restriction' ), 100, 4 );
            add_filter( 'pms_restriction_message_logged_out', array( $this, 'disable_course_message_content_restriction' ), 100, 4 );

            // Disable course enroll from TutorLMS archive view
            add_filter( 'tutor_course_restrict_new_entry', array( $this, 'remove_course_loop_enroll_class' ) );

            // Preselect the recommended Subscription Plan on Registration Form
            add_filter( 'pms_register_form_selected_subscription_plan', array( $this, 'preselect_recommended_subscription_plan' ), 10, 2 );
        }
        else {
            // Notify the user to enable PMS as TutorLMS Monetization engine
            add_action( 'pms_settings_tab_content', array( $this, 'add_settings_tab_notice' ), 20, 3 );
        }

        // Add required Scripts and Styles
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts_and_styles' ) );

        // Handle new member subscription and existing subscription updates
        add_action( 'pms_member_subscription_insert', array( $this, 'add_member_subscription_categories_meta' ), 10, 2 );
        add_action( 'pms_member_subscription_update', array( $this, 'handle_member_subscription_update' ), 10, 3 );
        add_action( 'pms_member_subscription_before_metadata_delete', array( $this, 'handle_member_subscription_remove' ), 10, 2 );

        // Add Content Restriction meta-box to TutorLMS Course Builder page
        add_action( 'tutor_course_builder_footer', array( $this, 'tutor_course_cr_metabox_output' ) );

        // Handle Content Restriction settings when TutorLMS Course Builder is saved
        add_action( 'tutor_before_course_builder_load', array( $this, 'enqueue_tutor_course_builder_scripts' ) );
        add_action( 'wp_ajax_pms_tutor_course_cr_data_save', array( $this, 'tutor_course_cr_data_save' ) );

    }

    /**
     * Enqueue admin scripts and styles
     *
     */
    public function enqueue_admin_scripts_and_styles( $hook ) {

        if ( ( !isset( $_GET['page'] ) || $_GET['page'] !== 'pms-settings-page' || !isset( $_GET['tab'] ) || $_GET['tab'] !== 'tutor_lms' ) && get_post_type() !== 'pms-subscription' )
            return;

        global $wp_scripts;

        // Try to detect if chosen has already been loaded; We use it for the Subscription list selector
        $found_chosen = false;

        foreach ( $wp_scripts as $wp_script ) {
            if ( !empty( $wp_script['src'] ) && strpos( $wp_script['src'], 'chosen' ) !== false )
                $found_chosen = true;
        }

        if ( !$found_chosen ) {
            wp_enqueue_script( 'pms-chosen', PMS_PLUGIN_DIR_URL . 'assets/libs/chosen/chosen.jquery.min.js', array( 'jquery' ), PMS_VERSION );
            wp_enqueue_style( 'pms-chosen', PMS_PLUGIN_DIR_URL . 'assets/libs/chosen/chosen.css', array(), PMS_VERSION );
        }

        // Back-end scripts and styles
        if ( file_exists( PMS_IN_TUTOR_LMS_PLUGIN_DIR_PATH . 'assets/css/pms-settings-tab-tutor-lms.css' ) )
            wp_enqueue_style( 'pms-settings-tab-tutor-lms-style', PMS_IN_TUTOR_LMS_PLUGIN_DIR_URL . 'assets/css/pms-settings-tab-tutor-lms.css', array(), PMS_VERSION );

        if ( file_exists( PMS_IN_TUTOR_LMS_PLUGIN_DIR_PATH . 'assets/js/pms-settings-tab-tutor-lms.js' ) )
            wp_enqueue_script('pms-settings-tab-tutor-lms-script', PMS_IN_TUTOR_LMS_PLUGIN_DIR_URL . 'assets/js/pms-settings-tab-tutor-lms.js', array('jquery'), PMS_VERSION);

    }

    /**
     * Get the active Tutor LMS monetization engine
     *
     */
    public function get_monetization_engine() {
        return tutor_utils()->get_option( 'monetize_by' );
    }

    /**
     * Add PMS as a Monetization option in Tutor LMS Settings
     *
     * @param array $options The Tutor LMS Monetization options
     */
    public function add_monetization_option( $options ) {

        $options['pms'] = 'Paid Member Subscriptions';

        return $options;

    }

    /**
     * Add tab for Tutor LMS integration under PMS Settings page
     *
     * @param array $pms_tabs The PMS Settings tabs
     * @return mixed
     */
    public function add_settings_tab( $pms_tabs ) {

        $pms_tabs['tutor_lms'] = __( 'Tutor LMS', 'paid-member-subscriptions' );

        return $pms_tabs;

    }

    /**
     * Add content for Tutor LMS tab under PMS Settings page
     *
     */
    public function add_settings_tab_content ( $output, $active_tab, $options ) {

        if ( $active_tab != 'tutor_lms' )
            return $output;


        $registration_url = pms_get_page( 'register', true );

        if ( $registration_url ) {
            ob_start();

            include_once 'views/view-settings-tab-tutor-lms.php';

            $output = ob_get_clean();
        }
        else {
            $output .= '<div class="cozmoslabs-form-subsection-wrapper cozmoslabs-settings">';

                $output .= '<h3 class="cozmoslabs-subsection-title">'. esc_html__( 'Restriction Settings', 'paid-member-subscriptions' ) .'</h3>';

                $output .= '<div class="tutor-lms-settings-notice">';
                    $output .= '<p class="cozmoslabs-description">'. sprintf( esc_html__( '%1$s Registration Page %2$s is not correctly set or missing. ', 'paid-member-subscriptions' ), '<strong>', '</strong>' ) .'</p>';
                    $output .= '<p class="cozmoslabs-description" style="margin-top: 10px;">'. sprintf( esc_html__( 'Go to %1$s Settings --> General --> Membership Pages %2$s and select the required pages accordingly.', 'paid-member-subscriptions' ),  '<a href="'.esc_url( admin_url( 'admin.php?page=pms-settings-page&tab=general#cozmoslabs-subsection-membership-pages' ) ).'">', '</a>' ) .'</p>';
                $output .= '</div>';

            $output .= '</div>';
        }

        return $output;
    }

    /**
     * Add content for Tutor LMS tab under PMS Settings page
     *
     */
    public function add_settings_tab_notice ( $output, $active_tab, $options ) {

        if ( $active_tab == 'tutor_lms' ) {
            $output = '<div class="cozmoslabs-form-subsection-wrapper cozmoslabs-settings">';

                $output .= '<h3 class="cozmoslabs-subsection-title">'. esc_html__( 'Restriction Settings', 'paid-member-subscriptions' ) .'</h3>';

                $output .= '<div class="tutor-lms-settings-notice">';
                    $output .= '<p class="cozmoslabs-description">'. sprintf( esc_html__( '%1$s Paid Member Subscriptions %2$s is not the currently active Monetization Engine for TutorLMS. ', 'paid-member-subscriptions' ), '<strong>', '</strong>' ) .'</p>';
                    $output .= '<p class="cozmoslabs-description" style="margin-top: 10px;">'. sprintf( esc_html__( 'You can easily enable it by navigating to %3$s TutorLMS --> Settings --> Monetization %4$s and selecting %1$s Paid Member Subscriptions %2$s as the eCommerce Engine.', 'paid-member-subscriptions' ), '<strong>', '</strong>', '<a href="'. esc_url( admin_url( 'admin.php?page=tutor_settings&tab_page=monetization' ) ) .'">', '</a>' ) .'</p>';
                $output .= '</div>';

            $output .= '</div>';
        }

        return $output;
    }

    /**
     * Update TutorLMS options from PMS Settings -> TutorLMS tab
     *
     */
    public function save_settings() {

        if ( !isset( $_POST['option_page'] ) || $_POST['option_page'] !== 'pms_tutor_lms_settings' || !isset( $_POST['pms_tutor_lms_settings']['restriction_type'] ) )
            return;

        if ( $_POST['pms_tutor_lms_settings']['restriction_type'] !== 'full_courses' ) {

            if ( isset( $_POST['pms_tutor_lms_settings']['access_type'] ) )
                unset( $_POST['pms_tutor_lms_settings']['access_type'] );

            if ( isset( $_POST['pms_tutor_lms_settings']['subscription_plans'] ) )
                unset( $_POST['pms_tutor_lms_settings']['subscription_plans'] );

        }
        elseif ( isset( $_POST['pms_tutor_lms_settings']['access_type'] ) && $_POST['pms_tutor_lms_settings']['access_type'] === 'any_member' ) {

            if ( isset( $_POST['pms_tutor_lms_settings']['subscription_plans'] ) )
                unset( $_POST['pms_tutor_lms_settings']['subscription_plans'] );

        }

        if ( $_POST['pms_tutor_lms_settings']['restriction_type'] !== 'individual' && isset( $_POST['pms_tutor_lms_settings']['auto_enroll'] ) ) {
            unset( $_POST['pms_tutor_lms_settings']['auto_enroll'] );
        }


        // update member enrollment status when the TutorLMS Restriction Type is changed
        $pms_tutor_settings = get_option( 'pms_tutor_lms_settings' );

        if ( empty( $pms_tutor_settings ) || $_POST['pms_tutor_lms_settings']['restriction_type'] !== $pms_tutor_settings['restriction_type'] ) {

            $tutor_course_post_type = tutor()->course_post_type;
            $all_tutor_course_ids = get_posts( array( 'fields' => 'ids', 'post_type' => $tutor_course_post_type ) );

            $users = get_users( array( 'fields' => 'ID' ) );

            foreach ( $users as $user_id ) {
                foreach ( $all_tutor_course_ids as $course_id ) {

                    $requested_subscription_plans = $this->get_course_requested_subscription_plans( $course_id, true, $_POST['pms_tutor_lms_settings'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                    $is_member = !empty( $requested_subscription_plans ) ? pms_is_member( $user_id, $requested_subscription_plans ) : false;
                    $public_course = get_post_meta( $course_id, '_tutor_is_public_course', true );

                    if ( $public_course === 'no' && !$is_member )
                        $this->user_course_enrollment_cancel( $course_id, $user_id );

                }
            }

        }

    }

    /**
     * Add Tutor LMS settings to Subscription Plan Settings
     *
     */
    public function add_subscription_plan_settings( $subscription_plan_id ) {

        if ( empty( $subscription_plan_id ) )
            return;

        $pms_tutor_settings = get_option( 'pms_tutor_lms_settings' );

        if ( isset( $pms_tutor_settings['restriction_type'] ) && $pms_tutor_settings['restriction_type'] === 'category' ) {

            $selected_categories = $this->get_subscription_plan_categories( $subscription_plan_id );
            $tutor_categories = $this->get_all_categories();

            // TutorLMS Categories selector
            echo '<div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">';

                echo '<label for="pms-tutor-categories" class="pms-meta-box-field-label cozmoslabs-form-field-label">' . esc_html__( 'Tutor LMS Categories', 'paid-member-subscriptions' ) . '</label>';


                if ( !empty( $tutor_categories ) ) {
                    echo '<select name="pms_tutor_categories[]" id="pms-tutor-categories" class="pms-chosen" multiple>';

                        $this->output_category_selector( $tutor_categories, $selected_categories );

                    echo '</select>';

                    echo '<p class="cozmoslabs-description cozmoslabs-description-align-right">' . esc_html__( 'Select the categories you want to associate with this Subscription Plan.', 'paid-member-subscriptions' ) . '</p>';
                }
                else {
                    echo '<div class="cozmoslabs-description">';

                        echo '<strong>' . esc_html__( 'No TutorLMS Categories have been created yet!', 'paid-member-subscriptions' ) . '</strong>';
                        echo '<p style="margin: 5px 0 0 0; font-style: italic;">' . sprintf( esc_html__( 'Go to %1$s Tutor LMS Pro -> Categories %2$s to start adding your own.', 'paid-member-subscriptions' ), '<a href="'. esc_url( admin_url( 'edit-tags.php?taxonomy=course-category&post_type=courses' ) ) .'">', '</a>') . '</p>';

                    echo '</div>';
                }

            echo '</div>';

        }

        $recommended = get_post_meta( $subscription_plan_id, 'pms_tutor_recommended_subscription_plan', true );
        $checked = ( isset( $recommended ) && $recommended === 'yes' )  ? 'checked' : '';

        // Recommended Subscription Plan
        echo '<div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">';

            echo '<label class="cozmoslabs-form-field-label" for="pms-tutor-recommended-subscription-plan">' . esc_html__( 'Tutor LMS Recommended', 'paid-member-subscriptions' ) . '</label>';

            echo '<div class="cozmoslabs-toggle-container">';

                echo '<input type="checkbox" id="pms-tutor-recommended-subscription-plan" name="pms_tutor_recommended_subscription_plan" value="yes" '. esc_html( $checked ) .' />';
                echo '<label class="cozmoslabs-toggle-track" for="pms-tutor-recommended-subscription-plan"></label>';

            echo '</div>';

            echo '<div class="cozmoslabs-toggle-description">';

                echo '<label class="cozmoslabs-description" for="pms-tutor-recommended-subscription-plan">' . esc_html__( 'Highlight this Subscription Plan in the required plans list on the Course Page.', 'paid-member-subscriptions' ) . '</label>';

            echo '</div>';

        echo '</div>';

    }

    /**
     * Update TutorLMS options from Subscription Plan settings
     *
     */
    public function save_subscription_plan_settings( $subscription_plan_id, $post ) {

        if ( $post->post_type !== 'pms-subscription' )
            return;

        if ( isset( $_POST['pms_tutor_categories'] ) )
            update_post_meta( $subscription_plan_id, 'pms_tutor_categories', array_map( 'sanitize_text_field', $_POST['pms_tutor_categories'] ));
        else update_post_meta( $subscription_plan_id, 'pms_tutor_categories', array() );

        if ( isset( $_POST['pms_tutor_recommended_subscription_plan'] ) )
            update_post_meta( $subscription_plan_id, 'pms_tutor_recommended_subscription_plan', sanitize_text_field( $_POST['pms_tutor_recommended_subscription_plan'] ) );
        else update_post_meta( $subscription_plan_id, 'pms_tutor_recommended_subscription_plan', 'no' );

    }

    /**
     * Output Tutor LMS category selector
     *
     */
    public function output_category_selector( $tutor_categories, $selected_categories ) {
        foreach ( $tutor_categories as $category ) {
            $this->output_category_selector_option( $category, $selected_categories );
        }
    }

    /**
     * Output Tutor LMS category selector options
     *
     */
    private function output_category_selector_option( $tutor_category, $selected_categories ) {
        $selected = '';

        if ( !empty( $selected_categories ) && is_array( $selected_categories ) && in_array( $tutor_category->term_id, $selected_categories ) )
            $selected = 'selected="selected"';

        echo '<option value="'. esc_html( $tutor_category->term_id ) .'" '. esc_html( $selected ) .' > '. esc_html( $tutor_category->name ) .'</option>';

        if ( ! empty( $tutor_category->children ) ) {
            $this->output_category_selector( $tutor_category->children, $selected_categories );
        }
    }

    /**
     * Add Subscription Plan -> TutorLMS categories to Member Subscription metadata
     *
     */
    public function add_member_subscription_categories_meta( $subscription_id = 0, $subscription_data = array() ) {
        if( $subscription_id === 0 || !isset( $subscription_data['subscription_plan_id'] ) )
            return;

        $subscription_plan_categories = $this->get_subscription_plan_categories( $subscription_data['subscription_plan_id'] );

        if ( empty( $subscription_plan_categories ) )
            $subscription_plan_categories = array();

        pms_add_member_subscription_meta( $subscription_id, 'pms_member_subscription_tutor_categories', $subscription_plan_categories, true );

    }

    /**
     * Add Content Restriction meta-box to TutorLMS Course Builder page
     *
     * @return void
     */
    public function tutor_course_cr_metabox_output() {

        if ( !isset( $_GET['page'] ) || $_GET['page'] != 'create-course' || !isset( $_GET['course_id'] ) || empty( $_GET['course_id'] ) )
            return;

        $post = get_post( sanitize_text_field( $_GET['course_id'] ) );

        if ( empty( $post ) )
            return;

        // output the Content Restriction meta-box
        $pms_meta_box_content_restriction = new PMS_Meta_Box_Content_Restriction( 'pms_post_content_restriction', esc_html__( 'Content Restriction', 'paid-member-subscriptions' ), $post->post_type, 'normal' );
        $pms_meta_box_content_restriction->output( $post );

    }

    /**
     * Enqueue Content Restriction scripts for Tutor LMS Course Builder
     *
     * @return void
     */
    public function enqueue_tutor_course_builder_scripts() {

        if ( !isset( $_GET['page'] ) || $_GET['page'] !== 'create-course' || !isset( $_GET['course_id'] ) )
            return;

        if ( file_exists( PMS_IN_TUTOR_LMS_PLUGIN_DIR_PATH . 'assets/js/pms-tutor-course.js' ) )
            wp_enqueue_script('pms-tutor-course-script', PMS_IN_TUTOR_LMS_PLUGIN_DIR_URL . 'assets/js/pms-tutor-course.js', array('jquery'), PMS_VERSION);

        if ( file_exists( PMS_PLUGIN_DIR_PATH . 'assets/js/admin/meta-box-post-content-restriction.js' ) )
            wp_enqueue_script( 'pms_post_content_restriction-js', PMS_PLUGIN_DIR_URL . 'assets/js/admin/meta-box-post-content-restriction.js', array( 'jquery' ) );

        // localize data needed to identify the course id (post_id) for handling Content Restriction settings data
        wp_localize_script('pms-tutor-course-script', 'pmsTutorCourse', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('pms_tutor_course_nonce'),
            'course_id' => sanitize_text_field( $_GET['course_id'] )
        ));

    }

    /**
     * Save Content Restriction data when a Tutor LMS Course is updated
     *
     * @return void
     */
    function tutor_course_cr_data_save() {
        check_ajax_referer('pms_tutor_course_nonce', 'security');

        $post_id = isset( $_POST['course_id'] ) ? sanitize_text_field( $_POST['course_id'] ) : '';
        $post = get_post( $post_id );

        // update Course content restriction settings
        $pms_meta_box_content_restriction = new PMS_Meta_Box_Content_Restriction( 'pms_post_content_restriction', esc_html__( 'Content Restriction', 'paid-member-subscriptions' ), $post->post_type, 'normal' );
        $pms_meta_box_content_restriction->save_data( $post_id, $post );
    }

    /**
     * Handle TutorLMS access and enrollment on Member Subscription update
     *
     */
    public function handle_member_subscription_update( $subscription_id = 0, $new_data = array(), $old_data = array() ) {
        if ( $subscription_id === 0 || !isset( $new_data['status'] ) || !isset( $old_data['status'] ) || !isset( $old_data['subscription_plan_id'] ) || !isset( $old_data['user_id'] ) )
            return;

        $pms_tutor_settings = get_option( 'pms_tutor_lms_settings' );

        if ( isset( $new_data['subscription_plan_id'] ) && $new_data['subscription_plan_id'] !== $old_data['subscription_plan_id'] ) {
            $new_categories = $this->get_subscription_plan_categories( $new_data['subscription_plan_id'] );
            $this->update_member_subscription_categories( $subscription_id, $new_categories );

            if ( $pms_tutor_settings['restriction_type'] === 'category' )
                $this->update_category_enrollment( $old_data['user_id'], $subscription_id, $new_data['status'] );
            elseif ( $pms_tutor_settings['restriction_type'] === 'individual' )
                $this->update_individual_enrollment( $old_data['user_id'], $subscription_id, $new_data['status'] );
            elseif ( $pms_tutor_settings['restriction_type'] === 'full_courses' )
                $this->update_full_courses_enrollment( $old_data['user_id'], $subscription_id, $new_data['status'], $pms_tutor_settings );

        }
        elseif ( $new_data['status'] !== $old_data['status'] ) {

            if ( $pms_tutor_settings['restriction_type'] === 'category' )
                $this->update_category_enrollment( $old_data['user_id'], $subscription_id, $new_data['status'] );
            elseif ( $pms_tutor_settings['restriction_type'] === 'individual' )
                $this->update_individual_enrollment( $old_data['user_id'], $subscription_id, $new_data['status'] );
            elseif ( $pms_tutor_settings['restriction_type'] === 'full_courses' )
                $this->update_full_courses_enrollment( $old_data['user_id'], $subscription_id, $new_data['status'], $pms_tutor_settings );

        }
    }

    /**
     * Handle TutorLMS access and enrollment on Member Subscription removal
     *
     */
    public function handle_member_subscription_remove( $subscription_id = 0, $subscription_data = array()  ) {

        if ( $subscription_id === 0 || !isset( $subscription_data['user_id'] ) )
            return;

        $this->update_member_subscription_categories( $subscription_id, array() );

        $pms_tutor_settings = get_option( 'pms_tutor_lms_settings' );

        if ( $pms_tutor_settings['restriction_type'] === 'category' )
            $this->update_category_enrollment( $subscription_data['user_id'], $subscription_id, $subscription_data['status'], $subscription_data );
        elseif ( $pms_tutor_settings['restriction_type'] === 'individual' )
            $this->update_individual_enrollment( $subscription_data['user_id'], $subscription_id, $subscription_data['status'], $subscription_data );
        elseif ( $pms_tutor_settings['restriction_type'] === 'full_courses' )
            $this->update_full_courses_enrollment( $subscription_data['user_id'], $subscription_id, $subscription_data['status'], $pms_tutor_settings, $subscription_data );

    }

    /**
     * Handle enrollment for TutorLMS Restriction Type: Individual
     *
     */
    public function update_individual_enrollment( $user_id, $subscription_id, $subscription_status, $removed_subscription_data = array() ) {

        // get the Subscription  Plan ID
        $subscription = pms_get_member_subscription( $subscription_id );
        $subscription_plan_id = '';

        if ( is_object( $subscription ) )
            $subscription_plan_id = $subscription->subscription_plan_id;
        elseif ( !empty( $removed_subscription_data ) )
            $subscription_plan_id = $removed_subscription_data['subscription_plan_id'];

        if ( empty( $subscription_plan_id ) )
            return;

        $pms_tutor_settings = get_option( 'pms_tutor_lms_settings' );

        $tutor_course_post_type = tutor()->course_post_type;
        $all_tutor_course_ids = get_posts( array( 'fields' => 'ids', 'post_type' => $tutor_course_post_type ) );

        foreach ( $all_tutor_course_ids as $course_id ) {
            $course_subscription_plan_ids = get_post_meta( $course_id, 'pms-content-restrict-subscription-plan' );

            $is_member = pms_is_member( $user_id, $course_subscription_plan_ids );

            if ( in_array( $subscription_plan_id, $course_subscription_plan_ids ) ) {

                if ( empty( $removed_subscription_data ) && $subscription_status === 'active' ) {

                    if ( isset( $pms_tutor_settings['auto_enroll'] ) && $pms_tutor_settings['auto_enroll'] === 'yes' )
                        $this->user_course_enroll( $course_id, $user_id );

                }
                elseif ( !$is_member )
                    $this->user_course_enrollment_cancel( $course_id, $user_id );

            }
            elseif ( !$is_member )
                $this->user_course_enrollment_cancel( $course_id, $user_id );

        }
    }

    /**
     * Handle enrollment for TutorLMS Restriction Type: Category
     *
     */
    public function update_category_enrollment( $user_id, $subscription_id, $subscription_status, $removed_subscription_data = array() ) {

        // get the Subscription  Plan ID
        $subscription = pms_get_member_subscription( $subscription_id );
        $subscription_plan_id = '';

        if ( is_object( $subscription ) )
            $subscription_plan_id = $subscription->subscription_plan_id;
        elseif ( !empty( $removed_subscription_data ) )
            $subscription_plan_id = $removed_subscription_data['subscription_plan_id'];

        if ( empty( $subscription_plan_id ) )
            return;

        // check if any of the Current Subscription categories are also accessible from other Member Subscriptions
        $targeted_categories = $this->get_member_subscription_categories( $subscription_id );
        $member_subscriptions = pms_get_member_subscriptions( array( 'user_id' => $user_id ) );

        foreach ( $member_subscriptions as $subscription ) {
            $subscription_categories = ( $subscription->id != $subscription_id && $subscription->status === 'active' ) ? $this->get_member_subscription_categories( $subscription->id ) : array();
            $targeted_categories = array_diff( $targeted_categories, $subscription_categories );
        }

        // handle user enrollment to courses in targeted categories
        $tutor_course_post_type = tutor()->course_post_type;
        $all_tutor_course_ids = get_posts( array( 'fields' => 'ids', 'post_type' => $tutor_course_post_type ) );

        foreach ( $all_tutor_course_ids as $course_id ) {
            $course_subscription_plan_ids = get_post_meta( $course_id, 'pms-content-restrict-subscription-plan' );
            $course_categories = wp_get_object_terms( $course_id, 'course-category', array( 'fields' => 'ids' ) );
            $course_in_target_category = !empty( array_intersect( $targeted_categories, $course_categories ) );
            $public_course = get_post_meta( $course_id, '_tutor_is_public_course', true );

            if ( $public_course === 'no' && empty( $course_subscription_plan_ids ) ) {

                if ( $course_in_target_category ) {

                    if ( $subscription_status !== 'active' )
                        $this->user_course_enrollment_cancel( $course_id, $user_id );

                }
                else {
                    $member_accessible_categories = $this->get_all_member_accessible_categories( $user_id );
                    $user_has_access = !empty( array_intersect( $member_accessible_categories, $course_categories ) );

                    if ( !$user_has_access )
                        $this->user_course_enrollment_cancel( $course_id, $user_id );
                }
            } elseif ( $public_course === 'no' && !empty( $course_subscription_plan_ids ) ) {

                $is_member = pms_is_member( $user_id, $course_subscription_plan_ids );

                if ( in_array( $subscription_plan_id, $course_subscription_plan_ids ) ) {

                    if ( ( !empty( $removed_subscription_data ) || $subscription_status !== 'active' ) && !$is_member )
                        $this->user_course_enrollment_cancel( $course_id, $user_id );

                }

            }

        }

    }

    /**
     * Handle enrollment for TutorLMS Restriction Type: Full Courses
     *
     */
    public function update_full_courses_enrollment( $user_id, $subscription_id, $subscription_status, $pms_tutor_settings, $removed_subscription_data = array() ) {
        // get the Subscription  Plan ID
        $subscription = pms_get_member_subscription( $subscription_id );
        $subscription_plan_id = '';

        if ( is_object( $subscription ) )
            $subscription_plan_id = $subscription->subscription_plan_id;
        elseif ( !empty( $removed_subscription_data ) )
            $subscription_plan_id = $removed_subscription_data['subscription_plan_id'];

        if ( empty( $subscription_plan_id ) )
            return;

        $tutor_course_post_type = tutor()->course_post_type;
        $all_tutor_course_ids = get_posts( array( 'fields' => 'ids', 'post_type' => $tutor_course_post_type ) );

        foreach ( $all_tutor_course_ids as $course_id ) {
            $course_subscription_plan_ids = get_post_meta( $course_id, 'pms-content-restrict-subscription-plan' );
            $public_course = get_post_meta( $course_id, '_tutor_is_public_course', true );

            if ( $public_course === 'no' && empty( $course_subscription_plan_ids ) ) {

                if ( $pms_tutor_settings['access_type'] === 'subscribed_member' && isset( $pms_tutor_settings['subscription_plans'] ) )
                    $course_access = pms_is_member( $user_id, $pms_tutor_settings['subscription_plans'] );
                elseif ( $pms_tutor_settings['access_type'] === 'any_member' || ( $pms_tutor_settings['access_type'] === 'subscribed_member' && empty( $pms_tutor_settings['subscription_plans'] ) ) )
                    $course_access = pms_is_member( $user_id );
                else $course_access = false;

                if ( !$course_access )
                    $this->user_course_enrollment_cancel( $course_id, $user_id );

            }
            elseif ( $public_course === 'no' && !empty( $course_subscription_plan_ids ) ) {

                $is_member = pms_is_member( $user_id, $course_subscription_plan_ids );

                if ( in_array( $subscription_plan_id, $course_subscription_plan_ids ) ) {

                    if ( ( !empty( $removed_subscription_data ) || $subscription_status !== 'active' ) && !$is_member )
                        $this->user_course_enrollment_cancel( $course_id, $user_id );

                }

            }

        }

    }

    /**
     * Preselect the recommended Subscription Plan on Registration Form
     *
     */
    public function preselect_recommended_subscription_plan( $selected, $atts ) {

        // Don't preselect the recommended Subscription Plan if the "selected" attribute is set on the registration form shortcode
        if ( !empty( $selected ) )
            return $selected;

        $subscription_plans = pms_get_subscription_plans_list();

        foreach ( $subscription_plans as $subscription_plan_id => $subscription_plan_name ) {
            $recommended = get_post_meta( $subscription_plan_id, 'pms_tutor_recommended_subscription_plan', true );

            if ( $recommended === 'yes' && ( empty( $atts['subscription_plans'] ) || in_array( $subscription_plan_id, $atts['subscription_plans'] ) ) && !in_array( $subscription_plan_id, $atts['exclude'] ) ) {
                $selected = $subscription_plan_id;
                break;
            }
        }

        return $selected;
    }

    /**
     * Disable TutorLMS Archive page enrollment
     * -> remove the "tutor-course-list-enroll" class to disable enrollment
     *
     */
    public function remove_course_loop_enroll_class( $enroll_button )
    {
        return str_replace('tutor-course-list-enroll', '', $enroll_button);
    }

    /**
     * Get all Tutor LMS categories
     *
     */
    public function get_all_categories() {
        return tutor_utils()->get_course_categories();
    }

    /**
     * Get Tutor LMS Course specific categories
     *
     */
    public function get_course_categories( $course_id ) {
        $course_categories = get_the_terms( $course_id, 'course-category' );
        $category_ids = array();

        if ( !empty( $course_categories ) ) {
            foreach ( $course_categories as $category ) {
                $category_ids[] = $category->term_id;
            }
        }

        return $category_ids;
    }

    /**
     * Get Tutor LMS categories selected on the Subscription Plan
     *
     */
    public function get_subscription_plan_categories( $subscription_plan_id ) {
        return get_post_meta( $subscription_plan_id, 'pms_tutor_categories', true );
    }

    /**
     * Get Tutor LMS categories that a Member Subscription grants access to
     *
     */
    public function get_member_subscription_categories( $subscription_id ) {
        return pms_get_member_subscription_meta( $subscription_id, 'pms_member_subscription_tutor_categories', true );
    }

    /**
     * Update Tutor LMS categories that a Member Subscription grants access to
     *
     */
    public function update_member_subscription_categories( $subscription_id, $new_categories ) {
        return pms_update_member_subscription_meta( $subscription_id, 'pms_member_subscription_tutor_categories', $new_categories );
    }

    /**
     * Check if a user is enrolled to a specific Tutor LMS Course
     *
     */
    public function is_user_enrolled( $course_id, $user_id ) {

        // "tutor_utils()->is_enrolled" returns CPT details if the user is enrolled, otherwise it returns FALSE
        $user_enrolled = tutor_utils()->is_enrolled( $course_id, $user_id ) === false ? false : true;

        return $user_enrolled;
    }

    /**
     * Enroll the user to a specific Tutor LMS Course
     *
     */
    public function user_course_enroll( $course_id, $user_id ) {
        $user_enrolled = $this->is_user_enrolled( $course_id, $user_id );

        if ( !$user_enrolled )
            tutor_utils()->do_enroll( $course_id, 0, $user_id );
    }

    /**
     * Cancel user enrollment to a specific Tutor LMS Course
     *
     */
    public function user_course_enrollment_cancel( $course_id, $user_id ) {

        $user_enrolled = $this->is_user_enrolled( $course_id, $user_id );

        if ( $user_enrolled )
            tutor_utils()->cancel_course_enrol( $course_id, $user_id );
    }

    /**
     * Get all Tutor LMS categories that a Member has access to
     *
     */
    public function get_all_member_accessible_categories( $user_id, $unique = false ) {

        if ( !is_user_logged_in() || $user_id == 0 )
            return array();

        $member_subscriptions = pms_get_member_subscriptions( array( 'user_id' => $user_id ) );
        $accessible_categories = array();

        foreach ( $member_subscriptions as $subscription ) {

            // if the member subscription is not active, the linked categories are not accessible by the user
            $subscription_categories = $subscription->status === 'active' ? $this->get_member_subscription_categories( $subscription->id ) : array();

            if ( !empty( $subscription_categories ) && is_array( $subscription_categories ) )
                $accessible_categories = array_merge( $accessible_categories, $subscription_categories );
        }

        if ( $unique && !empty( $accessible_categories ) )
            $accessible_categories = array_unique( $accessible_categories );

        return $accessible_categories;
    }

    /**
     * Get the Subscription Plans requested to access a specific TutorLMS Course
     *
     */
    public function get_course_requested_subscription_plans( $course_id, $return_only_ids, $pms_tutor_settings = array() ) {

        if ( empty( $pms_tutor_settings ) )
            $pms_tutor_settings = get_option( 'pms_tutor_lms_settings' );

        $requested_subscription_plans = array();

        switch ( $pms_tutor_settings['restriction_type'] ) {

            case 'full_courses':

                $course_subscription_plans = $this->get_course_specific_requested_subscription_plans( $course_id );

                if ( empty( $course_subscription_plans ) ) {
                    if ( $pms_tutor_settings['access_type'] === 'subscribed_member' && isset( $pms_tutor_settings['subscription_plans'] ) ) {

                        foreach ( $pms_tutor_settings['subscription_plans'] as $subscription_plan_id ) {
                            $requested_subscription_plans[] = pms_get_subscription_plan( $subscription_plan_id );
                        }

                    }
                    elseif ( $pms_tutor_settings['access_type'] === 'any_member' || ( $pms_tutor_settings['access_type'] === 'subscribed_member' && empty( $pms_tutor_settings['subscription_plans'] ) ) ) {
                        $requested_subscription_plans = pms_get_subscription_plans();
                    }
                }
                else $requested_subscription_plans = $course_subscription_plans;

                break;


            case 'category':

                $course_subscription_plans = $this->get_course_specific_requested_subscription_plans( $course_id );

                if ( empty( $course_subscription_plans ) ) {
                    $course_categories = $this->get_course_categories( $course_id );
                    $subscription_plans = pms_get_subscription_plans_list();

                    foreach ( $subscription_plans as $subscription_plan_id => $subscription_plan_name ) {
                        $subscription_plan_categories = $this->get_subscription_plan_categories( $subscription_plan_id );
                        $grants_access = !empty( $subscription_plan_categories ) ? !empty( array_intersect( $subscription_plan_categories, $course_categories ) ) : false;

                        if ( $grants_access )
                            $requested_subscription_plans[] = pms_get_subscription_plan( $subscription_plan_id );
                    }
                }
                else $requested_subscription_plans = $course_subscription_plans;

                break;


            case 'individual':

                $requested_subscription_plans = $this->get_course_specific_requested_subscription_plans( $course_id );

                break;

        }

        if ( $return_only_ids && !empty( $requested_subscription_plans ) ) {
            $requested_subscription_plan_ids = array();

            foreach ( $requested_subscription_plans as $subscription_plan ) {
                $requested_subscription_plan_ids[] = $subscription_plan->id;
            }

            $requested_subscription_plans = $requested_subscription_plan_ids;
        }


        return $requested_subscription_plans;
    }

    /**
     * Get the Content Restriction -> Subscription Plans requested to access a specific TutorLMS Course
     *
     */
    public function get_course_specific_requested_subscription_plans( $course_id ) {

        $course_subscription_plans = get_post_meta( $course_id, 'pms-content-restrict-subscription-plan' );
        $requested_subscription_plans = array();

        foreach ( $course_subscription_plans as $subscription_plan_id ) {
            $requested_subscription_plans[] = pms_get_subscription_plan( $subscription_plan_id );
        }

        return $requested_subscription_plans;
    }

    /**
     * Display the PMS Subscribe box based on the user's course access status
     *
     */
    public function course_subscribe_box( $output, $course_id ) {

        $user_id = is_user_logged_in() ? get_current_user_id() : 0;
        $user_enrolled = $user_id != 0 ? $this->is_user_enrolled( $course_id, $user_id ) : false;

        // check if the user is an administrator or an instructor
        $user_has_access = tutor_utils()->has_user_course_content_access();

        // Don't change the output if the user is enrolled or has access (admin or instructor) to this course
        if ( $user_enrolled || $user_has_access ) {
            return $output;
        }

        $display_subscribe_box = $this->course_subscribe_box_visibility( $user_id, $course_id );

        if ( $display_subscribe_box )
            $output = $this->course_subscribe_box_output( $course_id );

        return $output;
    }

    /**
     * PMS Subscribe box visibility based on the user's course access status
     *
     */
    public function course_subscribe_box_visibility( $user_id, $course_id ) {

        $pms_tutor_settings = get_option( 'pms_tutor_lms_settings' );
        $subscribe_box = false;

        switch ( $pms_tutor_settings['restriction_type'] ) {

            case 'full_courses':

                $course_subscription_plans = get_post_meta( $course_id, 'pms-content-restrict-subscription-plan' );

                if ( empty( $course_subscription_plans ) ) {
                    if ( !is_user_logged_in() ) {

                        $subscribe_box = true;

                    }
                    elseif ( isset( $pms_tutor_settings['access_type'] ) ) {

                        if ( ( $pms_tutor_settings['access_type'] === 'subscribed_member' && ( empty( $pms_tutor_settings['subscription_plans'] ) || !pms_is_member( $user_id, $pms_tutor_settings['subscription_plans'] ) ) ) || ( $pms_tutor_settings['access_type'] === 'any_member' && !pms_is_member( $user_id ) ) )
                            $subscribe_box = true;

                    }
                }
                else $subscribe_box = $this->course_specific_subscribe_box_visibility( $user_id, $course_id );

                break;


            case 'category':

                $course_subscription_plans = get_post_meta( $course_id, 'pms-content-restrict-subscription-plan' );

                if ( empty( $course_subscription_plans ) ) {
                    $requested_subscription_plans = $this->get_course_requested_subscription_plans( $course_id, true );
                    $course_categories = $this->get_course_categories( $course_id );
                    $member_accessible_categories = $this->get_all_member_accessible_categories( $user_id, true );

                    if ( !empty( $requested_subscription_plans ) && !empty( $course_categories ) && empty( array_intersect( $member_accessible_categories, $course_categories ) ) )
                        $subscribe_box = true;

                }
                else $subscribe_box = $this->course_specific_subscribe_box_visibility( $user_id, $course_id );

                break;


            case 'individual':

                $subscribe_box = $this->course_specific_subscribe_box_visibility( $user_id, $course_id );

                break;

        }

        return $subscribe_box;
    }

    /**
     * PMS Subscribe box visibility based on the Course Content Restriction Settings
     *
     */
    public function course_specific_subscribe_box_visibility( $user_id, $course_id ) {

        // Content Restriction settings for the current TutorLMS Course
        $user_status = get_post_meta( $course_id, 'pms-content-restrict-user-status', true );
        $all_subscription_plans = get_post_meta( $course_id, 'pms-content-restrict-all-subscription-plans', true );
        $course_subscription_plans = get_post_meta( $course_id, 'pms-content-restrict-subscription-plan' );
        $subscribe_box = false;

        if ( !empty( $course_subscription_plans ) && is_user_logged_in() ) {

            if ( ( $all_subscription_plans === 'all' && !pms_is_member( $user_id ) ) || ( $all_subscription_plans !== 'all' && !pms_is_member( $user_id, $course_subscription_plans ) ) )
                $subscribe_box = true;

        }
        elseif ( ( !empty( $user_status ) && $user_status == 'loggedin' ) || !empty( $course_subscription_plans ) ) {

            // show the Subscribe Box if the user is not logged in
            $subscribe_box = !is_user_logged_in();

        }

        return $subscribe_box;

    }

    /**
     * PMS Subscribe box HTML
     *
     */
    public function course_subscribe_box_output( $course_id ) {

        $registration_url = pms_get_page( 'register', true );

        if ( !$registration_url ) {

            $output = '<div id="pms-warning-wrapper">';

                $output .= '<p class="tutor-fs-3 tutor-color-secondary"">'. esc_html__( 'Paid Member Subscriptions', 'paid-member-subscriptions' ) .'</p>';

                $output .= '<p class="tutor-fs-4 tutor-color-secondary""><strong>'. esc_html__( 'Registration page is not correctly set or missing.', 'paid-member-subscriptions' ) .'</strong></p>';

                $output .= '<p class="tutor-fs-4 tutor-color-secondary">'. sprintf( esc_html__( 'Go to %1$s Settings -> General -> Membership Pages %2$s and select the required pages accordingly.', 'paid-member-subscriptions' ), '<em>', '</em>' ) .'</p>';


            $output .= '</div>';

            return $output;

        }

        // Front-end scripts and styles
        if ( file_exists( PMS_IN_TUTOR_LMS_PLUGIN_DIR_PATH . 'assets/css/pms-tutor-lms-front-end.css' ) )
            wp_enqueue_style( 'pms-tutor-lms-front-end-style', PMS_IN_TUTOR_LMS_PLUGIN_DIR_URL . 'assets/css/pms-tutor-lms-front-end.css', array(), PMS_VERSION );

        if ( file_exists( PMS_IN_TUTOR_LMS_PLUGIN_DIR_PATH . 'assets/js/pms-tutor-lms-front-end.js' ) )
            wp_enqueue_script('pms-tutor-lms-front-end-script', PMS_IN_TUTOR_LMS_PLUGIN_DIR_URL . 'assets/js/pms-tutor-lms-front-end.js', array('jquery'), PMS_VERSION);

        $requested_subscription_plans = $this->get_course_requested_subscription_plans( $course_id, false );
        $pms_tutor_settings = get_option( 'pms_tutor_lms_settings' );
        $user_status = get_post_meta( $course_id, 'pms-content-restrict-user-status', true );
        $all_subscription_plans = get_post_meta( $course_id, 'pms-content-restrict-all-subscription-plans', true );
        $course_subscription_plans = get_post_meta( $course_id, 'pms-content-restrict-subscription-plan' );

        $display_course_list = true;
        $subscribe_box_title = __( 'Select Your Plan', 'paid-member-subscriptions' );

        if ( ( $pms_tutor_settings['restriction_type'] === 'full_courses' && empty( $course_subscription_plans ) && ( $pms_tutor_settings['access_type'] === 'any_member' || ( $pms_tutor_settings['access_type'] === 'subscribed_member' && empty( $pms_tutor_settings['subscription_plans'] ) ) ) ) ||
            ( $pms_tutor_settings['restriction_type'] === 'individual' && ( ( !is_user_logged_in() && !empty( $user_status ) && $user_status == 'loggedin') || ( !empty( $all_subscription_plans ) && $all_subscription_plans === 'all' )  ) ) ) {

            $display_course_list = false;

            if ( is_user_logged_in() )
                $subscribe_box_title = __( 'Subscribe For Access', 'paid-member-subscriptions' );
            else $subscribe_box_title = __( 'Register For Access', 'paid-member-subscriptions' );

        }

        $output = '<h5 class="pms-title">'.$subscribe_box_title .'</h5>';
        $output .= '<div id="pms-subscription-plans-wrapper">';

        if ( !$display_course_list ) {

            if ( is_user_logged_in() )
                $button_text = __( 'Subscribe', 'paid-member-subscriptions' );
            else $button_text = __( 'Register', 'paid-member-subscriptions' );

            $output .= '<a href="'. $registration_url .'" class="tutor-btn tutor-btn-primary tutor-btn-lg tutor-btn-block">'. $button_text .'</a>';

        } else {

            $number_of_subscription_plans = count( $requested_subscription_plans );

            foreach ( $requested_subscription_plans as $subscription_plan ) {
                $recommended = get_post_meta( $subscription_plan->id, 'pms_tutor_recommended_subscription_plan', true );
                $recommended_class = ( isset( $recommended ) && $recommended === 'yes' )  ? 'pms_recommended' : '';

                if ( $number_of_subscription_plans === 1 || ( isset( $recommended ) && $recommended === 'yes' ) )
                    $checked = 'checked';
                else $checked = '';

                $output .= '<label class="pms-subscription-plan-container '. $recommended_class .'">';

                    $output .= '<div class="pms-subscription-plan-details">';

                        $output .= '<input type="radio" name="pms_subscription_plan" class="pms-subscription-plan" id="subscription-plan-'. $subscription_plan->id .'" '. $checked .'>';

                        $output .= '<span class="pms-subscription-plan-name">' . $subscription_plan->name . '</span>';

                        $output .= '<span class="pms-subscription-plan-price">' . pms_get_output_subscription_plan_price( $subscription_plan ) . '</span>';

                    $output .= '</div>';

                    $output .= '<a href="'. $registration_url .'?subscription_plan='. $subscription_plan->id .'&single_plan=yes" class="pms-subscribe-button tutor-btn tutor-btn-primary tutor-btn-lg tutor-btn-block">'. __( 'Subscribe', 'paid-member-subscriptions' ) .'</a>';

                $output .= '</label>';
            }

        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Do not replace the Content with PMS Content Restriction messages for TutorLMS Courses
     * -> TutorLMS uses the Post Content for the Course Description
     */
    public function disable_course_message_content_restriction( $message, $content, $post, $user_id ) {

        $tutor_course_post_type = tutor()->course_post_type;

        if (  $post->post_type === $tutor_course_post_type )
            $message = $content;

        return $message;
    }

    /**
     * TutorLMS integration notices
     */
    public function add_admin_notification() {

        // no need to check here if TutorLMS plugin is active (this is done when the PMS_IN_TutorLMS class is initialized)

        $notification_id = 'pms-tutor-lms-integration';
        $message = '<img style="float: left; margin: 20px 12px 10px 0; max-width: 100px;" src="' . PMS_PLUGIN_DIR_URL . 'assets/images/tutor-lms-logo.png" />';
        $message .= '<p style="margin-top: 16px;">' . wp_kses_post( '<strong>Tutor LMS Integration</strong> for <strong>Paid Member Subscriptions</strong> is now available!<br><br>Sell access to courses, create beautiful front-end register, login and reset password forms and restrict access to your Courses.<br>Enable by selecting <strong>Paid Member Subscriptions</strong> as the eCommerce Engine in <a href="'. esc_url( admin_url( 'admin.php?page=tutor_settings&tab_page=monetization' ) ) .'">Tutor LMS --> Settings --> Monetization</a>.' ) . '</p>';
        $message .= '<p><a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/integration-with-other-plugins/tutor-lms/?utm_source=wpbackend&utm_medium=pms-documentation&utm_campaign=PMSDocs" class="button-primary" target="_blank">' . esc_html__( 'Learn More', 'paid-member-subscriptions' ) . '</a></p>';
        $message .= '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms_dismiss_admin_notification' => $notification_id ) ), 'pms_plugin_notice_dismiss' ) ) . '" type="button" class="notice-dismiss" style="text-decoration: none;"><span class="screen-reader-text">' . esc_html__( 'Dismiss this notice.', 'paid-member-subscriptions' ) . '</span></a>';

        pms_add_plugin_notification( $notification_id, $message, 'pms-notice pms-narrow notice notice-success', true, array( 'pms-addons-page' ) );

    }

}


/**
 * Initiate Tutor LMS Integration
 *
 */
function pms_tutor_init() {

    /**
     * Filter for disabling/enabling Tutor LMS integration
     *
     * @param bool $enable_tutor_lms_integration -> Defaults to TRUE.
     *
     */
    $enable_tutor_lms_integration = apply_filters( 'pms_enable_tutor_lms_integration', true );


    if( is_plugin_active( 'tutor/tutor.php' ) && function_exists( 'tutor_utils' ) && $enable_tutor_lms_integration ) {
            new PMS_IN_TutorLMS;
    }

}
add_action( 'plugins_loaded', 'pms_tutor_init', 11 );
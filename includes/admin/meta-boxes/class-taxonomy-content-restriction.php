<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class PMS_Taxonomy_Content_Restriction {

    public function init() {
        $taxonomies = get_taxonomies( array( 'public' => true ), 'names' );

        unset( $taxonomies['nav_menu'], $taxonomies['link_category'], $taxonomies['post_format'] );

        $taxonomies = apply_filters( 'pms_taxonomy_content_restriction_taxonomies', $taxonomies );

        foreach ( $taxonomies as $taxonomy ) {
            add_action( "{$taxonomy}_add_form_fields", array( $this, 'output_add' ) );
            add_action( "{$taxonomy}_edit_form_fields", array( $this, 'output_edit' ), 10, 2 );

            add_action( "created_{$taxonomy}", array( $this, 'save_data' ), 10, 2 );
            add_action( "edited_{$taxonomy}", array( $this, 'save_data' ), 10, 2 );
        }

        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
    }

    public function enqueue_admin_scripts() {
        $screen = get_current_screen();

        if ( empty( $screen ) || empty( $screen->taxonomy ) ) {
            return;
        }

        wp_enqueue_script('pms-taxonomy-content-restriction-js', PMS_PLUGIN_DIR_URL . 'assets/js/admin/meta-box-post-content-restriction.js', array( 'jquery' ), PMS_VERSION);
    }

    public function output_add( $taxonomy ) {
        $term = false;

        include PMS_PLUGIN_DIR_PATH . 'includes/admin/meta-boxes/views/view-taxonomy-content-restriction.php';
    }

    public function output_edit( $term, $taxonomy ) {
        include PMS_PLUGIN_DIR_PATH . 'includes/admin/meta-boxes/views/view-taxonomy-content-restriction.php';
    }

    public function save_data( $term_id, $tt_id = 0 ) {
        if ( empty( $_POST['pmstkn'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['pmstkn'] ), 'pms_meta_box_single_content_restriction_nonce' ) ) {
            return;
        }

        $taxonomy = ! empty( $_POST['taxonomy'] ) ? get_taxonomy( sanitize_key( $_POST['taxonomy'] ) ) : false;

        if ( empty( $taxonomy ) || empty( $taxonomy->cap->manage_terms ) || ! current_user_can( $taxonomy->cap->manage_terms ) ) {
            return;
        }

        delete_term_meta( $term_id, 'pms-content-restrict-type' );
        if ( ! empty( $_POST['pms-content-restrict-type'] ) ) {
            update_term_meta( $term_id, 'pms-content-restrict-type', sanitize_text_field( $_POST['pms-content-restrict-type'] ) );
        }

        delete_term_meta( $term_id, 'pms-content-restrict-subscription-plan' );
        delete_term_meta( $term_id, 'pms-content-restrict-all-subscription-plans' );

        if ( isset( $_POST['pms-content-restrict-subscription-plan'] ) || isset( $_POST['pms-content-restrict-all-subscription-plans'] ) ) {

            if ( isset( $_POST['pms-content-restrict-all-subscription-plans'] ) ) {
                update_term_meta( $term_id, 'pms-content-restrict-all-subscription-plans', 'all' );
            }

            if ( isset( $_POST['pms-content-restrict-all-subscription-plans'] ) ) {

                $active_plans    = pms_get_subscription_plans();
                $active_plan_ids = array();

                foreach ( $active_plans as $active_plan ) {
                    $active_plan_ids[] = (int) $active_plan->id;
                }

                $plans = $active_plan_ids;

            } else {
                $plans = array_map( 'sanitize_text_field', $_POST['pms-content-restrict-subscription-plan'] );
            }

            foreach ( $plans as $subscription_plan_id ) {
                $subscription_plan_id = (int) $subscription_plan_id;

                if ( ! empty( $subscription_plan_id ) ) {
                    add_term_meta( $term_id, 'pms-content-restrict-subscription-plan', $subscription_plan_id );
                }
            }
        }

        if ( isset( $_POST['pms-content-restrict-user-status'] ) && $_POST['pms-content-restrict-user-status'] === 'loggedin' ) {
            update_term_meta( $term_id, 'pms-content-restrict-user-status', 'loggedin' );
        } else {
            delete_term_meta( $term_id, 'pms-content-restrict-user-status' );
        }

        delete_term_meta( $term_id, 'pms-content-restrict-custom-redirect-url-enabled' );
        if ( isset( $_POST['pms-content-restrict-custom-redirect-url-enabled'] ) ) {
            update_term_meta( $term_id, 'pms-content-restrict-custom-redirect-url-enabled', 'yes' );
        }

        update_term_meta( $term_id, 'pms-content-restrict-custom-redirect-url', ! empty( $_POST['pms-content-restrict-custom-redirect-url'] ) ? sanitize_text_field( $_POST['pms-content-restrict-custom-redirect-url'] ) : '' );
        update_term_meta( $term_id, 'pms-content-restrict-custom-non-member-redirect-url', ! empty( $_POST['pms-content-restrict-custom-non-member-redirect-url'] ) ? sanitize_text_field( $_POST['pms-content-restrict-custom-non-member-redirect-url'] ) : '' );

        delete_term_meta( $term_id, 'pms-content-restrict-messages-enabled' );
        if ( isset( $_POST['pms-content-restrict-messages-enabled'] ) ) {
            update_term_meta( $term_id, 'pms-content-restrict-messages-enabled', 'yes' );
        }

        update_term_meta( $term_id, 'pms-content-restrict-message-logged_out', ! empty( $_POST['pms-content-restrict-message-logged_out'] ) ? wp_kses_post( $_POST['pms-content-restrict-message-logged_out'] ) : '' );
        update_term_meta( $term_id, 'pms-content-restrict-message-non_members', ! empty( $_POST['pms-content-restrict-message-non_members'] ) ? wp_kses_post( $_POST['pms-content-restrict-message-non_members'] ) : '' );
    }
}

function pms_initialize_taxonomy_content_restriction() {
    $pms_taxonomy_content_restriction = new PMS_Taxonomy_Content_Restriction();
    $pms_taxonomy_content_restriction->init();
}
add_action( 'init', 'pms_initialize_taxonomy_content_restriction', 999 );

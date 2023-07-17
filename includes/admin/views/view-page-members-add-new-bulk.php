<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * HTML output for the members admin add new members bulk page
 */
?>

<div class="wrap cozmoslabs-wrap">

    <h1></h1>
    <!-- WordPress Notices are added after the h1 tag -->

    <div class="cozmoslabs-page-header">
        <div class="cozmoslabs-section-title">

            <h3 class="cozmoslabs-page-title"><?php echo esc_html__( 'Bulk Add Subscription Plans to Users', 'paid-member-subscriptions' ); ?></h3>

        </div>
    </div>

    <form id="pms-form-add-new-member-bulk" method="POST" action="">
        <?php
        $members_list_table = new PMS_Members_Add_New_Bulk_List_Table();
        $members_list_table->prepare_items();
        $members_list_table->views();
        ?>


        <div class="cozmoslabs-form-subsection-wrapper">

            <!-- Meta-box Title -->
            <h3 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Filter Users', 'paid-member-subscriptions' );?></h3>



                    <?php


                    echo '<div class="cozmoslabs-form-field-wrapper">';


                    $subscription_plans = pms_get_subscription_plans( false );
                    $user_roles = pms_get_user_role_names();

                    echo '<label class="cozmoslabs-form-field-label" for="pms-filter-user-role">'. esc_html__( 'Role', 'paid-member-subscriptions' ) .'</label>';
                    echo '<select name="pms-filter-user-role" class="pms-filter-select" id="pms-filter-user-role">';
                    echo '<option value="">' . esc_html__( 'User Role...', 'paid-member-subscriptions' ) . '</option>';

                    foreach( $user_roles as $role_slug => $role_name )
                        echo '<option value="' . esc_attr( $role_slug ) . '" ' . ( !empty( $_POST['pms-filter-user-role'] ) ? selected( $role_slug, sanitize_text_field( $_POST['pms-filter-user-role'] ), false ) : '' ) . '>' . esc_html( $role_name ) . '</option>';
                    echo '</select>';

                    echo '<p class="cozmoslabs-description cozmoslabs-description-align-right">' . esc_html__( 'Filter Users by their Role.', 'paid-member-subscriptions' ) . '</p>';

                    echo '</div>';

                    /**
                     * Action to add more filters
                     */
                    do_action( 'pms_members_list_users_extra_filter', 'top' );


                    /**
                     * Filter button
                     */
                    echo '<input class="button button-secondary" id="pms-filter-button" type="submit" value="' . esc_html__( 'Filter', 'paid-member-subscriptions' ) . '" />';


                    ?>



        </div>


        <div class="cozmoslabs-form-subsection-wrapper" id="cozmoslabs-subsection-users-add-bulk">

            <h2 class="cozmoslabs-subsection-title"><?php echo esc_html__( 'Add Subscription', 'paid-member-subscriptions' ); ?></h2>


            <?php
            $members_list_table->display();
            wp_nonce_field( 'pms_add_new_members_bulk_nonce' );
            ?>

        </div>


    </form>

</div>


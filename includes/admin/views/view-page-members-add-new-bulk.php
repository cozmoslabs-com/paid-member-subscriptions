<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * HTML output for the members admin add new members bulk page
 */
?>

<div class="wrap">

    <h1>
        <?php echo esc_html__( 'Bulk Add Subscription Plans to Users', 'paid-member-subscriptions' ); ?>
    </h1>

    <form id="pms-form-add-new-member-bulk" method="POST" action="">
    <?php
        $members_list_table = new PMS_Members_Add_New_Bulk_List_Table();
        $members_list_table->prepare_items();
        $members_list_table->views();
    ?>

        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">

                <div id="post-body-content">
                    <?php
                    $members_list_table->display();
                    wp_nonce_field( 'pms_add_new_members_bulk_nonce' );
                    ?>
                </div>

                <div id="postbox-container-1" class="postbox-container filter-members-sidebox">
                    <?php $members_list_table->pagination( 'top' ); ?>

                    <div id="side-sortables" class="meta-box-sortables ui-sortable">
                        <div class="postbox">

                            <!-- Meta-box Title -->
                            <h3>
									<span>
										<?php
                                        esc_html_e( 'Filter by', 'paid-member-subscriptions' );
                                        ?>
									</span>
                            </h3>

                            <div class="submitbox">

                                <div id="major-publishing-actions">

                                    <?php

                                    echo '<div style="display: inline-block;">';

                                        echo '<div class="pms-members-filter">';


                                            $subscription_plans = pms_get_subscription_plans( false );
                                            $user_roles = pms_get_user_role_names();

                                            echo '<select name="pms-filter-user-role" class="pms-filter-select" id="pms-filter-user-role">';
                                            echo '<option value="">' . esc_html__( 'User Role...', 'paid-member-subscriptions' ) . '</option>';

                                            foreach( $user_roles as $role_slug => $role_name )
                                                echo '<option value="' . esc_attr( $role_slug ) . '" ' . ( !empty( $_POST['pms-filter-user-role'] ) ? selected( $role_slug, sanitize_text_field( $_POST['pms-filter-user-role'] ), false ) : '' ) . '>' . esc_html( $role_name ) . '</option>';
                                            echo '</select>';

                                        echo '</div>';

                                        /**
                                         * Action to add more filters
                                         */
                                        do_action( 'pms_members_list_users_extra_filter', 'top' );

                                        /**
                                         * Filter button
                                         */
                                        echo '<input class="button button-secondary" id="pms-filter-button" type="submit" value="' . esc_html__( 'Filter', 'paid-member-subscriptions' ) . '" />';

                                    echo '</div>';

                                    ?>

                                </div>
                                <div class="clear"></div>

                            </div>


                        </div>
                    </div>

                </div>

            </div>
        </div>

    </form>

</div>

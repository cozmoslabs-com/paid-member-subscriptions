<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * HTML output for the members admin page
 */
?>

<div class="wrap">

    <h1 class="wp-heading-inline">
        <?php echo esc_html( $this->page_title ); ?>

        <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/member-management/?utm_source=wpbackend&utm_medium=pms-documentation&utm_campaign=PMSDocs" target="_blank" data-code="f223" class="pms-docs-link dashicons dashicons-editor-help"></a>

        <a href="<?php echo esc_url( add_query_arg( array( 'page' => $this->menu_slug, 'subpage' => 'add_subscription' ), admin_url( 'admin.php' ) ) ); ?>" class="add-new-h2 page-title-action"><?php echo esc_html__( 'Add New', 'paid-member-subscriptions' ); ?></a>
        <a href="<?php echo esc_url( add_query_arg( array( 'page' => $this->menu_slug, 'subpage' => 'add_new_members_bulk' ), admin_url( 'admin.php' ) ) ); ?>" class="add-new-h2 page-title-action"><?php echo esc_html__( 'Bulk Add New', 'paid-member-subscriptions' ); ?></a>
    </h1>
    <form method="get">
        <input type="hidden" name="page" value="pms-members-page" />
        <?php
            $this->list_table->prepare_items();
            $this->list_table->views();
            $this->list_table->search_box( esc_html__( 'Search Members', 'paid-member-subscriptions' ), 'pms_search_members' );
        ?>
        <div id="poststuff">

            <div id="post-body" class="metabox-holder columns-2">

                <div id="post-body-content">
                    <?php
                    $this->list_table->display();
                    ?>
                </div>

                <div id="postbox-container-1" class="postbox-container filter-members-sidebox">
                    <?php $this->list_table->pagination( 'top' ); ?>
                    <div id="side-sortables" class="meta-box-sortables ui-sortable">
                        <div class="postbox">

                            <!-- Meta-box Title -->
                            <h2 class="hndle">
									<span>
										<?php
                                        esc_html_e( 'Filter by', 'paid-member-subscriptions' );
                                        ?>
									</span>
                            </h2>

                            <div class="submitbox">
                                <div id="major-publishing-actions">
                                    <?php

                                        echo '<div>';
                                        /*
                                         * Add a custom select box to filter the list by Subscription Plans
                                         *
                                         */
                                        $subscription_plans = pms_get_subscription_plans( false );

                                            echo '<select name="pms-filter-subscription-plan" class="pms-filter-select" id="pms-filter-subscription-plan">';
                                            echo '<option value="">' . esc_html__( 'Subscription Plan...', 'paid-member-subscriptions' ) . '</option>';

                                            foreach( $subscription_plans as $subscription_plan )
                                                echo '<option value="' . esc_attr( $subscription_plan->id ) . '" ' . ( !empty( $_GET['pms-filter-subscription-plan'] ) ? selected( $subscription_plan->id, sanitize_text_field( $_GET['pms-filter-subscription-plan'] ), false ) : '' ) . '>' . esc_html( $subscription_plan->name ) . '</option>';
                                            echo '</select>';
                                        echo '</div>';

                                        /**
                                         * Action to add more filters
                                         */
                                        do_action( 'pms_members_list_extra_table_nav', 'top' );

                                        $payment_gateways = pms_get_payment_gateways();
                                        $payment_gateways_keys = array_keys( $payment_gateways );

                                        echo '<div>';
                                            echo '<select name="pms-filter-payment-gateway" class="pms-filter-select" id="pms-filter-payment-gateway">';
                                                echo '<option value="">' . esc_html__( 'Payment Gateway...', 'paid-member-subscriptions' ) . '</option>';
                                                $i = 0;
                                                foreach( $payment_gateways as $payment_gateway ){
                                                    if( isset( $payment_gateway[ 'display_name_admin' ] ) )
                                                        echo '<option value="' . esc_attr( $payment_gateways_keys[ $i ] ) . '" ' . ( !empty( $_GET['pms-filter-payment-gateway'] ) ? selected( $payment_gateways_keys[ $i ], sanitize_text_field( $_GET['pms-filter-payment-gateway'] ), false ) : '' ) . '>' . esc_html( $payment_gateway[ 'display_name_admin' ] ) . '</option>';
                                                    $i++;
                                                }
                                            echo '</select>';
                                        echo '</div>';

                                        echo '<div>';
                                            echo '<select name="pms-filter-start-date" class="pms-filter-select" id="pms-filter-start-date">';
                                                echo '<option value="">' . esc_html__( 'Start Date...', 'paid-member-subscriptions' ) . '</option>';
                                                echo '<option value="last_week" ' . ( !empty( $_GET['pms-filter-start-date'] ) ? selected( "last_week", sanitize_text_field( $_GET['pms-filter-start-date'] ), false ) : '' ) . '>' . esc_html__( 'Last 7 Days', 'paid-member-subscriptions' ) . '</option>';
                                                echo '<option value="last_month" ' . ( !empty( $_GET['pms-filter-start-date'] ) ? selected( "last_month", sanitize_text_field( $_GET['pms-filter-start-date'] ), false ) : '' ) . '>' . esc_html__( 'Last 30 Days', 'paid-member-subscriptions' ) . '</option>';
                                                echo '<option value="last_year" ' . ( !empty( $_GET['pms-filter-start-date'] ) ? selected( "last_year", sanitize_text_field( $_GET['pms-filter-start-date'] ), false ) : '' ) . '>' . esc_html__( 'Last Year', 'paid-member-subscriptions' ) . '</option>';
                                                echo '<option value="custom" ' . ( !empty( $_GET['pms-filter-start-date'] ) ? selected( "custom", sanitize_text_field( $_GET['pms-filter-start-date'] ), false ) : '' ) . '>' . esc_html__( 'Custom', 'paid-member-subscriptions' ) . '</option>';
                                            echo '</select>';
                                        echo '</div>';

                                        echo '<div class="cozmoslabs-custom-interval" id="pms-start-date-interval">';
                                            echo '<label id="pms-label-start-date-beginning" for="pms-datepicker-start-date-beginning">' . esc_html__( 'Start of Interval', 'paid-member-subscriptions' ) . '</label>';
                                            echo '<input id="pms-datepicker-start-date-beginning" type="text" name="pms-datepicker-start-date-beginning" class="datepicker value="'. ( !empty( $_GET['pms-datepicker-start-date-beginning'] ) ? esc_attr( sanitize_text_field( $_GET['pms-datepicker-start-date-beginning'] ) ) : '' ) . '">';

                                            echo '<label id="pms-label-start-date-end" for="pms-datepicker-start-date-end">' . esc_html__( 'End of Interval', 'paid-member-subscriptions' ) . '</label>';
                                            echo '<input id="pms-datepicker-start-date-end" type="text" name="pms-datepicker-start-date-end" class="datepicker value="'. ( !empty( $_GET['pms-datepicker-start-date-end'] ) ? esc_attr( sanitize_text_field( $_GET['pms-datepicker-start-date-end'] ) ) : '' ) . '">';
                                        echo '</div>';


                                        echo '<div>';
                                            echo '<select name="pms-filter-expiration-date" class="pms-filter-select" id="pms-filter-expiration-date">';
                                                echo '<option value="">' . esc_html__( 'Expiration Date...', 'paid-member-subscriptions' ) . '</option>';
                                                echo '<option value="today" ' . ( !empty( $_GET['pms-filter-expiration-date'] ) ? selected( "today", sanitize_text_field( $_GET['pms-filter-expiration-date'] ), false ) : '' ) . '>' . esc_html__( 'Today', 'paid-member-subscriptions' ) . '</option>';
                                                echo '<option value="tomorrow" ' . ( !empty( $_GET['pms-filter-expiration-date'] ) ? selected( "tomorrow", sanitize_text_field( $_GET['pms-filter-expiration-date'] ), false ) : '' ) . '>' . esc_html__( 'Tomorrow', 'paid-member-subscriptions' ) . '</option>';
                                                echo '<option value="this_week" ' . ( !empty( $_GET['pms-filter-expiration-date'] ) ? selected( "this_week", sanitize_text_field( $_GET['pms-filter-expiration-date'] ), false ) : '' ) . '>' . esc_html__( 'This Week', 'paid-member-subscriptions' ) . '</option>';
                                                echo '<option value="this_month" ' . ( !empty( $_GET['pms-filter-expiration-date'] ) ? selected( "this_month", sanitize_text_field( $_GET['pms-filter-expiration-date'] ), false ) : '' ) . '>' . esc_html__( 'This Month', 'paid-member-subscriptions' ) . '</option>';
                                                echo '<option value="custom" ' . ( !empty( $_GET['pms-filter-expiration-date'] ) ? selected( "custom", sanitize_text_field( $_GET['pms-filter-expiration-date'] ), false ) : '' ) . '>' . esc_html__( 'Custom', 'paid-member-subscriptions' ) . '</option>';
                                            echo '</select>';
                                        echo '</div>';

                                        echo '<div class="cozmoslabs-custom-interval" id="pms-expiration-date-interval">';
                                            echo '<label id="pms-label-expiration-date-beginning" for="pms-datepicker-expiration-date-beginning">' . esc_html__( 'Start of Interval', 'paid-member-subscriptions' ) . '</label>';
                                            echo '<input id="pms-datepicker-expiration-date-beginning" type="text" name="pms-datepicker-expiration-date-beginning" class="datepicker value="'. ( !empty( $_GET['pms-datepicker-expiration-date-beginning'] ) ? esc_attr( sanitize_text_field( $_GET['pms-datepicker-expiration-date-beginning'] ) ) : '' ) . '" ' . ( !empty( $_GET['pms-datepicker-expiration-date-beginning'] ) ? esc_attr( sanitize_text_field( $_GET['pms-datepicker-expiration-date-beginning'] ) ) : '' ) . '>';

                                            echo '<label id="pms-label-expiration-date-end" for="pms-datepicker-expiration-date-end">' . esc_html__( 'End of Interval', 'paid-member-subscriptions' ) . '</label>';
                                            echo '<input id="pms-datepicker-expiration-date-end" type="text" name="pms-datepicker-expiration-date-end" class="datepicker value="'. ( !empty( $_GET['pms-datepicker-expiration-date-end'] ) ? esc_attr( sanitize_text_field( $_GET['pms-datepicker-expiration-date-end'] ) ) : '' ) . '">';
                                        echo '</div>';
                                        /*
                                         * Filter button
                                         *
                                         */
                                        echo '<input class="button button-secondary" id="pms-filter-button" type="submit" value="' . esc_html__( 'Filter', 'paid-member-subscriptions' ) . '" />';

                                    ?>

                                    <div class="clear"></div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>

        </div>
    </form>

</div>

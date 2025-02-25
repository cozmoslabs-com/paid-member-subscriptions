<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * HTML output for the payments admin page
 */
?>

<div class="wrap">

    <h1 class="wp-heading-inline">
        <?php echo esc_html( $this->page_title ); ?>
        <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/member-payments/?utm_source=wpbackend&utm_medium=pms-documentation&utm_campaign=PMSDocs" target="_blank" data-code="f223" class="pms-docs-link dashicons dashicons-editor-help"></a>
        <a href="<?php echo esc_url( add_query_arg( array( 'page' => $this->menu_slug, 'pms-action' => 'add_payment' ), admin_url( 'admin.php' ) ) ); ?>" class="add-new-h2 page-title-action"><?php echo esc_html__( 'Add New', 'paid-member-subscriptions' ); ?></a>
    </h1>

    <form method="get">
        <input type="hidden" name="page" value="pms-payments-page" />

        <?php
            $this->list_table->prepare_items();
            $this->list_table->views();
            $this->list_table->search_box( esc_html__( 'Search Payments', 'paid-member-subscriptions' ),'pms_search_payments' );
        ?>

        <div id="poststuff">

            <div id="post-body" class="metabox-holder columns-2">

                <div id="post-body-content">
                    <?php
                    $this->list_table->display();
                    ?>
                </div>

                <div id="postbox-container-1" class="postbox-container filter-payments-sidebox">

                    <div id="side-sortables" class="meta-box-sortables ui-sortable">
                        <div class="postbox">

                            <!-- Meta-box Title -->
                            <h2 class="hndle">
                                <span>
                                    <?php esc_html_e( 'Filter by', 'paid-member-subscriptions' ); ?>
                                </span>
                            </h2>

                            <div class="submitbox">
                                <div id="major-publishing-actions">
                                    <div>
                                        <?php
                                        /*
                                        * Add a custom select box to filter the list by Subscription Plans
                                        */
                                        $subscription_plans = pms_get_subscription_plans( false );
                                        ?>
                                        <select name="pms-filter-subscription-plan" class="pms-filter-select" id="pms-filter-subscription-plan">
                                            <option value=""><?php esc_html_e( 'Subscription Plan...', 'paid-member-subscriptions' ); ?></option>

                                            <?php foreach( $subscription_plans as $subscription_plan ) : ?>
                                                <option value="<?php echo esc_attr( $subscription_plan->id ); ?>" <?php echo !empty( $_GET['pms-filter-subscription-plan'] ) ? selected( $subscription_plan->id, sanitize_text_field( $_GET['pms-filter-subscription-plan'] ), false ) : ''; ?>><?php echo esc_html( $subscription_plan->name ); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <?php
                                        /**
                                         * Action to add more filters
                                         */
                                        do_action( 'pms_payments_list_extra_filters', 'top' );
                                    ?>

                                    <?php
                                        $payment_types = $this->list_table->get_used_payment_types();
                                    ?>

                                    <div>
                                        <select name="pms-filter-payment-type" class="pms-filter-select" id="pms-filter-payment-type">
                                            <option value=""><?php esc_html_e( 'Payment Type...', 'paid-member-subscriptions' ); ?></option>

                                            <?php foreach( $payment_types as $value => $label ) : ?>
                                                    <option value="<?php echo esc_attr( $value ); ?>" <?php echo !empty( $_GET['pms-filter-payment-type'] ) ? selected( $value, sanitize_text_field( $_GET['pms-filter-payment-type'] ), false ) : ''; ?>><?php echo esc_html( $label ); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <?php
                                        $payment_gateways = pms_get_payment_gateways();
                                        $payment_gateways_keys = array_keys( $payment_gateways );
                                    ?>

                                    <div>
                                        <select name="pms-filter-payment-gateway" class="pms-filter-select" id="pms-filter-payment-gateway">
                                            <option value=""><?php esc_html_e( 'Payment Gateway...', 'paid-member-subscriptions' ); ?></option>
                                            <?php 
                                            $i = 0;
                                            foreach( $payment_gateways as $payment_gateway ) :
                                                if( isset( $payment_gateway['display_name_admin'] ) ) : ?>
                                                    <option value="<?php echo esc_attr( $payment_gateways_keys[$i] ); ?>" <?php echo !empty( $_GET['pms-filter-payment-gateway'] ) ? selected( $payment_gateways_keys[$i], sanitize_text_field( $_GET['pms-filter-payment-gateway'] ), false ) : ''; ?>><?php echo esc_html( $payment_gateway['display_name_admin'] ); ?></option>
                                                <?php endif;
                                                $i++;
                                            endforeach; ?>
                                        </select>
                                    </div>

                                    <div>
                                        <select name="pms-filter-date" class="pms-filter-select" id="pms-filter-date">
                                            <option value=""><?php esc_html_e( 'Date...', 'paid-member-subscriptions' ); ?></option>
                                            <option value="today" <?php echo !empty( $_GET['pms-filter-date'] ) ? selected( "today", sanitize_text_field( $_GET['pms-filter-date'] ), false ) : ''; ?>><?php esc_html_e( 'Today', 'paid-member-subscriptions' ); ?></option>
                                            <option value="this_month" <?php echo !empty( $_GET['pms-filter-date'] ) ? selected( "this_month", sanitize_text_field( $_GET['pms-filter-date'] ), false ) : ''; ?>><?php esc_html_e( 'This Month', 'paid-member-subscriptions' ); ?></option>
                                            <option value="this_year" <?php echo !empty( $_GET['pms-filter-date'] ) ? selected( "this_year", sanitize_text_field( $_GET['pms-filter-date'] ), false ) : ''; ?>><?php esc_html_e( 'This Year', 'paid-member-subscriptions' ); ?></option>
                                            <option value="last_week" <?php echo !empty( $_GET['pms-filter-date'] ) ? selected( "last_week", sanitize_text_field( $_GET['pms-filter-date'] ), false ) : ''; ?>><?php esc_html_e( 'Last 7 Days', 'paid-member-subscriptions' ); ?></option>
                                            <option value="last_month" <?php echo !empty( $_GET['pms-filter-date'] ) ? selected( "last_month", sanitize_text_field( $_GET['pms-filter-date'] ), false ) : ''; ?>><?php esc_html_e( 'Last 30 Days', 'paid-member-subscriptions' ); ?></option>
                                            <option value="last_year" <?php echo !empty( $_GET['pms-filter-date'] ) ? selected( "last_year", sanitize_text_field( $_GET['pms-filter-date'] ), false ) : ''; ?>><?php esc_html_e( 'Last Year', 'paid-member-subscriptions' ); ?></option>
                                            <option value="custom" <?php echo !empty( $_GET['pms-filter-date'] ) ? selected( "custom", sanitize_text_field( $_GET['pms-filter-date'] ), false ) : ''; ?>><?php esc_html_e( 'Custom', 'paid-member-subscriptions' ); ?></option>
                                        </select>
                                    </div>

                                    <div class="cozmoslabs-custom-interval" id="pms-date-interval" style="display: none;">
                                        <label id="pms-label-date-start" for="pms-datepicker-date-start"><?php esc_html_e( 'Start of Interval', 'paid-member-subscriptions' ); ?></label>
                                        <input id="pms-datepicker-date-start" type="text" name="pms-datepicker-date-start" class="datepicker" value="<?php echo !empty( $_GET['pms-datepicker-date-start'] ) ? esc_attr( sanitize_text_field( $_GET['pms-datepicker-date-start'] ) ) : ''; ?>">

                                        <label id="pms-label-date-end" for="pms-datepicker-date-end"><?php esc_html_e( 'End of Interval', 'paid-member-subscriptions' ); ?></label>
                                        <input id="pms-datepicker-date-end" type="text" name="pms-datepicker-date-end" class="datepicker" value="<?php echo !empty( $_GET['pms-datepicker-date-end'] ) ? esc_attr( sanitize_text_field( $_GET['pms-datepicker-date-end'] ) ) : ''; ?>">
                                    </div>

                                    <?php do_action( 'pms_payments_list_extra_filters', 'bottom' ); ?>

                                    <a href="<?php echo esc_url( add_query_arg( array( 'page' => $this->menu_slug ), admin_url( 'admin.php' ) ) ); ?>" style="visibility:hidden;margin-left:auto;" id="pms-filter-clear-filters"><?php esc_html_e( 'Clear Filters', 'paid-member-subscriptions' ); ?></a>

                                    <input class="button button-secondary" id="pms-filter-button" type="submit" value="<?php esc_html_e( 'Filter', 'paid-member-subscriptions' ); ?>" />

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

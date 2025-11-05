<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * HTML output for the members admin edit member page
 */
?>


<div class="wrap cozmoslabs-wrap" id="pms-edit-member-page">

    <h1></h1>
    <!-- WordPress Notices are added after the h1 tag -->

    <div class="cozmoslabs-page-header">
        <div class="cozmoslabs-section-title">

            <h3 class="cozmoslabs-page-title"><?php echo esc_html__( 'Edit Member', 'paid-member-subscriptions' ); ?></h3>

        </div>
    </div>

    <form id="pms-form-edit-member" class="pms-form" method="POST">

        <?php if( isset( $_REQUEST['member_id'] ) ) : ?>
            <?php $member = pms_get_member( (int)sanitize_text_field( $_REQUEST['member_id'] ) );
            $user_profile_url = get_edit_user_link( $member->user_id );
            ?>

            <div class="cozmoslabs-form-subsection-wrapper" id="cozmoslabs-subsection-member-sub-list">
                <h3 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Subscriptions', 'paid-member-subscriptions' ); ?></h3>

                <div class="cozmoslabs-form-field-wrapper">

                    <label class="cozmoslabs-form-field-label"><?php echo esc_html__( 'Username', 'paid-member-subscriptions' ); ?></label>
                    <input type="hidden" id="pms-member-user-id" name="pms-member-user-id" class="widefat" value="<?php echo esc_attr( $member->user_id ); ?>" />

                    <input id="pms-member-username" type="text" name="pms-member-username" value="<?php echo esc_html( $member->username ); ?>" disabled="">

                    <a href="<?php echo esc_url( $user_profile_url ); ?>" title="Edit User"><?php echo esc_html__( 'Edit User', 'paid-member-subscriptions' ); ?></a>

                </div>

                <?php do_action( 'pms_member_edit_after_username', $member->user_id ); ?>

                <div class="cozmoslabs-form-field-wrapper">
                    <?php
                        $member_subscriptions_table = new PMS_Member_Subscription_List_Table( $member->user_id );
                        $member_subscriptions_table->prepare_items();
                        $member_subscriptions_table->display();
                    ?>
                </div>
            </div>

            <div class="cozmoslabs-form-subsection-wrapper" id="cozmoslabs-subsection-member-payment-list">
                <h3 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Recent Payments', 'paid-member-subscriptions' ); ?></h3>

                <div class="cozmoslabs-form-field-wrapper">
                    <?php
                        $member_payments_table = new PMS_Member_Payments_List_Table();
                        $member_payments_table->prepare_items();
                        $member_payments_table->display();
                    ?>
                </div>
            </div>

            <?php do_action( 'pms_member_edit_form_field' ); ?>

            <?php wp_nonce_field( 'pms_member_nonce' ); ?>
        <?php endif; ?>

    </form>

</div>

<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * HTML Output for the Uninstall page
 */
?>
<div class="wrap pms-wrap pms-uninstall-wrap cozmoslabs-wrap">

    <div id="cozmoslabs-uninstall-pms-header">
        <h1 class="cozmoslabs-page-title"><?php esc_html_e( 'Uninstall Paid Member Subscriptions', 'paid-member-subscriptions' ); ?></h1>
        <p class="cozmoslabs-description"><?php esc_html_e( 'We\'re sad to see you leave, but we understand that sometimes things don\'t work out as planned.', 'paid-member-subscriptions' ); ?></p>
    </div>

    <!-- Uninstall details -->
    <div class="cozmoslabs-form-subsection-wrapper">
        <h2 class="cozmoslabs-subsection-title"><?php esc_html_e( 'DataBase cleanup information', 'paid-member-subscriptions' ); ?></h2>
        <p class="cozmoslabs-description" style="margin-bottom: 5px;"><?php esc_html_e( 'Below you have information about what will be removed from your database.', 'paid-member-subscriptions' ); ?></p>
        <p class="cozmoslabs-description"><?php esc_html_e( 'Please be advised that once this information is removed it cannot be recovered.', 'paid-member-subscriptions' ); ?></p>

        <div class="cozmoslabs-form-field-wrapper">
            <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Custom Options', 'paid-member-subscriptions' ); ?></label>
            <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'Removes all custom options, used by Paid Member Subscriptions, from the "Options" table of the database.', 'paid-member-subscriptions' ); ?></p>
        </div>

        <div class="cozmoslabs-form-field-wrapper">
            <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Custom User Roles', 'paid-member-subscriptions' ); ?></label>
            <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'Removes all custom user roles created by Paid Member Subscriptions. These user roles will be removed for all users that have them.', 'paid-member-subscriptions' ); ?></p>
        </div>

        <div class="cozmoslabs-form-field-wrapper">
            <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Custom Database Tables', 'paid-member-subscriptions' ); ?></label>
            <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'Removes all information stored in our custom database tables and deletes these tables from your database.', 'paid-member-subscriptions' ); ?></p>
        </div>

        <div class="pms-uninstall-warning">
            <h4><?php esc_html_e( 'WARNING', 'paid-member-subscriptions' ); ?></h4>
            <p class="cozmoslabs-description cozmoslabs-notice-message"><?php esc_html_e( 'All information stored by Paid Member Subscriptions will be removed from your database in the Uninstall process and cannot be recovered.', 'paid-member-subscriptions' ); ?></p>
            <p class="cozmoslabs-description cozmoslabs-notice-message"><?php esc_html_e( 'Please do a backup of your database before proceeding.', 'paid-member-subscriptions' ); ?></p>
        </div>

        <div class="pms-uninstall-actions">
            <a class="button button-primary thickbox" href="#TB_inline?width=400&amp;height=210&amp;inlineId=pms-uninstall-confirmation"><?php echo esc_html__( 'Uninstall', 'paid-member-subscriptions' ); ?></a>
            <a class="button button-secondary" href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>"><?php echo esc_html__( 'Cancel', 'paid-member-subscriptions' ); ?></a>
        </div>
    </div>

    <!-- Uninstall Confirmation thickbox -->
    <?php add_thickbox(); ?>
    <div id="pms-uninstall-confirmation" style="display: none;">
        <form action="" method="POST">
            <h3><?php echo esc_html__( 'Confirm Uninstall', 'paid-member-subscriptions' ); ?></h3>
            <p><?php echo wp_kses_post( __( 'To confirm the Uninstall process please type the word <strong>REMOVE</strong> in the field below and then click the Uninstall button.', 'paid-member-subscriptions' ) ); ?></p>


            <div class="pms-uninstall-confirmation-actions">
                <input id="pms-confirm-uninstall" type="text" autocomplete="off" name="pms-confirm-uninstall" />

                <div class="pms-uninstall-thickbox-footer">
                    <input type="hidden" name="pmstkn" value="<?php echo esc_attr( wp_create_nonce( 'pms_uninstall_nonce', 'pmstkn' ) ) ?>" />

                    <input type="submit" disabled name="pms-confirm-uninstall-submit" class="button button-primary" value="<?php echo esc_html__( 'Uninstall', 'paid-member-subscriptions' ); ?>" />
                    <a id="pms-confirm-uninstall-cancel" class="button button-secondary" href="#"><?php echo esc_html__( 'Cancel', 'paid-member-subscriptions' ); ?></a>
                </div>
            </div>

        </form>
    </div>

</div>
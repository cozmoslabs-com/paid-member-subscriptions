<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'show_user_profile', 'pms_display_subscriptions_details' );
add_action( 'show_user_profile', 'pms_gdpr_agreement' );

add_action( 'edit_user_profile', 'pms_display_subscriptions_details' );
add_action( 'edit_user_profile', 'pms_gdpr_agreement' );

function pms_display_subscriptions_details( $user ){

    echo '<h2>' . esc_html__( 'Subscriptions', 'paid-member-subscriptions' ) . '</h2>';

    $all_data = pms_get_member( $user->ID );
    $subscriptions = $all_data->get_subscriptions();

    if( empty( $subscriptions ) ){

        echo '<p>' . esc_html__('User does not have any subscriptions.', 'paid-member-subscriptions' ) . '</p>';
        echo '<a href="' . esc_url( add_query_arg( array( 'page' => 'pms-members-page', 'subpage' => 'add_subscription', 'member_id' => $user->ID ), admin_url( 'admin.php' ) ) ) . '" class="button-primary">' . esc_html__( 'Add New Subscription', 'paid-member-subscriptions' ) . '</a>';
    }
    else{

        foreach ( $subscriptions as $subscription ){

            $all_subscription_plan_info = pms_get_subscription_plan( $subscription['subscription_plan_id'] );
            $all_subscription_member_info = pms_get_member_subscription( $subscription['id'] );
?>
            <table class="form-table">
            <tr>
                <th><?php echo esc_html__( 'Plan Name', 'paid-member-subscriptions' ); ?></th>
                <td><?php  echo esc_html( $all_subscription_plan_info->name ); ?></td>
            </tr>

            <tr>
                <th><?php echo esc_html__( 'Start Date', 'paid-member-subscriptions' ); ?></th>
                <td>
                    <?php

                     $date_time = new DateTime( $subscription['start_date'] );
                     $formated_start_date = $date_time->format( 'd/m/Y' );
                     echo esc_html( $formated_start_date );

                    ?>
                </td>
            </tr>

            <tr>
                <th><?php echo esc_html__( 'Expiration Date', 'paid-member-subscriptions' ); ?></th>
                <td>
                    <?php
                        if( empty( $all_subscription_member_info->expiration_date ) ){

                            $expired_date = $all_subscription_member_info->billing_next_payment;
                            $date_time = new DateTime( $expired_date );
                            $formated_expiration_date = $date_time->format( 'd/m/Y' );
                            echo esc_html( $formated_expiration_date );
                        }
                        else{

                            $date_time = new DateTime( $subscription['expiration_date'] );
                            $formated_expiration_date = $date_time->format( 'd/m/Y' );
                            echo esc_html( $formated_expiration_date );
                        }

                    ?>
                </td>
            </tr>
<?php
            if( $all_subscription_member_info->status === 'active' && !empty( $all_subscription_member_info->billing_duration ) && !empty( $all_subscription_member_info->billing_duration_unit ) ){
?>
                <tr>
                    <th><?php echo esc_html__( 'Next Payment Date', 'paid-member-subscriptions' ); ?></th>
                    <td>
                        <?php echo esc_html( $formated_expiration_date ); ?>
                    </td>
                </tr>

                <tr>
                    <th><?php echo esc_html__( 'Auto Renewal', 'paid-member-subscriptions' ); ?></th>
                    <td><?php echo esc_html__( 'On', 'paid-member-subscriptions' ); ?></td>
                </tr>
            </table>
<?php
            }

    }
?>

        <table class="form-table">
            <tr>
                <th><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pms-members-page', 'subpage' => 'edit_member', 'member_id' => $user->ID ), admin_url( 'admin.php' ) ) ) ?>" title="<?php esc_attr_e( 'Edit Member', 'paid-member-subscriptions' ) ?>" class="button-secondary"><?php echo esc_html__( 'Edit Member', 'paid-member-subscriptions' ); ?></a></th>
            </tr>
        </table>

<?php
    }
}

function pms_gdpr_agreement( $user ){

    $gdpr_settings = pms_get_gdpr_settings();

    if( !empty( $gdpr_settings ) &&  !empty( $gdpr_settings['gdpr_checkbox'] ) && $gdpr_settings['gdpr_checkbox'] === 'enabled' ){

        $gdpr_agreement_time = get_user_meta( $user->ID, 'pms_gdpr_user_consent_time',true );

        if( $gdpr_agreement_time ){

            $gdpr_formated_time = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),  $gdpr_agreement_time  + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );
            ?>
            <table class="form-table">
                <tr>
                    <th><?php echo esc_html__( 'GDPR', 'paid-member-subscriptions' ); ?></th>
                    <td><?php echo esc_html__( 'Agreed on ', 'paid-member-subscriptions' ) . esc_html( $gdpr_formated_time ); ?></td>
                </tr>
            </table>
            <?php
        }
        else{
            ?>
            <table class="form-table">
                <tr>
                    <th><?php echo esc_html__( 'GDPR', 'paid-member-subscriptions' ); ?></th>
                    <td><?php echo esc_html__( 'Not Agreed', 'paid-member-subscriptions' ); ?></td>
                </tr>
            </table>
            <?php
        }
    }

}
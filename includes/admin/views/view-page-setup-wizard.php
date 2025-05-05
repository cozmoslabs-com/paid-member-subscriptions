<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$steps_completion = PMS_Setup_Wizard::get_completed_progress_steps();
?>

<div class="pms-setup-holder">

    <div class="pms-setup-wrap">

        <img class="pms-setup-logo" src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ); ?>assets/images/pms-banner.png" alt="Paid Member Subscriptions" />

        <ul class="pms-setup-steps">
            <?php foreach( $this->steps as $step => $label ) :
                //if current step index is greater than the loop step index, we know that the loop step is completed
                $completed = array_search( $this->step, array_keys( $this->steps ), true ) > array_search( $step, array_keys( $this->steps ), true );

                if( $this->step === $step ) : ?>
                    <li class="active"><?php echo esc_html( $label ); ?></li>
                <?php elseif( $completed ) : ?>
                    <li class="active <?php echo $steps_completion[$step] && $steps_completion[$step] == 1 ? 'completed' : ''; ?>">
                        <a href="<?php echo esc_url( add_query_arg( 'step', $step ) ); ?>"><?php echo esc_html( $label ); ?></a>
                    </li>
                <?php else : ?>
                    <li><?php echo esc_html( $label ); ?></li>
                <?php endif;
            endforeach; ?>
        </ul>

        <div class="pms-setup-content">
            <?php include_once 'setup-wizard/view-tab-' . $this->step . '.php'; ?>
        </div>

        <div class="pms-setup-background"></div>
    </div>

    <div class="pms-setup-skip">
        <div class="pms-setup-skip__action">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=pms-dashboard-page' ) ); ?>"><?php esc_html_e( 'Skip Setup', 'paid-member-subscriptions' ); ?></a>
        </div>
    </div>

    <style>
        .pms-notice, .wppb-notice, .trp-notice, .notice { display: none !important; }
    </style>

</div>
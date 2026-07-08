<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<?php echo pms_get_subscription_plan_integrations_markup( $subscription_plan->id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

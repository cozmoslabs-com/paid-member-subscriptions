<?php

$pms_misc_settings = get_option('pms_misc_settings', array());

if (isset($pms_misc_settings, $pms_misc_settings['disable-change-button'])) {
    add_filter('pms_output_subscription_plan_action_change', '__return_empty_string');
}
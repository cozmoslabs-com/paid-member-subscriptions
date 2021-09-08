<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// recaptcha
if( file_exists( PMS_PLUGIN_DIR_PATH . 'includes/modules/recaptcha/index.php' ) )
    include_once PMS_PLUGIN_DIR_PATH . 'includes/modules/recaptcha/index.php';

// labels edit
if( file_exists( PMS_PLUGIN_DIR_PATH . 'includes/modules/labels-edit/index.php' ) )
    include_once PMS_PLUGIN_DIR_PATH . 'includes/modules/labels-edit/index.php';

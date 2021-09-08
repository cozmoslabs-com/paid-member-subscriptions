<?php
/**
 * Paid Member Subscriptions - Labels Edit Add-on
 * License: GPL2
 *
 * == Copyright ==
 * Copyright 2019 Cozmoslabs (www.cozmoslabs.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;


Class PMS_IN_LabelsEdit {

    public $countries;

    public function __construct() {

        define( 'PMS_IN_LABELSEDIT_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
        define( 'PMS_IN_LABELSEDIT_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );

        $this->load_dependencies();
        $this->init();
    }

    /**
     * Initialise plugin components
     *
     */
    private function init() {

        //setup page and main metabox
        add_action( 'init', array( $this, 'setup_page' ), 20 );

        //setup side metaboxes
        add_action( 'add_meta_boxes', array( $this, 'setup_metaboxes' ) );

        //enqueue scripts
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 20 );

        //handle WCK errors
        add_filter( 'wck_extra_message', array( $this, 'check_for_errors' ), 10, 6 );

        //reinit chosen JS after a refresh
        add_action( 'wck_ajax_add_form_pmsle', array( $this, 'refresh_chosen' ) );
        add_action( 'wck_after_adding_form_pmsle', array( $this, 'refresh_chosen' ) );

        //delete all fields option
        add_action( 'wck_metabox_content_header_pmsle', array( $this, 'add_delete_all_option' ) );
        add_action( 'wp_ajax_pmsle_delete_all_fields', array( $this, 'delete_all_fields' ) );

        //rescan labels
        add_action( 'admin_init', array( $this, 'rescan_labels' ) );
        add_action( 'admin_notices', array( $this, 'rescan_labels_success_message' ) );

        //export labels
        add_action( 'admin_init', array( $this, 'export' ) );

        //import labels
        add_action( 'admin_init', array( $this, 'import' ) );

        //remove gettext filter from ajax
        add_action( 'wp_ajax_wck_add_formpmsle', array( $this, 'remove_gettext_filter_from_ajax' ) );
        add_action( 'wp_ajax_wck_refresh_listpmsle', array( $this, 'remove_gettext_filter_from_ajax' ) );
        add_action( 'wp_ajax_wck_refresh_entrypmsle', array( $this, 'remove_gettext_filter_from_ajax' ) );

        //remove gettext filter from current screen
        add_action( 'current_screen', array( $this, 'remove_gettext_from_screen' ) );

        //change strings
        add_filter( 'gettext', array( $this, 'change_strings' ), 8, 3 );
        add_filter( 'ngettext', array( $this,'change_ngettext_strings' ), 8, 5 );

        //scan strings if we don't have any yet
        add_action( 'admin_init', array( $this, 'init_strings' ) );
    }

    private function load_dependencies() {

        if ( is_admin() ) {

            // WCK API
            if( file_exists( PMS_IN_LABELSEDIT_PLUGIN_DIR_PATH . 'assets/lib/wck-api/wordpress-creation-kit.php' ) )
                include PMS_IN_LABELSEDIT_PLUGIN_DIR_PATH . 'assets/lib/wck-api/wordpress-creation-kit.php';

        }

    }

    public function setup_page() {

    	$args = array(
    		'menu_title' 	=> __( 'Labels Edit', 'paid-member-subscriptions' ),
    		'page_title' 	=> __( 'Labels Edit', 'paid-member-subscriptions' ),
    		'menu_slug'		=> 'pms-labels-edit',
    		'page_type'		=> 'submenu_page',
    		'capability'	=> 'manage_options',
    		'priority'		=> 25,
    		'parent_slug'	=> 'paid-member-subscriptions'
    	);

    	if( class_exists( 'WCK_Page_Creator_PMSLE' ) )
    		new WCK_Page_Creator_PMSLE( $args );

    	// array with Profile Builder strings to edit
    	$strings = get_option( 'pmsle_backup', array() );

    	// array with fields for Edit Labels metabox
    	$metabox_fields = array(
    		array( 'type' => 'select', 'slug' => 'pmsle-label', 'title' => __( 'Label to Edit', 'paid-member-subscriptions' ), 'default-option' => true, 'values' => $strings, 'options' => $strings, 'description' => 'Here you will see the default label so you can copy it.' ),
    		array( 'type' => 'textarea', 'slug' => 'pmsle-newlabel', 'title' => __( 'New Label', 'paid-member-subscriptions' ) ),
    	);

    	// create Edit Labels metabox
    	$labels_edit_metabox_args = array(
    		'metabox_id' 	=> 'pmsle-id',
    		'metabox_title' => __( 'Edit Labels', 'paid-member-subscriptions' ),
    		'post_type' 	=> 'pms-labels-edit',
    		'meta_name' 	=> 'pmsle',
    		'meta_array' 	=> $metabox_fields,
    		'context'		=> 'option'
    	);

    	if( class_exists( 'Wordpress_Creation_Kit_PMSLE_IN' ) )
    		new Wordpress_Creation_Kit_PMSLE_IN( $labels_edit_metabox_args );

    }

    public function setup_metaboxes() {

        // otherwise it doesn't work on a localized website where this is translated.
	    $screen = sanitize_title( __( 'Paid Member Subscriptions', 'paid-member-subscriptions' ) ) . '_page_pms-labels-edit' ;

	    add_meta_box(
            'pmsle-id-side',
            __( 'Rescan Lables', 'paid-member-subscriptions' ),
            array( $this, 'rescan_metabox' ),
		    $screen,
            'side'
        );

        add_meta_box(
            'pmsle-id-side-info',
            __( 'Informations', 'paid-member-subscriptions' ),
            array( $this, 'info_metabox' ),
	        $screen,
            'side'
        );

        add_meta_box(
            'pmsle-id-side-impexp',
            __( 'Import and Export Labels', 'paid-member-subscriptions' ),
            array( $this, 'import_export_metabox' ),
	        $screen,
            'side'
        );

    }

    public function rescan_metabox() {
        ?>
    	<div class="wrap">
    		<p>Rescan all Paid Member Subscriptions labels.</p>

    		<form action="" method="post">
    			<input type="submit" class="button-primary" name="pmsle_rescan" value="Rescan" />
    		</form>
    	</div>
    <?php
    }

    public function info_metabox() {
        ?>
    	<div class="wrap">
    		<p><b>Variables:</b></p>
    		<ul>
    			<li>%1$s</li>
    			<li>%2$s</li>
    			<li>%s</li>
    			<li>etc.</li>
    		</ul>
    		<p><b>Place them like in the default string!</b></p>
    		<p>Example:</p>
    		<p>
    			<b>Old Label:</b><br>in %1$d sec, click %2$s.%3$s<br>
    			<b>New Label:</b><br>click %2$s.%3$s in %1$d sec<br>
    		</p>
    		<a href="http://www.cozmoslabs.com/?p=40126" target="_blank">Read more detailed informations</a>
    	</div>
    <?php
    }

    public function import_export_metabox() {
    ?>
    	<p>
    		<?php esc_html_e( 'Import Labels from a .json file.', 'paid-member-subscriptions' ); ?>
    		<br>
    		<?php esc_html_e( 'Easily import the labels from another site.', 'paid-member-subscriptions' ); ?>
    	</p>
    	<form name="pmsle-upload" method="post" action="" enctype= "multipart/form-data">
    		<div class="wrap">
    			<input type="file" name="pmsle-upload" value="pmsle-upload" id="pmsle-upload" />
    		</div>
    		<div class="wrap">
    			<input class="button-primary" type="submit" name="pmsle-import" value=<?php esc_attr_e( 'Import', 'paid-member-subscriptions' ); ?> id="pmsle-import" onclick="return confirm( '<?php esc_html_e( 'This will overwrite all your old edited labels! \n\rAre you sure you want to continue?', 'paid-member-subscriptions' ); ?>' )" />
    		</div>
    	</form>
    	<hr>
    	<p>
    		<?php esc_html_e( 'Export Labels as a .json file.', 'paid-member-subscriptions' ); ?>
    		<br>
    		<?php esc_html_e( 'Easily import the labels into another site.', 'paid-member-subscriptions' ); ?>
    	</p>
    	<div class="wrap">
    		<form action="" method="post"><input class="button-primary" type="submit" name="pmsle-export" value=<?php esc_attr_e( 'Export', 'paid-member-subscriptions' ); ?> id="pmsle-export" /></form>
    	</div>
    <?php
    }

    public function enqueue_scripts( $hook ) {
        if( $hook == 'paid-member-subscriptions_page_pms-labels-edit' ) {
            wp_enqueue_script( 'pmsle_init',      plugin_dir_url( __FILE__ ) . 'assets/js/init.js', array( 'jquery' ), PMS_VERSION );
            wp_enqueue_script( 'pmsle_chosen',    PMS_PLUGIN_DIR_URL . 'assets/libs/chosen/chosen.jquery.min.js', array( 'jquery' ), PMS_VERSION );
            wp_enqueue_style( 'pmsle_chosen_css', PMS_PLUGIN_DIR_URL . 'assets/libs/chosen/chosen.css', array(), PMS_VERSION );
            wp_enqueue_style( 'pmsle_css',        plugin_dir_url( __FILE__ ) . 'assets/css/style.css', array(), PMS_VERSION );
        }
    }

    public function check_for_errors( $message, $fields, $required_fields, $meta_name, $posted_values, $post_id ) {
        if ( $meta_name == 'pmsle' ) {

            if( $posted_values['pmsle-label'] == '' )
                $message = __( "You must select a label to edit!", 'paid-member-subscriptions' );
        }

        return $message;
    }

    public function refresh_chosen() {
        echo "<script type=\"text/javascript\">pmsle_chosen(); pmsle_description( jQuery( '.update_container_pmsle .mb-select' ) ); </script>";
    }

    public function add_delete_all_option() {
        return '<thead><tr><th class="wck-number">#</th><th class="wck-content">'. esc_html__( 'Labels', 'paid-member-subscriptions' ) .'</th><th class="wck-edit">'. esc_html__( 'Edit', 'paid-member-subscriptions' ) .'</th><th class="wck-delete"><a id="wppb-delete-all-fields" class="wppb-delete-all-fields" onclick="pmsle_delete_all_fields(event, this.id, \'' . esc_js( wp_create_nonce( 'pmsle-delete-all-entries' ) ) . '\')" title="' . esc_html__( 'Delete all', 'paid-member-subscriptions' ) . '" href="#">'. esc_html__( 'Delete all', 'paid-member-subscriptions' ) .'</a></th></tr></thead>';
    }

    public function delete_all_fields() {
        check_ajax_referer( 'pmsle-delete-all-entries' );

        if( ! empty( $_POST['meta'] ) )
            $meta_name = sanitize_text_field( $_POST['meta'] );
        else
            $meta_name = '';

        if( $meta_name == 'pmsle' )
            delete_option( 'pmsle' );

        exit;
    }

    public function scan_labels() {
        include_once PMS_IN_LABELSEDIT_PLUGIN_DIR_PATH . 'includes/potx.php';

        global $pms_countries;
        $pms_countries = array_values( pms_get_countries() );

        $iterator = new RecursiveDirectoryIterator( PMS_PLUGIN_DIR_PATH );

        $directories = array(
            'translations'
        );

        global $pms_strings;
        $pms_strings = array();

        // loop through directory and get _e() and __() function calls
        foreach( new RecursiveIteratorIterator( $iterator ) as $filename => $current_file ) {
            // http://php.net/manual/en/class.splfileinfo.php
            if( isset( $current_file ) ) {

                $current_file_pathinfo = pathinfo( $current_file );

                if( in_array( $this->get_directory_name( $current_file_pathinfo['dirname'] ), $directories ) ) {

                    if( ! empty( $current_file_pathinfo['extension'] ) && $current_file_pathinfo['extension'] == "php" ) {

                        if( file_exists( $current_file ) )
                            _pms_in_potx_process_file( realpath( $current_file ), 0, 'pms_le_output_string' );
                    }
                }
            }
        }

        update_option( 'pmsle_backup', '', 'no' );
        update_option( 'pmsle_backup', $pms_strings );
    }

    private function get_directory_name( $path ) {
        return str_replace( PMS_PLUGIN_DIR_PATH, '', $path );
    }

    public function rescan_labels() {
        if( isset( $_POST['pmsle_rescan'] ) )
            $this->scan_labels();
    }

    public function rescan_labels_success_message() {
    	if( isset( $_POST['pmsle_rescan'] ) ) {
    		global $pms_strings;

    		echo '<div id="message" class="updated"><p>' . count( $pms_strings ) . esc_html__( ' labels scanned.', 'paid-member-subscriptions' ) . '</p></div>';
    	}
    }

    public function export() {
        if( isset( $_POST['pmsle-export'] ) ) {
            include PMS_IN_LABELSEDIT_PLUGIN_DIR_PATH . 'includes/class-pmsle-export.php';

            $check_export = get_option( 'pmsle', 'not_set' );

            if( empty( $check_export ) || $check_export === 'not_set' ) {
                echo '<div id="message" class="error"><p>' . esc_html__('No labels edited, nothing to export!', 'paid-member-subscriptions') . '</p></div>';
            } else {
                $args = array(
                    'pmsle'
                );

                $prefix = 'PMSLE_';
                $export = new PMSLE_IN_Export( $args );
                $export->download_to_json_format( $prefix );
            }
        }
    }

    public function import() {
        if( isset( $_POST['pmsle-import'] ) ) {
            include PMS_IN_LABELSEDIT_PLUGIN_DIR_PATH . 'includes/class-pmsle-import.php';

            if( isset( $_FILES['pmsle-upload'] ) ) {
                $args = array(
                    'pmsle'
                );

                $import = new PMSLE_IN_Import( $args );
                $import->upload_json_file();

                $messages = $import->get_messages();

                foreach ( $messages as $message ) {
                    echo '<div id="message" class='. esc_attr( $message['type'] ) .'><p>'. esc_html( $message['message'] ) .'</p></div>';
                }
            }
        }
    }

    public function remove_gettext_filter_from_ajax() {
        remove_filter( 'gettext', array( $this, 'change_strings' ), 8 );
    }

    public function remove_gettext_from_screen( $screen ) {
        if( is_object( $screen ) && $screen->id == 'paid-member-subscriptions_page_pms-labels-edit' )
            remove_filter( 'gettext', array( $this, 'change_strings' ), 8 );
    }

    public function change_strings( $translated_text, $text, $domain ) {
        if( $domain != 'paid-member-subscriptions' )
            return $translated_text;

        $edited_labels = get_option( 'pmsle', false );

        if( empty( $edited_labels ) || $edited_labels == false )
            return $translated_text;

        if( is_array( $edited_labels ) ) {
            foreach( $edited_labels as $label ) {

                if( $text === $label['pmsle-label'] ) {
                    $translated_text = wp_kses_post( $label['pmsle-newlabel'] );
                    break;
                }

            }
        }

        return $translated_text;
    }

    public function change_ngettext_strings( $translated_text, $single, $plural, $number, $domain ){
        if( $domain != 'paid-member-subscriptions' )
            return $translated_text;

        $edited_labels = get_option( 'pmsle', false );

        if( empty( $edited_labels ) || $edited_labels == false )
            return $translated_text;

        if( is_array( $edited_labels ) ) {
            foreach( $edited_labels as $label ) {
                if( $single === $label['pmsle-label'] ) {
                    $translated_text = wp_kses_post( $label['pmsle-newlabel'] );
                    break;
                }
                if( $plural === $label['pmsle-label'] ) {
                    $translated_text = wp_kses_post( $label['pmsle-newlabel'] );
                    break;
                }
            }
        }

        return $translated_text;
    }

    //we want to exclude Countries from the strings list
    static function check_string( $string ) {
        global $pms_countries;

        if ( in_array( $string, $pms_countries ) )
            return false;

        return true;
    }

    public function init_strings() {
        $strings = get_option( 'pmsle_backup', false );

        if ( empty( $strings ) )
            $this->scan_labels();
    }
}

function pms_in_ledit_init() {

    $settings = get_option( 'pms_misc_settings', false );

    if( !empty( $settings ) && isset( $settings['labels-edit'] ) && $settings['labels-edit'] == 'enabled' )
        new PMS_IN_LabelsEdit;

}
add_action( 'plugins_loaded', 'pms_in_ledit_init', 11 );

function pms_in_le_output_string( $string ) {
    global $pms_strings;

    if( is_array( $pms_strings ) && ! in_array( $string, $pms_strings ) && PMS_IN_LabelsEdit::check_string( $string ) ) {
        $pms_strings[] = $string;
    }
}

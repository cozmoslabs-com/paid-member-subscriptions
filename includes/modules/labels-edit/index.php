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


Class PMS_IN_LabelsEdit extends PMS_Submenu_Page {

    /**
     * Method that initializes class
     *
     */
    public function init() {

        define( 'PMS_IN_LABELSEDIT_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
        define( 'PMS_LABELSEDIT_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );

        // Enqueue admin scripts
        add_action( 'pms_submenu_page_enqueue_admin_scripts_' . $this->menu_slug, array( $this, 'admin_scripts' ) );

        // Hook the output method to the parent's class action for output instead of overwriting the
        // output method
        add_action( 'pms_output_content_submenu_page_' . $this->menu_slug, array( $this, 'output' ) );

        // Process different actions within the page
        add_action( 'admin_init', array( $this, 'process_data' ) );

        //change strings
        add_filter( 'gettext', array( $this, 'change_strings' ), 8, 3 );
        add_filter( 'ngettext', array( $this,'change_ngettext_strings' ), 8, 5 );

        //scan strings if we don't have any yet
        add_action( 'admin_init', array( $this, 'init_strings' ) );

        //remove gettext filter from current screen
        add_action( 'current_screen', array( $this, 'remove_gettext_from_screen' ) );

    }

    /*
     * Method to enqueue admin scripts
     *
     */
    public function admin_scripts() {

        global $wp_scripts;

        // Try to detect if chosen has already been loaded
        $found_chosen = false;

        foreach( $wp_scripts as $wp_script ) {
            if( !empty( $wp_script['src'] ) && strpos( $wp_script['src'], 'chosen' ) !== false )
                $found_chosen = true;
        }

        if( !$found_chosen ) {
            wp_enqueue_script( 'pms-chosen', PMS_PLUGIN_DIR_URL . 'assets/libs/chosen/chosen.jquery.min.js', array( 'jquery' ), PMS_VERSION );
            wp_enqueue_style( 'pms-chosen', PMS_PLUGIN_DIR_URL . 'assets/libs/chosen/chosen.css', array(), PMS_VERSION );
        }

        wp_enqueue_script( 'jquery-ui-sortable' );

        wp_register_script( 'pmsle_init', PMS_LABELSEDIT_PLUGIN_DIR_URL . 'assets/js/init.js', array( 'jquery', 'pms-chosen' ), PMS_VERSION );
        wp_enqueue_style( 'pmsle_css', PMS_LABELSEDIT_PLUGIN_DIR_URL . 'assets/css/style.css', array(), PMS_VERSION );
        wp_enqueue_style( 'jquery-style', PMS_PLUGIN_DIR_URL . 'assets/css/admin/jquery-ui.min.css', array(), PMS_VERSION );
        

        wp_localize_script( 'pmsle_init', 'pmsle_update_button_text', array( 'text' => esc_html__( 'Update', 'paid-member-subscriptions' ) ) );
        wp_enqueue_script( 'pmsle_init' );
    }

    /**
     * Returns a custom message by the provided code
     *
     * @param int $code
     *
     * @return string
     *
     */
    protected function get_message_by_code( $code = 0 ) {

        $messages = array(
            1 => esc_html__( 'Label added successfully.', 'paid-member-subscriptions' ),
            2 => esc_html__( 'You must select a label to edit!', 'paid-member-subscriptions' ),
            3 => esc_html__( 'Label updated successfully.', 'paid-member-subscriptions' ),
            4 => esc_html__( 'Label deleted successfully.', 'paid-member-subscriptions' ),
            5 => esc_html__( 'All labels deleted successfully.', 'paid-member-subscriptions' ),
            6 => esc_html__( 'Labels rescanned successfully.', 'paid-member-subscriptions' ),
            7 => esc_html__( 'Label edited successfully.', 'paid-member-subscriptions' )
        );

        return ( ! empty( $messages[$code] ) ? $messages[$code] : '' );

    }

    /*
     * Method that processes data on payment admin pages
     *
     */
    public function process_data() {

        // These processes should be handled only by an admin
        if( !current_user_can( 'manage_options') )
            return;

        /*
         *  Handle add new label
         */
        if( ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['_wpnonce'] ), 'pmsle_add_entry_nonce' ) ) {

            if( !empty( $_POST['pmsle-label-select'] ) && isset( $_POST['pmsle-newlabel'] ) ){

                $select_label = str_replace( '\\\\\\', '\\', $_POST['pmsle-label-select'] ); /* phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized */ /* escaped on output */
                $newlabel = str_replace( '\\\\\\', '\\', $_POST['pmsle-newlabel'] ); /* phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized */ /* escaped on output */

                $edited_labels = get_option( 'pmsle', false );

                if( !empty( $edited_labels ) ){
                    $i = 0;
                    foreach( $edited_labels as $label ){

                        // Edit existing label
                        if( $label['pmsle-label'] == $select_label ){
                            $edited_labels[$i]['pmsle-newlabel'] = $newlabel;
                            $edited = true;
                            break;
                        }
                        $i++;
                    }
                }

                // Add new label
                if( !isset( $edited ) ){
                    $edited_labels[] = array(
                        'pmsle-label'      => $select_label,
                        'pmsle-newlabel'   => $newlabel
                    );
                }

                update_option( 'pmsle', $edited_labels, false );

                if( isset( $edited ) )
                    wp_redirect( add_query_arg( array( 'page' => 'pms-labels-edit', 'message' => '7', 'updated' => '1' ), admin_url( 'admin.php' ) ) );
                else
                    wp_redirect( add_query_arg( array( 'page' => 'pms-labels-edit', 'message' => '1', 'updated' => '1' ), admin_url( 'admin.php' ) ) );
            }
            else{
                wp_redirect( add_query_arg( array( 'page' => 'pms-labels-edit', 'message' => '2', 'error' => '1' ), admin_url( 'admin.php' ) ) );
            }

        }

        /*
         *  Handle delete label
         */
        if( ! empty( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'pmsle_delete_label_nonce' ) ) {

            if( isset( $_GET['pmsle_label_id'] ) ){

                $edited_labels = get_option( 'pmsle', false );

                if( !empty( $edited_labels ) ){

                    array_splice( $edited_labels, intval( $_GET['pmsle_label_id'] ), 1 );
                    update_option( 'pmsle', $edited_labels, false );

                    wp_redirect( add_query_arg( array( 'page' => 'pms-labels-edit', 'message' => '4', 'updated' => '1' ), admin_url( 'admin.php' ) ) );

                }

            }

        }

        /*
         *  Handle delete all labels
         */
        if( ! empty( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'pmsle_delete_all_nonce' ) ) {
            update_option( 'pmsle', array(), false );
            wp_redirect( add_query_arg( array( 'page' => 'pms-labels-edit', 'message' => '5', 'updated' => '1' ), admin_url( 'admin.php' ) ) );
        }

        /*
         *  Handle rescan labels
         */
        if( ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['_wpnonce'] ), 'pmsle_rescan_nonce' ) ) {
            if( isset( $_POST['pmsle_rescan'] ) ){
                $this->scan_labels();

                wp_redirect( add_query_arg( array( 'page' => 'pms-labels-edit', 'message' => '6', 'updated' => '1' ), admin_url( 'admin.php' ) ) );
            }
        }

        /*
         *  Handle import labels
         */
        if( ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['_wpnonce'] ), 'pmsle_import_nonce' ) ) {
            $this->import();
        }

        /*
         *  Handle export labels
         */
        if( ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['_wpnonce'] ), 'pmsle_export_nonce' ) ) {
            $this->export();
        }

    }

    /**
     * Method to output content in the custom page
     *
     */
    public function output() {
        include_once PMS_IN_LABELSEDIT_PLUGIN_DIR_PATH . 'includes/views/view-page-labels-edit.php';
    }

    public function edit_labels_metabox(){
        ?>
            <form method="post">
                <div class="cozmoslabs-form-field-wrapper">
                    <label for="pmsle-label" class="cozmoslabs-form-field-label"><?php echo esc_html__( 'Label to Edit:', 'paid-member-subscriptions' ); ?></label>

                    <?php

                    $strings = get_option( 'pmsle_backup', array() );

                    /*
                     * Display Labels Edit select
                     */
                    echo '<select name="pmsle-label-select" class="pmsle-label-select" id="pmsle-label-select">';
                    echo '<option value="">' . esc_html__( '...Choose', 'paid-member-subscriptions' ) . '</option>';
                    $i = 0;
                    foreach( $strings as $string ){
                        if( isset( $strings[ $i ] ) )
                            echo '<option value="' . esc_attr( $strings[ $i ] ) . '">' . esc_html( $strings[ $i ] ) . '</option>';
                        $i++;
                    }
                    echo '</select>';

                    ?>
                    <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Here you will see the default label so you can copy it.', 'paid-member-subscriptions' ) ?></p>

                </div>

                <div class="cozmoslabs-form-field-wrapper">
                    <label for="pmsle-newlabel-textarea" class="cozmoslabs-form-field-label"><?php echo esc_html__( 'New Label:', 'paid-member-subscriptions' ); ?></label>
                    <textarea name="pmsle-newlabel" id="pmsle-newlabel-textarea"></textarea>
                </div>

                <input id="pmsle-submit" type="submit" class="button-primary" value="<?php esc_html_e( 'Add Entry', 'paid-member-subscriptions' ); ?>"/>

                <?php
                    wp_nonce_field( 'pmsle_add_entry_nonce' );

                    echo '<table id="pmsle-table" class="widefat">';
                    $edited_labels = get_option( 'pmsle', false );
                    if( !empty( $edited_labels ) ){
                        echo '<thead>';
                            echo '<tr class="pmsle-header">';
                                echo '<th class="pmsle-table-number">' . esc_html__( '#', 'paid-member-subscriptions' ) . '</th>';
                                echo '<th class="pmsle-table-label">' . esc_html__( 'Labels', 'paid-member-subscriptions' ) . '</th>';
                                echo '<th class="pmsle-table-edit">' . esc_html__( 'Edit', 'paid-member-subscriptions' ) . '</th>';
                                echo '<th class="pmsle-table-delete"><a id="pmsle-delete-all-fields" onclick="return confirm( \'' . esc_html__( "Are you sure you want to delete all items?", "paid-member-subscriptions" ) . ' \' )" href="' . esc_url( wp_nonce_url( add_query_arg( array( 'page' => 'pms-labels-edit' ) ), 'pmsle_delete_all_nonce' ) ). '">' . esc_html__( 'Delete all', 'paid-member-subscriptions' ) . '</a></th>';
                            echo '</tr>';
                        echo '</thead>';
                        echo '<tbody class="sortable">';
                        $i = 0;
                        foreach( $edited_labels as $label ){
                            if( isset( $edited_labels[ $i ] ) ) {

                                $alternate_row = '';
                                if ( $i % 2 === 0 )
                                    $alternate_row = 'alternate';

                                echo '<tr id="pmsle-table-element-' . esc_attr( $i ) . '" class='. esc_html( $alternate_row ) .' >';
                                    echo '<td class="pmsle-table-number">' . esc_attr( $i + 1 ) . '</td>';
                                    echo '<td class="pmsle-table-label">';
                                        echo '<ul>';
                                            echo '<li><strong>' . esc_html__( 'Label to Edit:', 'paid-member-subscriptions' ) . '</strong><pre id="pmsle-label-' . esc_attr( $i ) . '">' . esc_attr( $label['pmsle-label'] ) . '</pre></li>';
                                            echo '<li><strong>' . esc_html__( 'New Label:', 'paid-member-subscriptions' ) . '</strong><pre id="pmsle-newlabel-' . esc_attr( $i ) . '">' . esc_attr( $label['pmsle-newlabel'] ) . '</pre></li>';
                                        echo '</ul>';
                                    echo '</td>';
                                    echo '<td id="pmsle-edit-item-' . esc_attr( $i ) .'" class="pmsle-table-edit"><a class="button-secondary" >' . esc_html__( 'Edit', 'paid-member-subscriptions' ) . '</a></td>';
                                    echo '<td class="pmsle-table-delete"><a class="delete cozmoslabs-remove-item" onclick="return confirm( \'' . esc_html__( "Delete this item?", "paid-member-subscriptions" ) . ' \' )" href="' . esc_url( wp_nonce_url( add_query_arg( array( 'page' => 'pms-labels-edit', 'pmsle_label_id' => $i ) ), 'pmsle_delete_label_nonce' ) ) . '"><span class="dashicons dashicons-no-alt"></span></a></td>';
                                echo '</tr>';
                            }
                            $i++;
                        }
                        echo '</tbody>';
                    }
                    echo '</table>';

                ?>

            </form>
    <?php
    }

    public function rescan_metabox() {
        ?>

        <div class="cozmoslabs-form-field-wrapper">

            <form action="" method="post">
                <input type="submit" class="button-primary" name="pmsle_rescan" value="Rescan" />
                <?php wp_nonce_field( 'pmsle_rescan_nonce' ); ?>
            </form>

            <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'Rescan all Paid Member Subscriptions labels.', 'paid-member-subscriptions' ); ?></p>

        </div>


    <?php
    }

    public function info_metabox() {
        ?>

        <div class="cozmoslabs-form-field-wrapper">
            <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Variables', 'paid-member-subscriptions' ); ?></label>

            <ul class="pmsle-var-list">
                <li>%1$s</li>
                <li>%2$s</li>
                <li>%s</li>
                <li>etc.</li>
            </ul>

            <p class="cozmoslabs-description"><?php esc_html_e( 'Place them like in the default string.', 'paid-member-subscriptions' ); ?></p>
        </div>
        <div id="pmsle-example" class="cozmoslabs-form-field-wrapper">
            <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Example:', 'paid-member-subscriptions' ); ?></label>
            <div class="pmsle-example-container">
                <p class="cozmoslabs-description"><strong><?php esc_html_e( 'Old Label:', 'paid-member-subscriptions' ); ?></strong> in %1$d sec, click %2$s.%3$s</p>
                <p class="cozmoslabs-description"><strong><?php esc_html_e( 'New Label:', 'paid-member-subscriptions' ); ?></strong> click %2$s.%3$s in %1$d sec</p>
            </div>
            <p class="cozmoslabs-description cozmoslabs-description-space-left">
                <a href="http://www.cozmoslabs.com/?p=40126" target="_blank"><?php esc_html_e( 'Read more detailed information', 'paid-member-subscriptions' ); ?></a>
            </p>
        </div>

    <?php
    }

    public function import_export_metabox() {
    ?>

        <div class="cozmoslabs-form-field-wrapper">
            <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Import Labels', 'paid-member-subscriptions' ); ?></label>

            <form name="pmsle-upload" method="post" action="" enctype= "multipart/form-data">
                <input class="button-primary" type="submit" name="pmsle-import" value=<?php esc_attr_e( 'Import', 'paid-member-subscriptions' ); ?> id="pmsle-import" onclick="return confirm( '<?php esc_html_e( 'This will overwrite all your old edited labels! \n\rAre you sure you want to continue?', 'paid-member-subscriptions' ); ?>' )" />
                <input type="file" name="pmsle-upload" value="pmsle-upload" id="pmsle-upload" />
                <?php wp_nonce_field( 'pmsle_import_nonce' ); ?>
            </form>

            <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Import Labels from a .json file.', 'paid-member-subscriptions' ); ?></p>
            <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Easily import the labels from another site.', 'paid-member-subscriptions' ); ?></p>
        </div>

        <div class="cozmoslabs-form-field-wrapper">
            <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Export Labels', 'paid-member-subscriptions' ); ?></label>

            <form action="" method="post"><input class="button-primary" type="submit" name="pmsle-export" value=<?php esc_attr_e( 'Export', 'paid-member-subscriptions' ); ?> id="pmsle-export" />
                <?php wp_nonce_field( 'pmsle_export_nonce' ); ?>
            </form>

            <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Export Labels as a .json file.', 'paid-member-subscriptions' ); ?></p>
            <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Easily import the labels into another site.', 'paid-member-subscriptions' ); ?></p>
        </div>

    <?php
    }

    private function scan_labels() {
        include_once PMS_IN_LABELSEDIT_PLUGIN_DIR_PATH . 'includes/potx.php';

        global $pms_countries;
        $pms_countries = array_values( pms_get_countries() );

        global $pms_strings;
        $pms_strings = array();

        $current_file = PMS_PLUGIN_DIR_PATH . "translations/paid-member-subscriptions.catalog.php";

        if( file_exists( $current_file ) ){
            _pms_in_potx_process_file( realpath( $current_file ), 0, 'pms_in_le_output_string' );
        }

        update_option( 'pmsle_backup', '', 'no' );
        update_option( 'pmsle_backup', $pms_strings, false );
    }

    private function get_directory_name( $path ) {
        return str_replace( PMS_PLUGIN_DIR_PATH, '', $path );
    }

    private function export() {
        if( isset( $_POST['pmsle-export'] ) ) {
            include PMS_IN_LABELSEDIT_PLUGIN_DIR_PATH . 'includes/class-pmsle-export.php';

            $check_export = get_option( 'pmsle', 'not_set' );

            if( empty( $check_export ) || $check_export === 'not_set' ) {
                $this->add_admin_notice( esc_html__( 'No labels edited, nothing to export!', 'paid-member-subscriptions' ), 'error' );
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

    private function import() {
        if( isset( $_POST['pmsle-import'] ) ) {
            include PMS_IN_LABELSEDIT_PLUGIN_DIR_PATH . 'includes/class-pmsle-import.php';

            if( isset( $_FILES['pmsle-upload'] ) ) {
                $args = array(
                    'pmsle'
                );

                $import = new PMSLE_IN_Import( $args );
                $import->upload_json_file();

                $messages = $import->get_messages();
                foreach( $messages as $message ){
                    if( $message['type'] == 'error' ) {
                        $this->add_admin_notice( esc_html__( 'Please select a .json file to import!', 'paid-member-subscriptions' ), 'error' );
                        return;
                    }
                }
                $this->add_admin_notice( esc_html__( 'Labels imported successfully.', 'paid-member-subscriptions' ), 'updated' );
            }
        }
    }

    public function remove_gettext_from_screen( $screen ) {
        $parent_menu_slug = sanitize_title( __( 'Paid Member Subscriptions', 'paid-member-subscriptions' ) );

        if( is_object( $screen ) && $screen->id == $parent_menu_slug . '_page_pms-labels-edit' )
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

function pms_in_le_output_string( $string ) {
    global $pms_strings;

    if( is_array( $pms_strings ) && ! in_array( $string, $pms_strings ) && PMS_IN_LabelsEdit::check_string( $string ) ) {
        $pms_strings[] = $string;
    }
}

add_action( 'init', 'pms_in_le_init' );
function pms_in_le_init() {

    // Initialize Labels Edit module if selected accordingly in Settings
    $pms_misc_settings = get_option( 'pms_misc_settings', array() );

    if( isset( $pms_misc_settings['labels-edit'] ) && $pms_misc_settings['labels-edit'] == 'enabled' ){
        $pms_labels_edit = new PMS_IN_LabelsEdit( 'paid-member-subscriptions', esc_html__( 'Labels Edit', 'paid-member-subscriptions' ), esc_html__( 'Labels Edit', 'paid-member-subscriptions' ), 'manage_options', 'pms-labels-edit', 25 );
        $pms_labels_edit->init();
    }

}
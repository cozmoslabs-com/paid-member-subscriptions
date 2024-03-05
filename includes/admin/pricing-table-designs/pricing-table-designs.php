<?php

if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Generate the Pricing Table Page Design Selector to PMS -> Create Pricing Page Button
 *
 */
function pms_render_pricing_tables_design_selector() {

    $pricing_table_designs_data = pms_get_pricing_table_designs_data();

    wp_enqueue_script( 'jquery-ui-dialog' );
    wp_enqueue_style( 'wp-jquery-ui-dialog' );

    $output = '<div id="pms-pricing-tables-design-browser">';

    foreach ( $pricing_table_designs_data as $pricing_table_design ) {

//        if ( $pricing_table_design['status'] == 'active' ) {
//            $status = ' active';
//            $title = '<strong>Active: </strong> ' . $pricing_table_design['name'];
//        } else {
//            $status = '';
//            $title = $pricing_table_design['name'];
//        }

        if ( $pricing_table_design['id'] != 'pricing-table-style-default' )
            $preview_button = '<div class="pms-pricing-tables-design-preview button-secondary" id="'. $pricing_table_design['id'] .'-info">Preview</div>';
        else $preview_button = '';

        $output .= '
                <div class="pms-pricing-tables-design" id="'. $pricing_table_design['id'] .'">
                    <label for="pms-pt-option-' . $pricing_table_design['id'] . '">
                        <input type="radio" id="pms-pt-option-' . $pricing_table_design['id'] . '" value="' . $pricing_table_design['id'] . '" name="pms_general_settings[pricing_tables_design]" ' . ( ( $pricing_table_design['status'] === 'active' ) ? 'checked="checked"' : '') . '>
                        ' . $pricing_table_design['name'] . '
                   
                       <div class="pms-pricing-tables-design-screenshot">
                         <img src="' . $pricing_table_design['images']['main'] . '" alt="Pricing Table Design">
                          '. $preview_button .'
                       </div>
                   </label>
                </div>
        ';

        $img_count = 0;
        $image_list = '';
        foreach ( $pricing_table_design['images'] as $image ) {
            $img_count++;
            $active_img = ( $img_count == 1 ) ? ' active' : '';
            $image_list .= '<img class="pms-pricing-tables-design-preview-image'. $active_img .'" src="'. $image .'">';
        }

        if ( $img_count > 1 ) {
            $previous_button = '<div class="pms-slideshow-button pms-pricing-tables-design-sildeshow-previous disabled" data-theme-id="'. $pricing_table_design['id'] .'" data-slideshow-direction="previous"> < </div>';
            $next_button = '<div class="pms-slideshow-button pms-pricing-tables-design-sildeshow-next" data-theme-id="'. $pricing_table_design['id'] .'" data-slideshow-direction="next"> > </div>';
            $justify_content = 'space-between';
        }
        else {
            $previous_button = $next_button = '';
            $justify_content = 'center';
        }

        $output .= '<div id="pms-modal-'. $pricing_table_design['id'] .'" class="pms-pricing-tables-design-modal" title="'. $pricing_table_design['name'] .'">
                        <div class="pms-pricing-tables-design-modal-slideshow" style="justify-content: '. $justify_content .'">
                            '. $previous_button .'
                            <div class="pms-pricing-tables-design-modal-images">
                                '. $image_list .'
                            </div>
                            '. $next_button .'
                        </div>
                    </div>';

    }

    $output .= '</div>';

    return $output;
}


/**
 * Get Pricing Table Designs Data
 *
 */
function pms_get_pricing_table_designs_data() {
    $active_design = pms_get_active_pricing_table_design();

    $pricing_table_designs = array(
        array(
            'id' => 'pricing-table-style-default',
            'name' => 'Default Style',
            'status' => $active_design == 'pricing-table-style-default' ? 'active' : '',
            'images' => array(
                'main' => PMS_PLUGIN_DIR_URL.'assets/images/pms-pt-style-default.png',
            ),
        ),
        array(
            'id' => 'pricing-table-style-1',
            'name' => 'Sublime',
            'status' => $active_design == 'pricing-table-style-1' ? 'active' : '',
            'images' => array(
                'main' => PMS_PLUGIN_DIR_URL.'assets/images/pms-pt-style1-slide1.png',
//                'slide1' => PMS_PLUGIN_DIR_URL.'assets/images/pms-pt-style1-slide1.png',
            ),
        ),
        array(
            'id' => 'pricing-table-style-2',
            'name' => 'Greenery',
            'status' => $active_design == 'pricing-table-style-2' ? 'active' : '',
            'images' => array(
                'main' => PMS_PLUGIN_DIR_URL.'assets/images/pms-pt-style2-slide1.png',
//                'slide1' => PMS_PLUGIN_DIR_URL.'assets/images/pms-pt-style2-slide1.png',
            ),
        ),
        array(
            'id' => 'pricing-table-style-3',
            'name' => 'Slim',
            'status' => $active_design == 'pricing-table-style-3' ? 'active' : '',
            'images' => array(
                'main' => PMS_PLUGIN_DIR_URL.'assets/images/pms-pt-style3-slide1.png',
//                'slide1' => PMS_PLUGIN_DIR_URL.'assets/images/pms-pt-style3-slide1.png',
            ),
        )
    );

    return $pricing_table_designs;
}


/**
 * Get the Pricing Table Designs active Style
 *
 */
function pms_get_active_pricing_table_design() {

    $current_page_id = get_the_ID();

    $active_design = get_post_meta( $current_page_id, 'pms_pricing_page_design',true );

    if( empty( $active_design ) || $active_design == 'pricing-table-style-default')
        $active_design = 'pricing-table-style-default';

    return $active_design;
}


/**
 * Add Pricing Table Design active Style wrapper
 */
function pms_add_pricing_table_styling( $output, $extra_data ) {
    $active_design = pms_get_active_pricing_table_design();

    if ( $active_design == 'pricing-table-style-default'  )
        return $output;

    $edited_output = '<div class="pms-pricing-table-design-wrapper" id="pms-'. $active_design .'-wrapper">'; // the wrapper helps when overwriting form styles (more specific targeting)
    $edited_output .= $output;
    $edited_output .= '</div>';

    return $edited_output;
}

/**
 * Load Pricing Table Design Feature Scripts and Styles
 *
 */
function pms_enqueue_pricing_table_design_styles() {

    $active_design = pms_get_active_pricing_table_design();

    if ( $active_design == 'pricing-table-style-default' )
        return;

    $file_path = plugin_dir_url( __FILE__ ) . 'css/pms-pt-'. $active_design .'.css';

    wp_register_style( 'pms_pricing_table_designs_style', $file_path, array(),PMS_VERSION );
    wp_enqueue_style( 'pms_pricing_table_designs_style' );

    wp_enqueue_style( 'pms-style-front-end', PMS_PLUGIN_DIR_URL . 'assets/css/style-front-end.css', array(), PMS_VERSION );
}
add_action('wp_enqueue_scripts' , 'pms_enqueue_pricing_table_design_styles');
add_action('elementor/editor/after_enqueue_styles' , 'pms_enqueue_pricing_table_design_styles');
add_action( 'enqueue_block_editor_assets', 'pms_enqueue_pricing_table_design_styles' );

function pms_enqueue_pricing_table_scripts(){

    wp_enqueue_style( 'wp-jquery-ui-dialog' );

    //This part of the code is for enqueue scripts in the future

//    $active_design = pms_get_active_form_design();
//
//    if ( $active_design == 'pricing-table-style-default' )
//        return;
//
//    $file = 'pms-pricing-table-design.js';
//
//    $file_path = plugin_dir_url( __FILE__ ) . 'js/'.$file;
//
//    wp_enqueue_script( 'pms_pricing_table_designs_script', $file_path, array( 'jquery' ), PMS_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'pms_enqueue_pricing_table_scripts' );
add_action('wp_enqueue_scripts' , 'pms_enqueue_pricing_table_scripts');
add_action('elementor/editor/after_enqueue_scripts' , 'pms_enqueue_pricing_table_scripts');
add_action( 'enqueue_block_editor_assets', 'pms_enqueue_pricing_table_scripts' );
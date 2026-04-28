<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Admin Bar Widget for Paid Member Subscriptions
 * 
 * Adds a PMS menu item to the WordPress admin bar with quick links
 * to Dashboard, Members, Subscriptions, Payments, and Reports pages
 */
class PMS_Admin_Bar_Widget {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_menu' ), 100 );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
    }

    /**
     * Check if current user has permission to view the admin bar widget
     * 
     * @return bool
     */
    private function has_permission() {
        return pms_current_user_can_access_any_area( array(
            'pms-dashboard-page',
            'pms-members-page',
            'pms-subscriptions-page',
            'pms-payments-page',
            'pms-reports-page',
        ) );
    }

    /**
     * Get the dashboard issues
     * 
     * @return array
     */
    private function get_issues() {       
        return PMS_Submenu_Page_Dashboard::get_dashboard_issues();
    }

    /**
     * Check if there are any critical dashboard issues
     * 
     * @return bool
     */
    private function has_issues() {
        return $this->get_issue_count() > 0;
    }

    /**
     * Get the total count of critical issues only
     * 
     * @return int
     */
    private function get_issue_count() {
        $issues = $this->get_issues();
        $interpreted_issues = PMS_Submenu_Page_Dashboard::interpret_dashboard_issues( $issues );
        
        // Count only critical issues
        $critical_count = 0;
        foreach( $interpreted_issues as $issue ) {
            if( isset( $issue['severity'] ) && $issue['severity'] === 'critical' ) {
                $critical_count++;
            }
        }
        
        return $critical_count;
    }

    /**
     * Add menu items to the admin bar
     * 
     * @param WP_Admin_Bar $wp_admin_bar
     */
    public function add_admin_bar_menu( $wp_admin_bar ) {
        $areas = array(
            'pms-dashboard-page' => array(
                'id'    => 'pms-dashboard',
                'title' => __( 'Dashboard', 'paid-member-subscriptions' ),
            ),
            'pms-members-page' => array(
                'id'    => 'pms-members',
                'title' => __( 'Members', 'paid-member-subscriptions' ),
            ),
            'pms-subscriptions-page' => array(
                'id'    => 'pms-subscriptions',
                'title' => __( 'Subscriptions', 'paid-member-subscriptions' ),
            ),
            'pms-payments-page' => array(
                'id'    => 'pms-payments',
                'title' => __( 'Payments', 'paid-member-subscriptions' ),
            ),
            'pms-reports-page' => array(
                'id'    => 'pms-reports',
                'title' => __( 'Reports', 'paid-member-subscriptions' ),
            ),
        );

        $available_areas = array();

        foreach ( $areas as $area_slug => $area_data ) {

            if ( pms_current_user_can_access_area( $area_slug ) )
                $available_areas[ $area_slug ] = $area_data;

        }

        if ( empty( $available_areas ) )
            return;

        // Prefer Dashboard as the parent link, otherwise fall back to the first visible area.
        $parent_area = isset( $available_areas['pms-dashboard-page'] ) ? 'pms-dashboard-page' : array_key_first( $available_areas );

        // Add parent menu item with logo
        $wp_admin_bar->add_node( array(
            'id'    => 'pms-admin-bar',
            'title' => $this->get_menu_title(),
            'href'  => admin_url( 'admin.php?page=' . $parent_area ),
            'meta'  => array(
                'class' => 'pms-admin-bar-menu',
            ),
        ) );

        foreach ( $available_areas as $area_slug => $area_data ) {

            $title = $area_data['title'];

            if ( $area_slug === 'pms-dashboard-page' && $this->has_issues() ) {
                $title  = __( 'Issues', 'paid-member-subscriptions' );
                $title .= ' <span class="pms-notification-badge">' . esc_html( $this->get_issue_count() ) . '</span>';
            }

            $wp_admin_bar->add_node( array(
                'id'     => $area_data['id'],
                'parent' => 'pms-admin-bar',
                'title'  => $title,
                'href'   => admin_url( 'admin.php?page=' . $area_slug ),
            ) );

        }
    }

    /**
     * Get the menu title with logo and notification badge if needed
     * 
     * @return string
     */
    private function get_menu_title() {
        $icon_url = PMS_PLUGIN_DIR_URL . 'assets/images/pms-wp-menu-icon.svg';
        
        $title = '<span class="pms-admin-bar-icon"><img src="' . esc_url( $icon_url ) . '" alt="PMS" /></span>' .
                 '<span class="pms-admin-bar-text">' . esc_html__( 'Memberships', 'paid-member-subscriptions' ) . '</span>';
        
        // Add notification badge to main item if there are issues
        if ( pms_current_user_can_access_area( 'pms-dashboard-page' ) && $this->has_issues() ) {
            $title .= ' <span class="pms-notification-badge pms-main-badge"></span>';
        }
        
        return $title;
    }

    /**
     * Enqueue custom styles for the admin bar widget
     */
    public function enqueue_styles() {
        
        // Only load if user has permission
        if ( ! $this->has_permission() ) {
            return;
        }

        // Add inline CSS for the admin bar widget
        $custom_css = "
            #wpadminbar .pms-admin-bar-icon {
                display: flex;
                align-items: center;
                margin-right: 5px;
            }
            
            #wpadminbar .pms-admin-bar-icon img {
                width: 20px;
                height: 20px;
                vertical-align: middle;
                opacity: .6;
            }
            
            #wpadminbar .pms-admin-bar-text {
                vertical-align: middle;
            }
            
            #wpadminbar .pms-notification-badge {
                display: inline-block;
                background-color: #d63638;
                border-radius: 10px;
                margin-left: 5px;
                vertical-align: middle;
                color: #fff;
                font-size: 11px;
                line-height: 18px;
                text-align: center;
                padding: 0 5px;
            }
            
            #wpadminbar .pms-notification-badge.pms-main-badge {
                min-width: 8px;
                width: 8px;
                height: 8px;
                border-radius: 50%;
                padding: 0;
                font-size: 0;
            }
            
            #wp-admin-bar-pms-admin-bar .ab-item {
                display: flex !important;
                align-items: center;
            }
            
            @media screen and (max-width: 782px) {
                #wpadminbar .pms-admin-bar-text {
                    display: none;
                }
            }
        ";
        
        wp_add_inline_style( 'admin-bar', $custom_css );
    }
}

// Initialize the admin bar widget only if not disabled in settings
$pms_misc_settings = get_option( 'pms_misc_settings', array() );

if ( ! isset( $pms_misc_settings['disable-admin-bar-widget'] ) ) {
    new PMS_Admin_Bar_Widget();
}

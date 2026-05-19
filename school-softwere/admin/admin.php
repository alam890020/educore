<?php
/**
 * Admin bootstrap - hooks, asset enqueue, menu, modules.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

// Core admin classes.
require_once SS_PLUGIN_DIR . 'admin/inc/SS_Database.php';
require_once SS_PLUGIN_DIR . 'admin/inc/SS_Admin_Shell.php';
require_once SS_PLUGIN_DIR . 'admin/inc/SS_Menu.php';

// Manager (super-admin) modules.
require_once SS_PLUGIN_DIR . 'admin/inc/manager/SS_School.php';
require_once SS_PLUGIN_DIR . 'admin/inc/manager/SS_Class.php';
require_once SS_PLUGIN_DIR . 'admin/inc/manager/SS_Session.php';
require_once SS_PLUGIN_DIR . 'admin/inc/manager/SS_Category.php';
require_once SS_PLUGIN_DIR . 'admin/inc/manager/SS_Setting.php';
require_once SS_PLUGIN_DIR . 'admin/inc/manager/SS_LM.php';
require_once SS_PLUGIN_DIR . 'admin/inc/manager/SS_LC.php';
require_once SS_PLUGIN_DIR . 'admin/inc/manager/SS_Dashboard.php';

// School portal modules.
require_once SS_PLUGIN_DIR . 'admin/inc/school/SS_Setup_Wizard.php';
require_once SS_PLUGIN_DIR . 'admin/inc/school/SS_Schools_Assigned.php';
require_once SS_PLUGIN_DIR . 'admin/inc/school/staff/SS_Staff_School.php';
require_once SS_PLUGIN_DIR . 'admin/inc/school/staff/SS_Staff_General.php';
require_once SS_PLUGIN_DIR . 'admin/inc/school/staff/SS_Staff_Class.php';
require_once SS_PLUGIN_DIR . 'admin/inc/school/staff/SS_Staff_Accountant.php';
require_once SS_PLUGIN_DIR . 'admin/inc/school/staff/SS_Staff_Examination.php';
require_once SS_PLUGIN_DIR . 'admin/inc/school/staff/SS_Staff_Library.php';
require_once SS_PLUGIN_DIR . 'admin/inc/school/staff/SS_Staff_Transport.php';
require_once SS_PLUGIN_DIR . 'admin/inc/school/staff/SS_Staff_Hostel.php';
require_once SS_PLUGIN_DIR . 'admin/inc/school/staff/SS_Staff_Notices.php';
require_once SS_PLUGIN_DIR . 'admin/inc/school/staff/SS_Staff_Homework.php';
require_once SS_PLUGIN_DIR . 'admin/inc/school/staff/SS_Staff_Reports.php';
require_once SS_PLUGIN_DIR . 'admin/inc/school/staff/SS_Staff_Tickets.php';
require_once SS_PLUGIN_DIR . 'admin/inc/school/staff/SS_Staff_Leaves.php';
require_once SS_PLUGIN_DIR . 'admin/inc/school/staff/SS_Staff_Inquiries.php';
require_once SS_PLUGIN_DIR . 'admin/inc/school/staff/SS_Staff_Certificates.php';
require_once SS_PLUGIN_DIR . 'admin/inc/school/staff/SS_Staff_Logs.php';
require_once SS_PLUGIN_DIR . 'admin/inc/school/staff/SS_Staff_Lectures.php';
require_once SS_PLUGIN_DIR . 'admin/inc/school/staff/SS_Staff_Meetings.php';
require_once SS_PLUGIN_DIR . 'admin/inc/school/staff/SS_Staff_Activities.php';
require_once SS_PLUGIN_DIR . 'admin/inc/school/staff/SS_Staff_Import.php';
require_once SS_PLUGIN_DIR . 'admin/inc/school/staff/SS_Staff_Export.php';

/**
 * Admin bootstrap class.
 */
class SS_Admin {

    public static function init() {
        add_action( 'admin_menu',           array( __CLASS__, 'register_menu' ) );
        add_action( 'admin_enqueue_scripts',array( __CLASS__, 'enqueue_assets' ) );
        add_action( 'admin_init',           array( __CLASS__, 'maybe_handle_post' ) );
        add_action( 'admin_init',           array( __CLASS__, 'handle_print_route' ) );
        add_action( 'admin_body_class',     array( __CLASS__, 'admin_body_class' ) );
    }

    /**
     * Register all plugin admin pages.
     */
    public static function register_menu() {
        SS_Menu::register();
    }

    /**
     * Enqueue plugin admin CSS/JS only on plugin pages.
     *
     * @param string $hook
     */
    public static function enqueue_assets( $hook ) {
        if ( ! isset( $_GET['page'] ) ) {
            return;
        }
        $page = sanitize_text_field( wp_unslash( $_GET['page'] ) );
        if ( 0 !== strpos( $page, 'school-softwere' ) ) {
            return;
        }

        // Google fonts.
        wp_enqueue_style( 'ss-google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Nunito:wght@600;700;800&display=swap', array(), null );

        // Phosphor icons.
        wp_enqueue_style( 'ss-phosphor', 'https://unpkg.com/@phosphor-icons/web@2.0.3/src/regular/style.css', array(), '2.0.3' );
        wp_enqueue_style( 'ss-phosphor-fill', 'https://unpkg.com/@phosphor-icons/web@2.0.3/src/fill/style.css', array(), '2.0.3' );

        // Vendor: Chart.js, DataTables, Select2, Flatpickr, SweetAlert2, Toastify.
        wp_enqueue_script( 'ss-chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js', array(), '4.4.1', true );
        wp_enqueue_style(  'ss-datatables', 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css', array(), '1.13.6' );
        wp_enqueue_script( 'ss-datatables', 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', array( 'jquery' ), '1.13.6', true );
        wp_enqueue_style(  'ss-select2',    'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0' );
        wp_enqueue_script( 'ss-select2',    'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array( 'jquery' ), '4.1.0', true );
        wp_enqueue_style(  'ss-flatpickr',  'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css', array(), '4.6.13' );
        wp_enqueue_script( 'ss-flatpickr',  'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js', array(), '4.6.13', true );
        wp_enqueue_script( 'ss-sweetalert', 'https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.all.min.js', array(), '11.10.5', true );
        wp_enqueue_style(  'ss-toastify',   'https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css', array(), '1.12.0' );
        wp_enqueue_script( 'ss-toastify',   'https://cdn.jsdelivr.net/npm/toastify-js', array(), '1.12.0', true );

        // Plugin assets.
        wp_enqueue_style( 'ss-admin', SS_PLUGIN_URL . 'assets/css/ss-admin.css', array(), SS_VERSION );
        if ( 'dark' === SS_Config::get( 'theme_mode', 'light' ) ) {
            wp_enqueue_style( 'ss-admin-dark', SS_PLUGIN_URL . 'assets/css/ss-admin-dark.css', array( 'ss-admin' ), SS_VERSION );
        }
        wp_enqueue_script( 'ss-admin-js',  SS_PLUGIN_URL . 'assets/js/ss-admin.js',  array( 'jquery' ), SS_VERSION, true );
        wp_enqueue_script( 'ss-charts-js', SS_PLUGIN_URL . 'assets/js/ss-charts.js', array( 'ss-chart-js' ), SS_VERSION, true );

        wp_localize_script( 'ss-admin-js', 'SSAdmin', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'ss_ajax' ),
            'strings' => array(
                'confirm_delete' => __( 'Are you sure you want to delete this item?', 'school-softwere' ),
                'saved'          => __( 'Saved successfully', 'school-softwere' ),
                'error'          => __( 'An error occurred', 'school-softwere' ),
            ),
        ) );
    }

    /**
     * Add body class on plugin pages.
     *
     * @param string $classes
     * @return string
     */
    public static function admin_body_class( $classes ) {
        if ( isset( $_GET['page'] ) && 0 === strpos( sanitize_text_field( wp_unslash( $_GET['page'] ) ), 'school-softwere' ) ) {
            $classes .= ' ss-app';
            if ( 'dark' === SS_Config::get( 'theme_mode', 'light' ) ) {
                $classes .= ' ss-theme-dark';
            }
        }
        return $classes;
    }

    /**
     * Dispatch generic POST handlers - module classes register their own ss_action_* methods.
     */
    public static function maybe_handle_post() {
        if ( ! is_admin() || empty( $_POST['ss_action'] ) ) {
            return;
        }
        $action = sanitize_key( wp_unslash( $_POST['ss_action'] ) );
        do_action( "ss_handle_post_{$action}" );
    }

    /**
     * Print routes - load template without WP wrapper.
     */
    public static function handle_print_route() {
        if ( empty( $_GET['ss_print'] ) ) {
            return;
        }
        if ( ! is_user_logged_in() ) {
            wp_die( esc_html__( 'You must be logged in.', 'school-softwere' ) );
        }
        $route = sanitize_file_name( wp_unslash( $_GET['ss_print'] ) );
        $file  = SS_PLUGIN_DIR . 'admin/inc/school/print/' . $route . '.php';
        if ( ! file_exists( $file ) ) {
            wp_die( esc_html__( 'Print template not found.', 'school-softwere' ) );
        }
        // Render print template, no WP chrome.
        require $file;
        die();
    }
}

SS_Admin::init();

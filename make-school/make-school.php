<?php
/**
 * Plugin Name: MAKE SCHOOL
 * Plugin URI: https://makeschool.dev
 * Description: A premium, enterprise-grade School Management System for WordPress. Manage branches, classes, admissions, fees, attendance, exams, and results with dedicated portals for admins, teachers, students, and parents.
 * Version: 1.0.0
 * Author: Make School Team
 * Author URI: https://makeschool.dev
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: make-school
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Plugin constants
 */
define( 'MAKE_SCHOOL_VERSION', '1.0.0' );
define( 'MAKE_SCHOOL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MAKE_SCHOOL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MAKE_SCHOOL_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Include required files
 */
require_once MAKE_SCHOOL_PLUGIN_DIR . 'includes/class-make-school-db.php';
require_once MAKE_SCHOOL_PLUGIN_DIR . 'admin/class-make-school-admin.php';
require_once MAKE_SCHOOL_PLUGIN_DIR . 'admin/class-make-school-fees.php';
require_once MAKE_SCHOOL_PLUGIN_DIR . 'public/class-make-school-public.php';

/**
 * Main Plugin Class
 */
final class Make_School {

    /**
     * Singleton instance
     *
     * @var Make_School|null
     */
    private static $instance = null;

    /**
     * Admin handler instance
     *
     * @var Make_School_Admin|null
     */
    public $admin = null;

    /**
     * Fees handler instance
     *
     * @var Make_School_Fees|null
     */
    public $fees = null;

    /**
     * Public handler instance
     *
     * @var Make_School_Public|null
     */
    public $public = null;

    /**
     * Get singleton instance
     *
     * @return Make_School
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - Initialize plugin
     */
    private function __construct() {
        // Register activation hook
        register_activation_hook( __FILE__, array( $this, 'activate' ) );

        // Register deactivation hook
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

        // Initialize plugin after WordPress is fully loaded
        add_action( 'init', array( $this, 'init' ) );

        // Enqueue global scripts and styles
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

        // Login redirect hook for role-based routing
        add_filter( 'login_redirect', array( $this, 'role_based_login_redirect' ), 10, 3 );

        // Block restricted roles from wp-admin
        add_action( 'admin_init', array( $this, 'enforce_backend_lockdown' ) );

        // Hide admin bar for restricted roles
        add_action( 'after_setup_theme', array( $this, 'hide_admin_bar_for_restricted_roles' ) );

        // Initialize controllers
        $this->admin  = new Make_School_Admin();
        $this->fees   = new Make_School_Fees();
        $this->public = new Make_School_Public();
    }

    /**
     * Plugin activation callback
     */
    public function activate() {
        // Create database tables
        Make_School_DB::create_tables();

        // Register custom user roles
        $this->register_roles();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation callback
     */
    public function deactivate() {
        // Flush rewrite rules on deactivation
        flush_rewrite_rules();
    }

    /**
     * Initialize plugin on 'init' hook
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain( 'make-school', false, dirname( MAKE_SCHOOL_PLUGIN_BASENAME ) . '/languages' );
    }

    /**
     * Register custom user roles with specific capabilities
     */
    private function register_roles() {
        // Remove roles first to avoid duplication conflicts on reactivation
        remove_role( 'make_school_admin' );
        remove_role( 'make_school_teacher' );
        remove_role( 'make_school_student' );
        remove_role( 'make_school_parent' );

        // School Admin - Full access to plugin backend
        add_role( 'make_school_admin', __( 'School Admin', 'make-school' ), array(
            'read'                    => true,
            'manage_options'          => true,
            'upload_files'            => true,
            'edit_posts'              => true,
            'make_school_manage_all'  => true,
            'make_school_manage_fees' => true,
            'make_school_manage_exams'=> true,
        ) );

        // Teacher - Attendance and marks entry
        add_role( 'make_school_teacher', __( 'School Teacher', 'make-school' ), array(
            'read'                         => true,
            'make_school_mark_attendance'   => true,
            'make_school_enter_marks'       => true,
            'make_school_view_classes'      => true,
        ) );

        // Student - View own data through frontend portal
        add_role( 'make_school_student', __( 'School Student', 'make-school' ), array(
            'read'                        => true,
            'make_school_view_own_data'   => true,
        ) );

        // Parent - View child data through frontend portal
        add_role( 'make_school_parent', __( 'School Parent', 'make-school' ), array(
            'read'                          => true,
            'make_school_view_child_data'   => true,
        ) );
    }

    /**
     * Role-based login redirect
     *
     * @param string           $redirect_to Default redirect URL.
     * @param string           $requested   Requested redirect URL.
     * @param WP_User|WP_Error $user        WP_User object or WP_Error.
     * @return string Redirect URL.
     */
    public function role_based_login_redirect( $redirect_to, $requested, $user ) {
        if ( ! is_wp_error( $user ) && isset( $user->roles ) && is_array( $user->roles ) ) {

            if ( in_array( 'make_school_student', $user->roles, true ) ) {
                return home_url( '/student-portal/' );
            }

            if ( in_array( 'make_school_parent', $user->roles, true ) ) {
                return home_url( '/student-portal/' );
            }

            if ( in_array( 'make_school_teacher', $user->roles, true ) ) {
                return home_url( '/teacher-portal/' );
            }

            if ( in_array( 'make_school_admin', $user->roles, true ) ) {
                return admin_url( 'admin.php?page=make-school' );
            }

            if ( in_array( 'administrator', $user->roles, true ) ) {
                return admin_url();
            }
        }

        return $redirect_to;
    }

    /**
     * Enforce backend lockdown - block students, parents, and teachers from wp-admin
     */
    public function enforce_backend_lockdown() {
        if ( wp_doing_ajax() ) {
            return;
        }

        $user = wp_get_current_user();

        if ( ! $user || ! $user->exists() ) {
            return;
        }

        $restricted_roles = array( 'make_school_student', 'make_school_parent', 'make_school_teacher' );

        foreach ( $restricted_roles as $role ) {
            if ( in_array( $role, (array) $user->roles, true ) ) {
                if ( in_array( $role, array( 'make_school_student', 'make_school_parent' ), true ) ) {
                    wp_safe_redirect( home_url( '/student-portal/' ) );
                    exit;
                }
                if ( 'make_school_teacher' === $role ) {
                    wp_safe_redirect( home_url( '/teacher-portal/' ) );
                    exit;
                }
            }
        }
    }

    /**
     * Hide the admin bar for restricted roles
     */
    public function hide_admin_bar_for_restricted_roles() {
        if ( ! is_user_logged_in() ) {
            return;
        }

        $user = wp_get_current_user();
        $restricted_roles = array( 'make_school_student', 'make_school_parent', 'make_school_teacher' );

        foreach ( $restricted_roles as $role ) {
            if ( in_array( $role, (array) $user->roles, true ) ) {
                show_admin_bar( false );
                break;
            }
        }
    }

    /**
     * Enqueue public-facing assets
     */
    public function enqueue_public_assets() {
        wp_enqueue_script( 'jquery' );

        wp_enqueue_style(
            'make-school-public',
            MAKE_SCHOOL_PLUGIN_URL . 'assets/css/make-school-public.css',
            array(),
            MAKE_SCHOOL_VERSION
        );

        wp_enqueue_script(
            'make-school-public',
            MAKE_SCHOOL_PLUGIN_URL . 'assets/js/make-school-public.js',
            array( 'jquery' ),
            MAKE_SCHOOL_VERSION,
            true
        );

        wp_localize_script( 'make-school-public', 'MakeSchoolAjax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'make_school_public_nonce' ),
        ) );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets() {
        $screen = get_current_screen();

        if ( ! $screen || strpos( $screen->id, 'make-school' ) === false ) {
            return;
        }

        wp_enqueue_script( 'jquery' );
        wp_enqueue_style( 'wp-jquery-ui-dialog' );
        wp_enqueue_script( 'jquery-ui-datepicker' );

        wp_enqueue_style(
            'make-school-admin',
            MAKE_SCHOOL_PLUGIN_URL . 'assets/css/make-school-admin.css',
            array(),
            MAKE_SCHOOL_VERSION
        );

        wp_enqueue_script(
            'make-school-admin',
            MAKE_SCHOOL_PLUGIN_URL . 'assets/js/make-school-admin.js',
            array( 'jquery', 'jquery-ui-datepicker' ),
            MAKE_SCHOOL_VERSION,
            true
        );

        wp_localize_script( 'make-school-admin', 'MakeSchoolAdmin', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'make_school_admin_nonce' ),
        ) );
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new \Exception( 'Cannot unserialize singleton.' );
    }
}

/**
 * Boot the plugin
 */
function make_school_init() {
    return Make_School::get_instance();
}
add_action( 'plugins_loaded', 'make_school_init' );

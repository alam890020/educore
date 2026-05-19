<?php
/*
 * Plugin Name: School Softwere
 * Plugin URI: https://yoursite.com/school-softwere/
 * Description: School Softwere is a powerful WordPress plugin to manage multiple schools with students, staff, exams, fees, attendance, library, transport, hostel, and much more all in one beautiful dashboard.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yoursite.com
 * Text Domain: school-softwere
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || die();

// Core constants.
require_once __DIR__ . '/includes/constants.php';

// Helpers (autoload-style require).
require_once SS_PLUGIN_DIR . 'includes/helpers/SS_Config.php';
require_once SS_PLUGIN_DIR . 'includes/helpers/SS_Helper.php';
require_once SS_PLUGIN_DIR . 'includes/helpers/SS_M_Role.php';
require_once SS_PLUGIN_DIR . 'includes/helpers/SS_M_Staff_General.php';
require_once SS_PLUGIN_DIR . 'includes/helpers/SS_M_Staff_Class.php';
require_once SS_PLUGIN_DIR . 'includes/helpers/SS_M_Staff_Accountant.php';
require_once SS_PLUGIN_DIR . 'includes/helpers/SS_M_Staff_Examination.php';
require_once SS_PLUGIN_DIR . 'includes/helpers/SS_M_Staff_Library.php';
require_once SS_PLUGIN_DIR . 'includes/helpers/SS_M_Staff_Transport.php';
require_once SS_PLUGIN_DIR . 'includes/helpers/SS_M_Lecture.php';
require_once SS_PLUGIN_DIR . 'includes/helpers/SS_M_Tickets.php';

// Admin and public bootstraps.
require_once SS_PLUGIN_DIR . 'admin/admin.php';
require_once SS_PLUGIN_DIR . 'public/public.php';

/**
 * Main plugin class.
 */
final class SS_Plugin {

    /** @var SS_Plugin|null */
    private static $instance = null;

    /**
     * Singleton accessor.
     *
     * @return SS_Plugin
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        // Lifecycle hooks.
        register_activation_hook( SS_PLUGIN_FILE, array( 'SS_Database', 'activate' ) );
        register_deactivation_hook( SS_PLUGIN_FILE, array( 'SS_Database', 'deactivate' ) );

        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
        add_action( 'init', array( $this, 'init' ) );
    }

    /**
     * Load translations.
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'school-softwere', false, dirname( plugin_basename( SS_PLUGIN_FILE ) ) . '/languages' );
    }

    /**
     * Init hook.
     */
    public function init() {
        // Reserved for future init logic (post types, taxonomies, schedules).
        do_action( 'ss_init' );
    }
}

// Bootstrap.
SS_Plugin::instance();

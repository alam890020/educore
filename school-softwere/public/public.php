<?php
/**
 * Public bootstrap - shortcodes, widgets, REST API.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

// These files only contain plain classes / functions — safe to load immediately.
require_once SS_PLUGIN_DIR . 'public/inc/SS_Login.php';
require_once SS_PLUGIN_DIR . 'public/inc/SS_Noticeboard.php';
require_once SS_PLUGIN_DIR . 'public/inc/SS_Student_Portal.php';
require_once SS_PLUGIN_DIR . 'public/api/SS_REST.php';
// Widget files extend WP_Widget which is not available until after 'widgets_init'.
// They are loaded lazily inside register_widgets() below.

class SS_Public {

    public static function init() {
        add_action( 'init',               array( __CLASS__, 'register_shortcodes' ) );
        add_action( 'widgets_init',       array( __CLASS__, 'register_widgets' ) );
        add_action( 'rest_api_init',      array( 'SS_REST', 'register' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
    }

    public static function register_shortcodes() {
        add_shortcode( 'ss_login',             array( 'SS_Login',          'shortcode' ) );
        add_shortcode( 'ss_noticeboard',       array( 'SS_Noticeboard',    'shortcode' ) );
        add_shortcode( 'ss_events',            array( 'SS_Noticeboard',    'events_shortcode' ) );
        add_shortcode( 'ss_student_dashboard', array( 'SS_Student_Portal', 'dashboard_shortcode' ) );
        add_shortcode( 'ss_fee_status',        array( 'SS_Student_Portal', 'fee_status_shortcode' ) );
        add_shortcode( 'ss_results',           array( 'SS_Student_Portal', 'results_shortcode' ) );
        add_shortcode( 'ss_attendance',        array( 'SS_Student_Portal', 'attendance_shortcode' ) );
        add_shortcode( 'ss_homework',          array( 'SS_Student_Portal', 'homework_shortcode' ) );
    }

    /**
     * Load widget classes (which extend WP_Widget) and register them.
     * This runs on 'widgets_init', by which point WP_Widget is available.
     */
    public static function register_widgets() {
        require_once SS_PLUGIN_DIR . 'public/inc/widgets/SS_Login_Widget.php';
        require_once SS_PLUGIN_DIR . 'public/inc/widgets/SS_Noticeboard_Widget.php';
        register_widget( 'SS_Login_Widget' );
        register_widget( 'SS_Noticeboard_Widget' );
    }

    public static function enqueue_assets() {
        // Only enqueue on pages where any plugin shortcode might be present.
        global $post;
        if ( ! is_singular() || ! $post ) {
            return;
        }
        $needs_assets = false;
        foreach ( array( 'ss_login', 'ss_noticeboard', 'ss_events', 'ss_student_dashboard', 'ss_fee_status', 'ss_results', 'ss_attendance', 'ss_homework' ) as $code ) {
            if ( has_shortcode( $post->post_content, $code ) ) { $needs_assets = true; break; }
        }
        if ( ! $needs_assets ) { return; }
        wp_enqueue_style( 'ss-public-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Nunito:wght@600;700;800&display=swap', array(), null );
        wp_enqueue_style( 'ss-public', SS_PLUGIN_URL . 'assets/css/ss-public.css', array(), SS_VERSION );
        wp_enqueue_script( 'ss-public-js', SS_PLUGIN_URL . 'assets/js/ss-public.js', array( 'jquery' ), SS_VERSION, true );
    }
}

SS_Public::init();

<?php
/**
 * School Softwere - Plugin constants.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

if ( ! defined( 'SS_VERSION' ) ) {
    define( 'SS_VERSION', '1.0.0' );
}

if ( ! defined( 'SS_PLUGIN_FILE' ) ) {
    define( 'SS_PLUGIN_FILE', dirname( __DIR__ ) . '/school-softwere.php' );
}

if ( ! defined( 'SS_PLUGIN_DIR' ) ) {
    define( 'SS_PLUGIN_DIR', plugin_dir_path( SS_PLUGIN_FILE ) );
}

if ( ! defined( 'SS_PLUGIN_URL' ) ) {
    define( 'SS_PLUGIN_URL', plugin_dir_url( SS_PLUGIN_FILE ) );
}

if ( ! defined( 'SS_PLUGIN_BASENAME' ) ) {
    define( 'SS_PLUGIN_BASENAME', plugin_basename( SS_PLUGIN_FILE ) );
}

if ( ! defined( 'SS_TEXT_DOMAIN' ) ) {
    define( 'SS_TEXT_DOMAIN', 'school-softwere' );
}

if ( ! defined( 'SS_DB_PREFIX' ) ) {
    define( 'SS_DB_PREFIX', 'ss_' );
}

// Capability used for super-admin operations.
if ( ! defined( 'SS_CAP_SUPER' ) ) {
    define( 'SS_CAP_SUPER', 'manage_options' );
}

// Menu slugs.
if ( ! defined( 'SS_MENU_DASHBOARD' ) ) {
    define( 'SS_MENU_DASHBOARD', 'school-softwere' );
}
if ( ! defined( 'SS_MENU_SCHOOLS' ) ) {
    define( 'SS_MENU_SCHOOLS', 'school-softwere-schools' );
}
if ( ! defined( 'SS_MENU_CLASSES' ) ) {
    define( 'SS_MENU_CLASSES', 'school-softwere-classes' );
}
if ( ! defined( 'SS_MENU_SESSIONS' ) ) {
    define( 'SS_MENU_SESSIONS', 'school-softwere-sessions' );
}
if ( ! defined( 'SS_MENU_CATEGORY' ) ) {
    define( 'SS_MENU_CATEGORY', 'school-softwere-categories' );
}
if ( ! defined( 'SS_MENU_SETTINGS' ) ) {
    define( 'SS_MENU_SETTINGS', 'school-softwere-settings' );
}
if ( ! defined( 'SS_MENU_SCHOOL_PORTAL' ) ) {
    define( 'SS_MENU_SCHOOL_PORTAL', 'school-softwere-portal' );
}
if ( ! defined( 'SS_MENU_SETUP_WIZARD' ) ) {
    define( 'SS_MENU_SETUP_WIZARD', 'school-softwere-setup' );
}

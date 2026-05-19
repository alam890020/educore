<?php
/**
 * SS_LM - License management (placeholder for future Pro licensing).
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_LM {

    /**
     * Activate license key (no-op stub).
     *
     * @param string $key
     * @return bool
     */
    public static function activate( $key ) {
        update_option( 'ss_license_key', sanitize_text_field( $key ) );
        update_option( 'ss_license_status', 'active' );
        return true;
    }

    public static function status() {
        return get_option( 'ss_license_status', 'inactive' );
    }
}

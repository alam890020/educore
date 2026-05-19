<?php
/**
 * SS_M_Staff_Transport - Transport queries.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_M_Staff_Transport {

    public static function vehicles( $school_id ) {
        global $wpdb;
        $t = SS_Helper::table( 'vehicles' );
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} WHERE school_id = %d ORDER BY ID DESC", (int) $school_id ) );
    }

    public static function routes( $school_id ) {
        global $wpdb;
        $t = SS_Helper::table( 'routes' );
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} WHERE school_id = %d ORDER BY ID DESC", (int) $school_id ) );
    }
}

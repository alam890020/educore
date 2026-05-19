<?php
/**
 * SS_M_Staff_General - General staff queries.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_M_Staff_General {

    /**
     * List staff for a school.
     *
     * @param int $school_id
     * @return array
     */
    public static function list_for_school( $school_id ) {
        global $wpdb;
        $t = SS_Helper::table( 'staff' );
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} WHERE school_id = %d ORDER BY ID DESC", (int) $school_id ) );
    }

    /**
     * Count active staff in a school.
     *
     * @param int $school_id
     * @return int
     */
    public static function count_active( $school_id ) {
        global $wpdb;
        $t = SS_Helper::table( 'staff' );
        return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE school_id = %d AND is_active = 1", (int) $school_id ) );
    }
}

<?php
/**
 * SS_Schools_Assigned - Helper to determine which schools a user can access.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Schools_Assigned {

    /**
     * Schools accessible by current user.
     *
     * @return array
     */
    public static function for_current_user() {
        global $wpdb;
        if ( current_user_can( SS_CAP_SUPER ) ) {
            return $wpdb->get_results( 'SELECT * FROM ' . SS_Helper::table( 'schools' ) . ' ORDER BY label ASC' );
        }
        $uid = get_current_user_id();
        $a   = SS_Helper::table( 'admins' );
        $sf  = SS_Helper::table( 'staff' );
        $s   = SS_Helper::table( 'schools' );
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT DISTINCT s.* FROM {$s} s
             LEFT JOIN {$a} a  ON a.school_id = s.ID AND a.user_id = %d
             LEFT JOIN {$sf} sf ON sf.school_id = s.ID AND sf.user_id = %d
             WHERE a.user_id IS NOT NULL OR sf.user_id IS NOT NULL
             ORDER BY s.label ASC",
            $uid, $uid
        ) );
    }
}

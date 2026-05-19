<?php
/**
 * SS_M_Staff_Library - Library helper queries.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_M_Staff_Library {

    public static function total_books( $school_id ) {
        global $wpdb;
        $t = SS_Helper::table( 'books' );
        return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(quantity),0) FROM {$t} WHERE school_id = %d", (int) $school_id ) );
    }

    public static function issued_count( $school_id ) {
        global $wpdb;
        $bi = SS_Helper::table( 'books_issued' );
        $b  = SS_Helper::table( 'books' );
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$bi} bi INNER JOIN {$b} b ON b.ID = bi.book_id WHERE b.school_id = %d AND bi.returned_at IS NULL",
            (int) $school_id
        ) );
    }
}

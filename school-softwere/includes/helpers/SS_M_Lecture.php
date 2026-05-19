<?php
/**
 * SS_M_Lecture - Lecture/chapter queries.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_M_Lecture {

    public static function chapters_for_subject( $subject_id ) {
        global $wpdb;
        $t = SS_Helper::table( 'chapter' );
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} WHERE subject_id = %d ORDER BY `order` ASC, ID ASC", (int) $subject_id ) );
    }

    public static function lectures_for_chapter( $chapter_id ) {
        global $wpdb;
        $t = SS_Helper::table( 'lecture' );
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} WHERE chapter_id = %d ORDER BY ID DESC", (int) $chapter_id ) );
    }
}

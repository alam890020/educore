<?php
/**
 * SS_M_Staff_Class - Class/section/student queries used by teacher staff.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_M_Staff_Class {

    /**
     * Get all class_school rows for a school.
     *
     * @param int $school_id
     * @return array
     */
    public static function get_class_schools( $school_id ) {
        global $wpdb;
        $cs = SS_Helper::table( 'class_school' );
        $c  = SS_Helper::table( 'classes' );
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT cs.ID, cs.class_id, cs.session_id, c.label
             FROM {$cs} cs INNER JOIN {$c} c ON c.ID = cs.class_id
             WHERE cs.school_id = %d ORDER BY c.ID ASC",
            (int) $school_id
        ) );
    }

    /**
     * Sections for a class_school.
     *
     * @param int $class_school_id
     * @return array
     */
    public static function get_sections( $class_school_id ) {
        global $wpdb;
        $t = SS_Helper::table( 'sections' );
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} WHERE class_school_id = %d ORDER BY label ASC", (int) $class_school_id ) );
    }

    /**
     * Students by class_school + optional section.
     *
     * @param int $class_school_id
     * @param int $section_id
     * @return array
     */
    public static function students( $class_school_id, $section_id = 0 ) {
        global $wpdb;
        $t = SS_Helper::table( 'student_records' );
        if ( $section_id ) {
            return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} WHERE class_school_id = %d AND section_id = %d ORDER BY roll_number ASC", (int) $class_school_id, (int) $section_id ) );
        }
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} WHERE class_school_id = %d ORDER BY roll_number ASC", (int) $class_school_id ) );
    }
}

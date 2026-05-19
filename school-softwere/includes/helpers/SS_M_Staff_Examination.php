<?php
/**
 * SS_M_Staff_Examination - Exam helper queries.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_M_Staff_Examination {

    /**
     * Pass/fail summary for a school.
     *
     * @param int $school_id
     * @return array { pass, fail }
     */
    public static function pass_fail( $school_id ) {
        global $wpdb;
        $r  = SS_Helper::table( 'exam_results' );
        $ep = SS_Helper::table( 'exam_papers' );
        $e  = SS_Helper::table( 'exams' );
        $cs = SS_Helper::table( 'class_school' );
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT er.obtained_marks, ep.pass_marks
             FROM {$r} er
             INNER JOIN {$ep} ep ON ep.ID = er.exam_paper_id
             INNER JOIN {$e} e ON e.ID = ep.exam_id
             INNER JOIN {$cs} cs ON cs.ID = e.class_school_id
             WHERE cs.school_id = %d", (int) $school_id
        ) );
        $pass = 0; $fail = 0;
        foreach ( (array) $rows as $row ) {
            if ( (float) $row->obtained_marks >= (float) $row->pass_marks ) {
                $pass++;
            } else {
                $fail++;
            }
        }
        return array( 'pass' => $pass, 'fail' => $fail );
    }
}

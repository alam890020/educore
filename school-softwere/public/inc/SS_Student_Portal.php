<?php
/**
 * SS_Student_Portal - Frontend dashboard for logged-in students/parents.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Student_Portal {

    /**
     * Resolve the student record associated with the current logged-in user.
     *
     * @return object|null
     */
    private static function current_student() {
        if ( ! is_user_logged_in() ) { return null; }
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            'SELECT sr.*, c.label class_label, sec.label section_label
             FROM ' . SS_Helper::table( 'student_records' ) . ' sr
             LEFT JOIN ' . SS_Helper::table( 'class_school' ) . ' cs ON cs.ID = sr.class_school_id
             LEFT JOIN ' . SS_Helper::table( 'classes' ) . ' c ON c.ID = cs.class_id
             LEFT JOIN ' . SS_Helper::table( 'sections' ) . ' sec ON sec.ID = sr.section_id
             WHERE sr.user_id = %d AND sr.is_active = 1 LIMIT 1',
            get_current_user_id()
        ) );
    }

    private static function require_login() {
        if ( ! is_user_logged_in() ) {
            return '<div class="ss-public"><div class="ss-public-card"><h3>' . esc_html__( 'Login Required', 'school-softwere' ) . '</h3><p>' . esc_html__( 'Please login to view this section.', 'school-softwere' ) . '</p>' . do_shortcode( '[ss_login]' ) . '</div></div>';
        }
        return null;
    }

    public static function dashboard_shortcode( $atts = array() ) {
        if ( ( $r = self::require_login() ) ) { return $r; }
        $student = self::current_student();
        if ( ! $student ) {
            return '<div class="ss-public"><div class="ss-public-card"><p>' . esc_html__( 'No student record linked to your account.', 'school-softwere' ) . '</p></div></div>';
        }
        global $wpdb;
        $att_total = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . SS_Helper::table( 'attendance' ) . ' WHERE student_record_id = %d', $student->ID ) );
        $att_pres  = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . SS_Helper::table( 'attendance' ) . ' WHERE student_record_id = %d AND status = %s', $student->ID, 'present' ) );
        $att_pct   = $att_total ? round( ( $att_pres / $att_total ) * 100, 1 ) : 0;
        $due       = (float) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(due_amount),0) FROM ' . SS_Helper::table( 'invoices' ) . ' WHERE student_record_id = %d', $student->ID ) );
        $hw_count  = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . SS_Helper::table( 'homework' ) . ' WHERE class_school_id = %d AND submission_date >= %s', $student->class_school_id, current_time( 'Y-m-d' ) ) );

        ob_start();
        echo '<div class="ss-public">';
        echo '<div class="ss-public-card"><h3>' . esc_html( sprintf( __( 'Hello, %s', 'school-softwere' ), $student->first_name ) ) . '</h3>';
        echo '<p class="meta">' . esc_html( $student->admission_number . ' &bull; ' . $student->class_label . ( $student->section_label ? ' / ' . $student->section_label : '' ) ) . '</p>';
        echo '</div>';
        echo '<div class="ss-public-grid">';
        echo '<div class="ss-public-tile"><div class="label">' . esc_html__( 'Attendance', 'school-softwere' ) . '</div><div class="value">' . esc_html( $att_pct ) . '%</div></div>';
        echo '<div class="ss-public-tile"><div class="label">' . esc_html__( 'Pending Fees', 'school-softwere' ) . '</div><div class="value">' . esc_html( SS_Helper::format_money( $due ) ) . '</div></div>';
        echo '<div class="ss-public-tile"><div class="label">' . esc_html__( 'Active Homework', 'school-softwere' ) . '</div><div class="value">' . (int) $hw_count . '</div></div>';
        echo '</div>';

        echo do_shortcode( '[ss_attendance]' );
        echo do_shortcode( '[ss_fee_status]' );
        echo do_shortcode( '[ss_homework]' );
        echo do_shortcode( '[ss_results]' );

        echo '</div>';
        return ob_get_clean();
    }

    public static function fee_status_shortcode() {
        if ( ( $r = self::require_login() ) ) { return $r; }
        $student = self::current_student();
        if ( ! $student ) { return ''; }
        global $wpdb;
        $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . SS_Helper::table( 'invoices' ) . ' WHERE student_record_id = %d ORDER BY ID DESC LIMIT 20', $student->ID ) );
        ob_start();
        echo '<div class="ss-public-card"><h3>' . esc_html__( 'Fee Status', 'school-softwere' ) . '</h3>';
        if ( empty( $rows ) ) {
            echo '<p class="meta">' . esc_html__( 'No invoices yet.', 'school-softwere' ) . '</p>';
        } else {
            echo '<ul class="ss-public-list">';
            foreach ( $rows as $r ) {
                echo '<li><div><strong>' . esc_html( $r->invoice_number ) . '</strong><div class="meta">' . esc_html( SS_Helper::format_date( $r->due_date ) . ' &bull; ' . ucfirst( (string) $r->status ) ) . '</div></div><div>' . esc_html( SS_Helper::format_money( $r->due_amount ) ) . ' ' . esc_html__( 'due', 'school-softwere' ) . '</div></li>';
            }
            echo '</ul>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    public static function results_shortcode() {
        if ( ( $r = self::require_login() ) ) { return $r; }
        $student = self::current_student();
        if ( ! $student ) { return ''; }
        global $wpdb;
        $rows = $wpdb->get_results( $wpdb->prepare(
            'SELECT er.*, ep.total_marks, ep.pass_marks, s.label subject_label, e.label exam_label
             FROM ' . SS_Helper::table( 'exam_results' ) . ' er
             INNER JOIN ' . SS_Helper::table( 'exam_papers' ) . ' ep ON ep.ID = er.exam_paper_id
             INNER JOIN ' . SS_Helper::table( 'exams' ) . ' e ON e.ID = ep.exam_id
             LEFT JOIN ' . SS_Helper::table( 'subjects' ) . ' s ON s.ID = ep.subject_id
             WHERE er.student_record_id = %d ORDER BY er.ID DESC LIMIT 50',
            $student->ID
        ) );
        ob_start();
        echo '<div class="ss-public-card"><h3>' . esc_html__( 'Recent Results', 'school-softwere' ) . '</h3>';
        if ( empty( $rows ) ) {
            echo '<p class="meta">' . esc_html__( 'No results yet.', 'school-softwere' ) . '</p>';
        } else {
            echo '<ul class="ss-public-list">';
            foreach ( $rows as $r ) {
                $passed = (float) $r->obtained_marks >= (float) $r->pass_marks;
                echo '<li><div><strong>' . esc_html( $r->subject_label ) . '</strong><div class="meta">' . esc_html( $r->exam_label ) . '</div></div><div>' . esc_html( $r->obtained_marks . ' / ' . (int) $r->total_marks ) . ' &middot; <span style="color:' . ( $passed ? '#10B981' : '#EF4444' ) . ';font-weight:600;">' . ( $passed ? __( 'PASS', 'school-softwere' ) : __( 'FAIL', 'school-softwere' ) ) . '</span></div></li>';
            }
            echo '</ul>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    public static function attendance_shortcode() {
        if ( ( $r = self::require_login() ) ) { return $r; }
        $student = self::current_student();
        if ( ! $student ) { return ''; }
        global $wpdb;
        $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . SS_Helper::table( 'attendance' ) . ' WHERE student_record_id = %d ORDER BY date DESC LIMIT 30', $student->ID ) );
        ob_start();
        echo '<div class="ss-public-card"><h3>' . esc_html__( 'Recent Attendance', 'school-softwere' ) . '</h3>';
        if ( empty( $rows ) ) {
            echo '<p class="meta">' . esc_html__( 'No records yet.', 'school-softwere' ) . '</p>';
        } else {
            echo '<ul class="ss-public-list">';
            foreach ( $rows as $r ) {
                echo '<li><div>' . esc_html( SS_Helper::format_date( $r->date ) ) . '</div><div><strong>' . esc_html( ucfirst( (string) $r->status ) ) . '</strong></div></li>';
            }
            echo '</ul>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    public static function homework_shortcode() {
        if ( ( $r = self::require_login() ) ) { return $r; }
        $student = self::current_student();
        if ( ! $student ) { return ''; }
        global $wpdb;
        $rows = $wpdb->get_results( $wpdb->prepare(
            'SELECT h.*, s.label subject_label FROM ' . SS_Helper::table( 'homework' ) . ' h LEFT JOIN ' . SS_Helper::table( 'subjects' ) . ' s ON s.ID = h.subject_id WHERE h.class_school_id = %d ORDER BY h.submission_date DESC LIMIT 20',
            $student->class_school_id
        ) );
        ob_start();
        echo '<div class="ss-public-card"><h3>' . esc_html__( 'Homework', 'school-softwere' ) . '</h3>';
        if ( empty( $rows ) ) {
            echo '<p class="meta">' . esc_html__( 'No homework yet.', 'school-softwere' ) . '</p>';
        } else {
            echo '<ul class="ss-public-list">';
            foreach ( $rows as $r ) {
                echo '<li><div><strong>' . esc_html( $r->title ) . '</strong><div class="meta">' . esc_html( $r->subject_label . ' &bull; ' . __( 'Due', 'school-softwere' ) . ': ' . SS_Helper::format_date( $r->submission_date ) ) . '</div></div></li>';
            }
            echo '</ul>';
        }
        echo '</div>';
        return ob_get_clean();
    }
}

<?php
/**
 * SS_REST - REST API endpoints.
 *
 * Namespace: school-softwere/v1
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_REST {

    const NS = 'school-softwere/v1';

    public static function register() {
        register_rest_route( self::NS, '/student/dashboard', array(
            'methods'             => 'GET',
            'permission_callback' => array( __CLASS__, 'logged_in' ),
            'callback'            => array( __CLASS__, 'student_dashboard' ),
        ) );
        register_rest_route( self::NS, '/student/attendance', array(
            'methods'             => 'GET',
            'permission_callback' => array( __CLASS__, 'logged_in' ),
            'callback'            => array( __CLASS__, 'student_attendance' ),
        ) );
        register_rest_route( self::NS, '/student/fees', array(
            'methods'             => 'GET',
            'permission_callback' => array( __CLASS__, 'logged_in' ),
            'callback'            => array( __CLASS__, 'student_fees' ),
        ) );
        register_rest_route( self::NS, '/student/results', array(
            'methods'             => 'GET',
            'permission_callback' => array( __CLASS__, 'logged_in' ),
            'callback'            => array( __CLASS__, 'student_results' ),
        ) );
        register_rest_route( self::NS, '/notices', array(
            'methods'             => 'GET',
            'permission_callback' => '__return_true',
            'callback'            => array( __CLASS__, 'public_notices' ),
        ) );
        register_rest_route( self::NS, '/events', array(
            'methods'             => 'GET',
            'permission_callback' => '__return_true',
            'callback'            => array( __CLASS__, 'public_events' ),
        ) );
    }

    public static function logged_in() {
        return is_user_logged_in();
    }

    private static function get_student() {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . SS_Helper::table( 'student_records' ) . ' WHERE user_id = %d AND is_active = 1 LIMIT 1', get_current_user_id() ) );
    }

    public static function student_dashboard() {
        $s = self::get_student();
        if ( ! $s ) { return new WP_Error( 'no_student', 'No student linked', array( 'status' => 404 ) ); }
        global $wpdb;
        $att_total = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . SS_Helper::table( 'attendance' ) . ' WHERE student_record_id = %d', $s->ID ) );
        $att_pres  = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . SS_Helper::table( 'attendance' ) . ' WHERE student_record_id = %d AND status = %s', $s->ID, 'present' ) );
        $due       = (float) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(due_amount),0) FROM ' . SS_Helper::table( 'invoices' ) . ' WHERE student_record_id = %d', $s->ID ) );
        return array(
            'student'        => array(
                'id'                => (int) $s->ID,
                'admission_number'  => $s->admission_number,
                'name'              => trim( $s->first_name . ' ' . $s->last_name ),
            ),
            'attendance_pct' => $att_total ? round( ( $att_pres / $att_total ) * 100, 2 ) : 0,
            'pending_fees'   => $due,
        );
    }

    public static function student_attendance( WP_REST_Request $req ) {
        $s = self::get_student();
        if ( ! $s ) { return new WP_Error( 'no_student', 'No student linked', array( 'status' => 404 ) ); }
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare( 'SELECT date, status, note FROM ' . SS_Helper::table( 'attendance' ) . ' WHERE student_record_id = %d ORDER BY date DESC LIMIT 200', $s->ID ) );
    }

    public static function student_fees() {
        $s = self::get_student();
        if ( ! $s ) { return new WP_Error( 'no_student', 'No student linked', array( 'status' => 404 ) ); }
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare( 'SELECT invoice_number, total_amount, paid_amount, due_amount, status, due_date, created_at FROM ' . SS_Helper::table( 'invoices' ) . ' WHERE student_record_id = %d ORDER BY ID DESC', $s->ID ) );
    }

    public static function student_results() {
        $s = self::get_student();
        if ( ! $s ) { return new WP_Error( 'no_student', 'No student linked', array( 'status' => 404 ) ); }
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            'SELECT er.obtained_marks, ep.total_marks, ep.pass_marks, sub.label as subject, e.label as exam
             FROM ' . SS_Helper::table( 'exam_results' ) . ' er
             INNER JOIN ' . SS_Helper::table( 'exam_papers' ) . ' ep ON ep.ID = er.exam_paper_id
             INNER JOIN ' . SS_Helper::table( 'exams' ) . ' e ON e.ID = ep.exam_id
             LEFT JOIN ' . SS_Helper::table( 'subjects' ) . ' sub ON sub.ID = ep.subject_id
             WHERE er.student_record_id = %d ORDER BY er.ID DESC LIMIT 200', $s->ID
        ) );
    }

    public static function public_notices( WP_REST_Request $req ) {
        global $wpdb;
        $school_id = (int) $req->get_param( 'school_id' ) ?: SS_Helper::active_school_id();
        $limit     = max( 1, min( 100, (int) ( $req->get_param( 'limit' ) ?: 20 ) ) );
        return $wpdb->get_results( $wpdb->prepare(
            'SELECT ID, title, description, date FROM ' . SS_Helper::table( 'notices' ) . ' WHERE school_id = %d ORDER BY date DESC LIMIT %d',
            $school_id, $limit
        ) );
    }

    public static function public_events( WP_REST_Request $req ) {
        global $wpdb;
        $school_id = (int) $req->get_param( 'school_id' ) ?: SS_Helper::active_school_id();
        $limit     = max( 1, min( 100, (int) ( $req->get_param( 'limit' ) ?: 20 ) ) );
        return $wpdb->get_results( $wpdb->prepare(
            'SELECT ID, title, description, start_date, end_date, venue FROM ' . SS_Helper::table( 'events' ) . ' WHERE school_id = %d ORDER BY start_date ASC LIMIT %d',
            $school_id, $limit
        ) );
    }
}

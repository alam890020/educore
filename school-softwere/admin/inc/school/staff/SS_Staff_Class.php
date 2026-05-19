<?php
/**
 * SS_Staff_Class - Subjects, Routines (Timetable), and Attendance.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Staff_Class {

    /* ----------------------------------------------------------------
     *  SUBJECTS
     * ---------------------------------------------------------------- */
    public static function render_subjects() {
        global $wpdb;
        $school_id = SS_Helper::active_school_id();
        $tbl       = SS_Helper::table( 'subjects' );
        $view      = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'list';

        if ( isset( $_POST['ss_action'] ) && 'save_subject' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'save_subject' ) ) {
            $id   = (int) ( $_POST['id'] ?? 0 );
            $data = array(
                'class_school_id' => (int) ( $_POST['class_school_id'] ?? 0 ),
                'label'           => sanitize_text_field( wp_unslash( $_POST['label'] ?? '' ) ),
                'subject_type_id' => (int) ( $_POST['subject_type_id'] ?? 0 ) ?: null,
                'code'            => sanitize_text_field( wp_unslash( $_POST['code'] ?? '' ) ),
            );
            if ( $id ) { $wpdb->update( $tbl, $data, array( 'ID' => $id ) ); }
            else      { $data['created_at'] = current_time( 'mysql' ); $wpdb->insert( $tbl, $data ); }
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-subjects' ) ); exit;
        }
        if ( 'delete' === $view && SS_Helper::verify_nonce( 'delete_subject' ) ) {
            $wpdb->delete( $tbl, array( 'ID' => (int) $_GET['id'] ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-subjects' ) ); exit;
        }

        SS_Admin_Shell::open( __( 'Subjects', 'school-softwere' ), 'school-softwere-subjects', array(
            array( 'label' => __( 'Subjects', 'school-softwere' ) ),
        ) );

        $editing = null;
        if ( 'edit' === $view && ! empty( $_GET['id'] ) ) {
            $editing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tbl} WHERE ID = %d", (int) $_GET['id'] ) );
        }

        $cs    = self::class_schools_for( $school_id );
        $types = $wpdb->get_results( 'SELECT * FROM ' . SS_Helper::table( 'subject_types' ) . ' ORDER BY label' );

        echo '<div class="ss-row">';
        echo '<div class="ss-col" style="flex:1">';
        SS_Admin_Shell::card_open( $editing ? __( 'Edit Subject', 'school-softwere' ) : __( 'Add Subject', 'school-softwere' ) );
        echo '<form method="post" class="ss-form">';
        SS_Helper::nonce_field( 'save_subject' );
        echo '<input type="hidden" name="ss_action" value="save_subject">';
        echo '<input type="hidden" name="id" value="' . ( $editing ? (int) $editing->ID : 0 ) . '">';
        SS_School::select( 'class_school_id', __( 'Class', 'school-softwere' ), $editing ? (int) $editing->class_school_id : 0, $cs, true );
        SS_School::field( 'label', __( 'Subject Name', 'school-softwere' ), $editing ? $editing->label : '', true );
        SS_School::field( 'code', __( 'Code', 'school-softwere' ), $editing ? $editing->code : '' );
        SS_School::select( 'subject_type_id', __( 'Type', 'school-softwere' ), $editing ? (int) $editing->subject_type_id : 0, $types );
        echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-floppy-disk"></i> ' . esc_html__( 'Save', 'school-softwere' ) . '</button></div></form>';
        SS_Admin_Shell::card_close();
        echo '</div>';

        echo '<div class="ss-col" style="flex:2">';
        SS_Admin_Shell::card_open( __( 'All Subjects', 'school-softwere' ) );
        $rows = $wpdb->get_results( $wpdb->prepare(
            'SELECT s.*, c.label class_label, st.label type_label FROM ' . $tbl . ' s
             LEFT JOIN ' . SS_Helper::table( 'class_school' ) . ' cs ON cs.ID = s.class_school_id
             LEFT JOIN ' . SS_Helper::table( 'classes' ) . ' c ON c.ID = cs.class_id
             LEFT JOIN ' . SS_Helper::table( 'subject_types' ) . ' st ON st.ID = s.subject_type_id
             WHERE cs.school_id = %d ORDER BY s.ID DESC', $school_id
        ) );
        if ( empty( $rows ) ) {
            echo '<div class="ss-empty"><i class="ph ph-book-open"></i><h3>' . esc_html__( 'No subjects', 'school-softwere' ) . '</h3></div>';
        } else {
            echo '<div class="ss-table-wrap"><table class="ss-table"><thead><tr><th>' . esc_html__( 'Subject', 'school-softwere' ) . '</th><th>' . esc_html__( 'Class', 'school-softwere' ) . '</th><th>' . esc_html__( 'Code', 'school-softwere' ) . '</th><th>' . esc_html__( 'Type', 'school-softwere' ) . '</th><th class="ss-text-right">' . esc_html__( 'Actions', 'school-softwere' ) . '</th></tr></thead><tbody>';
            foreach ( $rows as $r ) {
                $edit = SS_Helper::admin_url( 'school-softwere-subjects' ) . '&view=edit&id=' . $r->ID;
                $del  = wp_nonce_url( SS_Helper::admin_url( 'school-softwere-subjects' ) . '&view=delete&id=' . $r->ID, 'delete_subject', '_ssnonce' );
                echo '<tr><td><strong>' . esc_html( $r->label ) . '</strong></td><td>' . esc_html( $r->class_label ) . '</td><td>' . esc_html( $r->code ) . '</td><td>' . esc_html( $r->type_label ?: '-' ) . '</td><td class="ss-text-right"><a class="ss-btn ss-btn-secondary ss-btn-sm ss-btn-icon" href="' . esc_url( $edit ) . '"><i class="ph ph-pencil-simple"></i></a> <a class="ss-btn ss-btn-danger ss-btn-sm ss-btn-icon ss-confirm-delete" href="' . esc_url( $del ) . '"><i class="ph ph-trash"></i></a></td></tr>';
            }
            echo '</tbody></table></div>';
        }
        SS_Admin_Shell::card_close();
        echo '</div>';
        echo '</div>';
        SS_Admin_Shell::close();
    }

    /* ----------------------------------------------------------------
     *  ROUTINES (TIMETABLE)
     * ---------------------------------------------------------------- */
    public static function render_routines() {
        global $wpdb;
        $school_id = SS_Helper::active_school_id();
        $tbl = SS_Helper::table( 'routines' );

        if ( isset( $_POST['ss_action'] ) && 'save_routine' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'save_routine' ) ) {
            $data = array(
                'class_school_id' => (int) ( $_POST['class_school_id'] ?? 0 ),
                'section_id'      => (int) ( $_POST['section_id'] ?? 0 ) ?: null,
                'subject_id'      => (int) ( $_POST['subject_id'] ?? 0 ) ?: null,
                'staff_id'        => (int) ( $_POST['staff_id'] ?? 0 ) ?: null,
                'day'             => sanitize_text_field( wp_unslash( $_POST['day'] ?? '' ) ),
                'start_time'      => sanitize_text_field( wp_unslash( $_POST['start_time'] ?? '' ) ) ?: null,
                'end_time'        => sanitize_text_field( wp_unslash( $_POST['end_time'] ?? '' ) ) ?: null,
            );
            $wpdb->insert( $tbl, $data );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-routines' ) ); exit;
        }
        if ( 'delete' === ( $_GET['view'] ?? '' ) && SS_Helper::verify_nonce( 'delete_routine' ) ) {
            $wpdb->delete( $tbl, array( 'ID' => (int) $_GET['id'] ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-routines' ) ); exit;
        }

        SS_Admin_Shell::open( __( 'Class Timetable', 'school-softwere' ), 'school-softwere-routines', array(
            array( 'label' => __( 'Timetable', 'school-softwere' ) ),
        ) );

        $cs       = self::class_schools_for( $school_id );
        $sections = $wpdb->get_results( 'SELECT * FROM ' . SS_Helper::table( 'sections' ) . ' ORDER BY label' );
        $subjects = $wpdb->get_results( 'SELECT * FROM ' . SS_Helper::table( 'subjects' ) . ' ORDER BY label' );
        $staff    = $wpdb->get_results( $wpdb->prepare( 'SELECT ID, CONCAT(first_name," ",last_name) as label FROM ' . SS_Helper::table( 'staff' ) . ' WHERE school_id = %d ORDER BY first_name', $school_id ) );

        echo '<div class="ss-row">';
        echo '<div class="ss-col" style="flex:1">';
        SS_Admin_Shell::card_open( __( 'Add Routine Slot', 'school-softwere' ) );
        echo '<form method="post" class="ss-form">';
        SS_Helper::nonce_field( 'save_routine' );
        echo '<input type="hidden" name="ss_action" value="save_routine">';
        SS_School::select( 'class_school_id', __( 'Class', 'school-softwere' ), 0, $cs, true );
        SS_School::select( 'section_id',      __( 'Section', 'school-softwere' ), 0, $sections );
        SS_School::select( 'subject_id',      __( 'Subject', 'school-softwere' ), 0, $subjects );
        SS_School::select( 'staff_id',        __( 'Teacher', 'school-softwere' ), 0, $staff );
        echo '<div class="ss-field"><label>' . esc_html__( 'Day', 'school-softwere' ) . '</label><select name="day" class="ss-select2">';
        foreach ( array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ) as $d ) {
            echo '<option value="' . esc_attr( $d ) . '">' . esc_html( ucfirst( $d ) ) . '</option>';
        }
        echo '</select></div>';
        echo '<div class="ss-field"><label>' . esc_html__( 'Start Time', 'school-softwere' ) . '</label><input class="ss-time" type="text" name="start_time"></div>';
        echo '<div class="ss-field"><label>' . esc_html__( 'End Time', 'school-softwere' ) . '</label><input class="ss-time" type="text" name="end_time"></div>';
        echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-plus"></i> ' . esc_html__( 'Add Slot', 'school-softwere' ) . '</button></div></form>';
        SS_Admin_Shell::card_close();
        echo '</div>';

        echo '<div class="ss-col" style="flex:2">';
        SS_Admin_Shell::card_open( __( 'Weekly Routine', 'school-softwere' ) );
        $rows = $wpdb->get_results( $wpdb->prepare(
            'SELECT r.*, c.label class_label, s.label subject_label, CONCAT(st.first_name," ",st.last_name) staff_name, sec.label section_label
             FROM ' . $tbl . ' r
             LEFT JOIN ' . SS_Helper::table( 'class_school' ) . ' cs ON cs.ID = r.class_school_id
             LEFT JOIN ' . SS_Helper::table( 'classes' ) . ' c ON c.ID = cs.class_id
             LEFT JOIN ' . SS_Helper::table( 'subjects' ) . ' s ON s.ID = r.subject_id
             LEFT JOIN ' . SS_Helper::table( 'staff' ) . ' st ON st.ID = r.staff_id
             LEFT JOIN ' . SS_Helper::table( 'sections' ) . ' sec ON sec.ID = r.section_id
             WHERE cs.school_id = %d
             ORDER BY FIELD(r.day,"monday","tuesday","wednesday","thursday","friday","saturday","sunday"), r.start_time ASC', $school_id
        ) );
        if ( empty( $rows ) ) {
            echo '<div class="ss-empty"><i class="ph ph-clock"></i><h3>' . esc_html__( 'No timetable slots', 'school-softwere' ) . '</h3></div>';
        } else {
            echo '<div class="ss-table-wrap"><table class="ss-table"><thead><tr><th>' . esc_html__( 'Day', 'school-softwere' ) . '</th><th>' . esc_html__( 'Time', 'school-softwere' ) . '</th><th>' . esc_html__( 'Class', 'school-softwere' ) . '</th><th>' . esc_html__( 'Subject', 'school-softwere' ) . '</th><th>' . esc_html__( 'Teacher', 'school-softwere' ) . '</th><th></th></tr></thead><tbody>';
            foreach ( $rows as $r ) {
                $del = wp_nonce_url( SS_Helper::admin_url( 'school-softwere-routines' ) . '&view=delete&id=' . $r->ID, 'delete_routine', '_ssnonce' );
                echo '<tr><td>' . esc_html( ucfirst( (string) $r->day ) ) . '</td><td>' . esc_html( $r->start_time . ' - ' . $r->end_time ) . '</td><td>' . esc_html( $r->class_label . ( $r->section_label ? ' / ' . $r->section_label : '' ) ) . '</td><td>' . esc_html( $r->subject_label ) . '</td><td>' . esc_html( $r->staff_name ) . '</td><td class="ss-text-right"><a class="ss-btn ss-btn-danger ss-btn-sm ss-btn-icon ss-confirm-delete" href="' . esc_url( $del ) . '"><i class="ph ph-trash"></i></a></td></tr>';
            }
            echo '</tbody></table></div>';
        }
        SS_Admin_Shell::card_close();
        echo '</div>';
        echo '</div>';
        SS_Admin_Shell::close();
    }

    /* ----------------------------------------------------------------
     *  ATTENDANCE
     * ---------------------------------------------------------------- */
    public static function render_attendance() {
        global $wpdb;
        $school_id = SS_Helper::active_school_id();

        // Save attendance.
        if ( isset( $_POST['ss_action'] ) && 'mark_attendance' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'mark_attendance' ) ) {
            $cs_id  = (int) $_POST['class_school_id'];
            $sec_id = (int) ( $_POST['section_id'] ?? 0 );
            $date   = sanitize_text_field( wp_unslash( $_POST['date'] ?? current_time( 'Y-m-d' ) ) );
            $statuses = (array) ( $_POST['status'] ?? array() );
            $tbl = SS_Helper::table( 'attendance' );
            foreach ( $statuses as $sid => $status ) {
                $sid    = (int) $sid;
                $status = sanitize_key( $status );
                if ( ! in_array( $status, array( 'present', 'absent', 'late', 'half_day' ), true ) ) {
                    continue;
                }
                $existing = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$tbl} WHERE student_record_id = %d AND date = %s", $sid, $date ) );
                $row = array(
                    'student_record_id' => $sid,
                    'class_school_id'   => $cs_id,
                    'section_id'        => $sec_id ?: null,
                    'date'              => $date,
                    'status'            => $status,
                );
                if ( $existing ) {
                    $wpdb->update( $tbl, array( 'status' => $status ), array( 'ID' => $existing ) );
                } else {
                    $wpdb->insert( $tbl, $row );
                }
            }
            wp_safe_redirect( add_query_arg( array( 'class_school_id' => $cs_id, 'section_id' => $sec_id, 'date' => $date, 'ss_notice' => __( 'Attendance saved', 'school-softwere' ), 'ss_notice_type' => 'success' ), SS_Helper::admin_url( 'school-softwere-attendance' ) ) );
            exit;
        }

        SS_Admin_Shell::open( __( 'Attendance', 'school-softwere' ), 'school-softwere-attendance', array(
            array( 'label' => __( 'Attendance', 'school-softwere' ) ),
        ) );

        $cs_id  = isset( $_GET['class_school_id'] ) ? (int) $_GET['class_school_id'] : 0;
        $sec_id = isset( $_GET['section_id'] ) ? (int) $_GET['section_id'] : 0;
        $date   = isset( $_GET['date'] ) ? sanitize_text_field( wp_unslash( $_GET['date'] ) ) : current_time( 'Y-m-d' );

        $cs       = self::class_schools_for( $school_id );
        $sections = $wpdb->get_results( 'SELECT * FROM ' . SS_Helper::table( 'sections' ) . ' ORDER BY label' );

        SS_Admin_Shell::card_open( __( 'Select Class & Date', 'school-softwere' ) );
        echo '<form method="get" class="ss-form">';
        echo '<input type="hidden" name="page" value="school-softwere-attendance">';
        echo '<div class="ss-form-grid">';
        SS_School::select( 'class_school_id', __( 'Class', 'school-softwere' ), $cs_id, $cs, true );
        SS_School::select( 'section_id',      __( 'Section', 'school-softwere' ), $sec_id, $sections );
        echo '<div class="ss-field"><label>' . esc_html__( 'Date', 'school-softwere' ) . '</label><input class="ss-date" type="text" name="date" value="' . esc_attr( $date ) . '"></div>';
        echo '</div>';
        echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-magnifying-glass"></i> ' . esc_html__( 'Load Students', 'school-softwere' ) . '</button></div></form>';
        SS_Admin_Shell::card_close();

        if ( $cs_id ) {
            $students = SS_M_Staff_Class::students( $cs_id, $sec_id );
            $existing = array();
            foreach ( (array) $wpdb->get_results( $wpdb->prepare( 'SELECT student_record_id, status FROM ' . SS_Helper::table( 'attendance' ) . ' WHERE class_school_id = %d AND date = %s', $cs_id, $date ) ) as $a ) {
                $existing[ (int) $a->student_record_id ] = $a->status;
            }
            SS_Admin_Shell::card_open( __( 'Mark Attendance', 'school-softwere' ),
                '<a class="ss-btn ss-btn-secondary ss-btn-sm" href="' . esc_url( SS_Helper::admin_url() . '&ss_print=attendance_sheet&class_school_id=' . $cs_id . '&date=' . $date ) . '" target="_blank"><i class="ph ph-printer"></i> ' . esc_html__( 'Print Sheet', 'school-softwere' ) . '</a>'
            );
            if ( empty( $students ) ) {
                echo '<div class="ss-empty"><i class="ph ph-graduation-cap"></i><h3>' . esc_html__( 'No students in this class', 'school-softwere' ) . '</h3></div>';
            } else {
                echo '<form method="post" class="ss-form">';
                SS_Helper::nonce_field( 'mark_attendance' );
                echo '<input type="hidden" name="ss_action" value="mark_attendance">';
                echo '<input type="hidden" name="class_school_id" value="' . (int) $cs_id . '">';
                echo '<input type="hidden" name="section_id" value="' . (int) $sec_id . '">';
                echo '<input type="hidden" name="date" value="' . esc_attr( $date ) . '">';
                echo '<div class="ss-table-wrap"><table class="ss-table"><thead><tr><th>' . esc_html__( 'Roll', 'school-softwere' ) . '</th><th>' . esc_html__( 'Student', 'school-softwere' ) . '</th><th>' . esc_html__( 'Status', 'school-softwere' ) . '</th></tr></thead><tbody>';
                foreach ( $students as $s ) {
                    $cur = $existing[ (int) $s->ID ] ?? 'present';
                    echo '<tr><td>' . esc_html( $s->roll_number ) . '</td><td><strong>' . esc_html( trim( $s->first_name . ' ' . $s->last_name ) ) . '</strong><br><small class="ss-text-muted">' . esc_html( $s->admission_number ) . '</small></td>';
                    echo '<td><select name="status[' . (int) $s->ID . ']" class="ss-select2">';
                    foreach ( array( 'present' => __( 'Present', 'school-softwere' ), 'absent' => __( 'Absent', 'school-softwere' ), 'late' => __( 'Late', 'school-softwere' ), 'half_day' => __( 'Half Day', 'school-softwere' ) ) as $k => $v ) {
                        echo '<option value="' . esc_attr( $k ) . '"' . selected( $cur, $k, false ) . '>' . esc_html( $v ) . '</option>';
                    }
                    echo '</select></td></tr>';
                }
                echo '</tbody></table></div>';
                echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-floppy-disk"></i> ' . esc_html__( 'Save Attendance', 'school-softwere' ) . '</button></div>';
                echo '</form>';
            }
            SS_Admin_Shell::card_close();
        }

        SS_Admin_Shell::close();
    }

    /* ----------------------------------------------------------------
     *  Helpers
     * ---------------------------------------------------------------- */
    private static function class_schools_for( $school_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            'SELECT cs.ID, c.label FROM ' . SS_Helper::table( 'class_school' ) . ' cs INNER JOIN ' . SS_Helper::table( 'classes' ) . ' c ON c.ID = cs.class_id WHERE cs.school_id = %d ORDER BY c.ID ASC',
            $school_id
        ) );
    }
}

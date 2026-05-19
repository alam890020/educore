<?php
/**
 * SS_Staff_Examination - Exams, papers, results, admit cards.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Staff_Examination {

    public static function render() {
        global $wpdb;
        $school_id = SS_Helper::active_school_id();
        $tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'exams';

        // Save exam.
        if ( isset( $_POST['ss_action'] ) && 'save_exam' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'save_exam' ) ) {
            $id   = (int) ( $_POST['id'] ?? 0 );
            $tbl  = SS_Helper::table( 'exams' );
            $data = array(
                'class_school_id' => (int) ( $_POST['class_school_id'] ?? 0 ),
                'label'           => sanitize_text_field( wp_unslash( $_POST['label'] ?? '' ) ),
                'session_id'      => SS_Helper::active_session_id( $school_id ) ?: null,
            );
            if ( $id ) { $wpdb->update( $tbl, $data, array( 'ID' => $id ) ); }
            else      { $data['created_at'] = current_time( 'mysql' ); $wpdb->insert( $tbl, $data ); }
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-exams' ) ); exit;
        }
        if ( 'delete_exam' === ( $_GET['view'] ?? '' ) && SS_Helper::verify_nonce( 'delete_exam' ) ) {
            $wpdb->delete( SS_Helper::table( 'exams' ), array( 'ID' => (int) $_GET['id'] ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-exams' ) ); exit;
        }
        // Save exam paper.
        if ( isset( $_POST['ss_action'] ) && 'save_paper' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'save_paper' ) ) {
            $tbl  = SS_Helper::table( 'exam_papers' );
            $data = array(
                'exam_id'     => (int) ( $_POST['exam_id'] ?? 0 ),
                'subject_id'  => (int) ( $_POST['subject_id'] ?? 0 ),
                'date'        => sanitize_text_field( wp_unslash( $_POST['date'] ?? '' ) ) ?: null,
                'start_time'  => sanitize_text_field( wp_unslash( $_POST['start_time'] ?? '' ) ) ?: null,
                'end_time'    => sanitize_text_field( wp_unslash( $_POST['end_time'] ?? '' ) ) ?: null,
                'total_marks' => (float) ( $_POST['total_marks'] ?? 0 ),
                'pass_marks'  => (float) ( $_POST['pass_marks'] ?? 0 ),
                'created_at'  => current_time( 'mysql' ),
            );
            $wpdb->insert( $tbl, $data );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-exams' ) . '&tab=papers&exam_id=' . (int) $_POST['exam_id'] ); exit;
        }
        if ( 'delete_paper' === ( $_GET['view'] ?? '' ) && SS_Helper::verify_nonce( 'delete_paper' ) ) {
            $wpdb->delete( SS_Helper::table( 'exam_papers' ), array( 'ID' => (int) $_GET['id'] ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-exams' ) . '&tab=papers' ); exit;
        }
        // Save results bulk.
        if ( isset( $_POST['ss_action'] ) && 'save_results' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'save_results' ) ) {
            $tbl    = SS_Helper::table( 'exam_results' );
            $marks  = (array) ( $_POST['marks'] ?? array() );
            $paper  = (int) ( $_POST['exam_paper_id'] ?? 0 );
            foreach ( $marks as $sid => $m ) {
                $sid = (int) $sid; $m = (float) $m;
                $existing = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$tbl} WHERE exam_paper_id = %d AND student_record_id = %d", $paper, $sid ) );
                if ( $existing ) {
                    $wpdb->update( $tbl, array( 'obtained_marks' => $m ), array( 'ID' => $existing ) );
                } else {
                    $wpdb->insert( $tbl, array(
                        'exam_paper_id'    => $paper,
                        'student_record_id'=> $sid,
                        'obtained_marks'   => $m,
                        'created_at'       => current_time( 'mysql' ),
                    ) );
                }
            }
            wp_safe_redirect( add_query_arg( array( 'ss_notice' => __( 'Results saved', 'school-softwere' ), 'ss_notice_type' => 'success' ), SS_Helper::admin_url( 'school-softwere-exams' ) . '&tab=results&exam_paper_id=' . $paper ) ); exit;
        }
        // Generate admit cards.
        if ( isset( $_POST['ss_action'] ) && 'gen_admit' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'gen_admit' ) ) {
            $exam_id = (int) ( $_POST['exam_id'] ?? 0 );
            $exam    = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . SS_Helper::table( 'exams' ) . ' WHERE ID = %d', $exam_id ) );
            if ( $exam ) {
                $students = SS_M_Staff_Class::students( $exam->class_school_id );
                $tbl = SS_Helper::table( 'admit_cards' );
                foreach ( $students as $st ) {
                    $exists = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$tbl} WHERE exam_id = %d AND student_record_id = %d", $exam_id, $st->ID ) );
                    if ( ! $exists ) {
                        $wpdb->insert( $tbl, array(
                            'exam_id'           => $exam_id,
                            'student_record_id' => $st->ID,
                            'admit_card_number' => 'AC-' . $exam_id . '-' . $st->ID,
                            'created_at'        => current_time( 'mysql' ),
                        ) );
                    }
                }
            }
            wp_safe_redirect( add_query_arg( array( 'ss_notice' => __( 'Admit cards generated', 'school-softwere' ), 'ss_notice_type' => 'success' ), SS_Helper::admin_url( 'school-softwere-exams' ) . '&tab=admits&exam_id=' . $exam_id ) ); exit;
        }

        SS_Admin_Shell::open( __( 'Examinations', 'school-softwere' ), 'school-softwere-exams', array(
            array( 'label' => __( 'Examinations', 'school-softwere' ) ),
        ) );

        // Tab nav.
        echo '<div class="ss-tabs" style="margin-bottom:16px;">';
        $base = SS_Helper::admin_url( 'school-softwere-exams' );
        foreach ( array( 'exams' => __( 'Exams', 'school-softwere' ), 'papers' => __( 'Exam Papers', 'school-softwere' ), 'results' => __( 'Results', 'school-softwere' ), 'admits' => __( 'Admit Cards', 'school-softwere' ) ) as $k => $v ) {
            $active = $k === $tab ? 'active' : '';
            echo '<a class="ss-tab ' . esc_attr( $active ) . '" href="' . esc_url( $base . '&tab=' . $k ) . '">' . esc_html( $v ) . '</a>';
        }
        echo '</div>';

        if ( 'exams' === $tab )       { self::tab_exams( $school_id ); }
        elseif ( 'papers' === $tab )  { self::tab_papers( $school_id ); }
        elseif ( 'results' === $tab ) { self::tab_results( $school_id ); }
        elseif ( 'admits' === $tab )  { self::tab_admit( $school_id ); }

        SS_Admin_Shell::close();
    }

    private static function tab_exams( $school_id ) {
        global $wpdb;
        $cs = SS_Staff_Accountant::class_schools_for( $school_id );

        echo '<div class="ss-row">';
        echo '<div class="ss-col" style="flex:1">';
        SS_Admin_Shell::card_open( __( 'Add Exam', 'school-softwere' ) );
        echo '<form method="post" class="ss-form">';
        SS_Helper::nonce_field( 'save_exam' );
        echo '<input type="hidden" name="ss_action" value="save_exam">';
        SS_School::select( 'class_school_id', __( 'Class', 'school-softwere' ), 0, $cs, true );
        SS_School::field( 'label', __( 'Exam Label', 'school-softwere' ), '', true );
        echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-plus"></i> ' . esc_html__( 'Add', 'school-softwere' ) . '</button></div></form>';
        SS_Admin_Shell::card_close();
        echo '</div>';

        echo '<div class="ss-col" style="flex:2">';
        SS_Admin_Shell::card_open( __( 'All Exams', 'school-softwere' ) );
        $rows = $wpdb->get_results( $wpdb->prepare(
            'SELECT e.*, c.label class_label FROM ' . SS_Helper::table( 'exams' ) . ' e LEFT JOIN ' . SS_Helper::table( 'class_school' ) . ' cs ON cs.ID = e.class_school_id LEFT JOIN ' . SS_Helper::table( 'classes' ) . ' c ON c.ID = cs.class_id WHERE cs.school_id = %d ORDER BY e.ID DESC', $school_id
        ) );
        if ( empty( $rows ) ) {
            echo '<div class="ss-empty"><i class="ph ph-exam"></i><h3>' . esc_html__( 'No exams', 'school-softwere' ) . '</h3></div>';
        } else {
            echo '<div class="ss-table-wrap"><table class="ss-table"><thead><tr><th>' . esc_html__( 'Label', 'school-softwere' ) . '</th><th>' . esc_html__( 'Class', 'school-softwere' ) . '</th><th></th></tr></thead><tbody>';
            foreach ( $rows as $r ) {
                $papers = SS_Helper::admin_url( 'school-softwere-exams' ) . '&tab=papers&exam_id=' . $r->ID;
                $admits = SS_Helper::admin_url( 'school-softwere-exams' ) . '&tab=admits&exam_id=' . $r->ID;
                $del    = wp_nonce_url( SS_Helper::admin_url( 'school-softwere-exams' ) . '&view=delete_exam&id=' . $r->ID, 'delete_exam', '_ssnonce' );
                echo '<tr><td><strong>' . esc_html( $r->label ) . '</strong></td><td>' . esc_html( $r->class_label ) . '</td><td class="ss-text-right"><div class="ss-actions"><a class="ss-btn ss-btn-secondary ss-btn-sm" href="' . esc_url( $papers ) . '">' . esc_html__( 'Papers', 'school-softwere' ) . '</a> <a class="ss-btn ss-btn-secondary ss-btn-sm" href="' . esc_url( $admits ) . '">' . esc_html__( 'Admit', 'school-softwere' ) . '</a> <a class="ss-btn ss-btn-danger ss-btn-sm ss-btn-icon ss-confirm-delete" href="' . esc_url( $del ) . '"><i class="ph ph-trash"></i></a></div></td></tr>';
            }
            echo '</tbody></table></div>';
        }
        SS_Admin_Shell::card_close();
        echo '</div>';
        echo '</div>';
    }

    private static function tab_papers( $school_id ) {
        global $wpdb;
        $exam_id = isset( $_GET['exam_id'] ) ? (int) $_GET['exam_id'] : 0;
        $exams   = $wpdb->get_results( $wpdb->prepare( 'SELECT e.ID, CONCAT(c.label," - ",e.label) as label FROM ' . SS_Helper::table( 'exams' ) . ' e LEFT JOIN ' . SS_Helper::table( 'class_school' ) . ' cs ON cs.ID = e.class_school_id LEFT JOIN ' . SS_Helper::table( 'classes' ) . ' c ON c.ID = cs.class_id WHERE cs.school_id = %d ORDER BY e.ID DESC', $school_id ) );
        $subjects= $wpdb->get_results( 'SELECT * FROM ' . SS_Helper::table( 'subjects' ) . ' ORDER BY label' );

        SS_Admin_Shell::card_open( __( 'Add Exam Paper', 'school-softwere' ) );
        echo '<form method="post" class="ss-form">';
        SS_Helper::nonce_field( 'save_paper' );
        echo '<input type="hidden" name="ss_action" value="save_paper">';
        echo '<div class="ss-form-grid">';
        SS_School::select( 'exam_id',     __( 'Exam', 'school-softwere' ),    $exam_id, $exams, true );
        SS_School::select( 'subject_id',  __( 'Subject', 'school-softwere' ), 0, $subjects, true );
        echo '<div class="ss-field"><label>' . esc_html__( 'Date', 'school-softwere' ) . '</label><input class="ss-date" type="text" name="date"></div>';
        echo '<div class="ss-field"><label>' . esc_html__( 'Start Time', 'school-softwere' ) . '</label><input class="ss-time" type="text" name="start_time"></div>';
        echo '<div class="ss-field"><label>' . esc_html__( 'End Time', 'school-softwere' ) . '</label><input class="ss-time" type="text" name="end_time"></div>';
        SS_School::field( 'total_marks', __( 'Total Marks', 'school-softwere' ), 100, true, 'number' );
        SS_School::field( 'pass_marks',  __( 'Pass Marks', 'school-softwere' ),  35, true, 'number' );
        echo '</div>';
        echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-plus"></i> ' . esc_html__( 'Add Paper', 'school-softwere' ) . '</button></div></form>';
        SS_Admin_Shell::card_close();

        SS_Admin_Shell::card_open( __( 'Exam Papers', 'school-softwere' ) );
        $where = $exam_id ? $wpdb->prepare( 'WHERE p.exam_id = %d', $exam_id ) : '';
        $rows  = $wpdb->get_results(
            'SELECT p.*, s.label subject_label, e.label exam_label FROM ' . SS_Helper::table( 'exam_papers' ) . ' p LEFT JOIN ' . SS_Helper::table( 'subjects' ) . ' s ON s.ID = p.subject_id LEFT JOIN ' . SS_Helper::table( 'exams' ) . ' e ON e.ID = p.exam_id ' . $where . ' ORDER BY p.date ASC, p.start_time ASC LIMIT 200'
        );
        if ( empty( $rows ) ) {
            echo '<div class="ss-empty"><i class="ph ph-file-text"></i><h3>' . esc_html__( 'No papers', 'school-softwere' ) . '</h3></div>';
        } else {
            echo '<div class="ss-table-wrap"><table class="ss-table"><thead><tr><th>' . esc_html__( 'Exam', 'school-softwere' ) . '</th><th>' . esc_html__( 'Subject', 'school-softwere' ) . '</th><th>' . esc_html__( 'Date', 'school-softwere' ) . '</th><th>' . esc_html__( 'Time', 'school-softwere' ) . '</th><th>' . esc_html__( 'Marks', 'school-softwere' ) . '</th><th></th></tr></thead><tbody>';
            foreach ( $rows as $r ) {
                $del = wp_nonce_url( SS_Helper::admin_url( 'school-softwere-exams' ) . '&tab=papers&view=delete_paper&id=' . $r->ID, 'delete_paper', '_ssnonce' );
                $res = SS_Helper::admin_url( 'school-softwere-exams' ) . '&tab=results&exam_paper_id=' . $r->ID;
                echo '<tr><td>' . esc_html( $r->exam_label ) . '</td><td><strong>' . esc_html( $r->subject_label ) . '</strong></td><td>' . esc_html( SS_Helper::format_date( $r->date ) ) . '</td><td>' . esc_html( $r->start_time . ' - ' . $r->end_time ) . '</td><td>' . esc_html( (int) $r->total_marks . ' / ' . (int) $r->pass_marks ) . '</td><td class="ss-text-right"><a class="ss-btn ss-btn-secondary ss-btn-sm" href="' . esc_url( $res ) . '">' . esc_html__( 'Enter Results', 'school-softwere' ) . '</a> <a class="ss-btn ss-btn-danger ss-btn-sm ss-btn-icon ss-confirm-delete" href="' . esc_url( $del ) . '"><i class="ph ph-trash"></i></a></td></tr>';
            }
            echo '</tbody></table></div>';
        }
        SS_Admin_Shell::card_close();
    }

    private static function tab_results( $school_id ) {
        global $wpdb;
        $paper_id = isset( $_GET['exam_paper_id'] ) ? (int) $_GET['exam_paper_id'] : 0;
        if ( ! $paper_id ) {
            $papers = $wpdb->get_results( $wpdb->prepare(
                'SELECT p.ID, CONCAT(s.label," (",e.label,")") as label FROM ' . SS_Helper::table( 'exam_papers' ) . ' p LEFT JOIN ' . SS_Helper::table( 'subjects' ) . ' s ON s.ID = p.subject_id LEFT JOIN ' . SS_Helper::table( 'exams' ) . ' e ON e.ID = p.exam_id LEFT JOIN ' . SS_Helper::table( 'class_school' ) . ' cs ON cs.ID = e.class_school_id WHERE cs.school_id = %d ORDER BY p.date DESC LIMIT 200', $school_id
            ) );
            SS_Admin_Shell::card_open( __( 'Choose Exam Paper', 'school-softwere' ) );
            echo '<form method="get" class="ss-form"><input type="hidden" name="page" value="school-softwere-exams"><input type="hidden" name="tab" value="results">';
            SS_School::select( 'exam_paper_id', __( 'Exam Paper', 'school-softwere' ), 0, $papers, true );
            echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-magnifying-glass"></i> ' . esc_html__( 'Load', 'school-softwere' ) . '</button></div></form>';
            SS_Admin_Shell::card_close();
            return;
        }
        $paper = $wpdb->get_row( $wpdb->prepare( 'SELECT p.*, e.class_school_id FROM ' . SS_Helper::table( 'exam_papers' ) . ' p LEFT JOIN ' . SS_Helper::table( 'exams' ) . ' e ON e.ID = p.exam_id WHERE p.ID = %d', $paper_id ) );
        if ( ! $paper ) { return; }
        $students = SS_M_Staff_Class::students( $paper->class_school_id );
        $existing = array();
        foreach ( (array) $wpdb->get_results( $wpdb->prepare( 'SELECT student_record_id, obtained_marks FROM ' . SS_Helper::table( 'exam_results' ) . ' WHERE exam_paper_id = %d', $paper_id ) ) as $r ) {
            $existing[ (int) $r->student_record_id ] = $r->obtained_marks;
        }

        SS_Admin_Shell::card_open( __( 'Enter Results', 'school-softwere' ),
            '<a class="ss-btn ss-btn-secondary ss-btn-sm" href="' . esc_url( SS_Helper::admin_url() . '&ss_print=bulk-results&exam_paper_id=' . $paper_id ) . '" target="_blank"><i class="ph ph-printer"></i> ' . esc_html__( 'Print Results', 'school-softwere' ) . '</a>'
        );
        if ( empty( $students ) ) {
            echo '<div class="ss-empty"><i class="ph ph-user"></i><h3>' . esc_html__( 'No students', 'school-softwere' ) . '</h3></div>';
        } else {
            echo '<form method="post" class="ss-form">';
            SS_Helper::nonce_field( 'save_results' );
            echo '<input type="hidden" name="ss_action" value="save_results">';
            echo '<input type="hidden" name="exam_paper_id" value="' . (int) $paper_id . '">';
            echo '<p class="ss-text-muted">' . esc_html( sprintf( __( 'Total Marks: %1$d / Pass Marks: %2$d', 'school-softwere' ), (int) $paper->total_marks, (int) $paper->pass_marks ) ) . '</p>';
            echo '<div class="ss-table-wrap"><table class="ss-table"><thead><tr><th>' . esc_html__( 'Roll', 'school-softwere' ) . '</th><th>' . esc_html__( 'Student', 'school-softwere' ) . '</th><th>' . esc_html__( 'Marks Obtained', 'school-softwere' ) . '</th></tr></thead><tbody>';
            foreach ( $students as $s ) {
                $val = $existing[ (int) $s->ID ] ?? '';
                echo '<tr><td>' . esc_html( $s->roll_number ) . '</td><td><strong>' . esc_html( trim( $s->first_name . ' ' . $s->last_name ) ) . '</strong></td><td><input type="number" step="0.01" min="0" max="' . (int) $paper->total_marks . '" name="marks[' . (int) $s->ID . ']" value="' . esc_attr( $val ) . '" style="width:120px"></td></tr>';
            }
            echo '</tbody></table></div>';
            echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-floppy-disk"></i> ' . esc_html__( 'Save Results', 'school-softwere' ) . '</button></div></form>';
        }
        SS_Admin_Shell::card_close();
    }

    private static function tab_admit( $school_id ) {
        global $wpdb;
        $exam_id = isset( $_GET['exam_id'] ) ? (int) $_GET['exam_id'] : 0;
        $exams   = $wpdb->get_results( $wpdb->prepare( 'SELECT e.ID, CONCAT(c.label," - ",e.label) as label FROM ' . SS_Helper::table( 'exams' ) . ' e LEFT JOIN ' . SS_Helper::table( 'class_school' ) . ' cs ON cs.ID = e.class_school_id LEFT JOIN ' . SS_Helper::table( 'classes' ) . ' c ON c.ID = cs.class_id WHERE cs.school_id = %d ORDER BY e.ID DESC', $school_id ) );

        SS_Admin_Shell::card_open( __( 'Generate Admit Cards', 'school-softwere' ) );
        echo '<form method="post" class="ss-form">';
        SS_Helper::nonce_field( 'gen_admit' );
        echo '<input type="hidden" name="ss_action" value="gen_admit">';
        SS_School::select( 'exam_id', __( 'Exam', 'school-softwere' ), $exam_id, $exams, true );
        echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-identification-card"></i> ' . esc_html__( 'Generate Admit Cards', 'school-softwere' ) . '</button></div></form>';
        SS_Admin_Shell::card_close();

        if ( $exam_id ) {
            SS_Admin_Shell::card_open( __( 'Admit Cards', 'school-softwere' ),
                '<a class="ss-btn ss-btn-secondary ss-btn-sm" href="' . esc_url( SS_Helper::admin_url() . '&ss_print=admit_cards&exam_id=' . $exam_id ) . '" target="_blank"><i class="ph ph-printer"></i> ' . esc_html__( 'Print All', 'school-softwere' ) . '</a>'
            );
            $rows = $wpdb->get_results( $wpdb->prepare(
                'SELECT a.*, sr.first_name, sr.last_name, sr.admission_number FROM ' . SS_Helper::table( 'admit_cards' ) . ' a LEFT JOIN ' . SS_Helper::table( 'student_records' ) . ' sr ON sr.ID = a.student_record_id WHERE a.exam_id = %d', $exam_id
            ) );
            if ( empty( $rows ) ) {
                echo '<div class="ss-empty"><i class="ph ph-identification-card"></i><h3>' . esc_html__( 'No admit cards yet', 'school-softwere' ) . '</h3></div>';
            } else {
                echo '<div class="ss-table-wrap"><table class="ss-table"><thead><tr><th>' . esc_html__( 'Card #', 'school-softwere' ) . '</th><th>' . esc_html__( 'Student', 'school-softwere' ) . '</th><th>' . esc_html__( 'Adm. #', 'school-softwere' ) . '</th><th></th></tr></thead><tbody>';
                foreach ( $rows as $r ) {
                    echo '<tr><td><strong>' . esc_html( $r->admit_card_number ) . '</strong></td><td>' . esc_html( trim( $r->first_name . ' ' . $r->last_name ) ) . '</td><td>' . esc_html( $r->admission_number ) . '</td><td class="ss-text-right"><a class="ss-btn ss-btn-secondary ss-btn-sm ss-btn-icon" href="' . esc_url( SS_Helper::admin_url() . '&ss_print=admit_card&id=' . $r->ID ) . '" target="_blank"><i class="ph ph-printer"></i></a></td></tr>';
                }
                echo '</tbody></table></div>';
            }
            SS_Admin_Shell::card_close();
        }
    }
}

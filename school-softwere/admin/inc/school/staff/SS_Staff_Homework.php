<?php
/**
 * SS_Staff_Homework - Homework, submissions and study materials.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Staff_Homework {

    public static function render() {
        global $wpdb;
        $school_id = SS_Helper::active_school_id();
        $tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'homework';

        if ( isset( $_POST['ss_action'] ) && 'save_homework' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'save_homework' ) ) {
            $tbl = SS_Helper::table( 'homework' );
            $wpdb->insert( $tbl, array(
                'class_school_id' => (int) ( $_POST['class_school_id'] ?? 0 ),
                'section_id'      => (int) ( $_POST['section_id'] ?? 0 ) ?: null,
                'subject_id'      => (int) ( $_POST['subject_id'] ?? 0 ) ?: null,
                'staff_id'        => (int) ( $_POST['staff_id'] ?? 0 ) ?: null,
                'title'           => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
                'description'     => wp_kses_post( wp_unslash( $_POST['description'] ?? '' ) ),
                'submission_date' => sanitize_text_field( wp_unslash( $_POST['submission_date'] ?? '' ) ) ?: null,
                'attachment'      => esc_url_raw( wp_unslash( $_POST['attachment'] ?? '' ) ),
                'created_at'      => current_time( 'mysql' ),
            ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-homework' ) ); exit;
        }
        if ( 'delete' === ( $_GET['view'] ?? '' ) && SS_Helper::verify_nonce( 'delete_homework' ) ) {
            $wpdb->delete( SS_Helper::table( 'homework' ), array( 'ID' => (int) $_GET['id'] ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-homework' ) ); exit;
        }
        if ( isset( $_POST['ss_action'] ) && 'save_material' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'save_material' ) ) {
            $wpdb->insert( SS_Helper::table( 'study_materials' ), array(
                'class_school_id' => (int) ( $_POST['class_school_id'] ?? 0 ),
                'section_id'      => (int) ( $_POST['section_id'] ?? 0 ) ?: null,
                'subject_id'      => (int) ( $_POST['subject_id'] ?? 0 ) ?: null,
                'title'           => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
                'description'     => wp_kses_post( wp_unslash( $_POST['description'] ?? '' ) ),
                'file_type'       => sanitize_text_field( wp_unslash( $_POST['file_type'] ?? '' ) ),
                'file'            => esc_url_raw( wp_unslash( $_POST['file'] ?? '' ) ),
                'created_at'      => current_time( 'mysql' ),
            ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-homework' ) . '&tab=materials' ); exit;
        }

        SS_Admin_Shell::open( __( 'Homework', 'school-softwere' ), 'school-softwere-homework', array(
            array( 'label' => __( 'Homework', 'school-softwere' ) ),
        ) );

        echo '<div class="ss-tabs" style="margin-bottom:16px;">';
        $base = SS_Helper::admin_url( 'school-softwere-homework' );
        echo '<a class="ss-tab ' . ( 'homework' === $tab ? 'active' : '' ) . '" href="' . esc_url( $base ) . '">' . esc_html__( 'Homework', 'school-softwere' ) . '</a>';
        echo '<a class="ss-tab ' . ( 'materials' === $tab ? 'active' : '' ) . '" href="' . esc_url( $base . '&tab=materials' ) . '">' . esc_html__( 'Study Materials', 'school-softwere' ) . '</a>';
        echo '</div>';

        $cs       = SS_Staff_Accountant::class_schools_for( $school_id );
        $sections = $wpdb->get_results( 'SELECT * FROM ' . SS_Helper::table( 'sections' ) . ' ORDER BY label' );
        $subjects = $wpdb->get_results( 'SELECT * FROM ' . SS_Helper::table( 'subjects' ) . ' ORDER BY label' );
        $staff    = $wpdb->get_results( $wpdb->prepare( 'SELECT ID, CONCAT(first_name," ",last_name) as label FROM ' . SS_Helper::table( 'staff' ) . ' WHERE school_id = %d ORDER BY first_name', $school_id ) );

        if ( 'materials' === $tab ) {
            echo '<div class="ss-row">';
            echo '<div class="ss-col" style="flex:1">';
            SS_Admin_Shell::card_open( __( 'Add Study Material', 'school-softwere' ) );
            echo '<form method="post" class="ss-form">';
            SS_Helper::nonce_field( 'save_material' );
            echo '<input type="hidden" name="ss_action" value="save_material">';
            SS_School::select( 'class_school_id', __( 'Class', 'school-softwere' ), 0, $cs, true );
            SS_School::select( 'section_id',      __( 'Section', 'school-softwere' ), 0, $sections );
            SS_School::select( 'subject_id',      __( 'Subject', 'school-softwere' ), 0, $subjects );
            SS_School::field( 'title', __( 'Title', 'school-softwere' ), '', true );
            SS_School::textarea( 'description', __( 'Description', 'school-softwere' ), '' );
            SS_School::field( 'file_type', __( 'File Type (pdf/video/doc)', 'school-softwere' ), '' );
            SS_School::field( 'file', __( 'File URL', 'school-softwere' ), '' );
            echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-plus"></i> ' . esc_html__( 'Add Material', 'school-softwere' ) . '</button></div></form>';
            SS_Admin_Shell::card_close();
            echo '</div>';

            echo '<div class="ss-col" style="flex:2">';
            SS_Admin_Shell::card_open( __( 'All Materials', 'school-softwere' ) );
            $rows = $wpdb->get_results( $wpdb->prepare(
                'SELECT m.*, c.label class_label, s.label subject_label FROM ' . SS_Helper::table( 'study_materials' ) . ' m
                 LEFT JOIN ' . SS_Helper::table( 'class_school' ) . ' cs ON cs.ID = m.class_school_id
                 LEFT JOIN ' . SS_Helper::table( 'classes' ) . ' c ON c.ID = cs.class_id
                 LEFT JOIN ' . SS_Helper::table( 'subjects' ) . ' s ON s.ID = m.subject_id
                 WHERE cs.school_id = %d ORDER BY m.ID DESC LIMIT 200', $school_id
            ) );
            if ( empty( $rows ) ) {
                echo '<div class="ss-empty"><i class="ph ph-file"></i><h3>' . esc_html__( 'No materials', 'school-softwere' ) . '</h3></div>';
            } else {
                echo '<div class="ss-table-wrap"><table class="ss-datatable ss-table"><thead><tr><th>' . esc_html__( 'Title', 'school-softwere' ) . '</th><th>' . esc_html__( 'Class', 'school-softwere' ) . '</th><th>' . esc_html__( 'Subject', 'school-softwere' ) . '</th><th>' . esc_html__( 'Type', 'school-softwere' ) . '</th><th>' . esc_html__( 'File', 'school-softwere' ) . '</th></tr></thead><tbody>';
                foreach ( $rows as $r ) {
                    echo '<tr><td><strong>' . esc_html( $r->title ) . '</strong></td><td>' . esc_html( $r->class_label ) . '</td><td>' . esc_html( $r->subject_label ?: '-' ) . '</td><td>' . esc_html( $r->file_type ) . '</td><td>' . ( $r->file ? '<a target="_blank" href="' . esc_url( $r->file ) . '"><i class="ph ph-link"></i> ' . esc_html__( 'Open', 'school-softwere' ) . '</a>' : '-' ) . '</td></tr>';
                }
                echo '</tbody></table></div>';
            }
            SS_Admin_Shell::card_close();
            echo '</div>';
            echo '</div>';
        } else {
            // Homework tab.
            echo '<div class="ss-row">';
            echo '<div class="ss-col" style="flex:1">';
            SS_Admin_Shell::card_open( __( 'Assign Homework', 'school-softwere' ) );
            echo '<form method="post" class="ss-form">';
            SS_Helper::nonce_field( 'save_homework' );
            echo '<input type="hidden" name="ss_action" value="save_homework">';
            SS_School::select( 'class_school_id', __( 'Class', 'school-softwere' ), 0, $cs, true );
            SS_School::select( 'section_id',      __( 'Section', 'school-softwere' ), 0, $sections );
            SS_School::select( 'subject_id',      __( 'Subject', 'school-softwere' ), 0, $subjects );
            SS_School::select( 'staff_id',        __( 'Teacher', 'school-softwere' ), 0, $staff );
            SS_School::field( 'title', __( 'Title', 'school-softwere' ), '', true );
            SS_School::textarea( 'description', __( 'Description', 'school-softwere' ), '' );
            echo '<div class="ss-field"><label>' . esc_html__( 'Submission Date', 'school-softwere' ) . '</label><input class="ss-date" type="text" name="submission_date"></div>';
            SS_School::field( 'attachment', __( 'Attachment URL', 'school-softwere' ), '' );
            echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-notebook"></i> ' . esc_html__( 'Assign Homework', 'school-softwere' ) . '</button></div></form>';
            SS_Admin_Shell::card_close();
            echo '</div>';

            echo '<div class="ss-col" style="flex:2">';
            SS_Admin_Shell::card_open( __( 'All Homework', 'school-softwere' ) );
            $rows = $wpdb->get_results( $wpdb->prepare(
                'SELECT h.*, c.label class_label, s.label subject_label FROM ' . SS_Helper::table( 'homework' ) . ' h
                 LEFT JOIN ' . SS_Helper::table( 'class_school' ) . ' cs ON cs.ID = h.class_school_id
                 LEFT JOIN ' . SS_Helper::table( 'classes' ) . ' c ON c.ID = cs.class_id
                 LEFT JOIN ' . SS_Helper::table( 'subjects' ) . ' s ON s.ID = h.subject_id
                 WHERE cs.school_id = %d ORDER BY h.ID DESC LIMIT 200', $school_id
            ) );
            if ( empty( $rows ) ) {
                echo '<div class="ss-empty"><i class="ph ph-notebook"></i><h3>' . esc_html__( 'No homework', 'school-softwere' ) . '</h3></div>';
            } else {
                echo '<div class="ss-table-wrap"><table class="ss-datatable ss-table"><thead><tr><th>' . esc_html__( 'Title', 'school-softwere' ) . '</th><th>' . esc_html__( 'Class', 'school-softwere' ) . '</th><th>' . esc_html__( 'Subject', 'school-softwere' ) . '</th><th>' . esc_html__( 'Submit By', 'school-softwere' ) . '</th><th></th></tr></thead><tbody>';
                foreach ( $rows as $r ) {
                    $del = wp_nonce_url( SS_Helper::admin_url( 'school-softwere-homework' ) . '&view=delete&id=' . $r->ID, 'delete_homework', '_ssnonce' );
                    echo '<tr><td><strong>' . esc_html( $r->title ) . '</strong></td><td>' . esc_html( $r->class_label ) . '</td><td>' . esc_html( $r->subject_label ?: '-' ) . '</td><td>' . esc_html( SS_Helper::format_date( $r->submission_date ) ) . '</td><td class="ss-text-right"><a class="ss-btn ss-btn-danger ss-btn-sm ss-btn-icon ss-confirm-delete" href="' . esc_url( $del ) . '"><i class="ph ph-trash"></i></a></td></tr>';
                }
                echo '</tbody></table></div>';
            }
            SS_Admin_Shell::card_close();
            echo '</div>';
            echo '</div>';
        }

        SS_Admin_Shell::close();
    }
}

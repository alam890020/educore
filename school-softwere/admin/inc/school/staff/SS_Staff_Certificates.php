<?php
/**
 * SS_Staff_Certificates - Certificate templates and issuance.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Staff_Certificates {

    public static function render() {
        global $wpdb;
        $school_id = SS_Helper::active_school_id();
        $tbl = SS_Helper::table( 'certificates' );
        $tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'templates';

        if ( isset( $_POST['ss_action'] ) && 'save_cert' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'save_cert' ) ) {
            $wpdb->insert( $tbl, array(
                'school_id'        => $school_id,
                'label'            => sanitize_text_field( wp_unslash( $_POST['label'] ?? '' ) ),
                'content_template' => wp_kses_post( wp_unslash( $_POST['content_template'] ?? '' ) ),
                'created_at'       => current_time( 'mysql' ),
            ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-certificates' ) ); exit;
        }
        if ( 'delete_cert' === ( $_GET['view'] ?? '' ) && SS_Helper::verify_nonce( 'delete_cert' ) ) {
            $wpdb->delete( $tbl, array( 'ID' => (int) $_GET['id'], 'school_id' => $school_id ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-certificates' ) ); exit;
        }
        if ( isset( $_POST['ss_action'] ) && 'issue_cert' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'issue_cert' ) ) {
            $wpdb->insert( SS_Helper::table( 'certificate_student' ), array(
                'certificate_id'    => (int) ( $_POST['certificate_id'] ?? 0 ),
                'student_record_id' => (int) ( $_POST['student_record_id'] ?? 0 ),
                'issued_date'       => sanitize_text_field( wp_unslash( $_POST['issued_date'] ?? current_time( 'Y-m-d' ) ) ),
                'created_at'        => current_time( 'mysql' ),
            ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-certificates' ) . '&tab=issued' ); exit;
        }
        if ( isset( $_POST['ss_action'] ) && 'issue_tc' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'issue_tc' ) ) {
            $wpdb->insert( SS_Helper::table( 'transfer_certificates' ), array(
                'student_record_id' => (int) ( $_POST['student_record_id'] ?? 0 ),
                'reason'            => sanitize_textarea_field( wp_unslash( $_POST['reason'] ?? '' ) ),
                'issued_date'       => current_time( 'Y-m-d' ),
                'created_at'        => current_time( 'mysql' ),
            ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-certificates' ) . '&tab=tc' ); exit;
        }

        SS_Admin_Shell::open( __( 'Certificates', 'school-softwere' ), 'school-softwere-certificates', array(
            array( 'label' => __( 'Certificates', 'school-softwere' ) ),
        ) );

        echo '<div class="ss-tabs" style="margin-bottom:16px;">';
        $base = SS_Helper::admin_url( 'school-softwere-certificates' );
        echo '<a class="ss-tab ' . ( 'templates' === $tab ? 'active' : '' ) . '" href="' . esc_url( $base ) . '">' . esc_html__( 'Templates', 'school-softwere' ) . '</a>';
        echo '<a class="ss-tab ' . ( 'issued'    === $tab ? 'active' : '' ) . '" href="' . esc_url( $base . '&tab=issued' ) . '">' . esc_html__( 'Issued', 'school-softwere' ) . '</a>';
        echo '<a class="ss-tab ' . ( 'tc'        === $tab ? 'active' : '' ) . '" href="' . esc_url( $base . '&tab=tc' ) . '">' . esc_html__( 'Transfer Certificates', 'school-softwere' ) . '</a>';
        echo '</div>';

        $students = $wpdb->get_results( $wpdb->prepare( 'SELECT ID, CONCAT(first_name," ",last_name," (",admission_number,")") as label FROM ' . SS_Helper::table( 'student_records' ) . ' WHERE school_id = %d ORDER BY first_name', $school_id ) );

        if ( 'issued' === $tab ) {
            $certs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl} WHERE school_id = %d", $school_id ) );
            echo '<div class="ss-row"><div class="ss-col" style="flex:1">';
            SS_Admin_Shell::card_open( __( 'Issue Certificate', 'school-softwere' ) );
            echo '<form method="post" class="ss-form">';
            SS_Helper::nonce_field( 'issue_cert' );
            echo '<input type="hidden" name="ss_action" value="issue_cert">';
            SS_School::select( 'certificate_id', __( 'Template', 'school-softwere' ), 0, $certs, true );
            SS_School::select( 'student_record_id', __( 'Student', 'school-softwere' ), 0, $students, true );
            echo '<div class="ss-field"><label>' . esc_html__( 'Issued Date', 'school-softwere' ) . '</label><input class="ss-date" type="text" name="issued_date" value="' . esc_attr( current_time( 'Y-m-d' ) ) . '"></div>';
            echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-certificate"></i> ' . esc_html__( 'Issue', 'school-softwere' ) . '</button></div></form>';
            SS_Admin_Shell::card_close();
            echo '</div><div class="ss-col" style="flex:2">';
            SS_Admin_Shell::card_open( __( 'Issued Certificates', 'school-softwere' ) );
            $rows = $wpdb->get_results( $wpdb->prepare(
                'SELECT cs.*, c.label cert_label, sr.first_name, sr.last_name FROM ' . SS_Helper::table( 'certificate_student' ) . ' cs LEFT JOIN ' . SS_Helper::table( 'certificates' ) . ' c ON c.ID = cs.certificate_id LEFT JOIN ' . SS_Helper::table( 'student_records' ) . ' sr ON sr.ID = cs.student_record_id WHERE sr.school_id = %d ORDER BY cs.ID DESC LIMIT 200', $school_id
            ) );
            if ( empty( $rows ) ) {
                echo '<div class="ss-empty"><i class="ph ph-certificate"></i><h3>' . esc_html__( 'None issued yet', 'school-softwere' ) . '</h3></div>';
            } else {
                echo '<div class="ss-table-wrap"><table class="ss-datatable ss-table"><thead><tr><th>' . esc_html__( 'Student', 'school-softwere' ) . '</th><th>' . esc_html__( 'Certificate', 'school-softwere' ) . '</th><th>' . esc_html__( 'Issued', 'school-softwere' ) . '</th><th></th></tr></thead><tbody>';
                foreach ( $rows as $r ) {
                    echo '<tr><td>' . esc_html( trim( $r->first_name . ' ' . $r->last_name ) ) . '</td><td><strong>' . esc_html( $r->cert_label ) . '</strong></td><td>' . esc_html( SS_Helper::format_date( $r->issued_date ) ) . '</td><td class="ss-text-right"><a class="ss-btn ss-btn-secondary ss-btn-sm ss-btn-icon" href="' . esc_url( SS_Helper::admin_url() . '&ss_print=certificate&id=' . $r->ID ) . '" target="_blank"><i class="ph ph-printer"></i></a></td></tr>';
                }
                echo '</tbody></table></div>';
            }
            SS_Admin_Shell::card_close();
            echo '</div></div>';
        } elseif ( 'tc' === $tab ) {
            echo '<div class="ss-row"><div class="ss-col" style="flex:1">';
            SS_Admin_Shell::card_open( __( 'Issue Transfer Certificate', 'school-softwere' ) );
            echo '<form method="post" class="ss-form">';
            SS_Helper::nonce_field( 'issue_tc' );
            echo '<input type="hidden" name="ss_action" value="issue_tc">';
            SS_School::select( 'student_record_id', __( 'Student', 'school-softwere' ), 0, $students, true );
            SS_School::textarea( 'reason', __( 'Reason', 'school-softwere' ), '' );
            echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-paper-plane"></i> ' . esc_html__( 'Issue TC', 'school-softwere' ) . '</button></div></form>';
            SS_Admin_Shell::card_close();
            echo '</div><div class="ss-col" style="flex:2">';
            SS_Admin_Shell::card_open( __( 'Transfer Certificates', 'school-softwere' ) );
            $rows = $wpdb->get_results( $wpdb->prepare(
                'SELECT t.*, sr.first_name, sr.last_name FROM ' . SS_Helper::table( 'transfer_certificates' ) . ' t LEFT JOIN ' . SS_Helper::table( 'student_records' ) . ' sr ON sr.ID = t.student_record_id WHERE sr.school_id = %d ORDER BY t.ID DESC', $school_id
            ) );
            if ( empty( $rows ) ) {
                echo '<div class="ss-empty"><i class="ph ph-paper-plane"></i><h3>' . esc_html__( 'No TCs issued', 'school-softwere' ) . '</h3></div>';
            } else {
                echo '<div class="ss-table-wrap"><table class="ss-table"><thead><tr><th>' . esc_html__( 'Student', 'school-softwere' ) . '</th><th>' . esc_html__( 'Reason', 'school-softwere' ) . '</th><th>' . esc_html__( 'Issued', 'school-softwere' ) . '</th></tr></thead><tbody>';
                foreach ( $rows as $r ) {
                    echo '<tr><td><strong>' . esc_html( trim( $r->first_name . ' ' . $r->last_name ) ) . '</strong></td><td>' . esc_html( wp_trim_words( $r->reason, 16 ) ) . '</td><td>' . esc_html( SS_Helper::format_date( $r->issued_date ) ) . '</td></tr>';
                }
                echo '</tbody></table></div>';
            }
            SS_Admin_Shell::card_close();
            echo '</div></div>';
        } else {
            echo '<div class="ss-row"><div class="ss-col" style="flex:1">';
            SS_Admin_Shell::card_open( __( 'Add Template', 'school-softwere' ) );
            echo '<form method="post" class="ss-form">';
            SS_Helper::nonce_field( 'save_cert' );
            echo '<input type="hidden" name="ss_action" value="save_cert">';
            SS_School::field( 'label', __( 'Template Label', 'school-softwere' ), '', true );
            SS_School::textarea( 'content_template', __( 'Content Template (HTML)', 'school-softwere' ), 'This is to certify that {student_name} of {class} has...' );
            echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-floppy-disk"></i> ' . esc_html__( 'Save', 'school-softwere' ) . '</button></div></form>';
            SS_Admin_Shell::card_close();
            echo '</div><div class="ss-col" style="flex:2">';
            SS_Admin_Shell::card_open( __( 'Templates', 'school-softwere' ) );
            $rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl} WHERE school_id = %d ORDER BY ID DESC", $school_id ) );
            if ( empty( $rows ) ) {
                echo '<div class="ss-empty"><i class="ph ph-certificate"></i><h3>' . esc_html__( 'No templates', 'school-softwere' ) . '</h3></div>';
            } else {
                echo '<div class="ss-table-wrap"><table class="ss-table"><thead><tr><th>' . esc_html__( 'Label', 'school-softwere' ) . '</th><th></th></tr></thead><tbody>';
                foreach ( $rows as $r ) {
                    $del = wp_nonce_url( SS_Helper::admin_url( 'school-softwere-certificates' ) . '&view=delete_cert&id=' . $r->ID, 'delete_cert', '_ssnonce' );
                    echo '<tr><td><strong>' . esc_html( $r->label ) . '</strong></td><td class="ss-text-right"><a class="ss-btn ss-btn-danger ss-btn-sm ss-btn-icon ss-confirm-delete" href="' . esc_url( $del ) . '"><i class="ph ph-trash"></i></a></td></tr>';
                }
                echo '</tbody></table></div>';
            }
            SS_Admin_Shell::card_close();
            echo '</div></div>';
        }
        SS_Admin_Shell::close();
    }
}

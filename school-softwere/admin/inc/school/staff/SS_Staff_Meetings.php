<?php
/**
 * SS_Staff_Meetings - Live class meetings (Zoom/Meet links).
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Staff_Meetings {

    public static function render() {
        global $wpdb;
        $school_id = SS_Helper::active_school_id();
        $tbl = SS_Helper::table( 'meetings' );

        if ( isset( $_POST['ss_action'] ) && 'save_meeting' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'save_meeting' ) ) {
            $wpdb->insert( $tbl, array(
                'school_id'       => $school_id,
                'class_school_id' => (int) ( $_POST['class_school_id'] ?? 0 ) ?: null,
                'section_id'      => (int) ( $_POST['section_id'] ?? 0 ) ?: null,
                'title'           => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
                'meeting_link'    => esc_url_raw( wp_unslash( $_POST['meeting_link'] ?? '' ) ),
                'start_time'      => sanitize_text_field( wp_unslash( $_POST['start_time'] ?? '' ) ) ?: null,
                'duration'        => (int) ( $_POST['duration'] ?? 0 ),
                'created_at'      => current_time( 'mysql' ),
            ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-meetings' ) ); exit;
        }
        if ( 'delete' === ( $_GET['view'] ?? '' ) && SS_Helper::verify_nonce( 'delete_meeting' ) ) {
            $wpdb->delete( $tbl, array( 'ID' => (int) $_GET['id'], 'school_id' => $school_id ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-meetings' ) ); exit;
        }

        SS_Admin_Shell::open( __( 'Live Classes', 'school-softwere' ), 'school-softwere-meetings', array(
            array( 'label' => __( 'Live Classes', 'school-softwere' ) ),
        ) );

        $cs       = SS_Staff_Accountant::class_schools_for( $school_id );
        $sections = $wpdb->get_results( 'SELECT * FROM ' . SS_Helper::table( 'sections' ) . ' ORDER BY label' );

        echo '<div class="ss-row"><div class="ss-col" style="flex:1">';
        SS_Admin_Shell::card_open( __( 'Schedule Meeting', 'school-softwere' ) );
        echo '<form method="post" class="ss-form">';
        SS_Helper::nonce_field( 'save_meeting' );
        echo '<input type="hidden" name="ss_action" value="save_meeting">';
        SS_School::select( 'class_school_id', __( 'Class', 'school-softwere' ), 0, $cs );
        SS_School::select( 'section_id', __( 'Section', 'school-softwere' ), 0, $sections );
        SS_School::field( 'title', __( 'Title', 'school-softwere' ), '', true );
        SS_School::field( 'meeting_link', __( 'Meeting Link (Zoom/Meet)', 'school-softwere' ), '', true );
        echo '<div class="ss-field"><label>' . esc_html__( 'Start Time', 'school-softwere' ) . '</label><input class="ss-datetime" type="text" name="start_time"></div>';
        SS_School::field( 'duration', __( 'Duration (minutes)', 'school-softwere' ), 60, false, 'number' );
        echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-monitor-play"></i> ' . esc_html__( 'Schedule', 'school-softwere' ) . '</button></div></form>';
        SS_Admin_Shell::card_close();
        echo '</div><div class="ss-col" style="flex:2">';
        SS_Admin_Shell::card_open( __( 'All Live Classes', 'school-softwere' ) );
        $rows = $wpdb->get_results( $wpdb->prepare(
            'SELECT m.*, c.label class_label FROM ' . $tbl . ' m LEFT JOIN ' . SS_Helper::table( 'class_school' ) . ' cs ON cs.ID = m.class_school_id LEFT JOIN ' . SS_Helper::table( 'classes' ) . ' c ON c.ID = cs.class_id WHERE m.school_id = %d ORDER BY m.start_time DESC LIMIT 200', $school_id
        ) );
        if ( empty( $rows ) ) {
            echo '<div class="ss-empty"><i class="ph ph-monitor-play"></i><h3>' . esc_html__( 'No meetings', 'school-softwere' ) . '</h3></div>';
        } else {
            echo '<div class="ss-table-wrap"><table class="ss-datatable ss-table"><thead><tr><th>' . esc_html__( 'Title', 'school-softwere' ) . '</th><th>' . esc_html__( 'Class', 'school-softwere' ) . '</th><th>' . esc_html__( 'Start', 'school-softwere' ) . '</th><th>' . esc_html__( 'Link', 'school-softwere' ) . '</th><th></th></tr></thead><tbody>';
            foreach ( $rows as $r ) {
                $del = wp_nonce_url( SS_Helper::admin_url( 'school-softwere-meetings' ) . '&view=delete&id=' . $r->ID, 'delete_meeting', '_ssnonce' );
                echo '<tr><td><strong>' . esc_html( $r->title ) . '</strong></td><td>' . esc_html( $r->class_label ?: '-' ) . '</td><td>' . esc_html( SS_Helper::format_date( $r->start_time ) ) . '</td><td>' . ( $r->meeting_link ? '<a target="_blank" class="ss-btn ss-btn-secondary ss-btn-sm" href="' . esc_url( $r->meeting_link ) . '"><i class="ph ph-link"></i> ' . esc_html__( 'Join', 'school-softwere' ) . '</a>' : '-' ) . '</td><td class="ss-text-right"><a class="ss-btn ss-btn-danger ss-btn-sm ss-btn-icon ss-confirm-delete" href="' . esc_url( $del ) . '"><i class="ph ph-trash"></i></a></td></tr>'; // phpcs:ignore
            }
            echo '</tbody></table></div>';
        }
        SS_Admin_Shell::card_close();
        echo '</div></div>';
        SS_Admin_Shell::close();
    }
}

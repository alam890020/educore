<?php
/**
 * SS_Session - Sessions CRUD.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Session {

    public static function render() {
        if ( ! current_user_can( SS_CAP_SUPER ) ) { wp_die( esc_html__( 'No access.', 'school-softwere' ) ); }
        global $wpdb;
        $tbl     = SS_Helper::table( 'sessions' );
        $schools = $wpdb->get_results( 'SELECT * FROM ' . SS_Helper::table( 'schools' ) . ' ORDER BY label ASC' );
        $view    = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'list';

        if ( isset( $_POST['ss_action'] ) && 'save_session' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'save_session' ) ) {
            $id   = (int) ( $_POST['id'] ?? 0 );
            $data = array(
                'school_id'  => (int) ( $_POST['school_id'] ?? 0 ),
                'label'      => sanitize_text_field( wp_unslash( $_POST['label'] ?? '' ) ),
                'start_date' => sanitize_text_field( wp_unslash( $_POST['start_date'] ?? '' ) ),
                'end_date'   => sanitize_text_field( wp_unslash( $_POST['end_date'] ?? '' ) ),
                'is_active'  => empty( $_POST['is_active'] ) ? 0 : 1,
            );
            if ( $data['is_active'] ) {
                $wpdb->update( $tbl, array( 'is_active' => 0 ), array( 'school_id' => $data['school_id'] ) );
            }
            if ( $id ) { $wpdb->update( $tbl, $data, array( 'ID' => $id ) ); }
            else      { $wpdb->insert( $tbl, $data ); }
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-sessions' ) ); exit;
        }
        if ( 'delete' === $view && SS_Helper::verify_nonce( 'delete_session' ) ) {
            $wpdb->delete( $tbl, array( 'ID' => (int) $_GET['id'] ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-sessions' ) ); exit;
        }

        SS_Admin_Shell::open( __( 'Academic Sessions', 'school-softwere' ), 'school-softwere-sessions', array(
            array( 'label' => __( 'Sessions', 'school-softwere' ) ),
        ) );

        $editing = null;
        if ( 'edit' === $view && ! empty( $_GET['id'] ) ) {
            $editing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tbl} WHERE ID = %d", (int) $_GET['id'] ) );
        }

        echo '<div class="ss-row">';
        echo '<div class="ss-col" style="flex:1">';
        SS_Admin_Shell::card_open( $editing ? __( 'Edit Session', 'school-softwere' ) : __( 'Add Session', 'school-softwere' ) );
        echo '<form class="ss-form" method="post">';
        SS_Helper::nonce_field( 'save_session' );
        echo '<input type="hidden" name="ss_action" value="save_session">';
        echo '<input type="hidden" name="id" value="' . ( $editing ? (int) $editing->ID : 0 ) . '">';
        SS_School::select( 'school_id', __( 'School', 'school-softwere' ), $editing ? (int) $editing->school_id : 0, $schools, true );
        SS_School::field( 'label', __( 'Session Label (e.g. 2024-2025)', 'school-softwere' ), $editing ? $editing->label : '', true );
        echo '<div class="ss-field"><label>' . esc_html__( 'Start Date', 'school-softwere' ) . '</label><input class="ss-date" type="text" name="start_date" value="' . esc_attr( $editing ? $editing->start_date : '' ) . '"></div>';
        echo '<div class="ss-field"><label>' . esc_html__( 'End Date', 'school-softwere' ) . '</label><input class="ss-date" type="text" name="end_date" value="' . esc_attr( $editing ? $editing->end_date : '' ) . '"></div>';
        SS_School::checkbox( 'is_active', __( 'Set as Active Session', 'school-softwere' ), $editing ? (bool) $editing->is_active : true );
        echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-floppy-disk"></i> ' . esc_html__( 'Save', 'school-softwere' ) . '</button></div></form>';
        SS_Admin_Shell::card_close();
        echo '</div>';

        echo '<div class="ss-col" style="flex:2">';
        SS_Admin_Shell::card_open( __( 'All Sessions', 'school-softwere' ) );
        $rows = $wpdb->get_results( 'SELECT s.*, sch.label as school_label FROM ' . $tbl . ' s LEFT JOIN ' . SS_Helper::table( 'schools' ) . ' sch ON sch.ID = s.school_id ORDER BY s.ID DESC' );
        if ( empty( $rows ) ) {
            echo '<div class="ss-empty"><i class="ph ph-calendar"></i><h3>' . esc_html__( 'No sessions', 'school-softwere' ) . '</h3></div>';
        } else {
            echo '<div class="ss-table-wrap"><table class="ss-table"><thead><tr><th>' . esc_html__( 'Session', 'school-softwere' ) . '</th><th>' . esc_html__( 'School', 'school-softwere' ) . '</th><th>' . esc_html__( 'Period', 'school-softwere' ) . '</th><th>' . esc_html__( 'Status', 'school-softwere' ) . '</th><th class="ss-text-right">' . esc_html__( 'Actions', 'school-softwere' ) . '</th></tr></thead><tbody>';
            foreach ( $rows as $r ) {
                $edit = SS_Helper::admin_url( 'school-softwere-sessions' ) . '&view=edit&id=' . $r->ID;
                $del  = wp_nonce_url( SS_Helper::admin_url( 'school-softwere-sessions' ) . '&view=delete&id=' . $r->ID, 'delete_session', '_ssnonce' );
                echo '<tr><td><strong>' . esc_html( $r->label ) . '</strong></td><td>' . esc_html( $r->school_label ) . '</td><td>' . esc_html( SS_Helper::format_date( $r->start_date ) . ' - ' . SS_Helper::format_date( $r->end_date ) ) . '</td><td>' . SS_Helper::badge( $r->is_active ? __( 'Active', 'school-softwere' ) : __( 'Inactive', 'school-softwere' ), $r->is_active ? 'success' : 'muted' ) . '</td><td class="ss-text-right"><a class="ss-btn ss-btn-secondary ss-btn-sm ss-btn-icon" href="' . esc_url( $edit ) . '"><i class="ph ph-pencil-simple"></i></a> <a class="ss-btn ss-btn-danger ss-btn-sm ss-btn-icon ss-confirm-delete" href="' . esc_url( $del ) . '"><i class="ph ph-trash"></i></a></td></tr>'; // phpcs:ignore
            }
            echo '</tbody></table></div>';
        }
        SS_Admin_Shell::card_close();
        echo '</div>';
        echo '</div>';
        SS_Admin_Shell::close();
    }
}

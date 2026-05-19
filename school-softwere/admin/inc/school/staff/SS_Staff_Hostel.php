<?php
/**
 * SS_Staff_Hostel - Hostels and rooms.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Staff_Hostel {

    public static function render() {
        global $wpdb;
        $school_id = SS_Helper::active_school_id();
        $tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'hostels';

        if ( isset( $_POST['ss_action'] ) && 'save_hostel' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'save_hostel' ) ) {
            $tbl = SS_Helper::table( 'hostels' );
            $id  = (int) ( $_POST['id'] ?? 0 );
            $data = array(
                'school_id'   => $school_id,
                'label'       => sanitize_text_field( wp_unslash( $_POST['label'] ?? '' ) ),
                'type'        => sanitize_key( wp_unslash( $_POST['type'] ?? 'mixed' ) ),
                'warden_name' => sanitize_text_field( wp_unslash( $_POST['warden_name'] ?? '' ) ),
                'capacity'    => (int) ( $_POST['capacity'] ?? 0 ),
            );
            if ( $id ) { $wpdb->update( $tbl, $data, array( 'ID' => $id, 'school_id' => $school_id ) ); }
            else      { $data['created_at'] = current_time( 'mysql' ); $wpdb->insert( $tbl, $data ); }
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-hostel' ) ); exit;
        }
        if ( 'delete_hostel' === ( $_GET['view'] ?? '' ) && SS_Helper::verify_nonce( 'delete_hostel' ) ) {
            $wpdb->delete( SS_Helper::table( 'hostels' ), array( 'ID' => (int) $_GET['id'], 'school_id' => $school_id ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-hostel' ) ); exit;
        }
        if ( isset( $_POST['ss_action'] ) && 'save_room' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'save_room' ) ) {
            $wpdb->insert( SS_Helper::table( 'rooms' ), array(
                'hostel_id'   => (int) ( $_POST['hostel_id'] ?? 0 ),
                'room_number' => sanitize_text_field( wp_unslash( $_POST['room_number'] ?? '' ) ),
                'capacity'    => (int) ( $_POST['capacity'] ?? 0 ),
                'room_type'   => sanitize_text_field( wp_unslash( $_POST['room_type'] ?? '' ) ),
                'fee'         => (float) ( $_POST['fee'] ?? 0 ),
                'created_at'  => current_time( 'mysql' ),
            ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-hostel' ) . '&tab=rooms' ); exit;
        }
        if ( 'delete_room' === ( $_GET['view'] ?? '' ) && SS_Helper::verify_nonce( 'delete_room' ) ) {
            $wpdb->delete( SS_Helper::table( 'rooms' ), array( 'ID' => (int) $_GET['id'] ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-hostel' ) . '&tab=rooms' ); exit;
        }

        SS_Admin_Shell::open( __( 'Hostel', 'school-softwere' ), 'school-softwere-hostel', array(
            array( 'label' => __( 'Hostel', 'school-softwere' ) ),
        ) );
        echo '<div class="ss-tabs" style="margin-bottom:16px;">';
        $base = SS_Helper::admin_url( 'school-softwere-hostel' );
        echo '<a class="ss-tab ' . ( 'hostels' === $tab ? 'active' : '' ) . '" href="' . esc_url( $base ) . '">' . esc_html__( 'Hostels', 'school-softwere' ) . '</a>';
        echo '<a class="ss-tab ' . ( 'rooms'   === $tab ? 'active' : '' ) . '" href="' . esc_url( $base . '&tab=rooms' ) . '">' . esc_html__( 'Rooms', 'school-softwere' ) . '</a>';
        echo '</div>';

        if ( 'rooms' === $tab ) {
            $hostels = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . SS_Helper::table( 'hostels' ) . ' WHERE school_id = %d', $school_id ) );
            echo '<div class="ss-row"><div class="ss-col" style="flex:1">';
            SS_Admin_Shell::card_open( __( 'Add Room', 'school-softwere' ) );
            echo '<form method="post" class="ss-form">';
            SS_Helper::nonce_field( 'save_room' );
            echo '<input type="hidden" name="ss_action" value="save_room">';
            SS_School::select( 'hostel_id', __( 'Hostel', 'school-softwere' ), 0, $hostels, true );
            SS_School::field( 'room_number', __( 'Room Number', 'school-softwere' ), '', true );
            SS_School::field( 'capacity', __( 'Capacity', 'school-softwere' ), 1, false, 'number' );
            SS_School::field( 'room_type', __( 'Room Type', 'school-softwere' ), '' );
            SS_School::field( 'fee', __( 'Monthly Fee', 'school-softwere' ), 0, false, 'number' );
            echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-bed"></i> ' . esc_html__( 'Save', 'school-softwere' ) . '</button></div></form>';
            SS_Admin_Shell::card_close();
            echo '</div><div class="ss-col" style="flex:2">';
            SS_Admin_Shell::card_open( __( 'All Rooms', 'school-softwere' ) );
            $rows = $wpdb->get_results( $wpdb->prepare(
                'SELECT r.*, h.label hostel_label FROM ' . SS_Helper::table( 'rooms' ) . ' r LEFT JOIN ' . SS_Helper::table( 'hostels' ) . ' h ON h.ID = r.hostel_id WHERE h.school_id = %d ORDER BY r.ID DESC', $school_id
            ) );
            if ( empty( $rows ) ) {
                echo '<div class="ss-empty"><i class="ph ph-bed"></i><h3>' . esc_html__( 'No rooms', 'school-softwere' ) . '</h3></div>';
            } else {
                echo '<div class="ss-table-wrap"><table class="ss-table"><thead><tr><th>' . esc_html__( 'Hostel', 'school-softwere' ) . '</th><th>' . esc_html__( 'Room', 'school-softwere' ) . '</th><th>' . esc_html__( 'Type', 'school-softwere' ) . '</th><th>' . esc_html__( 'Capacity', 'school-softwere' ) . '</th><th>' . esc_html__( 'Fee', 'school-softwere' ) . '</th><th></th></tr></thead><tbody>';
                foreach ( $rows as $r ) {
                    $del = wp_nonce_url( SS_Helper::admin_url( 'school-softwere-hostel' ) . '&tab=rooms&view=delete_room&id=' . $r->ID, 'delete_room', '_ssnonce' );
                    echo '<tr><td>' . esc_html( $r->hostel_label ) . '</td><td><strong>' . esc_html( $r->room_number ) . '</strong></td><td>' . esc_html( $r->room_type ) . '</td><td>' . (int) $r->capacity . '</td><td>' . esc_html( SS_Helper::format_money( $r->fee ) ) . '</td><td class="ss-text-right"><a class="ss-btn ss-btn-danger ss-btn-sm ss-btn-icon ss-confirm-delete" href="' . esc_url( $del ) . '"><i class="ph ph-trash"></i></a></td></tr>';
                }
                echo '</tbody></table></div>';
            }
            SS_Admin_Shell::card_close();
            echo '</div></div>';
        } else {
            echo '<div class="ss-row"><div class="ss-col" style="flex:1">';
            SS_Admin_Shell::card_open( __( 'Add Hostel', 'school-softwere' ) );
            echo '<form method="post" class="ss-form">';
            SS_Helper::nonce_field( 'save_hostel' );
            echo '<input type="hidden" name="ss_action" value="save_hostel">';
            SS_School::field( 'label', __( 'Hostel Name', 'school-softwere' ), '', true );
            echo '<div class="ss-field"><label>' . esc_html__( 'Type', 'school-softwere' ) . '</label><select class="ss-select2" name="type"><option value="boys">Boys</option><option value="girls">Girls</option><option value="mixed" selected>Mixed</option></select></div>';
            SS_School::field( 'warden_name', __( 'Warden Name', 'school-softwere' ), '' );
            SS_School::field( 'capacity', __( 'Total Capacity', 'school-softwere' ), 0, false, 'number' );
            echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-house-line"></i> ' . esc_html__( 'Save', 'school-softwere' ) . '</button></div></form>';
            SS_Admin_Shell::card_close();
            echo '</div><div class="ss-col" style="flex:2">';
            SS_Admin_Shell::card_open( __( 'All Hostels', 'school-softwere' ) );
            $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . SS_Helper::table( 'hostels' ) . ' WHERE school_id = %d ORDER BY ID DESC', $school_id ) );
            if ( empty( $rows ) ) {
                echo '<div class="ss-empty"><i class="ph ph-house-line"></i><h3>' . esc_html__( 'No hostels', 'school-softwere' ) . '</h3></div>';
            } else {
                echo '<div class="ss-table-wrap"><table class="ss-table"><thead><tr><th>' . esc_html__( 'Name', 'school-softwere' ) . '</th><th>' . esc_html__( 'Type', 'school-softwere' ) . '</th><th>' . esc_html__( 'Warden', 'school-softwere' ) . '</th><th>' . esc_html__( 'Capacity', 'school-softwere' ) . '</th><th></th></tr></thead><tbody>';
                foreach ( $rows as $r ) {
                    $del = wp_nonce_url( SS_Helper::admin_url( 'school-softwere-hostel' ) . '&view=delete_hostel&id=' . $r->ID, 'delete_hostel', '_ssnonce' );
                    echo '<tr><td><strong>' . esc_html( $r->label ) . '</strong></td><td>' . SS_Helper::badge( ucfirst( (string) $r->type ), 'info' ) . '</td><td>' . esc_html( $r->warden_name ) . '</td><td>' . (int) $r->capacity . '</td><td class="ss-text-right"><a class="ss-btn ss-btn-danger ss-btn-sm ss-btn-icon ss-confirm-delete" href="' . esc_url( $del ) . '"><i class="ph ph-trash"></i></a></td></tr>'; // phpcs:ignore
                }
                echo '</tbody></table></div>';
            }
            SS_Admin_Shell::card_close();
            echo '</div></div>';
        }
        SS_Admin_Shell::close();
    }
}

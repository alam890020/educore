<?php
/**
 * SS_Staff_Leaves - Staff leave management.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Staff_Leaves {

    public static function render() {
        global $wpdb;
        $school_id = SS_Helper::active_school_id();
        $tbl = SS_Helper::table( 'leaves' );

        if ( isset( $_POST['ss_action'] ) && 'save_leave' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'save_leave' ) ) {
            $wpdb->insert( $tbl, array(
                'staff_id'   => (int) ( $_POST['staff_id'] ?? 0 ),
                'school_id'  => $school_id,
                'leave_type' => sanitize_text_field( wp_unslash( $_POST['leave_type'] ?? '' ) ),
                'from_date'  => sanitize_text_field( wp_unslash( $_POST['from_date'] ?? '' ) ) ?: null,
                'to_date'    => sanitize_text_field( wp_unslash( $_POST['to_date'] ?? '' ) ) ?: null,
                'reason'     => sanitize_textarea_field( wp_unslash( $_POST['reason'] ?? '' ) ),
                'status'     => 'pending',
                'created_at' => current_time( 'mysql' ),
            ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-leaves' ) ); exit;
        }
        if ( isset( $_GET['view'] ) && in_array( $_GET['view'], array( 'approve', 'reject' ), true ) && SS_Helper::verify_nonce( 'leave_status' ) ) {
            $st = 'approve' === $_GET['view'] ? 'approved' : 'rejected';
            $wpdb->update( $tbl, array( 'status' => $st ), array( 'ID' => (int) $_GET['id'], 'school_id' => $school_id ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-leaves' ) ); exit;
        }

        SS_Admin_Shell::open( __( 'Leaves', 'school-softwere' ), 'school-softwere-leaves', array(
            array( 'label' => __( 'Leaves', 'school-softwere' ) ),
        ) );

        $staff = $wpdb->get_results( $wpdb->prepare( 'SELECT ID, CONCAT(first_name," ",last_name) as label FROM ' . SS_Helper::table( 'staff' ) . ' WHERE school_id = %d ORDER BY first_name', $school_id ) );

        echo '<div class="ss-row"><div class="ss-col" style="flex:1">';
        SS_Admin_Shell::card_open( __( 'Apply Leave', 'school-softwere' ) );
        echo '<form method="post" class="ss-form">';
        SS_Helper::nonce_field( 'save_leave' );
        echo '<input type="hidden" name="ss_action" value="save_leave">';
        SS_School::select( 'staff_id', __( 'Staff', 'school-softwere' ), 0, $staff, true );
        echo '<div class="ss-field"><label>' . esc_html__( 'Leave Type', 'school-softwere' ) . '</label><select class="ss-select2" name="leave_type"><option value="casual">Casual</option><option value="sick">Sick</option><option value="earned">Earned</option><option value="other">Other</option></select></div>';
        echo '<div class="ss-field"><label>' . esc_html__( 'From', 'school-softwere' ) . '</label><input class="ss-date" type="text" name="from_date"></div>';
        echo '<div class="ss-field"><label>' . esc_html__( 'To', 'school-softwere' ) . '</label><input class="ss-date" type="text" name="to_date"></div>';
        SS_School::textarea( 'reason', __( 'Reason', 'school-softwere' ), '' );
        echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-airplane-takeoff"></i> ' . esc_html__( 'Apply', 'school-softwere' ) . '</button></div></form>';
        SS_Admin_Shell::card_close();
        echo '</div><div class="ss-col" style="flex:2">';
        SS_Admin_Shell::card_open( __( 'All Leave Requests', 'school-softwere' ) );
        $rows = $wpdb->get_results( $wpdb->prepare(
            'SELECT l.*, CONCAT(s.first_name," ",s.last_name) staff_name FROM ' . $tbl . ' l LEFT JOIN ' . SS_Helper::table( 'staff' ) . ' s ON s.ID = l.staff_id WHERE l.school_id = %d ORDER BY l.ID DESC LIMIT 200', $school_id
        ) );
        if ( empty( $rows ) ) {
            echo '<div class="ss-empty"><i class="ph ph-airplane-takeoff"></i><h3>' . esc_html__( 'No leave requests', 'school-softwere' ) . '</h3></div>';
        } else {
            echo '<div class="ss-table-wrap"><table class="ss-datatable ss-table"><thead><tr><th>' . esc_html__( 'Staff', 'school-softwere' ) . '</th><th>' . esc_html__( 'Type', 'school-softwere' ) . '</th><th>' . esc_html__( 'From', 'school-softwere' ) . '</th><th>' . esc_html__( 'To', 'school-softwere' ) . '</th><th>' . esc_html__( 'Status', 'school-softwere' ) . '</th><th></th></tr></thead><tbody>';
            foreach ( $rows as $r ) {
                $variant = 'pending' === $r->status ? 'warning' : ( 'approved' === $r->status ? 'success' : 'danger' );
                $appr = wp_nonce_url( SS_Helper::admin_url( 'school-softwere-leaves' ) . '&view=approve&id=' . $r->ID, 'leave_status', '_ssnonce' );
                $rej  = wp_nonce_url( SS_Helper::admin_url( 'school-softwere-leaves' ) . '&view=reject&id=' . $r->ID, 'leave_status', '_ssnonce' );
                echo '<tr><td><strong>' . esc_html( $r->staff_name ) . '</strong></td><td>' . esc_html( $r->leave_type ) . '</td><td>' . esc_html( SS_Helper::format_date( $r->from_date ) ) . '</td><td>' . esc_html( SS_Helper::format_date( $r->to_date ) ) . '</td><td>' . SS_Helper::badge( ucfirst( (string) $r->status ), $variant ) . '</td><td class="ss-text-right">' . ( 'pending' === $r->status ? '<a class="ss-btn ss-btn-success ss-btn-sm ss-btn-icon" href="' . esc_url( $appr ) . '" title="Approve"><i class="ph ph-check"></i></a> <a class="ss-btn ss-btn-danger ss-btn-sm ss-btn-icon" href="' . esc_url( $rej ) . '" title="Reject"><i class="ph ph-x"></i></a>' : '-' ) . '</td></tr>'; // phpcs:ignore
            }
            echo '</tbody></table></div>';
        }
        SS_Admin_Shell::card_close();
        echo '</div></div>';
        SS_Admin_Shell::close();
    }
}

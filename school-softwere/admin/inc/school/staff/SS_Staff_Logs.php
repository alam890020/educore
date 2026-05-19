<?php
/**
 * SS_Staff_Logs - Activity logs viewer.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Staff_Logs {

    public static function render() {
        global $wpdb;
        $school_id = SS_Helper::active_school_id();
        SS_Admin_Shell::open( __( 'Activity Logs', 'school-softwere' ), 'school-softwere-logs', array(
            array( 'label' => __( 'Logs', 'school-softwere' ) ),
        ) );
        SS_Admin_Shell::card_open( __( 'Recent Activity', 'school-softwere' ) );
        $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . SS_Helper::table( 'logs' ) . ' WHERE school_id = %d OR school_id IS NULL ORDER BY ID DESC LIMIT 500', $school_id ) );
        if ( empty( $rows ) ) {
            echo '<div class="ss-empty"><i class="ph ph-clipboard-text"></i><h3>' . esc_html__( 'No logs yet', 'school-softwere' ) . '</h3></div>';
        } else {
            echo '<div class="ss-table-wrap"><table class="ss-datatable ss-table"><thead><tr><th>' . esc_html__( 'Date', 'school-softwere' ) . '</th><th>' . esc_html__( 'User', 'school-softwere' ) . '</th><th>' . esc_html__( 'Action', 'school-softwere' ) . '</th><th>' . esc_html__( 'Details', 'school-softwere' ) . '</th></tr></thead><tbody>';
            foreach ( $rows as $r ) {
                $u = $r->user_id ? get_user_by( 'id', $r->user_id ) : null;
                echo '<tr><td>' . esc_html( SS_Helper::format_date( $r->created_at ) ) . '</td><td>' . esc_html( $u ? $u->display_name : '-' ) . '</td><td>' . esc_html( $r->action ) . '</td><td><small class="ss-text-muted">' . esc_html( wp_trim_words( $r->details, 20 ) ) . '</small></td></tr>';
            }
            echo '</tbody></table></div>';
        }
        SS_Admin_Shell::card_close();
        SS_Admin_Shell::close();
    }
}

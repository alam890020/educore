<?php
/**
 * SS_Staff_Inquiries - Admission inquiries.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Staff_Inquiries {

    public static function render() {
        global $wpdb;
        $school_id = SS_Helper::active_school_id();
        $tbl = SS_Helper::table( 'inquiries' );

        if ( isset( $_POST['ss_action'] ) && 'save_inquiry' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'save_inquiry' ) ) {
            $wpdb->insert( $tbl, array(
                'school_id'  => $school_id,
                'name'       => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
                'email'      => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
                'phone'      => sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ),
                'message'    => sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) ),
                'source'     => sanitize_text_field( wp_unslash( $_POST['source'] ?? '' ) ),
                'status'     => 'open',
                'created_at' => current_time( 'mysql' ),
            ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-inquiries' ) ); exit;
        }
        if ( 'delete' === ( $_GET['view'] ?? '' ) && SS_Helper::verify_nonce( 'delete_inquiry' ) ) {
            $wpdb->delete( $tbl, array( 'ID' => (int) $_GET['id'], 'school_id' => $school_id ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-inquiries' ) ); exit;
        }
        if ( isset( $_GET['view'] ) && in_array( $_GET['view'], array( 'mark_open', 'mark_contacted', 'mark_admitted', 'mark_closed' ), true ) && SS_Helper::verify_nonce( 'inquiry_status' ) ) {
            $map = array( 'mark_open' => 'open', 'mark_contacted' => 'contacted', 'mark_admitted' => 'admitted', 'mark_closed' => 'closed' );
            $wpdb->update( $tbl, array( 'status' => $map[ $_GET['view'] ] ), array( 'ID' => (int) $_GET['id'], 'school_id' => $school_id ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-inquiries' ) ); exit;
        }

        SS_Admin_Shell::open( __( 'Inquiries', 'school-softwere' ), 'school-softwere-inquiries', array(
            array( 'label' => __( 'Inquiries', 'school-softwere' ) ),
        ) );

        echo '<div class="ss-row"><div class="ss-col" style="flex:1">';
        SS_Admin_Shell::card_open( __( 'Add Inquiry', 'school-softwere' ) );
        echo '<form method="post" class="ss-form">';
        SS_Helper::nonce_field( 'save_inquiry' );
        echo '<input type="hidden" name="ss_action" value="save_inquiry">';
        SS_School::field( 'name', __( 'Name', 'school-softwere' ), '', true );
        SS_School::field( 'email', __( 'Email', 'school-softwere' ), '', false, 'email' );
        SS_School::field( 'phone', __( 'Phone', 'school-softwere' ), '' );
        SS_School::field( 'source', __( 'Source', 'school-softwere' ), '' );
        SS_School::textarea( 'message', __( 'Message', 'school-softwere' ), '' );
        echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-question"></i> ' . esc_html__( 'Save', 'school-softwere' ) . '</button></div></form>';
        SS_Admin_Shell::card_close();
        echo '</div><div class="ss-col" style="flex:2">';
        SS_Admin_Shell::card_open( __( 'All Inquiries', 'school-softwere' ) );
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl} WHERE school_id = %d ORDER BY ID DESC LIMIT 200", $school_id ) );
        if ( empty( $rows ) ) {
            echo '<div class="ss-empty"><i class="ph ph-question"></i><h3>' . esc_html__( 'No inquiries', 'school-softwere' ) . '</h3></div>';
        } else {
            echo '<div class="ss-table-wrap"><table class="ss-datatable ss-table"><thead><tr><th>' . esc_html__( 'Name', 'school-softwere' ) . '</th><th>' . esc_html__( 'Contact', 'school-softwere' ) . '</th><th>' . esc_html__( 'Status', 'school-softwere' ) . '</th><th>' . esc_html__( 'Date', 'school-softwere' ) . '</th><th></th></tr></thead><tbody>';
            foreach ( $rows as $r ) {
                $del = wp_nonce_url( SS_Helper::admin_url( 'school-softwere-inquiries' ) . '&view=delete&id=' . $r->ID, 'delete_inquiry', '_ssnonce' );
                $cnt = wp_nonce_url( SS_Helper::admin_url( 'school-softwere-inquiries' ) . '&view=mark_contacted&id=' . $r->ID, 'inquiry_status', '_ssnonce' );
                $adm = wp_nonce_url( SS_Helper::admin_url( 'school-softwere-inquiries' ) . '&view=mark_admitted&id=' . $r->ID, 'inquiry_status', '_ssnonce' );
                $variant = 'open' === $r->status ? 'warning' : ( 'admitted' === $r->status ? 'success' : 'info' );
                echo '<tr><td><strong>' . esc_html( $r->name ) . '</strong><br><small class="ss-text-muted">' . esc_html( wp_trim_words( $r->message, 12 ) ) . '</small></td><td>' . esc_html( $r->email ) . '<br><small>' . esc_html( $r->phone ) . '</small></td><td>' . SS_Helper::badge( ucfirst( (string) $r->status ), $variant ) . '</td><td>' . esc_html( SS_Helper::format_date( $r->created_at ) ) . '</td><td class="ss-text-right"><div class="ss-actions"><a class="ss-btn ss-btn-secondary ss-btn-sm" href="' . esc_url( $cnt ) . '">' . esc_html__( 'Contacted', 'school-softwere' ) . '</a> <a class="ss-btn ss-btn-success ss-btn-sm" href="' . esc_url( $adm ) . '">' . esc_html__( 'Admit', 'school-softwere' ) . '</a> <a class="ss-btn ss-btn-danger ss-btn-sm ss-btn-icon ss-confirm-delete" href="' . esc_url( $del ) . '"><i class="ph ph-trash"></i></a></div></td></tr>'; // phpcs:ignore
            }
            echo '</tbody></table></div>';
        }
        SS_Admin_Shell::card_close();
        echo '</div></div>';
        SS_Admin_Shell::close();
    }
}

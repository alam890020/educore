<?php
/**
 * SS_Staff_Tickets - Support tickets.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Staff_Tickets {

    public static function render() {
        global $wpdb;
        $school_id = SS_Helper::active_school_id();

        if ( isset( $_POST['ss_action'] ) && 'save_ticket' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'save_ticket' ) ) {
            $wpdb->insert( SS_Helper::table( 'tickets' ), array(
                'school_id'  => $school_id,
                'user_id'    => get_current_user_id(),
                'subject'    => sanitize_text_field( wp_unslash( $_POST['subject'] ?? '' ) ),
                'status'     => 'open',
                'priority'   => sanitize_key( wp_unslash( $_POST['priority'] ?? 'normal' ) ),
                'created_at' => current_time( 'mysql' ),
            ) );
            $tid = (int) $wpdb->insert_id;
            $msg = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );
            if ( $msg ) {
                SS_M_Tickets::add_history( $tid, get_current_user_id(), $msg );
            }
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-tickets' ) ); exit;
        }
        if ( isset( $_POST['ss_action'] ) && 'reply_ticket' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'reply_ticket' ) ) {
            $tid = (int) ( $_POST['ticket_id'] ?? 0 );
            $msg = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );
            if ( $tid && $msg ) {
                SS_M_Tickets::add_history( $tid, get_current_user_id(), $msg );
                if ( ! empty( $_POST['status'] ) ) {
                    $wpdb->update( SS_Helper::table( 'tickets' ), array( 'status' => sanitize_key( $_POST['status'] ) ), array( 'ID' => $tid ) );
                }
            }
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-tickets' ) . '&view=detail&id=' . $tid ); exit;
        }

        $view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'list';
        SS_Admin_Shell::open( __( 'Support Tickets', 'school-softwere' ), 'school-softwere-tickets', array(
            array( 'label' => __( 'Tickets', 'school-softwere' ) ),
        ) );

        if ( 'detail' === $view && ! empty( $_GET['id'] ) ) {
            $tid = (int) $_GET['id'];
            $t   = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . SS_Helper::table( 'tickets' ) . ' WHERE ID = %d AND school_id = %d', $tid, $school_id ) );
            if ( ! $t ) { wp_die( esc_html__( 'Ticket not found', 'school-softwere' ) ); }

            SS_Admin_Shell::card_open( __( 'Ticket', 'school-softwere' ) . ' #' . $t->ID . ' - ' . $t->subject,
                SS_Helper::badge( ucfirst( (string) $t->status ), 'open' === $t->status ? 'warning' : ( 'closed' === $t->status ? 'success' : 'info' ) ) . ' ' . SS_Helper::badge( ucfirst( (string) $t->priority ), 'info' )
            );
            $hist = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . SS_Helper::table( 'ticket_history' ) . ' WHERE ticket_id = %d ORDER BY ID ASC', $tid ) );
            echo '<ul class="ss-feed">';
            foreach ( $hist as $h ) {
                $u = get_user_by( 'id', $h->user_id );
                echo '<li><div class="ss-feed-icon"><i class="ph ph-chat-circle"></i></div><div class="ss-feed-meta"><strong>' . esc_html( $u ? $u->display_name : 'User' ) . '</strong><small>' . esc_html( SS_Helper::format_date( $h->created_at ) ) . '</small><div style="margin-top:6px">' . esc_html( $h->message ) . '</div></div></li>';
            }
            echo '</ul>';
            echo '<form method="post" class="ss-form" style="margin-top:18px">';
            SS_Helper::nonce_field( 'reply_ticket' );
            echo '<input type="hidden" name="ss_action" value="reply_ticket">';
            echo '<input type="hidden" name="ticket_id" value="' . (int) $tid . '">';
            SS_School::textarea( 'message', __( 'Reply', 'school-softwere' ), '' );
            echo '<div class="ss-field"><label>' . esc_html__( 'Update Status', 'school-softwere' ) . '</label><select name="status" class="ss-select2"><option value="">-- ' . esc_html__( 'Keep current', 'school-softwere' ) . ' --</option><option value="open">Open</option><option value="in_progress">In Progress</option><option value="closed">Closed</option></select></div>';
            echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-paper-plane-tilt"></i> ' . esc_html__( 'Send Reply', 'school-softwere' ) . '</button></div>';
            echo '</form>';
            SS_Admin_Shell::card_close();
        } else {
            echo '<div class="ss-row"><div class="ss-col" style="flex:1">';
            SS_Admin_Shell::card_open( __( 'New Ticket', 'school-softwere' ) );
            echo '<form method="post" class="ss-form">';
            SS_Helper::nonce_field( 'save_ticket' );
            echo '<input type="hidden" name="ss_action" value="save_ticket">';
            SS_School::field( 'subject', __( 'Subject', 'school-softwere' ), '', true );
            echo '<div class="ss-field"><label>' . esc_html__( 'Priority', 'school-softwere' ) . '</label><select class="ss-select2" name="priority"><option value="low">Low</option><option value="normal" selected>Normal</option><option value="high">High</option></select></div>';
            SS_School::textarea( 'message', __( 'Message', 'school-softwere' ), '', true );
            echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-lifebuoy"></i> ' . esc_html__( 'Submit Ticket', 'school-softwere' ) . '</button></div></form>';
            SS_Admin_Shell::card_close();
            echo '</div><div class="ss-col" style="flex:2">';
            SS_Admin_Shell::card_open( __( 'All Tickets', 'school-softwere' ) );
            $rows = SS_M_Tickets::list_for_school( $school_id );
            if ( empty( $rows ) ) {
                echo '<div class="ss-empty"><i class="ph ph-lifebuoy"></i><h3>' . esc_html__( 'No tickets', 'school-softwere' ) . '</h3></div>';
            } else {
                echo '<div class="ss-table-wrap"><table class="ss-datatable ss-table"><thead><tr><th>#</th><th>' . esc_html__( 'Subject', 'school-softwere' ) . '</th><th>' . esc_html__( 'Priority', 'school-softwere' ) . '</th><th>' . esc_html__( 'Status', 'school-softwere' ) . '</th><th>' . esc_html__( 'Date', 'school-softwere' ) . '</th><th></th></tr></thead><tbody>';
                foreach ( $rows as $r ) {
                    $url = SS_Helper::admin_url( 'school-softwere-tickets' ) . '&view=detail&id=' . $r->ID;
                    echo '<tr><td>' . (int) $r->ID . '</td><td><strong>' . esc_html( $r->subject ) . '</strong></td><td>' . SS_Helper::badge( ucfirst( (string) $r->priority ), 'info' ) . '</td><td>' . SS_Helper::badge( ucfirst( (string) $r->status ), 'open' === $r->status ? 'warning' : ( 'closed' === $r->status ? 'success' : 'info' ) ) . '</td><td>' . esc_html( SS_Helper::format_date( $r->created_at ) ) . '</td><td class="ss-text-right"><a class="ss-btn ss-btn-secondary ss-btn-sm" href="' . esc_url( $url ) . '">' . esc_html__( 'View', 'school-softwere' ) . '</a></td></tr>'; // phpcs:ignore
                }
                echo '</tbody></table></div>';
            }
            SS_Admin_Shell::card_close();
            echo '</div></div>';
        }
        SS_Admin_Shell::close();
    }
}

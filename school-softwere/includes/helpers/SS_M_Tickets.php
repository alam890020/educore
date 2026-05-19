<?php
/**
 * SS_M_Tickets - Ticket helper queries.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_M_Tickets {

    public static function list_for_school( $school_id ) {
        global $wpdb;
        $t = SS_Helper::table( 'tickets' );
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} WHERE school_id = %d ORDER BY ID DESC", (int) $school_id ) );
    }

    public static function add_history( $ticket_id, $user_id, $message, $attachment = '' ) {
        global $wpdb;
        $t = SS_Helper::table( 'ticket_history' );
        return $wpdb->insert( $t, array(
            'ticket_id'  => (int) $ticket_id,
            'user_id'    => (int) $user_id,
            'message'    => wp_kses_post( $message ),
            'attachment' => esc_url_raw( $attachment ),
            'created_at' => current_time( 'mysql' ),
        ) );
    }
}

<?php
/**
 * SS_Staff_Notices - Notices & Events.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Staff_Notices {

    public static function render() {
        global $wpdb;
        $school_id = SS_Helper::active_school_id();
        $tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'notices';

        if ( isset( $_POST['ss_action'] ) && 'save_notice' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'save_notice' ) ) {
            $wpdb->insert( SS_Helper::table( 'notices' ), array(
                'school_id'   => $school_id,
                'title'       => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
                'description' => wp_kses_post( wp_unslash( $_POST['description'] ?? '' ) ),
                'date'        => sanitize_text_field( wp_unslash( $_POST['date'] ?? current_time( 'Y-m-d' ) ) ),
                'attachment'  => esc_url_raw( wp_unslash( $_POST['attachment'] ?? '' ) ),
                'created_at'  => current_time( 'mysql' ),
            ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-notices' ) ); exit;
        }
        if ( 'delete_notice' === ( $_GET['view'] ?? '' ) && SS_Helper::verify_nonce( 'delete_notice' ) ) {
            $wpdb->delete( SS_Helper::table( 'notices' ), array( 'ID' => (int) $_GET['id'], 'school_id' => $school_id ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-notices' ) ); exit;
        }
        if ( isset( $_POST['ss_action'] ) && 'save_event' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'save_event' ) ) {
            $wpdb->insert( SS_Helper::table( 'events' ), array(
                'school_id'   => $school_id,
                'title'       => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
                'description' => wp_kses_post( wp_unslash( $_POST['description'] ?? '' ) ),
                'start_date'  => sanitize_text_field( wp_unslash( $_POST['start_date'] ?? '' ) ) ?: null,
                'end_date'    => sanitize_text_field( wp_unslash( $_POST['end_date'] ?? '' ) ) ?: null,
                'venue'       => sanitize_text_field( wp_unslash( $_POST['venue'] ?? '' ) ),
                'attachment'  => esc_url_raw( wp_unslash( $_POST['attachment'] ?? '' ) ),
                'created_at'  => current_time( 'mysql' ),
            ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-notices' ) . '&tab=events' ); exit;
        }
        if ( 'delete_event' === ( $_GET['view'] ?? '' ) && SS_Helper::verify_nonce( 'delete_event' ) ) {
            $wpdb->delete( SS_Helper::table( 'events' ), array( 'ID' => (int) $_GET['id'], 'school_id' => $school_id ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-notices' ) . '&tab=events' ); exit;
        }

        SS_Admin_Shell::open( __( 'Notices & Events', 'school-softwere' ), 'school-softwere-notices', array(
            array( 'label' => __( 'Notices & Events', 'school-softwere' ) ),
        ) );

        echo '<div class="ss-tabs" style="margin-bottom:16px;">';
        $base = SS_Helper::admin_url( 'school-softwere-notices' );
        echo '<a class="ss-tab ' . ( 'notices' === $tab ? 'active' : '' ) . '" href="' . esc_url( $base ) . '">' . esc_html__( 'Notices', 'school-softwere' ) . '</a>';
        echo '<a class="ss-tab ' . ( 'events'  === $tab ? 'active' : '' ) . '" href="' . esc_url( $base . '&tab=events' ) . '">' . esc_html__( 'Events', 'school-softwere' ) . '</a>';
        echo '</div>';

        if ( 'events' === $tab ) {
            echo '<div class="ss-row"><div class="ss-col" style="flex:1">';
            SS_Admin_Shell::card_open( __( 'Add Event', 'school-softwere' ) );
            echo '<form method="post" class="ss-form">';
            SS_Helper::nonce_field( 'save_event' );
            echo '<input type="hidden" name="ss_action" value="save_event">';
            SS_School::field( 'title', __( 'Title', 'school-softwere' ), '', true );
            SS_School::textarea( 'description', __( 'Description', 'school-softwere' ), '' );
            echo '<div class="ss-field"><label>' . esc_html__( 'Start', 'school-softwere' ) . '</label><input class="ss-datetime" type="text" name="start_date"></div>';
            echo '<div class="ss-field"><label>' . esc_html__( 'End', 'school-softwere' ) . '</label><input class="ss-datetime" type="text" name="end_date"></div>';
            SS_School::field( 'venue', __( 'Venue', 'school-softwere' ), '' );
            SS_School::field( 'attachment', __( 'Attachment URL', 'school-softwere' ), '' );
            echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-calendar"></i> ' . esc_html__( 'Save', 'school-softwere' ) . '</button></div></form>';
            SS_Admin_Shell::card_close();
            echo '</div><div class="ss-col" style="flex:2">';
            SS_Admin_Shell::card_open( __( 'All Events', 'school-softwere' ) );
            $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . SS_Helper::table( 'events' ) . ' WHERE school_id = %d ORDER BY start_date DESC LIMIT 200', $school_id ) );
            if ( empty( $rows ) ) {
                echo '<div class="ss-empty"><i class="ph ph-calendar"></i><h3>' . esc_html__( 'No events', 'school-softwere' ) . '</h3></div>';
            } else {
                echo '<div class="ss-table-wrap"><table class="ss-datatable ss-table"><thead><tr><th>' . esc_html__( 'Title', 'school-softwere' ) . '</th><th>' . esc_html__( 'Start', 'school-softwere' ) . '</th><th>' . esc_html__( 'Venue', 'school-softwere' ) . '</th><th></th></tr></thead><tbody>';
                foreach ( $rows as $r ) {
                    $del = wp_nonce_url( SS_Helper::admin_url( 'school-softwere-notices' ) . '&tab=events&view=delete_event&id=' . $r->ID, 'delete_event', '_ssnonce' );
                    echo '<tr><td><strong>' . esc_html( $r->title ) . '</strong></td><td>' . esc_html( SS_Helper::format_date( $r->start_date ) ) . '</td><td>' . esc_html( $r->venue ) . '</td><td class="ss-text-right"><a class="ss-btn ss-btn-danger ss-btn-sm ss-btn-icon ss-confirm-delete" href="' . esc_url( $del ) . '"><i class="ph ph-trash"></i></a></td></tr>';
                }
                echo '</tbody></table></div>';
            }
            SS_Admin_Shell::card_close();
            echo '</div></div>';
        } else {
            echo '<div class="ss-row"><div class="ss-col" style="flex:1">';
            SS_Admin_Shell::card_open( __( 'Post Notice', 'school-softwere' ) );
            echo '<form method="post" class="ss-form">';
            SS_Helper::nonce_field( 'save_notice' );
            echo '<input type="hidden" name="ss_action" value="save_notice">';
            SS_School::field( 'title', __( 'Title', 'school-softwere' ), '', true );
            SS_School::textarea( 'description', __( 'Description', 'school-softwere' ), '' );
            echo '<div class="ss-field"><label>' . esc_html__( 'Date', 'school-softwere' ) . '</label><input class="ss-date" type="text" name="date" value="' . esc_attr( current_time( 'Y-m-d' ) ) . '"></div>';
            SS_School::field( 'attachment', __( 'Attachment URL', 'school-softwere' ), '' );
            echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-megaphone"></i> ' . esc_html__( 'Post Notice', 'school-softwere' ) . '</button></div></form>';
            SS_Admin_Shell::card_close();
            echo '</div><div class="ss-col" style="flex:2">';
            SS_Admin_Shell::card_open( __( 'All Notices', 'school-softwere' ) );
            $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . SS_Helper::table( 'notices' ) . ' WHERE school_id = %d ORDER BY date DESC LIMIT 200', $school_id ) );
            if ( empty( $rows ) ) {
                echo '<div class="ss-empty"><i class="ph ph-megaphone"></i><h3>' . esc_html__( 'No notices', 'school-softwere' ) . '</h3></div>';
            } else {
                echo '<div class="ss-table-wrap"><table class="ss-datatable ss-table"><thead><tr><th>' . esc_html__( 'Date', 'school-softwere' ) . '</th><th>' . esc_html__( 'Title', 'school-softwere' ) . '</th><th></th></tr></thead><tbody>';
                foreach ( $rows as $r ) {
                    $del = wp_nonce_url( SS_Helper::admin_url( 'school-softwere-notices' ) . '&view=delete_notice&id=' . $r->ID, 'delete_notice', '_ssnonce' );
                    echo '<tr><td>' . esc_html( SS_Helper::format_date( $r->date ) ) . '</td><td><strong>' . esc_html( $r->title ) . '</strong><br><small class="ss-text-muted">' . esc_html( wp_trim_words( wp_strip_all_tags( $r->description ), 18 ) ) . '</small></td><td class="ss-text-right"><a class="ss-btn ss-btn-danger ss-btn-sm ss-btn-icon ss-confirm-delete" href="' . esc_url( $del ) . '"><i class="ph ph-trash"></i></a></td></tr>';
                }
                echo '</tbody></table></div>';
            }
            SS_Admin_Shell::card_close();
            echo '</div></div>';
        }
        SS_Admin_Shell::close();
    }
}

<?php
/**
 * SS_Noticeboard - Public notices & events shortcodes.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Noticeboard {

    public static function shortcode( $atts = array() ) {
        $atts = shortcode_atts( array(
            'limit'     => 10,
            'school_id' => 0,
        ), $atts, 'ss_noticeboard' );

        global $wpdb;
        $school_id = (int) $atts['school_id'] ?: SS_Helper::active_school_id();
        $rows = $wpdb->get_results( $wpdb->prepare(
            'SELECT * FROM ' . SS_Helper::table( 'notices' ) . ' WHERE school_id = %d ORDER BY date DESC LIMIT %d',
            $school_id, max( 1, (int) $atts['limit'] )
        ) );

        ob_start();
        echo '<div class="ss-public"><div class="ss-public-card"><h3>' . esc_html__( 'Notices', 'school-softwere' ) . '</h3>';
        if ( empty( $rows ) ) {
            echo '<p class="meta">' . esc_html__( 'No notices yet.', 'school-softwere' ) . '</p>';
        } else {
            echo '<ul class="ss-public-list">';
            foreach ( $rows as $r ) {
                echo '<li><div><strong>' . esc_html( $r->title ) . '</strong><div class="meta">' . esc_html( SS_Helper::format_date( $r->date ) ) . '</div><div>' . esc_html( wp_trim_words( wp_strip_all_tags( $r->description ), 24 ) ) . '</div></div></li>';
            }
            echo '</ul>';
        }
        echo '</div></div>';
        return ob_get_clean();
    }

    public static function events_shortcode( $atts = array() ) {
        $atts = shortcode_atts( array(
            'limit'     => 10,
            'school_id' => 0,
        ), $atts, 'ss_events' );

        global $wpdb;
        $school_id = (int) $atts['school_id'] ?: SS_Helper::active_school_id();
        $rows = $wpdb->get_results( $wpdb->prepare(
            'SELECT * FROM ' . SS_Helper::table( 'events' ) . ' WHERE school_id = %d AND start_date >= %s ORDER BY start_date ASC LIMIT %d',
            $school_id, current_time( 'mysql' ), max( 1, (int) $atts['limit'] )
        ) );

        ob_start();
        echo '<div class="ss-public"><div class="ss-public-card"><h3>' . esc_html__( 'Upcoming Events', 'school-softwere' ) . '</h3>';
        if ( empty( $rows ) ) {
            echo '<p class="meta">' . esc_html__( 'No upcoming events.', 'school-softwere' ) . '</p>';
        } else {
            echo '<ul class="ss-public-list">';
            foreach ( $rows as $r ) {
                echo '<li><div><strong>' . esc_html( $r->title ) . '</strong><div class="meta">' . esc_html( SS_Helper::format_date( $r->start_date ) . ( $r->venue ? ' &bull; ' . $r->venue : '' ) ) . '</div></div></li>';
            }
            echo '</ul>';
        }
        echo '</div></div>';
        return ob_get_clean();
    }
}

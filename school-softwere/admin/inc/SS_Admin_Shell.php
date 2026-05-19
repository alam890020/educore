<?php
/**
 * SS_Admin_Shell - The visual chrome wrapping every plugin admin page.
 * Renders the sidebar, top header, and content frame.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Admin_Shell {

    /**
     * Open the shell. Call at the start of every page render.
     *
     * @param string $page_title
     * @param string $current_slug Active menu slug.
     * @param array  $breadcrumbs  [ ['label'=>..,'url'=>..], ... ]
     */
    public static function open( $page_title, $current_slug = '', $breadcrumbs = array() ) {
        $school_id  = SS_Helper::active_school_id();
        $school     = $school_id ? self::get_school( $school_id ) : null;
        $session_id = SS_Helper::active_session_id( $school_id );
        $session    = $session_id ? self::get_session( $session_id ) : null;

        $user        = wp_get_current_user();
        $stats       = self::quick_stats( $school_id );
        $unread_n    = self::unread_notices_count( $school_id );

        echo '<div class="ss-shell">';

        // Sidebar.
        echo '<aside class="ss-sidebar" id="ss-sidebar">';
        echo '<div class="ss-brand">';
        echo '<span class="ss-brand-icon">' . SS_Helper::logo_svg() . '</span>';
        echo '<span class="ss-brand-title">' . esc_html__( 'School Softwere', 'school-softwere' ) . '</span>';
        echo '</div>';

        echo '<nav class="ss-nav">';
        foreach ( SS_Menu::sidebar_groups() as $group ) {
            echo '<div class="ss-nav-group">';
            echo '<div class="ss-nav-group-label"><i class="ph ' . esc_attr( $group['icon'] ) . '"></i> ' . esc_html( $group['label'] ) . '</div>';
            echo '<ul class="ss-nav-list">';
            foreach ( $group['items'] as $item ) {
                $active = ( $item['slug'] === $current_slug ) ? 'active' : '';
                echo '<li class="ss-nav-item ' . esc_attr( $active ) . '">';
                echo '<a href="' . esc_url( SS_Helper::admin_url( $item['slug'] ) ) . '">';
                echo '<i class="ph ' . esc_attr( $item['icon'] ) . '"></i>';
                echo '<span>' . esc_html( $item['label'] ) . '</span>';
                echo '</a></li>';
            }
            echo '</ul></div>';
        }
        echo '</nav>';

        echo '<div class="ss-sidebar-footer">';
        echo '<small>v' . esc_html( SS_VERSION ) . '</small>';
        echo '</div>';
        echo '</aside>';

        // Main column.
        echo '<div class="ss-main">';

        // Header.
        echo '<header class="ss-header">';
        echo '<div class="ss-header-left">';
        echo '<button class="ss-toggle-sidebar" type="button" aria-label="Toggle"><i class="ph ph-list"></i></button>';
        if ( $school ) {
            echo '<div class="ss-school-pill"><i class="ph-fill ph-buildings"></i> ' . esc_html( $school->label ) . '</div>';
        }
        if ( $session ) {
            echo '<div class="ss-session-pill"><i class="ph ph-calendar"></i> ' . esc_html( $session->label ) . '</div>';
        }
        echo '</div>';

        echo '<div class="ss-header-right">';
        echo '<div class="ss-quick-stat"><i class="ph ph-graduation-cap"></i> <strong>' . (int) $stats['students'] . '</strong> ' . esc_html__( 'Students', 'school-softwere' ) . '</div>';
        echo '<div class="ss-quick-stat"><i class="ph ph-users-three"></i> <strong>' . (int) $stats['staff'] . '</strong> ' . esc_html__( 'Staff', 'school-softwere' ) . '</div>';
        echo '<a class="ss-bell" href="' . esc_url( SS_Helper::admin_url( 'school-softwere-notices' ) ) . '" title="' . esc_attr__( 'Notices', 'school-softwere' ) . '"><i class="ph ph-bell"></i>';
        if ( $unread_n > 0 ) {
            echo '<span class="ss-bell-dot">' . (int) $unread_n . '</span>';
        }
        echo '</a>';
        echo '<div class="ss-user">';
        echo '<div class="ss-avatar">' . esc_html( strtoupper( substr( $user->display_name, 0, 1 ) ) ) . '</div>';
        echo '<div class="ss-user-meta"><strong>' . esc_html( $user->display_name ) . '</strong>';
        echo '<small>' . esc_html( current_user_can( SS_CAP_SUPER ) ? __( 'Super Admin', 'school-softwere' ) : __( 'School Staff', 'school-softwere' ) ) . '</small></div>';
        echo '</div>';
        echo '</div>';
        echo '</header>';

        // Page header bar.
        echo '<div class="ss-page-bar">';
        echo '<div class="ss-page-title-wrap">';
        echo '<h1 class="ss-page-title">' . esc_html( $page_title ) . '</h1>';
        if ( ! empty( $breadcrumbs ) ) {
            echo '<nav class="ss-breadcrumbs"><ol>';
            foreach ( $breadcrumbs as $i => $bc ) {
                $is_last = ( $i === count( $breadcrumbs ) - 1 );
                echo '<li>';
                if ( ! $is_last && ! empty( $bc['url'] ) ) {
                    echo '<a href="' . esc_url( $bc['url'] ) . '">' . esc_html( $bc['label'] ) . '</a>';
                } else {
                    echo '<span>' . esc_html( $bc['label'] ) . '</span>';
                }
                if ( ! $is_last ) {
                    echo '<i class="ph ph-caret-right"></i>';
                }
                echo '</li>';
            }
            echo '</ol></nav>';
        }
        echo '</div>';
        echo '<div class="ss-page-actions" id="ss-page-actions"></div>';
        echo '</div>';

        // Notices container (transient ?ss_notice handler).
        if ( ! empty( $_GET['ss_notice'] ) ) {
            $type = sanitize_key( wp_unslash( $_GET['ss_notice_type'] ?? 'info' ) );
            $msg  = sanitize_text_field( wp_unslash( $_GET['ss_notice'] ) );
            SS_Helper::notice( $msg, $type );
        }

        echo '<div class="ss-content">';
    }

    /**
     * Close the shell.
     */
    public static function close() {
        echo '</div>'; // .ss-content
        echo '</div>'; // .ss-main
        echo '</div>'; // .ss-shell
    }

    /**
     * Page section card start.
     *
     * @param string $title
     * @param string $action_html
     */
    public static function card_open( $title = '', $action_html = '' ) {
        echo '<section class="ss-card">';
        if ( $title || $action_html ) {
            echo '<header class="ss-card-head">';
            if ( $title ) {
                echo '<h2 class="ss-card-title">' . esc_html( $title ) . '</h2>';
            }
            if ( $action_html ) {
                echo '<div class="ss-card-actions">' . $action_html . '</div>'; // phpcs:ignore - actions are safe HTML composed by callers.
            }
            echo '</header>';
        }
        echo '<div class="ss-card-body">';
    }

    public static function card_close() {
        echo '</div></section>';
    }

    /**
     * Helper: get school row.
     *
     * @param int $school_id
     * @return object|null
     */
    private static function get_school( $school_id ) {
        global $wpdb;
        $t = SS_Helper::table( 'schools' );
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE ID = %d", (int) $school_id ) );
    }

    /**
     * Helper: get session row.
     *
     * @param int $session_id
     * @return object|null
     */
    private static function get_session( $session_id ) {
        global $wpdb;
        $t = SS_Helper::table( 'sessions' );
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE ID = %d", (int) $session_id ) );
    }

    /**
     * Quick header stats.
     *
     * @param int $school_id
     * @return array
     */
    private static function quick_stats( $school_id ) {
        global $wpdb;
        $st = SS_Helper::table( 'student_records' );
        $sf = SS_Helper::table( 'staff' );
        if ( ! $school_id ) {
            return array( 'students' => 0, 'staff' => 0 );
        }
        return array(
            'students' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$st} WHERE school_id = %d AND is_active = 1", $school_id ) ),
            'staff'    => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$sf} WHERE school_id = %d AND is_active = 1", $school_id ) ),
        );
    }

    /**
     * Count notices in last 7 days.
     *
     * @param int $school_id
     * @return int
     */
    private static function unread_notices_count( $school_id ) {
        global $wpdb;
        if ( ! $school_id ) {
            return 0;
        }
        $t = SS_Helper::table( 'notices' );
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$t} WHERE school_id = %d AND created_at >= %s",
            $school_id,
            gmdate( 'Y-m-d 00:00:00', strtotime( '-7 days' ) )
        ) );
    }
}

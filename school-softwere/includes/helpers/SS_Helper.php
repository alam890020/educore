<?php
/**
 * SS_Helper - Common utility methods.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Helper {

    /**
     * Resolve full DB table name with WP prefix.
     *
     * @param string $name
     * @return string
     */
    public static function table( $name ) {
        global $wpdb;
        return $wpdb->prefix . SS_DB_PREFIX . ltrim( $name, '_' );
    }

    /**
     * Format a date using plugin date format setting.
     *
     * @param string $date
     * @return string
     */
    public static function format_date( $date ) {
        if ( empty( $date ) || '0000-00-00' === $date ) {
            return '';
        }
        $ts = strtotime( $date );
        if ( ! $ts ) {
            return $date;
        }
        return date_i18n( SS_Config::get( 'date_format', 'd-m-Y' ), $ts );
    }

    /**
     * Format currency.
     *
     * @param float|int|string $amount
     * @return string
     */
    public static function format_money( $amount ) {
        $symbol = SS_Config::get( 'currency_symbol', '$' );
        return $symbol . number_format( (float) $amount, 2 );
    }

    /**
     * Echo a status badge HTML.
     *
     * @param string $label
     * @param string $type success|warning|danger|info|primary
     * @return string
     */
    public static function badge( $label, $type = 'primary' ) {
        $type = in_array( $type, array( 'success', 'warning', 'danger', 'info', 'primary', 'muted' ), true ) ? $type : 'primary';
        return '<span class="ss-badge ss-badge-' . esc_attr( $type ) . '">' . esc_html( $label ) . '</span>';
    }

    /**
     * Verify nonce coming from $_REQUEST.
     *
     * @param string $action
     * @param string $field
     * @return bool
     */
    public static function verify_nonce( $action, $field = '_ssnonce' ) {
        return isset( $_REQUEST[ $field ] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST[ $field ] ) ), $action );
    }

    /**
     * Render a hidden nonce field for forms.
     *
     * @param string $action
     */
    public static function nonce_field( $action ) {
        wp_nonce_field( $action, '_ssnonce' );
    }

    /**
     * Get current logged in WP user ID.
     *
     * @return int
     */
    public static function current_user_id() {
        return get_current_user_id();
    }

    /**
     * Get the active school ID for current admin context.
     *
     * @return int
     */
    public static function active_school_id() {
        $sid = (int) get_user_meta( get_current_user_id(), 'ss_active_school', true );
        if ( $sid ) {
            return $sid;
        }
        global $wpdb;
        $row = $wpdb->get_var( 'SELECT ID FROM ' . self::table( 'schools' ) . ' WHERE is_active = 1 ORDER BY ID ASC LIMIT 1' );
        return (int) $row;
    }

    /**
     * Get active session ID for the active school.
     *
     * @param int $school_id
     * @return int
     */
    public static function active_session_id( $school_id = 0 ) {
        global $wpdb;
        $school_id = (int) ( $school_id ?: self::active_school_id() );
        if ( ! $school_id ) {
            return 0;
        }
        $row = $wpdb->get_var( $wpdb->prepare(
            'SELECT ID FROM ' . self::table( 'sessions' ) . ' WHERE school_id = %d AND is_active = 1 LIMIT 1',
            $school_id
        ) );
        return (int) $row;
    }

    /**
     * Pagination args from current request.
     *
     * @param int $default_per_page
     * @return array { page, per_page, offset }
     */
    public static function pagination_args( $default_per_page = 20 ) {
        $page     = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
        $per_page = isset( $_GET['per_page'] ) ? max( 5, min( 200, (int) $_GET['per_page'] ) ) : $default_per_page;
        $offset   = ( $page - 1 ) * $per_page;
        return array(
            'page'     => $page,
            'per_page' => $per_page,
            'offset'   => $offset,
        );
    }

    /**
     * Render pagination links.
     *
     * @param int    $total
     * @param int    $per_page
     * @param int    $current
     * @param string $base_url
     */
    public static function render_pagination( $total, $per_page, $current, $base_url ) {
        $pages = max( 1, (int) ceil( $total / max( 1, $per_page ) ) );
        if ( $pages <= 1 ) {
            return;
        }
        echo '<nav class="ss-pagination" role="navigation">';
        for ( $i = 1; $i <= $pages; $i++ ) {
            $url = add_query_arg( 'paged', $i, $base_url );
            $cls = $i === (int) $current ? 'active' : '';
            echo '<a href="' . esc_url( $url ) . '" class="ss-page ' . esc_attr( $cls ) . '">' . (int) $i . '</a>';
        }
        echo '</nav>';
    }

    /**
     * Generate a unique number with prefix and zero-padding.
     *
     * @param string $prefix
     * @param int    $base
     * @param int    $padding
     * @return string
     */
    public static function generate_number( $prefix, $base, $padding = 5 ) {
        return $prefix . str_pad( (string) $base, max( 1, (int) $padding ), '0', STR_PAD_LEFT );
    }

    /**
     * Render a notice.
     *
     * @param string $message
     * @param string $type success|warning|error|info
     */
    public static function notice( $message, $type = 'success' ) {
        $type = in_array( $type, array( 'success', 'warning', 'error', 'info' ), true ) ? $type : 'info';
        echo '<div class="ss-notice ss-notice-' . esc_attr( $type ) . '">' . esc_html( $message ) . '</div>';
    }

    /**
     * Sanitize an array recursively.
     *
     * @param array $data
     * @return array
     */
    public static function sanitize_deep( $data ) {
        if ( ! is_array( $data ) ) {
            return is_scalar( $data ) ? sanitize_text_field( (string) $data ) : '';
        }
        $out = array();
        foreach ( $data as $k => $v ) {
            $out[ $k ] = self::sanitize_deep( $v );
        }
        return $out;
    }

    /**
     * URL to the plugin admin dashboard page.
     *
     * @param string $page slug suffix
     * @return string
     */
    public static function admin_url( $page = '' ) {
        $page = $page ?: SS_MENU_DASHBOARD;
        return admin_url( 'admin.php?page=' . $page );
    }

    /**
     * Print the plugin logo svg.
     */
    public static function logo_svg() {
        return '<svg width="28" height="28" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <defs><linearGradient id="ssg" x1="0" x2="1" y1="0" y2="1"><stop offset="0%" stop-color="#818CF8"/><stop offset="100%" stop-color="#4F46E5"/></linearGradient></defs>
            <rect x="6" y="14" width="52" height="40" rx="6" fill="url(#ssg)"/>
            <path d="M32 6 L60 22 L32 38 L4 22 Z" fill="#312E81"/>
            <circle cx="32" cy="22" r="4" fill="#fff"/>
        </svg>';
    }
}

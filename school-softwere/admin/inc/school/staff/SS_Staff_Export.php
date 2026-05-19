<?php
/**
 * SS_Staff_Export - Export students/staff/invoices to CSV.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Staff_Export {

    public static function render() {
        $school_id = SS_Helper::active_school_id();

        if ( isset( $_GET['export'] ) && SS_Helper::verify_nonce( 'export_data' ) ) {
            self::stream( sanitize_key( wp_unslash( $_GET['export'] ) ), $school_id );
        }

        SS_Admin_Shell::open( __( 'Export', 'school-softwere' ), 'school-softwere-export', array(
            array( 'label' => __( 'Export', 'school-softwere' ) ),
        ) );

        SS_Admin_Shell::card_open( __( 'Export Data', 'school-softwere' ) );
        echo '<p class="ss-text-muted">' . esc_html__( 'Click any button to download a CSV file.', 'school-softwere' ) . '</p>';
        echo '<div class="ss-quick-actions">';
        foreach ( array(
            'students' => array( __( 'Students', 'school-softwere' ), 'ph-graduation-cap' ),
            'staff'    => array( __( 'Staff', 'school-softwere' ),    'ph-users-three' ),
            'invoices' => array( __( 'Invoices', 'school-softwere' ), 'ph-receipt' ),
            'payments' => array( __( 'Payments', 'school-softwere' ), 'ph-money' ),
            'expenses' => array( __( 'Expenses', 'school-softwere' ), 'ph-trend-down' ),
            'income'   => array( __( 'Income', 'school-softwere' ),   'ph-trend-up' ),
        ) as $key => $info ) {
            $url = wp_nonce_url( SS_Helper::admin_url( 'school-softwere-export' ) . '&export=' . $key, 'export_data', '_ssnonce' );
            echo '<a class="ss-quick-action" href="' . esc_url( $url ) . '"><i class="ph ' . esc_attr( $info[1] ) . '"></i><span>' . esc_html( $info[0] ) . '</span></a>';
        }
        echo '</div>';
        SS_Admin_Shell::card_close();
        SS_Admin_Shell::close();
    }

    private static function stream( $type, $school_id ) {
        global $wpdb;
        $rows = array();
        switch ( $type ) {
            case 'students':
                $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT admission_number, roll_number, first_name, last_name, gender, dob, phone, email, father_name, mother_name FROM ' . SS_Helper::table( 'student_records' ) . ' WHERE school_id = %d', $school_id ), ARRAY_A );
                break;
            case 'staff':
                $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT first_name, last_name, designation, phone, email, joining_date, salary, is_active FROM ' . SS_Helper::table( 'staff' ) . ' WHERE school_id = %d', $school_id ), ARRAY_A );
                break;
            case 'invoices':
                $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT invoice_number, total_amount, paid_amount, due_amount, status, due_date, created_at FROM ' . SS_Helper::table( 'invoices' ) . ' WHERE school_id = %d', $school_id ), ARRAY_A );
                break;
            case 'payments':
                $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT p.amount, p.payment_method, p.payment_date, p.note, i.invoice_number FROM ' . SS_Helper::table( 'payments' ) . ' p LEFT JOIN ' . SS_Helper::table( 'invoices' ) . ' i ON i.ID = p.invoice_id WHERE i.school_id = %d', $school_id ), ARRAY_A );
                break;
            case 'expenses':
                $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT label, amount, date, note FROM ' . SS_Helper::table( 'expenses' ) . ' WHERE school_id = %d', $school_id ), ARRAY_A );
                break;
            case 'income':
                $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT label, amount, date, note FROM ' . SS_Helper::table( 'income' ) . ' WHERE school_id = %d', $school_id ), ARRAY_A );
                break;
        }
        if ( empty( $rows ) ) {
            wp_safe_redirect( add_query_arg( array( 'ss_notice' => __( 'No data to export', 'school-softwere' ), 'ss_notice_type' => 'warning' ), SS_Helper::admin_url( 'school-softwere-export' ) ) );
            exit;
        }
        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="ss-' . $type . '-' . date( 'Ymd-His' ) . '.csv"' );
        $out = fopen( 'php://output', 'w' ); // phpcs:ignore
        fputcsv( $out, array_keys( $rows[0] ) );
        foreach ( $rows as $r ) {
            fputcsv( $out, array_values( $r ) );
        }
        fclose( $out );
        exit;
    }
}

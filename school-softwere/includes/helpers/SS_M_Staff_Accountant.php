<?php
/**
 * SS_M_Staff_Accountant - Accounting helper queries.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_M_Staff_Accountant {

    /**
     * Total fees collected (sum of paid_amount on invoices) for a school.
     *
     * @param int $school_id
     * @return float
     */
    public static function total_collected( $school_id ) {
        global $wpdb;
        $t = SS_Helper::table( 'invoices' );
        return (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(paid_amount),0) FROM {$t} WHERE school_id = %d", (int) $school_id ) );
    }

    /**
     * Total pending dues for a school.
     *
     * @param int $school_id
     * @return float
     */
    public static function total_due( $school_id ) {
        global $wpdb;
        $t = SS_Helper::table( 'invoices' );
        return (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(due_amount),0) FROM {$t} WHERE school_id = %d AND status != 'paid'", (int) $school_id ) );
    }

    /**
     * Get last 6 months collection trend.
     *
     * @param int $school_id
     * @return array { labels: [], data: [] }
     */
    public static function monthly_collection( $school_id ) {
        global $wpdb;
        $t = SS_Helper::table( 'payments' );
        $i = SS_Helper::table( 'invoices' );
        $labels = array();
        $data   = array();
        for ( $i_month = 5; $i_month >= 0; $i_month-- ) {
            $start = date( 'Y-m-01', strtotime( "-{$i_month} months" ) );
            $end   = date( 'Y-m-t', strtotime( "-{$i_month} months" ) );
            $sum   = $wpdb->get_var( $wpdb->prepare(
                "SELECT COALESCE(SUM(p.amount),0) FROM {$t} p INNER JOIN {$i} i ON i.ID = p.invoice_id WHERE i.school_id = %d AND p.payment_date BETWEEN %s AND %s",
                (int) $school_id, $start, $end
            ) );
            $labels[] = date( 'M Y', strtotime( $start ) );
            $data[]   = (float) $sum;
        }
        return array( 'labels' => $labels, 'data' => $data );
    }
}

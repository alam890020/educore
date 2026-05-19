<?php
/**
 * SS_Staff_Reports - Reports & analytics.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Staff_Reports {

    public static function render() {
        global $wpdb;
        $school_id = SS_Helper::active_school_id();

        SS_Admin_Shell::open( __( 'Reports & Analytics', 'school-softwere' ), 'school-softwere-reports', array(
            array( 'label' => __( 'Reports', 'school-softwere' ) ),
        ) );

        // Top stats.
        $st = SS_Helper::table( 'student_records' );
        $iv = SS_Helper::table( 'invoices' );
        $ex = SS_Helper::table( 'expenses' );
        $in = SS_Helper::table( 'income' );
        $stats = array(
            'students' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$st} WHERE school_id = %d AND is_active = 1", $school_id ) ),
            'collected'=> (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(paid_amount),0) FROM {$iv} WHERE school_id = %d", $school_id ) ),
            'expenses' => (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(amount),0) FROM {$ex} WHERE school_id = %d", $school_id ) ),
            'income'   => (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(amount),0) FROM {$in} WHERE school_id = %d", $school_id ) ),
        );

        echo '<div class="ss-stats-grid">';
        echo '<div class="ss-stat-card"><div class="ss-stat-icon"><i class="ph ph-graduation-cap"></i></div><div class="ss-stat-meta"><p class="ss-stat-label">' . esc_html__( 'Active Students', 'school-softwere' ) . '</p><p class="ss-stat-value">' . (int) $stats['students'] . '</p></div></div>';
        echo '<div class="ss-stat-card ss-stat-success"><div class="ss-stat-icon"><i class="ph ph-trend-up"></i></div><div class="ss-stat-meta"><p class="ss-stat-label">' . esc_html__( 'Fees Collected', 'school-softwere' ) . '</p><p class="ss-stat-value">' . esc_html( SS_Helper::format_money( $stats['collected'] ) ) . '</p></div></div>';
        echo '<div class="ss-stat-card ss-stat-warning"><div class="ss-stat-icon"><i class="ph ph-piggy-bank"></i></div><div class="ss-stat-meta"><p class="ss-stat-label">' . esc_html__( 'Other Income', 'school-softwere' ) . '</p><p class="ss-stat-value">' . esc_html( SS_Helper::format_money( $stats['income'] ) ) . '</p></div></div>';
        echo '<div class="ss-stat-card ss-stat-danger"><div class="ss-stat-icon"><i class="ph ph-trend-down"></i></div><div class="ss-stat-meta"><p class="ss-stat-label">' . esc_html__( 'Expenses', 'school-softwere' ) . '</p><p class="ss-stat-value">' . esc_html( SS_Helper::format_money( $stats['expenses'] ) ) . '</p></div></div>';
        echo '</div>';

        echo '<div class="ss-chart-grid">';
        // Income vs Expense bar chart.
        $months_lbl = array();
        $inc_data   = array();
        $exp_data   = array();
        for ( $i = 5; $i >= 0; $i-- ) {
            $start = date( 'Y-m-01', strtotime( "-{$i} months" ) );
            $end   = date( 'Y-m-t', strtotime( "-{$i} months" ) );
            $months_lbl[] = date( 'M Y', strtotime( $start ) );
            $inc_data[]   = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(amount),0) FROM {$in} WHERE school_id = %d AND date BETWEEN %s AND %s", $school_id, $start, $end ) );
            $exp_data[]   = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(amount),0) FROM {$ex} WHERE school_id = %d AND date BETWEEN %s AND %s", $school_id, $start, $end ) );
        }
        SS_Admin_Shell::card_open( __( 'Income vs Expense (6 months)', 'school-softwere' ) );
        echo '<div class="ss-chart-wrap" style="height:300px"><canvas data-ss-chart="bar" data-ss-labels=\'' . esc_attr( wp_json_encode( $months_lbl ) ) . '\' data-ss-datasets=\'' . esc_attr( wp_json_encode( array(
            array( 'label' => __( 'Income', 'school-softwere' ), 'data' => $inc_data, 'backgroundColor' => '#10B981' ),
            array( 'label' => __( 'Expense', 'school-softwere' ), 'data' => $exp_data, 'backgroundColor' => '#EF4444' ),
        ) ) ) . '\'></canvas></div>';
        SS_Admin_Shell::card_close();

        // Class-wise students.
        $rows = $wpdb->get_results( $wpdb->prepare(
            'SELECT c.label, COUNT(sr.ID) as cnt FROM ' . SS_Helper::table( 'class_school' ) . ' cs INNER JOIN ' . SS_Helper::table( 'classes' ) . ' c ON c.ID = cs.class_id LEFT JOIN ' . SS_Helper::table( 'student_records' ) . ' sr ON sr.class_school_id = cs.ID AND sr.is_active = 1 WHERE cs.school_id = %d GROUP BY c.ID ORDER BY c.ID ASC', $school_id
        ) );
        $cls_lbl = array(); $cls_data = array();
        foreach ( (array) $rows as $r ) { $cls_lbl[] = $r->label; $cls_data[] = (int) $r->cnt; }
        SS_Admin_Shell::card_open( __( 'Class-wise Student Distribution', 'school-softwere' ) );
        echo '<div class="ss-chart-wrap" style="height:300px"><canvas data-ss-chart="bar" data-ss-labels=\'' . esc_attr( wp_json_encode( $cls_lbl ) ) . '\' data-ss-datasets=\'' . esc_attr( wp_json_encode( array( array( 'label' => __( 'Students', 'school-softwere' ), 'data' => $cls_data ) ) ) ) . '\'></canvas></div>';
        SS_Admin_Shell::card_close();
        echo '</div>';

        // Quick links.
        SS_Admin_Shell::card_open( __( 'Available Reports', 'school-softwere' ) );
        echo '<div class="ss-quick-actions">';
        $items = array(
            array( 'school-softwere-students',  __( 'Student List', 'school-softwere' ),       'ph-graduation-cap' ),
            array( 'school-softwere-attendance',__( 'Attendance Report', 'school-softwere' ),  'ph-check-square' ),
            array( 'school-softwere-invoices',  __( 'Fee Collection', 'school-softwere' ),     'ph-receipt' ),
            array( 'school-softwere-expenses',  __( 'Expense Report', 'school-softwere' ),     'ph-trend-down' ),
            array( 'school-softwere-income',    __( 'Income Report', 'school-softwere' ),      'ph-trend-up' ),
            array( 'school-softwere-staff',     __( 'Staff Report', 'school-softwere' ),       'ph-users-three' ),
            array( 'school-softwere-export',    __( 'Export Data', 'school-softwere' ),        'ph-download' ),
        );
        foreach ( $items as $it ) {
            echo '<a href="' . esc_url( SS_Helper::admin_url( $it[0] ) ) . '" class="ss-quick-action"><i class="ph ' . esc_attr( $it[2] ) . '"></i><span>' . esc_html( $it[1] ) . '</span></a>';
        }
        echo '</div>';
        SS_Admin_Shell::card_close();

        SS_Admin_Shell::close();
    }
}

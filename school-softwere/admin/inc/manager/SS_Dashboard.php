<?php
/**
 * SS_Dashboard - Main dashboard page.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Dashboard {

    public static function render() {
        // Setup wizard redirect on first activation.
        if ( ! get_option( 'ss_setup_complete' ) && empty( $_GET['skip_setup'] ) ) {
            if ( ! isset( $_GET['_ssseen'] ) ) {
                wp_safe_redirect( SS_Helper::admin_url( SS_MENU_SETUP_WIZARD ) );
                exit;
            }
        }

        global $wpdb;
        $school_id  = SS_Helper::active_school_id();

        // Stats.
        $stats = self::get_stats( $school_id );
        $coll  = SS_M_Staff_Accountant::monthly_collection( $school_id );
        $att   = self::attendance_summary( $school_id );
        $exam  = SS_M_Staff_Examination::pass_fail( $school_id );

        SS_Admin_Shell::open( __( 'Dashboard', 'school-softwere' ), 'school-softwere', array(
            array( 'label' => __( 'Dashboard', 'school-softwere' ) ),
        ) );

        // Welcome banner.
        $school_name = $school_id ? $wpdb->get_var( $wpdb->prepare( 'SELECT label FROM ' . SS_Helper::table( 'schools' ) . ' WHERE ID = %d', $school_id ) ) : '';
        echo '<section class="ss-welcome">';
        echo '<div>';
        echo '<h2>' . esc_html( sprintf( __( 'Welcome to %s', 'school-softwere' ), $school_name ?: __( 'School Softwere', 'school-softwere' ) ) ) . '</h2>';
        echo '<p>' . esc_html( date_i18n( 'l, F j, Y' ) ) . ' &middot; ' . esc_html__( 'Your school at a glance', 'school-softwere' ) . '</p>';
        echo '</div>';
        echo '<div class="ss-welcome-actions">';
        echo '<a href="' . esc_url( SS_Helper::admin_url( 'school-softwere-students' ) . '&view=add' ) . '" class="ss-btn ss-btn-on-primary"><i class="ph ph-user-plus"></i> ' . esc_html__( 'Add Student', 'school-softwere' ) . '</a>';
        echo '<a href="' . esc_url( SS_Helper::admin_url( 'school-softwere-payments' ) ) . '" class="ss-btn ss-btn-on-primary"><i class="ph ph-money"></i> ' . esc_html__( 'Collect Fee', 'school-softwere' ) . '</a>';
        echo '</div>';
        echo '</section>';

        // Stats cards.
        echo '<div class="ss-stats-grid">';
        self::stat_card( __( 'Total Students', 'school-softwere' ), $stats['students'], 'ph-graduation-cap', 'primary', '+' . $stats['students_new_30'] . __( ' this month', 'school-softwere' ) );
        self::stat_card( __( 'Total Staff', 'school-softwere' ), $stats['staff'], 'ph-users-three', 'success' );
        self::stat_card( __( 'Total Revenue', 'school-softwere' ), SS_Helper::format_money( $stats['revenue'] ), 'ph-trend-up', 'warning' );
        self::stat_card( __( 'Pending Fees', 'school-softwere' ), SS_Helper::format_money( $stats['pending'] ), 'ph-warning', 'danger' );
        echo '</div>';

        // Charts row.
        echo '<div class="ss-chart-grid">';
        SS_Admin_Shell::card_open( __( 'Fee Collection (Last 6 Months)', 'school-softwere' ) );
        echo '<div class="ss-chart-wrap" style="height:300px"><canvas data-ss-chart="line" data-ss-labels=\'' . esc_attr( wp_json_encode( $coll['labels'] ) ) . '\' data-ss-datasets=\'' . esc_attr( wp_json_encode( array( array( 'label' => __( 'Collected', 'school-softwere' ), 'data' => $coll['data'] ) ) ) ) . '\'></canvas></div>';
        SS_Admin_Shell::card_close();

        SS_Admin_Shell::card_open( __( 'Attendance Today', 'school-softwere' ) );
        echo '<div class="ss-chart-wrap" style="height:300px"><canvas data-ss-chart="doughnut" data-ss-labels=\'' . esc_attr( wp_json_encode( array( __( 'Present', 'school-softwere' ), __( 'Absent', 'school-softwere' ), __( 'Late', 'school-softwere' ), __( 'Half Day', 'school-softwere' ) ) ) ) . '\' data-ss-datasets=\'' . esc_attr( wp_json_encode( array( array( 'data' => array( $att['present'], $att['absent'], $att['late'], $att['half_day'] ) ) ) ) ) . '\'></canvas></div>';
        SS_Admin_Shell::card_close();
        echo '</div>';

        // Bottom row.
        echo '<div class="ss-row">';

        echo '<div class="ss-col" style="flex:2">';
        SS_Admin_Shell::card_open( __( 'Quick Actions', 'school-softwere' ) );
        echo '<div class="ss-quick-actions">';
        $qas = array(
            array( 'school-softwere-students',   __( 'Add Student', 'school-softwere' ),   'ph-user-plus' ),
            array( 'school-softwere-staff',      __( 'Add Staff', 'school-softwere' ),     'ph-user-circle-plus' ),
            array( 'school-softwere-attendance', __( 'Mark Attendance', 'school-softwere' ),'ph-check-square' ),
            array( 'school-softwere-fees',       __( 'Add Fee', 'school-softwere' ),       'ph-currency-circle-dollar' ),
            array( 'school-softwere-invoices',   __( 'Generate Invoice', 'school-softwere' ),'ph-receipt' ),
            array( 'school-softwere-notices',    __( 'Post Notice', 'school-softwere' ),   'ph-megaphone' ),
            array( 'school-softwere-exams',      __( 'New Exam', 'school-softwere' ),      'ph-exam' ),
            array( 'school-softwere-reports',    __( 'View Reports', 'school-softwere' ),  'ph-chart-bar' ),
        );
        foreach ( $qas as $qa ) {
            echo '<a href="' . esc_url( SS_Helper::admin_url( $qa[0] ) ) . '" class="ss-quick-action"><i class="ph ' . esc_attr( $qa[2] ) . '"></i><span>' . esc_html( $qa[1] ) . '</span></a>';
        }
        echo '</div>';
        SS_Admin_Shell::card_close();

        // Recent activities.
        SS_Admin_Shell::card_open( __( 'Recent Activities', 'school-softwere' ) );
        $logs = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . SS_Helper::table( 'logs' ) . ' WHERE school_id = %d ORDER BY ID DESC LIMIT 10', $school_id ) );
        if ( empty( $logs ) ) {
            echo '<div class="ss-empty"><i class="ph ph-clipboard-text"></i><h3>' . esc_html__( 'No activity yet', 'school-softwere' ) . '</h3><p>' . esc_html__( 'Actions will appear here.', 'school-softwere' ) . '</p></div>';
        } else {
            echo '<ul class="ss-feed">';
            foreach ( $logs as $l ) {
                echo '<li><div class="ss-feed-icon"><i class="ph ph-circle-wavy-check"></i></div><div class="ss-feed-meta"><strong>' . esc_html( $l->action ) . '</strong><small>' . esc_html( SS_Helper::format_date( $l->created_at ) ) . '</small></div></li>';
            }
            echo '</ul>';
        }
        SS_Admin_Shell::card_close();
        echo '</div>';

        echo '<div class="ss-col" style="flex:1">';
        // Upcoming events.
        SS_Admin_Shell::card_open( __( 'Upcoming Events', 'school-softwere' ) );
        $events = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . SS_Helper::table( 'events' ) . ' WHERE school_id = %d AND start_date >= %s ORDER BY start_date ASC LIMIT 5', $school_id, current_time( 'mysql' ) ) );
        if ( empty( $events ) ) {
            echo '<div class="ss-empty"><i class="ph ph-calendar"></i><h3>' . esc_html__( 'No events', 'school-softwere' ) . '</h3></div>';
        } else {
            echo '<ul class="ss-feed">';
            foreach ( $events as $e ) {
                echo '<li><div class="ss-feed-icon"><i class="ph ph-calendar-check"></i></div><div class="ss-feed-meta"><strong>' . esc_html( $e->title ) . '</strong><small>' . esc_html( SS_Helper::format_date( $e->start_date ) ) . '</small></div></li>';
            }
            echo '</ul>';
        }
        SS_Admin_Shell::card_close();

        // Pass/Fail donut.
        SS_Admin_Shell::card_open( __( 'Exam Results Overview', 'school-softwere' ) );
        echo '<div class="ss-chart-wrap" style="height:240px"><canvas data-ss-chart="doughnut" data-ss-labels=\'' . esc_attr( wp_json_encode( array( __( 'Pass', 'school-softwere' ), __( 'Fail', 'school-softwere' ) ) ) ) . '\' data-ss-datasets=\'' . esc_attr( wp_json_encode( array( array( 'data' => array( $exam['pass'], $exam['fail'] ), 'backgroundColor' => array( '#10B981', '#EF4444' ) ) ) ) ) . '\'></canvas></div>';
        SS_Admin_Shell::card_close();
        echo '</div>';

        echo '</div>'; // .ss-row

        // Pending fees table.
        SS_Admin_Shell::card_open( __( 'Pending Fee Reminders', 'school-softwere' ),
            '<a href="' . esc_url( SS_Helper::admin_url( 'school-softwere-invoices' ) ) . '" class="ss-btn ss-btn-secondary ss-btn-sm">' . esc_html__( 'View All', 'school-softwere' ) . '</a>'
        );
        $pending = $wpdb->get_results( $wpdb->prepare(
            'SELECT i.*, sr.first_name, sr.last_name, sr.admission_number FROM ' . SS_Helper::table( 'invoices' ) . ' i LEFT JOIN ' . SS_Helper::table( 'student_records' ) . ' sr ON sr.ID = i.student_record_id WHERE i.school_id = %d AND i.status != %s ORDER BY i.due_date ASC LIMIT 8',
            $school_id, 'paid'
        ) );
        if ( empty( $pending ) ) {
            echo '<div class="ss-empty"><i class="ph ph-check-circle"></i><h3>' . esc_html__( 'All clear!', 'school-softwere' ) . '</h3><p>' . esc_html__( 'No pending payments.', 'school-softwere' ) . '</p></div>';
        } else {
            echo '<div class="ss-table-wrap"><table class="ss-table"><thead><tr><th>' . esc_html__( 'Student', 'school-softwere' ) . '</th><th>' . esc_html__( 'Invoice #', 'school-softwere' ) . '</th><th>' . esc_html__( 'Due', 'school-softwere' ) . '</th><th>' . esc_html__( 'Due Date', 'school-softwere' ) . '</th><th>' . esc_html__( 'Status', 'school-softwere' ) . '</th></tr></thead><tbody>';
            foreach ( $pending as $r ) {
                $name = trim( $r->first_name . ' ' . $r->last_name );
                echo '<tr><td><strong>' . esc_html( $name ) . '</strong><br><small class="ss-text-muted">' . esc_html( $r->admission_number ) . '</small></td><td>' . esc_html( $r->invoice_number ) . '</td><td>' . esc_html( SS_Helper::format_money( $r->due_amount ) ) . '</td><td>' . esc_html( SS_Helper::format_date( $r->due_date ) ) . '</td><td>' . SS_Helper::badge( $r->status, 'partial' === $r->status ? 'warning' : 'danger' ) . '</td></tr>'; // phpcs:ignore
            }
            echo '</tbody></table></div>';
        }
        SS_Admin_Shell::card_close();

        SS_Admin_Shell::close();
    }

    private static function stat_card( $label, $value, $icon, $variant = 'primary', $trend = '' ) {
        $cls = $variant ? 'ss-stat-' . $variant : '';
        echo '<div class="ss-stat-card ' . esc_attr( $cls ) . '">';
        echo '<div class="ss-stat-icon"><i class="ph ' . esc_attr( $icon ) . '"></i></div>';
        echo '<div class="ss-stat-meta">';
        echo '<p class="ss-stat-label">' . esc_html( $label ) . '</p>';
        echo '<p class="ss-stat-value">' . esc_html( (string) $value ) . '</p>';
        if ( $trend ) {
            echo '<div class="ss-stat-trend"><i class="ph ph-trend-up"></i> ' . esc_html( $trend ) . '</div>';
        }
        echo '</div></div>';
    }

    private static function get_stats( $school_id ) {
        global $wpdb;
        if ( ! $school_id ) {
            return array( 'students' => 0, 'staff' => 0, 'revenue' => 0, 'pending' => 0, 'students_new_30' => 0 );
        }
        $st = SS_Helper::table( 'student_records' );
        $sf = SS_Helper::table( 'staff' );
        $iv = SS_Helper::table( 'invoices' );
        return array(
            'students' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$st} WHERE school_id = %d AND is_active = 1", $school_id ) ),
            'staff'    => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$sf} WHERE school_id = %d AND is_active = 1", $school_id ) ),
            'revenue'  => (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(paid_amount),0) FROM {$iv} WHERE school_id = %d", $school_id ) ),
            'pending'  => (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(due_amount),0) FROM {$iv} WHERE school_id = %d AND status != 'paid'", $school_id ) ),
            'students_new_30' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$st} WHERE school_id = %d AND created_at >= %s", $school_id, gmdate( 'Y-m-d 00:00:00', strtotime( '-30 days' ) ) ) ),
        );
    }

    private static function attendance_summary( $school_id ) {
        global $wpdb;
        $t = SS_Helper::table( 'attendance' );
        $r = array( 'present' => 0, 'absent' => 0, 'late' => 0, 'half_day' => 0 );
        if ( ! $school_id ) {
            return $r;
        }
        $st = SS_Helper::table( 'student_records' );
        $today = current_time( 'Y-m-d' );
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT a.status, COUNT(*) c FROM {$t} a INNER JOIN {$st} sr ON sr.ID = a.student_record_id WHERE sr.school_id = %d AND a.date = %s GROUP BY a.status",
            $school_id, $today
        ) );
        foreach ( (array) $rows as $row ) {
            if ( isset( $r[ $row->status ] ) ) {
                $r[ $row->status ] = (int) $row->c;
            }
        }
        return $r;
    }
}

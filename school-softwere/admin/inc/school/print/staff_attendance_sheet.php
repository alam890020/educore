<?php
/**
 * Print: Staff attendance summary.
 */
defined( 'ABSPATH' ) || die();
require_once __DIR__ . '/_print_shell.php';

global $wpdb;
$school_id = SS_Helper::active_school_id();
$from = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : date( 'Y-m-01' );
$to   = isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : date( 'Y-m-t' );
$school = ss_print_school( $school_id );
$rows = $wpdb->get_results( $wpdb->prepare(
    'SELECT a.*, CONCAT(s.first_name," ",s.last_name) staff_name FROM ' . SS_Helper::table( 'staff_attendance' ) . ' a INNER JOIN ' . SS_Helper::table( 'staff' ) . ' s ON s.ID = a.staff_id WHERE a.school_id = %d AND a.date BETWEEN %s AND %s ORDER BY a.date DESC',
    $school_id, $from, $to
) );

ss_print_open( __( 'Staff Attendance', 'school-softwere' ) );
$title = __( 'Staff Attendance Sheet', 'school-softwere' );
include __DIR__ . '/partials/school_header.php';
?>
<table class="ss-print-table">
    <thead><tr><th>Date</th><th>Staff</th><th>Status</th><th>Note</th></tr></thead>
    <tbody>
    <?php foreach ( $rows as $r ) : ?>
        <tr>
            <td><?php echo esc_html( SS_Helper::format_date( $r->date ) ); ?></td>
            <td><?php echo esc_html( $r->staff_name ); ?></td>
            <td><?php echo esc_html( ucfirst( (string) $r->status ) ); ?></td>
            <td><?php echo esc_html( $r->note ); ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php ss_print_close();

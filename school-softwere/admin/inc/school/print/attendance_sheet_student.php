<?php
/**
 * Print: Student-wise attendance summary (date range).
 */
defined( 'ABSPATH' ) || die();
require_once __DIR__ . '/_print_shell.php';

global $wpdb;
$student_id = isset( $_GET['student_id'] ) ? (int) $_GET['student_id'] : 0;
$from = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : date( 'Y-m-01' );
$to   = isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : date( 'Y-m-t' );
$student = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . SS_Helper::table( 'student_records' ) . ' WHERE ID = %d', $student_id ) );
if ( ! $student ) { wp_die( esc_html__( 'Student not found', 'school-softwere' ) ); }
$school = ss_print_school( $student->school_id );
$rows = $wpdb->get_results( $wpdb->prepare(
    'SELECT * FROM ' . SS_Helper::table( 'attendance' ) . ' WHERE student_record_id = %d AND date BETWEEN %s AND %s ORDER BY date ASC',
    $student_id, $from, $to
) );

ss_print_open( __( 'Student Attendance', 'school-softwere' ) );
$title = trim( $student->first_name . ' ' . $student->last_name ) . ' - ' . SS_Helper::format_date( $from ) . ' to ' . SS_Helper::format_date( $to );
include __DIR__ . '/partials/school_header.php';
$counts = array( 'present' => 0, 'absent' => 0, 'late' => 0, 'half_day' => 0 );
foreach ( $rows as $r ) { if ( isset( $counts[ $r->status ] ) ) { $counts[ $r->status ]++; } }
?>
<div style="display:flex; gap:14px; margin-bottom:14px;">
    <div style="background:#ECFDF5; border:1px solid #D1FAE5; padding:8px 14px; border-radius:8px;">Present: <strong><?php echo (int) $counts['present']; ?></strong></div>
    <div style="background:#FEF2F2; border:1px solid #FECACA; padding:8px 14px; border-radius:8px;">Absent: <strong><?php echo (int) $counts['absent']; ?></strong></div>
    <div style="background:#FFFBEB; border:1px solid #FDE68A; padding:8px 14px; border-radius:8px;">Late: <strong><?php echo (int) $counts['late']; ?></strong></div>
    <div style="background:#EEF2FF; border:1px solid #E0E7FF; padding:8px 14px; border-radius:8px;">Half: <strong><?php echo (int) $counts['half_day']; ?></strong></div>
</div>
<table class="ss-print-table">
    <thead><tr><th>Date</th><th>Status</th><th>Note</th></tr></thead>
    <tbody>
    <?php foreach ( $rows as $r ) : ?>
        <tr>
            <td><?php echo esc_html( SS_Helper::format_date( $r->date ) ); ?></td>
            <td><?php echo esc_html( ucfirst( (string) $r->status ) ); ?></td>
            <td><?php echo esc_html( $r->note ); ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php ss_print_close();

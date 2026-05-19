<?php
/**
 * Print: Exam timetable.
 */
defined( 'ABSPATH' ) || die();
require_once __DIR__ . '/_print_shell.php';

global $wpdb;
$exam_id = isset( $_GET['exam_id'] ) ? (int) $_GET['exam_id'] : 0;
$exam = $wpdb->get_row( $wpdb->prepare(
    'SELECT e.*, c.label class_label, cs.school_id FROM ' . SS_Helper::table( 'exams' ) . ' e
     LEFT JOIN ' . SS_Helper::table( 'class_school' ) . ' cs ON cs.ID = e.class_school_id
     LEFT JOIN ' . SS_Helper::table( 'classes' ) . ' c ON c.ID = cs.class_id
     WHERE e.ID = %d', $exam_id
) );
if ( ! $exam ) { wp_die( esc_html__( 'Exam not found', 'school-softwere' ) ); }
$school = ss_print_school( $exam->school_id );
$rows = $wpdb->get_results( $wpdb->prepare(
    'SELECT p.*, s.label subject_label FROM ' . SS_Helper::table( 'exam_papers' ) . ' p LEFT JOIN ' . SS_Helper::table( 'subjects' ) . ' s ON s.ID = p.subject_id WHERE p.exam_id = %d ORDER BY p.date ASC',
    $exam_id
) );

ss_print_open( __( 'Exam Timetable', 'school-softwere' ) );
$title = $exam->label . ' - ' . $exam->class_label;
include __DIR__ . '/partials/school_header.php';
?>
<table class="ss-print-table">
    <thead><tr><th>Date</th><th>Subject</th><th>Time</th><th>Total Marks</th><th>Pass Marks</th></tr></thead>
    <tbody>
    <?php foreach ( $rows as $r ) : ?>
        <tr>
            <td><?php echo esc_html( SS_Helper::format_date( $r->date ) ); ?></td>
            <td><strong><?php echo esc_html( $r->subject_label ); ?></strong></td>
            <td><?php echo esc_html( $r->start_time . ' - ' . $r->end_time ); ?></td>
            <td><?php echo (int) $r->total_marks; ?></td>
            <td><?php echo (int) $r->pass_marks; ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php ss_print_close();

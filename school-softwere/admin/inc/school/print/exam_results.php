<?php
/**
 * Print: Single student exam result card.
 */
defined( 'ABSPATH' ) || die();
require_once __DIR__ . '/_print_shell.php';

global $wpdb;
$student_id = isset( $_GET['student_id'] ) ? (int) $_GET['student_id'] : 0;
$exam_id    = isset( $_GET['exam_id'] ) ? (int) $_GET['exam_id'] : 0;
$student = $wpdb->get_row( $wpdb->prepare( 'SELECT sr.*, c.label class_label FROM ' . SS_Helper::table( 'student_records' ) . ' sr LEFT JOIN ' . SS_Helper::table( 'class_school' ) . ' cs ON cs.ID = sr.class_school_id LEFT JOIN ' . SS_Helper::table( 'classes' ) . ' c ON c.ID = cs.class_id WHERE sr.ID = %d', $student_id ) );
if ( ! $student ) { wp_die( esc_html__( 'Student not found', 'school-softwere' ) ); }
$school = ss_print_school( $student->school_id );
$rows = $wpdb->get_results( $wpdb->prepare(
    'SELECT er.*, ep.total_marks, ep.pass_marks, s.label subject_label, e.label exam_label
     FROM ' . SS_Helper::table( 'exam_results' ) . ' er
     INNER JOIN ' . SS_Helper::table( 'exam_papers' ) . ' ep ON ep.ID = er.exam_paper_id
     INNER JOIN ' . SS_Helper::table( 'exams' ) . ' e ON e.ID = ep.exam_id
     LEFT JOIN ' . SS_Helper::table( 'subjects' ) . ' s ON s.ID = ep.subject_id
     WHERE er.student_record_id = %d ' . ( $exam_id ? ' AND e.ID = ' . (int) $exam_id : '' ),
    $student_id
) );

$tot = 0; $obt = 0;
foreach ( (array) $rows as $r ) { $tot += (float) $r->total_marks; $obt += (float) $r->obtained_marks; }
$pct = $tot ? round( ( $obt / $tot ) * 100, 2 ) : 0;

ss_print_open( __( 'Result Card', 'school-softwere' ) );
$title = __( 'Examination Result Card', 'school-softwere' );
include __DIR__ . '/partials/school_header.php';
?>
<div style="display:flex; justify-content:space-between; margin-bottom:16px;">
    <div>
        <div class="ss-print-row"><span class="label">Student:</span> <strong><?php echo esc_html( trim( $student->first_name . ' ' . $student->last_name ) ); ?></strong></div>
        <div class="ss-print-row"><span class="label">Adm. #:</span> <?php echo esc_html( $student->admission_number ); ?></div>
        <div class="ss-print-row"><span class="label">Class:</span> <?php echo esc_html( $student->class_label ); ?></div>
    </div>
    <div>
        <div class="ss-print-row"><span class="label">Total:</span> <strong><?php echo esc_html( $obt . ' / ' . $tot ); ?></strong></div>
        <div class="ss-print-row"><span class="label">Percentage:</span> <strong><?php echo esc_html( $pct ); ?>%</strong></div>
    </div>
</div>
<table class="ss-print-table">
    <thead><tr><th>Exam</th><th>Subject</th><th>Total</th><th>Pass</th><th>Obtained</th><th>Result</th></tr></thead>
    <tbody>
        <?php foreach ( $rows as $r ) : $passed = (float) $r->obtained_marks >= (float) $r->pass_marks; ?>
        <tr>
            <td><?php echo esc_html( $r->exam_label ); ?></td>
            <td><?php echo esc_html( $r->subject_label ); ?></td>
            <td><?php echo (int) $r->total_marks; ?></td>
            <td><?php echo (int) $r->pass_marks; ?></td>
            <td><strong><?php echo (float) $r->obtained_marks; ?></strong></td>
            <td><span style="color:<?php echo $passed ? '#10B981' : '#EF4444'; ?>;font-weight:600;"><?php echo $passed ? 'PASS' : 'FAIL'; ?></span></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php ss_print_close();

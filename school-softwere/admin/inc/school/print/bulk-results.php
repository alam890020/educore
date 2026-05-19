<?php
/**
 * Print: Class-wise results for an exam paper.
 */
defined( 'ABSPATH' ) || die();
require_once __DIR__ . '/_print_shell.php';

global $wpdb;
$paper_id = isset( $_GET['exam_paper_id'] ) ? (int) $_GET['exam_paper_id'] : 0;
$paper = $wpdb->get_row( $wpdb->prepare(
    'SELECT p.*, s.label subject_label, e.label exam_label, e.class_school_id, cs.school_id
     FROM ' . SS_Helper::table( 'exam_papers' ) . ' p
     INNER JOIN ' . SS_Helper::table( 'subjects' ) . ' s ON s.ID = p.subject_id
     INNER JOIN ' . SS_Helper::table( 'exams' ) . ' e ON e.ID = p.exam_id
     INNER JOIN ' . SS_Helper::table( 'class_school' ) . ' cs ON cs.ID = e.class_school_id
     WHERE p.ID = %d', $paper_id
) );
if ( ! $paper ) { wp_die( esc_html__( 'Not found', 'school-softwere' ) ); }
$school = ss_print_school( $paper->school_id );
$rows = $wpdb->get_results( $wpdb->prepare(
    'SELECT er.obtained_marks, sr.first_name, sr.last_name, sr.admission_number, sr.roll_number FROM ' . SS_Helper::table( 'exam_results' ) . ' er INNER JOIN ' . SS_Helper::table( 'student_records' ) . ' sr ON sr.ID = er.student_record_id WHERE er.exam_paper_id = %d ORDER BY sr.roll_number ASC',
    $paper_id
) );

ss_print_open( __( 'Results', 'school-softwere' ) );
$title = $paper->exam_label . ' - ' . $paper->subject_label;
include __DIR__ . '/partials/school_header.php';
?>
<table class="ss-print-table">
    <thead><tr><th>Roll</th><th>Adm. #</th><th>Student</th><th>Marks</th><th>Result</th></tr></thead>
    <tbody>
    <?php foreach ( $rows as $r ) : $passed = (float) $r->obtained_marks >= (float) $paper->pass_marks; ?>
        <tr>
            <td><?php echo esc_html( $r->roll_number ); ?></td>
            <td><?php echo esc_html( $r->admission_number ); ?></td>
            <td><?php echo esc_html( trim( $r->first_name . ' ' . $r->last_name ) ); ?></td>
            <td><strong><?php echo (float) $r->obtained_marks; ?></strong> / <?php echo (int) $paper->total_marks; ?></td>
            <td><span style="color:<?php echo $passed ? '#10B981' : '#EF4444'; ?>;font-weight:600;"><?php echo $passed ? 'PASS' : 'FAIL'; ?></span></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php ss_print_close();

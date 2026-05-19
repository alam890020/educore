<?php
/**
 * Print: Admit Card (single).
 */
defined( 'ABSPATH' ) || die();
require_once __DIR__ . '/_print_shell.php';

global $wpdb;
$id  = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
$ac  = $wpdb->get_row( $wpdb->prepare(
    'SELECT a.*, sr.first_name, sr.last_name, sr.admission_number, sr.school_id, sr.photo, e.label exam_label, c.label class_label
     FROM ' . SS_Helper::table( 'admit_cards' ) . ' a
     LEFT JOIN ' . SS_Helper::table( 'student_records' ) . ' sr ON sr.ID = a.student_record_id
     LEFT JOIN ' . SS_Helper::table( 'exams' ) . ' e ON e.ID = a.exam_id
     LEFT JOIN ' . SS_Helper::table( 'class_school' ) . ' cs ON cs.ID = e.class_school_id
     LEFT JOIN ' . SS_Helper::table( 'classes' ) . ' c ON c.ID = cs.class_id
     WHERE a.ID = %d', $id
) );
if ( ! $ac ) { wp_die( esc_html__( 'Admit card not found', 'school-softwere' ) ); }
$papers = $wpdb->get_results( $wpdb->prepare( 'SELECT p.*, s.label subject_label FROM ' . SS_Helper::table( 'exam_papers' ) . ' p LEFT JOIN ' . SS_Helper::table( 'subjects' ) . ' s ON s.ID = p.subject_id WHERE p.exam_id = %d ORDER BY p.date ASC', $ac->exam_id ) );
$school = ss_print_school( $ac->school_id );

ss_print_open( __( 'Admit Card', 'school-softwere' ) );
$title = __( 'Admit Card', 'school-softwere' );
include __DIR__ . '/partials/school_header.php';
?>
<div style="display:flex; gap:16px; margin-bottom:16px;">
    <div style="width:90px;height:110px;border:1px solid #E0E7FF;border-radius:6px;display:flex;align-items:center;justify-content:center;background:#F8FAFF;">
        <?php if ( $ac->photo ) : ?>
            <img src="<?php echo esc_url( $ac->photo ); ?>" style="width:100%;height:100%;object-fit:cover;">
        <?php else : ?>
            <span style="color:#94A3B8;font-size:11px;">Photo</span>
        <?php endif; ?>
    </div>
    <div style="flex:1;">
        <div class="ss-print-row"><span class="label">Card #:</span> <strong><?php echo esc_html( $ac->admit_card_number ); ?></strong></div>
        <div class="ss-print-row"><span class="label">Student:</span> <strong><?php echo esc_html( trim( $ac->first_name . ' ' . $ac->last_name ) ); ?></strong></div>
        <div class="ss-print-row"><span class="label">Adm. #:</span> <?php echo esc_html( $ac->admission_number ); ?></div>
        <div class="ss-print-row"><span class="label">Exam:</span> <?php echo esc_html( $ac->exam_label ); ?></div>
        <div class="ss-print-row"><span class="label">Class:</span> <?php echo esc_html( $ac->class_label ); ?></div>
    </div>
    <div class="ss-qr">QR</div>
</div>
<table class="ss-print-table">
    <thead><tr><th>Subject</th><th>Date</th><th>Time</th><th>Marks</th></tr></thead>
    <tbody>
        <?php foreach ( $papers as $p ) : ?>
        <tr>
            <td><?php echo esc_html( $p->subject_label ); ?></td>
            <td><?php echo esc_html( SS_Helper::format_date( $p->date ) ); ?></td>
            <td><?php echo esc_html( $p->start_time . ' - ' . $p->end_time ); ?></td>
            <td><?php echo (int) $p->total_marks; ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<div style="margin-top:60px; display:flex; justify-content:space-between; font-size:12px; color:#64748B;">
    <div style="border-top:1px solid #E0E7FF; padding-top:6px; width:180px; text-align:center;">Student Signature</div>
    <div style="border-top:1px solid #E0E7FF; padding-top:6px; width:180px; text-align:center;">Principal</div>
</div>
<?php ss_print_close();

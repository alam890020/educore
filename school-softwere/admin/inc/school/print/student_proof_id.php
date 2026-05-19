<?php
/**
 * Print: Student proof of admission.
 */
defined( 'ABSPATH' ) || die();
require_once __DIR__ . '/_print_shell.php';

global $wpdb;
$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
$row = $wpdb->get_row( $wpdb->prepare(
    'SELECT sr.*, c.label class_label FROM ' . SS_Helper::table( 'student_records' ) . ' sr LEFT JOIN ' . SS_Helper::table( 'class_school' ) . ' cs ON cs.ID = sr.class_school_id LEFT JOIN ' . SS_Helper::table( 'classes' ) . ' c ON c.ID = cs.class_id WHERE sr.ID = %d', $id
) );
if ( ! $row ) { wp_die( esc_html__( 'Student not found', 'school-softwere' ) ); }
$school = ss_print_school( $row->school_id );

ss_print_open( __( 'Proof of Admission', 'school-softwere' ) );
$title = __( 'Proof of Admission', 'school-softwere' );
include __DIR__ . '/partials/school_header.php';
?>
<p style="line-height:1.8; margin:24px 0;">
This is to certify that <strong><?php echo esc_html( trim( $row->first_name . ' ' . $row->last_name ) ); ?></strong> (Admission Number <strong><?php echo esc_html( $row->admission_number ); ?></strong>) is enrolled as a regular student in class <strong><?php echo esc_html( $row->class_label ); ?></strong> at <strong><?php echo esc_html( $school ? $school->label : '' ); ?></strong>, and has been on rolls since <?php echo esc_html( SS_Helper::format_date( $row->admission_date ) ); ?>.
</p>
<p>This certificate is issued upon the request of the student/guardian for official purposes.</p>
<div style="margin-top:80px; display:flex; justify-content:space-between;">
    <div style="border-top:1px solid #1E1B4B; padding-top:6px; width:180px; text-align:center;">Class Teacher</div>
    <div style="border-top:1px solid #1E1B4B; padding-top:6px; width:180px; text-align:center;">Principal</div>
</div>
<?php ss_print_close();

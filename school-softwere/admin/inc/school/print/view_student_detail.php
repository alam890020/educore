<?php
/**
 * Print: Full student profile.
 */
defined( 'ABSPATH' ) || die();
require_once __DIR__ . '/_print_shell.php';

global $wpdb;
$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
$row = $wpdb->get_row( $wpdb->prepare(
    'SELECT sr.*, c.label class_label, sec.label section_label FROM ' . SS_Helper::table( 'student_records' ) . ' sr LEFT JOIN ' . SS_Helper::table( 'class_school' ) . ' cs ON cs.ID = sr.class_school_id LEFT JOIN ' . SS_Helper::table( 'classes' ) . ' c ON c.ID = cs.class_id LEFT JOIN ' . SS_Helper::table( 'sections' ) . ' sec ON sec.ID = sr.section_id WHERE sr.ID = %d', $id
) );
if ( ! $row ) { wp_die( esc_html__( 'Student not found', 'school-softwere' ) ); }
$school = ss_print_school( $row->school_id );

ss_print_open( __( 'Student Profile', 'school-softwere' ) );
$title = __( 'Student Detail', 'school-softwere' );
include __DIR__ . '/partials/school_header.php';
$pairs = array(
    'Name'         => trim( $row->first_name . ' ' . $row->last_name ),
    'Adm. #'       => $row->admission_number,
    'Roll #'       => $row->roll_number,
    'Class'        => $row->class_label . ( $row->section_label ? ' / ' . $row->section_label : '' ),
    'Date of Birth'=> SS_Helper::format_date( $row->dob ),
    'Gender'       => ucfirst( (string) $row->gender ),
    'Blood Group'  => $row->blood_group,
    'Religion'     => $row->religion,
    'Father'       => $row->father_name,
    'Mother'       => $row->mother_name,
    'Guardian'     => $row->guardian_name . ( $row->guardian_relation ? ' (' . $row->guardian_relation . ')' : '' ),
    'Phone'        => $row->phone,
    'Email'        => $row->email,
    'Address'      => trim( $row->address . ', ' . $row->city . ', ' . $row->state . ' ' . $row->zip . ', ' . $row->country, ', ' ),
    'Admission Date' => SS_Helper::format_date( $row->admission_date ),
);
?>
<table class="ss-print-table">
    <tbody>
    <?php foreach ( $pairs as $k => $v ) : ?>
        <tr><th style="width:30%; text-align:left;"><?php echo esc_html( $k ); ?></th><td><?php echo esc_html( $v ); ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php ss_print_close();

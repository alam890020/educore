<?php
/**
 * Print: Student ID card (single).
 */
defined( 'ABSPATH' ) || die();
require_once __DIR__ . '/_print_shell.php';

global $wpdb;
$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
$row = $wpdb->get_row( $wpdb->prepare(
    'SELECT sr.*, c.label class_label, sec.label section_label FROM ' . SS_Helper::table( 'student_records' ) . ' sr LEFT JOIN ' . SS_Helper::table( 'class_school' ) . ' cs ON cs.ID = sr.class_school_id LEFT JOIN ' . SS_Helper::table( 'classes' ) . ' c ON c.ID = cs.class_id LEFT JOIN ' . SS_Helper::table( 'sections' ) . ' sec ON sec.ID = sr.section_id WHERE sr.ID = %d',
    $id
) );
if ( ! $row ) { wp_die( esc_html__( 'Student not found', 'school-softwere' ) ); }
$school = ss_print_school( $row->school_id );

ss_print_open( __( 'Student ID Card', 'school-softwere' ) );
?>
<div class="ss-id-card">
    <div class="head"><?php echo esc_html( $school ? $school->label : 'School' ); ?></div>
    <div class="body">
        <div class="photo">
            <?php if ( $row->photo ) : ?>
                <img src="<?php echo esc_url( $row->photo ); ?>" style="width:100%;height:100%;object-fit:cover;border-radius:6px;">
            <?php else : ?>
                Photo
            <?php endif; ?>
        </div>
        <div class="info">
            <b><?php echo esc_html( trim( $row->first_name . ' ' . $row->last_name ) ); ?></b>
            <div><?php echo esc_html( $row->admission_number ); ?></div>
            <div><?php echo esc_html( $row->class_label . ( $row->section_label ? ' / ' . $row->section_label : '' ) ); ?></div>
            <div>DOB: <?php echo esc_html( SS_Helper::format_date( $row->dob ) ); ?></div>
            <div>BG: <?php echo esc_html( $row->blood_group ); ?></div>
            <div>Phone: <?php echo esc_html( $row->phone ); ?></div>
        </div>
    </div>
</div>
<?php ss_print_close();

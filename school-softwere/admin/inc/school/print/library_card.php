<?php
/**
 * Print: Library Card.
 */
defined( 'ABSPATH' ) || die();
require_once __DIR__ . '/_print_shell.php';

global $wpdb;
$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
$row = $wpdb->get_row( $wpdb->prepare(
    'SELECT lc.*, sr.first_name, sr.last_name, sr.admission_number, sr.school_id, c.label class_label FROM ' . SS_Helper::table( 'library_cards' ) . ' lc LEFT JOIN ' . SS_Helper::table( 'student_records' ) . ' sr ON sr.ID = lc.student_record_id LEFT JOIN ' . SS_Helper::table( 'class_school' ) . ' cs ON cs.ID = sr.class_school_id LEFT JOIN ' . SS_Helper::table( 'classes' ) . ' c ON c.ID = cs.class_id WHERE lc.ID = %d', $id
) );
if ( ! $row ) { wp_die( esc_html__( 'Card not found', 'school-softwere' ) ); }
$school = ss_print_school( $row->school_id );

ss_print_open( __( 'Library Card', 'school-softwere' ) );
?>
<div class="ss-id-card" style="background:linear-gradient(135deg,#FFFBEB,#fff);">
    <div class="head" style="background:#F59E0B;">Library Card - <?php echo esc_html( $school ? $school->label : '' ); ?></div>
    <div class="body">
        <div class="ss-qr">QR</div>
        <div class="info">
            <b><?php echo esc_html( trim( $row->first_name . ' ' . $row->last_name ) ); ?></b>
            <div>Card #: <?php echo esc_html( $row->card_number ); ?></div>
            <div>Adm: <?php echo esc_html( $row->admission_number ); ?></div>
            <div>Class: <?php echo esc_html( $row->class_label ); ?></div>
            <div>Issued: <?php echo esc_html( SS_Helper::format_date( $row->issued_date ) ); ?></div>
            <div>Expires: <?php echo esc_html( SS_Helper::format_date( $row->expiry_date ) ); ?></div>
        </div>
    </div>
</div>
<?php ss_print_close();

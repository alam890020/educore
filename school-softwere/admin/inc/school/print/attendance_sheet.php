<?php
/**
 * Print: Class attendance sheet for a single date.
 */
defined( 'ABSPATH' ) || die();
require_once __DIR__ . '/_print_shell.php';

global $wpdb;
$cs_id = isset( $_GET['class_school_id'] ) ? (int) $_GET['class_school_id'] : 0;
$date  = isset( $_GET['date'] ) ? sanitize_text_field( wp_unslash( $_GET['date'] ) ) : current_time( 'Y-m-d' );
$cs = $wpdb->get_row( $wpdb->prepare(
    'SELECT cs.*, c.label class_label FROM ' . SS_Helper::table( 'class_school' ) . ' cs LEFT JOIN ' . SS_Helper::table( 'classes' ) . ' c ON c.ID = cs.class_id WHERE cs.ID = %d', $cs_id
) );
if ( ! $cs ) { wp_die( esc_html__( 'Not found', 'school-softwere' ) ); }
$school = ss_print_school( $cs->school_id );
$students = SS_M_Staff_Class::students( $cs_id );
$existing = array();
foreach ( (array) $wpdb->get_results( $wpdb->prepare( 'SELECT student_record_id, status FROM ' . SS_Helper::table( 'attendance' ) . ' WHERE class_school_id = %d AND date = %s', $cs_id, $date ) ) as $a ) {
    $existing[ (int) $a->student_record_id ] = $a->status;
}

ss_print_open( __( 'Attendance Sheet', 'school-softwere' ) );
$title = __( 'Attendance', 'school-softwere' ) . ' - ' . $cs->class_label . ' (' . SS_Helper::format_date( $date ) . ')';
include __DIR__ . '/partials/school_header.php';
?>
<table class="ss-print-table">
    <thead><tr><th>Roll</th><th>Adm. #</th><th>Student</th><th>Status</th><th>Signature</th></tr></thead>
    <tbody>
    <?php foreach ( $students as $s ) : $st = $existing[ (int) $s->ID ] ?? '-'; ?>
        <tr>
            <td><?php echo esc_html( $s->roll_number ); ?></td>
            <td><?php echo esc_html( $s->admission_number ); ?></td>
            <td><?php echo esc_html( trim( $s->first_name . ' ' . $s->last_name ) ); ?></td>
            <td><?php echo esc_html( ucfirst( (string) $st ) ); ?></td>
            <td>&nbsp;</td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php ss_print_close();

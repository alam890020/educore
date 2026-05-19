<?php
/**
 * Print: Class timetable / routine.
 */
defined( 'ABSPATH' ) || die();
require_once __DIR__ . '/_print_shell.php';

global $wpdb;
$cs_id = isset( $_GET['class_school_id'] ) ? (int) $_GET['class_school_id'] : 0;
$cs = $wpdb->get_row( $wpdb->prepare(
    'SELECT cs.*, c.label class_label FROM ' . SS_Helper::table( 'class_school' ) . ' cs LEFT JOIN ' . SS_Helper::table( 'classes' ) . ' c ON c.ID = cs.class_id WHERE cs.ID = %d', $cs_id
) );
if ( ! $cs ) { wp_die( esc_html__( 'Class not found', 'school-softwere' ) ); }
$school = ss_print_school( $cs->school_id );
$rows = $wpdb->get_results( $wpdb->prepare(
    'SELECT r.*, s.label subject_label, CONCAT(st.first_name," ",st.last_name) staff_name FROM ' . SS_Helper::table( 'routines' ) . ' r LEFT JOIN ' . SS_Helper::table( 'subjects' ) . ' s ON s.ID = r.subject_id LEFT JOIN ' . SS_Helper::table( 'staff' ) . ' st ON st.ID = r.staff_id WHERE r.class_school_id = %d ORDER BY FIELD(r.day,"monday","tuesday","wednesday","thursday","friday","saturday","sunday"), r.start_time ASC',
    $cs_id
) );

ss_print_open( __( 'Class Timetable', 'school-softwere' ) );
$title = __( 'Class Routine', 'school-softwere' ) . ' - ' . $cs->class_label;
include __DIR__ . '/partials/school_header.php';
?>
<table class="ss-print-table">
    <thead><tr><th>Day</th><th>Time</th><th>Subject</th><th>Teacher</th></tr></thead>
    <tbody>
    <?php foreach ( $rows as $r ) : ?>
        <tr>
            <td><?php echo esc_html( ucfirst( (string) $r->day ) ); ?></td>
            <td><?php echo esc_html( $r->start_time . ' - ' . $r->end_time ); ?></td>
            <td><?php echo esc_html( $r->subject_label ); ?></td>
            <td><?php echo esc_html( $r->staff_name ); ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php ss_print_close();

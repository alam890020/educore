<?php
/**
 * Print: Fee structure for a class.
 */
defined( 'ABSPATH' ) || die();
require_once __DIR__ . '/_print_shell.php';

global $wpdb;
$cs_id = isset( $_GET['class_school_id'] ) ? (int) $_GET['class_school_id'] : 0;
$cs = $wpdb->get_row( $wpdb->prepare(
    'SELECT cs.*, c.label class_label FROM ' . SS_Helper::table( 'class_school' ) . ' cs LEFT JOIN ' . SS_Helper::table( 'classes' ) . ' c ON c.ID = cs.class_id WHERE cs.ID = %d', $cs_id
) );
if ( ! $cs ) { wp_die( esc_html__( 'Not found', 'school-softwere' ) ); }
$school = ss_print_school( $cs->school_id );
$fees = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . SS_Helper::table( 'fees' ) . ' WHERE class_school_id = %d', $cs_id ) );

ss_print_open( __( 'Fee Structure', 'school-softwere' ) );
$title = __( 'Fee Structure', 'school-softwere' ) . ' - ' . $cs->class_label;
include __DIR__ . '/partials/school_header.php';
$total = 0;
?>
<table class="ss-print-table">
    <thead><tr><th>Fee Head</th><th>Frequency</th><th style="text-align:right">Amount</th></tr></thead>
    <tbody>
    <?php foreach ( $fees as $f ) : $total += (float) $f->amount; ?>
        <tr>
            <td><strong><?php echo esc_html( $f->label ); ?></strong></td>
            <td><?php echo esc_html( $f->frequency ?: ( $f->is_recurring ? 'Recurring' : 'One-Time' ) ); ?></td>
            <td style="text-align:right"><?php echo esc_html( SS_Helper::format_money( $f->amount ) ); ?></td>
        </tr>
    <?php endforeach; ?>
        <tr style="background:#F8FAFF; font-weight:700;">
            <td colspan="2" style="text-align:right">Total</td>
            <td style="text-align:right"><?php echo esc_html( SS_Helper::format_money( $total ) ); ?></td>
        </tr>
    </tbody>
</table>
<?php ss_print_close();

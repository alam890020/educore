<?php
/**
 * Print: Payment receipt.
 */
defined( 'ABSPATH' ) || die();
require_once __DIR__ . '/_print_shell.php';

global $wpdb;
$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
$p = $wpdb->get_row( $wpdb->prepare(
    'SELECT p.*, i.invoice_number, i.school_id, sr.first_name, sr.last_name, sr.admission_number FROM ' . SS_Helper::table( 'payments' ) . ' p LEFT JOIN ' . SS_Helper::table( 'invoices' ) . ' i ON i.ID = p.invoice_id LEFT JOIN ' . SS_Helper::table( 'student_records' ) . ' sr ON sr.ID = i.student_record_id WHERE p.ID = %d', $id
) );
if ( ! $p ) { wp_die( esc_html__( 'Payment not found', 'school-softwere' ) ); }
$school = ss_print_school( $p->school_id );

ss_print_open( __( 'Payment Receipt', 'school-softwere' ) );
$title = __( 'Payment Receipt', 'school-softwere' );
include __DIR__ . '/partials/school_header.php';
?>
<div class="ss-print-row"><span class="label">Receipt #:</span> <strong>RCPT-<?php echo (int) $p->ID; ?></strong></div>
<div class="ss-print-row"><span class="label">Invoice:</span> <?php echo esc_html( $p->invoice_number ); ?></div>
<div class="ss-print-row"><span class="label">Student:</span> <?php echo esc_html( trim( $p->first_name . ' ' . $p->last_name ) ); ?> (<?php echo esc_html( $p->admission_number ); ?>)</div>
<div class="ss-print-row"><span class="label">Paid On:</span> <?php echo esc_html( SS_Helper::format_date( $p->payment_date ) ); ?></div>
<div class="ss-print-row"><span class="label">Method:</span> <?php echo esc_html( ucfirst( str_replace( '_', ' ', (string) $p->payment_method ) ) ); ?></div>
<div class="ss-print-row"><span class="label">Note:</span> <?php echo esc_html( $p->note ); ?></div>
<div style="text-align:center; padding:24px; background:#ECFDF5; border-radius:12px; margin:24px 0;"><span style="font-family:Nunito,sans-serif; font-weight:800; font-size:32px; color:#047857;"><?php echo esc_html( SS_Helper::format_money( $p->amount ) ); ?></span><br><small style="color:#64748B;">Amount Paid</small></div>
<div style="margin-top:60px; display:flex; justify-content:space-between;"><div style="border-top:1px solid #E0E7FF; width:200px; padding-top:6px; font-size:12px; text-align:center;">Receiver</div><div style="border-top:1px solid #E0E7FF; width:200px; padding-top:6px; font-size:12px; text-align:center;">Cashier</div></div>
<?php ss_print_close();

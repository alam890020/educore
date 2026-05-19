<?php
/**
 * Print: Invoice.
 */
defined( 'ABSPATH' ) || die();
require_once __DIR__ . '/_print_shell.php';

global $wpdb;
$id  = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
$inv = $wpdb->get_row( $wpdb->prepare( 'SELECT i.*, sr.first_name, sr.last_name, sr.admission_number, c.label class_label FROM ' . SS_Helper::table( 'invoices' ) . ' i LEFT JOIN ' . SS_Helper::table( 'student_records' ) . ' sr ON sr.ID = i.student_record_id LEFT JOIN ' . SS_Helper::table( 'class_school' ) . ' cs ON cs.ID = i.class_school_id LEFT JOIN ' . SS_Helper::table( 'classes' ) . ' c ON c.ID = cs.class_id WHERE i.ID = %d', $id ) );
if ( ! $inv ) { wp_die( esc_html__( 'Invoice not found', 'school-softwere' ) ); }
$school = ss_print_school( $inv->school_id );
$payments = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . SS_Helper::table( 'payments' ) . ' WHERE invoice_id = %d ORDER BY ID DESC', $id ) );

ss_print_open( __( 'Invoice', 'school-softwere' ) );
$title = __( 'Fee Invoice', 'school-softwere' );
include __DIR__ . '/partials/school_header.php';
?>
<div style="display:flex; justify-content:space-between; margin-bottom:16px;">
    <div>
        <div class="ss-print-row"><span class="label"><?php esc_html_e( 'Student', 'school-softwere' ); ?>:</span> <strong><?php echo esc_html( trim( $inv->first_name . ' ' . $inv->last_name ) ); ?></strong></div>
        <div class="ss-print-row"><span class="label"><?php esc_html_e( 'Adm. #', 'school-softwere' ); ?>:</span> <?php echo esc_html( $inv->admission_number ); ?></div>
        <div class="ss-print-row"><span class="label"><?php esc_html_e( 'Class', 'school-softwere' ); ?>:</span> <?php echo esc_html( $inv->class_label ); ?></div>
    </div>
    <div>
        <div class="ss-print-row"><span class="label"><?php esc_html_e( 'Invoice #', 'school-softwere' ); ?>:</span> <strong><?php echo esc_html( $inv->invoice_number ); ?></strong></div>
        <div class="ss-print-row"><span class="label"><?php esc_html_e( 'Issued', 'school-softwere' ); ?>:</span> <?php echo esc_html( SS_Helper::format_date( $inv->created_at ) ); ?></div>
        <div class="ss-print-row"><span class="label"><?php esc_html_e( 'Due Date', 'school-softwere' ); ?>:</span> <?php echo esc_html( SS_Helper::format_date( $inv->due_date ) ); ?></div>
    </div>
</div>

<table class="ss-print-table" style="margin-bottom:16px;">
    <thead>
        <tr>
            <th><?php esc_html_e( 'Description', 'school-softwere' ); ?></th>
            <th style="width:120px; text-align:right;"><?php esc_html_e( 'Amount', 'school-softwere' ); ?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><?php esc_html_e( 'Total Fee', 'school-softwere' ); ?></td>
            <td style="text-align:right;"><strong><?php echo esc_html( SS_Helper::format_money( $inv->total_amount ) ); ?></strong></td>
        </tr>
        <tr>
            <td><?php esc_html_e( 'Paid', 'school-softwere' ); ?></td>
            <td style="text-align:right; color:#10B981;"><?php echo esc_html( SS_Helper::format_money( $inv->paid_amount ) ); ?></td>
        </tr>
        <tr>
            <td><strong><?php esc_html_e( 'Due', 'school-softwere' ); ?></strong></td>
            <td style="text-align:right;"><strong style="color:#EF4444;"><?php echo esc_html( SS_Helper::format_money( $inv->due_amount ) ); ?></strong></td>
        </tr>
    </tbody>
</table>

<?php if ( $payments ) : ?>
    <h3 style="font-family:Nunito,sans-serif; font-size:14px; margin:18px 0 6px;"><?php esc_html_e( 'Payment History', 'school-softwere' ); ?></h3>
    <table class="ss-print-table">
        <thead><tr><th><?php esc_html_e( 'Date', 'school-softwere' ); ?></th><th><?php esc_html_e( 'Method', 'school-softwere' ); ?></th><th><?php esc_html_e( 'Note', 'school-softwere' ); ?></th><th style="text-align:right"><?php esc_html_e( 'Amount', 'school-softwere' ); ?></th></tr></thead>
        <tbody>
        <?php foreach ( $payments as $p ) : ?>
            <tr>
                <td><?php echo esc_html( SS_Helper::format_date( $p->payment_date ) ); ?></td>
                <td><?php echo esc_html( ucfirst( str_replace( '_', ' ', (string) $p->payment_method ) ) ); ?></td>
                <td><?php echo esc_html( $p->note ); ?></td>
                <td style="text-align:right"><?php echo esc_html( SS_Helper::format_money( $p->amount ) ); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<div style="display:flex; justify-content:space-between; margin-top:60px; font-size:12px; color:#64748B;">
    <div style="border-top:1px solid #E0E7FF; padding-top:6px; width:200px; text-align:center;"><?php esc_html_e( 'Receiver Sign', 'school-softwere' ); ?></div>
    <div class="ss-qr"><?php esc_html_e( 'QR', 'school-softwere' ); ?></div>
    <div style="border-top:1px solid #E0E7FF; padding-top:6px; width:200px; text-align:center;"><?php esc_html_e( 'Authorized Sign', 'school-softwere' ); ?></div>
</div>
<?php ss_print_close();

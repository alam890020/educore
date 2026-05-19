<?php
/**
 * Print: Income report.
 */
defined( 'ABSPATH' ) || die();
require_once __DIR__ . '/_print_shell.php';

global $wpdb;
$school_id = SS_Helper::active_school_id();
$from = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : date( 'Y-m-01' );
$to   = isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : date( 'Y-m-t' );
$school = ss_print_school( $school_id );
$rows = $wpdb->get_results( $wpdb->prepare(
    'SELECT i.*, c.label cat_label FROM ' . SS_Helper::table( 'income' ) . ' i LEFT JOIN ' . SS_Helper::table( 'income_categories' ) . ' c ON c.ID = i.income_category_id WHERE i.school_id = %d AND i.date BETWEEN %s AND %s ORDER BY i.date DESC',
    $school_id, $from, $to
) );
$total = 0;
foreach ( $rows as $r ) { $total += (float) $r->amount; }

ss_print_open( __( 'Income Report', 'school-softwere' ) );
$title = __( 'Income Report', 'school-softwere' ) . ' (' . SS_Helper::format_date( $from ) . ' - ' . SS_Helper::format_date( $to ) . ')';
include __DIR__ . '/partials/school_header.php';
?>
<table class="ss-print-table">
    <thead><tr><th>Date</th><th>Label</th><th>Category</th><th style="text-align:right">Amount</th></tr></thead>
    <tbody>
    <?php foreach ( $rows as $r ) : ?>
        <tr><td><?php echo esc_html( SS_Helper::format_date( $r->date ) ); ?></td><td><strong><?php echo esc_html( $r->label ); ?></strong></td><td><?php echo esc_html( $r->cat_label ?: '-' ); ?></td><td style="text-align:right"><?php echo esc_html( SS_Helper::format_money( $r->amount ) ); ?></td></tr>
    <?php endforeach; ?>
        <tr style="background:#F8FAFF; font-weight:700;"><td colspan="3" style="text-align:right">Total Income</td><td style="text-align:right"><?php echo esc_html( SS_Helper::format_money( $total ) ); ?></td></tr>
    </tbody>
</table>
<?php ss_print_close();

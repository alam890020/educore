<?php
/**
 * Print: Bulk invoices (page-break each).
 */
defined( 'ABSPATH' ) || die();
require_once __DIR__ . '/_print_shell.php';
global $wpdb;
$school_id = SS_Helper::active_school_id();
$ids_param = isset( $_GET['ids'] ) ? sanitize_text_field( wp_unslash( $_GET['ids'] ) ) : '';
if ( $ids_param ) {
    $ids = array_map( 'intval', explode( ',', $ids_param ) );
} else {
    $ids = $wpdb->get_col( $wpdb->prepare( 'SELECT ID FROM ' . SS_Helper::table( 'invoices' ) . ' WHERE school_id = %d AND status != "paid" ORDER BY ID DESC LIMIT 50', $school_id ) );
}
ss_print_open( __( 'Invoices', 'school-softwere' ) );
foreach ( (array) $ids as $iid ) {
    $_GET['id'] = (int) $iid;
    echo '<div style="page-break-after:always">';
    include __DIR__ . '/invoice.php';
    echo '</div>';
}
ss_print_close();

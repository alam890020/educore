<?php
/**
 * Print: Bulk admit cards for an exam.
 */
defined( 'ABSPATH' ) || die();
global $wpdb;
$exam_id = isset( $_GET['exam_id'] ) ? (int) $_GET['exam_id'] : 0;
$ids = $wpdb->get_col( $wpdb->prepare( 'SELECT ID FROM ' . SS_Helper::table( 'admit_cards' ) . ' WHERE exam_id = %d', $exam_id ) );
require_once __DIR__ . '/_print_shell.php';
ss_print_open( __( 'Admit Cards', 'school-softwere' ) );
foreach ( (array) $ids as $aid ) {
    $_GET['id'] = (int) $aid;
    echo '<div style="page-break-after:always">';
    include __DIR__ . '/admit_card.php';
    echo '</div>';
}
ss_print_close();

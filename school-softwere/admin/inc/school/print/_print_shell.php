<?php
/**
 * Print shell - opens HTML, loads CSS, exposes $school, $body_inner closures.
 *
 * Usage from any print template:
 *   require __DIR__ . '/_print_shell.php';
 *   ss_print_open( $title );
 *   // body...
 *   ss_print_close();
 */
defined( 'ABSPATH' ) || die();

if ( ! function_exists( 'ss_print_open' ) ) {
    function ss_print_open( $title = '' ) {
        $watermark = SS_Config::get( 'watermark_enabled' ) ? ' ss-watermark' : '';
        ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo esc_html( $title ); ?> - <?php echo esc_html( SS_Config::get( 'school_name' ) ); ?></title>
    <link rel="stylesheet" href="<?php echo esc_url( SS_PLUGIN_URL . 'assets/css/ss-print.css?v=' . SS_VERSION ); ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Nunito:wght@600;700;800&display=swap">
</head>
<body class="<?php echo esc_attr( $watermark ); ?>">
<div class="ss-print-wrap">
    <div class="ss-print-actions"><button class="btn" onclick="window.print()">Print</button></div>
        <?php
    }
    function ss_print_close() {
        ?>
</div>
</body>
</html>
        <?php
    }
}

if ( ! function_exists( 'ss_print_school' ) ) {
    function ss_print_school( $school_id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . SS_Helper::table( 'schools' ) . ' WHERE ID = %d', (int) $school_id ) );
    }
}

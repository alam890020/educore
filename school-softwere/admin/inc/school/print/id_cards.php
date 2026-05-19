<?php
/**
 * Print: Bulk student ID cards.
 */
defined( 'ABSPATH' ) || die();
require_once __DIR__ . '/_print_shell.php';

global $wpdb;
$cs_id = isset( $_GET['class_school_id'] ) ? (int) $_GET['class_school_id'] : 0;
$rows  = SS_M_Staff_Class::students( $cs_id );
$school = ss_print_school( SS_Helper::active_school_id() );

ss_print_open( __( 'Student ID Cards', 'school-softwere' ) );
foreach ( (array) $rows as $row ) :
    ?>
    <div class="ss-id-card">
        <div class="head"><?php echo esc_html( $school ? $school->label : 'School' ); ?></div>
        <div class="body">
            <div class="photo"><?php echo $row->photo ? '<img src="' . esc_url( $row->photo ) . '" style="width:100%;height:100%;object-fit:cover;border-radius:6px;">' : 'Photo'; ?></div>
            <div class="info">
                <b><?php echo esc_html( trim( $row->first_name . ' ' . $row->last_name ) ); ?></b>
                <div><?php echo esc_html( $row->admission_number ); ?></div>
                <div>Roll: <?php echo esc_html( $row->roll_number ); ?></div>
                <div>DOB: <?php echo esc_html( SS_Helper::format_date( $row->dob ) ); ?></div>
                <div>Phone: <?php echo esc_html( $row->phone ); ?></div>
            </div>
        </div>
    </div>
<?php endforeach; ss_print_close();

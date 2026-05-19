<?php
/**
 * Print: Staff ID card(s).
 */
defined( 'ABSPATH' ) || die();
require_once __DIR__ . '/_print_shell.php';

global $wpdb;
$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
$school_id = SS_Helper::active_school_id();
$where = $id ? $wpdb->prepare( 'WHERE s.ID = %d', $id ) : $wpdb->prepare( 'WHERE s.school_id = %d', $school_id );
$rows = $wpdb->get_results( 'SELECT s.*, r.label as role_label FROM ' . SS_Helper::table( 'staff' ) . ' s LEFT JOIN ' . SS_Helper::table( 'roles' ) . ' r ON r.ID = s.role_id ' . $where );
$school = ss_print_school( $school_id );

ss_print_open( __( 'Staff ID Cards', 'school-softwere' ) );
foreach ( (array) $rows as $row ) :
    ?>
    <div class="ss-id-card" style="background:linear-gradient(135deg,#ECFDF5,#fff);">
        <div class="head" style="background:#10B981;"><?php echo esc_html( $school ? $school->label : '' ); ?> - Staff</div>
        <div class="body">
            <div class="photo"><?php echo $row->photo ? '<img src="' . esc_url( $row->photo ) . '" style="width:100%;height:100%;object-fit:cover;border-radius:6px;">' : 'Photo'; ?></div>
            <div class="info">
                <b><?php echo esc_html( trim( $row->first_name . ' ' . $row->last_name ) ); ?></b>
                <div><?php echo esc_html( $row->designation ); ?></div>
                <div><?php echo esc_html( $row->role_label ); ?></div>
                <div>Joined: <?php echo esc_html( SS_Helper::format_date( $row->joining_date ) ); ?></div>
                <div>Phone: <?php echo esc_html( $row->phone ); ?></div>
            </div>
        </div>
    </div>
<?php endforeach; ss_print_close();

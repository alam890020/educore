<?php
/**
 * SS_Setup_Wizard - 6-step first-run setup wizard.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Setup_Wizard {

    public static function render() {
        if ( ! current_user_can( SS_CAP_SUPER ) ) {
            wp_die( esc_html__( 'No access.', 'school-softwere' ) );
        }

        global $wpdb;
        $step = isset( $_GET['step'] ) ? max( 1, min( 6, (int) $_GET['step'] ) ) : 1;

        // Process posts.
        if ( isset( $_POST['ss_action'] ) && SS_Helper::verify_nonce( 'wizard' ) ) {
            $school_id = SS_Helper::active_school_id();
            switch ( $_POST['ss_action'] ) {
                case 'wizard_school':
                    if ( $school_id ) {
                        $wpdb->update( SS_Helper::table( 'schools' ), array(
                            'label'   => sanitize_text_field( wp_unslash( $_POST['label'] ?? '' ) ),
                            'phone'   => sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ),
                            'email'   => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
                            'address' => sanitize_textarea_field( wp_unslash( $_POST['address'] ?? '' ) ),
                        ), array( 'ID' => $school_id ) );
                    }
                    wp_safe_redirect( SS_Helper::admin_url( SS_MENU_SETUP_WIZARD ) . '&step=2' ); exit;

                case 'wizard_classes':
                    $cs_tbl  = SS_Helper::table( 'class_school' );
                    $sec_tbl = SS_Helper::table( 'sections' );
                    $session_id = SS_Helper::active_session_id( $school_id );
                    $class_ids  = (array) ( $_POST['class_ids'] ?? array() );
                    foreach ( $class_ids as $cid ) {
                        $cid = (int) $cid;
                        if ( ! $cid ) { continue; }
                        $exists = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$cs_tbl} WHERE class_id = %d AND school_id = %d AND session_id = %d", $cid, $school_id, $session_id ) );
                        if ( ! $exists ) {
                            $wpdb->insert( $cs_tbl, array( 'class_id' => $cid, 'school_id' => $school_id, 'session_id' => $session_id ) );
                            $cs_id = (int) $wpdb->insert_id;
                            // Default A section.
                            $wpdb->insert( $sec_tbl, array( 'class_school_id' => $cs_id, 'label' => 'A', 'capacity' => 40, 'created_at' => current_time( 'mysql' ) ) );
                        }
                    }
                    wp_safe_redirect( SS_Helper::admin_url( SS_MENU_SETUP_WIZARD ) . '&step=3' ); exit;

                case 'wizard_session':
                    $year = (int) ( $_POST['year'] ?? date( 'Y' ) );
                    $tbl  = SS_Helper::table( 'sessions' );
                    $wpdb->update( $tbl, array( 'is_active' => 0 ), array( 'school_id' => $school_id ) );
                    $exists = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$tbl} WHERE school_id = %d AND label = %s", $school_id, $year . '-' . ( $year + 1 ) ) );
                    if ( $exists ) {
                        $wpdb->update( $tbl, array( 'is_active' => 1 ), array( 'ID' => $exists ) );
                    } else {
                        $wpdb->insert( $tbl, array(
                            'school_id'  => $school_id,
                            'label'      => $year . '-' . ( $year + 1 ),
                            'start_date' => $year . '-04-01',
                            'end_date'   => ( $year + 1 ) . '-03-31',
                            'is_active'  => 1,
                        ) );
                    }
                    wp_safe_redirect( SS_Helper::admin_url( SS_MENU_SETUP_WIZARD ) . '&step=4' ); exit;

                case 'wizard_admin':
                    // Link current user as admin to the school.
                    $tbl = SS_Helper::table( 'admins' );
                    $exists = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$tbl} WHERE school_id = %d AND user_id = %d", $school_id, get_current_user_id() ) );
                    if ( ! $exists ) {
                        $wpdb->insert( $tbl, array( 'school_id' => $school_id, 'user_id' => get_current_user_id(), 'created_at' => current_time( 'mysql' ) ) );
                    }
                    wp_safe_redirect( SS_Helper::admin_url( SS_MENU_SETUP_WIZARD ) . '&step=5' ); exit;

                case 'wizard_fees':
                    $cs_rows = $wpdb->get_results( $wpdb->prepare( 'SELECT ID FROM ' . SS_Helper::table( 'class_school' ) . ' WHERE school_id = %d', $school_id ) );
                    $tuition = (float) ( $_POST['tuition'] ?? 0 );
                    if ( $tuition > 0 ) {
                        foreach ( (array) $cs_rows as $r ) {
                            $wpdb->insert( SS_Helper::table( 'fees' ), array(
                                'class_school_id' => $r->ID,
                                'label'           => 'Tuition Fee',
                                'amount'          => $tuition,
                                'is_recurring'    => 1,
                                'frequency'       => 'monthly',
                                'created_at'      => current_time( 'mysql' ),
                            ) );
                        }
                    }
                    wp_safe_redirect( SS_Helper::admin_url( SS_MENU_SETUP_WIZARD ) . '&step=6' ); exit;

                case 'wizard_done':
                    update_option( 'ss_setup_complete', 1 );
                    wp_safe_redirect( SS_Helper::admin_url( SS_MENU_DASHBOARD ) . '&_ssseen=1' ); exit;
            }
        }

        SS_Admin_Shell::open( __( 'Setup Wizard', 'school-softwere' ), SS_MENU_SETUP_WIZARD, array(
            array( 'label' => __( 'Setup Wizard', 'school-softwere' ) ),
        ) );

        echo '<div class="ss-wizard-container">';
        echo '<div class="ss-wizard-steps">';
        $steps = array(
            1 => __( 'School Details', 'school-softwere' ),
            2 => __( 'Classes & Sections', 'school-softwere' ),
            3 => __( 'Academic Session', 'school-softwere' ),
            4 => __( 'Admin Staff', 'school-softwere' ),
            5 => __( 'Fee Structure', 'school-softwere' ),
            6 => __( 'Done', 'school-softwere' ),
        );
        foreach ( $steps as $n => $lbl ) {
            $cls = $n === $step ? 'active' : ( $n < $step ? 'done' : '' );
            echo '<div class="ss-wizard-step ' . esc_attr( $cls ) . '"><span class="num">' . (int) $n . '</span> ' . esc_html( $lbl ) . '</div>';
        }
        echo '</div>';

        if ( 1 === $step )      { self::step_school(); }
        elseif ( 2 === $step )  { self::step_classes(); }
        elseif ( 3 === $step )  { self::step_session(); }
        elseif ( 4 === $step )  { self::step_admin(); }
        elseif ( 5 === $step )  { self::step_fees(); }
        else                    { self::step_done(); }

        echo '</div>';
        SS_Admin_Shell::close();
    }

    private static function step_school() {
        global $wpdb;
        $school_id = SS_Helper::active_school_id();
        $school = $school_id ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . SS_Helper::table( 'schools' ) . ' WHERE ID = %d', $school_id ) ) : null;
        SS_Admin_Shell::card_open( __( 'Step 1 - School Details', 'school-softwere' ) );
        echo '<form method="post" class="ss-form">';
        SS_Helper::nonce_field( 'wizard' );
        echo '<input type="hidden" name="ss_action" value="wizard_school">';
        SS_School::field( 'label',   __( 'School Name', 'school-softwere' ), $school ? $school->label : '', true );
        SS_School::field( 'phone',   __( 'Phone', 'school-softwere' ),       $school ? $school->phone : '' );
        SS_School::field( 'email',   __( 'Email', 'school-softwere' ),       $school ? $school->email : '', false, 'email' );
        SS_School::textarea( 'address', __( 'Address', 'school-softwere' ),  $school ? $school->address : '' );
        echo '<div class="ss-form-actions"><button class="ss-btn">' . esc_html__( 'Next: Classes', 'school-softwere' ) . ' <i class="ph ph-arrow-right"></i></button></div></form>';
        SS_Admin_Shell::card_close();
    }

    private static function step_classes() {
        global $wpdb;
        $school_id = SS_Helper::active_school_id();
        $classes   = $wpdb->get_results( 'SELECT * FROM ' . SS_Helper::table( 'classes' ) . ' ORDER BY ID ASC' );
        $linked    = $wpdb->get_col( $wpdb->prepare( 'SELECT class_id FROM ' . SS_Helper::table( 'class_school' ) . ' WHERE school_id = %d', $school_id ) );
        $linked    = array_map( 'intval', (array) $linked );

        SS_Admin_Shell::card_open( __( 'Step 2 - Classes & Sections', 'school-softwere' ) );
        echo '<p class="ss-text-muted">' . esc_html__( 'Select classes to add to your school. A default Section "A" will be created for each.', 'school-softwere' ) . '</p>';
        echo '<form method="post" class="ss-form">';
        SS_Helper::nonce_field( 'wizard' );
        echo '<input type="hidden" name="ss_action" value="wizard_classes">';
        echo '<div class="ss-form-grid">';
        foreach ( $classes as $c ) {
            $checked = in_array( (int) $c->ID, $linked, true ) ? ' checked disabled' : ' checked';
            echo '<div class="ss-field"><label><input type="checkbox" name="class_ids[]" value="' . (int) $c->ID . '"' . $checked . '> ' . esc_html( $c->label ) . '</label></div>';
        }
        echo '</div>';
        echo '<div class="ss-form-actions"><button class="ss-btn">' . esc_html__( 'Next: Session', 'school-softwere' ) . ' <i class="ph ph-arrow-right"></i></button></div></form>';
        SS_Admin_Shell::card_close();
    }

    private static function step_session() {
        SS_Admin_Shell::card_open( __( 'Step 3 - Academic Session', 'school-softwere' ) );
        echo '<form method="post" class="ss-form">';
        SS_Helper::nonce_field( 'wizard' );
        echo '<input type="hidden" name="ss_action" value="wizard_session">';
        SS_School::field( 'year', __( 'Start Year (e.g. 2025)', 'school-softwere' ), date( 'Y' ), true, 'number' );
        echo '<p class="ss-text-muted"><i class="ph ph-info"></i> ' . esc_html__( 'A session like "2025-2026" will be created and set active.', 'school-softwere' ) . '</p>';
        echo '<div class="ss-form-actions"><button class="ss-btn">' . esc_html__( 'Next: Admin', 'school-softwere' ) . ' <i class="ph ph-arrow-right"></i></button></div></form>';
        SS_Admin_Shell::card_close();
    }

    private static function step_admin() {
        $u = wp_get_current_user();
        SS_Admin_Shell::card_open( __( 'Step 4 - Admin Staff', 'school-softwere' ) );
        echo '<form method="post" class="ss-form">';
        SS_Helper::nonce_field( 'wizard' );
        echo '<input type="hidden" name="ss_action" value="wizard_admin">';
        echo '<p>' . esc_html__( 'Assign yourself as school admin:', 'school-softwere' ) . '</p>';
        echo '<div class="ss-card" style="background:var(--ss-surface-2)"><div class="ss-card-body" style="display:flex; gap:12px; align-items:center;"><div class="ss-avatar">' . esc_html( strtoupper( substr( $u->display_name, 0, 1 ) ) ) . '</div><div><strong>' . esc_html( $u->display_name ) . '</strong><br><small class="ss-text-muted">' . esc_html( $u->user_email ) . '</small></div></div></div>';
        echo '<div class="ss-form-actions"><button class="ss-btn">' . esc_html__( 'Next: Fee Structure', 'school-softwere' ) . ' <i class="ph ph-arrow-right"></i></button></div></form>';
        SS_Admin_Shell::card_close();
    }

    private static function step_fees() {
        SS_Admin_Shell::card_open( __( 'Step 5 - Fee Structure', 'school-softwere' ) );
        echo '<form method="post" class="ss-form">';
        SS_Helper::nonce_field( 'wizard' );
        echo '<input type="hidden" name="ss_action" value="wizard_fees">';
        SS_School::field( 'tuition', __( 'Default Monthly Tuition Fee', 'school-softwere' ), 100, false, 'number' );
        echo '<p class="ss-text-muted">' . esc_html__( 'A monthly tuition fee will be added to every class. You can refine fees later.', 'school-softwere' ) . '</p>';
        echo '<div class="ss-form-actions"><button class="ss-btn">' . esc_html__( 'Finish Setup', 'school-softwere' ) . ' <i class="ph ph-check"></i></button></div></form>';
        SS_Admin_Shell::card_close();
    }

    private static function step_done() {
        SS_Admin_Shell::card_open( __( 'Step 6 - All Set!', 'school-softwere' ) );
        echo '<div class="ss-empty"><i class="ph-fill ph-check-circle" style="color:var(--ss-success)"></i><h3>' . esc_html__( 'Setup Complete', 'school-softwere' ) . '</h3><p>' . esc_html__( 'Your school is configured. Click below to go to your dashboard.', 'school-softwere' ) . '</p></div>';
        echo '<form method="post" class="ss-form" style="text-align:center">';
        SS_Helper::nonce_field( 'wizard' );
        echo '<input type="hidden" name="ss_action" value="wizard_done">';
        echo '<button class="ss-btn"><i class="ph ph-house"></i> ' . esc_html__( 'Go to Dashboard', 'school-softwere' ) . '</button></form>';
        SS_Admin_Shell::card_close();
    }
}

<?php
/**
 * SS_Staff_Activities - Extracurricular activities.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Staff_Activities {

    public static function render() {
        global $wpdb;
        $school_id = SS_Helper::active_school_id();
        $tbl = SS_Helper::table( 'activities' );

        if ( isset( $_POST['ss_action'] ) && 'save_activity' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'save_activity' ) ) {
            $wpdb->insert( $tbl, array(
                'class_school_id' => (int) ( $_POST['class_school_id'] ?? 0 ),
                'section_id'      => (int) ( $_POST['section_id'] ?? 0 ) ?: null,
                'title'           => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
                'description'     => wp_kses_post( wp_unslash( $_POST['description'] ?? '' ) ),
                'date'            => sanitize_text_field( wp_unslash( $_POST['date'] ?? current_time( 'Y-m-d' ) ) ),
                'created_at'      => current_time( 'mysql' ),
            ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-activities' ) ); exit;
        }
        if ( 'delete' === ( $_GET['view'] ?? '' ) && SS_Helper::verify_nonce( 'delete_activity' ) ) {
            $wpdb->delete( $tbl, array( 'ID' => (int) $_GET['id'] ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-activities' ) ); exit;
        }

        SS_Admin_Shell::open( __( 'Activities', 'school-softwere' ), 'school-softwere-activities', array(
            array( 'label' => __( 'Activities', 'school-softwere' ) ),
        ) );

        $cs       = SS_Staff_Accountant::class_schools_for( $school_id );
        $sections = $wpdb->get_results( 'SELECT * FROM ' . SS_Helper::table( 'sections' ) . ' ORDER BY label' );

        echo '<div class="ss-row"><div class="ss-col" style="flex:1">';
        SS_Admin_Shell::card_open( __( 'Add Activity', 'school-softwere' ) );
        echo '<form method="post" class="ss-form">';
        SS_Helper::nonce_field( 'save_activity' );
        echo '<input type="hidden" name="ss_action" value="save_activity">';
        SS_School::select( 'class_school_id', __( 'Class', 'school-softwere' ), 0, $cs, true );
        SS_School::select( 'section_id', __( 'Section', 'school-softwere' ), 0, $sections );
        SS_School::field( 'title', __( 'Title', 'school-softwere' ), '', true );
        SS_School::textarea( 'description', __( 'Description', 'school-softwere' ), '' );
        echo '<div class="ss-field"><label>' . esc_html__( 'Date', 'school-softwere' ) . '</label><input class="ss-date" type="text" name="date" value="' . esc_attr( current_time( 'Y-m-d' ) ) . '"></div>';
        echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-sparkle"></i> ' . esc_html__( 'Add', 'school-softwere' ) . '</button></div></form>';
        SS_Admin_Shell::card_close();
        echo '</div><div class="ss-col" style="flex:2">';
        SS_Admin_Shell::card_open( __( 'All Activities', 'school-softwere' ) );
        $rows = $wpdb->get_results( $wpdb->prepare(
            'SELECT a.*, c.label class_label FROM ' . $tbl . ' a LEFT JOIN ' . SS_Helper::table( 'class_school' ) . ' cs ON cs.ID = a.class_school_id LEFT JOIN ' . SS_Helper::table( 'classes' ) . ' c ON c.ID = cs.class_id WHERE cs.school_id = %d ORDER BY a.date DESC LIMIT 200', $school_id
        ) );
        if ( empty( $rows ) ) {
            echo '<div class="ss-empty"><i class="ph ph-sparkle"></i><h3>' . esc_html__( 'No activities', 'school-softwere' ) . '</h3></div>';
        } else {
            echo '<div class="ss-table-wrap"><table class="ss-datatable ss-table"><thead><tr><th>' . esc_html__( 'Date', 'school-softwere' ) . '</th><th>' . esc_html__( 'Title', 'school-softwere' ) . '</th><th>' . esc_html__( 'Class', 'school-softwere' ) . '</th><th></th></tr></thead><tbody>';
            foreach ( $rows as $r ) {
                $del = wp_nonce_url( SS_Helper::admin_url( 'school-softwere-activities' ) . '&view=delete&id=' . $r->ID, 'delete_activity', '_ssnonce' );
                echo '<tr><td>' . esc_html( SS_Helper::format_date( $r->date ) ) . '</td><td><strong>' . esc_html( $r->title ) . '</strong></td><td>' . esc_html( $r->class_label ) . '</td><td class="ss-text-right"><a class="ss-btn ss-btn-danger ss-btn-sm ss-btn-icon ss-confirm-delete" href="' . esc_url( $del ) . '"><i class="ph ph-trash"></i></a></td></tr>';
            }
            echo '</tbody></table></div>';
        }
        SS_Admin_Shell::card_close();
        echo '</div></div>';
        SS_Admin_Shell::close();
    }
}

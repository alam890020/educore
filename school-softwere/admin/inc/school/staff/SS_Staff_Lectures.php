<?php
/**
 * SS_Staff_Lectures - Chapters & video lectures.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Staff_Lectures {

    public static function render() {
        global $wpdb;
        $school_id = SS_Helper::active_school_id();
        $tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'chapters';

        if ( isset( $_POST['ss_action'] ) && 'save_chapter' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'save_chapter' ) ) {
            $wpdb->insert( SS_Helper::table( 'chapter' ), array(
                'subject_id'      => (int) ( $_POST['subject_id'] ?? 0 ),
                'class_school_id' => (int) ( $_POST['class_school_id'] ?? 0 ),
                'label'           => sanitize_text_field( wp_unslash( $_POST['label'] ?? '' ) ),
                'description'     => sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ),
                'order'           => (int) ( $_POST['order'] ?? 0 ),
                'created_at'      => current_time( 'mysql' ),
            ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-lectures' ) ); exit;
        }
        if ( 'delete_chapter' === ( $_GET['view'] ?? '' ) && SS_Helper::verify_nonce( 'delete_chapter' ) ) {
            $wpdb->delete( SS_Helper::table( 'chapter' ), array( 'ID' => (int) $_GET['id'] ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-lectures' ) ); exit;
        }
        if ( isset( $_POST['ss_action'] ) && 'save_lecture' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'save_lecture' ) ) {
            $wpdb->insert( SS_Helper::table( 'lecture' ), array(
                'chapter_id'  => (int) ( $_POST['chapter_id'] ?? 0 ),
                'staff_id'    => (int) ( $_POST['staff_id'] ?? 0 ) ?: null,
                'title'       => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
                'description' => wp_kses_post( wp_unslash( $_POST['description'] ?? '' ) ),
                'video_url'   => esc_url_raw( wp_unslash( $_POST['video_url'] ?? '' ) ),
                'attachment'  => esc_url_raw( wp_unslash( $_POST['attachment'] ?? '' ) ),
                'created_at'  => current_time( 'mysql' ),
            ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-lectures' ) . '&tab=lectures' ); exit;
        }
        if ( 'delete_lecture' === ( $_GET['view'] ?? '' ) && SS_Helper::verify_nonce( 'delete_lecture' ) ) {
            $wpdb->delete( SS_Helper::table( 'lecture' ), array( 'ID' => (int) $_GET['id'] ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-lectures' ) . '&tab=lectures' ); exit;
        }

        SS_Admin_Shell::open( __( 'Lectures', 'school-softwere' ), 'school-softwere-lectures', array(
            array( 'label' => __( 'Lectures', 'school-softwere' ) ),
        ) );
        echo '<div class="ss-tabs" style="margin-bottom:16px;">';
        $base = SS_Helper::admin_url( 'school-softwere-lectures' );
        echo '<a class="ss-tab ' . ( 'chapters' === $tab ? 'active' : '' ) . '" href="' . esc_url( $base ) . '">' . esc_html__( 'Chapters', 'school-softwere' ) . '</a>';
        echo '<a class="ss-tab ' . ( 'lectures' === $tab ? 'active' : '' ) . '" href="' . esc_url( $base . '&tab=lectures' ) . '">' . esc_html__( 'Lectures', 'school-softwere' ) . '</a>';
        echo '</div>';

        $cs = SS_Staff_Accountant::class_schools_for( $school_id );
        $subjects = $wpdb->get_results( 'SELECT * FROM ' . SS_Helper::table( 'subjects' ) . ' ORDER BY label' );
        $chapters = $wpdb->get_results( 'SELECT * FROM ' . SS_Helper::table( 'chapter' ) . ' ORDER BY label' );
        $staff    = $wpdb->get_results( $wpdb->prepare( 'SELECT ID, CONCAT(first_name," ",last_name) as label FROM ' . SS_Helper::table( 'staff' ) . ' WHERE school_id = %d ORDER BY first_name', $school_id ) );

        if ( 'lectures' === $tab ) {
            echo '<div class="ss-row"><div class="ss-col" style="flex:1">';
            SS_Admin_Shell::card_open( __( 'Add Lecture', 'school-softwere' ) );
            echo '<form method="post" class="ss-form">';
            SS_Helper::nonce_field( 'save_lecture' );
            echo '<input type="hidden" name="ss_action" value="save_lecture">';
            SS_School::select( 'chapter_id', __( 'Chapter', 'school-softwere' ), 0, $chapters, true );
            SS_School::select( 'staff_id', __( 'Teacher', 'school-softwere' ), 0, $staff );
            SS_School::field( 'title', __( 'Title', 'school-softwere' ), '', true );
            SS_School::textarea( 'description', __( 'Description', 'school-softwere' ), '' );
            SS_School::field( 'video_url', __( 'Video URL (YouTube/Vimeo)', 'school-softwere' ), '' );
            SS_School::field( 'attachment', __( 'Attachment URL', 'school-softwere' ), '' );
            echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-video"></i> ' . esc_html__( 'Save', 'school-softwere' ) . '</button></div></form>';
            SS_Admin_Shell::card_close();
            echo '</div><div class="ss-col" style="flex:2">';
            SS_Admin_Shell::card_open( __( 'All Lectures', 'school-softwere' ) );
            $rows = $wpdb->get_results( 'SELECT l.*, ch.label chapter_label FROM ' . SS_Helper::table( 'lecture' ) . ' l LEFT JOIN ' . SS_Helper::table( 'chapter' ) . ' ch ON ch.ID = l.chapter_id ORDER BY l.ID DESC LIMIT 200' );
            if ( empty( $rows ) ) {
                echo '<div class="ss-empty"><i class="ph ph-video"></i><h3>' . esc_html__( 'No lectures', 'school-softwere' ) . '</h3></div>';
            } else {
                echo '<div class="ss-table-wrap"><table class="ss-datatable ss-table"><thead><tr><th>' . esc_html__( 'Title', 'school-softwere' ) . '</th><th>' . esc_html__( 'Chapter', 'school-softwere' ) . '</th><th>' . esc_html__( 'Video', 'school-softwere' ) . '</th><th></th></tr></thead><tbody>';
                foreach ( $rows as $r ) {
                    $del = wp_nonce_url( SS_Helper::admin_url( 'school-softwere-lectures' ) . '&tab=lectures&view=delete_lecture&id=' . $r->ID, 'delete_lecture', '_ssnonce' );
                    echo '<tr><td><strong>' . esc_html( $r->title ) . '</strong></td><td>' . esc_html( $r->chapter_label ) . '</td><td>' . ( $r->video_url ? '<a target="_blank" href="' . esc_url( $r->video_url ) . '"><i class="ph ph-play-circle"></i></a>' : '-' ) . '</td><td class="ss-text-right"><a class="ss-btn ss-btn-danger ss-btn-sm ss-btn-icon ss-confirm-delete" href="' . esc_url( $del ) . '"><i class="ph ph-trash"></i></a></td></tr>';
                }
                echo '</tbody></table></div>';
            }
            SS_Admin_Shell::card_close();
            echo '</div></div>';
        } else {
            echo '<div class="ss-row"><div class="ss-col" style="flex:1">';
            SS_Admin_Shell::card_open( __( 'Add Chapter', 'school-softwere' ) );
            echo '<form method="post" class="ss-form">';
            SS_Helper::nonce_field( 'save_chapter' );
            echo '<input type="hidden" name="ss_action" value="save_chapter">';
            SS_School::select( 'class_school_id', __( 'Class', 'school-softwere' ), 0, $cs, true );
            SS_School::select( 'subject_id', __( 'Subject', 'school-softwere' ), 0, $subjects, true );
            SS_School::field( 'label', __( 'Chapter Title', 'school-softwere' ), '', true );
            SS_School::field( 'order', __( 'Order', 'school-softwere' ), 0, false, 'number' );
            SS_School::textarea( 'description', __( 'Description', 'school-softwere' ), '' );
            echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-bookmark"></i> ' . esc_html__( 'Save', 'school-softwere' ) . '</button></div></form>';
            SS_Admin_Shell::card_close();
            echo '</div><div class="ss-col" style="flex:2">';
            SS_Admin_Shell::card_open( __( 'All Chapters', 'school-softwere' ) );
            $rows = $wpdb->get_results( $wpdb->prepare(
                'SELECT ch.*, s.label subject_label FROM ' . SS_Helper::table( 'chapter' ) . ' ch
                 LEFT JOIN ' . SS_Helper::table( 'subjects' ) . ' s ON s.ID = ch.subject_id
                 LEFT JOIN ' . SS_Helper::table( 'class_school' ) . ' cs ON cs.ID = ch.class_school_id
                 WHERE cs.school_id = %d ORDER BY ch.`order` ASC, ch.ID DESC LIMIT 200', $school_id
            ) );
            if ( empty( $rows ) ) {
                echo '<div class="ss-empty"><i class="ph ph-bookmark"></i><h3>' . esc_html__( 'No chapters', 'school-softwere' ) . '</h3></div>';
            } else {
                echo '<div class="ss-table-wrap"><table class="ss-table"><thead><tr><th>' . esc_html__( 'Order', 'school-softwere' ) . '</th><th>' . esc_html__( 'Chapter', 'school-softwere' ) . '</th><th>' . esc_html__( 'Subject', 'school-softwere' ) . '</th><th></th></tr></thead><tbody>';
                foreach ( $rows as $r ) {
                    $del = wp_nonce_url( SS_Helper::admin_url( 'school-softwere-lectures' ) . '&view=delete_chapter&id=' . $r->ID, 'delete_chapter', '_ssnonce' );
                    echo '<tr><td>' . (int) $r->order . '</td><td><strong>' . esc_html( $r->label ) . '</strong></td><td>' . esc_html( $r->subject_label ) . '</td><td class="ss-text-right"><a class="ss-btn ss-btn-danger ss-btn-sm ss-btn-icon ss-confirm-delete" href="' . esc_url( $del ) . '"><i class="ph ph-trash"></i></a></td></tr>';
                }
                echo '</tbody></table></div>';
            }
            SS_Admin_Shell::card_close();
            echo '</div></div>';
        }
        SS_Admin_Shell::close();
    }
}

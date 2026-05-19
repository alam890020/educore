<?php
/**
 * SS_Category - School category lookup.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Category {

    public static function render() {
        if ( ! current_user_can( SS_CAP_SUPER ) ) { wp_die( esc_html__( 'No access.', 'school-softwere' ) ); }
        global $wpdb;
        $tbl  = SS_Helper::table( 'category' );
        $view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'list';

        if ( isset( $_POST['ss_action'] ) && 'save_category' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'save_category' ) ) {
            $id    = (int) ( $_POST['id'] ?? 0 );
            $label = sanitize_text_field( wp_unslash( $_POST['label'] ?? '' ) );
            if ( $label ) {
                if ( $id ) { $wpdb->update( $tbl, array( 'label' => $label ), array( 'ID' => $id ) ); }
                else      { $wpdb->insert( $tbl, array( 'label' => $label, 'created_at' => current_time( 'mysql' ) ) ); }
            }
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-categories' ) ); exit;
        }
        if ( 'delete' === $view && SS_Helper::verify_nonce( 'delete_category' ) ) {
            $wpdb->delete( $tbl, array( 'ID' => (int) $_GET['id'] ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-categories' ) ); exit;
        }

        SS_Admin_Shell::open( __( 'Categories', 'school-softwere' ), 'school-softwere-categories', array(
            array( 'label' => __( 'Categories', 'school-softwere' ) ),
        ) );

        $editing = null;
        if ( 'edit' === $view && ! empty( $_GET['id'] ) ) {
            $editing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tbl} WHERE ID = %d", (int) $_GET['id'] ) );
        }

        echo '<div class="ss-row">';
        echo '<div class="ss-col" style="flex:1">';
        SS_Admin_Shell::card_open( $editing ? __( 'Edit Category', 'school-softwere' ) : __( 'Add Category', 'school-softwere' ) );
        echo '<form class="ss-form" method="post">';
        SS_Helper::nonce_field( 'save_category' );
        echo '<input type="hidden" name="ss_action" value="save_category">';
        echo '<input type="hidden" name="id" value="' . ( $editing ? (int) $editing->ID : 0 ) . '">';
        SS_School::field( 'label', __( 'Category Label', 'school-softwere' ), $editing ? $editing->label : '', true );
        echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-floppy-disk"></i> ' . esc_html__( 'Save', 'school-softwere' ) . '</button></div></form>';
        SS_Admin_Shell::card_close();
        echo '</div>';

        echo '<div class="ss-col" style="flex:2">';
        SS_Admin_Shell::card_open( __( 'All Categories', 'school-softwere' ) );
        $rows = $wpdb->get_results( "SELECT * FROM {$tbl} ORDER BY ID ASC" );
        if ( empty( $rows ) ) {
            echo '<div class="ss-empty"><i class="ph ph-tag"></i><h3>' . esc_html__( 'No categories', 'school-softwere' ) . '</h3></div>';
        } else {
            echo '<div class="ss-table-wrap"><table class="ss-table"><thead><tr><th>#</th><th>' . esc_html__( 'Label', 'school-softwere' ) . '</th><th class="ss-text-right">' . esc_html__( 'Actions', 'school-softwere' ) . '</th></tr></thead><tbody>';
            foreach ( $rows as $r ) {
                $edit = SS_Helper::admin_url( 'school-softwere-categories' ) . '&view=edit&id=' . $r->ID;
                $del  = wp_nonce_url( SS_Helper::admin_url( 'school-softwere-categories' ) . '&view=delete&id=' . $r->ID, 'delete_category', '_ssnonce' );
                echo '<tr><td>' . (int) $r->ID . '</td><td>' . esc_html( $r->label ) . '</td><td class="ss-text-right"><a class="ss-btn ss-btn-secondary ss-btn-sm ss-btn-icon" href="' . esc_url( $edit ) . '"><i class="ph ph-pencil-simple"></i></a> <a class="ss-btn ss-btn-danger ss-btn-sm ss-btn-icon ss-confirm-delete" href="' . esc_url( $del ) . '"><i class="ph ph-trash"></i></a></td></tr>';
            }
            echo '</tbody></table></div>';
        }
        SS_Admin_Shell::card_close();
        echo '</div>';
        echo '</div>';
        SS_Admin_Shell::close();
    }
}

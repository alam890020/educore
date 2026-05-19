<?php
/**
 * SS_School - Schools CRUD (super-admin).
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_School {

    public static function render() {
        if ( ! current_user_can( SS_CAP_SUPER ) ) {
            wp_die( esc_html__( 'You do not have access.', 'school-softwere' ) );
        }
        $view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'list';

        // Handle POST.
        if ( isset( $_POST['ss_action'] ) && 'save_school' === $_POST['ss_action'] ) {
            self::handle_save();
        }
        // Delete.
        if ( 'delete' === $view && isset( $_GET['id'] ) && SS_Helper::verify_nonce( 'delete_school' ) ) {
            self::handle_delete( (int) $_GET['id'] );
        }

        if ( in_array( $view, array( 'add', 'edit' ), true ) ) {
            self::render_form( $view );
        } else {
            self::render_list();
        }
    }

    private static function render_list() {
        global $wpdb;
        SS_Admin_Shell::open( __( 'Schools', 'school-softwere' ), 'school-softwere-schools', array(
            array( 'label' => __( 'Schools', 'school-softwere' ) ),
        ) );

        SS_Admin_Shell::card_open( __( 'All Schools', 'school-softwere' ),
            '<a href="' . esc_url( SS_Helper::admin_url( 'school-softwere-schools' ) . '&view=add' ) . '" class="ss-btn"><i class="ph ph-plus"></i> ' . esc_html__( 'Add School', 'school-softwere' ) . '</a>'
        );

        $rows = $wpdb->get_results( 'SELECT s.*, c.label as category_label FROM ' . SS_Helper::table( 'schools' ) . ' s LEFT JOIN ' . SS_Helper::table( 'category' ) . ' c ON c.ID = s.category_id ORDER BY s.ID DESC' );
        if ( empty( $rows ) ) {
            echo '<div class="ss-empty"><i class="ph ph-buildings"></i><h3>' . esc_html__( 'No schools yet', 'school-softwere' ) . '</h3><p>' . esc_html__( 'Click "Add School" to create one.', 'school-softwere' ) . '</p></div>';
        } else {
            echo '<div class="ss-table-wrap"><table class="ss-datatable ss-table"><thead><tr><th>' . esc_html__( 'Name', 'school-softwere' ) . '</th><th>' . esc_html__( 'Category', 'school-softwere' ) . '</th><th>' . esc_html__( 'Phone', 'school-softwere' ) . '</th><th>' . esc_html__( 'Email', 'school-softwere' ) . '</th><th>' . esc_html__( 'Status', 'school-softwere' ) . '</th><th class="ss-text-right">' . esc_html__( 'Actions', 'school-softwere' ) . '</th></tr></thead><tbody>';
            foreach ( $rows as $r ) {
                $edit = SS_Helper::admin_url( 'school-softwere-schools' ) . '&view=edit&id=' . $r->ID;
                $del  = wp_nonce_url( SS_Helper::admin_url( 'school-softwere-schools' ) . '&view=delete&id=' . $r->ID, 'delete_school', '_ssnonce' );
                echo '<tr><td><strong>' . esc_html( $r->label ) . '</strong><br><small class="ss-text-muted">' . esc_html( $r->registration_number ) . '</small></td>';
                echo '<td>' . esc_html( $r->category_label ?: '-' ) . '</td>';
                echo '<td>' . esc_html( $r->phone ) . '</td>';
                echo '<td>' . esc_html( $r->email ) . '</td>';
                echo '<td>' . SS_Helper::badge( $r->is_active ? __( 'Active', 'school-softwere' ) : __( 'Inactive', 'school-softwere' ), $r->is_active ? 'success' : 'danger' ) . '</td>'; // phpcs:ignore
                echo '<td class="ss-text-right"><div class="ss-actions"><a class="ss-btn ss-btn-secondary ss-btn-sm ss-btn-icon" href="' . esc_url( $edit ) . '" title="Edit"><i class="ph ph-pencil-simple"></i></a> <a class="ss-btn ss-btn-danger ss-btn-sm ss-btn-icon ss-confirm-delete" href="' . esc_url( $del ) . '" title="Delete"><i class="ph ph-trash"></i></a></div></td>';
                echo '</tr>';
            }
            echo '</tbody></table></div>';
        }
        SS_Admin_Shell::card_close();
        SS_Admin_Shell::close();
    }

    private static function render_form( $view ) {
        global $wpdb;
        $row = (object) array(
            'ID' => 0, 'label' => '', 'phone' => '', 'email' => '', 'address' => '',
            'description' => '', 'registration_number' => '', 'category_id' => 0,
            'logo' => '', 'is_active' => 1, 'admission_prefix' => 'ADM-',
            'admission_base' => 1, 'admission_padding' => 5,
        );
        if ( 'edit' === $view && ! empty( $_GET['id'] ) ) {
            $row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . SS_Helper::table( 'schools' ) . ' WHERE ID = %d', (int) $_GET['id'] ) );
            if ( ! $row ) {
                wp_die( esc_html__( 'School not found', 'school-softwere' ) );
            }
        }

        $categories = $wpdb->get_results( 'SELECT * FROM ' . SS_Helper::table( 'category' ) . ' ORDER BY label ASC' );

        SS_Admin_Shell::open( $row->ID ? __( 'Edit School', 'school-softwere' ) : __( 'Add School', 'school-softwere' ),
            'school-softwere-schools',
            array(
                array( 'label' => __( 'Schools', 'school-softwere' ), 'url' => SS_Helper::admin_url( 'school-softwere-schools' ) ),
                array( 'label' => $row->ID ? __( 'Edit', 'school-softwere' ) : __( 'Add', 'school-softwere' ) ),
            )
        );

        SS_Admin_Shell::card_open( __( 'School Details', 'school-softwere' ) );
        echo '<form class="ss-form" method="post">';
        SS_Helper::nonce_field( 'save_school' );
        echo '<input type="hidden" name="ss_action" value="save_school">';
        echo '<input type="hidden" name="id" value="' . (int) $row->ID . '">';

        echo '<div class="ss-form-section">';
        echo '<h3 class="ss-form-section-title"><i class="ph ph-buildings"></i> ' . esc_html__( 'Basic Information', 'school-softwere' ) . '</h3>';
        echo '<div class="ss-form-grid">';
        self::field( 'label',                __( 'School Name', 'school-softwere' ),         $row->label, true );
        self::field( 'registration_number',  __( 'Registration #', 'school-softwere' ),       $row->registration_number );
        self::select( 'category_id',         __( 'Category', 'school-softwere' ),             $row->category_id, $categories );
        self::field( 'phone',                __( 'Phone', 'school-softwere' ),                $row->phone );
        self::field( 'email',                __( 'Email', 'school-softwere' ),                $row->email, false, 'email' );
        self::field( 'logo',                 __( 'Logo URL', 'school-softwere' ),             $row->logo );
        echo '</div>';
        echo '<div class="ss-form-grid" style="margin-top:14px">';
        self::textarea( 'address',           __( 'Address', 'school-softwere' ),              $row->address );
        self::textarea( 'description',       __( 'Description', 'school-softwere' ),          $row->description );
        echo '</div>';
        echo '</div>';

        echo '<div class="ss-form-section">';
        echo '<h3 class="ss-form-section-title"><i class="ph ph-identification-card"></i> ' . esc_html__( 'Admission Numbering', 'school-softwere' ) . '</h3>';
        echo '<div class="ss-form-grid">';
        self::field( 'admission_prefix',   __( 'Admission Prefix', 'school-softwere' ),  $row->admission_prefix );
        self::field( 'admission_base',     __( 'Starting Number', 'school-softwere' ),   $row->admission_base, false, 'number' );
        self::field( 'admission_padding',  __( 'Zero Padding', 'school-softwere' ),      $row->admission_padding, false, 'number' );
        self::checkbox( 'is_active',       __( 'Active', 'school-softwere' ),            (bool) $row->is_active );
        echo '</div>';
        echo '</div>';

        echo '<div class="ss-form-actions">';
        echo '<button type="submit" class="ss-btn"><i class="ph ph-floppy-disk"></i> ' . esc_html__( 'Save School', 'school-softwere' ) . '</button>';
        echo '<a href="' . esc_url( SS_Helper::admin_url( 'school-softwere-schools' ) ) . '" class="ss-btn ss-btn-ghost">' . esc_html__( 'Cancel', 'school-softwere' ) . '</a>';
        echo '</div>';
        echo '</form>';
        SS_Admin_Shell::card_close();
        SS_Admin_Shell::close();
    }

    private static function handle_save() {
        if ( ! current_user_can( SS_CAP_SUPER ) ) {
            wp_die( esc_html__( 'No permission', 'school-softwere' ) );
        }
        if ( ! SS_Helper::verify_nonce( 'save_school' ) ) {
            wp_die( esc_html__( 'Invalid request', 'school-softwere' ) );
        }
        global $wpdb;
        $id   = (int) ( $_POST['id'] ?? 0 );
        $data = array(
            'label'               => sanitize_text_field( wp_unslash( $_POST['label'] ?? '' ) ),
            'phone'               => sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ),
            'email'               => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
            'address'             => sanitize_textarea_field( wp_unslash( $_POST['address'] ?? '' ) ),
            'description'         => sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ),
            'registration_number' => sanitize_text_field( wp_unslash( $_POST['registration_number'] ?? '' ) ),
            'category_id'         => (int) ( $_POST['category_id'] ?? 0 ),
            'logo'                => esc_url_raw( wp_unslash( $_POST['logo'] ?? '' ) ),
            'is_active'           => empty( $_POST['is_active'] ) ? 0 : 1,
            'admission_prefix'    => sanitize_text_field( wp_unslash( $_POST['admission_prefix'] ?? 'ADM-' ) ),
            'admission_base'      => max( 1, (int) ( $_POST['admission_base'] ?? 1 ) ),
            'admission_padding'   => max( 1, (int) ( $_POST['admission_padding'] ?? 5 ) ),
        );
        $tbl = SS_Helper::table( 'schools' );
        if ( $id ) {
            $wpdb->update( $tbl, $data, array( 'ID' => $id ) );
        } else {
            $data['created_at'] = current_time( 'mysql' );
            $wpdb->insert( $tbl, $data );
            $id = (int) $wpdb->insert_id;
            // Create a default session and roles for the new school.
            SS_M_Role::sync_builtins( $id );
            $year = (int) date( 'Y' );
            $wpdb->insert( SS_Helper::table( 'sessions' ), array(
                'school_id' => $id, 'label' => $year . '-' . ( $year + 1 ),
                'start_date' => $year . '-04-01', 'end_date' => ( $year + 1 ) . '-03-31',
                'is_active' => 1,
            ) );
        }
        wp_safe_redirect( add_query_arg( array( 'ss_notice' => __( 'School saved', 'school-softwere' ), 'ss_notice_type' => 'success' ), SS_Helper::admin_url( 'school-softwere-schools' ) ) );
        exit;
    }

    private static function handle_delete( $id ) {
        if ( ! current_user_can( SS_CAP_SUPER ) ) {
            wp_die( esc_html__( 'No permission', 'school-softwere' ) );
        }
        global $wpdb;
        $wpdb->delete( SS_Helper::table( 'schools' ), array( 'ID' => $id ) );
        wp_safe_redirect( add_query_arg( array( 'ss_notice' => __( 'School deleted', 'school-softwere' ), 'ss_notice_type' => 'success' ), SS_Helper::admin_url( 'school-softwere-schools' ) ) );
        exit;
    }

    // ---- Form helpers (used by manager/portal modules) ----
    public static function field( $name, $label, $value = '', $required = false, $type = 'text' ) {
        echo '<div class="ss-field">';
        echo '<label>' . esc_html( $label ) . ( $required ? ' <span class="req">*</span>' : '' ) . '</label>';
        echo '<input type="' . esc_attr( $type ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" ' . ( $required ? 'required' : '' ) . '>';
        echo '</div>';
    }
    public static function textarea( $name, $label, $value = '', $required = false ) {
        echo '<div class="ss-field">';
        echo '<label>' . esc_html( $label ) . ( $required ? ' <span class="req">*</span>' : '' ) . '</label>';
        echo '<textarea name="' . esc_attr( $name ) . '"' . ( $required ? ' required' : '' ) . '>' . esc_textarea( $value ) . '</textarea>';
        echo '</div>';
    }
    public static function select( $name, $label, $selected, $options, $required = false, $key = 'ID', $val = 'label' ) {
        echo '<div class="ss-field">';
        echo '<label>' . esc_html( $label ) . ( $required ? ' <span class="req">*</span>' : '' ) . '</label>';
        echo '<select class="ss-select2" name="' . esc_attr( $name ) . '"' . ( $required ? ' required' : '' ) . '>';
        echo '<option value="">' . esc_html__( '-- Select --', 'school-softwere' ) . '</option>';
        foreach ( (array) $options as $opt ) {
            $opt = (object) $opt;
            $k   = $opt->{$key};
            $l   = $opt->{$val};
            echo '<option value="' . esc_attr( $k ) . '"' . selected( $selected, $k, false ) . '>' . esc_html( $l ) . '</option>';
        }
        echo '</select>';
        echo '</div>';
    }
    public static function checkbox( $name, $label, $checked = false ) {
        echo '<div class="ss-field">';
        echo '<label><input type="checkbox" name="' . esc_attr( $name ) . '" value="1" ' . checked( $checked, true, false ) . '> ' . esc_html( $label ) . '</label>';
        echo '</div>';
    }
}

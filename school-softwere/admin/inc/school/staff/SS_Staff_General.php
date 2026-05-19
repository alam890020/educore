<?php
/**
 * SS_Staff_General - Staff CRUD module.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Staff_General {

    public static function render() {
        global $wpdb;
        $school_id = SS_Helper::active_school_id();
        $view      = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'list';

        if ( isset( $_POST['ss_action'] ) && 'save_staff' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'save_staff' ) ) {
            self::save( $school_id );
        }
        if ( 'delete' === $view && isset( $_GET['id'] ) && SS_Helper::verify_nonce( 'delete_staff' ) ) {
            $wpdb->delete( SS_Helper::table( 'staff' ), array( 'ID' => (int) $_GET['id'], 'school_id' => $school_id ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-staff' ) ); exit;
        }

        if ( in_array( $view, array( 'add', 'edit' ), true ) ) {
            self::render_form( $school_id, $view );
        } else {
            self::render_list( $school_id );
        }
    }

    private static function render_list( $school_id ) {
        global $wpdb;
        SS_Admin_Shell::open( __( 'Staff', 'school-softwere' ), 'school-softwere-staff', array(
            array( 'label' => __( 'Staff', 'school-softwere' ) ),
        ) );
        SS_Admin_Shell::card_open( __( 'All Staff', 'school-softwere' ),
            '<a href="' . esc_url( SS_Helper::admin_url( 'school-softwere-staff' ) . '&view=add' ) . '" class="ss-btn"><i class="ph ph-user-plus"></i> ' . esc_html__( 'Add Staff', 'school-softwere' ) . '</a>'
        );
        $rows = $wpdb->get_results( $wpdb->prepare(
            'SELECT s.*, r.label as role_label FROM ' . SS_Helper::table( 'staff' ) . ' s LEFT JOIN ' . SS_Helper::table( 'roles' ) . ' r ON r.ID = s.role_id WHERE s.school_id = %d ORDER BY s.ID DESC',
            $school_id
        ) );
        if ( empty( $rows ) ) {
            echo '<div class="ss-empty"><i class="ph ph-users-three"></i><h3>' . esc_html__( 'No staff yet', 'school-softwere' ) . '</h3></div>';
        } else {
            echo '<div class="ss-table-wrap"><table class="ss-datatable ss-table"><thead><tr><th>' . esc_html__( 'Name', 'school-softwere' ) . '</th><th>' . esc_html__( 'Role', 'school-softwere' ) . '</th><th>' . esc_html__( 'Designation', 'school-softwere' ) . '</th><th>' . esc_html__( 'Phone', 'school-softwere' ) . '</th><th>' . esc_html__( 'Email', 'school-softwere' ) . '</th><th>' . esc_html__( 'Status', 'school-softwere' ) . '</th><th class="ss-text-right">' . esc_html__( 'Actions', 'school-softwere' ) . '</th></tr></thead><tbody>';
            foreach ( $rows as $r ) {
                $edit = SS_Helper::admin_url( 'school-softwere-staff' ) . '&view=edit&id=' . $r->ID;
                $del  = wp_nonce_url( SS_Helper::admin_url( 'school-softwere-staff' ) . '&view=delete&id=' . $r->ID, 'delete_staff', '_ssnonce' );
                $idc  = SS_Helper::admin_url() . '&ss_print=staff_id_cards&id=' . $r->ID;
                echo '<tr><td><strong>' . esc_html( trim( $r->first_name . ' ' . $r->last_name ) ) . '</strong></td><td>' . esc_html( $r->role_label ?: '-' ) . '</td><td>' . esc_html( $r->designation ) . '</td><td>' . esc_html( $r->phone ) . '</td><td>' . esc_html( $r->email ) . '</td><td>' . SS_Helper::badge( $r->is_active ? __( 'Active', 'school-softwere' ) : __( 'Inactive', 'school-softwere' ), $r->is_active ? 'success' : 'muted' ) . '</td>'; // phpcs:ignore
                echo '<td class="ss-text-right"><div class="ss-actions">';
                echo '<a class="ss-btn ss-btn-secondary ss-btn-sm ss-btn-icon" href="' . esc_url( $idc ) . '" target="_blank" title="ID Card"><i class="ph ph-identification-card"></i></a> ';
                echo '<a class="ss-btn ss-btn-secondary ss-btn-sm ss-btn-icon" href="' . esc_url( $edit ) . '"><i class="ph ph-pencil-simple"></i></a> ';
                echo '<a class="ss-btn ss-btn-danger ss-btn-sm ss-btn-icon ss-confirm-delete" href="' . esc_url( $del ) . '"><i class="ph ph-trash"></i></a>';
                echo '</div></td></tr>';
            }
            echo '</tbody></table></div>';
        }
        SS_Admin_Shell::card_close();
        SS_Admin_Shell::close();
    }

    private static function render_form( $school_id, $view ) {
        global $wpdb;
        $row = (object) array_fill_keys( array( 'ID', 'role_id', 'first_name', 'last_name', 'dob', 'gender', 'phone', 'email', 'address', 'photo', 'joining_date', 'designation', 'salary', 'is_active' ), '' );
        $row->ID        = 0;
        $row->is_active = 1;
        $row->gender    = 'male';

        if ( 'edit' === $view && ! empty( $_GET['id'] ) ) {
            $found = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . SS_Helper::table( 'staff' ) . ' WHERE ID = %d AND school_id = %d', (int) $_GET['id'], $school_id ) );
            if ( $found ) { $row = $found; }
        }
        $roles = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . SS_Helper::table( 'roles' ) . ' WHERE school_id = %d ORDER BY label ASC', $school_id ) );

        SS_Admin_Shell::open( $row->ID ? __( 'Edit Staff', 'school-softwere' ) : __( 'Add Staff', 'school-softwere' ),
            'school-softwere-staff',
            array(
                array( 'label' => __( 'Staff', 'school-softwere' ), 'url' => SS_Helper::admin_url( 'school-softwere-staff' ) ),
                array( 'label' => $row->ID ? __( 'Edit', 'school-softwere' ) : __( 'Add', 'school-softwere' ) ),
            )
        );

        SS_Admin_Shell::card_open( __( 'Staff Information', 'school-softwere' ) );
        echo '<form class="ss-form" method="post">';
        SS_Helper::nonce_field( 'save_staff' );
        echo '<input type="hidden" name="ss_action" value="save_staff">';
        echo '<input type="hidden" name="id" value="' . (int) $row->ID . '">';

        echo '<div class="ss-form-section"><h3 class="ss-form-section-title"><i class="ph ph-user"></i> ' . esc_html__( 'Personal', 'school-softwere' ) . '</h3><div class="ss-form-grid">';
        SS_School::field( 'first_name', __( 'First Name', 'school-softwere' ), $row->first_name, true );
        SS_School::field( 'last_name',  __( 'Last Name', 'school-softwere' ),  $row->last_name );
        echo '<div class="ss-field"><label>' . esc_html__( 'Date of Birth', 'school-softwere' ) . '</label><input class="ss-date" type="text" name="dob" value="' . esc_attr( $row->dob ) . '"></div>';
        echo '<div class="ss-field"><label>' . esc_html__( 'Gender', 'school-softwere' ) . '</label><select class="ss-select2" name="gender"><option value="male"' . selected( $row->gender, 'male', false ) . '>Male</option><option value="female"' . selected( $row->gender, 'female', false ) . '>Female</option><option value="other"' . selected( $row->gender, 'other', false ) . '>Other</option></select></div>';
        SS_School::field( 'phone', __( 'Phone', 'school-softwere' ), $row->phone );
        SS_School::field( 'email', __( 'Email', 'school-softwere' ), $row->email, false, 'email' );
        SS_School::field( 'photo', __( 'Photo URL', 'school-softwere' ), $row->photo );
        echo '</div></div>';

        echo '<div class="ss-form-section"><h3 class="ss-form-section-title"><i class="ph ph-briefcase"></i> ' . esc_html__( 'Employment', 'school-softwere' ) . '</h3><div class="ss-form-grid">';
        SS_School::select( 'role_id', __( 'Role', 'school-softwere' ), $row->role_id, $roles );
        SS_School::field( 'designation', __( 'Designation', 'school-softwere' ), $row->designation );
        echo '<div class="ss-field"><label>' . esc_html__( 'Joining Date', 'school-softwere' ) . '</label><input class="ss-date" type="text" name="joining_date" value="' . esc_attr( $row->joining_date ) . '"></div>';
        SS_School::field( 'salary', __( 'Salary', 'school-softwere' ), $row->salary, false, 'number' );
        SS_School::checkbox( 'is_active', __( 'Active', 'school-softwere' ), (bool) $row->is_active );
        echo '</div></div>';

        echo '<div class="ss-form-section"><h3 class="ss-form-section-title"><i class="ph ph-map-pin"></i> ' . esc_html__( 'Address', 'school-softwere' ) . '</h3>';
        SS_School::textarea( 'address', __( 'Address', 'school-softwere' ), $row->address );
        echo '</div>';

        echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-floppy-disk"></i> ' . esc_html__( 'Save Staff', 'school-softwere' ) . '</button> <a class="ss-btn ss-btn-ghost" href="' . esc_url( SS_Helper::admin_url( 'school-softwere-staff' ) ) . '">' . esc_html__( 'Cancel', 'school-softwere' ) . '</a></div>';
        echo '</form>';
        SS_Admin_Shell::card_close();
        SS_Admin_Shell::close();
    }

    private static function save( $school_id ) {
        global $wpdb;
        $id = (int) ( $_POST['id'] ?? 0 );
        $tbl = SS_Helper::table( 'staff' );
        $data = array(
            'school_id'    => $school_id,
            'role_id'      => (int) ( $_POST['role_id'] ?? 0 ) ?: null,
            'first_name'   => sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) ),
            'last_name'    => sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) ),
            'dob'          => sanitize_text_field( wp_unslash( $_POST['dob'] ?? '' ) ) ?: null,
            'gender'       => sanitize_text_field( wp_unslash( $_POST['gender'] ?? 'male' ) ),
            'phone'        => sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ),
            'email'        => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
            'address'      => sanitize_textarea_field( wp_unslash( $_POST['address'] ?? '' ) ),
            'photo'        => esc_url_raw( wp_unslash( $_POST['photo'] ?? '' ) ),
            'joining_date' => sanitize_text_field( wp_unslash( $_POST['joining_date'] ?? '' ) ) ?: null,
            'designation'  => sanitize_text_field( wp_unslash( $_POST['designation'] ?? '' ) ),
            'salary'       => (float) ( $_POST['salary'] ?? 0 ),
            'is_active'    => empty( $_POST['is_active'] ) ? 0 : 1,
        );
        if ( $id ) { $wpdb->update( $tbl, $data, array( 'ID' => $id ) ); }
        else      { $data['created_at'] = current_time( 'mysql' ); $wpdb->insert( $tbl, $data ); }
        wp_safe_redirect( add_query_arg( array( 'ss_notice' => __( 'Staff saved', 'school-softwere' ), 'ss_notice_type' => 'success' ), SS_Helper::admin_url( 'school-softwere-staff' ) ) );
        exit;
    }
}

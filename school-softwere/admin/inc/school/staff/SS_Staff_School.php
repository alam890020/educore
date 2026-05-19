<?php
/**
 * SS_Staff_School - Students module (full CRUD with multi-tab admission).
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Staff_School {

    public static function render() {
        global $wpdb;
        $school_id = SS_Helper::active_school_id();
        $view      = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'list';

        if ( isset( $_POST['ss_action'] ) && 'save_student' === $_POST['ss_action'] ) {
            self::handle_save( $school_id );
        }
        if ( 'delete' === $view && isset( $_GET['id'] ) && SS_Helper::verify_nonce( 'delete_student' ) ) {
            $wpdb->delete( SS_Helper::table( 'student_records' ), array( 'ID' => (int) $_GET['id'] ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-students' ) ); exit;
        }
        if ( 'view' === $view && ! empty( $_GET['id'] ) ) {
            self::render_profile( (int) $_GET['id'] );
            return;
        }
        if ( in_array( $view, array( 'add', 'edit' ), true ) ) {
            self::render_form( $school_id, $view );
        } else {
            self::render_list( $school_id );
        }
    }

    private static function render_list( $school_id ) {
        global $wpdb;
        SS_Admin_Shell::open( __( 'Students', 'school-softwere' ), 'school-softwere-students', array(
            array( 'label' => __( 'Students', 'school-softwere' ) ),
        ) );

        // Filter values.
        $cs_filter   = isset( $_GET['class_school_id'] ) ? (int) $_GET['class_school_id'] : 0;
        $sec_filter  = isset( $_GET['section_id'] ) ? (int) $_GET['section_id'] : 0;
        $search      = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

        SS_Admin_Shell::card_open( __( 'All Students', 'school-softwere' ),
            '<a href="' . esc_url( SS_Helper::admin_url( 'school-softwere-students' ) . '&view=add' ) . '" class="ss-btn"><i class="ph ph-user-plus"></i> ' . esc_html__( 'Add Student', 'school-softwere' ) . '</a>'
        );

        // Toolbar/filter.
        echo '<form method="get" class="ss-toolbar" style="margin:-20px -20px 0; border-radius:0;">';
        echo '<input type="hidden" name="page" value="school-softwere-students">';
        echo '<div class="ss-search"><i class="ph ph-magnifying-glass"></i><input type="search" name="s" placeholder="' . esc_attr__( 'Search by name or admission number...', 'school-softwere' ) . '" value="' . esc_attr( $search ) . '"></div>';
        echo '<button class="ss-btn ss-btn-secondary ss-btn-sm"><i class="ph ph-funnel"></i> ' . esc_html__( 'Filter', 'school-softwere' ) . '</button>';
        echo '</form>';

        // Build query.
        $tbl = SS_Helper::table( 'student_records' );
        $cs  = SS_Helper::table( 'class_school' );
        $c   = SS_Helper::table( 'classes' );
        $sec = SS_Helper::table( 'sections' );

        $where  = array( 'sr.school_id = %d' );
        $params = array( $school_id );
        if ( $search ) {
            $where[] = '(sr.first_name LIKE %s OR sr.last_name LIKE %s OR sr.admission_number LIKE %s)';
            $like    = '%' . $wpdb->esc_like( $search ) . '%';
            $params  = array_merge( $params, array( $like, $like, $like ) );
        }
        if ( $cs_filter ) {
            $where[] = 'sr.class_school_id = %d'; $params[] = $cs_filter;
        }
        if ( $sec_filter ) {
            $where[] = 'sr.section_id = %d'; $params[] = $sec_filter;
        }
        $where_sql = 'WHERE ' . implode( ' AND ', $where );

        $sql  = "SELECT sr.*, c.label class_label, sec.label section_label
                 FROM {$tbl} sr
                 LEFT JOIN {$cs} cs ON cs.ID = sr.class_school_id
                 LEFT JOIN {$c}  c  ON c.ID  = cs.class_id
                 LEFT JOIN {$sec} sec ON sec.ID = sr.section_id
                 {$where_sql}
                 ORDER BY sr.ID DESC LIMIT 500";
        $rows = $params ? $wpdb->get_results( $wpdb->prepare( $sql, $params ) ) : $wpdb->get_results( $sql );

        if ( empty( $rows ) ) {
            echo '<div class="ss-empty"><i class="ph ph-graduation-cap"></i><h3>' . esc_html__( 'No students yet', 'school-softwere' ) . '</h3><p>' . esc_html__( 'Add your first student to get started.', 'school-softwere' ) . '</p></div>';
        } else {
            echo '<div class="ss-table-wrap"><table class="ss-datatable ss-table"><thead><tr>';
            echo '<th>' . esc_html__( 'Adm. #', 'school-softwere' ) . '</th>';
            echo '<th>' . esc_html__( 'Name', 'school-softwere' ) . '</th>';
            echo '<th>' . esc_html__( 'Class', 'school-softwere' ) . '</th>';
            echo '<th>' . esc_html__( 'Roll', 'school-softwere' ) . '</th>';
            echo '<th>' . esc_html__( 'Gender', 'school-softwere' ) . '</th>';
            echo '<th>' . esc_html__( 'Phone', 'school-softwere' ) . '</th>';
            echo '<th>' . esc_html__( 'Status', 'school-softwere' ) . '</th>';
            echo '<th class="ss-text-right">' . esc_html__( 'Actions', 'school-softwere' ) . '</th>';
            echo '</tr></thead><tbody>';
            foreach ( $rows as $r ) {
                $view_url = SS_Helper::admin_url( 'school-softwere-students' ) . '&view=view&id=' . $r->ID;
                $edit_url = SS_Helper::admin_url( 'school-softwere-students' ) . '&view=edit&id=' . $r->ID;
                $del_url  = wp_nonce_url( SS_Helper::admin_url( 'school-softwere-students' ) . '&view=delete&id=' . $r->ID, 'delete_student', '_ssnonce' );
                $idc_url  = SS_Helper::admin_url() . '&ss_print=id_card&id=' . $r->ID;

                echo '<tr>';
                echo '<td><strong>' . esc_html( $r->admission_number ) . '</strong></td>';
                echo '<td>' . esc_html( trim( $r->first_name . ' ' . $r->last_name ) ) . '</td>';
                echo '<td>' . esc_html( $r->class_label . ( $r->section_label ? ' / ' . $r->section_label : '' ) ) . '</td>';
                echo '<td>' . esc_html( $r->roll_number ) . '</td>';
                echo '<td>' . esc_html( ucfirst( (string) $r->gender ) ) . '</td>';
                echo '<td>' . esc_html( $r->phone ) . '</td>';
                echo '<td>' . SS_Helper::badge( $r->is_active ? __( 'Active', 'school-softwere' ) : __( 'Inactive', 'school-softwere' ), $r->is_active ? 'success' : 'muted' ) . '</td>'; // phpcs:ignore
                echo '<td class="ss-text-right"><div class="ss-actions">';
                echo '<a class="ss-btn ss-btn-secondary ss-btn-sm ss-btn-icon" href="' . esc_url( $view_url ) . '" title="View"><i class="ph ph-eye"></i></a> ';
                echo '<a class="ss-btn ss-btn-secondary ss-btn-sm ss-btn-icon" href="' . esc_url( $idc_url ) . '" target="_blank" title="ID Card"><i class="ph ph-identification-card"></i></a> ';
                echo '<a class="ss-btn ss-btn-secondary ss-btn-sm ss-btn-icon" href="' . esc_url( $edit_url ) . '" title="Edit"><i class="ph ph-pencil-simple"></i></a> ';
                echo '<a class="ss-btn ss-btn-danger ss-btn-sm ss-btn-icon ss-confirm-delete" href="' . esc_url( $del_url ) . '" title="Delete"><i class="ph ph-trash"></i></a>';
                echo '</div></td>';
                echo '</tr>';
            }
            echo '</tbody></table></div>';
        }
        SS_Admin_Shell::card_close();
        SS_Admin_Shell::close();
    }

    private static function render_form( $school_id, $view ) {
        global $wpdb;
        $row = (object) array_fill_keys( array(
            'ID', 'class_school_id', 'section_id', 'admission_number', 'roll_number',
            'first_name', 'last_name', 'father_name', 'mother_name', 'guardian_name', 'guardian_relation',
            'dob', 'gender', 'blood_group', 'religion', 'caste', 'nationality',
            'address', 'city', 'state', 'zip', 'country', 'phone', 'email', 'photo',
            'admission_date', 'student_type_id', 'is_active',
        ), '' );
        $row->ID         = 0;
        $row->is_active  = 1;
        $row->gender     = 'male';

        if ( 'edit' === $view && ! empty( $_GET['id'] ) ) {
            $found = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . SS_Helper::table( 'student_records' ) . ' WHERE ID = %d AND school_id = %d', (int) $_GET['id'], $school_id ) );
            if ( $found ) {
                $row = $found;
            }
        }

        $class_schools = $wpdb->get_results( $wpdb->prepare(
            'SELECT cs.ID, c.label FROM ' . SS_Helper::table( 'class_school' ) . ' cs INNER JOIN ' . SS_Helper::table( 'classes' ) . ' c ON c.ID = cs.class_id WHERE cs.school_id = %d ORDER BY c.ID ASC',
            $school_id
        ) );
        $sections = $wpdb->get_results( 'SELECT * FROM ' . SS_Helper::table( 'sections' ) . ' ORDER BY label ASC' );
        $stypes   = $wpdb->get_results( 'SELECT * FROM ' . SS_Helper::table( 'student_type' ) . ' ORDER BY label ASC' );

        SS_Admin_Shell::open( $row->ID ? __( 'Edit Student', 'school-softwere' ) : __( 'Student Admission', 'school-softwere' ),
            'school-softwere-students',
            array(
                array( 'label' => __( 'Students', 'school-softwere' ), 'url' => SS_Helper::admin_url( 'school-softwere-students' ) ),
                array( 'label' => $row->ID ? __( 'Edit', 'school-softwere' ) : __( 'Admission', 'school-softwere' ) ),
            )
        );

        SS_Admin_Shell::card_open( $row->ID ? __( 'Update Student', 'school-softwere' ) : __( 'New Admission', 'school-softwere' ) );
        echo '<form class="ss-form" method="post">';
        SS_Helper::nonce_field( 'save_student' );
        echo '<input type="hidden" name="ss_action" value="save_student">';
        echo '<input type="hidden" name="id" value="' . (int) $row->ID . '">';

        // Tabs.
        echo '<div class="ss-tabs">';
        echo '<button type="button" class="ss-tab active" data-tab="personal">' . esc_html__( 'Personal', 'school-softwere' ) . '</button>';
        echo '<button type="button" class="ss-tab" data-tab="guardian">' . esc_html__( 'Guardian', 'school-softwere' ) . '</button>';
        echo '<button type="button" class="ss-tab" data-tab="academic">' . esc_html__( 'Academic', 'school-softwere' ) . '</button>';
        echo '<button type="button" class="ss-tab" data-tab="contact">' . esc_html__( 'Contact', 'school-softwere' ) . '</button>';
        echo '</div>';

        // Personal.
        echo '<div class="ss-tab-pane active" data-tab="personal"><div class="ss-form-grid">';
        SS_School::field( 'first_name', __( 'First Name', 'school-softwere' ), $row->first_name, true );
        SS_School::field( 'last_name',  __( 'Last Name', 'school-softwere' ),  $row->last_name );
        echo '<div class="ss-field"><label>' . esc_html__( 'Date of Birth', 'school-softwere' ) . '</label><input class="ss-date" type="text" name="dob" value="' . esc_attr( $row->dob ) . '"></div>';
        echo '<div class="ss-field"><label>' . esc_html__( 'Gender', 'school-softwere' ) . '</label><select class="ss-select2" name="gender"><option value="male"' . selected( $row->gender, 'male', false ) . '>Male</option><option value="female"' . selected( $row->gender, 'female', false ) . '>Female</option><option value="other"' . selected( $row->gender, 'other', false ) . '>Other</option></select></div>';
        SS_School::field( 'blood_group', __( 'Blood Group', 'school-softwere' ), $row->blood_group );
        SS_School::field( 'religion',    __( 'Religion', 'school-softwere' ),    $row->religion );
        SS_School::field( 'caste',       __( 'Caste', 'school-softwere' ),       $row->caste );
        SS_School::field( 'nationality', __( 'Nationality', 'school-softwere' ), $row->nationality );
        SS_School::field( 'photo',       __( 'Photo URL', 'school-softwere' ),   $row->photo );
        echo '</div></div>';

        // Guardian.
        echo '<div class="ss-tab-pane" data-tab="guardian"><div class="ss-form-grid">';
        SS_School::field( 'father_name',       __( 'Father Name', 'school-softwere' ),       $row->father_name );
        SS_School::field( 'mother_name',       __( 'Mother Name', 'school-softwere' ),       $row->mother_name );
        SS_School::field( 'guardian_name',     __( 'Guardian Name', 'school-softwere' ),     $row->guardian_name );
        SS_School::field( 'guardian_relation', __( 'Relation', 'school-softwere' ),          $row->guardian_relation );
        echo '</div></div>';

        // Academic.
        echo '<div class="ss-tab-pane" data-tab="academic"><div class="ss-form-grid">';
        SS_School::field( 'admission_number',  __( 'Admission #', 'school-softwere' ),       $row->admission_number );
        SS_School::field( 'roll_number',       __( 'Roll Number', 'school-softwere' ),       $row->roll_number );
        echo '<div class="ss-field"><label>' . esc_html__( 'Admission Date', 'school-softwere' ) . '</label><input class="ss-date" type="text" name="admission_date" value="' . esc_attr( $row->admission_date ) . '"></div>';
        SS_School::select( 'class_school_id', __( 'Class', 'school-softwere' ), $row->class_school_id, $class_schools, true );
        SS_School::select( 'section_id',       __( 'Section', 'school-softwere' ), $row->section_id, $sections );
        SS_School::select( 'student_type_id',  __( 'Student Type', 'school-softwere' ), $row->student_type_id, $stypes );
        SS_School::checkbox( 'is_active',      __( 'Active', 'school-softwere' ), (bool) $row->is_active );
        echo '</div></div>';

        // Contact.
        echo '<div class="ss-tab-pane" data-tab="contact"><div class="ss-form-grid">';
        SS_School::field( 'phone',   __( 'Phone', 'school-softwere' ),   $row->phone );
        SS_School::field( 'email',   __( 'Email', 'school-softwere' ),   $row->email, false, 'email' );
        SS_School::field( 'city',    __( 'City', 'school-softwere' ),    $row->city );
        SS_School::field( 'state',   __( 'State', 'school-softwere' ),   $row->state );
        SS_School::field( 'zip',     __( 'Zip', 'school-softwere' ),     $row->zip );
        SS_School::field( 'country', __( 'Country', 'school-softwere' ), $row->country );
        echo '</div>';
        echo '<div style="margin-top:14px">';
        SS_School::textarea( 'address', __( 'Address', 'school-softwere' ), $row->address );
        echo '</div></div>';

        echo '<div class="ss-form-actions">';
        echo '<button class="ss-btn"><i class="ph ph-floppy-disk"></i> ' . esc_html__( 'Save Student', 'school-softwere' ) . '</button>';
        echo '<a href="' . esc_url( SS_Helper::admin_url( 'school-softwere-students' ) ) . '" class="ss-btn ss-btn-ghost">' . esc_html__( 'Cancel', 'school-softwere' ) . '</a>';
        echo '</div>';
        echo '</form>';
        SS_Admin_Shell::card_close();
        SS_Admin_Shell::close();
    }

    private static function handle_save( $school_id ) {
        if ( ! SS_Helper::verify_nonce( 'save_student' ) ) {
            wp_die( esc_html__( 'Invalid request', 'school-softwere' ) );
        }
        global $wpdb;
        $id   = (int) ( $_POST['id'] ?? 0 );
        $tbl  = SS_Helper::table( 'student_records' );

        $data = array(
            'school_id'         => $school_id,
            'class_school_id'   => (int) ( $_POST['class_school_id'] ?? 0 ),
            'section_id'        => (int) ( $_POST['section_id'] ?? 0 ),
            'admission_number'  => sanitize_text_field( wp_unslash( $_POST['admission_number'] ?? '' ) ),
            'roll_number'       => sanitize_text_field( wp_unslash( $_POST['roll_number'] ?? '' ) ),
            'first_name'        => sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) ),
            'last_name'         => sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) ),
            'father_name'       => sanitize_text_field( wp_unslash( $_POST['father_name'] ?? '' ) ),
            'mother_name'       => sanitize_text_field( wp_unslash( $_POST['mother_name'] ?? '' ) ),
            'guardian_name'     => sanitize_text_field( wp_unslash( $_POST['guardian_name'] ?? '' ) ),
            'guardian_relation' => sanitize_text_field( wp_unslash( $_POST['guardian_relation'] ?? '' ) ),
            'dob'               => sanitize_text_field( wp_unslash( $_POST['dob'] ?? '' ) ) ?: null,
            'gender'            => sanitize_text_field( wp_unslash( $_POST['gender'] ?? 'male' ) ),
            'blood_group'       => sanitize_text_field( wp_unslash( $_POST['blood_group'] ?? '' ) ),
            'religion'          => sanitize_text_field( wp_unslash( $_POST['religion'] ?? '' ) ),
            'caste'             => sanitize_text_field( wp_unslash( $_POST['caste'] ?? '' ) ),
            'nationality'       => sanitize_text_field( wp_unslash( $_POST['nationality'] ?? '' ) ),
            'address'           => sanitize_textarea_field( wp_unslash( $_POST['address'] ?? '' ) ),
            'city'              => sanitize_text_field( wp_unslash( $_POST['city'] ?? '' ) ),
            'state'             => sanitize_text_field( wp_unslash( $_POST['state'] ?? '' ) ),
            'zip'               => sanitize_text_field( wp_unslash( $_POST['zip'] ?? '' ) ),
            'country'           => sanitize_text_field( wp_unslash( $_POST['country'] ?? '' ) ),
            'phone'             => sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ),
            'email'             => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
            'photo'             => esc_url_raw( wp_unslash( $_POST['photo'] ?? '' ) ),
            'admission_date'    => sanitize_text_field( wp_unslash( $_POST['admission_date'] ?? '' ) ) ?: null,
            'student_type_id'   => (int) ( $_POST['student_type_id'] ?? 0 ),
            'is_active'         => empty( $_POST['is_active'] ) ? 0 : 1,
        );

        if ( $id ) {
            $wpdb->update( $tbl, $data, array( 'ID' => $id ) );
        } else {
            // Auto-generate admission number if missing.
            if ( empty( $data['admission_number'] ) ) {
                $school = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . SS_Helper::table( 'schools' ) . ' WHERE ID = %d', $school_id ) );
                $base   = (int) $school->last_enrollment_count + 1;
                $data['admission_number'] = SS_Helper::generate_number( $school->admission_prefix ?: 'ADM-', max( $base, $school->admission_base ), $school->admission_padding );
                $wpdb->update( SS_Helper::table( 'schools' ), array( 'last_enrollment_count' => $base ), array( 'ID' => $school_id ) );
            }
            $data['created_at'] = current_time( 'mysql' );
            $wpdb->insert( $tbl, $data );
            $id = (int) $wpdb->insert_id;
        }

        // Activity log.
        $wpdb->insert( SS_Helper::table( 'logs' ), array(
            'school_id'  => $school_id,
            'user_id'    => get_current_user_id(),
            'action'     => __( 'Student saved', 'school-softwere' ) . ': ' . $data['first_name'] . ' ' . $data['last_name'],
            'created_at' => current_time( 'mysql' ),
        ) );

        wp_safe_redirect( add_query_arg( array( 'ss_notice' => __( 'Student saved', 'school-softwere' ), 'ss_notice_type' => 'success' ), SS_Helper::admin_url( 'school-softwere-students' ) ) );
        exit;
    }

    private static function render_profile( $student_id ) {
        global $wpdb;
        $school_id = SS_Helper::active_school_id();
        $row = $wpdb->get_row( $wpdb->prepare(
            'SELECT sr.*, c.label class_label, sec.label section_label
             FROM ' . SS_Helper::table( 'student_records' ) . ' sr
             LEFT JOIN ' . SS_Helper::table( 'class_school' ) . ' cs ON cs.ID = sr.class_school_id
             LEFT JOIN ' . SS_Helper::table( 'classes' ) . ' c ON c.ID = cs.class_id
             LEFT JOIN ' . SS_Helper::table( 'sections' ) . ' sec ON sec.ID = sr.section_id
             WHERE sr.ID = %d AND sr.school_id = %d',
            $student_id, $school_id
        ) );
        if ( ! $row ) {
            wp_die( esc_html__( 'Student not found', 'school-softwere' ) );
        }

        SS_Admin_Shell::open( __( 'Student Profile', 'school-softwere' ), 'school-softwere-students', array(
            array( 'label' => __( 'Students', 'school-softwere' ), 'url' => SS_Helper::admin_url( 'school-softwere-students' ) ),
            array( 'label' => trim( $row->first_name . ' ' . $row->last_name ) ),
        ) );

        // Header card.
        echo '<div class="ss-card"><div class="ss-card-body" style="display:flex; gap:24px; align-items:center; flex-wrap:wrap;">';
        echo '<div class="ss-avatar" style="width:80px; height:80px; font-size:30px;">' . esc_html( strtoupper( substr( $row->first_name, 0, 1 ) ) ) . '</div>';
        echo '<div style="flex:1; min-width:240px;">';
        echo '<h2 style="margin:0; font-family:Nunito; font-weight:800;">' . esc_html( trim( $row->first_name . ' ' . $row->last_name ) ) . '</h2>';
        echo '<p class="ss-text-muted" style="margin:4px 0 8px;">' . esc_html( $row->admission_number ) . ' &middot; ' . esc_html( $row->class_label . ( $row->section_label ? ' / ' . $row->section_label : '' ) ) . '</p>';
        echo SS_Helper::badge( $row->is_active ? __( 'Active', 'school-softwere' ) : __( 'Inactive', 'school-softwere' ), $row->is_active ? 'success' : 'muted' );
        echo '</div>';
        echo '<div class="ss-actions">';
        echo '<a class="ss-btn ss-btn-secondary" href="' . esc_url( SS_Helper::admin_url() . '&ss_print=id_card&id=' . $row->ID ) . '" target="_blank"><i class="ph ph-identification-card"></i> ' . esc_html__( 'ID Card', 'school-softwere' ) . '</a>';
        echo '<a class="ss-btn ss-btn-secondary" href="' . esc_url( SS_Helper::admin_url() . '&ss_print=view_student_detail&id=' . $row->ID ) . '" target="_blank"><i class="ph ph-printer"></i> ' . esc_html__( 'Print Profile', 'school-softwere' ) . '</a>';
        echo '<a class="ss-btn" href="' . esc_url( SS_Helper::admin_url( 'school-softwere-students' ) . '&view=edit&id=' . $row->ID ) . '"><i class="ph ph-pencil-simple"></i> ' . esc_html__( 'Edit', 'school-softwere' ) . '</a>';
        echo '</div>';
        echo '</div></div>';

        // Detail tables.
        echo '<div class="ss-row">';
        echo '<div class="ss-col">';
        SS_Admin_Shell::card_open( __( 'Personal Information', 'school-softwere' ) );
        self::detail_row( __( 'Date of Birth', 'school-softwere' ), SS_Helper::format_date( $row->dob ) );
        self::detail_row( __( 'Gender', 'school-softwere' ), ucfirst( (string) $row->gender ) );
        self::detail_row( __( 'Blood Group', 'school-softwere' ), $row->blood_group );
        self::detail_row( __( 'Religion', 'school-softwere' ), $row->religion );
        self::detail_row( __( 'Phone', 'school-softwere' ), $row->phone );
        self::detail_row( __( 'Email', 'school-softwere' ), $row->email );
        SS_Admin_Shell::card_close();
        echo '</div>';
        echo '<div class="ss-col">';
        SS_Admin_Shell::card_open( __( 'Guardian & Address', 'school-softwere' ) );
        self::detail_row( __( 'Father', 'school-softwere' ), $row->father_name );
        self::detail_row( __( 'Mother', 'school-softwere' ), $row->mother_name );
        self::detail_row( __( 'Guardian', 'school-softwere' ), $row->guardian_name );
        self::detail_row( __( 'Address', 'school-softwere' ), $row->address );
        self::detail_row( __( 'City/State', 'school-softwere' ), trim( ( $row->city ?: '' ) . ' ' . ( $row->state ?: '' ) ) );
        self::detail_row( __( 'Country', 'school-softwere' ), $row->country );
        SS_Admin_Shell::card_close();
        echo '</div>';
        echo '</div>';

        SS_Admin_Shell::close();
    }

    private static function detail_row( $label, $value ) {
        $value = $value ? esc_html( $value ) : '<span class="ss-text-muted">-</span>';
        echo '<div style="display:flex; padding:8px 0; border-bottom:1px solid var(--ss-border);"><div style="flex:0 0 140px; color:var(--ss-text-muted); font-size:13px;">' . esc_html( $label ) . '</div><div style="flex:1; font-weight:500;">' . $value . '</div></div>'; // phpcs:ignore
    }
}

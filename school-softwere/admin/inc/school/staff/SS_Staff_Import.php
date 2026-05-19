<?php
/**
 * SS_Staff_Import - CSV import for students.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Staff_Import {

    public static function render() {
        global $wpdb;
        $school_id = SS_Helper::active_school_id();
        $imported  = 0;
        $errors    = array();

        if ( isset( $_POST['ss_action'] ) && 'import_students' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'import_students' ) ) {
            if ( ! empty( $_FILES['csv']['tmp_name'] ) ) {
                $cs_id = (int) ( $_POST['class_school_id'] ?? 0 );
                $sec_id = (int) ( $_POST['section_id'] ?? 0 );
                $fh = fopen( $_FILES['csv']['tmp_name'], 'r' ); // phpcs:ignore
                if ( $fh ) {
                    $header = fgetcsv( $fh );
                    $tbl = SS_Helper::table( 'student_records' );
                    while ( ( $row = fgetcsv( $fh ) ) !== false ) {
                        $data = @array_combine( (array) $header, (array) $row );
                        if ( ! $data || empty( $data['first_name'] ) ) {
                            continue;
                        }
                        $wpdb->insert( $tbl, array(
                            'school_id'        => $school_id,
                            'class_school_id'  => $cs_id,
                            'section_id'       => $sec_id ?: null,
                            'first_name'       => sanitize_text_field( $data['first_name'] ?? '' ),
                            'last_name'        => sanitize_text_field( $data['last_name'] ?? '' ),
                            'admission_number' => sanitize_text_field( $data['admission_number'] ?? '' ),
                            'roll_number'      => sanitize_text_field( $data['roll_number'] ?? '' ),
                            'father_name'      => sanitize_text_field( $data['father_name'] ?? '' ),
                            'mother_name'      => sanitize_text_field( $data['mother_name'] ?? '' ),
                            'phone'            => sanitize_text_field( $data['phone'] ?? '' ),
                            'email'            => sanitize_email( $data['email'] ?? '' ),
                            'gender'           => sanitize_text_field( $data['gender'] ?? 'male' ),
                            'dob'              => ! empty( $data['dob'] ) ? sanitize_text_field( $data['dob'] ) : null,
                            'is_active'        => 1,
                            'created_at'       => current_time( 'mysql' ),
                        ) );
                        $imported++;
                    }
                    fclose( $fh );
                }
            } else {
                $errors[] = __( 'Please choose a CSV file.', 'school-softwere' );
            }
        }

        SS_Admin_Shell::open( __( 'Import Students', 'school-softwere' ), 'school-softwere-import', array(
            array( 'label' => __( 'Import', 'school-softwere' ) ),
        ) );

        if ( $imported ) {
            SS_Helper::notice( sprintf( __( '%d students imported successfully.', 'school-softwere' ), $imported ), 'success' );
        }
        foreach ( $errors as $err ) {
            SS_Helper::notice( $err, 'error' );
        }

        $cs       = SS_Staff_Accountant::class_schools_for( $school_id );
        $sections = $wpdb->get_results( 'SELECT * FROM ' . SS_Helper::table( 'sections' ) . ' ORDER BY label' );

        SS_Admin_Shell::card_open( __( 'Upload Students CSV', 'school-softwere' ) );
        echo '<p class="ss-text-muted">' . esc_html__( 'CSV columns required:', 'school-softwere' ) . ' <code>first_name, last_name, admission_number, roll_number, father_name, mother_name, phone, email, gender, dob</code></p>';
        echo '<form method="post" enctype="multipart/form-data" class="ss-form">';
        SS_Helper::nonce_field( 'import_students' );
        echo '<input type="hidden" name="ss_action" value="import_students">';
        SS_School::select( 'class_school_id', __( 'Target Class', 'school-softwere' ), 0, $cs, true );
        SS_School::select( 'section_id', __( 'Section', 'school-softwere' ), 0, $sections );
        echo '<div class="ss-field"><label>' . esc_html__( 'CSV File', 'school-softwere' ) . ' <span class="req">*</span></label><input type="file" name="csv" accept=".csv" required></div>';
        echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-upload"></i> ' . esc_html__( 'Import', 'school-softwere' ) . '</button></div>';
        echo '</form>';
        SS_Admin_Shell::card_close();
        SS_Admin_Shell::close();
    }
}

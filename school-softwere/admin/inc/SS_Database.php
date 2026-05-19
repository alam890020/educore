<?php
/**
 * SS_Database - Tables, activation, deactivation, uninstall, sample data.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Database {

    /**
     * Plugin activation.
     */
    public static function activate() {
        self::create_tables();
        self::seed_defaults();
        if ( ! get_option( 'ss_setup_complete' ) ) {
            update_option( 'ss_setup_complete', 0 );
        }
        update_option( 'ss_db_version', SS_VERSION );
    }

    /**
     * Plugin deactivation.
     */
    public static function deactivate() {
        wp_clear_scheduled_hook( 'ss_daily_event' );
    }

    /**
     * All table definitions. Returns an associative array { unprefixed_name => SQL_create }.
     *
     * @return array
     */
    public static function table_definitions() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        $p       = $wpdb->prefix . SS_DB_PREFIX;

        $defs = array();

        // -------- Lookups --------
        $defs['classes'] = "CREATE TABLE {$p}classes (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            label varchar(190) NOT NULL,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY label (label)
        ) {$charset};";

        $defs['category'] = "CREATE TABLE {$p}category (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            label varchar(190) NOT NULL,
            created_at datetime NULL,
            PRIMARY KEY (ID)
        ) {$charset};";

        $defs['medium'] = "CREATE TABLE {$p}medium (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            label varchar(190) NOT NULL,
            PRIMARY KEY (ID)
        ) {$charset};";

        $defs['student_type'] = "CREATE TABLE {$p}student_type (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            label varchar(190) NOT NULL,
            PRIMARY KEY (ID)
        ) {$charset};";

        $defs['subject_types'] = "CREATE TABLE {$p}subject_types (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            label varchar(190) NOT NULL,
            PRIMARY KEY (ID)
        ) {$charset};";

        // -------- Schools --------
        $defs['schools'] = "CREATE TABLE {$p}schools (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            label varchar(190) NOT NULL,
            phone varchar(40) NULL,
            email varchar(190) NULL,
            address text NULL,
            description text NULL,
            registration_number varchar(100) NULL,
            category_id bigint(20) UNSIGNED NULL,
            logo varchar(500) NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            last_enrollment_count bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            last_invoice_count bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            admission_prefix varchar(20) NULL,
            admission_base bigint(20) UNSIGNED NOT NULL DEFAULT 1,
            admission_padding tinyint(2) NOT NULL DEFAULT 5,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY category_id (category_id),
            KEY is_active (is_active)
        ) {$charset};";

        $defs['settings'] = "CREATE TABLE {$p}settings (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id bigint(20) UNSIGNED NOT NULL,
            setting_key varchar(190) NOT NULL,
            setting_value longtext NULL,
            PRIMARY KEY (ID),
            KEY school_id (school_id),
            KEY setting_key (setting_key)
        ) {$charset};";

        // -------- Sessions / Class-school / Sections --------
        $defs['sessions'] = "CREATE TABLE {$p}sessions (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id bigint(20) UNSIGNED NOT NULL,
            label varchar(190) NOT NULL,
            start_date date NULL,
            end_date date NULL,
            is_active tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (ID),
            KEY school_id (school_id)
        ) {$charset};";

        $defs['class_school'] = "CREATE TABLE {$p}class_school (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            class_id bigint(20) UNSIGNED NOT NULL,
            school_id bigint(20) UNSIGNED NOT NULL,
            session_id bigint(20) UNSIGNED NOT NULL,
            PRIMARY KEY (ID),
            KEY class_id (class_id),
            KEY school_id (school_id),
            KEY session_id (session_id)
        ) {$charset};";

        $defs['sections'] = "CREATE TABLE {$p}sections (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            class_school_id bigint(20) UNSIGNED NOT NULL,
            label varchar(190) NOT NULL,
            medium_id bigint(20) UNSIGNED NULL,
            capacity int(11) NOT NULL DEFAULT 0,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY class_school_id (class_school_id)
        ) {$charset};";

        // -------- Students --------
        $defs['student_records'] = "CREATE TABLE {$p}student_records (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id bigint(20) UNSIGNED NOT NULL,
            class_school_id bigint(20) UNSIGNED NOT NULL,
            section_id bigint(20) UNSIGNED NULL,
            user_id bigint(20) UNSIGNED NULL,
            admission_number varchar(100) NULL,
            roll_number varchar(50) NULL,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NULL,
            father_name varchar(150) NULL,
            mother_name varchar(150) NULL,
            guardian_name varchar(150) NULL,
            guardian_relation varchar(50) NULL,
            dob date NULL,
            gender varchar(20) NULL,
            blood_group varchar(10) NULL,
            religion varchar(50) NULL,
            caste varchar(50) NULL,
            nationality varchar(50) NULL,
            address text NULL,
            city varchar(100) NULL,
            state varchar(100) NULL,
            zip varchar(20) NULL,
            country varchar(100) NULL,
            phone varchar(40) NULL,
            email varchar(190) NULL,
            photo varchar(500) NULL,
            admission_date date NULL,
            student_type_id bigint(20) UNSIGNED NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY school_id (school_id),
            KEY class_school_id (class_school_id),
            KEY section_id (section_id),
            KEY user_id (user_id),
            KEY admission_number (admission_number),
            KEY is_active (is_active)
        ) {$charset};";

        $defs['promotions'] = "CREATE TABLE {$p}promotions (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            student_record_id bigint(20) UNSIGNED NOT NULL,
            from_class_school_id bigint(20) UNSIGNED NULL,
            to_class_school_id bigint(20) UNSIGNED NULL,
            promoted_at datetime NULL,
            PRIMARY KEY (ID),
            KEY student_record_id (student_record_id)
        ) {$charset};";

        $defs['transfers'] = "CREATE TABLE {$p}transfers (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            student_record_id bigint(20) UNSIGNED NOT NULL,
            reason text NULL,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY student_record_id (student_record_id)
        ) {$charset};";

        // -------- Staff & Roles --------
        $defs['roles'] = "CREATE TABLE {$p}roles (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id bigint(20) UNSIGNED NOT NULL,
            label varchar(190) NOT NULL,
            permissions longtext NULL,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY school_id (school_id)
        ) {$charset};";

        $defs['staff'] = "CREATE TABLE {$p}staff (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NULL,
            role_id bigint(20) UNSIGNED NULL,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NULL,
            dob date NULL,
            gender varchar(20) NULL,
            phone varchar(40) NULL,
            email varchar(190) NULL,
            address text NULL,
            photo varchar(500) NULL,
            joining_date date NULL,
            designation varchar(150) NULL,
            salary decimal(14,2) NOT NULL DEFAULT 0,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY school_id (school_id),
            KEY user_id (user_id),
            KEY role_id (role_id)
        ) {$charset};";

        $defs['admins'] = "CREATE TABLE {$p}admins (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY school_id (school_id),
            KEY user_id (user_id)
        ) {$charset};";

        // -------- Subjects & routines --------
        $defs['subjects'] = "CREATE TABLE {$p}subjects (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            class_school_id bigint(20) UNSIGNED NOT NULL,
            label varchar(190) NOT NULL,
            subject_type_id bigint(20) UNSIGNED NULL,
            code varchar(50) NULL,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY class_school_id (class_school_id)
        ) {$charset};";

        $defs['routines'] = "CREATE TABLE {$p}routines (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            class_school_id bigint(20) UNSIGNED NOT NULL,
            section_id bigint(20) UNSIGNED NULL,
            subject_id bigint(20) UNSIGNED NULL,
            staff_id bigint(20) UNSIGNED NULL,
            day varchar(20) NOT NULL,
            start_time time NULL,
            end_time time NULL,
            PRIMARY KEY (ID),
            KEY class_school_id (class_school_id)
        ) {$charset};";

        // -------- Fees / Invoices / Payments --------
        $defs['fees'] = "CREATE TABLE {$p}fees (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            class_school_id bigint(20) UNSIGNED NOT NULL,
            label varchar(190) NOT NULL,
            amount decimal(14,2) NOT NULL DEFAULT 0,
            due_date date NULL,
            is_recurring tinyint(1) NOT NULL DEFAULT 0,
            frequency varchar(30) NULL,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY class_school_id (class_school_id)
        ) {$charset};";

        $defs['student_fees'] = "CREATE TABLE {$p}student_fees (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            student_record_id bigint(20) UNSIGNED NOT NULL,
            fee_id bigint(20) UNSIGNED NOT NULL,
            amount decimal(14,2) NOT NULL DEFAULT 0,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY student_record_id (student_record_id),
            KEY fee_id (fee_id)
        ) {$charset};";

        $defs['invoices'] = "CREATE TABLE {$p}invoices (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id bigint(20) UNSIGNED NOT NULL,
            student_record_id bigint(20) UNSIGNED NOT NULL,
            class_school_id bigint(20) UNSIGNED NOT NULL,
            invoice_number varchar(100) NULL,
            total_amount decimal(14,2) NOT NULL DEFAULT 0,
            paid_amount decimal(14,2) NOT NULL DEFAULT 0,
            due_amount decimal(14,2) NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'unpaid',
            due_date date NULL,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY school_id (school_id),
            KEY student_record_id (student_record_id),
            KEY status (status)
        ) {$charset};";

        $defs['payments'] = "CREATE TABLE {$p}payments (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            invoice_id bigint(20) UNSIGNED NOT NULL,
            amount decimal(14,2) NOT NULL DEFAULT 0,
            payment_method varchar(40) NULL,
            payment_date date NULL,
            note text NULL,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY invoice_id (invoice_id)
        ) {$charset};";

        $defs['pending_payments'] = "CREATE TABLE {$p}pending_payments (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            invoice_id bigint(20) UNSIGNED NOT NULL,
            student_record_id bigint(20) UNSIGNED NOT NULL,
            amount decimal(14,2) NOT NULL DEFAULT 0,
            reminder_date date NULL,
            PRIMARY KEY (ID),
            KEY invoice_id (invoice_id)
        ) {$charset};";

        $defs['concession_types'] = "CREATE TABLE {$p}concession_types (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id bigint(20) UNSIGNED NOT NULL,
            label varchar(190) NOT NULL,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY school_id (school_id)
        ) {$charset};";

        $defs['concession_fee_mappings'] = "CREATE TABLE {$p}concession_fee_mappings (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            concession_type_id bigint(20) UNSIGNED NOT NULL,
            fee_id bigint(20) UNSIGNED NOT NULL,
            amount decimal(14,2) NOT NULL DEFAULT 0,
            PRIMARY KEY (ID),
            KEY concession_type_id (concession_type_id),
            KEY fee_id (fee_id)
        ) {$charset};";

        $defs['student_concession'] = "CREATE TABLE {$p}student_concession (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            student_record_id bigint(20) UNSIGNED NOT NULL,
            concession_type_id bigint(20) UNSIGNED NOT NULL,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY student_record_id (student_record_id)
        ) {$charset};";

        // -------- Income / Expenses --------
        $defs['expense_categories'] = "CREATE TABLE {$p}expense_categories (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id bigint(20) UNSIGNED NOT NULL,
            label varchar(190) NOT NULL,
            PRIMARY KEY (ID),
            KEY school_id (school_id)
        ) {$charset};";

        $defs['expenses'] = "CREATE TABLE {$p}expenses (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id bigint(20) UNSIGNED NOT NULL,
            expense_category_id bigint(20) UNSIGNED NULL,
            label varchar(190) NOT NULL,
            amount decimal(14,2) NOT NULL DEFAULT 0,
            date date NULL,
            note text NULL,
            attachment varchar(500) NULL,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY school_id (school_id),
            KEY expense_category_id (expense_category_id)
        ) {$charset};";

        $defs['income_categories'] = "CREATE TABLE {$p}income_categories (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id bigint(20) UNSIGNED NOT NULL,
            label varchar(190) NOT NULL,
            PRIMARY KEY (ID),
            KEY school_id (school_id)
        ) {$charset};";

        $defs['income'] = "CREATE TABLE {$p}income (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id bigint(20) UNSIGNED NOT NULL,
            income_category_id bigint(20) UNSIGNED NULL,
            label varchar(190) NOT NULL,
            amount decimal(14,2) NOT NULL DEFAULT 0,
            date date NULL,
            note text NULL,
            attachment varchar(500) NULL,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY school_id (school_id),
            KEY income_category_id (income_category_id)
        ) {$charset};";

        // -------- Attendance --------
        $defs['attendance'] = "CREATE TABLE {$p}attendance (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            student_record_id bigint(20) UNSIGNED NOT NULL,
            class_school_id bigint(20) UNSIGNED NOT NULL,
            section_id bigint(20) UNSIGNED NULL,
            date date NOT NULL,
            status varchar(20) NOT NULL,
            note text NULL,
            PRIMARY KEY (ID),
            KEY student_record_id (student_record_id),
            KEY class_school_id (class_school_id),
            KEY date (date)
        ) {$charset};";

        $defs['staff_attendance'] = "CREATE TABLE {$p}staff_attendance (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            staff_id bigint(20) UNSIGNED NOT NULL,
            school_id bigint(20) UNSIGNED NOT NULL,
            date date NOT NULL,
            status varchar(20) NOT NULL,
            note text NULL,
            PRIMARY KEY (ID),
            KEY staff_id (staff_id),
            KEY date (date)
        ) {$charset};";

        // -------- Exams --------
        $defs['exams'] = "CREATE TABLE {$p}exams (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            class_school_id bigint(20) UNSIGNED NOT NULL,
            label varchar(190) NOT NULL,
            session_id bigint(20) UNSIGNED NULL,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY class_school_id (class_school_id)
        ) {$charset};";

        $defs['exams_group'] = "CREATE TABLE {$p}exams_group (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id bigint(20) UNSIGNED NOT NULL,
            label varchar(190) NOT NULL,
            session_id bigint(20) UNSIGNED NULL,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY school_id (school_id)
        ) {$charset};";

        $defs['exam_papers'] = "CREATE TABLE {$p}exam_papers (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            exam_id bigint(20) UNSIGNED NOT NULL,
            subject_id bigint(20) UNSIGNED NOT NULL,
            date date NULL,
            start_time time NULL,
            end_time time NULL,
            total_marks decimal(8,2) NOT NULL DEFAULT 0,
            pass_marks decimal(8,2) NOT NULL DEFAULT 0,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY exam_id (exam_id)
        ) {$charset};";

        $defs['exam_results'] = "CREATE TABLE {$p}exam_results (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            exam_paper_id bigint(20) UNSIGNED NOT NULL,
            student_record_id bigint(20) UNSIGNED NOT NULL,
            obtained_marks decimal(8,2) NOT NULL DEFAULT 0,
            grade varchar(10) NULL,
            remarks text NULL,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY exam_paper_id (exam_paper_id),
            KEY student_record_id (student_record_id)
        ) {$charset};";

        $defs['admit_cards'] = "CREATE TABLE {$p}admit_cards (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            exam_id bigint(20) UNSIGNED NOT NULL,
            student_record_id bigint(20) UNSIGNED NOT NULL,
            admit_card_number varchar(100) NULL,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY exam_id (exam_id)
        ) {$charset};";

        // -------- Notices / Events --------
        $defs['notices'] = "CREATE TABLE {$p}notices (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id bigint(20) UNSIGNED NOT NULL,
            title varchar(255) NOT NULL,
            description longtext NULL,
            date date NULL,
            attachment varchar(500) NULL,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY school_id (school_id)
        ) {$charset};";

        $defs['class_school_notice'] = "CREATE TABLE {$p}class_school_notice (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            class_school_id bigint(20) UNSIGNED NOT NULL,
            notice_id bigint(20) UNSIGNED NOT NULL,
            PRIMARY KEY (ID),
            KEY notice_id (notice_id)
        ) {$charset};";

        $defs['events'] = "CREATE TABLE {$p}events (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id bigint(20) UNSIGNED NOT NULL,
            title varchar(255) NOT NULL,
            description longtext NULL,
            start_date datetime NULL,
            end_date datetime NULL,
            venue varchar(255) NULL,
            attachment varchar(500) NULL,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY school_id (school_id)
        ) {$charset};";

        $defs['event_responses'] = "CREATE TABLE {$p}event_responses (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            event_id bigint(20) UNSIGNED NOT NULL,
            student_record_id bigint(20) UNSIGNED NOT NULL,
            response varchar(40) NULL,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY event_id (event_id)
        ) {$charset};";

        // -------- Homework / Materials --------
        $defs['homework'] = "CREATE TABLE {$p}homework (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            class_school_id bigint(20) UNSIGNED NOT NULL,
            section_id bigint(20) UNSIGNED NULL,
            subject_id bigint(20) UNSIGNED NULL,
            staff_id bigint(20) UNSIGNED NULL,
            title varchar(255) NOT NULL,
            description longtext NULL,
            submission_date date NULL,
            attachment varchar(500) NULL,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY class_school_id (class_school_id)
        ) {$charset};";

        $defs['homework_submission'] = "CREATE TABLE {$p}homework_submission (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            homework_id bigint(20) UNSIGNED NOT NULL,
            student_record_id bigint(20) UNSIGNED NOT NULL,
            file varchar(500) NULL,
            note text NULL,
            submitted_at datetime NULL,
            PRIMARY KEY (ID),
            KEY homework_id (homework_id)
        ) {$charset};";

        $defs['study_materials'] = "CREATE TABLE {$p}study_materials (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            class_school_id bigint(20) UNSIGNED NOT NULL,
            section_id bigint(20) UNSIGNED NULL,
            subject_id bigint(20) UNSIGNED NULL,
            title varchar(255) NOT NULL,
            description longtext NULL,
            file_type varchar(40) NULL,
            file varchar(500) NULL,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY class_school_id (class_school_id)
        ) {$charset};";

        // -------- Library --------
        $defs['books'] = "CREATE TABLE {$p}books (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id bigint(20) UNSIGNED NOT NULL,
            title varchar(255) NOT NULL,
            author varchar(190) NULL,
            isbn varchar(50) NULL,
            publisher varchar(190) NULL,
            edition varchar(50) NULL,
            quantity int(11) NOT NULL DEFAULT 0,
            available int(11) NOT NULL DEFAULT 0,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY school_id (school_id)
        ) {$charset};";

        $defs['books_issued'] = "CREATE TABLE {$p}books_issued (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            book_id bigint(20) UNSIGNED NOT NULL,
            student_record_id bigint(20) UNSIGNED NOT NULL,
            issue_date date NULL,
            return_date date NULL,
            returned_at date NULL,
            fine decimal(10,2) NOT NULL DEFAULT 0,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY book_id (book_id),
            KEY student_record_id (student_record_id)
        ) {$charset};";

        $defs['library_cards'] = "CREATE TABLE {$p}library_cards (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            student_record_id bigint(20) UNSIGNED NOT NULL,
            card_number varchar(100) NULL,
            issued_date date NULL,
            expiry_date date NULL,
            PRIMARY KEY (ID),
            KEY student_record_id (student_record_id)
        ) {$charset};";

        // -------- Transport --------
        $defs['vehicles'] = "CREATE TABLE {$p}vehicles (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id bigint(20) UNSIGNED NOT NULL,
            number varchar(50) NULL,
            model varchar(100) NULL,
            capacity int(11) NOT NULL DEFAULT 0,
            driver_name varchar(150) NULL,
            driver_phone varchar(40) NULL,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY school_id (school_id)
        ) {$charset};";

        $defs['routes'] = "CREATE TABLE {$p}routes (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id bigint(20) UNSIGNED NOT NULL,
            label varchar(190) NOT NULL,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY school_id (school_id)
        ) {$charset};";

        $defs['route_vehicle'] = "CREATE TABLE {$p}route_vehicle (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            route_id bigint(20) UNSIGNED NOT NULL,
            vehicle_id bigint(20) UNSIGNED NOT NULL,
            stop_name varchar(190) NULL,
            stop_time time NULL,
            fee decimal(10,2) NOT NULL DEFAULT 0,
            PRIMARY KEY (ID),
            KEY route_id (route_id)
        ) {$charset};";

        // -------- Hostel --------
        $defs['hostels'] = "CREATE TABLE {$p}hostels (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id bigint(20) UNSIGNED NOT NULL,
            label varchar(190) NOT NULL,
            type varchar(20) NOT NULL DEFAULT 'mixed',
            warden_name varchar(150) NULL,
            capacity int(11) NOT NULL DEFAULT 0,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY school_id (school_id)
        ) {$charset};";

        $defs['rooms'] = "CREATE TABLE {$p}rooms (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            hostel_id bigint(20) UNSIGNED NOT NULL,
            room_number varchar(50) NULL,
            capacity int(11) NOT NULL DEFAULT 0,
            room_type varchar(40) NULL,
            fee decimal(10,2) NOT NULL DEFAULT 0,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY hostel_id (hostel_id)
        ) {$charset};";

        // -------- Leaves / Certificates --------
        $defs['leaves'] = "CREATE TABLE {$p}leaves (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            staff_id bigint(20) UNSIGNED NOT NULL,
            school_id bigint(20) UNSIGNED NOT NULL,
            leave_type varchar(50) NULL,
            from_date date NULL,
            to_date date NULL,
            reason text NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY school_id (school_id),
            KEY status (status)
        ) {$charset};";

        $defs['certificates'] = "CREATE TABLE {$p}certificates (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id bigint(20) UNSIGNED NOT NULL,
            label varchar(190) NOT NULL,
            content_template longtext NULL,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY school_id (school_id)
        ) {$charset};";

        $defs['certificate_student'] = "CREATE TABLE {$p}certificate_student (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            certificate_id bigint(20) UNSIGNED NOT NULL,
            student_record_id bigint(20) UNSIGNED NOT NULL,
            issued_date date NULL,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY certificate_id (certificate_id)
        ) {$charset};";

        $defs['transfer_certificates'] = "CREATE TABLE {$p}transfer_certificates (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            student_record_id bigint(20) UNSIGNED NOT NULL,
            reason text NULL,
            issued_date date NULL,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY student_record_id (student_record_id)
        ) {$charset};";

        // -------- Inquiries / Chapters / Lectures / Meetings --------
        $defs['inquiries'] = "CREATE TABLE {$p}inquiries (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id bigint(20) UNSIGNED NOT NULL,
            name varchar(190) NOT NULL,
            email varchar(190) NULL,
            phone varchar(40) NULL,
            message text NULL,
            source varchar(100) NULL,
            status varchar(40) NOT NULL DEFAULT 'open',
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY school_id (school_id)
        ) {$charset};";

        $defs['chapter'] = "CREATE TABLE {$p}chapter (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            subject_id bigint(20) UNSIGNED NOT NULL,
            class_school_id bigint(20) UNSIGNED NOT NULL,
            label varchar(190) NOT NULL,
            description text NULL,
            `order` int(11) NOT NULL DEFAULT 0,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY subject_id (subject_id)
        ) {$charset};";

        $defs['lecture'] = "CREATE TABLE {$p}lecture (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            chapter_id bigint(20) UNSIGNED NOT NULL,
            staff_id bigint(20) UNSIGNED NULL,
            title varchar(255) NOT NULL,
            description longtext NULL,
            video_url varchar(500) NULL,
            attachment varchar(500) NULL,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY chapter_id (chapter_id)
        ) {$charset};";

        $defs['meetings'] = "CREATE TABLE {$p}meetings (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id bigint(20) UNSIGNED NOT NULL,
            class_school_id bigint(20) UNSIGNED NULL,
            section_id bigint(20) UNSIGNED NULL,
            title varchar(255) NOT NULL,
            meeting_link varchar(500) NULL,
            start_time datetime NULL,
            duration int(11) NULL,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY school_id (school_id)
        ) {$charset};";

        $defs['activities'] = "CREATE TABLE {$p}activities (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            class_school_id bigint(20) UNSIGNED NOT NULL,
            section_id bigint(20) UNSIGNED NULL,
            title varchar(255) NOT NULL,
            description longtext NULL,
            date date NULL,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY class_school_id (class_school_id)
        ) {$charset};";

        // -------- Tickets / Logs / Reports / Reminders --------
        $defs['tickets'] = "CREATE TABLE {$p}tickets (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            subject varchar(255) NOT NULL,
            status varchar(40) NOT NULL DEFAULT 'open',
            priority varchar(20) NOT NULL DEFAULT 'normal',
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY school_id (school_id),
            KEY user_id (user_id)
        ) {$charset};";

        $defs['ticket_history'] = "CREATE TABLE {$p}ticket_history (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ticket_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            message longtext NULL,
            attachment varchar(500) NULL,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY ticket_id (ticket_id)
        ) {$charset};";

        $defs['logs'] = "CREATE TABLE {$p}logs (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id bigint(20) UNSIGNED NULL,
            user_id bigint(20) UNSIGNED NULL,
            action varchar(190) NOT NULL,
            details longtext NULL,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY school_id (school_id),
            KEY action (action)
        ) {$charset};";

        $defs['academic_reports'] = "CREATE TABLE {$p}academic_reports (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            student_record_id bigint(20) UNSIGNED NOT NULL,
            class_school_id bigint(20) UNSIGNED NOT NULL,
            session_id bigint(20) UNSIGNED NOT NULL,
            report_data longtext NULL,
            created_at datetime NULL,
            PRIMARY KEY (ID),
            KEY student_record_id (student_record_id)
        ) {$charset};";

        $defs['reminder'] = "CREATE TABLE {$p}reminder (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id bigint(20) UNSIGNED NOT NULL,
            student_record_id bigint(20) UNSIGNED NULL,
            type varchar(40) NULL,
            message text NULL,
            send_date datetime NULL,
            is_sent tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (ID),
            KEY school_id (school_id)
        ) {$charset};";

        return $defs;
    }

    /**
     * Run dbDelta on all definitions.
     */
    public static function create_tables() {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $defs = self::table_definitions();
        foreach ( $defs as $sql ) {
            dbDelta( $sql );
        }
    }

    /**
     * Insert default data on activation if not present.
     */
    public static function seed_defaults() {
        global $wpdb;

        // Default settings option.
        if ( ! get_option( 'ss_settings' ) ) {
            update_option( 'ss_settings', SS_Config::defaults() );
        }

        // Lookups: subject types.
        $st = SS_Helper::table( 'subject_types' );
        if ( 0 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$st}" ) ) {
            $wpdb->insert( $st, array( 'label' => 'Theory' ) );
            $wpdb->insert( $st, array( 'label' => 'Practical' ) );
            $wpdb->insert( $st, array( 'label' => 'Elective' ) );
        }

        // Mediums.
        $m = SS_Helper::table( 'medium' );
        if ( 0 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$m}" ) ) {
            foreach ( array( 'English', 'Hindi', 'Bengali', 'Spanish', 'French' ) as $lbl ) {
                $wpdb->insert( $m, array( 'label' => $lbl ) );
            }
        }

        // Student types.
        $sty = SS_Helper::table( 'student_type' );
        if ( 0 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$sty}" ) ) {
            foreach ( array( 'Regular', 'Day Boarder', 'Hostel', 'Online' ) as $lbl ) {
                $wpdb->insert( $sty, array( 'label' => $lbl ) );
            }
        }

        // Classes.
        $c = SS_Helper::table( 'classes' );
        if ( 0 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$c}" ) ) {
            $defaults = array( 'Nursery', 'KG', 'Class 1', 'Class 2', 'Class 3', 'Class 4', 'Class 5', 'Class 6', 'Class 7', 'Class 8', 'Class 9', 'Class 10', 'Class 11', 'Class 12' );
            foreach ( $defaults as $lbl ) {
                $wpdb->insert( $c, array( 'label' => $lbl, 'created_at' => current_time( 'mysql' ) ) );
            }
        }

        // Categories.
        $cat = SS_Helper::table( 'category' );
        if ( 0 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$cat}" ) ) {
            foreach ( array( 'Public', 'Private', 'International', 'Boarding' ) as $lbl ) {
                $wpdb->insert( $cat, array( 'label' => $lbl, 'created_at' => current_time( 'mysql' ) ) );
            }
        }

        // Default school.
        $sch = SS_Helper::table( 'schools' );
        $school_id = (int) $wpdb->get_var( "SELECT ID FROM {$sch} ORDER BY ID ASC LIMIT 1" );
        if ( ! $school_id ) {
            $wpdb->insert( $sch, array(
                'label'              => 'Sample School',
                'phone'              => '+1-555-0100',
                'email'              => 'info@sample-school.test',
                'address'            => '123 Education Lane',
                'description'        => 'Default sample school created on plugin activation.',
                'registration_number'=> 'REG-0001',
                'category_id'        => 1,
                'is_active'          => 1,
                'admission_prefix'   => 'ADM-',
                'admission_base'     => 1,
                'admission_padding'  => 5,
                'created_at'         => current_time( 'mysql' ),
            ) );
            $school_id = (int) $wpdb->insert_id;
        }

        // Default session for the default school.
        $ses = SS_Helper::table( 'sessions' );
        $sid = (int) $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$ses} WHERE school_id = %d LIMIT 1", $school_id ) );
        if ( ! $sid ) {
            $year = (int) date( 'Y' );
            $wpdb->insert( $ses, array(
                'school_id'  => $school_id,
                'label'      => $year . '-' . ( $year + 1 ),
                'start_date' => $year . '-04-01',
                'end_date'   => ( $year + 1 ) . '-03-31',
                'is_active'  => 1,
            ) );
        }

        // Default expense / income categories.
        $ec = SS_Helper::table( 'expense_categories' );
        if ( 0 === (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$ec} WHERE school_id = %d", $school_id ) ) ) {
            foreach ( array( 'Salary', 'Utilities', 'Maintenance', 'Stationery' ) as $lbl ) {
                $wpdb->insert( $ec, array( 'school_id' => $school_id, 'label' => $lbl ) );
            }
        }
        $ic = SS_Helper::table( 'income_categories' );
        if ( 0 === (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$ic} WHERE school_id = %d", $school_id ) ) ) {
            foreach ( array( 'Donations', 'Grants', 'Other' ) as $lbl ) {
                $wpdb->insert( $ic, array( 'school_id' => $school_id, 'label' => $lbl ) );
            }
        }

        // Sync builtin roles for default school.
        SS_M_Role::sync_builtins( $school_id );
    }
}

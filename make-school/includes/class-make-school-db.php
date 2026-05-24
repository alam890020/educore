<?php
/**
 * MAKE SCHOOL - Database Management Class
 *
 * Handles the creation and management of all custom database tables
 * using WordPress dbDelta() for safe, incremental schema deployment.
 *
 * @package    Make_School
 * @subpackage Make_School/includes
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Make_School_DB {

    /**
     * Database version for schema migration tracking
     *
     * @var string
     */
    const DB_VERSION = '1.0.0';

    /**
     * Option name to store current DB version
     *
     * @var string
     */
    const DB_VERSION_OPTION = 'make_school_db_version';

    /**
     * Create all plugin database tables using dbDelta
     *
     * This method is called during plugin activation and handles
     * both initial creation and incremental schema updates.
     *
     * @return void
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // ─────────────────────────────────────────────────────────────
        // TABLE 1: Branches
        // Stores school branch/campus information
        // ─────────────────────────────────────────────────────────────
        $table_branches = $wpdb->prefix . 'make_school_branches';
        $sql_branches = "CREATE TABLE {$table_branches} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            branch_name varchar(255) NOT NULL DEFAULT '',
            code varchar(50) NOT NULL DEFAULT '',
            address text NOT NULL,
            phone varchar(50) NOT NULL DEFAULT '',
            email varchar(100) NOT NULL DEFAULT '',
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_branch_code (code),
            KEY idx_branch_status (status)
        ) {$charset_collate};";

        dbDelta( $sql_branches );

        // ─────────────────────────────────────────────────────────────
        // TABLE 2: Classes & Sections
        // Stores class/section configuration per branch
        // ─────────────────────────────────────────────────────────────
        $table_classes = $wpdb->prefix . 'make_school_classes';
        $sql_classes = "CREATE TABLE {$table_classes} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            branch_id bigint(20) unsigned NOT NULL DEFAULT 0,
            class_name varchar(100) NOT NULL DEFAULT '',
            section_name varchar(50) NOT NULL DEFAULT '',
            capacity int(11) NOT NULL DEFAULT 0,
            class_teacher_id bigint(20) unsigned NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_class_branch (branch_id),
            KEY idx_class_teacher (class_teacher_id),
            KEY idx_class_status (status)
        ) {$charset_collate};";

        dbDelta( $sql_classes );

        // ─────────────────────────────────────────────────────────────
        // TABLE 3: Admissions
        // Stores admission requests/applications
        // ─────────────────────────────────────────────────────────────
        $table_admissions = $wpdb->prefix . 'make_school_admissions';
        $sql_admissions = "CREATE TABLE {$table_admissions} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            full_name varchar(255) NOT NULL DEFAULT '',
            dob date NOT NULL DEFAULT '0000-00-00',
            gender varchar(20) NOT NULL DEFAULT '',
            blood_group varchar(10) NOT NULL DEFAULT '',
            parent_name varchar(255) NOT NULL DEFAULT '',
            email varchar(100) NOT NULL DEFAULT '',
            phone varchar(50) NOT NULL DEFAULT '',
            address text NOT NULL,
            class_id bigint(20) unsigned NOT NULL DEFAULT 0,
            previous_school varchar(255) NOT NULL DEFAULT '',
            status varchar(20) NOT NULL DEFAULT 'pending',
            rejection_reason text NOT NULL,
            wp_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_admission_class (class_id),
            KEY idx_admission_status (status),
            KEY idx_admission_email (email),
            KEY idx_admission_user (wp_user_id)
        ) {$charset_collate};";

        dbDelta( $sql_admissions );

        // ─────────────────────────────────────────────────────────────
        // TABLE 4: Attendance
        // Stores daily attendance records per student
        // ─────────────────────────────────────────────────────────────
        $table_attendance = $wpdb->prefix . 'make_school_attendance';
        $sql_attendance = "CREATE TABLE {$table_attendance} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            student_id bigint(20) unsigned NOT NULL DEFAULT 0,
            class_id bigint(20) unsigned NOT NULL DEFAULT 0,
            date date NOT NULL DEFAULT '0000-00-00',
            status varchar(20) NOT NULL DEFAULT 'present',
            marked_by bigint(20) unsigned NOT NULL DEFAULT 0,
            remarks varchar(255) NOT NULL DEFAULT '',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY idx_attendance_unique (student_id,class_id,date),
            KEY idx_attendance_student (student_id),
            KEY idx_attendance_class (class_id),
            KEY idx_attendance_date (date),
            KEY idx_attendance_status (status),
            KEY idx_attendance_marked_by (marked_by)
        ) {$charset_collate};";

        dbDelta( $sql_attendance );

        // ─────────────────────────────────────────────────────────────
        // TABLE 5: Invoices / Fee Records
        // Stores individual billing records per student
        // ─────────────────────────────────────────────────────────────
        $table_invoices = $wpdb->prefix . 'make_school_invoices';
        $sql_invoices = "CREATE TABLE {$table_invoices} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            student_id bigint(20) unsigned NOT NULL DEFAULT 0,
            class_id bigint(20) unsigned NOT NULL DEFAULT 0,
            fee_type varchar(100) NOT NULL DEFAULT '',
            description varchar(255) NOT NULL DEFAULT '',
            amount decimal(10,2) NOT NULL DEFAULT 0.00,
            paid_amount decimal(10,2) NOT NULL DEFAULT 0.00,
            due_date date NOT NULL DEFAULT '0000-00-00',
            paid_date date DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'unpaid',
            tracking_id varchar(100) NOT NULL DEFAULT '',
            payment_method varchar(50) NOT NULL DEFAULT '',
            academic_year varchar(20) NOT NULL DEFAULT '',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_invoice_student (student_id),
            KEY idx_invoice_class (class_id),
            KEY idx_invoice_status (status),
            KEY idx_invoice_due_date (due_date),
            KEY idx_invoice_tracking (tracking_id),
            KEY idx_invoice_fee_type (fee_type)
        ) {$charset_collate};";

        dbDelta( $sql_invoices );

        // ─────────────────────────────────────────────────────────────
        // TABLE 6: Exams
        // Stores exam schedule and configuration
        // ─────────────────────────────────────────────────────────────
        $table_exams = $wpdb->prefix . 'make_school_exams';
        $sql_exams = "CREATE TABLE {$table_exams} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            exam_name varchar(255) NOT NULL DEFAULT '',
            class_id bigint(20) unsigned NOT NULL DEFAULT 0,
            subject_name varchar(255) NOT NULL DEFAULT '',
            exam_date date NOT NULL DEFAULT '0000-00-00',
            start_time time DEFAULT NULL,
            end_time time DEFAULT NULL,
            room_number varchar(50) NOT NULL DEFAULT '',
            max_marks int(11) NOT NULL DEFAULT 100,
            pass_marks int(11) NOT NULL DEFAULT 33,
            academic_year varchar(20) NOT NULL DEFAULT '',
            status varchar(20) NOT NULL DEFAULT 'scheduled',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_exam_class (class_id),
            KEY idx_exam_date (exam_date),
            KEY idx_exam_name (exam_name(100)),
            KEY idx_exam_status (status)
        ) {$charset_collate};";

        dbDelta( $sql_exams );

        // ─────────────────────────────────────────────────────────────
        // TABLE 7: Marks / Results
        // Stores individual student marks per exam
        // ─────────────────────────────────────────────────────────────
        $table_marks = $wpdb->prefix . 'make_school_marks';
        $sql_marks = "CREATE TABLE {$table_marks} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            student_id bigint(20) unsigned NOT NULL DEFAULT 0,
            exam_id bigint(20) unsigned NOT NULL DEFAULT 0,
            class_id bigint(20) unsigned NOT NULL DEFAULT 0,
            marks_obtained decimal(5,2) NOT NULL DEFAULT 0.00,
            max_marks int(11) NOT NULL DEFAULT 100,
            grade varchar(10) NOT NULL DEFAULT '',
            teacher_remarks text NOT NULL,
            entered_by bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY idx_marks_unique (student_id,exam_id),
            KEY idx_marks_student (student_id),
            KEY idx_marks_exam (exam_id),
            KEY idx_marks_class (class_id),
            KEY idx_marks_entered_by (entered_by)
        ) {$charset_collate};";

        dbDelta( $sql_marks );

        // Store the current database version
        update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
    }

    /**
     * Check if database needs upgrade and run if necessary
     *
     * @return void
     */
    public static function maybe_upgrade() {
        $installed_version = get_option( self::DB_VERSION_OPTION, '0' );

        if ( version_compare( $installed_version, self::DB_VERSION, '<' ) ) {
            self::create_tables();
        }
    }

    /**
     * Drop all plugin tables (used during uninstall)
     *
     * @return void
     */
    public static function drop_tables() {
        global $wpdb;

        $tables = array(
            $wpdb->prefix . 'make_school_branches',
            $wpdb->prefix . 'make_school_classes',
            $wpdb->prefix . 'make_school_admissions',
            $wpdb->prefix . 'make_school_attendance',
            $wpdb->prefix . 'make_school_invoices',
            $wpdb->prefix . 'make_school_exams',
            $wpdb->prefix . 'make_school_marks',
        );

        foreach ( $tables as $table ) {
            $wpdb->query( $wpdb->prepare( "DROP TABLE IF EXISTS %i", $table ) );
        }

        delete_option( self::DB_VERSION_OPTION );
    }

    /**
     * Get table name with proper prefix
     *
     * @param string $table_slug The table slug without prefix.
     * @return string Full table name with prefix.
     */
    public static function get_table( $table_slug ) {
        global $wpdb;
        return $wpdb->prefix . 'make_school_' . $table_slug;
    }

    /**
     * Get total row count for a table
     *
     * @param string $table_slug The table slug.
     * @param array  $where      Optional WHERE conditions as key=>value pairs.
     * @return int Row count.
     */
    public static function count_rows( $table_slug, $where = array() ) {
        global $wpdb;

        $table = self::get_table( $table_slug );
        $sql   = "SELECT COUNT(*) FROM {$table}";

        if ( ! empty( $where ) ) {
            $conditions = array();
            foreach ( $where as $column => $value ) {
                $conditions[] = $wpdb->prepare( "{$column} = %s", $value );
            }
            $sql .= ' WHERE ' . implode( ' AND ', $conditions );
        }

        return (int) $wpdb->get_var( $sql );
    }
}

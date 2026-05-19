<?php
/**
 * School Softwere - Uninstall cleanup.
 *
 * Removes plugin tables and options when the user deletes the plugin.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

$tables = array(
    'ss_logs', 'ss_reminder', 'ss_academic_reports', 'ss_ticket_history', 'ss_tickets',
    'ss_activities', 'ss_meetings', 'ss_lecture', 'ss_chapter', 'ss_inquiries',
    'ss_transfer_certificates', 'ss_certificate_student', 'ss_certificates',
    'ss_leaves', 'ss_rooms', 'ss_hostels', 'ss_route_vehicle', 'ss_routes', 'ss_vehicles',
    'ss_library_cards', 'ss_books_issued', 'ss_books',
    'ss_study_materials', 'ss_homework_submission', 'ss_homework',
    'ss_event_responses', 'ss_events', 'ss_class_school_notice', 'ss_notices',
    'ss_admit_cards', 'ss_exam_results', 'ss_exam_papers', 'ss_exams_group', 'ss_exams',
    'ss_staff_attendance', 'ss_attendance',
    'ss_income', 'ss_income_categories', 'ss_expenses', 'ss_expense_categories',
    'ss_student_concession', 'ss_concession_fee_mappings', 'ss_concession_types',
    'ss_pending_payments', 'ss_payments', 'ss_invoices', 'ss_student_fees', 'ss_fees',
    'ss_routines', 'ss_subjects', 'ss_subject_types',
    'ss_admins', 'ss_roles', 'ss_staff',
    'ss_transfers', 'ss_promotions', 'ss_student_records',
    'ss_class_school', 'ss_sections', 'ss_sessions',
    'ss_category', 'ss_classes', 'ss_settings', 'ss_schools',
    'ss_medium', 'ss_student_type',
);

foreach ( $tables as $t ) {
    $table = $wpdb->prefix . $t;
    $wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore
}

// Delete options.
$options = array( 'ss_db_version', 'ss_settings', 'ss_setup_complete', 'ss_active_school', 'ss_active_session' );
foreach ( $options as $opt ) {
    delete_option( $opt );
}

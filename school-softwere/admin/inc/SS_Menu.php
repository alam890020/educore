<?php
/**
 * SS_Menu - Registers all admin pages and provides sidebar definitions.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Menu {

    /**
     * Register WordPress admin menu/submenus.
     */
    public static function register() {
        $cap = SS_CAP_SUPER;

        // Top-level menu.
        add_menu_page(
            __( 'School Softwere', 'school-softwere' ),
            __( 'School Softwere', 'school-softwere' ),
            $cap,
            SS_MENU_DASHBOARD,
            array( 'SS_Dashboard', 'render' ),
            'dashicons-welcome-learn-more',
            3
        );

        $items = self::flat_items();
        foreach ( $items as $item ) {
            add_submenu_page(
                SS_MENU_DASHBOARD,
                $item['label'],
                $item['label'],
                $cap,
                $item['slug'],
                $item['callback']
            );
        }
    }

    /**
     * Sidebar groups (visual sidebar nav).
     *
     * @return array
     */
    public static function sidebar_groups() {
        return array(
            array(
                'label' => __( 'Overview', 'school-softwere' ),
                'icon'  => 'ph-house',
                'items' => array(
                    array( 'slug' => 'school-softwere',                  'label' => __( 'Dashboard', 'school-softwere' ),     'icon' => 'ph-gauge' ),
                    array( 'slug' => 'school-softwere-setup',            'label' => __( 'Setup Wizard', 'school-softwere' ),  'icon' => 'ph-magic-wand' ),
                ),
            ),
            array(
                'label' => __( 'Manage', 'school-softwere' ),
                'icon'  => 'ph-buildings',
                'items' => array(
                    array( 'slug' => 'school-softwere-schools',          'label' => __( 'Schools', 'school-softwere' ),       'icon' => 'ph-buildings' ),
                    array( 'slug' => 'school-softwere-classes',          'label' => __( 'Classes', 'school-softwere' ),       'icon' => 'ph-stack' ),
                    array( 'slug' => 'school-softwere-sessions',         'label' => __( 'Sessions', 'school-softwere' ),      'icon' => 'ph-calendar' ),
                    array( 'slug' => 'school-softwere-categories',       'label' => __( 'Categories', 'school-softwere' ),    'icon' => 'ph-tag' ),
                ),
            ),
            array(
                'label' => __( 'Academic', 'school-softwere' ),
                'icon'  => 'ph-graduation-cap',
                'items' => array(
                    array( 'slug' => 'school-softwere-students',         'label' => __( 'Students', 'school-softwere' ),      'icon' => 'ph-graduation-cap' ),
                    array( 'slug' => 'school-softwere-staff',            'label' => __( 'Staff', 'school-softwere' ),         'icon' => 'ph-users-three' ),
                    array( 'slug' => 'school-softwere-subjects',         'label' => __( 'Subjects', 'school-softwere' ),      'icon' => 'ph-book-open' ),
                    array( 'slug' => 'school-softwere-routines',         'label' => __( 'Timetable', 'school-softwere' ),     'icon' => 'ph-clock' ),
                    array( 'slug' => 'school-softwere-exams',            'label' => __( 'Examinations', 'school-softwere' ),  'icon' => 'ph-exam' ),
                    array( 'slug' => 'school-softwere-attendance',       'label' => __( 'Attendance', 'school-softwere' ),    'icon' => 'ph-check-square' ),
                    array( 'slug' => 'school-softwere-homework',         'label' => __( 'Homework', 'school-softwere' ),      'icon' => 'ph-notebook' ),
                    array( 'slug' => 'school-softwere-lectures',         'label' => __( 'Lectures', 'school-softwere' ),      'icon' => 'ph-video' ),
                    array( 'slug' => 'school-softwere-meetings',         'label' => __( 'Live Classes', 'school-softwere' ),  'icon' => 'ph-monitor-play' ),
                    array( 'slug' => 'school-softwere-activities',       'label' => __( 'Activities', 'school-softwere' ),    'icon' => 'ph-sparkle' ),
                ),
            ),
            array(
                'label' => __( 'Finance', 'school-softwere' ),
                'icon'  => 'ph-wallet',
                'items' => array(
                    array( 'slug' => 'school-softwere-fees',             'label' => __( 'Fees', 'school-softwere' ),          'icon' => 'ph-currency-circle-dollar' ),
                    array( 'slug' => 'school-softwere-invoices',         'label' => __( 'Invoices', 'school-softwere' ),      'icon' => 'ph-receipt' ),
                    array( 'slug' => 'school-softwere-payments',         'label' => __( 'Payments', 'school-softwere' ),      'icon' => 'ph-money' ),
                    array( 'slug' => 'school-softwere-concessions',      'label' => __( 'Concessions', 'school-softwere' ),   'icon' => 'ph-percent' ),
                    array( 'slug' => 'school-softwere-income',           'label' => __( 'Income', 'school-softwere' ),        'icon' => 'ph-trend-up' ),
                    array( 'slug' => 'school-softwere-expenses',         'label' => __( 'Expenses', 'school-softwere' ),      'icon' => 'ph-trend-down' ),
                ),
            ),
            array(
                'label' => __( 'Resources', 'school-softwere' ),
                'icon'  => 'ph-folders',
                'items' => array(
                    array( 'slug' => 'school-softwere-library',          'label' => __( 'Library', 'school-softwere' ),       'icon' => 'ph-books' ),
                    array( 'slug' => 'school-softwere-transport',        'label' => __( 'Transport', 'school-softwere' ),     'icon' => 'ph-bus' ),
                    array( 'slug' => 'school-softwere-hostel',           'label' => __( 'Hostel', 'school-softwere' ),        'icon' => 'ph-house-line' ),
                ),
            ),
            array(
                'label' => __( 'Communication', 'school-softwere' ),
                'icon'  => 'ph-megaphone',
                'items' => array(
                    array( 'slug' => 'school-softwere-notices',          'label' => __( 'Notices & Events', 'school-softwere' ),'icon' => 'ph-megaphone' ),
                    array( 'slug' => 'school-softwere-inquiries',        'label' => __( 'Inquiries', 'school-softwere' ),     'icon' => 'ph-question' ),
                    array( 'slug' => 'school-softwere-tickets',          'label' => __( 'Support Tickets', 'school-softwere' ),'icon' => 'ph-lifebuoy' ),
                ),
            ),
            array(
                'label' => __( 'Operations', 'school-softwere' ),
                'icon'  => 'ph-toolbox',
                'items' => array(
                    array( 'slug' => 'school-softwere-leaves',           'label' => __( 'Leaves', 'school-softwere' ),        'icon' => 'ph-airplane-takeoff' ),
                    array( 'slug' => 'school-softwere-certificates',     'label' => __( 'Certificates', 'school-softwere' ),  'icon' => 'ph-certificate' ),
                    array( 'slug' => 'school-softwere-reports',          'label' => __( 'Reports', 'school-softwere' ),       'icon' => 'ph-chart-bar' ),
                    array( 'slug' => 'school-softwere-import',           'label' => __( 'Import', 'school-softwere' ),        'icon' => 'ph-upload' ),
                    array( 'slug' => 'school-softwere-export',           'label' => __( 'Export', 'school-softwere' ),        'icon' => 'ph-download' ),
                    array( 'slug' => 'school-softwere-logs',             'label' => __( 'Activity Logs', 'school-softwere' ), 'icon' => 'ph-clipboard-text' ),
                ),
            ),
            array(
                'label' => __( 'System', 'school-softwere' ),
                'icon'  => 'ph-gear',
                'items' => array(
                    array( 'slug' => 'school-softwere-settings',         'label' => __( 'Settings', 'school-softwere' ),      'icon' => 'ph-gear' ),
                ),
            ),
        );
    }

    /**
     * Flat list of items mapped to callback render functions.
     *
     * @return array
     */
    public static function flat_items() {
        return array(
            // Manager.
            array( 'slug' => 'school-softwere-schools',     'label' => __( 'Schools', 'school-softwere' ),     'callback' => array( 'SS_School',   'render' ) ),
            array( 'slug' => 'school-softwere-classes',     'label' => __( 'Classes', 'school-softwere' ),     'callback' => array( 'SS_Class',    'render' ) ),
            array( 'slug' => 'school-softwere-sessions',    'label' => __( 'Sessions', 'school-softwere' ),    'callback' => array( 'SS_Session',  'render' ) ),
            array( 'slug' => 'school-softwere-categories',  'label' => __( 'Categories', 'school-softwere' ),  'callback' => array( 'SS_Category', 'render' ) ),
            array( 'slug' => 'school-softwere-settings',    'label' => __( 'Settings', 'school-softwere' ),    'callback' => array( 'SS_Setting',  'render' ) ),
            array( 'slug' => 'school-softwere-setup',       'label' => __( 'Setup Wizard', 'school-softwere' ),'callback' => array( 'SS_Setup_Wizard', 'render' ) ),
            // Academic.
            array( 'slug' => 'school-softwere-students',    'label' => __( 'Students', 'school-softwere' ),    'callback' => array( 'SS_Staff_School', 'render' ) ),
            array( 'slug' => 'school-softwere-staff',       'label' => __( 'Staff', 'school-softwere' ),       'callback' => array( 'SS_Staff_General', 'render' ) ),
            array( 'slug' => 'school-softwere-subjects',    'label' => __( 'Subjects', 'school-softwere' ),    'callback' => array( 'SS_Staff_Class', 'render_subjects' ) ),
            array( 'slug' => 'school-softwere-routines',    'label' => __( 'Timetable', 'school-softwere' ),   'callback' => array( 'SS_Staff_Class', 'render_routines' ) ),
            array( 'slug' => 'school-softwere-exams',       'label' => __( 'Examinations', 'school-softwere' ),'callback' => array( 'SS_Staff_Examination', 'render' ) ),
            array( 'slug' => 'school-softwere-attendance',  'label' => __( 'Attendance', 'school-softwere' ),  'callback' => array( 'SS_Staff_Class', 'render_attendance' ) ),
            array( 'slug' => 'school-softwere-homework',    'label' => __( 'Homework', 'school-softwere' ),    'callback' => array( 'SS_Staff_Homework', 'render' ) ),
            array( 'slug' => 'school-softwere-lectures',    'label' => __( 'Lectures', 'school-softwere' ),    'callback' => array( 'SS_Staff_Lectures', 'render' ) ),
            array( 'slug' => 'school-softwere-meetings',    'label' => __( 'Live Classes', 'school-softwere' ),'callback' => array( 'SS_Staff_Meetings', 'render' ) ),
            array( 'slug' => 'school-softwere-activities',  'label' => __( 'Activities', 'school-softwere' ),  'callback' => array( 'SS_Staff_Activities', 'render' ) ),
            // Finance.
            array( 'slug' => 'school-softwere-fees',        'label' => __( 'Fees', 'school-softwere' ),        'callback' => array( 'SS_Staff_Accountant', 'render_fees' ) ),
            array( 'slug' => 'school-softwere-invoices',    'label' => __( 'Invoices', 'school-softwere' ),    'callback' => array( 'SS_Staff_Accountant', 'render_invoices' ) ),
            array( 'slug' => 'school-softwere-payments',    'label' => __( 'Payments', 'school-softwere' ),    'callback' => array( 'SS_Staff_Accountant', 'render_payments' ) ),
            array( 'slug' => 'school-softwere-concessions', 'label' => __( 'Concessions', 'school-softwere' ), 'callback' => array( 'SS_Staff_Accountant', 'render_concessions' ) ),
            array( 'slug' => 'school-softwere-income',      'label' => __( 'Income', 'school-softwere' ),      'callback' => array( 'SS_Staff_Accountant', 'render_income' ) ),
            array( 'slug' => 'school-softwere-expenses',    'label' => __( 'Expenses', 'school-softwere' ),    'callback' => array( 'SS_Staff_Accountant', 'render_expenses' ) ),
            // Resources.
            array( 'slug' => 'school-softwere-library',     'label' => __( 'Library', 'school-softwere' ),     'callback' => array( 'SS_Staff_Library', 'render' ) ),
            array( 'slug' => 'school-softwere-transport',   'label' => __( 'Transport', 'school-softwere' ),   'callback' => array( 'SS_Staff_Transport', 'render' ) ),
            array( 'slug' => 'school-softwere-hostel',      'label' => __( 'Hostel', 'school-softwere' ),      'callback' => array( 'SS_Staff_Hostel', 'render' ) ),
            // Communication.
            array( 'slug' => 'school-softwere-notices',     'label' => __( 'Notices', 'school-softwere' ),     'callback' => array( 'SS_Staff_Notices', 'render' ) ),
            array( 'slug' => 'school-softwere-inquiries',   'label' => __( 'Inquiries', 'school-softwere' ),   'callback' => array( 'SS_Staff_Inquiries', 'render' ) ),
            array( 'slug' => 'school-softwere-tickets',     'label' => __( 'Tickets', 'school-softwere' ),     'callback' => array( 'SS_Staff_Tickets', 'render' ) ),
            // Operations.
            array( 'slug' => 'school-softwere-leaves',      'label' => __( 'Leaves', 'school-softwere' ),      'callback' => array( 'SS_Staff_Leaves', 'render' ) ),
            array( 'slug' => 'school-softwere-certificates','label' => __( 'Certificates', 'school-softwere' ),'callback' => array( 'SS_Staff_Certificates', 'render' ) ),
            array( 'slug' => 'school-softwere-reports',     'label' => __( 'Reports', 'school-softwere' ),     'callback' => array( 'SS_Staff_Reports', 'render' ) ),
            array( 'slug' => 'school-softwere-import',      'label' => __( 'Import', 'school-softwere' ),      'callback' => array( 'SS_Staff_Import', 'render' ) ),
            array( 'slug' => 'school-softwere-export',      'label' => __( 'Export', 'school-softwere' ),      'callback' => array( 'SS_Staff_Export', 'render' ) ),
            array( 'slug' => 'school-softwere-logs',        'label' => __( 'Activity Logs', 'school-softwere' ),'callback' => array( 'SS_Staff_Logs', 'render' ) ),
        );
    }
}

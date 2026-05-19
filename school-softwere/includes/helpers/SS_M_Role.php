<?php
/**
 * SS_M_Role - Role & permission helpers.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_M_Role {

    /**
     * All available granular permissions.
     *
     * @return array
     */
    public static function all_permissions() {
        return array(
            'manage_students'   => __( 'Manage Students', 'school-softwere' ),
            'view_students'     => __( 'View Students', 'school-softwere' ),
            'manage_staff'      => __( 'Manage Staff', 'school-softwere' ),
            'view_staff'        => __( 'View Staff', 'school-softwere' ),
            'manage_classes'    => __( 'Manage Classes', 'school-softwere' ),
            'manage_fees'       => __( 'Manage Fees', 'school-softwere' ),
            'collect_fees'      => __( 'Collect Fees', 'school-softwere' ),
            'view_fees'         => __( 'View Fees', 'school-softwere' ),
            'manage_exams'      => __( 'Manage Exams', 'school-softwere' ),
            'enter_results'     => __( 'Enter Results', 'school-softwere' ),
            'view_results'      => __( 'View Results', 'school-softwere' ),
            'manage_attendance' => __( 'Manage Attendance', 'school-softwere' ),
            'view_attendance'   => __( 'View Attendance', 'school-softwere' ),
            'manage_library'    => __( 'Manage Library', 'school-softwere' ),
            'manage_transport'  => __( 'Manage Transport', 'school-softwere' ),
            'manage_hostel'     => __( 'Manage Hostel', 'school-softwere' ),
            'manage_notices'    => __( 'Manage Notices', 'school-softwere' ),
            'manage_events'     => __( 'Manage Events', 'school-softwere' ),
            'manage_homework'   => __( 'Manage Homework', 'school-softwere' ),
            'manage_settings'   => __( 'Manage Settings', 'school-softwere' ),
            'manage_logs'       => __( 'Manage Logs', 'school-softwere' ),
            'view_inquiries'    => __( 'View Inquiries', 'school-softwere' ),
            'manage_leaves'     => __( 'Manage Leaves', 'school-softwere' ),
            'approve_leaves'    => __( 'Approve Leaves', 'school-softwere' ),
            'view_live_classes' => __( 'View Live Classes', 'school-softwere' ),
            'manage_meetings'   => __( 'Manage Meetings', 'school-softwere' ),
        );
    }

    /**
     * Built-in roles with default permissions.
     *
     * @return array
     */
    public static function builtin_roles() {
        $all = array_keys( self::all_permissions() );
        return array(
            'super_admin'       => array( 'label' => __( 'Super Admin', 'school-softwere' ),       'permissions' => $all ),
            'school_admin'      => array( 'label' => __( 'School Admin', 'school-softwere' ),      'permissions' => $all ),
            'teacher'           => array( 'label' => __( 'Teacher', 'school-softwere' ),           'permissions' => array( 'view_students', 'manage_attendance', 'view_attendance', 'manage_homework', 'view_results', 'enter_results', 'manage_meetings', 'view_live_classes' ) ),
            'accountant'        => array( 'label' => __( 'Accountant', 'school-softwere' ),        'permissions' => array( 'manage_fees', 'collect_fees', 'view_fees', 'view_students' ) ),
            'librarian'         => array( 'label' => __( 'Librarian', 'school-softwere' ),         'permissions' => array( 'manage_library', 'view_students' ) ),
            'transport_manager' => array( 'label' => __( 'Transport Manager', 'school-softwere' ), 'permissions' => array( 'manage_transport', 'view_students' ) ),
            'receptionist'      => array( 'label' => __( 'Receptionist', 'school-softwere' ),      'permissions' => array( 'view_inquiries', 'manage_notices', 'view_students' ) ),
            'hostel_warden'     => array( 'label' => __( 'Hostel Warden', 'school-softwere' ),     'permissions' => array( 'manage_hostel', 'view_students' ) ),
            'employee'          => array( 'label' => __( 'Employee', 'school-softwere' ),          'permissions' => array( 'manage_leaves' ) ),
        );
    }

    /**
     * Does the current WP user have a given plugin permission?
     *
     * @param string $perm
     * @return bool
     */
    public static function user_can( $perm ) {
        if ( current_user_can( SS_CAP_SUPER ) ) {
            return true;
        }
        $perms = (array) get_user_meta( get_current_user_id(), 'ss_permissions', true );
        return in_array( $perm, $perms, true );
    }

    /**
     * Sync built-in roles into the ss_roles table for a school.
     *
     * @param int $school_id
     */
    public static function sync_builtins( $school_id ) {
        global $wpdb;
        $table = SS_Helper::table( 'roles' );
        foreach ( self::builtin_roles() as $key => $def ) {
            $exists = $wpdb->get_var( $wpdb->prepare(
                "SELECT ID FROM {$table} WHERE school_id = %d AND label = %s",
                $school_id,
                $def['label']
            ) );
            if ( ! $exists ) {
                $wpdb->insert( $table, array(
                    'school_id'   => $school_id,
                    'label'       => $def['label'],
                    'permissions' => wp_json_encode( $def['permissions'] ),
                    'created_at'  => current_time( 'mysql' ),
                ) );
            }
        }
    }
}

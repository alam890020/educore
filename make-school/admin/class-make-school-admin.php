<?php
/**
 * MAKE SCHOOL - Admin Management Class
 *
 * Handles all backend administration: menus, branches, classes, admissions, and exams.
 *
 * @package    Make_School
 * @subpackage Make_School/admin
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Make_School_Admin {

    /**
     * Constructor - Register admin hooks
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_admin_menus' ) );
        add_action( 'admin_init', array( $this, 'handle_form_submissions' ) );
        add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
    }

    /**
     * Register all admin menus and submenus
     */
    public function register_admin_menus() {
        // Top-level menu
        add_menu_page(
            __( 'MAKE SCHOOL', 'make-school' ),
            __( 'MAKE SCHOOL', 'make-school' ),
            'manage_options',
            'make-school',
            array( $this, 'render_dashboard_page' ),
            'dashicons-welcome-learn-more',
            3
        );


        // Submenu: Dashboard
        add_submenu_page(
            'make-school',
            __( 'Dashboard', 'make-school' ),
            __( 'Dashboard', 'make-school' ),
            'manage_options',
            'make-school',
            array( $this, 'render_dashboard_page' )
        );

        // Submenu: Branches
        add_submenu_page(
            'make-school',
            __( 'Branches', 'make-school' ),
            __( 'Branches', 'make-school' ),
            'manage_options',
            'make-school-branches',
            array( $this, 'render_branches_page' )
        );

        // Submenu: Classes & Sections
        add_submenu_page(
            'make-school',
            __( 'Classes & Sections', 'make-school' ),
            __( 'Classes & Sections', 'make-school' ),
            'manage_options',
            'make-school-classes',
            array( $this, 'render_classes_page' )
        );

        // Submenu: Admission Requests
        add_submenu_page(
            'make-school',
            __( 'Admission Requests', 'make-school' ),
            __( 'Admission Requests', 'make-school' ),
            'manage_options',
            'make-school-admissions',
            array( $this, 'render_admissions_page' )
        );

        // Submenu: Exams Engine
        add_submenu_page(
            'make-school',
            __( 'Exams Engine', 'make-school' ),
            __( 'Exams Engine', 'make-school' ),
            'manage_options',
            'make-school-exams',
            array( $this, 'render_exams_page' )
        );
    }


    /**
     * Handle all form submissions from admin pages
     */
    public function handle_form_submissions() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Handle Branch form submission
        if ( isset( $_POST['make_school_branch_submit'] ) ) {
            $this->process_branch_form();
        }

        // Handle Class form submission
        if ( isset( $_POST['make_school_class_submit'] ) ) {
            $this->process_class_form();
        }

        // Handle Exam form submission
        if ( isset( $_POST['make_school_exam_submit'] ) ) {
            $this->process_exam_form();
        }

        // Handle Admission Approval
        if ( isset( $_GET['ms_action'] ) && $_GET['ms_action'] === 'approve' && isset( $_GET['admission_id'] ) ) {
            $this->process_admission_approval();
        }

        // Handle Admission Rejection
        if ( isset( $_GET['ms_action'] ) && $_GET['ms_action'] === 'reject' && isset( $_GET['admission_id'] ) ) {
            $this->process_admission_rejection();
        }

        // Handle Branch Deletion
        if ( isset( $_GET['ms_action'] ) && $_GET['ms_action'] === 'delete_branch' && isset( $_GET['branch_id'] ) ) {
            $this->process_branch_deletion();
        }

        // Handle Class Deletion
        if ( isset( $_GET['ms_action'] ) && $_GET['ms_action'] === 'delete_class' && isset( $_GET['class_id'] ) ) {
            $this->process_class_deletion();
        }
    }


    /**
     * Display admin notices for operations
     */
    public function display_admin_notices() {
        if ( isset( $_GET['ms_notice'] ) ) {
            $type    = isset( $_GET['ms_type'] ) ? sanitize_text_field( wp_unslash( $_GET['ms_type'] ) ) : 'success';
            $message = '';

            switch ( sanitize_text_field( wp_unslash( $_GET['ms_notice'] ) ) ) {
                case 'branch_added':
                    $message = __( 'Branch has been successfully added.', 'make-school' );
                    break;
                case 'branch_deleted':
                    $message = __( 'Branch has been successfully deleted.', 'make-school' );
                    break;
                case 'class_added':
                    $message = __( 'Class & Section has been successfully added.', 'make-school' );
                    break;
                case 'class_deleted':
                    $message = __( 'Class has been successfully deleted.', 'make-school' );
                    break;
                case 'admission_approved':
                    $username = isset( $_GET['ms_username'] ) ? sanitize_text_field( wp_unslash( $_GET['ms_username'] ) ) : '';
                    $password = isset( $_GET['ms_password'] ) ? sanitize_text_field( wp_unslash( $_GET['ms_password'] ) ) : '';
                    $message  = sprintf(
                        __( 'Admission APPROVED! Student account created. Username: %s | Password: %s — Please save these credentials securely.', 'make-school' ),
                        '<strong>' . esc_html( $username ) . '</strong>',
                        '<strong>' . esc_html( $password ) . '</strong>'
                    );
                    break;
                case 'admission_rejected':
                    $message = __( 'Admission has been rejected.', 'make-school' );
                    break;
                case 'exam_added':
                    $message = __( 'Exam has been successfully scheduled.', 'make-school' );
                    break;
                case 'error':
                    $message = __( 'An error occurred. Please try again.', 'make-school' );
                    $type    = 'error';
                    break;
                case 'nonce_failed':
                    $message = __( 'Security verification failed. Please try again.', 'make-school' );
                    $type    = 'error';
                    break;
            }

            if ( $message ) {
                printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $type ), wp_kses_post( $message ) );
            }
        }
    }


    // ═══════════════════════════════════════════════════════════════
    // DASHBOARD PAGE
    // ═══════════════════════════════════════════════════════════════

    /**
     * Render the main dashboard page
     */
    public function render_dashboard_page() {
        global $wpdb;

        $total_branches   = Make_School_DB::count_rows( 'branches' );
        $total_classes    = Make_School_DB::count_rows( 'classes' );
        $total_admissions = Make_School_DB::count_rows( 'admissions', array( 'status' => 'pending' ) );
        $total_students   = count( get_users( array( 'role' => 'make_school_student', 'fields' => 'ID' ) ) );
        $total_teachers   = count( get_users( array( 'role' => 'make_school_teacher', 'fields' => 'ID' ) ) );
        $total_invoices   = Make_School_DB::count_rows( 'invoices', array( 'status' => 'unpaid' ) );

        ?>
        <div class="wrap make-school-wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'MAKE SCHOOL — Dashboard', 'make-school' ); ?></h1>
            <hr class="wp-header-end">

            <div class="make-school-dashboard-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap:20px; margin-top:20px;">

                <div class="make-school-card" style="background:#fff; border-left:4px solid #0073aa; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin:0 0 5px; color:#0073aa;"><?php echo esc_html( $total_branches ); ?></h3>
                    <p style="margin:0; color:#666;"><?php esc_html_e( 'Total Branches', 'make-school' ); ?></p>
                </div>

                <div class="make-school-card" style="background:#fff; border-left:4px solid #00a32a; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin:0 0 5px; color:#00a32a;"><?php echo esc_html( $total_classes ); ?></h3>
                    <p style="margin:0; color:#666;"><?php esc_html_e( 'Total Classes', 'make-school' ); ?></p>
                </div>

                <div class="make-school-card" style="background:#fff; border-left:4px solid #dba617; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin:0 0 5px; color:#dba617;"><?php echo esc_html( $total_admissions ); ?></h3>
                    <p style="margin:0; color:#666;"><?php esc_html_e( 'Pending Admissions', 'make-school' ); ?></p>
                </div>

                <div class="make-school-card" style="background:#fff; border-left:4px solid #8c5ed5; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin:0 0 5px; color:#8c5ed5;"><?php echo esc_html( $total_students ); ?></h3>
                    <p style="margin:0; color:#666;"><?php esc_html_e( 'Active Students', 'make-school' ); ?></p>
                </div>

                <div class="make-school-card" style="background:#fff; border-left:4px solid #d63638; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin:0 0 5px; color:#d63638;"><?php echo esc_html( $total_teachers ); ?></h3>
                    <p style="margin:0; color:#666;"><?php esc_html_e( 'Active Teachers', 'make-school' ); ?></p>
                </div>

                <div class="make-school-card" style="background:#fff; border-left:4px solid #e65100; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin:0 0 5px; color:#e65100;"><?php echo esc_html( $total_invoices ); ?></h3>
                    <p style="margin:0; color:#666;"><?php esc_html_e( 'Unpaid Invoices', 'make-school' ); ?></p>
                </div>

            </div>
        </div>
        <?php
    }


    // ═══════════════════════════════════════════════════════════════
    // BRANCHES MANAGEMENT
    // ═══════════════════════════════════════════════════════════════

    /**
     * Render branches management page
     */
    public function render_branches_page() {
        global $wpdb;

        $table = Make_School_DB::get_table( 'branches' );
        $branches = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC" );

        ?>
        <div class="wrap make-school-wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Manage Branches', 'make-school' ); ?></h1>
            <hr class="wp-header-end">

            <!-- Add Branch Form -->
            <div class="make-school-form-section" style="background:#fff; padding:25px; margin:20px 0; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                <h2><?php esc_html_e( 'Add New Branch', 'make-school' ); ?></h2>
                <form method="post" action="">
                    <?php wp_nonce_field( 'make_school_branch_nonce', 'make_school_branch_nonce_field' ); ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="branch_name"><?php esc_html_e( 'Branch Name', 'make-school' ); ?></label>
                            </th>
                            <td>
                                <input type="text" id="branch_name" name="branch_name" class="regular-text" required
                                       placeholder="<?php esc_attr_e( 'e.g. Main Campus', 'make-school' ); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="branch_code"><?php esc_html_e( 'Branch Code', 'make-school' ); ?></label>
                            </th>
                            <td>
                                <input type="text" id="branch_code" name="branch_code" class="regular-text" required
                                       placeholder="<?php esc_attr_e( 'e.g. MC-001', 'make-school' ); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="branch_address"><?php esc_html_e( 'Address', 'make-school' ); ?></label>
                            </th>
                            <td>
                                <textarea id="branch_address" name="branch_address" class="large-text" rows="3"
                                          placeholder="<?php esc_attr_e( 'Full address of the branch', 'make-school' ); ?>"></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="branch_phone"><?php esc_html_e( 'Phone', 'make-school' ); ?></label>
                            </th>
                            <td>
                                <input type="text" id="branch_phone" name="branch_phone" class="regular-text"
                                       placeholder="<?php esc_attr_e( '+1-234-567-8900', 'make-school' ); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="branch_email"><?php esc_html_e( 'Email', 'make-school' ); ?></label>
                            </th>
                            <td>
                                <input type="email" id="branch_email" name="branch_email" class="regular-text"
                                       placeholder="<?php esc_attr_e( 'branch@school.com', 'make-school' ); ?>">
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <input type="submit" name="make_school_branch_submit" class="button button-primary"
                               value="<?php esc_attr_e( 'Add Branch', 'make-school' ); ?>">
                    </p>
                </form>
            </div>


            <!-- Branches List Table -->
            <div class="make-school-table-section" style="background:#fff; padding:25px; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                <h2><?php esc_html_e( 'All Branches', 'make-school' ); ?></h2>
                <?php if ( empty( $branches ) ) : ?>
                    <p><?php esc_html_e( 'No branches found. Add your first branch above.', 'make-school' ); ?></p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th width="5%"><?php esc_html_e( 'ID', 'make-school' ); ?></th>
                                <th width="20%"><?php esc_html_e( 'Branch Name', 'make-school' ); ?></th>
                                <th width="10%"><?php esc_html_e( 'Code', 'make-school' ); ?></th>
                                <th width="25%"><?php esc_html_e( 'Address', 'make-school' ); ?></th>
                                <th width="12%"><?php esc_html_e( 'Phone', 'make-school' ); ?></th>
                                <th width="15%"><?php esc_html_e( 'Email', 'make-school' ); ?></th>
                                <th width="13%"><?php esc_html_e( 'Actions', 'make-school' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $branches as $branch ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $branch->id ); ?></td>
                                    <td><strong><?php echo esc_html( $branch->branch_name ); ?></strong></td>
                                    <td><code><?php echo esc_html( $branch->code ); ?></code></td>
                                    <td><?php echo esc_html( $branch->address ); ?></td>
                                    <td><?php echo esc_html( $branch->phone ); ?></td>
                                    <td><?php echo esc_html( $branch->email ); ?></td>
                                    <td>
                                        <?php
                                        $delete_url = wp_nonce_url(
                                            admin_url( 'admin.php?page=make-school-branches&ms_action=delete_branch&branch_id=' . intval( $branch->id ) ),
                                            'make_school_delete_branch_' . intval( $branch->id )
                                        );
                                        ?>
                                        <a href="<?php echo esc_url( $delete_url ); ?>" class="button button-small"
                                           onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this branch?', 'make-school' ); ?>');">
                                            <?php esc_html_e( 'Delete', 'make-school' ); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Process branch form submission
     */
    private function process_branch_form() {
        if ( ! isset( $_POST['make_school_branch_nonce_field'] ) ||
             ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['make_school_branch_nonce_field'] ) ), 'make_school_branch_nonce' ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=make-school-branches&ms_notice=nonce_failed&ms_type=error' ) );
            exit;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized access.', 'make-school' ) );
        }

        global $wpdb;

        $branch_name = sanitize_text_field( wp_unslash( $_POST['branch_name'] ?? '' ) );
        $code        = sanitize_text_field( wp_unslash( $_POST['branch_code'] ?? '' ) );
        $address     = sanitize_textarea_field( wp_unslash( $_POST['branch_address'] ?? '' ) );
        $phone       = sanitize_text_field( wp_unslash( $_POST['branch_phone'] ?? '' ) );
        $email       = sanitize_email( wp_unslash( $_POST['branch_email'] ?? '' ) );

        $table = Make_School_DB::get_table( 'branches' );

        $result = $wpdb->insert(
            $table,
            array(
                'branch_name' => $branch_name,
                'code'        => $code,
                'address'     => $address,
                'phone'       => $phone,
                'email'       => $email,
            ),
            array( '%s', '%s', '%s', '%s', '%s' )
        );

        if ( false !== $result ) {
            wp_safe_redirect( admin_url( 'admin.php?page=make-school-branches&ms_notice=branch_added&ms_type=success' ) );
        } else {
            wp_safe_redirect( admin_url( 'admin.php?page=make-school-branches&ms_notice=error&ms_type=error' ) );
        }
        exit;
    }

    /**
     * Process branch deletion
     */
    private function process_branch_deletion() {
        $branch_id = intval( $_GET['branch_id'] );

        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'make_school_delete_branch_' . $branch_id ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=make-school-branches&ms_notice=nonce_failed&ms_type=error' ) );
            exit;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized access.', 'make-school' ) );
        }

        global $wpdb;
        $table = Make_School_DB::get_table( 'branches' );

        $wpdb->delete( $table, array( 'id' => $branch_id ), array( '%d' ) );

        wp_safe_redirect( admin_url( 'admin.php?page=make-school-branches&ms_notice=branch_deleted&ms_type=success' ) );
        exit;
    }


    // ═══════════════════════════════════════════════════════════════
    // CLASSES & SECTIONS MANAGEMENT
    // ═══════════════════════════════════════════════════════════════

    /**
     * Render classes and sections management page
     */
    public function render_classes_page() {
        global $wpdb;

        $classes_table  = Make_School_DB::get_table( 'classes' );
        $branches_table = Make_School_DB::get_table( 'branches' );

        $classes = $wpdb->get_results(
            "SELECT c.*, b.branch_name
             FROM {$classes_table} c
             LEFT JOIN {$branches_table} b ON c.branch_id = b.id
             ORDER BY c.id DESC"
        );

        $branches = $wpdb->get_results( "SELECT id, branch_name FROM {$branches_table} ORDER BY branch_name ASC" );

        ?>
        <div class="wrap make-school-wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Classes & Sections', 'make-school' ); ?></h1>
            <hr class="wp-header-end">

            <!-- Add Class Form -->
            <div class="make-school-form-section" style="background:#fff; padding:25px; margin:20px 0; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                <h2><?php esc_html_e( 'Add New Class', 'make-school' ); ?></h2>
                <form method="post" action="">
                    <?php wp_nonce_field( 'make_school_class_nonce', 'make_school_class_nonce_field' ); ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="class_branch_id"><?php esc_html_e( 'Branch', 'make-school' ); ?></label>
                            </th>
                            <td>
                                <select id="class_branch_id" name="class_branch_id" class="regular-text" required>
                                    <option value=""><?php esc_html_e( '— Select Branch —', 'make-school' ); ?></option>
                                    <?php foreach ( $branches as $branch ) : ?>
                                        <option value="<?php echo esc_attr( $branch->id ); ?>">
                                            <?php echo esc_html( $branch->branch_name ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ( empty( $branches ) ) : ?>
                                    <p class="description" style="color:#d63638;">
                                        <?php esc_html_e( 'Please add a branch first.', 'make-school' ); ?>
                                    </p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="class_name"><?php esc_html_e( 'Class Name', 'make-school' ); ?></label>
                            </th>
                            <td>
                                <input type="text" id="class_name" name="class_name" class="regular-text" required
                                       placeholder="<?php esc_attr_e( 'e.g. Grade 10, Class XII', 'make-school' ); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="section_name"><?php esc_html_e( 'Section Name', 'make-school' ); ?></label>
                            </th>
                            <td>
                                <input type="text" id="section_name" name="section_name" class="regular-text" required
                                       placeholder="<?php esc_attr_e( 'e.g. A, B, Science, Commerce', 'make-school' ); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="class_capacity"><?php esc_html_e( 'Capacity', 'make-school' ); ?></label>
                            </th>
                            <td>
                                <input type="number" id="class_capacity" name="class_capacity" class="small-text"
                                       min="1" max="500" value="40"
                                       placeholder="<?php esc_attr_e( '40', 'make-school' ); ?>">
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <input type="submit" name="make_school_class_submit" class="button button-primary"
                               value="<?php esc_attr_e( 'Add Class', 'make-school' ); ?>">
                    </p>
                </form>
            </div>


            <!-- Classes List Table -->
            <div class="make-school-table-section" style="background:#fff; padding:25px; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                <h2><?php esc_html_e( 'All Classes', 'make-school' ); ?></h2>
                <?php if ( empty( $classes ) ) : ?>
                    <p><?php esc_html_e( 'No classes found. Add your first class above.', 'make-school' ); ?></p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th width="5%"><?php esc_html_e( 'ID', 'make-school' ); ?></th>
                                <th width="20%"><?php esc_html_e( 'Branch', 'make-school' ); ?></th>
                                <th width="25%"><?php esc_html_e( 'Class Name', 'make-school' ); ?></th>
                                <th width="15%"><?php esc_html_e( 'Section', 'make-school' ); ?></th>
                                <th width="10%"><?php esc_html_e( 'Capacity', 'make-school' ); ?></th>
                                <th width="12%"><?php esc_html_e( 'Status', 'make-school' ); ?></th>
                                <th width="13%"><?php esc_html_e( 'Actions', 'make-school' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $classes as $class ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $class->id ); ?></td>
                                    <td><?php echo esc_html( $class->branch_name ?? __( 'N/A', 'make-school' ) ); ?></td>
                                    <td><strong><?php echo esc_html( $class->class_name ); ?></strong></td>
                                    <td><?php echo esc_html( $class->section_name ); ?></td>
                                    <td><?php echo esc_html( $class->capacity ); ?></td>
                                    <td>
                                        <span style="background:<?php echo $class->status === 'active' ? '#00a32a' : '#d63638'; ?>; color:#fff; padding:2px 8px; border-radius:3px; font-size:12px;">
                                            <?php echo esc_html( ucfirst( $class->status ) ); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $delete_url = wp_nonce_url(
                                            admin_url( 'admin.php?page=make-school-classes&ms_action=delete_class&class_id=' . intval( $class->id ) ),
                                            'make_school_delete_class_' . intval( $class->id )
                                        );
                                        ?>
                                        <a href="<?php echo esc_url( $delete_url ); ?>" class="button button-small"
                                           onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this class?', 'make-school' ); ?>');">
                                            <?php esc_html_e( 'Delete', 'make-school' ); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Process class form submission
     */
    private function process_class_form() {
        if ( ! isset( $_POST['make_school_class_nonce_field'] ) ||
             ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['make_school_class_nonce_field'] ) ), 'make_school_class_nonce' ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=make-school-classes&ms_notice=nonce_failed&ms_type=error' ) );
            exit;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized access.', 'make-school' ) );
        }

        global $wpdb;

        $branch_id    = intval( $_POST['class_branch_id'] ?? 0 );
        $class_name   = sanitize_text_field( wp_unslash( $_POST['class_name'] ?? '' ) );
        $section_name = sanitize_text_field( wp_unslash( $_POST['section_name'] ?? '' ) );
        $capacity     = intval( $_POST['class_capacity'] ?? 40 );

        $table = Make_School_DB::get_table( 'classes' );

        $result = $wpdb->insert(
            $table,
            array(
                'branch_id'    => $branch_id,
                'class_name'   => $class_name,
                'section_name' => $section_name,
                'capacity'     => $capacity,
            ),
            array( '%d', '%s', '%s', '%d' )
        );

        if ( false !== $result ) {
            wp_safe_redirect( admin_url( 'admin.php?page=make-school-classes&ms_notice=class_added&ms_type=success' ) );
        } else {
            wp_safe_redirect( admin_url( 'admin.php?page=make-school-classes&ms_notice=error&ms_type=error' ) );
        }
        exit;
    }

    /**
     * Process class deletion
     */
    private function process_class_deletion() {
        $class_id = intval( $_GET['class_id'] );

        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'make_school_delete_class_' . $class_id ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=make-school-classes&ms_notice=nonce_failed&ms_type=error' ) );
            exit;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized access.', 'make-school' ) );
        }

        global $wpdb;
        $table = Make_School_DB::get_table( 'classes' );

        $wpdb->delete( $table, array( 'id' => $class_id ), array( '%d' ) );

        wp_safe_redirect( admin_url( 'admin.php?page=make-school-classes&ms_notice=class_deleted&ms_type=success' ) );
        exit;
    }


    // ═══════════════════════════════════════════════════════════════
    // ADMISSION REQUESTS MANAGEMENT
    // ═══════════════════════════════════════════════════════════════

    /**
     * Render admission requests management page
     */
    public function render_admissions_page() {
        global $wpdb;

        $admissions_table = Make_School_DB::get_table( 'admissions' );
        $classes_table    = Make_School_DB::get_table( 'classes' );

        // Filter by status
        $status_filter = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : 'pending';
        $valid_statuses = array( 'pending', 'approved', 'rejected', 'all' );
        if ( ! in_array( $status_filter, $valid_statuses, true ) ) {
            $status_filter = 'pending';
        }

        if ( 'all' === $status_filter ) {
            $admissions = $wpdb->get_results(
                "SELECT a.*, c.class_name, c.section_name
                 FROM {$admissions_table} a
                 LEFT JOIN {$classes_table} c ON a.class_id = c.id
                 ORDER BY a.created_at DESC"
            );
        } else {
            $admissions = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT a.*, c.class_name, c.section_name
                     FROM {$admissions_table} a
                     LEFT JOIN {$classes_table} c ON a.class_id = c.id
                     WHERE a.status = %s
                     ORDER BY a.created_at DESC",
                    $status_filter
                )
            );
        }

        ?>
        <div class="wrap make-school-wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Admission Requests', 'make-school' ); ?></h1>
            <hr class="wp-header-end">

            <!-- Status Filter Tabs -->
            <ul class="subsubsub" style="margin-bottom:15px;">
                <li>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=make-school-admissions&status=all' ) ); ?>"
                       class="<?php echo 'all' === $status_filter ? 'current' : ''; ?>">
                        <?php esc_html_e( 'All', 'make-school' ); ?>
                    </a> |
                </li>
                <li>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=make-school-admissions&status=pending' ) ); ?>"
                       class="<?php echo 'pending' === $status_filter ? 'current' : ''; ?>">
                        <?php esc_html_e( 'Pending', 'make-school' ); ?>
                    </a> |
                </li>
                <li>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=make-school-admissions&status=approved' ) ); ?>"
                       class="<?php echo 'approved' === $status_filter ? 'current' : ''; ?>">
                        <?php esc_html_e( 'Approved', 'make-school' ); ?>
                    </a> |
                </li>
                <li>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=make-school-admissions&status=rejected' ) ); ?>"
                       class="<?php echo 'rejected' === $status_filter ? 'current' : ''; ?>">
                        <?php esc_html_e( 'Rejected', 'make-school' ); ?>
                    </a>
                </li>
            </ul>

            <div style="clear:both;"></div>


            <!-- Admissions Table -->
            <div class="make-school-table-section" style="background:#fff; padding:25px; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                <?php if ( empty( $admissions ) ) : ?>
                    <p><?php esc_html_e( 'No admission requests found for this filter.', 'make-school' ); ?></p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th width="3%"><?php esc_html_e( 'ID', 'make-school' ); ?></th>
                                <th width="14%"><?php esc_html_e( 'Full Name', 'make-school' ); ?></th>
                                <th width="8%"><?php esc_html_e( 'DOB', 'make-school' ); ?></th>
                                <th width="6%"><?php esc_html_e( 'Gender', 'make-school' ); ?></th>
                                <th width="12%"><?php esc_html_e( 'Parent Name', 'make-school' ); ?></th>
                                <th width="12%"><?php esc_html_e( 'Email', 'make-school' ); ?></th>
                                <th width="9%"><?php esc_html_e( 'Phone', 'make-school' ); ?></th>
                                <th width="12%"><?php esc_html_e( 'Class Applied', 'make-school' ); ?></th>
                                <th width="8%"><?php esc_html_e( 'Status', 'make-school' ); ?></th>
                                <th width="8%"><?php esc_html_e( 'Date', 'make-school' ); ?></th>
                                <th width="8%"><?php esc_html_e( 'Actions', 'make-school' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $admissions as $admission ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $admission->id ); ?></td>
                                    <td><strong><?php echo esc_html( $admission->full_name ); ?></strong></td>
                                    <td><?php echo esc_html( $admission->dob ); ?></td>
                                    <td><?php echo esc_html( ucfirst( $admission->gender ) ); ?></td>
                                    <td><?php echo esc_html( $admission->parent_name ); ?></td>
                                    <td><?php echo esc_html( $admission->email ); ?></td>
                                    <td><?php echo esc_html( $admission->phone ); ?></td>
                                    <td>
                                        <?php
                                        $class_display = $admission->class_name ? $admission->class_name . ' - ' . $admission->section_name : __( 'N/A', 'make-school' );
                                        echo esc_html( $class_display );
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status_colors = array(
                                            'pending'  => '#dba617',
                                            'approved' => '#00a32a',
                                            'rejected' => '#d63638',
                                        );
                                        $color = $status_colors[ $admission->status ] ?? '#666';
                                        ?>
                                        <span style="background:<?php echo esc_attr( $color ); ?>; color:#fff; padding:2px 8px; border-radius:3px; font-size:11px;">
                                            <?php echo esc_html( ucfirst( $admission->status ) ); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $admission->created_at ) ) ); ?></td>
                                    <td>
                                        <?php if ( 'pending' === $admission->status ) : ?>
                                            <?php
                                            $approve_url = wp_nonce_url(
                                                admin_url( 'admin.php?page=make-school-admissions&ms_action=approve&admission_id=' . intval( $admission->id ) ),
                                                'make_school_approve_admission_' . intval( $admission->id )
                                            );
                                            $reject_url = wp_nonce_url(
                                                admin_url( 'admin.php?page=make-school-admissions&ms_action=reject&admission_id=' . intval( $admission->id ) ),
                                                'make_school_reject_admission_' . intval( $admission->id )
                                            );
                                            ?>
                                            <a href="<?php echo esc_url( $approve_url ); ?>" class="button button-small button-primary"
                                               style="margin-bottom:3px;">
                                                <?php esc_html_e( 'Approve', 'make-school' ); ?>
                                            </a>
                                            <a href="<?php echo esc_url( $reject_url ); ?>" class="button button-small"
                                               onclick="return confirm('<?php esc_attr_e( 'Reject this admission?', 'make-school' ); ?>');">
                                                <?php esc_html_e( 'Reject', 'make-school' ); ?>
                                            </a>
                                        <?php else : ?>
                                            <em><?php echo esc_html( ucfirst( $admission->status ) ); ?></em>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }


    /**
     * Process admission approval
     *
     * Creates a WordPress user account for the approved student,
     * sets role to make_school_student, saves class_id to user meta,
     * and updates admission status.
     */
    private function process_admission_approval() {
        $admission_id = intval( $_GET['admission_id'] );

        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'make_school_approve_admission_' . $admission_id ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=make-school-admissions&ms_notice=nonce_failed&ms_type=error' ) );
            exit;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized access.', 'make-school' ) );
        }

        global $wpdb;

        $admissions_table = Make_School_DB::get_table( 'admissions' );

        // Fetch the admission record
        $admission = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$admissions_table} WHERE id = %d AND status = 'pending'", $admission_id )
        );

        if ( ! $admission ) {
            wp_safe_redirect( admin_url( 'admin.php?page=make-school-admissions&ms_notice=error&ms_type=error' ) );
            exit;
        }

        // Generate a unique username from the student's name
        $base_username = sanitize_user( strtolower( str_replace( ' ', '.', $admission->full_name ) ), true );
        $username      = $base_username;
        $counter       = 1;

        while ( username_exists( $username ) ) {
            $username = $base_username . $counter;
            $counter++;
        }

        // Generate a secure random password
        $password = wp_generate_password( 12, true, false );

        // Create the WordPress user
        $user_id = wp_create_user( $username, $password, $admission->email );

        if ( is_wp_error( $user_id ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=make-school-admissions&ms_notice=error&ms_type=error' ) );
            exit;
        }

        // Set user role to student
        $user = new WP_User( $user_id );
        $user->set_role( 'make_school_student' );

        // Update user display name and meta
        wp_update_user( array(
            'ID'           => $user_id,
            'display_name' => $admission->full_name,
            'first_name'   => explode( ' ', $admission->full_name )[0],
            'last_name'    => implode( ' ', array_slice( explode( ' ', $admission->full_name ), 1 ) ),
        ) );

        // Save student-specific meta data
        update_user_meta( $user_id, 'make_school_class_id', intval( $admission->class_id ) );
        update_user_meta( $user_id, 'make_school_admission_id', $admission_id );
        update_user_meta( $user_id, 'make_school_dob', $admission->dob );
        update_user_meta( $user_id, 'make_school_gender', $admission->gender );
        update_user_meta( $user_id, 'make_school_blood_group', $admission->blood_group );
        update_user_meta( $user_id, 'make_school_parent_name', $admission->parent_name );
        update_user_meta( $user_id, 'make_school_phone', $admission->phone );
        update_user_meta( $user_id, 'make_school_address', $admission->address );

        // Update admission status to approved
        $wpdb->update(
            $admissions_table,
            array(
                'status'     => 'approved',
                'wp_user_id' => $user_id,
            ),
            array( 'id' => $admission_id ),
            array( '%s', '%d' ),
            array( '%d' )
        );

        // Redirect with success notice showing credentials
        wp_safe_redirect( admin_url(
            'admin.php?page=make-school-admissions&ms_notice=admission_approved&ms_type=success'
            . '&ms_username=' . urlencode( $username )
            . '&ms_password=' . urlencode( $password )
        ) );
        exit;
    }

    /**
     * Process admission rejection
     */
    private function process_admission_rejection() {
        $admission_id = intval( $_GET['admission_id'] );

        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'make_school_reject_admission_' . $admission_id ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=make-school-admissions&ms_notice=nonce_failed&ms_type=error' ) );
            exit;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized access.', 'make-school' ) );
        }

        global $wpdb;
        $admissions_table = Make_School_DB::get_table( 'admissions' );

        $wpdb->update(
            $admissions_table,
            array( 'status' => 'rejected' ),
            array( 'id' => $admission_id ),
            array( '%s' ),
            array( '%d' )
        );

        wp_safe_redirect( admin_url( 'admin.php?page=make-school-admissions&ms_notice=admission_rejected&ms_type=success' ) );
        exit;
    }


    // ═══════════════════════════════════════════════════════════════
    // EXAMS ENGINE
    // ═══════════════════════════════════════════════════════════════

    /**
     * Render exams engine page
     */
    public function render_exams_page() {
        global $wpdb;

        $exams_table   = Make_School_DB::get_table( 'exams' );
        $classes_table = Make_School_DB::get_table( 'classes' );

        $exams = $wpdb->get_results(
            "SELECT e.*, c.class_name, c.section_name
             FROM {$exams_table} e
             LEFT JOIN {$classes_table} c ON e.class_id = c.id
             ORDER BY e.exam_date DESC"
        );

        $classes = $wpdb->get_results( "SELECT id, class_name, section_name FROM {$classes_table} WHERE status = 'active' ORDER BY class_name ASC" );

        ?>
        <div class="wrap make-school-wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Exams Engine', 'make-school' ); ?></h1>
            <hr class="wp-header-end">

            <!-- Add Exam Form -->
            <div class="make-school-form-section" style="background:#fff; padding:25px; margin:20px 0; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                <h2><?php esc_html_e( 'Schedule New Exam', 'make-school' ); ?></h2>
                <form method="post" action="">
                    <?php wp_nonce_field( 'make_school_exam_nonce', 'make_school_exam_nonce_field' ); ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="exam_name"><?php esc_html_e( 'Exam Name', 'make-school' ); ?></label>
                            </th>
                            <td>
                                <input type="text" id="exam_name" name="exam_name" class="regular-text" required
                                       placeholder="<?php esc_attr_e( 'e.g. Mid-Term Examination 2025', 'make-school' ); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="exam_class_id"><?php esc_html_e( 'Class', 'make-school' ); ?></label>
                            </th>
                            <td>
                                <select id="exam_class_id" name="exam_class_id" class="regular-text" required>
                                    <option value=""><?php esc_html_e( '— Select Class —', 'make-school' ); ?></option>
                                    <?php foreach ( $classes as $class ) : ?>
                                        <option value="<?php echo esc_attr( $class->id ); ?>">
                                            <?php echo esc_html( $class->class_name . ' - ' . $class->section_name ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="subject_name"><?php esc_html_e( 'Subject Name', 'make-school' ); ?></label>
                            </th>
                            <td>
                                <input type="text" id="subject_name" name="subject_name" class="regular-text" required
                                       placeholder="<?php esc_attr_e( 'e.g. Mathematics, Physics, English', 'make-school' ); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="exam_date"><?php esc_html_e( 'Exam Date', 'make-school' ); ?></label>
                            </th>
                            <td>
                                <input type="date" id="exam_date" name="exam_date" class="regular-text" required>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="room_number"><?php esc_html_e( 'Room Number', 'make-school' ); ?></label>
                            </th>
                            <td>
                                <input type="text" id="room_number" name="room_number" class="regular-text"
                                       placeholder="<?php esc_attr_e( 'e.g. Room 101, Hall A', 'make-school' ); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="max_marks"><?php esc_html_e( 'Maximum Marks', 'make-school' ); ?></label>
                            </th>
                            <td>
                                <input type="number" id="max_marks" name="max_marks" class="small-text"
                                       min="1" max="1000" value="100">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="pass_marks"><?php esc_html_e( 'Pass Marks', 'make-school' ); ?></label>
                            </th>
                            <td>
                                <input type="number" id="pass_marks" name="pass_marks" class="small-text"
                                       min="0" max="1000" value="33">
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <input type="submit" name="make_school_exam_submit" class="button button-primary"
                               value="<?php esc_attr_e( 'Schedule Exam', 'make-school' ); ?>">
                    </p>
                </form>
            </div>


            <!-- Exams List Table -->
            <div class="make-school-table-section" style="background:#fff; padding:25px; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                <h2><?php esc_html_e( 'Scheduled Exams', 'make-school' ); ?></h2>
                <?php if ( empty( $exams ) ) : ?>
                    <p><?php esc_html_e( 'No exams scheduled yet.', 'make-school' ); ?></p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th width="3%"><?php esc_html_e( 'ID', 'make-school' ); ?></th>
                                <th width="20%"><?php esc_html_e( 'Exam Name', 'make-school' ); ?></th>
                                <th width="15%"><?php esc_html_e( 'Class', 'make-school' ); ?></th>
                                <th width="15%"><?php esc_html_e( 'Subject', 'make-school' ); ?></th>
                                <th width="12%"><?php esc_html_e( 'Date', 'make-school' ); ?></th>
                                <th width="10%"><?php esc_html_e( 'Room', 'make-school' ); ?></th>
                                <th width="10%"><?php esc_html_e( 'Max Marks', 'make-school' ); ?></th>
                                <th width="8%"><?php esc_html_e( 'Pass', 'make-school' ); ?></th>
                                <th width="7%"><?php esc_html_e( 'Status', 'make-school' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $exams as $exam ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $exam->id ); ?></td>
                                    <td><strong><?php echo esc_html( $exam->exam_name ); ?></strong></td>
                                    <td><?php echo esc_html( ( $exam->class_name ?? '' ) . ' - ' . ( $exam->section_name ?? '' ) ); ?></td>
                                    <td><?php echo esc_html( $exam->subject_name ); ?></td>
                                    <td><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $exam->exam_date ) ) ); ?></td>
                                    <td><?php echo esc_html( $exam->room_number ); ?></td>
                                    <td><?php echo esc_html( $exam->max_marks ); ?></td>
                                    <td><?php echo esc_html( $exam->pass_marks ); ?></td>
                                    <td>
                                        <?php
                                        $exam_status_color = $exam->status === 'scheduled' ? '#0073aa' : '#00a32a';
                                        ?>
                                        <span style="background:<?php echo esc_attr( $exam_status_color ); ?>; color:#fff; padding:2px 8px; border-radius:3px; font-size:11px;">
                                            <?php echo esc_html( ucfirst( $exam->status ) ); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Process exam form submission
     */
    private function process_exam_form() {
        if ( ! isset( $_POST['make_school_exam_nonce_field'] ) ||
             ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['make_school_exam_nonce_field'] ) ), 'make_school_exam_nonce' ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=make-school-exams&ms_notice=nonce_failed&ms_type=error' ) );
            exit;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized access.', 'make-school' ) );
        }

        global $wpdb;

        $exam_name    = sanitize_text_field( wp_unslash( $_POST['exam_name'] ?? '' ) );
        $class_id     = intval( $_POST['exam_class_id'] ?? 0 );
        $subject_name = sanitize_text_field( wp_unslash( $_POST['subject_name'] ?? '' ) );
        $exam_date    = sanitize_text_field( wp_unslash( $_POST['exam_date'] ?? '' ) );
        $room_number  = sanitize_text_field( wp_unslash( $_POST['room_number'] ?? '' ) );
        $max_marks    = intval( $_POST['max_marks'] ?? 100 );
        $pass_marks   = intval( $_POST['pass_marks'] ?? 33 );

        $table = Make_School_DB::get_table( 'exams' );

        $result = $wpdb->insert(
            $table,
            array(
                'exam_name'    => $exam_name,
                'class_id'     => $class_id,
                'subject_name' => $subject_name,
                'exam_date'    => $exam_date,
                'room_number'  => $room_number,
                'max_marks'    => $max_marks,
                'pass_marks'   => $pass_marks,
            ),
            array( '%s', '%d', '%s', '%s', '%s', '%d', '%d' )
        );

        if ( false !== $result ) {
            wp_safe_redirect( admin_url( 'admin.php?page=make-school-exams&ms_notice=exam_added&ms_type=success' ) );
        } else {
            wp_safe_redirect( admin_url( 'admin.php?page=make-school-exams&ms_notice=error&ms_type=error' ) );
        }
        exit;
    }

} // End class Make_School_Admin

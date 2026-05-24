<?php
/**
 * MAKE SCHOOL - Fees & Billing Management Class
 *
 * Handles fee structure creation, bulk invoice generation,
 * payment tracking, and financial reporting for the admin panel.
 *
 * @package    Make_School
 * @subpackage Make_School/admin
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Make_School_Fees {

    /**
     * Constructor - Register hooks for fees management
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_fees_menu' ) );
        add_action( 'admin_init', array( $this, 'handle_fees_submissions' ) );
    }

    /**
     * Register the Fees Control submenu
     */
    public function register_fees_menu() {
        add_submenu_page(
            'make-school',
            __( 'Fees Control', 'make-school' ),
            __( 'Fees Control', 'make-school' ),
            'manage_options',
            'make-school-fees',
            array( $this, 'render_fees_page' )
        );
    }


    /**
     * Handle fees-related form submissions
     */
    public function handle_fees_submissions() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Handle Bulk Invoice Generation
        if ( isset( $_POST['make_school_generate_invoices'] ) ) {
            $this->process_bulk_invoice_generation();
        }

        // Handle individual invoice status update
        if ( isset( $_GET['ms_action'] ) && $_GET['ms_action'] === 'mark_paid' && isset( $_GET['invoice_id'] ) ) {
            $this->process_mark_invoice_paid();
        }
    }

    /**
     * Render the main Fees Control page
     */
    public function render_fees_page() {
        global $wpdb;

        $classes_table  = Make_School_DB::get_table( 'classes' );
        $invoices_table = Make_School_DB::get_table( 'invoices' );

        // Fetch active classes for the dropdown
        $classes = $wpdb->get_results(
            "SELECT id, class_name, section_name FROM {$classes_table} WHERE status = 'active' ORDER BY class_name ASC"
        );

        // Fetch recent invoices with student info
        $filter_class  = isset( $_GET['filter_class'] ) ? intval( $_GET['filter_class'] ) : 0;
        $filter_status = isset( $_GET['filter_status'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_status'] ) ) : '';

        $where_clauses = array();
        $where_values  = array();

        if ( $filter_class > 0 ) {
            $where_clauses[] = 'i.class_id = %d';
            $where_values[]  = $filter_class;
        }

        if ( in_array( $filter_status, array( 'paid', 'unpaid', 'partial' ), true ) ) {
            $where_clauses[] = 'i.status = %s';
            $where_values[]  = $filter_status;
        }

        $where_sql = '';
        if ( ! empty( $where_clauses ) ) {
            $where_sql = 'WHERE ' . implode( ' AND ', $where_clauses );
        }

        if ( ! empty( $where_values ) ) {
            $query = $wpdb->prepare(
                "SELECT i.*, c.class_name, c.section_name
                 FROM {$invoices_table} i
                 LEFT JOIN {$classes_table} c ON i.class_id = c.id
                 {$where_sql}
                 ORDER BY i.created_at DESC
                 LIMIT 200",
                ...$where_values
            );
        } else {
            $query = "SELECT i.*, c.class_name, c.section_name
                      FROM {$invoices_table} i
                      LEFT JOIN {$classes_table} c ON i.class_id = c.id
                      ORDER BY i.created_at DESC
                      LIMIT 200";
        }

        $invoices = $wpdb->get_results( $query );

        // Calculate summary statistics
        $total_billed = $wpdb->get_var( "SELECT SUM(amount) FROM {$invoices_table}" );
        $total_paid   = $wpdb->get_var( "SELECT SUM(amount) FROM {$invoices_table} WHERE status = 'paid'" );
        $total_unpaid = $wpdb->get_var( "SELECT SUM(amount) FROM {$invoices_table} WHERE status = 'unpaid'" );

        ?>
        <div class="wrap make-school-wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Fees Control Panel', 'make-school' ); ?></h1>
            <hr class="wp-header-end">


            <!-- Financial Summary Cards -->
            <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:15px; margin:20px 0;">
                <div style="background:#fff; border-left:4px solid #0073aa; padding:15px; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin:0 0 5px; color:#0073aa;">
                        <?php echo esc_html( number_format( (float) $total_billed, 2 ) ); ?>
                    </h3>
                    <p style="margin:0; color:#666; font-size:13px;"><?php esc_html_e( 'Total Billed', 'make-school' ); ?></p>
                </div>
                <div style="background:#fff; border-left:4px solid #00a32a; padding:15px; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin:0 0 5px; color:#00a32a;">
                        <?php echo esc_html( number_format( (float) $total_paid, 2 ) ); ?>
                    </h3>
                    <p style="margin:0; color:#666; font-size:13px;"><?php esc_html_e( 'Total Collected', 'make-school' ); ?></p>
                </div>
                <div style="background:#fff; border-left:4px solid #d63638; padding:15px; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin:0 0 5px; color:#d63638;">
                        <?php echo esc_html( number_format( (float) $total_unpaid, 2 ) ); ?>
                    </h3>
                    <p style="margin:0; color:#666; font-size:13px;"><?php esc_html_e( 'Outstanding Balance', 'make-school' ); ?></p>
                </div>
            </div>

            <!-- Bulk Invoice Generation Form -->
            <div class="make-school-form-section" style="background:#fff; padding:25px; margin:20px 0; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                <h2><?php esc_html_e( 'Generate Bulk Invoices', 'make-school' ); ?></h2>
                <p class="description" style="margin-bottom:15px;">
                    <?php esc_html_e( 'Select a class and fee details below. An individual invoice will be created for every student enrolled in the selected class.', 'make-school' ); ?>
                </p>

                <form method="post" action="">
                    <?php wp_nonce_field( 'make_school_fees_nonce', 'make_school_fees_nonce_field' ); ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="fee_class_id"><?php esc_html_e( 'Target Class', 'make-school' ); ?></label>
                            </th>
                            <td>
                                <select id="fee_class_id" name="fee_class_id" class="regular-text" required>
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
                                <label for="fee_type"><?php esc_html_e( 'Fee Category', 'make-school' ); ?></label>
                            </th>
                            <td>
                                <select id="fee_type" name="fee_type" class="regular-text" required>
                                    <option value=""><?php esc_html_e( '— Select Fee Type —', 'make-school' ); ?></option>
                                    <option value="Tuition Fee"><?php esc_html_e( 'Tuition Fee', 'make-school' ); ?></option>
                                    <option value="Registration Fee"><?php esc_html_e( 'Registration Fee', 'make-school' ); ?></option>
                                    <option value="Exam Fee"><?php esc_html_e( 'Exam Fee', 'make-school' ); ?></option>
                                    <option value="Transport Fee"><?php esc_html_e( 'Transport Fee', 'make-school' ); ?></option>
                                    <option value="Library Fee"><?php esc_html_e( 'Library Fee', 'make-school' ); ?></option>
                                    <option value="Lab Fee"><?php esc_html_e( 'Lab Fee', 'make-school' ); ?></option>
                                    <option value="Sports Fee"><?php esc_html_e( 'Sports Fee', 'make-school' ); ?></option>
                                    <option value="Miscellaneous"><?php esc_html_e( 'Miscellaneous', 'make-school' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="fee_amount"><?php esc_html_e( 'Amount', 'make-school' ); ?></label>
                            </th>
                            <td>
                                <input type="number" id="fee_amount" name="fee_amount" class="regular-text"
                                       min="0.01" step="0.01" required
                                       placeholder="<?php esc_attr_e( 'e.g. 5000.00', 'make-school' ); ?>">
                                <p class="description"><?php esc_html_e( 'Amount per student in your currency.', 'make-school' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="fee_description"><?php esc_html_e( 'Description (Optional)', 'make-school' ); ?></label>
                            </th>
                            <td>
                                <input type="text" id="fee_description" name="fee_description" class="large-text"
                                       placeholder="<?php esc_attr_e( 'e.g. Monthly Tuition - January 2025', 'make-school' ); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="fee_due_date"><?php esc_html_e( 'Due Date', 'make-school' ); ?></label>
                            </th>
                            <td>
                                <input type="date" id="fee_due_date" name="fee_due_date" class="regular-text" required>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <input type="submit" name="make_school_generate_invoices" class="button button-primary button-hero"
                               value="<?php esc_attr_e( 'Generate Invoices for All Students in Class', 'make-school' ); ?>"
                               onclick="return confirm('<?php esc_attr_e( 'This will create an unpaid invoice for every student in the selected class. Continue?', 'make-school' ); ?>');">
                    </p>
                </form>
            </div>


            <!-- Invoice Filter -->
            <div style="background:#fff; padding:15px 25px; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                <form method="get" action="" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                    <input type="hidden" name="page" value="make-school-fees">

                    <label><strong><?php esc_html_e( 'Filter:', 'make-school' ); ?></strong></label>

                    <select name="filter_class">
                        <option value="0"><?php esc_html_e( 'All Classes', 'make-school' ); ?></option>
                        <?php foreach ( $classes as $class ) : ?>
                            <option value="<?php echo esc_attr( $class->id ); ?>"
                                <?php selected( $filter_class, $class->id ); ?>>
                                <?php echo esc_html( $class->class_name . ' - ' . $class->section_name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="filter_status">
                        <option value=""><?php esc_html_e( 'All Statuses', 'make-school' ); ?></option>
                        <option value="unpaid" <?php selected( $filter_status, 'unpaid' ); ?>><?php esc_html_e( 'Unpaid', 'make-school' ); ?></option>
                        <option value="paid" <?php selected( $filter_status, 'paid' ); ?>><?php esc_html_e( 'Paid', 'make-school' ); ?></option>
                        <option value="partial" <?php selected( $filter_status, 'partial' ); ?>><?php esc_html_e( 'Partial', 'make-school' ); ?></option>
                    </select>

                    <input type="submit" class="button" value="<?php esc_attr_e( 'Apply Filter', 'make-school' ); ?>">
                </form>
            </div>

            <!-- Invoices List Table -->
            <div class="make-school-table-section" style="background:#fff; padding:25px; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                <h2><?php esc_html_e( 'Invoice Records', 'make-school' ); ?></h2>
                <?php if ( empty( $invoices ) ) : ?>
                    <p><?php esc_html_e( 'No invoices found. Use the form above to generate bulk invoices.', 'make-school' ); ?></p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th width="3%"><?php esc_html_e( 'ID', 'make-school' ); ?></th>
                                <th width="12%"><?php esc_html_e( 'Student', 'make-school' ); ?></th>
                                <th width="12%"><?php esc_html_e( 'Class', 'make-school' ); ?></th>
                                <th width="12%"><?php esc_html_e( 'Fee Type', 'make-school' ); ?></th>
                                <th width="10%"><?php esc_html_e( 'Amount', 'make-school' ); ?></th>
                                <th width="10%"><?php esc_html_e( 'Due Date', 'make-school' ); ?></th>
                                <th width="10%"><?php esc_html_e( 'Tracking ID', 'make-school' ); ?></th>
                                <th width="8%"><?php esc_html_e( 'Status', 'make-school' ); ?></th>
                                <th width="10%"><?php esc_html_e( 'Actions', 'make-school' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $invoices as $invoice ) :
                                $student = get_userdata( $invoice->student_id );
                                $student_name = $student ? $student->display_name : __( 'Unknown', 'make-school' );
                            ?>
                                <tr>
                                    <td><?php echo esc_html( $invoice->id ); ?></td>
                                    <td><strong><?php echo esc_html( $student_name ); ?></strong></td>
                                    <td><?php echo esc_html( ( $invoice->class_name ?? '' ) . ' - ' . ( $invoice->section_name ?? '' ) ); ?></td>
                                    <td><?php echo esc_html( $invoice->fee_type ); ?></td>
                                    <td><strong><?php echo esc_html( number_format( (float) $invoice->amount, 2 ) ); ?></strong></td>
                                    <td><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $invoice->due_date ) ) ); ?></td>
                                    <td><code><?php echo esc_html( $invoice->tracking_id ); ?></code></td>
                                    <td>
                                        <?php
                                        $inv_status_colors = array(
                                            'paid'   => '#00a32a',
                                            'unpaid' => '#d63638',
                                            'partial'=> '#dba617',
                                        );
                                        $inv_color = $inv_status_colors[ $invoice->status ] ?? '#666';
                                        ?>
                                        <span style="background:<?php echo esc_attr( $inv_color ); ?>; color:#fff; padding:2px 8px; border-radius:3px; font-size:11px;">
                                            <?php echo esc_html( ucfirst( $invoice->status ) ); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ( 'unpaid' === $invoice->status || 'partial' === $invoice->status ) : ?>
                                            <?php
                                            $mark_paid_url = wp_nonce_url(
                                                admin_url( 'admin.php?page=make-school-fees&ms_action=mark_paid&invoice_id=' . intval( $invoice->id ) ),
                                                'make_school_mark_paid_' . intval( $invoice->id )
                                            );
                                            ?>
                                            <a href="<?php echo esc_url( $mark_paid_url ); ?>" class="button button-small"
                                               onclick="return confirm('<?php esc_attr_e( 'Mark this invoice as paid?', 'make-school' ); ?>');">
                                                <?php esc_html_e( 'Mark Paid', 'make-school' ); ?>
                                            </a>
                                        <?php else : ?>
                                            <em style="color:#00a32a;"><?php esc_html_e( 'Paid', 'make-school' ); ?></em>
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
     * Process Bulk Invoice Generation
     *
     * Queries all students in the selected class and creates
     * an individual 'unpaid' invoice record for each one.
     */
    private function process_bulk_invoice_generation() {
        // Verify nonce
        if ( ! isset( $_POST['make_school_fees_nonce_field'] ) ||
             ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['make_school_fees_nonce_field'] ) ), 'make_school_fees_nonce' ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=make-school-fees&ms_notice=nonce_failed&ms_type=error' ) );
            exit;
        }

        // Verify capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized access.', 'make-school' ) );
        }

        global $wpdb;

        // Sanitize all input
        $class_id    = intval( $_POST['fee_class_id'] ?? 0 );
        $fee_type    = sanitize_text_field( wp_unslash( $_POST['fee_type'] ?? '' ) );
        $amount      = floatval( $_POST['fee_amount'] ?? 0 );
        $description = sanitize_text_field( wp_unslash( $_POST['fee_description'] ?? '' ) );
        $due_date    = sanitize_text_field( wp_unslash( $_POST['fee_due_date'] ?? '' ) );

        // Validate required fields
        if ( $class_id <= 0 || empty( $fee_type ) || $amount <= 0 || empty( $due_date ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=make-school-fees&ms_notice=error&ms_type=error' ) );
            exit;
        }

        // Query all students enrolled in the target class
        $students = get_users( array(
            'role'       => 'make_school_student',
            'meta_key'   => 'make_school_class_id',
            'meta_value' => $class_id,
            'fields'     => array( 'ID' ),
        ) );

        if ( empty( $students ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=make-school-fees&ms_notice=no_students&ms_type=error' ) );
            exit;
        }

        $invoices_table = Make_School_DB::get_table( 'invoices' );
        $generated      = 0;

        // Loop through each student and create an individual invoice
        foreach ( $students as $student ) {
            // Generate a unique tracking ID for each invoice
            $tracking_id = 'INV-' . strtoupper( wp_generate_password( 8, false, false ) ) . '-' . $student->ID;

            $result = $wpdb->insert(
                $invoices_table,
                array(
                    'student_id'  => intval( $student->ID ),
                    'class_id'    => $class_id,
                    'fee_type'    => $fee_type,
                    'description' => $description,
                    'amount'      => $amount,
                    'paid_amount' => 0.00,
                    'due_date'    => $due_date,
                    'status'      => 'unpaid',
                    'tracking_id' => $tracking_id,
                ),
                array( '%d', '%d', '%s', '%s', '%f', '%f', '%s', '%s', '%s' )
            );

            if ( false !== $result ) {
                $generated++;
            }
        }

        // Redirect with success notice
        wp_safe_redirect( admin_url(
            'admin.php?page=make-school-fees&ms_notice=invoices_generated&ms_type=success&ms_count=' . $generated
        ) );
        exit;
    }

    /**
     * Process marking an invoice as paid
     */
    private function process_mark_invoice_paid() {
        $invoice_id = intval( $_GET['invoice_id'] );

        // Verify nonce
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'make_school_mark_paid_' . $invoice_id ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=make-school-fees&ms_notice=nonce_failed&ms_type=error' ) );
            exit;
        }

        // Verify capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized access.', 'make-school' ) );
        }

        global $wpdb;
        $invoices_table = Make_School_DB::get_table( 'invoices' );

        // Get the invoice to set paid_amount equal to amount
        $invoice = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$invoices_table} WHERE id = %d", $invoice_id )
        );

        if ( ! $invoice ) {
            wp_safe_redirect( admin_url( 'admin.php?page=make-school-fees&ms_notice=error&ms_type=error' ) );
            exit;
        }

        $wpdb->update(
            $invoices_table,
            array(
                'status'      => 'paid',
                'paid_amount' => $invoice->amount,
                'paid_date'   => current_time( 'Y-m-d' ),
            ),
            array( 'id' => $invoice_id ),
            array( '%s', '%f', '%s' ),
            array( '%d' )
        );

        wp_safe_redirect( admin_url( 'admin.php?page=make-school-fees&ms_notice=invoice_paid&ms_type=success' ) );
        exit;
    }

} // End class Make_School_Fees

<?php
/**
 * SS_Staff_Accountant - Finance module: Fees, Invoices, Payments, Concessions, Income, Expenses.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Staff_Accountant {

    /* ===============================================================
     *  FEES
     * =============================================================== */
    public static function render_fees() {
        global $wpdb;
        $school_id = SS_Helper::active_school_id();
        $tbl = SS_Helper::table( 'fees' );

        if ( isset( $_POST['ss_action'] ) && 'save_fee' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'save_fee' ) ) {
            $id   = (int) ( $_POST['id'] ?? 0 );
            $data = array(
                'class_school_id' => (int) ( $_POST['class_school_id'] ?? 0 ),
                'label'           => sanitize_text_field( wp_unslash( $_POST['label'] ?? '' ) ),
                'amount'          => (float) ( $_POST['amount'] ?? 0 ),
                'due_date'        => sanitize_text_field( wp_unslash( $_POST['due_date'] ?? '' ) ) ?: null,
                'is_recurring'    => empty( $_POST['is_recurring'] ) ? 0 : 1,
                'frequency'       => sanitize_text_field( wp_unslash( $_POST['frequency'] ?? '' ) ),
            );
            if ( $id ) { $wpdb->update( $tbl, $data, array( 'ID' => $id ) ); }
            else      { $data['created_at'] = current_time( 'mysql' ); $wpdb->insert( $tbl, $data ); }
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-fees' ) ); exit;
        }
        if ( 'delete' === ( $_GET['view'] ?? '' ) && SS_Helper::verify_nonce( 'delete_fee' ) ) {
            $wpdb->delete( $tbl, array( 'ID' => (int) $_GET['id'] ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-fees' ) ); exit;
        }

        SS_Admin_Shell::open( __( 'Fee Structure', 'school-softwere' ), 'school-softwere-fees', array(
            array( 'label' => __( 'Fees', 'school-softwere' ) ),
        ) );

        $cs       = self::class_schools_for( $school_id );
        $editing  = null;
        if ( 'edit' === ( $_GET['view'] ?? '' ) && ! empty( $_GET['id'] ) ) {
            $editing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tbl} WHERE ID = %d", (int) $_GET['id'] ) );
        }

        echo '<div class="ss-row">';
        echo '<div class="ss-col" style="flex:1">';
        SS_Admin_Shell::card_open( $editing ? __( 'Edit Fee', 'school-softwere' ) : __( 'Add Fee Head', 'school-softwere' ) );
        echo '<form method="post" class="ss-form">';
        SS_Helper::nonce_field( 'save_fee' );
        echo '<input type="hidden" name="ss_action" value="save_fee">';
        echo '<input type="hidden" name="id" value="' . ( $editing ? (int) $editing->ID : 0 ) . '">';
        SS_School::select( 'class_school_id', __( 'Class', 'school-softwere' ), $editing ? (int) $editing->class_school_id : 0, $cs, true );
        SS_School::field( 'label',  __( 'Fee Label', 'school-softwere' ), $editing ? $editing->label : '', true );
        SS_School::field( 'amount', __( 'Amount', 'school-softwere' ),     $editing ? $editing->amount : 0, false, 'number' );
        echo '<div class="ss-field"><label>' . esc_html__( 'Due Date', 'school-softwere' ) . '</label><input class="ss-date" type="text" name="due_date" value="' . esc_attr( $editing ? $editing->due_date : '' ) . '"></div>';
        echo '<div class="ss-field"><label>' . esc_html__( 'Frequency', 'school-softwere' ) . '</label><select name="frequency" class="ss-select2"><option value="">' . esc_html__( 'One-Time', 'school-softwere' ) . '</option>';
        foreach ( array( 'monthly', 'quarterly', 'half_yearly', 'yearly' ) as $f ) {
            echo '<option value="' . esc_attr( $f ) . '"' . selected( $editing ? $editing->frequency : '', $f, false ) . '>' . esc_html( ucfirst( str_replace( '_', '-', $f ) ) ) . '</option>';
        }
        echo '</select></div>';
        SS_School::checkbox( 'is_recurring', __( 'Recurring', 'school-softwere' ), $editing ? (bool) $editing->is_recurring : false );
        echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-floppy-disk"></i> ' . esc_html__( 'Save', 'school-softwere' ) . '</button></div></form>';
        SS_Admin_Shell::card_close();
        echo '</div>';

        echo '<div class="ss-col" style="flex:2">';
        SS_Admin_Shell::card_open( __( 'All Fees', 'school-softwere' ) );
        $rows = $wpdb->get_results( $wpdb->prepare(
            'SELECT f.*, c.label class_label FROM ' . $tbl . ' f
             LEFT JOIN ' . SS_Helper::table( 'class_school' ) . ' cs ON cs.ID = f.class_school_id
             LEFT JOIN ' . SS_Helper::table( 'classes' ) . ' c ON c.ID = cs.class_id
             WHERE cs.school_id = %d ORDER BY f.ID DESC', $school_id
        ) );
        if ( empty( $rows ) ) {
            echo '<div class="ss-empty"><i class="ph ph-currency-circle-dollar"></i><h3>' . esc_html__( 'No fees defined', 'school-softwere' ) . '</h3></div>';
        } else {
            echo '<div class="ss-table-wrap"><table class="ss-table"><thead><tr><th>' . esc_html__( 'Label', 'school-softwere' ) . '</th><th>' . esc_html__( 'Class', 'school-softwere' ) . '</th><th>' . esc_html__( 'Amount', 'school-softwere' ) . '</th><th>' . esc_html__( 'Due', 'school-softwere' ) . '</th><th>' . esc_html__( 'Recurring', 'school-softwere' ) . '</th><th class="ss-text-right">' . esc_html__( 'Actions', 'school-softwere' ) . '</th></tr></thead><tbody>';
            foreach ( $rows as $r ) {
                $edit = SS_Helper::admin_url( 'school-softwere-fees' ) . '&view=edit&id=' . $r->ID;
                $del  = wp_nonce_url( SS_Helper::admin_url( 'school-softwere-fees' ) . '&view=delete&id=' . $r->ID, 'delete_fee', '_ssnonce' );
                echo '<tr><td><strong>' . esc_html( $r->label ) . '</strong></td><td>' . esc_html( $r->class_label ) . '</td><td>' . esc_html( SS_Helper::format_money( $r->amount ) ) . '</td><td>' . esc_html( SS_Helper::format_date( $r->due_date ) ) . '</td><td>' . SS_Helper::badge( $r->is_recurring ? __( 'Yes', 'school-softwere' ) . ' (' . $r->frequency . ')' : __( 'No', 'school-softwere' ), $r->is_recurring ? 'info' : 'muted' ) . '</td><td class="ss-text-right"><a class="ss-btn ss-btn-secondary ss-btn-sm ss-btn-icon" href="' . esc_url( $edit ) . '"><i class="ph ph-pencil-simple"></i></a> <a class="ss-btn ss-btn-danger ss-btn-sm ss-btn-icon ss-confirm-delete" href="' . esc_url( $del ) . '"><i class="ph ph-trash"></i></a></td></tr>'; // phpcs:ignore
            }
            echo '</tbody></table></div>';
        }
        SS_Admin_Shell::card_close();
        echo '</div>';
        echo '</div>';
        SS_Admin_Shell::close();
    }

    /* ===============================================================
     *  INVOICES
     * =============================================================== */
    public static function render_invoices() {
        global $wpdb;
        $school_id = SS_Helper::active_school_id();
        $tbl = SS_Helper::table( 'invoices' );

        if ( isset( $_POST['ss_action'] ) && 'save_invoice' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'save_invoice' ) ) {
            $student_id = (int) ( $_POST['student_record_id'] ?? 0 );
            $student    = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . SS_Helper::table( 'student_records' ) . ' WHERE ID = %d', $student_id ) );
            if ( $student ) {
                $school = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . SS_Helper::table( 'schools' ) . ' WHERE ID = %d', $school_id ) );
                $count  = (int) $school->last_invoice_count + 1;
                $cfg    = SS_Config::all();
                $number = SS_Helper::generate_number( $cfg['invoice_prefix'] ?? 'INV-', $count, $cfg['invoice_padding'] ?? 5 );
                $total  = (float) ( $_POST['total_amount'] ?? 0 );
                $wpdb->insert( $tbl, array(
                    'school_id'         => $school_id,
                    'student_record_id' => $student_id,
                    'class_school_id'   => (int) $student->class_school_id,
                    'invoice_number'    => $number,
                    'total_amount'      => $total,
                    'paid_amount'       => 0,
                    'due_amount'        => $total,
                    'status'            => 'unpaid',
                    'due_date'          => sanitize_text_field( wp_unslash( $_POST['due_date'] ?? '' ) ) ?: null,
                    'created_at'        => current_time( 'mysql' ),
                ) );
                $wpdb->update( SS_Helper::table( 'schools' ), array( 'last_invoice_count' => $count ), array( 'ID' => $school_id ) );
            }
            wp_safe_redirect( add_query_arg( array( 'ss_notice' => __( 'Invoice generated', 'school-softwere' ), 'ss_notice_type' => 'success' ), SS_Helper::admin_url( 'school-softwere-invoices' ) ) );
            exit;
        }
        if ( 'delete' === ( $_GET['view'] ?? '' ) && SS_Helper::verify_nonce( 'delete_invoice' ) ) {
            $wpdb->delete( $tbl, array( 'ID' => (int) $_GET['id'], 'school_id' => $school_id ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-invoices' ) ); exit;
        }

        SS_Admin_Shell::open( __( 'Invoices', 'school-softwere' ), 'school-softwere-invoices', array(
            array( 'label' => __( 'Invoices', 'school-softwere' ) ),
        ) );

        $students = $wpdb->get_results( $wpdb->prepare(
            'SELECT ID, CONCAT(first_name," ",last_name," (",admission_number,")") as label FROM ' . SS_Helper::table( 'student_records' ) . ' WHERE school_id = %d AND is_active = 1 ORDER BY first_name', $school_id
        ) );

        echo '<div class="ss-row">';
        echo '<div class="ss-col" style="flex:1">';
        SS_Admin_Shell::card_open( __( 'Generate Invoice', 'school-softwere' ) );
        echo '<form method="post" class="ss-form">';
        SS_Helper::nonce_field( 'save_invoice' );
        echo '<input type="hidden" name="ss_action" value="save_invoice">';
        SS_School::select( 'student_record_id', __( 'Student', 'school-softwere' ), 0, $students, true );
        SS_School::field( 'total_amount', __( 'Total Amount', 'school-softwere' ), 0, true, 'number' );
        echo '<div class="ss-field"><label>' . esc_html__( 'Due Date', 'school-softwere' ) . '</label><input class="ss-date" type="text" name="due_date"></div>';
        echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-receipt"></i> ' . esc_html__( 'Create Invoice', 'school-softwere' ) . '</button></div></form>';
        SS_Admin_Shell::card_close();
        echo '</div>';

        echo '<div class="ss-col" style="flex:2">';
        SS_Admin_Shell::card_open( __( 'All Invoices', 'school-softwere' ) );
        $rows = $wpdb->get_results( $wpdb->prepare(
            'SELECT i.*, sr.first_name, sr.last_name, sr.admission_number FROM ' . $tbl . ' i LEFT JOIN ' . SS_Helper::table( 'student_records' ) . ' sr ON sr.ID = i.student_record_id WHERE i.school_id = %d ORDER BY i.ID DESC LIMIT 200', $school_id
        ) );
        if ( empty( $rows ) ) {
            echo '<div class="ss-empty"><i class="ph ph-receipt"></i><h3>' . esc_html__( 'No invoices', 'school-softwere' ) . '</h3></div>';
        } else {
            echo '<div class="ss-table-wrap"><table class="ss-datatable ss-table"><thead><tr><th>' . esc_html__( 'Invoice #', 'school-softwere' ) . '</th><th>' . esc_html__( 'Student', 'school-softwere' ) . '</th><th>' . esc_html__( 'Total', 'school-softwere' ) . '</th><th>' . esc_html__( 'Paid', 'school-softwere' ) . '</th><th>' . esc_html__( 'Due', 'school-softwere' ) . '</th><th>' . esc_html__( 'Status', 'school-softwere' ) . '</th><th>' . esc_html__( 'Due Date', 'school-softwere' ) . '</th><th class="ss-text-right">' . esc_html__( 'Actions', 'school-softwere' ) . '</th></tr></thead><tbody>';
            foreach ( $rows as $r ) {
                $del   = wp_nonce_url( SS_Helper::admin_url( 'school-softwere-invoices' ) . '&view=delete&id=' . $r->ID, 'delete_invoice', '_ssnonce' );
                $print = SS_Helper::admin_url() . '&ss_print=invoice&id=' . $r->ID;
                $pay   = SS_Helper::admin_url( 'school-softwere-payments' ) . '&invoice_id=' . $r->ID;
                $badge = 'paid' === $r->status ? 'success' : ( 'partial' === $r->status ? 'warning' : 'danger' );
                echo '<tr><td><strong>' . esc_html( $r->invoice_number ) . '</strong></td><td>' . esc_html( trim( $r->first_name . ' ' . $r->last_name ) ) . '<br><small class="ss-text-muted">' . esc_html( $r->admission_number ) . '</small></td><td>' . esc_html( SS_Helper::format_money( $r->total_amount ) ) . '</td><td>' . esc_html( SS_Helper::format_money( $r->paid_amount ) ) . '</td><td>' . esc_html( SS_Helper::format_money( $r->due_amount ) ) . '</td><td>' . SS_Helper::badge( ucfirst( (string) $r->status ), $badge ) . '</td><td>' . esc_html( SS_Helper::format_date( $r->due_date ) ) . '</td><td class="ss-text-right"><div class="ss-actions"><a class="ss-btn ss-btn-secondary ss-btn-sm ss-btn-icon" href="' . esc_url( $print ) . '" target="_blank" title="Print"><i class="ph ph-printer"></i></a> <a class="ss-btn ss-btn-success ss-btn-sm ss-btn-icon" href="' . esc_url( $pay ) . '" title="Collect Payment"><i class="ph ph-money"></i></a> <a class="ss-btn ss-btn-danger ss-btn-sm ss-btn-icon ss-confirm-delete" href="' . esc_url( $del ) . '"><i class="ph ph-trash"></i></a></div></td></tr>'; // phpcs:ignore
            }
            echo '</tbody></table></div>';
        }
        SS_Admin_Shell::card_close();
        echo '</div>';
        echo '</div>';
        SS_Admin_Shell::close();
    }

    /* ===============================================================
     *  PAYMENTS
     * =============================================================== */
    public static function render_payments() {
        global $wpdb;
        $school_id = SS_Helper::active_school_id();
        $tbl = SS_Helper::table( 'payments' );
        $itbl = SS_Helper::table( 'invoices' );

        if ( isset( $_POST['ss_action'] ) && 'save_payment' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'save_payment' ) ) {
            $invoice_id = (int) ( $_POST['invoice_id'] ?? 0 );
            $invoice    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$itbl} WHERE ID = %d AND school_id = %d", $invoice_id, $school_id ) );
            if ( $invoice ) {
                $amount = max( 0, (float) ( $_POST['amount'] ?? 0 ) );
                $wpdb->insert( $tbl, array(
                    'invoice_id'     => $invoice_id,
                    'amount'         => $amount,
                    'payment_method' => sanitize_text_field( wp_unslash( $_POST['payment_method'] ?? 'cash' ) ),
                    'payment_date'   => sanitize_text_field( wp_unslash( $_POST['payment_date'] ?? current_time( 'Y-m-d' ) ) ),
                    'note'           => sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) ),
                    'created_at'     => current_time( 'mysql' ),
                ) );
                $paid    = (float) $invoice->paid_amount + $amount;
                $due     = max( 0, (float) $invoice->total_amount - $paid );
                $status  = $due <= 0 ? 'paid' : ( $paid > 0 ? 'partial' : 'unpaid' );
                $wpdb->update( $itbl, array(
                    'paid_amount' => $paid,
                    'due_amount'  => $due,
                    'status'      => $status,
                ), array( 'ID' => $invoice_id ) );
            }
            wp_safe_redirect( add_query_arg( array( 'ss_notice' => __( 'Payment recorded', 'school-softwere' ), 'ss_notice_type' => 'success' ), SS_Helper::admin_url( 'school-softwere-payments' ) ) );
            exit;
        }

        SS_Admin_Shell::open( __( 'Payments', 'school-softwere' ), 'school-softwere-payments', array(
            array( 'label' => __( 'Payments', 'school-softwere' ) ),
        ) );

        // Pre-select invoice if specified.
        $sel_invoice_id = isset( $_GET['invoice_id'] ) ? (int) $_GET['invoice_id'] : 0;
        $invoices = $wpdb->get_results( $wpdb->prepare(
            'SELECT i.ID, CONCAT(i.invoice_number," - ",sr.first_name," ",sr.last_name," | Due: ",i.due_amount) as label
             FROM ' . $itbl . ' i LEFT JOIN ' . SS_Helper::table( 'student_records' ) . ' sr ON sr.ID = i.student_record_id
             WHERE i.school_id = %d AND i.status != "paid" ORDER BY i.ID DESC', $school_id
        ) );

        echo '<div class="ss-row">';
        echo '<div class="ss-col" style="flex:1">';
        SS_Admin_Shell::card_open( __( 'Record Payment', 'school-softwere' ) );
        echo '<form method="post" class="ss-form">';
        SS_Helper::nonce_field( 'save_payment' );
        echo '<input type="hidden" name="ss_action" value="save_payment">';
        SS_School::select( 'invoice_id', __( 'Invoice', 'school-softwere' ), $sel_invoice_id, $invoices, true );
        SS_School::field( 'amount', __( 'Amount', 'school-softwere' ), 0, true, 'number' );
        echo '<div class="ss-field"><label>' . esc_html__( 'Method', 'school-softwere' ) . '</label><select name="payment_method" class="ss-select2">';
        foreach ( array( 'cash', 'cheque', 'card', 'online', 'bank_transfer' ) as $m ) {
            echo '<option value="' . esc_attr( $m ) . '">' . esc_html( ucfirst( str_replace( '_', ' ', $m ) ) ) . '</option>';
        }
        echo '</select></div>';
        echo '<div class="ss-field"><label>' . esc_html__( 'Payment Date', 'school-softwere' ) . '</label><input class="ss-date" type="text" name="payment_date" value="' . esc_attr( current_time( 'Y-m-d' ) ) . '"></div>';
        SS_School::textarea( 'note', __( 'Note', 'school-softwere' ), '' );
        echo '<div class="ss-form-actions"><button class="ss-btn ss-btn-success"><i class="ph ph-money"></i> ' . esc_html__( 'Record Payment', 'school-softwere' ) . '</button></div></form>';
        SS_Admin_Shell::card_close();
        echo '</div>';

        echo '<div class="ss-col" style="flex:2">';
        SS_Admin_Shell::card_open( __( 'Recent Payments', 'school-softwere' ) );
        $rows = $wpdb->get_results( $wpdb->prepare(
            'SELECT p.*, i.invoice_number, sr.first_name, sr.last_name FROM ' . $tbl . ' p
             LEFT JOIN ' . $itbl . ' i ON i.ID = p.invoice_id
             LEFT JOIN ' . SS_Helper::table( 'student_records' ) . ' sr ON sr.ID = i.student_record_id
             WHERE i.school_id = %d ORDER BY p.ID DESC LIMIT 100', $school_id
        ) );
        if ( empty( $rows ) ) {
            echo '<div class="ss-empty"><i class="ph ph-money"></i><h3>' . esc_html__( 'No payments yet', 'school-softwere' ) . '</h3></div>';
        } else {
            echo '<div class="ss-table-wrap"><table class="ss-datatable ss-table"><thead><tr><th>' . esc_html__( 'Date', 'school-softwere' ) . '</th><th>' . esc_html__( 'Invoice', 'school-softwere' ) . '</th><th>' . esc_html__( 'Student', 'school-softwere' ) . '</th><th>' . esc_html__( 'Amount', 'school-softwere' ) . '</th><th>' . esc_html__( 'Method', 'school-softwere' ) . '</th><th></th></tr></thead><tbody>';
            foreach ( $rows as $r ) {
                echo '<tr><td>' . esc_html( SS_Helper::format_date( $r->payment_date ) ) . '</td><td>' . esc_html( $r->invoice_number ) . '</td><td>' . esc_html( trim( $r->first_name . ' ' . $r->last_name ) ) . '</td><td><strong>' . esc_html( SS_Helper::format_money( $r->amount ) ) . '</strong></td><td>' . SS_Helper::badge( ucfirst( str_replace( '_', ' ', (string) $r->payment_method ) ), 'info' ) . '</td><td class="ss-text-right"><a class="ss-btn ss-btn-secondary ss-btn-sm ss-btn-icon" href="' . esc_url( SS_Helper::admin_url() . '&ss_print=payment&id=' . $r->ID ) . '" target="_blank"><i class="ph ph-printer"></i></a></td></tr>'; // phpcs:ignore
            }
            echo '</tbody></table></div>';
        }
        SS_Admin_Shell::card_close();
        echo '</div>';
        echo '</div>';
        SS_Admin_Shell::close();
    }

    /* ===============================================================
     *  CONCESSIONS
     * =============================================================== */
    public static function render_concessions() {
        global $wpdb;
        $school_id = SS_Helper::active_school_id();
        $tbl = SS_Helper::table( 'concession_types' );

        if ( isset( $_POST['ss_action'] ) && 'save_concession_type' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'save_concession_type' ) ) {
            $label = sanitize_text_field( wp_unslash( $_POST['label'] ?? '' ) );
            if ( $label ) {
                $wpdb->insert( $tbl, array( 'school_id' => $school_id, 'label' => $label, 'created_at' => current_time( 'mysql' ) ) );
            }
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-concessions' ) ); exit;
        }
        if ( 'delete' === ( $_GET['view'] ?? '' ) && SS_Helper::verify_nonce( 'delete_concession' ) ) {
            $wpdb->delete( $tbl, array( 'ID' => (int) $_GET['id'], 'school_id' => $school_id ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-concessions' ) ); exit;
        }

        SS_Admin_Shell::open( __( 'Concessions', 'school-softwere' ), 'school-softwere-concessions', array(
            array( 'label' => __( 'Concessions', 'school-softwere' ) ),
        ) );

        echo '<div class="ss-row">';
        echo '<div class="ss-col" style="flex:1">';
        SS_Admin_Shell::card_open( __( 'Add Concession Type', 'school-softwere' ) );
        echo '<form method="post" class="ss-form">';
        SS_Helper::nonce_field( 'save_concession_type' );
        echo '<input type="hidden" name="ss_action" value="save_concession_type">';
        SS_School::field( 'label', __( 'Label (e.g. Sibling Discount)', 'school-softwere' ), '', true );
        echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-plus"></i> ' . esc_html__( 'Add', 'school-softwere' ) . '</button></div></form>';
        SS_Admin_Shell::card_close();
        echo '</div>';

        echo '<div class="ss-col" style="flex:2">';
        SS_Admin_Shell::card_open( __( 'All Concession Types', 'school-softwere' ) );
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl} WHERE school_id = %d ORDER BY ID DESC", $school_id ) );
        if ( empty( $rows ) ) {
            echo '<div class="ss-empty"><i class="ph ph-percent"></i><h3>' . esc_html__( 'No concession types', 'school-softwere' ) . '</h3></div>';
        } else {
            echo '<div class="ss-table-wrap"><table class="ss-table"><thead><tr><th>' . esc_html__( 'Label', 'school-softwere' ) . '</th><th>' . esc_html__( 'Created', 'school-softwere' ) . '</th><th></th></tr></thead><tbody>';
            foreach ( $rows as $r ) {
                $del = wp_nonce_url( SS_Helper::admin_url( 'school-softwere-concessions' ) . '&view=delete&id=' . $r->ID, 'delete_concession', '_ssnonce' );
                echo '<tr><td><strong>' . esc_html( $r->label ) . '</strong></td><td>' . esc_html( SS_Helper::format_date( $r->created_at ) ) . '</td><td class="ss-text-right"><a class="ss-btn ss-btn-danger ss-btn-sm ss-btn-icon ss-confirm-delete" href="' . esc_url( $del ) . '"><i class="ph ph-trash"></i></a></td></tr>';
            }
            echo '</tbody></table></div>';
        }
        SS_Admin_Shell::card_close();
        echo '</div>';
        echo '</div>';
        SS_Admin_Shell::close();
    }

    /* ===============================================================
     *  INCOME / EXPENSES
     * =============================================================== */
    public static function render_income() {
        self::render_money_module( 'income', __( 'Income', 'school-softwere' ), 'school-softwere-income', 'ph-trend-up', 'success' );
    }

    public static function render_expenses() {
        self::render_money_module( 'expenses', __( 'Expenses', 'school-softwere' ), 'school-softwere-expenses', 'ph-trend-down', 'danger' );
    }

    private static function render_money_module( $type, $title, $slug, $icon, $variant ) {
        global $wpdb;
        $school_id = SS_Helper::active_school_id();
        $tbl       = SS_Helper::table( $type );
        $cat_tbl   = SS_Helper::table( 'expenses' === $type ? 'expense_categories' : 'income_categories' );
        $cat_field = 'expenses' === $type ? 'expense_category_id' : 'income_category_id';

        if ( isset( $_POST['ss_action'] ) && 'save_money' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'save_money_' . $type ) ) {
            $data = array(
                'school_id'  => $school_id,
                $cat_field   => (int) ( $_POST[ $cat_field ] ?? 0 ) ?: null,
                'label'      => sanitize_text_field( wp_unslash( $_POST['label'] ?? '' ) ),
                'amount'     => (float) ( $_POST['amount'] ?? 0 ),
                'date'       => sanitize_text_field( wp_unslash( $_POST['date'] ?? current_time( 'Y-m-d' ) ) ),
                'note'       => sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) ),
                'created_at' => current_time( 'mysql' ),
            );
            $wpdb->insert( $tbl, $data );
            wp_safe_redirect( SS_Helper::admin_url( $slug ) ); exit;
        }
        if ( 'delete' === ( $_GET['view'] ?? '' ) && SS_Helper::verify_nonce( 'delete_money_' . $type ) ) {
            $wpdb->delete( $tbl, array( 'ID' => (int) $_GET['id'], 'school_id' => $school_id ) );
            wp_safe_redirect( SS_Helper::admin_url( $slug ) ); exit;
        }
        if ( isset( $_POST['ss_action'] ) && 'save_money_cat' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'save_money_cat_' . $type ) ) {
            $label = sanitize_text_field( wp_unslash( $_POST['cat_label'] ?? '' ) );
            if ( $label ) { $wpdb->insert( $cat_tbl, array( 'school_id' => $school_id, 'label' => $label ) ); }
            wp_safe_redirect( SS_Helper::admin_url( $slug ) ); exit;
        }

        SS_Admin_Shell::open( $title, $slug, array( array( 'label' => $title ) ) );

        $cats = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$cat_tbl} WHERE school_id = %d ORDER BY label", $school_id ) );

        echo '<div class="ss-row">';
        echo '<div class="ss-col" style="flex:1">';
        SS_Admin_Shell::card_open( sprintf( __( 'Add %s Entry', 'school-softwere' ), $title ) );
        echo '<form method="post" class="ss-form">';
        SS_Helper::nonce_field( 'save_money_' . $type );
        echo '<input type="hidden" name="ss_action" value="save_money">';
        SS_School::select( $cat_field, __( 'Category', 'school-softwere' ), 0, $cats );
        SS_School::field( 'label',  __( 'Label', 'school-softwere' ), '', true );
        SS_School::field( 'amount', __( 'Amount', 'school-softwere' ), 0, true, 'number' );
        echo '<div class="ss-field"><label>' . esc_html__( 'Date', 'school-softwere' ) . '</label><input class="ss-date" type="text" name="date" value="' . esc_attr( current_time( 'Y-m-d' ) ) . '"></div>';
        SS_School::textarea( 'note', __( 'Note', 'school-softwere' ), '' );
        echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ' . esc_attr( $icon ) . '"></i> ' . esc_html__( 'Save', 'school-softwere' ) . '</button></div></form>';
        SS_Admin_Shell::card_close();

        SS_Admin_Shell::card_open( __( 'Categories', 'school-softwere' ) );
        echo '<form method="post" class="ss-form" style="display:flex; gap:8px; align-items:flex-end;">';
        SS_Helper::nonce_field( 'save_money_cat_' . $type );
        echo '<input type="hidden" name="ss_action" value="save_money_cat">';
        echo '<div class="ss-field" style="flex:1"><label>' . esc_html__( 'New Category', 'school-softwere' ) . '</label><input type="text" name="cat_label" required></div>';
        echo '<button class="ss-btn"><i class="ph ph-plus"></i></button></form>';
        if ( $cats ) {
            echo '<ul class="ss-feed">';
            foreach ( $cats as $c ) {
                echo '<li><div class="ss-feed-icon"><i class="ph ph-tag"></i></div><div class="ss-feed-meta"><strong>' . esc_html( $c->label ) . '</strong></div></li>';
            }
            echo '</ul>';
        }
        SS_Admin_Shell::card_close();
        echo '</div>';

        echo '<div class="ss-col" style="flex:2">';
        SS_Admin_Shell::card_open( $title . ' ' . __( 'Entries', 'school-softwere' ) );
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT m.*, c.label cat_label FROM {$tbl} m LEFT JOIN {$cat_tbl} c ON c.ID = m.{$cat_field} WHERE m.school_id = %d ORDER BY m.date DESC, m.ID DESC LIMIT 200", $school_id
        ) );
        if ( empty( $rows ) ) {
            echo '<div class="ss-empty"><i class="ph ' . esc_attr( $icon ) . '"></i><h3>' . esc_html__( 'No entries', 'school-softwere' ) . '</h3></div>';
        } else {
            $total = 0;
            foreach ( $rows as $r ) { $total += (float) $r->amount; }
            echo '<div style="margin-bottom:16px"><span class="ss-badge ss-badge-' . esc_attr( $variant ) . '">' . esc_html__( 'Total', 'school-softwere' ) . ': ' . esc_html( SS_Helper::format_money( $total ) ) . '</span></div>';
            echo '<div class="ss-table-wrap"><table class="ss-datatable ss-table"><thead><tr><th>' . esc_html__( 'Date', 'school-softwere' ) . '</th><th>' . esc_html__( 'Label', 'school-softwere' ) . '</th><th>' . esc_html__( 'Category', 'school-softwere' ) . '</th><th>' . esc_html__( 'Amount', 'school-softwere' ) . '</th><th></th></tr></thead><tbody>';
            foreach ( $rows as $r ) {
                $del = wp_nonce_url( SS_Helper::admin_url( $slug ) . '&view=delete&id=' . $r->ID, 'delete_money_' . $type, '_ssnonce' );
                echo '<tr><td>' . esc_html( SS_Helper::format_date( $r->date ) ) . '</td><td><strong>' . esc_html( $r->label ) . '</strong></td><td>' . esc_html( $r->cat_label ?: '-' ) . '</td><td><strong>' . esc_html( SS_Helper::format_money( $r->amount ) ) . '</strong></td><td class="ss-text-right"><a class="ss-btn ss-btn-danger ss-btn-sm ss-btn-icon ss-confirm-delete" href="' . esc_url( $del ) . '"><i class="ph ph-trash"></i></a></td></tr>';
            }
            echo '</tbody></table></div>';
        }
        SS_Admin_Shell::card_close();
        echo '</div>';
        echo '</div>';
        SS_Admin_Shell::close();
    }

    /* ===============================================================
     *  HELPERS
     * =============================================================== */
    public static function class_schools_for( $school_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            'SELECT cs.ID, c.label FROM ' . SS_Helper::table( 'class_school' ) . ' cs INNER JOIN ' . SS_Helper::table( 'classes' ) . ' c ON c.ID = cs.class_id WHERE cs.school_id = %d ORDER BY c.ID ASC',
            $school_id
        ) );
    }
}

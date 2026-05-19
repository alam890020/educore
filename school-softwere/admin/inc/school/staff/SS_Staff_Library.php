<?php
/**
 * SS_Staff_Library - Books, issue/return, library cards.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Staff_Library {

    public static function render() {
        global $wpdb;
        $school_id = SS_Helper::active_school_id();
        $tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'books';

        // Save book.
        if ( isset( $_POST['ss_action'] ) && 'save_book' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'save_book' ) ) {
            $tbl  = SS_Helper::table( 'books' );
            $id   = (int) ( $_POST['id'] ?? 0 );
            $qty  = max( 0, (int) ( $_POST['quantity'] ?? 0 ) );
            $data = array(
                'school_id' => $school_id,
                'title'     => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
                'author'    => sanitize_text_field( wp_unslash( $_POST['author'] ?? '' ) ),
                'isbn'      => sanitize_text_field( wp_unslash( $_POST['isbn'] ?? '' ) ),
                'publisher' => sanitize_text_field( wp_unslash( $_POST['publisher'] ?? '' ) ),
                'edition'   => sanitize_text_field( wp_unslash( $_POST['edition'] ?? '' ) ),
                'quantity'  => $qty,
            );
            if ( $id ) {
                $wpdb->update( $tbl, $data, array( 'ID' => $id, 'school_id' => $school_id ) );
            } else {
                $data['available']  = $qty;
                $data['created_at'] = current_time( 'mysql' );
                $wpdb->insert( $tbl, $data );
            }
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-library' ) ); exit;
        }
        if ( 'delete_book' === ( $_GET['view'] ?? '' ) && SS_Helper::verify_nonce( 'delete_book' ) ) {
            $wpdb->delete( SS_Helper::table( 'books' ), array( 'ID' => (int) $_GET['id'], 'school_id' => $school_id ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-library' ) ); exit;
        }
        // Issue book.
        if ( isset( $_POST['ss_action'] ) && 'issue_book' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'issue_book' ) ) {
            $book_id = (int) ( $_POST['book_id'] ?? 0 );
            $student = (int) ( $_POST['student_record_id'] ?? 0 );
            $book    = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . SS_Helper::table( 'books' ) . ' WHERE ID = %d AND school_id = %d', $book_id, $school_id ) );
            if ( $book && (int) $book->available > 0 ) {
                $wpdb->insert( SS_Helper::table( 'books_issued' ), array(
                    'book_id'           => $book_id,
                    'student_record_id' => $student,
                    'issue_date'        => sanitize_text_field( wp_unslash( $_POST['issue_date'] ?? current_time( 'Y-m-d' ) ) ),
                    'return_date'       => sanitize_text_field( wp_unslash( $_POST['return_date'] ?? '' ) ) ?: null,
                    'created_at'        => current_time( 'mysql' ),
                ) );
                $wpdb->update( SS_Helper::table( 'books' ), array( 'available' => max( 0, (int) $book->available - 1 ) ), array( 'ID' => $book_id ) );
            }
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-library' ) . '&tab=issued' ); exit;
        }
        // Return book.
        if ( 'return' === ( $_GET['view'] ?? '' ) && SS_Helper::verify_nonce( 'return_book' ) ) {
            $row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . SS_Helper::table( 'books_issued' ) . ' WHERE ID = %d', (int) $_GET['id'] ) );
            if ( $row && empty( $row->returned_at ) ) {
                $wpdb->update( SS_Helper::table( 'books_issued' ), array( 'returned_at' => current_time( 'Y-m-d' ) ), array( 'ID' => $row->ID ) );
                $book = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . SS_Helper::table( 'books' ) . ' WHERE ID = %d', $row->book_id ) );
                if ( $book ) {
                    $wpdb->update( SS_Helper::table( 'books' ), array( 'available' => (int) $book->available + 1 ), array( 'ID' => $book->ID ) );
                }
            }
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-library' ) . '&tab=issued' ); exit;
        }

        SS_Admin_Shell::open( __( 'Library', 'school-softwere' ), 'school-softwere-library', array(
            array( 'label' => __( 'Library', 'school-softwere' ) ),
        ) );

        echo '<div class="ss-tabs" style="margin-bottom:16px;">';
        $base = SS_Helper::admin_url( 'school-softwere-library' );
        echo '<a class="ss-tab ' . ( 'books'  === $tab ? 'active' : '' ) . '" href="' . esc_url( $base ) . '">' . esc_html__( 'Books', 'school-softwere' ) . '</a>';
        echo '<a class="ss-tab ' . ( 'issued' === $tab ? 'active' : '' ) . '" href="' . esc_url( $base . '&tab=issued' ) . '">' . esc_html__( 'Issued', 'school-softwere' ) . '</a>';
        echo '<a class="ss-tab ' . ( 'cards'  === $tab ? 'active' : '' ) . '" href="' . esc_url( $base . '&tab=cards' ) . '">' . esc_html__( 'Library Cards', 'school-softwere' ) . '</a>';
        echo '</div>';

        if ( 'issued' === $tab )      { self::tab_issued( $school_id ); }
        elseif ( 'cards' === $tab )   { self::tab_cards( $school_id ); }
        else                          { self::tab_books( $school_id ); }

        SS_Admin_Shell::close();
    }

    private static function tab_books( $school_id ) {
        global $wpdb;
        $editing = null;
        if ( 'edit_book' === ( $_GET['view'] ?? '' ) && ! empty( $_GET['id'] ) ) {
            $editing = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . SS_Helper::table( 'books' ) . ' WHERE ID = %d AND school_id = %d', (int) $_GET['id'], $school_id ) );
        }

        echo '<div class="ss-row">';
        echo '<div class="ss-col" style="flex:1">';
        SS_Admin_Shell::card_open( $editing ? __( 'Edit Book', 'school-softwere' ) : __( 'Add Book', 'school-softwere' ) );
        echo '<form method="post" class="ss-form">';
        SS_Helper::nonce_field( 'save_book' );
        echo '<input type="hidden" name="ss_action" value="save_book">';
        echo '<input type="hidden" name="id" value="' . ( $editing ? (int) $editing->ID : 0 ) . '">';
        SS_School::field( 'title',     __( 'Title', 'school-softwere' ),     $editing ? $editing->title : '', true );
        SS_School::field( 'author',    __( 'Author', 'school-softwere' ),    $editing ? $editing->author : '' );
        SS_School::field( 'isbn',      __( 'ISBN', 'school-softwere' ),      $editing ? $editing->isbn : '' );
        SS_School::field( 'publisher', __( 'Publisher', 'school-softwere' ), $editing ? $editing->publisher : '' );
        SS_School::field( 'edition',   __( 'Edition', 'school-softwere' ),   $editing ? $editing->edition : '' );
        SS_School::field( 'quantity',  __( 'Quantity', 'school-softwere' ),  $editing ? $editing->quantity : 1, true, 'number' );
        echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-floppy-disk"></i> ' . esc_html__( 'Save Book', 'school-softwere' ) . '</button></div></form>';
        SS_Admin_Shell::card_close();
        echo '</div>';

        echo '<div class="ss-col" style="flex:2">';
        SS_Admin_Shell::card_open( __( 'All Books', 'school-softwere' ) );
        $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . SS_Helper::table( 'books' ) . ' WHERE school_id = %d ORDER BY ID DESC', $school_id ) );
        if ( empty( $rows ) ) {
            echo '<div class="ss-empty"><i class="ph ph-books"></i><h3>' . esc_html__( 'No books', 'school-softwere' ) . '</h3></div>';
        } else {
            echo '<div class="ss-table-wrap"><table class="ss-datatable ss-table"><thead><tr><th>' . esc_html__( 'Title', 'school-softwere' ) . '</th><th>' . esc_html__( 'Author', 'school-softwere' ) . '</th><th>' . esc_html__( 'ISBN', 'school-softwere' ) . '</th><th>' . esc_html__( 'Qty', 'school-softwere' ) . '</th><th>' . esc_html__( 'Available', 'school-softwere' ) . '</th><th></th></tr></thead><tbody>';
            foreach ( $rows as $r ) {
                $edit = SS_Helper::admin_url( 'school-softwere-library' ) . '&view=edit_book&id=' . $r->ID;
                $del  = wp_nonce_url( SS_Helper::admin_url( 'school-softwere-library' ) . '&view=delete_book&id=' . $r->ID, 'delete_book', '_ssnonce' );
                echo '<tr><td><strong>' . esc_html( $r->title ) . '</strong></td><td>' . esc_html( $r->author ) . '</td><td>' . esc_html( $r->isbn ) . '</td><td>' . (int) $r->quantity . '</td><td>' . SS_Helper::badge( (int) $r->available, ( (int) $r->available > 0 ? 'success' : 'danger' ) ) . '</td><td class="ss-text-right"><a class="ss-btn ss-btn-secondary ss-btn-sm ss-btn-icon" href="' . esc_url( $edit ) . '"><i class="ph ph-pencil-simple"></i></a> <a class="ss-btn ss-btn-danger ss-btn-sm ss-btn-icon ss-confirm-delete" href="' . esc_url( $del ) . '"><i class="ph ph-trash"></i></a></td></tr>';
            }
            echo '</tbody></table></div>';
        }
        SS_Admin_Shell::card_close();
        echo '</div>';
        echo '</div>';
    }

    private static function tab_issued( $school_id ) {
        global $wpdb;
        $books = $wpdb->get_results( $wpdb->prepare( 'SELECT ID, title as label FROM ' . SS_Helper::table( 'books' ) . ' WHERE school_id = %d AND available > 0 ORDER BY title', $school_id ) );
        $students = $wpdb->get_results( $wpdb->prepare( 'SELECT ID, CONCAT(first_name," ",last_name," (",admission_number,")") as label FROM ' . SS_Helper::table( 'student_records' ) . ' WHERE school_id = %d AND is_active = 1 ORDER BY first_name', $school_id ) );

        echo '<div class="ss-row">';
        echo '<div class="ss-col" style="flex:1">';
        SS_Admin_Shell::card_open( __( 'Issue Book', 'school-softwere' ) );
        echo '<form method="post" class="ss-form">';
        SS_Helper::nonce_field( 'issue_book' );
        echo '<input type="hidden" name="ss_action" value="issue_book">';
        SS_School::select( 'book_id',           __( 'Book', 'school-softwere' ),    0, $books, true );
        SS_School::select( 'student_record_id', __( 'Student', 'school-softwere' ), 0, $students, true );
        echo '<div class="ss-field"><label>' . esc_html__( 'Issue Date', 'school-softwere' ) . '</label><input class="ss-date" type="text" name="issue_date" value="' . esc_attr( current_time( 'Y-m-d' ) ) . '"></div>';
        echo '<div class="ss-field"><label>' . esc_html__( 'Return By', 'school-softwere' ) . '</label><input class="ss-date" type="text" name="return_date"></div>';
        echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-arrow-square-out"></i> ' . esc_html__( 'Issue', 'school-softwere' ) . '</button></div></form>';
        SS_Admin_Shell::card_close();
        echo '</div>';

        echo '<div class="ss-col" style="flex:2">';
        SS_Admin_Shell::card_open( __( 'Issued Books', 'school-softwere' ) );
        $rows = $wpdb->get_results( $wpdb->prepare(
            'SELECT bi.*, b.title book_title, sr.first_name, sr.last_name, sr.admission_number FROM ' . SS_Helper::table( 'books_issued' ) . ' bi
             LEFT JOIN ' . SS_Helper::table( 'books' ) . ' b ON b.ID = bi.book_id
             LEFT JOIN ' . SS_Helper::table( 'student_records' ) . ' sr ON sr.ID = bi.student_record_id
             WHERE b.school_id = %d ORDER BY bi.ID DESC LIMIT 200', $school_id
        ) );
        if ( empty( $rows ) ) {
            echo '<div class="ss-empty"><i class="ph ph-arrow-square-out"></i><h3>' . esc_html__( 'No issued books', 'school-softwere' ) . '</h3></div>';
        } else {
            echo '<div class="ss-table-wrap"><table class="ss-datatable ss-table"><thead><tr><th>' . esc_html__( 'Book', 'school-softwere' ) . '</th><th>' . esc_html__( 'Student', 'school-softwere' ) . '</th><th>' . esc_html__( 'Issued', 'school-softwere' ) . '</th><th>' . esc_html__( 'Return By', 'school-softwere' ) . '</th><th>' . esc_html__( 'Returned', 'school-softwere' ) . '</th><th></th></tr></thead><tbody>';
            foreach ( $rows as $r ) {
                $ret = wp_nonce_url( SS_Helper::admin_url( 'school-softwere-library' ) . '&tab=issued&view=return&id=' . $r->ID, 'return_book', '_ssnonce' );
                echo '<tr><td>' . esc_html( $r->book_title ) . '</td><td>' . esc_html( trim( $r->first_name . ' ' . $r->last_name ) ) . '<br><small class="ss-text-muted">' . esc_html( $r->admission_number ) . '</small></td><td>' . esc_html( SS_Helper::format_date( $r->issue_date ) ) . '</td><td>' . esc_html( SS_Helper::format_date( $r->return_date ) ) . '</td><td>' . ( $r->returned_at ? SS_Helper::badge( SS_Helper::format_date( $r->returned_at ), 'success' ) : SS_Helper::badge( __( 'Not returned', 'school-softwere' ), 'warning' ) ) . '</td><td class="ss-text-right">' . ( $r->returned_at ? '' : '<a class="ss-btn ss-btn-success ss-btn-sm" href="' . esc_url( $ret ) . '"><i class="ph ph-arrow-counter-clockwise"></i> ' . esc_html__( 'Return', 'school-softwere' ) . '</a>' ) . '</td></tr>'; // phpcs:ignore
            }
            echo '</tbody></table></div>';
        }
        SS_Admin_Shell::card_close();
        echo '</div>';
        echo '</div>';
    }

    private static function tab_cards( $school_id ) {
        global $wpdb;
        if ( isset( $_POST['ss_action'] ) && 'gen_lib_cards' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'gen_lib_cards' ) ) {
            $students = $wpdb->get_col( $wpdb->prepare( 'SELECT ID FROM ' . SS_Helper::table( 'student_records' ) . ' WHERE school_id = %d AND is_active = 1', $school_id ) );
            $tbl = SS_Helper::table( 'library_cards' );
            foreach ( (array) $students as $sid ) {
                $exists = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$tbl} WHERE student_record_id = %d", $sid ) );
                if ( ! $exists ) {
                    $wpdb->insert( $tbl, array(
                        'student_record_id' => $sid,
                        'card_number'       => 'LIB-' . str_pad( (string) $sid, 5, '0', STR_PAD_LEFT ),
                        'issued_date'       => current_time( 'Y-m-d' ),
                        'expiry_date'       => date( 'Y-m-d', strtotime( '+1 year' ) ),
                    ) );
                }
            }
            wp_safe_redirect( add_query_arg( array( 'ss_notice' => __( 'Library cards generated', 'school-softwere' ), 'ss_notice_type' => 'success' ), SS_Helper::admin_url( 'school-softwere-library' ) . '&tab=cards' ) );
            exit;
        }

        SS_Admin_Shell::card_open( __( 'Library Cards', 'school-softwere' ),
            '<form method="post" style="display:inline-block">' . wp_nonce_field( 'gen_lib_cards', '_ssnonce', true, false ) . '<input type="hidden" name="ss_action" value="gen_lib_cards"><button class="ss-btn ss-btn-secondary ss-btn-sm"><i class="ph ph-identification-card"></i> ' . esc_html__( 'Generate for All Students', 'school-softwere' ) . '</button></form>'
        );
        $rows = $wpdb->get_results( $wpdb->prepare(
            'SELECT lc.*, sr.first_name, sr.last_name, sr.admission_number FROM ' . SS_Helper::table( 'library_cards' ) . ' lc LEFT JOIN ' . SS_Helper::table( 'student_records' ) . ' sr ON sr.ID = lc.student_record_id WHERE sr.school_id = %d ORDER BY lc.ID DESC', $school_id
        ) );
        if ( empty( $rows ) ) {
            echo '<div class="ss-empty"><i class="ph ph-identification-card"></i><h3>' . esc_html__( 'No library cards', 'school-softwere' ) . '</h3></div>';
        } else {
            echo '<div class="ss-table-wrap"><table class="ss-datatable ss-table"><thead><tr><th>' . esc_html__( 'Card #', 'school-softwere' ) . '</th><th>' . esc_html__( 'Student', 'school-softwere' ) . '</th><th>' . esc_html__( 'Issued', 'school-softwere' ) . '</th><th>' . esc_html__( 'Expires', 'school-softwere' ) . '</th><th></th></tr></thead><tbody>';
            foreach ( $rows as $r ) {
                $print = SS_Helper::admin_url() . '&ss_print=library_card&id=' . $r->ID;
                echo '<tr><td><strong>' . esc_html( $r->card_number ) . '</strong></td><td>' . esc_html( trim( $r->first_name . ' ' . $r->last_name ) ) . '</td><td>' . esc_html( SS_Helper::format_date( $r->issued_date ) ) . '</td><td>' . esc_html( SS_Helper::format_date( $r->expiry_date ) ) . '</td><td class="ss-text-right"><a class="ss-btn ss-btn-secondary ss-btn-sm ss-btn-icon" href="' . esc_url( $print ) . '" target="_blank"><i class="ph ph-printer"></i></a></td></tr>';
            }
            echo '</tbody></table></div>';
        }
        SS_Admin_Shell::card_close();
    }
}

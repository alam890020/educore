<?php
/**
 * SS_Staff_Transport - Vehicles, routes, route stops.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Staff_Transport {

    public static function render() {
        global $wpdb;
        $school_id = SS_Helper::active_school_id();
        $tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'vehicles';

        // Vehicles.
        if ( isset( $_POST['ss_action'] ) && 'save_vehicle' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'save_vehicle' ) ) {
            $tbl = SS_Helper::table( 'vehicles' );
            $id  = (int) ( $_POST['id'] ?? 0 );
            $data = array(
                'school_id'    => $school_id,
                'number'       => sanitize_text_field( wp_unslash( $_POST['number'] ?? '' ) ),
                'model'        => sanitize_text_field( wp_unslash( $_POST['model'] ?? '' ) ),
                'capacity'     => (int) ( $_POST['capacity'] ?? 0 ),
                'driver_name'  => sanitize_text_field( wp_unslash( $_POST['driver_name'] ?? '' ) ),
                'driver_phone' => sanitize_text_field( wp_unslash( $_POST['driver_phone'] ?? '' ) ),
            );
            if ( $id ) { $wpdb->update( $tbl, $data, array( 'ID' => $id, 'school_id' => $school_id ) ); }
            else      { $data['created_at'] = current_time( 'mysql' ); $wpdb->insert( $tbl, $data ); }
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-transport' ) ); exit;
        }
        if ( 'delete_vehicle' === ( $_GET['view'] ?? '' ) && SS_Helper::verify_nonce( 'delete_vehicle' ) ) {
            $wpdb->delete( SS_Helper::table( 'vehicles' ), array( 'ID' => (int) $_GET['id'], 'school_id' => $school_id ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-transport' ) ); exit;
        }
        // Routes.
        if ( isset( $_POST['ss_action'] ) && 'save_route' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'save_route' ) ) {
            $tbl  = SS_Helper::table( 'routes' );
            $id   = (int) ( $_POST['id'] ?? 0 );
            $data = array( 'school_id' => $school_id, 'label' => sanitize_text_field( wp_unslash( $_POST['label'] ?? '' ) ) );
            if ( $id ) { $wpdb->update( $tbl, $data, array( 'ID' => $id, 'school_id' => $school_id ) ); }
            else      { $data['created_at'] = current_time( 'mysql' ); $wpdb->insert( $tbl, $data ); }
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-transport' ) . '&tab=routes' ); exit;
        }
        if ( 'delete_route' === ( $_GET['view'] ?? '' ) && SS_Helper::verify_nonce( 'delete_route' ) ) {
            $wpdb->delete( SS_Helper::table( 'routes' ), array( 'ID' => (int) $_GET['id'], 'school_id' => $school_id ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-transport' ) . '&tab=routes' ); exit;
        }
        // Route stop assignment.
        if ( isset( $_POST['ss_action'] ) && 'save_stop' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'save_stop' ) ) {
            $wpdb->insert( SS_Helper::table( 'route_vehicle' ), array(
                'route_id'   => (int) ( $_POST['route_id'] ?? 0 ),
                'vehicle_id' => (int) ( $_POST['vehicle_id'] ?? 0 ),
                'stop_name'  => sanitize_text_field( wp_unslash( $_POST['stop_name'] ?? '' ) ),
                'stop_time'  => sanitize_text_field( wp_unslash( $_POST['stop_time'] ?? '' ) ) ?: null,
                'fee'        => (float) ( $_POST['fee'] ?? 0 ),
            ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-transport' ) . '&tab=stops' ); exit;
        }
        if ( 'delete_stop' === ( $_GET['view'] ?? '' ) && SS_Helper::verify_nonce( 'delete_stop' ) ) {
            $wpdb->delete( SS_Helper::table( 'route_vehicle' ), array( 'ID' => (int) $_GET['id'] ) );
            wp_safe_redirect( SS_Helper::admin_url( 'school-softwere-transport' ) . '&tab=stops' ); exit;
        }

        SS_Admin_Shell::open( __( 'Transport', 'school-softwere' ), 'school-softwere-transport', array(
            array( 'label' => __( 'Transport', 'school-softwere' ) ),
        ) );

        echo '<div class="ss-tabs" style="margin-bottom:16px;">';
        $base = SS_Helper::admin_url( 'school-softwere-transport' );
        echo '<a class="ss-tab ' . ( 'vehicles' === $tab ? 'active' : '' ) . '" href="' . esc_url( $base ) . '">' . esc_html__( 'Vehicles', 'school-softwere' ) . '</a>';
        echo '<a class="ss-tab ' . ( 'routes'   === $tab ? 'active' : '' ) . '" href="' . esc_url( $base . '&tab=routes' ) . '">' . esc_html__( 'Routes', 'school-softwere' ) . '</a>';
        echo '<a class="ss-tab ' . ( 'stops'    === $tab ? 'active' : '' ) . '" href="' . esc_url( $base . '&tab=stops' ) . '">' . esc_html__( 'Stops & Fees', 'school-softwere' ) . '</a>';
        echo '</div>';

        if ( 'routes' === $tab )    { self::tab_routes( $school_id ); }
        elseif ( 'stops' === $tab ) { self::tab_stops( $school_id ); }
        else                        { self::tab_vehicles( $school_id ); }

        SS_Admin_Shell::close();
    }

    private static function tab_vehicles( $school_id ) {
        global $wpdb;
        echo '<div class="ss-row"><div class="ss-col" style="flex:1">';
        SS_Admin_Shell::card_open( __( 'Add Vehicle', 'school-softwere' ) );
        echo '<form method="post" class="ss-form">';
        SS_Helper::nonce_field( 'save_vehicle' );
        echo '<input type="hidden" name="ss_action" value="save_vehicle">';
        SS_School::field( 'number', __( 'Vehicle Number', 'school-softwere' ), '', true );
        SS_School::field( 'model', __( 'Model', 'school-softwere' ), '' );
        SS_School::field( 'capacity', __( 'Capacity', 'school-softwere' ), 0, false, 'number' );
        SS_School::field( 'driver_name', __( 'Driver Name', 'school-softwere' ), '' );
        SS_School::field( 'driver_phone', __( 'Driver Phone', 'school-softwere' ), '' );
        echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-bus"></i> ' . esc_html__( 'Save', 'school-softwere' ) . '</button></div></form>';
        SS_Admin_Shell::card_close();
        echo '</div><div class="ss-col" style="flex:2">';
        SS_Admin_Shell::card_open( __( 'All Vehicles', 'school-softwere' ) );
        $rows = SS_M_Staff_Transport::vehicles( $school_id );
        if ( empty( $rows ) ) {
            echo '<div class="ss-empty"><i class="ph ph-bus"></i><h3>' . esc_html__( 'No vehicles', 'school-softwere' ) . '</h3></div>';
        } else {
            echo '<div class="ss-table-wrap"><table class="ss-table"><thead><tr><th>' . esc_html__( 'Number', 'school-softwere' ) . '</th><th>' . esc_html__( 'Model', 'school-softwere' ) . '</th><th>' . esc_html__( 'Capacity', 'school-softwere' ) . '</th><th>' . esc_html__( 'Driver', 'school-softwere' ) . '</th><th></th></tr></thead><tbody>';
            foreach ( $rows as $r ) {
                $del = wp_nonce_url( SS_Helper::admin_url( 'school-softwere-transport' ) . '&view=delete_vehicle&id=' . $r->ID, 'delete_vehicle', '_ssnonce' );
                echo '<tr><td><strong>' . esc_html( $r->number ) . '</strong></td><td>' . esc_html( $r->model ) . '</td><td>' . (int) $r->capacity . '</td><td>' . esc_html( $r->driver_name . ' (' . $r->driver_phone . ')' ) . '</td><td class="ss-text-right"><a class="ss-btn ss-btn-danger ss-btn-sm ss-btn-icon ss-confirm-delete" href="' . esc_url( $del ) . '"><i class="ph ph-trash"></i></a></td></tr>';
            }
            echo '</tbody></table></div>';
        }
        SS_Admin_Shell::card_close();
        echo '</div></div>';
    }

    private static function tab_routes( $school_id ) {
        echo '<div class="ss-row"><div class="ss-col" style="flex:1">';
        SS_Admin_Shell::card_open( __( 'Add Route', 'school-softwere' ) );
        echo '<form method="post" class="ss-form">';
        SS_Helper::nonce_field( 'save_route' );
        echo '<input type="hidden" name="ss_action" value="save_route">';
        SS_School::field( 'label', __( 'Route Name', 'school-softwere' ), '', true );
        echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-map-trifold"></i> ' . esc_html__( 'Save', 'school-softwere' ) . '</button></div></form>';
        SS_Admin_Shell::card_close();
        echo '</div><div class="ss-col" style="flex:2">';
        SS_Admin_Shell::card_open( __( 'All Routes', 'school-softwere' ) );
        $rows = SS_M_Staff_Transport::routes( $school_id );
        if ( empty( $rows ) ) {
            echo '<div class="ss-empty"><i class="ph ph-map-trifold"></i><h3>' . esc_html__( 'No routes', 'school-softwere' ) . '</h3></div>';
        } else {
            echo '<div class="ss-table-wrap"><table class="ss-table"><thead><tr><th>' . esc_html__( 'Route', 'school-softwere' ) . '</th><th></th></tr></thead><tbody>';
            foreach ( $rows as $r ) {
                $del = wp_nonce_url( SS_Helper::admin_url( 'school-softwere-transport' ) . '&tab=routes&view=delete_route&id=' . $r->ID, 'delete_route', '_ssnonce' );
                echo '<tr><td><strong>' . esc_html( $r->label ) . '</strong></td><td class="ss-text-right"><a class="ss-btn ss-btn-danger ss-btn-sm ss-btn-icon ss-confirm-delete" href="' . esc_url( $del ) . '"><i class="ph ph-trash"></i></a></td></tr>';
            }
            echo '</tbody></table></div>';
        }
        SS_Admin_Shell::card_close();
        echo '</div></div>';
    }

    private static function tab_stops( $school_id ) {
        global $wpdb;
        $vehicles = SS_M_Staff_Transport::vehicles( $school_id );
        $routes   = SS_M_Staff_Transport::routes( $school_id );

        echo '<div class="ss-row"><div class="ss-col" style="flex:1">';
        SS_Admin_Shell::card_open( __( 'Add Stop', 'school-softwere' ) );
        echo '<form method="post" class="ss-form">';
        SS_Helper::nonce_field( 'save_stop' );
        echo '<input type="hidden" name="ss_action" value="save_stop">';
        SS_School::select( 'route_id',   __( 'Route', 'school-softwere' ),   0, $routes, true );
        SS_School::select( 'vehicle_id', __( 'Vehicle', 'school-softwere' ), 0, $vehicles, true );
        SS_School::field( 'stop_name', __( 'Stop Name', 'school-softwere' ), '', true );
        echo '<div class="ss-field"><label>' . esc_html__( 'Stop Time', 'school-softwere' ) . '</label><input class="ss-time" type="text" name="stop_time"></div>';
        SS_School::field( 'fee', __( 'Monthly Fee', 'school-softwere' ), 0, false, 'number' );
        echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-plus"></i> ' . esc_html__( 'Add', 'school-softwere' ) . '</button></div></form>';
        SS_Admin_Shell::card_close();
        echo '</div><div class="ss-col" style="flex:2">';
        SS_Admin_Shell::card_open( __( 'Route Stops', 'school-softwere' ) );
        $rows = $wpdb->get_results( $wpdb->prepare(
            'SELECT rv.*, r.label route_label, v.number vehicle_number FROM ' . SS_Helper::table( 'route_vehicle' ) . ' rv
             LEFT JOIN ' . SS_Helper::table( 'routes' ) . ' r ON r.ID = rv.route_id
             LEFT JOIN ' . SS_Helper::table( 'vehicles' ) . ' v ON v.ID = rv.vehicle_id
             WHERE r.school_id = %d ORDER BY rv.ID DESC', $school_id
        ) );
        if ( empty( $rows ) ) {
            echo '<div class="ss-empty"><i class="ph ph-map-pin"></i><h3>' . esc_html__( 'No stops', 'school-softwere' ) . '</h3></div>';
        } else {
            echo '<div class="ss-table-wrap"><table class="ss-table"><thead><tr><th>' . esc_html__( 'Route', 'school-softwere' ) . '</th><th>' . esc_html__( 'Vehicle', 'school-softwere' ) . '</th><th>' . esc_html__( 'Stop', 'school-softwere' ) . '</th><th>' . esc_html__( 'Time', 'school-softwere' ) . '</th><th>' . esc_html__( 'Fee', 'school-softwere' ) . '</th><th></th></tr></thead><tbody>';
            foreach ( $rows as $r ) {
                $del = wp_nonce_url( SS_Helper::admin_url( 'school-softwere-transport' ) . '&tab=stops&view=delete_stop&id=' . $r->ID, 'delete_stop', '_ssnonce' );
                echo '<tr><td>' . esc_html( $r->route_label ) . '</td><td>' . esc_html( $r->vehicle_number ) . '</td><td>' . esc_html( $r->stop_name ) . '</td><td>' . esc_html( $r->stop_time ) . '</td><td>' . esc_html( SS_Helper::format_money( $r->fee ) ) . '</td><td class="ss-text-right"><a class="ss-btn ss-btn-danger ss-btn-sm ss-btn-icon ss-confirm-delete" href="' . esc_url( $del ) . '"><i class="ph ph-trash"></i></a></td></tr>';
            }
            echo '</tbody></table></div>';
        }
        SS_Admin_Shell::card_close();
        echo '</div></div>';
    }
}

<?php
/**
 * SS_Setting - Plugin settings page.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Setting {

    public static function render() {
        if ( ! current_user_can( SS_CAP_SUPER ) ) { wp_die( esc_html__( 'No access.', 'school-softwere' ) ); }

        if ( isset( $_POST['ss_action'] ) && 'save_settings' === $_POST['ss_action'] && SS_Helper::verify_nonce( 'save_settings' ) ) {
            $current = SS_Config::all();
            $merged  = array_merge( $current, array(
                'school_name'      => sanitize_text_field( wp_unslash( $_POST['school_name'] ?? '' ) ),
                'tagline'          => sanitize_text_field( wp_unslash( $_POST['tagline'] ?? '' ) ),
                'currency_symbol'  => sanitize_text_field( wp_unslash( $_POST['currency_symbol'] ?? '$' ) ),
                'currency_code'    => sanitize_text_field( wp_unslash( $_POST['currency_code'] ?? 'USD' ) ),
                'date_format'      => sanitize_text_field( wp_unslash( $_POST['date_format'] ?? 'd-m-Y' ) ),
                'invoice_prefix'   => sanitize_text_field( wp_unslash( $_POST['invoice_prefix'] ?? 'INV-' ) ),
                'invoice_padding'  => max( 1, (int) ( $_POST['invoice_padding'] ?? 5 ) ),
                'late_fee_amount'  => (float) ( $_POST['late_fee_amount'] ?? 0 ),
                'theme_mode'       => in_array( ( $_POST['theme_mode'] ?? '' ), array( 'light', 'dark' ), true ) ? $_POST['theme_mode'] : 'light',
                'watermark_enabled'=> empty( $_POST['watermark_enabled'] ) ? 0 : 1,
            ) );
            update_option( 'ss_settings', $merged );
            wp_safe_redirect( add_query_arg( array( 'ss_notice' => __( 'Settings saved', 'school-softwere' ), 'ss_notice_type' => 'success' ), SS_Helper::admin_url( 'school-softwere-settings' ) ) );
            exit;
        }

        $cfg = SS_Config::all();

        SS_Admin_Shell::open( __( 'Settings', 'school-softwere' ), 'school-softwere-settings', array(
            array( 'label' => __( 'Settings', 'school-softwere' ) ),
        ) );

        SS_Admin_Shell::card_open( __( 'General Settings', 'school-softwere' ) );
        echo '<form method="post" class="ss-form">';
        SS_Helper::nonce_field( 'save_settings' );
        echo '<input type="hidden" name="ss_action" value="save_settings">';

        echo '<div class="ss-form-section"><h3 class="ss-form-section-title"><i class="ph ph-buildings"></i> ' . esc_html__( 'School Identity', 'school-softwere' ) . '</h3><div class="ss-form-grid">';
        SS_School::field( 'school_name', __( 'School Display Name', 'school-softwere' ), $cfg['school_name'] ?? '' );
        SS_School::field( 'tagline',     __( 'Tagline', 'school-softwere' ),             $cfg['tagline'] ?? '' );
        echo '</div></div>';

        echo '<div class="ss-form-section"><h3 class="ss-form-section-title"><i class="ph ph-currency-circle-dollar"></i> ' . esc_html__( 'Currency & Format', 'school-softwere' ) . '</h3><div class="ss-form-grid">';
        SS_School::field( 'currency_symbol', __( 'Currency Symbol', 'school-softwere' ), $cfg['currency_symbol'] ?? '$' );
        SS_School::field( 'currency_code',   __( 'Currency Code', 'school-softwere' ),   $cfg['currency_code'] ?? 'USD' );
        SS_School::field( 'date_format',     __( 'Date Format', 'school-softwere' ),     $cfg['date_format'] ?? 'd-m-Y' );
        echo '</div></div>';

        echo '<div class="ss-form-section"><h3 class="ss-form-section-title"><i class="ph ph-receipt"></i> ' . esc_html__( 'Invoice Settings', 'school-softwere' ) . '</h3><div class="ss-form-grid">';
        SS_School::field( 'invoice_prefix',  __( 'Invoice Prefix', 'school-softwere' ),  $cfg['invoice_prefix'] ?? 'INV-' );
        SS_School::field( 'invoice_padding', __( 'Invoice Padding', 'school-softwere' ), $cfg['invoice_padding'] ?? 5, false, 'number' );
        SS_School::field( 'late_fee_amount', __( 'Late Fee Amount', 'school-softwere' ), $cfg['late_fee_amount'] ?? 0, false, 'number' );
        echo '</div></div>';

        echo '<div class="ss-form-section"><h3 class="ss-form-section-title"><i class="ph ph-paint-brush"></i> ' . esc_html__( 'Appearance', 'school-softwere' ) . '</h3><div class="ss-form-grid">';
        echo '<div class="ss-field"><label>' . esc_html__( 'Theme Mode', 'school-softwere' ) . '</label><select name="theme_mode" class="ss-select2"><option value="light"' . selected( $cfg['theme_mode'] ?? 'light', 'light', false ) . '>Light</option><option value="dark"' . selected( $cfg['theme_mode'] ?? 'light', 'dark', false ) . '>Dark</option></select></div>';
        SS_School::checkbox( 'watermark_enabled', __( 'Enable Watermark on Print Documents', 'school-softwere' ), ! empty( $cfg['watermark_enabled'] ) );
        echo '</div></div>';

        echo '<div class="ss-form-actions"><button class="ss-btn"><i class="ph ph-floppy-disk"></i> ' . esc_html__( 'Save Settings', 'school-softwere' ) . '</button></div>';
        echo '</form>';
        SS_Admin_Shell::card_close();
        SS_Admin_Shell::close();
    }
}

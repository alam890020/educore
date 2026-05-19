<?php
/**
 * SS_Config - Plugin configuration accessor.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Config {

    /**
     * Get a plugin setting (option key: ss_settings) with fallback default.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public static function get( $key, $default = null ) {
        $settings = get_option( 'ss_settings', array() );
        return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
    }

    /**
     * Set a plugin setting.
     *
     * @param string $key
     * @param mixed  $value
     */
    public static function set( $key, $value ) {
        $settings         = get_option( 'ss_settings', array() );
        $settings[ $key ] = $value;
        update_option( 'ss_settings', $settings );
    }

    /**
     * All settings as an array.
     *
     * @return array
     */
    public static function all() {
        return (array) get_option( 'ss_settings', array() );
    }

    /**
     * Default plugin settings inserted on activation.
     *
     * @return array
     */
    public static function defaults() {
        return array(
            'currency_symbol'      => '$',
            'currency_code'        => 'USD',
            'date_format'          => 'd-m-Y',
            'time_format'          => 'H:i',
            'academic_year_start'  => 4, // April.
            'school_name'          => get_bloginfo( 'name' ),
            'tagline'              => '',
            'invoice_prefix'       => 'INV-',
            'invoice_padding'      => 5,
            'admission_prefix'     => 'ADM-',
            'admission_padding'    => 5,
            'late_fee_amount'      => 0,
            'working_days'         => array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday' ),
            'watermark_enabled'    => 0,
            'theme_mode'           => 'light',
        );
    }
}

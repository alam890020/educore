<?php
/**
 * SS_Login - Frontend login shortcode for students/parents/staff.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Login {

    public static function shortcode( $atts = array() ) {
        $atts = shortcode_atts( array(
            'redirect' => '',
        ), $atts, 'ss_login' );

        ob_start();
        echo '<div class="ss-public">';
        if ( is_user_logged_in() ) {
            $u = wp_get_current_user();
            echo '<div class="ss-public-card">';
            echo '<h3>' . esc_html__( 'Welcome', 'school-softwere' ) . ', ' . esc_html( $u->display_name ) . '</h3>';
            echo '<p class="meta">' . esc_html( $u->user_email ) . '</p>';
            echo '<a class="ss-public-btn" href="' . esc_url( wp_logout_url( home_url() ) ) . '">' . esc_html__( 'Logout', 'school-softwere' ) . '</a>';
            echo '</div>';
        } else {
            $redirect = $atts['redirect'] ?: home_url( $_SERVER['REQUEST_URI'] ?? '/' );
            echo '<div class="ss-public-card">';
            echo '<h3>' . esc_html__( 'Login', 'school-softwere' ) . '</h3>';
            wp_login_form( array(
                'redirect'       => esc_url( $redirect ),
                'form_id'        => 'ss-login-form',
                'label_username' => __( 'Username or Email', 'school-softwere' ),
                'label_password' => __( 'Password', 'school-softwere' ),
                'label_remember' => __( 'Remember Me', 'school-softwere' ),
                'label_log_in'   => __( 'Sign In', 'school-softwere' ),
            ) );
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }
}

<?php
/**
 * SS_Login_Widget - Sidebar login widget.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Login_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct( 'ss_login_widget', __( 'School Softwere Login', 'school-softwere' ),
            array( 'description' => __( 'Display student/parent/staff login.', 'school-softwere' ) )
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget']; // phpcs:ignore
        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . esc_html( $instance['title'] ) . $args['after_title']; // phpcs:ignore
        }
        echo do_shortcode( '[ss_login]' );
        echo $args['after_widget']; // phpcs:ignore
    }

    public function form( $instance ) {
        $title = $instance['title'] ?? __( 'Portal Login', 'school-softwere' );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'school-softwere' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        return array( 'title' => sanitize_text_field( $new_instance['title'] ?? '' ) );
    }
}

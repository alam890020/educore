<?php
/**
 * SS_Noticeboard_Widget - Sidebar noticeboard widget.
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_Noticeboard_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct( 'ss_noticeboard_widget', __( 'School Softwere Noticeboard', 'school-softwere' ),
            array( 'description' => __( 'Display latest notices.', 'school-softwere' ) )
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget']; // phpcs:ignore
        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . esc_html( $instance['title'] ) . $args['after_title']; // phpcs:ignore
        }
        $limit = max( 1, (int) ( $instance['limit'] ?? 5 ) );
        echo do_shortcode( '[ss_noticeboard limit="' . $limit . '"]' );
        echo $args['after_widget']; // phpcs:ignore
    }

    public function form( $instance ) {
        $title = $instance['title'] ?? __( 'Notices', 'school-softwere' );
        $limit = $instance['limit'] ?? 5;
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'school-softwere' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>"><?php esc_html_e( 'Limit:', 'school-softwere' ); ?></label>
            <input id="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'limit' ) ); ?>" type="number" value="<?php echo esc_attr( $limit ); ?>">
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        return array(
            'title' => sanitize_text_field( $new_instance['title'] ?? '' ),
            'limit' => (int) ( $new_instance['limit'] ?? 5 ),
        );
    }
}

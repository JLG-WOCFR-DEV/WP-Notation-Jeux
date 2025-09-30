<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class JLG_Latest_Reviews_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'jlg_latest_reviews_widget',
            __( 'Notation JLG : Derniers Tests', 'notation-jlg' ),
            array( 'description' => __( 'Affiche les derniers articles ayant reçu une note.', 'notation-jlg' ) )
        );
    }

    public function widget( $args, $instance ) {
        $title  = apply_filters( 'widget_title', $instance['title'] ?? __( 'Derniers Tests', 'notation-jlg' ) );
        $number = ( ! empty( $instance['number'] ) ) ? absint( $instance['number'] ) : 5;

        $rated_post_ids = JLG_Helpers::get_rated_post_ids();

        if ( empty( $rated_post_ids ) ) {
            echo $args['before_widget'];
            if ( ! empty( $title ) ) {
                echo $args['before_title'] . $title . $args['after_title'];
            }
            echo '<p>' . esc_html__( 'Aucun test trouvé.', 'notation-jlg' ) . '</p>';
            echo $args['after_widget'];
            return;
        }

        $query_args = array(
            'post_type'           => JLG_Helpers::get_allowed_post_types(),
            'posts_per_page'      => $number,
            'post__in'            => $rated_post_ids,
            'orderby'             => 'date',
            'order'               => 'DESC',
            'ignore_sticky_posts' => 1,
        );

        $latest_reviews = new WP_Query( $query_args );

        // Utilisation d'un fichier template pour l'affichage
        echo JLG_Frontend::get_template_html(
            'widget-latest-reviews',
            array(
				'widget_args'    => $args,
				'title'          => $title,
				'latest_reviews' => $latest_reviews,
			)
        );
    }

    public function form( $instance ) {
        $title  = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Derniers Tests', 'notation-jlg' );
        $number = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php echo esc_html__( 'Titre :', 'notation-jlg' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                    name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
                    type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>"><?php echo esc_html__( 'Nombre d\'articles à afficher :', 'notation-jlg' ); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>"
                    name="<?php echo esc_attr( $this->get_field_name( 'number' ) ); ?>"
                    type="number" step="1" min="1" value="<?php echo esc_attr( $number ); ?>" size="3">
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance           = array();
        $instance['title']  = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        $instance['number'] = ( ! empty( $new_instance['number'] ) ) ? absint( $new_instance['number'] ) : 5;
        return $instance;
    }
}
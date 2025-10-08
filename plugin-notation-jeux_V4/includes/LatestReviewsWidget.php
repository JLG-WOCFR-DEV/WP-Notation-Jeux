<?php

namespace JLG\Notation;

use JLG\Notation\Frontend;
use JLG\Notation\Helpers;
use WP_Query;
use WP_Widget;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LatestReviewsWidget extends WP_Widget {

    private const DEFAULT_POST_COUNT = 5;

    public function __construct() {
        parent::__construct(
            'jlg_latest_reviews_widget',
            __( 'Notation JLG : Derniers Tests', 'notation-jlg' ),
            array(
                'description' => __( 'Affiche les derniers articles ayant reçu une note.', 'notation-jlg' ),
            )
        );
    }

    public function widget( $args, $instance ) {
        $settings = $this->parse_instance_settings( $instance );

        $title      = apply_filters( 'widget_title', $settings['title'] );
        $post_limit = $settings['number'];

        $allowed_post_types = Helpers::get_allowed_post_types();

        if ( empty( $allowed_post_types ) ) {
            $this->render_empty_widget( $args, $title );
            return;
        }

        $rated_post_ids = Helpers::get_rated_post_ids();

        if ( empty( $rated_post_ids ) ) {
            $this->render_empty_widget( $args, $title );
            return;
        }

        $query_args = $this->build_query_args( $post_limit, $rated_post_ids, $allowed_post_types );

        $query_args = apply_filters(
            'jlg_latest_reviews_widget_query_args',
            $query_args,
            array(
                'title'  => $settings['title'],
                'number' => $post_limit,
            ),
            $args,
            $this
        );

        $latest_reviews = new WP_Query( $query_args );

        echo Frontend::get_template_html(
            'widget-latest-reviews',
            array(
                'widget_args'    => $args,
                'title'          => $title,
                'latest_reviews' => $latest_reviews,
            )
        );
    }

    public function form( $instance ) {
        $settings = $this->parse_instance_settings( $instance );

        $title  = $settings['title'];
        $number = $settings['number'];
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
        unset( $old_instance );

        $settings = $this->parse_instance_settings( $new_instance );

        return array(
            'title'  => $settings['title'],
            'number' => $settings['number'],
        );
    }

    /**
     * Normalise et sécurise les paramètres saisis dans le formulaire du widget.
     *
     * @param array|null $instance Valeurs brutes issues de WordPress.
     * @return array{title:string,number:int}
     */
    private function parse_instance_settings( $instance ) {
        if ( ! is_array( $instance ) ) {
            $instance = array();
        }

        $raw_title = isset( $instance['title'] ) && is_string( $instance['title'] )
            ? $instance['title']
            : '';

        $title = $raw_title !== ''
            ? sanitize_text_field( wp_strip_all_tags( $raw_title ) )
            : __( 'Derniers Tests', 'notation-jlg' );

        $raw_number = isset( $instance['number'] ) ? absint( $instance['number'] ) : 0;

        if ( $raw_number < 1 ) {
            $raw_number = self::DEFAULT_POST_COUNT;
        }

        return array(
            'title'  => $title,
            'number' => $raw_number,
        );
    }

    /**
     * Construit les arguments WP_Query utilisés par le widget.
     *
     * @param int     $post_limit         Nombre de posts à afficher.
     * @param int[]   $rated_post_ids     Liste brute des identifiants notés.
     * @param string[] $allowed_post_types Types de contenus autorisés.
     * @return array
     */
    private function build_query_args( $post_limit, $rated_post_ids, $allowed_post_types ) {
        $post_limit = max( 1, (int) $post_limit );

        $post_ids = array_values( array_unique( array_filter( array_map( 'intval', (array) $rated_post_ids ) ) ) );

        $max_ids = $post_limit * 3;
        if ( $max_ids > 0 && count( $post_ids ) > $max_ids ) {
            $post_ids = array_slice( $post_ids, -$max_ids, $max_ids );
        }

        if ( empty( $post_ids ) ) {
            $post_ids = array( 0 );
        }

        $allowed_post_types = array_values( array_filter( array_map( 'sanitize_key', (array) $allowed_post_types ) ) );

        if ( empty( $allowed_post_types ) ) {
            $allowed_post_types = array( 'post' );
        }

        return array(
            'post_type'              => $allowed_post_types,
            'posts_per_page'         => $post_limit,
            'post__in'               => $post_ids,
            'orderby'                => 'date',
            'order'                  => 'DESC',
            'ignore_sticky_posts'    => true,
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'lazy_load_term_meta'    => false,
        );
    }

    /**
     * Affiche le widget vide lorsqu'aucun contenu n'est disponible.
     *
     * @param array  $args  Arguments fournis par WordPress.
     * @param string $title Titre à afficher.
     * @return void
     */
    private function render_empty_widget( $args, $title ) {
        echo $args['before_widget'];

        if ( ! empty( $title ) ) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        echo '<p>' . esc_html__( 'Aucun test trouvé.', 'notation-jlg' ) . '</p>';
        echo $args['after_widget'];
    }
}

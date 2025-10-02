<?php

namespace JLG\Notation\Shortcodes;

use JLG\Notation\Frontend;
use JLG\Notation\Helpers;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

class RatingBlock {

    public function __construct() {
        add_shortcode( 'bloc_notation_jeu', array( $this, 'render' ) );
    }

    public function render( $atts, $content = '', $shortcode_tag = '' ) {
        $atts = shortcode_atts(
            array(
				'post_id' => get_the_ID(),
            ),
            $atts,
            'bloc_notation_jeu'
        );

        $post_id = intval( $atts['post_id'] );

        if ( ! $post_id ) {
            return '';
        }

        $post          = get_post( $post_id );
        $allowed_types = Helpers::get_allowed_post_types();

        if ( ! $post instanceof WP_Post || ! in_array( $post->post_type ?? '', $allowed_types, true ) ) {
            return '';
        }

        if ( ( $post->post_status ?? '' ) !== 'publish' && ! current_user_can( 'read_post', $post_id ) ) {
            return '';
        }

        // Sécurité : ne s'exécute que si des notes existent
        $average_score = Helpers::get_average_score_for_post( $post_id );
        if ( $average_score === null ) {
            return '';
        }

        $categories = Helpers::get_rating_categories();
        $scores     = array();

        foreach ( array_keys( $categories ) as $key ) {
            $score_value = get_post_meta( $post_id, '_note_' . $key, true );
            if ( $score_value !== '' && is_numeric( $score_value ) ) {
                $scores[ $key ] = floatval( $score_value );
            }
        }

        Frontend::mark_shortcode_rendered( $shortcode_tag ?: 'bloc_notation_jeu' );

        return Frontend::get_template_html(
            'shortcode-rating-block',
            array(
				'options'       => Helpers::get_plugin_options(),
				'average_score' => $average_score,
				'scores'        => $scores,
				'categories'    => $categories,
			)
        );
    }
}

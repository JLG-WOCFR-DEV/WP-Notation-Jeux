<?php

namespace JLG\Notation\Shortcodes;

use JLG\Notation\Frontend;
use JLG\Notation\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

class UserRating {

    public function __construct() {
        add_shortcode( 'notation_utilisateurs_jlg', array( $this, 'render' ) );
    }

    public function render( $atts = array(), $content = '', $shortcode_tag = '' ) {
        $allowed_types = Helpers::get_allowed_post_types();

        if ( ! is_singular( $allowed_types ) ) {
            return '';
        }

        $options = Helpers::get_plugin_options();
        if ( empty( $options['user_rating_enabled'] ) ) {
            return '';
        }

        $post_id                     = get_the_ID();
        list($has_voted, $user_vote) = Frontend::get_user_vote_for_post( $post_id );

        Frontend::mark_shortcode_rendered( $shortcode_tag ?: 'notation_utilisateurs_jlg' );

        return Frontend::get_template_html(
            'shortcode-user-rating',
            array(
                                'options'    => $options,
                                'post_id'    => $post_id,
                                'avg_rating' => get_post_meta( $post_id, '_jlg_user_rating_avg', true ),
                                'count'      => get_post_meta( $post_id, '_jlg_user_rating_count', true ),
                                'rating_breakdown' => Frontend::get_user_rating_breakdown_for_post( $post_id ),
                                'has_voted'  => $has_voted,
                                'user_vote'  => $user_vote,
                        )
        );
    }
}

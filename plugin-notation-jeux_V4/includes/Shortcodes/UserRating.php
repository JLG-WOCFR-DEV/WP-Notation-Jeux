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

        $requires_login = ! empty( $options['user_rating_requires_login'] );
        $is_logged_in   = function_exists( 'is_user_logged_in' ) ? is_user_logged_in() : false;

        $permalink = '';
        if ( function_exists( 'get_permalink' ) ) {
            $permalink = get_permalink( $post_id );
        }

        $login_url = '';
        if ( $requires_login && function_exists( 'wp_login_url' ) ) {
            $login_url = wp_login_url( $permalink );
        }

        /**
         * Permet de personnaliser l'URL de connexion utilisée par le module de vote.
         *
         * @param string $login_url URL de connexion calculée par défaut.
         * @param int    $post_id   Identifiant du contenu affiché.
         * @param array  $options   Options du plugin.
         */
        $login_url = apply_filters( 'jlg_user_rating_login_url', $login_url, $post_id, $options );

        if ( ! is_string( $login_url ) ) {
            $login_url = '';
        }

        $login_required = $requires_login && ! $is_logged_in;

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
                                'requires_login' => $requires_login,
                                'login_required' => $login_required,
                                'login_url'      => $login_url,
                                'is_logged_in'   => $is_logged_in,
                        )
        );
    }
}

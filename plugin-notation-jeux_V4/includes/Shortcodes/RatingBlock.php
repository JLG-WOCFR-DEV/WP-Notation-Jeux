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
                'post_id'      => get_the_ID(),
                'score_layout' => '',
                'animations'   => '',
                'accent_color' => '',
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
            if ( $this->is_editor_preview_context() ) {
                $shortcode_handle = $shortcode_tag ?: 'bloc_notation_jeu';

                Frontend::mark_shortcode_rendered( $shortcode_handle );

                return Frontend::get_template_html(
                    'shortcode-rating-block-empty',
                    array(
                        'post'    => $post,
                        'post_id' => $post_id,
                    )
                );
            }

            return '';
        }

        $category_scores = Helpers::get_category_scores_for_display( $post_id );
        $score_map       = array();

        foreach ( $category_scores as $category_score ) {
            if ( isset( $category_score['id'], $category_score['score'] ) ) {
                $category_id = (string) $category_score['id'];

                $score_map[ $category_id ] = array(
                    'score'  => (float) $category_score['score'],
                    'weight' => isset( $category_score['weight'] )
                        ? Helpers::normalize_category_weight( $category_score['weight'], 1.0 )
                        : 1.0,
                );
            }
        }

        $options  = Helpers::get_plugin_options();
        $defaults = Helpers::get_default_settings();

        $score_layout = is_string( $atts['score_layout'] ) ? sanitize_key( $atts['score_layout'] ) : '';
        if ( in_array( $score_layout, array( 'text', 'circle' ), true ) ) {
            $options['score_layout'] = $score_layout;
        } elseif ( ! isset( $options['score_layout'] ) ) {
            $options['score_layout'] = $defaults['score_layout'] ?? 'text';
        }

        $animations_override = $this->normalize_bool_attribute( $atts['animations'] );
        if ( $animations_override !== null ) {
            $options['enable_animations'] = $animations_override ? 1 : 0;
        } elseif ( ! isset( $options['enable_animations'] ) ) {
            $options['enable_animations'] = ! empty( $defaults['enable_animations'] );
        }

        $accent_color = '';
        if ( is_string( $atts['accent_color'] ) && $atts['accent_color'] !== '' ) {
            $accent_color = sanitize_hex_color( $atts['accent_color'] );
        }

        if ( ! empty( $accent_color ) ) {
            $options['score_gradient_1'] = $accent_color;
            $options['score_gradient_2'] = Helpers::adjust_hex_brightness( $accent_color, 20 );
            $options['color_high']       = $accent_color;
            $options['color_mid']        = Helpers::adjust_hex_brightness( $accent_color, -10 );
            $options['color_low']        = Helpers::adjust_hex_brightness( $accent_color, -25 );
            $options['accent_color']     = $accent_color;
        }

        $resolved_score_layout = in_array( $options['score_layout'] ?? '', array( 'text', 'circle' ), true )
            ? $options['score_layout']
            : 'text';

        $animations_enabled = ! empty( $options['enable_animations'] );
        $css_variables       = $this->build_css_variables( $options );
        $score_max           = Helpers::get_score_max( $options );

        Frontend::mark_shortcode_rendered( $shortcode_tag ?: 'bloc_notation_jeu' );

        return Frontend::get_template_html(
            'shortcode-rating-block',
            array(
                'options'              => $options,
                'average_score'        => $average_score,
                'scores'               => $score_map,
                'category_scores'      => $category_scores,
                'category_definitions' => Helpers::get_rating_category_definitions(),
                'score_layout'         => $resolved_score_layout,
                'animations_enabled'   => $animations_enabled,
                'css_variables'        => $css_variables,
                'score_max'            => $score_max,
            )
        );
    }

    private function build_css_variables( array $options ) {
        $variables = array(
            '--jlg-score-gradient-1' => isset( $options['score_gradient_1'] ) ? (string) $options['score_gradient_1'] : '',
            '--jlg-score-gradient-2' => isset( $options['score_gradient_2'] ) ? (string) $options['score_gradient_2'] : '',
            '--jlg-color-high'       => isset( $options['color_high'] ) ? (string) $options['color_high'] : '',
            '--jlg-color-mid'        => isset( $options['color_mid'] ) ? (string) $options['color_mid'] : '',
            '--jlg-color-low'        => isset( $options['color_low'] ) ? (string) $options['color_low'] : '',
        );

        $rules = array();

        foreach ( $variables as $name => $value ) {
            if ( is_string( $value ) && $value !== '' ) {
                $rules[] = $name . ':' . $value;
            }
        }

        return implode( ';', $rules );
    }

    private function normalize_bool_attribute( $value ) {
        if ( is_bool( $value ) ) {
            return $value;
        }

        if ( is_numeric( $value ) ) {
            return intval( $value ) === 1;
        }

        if ( is_string( $value ) ) {
            $normalized = strtolower( trim( $value ) );

            if ( in_array( $normalized, array( '1', 'true', 'on', 'yes', 'oui' ), true ) ) {
                return true;
            }

            if ( in_array( $normalized, array( '0', 'false', 'off', 'no', 'non' ), true ) ) {
                return false;
            }
        }

        return null;
    }

    private function is_editor_preview_context() {
        if ( ! function_exists( 'is_admin' ) || ! is_admin() ) {
            return false;
        }

        $doing_ajax = function_exists( 'wp_doing_ajax' )
            ? wp_doing_ajax()
            : ( defined( 'DOING_AJAX' ) && DOING_AJAX );

        if ( $doing_ajax ) {
            return true;
        }

        if ( function_exists( 'doing_filter' ) && doing_filter( 'rest_request_after_callbacks' ) ) {
            return true;
        }

        return false;
    }
}

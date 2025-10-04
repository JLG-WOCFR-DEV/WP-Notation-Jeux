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
                'display_mode' => '',
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
        $verdict_text    = Helpers::get_review_verdict( $post_id );
        $format_verdict  = static function ( $text ) {
            $sanitized = esc_html( $text );

            if ( $sanitized === '' ) {
                return '';
            }

            if ( function_exists( 'wpautop' ) ) {
                return wpautop( $sanitized );
            }

            $paragraphs = array_filter( preg_split( '/\r?\n/', $sanitized ) );

            if ( empty( $paragraphs ) ) {
                return '<p>' . $sanitized . '</p>';
            }

            $paragraphs = array_map(
                static function ( $paragraph ) {
                    return trim( $paragraph );
                },
                $paragraphs
            );

            return '<p>' . implode( '<br />', $paragraphs ) . '</p>';
        };
        $verdict_markup  = $verdict_text !== '' ? $format_verdict( $verdict_text ) : '';
        $editor_choice   = Helpers::is_editor_choice( $post_id );

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

        $display_mode = is_string( $atts['display_mode'] ) ? sanitize_key( $atts['display_mode'] ) : '';
        if ( ! in_array( $display_mode, array( 'absolute', 'percent' ), true ) ) {
            $display_mode = 'absolute';
        }

        $average_percentage = null;
        if ( $score_max > 0 ) {
            $average_percentage = max( 0, min( 100, ( $average_score / $score_max ) * 100 ) );
        }

        $category_percentages = array();
        if ( $score_max > 0 ) {
            foreach ( $category_scores as $category_score ) {
                if ( isset( $category_score['id'], $category_score['score'] ) ) {
                    $category_percentages[ (string) $category_score['id'] ] = max(
                        0,
                        min( 100, ( (float) $category_score['score'] / $score_max ) * 100 )
                    );
                }
            }
        }

        Frontend::mark_shortcode_rendered( $shortcode_tag ?: 'bloc_notation_jeu' );

        $editor_choice_label = apply_filters( 'jlg_editor_choice_badge_label', __( 'Recommandé', 'notation-jlg' ), $post_id );
        if ( ! is_string( $editor_choice_label ) || $editor_choice_label === '' ) {
            $editor_choice_label = __( 'Recommandé', 'notation-jlg' );
        }

        return Frontend::get_template_html(
            'shortcode-rating-block',
            array(
                'options'              => $options,
                'average_score'        => $average_score,
                'average_score_percentage' => $average_percentage,
                'scores'               => $score_map,
                'category_scores'      => $category_scores,
                'category_percentages' => $category_percentages,
                'category_definitions' => Helpers::get_rating_category_definitions(),
                'score_layout'         => $resolved_score_layout,
                'display_mode'         => $display_mode,
                'animations_enabled'   => $animations_enabled,
                'css_variables'        => $css_variables,
                'score_max'            => $score_max,
                'verdict_markup'       => $verdict_markup,
                'verdict_text'         => $verdict_text,
                'show_editor_choice_badge' => $editor_choice,
                'editor_choice_label'  => $editor_choice_label,
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
            '--jlg-editor-badge-color' => isset( $options['color_high'] ) ? (string) $options['color_high'] : '',
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

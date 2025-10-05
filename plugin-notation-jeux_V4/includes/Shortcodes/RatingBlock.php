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
                'preview_meta' => '',
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

        $display_mode = is_string( $atts['display_mode'] ) ? sanitize_key( $atts['display_mode'] ) : '';
        if ( ! in_array( $display_mode, array( 'absolute', 'percent' ), true ) ) {
            $display_mode = 'absolute';
        }

        $score_max = Helpers::get_score_max( $options );

        $preview_meta     = $this->parse_preview_meta_attribute( $atts['preview_meta'] ?? '' );
        $category_context = $this->build_category_context( $post_id, $score_max, $preview_meta );

        $average_score        = $category_context['average_score'];
        $category_scores      = $category_context['category_scores'];
        $score_map            = $category_context['score_map'];
        $category_percentages = $category_context['category_percentages'];
        $badge_override       = $category_context['badge_override'];

        if ( $average_score === null ) {
            if ( $this->is_editor_preview_context() ) {
                $shortcode_handle    = $shortcode_tag ?: 'bloc_notation_jeu';
                $placeholder_context = $this->build_editor_placeholder_context(
                    $post,
                    $post_id,
                    $options,
                    $score_max,
                    $display_mode
                );

                Frontend::mark_shortcode_rendered( $shortcode_handle );

                return Frontend::get_template_html(
                    'shortcode-rating-block-empty',
                    $placeholder_context
                );
            }

            return '';
        }

        $raw_user_rating     = get_post_meta( $post_id, '_jlg_user_rating_avg', true );
        $user_rating_average = null;

        if ( is_string( $raw_user_rating ) ) {
            $raw_user_rating = trim( $raw_user_rating );
        }

        if ( is_numeric( $raw_user_rating ) ) {
            $user_rating_average = (float) $raw_user_rating;
        }

        $resolved_score_layout = in_array( $options['score_layout'] ?? '', array( 'text', 'circle' ), true )
            ? $options['score_layout']
            : 'text';

        $animations_enabled = ! empty( $options['enable_animations'] );
        $css_variables       = $this->build_css_variables( $options );

        $badge_threshold = isset( $options['rating_badge_threshold'] ) && is_numeric( $options['rating_badge_threshold'] )
            ? (float) $options['rating_badge_threshold']
            : (float) ( $defaults['rating_badge_threshold'] ?? 0 );

        $should_show_badge = ! empty( $options['rating_badge_enabled'] )
            && is_numeric( $average_score )
            && $average_score >= $badge_threshold;

        if ( $badge_override === 'force-on' ) {
            $should_show_badge = true;
        } elseif ( $badge_override === 'force-off' ) {
            $should_show_badge = false;
        }

        $user_rating_delta = null;
        if ( $user_rating_average !== null && is_numeric( $average_score ) ) {
            $user_rating_delta = $user_rating_average - (float) $average_score;
        }

        $average_percentage = null;
        if ( $score_max > 0 ) {
            $average_percentage = max( 0, min( 100, ( $average_score / $score_max ) * 100 ) );
        }

        Frontend::mark_shortcode_rendered( $shortcode_tag ?: 'bloc_notation_jeu' );

        return Frontend::get_template_html(
            'shortcode-rating-block',
            array(
                'options'                  => $options,
                'average_score'            => $average_score,
                'average_score_percentage' => $average_percentage,
                'scores'                   => $score_map,
                'category_scores'          => $category_scores,
                'category_percentages'     => $category_percentages,
                'category_definitions'     => Helpers::get_rating_category_definitions(),
                'score_layout'             => $resolved_score_layout,
                'display_mode'             => $display_mode,
                'animations_enabled'       => $animations_enabled,
                'css_variables'            => $css_variables,
                'score_max'                => $score_max,
                'should_show_rating_badge' => $should_show_badge,
                'user_rating_average'      => $user_rating_average,
                'user_rating_delta'        => $user_rating_delta,
                'rating_badge_threshold'   => $badge_threshold,
            )
        );
    }

    private function parse_preview_meta_attribute( $raw_value ) {
        if ( is_array( $raw_value ) ) {
            return $raw_value;
        }

        if ( ! is_string( $raw_value ) || $raw_value === '' ) {
            return array();
        }

        $decoded = json_decode( $raw_value, true );

        if ( ! is_array( $decoded ) ) {
            return array();
        }

        return $decoded;
    }

    private function build_category_context( $post_id, $score_max, array $preview_meta ) {
        $definitions           = Helpers::get_rating_category_definitions();
        $category_scores       = array();
        $score_map             = array();
        $category_percentages  = array();
        $weighted_sum          = 0.0;
        $weight_total          = 0.0;
        $normalized_score_max  = is_numeric( $score_max ) ? (float) $score_max : 0.0;
        $has_valid_score_max   = $normalized_score_max > 0;
        $badge_override_source = array_key_exists( '_jlg_rating_badge_override', $preview_meta )
            ? $preview_meta['_jlg_rating_badge_override']
            : get_post_meta( $post_id, '_jlg_rating_badge_override', true );
        $badge_override        = $this->normalize_badge_override( $badge_override_source );

        foreach ( $definitions as $definition ) {
            $meta_key    = isset( $definition['meta_key'] ) ? (string) $definition['meta_key'] : '';
            $category_id = isset( $definition['id'] ) ? (string) $definition['id'] : '';

            if ( $meta_key === '' || $category_id === '' ) {
                continue;
            }

            $raw_value = array_key_exists( $meta_key, $preview_meta )
                ? $preview_meta[ $meta_key ]
                : Helpers::resolve_category_meta_value( $post_id, $definition, true );

            if ( $raw_value === null || $raw_value === '' || ! is_numeric( $raw_value ) ) {
                continue;
            }

            $score_value = round( (float) $raw_value, 1 );

            if ( $score_value < 0 ) {
                $score_value = 0.0;
            }

            if ( $has_valid_score_max ) {
                $score_value = min( $score_value, $normalized_score_max );
            }

            $weight = isset( $definition['weight'] )
                ? Helpers::normalize_category_weight( $definition['weight'], 1.0 )
                : 1.0;

            $score_map[ $category_id ] = array(
                'score'  => $score_value,
                'weight' => $weight,
            );

            $category_scores[] = array(
                'id'       => $category_id,
                'label'    => isset( $definition['label'] ) ? (string) $definition['label'] : $category_id,
                'score'    => $score_value,
                'weight'   => $weight,
                'meta_key' => $meta_key,
            );

            if ( $has_valid_score_max ) {
                $category_percentages[ $category_id ] = max(
                    0,
                    min( 100, ( $score_value / $normalized_score_max ) * 100 )
                );
            }

            if ( $weight > 0 ) {
                $weighted_sum += $score_value * $weight;
                $weight_total += $weight;
            }
        }

        $average_score = $weight_total > 0 ? round( $weighted_sum / $weight_total, 1 ) : null;

        return array(
            'category_scores'      => $category_scores,
            'score_map'            => $score_map,
            'category_percentages' => $category_percentages,
            'average_score'        => $average_score,
            'badge_override'       => $badge_override,
        );
    }

    private function normalize_badge_override( $value ) {
        if ( is_string( $value ) ) {
            $value = strtolower( trim( $value ) );
        }

        $allowed = array( 'auto', 'force-on', 'force-off' );

        if ( in_array( $value, $allowed, true ) ) {
            return $value;
        }

        return 'auto';
    }

    private function build_editor_placeholder_context( WP_Post $post, $post_id, array $options, $score_max, $display_mode ) {
        $resolved_score_max = is_numeric( $score_max ) && $score_max > 0
            ? (float) $score_max
            : (float) Helpers::get_score_max();

        if ( $resolved_score_max <= 0 ) {
            $resolved_score_max = 10.0;
        }

        $placeholder_score      = round( $resolved_score_max / 2, 1 );
        $placeholder_percentage = max( 0, min( 100, ( $placeholder_score / $resolved_score_max ) * 100 ) );

        $category_scores      = array();
        $category_percentages = array();
        $score_map            = array();

        foreach ( Helpers::get_rating_category_definitions() as $definition ) {
            $category_id = isset( $definition['id'] ) ? (string) $definition['id'] : '';
            $label       = isset( $definition['label'] ) ? (string) $definition['label'] : '';
            $weight      = isset( $definition['weight'] )
                ? Helpers::normalize_category_weight( $definition['weight'], 1.0 )
                : 1.0;

            $category_scores[] = array(
                'id'    => $category_id,
                'label' => $label,
                'score' => $placeholder_score,
                'weight'=> $weight,
            );

            if ( $category_id !== '' ) {
                $category_percentages[ $category_id ] = $placeholder_percentage;
                $score_map[ $category_id ]            = array(
                    'score'  => $placeholder_score,
                    'weight' => $weight,
                );
            }
        }

        $resolved_display_mode = in_array( $display_mode, array( 'absolute', 'percent' ), true )
            ? $display_mode
            : 'absolute';

        $resolved_layout = in_array( $options['score_layout'] ?? '', array( 'text', 'circle' ), true )
            ? $options['score_layout']
            : 'text';

        return array(
            'post'                     => $post,
            'post_id'                  => $post_id,
            'options'                  => $options,
            'average_score'            => $placeholder_score,
            'average_score_percentage' => $placeholder_percentage,
            'scores'                   => $score_map,
            'category_scores'          => $category_scores,
            'category_percentages'     => $category_percentages,
            'score_layout'             => $resolved_layout,
            'display_mode'             => $resolved_display_mode,
            'animations_enabled'       => false,
            'css_variables'            => '',
            'score_max'                => $resolved_score_max,
            'should_show_rating_badge' => true,
            'user_rating_average'      => $placeholder_score,
            'user_rating_delta'        => 0.0,
            'rating_badge_threshold'   => isset( $options['rating_badge_threshold'] )
                ? (float) $options['rating_badge_threshold']
                : 0.0,
            'is_placeholder'           => true,
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

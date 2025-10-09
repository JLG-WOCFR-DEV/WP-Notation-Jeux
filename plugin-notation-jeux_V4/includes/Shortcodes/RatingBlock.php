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
                'post_id'            => get_the_ID(),
                'score_layout'       => '',
                'animations'         => '',
                'accent_color'       => '',
                'display_mode'       => '',
                'preview_theme'      => '',
                'preview_animations' => '',
                'show_verdict'       => '',
                'verdict_summary'    => '',
                'verdict_cta_label'  => '',
                'verdict_cta_url'    => '',
                'visual_preset'      => '',
                'test_platforms'     => '',
                'test_build'         => '',
                'validation_status'  => '',
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

        $preview_theme      = $this->normalize_preview_theme( $atts['preview_theme'] );
        $preview_animations = $this->normalize_preview_animations( $atts['preview_animations'] );
        $extra_classes      = array();

        $context_payload = $this->build_test_context_payload(
            $atts['test_platforms'],
            $atts['test_build'],
            $atts['validation_status']
        );

        $visual_preset = isset( $options['visual_preset'] ) ? sanitize_key( (string) $options['visual_preset'] ) : 'signature';
        if ( ! in_array( $visual_preset, array( 'signature', 'minimal', 'editorial' ), true ) ) {
            $visual_preset = 'signature';
        }

        $visual_preset_override = is_string( $atts['visual_preset'] ) ? sanitize_key( $atts['visual_preset'] ) : '';
        if ( in_array( $visual_preset_override, array( 'signature', 'minimal', 'editorial' ), true ) ) {
            $visual_preset            = $visual_preset_override;
            $options['visual_preset'] = $visual_preset;
        }

        $extra_classes[] = 'review-box-jlg--preset-' . $visual_preset;

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

        if ( $preview_theme !== '' ) {
            $extra_classes[] = 'review-box-jlg--preview-theme-' . $preview_theme;
        }

        $theme_variables = array();
        if ( $preview_theme !== '' ) {
            $theme_variables = $this->build_preview_theme_variables( $options, $defaults, $preview_theme );
        }

        $display_mode = is_string( $atts['display_mode'] ) ? sanitize_key( $atts['display_mode'] ) : '';
        if ( ! in_array( $display_mode, array( 'absolute', 'percent' ), true ) ) {
            $display_mode = 'absolute';
        }

        $score_max = Helpers::get_score_max( $options );

        // Sécurité : ne s'exécute que si des notes existent
        $average_score = Helpers::get_average_score_for_post( $post_id );

        $raw_user_rating     = get_post_meta( $post_id, '_jlg_user_rating_avg', true );
        $user_rating_average = null;

        if ( is_string( $raw_user_rating ) ) {
            $raw_user_rating = trim( $raw_user_rating );
        }

        if ( is_numeric( $raw_user_rating ) ) {
            $user_rating_average = (float) $raw_user_rating;
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

        $resolved_score_layout = in_array( $options['score_layout'] ?? '', array( 'text', 'circle' ), true )
            ? $options['score_layout']
            : 'text';

        $animations_enabled = ! empty( $options['enable_animations'] );

        if ( $preview_animations === 'enabled' ) {
            $animations_enabled = true;
        } elseif ( $preview_animations === 'disabled' ) {
            $animations_enabled = false;
        }

        if ( $preview_animations === 'enabled' ) {
            $extra_classes[] = 'review-box-jlg--preview-animations-on';
        } elseif ( $preview_animations === 'disabled' ) {
            $extra_classes[] = 'review-box-jlg--preview-animations-off';
        }

        $css_variables = $this->build_css_variables( $options );

        if ( ! empty( $theme_variables ) ) {
            $css_variables = $this->merge_css_variables( $css_variables, $theme_variables );
        }

        $extra_classes        = array_values( array_unique( array_filter( $extra_classes ) ) );
        $extra_classes_string = implode( ' ', $extra_classes );

        if ( $average_score === null ) {
            if ( $this->is_editor_preview_context() ) {
                $shortcode_handle    = $shortcode_tag ?: 'bloc_notation_jeu';
                $placeholder_context = $this->build_editor_placeholder_context(
                    $post,
                    $post_id,
                    $options,
                    $score_max,
                    $display_mode,
                    $extra_classes_string,
                    $animations_enabled,
                    $theme_variables,
                    $context_payload['has_values']
                        ? $context_payload
                        : $this->build_placeholder_test_context_payload()
                );

                Frontend::mark_shortcode_rendered( $shortcode_handle );

                return Frontend::get_template_html(
                    'shortcode-rating-block-empty',
                    $placeholder_context
                );
            }

            return '';
        }

        $badge_threshold = isset( $options['rating_badge_threshold'] ) && is_numeric( $options['rating_badge_threshold'] )
            ? (float) $options['rating_badge_threshold']
            : (float) ( $defaults['rating_badge_threshold'] ?? 0 );

        $should_show_badge = ! empty( $options['rating_badge_enabled'] )
            && is_numeric( $average_score )
            && $average_score >= $badge_threshold;

        $user_rating_delta = null;
        if ( $user_rating_average !== null && is_numeric( $average_score ) ) {
            $user_rating_delta = $user_rating_average - (float) $average_score;
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

        $review_status_enabled  = ! empty( $options['review_status_enabled'] );
        $review_status          = Helpers::get_review_status_for_post( $post_id );
        $related_guides_enabled = ! empty( $options['related_guides_enabled'] );
        $related_guides         = $related_guides_enabled
            ? Helpers::get_related_guides_for_post( $post_id, $options )
            : array();

        $verdict_overrides = array( 'context' => 'rating-block' );

        if ( is_string( $atts['verdict_summary'] ) && $atts['verdict_summary'] !== '' ) {
            $verdict_overrides['summary'] = sanitize_text_field( $atts['verdict_summary'] );
        }

        if ( is_string( $atts['verdict_cta_label'] ) && $atts['verdict_cta_label'] !== '' ) {
            $verdict_overrides['cta_label'] = sanitize_text_field( $atts['verdict_cta_label'] );
        }

        if ( is_string( $atts['verdict_cta_url'] ) && $atts['verdict_cta_url'] !== '' ) {
            $verdict_overrides['cta_url'] = esc_url_raw( $atts['verdict_cta_url'] );
        }

        $verdict_data             = Helpers::get_verdict_data_for_post( $post_id, $options, $verdict_overrides );
        $verdict_enabled_override = $this->normalize_bool_attribute( $atts['show_verdict'] );

        if ( $verdict_enabled_override !== null ) {
            $verdict_data['enabled'] = (bool) $verdict_enabled_override;
        }

        Frontend::mark_shortcode_rendered( $shortcode_tag ?: 'bloc_notation_jeu' );

        return Frontend::get_template_html(
            'shortcode-rating-block',
            array(
                'post_id'                  => $post_id,
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
                'extra_classes'            => $extra_classes_string,
                'review_status_enabled'    => $review_status_enabled,
                'review_status'            => $review_status,
                'related_guides_enabled'   => $related_guides_enabled,
                'related_guides'           => $related_guides,
                'verdict'                  => $verdict_data,
                'test_context'             => $context_payload,
            )
        );
    }

    private function build_editor_placeholder_context( WP_Post $post, $post_id, array $options, $score_max, $display_mode, $extra_classes = '', $animations_enabled = false, array $theme_variables = array(), array $context_payload = array() ) {
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
                'id'     => $category_id,
                'label'  => $label,
                'score'  => $placeholder_score,
                'weight' => $weight,
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

        $review_status_enabled  = ! empty( $options['review_status_enabled'] );
        $related_guides_enabled = ! empty( $options['related_guides_enabled'] );
        $review_status          = Helpers::get_review_status_for_post( $post_id );
        $placeholder_guides     = array();

        if ( $related_guides_enabled ) {
            $placeholder_guides = array(
                array(
                    'id'    => 0,
                    'title' => __( 'Guide stratégique', 'notation-jlg' ),
                    'url'   => '',
                ),
                array(
                    'id'    => 0,
                    'title' => __( 'Astuces progression', 'notation-jlg' ),
                    'url'   => '',
                ),
                array(
                    'id'    => 0,
                    'title' => __( 'Build recommandé', 'notation-jlg' ),
                    'url'   => '',
                ),
            );
        }

        $current_timestamp   = time();
        $placeholder_verdict = array(
            'enabled'       => ! empty( $options['verdict_module_enabled'] ),
            'summary'       => __( 'Ajoutez un verdict court et percutant pour guider vos lecteurs vers la critique complète.', 'notation-jlg' ),
            'summary_limit' => 160,
            'cta'           => array(
                'label'     => __( 'Lire le test complet', 'notation-jlg' ),
                'url'       => '#',
                'rel'       => '',
                'available' => true,
            ),
            'status'        => $review_status,
            'updated'       => array(
                'timestamp' => $current_timestamp,
                'display'   => date_i18n( get_option( 'date_format', 'F j, Y' ), $current_timestamp ),
                'datetime'  => gmdate( 'c', $current_timestamp ),
                'title'     => date_i18n( get_option( 'date_format', 'F j, Y' ) . ' ' . get_option( 'time_format', 'H:i' ), $current_timestamp ),
            ),
            'permalink'     => '',
        );

        $resolved_context = ! empty( $context_payload )
            ? $context_payload
            : $this->build_placeholder_test_context_payload();

        if ( empty( $resolved_context['has_values'] ) ) {
            $resolved_context = $this->build_placeholder_test_context_payload();
        }

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
            'animations_enabled'       => (bool) $animations_enabled,
            'css_variables'            => $this->merge_css_variables( '', $theme_variables ),
            'score_max'                => $resolved_score_max,
            'should_show_rating_badge' => true,
            'user_rating_average'      => $placeholder_score,
            'user_rating_delta'        => 0.0,
            'rating_badge_threshold'   => isset( $options['rating_badge_threshold'] )
                ? (float) $options['rating_badge_threshold']
                : 0.0,
            'is_placeholder'           => true,
            'extra_classes'            => is_string( $extra_classes ) ? $extra_classes : '',
            'review_status_enabled'    => $review_status_enabled,
            'review_status'            => $review_status,
            'related_guides_enabled'   => $related_guides_enabled,
            'related_guides'           => $placeholder_guides,
            'verdict'                  => $placeholder_verdict,
            'test_context'             => $resolved_context,
        );
    }

    private function build_test_context_payload( $platforms, $build, $status, $is_placeholder = false ) {
        $platforms_value = $this->sanitize_context_value( $platforms );
        $build_value     = $this->sanitize_context_value( $build );
        $normalized      = $this->normalize_validation_status( $status );
        $catalog         = $this->get_validation_status_catalog();
        $status_data     = isset( $catalog[ $normalized ] ) ? $catalog[ $normalized ] : $catalog['none'];

        return array(
            'platforms'          => $platforms_value,
            'build'              => $build_value,
            'status_key'         => $normalized,
            'status_label'       => $status_data['label'],
            'status_description' => $status_data['description'],
            'status_tone'        => $status_data['tone'],
            'has_values'         => $platforms_value !== '' || $build_value !== '' || 'none' !== $normalized,
            'show_status'        => 'none' !== $normalized,
            'is_placeholder'     => (bool) $is_placeholder,
        );
    }

    private function build_placeholder_test_context_payload() {
        return $this->build_test_context_payload(
            __( 'PC (RTX 4080) & PS5', 'notation-jlg' ),
            __( 'Version presse 1.0.2 – Day One', 'notation-jlg' ),
            'in_review',
            true
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

    private function sanitize_context_value( $value ) {
        if ( is_string( $value ) ) {
            $stripped = trim( wp_strip_all_tags( $value ) );
            if ( $stripped === '' ) {
                return '';
            }

            $normalized_breaks = preg_replace( '/[\r\n]+/', ', ', $stripped );
            if ( is_string( $normalized_breaks ) ) {
                $stripped = $normalized_breaks;
            }

            $single_space = preg_replace( '/\s{2,}/', ' ', $stripped );
            if ( is_string( $single_space ) ) {
                $stripped = $single_space;
            }

            return trim( $stripped );
        }

        if ( is_array( $value ) ) {
            $normalized = array_filter( array_map( array( $this, 'sanitize_context_value' ), $value ) );

            return implode( ', ', $normalized );
        }

        return '';
    }

    private function normalize_validation_status( $value ) {
        if ( is_string( $value ) ) {
            $key = sanitize_key( $value );
            if ( $key !== '' && array_key_exists( $key, $this->get_validation_status_catalog() ) ) {
                return $key;
            }
        }

        return 'none';
    }

    private function get_validation_status_catalog() {
        return array(
            'none'         => array(
                'label'       => '',
                'description' => '',
                'tone'        => 'neutral',
            ),
            'in_review'    => array(
                'label'       => __( 'En cours de validation', 'notation-jlg' ),
                'description' => __( 'La rédaction finalise ses vérifications : la note pourra évoluer une fois la version finale confirmée.', 'notation-jlg' ),
                'tone'        => 'warning',
            ),
            'needs_retest' => array(
                'label'       => __( 'Re-test planifié', 'notation-jlg' ),
                'description' => __( 'Un nouveau passage est prévu sur une build corrective ; considérez ce verdict comme provisoire.', 'notation-jlg' ),
                'tone'        => 'alert',
            ),
            'validated'    => array(
                'label'       => __( 'Validé par la rédaction', 'notation-jlg' ),
                'description' => __( 'Les conclusions ont été revérifiées sur la version finale accessible au public.', 'notation-jlg' ),
                'tone'        => 'success',
            ),
        );
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

    private function normalize_preview_theme( $value ) {
        if ( ! is_string( $value ) ) {
            return '';
        }

        $normalized = sanitize_key( $value );

        if ( in_array( $normalized, array( 'dark', 'light' ), true ) ) {
            return $normalized;
        }

        return '';
    }

    private function normalize_preview_animations( $value ) {
        if ( ! is_string( $value ) ) {
            return 'inherit';
        }

        $normalized = sanitize_key( $value );

        if ( in_array( $normalized, array( 'inherit', 'enabled', 'disabled' ), true ) ) {
            return $normalized;
        }

        return 'inherit';
    }

    private function merge_css_variables( $existing, array $variables ) {
        $rules = array();

        foreach ( $variables as $name => $value ) {
            if ( ! is_string( $name ) || $name === '' ) {
                continue;
            }

            if ( ! is_string( $value ) || $value === '' ) {
                continue;
            }

            $rules[] = $name . ':' . $value;
        }

        if ( empty( $rules ) ) {
            return is_string( $existing ) ? $existing : '';
        }

        $existing_string = is_string( $existing ) ? trim( $existing ) : '';
        $rules_string    = implode( ';', $rules );

        if ( $existing_string === '' ) {
            return $rules_string;
        }

        if ( substr( $existing_string, -1 ) !== ';' ) {
            $existing_string .= ';';
        }

        return $existing_string . $rules_string;
    }

    private function build_preview_theme_variables( array $options, array $defaults, $theme ) {
        $variables = array();

        if ( $theme === 'light' ) {
            $variables['--jlg-bg-color']             = $this->sanitize_css_variable_value( $options['light_bg_color'] ?? ( $defaults['light_bg_color'] ?? '#ffffff' ) );
            $variables['--jlg-bg-color-secondary']   = $this->sanitize_css_variable_value( $options['light_bg_color_secondary'] ?? ( $defaults['light_bg_color_secondary'] ?? '#f9fafb' ) );
            $variables['--jlg-border-color']         = $this->sanitize_css_variable_value( $options['light_border_color'] ?? ( $defaults['light_border_color'] ?? '#e5e7eb' ) );
            $variables['--jlg-main-text-color']      = $this->sanitize_css_variable_value( $options['light_text_color'] ?? ( $defaults['light_text_color'] ?? '#111827' ) );
            $variables['--jlg-secondary-text-color'] = $this->sanitize_css_variable_value( $options['light_text_color_secondary'] ?? ( $defaults['light_text_color_secondary'] ?? '#6b7280' ) );
        } elseif ( $theme === 'dark' ) {
            $variables['--jlg-bg-color']             = $this->sanitize_css_variable_value( $options['dark_bg_color'] ?? ( $defaults['dark_bg_color'] ?? '#18181b' ) );
            $variables['--jlg-bg-color-secondary']   = $this->sanitize_css_variable_value( $options['dark_bg_color_secondary'] ?? ( $defaults['dark_bg_color_secondary'] ?? '#27272a' ) );
            $variables['--jlg-border-color']         = $this->sanitize_css_variable_value( $options['dark_border_color'] ?? ( $defaults['dark_border_color'] ?? '#3f3f46' ) );
            $variables['--jlg-main-text-color']      = $this->sanitize_css_variable_value( $options['dark_text_color'] ?? ( $defaults['dark_text_color'] ?? '#fafafa' ) );
            $variables['--jlg-secondary-text-color'] = $this->sanitize_css_variable_value( $options['dark_text_color_secondary'] ?? ( $defaults['dark_text_color_secondary'] ?? '#a1a1aa' ) );
        }

        if ( isset( $variables['--jlg-bg-color-secondary'] ) ) {
            $variables['--jlg-bar-bg-color'] = $variables['--jlg-bg-color-secondary'];
        }

        return $variables;
    }

    private function sanitize_css_variable_value( $value ) {
        if ( ! is_string( $value ) ) {
            return '';
        }

        $sanitized = sanitize_text_field( $value );

        return trim( $sanitized );
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

<?php

namespace JLG\Notation\Shortcodes;

use JLG\Notation\Frontend;
use JLG\Notation\Utils\Validator;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ExpressRating {

    public const SHORTCODE = 'jlg_notation_express';

    public function __construct() {
        add_shortcode( self::SHORTCODE, array( $this, 'render' ) );
    }

    public function render( $atts = array(), $content = '', $shortcode_tag = '' ) {
        $atts = shortcode_atts(
            array(
                'score'       => '',
                'score_max'   => '',
                'show_badge'  => 'non',
                'badge_label' => '',
                'cta_label'   => '',
                'cta_url'     => '',
                'cta_rel'     => '',
                'cta_new_tab' => 'non',
                'class'       => '',
            ),
            $atts,
            $shortcode_tag ?: self::SHORTCODE
        );

        $score_max = $this->normalize_score_value( $atts['score_max'] );
        if ( null === $score_max || $score_max <= 0 ) {
            $score_max = 10.0;
        }

        $score_value = $this->normalize_score_value( $atts['score'] );
        if ( null !== $score_value ) {
            if ( $score_value < 0 ) {
                $score_value = 0.0;
            }

            if ( $score_value > $score_max ) {
                $score_value = $score_max;
            }
        }

        $score_display     = $this->format_display_value( $score_value );
        $score_max_display = $this->format_display_value( $score_max );
        $score_ratio       = $score_value !== null && $score_max > 0 ? max( 0, min( 1, $score_value / $score_max ) ) : 0.0;
        $has_score         = $score_value !== null;
        $badge_label       = $this->sanitize_label( $atts['badge_label'] );
        $badge_enabled     = $this->normalize_boolean( $atts['show_badge'] ) && $badge_label !== '';
        $cta_label         = $this->sanitize_label( $atts['cta_label'] );
        $cta_new_tab       = $this->normalize_boolean( $atts['cta_new_tab'] );
        $cta_url           = $this->sanitize_url( $atts['cta_url'] );
        $cta_rel           = $this->normalize_rel_attribute( $atts['cta_rel'], $cta_new_tab );
        $cta_visible       = $cta_label !== '' && $cta_url !== '';
        $extra_classes     = $this->sanitize_class_attribute( $atts['class'] );
        $aria_label        = '';

        if ( $has_score ) {
            $aria_label = sprintf(
                /* translators: 1: game score value. 2: score maximum. */
                __( 'Note express : %1$s sur %2$s', 'notation-jlg' ),
                $score_display,
                $score_max_display
            );
        }

        Frontend::mark_shortcode_rendered( $shortcode_tag ?: self::SHORTCODE );

        return Frontend::get_template_html(
            'shortcode-rating-express',
            array(
                'score'          => array(
                    'has_score'   => $has_score,
                    'value'       => $score_value,
                    'display'     => $score_display,
                    'max'         => $score_max,
                    'max_display' => $score_max_display,
                    'ratio'       => $score_ratio,
                    'aria_label'  => $aria_label,
                ),
                'badge'          => array(
                    'visible' => $badge_enabled,
                    'label'   => $badge_label,
                ),
                'cta'            => array(
                    'visible' => $cta_visible,
                    'label'   => $cta_label,
                    'url'     => $cta_url,
                    'rel'     => $cta_rel,
                    'target'  => $cta_new_tab ? '_blank' : '',
                ),
                'extra_classes'  => $extra_classes,
                'is_placeholder' => ! $has_score,
            )
        );
    }

    private function normalize_score_value( $value ) {
        if ( $value === null || $value === '' ) {
            return null;
        }

        if ( is_string( $value ) ) {
            $value = str_replace( ',', '.', $value );
        }

        if ( ! is_numeric( $value ) ) {
            return null;
        }

        return (float) $value;
    }

    private function format_display_value( $value ) {
        if ( $value === null ) {
            return '';
        }

        $decimals = abs( $value - round( $value ) ) < 0.05 ? 0 : 1;

        if ( function_exists( 'number_format_i18n' ) ) {
            return number_format_i18n( $value, $decimals );
        }

        return number_format( $value, $decimals, ',', ' ' );
    }

    private function sanitize_label( $value ) {
        if ( ! is_string( $value ) ) {
            return '';
        }

        $sanitized = sanitize_text_field( $value );

        return $sanitized !== '' ? wp_strip_all_tags( $sanitized ) : '';
    }

    private function normalize_boolean( $value ) {
        if ( is_bool( $value ) ) {
            return $value;
        }

        if ( is_numeric( $value ) ) {
            return (bool) $value;
        }

        if ( is_string( $value ) ) {
            $value = strtolower( trim( $value ) );

            return in_array( $value, array( '1', 'true', 'yes', 'oui', 'on' ), true );
        }

        return false;
    }

    private function sanitize_url( $value ) {
        if ( ! is_string( $value ) ) {
            return '';
        }

        $url = esc_url_raw( $value );

        if ( $url === '' ) {
            return '';
        }

        if ( strpos( $url, '<' ) !== false || strpos( $url, '>' ) !== false ) {
            return '';
        }

        return Validator::is_valid_http_url( $url ) ? $url : '';
    }

    private function normalize_rel_attribute( $value, $new_tab ) {
        $tokens = array();

        if ( is_string( $value ) ) {
            $tokens = preg_split( '/\s+/', strtolower( sanitize_text_field( $value ) ) );
            $tokens = array_filter( array_map( 'trim', (array) $tokens ) );
        }

        if ( $new_tab ) {
            $tokens[] = 'noopener';
            $tokens[] = 'noreferrer';
        }

        if ( empty( $tokens ) ) {
            return '';
        }

        $tokens = array_unique( $tokens );

        return implode( ' ', $tokens );
    }

    private function sanitize_class_attribute( $value ) {
        if ( ! is_string( $value ) || $value === '' ) {
            return '';
        }

        $tokens = preg_split( '/\s+/', $value );
        $tokens = array_filter(
            array_map(
                static function ( $token ) {
                    $token = trim( (string) $token );

                    return $token !== '' ? sanitize_html_class( $token ) : '';
                },
                (array) $tokens
            )
        );

        if ( empty( $tokens ) ) {
            return '';
        }

        return implode( ' ', array_unique( $tokens ) );
    }
}

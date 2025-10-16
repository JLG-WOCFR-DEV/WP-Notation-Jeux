<?php
/**
 * Shortcode "jlg_score_insights" – synthèse statistique des notes.
 *
 * @package JLG_Notation
 * @version 5.1
 */

namespace JLG\Notation\Shortcodes;

use DateTimeImmutable;
use DateTimeZone;
use JLG\Notation\Frontend;
use JLG\Notation\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ScoreInsights {

    private const SHORTCODE              = 'jlg_score_insights';
    private const DEFAULT_PLATFORM_LIMIT = 5;
    private const UNKNOWN_PLATFORM_SLUG  = 'sans-plateforme';

    public function __construct() {
        add_shortcode( self::SHORTCODE, array( $this, 'render' ) );
    }

    public static function get_default_atts() {
        return array(
            'title'          => '',
            'time_range'     => 'all',
            'platform'       => '',
            'platform_limit' => self::DEFAULT_PLATFORM_LIMIT,
        );
    }

    public function render( $atts, $content = '', $shortcode_tag = '' ) {
        unset( $content );

        $shortcode = $shortcode_tag ?: self::SHORTCODE;
        $atts      = shortcode_atts( self::get_default_atts(), $atts, $shortcode );

        $time_range     = $this->sanitize_time_range( $atts['time_range'] ?? '' );
        $platform_slug  = $this->sanitize_platform_slug( $atts['platform'] ?? '' );
        $platform_limit = $this->sanitize_platform_limit( $atts['platform_limit'] ?? self::DEFAULT_PLATFORM_LIMIT );

        $post_context = $this->resolve_post_ids_with_history( $time_range, $platform_slug );
        $post_ids     = $post_context['current'];
        $insights     = Helpers::get_posts_score_insights( $post_ids, $time_range, $platform_slug );

        if ( isset( $insights['platform_rankings'] ) && is_array( $insights['platform_rankings'] ) ) {
            $insights['platform_rankings'] = array_slice( $insights['platform_rankings'], 0, $platform_limit );
        }

        $time_ranges = $this->get_available_time_ranges();
        $platforms   = Helpers::get_registered_platform_labels();

        $trend = $this->build_trend_summary( $insights, $post_context, $time_range, $time_ranges, $platform_slug );

        $platform_label = '';
        if ( $platform_slug !== '' ) {
            if ( isset( $platforms[ $platform_slug ] ) ) {
                $platform_label = $platforms[ $platform_slug ];
            } elseif ( $platform_slug === self::UNKNOWN_PLATFORM_SLUG ) {
                $platform_label = _x( 'Sans plateforme', 'Fallback platform label', 'notation-jlg' );
            } else {
                $platform_label = $this->humanize_slug( $platform_slug );
            }
        }

        $context = array(
            'atts'                  => $atts,
            'insights'              => $insights,
            'time_range'            => $time_range,
            'time_range_label'      => $time_ranges[ $time_range ]['label'] ?? '',
            'available_time_ranges' => $time_ranges,
            'platform_slug'         => $platform_slug,
            'platform_label'        => $platform_label,
            'platform_limit'        => $platform_limit,
            'post_ids'              => $post_ids,
            'trend'                 => $trend,
        );

        Frontend::mark_shortcode_rendered( $shortcode );

        return Frontend::get_template_html( 'shortcode-score-insights', $context );
    }

    private function sanitize_time_range( $value ) {
        $value      = is_string( $value ) ? $value : '';
        $value      = trim( strtolower( $value ) );
        $time_range = $value !== '' ? sanitize_key( $value ) : 'all';

        $ranges = $this->get_available_time_ranges();
        if ( ! isset( $ranges[ $time_range ] ) ) {
            return 'all';
        }

        return $time_range;
    }

    private function sanitize_platform_slug( $value ) {
        if ( is_string( $value ) ) {
            $value = trim( $value );
        } else {
            $value = '';
        }

        if ( $value === '' ) {
            return '';
        }

        $slug = sanitize_title( $value );
        if ( $slug === '' || $slug === 'all' ) {
            return '';
        }

        return $slug;
    }

    private function sanitize_platform_limit( $value ) {
        if ( is_numeric( $value ) ) {
            $value = (int) $value;
        } else {
            $value = self::DEFAULT_PLATFORM_LIMIT;
        }

        if ( $value < 1 ) {
            $value = 1;
        }

        if ( $value > 10 ) {
            $value = 10;
        }

        return $value;
    }

    private function get_available_time_ranges() {
        $ranges = array(
            'all'           => array(
                'label' => _x( 'Depuis toujours', 'Score insights time range', 'notation-jlg' ),
                'since' => null,
            ),
            'last_30_days'  => array(
                'label' => _x( '30 derniers jours', 'Score insights time range', 'notation-jlg' ),
                'since' => '-30 days',
            ),
            'last_90_days'  => array(
                'label' => _x( '90 derniers jours', 'Score insights time range', 'notation-jlg' ),
                'since' => '-90 days',
            ),
            'last_365_days' => array(
                'label' => _x( '12 derniers mois', 'Score insights time range', 'notation-jlg' ),
                'since' => '-365 days',
            ),
        );

        /**
         * Permet d'ajouter ou de modifier les plages temporelles proposées.
         *
         * @param array $ranges Liste des plages disponibles.
         */
        $ranges = apply_filters( 'jlg_score_insights_time_ranges', $ranges );

        if ( ! is_array( $ranges ) ) {
            return array(
                'all' => array(
                    'label' => _x( 'Depuis toujours', 'Score insights time range', 'notation-jlg' ),
                    'since' => null,
                ),
            );
        }

        // Assurer un label et une clé valide pour chaque entrée.
        foreach ( $ranges as $key => $range ) {
            if ( ! is_array( $range ) ) {
                unset( $ranges[ $key ] );
                continue;
            }

            if ( empty( $range['label'] ) || ! is_string( $range['label'] ) ) {
                $ranges[ $key ]['label'] = ucfirst( str_replace( '_', ' ', sanitize_key( $key ) ) );
            }

            if ( ! array_key_exists( 'since', $range ) ) {
                $ranges[ $key ]['since'] = null;
            }
        }

        return $ranges;
    }

    private function resolve_post_ids( $time_range, $platform_slug ) {
        $context = $this->resolve_post_ids_with_history( $time_range, $platform_slug );

        return $context['current'];
    }

    private function get_threshold_timestamp( $relative_time ) {
        $bounds = $this->get_time_range_bounds( $relative_time );

        if ( $bounds === null ) {
            return null;
        }

        return $bounds['start'];
    }

    private function get_post_timestamp( $post_id ) {
        $post_date_gmt = get_post_field( 'post_date_gmt', $post_id );
        if ( is_string( $post_date_gmt ) && $post_date_gmt !== '' && $post_date_gmt !== '0000-00-00 00:00:00' ) {
            $timestamp = strtotime( $post_date_gmt . ' GMT' );
            if ( $timestamp !== false ) {
                return (int) $timestamp;
            }
        }

        $post_date = get_post_field( 'post_date', $post_id );
        if ( is_string( $post_date ) && $post_date !== '' && $post_date !== '0000-00-00 00:00:00' ) {
            $timestamp = strtotime( $post_date );
            if ( $timestamp !== false ) {
                return (int) $timestamp;
            }
        }

        return null;
    }

    private function post_matches_platform( $post_id, $platform_slug ) {
        $slugs = $this->get_post_platform_slugs( $post_id );

        if ( empty( $slugs ) ) {
            return $platform_slug === self::UNKNOWN_PLATFORM_SLUG;
        }

        return in_array( $platform_slug, $slugs, true );
    }

    private function get_post_platform_slugs( $post_id ) {
        $meta   = get_post_meta( $post_id, '_jlg_plateformes', true );
        $labels = array();

        if ( is_array( $meta ) ) {
            foreach ( $meta as $value ) {
                if ( ! is_string( $value ) ) {
                    continue;
                }

                $label = sanitize_text_field( $value );
                if ( $label === '' ) {
                    continue;
                }

                $labels[] = $label;
            }
        } elseif ( is_string( $meta ) && $meta !== '' ) {
            $pieces = array_map( 'trim', explode( ',', $meta ) );
            foreach ( $pieces as $piece ) {
                if ( $piece === '' ) {
                    continue;
                }

                $labels[] = sanitize_text_field( $piece );
            }
        }

        if ( empty( $labels ) ) {
            return array();
        }

        $slugs = array();

        foreach ( $labels as $label ) {
            $slug = sanitize_title( $label );
            if ( $slug === '' ) {
                $slugs[] = self::UNKNOWN_PLATFORM_SLUG;
                continue;
            }

            $slugs[] = $slug;
        }

        return array_values( array_unique( $slugs ) );
    }

    private function humanize_slug( $slug ) {
        $slug = str_replace( array( '-', '_' ), ' ', (string) $slug );
        $slug = trim( $slug );

        if ( $slug === '' ) {
            return '';
        }

        if ( function_exists( 'mb_convert_case' ) ) {
            return mb_convert_case( $slug, MB_CASE_TITLE, 'UTF-8' );
        }

        return ucwords( $slug );
    }

    private function resolve_post_ids_with_history( $time_range, $platform_slug ) {
        $post_ids = Helpers::get_rated_post_ids();

        if ( empty( $post_ids ) || ! is_array( $post_ids ) ) {
            return array(
                'current'  => array(),
                'previous' => array(),
                'bounds'   => null,
            );
        }

        $post_ids = array_values(
            array_filter(
                array_map( 'absint', $post_ids ),
                static function ( $post_id ) {
                    return $post_id > 0;
                }
            )
        );

        $ranges           = $this->get_available_time_ranges();
        $range_definition = $ranges[ $time_range ] ?? array();
        $since_clause     = isset( $range_definition['since'] ) ? $range_definition['since'] : null;
        $bounds           = $this->get_time_range_bounds( $since_clause );

        $current  = array();
        $previous = array();

        $duration = null;
        if ( $bounds !== null ) {
            $duration = max( 0, $bounds['end'] - $bounds['start'] );
        }

        foreach ( $post_ids as $post_id ) {
            if ( $platform_slug !== '' && ! $this->post_matches_platform( $post_id, $platform_slug ) ) {
                continue;
            }

            if ( $bounds === null ) {
                $current[] = $post_id;
                continue;
            }

            $timestamp = $this->get_post_timestamp( $post_id );
            if ( $timestamp === null ) {
                continue;
            }

            if ( $timestamp >= $bounds['start'] && $timestamp <= $bounds['end'] ) {
                $current[] = $post_id;
                continue;
            }

            if ( $duration === null || $duration === 0 ) {
                continue;
            }

            $previous_start = $bounds['start'] - $duration;
            if ( $timestamp >= $previous_start && $timestamp < $bounds['start'] ) {
                $previous[] = $post_id;
            }
        }

        return array(
            'current'  => $current,
            'previous' => $previous,
            'bounds'   => $bounds,
        );
    }

    private function get_time_range_bounds( $relative_time ) {
        $relative_time = is_string( $relative_time ) ? trim( $relative_time ) : '';
        if ( $relative_time === '' ) {
            return null;
        }

        try {
            $timezone = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'UTC' );
        } catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
            $timezone = new DateTimeZone( 'UTC' );
        }

        $now        = new DateTimeImmutable( 'now', $timezone );
        $now_stamp  = $now->getTimestamp();
        $start_time = strtotime( $relative_time, $now_stamp );

        if ( $start_time === false ) {
            return null;
        }

        $start_time = (int) $start_time;

        if ( $start_time > $now_stamp ) {
            return null;
        }

        return array(
            'start' => $start_time,
            'end'   => $now_stamp,
        );
    }

    private function build_trend_summary( $current_insights, $post_context, $time_range, $time_ranges, $platform_slug ) {
        $bounds         = $post_context['bounds'] ?? null;
        $previous_ids   = $post_context['previous'] ?? array();
        $current_mean   = isset( $current_insights['mean']['value'] ) ? $current_insights['mean']['value'] : null;
        $current_total  = isset( $current_insights['total'] ) ? (int) $current_insights['total'] : 0;
        $time_range_key = is_string( $time_range ) ? $time_range : '';

        if ( $bounds === null || empty( $previous_ids ) || ! is_numeric( $current_mean ) || $current_total === 0 ) {
            return array(
                'available' => false,
            );
        }

        $previous_insights = Helpers::get_posts_score_insights( $previous_ids, 'previous:' . $time_range, $platform_slug );

        $previous_mean  = isset( $previous_insights['mean']['value'] ) ? $previous_insights['mean']['value'] : null;
        $previous_total = isset( $previous_insights['total'] ) ? (int) $previous_insights['total'] : 0;

        if ( ! is_numeric( $previous_mean ) || $previous_total === 0 ) {
            return array(
                'available' => false,
            );
        }

        $delta     = $current_mean - $previous_mean;
        $direction = $this->resolve_trend_direction( $delta );

        $comparison_label = sprintf(
            /* translators: %s: Human readable time range label. */
            __( 'Période précédente (%s)', 'notation-jlg' ),
            $time_ranges[ $time_range_key ]['label'] ?? __( 'Période glissante', 'notation-jlg' )
        );

        $previous_mean_formatted = $previous_insights['mean']['formatted'] ?? null;

        return array(
            'available'                => true,
            'comparison_label'         => $comparison_label,
            'previous_total'           => $previous_total,
            'previous_total_formatted' => number_format_i18n( $previous_total ),
            'mean'                     => array(
                'delta'              => round( $delta, 1 ),
                'delta_formatted'    => $this->format_signed_delta( $delta ),
                'direction'          => $direction,
                'direction_label'    => $this->get_trend_direction_label( $direction ),
                'previous_formatted' => $previous_mean_formatted !== null ? (string) $previous_mean_formatted : '',
            ),
        );
    }

    private function resolve_trend_direction( $delta ) {
        if ( ! is_numeric( $delta ) ) {
            return 'stable';
        }

        $rounded = round( (float) $delta, 1 );

        if ( $rounded > 0.0 ) {
            return 'up';
        }

        if ( $rounded < 0.0 ) {
            return 'down';
        }

        return 'stable';
    }

    private function format_signed_delta( $value ) {
        if ( ! is_numeric( $value ) ) {
            return '±' . number_format_i18n( 0, 1 );
        }

        $rounded = round( (float) $value, 1 );

        if ( abs( $rounded ) < 0.05 ) {
            return '±' . number_format_i18n( 0, 1 );
        }

        $formatted = number_format_i18n( abs( $rounded ), 1 );

        if ( $rounded > 0 ) {
            return '+' . $formatted;
        }

        return '-' . $formatted;
    }

    private function get_trend_direction_label( $direction ) {
        switch ( $direction ) {
            case 'up':
                return __( 'Tendance en hausse', 'notation-jlg' );
            case 'down':
                return __( 'Tendance en baisse', 'notation-jlg' );
            default:
                return __( 'Tendance stable', 'notation-jlg' );
        }
    }
}

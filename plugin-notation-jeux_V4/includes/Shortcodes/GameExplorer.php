<?php

namespace JLG\Notation\Shortcodes;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use JLG\Notation\Frontend;
use JLG\Notation\Helpers;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GameExplorer {

    private const SNAPSHOT_TRANSIENT_KEY      = 'jlg_game_explorer_snapshot_v1';
    private const SNAPSHOT_RELEVANT_META_KEYS = array(
        '_jlg_game_title',
        '_jlg_developpeur',
        '_jlg_editeur',
        '_jlg_plateformes',
        '_jlg_date_sortie',
    );

    private const REQUEST_KEYS = array(
        'orderby',
        'order',
        'letter',
        'category',
        'platform',
        'developer',
        'publisher',
        'availability',
        'year',
        'score',
        'search',
        'paged',
    );

    private const INDEX_META_KEYS = array(
        'letter'         => '_jlg_ge_letter',
        'developer'      => '_jlg_ge_developer_key',
        'publisher'      => '_jlg_ge_publisher_key',
        'availability'   => '_jlg_ge_availability',
        'release_year'   => '_jlg_ge_release_year',
        'search_index'   => '_jlg_ge_search_index',
        'platform_index' => '_jlg_ge_platform_index',
    );

    private const QUERY_CACHE_KEY_PREFIX     = 'jlg_ge_query_';
    private const QUERY_CACHE_VERSION_OPTION = 'jlg_ge_query_cache_version';

    /** @var array<string, mixed>|null */
    private static $filters_snapshot = null;
    /** @var int|null */
    private static $query_cache_version = null;

    public static function clear_filters_snapshot() {
        delete_transient( self::SNAPSHOT_TRANSIENT_KEY );
        self::$filters_snapshot = null;
        self::bump_query_cache_version();
    }

    public static function maybe_clear_filters_snapshot_for_meta( $meta_id, $post_id, $meta_key, $meta_value = null ) {
        unset( $meta_id, $meta_value );

        if ( ! is_string( $meta_key ) || $meta_key === '' ) {
            return;
        }

        if ( ! self::is_snapshot_relevant_meta_key( $meta_key ) ) {
            return;
        }

        $post = self::resolve_post( $post_id );
        if ( ! self::should_invalidate_for_post( $post ) ) {
            return;
        }

        self::clear_filters_snapshot();
    }

    public static function maybe_clear_filters_snapshot_for_post( $post_id, $post, $update ) {
        unset( $post_id, $update );

        if ( ! self::should_invalidate_for_post( $post ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( function_exists( 'wp_is_post_autosave' ) && wp_is_post_autosave( $post->ID ?? 0 ) ) {
            return;
        }

        if ( function_exists( 'wp_is_post_revision' ) && wp_is_post_revision( $post->ID ?? 0 ) ) {
            return;
        }

        self::clear_filters_snapshot();
    }

    public static function maybe_clear_filters_snapshot_for_status_change( $new_status, $old_status, $post ) {
        if ( ! self::is_snapshot_supported_post( $post ) ) {
            return;
        }

        if ( $new_status === $old_status ) {
            return;
        }

        if ( $new_status === 'publish' || $old_status === 'publish' ) {
            self::clear_filters_snapshot();
        }
    }

    public static function maybe_clear_filters_snapshot_for_terms( $object_id, $terms, $tt_ids, $taxonomy ) {
        unset( $terms, $tt_ids );

        if ( ! is_string( $taxonomy ) || $taxonomy !== 'category' ) {
            return;
        }

        $post = self::resolve_post( $object_id );
        if ( ! self::should_invalidate_for_post( $post ) ) {
            return;
        }

        self::clear_filters_snapshot();
    }

    public static function maybe_clear_filters_snapshot_for_term_event( $term, $tt_id = null, $taxonomy = '', $deleted_term = null ) {
        unset( $tt_id );

        $resolved_taxonomy = self::resolve_taxonomy_from_term_event( $term, $taxonomy, $deleted_term );

        if ( $resolved_taxonomy !== 'category' ) {
            return;
        }

        self::clear_filters_snapshot();
    }

    private static function is_snapshot_relevant_meta_key( $meta_key ) {
        return in_array( $meta_key, self::SNAPSHOT_RELEVANT_META_KEYS, true );
    }

    private static function resolve_post( $post ) {
        if ( $post instanceof WP_Post ) {
            return $post;
        }

        $post_id = is_numeric( $post ) ? (int) $post : 0;
        if ( $post_id <= 0 ) {
            return null;
        }

        $resolved = get_post( $post_id );

        return $resolved instanceof WP_Post ? $resolved : null;
    }

    private static function should_invalidate_for_post( $post ) {
        if ( ! $post instanceof WP_Post ) {
            return false;
        }

        if ( ! self::is_snapshot_supported_post( $post ) ) {
            return false;
        }

        return ( $post->post_status ?? '' ) === 'publish';
    }

    private static function is_snapshot_supported_post( $post ) {
        if ( ! $post instanceof WP_Post ) {
            return false;
        }

        $post_type = isset( $post->post_type ) ? (string) $post->post_type : '';
        if ( $post_type === '' ) {
            return false;
        }

        if ( ! class_exists( Helpers::class ) ) {
            return false;
        }

        $allowed_types = Helpers::get_allowed_post_types();

        return in_array( $post_type, $allowed_types, true );
    }

    private static function resolve_taxonomy_from_term_event( $term, $taxonomy, $deleted_term ) {
        if ( is_object( $term ) && isset( $term->taxonomy ) ) {
            return (string) $term->taxonomy;
        }

        if ( is_object( $deleted_term ) && isset( $deleted_term->taxonomy ) ) {
            return (string) $deleted_term->taxonomy;
        }

        if ( is_string( $taxonomy ) && $taxonomy !== '' ) {
            return $taxonomy;
        }

        return '';
    }

    public function __construct() {
        add_shortcode( 'jlg_game_explorer', array( $this, 'render' ) );
    }

    public function render( $atts, $content = '', $shortcode_tag = '' ) {
        $context = self::get_render_context( $atts, $_GET );

        if ( ! empty( $context['error'] ) && ! empty( $context['message'] ) ) {
            return $context['message'];
        }

        Frontend::mark_shortcode_rendered( $shortcode_tag ?: 'jlg_game_explorer' );

        return Frontend::get_template_html( 'shortcode-game-explorer', $context );
    }

    public static function get_default_atts() {
        $options        = Helpers::get_plugin_options();
        $posts_per_page = isset( $options['game_explorer_posts_per_page'] ) ? (int) $options['game_explorer_posts_per_page'] : 12;
        if ( $posts_per_page < 1 ) {
            $posts_per_page = 12;
        }

        $columns = isset( $options['game_explorer_columns'] ) ? (int) $options['game_explorer_columns'] : 3;
        if ( $columns < 1 ) {
            $columns = 3;
        }

        $filters_option = isset( $options['game_explorer_filters'] )
            ? $options['game_explorer_filters']
            : Helpers::get_default_game_explorer_filters();

        $filters_list = Helpers::normalize_game_explorer_filters(
            $filters_option,
            Helpers::get_default_game_explorer_filters()
        );

        $filters        = implode( ',', $filters_list );
        $score_position = Helpers::normalize_game_explorer_score_position(
            $options['game_explorer_score_position'] ?? ''
        );

        return array(
            'id'             => 'jlg-game-explorer-' . uniqid(),
            'posts_per_page' => $posts_per_page,
            'columns'        => $columns,
            'filters'        => $filters,
            'score_position' => $score_position,
            'categorie'      => '',
            'plateforme'     => '',
            'lettre'         => '',
            'developpeur'    => '',
            'editeur'        => '',
            'annee'          => '',
            'note_min'       => '',
            'recherche'      => '',
        );
    }

    protected static function get_sort_options() {
        return array(
            array(
                'value'   => 'date|DESC',
                'orderby' => 'date',
                'order'   => 'DESC',
                'label'   => esc_html__( 'Plus récents', 'notation-jlg' ),
            ),
            array(
                'value'   => 'date|ASC',
                'orderby' => 'date',
                'order'   => 'ASC',
                'label'   => esc_html__( 'Plus anciens', 'notation-jlg' ),
            ),
            array(
                'value'   => 'score|DESC',
                'orderby' => 'score',
                'order'   => 'DESC',
                'label'   => esc_html__( 'Meilleures notes', 'notation-jlg' ),
            ),
            array(
                'value'   => 'score|ASC',
                'orderby' => 'score',
                'order'   => 'ASC',
                'label'   => esc_html__( 'Notes les plus basses', 'notation-jlg' ),
            ),
            array(
                'value'   => 'popularity|DESC',
                'orderby' => 'popularity',
                'order'   => 'DESC',
                'label'   => esc_html__( 'Popularité (plus de votes)', 'notation-jlg' ),
            ),
            array(
                'value'   => 'popularity|ASC',
                'orderby' => 'popularity',
                'order'   => 'ASC',
                'label'   => esc_html__( 'Popularité (moins de votes)', 'notation-jlg' ),
            ),
            array(
                'value'   => 'title|ASC',
                'orderby' => 'title',
                'order'   => 'ASC',
                'label'   => esc_html__( 'Titre (A-Z)', 'notation-jlg' ),
            ),
            array(
                'value'   => 'title|DESC',
                'orderby' => 'title',
                'order'   => 'DESC',
                'label'   => esc_html__( 'Titre (Z-A)', 'notation-jlg' ),
            ),
        );
    }

    public static function get_allowed_sort_keys() {
        $options = self::get_sort_options();
        $keys    = array();

        foreach ( $options as $option ) {
            if ( isset( $option['orderby'] ) ) {
                $keys[] = sanitize_key( $option['orderby'] );
            }
        }

        return array_values( array_unique( array_filter( $keys ) ) );
    }

    protected static function normalize_filters( $filters_string ) {
        $allowed         = Helpers::get_game_explorer_allowed_filters();
        $default_filters = Helpers::get_default_game_explorer_filters();
        $list            = Helpers::normalize_game_explorer_filters( $filters_string, $default_filters );

        $normalized = array();

        foreach ( $allowed as $filter_key ) {
            $normalized[ $filter_key ] = in_array( $filter_key, $list, true );
        }

        return $normalized;
    }

    protected static function normalize_letter( $value ) {
        if ( ! is_string( $value ) ) {
            return '';
        }

        $value = trim( $value );
        if ( $value === '' ) {
            return '';
        }

        $value = remove_accents( $value );

        $first = self::substr_unicode( $value, 0, 1 );

        $first_upper = self::strtoupper_unicode( $first );

        if ( preg_match( '/[A-Z]/u', $first_upper ) ) {
            return $first_upper;
        }

        if ( preg_match( '/[0-9]/u', $first_upper ) ) {
            return '#';
        }

        return '#';
    }

    protected static function normalize_text_key( $value ) {
        if ( ! is_string( $value ) ) {
            return '';
        }

        $value = wp_strip_all_tags( $value );
        $value = trim( preg_replace( '/\s+/u', ' ', $value ) );

        if ( $value === '' ) {
            return '';
        }

        if ( function_exists( 'remove_accents' ) ) {
            $value = remove_accents( $value );
        }

        return strtolower( $value );
    }

    protected static function normalize_year( $value, array $buckets = array(), $min_year = null, $max_year = null ) {
        if ( $value === null || $value === '' ) {
            return '';
        }

        if ( is_int( $value ) || ctype_digit( (string) $value ) ) {
            $year = (int) $value;
        } else {
            $value = trim( (string) $value );
            if ( $value === '' || ! preg_match( '/^\d{4}$/', $value ) ) {
                return '';
            }
            $year = (int) $value;
        }

        if ( $year < 1000 || $year > 9999 ) {
            return '';
        }

        if ( is_int( $min_year ) && $year < $min_year ) {
            return '';
        }

        if ( is_int( $max_year ) && $year > $max_year ) {
            return '';
        }

        if ( ! empty( $buckets ) ) {
            $normalized_buckets = array();
            foreach ( $buckets as $bucket_year => $count ) {
                $bucket_year = is_int( $bucket_year ) ? $bucket_year : ( ctype_digit( (string) $bucket_year ) ? (int) $bucket_year : 0 );
                if ( $bucket_year > 0 ) {
                    $normalized_buckets[ $bucket_year ] = (int) $count;
                }
            }

            if ( empty( $normalized_buckets[ $year ] ) ) {
                return '';
            }
        }

        return (string) $year;
    }

    private static function get_score_filter_precision( $score_max ) {
        $normalized_max = is_numeric( $score_max ) ? (float) $score_max : 10.0;

        if ( $normalized_max <= 0 ) {
            $normalized_max = 10.0;
        }

        if ( $normalized_max > 100 ) {
            $normalized_max = 100.0;
        }

        return ( $normalized_max > 20 ) ? 0 : 1;
    }

    private static function format_score_value_attribute( $value, $precision ) {
        $numeric_value = is_numeric( $value ) ? (float) $value : 0.0;

        if ( $precision <= 0 ) {
            return (string) (int) round( $numeric_value );
        }

        $rounded   = round( $numeric_value, (int) $precision );
        $formatted = number_format( $rounded, (int) $precision, '.', '' );
        $formatted = rtrim( rtrim( $formatted, '0' ), '.' );

        if ( $formatted === '' ) {
            return '0';
        }

        return $formatted;
    }

    private static function format_score_value_display( $value, $precision ) {
        $numeric_value = is_numeric( $value ) ? (float) $value : 0.0;
        $decimals      = $precision > 0 ? (int) $precision : 0;

        if ( $decimals > 0 ) {
            $rounded = round( $numeric_value, $decimals );
            if ( abs( $rounded - round( $rounded ) ) < pow( 10, -$decimals ) ) {
                $decimals = 0;
                $rounded  = round( $rounded );
            }
        } else {
            $rounded  = round( $numeric_value );
            $decimals = 0;
        }

        if ( function_exists( 'number_format_i18n' ) ) {
            return number_format_i18n( $rounded, $decimals );
        }

        return number_format( $rounded, $decimals, ',', ' ' );
    }

    protected static function normalize_score_filter( $value, $score_max ) {
        if ( $value === null || $value === '' ) {
            return '';
        }

        if ( is_string( $value ) ) {
            $value = str_replace( ',', '.', $value );
            $value = trim( $value );
        }

        if ( $value === '' || ! is_numeric( $value ) ) {
            return '';
        }

        $normalized_max = is_numeric( $score_max ) ? (float) $score_max : 10.0;
        if ( $normalized_max <= 0 ) {
            $normalized_max = 10.0;
        }
        if ( $normalized_max > 100 ) {
            $normalized_max = 100.0;
        }

        $numeric_value = (float) $value;
        if ( $numeric_value < 0 ) {
            $numeric_value = 0.0;
        }
        if ( $numeric_value > $normalized_max ) {
            $numeric_value = $normalized_max;
        }

        $precision = self::get_score_filter_precision( $normalized_max );

        return self::format_score_value_attribute( $numeric_value, $precision );
    }

    protected static function build_score_filter_options( $score_max, $active_value ) {
        $normalized_max = is_numeric( $score_max ) ? (float) $score_max : 10.0;
        if ( $normalized_max <= 0 ) {
            $normalized_max = 10.0;
        }
        if ( $normalized_max > 100 ) {
            $normalized_max = 100.0;
        }

        $precision = self::get_score_filter_precision( $normalized_max );
        $ratios    = array( 0.5, 0.6, 0.7, 0.8, 0.9 );
        $options   = array();
        $seen      = array();

        foreach ( $ratios as $ratio ) {
            $raw_value = $normalized_max * $ratio;

            if ( $precision <= 0 ) {
                $raw_value = round( $raw_value );
            }

            if ( $raw_value <= 0 || $raw_value >= $normalized_max ) {
                continue;
            }

            $value = self::format_score_value_attribute( $raw_value, $precision );

            if ( isset( $seen[ $value ] ) ) {
                continue;
            }

            $seen[ $value ] = true;

            $options[] = array(
                'value' => $value,
                'label' => sprintf(
                    /* translators: 1: Minimum score, 2: Maximum score. */
                    esc_html__( 'Note ≥ %1$s / %2$s', 'notation-jlg' ),
                    self::format_score_value_display( $value, $precision ),
                    self::format_score_value_display( $normalized_max, $precision )
                ),
            );
        }

        if ( $active_value !== '' ) {
            $normalized_active = self::normalize_score_filter( $active_value, $normalized_max );
            if ( $normalized_active !== '' && ! isset( $seen[ $normalized_active ] ) ) {
                $options[] = array(
                    'value' => $normalized_active,
                    'label' => sprintf(
                        /* translators: 1: Minimum score, 2: Maximum score. */
                        esc_html__( 'Note ≥ %1$s / %2$s', 'notation-jlg' ),
                        self::format_score_value_display( $normalized_active, $precision ),
                        self::format_score_value_display( $normalized_max, $precision )
                    ),
                );
            }
        }

        usort(
            $options,
            static function ( $a, $b ) {
                $a_value = isset( $a['value'] ) ? (float) $a['value'] : 0.0;
                $b_value = isset( $b['value'] ) ? (float) $b['value'] : 0.0;

                if ( abs( $a_value - $b_value ) < 0.0001 ) {
                    return 0;
                }

                return ( $a_value < $b_value ) ? -1 : 1;
            }
        );

        return $options;
    }

    protected static function tokenize_search_terms( $value ) {
        if ( ! is_string( $value ) ) {
            return array();
        }

        $value = wp_strip_all_tags( $value );
        $value = trim( preg_replace( '/\s+/u', ' ', $value ) );

        if ( $value === '' ) {
            return array();
        }

        if ( function_exists( 'remove_accents' ) ) {
            $value = remove_accents( $value );
        }

        $value = strtolower( $value );
        if ( $value === '' ) {
            return array();
        }

        $parts = preg_split( '/\s+/u', $value, -1, PREG_SPLIT_NO_EMPTY );
        if ( ! is_array( $parts ) ) {
            return array();
        }

        $parts = array_values( array_unique( array_filter( array_map( 'trim', $parts ) ) ) );

        return $parts;
    }

    private static function get_index_meta_key( $field ) {
        return self::INDEX_META_KEYS[ $field ] ?? '';
    }

    private static function build_search_index( array $tokens ) {
        if ( empty( $tokens ) ) {
            return '';
        }

        $normalized = array();

        foreach ( $tokens as $token ) {
            if ( ! is_string( $token ) ) {
                continue;
            }

            $token = trim( wp_strip_all_tags( $token ) );
            if ( $token === '' ) {
                continue;
            }

            if ( function_exists( 'remove_accents' ) ) {
                $token = remove_accents( $token );
            }

            $token = strtolower( $token );

            if ( $token === '' ) {
                continue;
            }

            $normalized[] = $token;
        }

        if ( empty( $normalized ) ) {
            return '';
        }

        $normalized = array_values( array_unique( $normalized ) );

        return ' ' . implode( ' ', $normalized ) . ' ';
    }

    private static function build_platform_index( array $platform_slugs ) {
        if ( empty( $platform_slugs ) ) {
            return '';
        }

        $platform_slugs = array_values( array_unique( array_filter( array_map( 'strval', $platform_slugs ) ) ) );
        if ( empty( $platform_slugs ) ) {
            return '';
        }

        return '|' . implode( '|', $platform_slugs ) . '|';
    }

    private static function sync_post_index_meta( $post_id, array $index_data ) {
        $post_id = (int) $post_id;
        if ( $post_id <= 0 ) {
            return;
        }

        foreach ( $index_data as $field => $value ) {
            $meta_key = self::get_index_meta_key( $field );
            if ( $meta_key === '' ) {
                continue;
            }

            $current_value = get_post_meta( $post_id, $meta_key, true );

            if ( is_array( $value ) ) {
                $normalized_value = array_values( $value );
                $stored_value     = is_array( $current_value ) ? array_values( $current_value ) : array();

                if ( $stored_value !== $normalized_value ) {
                    update_post_meta( $post_id, $meta_key, $normalized_value );
                }
            } else {
                $normalized_value = is_string( $value ) ? $value : (string) $value;
                $stored_value     = is_string( $current_value ) ? $current_value : (string) $current_value;

                if ( $stored_value !== $normalized_value ) {
                    if ( $normalized_value === '' ) {
                        delete_post_meta( $post_id, $meta_key );
                    } else {
                        update_post_meta( $post_id, $meta_key, $normalized_value );
                    }
                }
            }
        }
    }

    private static function substr_unicode( $text, $start, $length = null, $encoding = 'UTF-8' ) {
        $text = (string) $text;

        if ( function_exists( 'mb_substr' ) ) {
            $result = $length === null
                ? mb_substr( $text, $start, null, $encoding )
                : mb_substr( $text, $start, $length, $encoding );

            return $result === false ? '' : $result;
        }

        if ( function_exists( 'iconv_substr' ) ) {
            if ( $length === null ) {
                $iconv_length = null;
                if ( function_exists( 'iconv_strlen' ) ) {
                    $computed_length = iconv_strlen( $text, $encoding );
                    if ( $computed_length !== false ) {
                        $iconv_length = $computed_length;
                    }
                }
                $result = $iconv_length === null
                    ? iconv_substr( $text, $start, strlen( $text ), $encoding )
                    : iconv_substr( $text, $start, $iconv_length, $encoding );
            } else {
                $result = iconv_substr( $text, $start, $length, $encoding );
            }

            if ( $result !== false && $result !== null ) {
                return $result;
            }
        }

        if ( $text === '' ) {
            return '';
        }

        if ( function_exists( 'wp_strlen' ) ) {
            $chars = preg_split( '//u', $text, -1, PREG_SPLIT_NO_EMPTY );
            if ( is_array( $chars ) ) {
                $slice = $length === null ? array_slice( $chars, $start ) : array_slice( $chars, $start, $length );
                return implode( '', $slice );
            }
        }

        if ( $length === null ) {
            return substr( $text, $start );
        }

        return substr( $text, $start, $length );
    }

    private static function strtoupper_unicode( $text, $encoding = 'UTF-8' ) {
        $text = (string) $text;

        if ( function_exists( 'mb_strtoupper' ) ) {
            return mb_strtoupper( $text, $encoding );
        }

        if ( function_exists( 'wp_strtoupper' ) ) {
            return wp_strtoupper( $text );
        }

        return strtoupper( $text );
    }

    private static function get_current_timestamp() {
        if ( function_exists( 'current_datetime' ) ) {
            $datetime = current_datetime();

            if ( $datetime instanceof \DateTimeInterface ) {
                return $datetime->getTimestamp();
            }
        }

        return time();
    }

    protected static function resolve_category_id( $value ) {
        if ( $value === null || $value === '' ) {
            return 0;
        }

        if ( is_numeric( $value ) ) {
            $id = (int) $value;
            return $id > 0 ? $id : 0;
        }

        $slug = sanitize_title( $value );
        if ( $slug === '' ) {
            return 0;
        }

        $term = get_category_by_slug( $slug );
        if ( $term && ! is_wp_error( $term ) ) {
            return (int) $term->term_id;
        }

        return 0;
    }

    protected static function resolve_platform_slug( $value ) {
        if ( ! is_string( $value ) ) {
            return '';
        }

        $value = trim( $value );
        if ( $value === '' ) {
            return '';
        }

        return sanitize_title( $value );
    }

    protected static function normalize_date( $raw_date ) {
        if ( ! is_string( $raw_date ) ) {
            return '';
        }

        $raw_date = trim( $raw_date );
        if ( $raw_date === '' ) {
            return '';
        }

        $date = DateTime::createFromFormat( 'Y-m-d', $raw_date );
        if ( $date instanceof DateTime ) {
            return $date->format( 'Y-m-d' );
        }

        $timestamp = strtotime( $raw_date );
        if ( $timestamp ) {
            return gmdate( 'Y-m-d', $timestamp );
        }

        return '';
    }

    protected static function determine_availability( $date_iso ) {
        if ( $date_iso === '' ) {
            return array(
                'status' => 'unknown',
                'label'  => esc_html__( 'À confirmer', 'notation-jlg' ),
            );
        }

        $timezone = null;

        if ( function_exists( 'wp_timezone' ) ) {
            $timezone = wp_timezone();
        }

        if ( ! $timezone instanceof DateTimeZone ) {
            $timezone_string = function_exists( 'wp_timezone_string' ) ? wp_timezone_string() : 'UTC';

            try {
                $timezone = new DateTimeZone( is_string( $timezone_string ) && $timezone_string !== '' ? $timezone_string : 'UTC' );
            } catch ( Exception $exception ) {
                $timezone = new DateTimeZone( 'UTC' );
            }
        }

        $release_date = DateTimeImmutable::createFromFormat( '!Y-m-d', $date_iso, $timezone );

        if ( ! $release_date instanceof DateTimeImmutable ) {
            return array(
                'status' => 'unknown',
                'label'  => esc_html__( 'À confirmer', 'notation-jlg' ),
            );
        }

        $parse_errors = DateTimeImmutable::getLastErrors();
        if ( is_array( $parse_errors ) && ( ( $parse_errors['warning_count'] ?? 0 ) > 0 || ( $parse_errors['error_count'] ?? 0 ) > 0 ) ) {
            return array(
                'status' => 'unknown',
                'label'  => esc_html__( 'À confirmer', 'notation-jlg' ),
            );
        }

        if ( $release_date->getTimestamp() > (int) self::get_current_timestamp() ) {
            return array(
                'status' => 'upcoming',
                'label'  => esc_html__( 'À venir', 'notation-jlg' ),
            );
        }

        return array(
            'status' => 'available',
            'label'  => esc_html__( 'Disponible', 'notation-jlg' ),
        );
    }

    protected static function build_letter_navigation( $letters_map ) {
        $letters_map = is_array( $letters_map ) ? $letters_map : array();
        $alphabet    = range( 'A', 'Z' );
        $letters     = array();

        foreach ( $alphabet as $letter ) {
            $letters[] = array(
                'value'   => $letter,
                'label'   => $letter,
                'enabled' => ! empty( $letters_map[ $letter ] ),
            );
        }

        $letters[] = array(
            'value'   => '#',
            'label'   => '#',
            'enabled' => ! empty( $letters_map['#'] ),
        );

        return $letters;
    }

    protected static function get_filters_snapshot() {
        if ( is_array( self::$filters_snapshot ) ) {
            return self::$filters_snapshot;
        }

        $snapshot = get_transient( self::SNAPSHOT_TRANSIENT_KEY );

        if ( ! is_array( $snapshot ) || empty( $snapshot['posts'] ) ) {
            $snapshot = self::build_filters_snapshot();

            $ttl = apply_filters( 'jlg_game_explorer_snapshot_ttl', defined( 'MINUTE_IN_SECONDS' ) ? 5 * MINUTE_IN_SECONDS : 300 );
            if ( is_numeric( $ttl ) && (int) $ttl > 0 ) {
                set_transient( self::SNAPSHOT_TRANSIENT_KEY, $snapshot, (int) $ttl );
            }
        }

        self::$filters_snapshot = $snapshot;

        return $snapshot;
    }

    private static function get_query_cache_version() {
        if ( self::$query_cache_version !== null ) {
            return self::$query_cache_version;
        }

        $stored_version = get_option( self::QUERY_CACHE_VERSION_OPTION, 1 );
        $stored_version = is_numeric( $stored_version ) ? (int) $stored_version : 1;

        if ( $stored_version < 1 ) {
            $stored_version = 1;
        }

        self::$query_cache_version = $stored_version;

        return self::$query_cache_version;
    }

    private static function bump_query_cache_version() {
        $next_version              = self::get_query_cache_version() + 1;
        self::$query_cache_version = $next_version;
        update_option( self::QUERY_CACHE_VERSION_OPTION, $next_version, false );
    }

    private static function is_query_cache_enabled() {
        $enabled = true;

        if ( function_exists( 'apply_filters' ) ) {
            $enabled = apply_filters( 'jlg_game_explorer_enable_query_cache', $enabled );
        }

        return (bool) $enabled;
    }

    private static function normalize_query_filters_for_cache( array $filters ) {
        ksort( $filters );

        foreach ( $filters as $key => $value ) {
            if ( is_array( $value ) ) {
                $filters[ $key ] = self::normalize_query_filters_for_cache( $value );
                continue;
            }

            if ( is_bool( $value ) ) {
                $filters[ $key ] = $value;
                continue;
            }

            if ( $value === null ) {
                $filters[ $key ] = null;
                continue;
            }

            $filters[ $key ] = (string) $value;
        }

        return $filters;
    }

    private static function build_query_cache_key( array $filters, $orderby, $order, $posts_per_page, $paged ) {
        $payload = array(
            'filters'        => self::normalize_query_filters_for_cache( $filters ),
            'orderby'        => (string) $orderby,
            'order'          => (string) $order,
            'posts_per_page' => (int) $posts_per_page,
            'paged'          => (int) $paged,
        );

        $hash = md5( wp_json_encode( $payload ) );

        return self::QUERY_CACHE_KEY_PREFIX . self::get_query_cache_version() . '_' . $hash;
    }

    private static function maybe_get_cached_query_result( array $filters, $orderby, $order, $posts_per_page, $paged ) {
        if ( ! self::is_query_cache_enabled() ) {
            return null;
        }

        $cache_key = self::build_query_cache_key( $filters, $orderby, $order, $posts_per_page, $paged );
        $cached    = get_transient( $cache_key );

        if ( ! is_array( $cached ) ) {
            return null;
        }

        if ( ! array_key_exists( 'post_ids', $cached ) ) {
            return null;
        }

        $cached['post_ids'] = array_values( array_map( 'intval', (array) $cached['post_ids'] ) );

        return $cached;
    }

    private static function store_query_result_cache( array $filters, $orderby, $order, $posts_per_page, $paged, array $result ) {
        if ( ! self::is_query_cache_enabled() ) {
            return;
        }

        $cache_key = self::build_query_cache_key( $filters, $orderby, $order, $posts_per_page, $paged );

        $ttl = defined( 'MINUTE_IN_SECONDS' ) ? 5 * MINUTE_IN_SECONDS : 300;
        if ( function_exists( 'apply_filters' ) ) {
            $ttl = apply_filters( 'jlg_game_explorer_query_cache_ttl', $ttl, $filters, $orderby, $order, $posts_per_page, $paged );
        }

        $ttl = is_numeric( $ttl ) ? (int) $ttl : 0;

        if ( $ttl <= 0 ) {
            set_transient( $cache_key, $result );
            return;
        }

        set_transient( $cache_key, $result, $ttl );
    }

    protected static function build_filters_snapshot() {
        $allowed_post_types = Helpers::get_allowed_post_types();
        if ( ! is_array( $allowed_post_types ) ) {
            $allowed_post_types = array();
        }

        $allowed_post_types = array_values(
            array_filter(
                array_map(
                    static function ( $post_type ) {
                        return is_string( $post_type ) ? $post_type : '';
                    },
                    $allowed_post_types
                ),
                static function ( $post_type ) {
                    return $post_type !== '';
                }
            )
        );

        if ( empty( $allowed_post_types ) ) {
            $allowed_post_types = array( 'post' );
        }

        $snapshot = array(
            'posts'          => array(),
            'letters_map'    => array(),
            'categories_map' => array(),
            'platforms_map'  => array(),
            'developers_map' => array(),
            'publishers_map' => array(),
            'search_tokens'  => array(),
            'years'          => array(
                'min'     => null,
                'max'     => null,
                'buckets' => array(),
            ),
        );

        $rated_posts = Helpers::get_rated_post_ids();
        if ( empty( $rated_posts ) ) {
            return $snapshot;
        }

        $rated_posts = array_values(
            array_filter(
                array_map( 'intval', $rated_posts ),
                static function ( $post_id ) {
					return $post_id > 0;
                }
            )
        );

        if ( empty( $rated_posts ) ) {
            return $snapshot;
        }

        $post_id_chunks = count( $rated_posts ) > 100 ? array_chunk( $rated_posts, 100 ) : array( $rated_posts );

        if ( function_exists( 'update_meta_cache' ) ) {
            foreach ( $post_id_chunks as $post_id_chunk ) {
                if ( ! empty( $post_id_chunk ) ) {
                    update_meta_cache( 'post', $post_id_chunk );
                }
            }
        }

        if ( function_exists( 'update_object_term_cache' ) ) {
            $post_ids_by_type = array();

            foreach ( $post_id_chunks as $post_id_chunk ) {
                if ( empty( $post_id_chunk ) ) {
                    continue;
                }

                foreach ( $post_id_chunk as $post_id ) {
                    $post_id = (int) $post_id;

                    if ( $post_id <= 0 ) {
                        continue;
                    }

                    $post_type = function_exists( 'get_post_type' ) ? get_post_type( $post_id ) : null;

                    if ( ! is_string( $post_type ) || $post_type === '' || ! in_array( $post_type, $allowed_post_types, true ) ) {
                        $post_type = 'post';
                    }

                    if ( ! isset( $post_ids_by_type[ $post_type ] ) ) {
                        $post_ids_by_type[ $post_type ] = array();
                    }

                    $post_ids_by_type[ $post_type ][] = $post_id;
                }
            }

            foreach ( $post_ids_by_type as $post_type => $ids_for_type ) {
                if ( empty( $ids_for_type ) ) {
                    continue;
                }

                $ids_for_type = array_values(
                    array_filter(
                        array_unique(
                            array_map( 'intval', $ids_for_type )
                        ),
                        static function ( $post_id ) {
                            return $post_id > 0;
                        }
                    )
                );

                if ( empty( $ids_for_type ) ) {
                    continue;
                }

                $type_chunks = count( $ids_for_type ) > 100 ? array_chunk( $ids_for_type, 100 ) : array( $ids_for_type );

                foreach ( $type_chunks as $type_chunk ) {
                    if ( empty( $type_chunk ) ) {
                        continue;
                    }

                    update_object_term_cache( $type_chunk, $post_type, array( 'category' ) );
                }
            }
        }

        foreach ( $rated_posts as $post_id ) {
            if ( $post_id <= 0 ) {
                continue;
            }

            $post = get_post( $post_id );
            if ( ! $post || $post->post_status !== 'publish' ) {
                continue;
            }

            $title = Helpers::get_game_title( $post_id );
            if ( $title === '' ) {
                $title = get_the_title( $post_id );
            }

            $letter = self::normalize_letter( $title );
            if ( $letter !== '' ) {
                $snapshot['letters_map'][ $letter ] = true;
            }

            $release_raw  = get_post_meta( $post_id, '_jlg_date_sortie', true );
            $release_iso  = self::normalize_date( $release_raw );
            $availability = self::determine_availability( $release_iso );

            $release_year = null;
            if ( $release_iso !== '' && preg_match( '/^(\d{4})-/', $release_iso, $matches ) ) {
                $year_value = (int) $matches[1];
                if ( $year_value > 0 ) {
                    $release_year = $year_value;

                    if ( ! isset( $snapshot['years']['buckets'][ $year_value ] ) ) {
                        $snapshot['years']['buckets'][ $year_value ] = 0;
                    }

                    ++$snapshot['years']['buckets'][ $year_value ];

                    if ( ! is_int( $snapshot['years']['min'] ) || $year_value < $snapshot['years']['min'] ) {
                        $snapshot['years']['min'] = $year_value;
                    }

                    if ( ! is_int( $snapshot['years']['max'] ) || $year_value > $snapshot['years']['max'] ) {
                        $snapshot['years']['max'] = $year_value;
                    }
                }
            }

            $developer = get_post_meta( $post_id, '_jlg_developpeur', true );
            $developer = is_string( $developer ) ? trim( sanitize_text_field( $developer ) ) : '';
            $publisher = get_post_meta( $post_id, '_jlg_editeur', true );
            $publisher = is_string( $publisher ) ? trim( sanitize_text_field( $publisher ) ) : '';

            $developer_key = $developer !== '' ? self::normalize_text_key( $developer ) : '';
            $publisher_key = $publisher !== '' ? self::normalize_text_key( $publisher ) : '';

            if ( $developer_key !== '' && ! isset( $snapshot['developers_map'][ $developer_key ] ) ) {
                $snapshot['developers_map'][ $developer_key ] = $developer;
            }

            if ( $publisher_key !== '' && ! isset( $snapshot['publishers_map'][ $publisher_key ] ) ) {
                $snapshot['publishers_map'][ $publisher_key ] = $publisher;
            }

            $platform_meta   = get_post_meta( $post_id, '_jlg_plateformes', true );
            $platform_labels = array();
            $platform_slugs  = array();

            if ( is_array( $platform_meta ) ) {
                foreach ( $platform_meta as $platform_item ) {
                    if ( ! is_string( $platform_item ) ) {
                        continue;
                    }

                    $label = sanitize_text_field( $platform_item );
                    if ( $label === '' ) {
                        continue;
                    }

                    $slug              = self::resolve_platform_slug( $label );
                    $platform_labels[] = $label;
                    if ( $slug !== '' ) {
                        $platform_slugs[] = $slug;
                        if ( ! isset( $snapshot['platforms_map'][ $slug ] ) ) {
                            $snapshot['platforms_map'][ $slug ] = $label;
                        }
                    }
                }
            } elseif ( is_string( $platform_meta ) && $platform_meta !== '' ) {
                $label             = sanitize_text_field( $platform_meta );
                $slug              = self::resolve_platform_slug( $label );
                $platform_labels[] = $label;
                if ( $slug !== '' ) {
                    $platform_slugs[] = $slug;
                    if ( ! isset( $snapshot['platforms_map'][ $slug ] ) ) {
                        $snapshot['platforms_map'][ $slug ] = $label;
                    }
                }
            }

            $terms          = get_the_terms( $post_id, 'category' );
            $category_ids   = array();
            $category_slugs = array();
            $primary_genre  = '';

            if ( $terms && ! is_wp_error( $terms ) ) {
                foreach ( $terms as $term ) {
                    $snapshot['categories_map'][ $term->term_id ] = $term->name;
                    $category_ids[]                               = (int) $term->term_id;
                    $category_slugs[]                             = $term->slug;
                    if ( $primary_genre === '' ) {
                        $primary_genre = $term->name;
                    }
                }
            }

            $search_index_tokens = array_merge(
                array( $title, $developer, $publisher, $primary_genre ),
                $platform_labels
            );

            $search_index   = self::build_search_index( $search_index_tokens );
            $platform_index = self::build_platform_index( $platform_slugs );

            $tokens_for_counts = self::tokenize_search_terms( implode( ' ', $search_index_tokens ) );
            if ( ! empty( $tokens_for_counts ) ) {
                foreach ( $tokens_for_counts as $token_value ) {
                    if ( $token_value === '' ) {
                        continue;
                    }

                    if ( ! isset( $snapshot['search_tokens'][ $token_value ] ) ) {
                        $snapshot['search_tokens'][ $token_value ] = 0;
                    }

                    ++$snapshot['search_tokens'][ $token_value ];
                }
            }

            self::sync_post_index_meta(
                $post_id,
                array(
                    'letter'         => $letter,
                    'developer'      => $developer_key,
                    'publisher'      => $publisher_key,
                    'availability'   => $availability['status'],
                    'release_year'   => $release_year !== null ? (string) $release_year : '',
                    'search_index'   => $search_index,
                    'platform_index' => $platform_index,
                )
            );

            $popularity_score = get_post_meta( $post_id, '_jlg_user_rating_count', true );
            if ( ! is_numeric( $popularity_score ) ) {
                $popularity_score = 0;
            }

            $snapshot['posts'][ $post_id ] = array(
                'letter'          => $letter,
                'category_ids'    => $category_ids,
                'category_slugs'  => $category_slugs,
                'primary_genre'   => $primary_genre,
                'platform_labels' => $platform_labels,
                'platform_slugs'  => $platform_slugs,
                'developer'       => $developer,
                'developer_key'   => $developer_key,
                'publisher'       => $publisher,
                'publisher_key'   => $publisher_key,
                'release_iso'     => $release_iso,
                'release_year'    => $release_year,
                'availability'    => $availability['status'],
                'search_index'    => $search_index,
                'popularity'      => (int) $popularity_score,
                'index_meta'      => array(
                    'letter'         => $letter,
                    'developer'      => $developer_key,
                    'publisher'      => $publisher_key,
                    'availability'   => $availability['status'],
                    'release_year'   => $release_year !== null ? (string) $release_year : '',
                    'search_index'   => $search_index,
                    'platform_index' => $platform_index,
                ),
            );
        }

        if ( ! empty( $snapshot['years']['buckets'] ) ) {
            ksort( $snapshot['years']['buckets'], SORT_NUMERIC );
            $year_keys = array_keys( $snapshot['years']['buckets'] );
            $first_key = reset( $year_keys );
            $last_key  = end( $year_keys );

            $snapshot['years']['min'] = $first_key !== false ? (int) $first_key : null;
            $snapshot['years']['max'] = $last_key !== false ? (int) $last_key : null;
        } else {
            $snapshot['years']['min'] = null;
            $snapshot['years']['max'] = null;
        }

        return $snapshot;
    }

    protected static function build_query_args_from_filters( array $filters, $orderby, $order, $posts_per_page, $paged ) {
        $post_types    = Helpers::get_allowed_post_types();
        $post_statuses = apply_filters( 'jlg_rated_post_statuses', array( 'publish' ) );
        if ( ! is_array( $post_statuses ) || empty( $post_statuses ) ) {
            $post_statuses = array( 'publish' );
        }

        $meta_clauses = array();
        $tax_query    = array();

        if ( ! empty( $filters['letter'] ) ) {
            $meta_clauses[] = array(
                'key'     => self::get_index_meta_key( 'letter' ),
                'value'   => $filters['letter'],
                'compare' => '=',
            );
        }

        if ( ! empty( $filters['developer_key'] ) ) {
            $meta_clauses[] = array(
                'key'     => self::get_index_meta_key( 'developer' ),
                'value'   => $filters['developer_key'],
                'compare' => '=',
            );
        }

        if ( ! empty( $filters['publisher_key'] ) ) {
            $meta_clauses[] = array(
                'key'     => self::get_index_meta_key( 'publisher' ),
                'value'   => $filters['publisher_key'],
                'compare' => '=',
            );
        }

        if ( ! empty( $filters['availability'] ) ) {
            $meta_clauses[] = array(
                'key'     => self::get_index_meta_key( 'availability' ),
                'value'   => $filters['availability'],
                'compare' => '=',
            );
        }

        if ( ! empty( $filters['year'] ) ) {
            $meta_clauses[] = array(
                'key'     => self::get_index_meta_key( 'release_year' ),
                'value'   => (string) $filters['year'],
                'compare' => '=',
                'type'    => 'NUMERIC',
            );
        }

        if ( ! empty( $filters['platform_slug'] ) ) {
            $meta_clauses[] = array(
                'key'     => self::get_index_meta_key( 'platform_index' ),
                'value'   => '|' . $filters['platform_slug'] . '|',
                'compare' => 'LIKE',
            );
        }

        if ( ! empty( $filters['search_terms'] ) ) {
            $search_index_key = self::get_index_meta_key( 'search_index' );
            $search_relation  = array( 'relation' => 'AND' );

            foreach ( $filters['search_terms'] as $term ) {
                $search_relation[] = array(
                    'key'     => $search_index_key,
                    'value'   => $term,
                    'compare' => 'LIKE',
                );
            }

            $meta_clauses[] = $search_relation;
        }

        if ( isset( $filters['score_min'] ) && $filters['score_min'] !== '' ) {
            $meta_clauses[] = array(
                'key'     => '_jlg_average_score',
                'value'   => (float) $filters['score_min'],
                'compare' => '>=',
                'type'    => 'NUMERIC',
            );
        }

        if ( ! empty( $filters['category_id'] ) ) {
            $tax_query[] = array(
                'taxonomy'         => 'category',
                'field'            => 'term_id',
                'terms'            => array( (int) $filters['category_id'] ),
                'include_children' => false,
            );
        } elseif ( ! empty( $filters['category_slug'] ) ) {
            $tax_query[] = array(
                'taxonomy'         => 'category',
                'field'            => 'slug',
                'terms'            => array( $filters['category_slug'] ),
                'include_children' => false,
            );
        }

        if ( ! empty( $tax_query ) && count( $tax_query ) > 1 ) {
            $tax_query['relation'] = 'AND';
        }

        $query_args = array(
            'post_type'              => $post_types,
            'post_status'            => $post_statuses,
            'posts_per_page'         => $posts_per_page,
            'paged'                  => $paged,
            'order'                  => $order,
            'orderby'                => 'date',
            'ignore_sticky_posts'    => true,
            'no_found_rows'          => false,
            'update_post_term_cache' => false,
        );

        if ( ! empty( $meta_clauses ) ) {
            $query_args['meta_query'] = array_merge( array( 'relation' => 'AND' ), $meta_clauses );
        }

        if ( ! empty( $tax_query ) ) {
            $query_args['tax_query'] = $tax_query;
        }

        if ( $orderby === 'score' ) {
            $query_args['meta_key']  = '_jlg_average_score';
            $query_args['orderby']   = 'meta_value_num';
            $query_args['meta_type'] = 'DECIMAL';
        } elseif ( $orderby === 'popularity' ) {
            $popularity_meta_key     = '_jlg_user_rating_count';
            $query_args['meta_key']  = $popularity_meta_key;
            $query_args['meta_type'] = 'NUMERIC';
            $query_args['orderby']   = array(
                'meta_value_num' => $order,
                'date'           => 'DESC',
            );
        } elseif ( $orderby === 'title' ) {
            $query_args['orderby'] = 'title';
        }

        return $query_args;
    }

    /**
     * Builds the render context used by the [jlg_game_explorer] shortcode.
     *
     * Supported request parameters (optionally namespaced with the container prefix):
     * - orderby: Sorting key (date, score, title or popularity).
     * - order: Sorting direction (ASC or DESC).
     * - letter: Letter filter applied to the list.
     * - category: Category identifier or slug filter.
     * - platform: Platform slug filter.
     * - developer: Developer name filter.
     * - publisher: Publisher name filter.
     * - availability: Availability status filter.
     * - score: Minimum editorial score filter.
     * - search: Search query string.
     * - paged: Current page number.
     *
     * @param array<string, mixed> $atts    Shortcode attributes.
     * @param array<string, mixed> $request Raw request data (e.g. $_GET).
     *
     * @return array<string, mixed>
     */
    public static function get_render_context( $atts, $request = array() ) {
        $defaults = self::get_default_atts();
        $atts     = shortcode_atts( $defaults, $atts, 'jlg_game_explorer' );

        $atts['id']     = self::normalize_container_id( $atts['id'] ?? '' );
        $request_prefix = self::get_request_prefix( $atts['id'] );
        $request_keys   = self::build_request_keys( $request_prefix );

        $request = self::extract_request_params( $request, $request_keys );

        if ( isset( $request['orderby'] ) && is_string( $request['orderby'] ) && strpos( $request['orderby'], '|' ) !== false ) {
            $parts = array_map( 'trim', explode( '|', $request['orderby'] ) );
            if ( isset( $parts[0] ) && $parts[0] !== '' ) {
                $request['orderby'] = $parts[0];
            }
            if ( isset( $parts[1] ) && $parts[1] !== '' ) {
                $request['order'] = $parts[1];
            }
        }

        $options        = Helpers::get_plugin_options();
        $posts_per_page = isset( $atts['posts_per_page'] ) ? (int) $atts['posts_per_page'] : $defaults['posts_per_page'];
        if ( $posts_per_page < 1 ) {
            $posts_per_page = isset( $options['game_explorer_posts_per_page'] ) ? (int) $options['game_explorer_posts_per_page'] : 12;
        }
        $posts_per_page = max( 1, min( $posts_per_page, 60 ) );

        $columns = isset( $atts['columns'] ) ? (int) $atts['columns'] : $defaults['columns'];
        if ( $columns < 1 ) {
            $columns = isset( $options['game_explorer_columns'] ) ? (int) $options['game_explorer_columns'] : 3;
        }
        $columns = max( 1, min( $columns, 4 ) );

        $filters_enabled = self::normalize_filters( $atts['filters'] );
        $score_position  = Helpers::normalize_game_explorer_score_position( $atts['score_position'] ?? '' );
        $score_max       = Helpers::get_score_max( $options );

        $orderby = ( isset( $request['orderby'] ) && is_string( $request['orderby'] ) ) ? sanitize_key( $request['orderby'] ) : 'date';
        $order   = isset( $request['order'] ) ? strtoupper( sanitize_text_field( $request['order'] ) ) : 'DESC';
        if ( ! in_array( $order, array( 'ASC', 'DESC' ), true ) ) {
            $order = 'DESC';
        }

        $letter_filter       = isset( $request['letter'] ) ? self::normalize_letter( $request['letter'] ) : '';
        $category_filter     = isset( $request['category'] ) ? sanitize_text_field( $request['category'] ) : '';
        $platform_filter     = isset( $request['platform'] ) ? sanitize_text_field( $request['platform'] ) : '';
        $developer_filter    = isset( $request['developer'] ) ? trim( sanitize_text_field( $request['developer'] ) ) : '';
        $publisher_filter    = isset( $request['publisher'] ) ? trim( sanitize_text_field( $request['publisher'] ) ) : '';
        $availability_filter = ( isset( $request['availability'] ) && is_string( $request['availability'] ) ) ? sanitize_key( $request['availability'] ) : '';
        $year_filter_raw     = isset( $request['year'] ) ? sanitize_text_field( $request['year'] ) : '';
        $score_filter_raw    = isset( $request['score'] ) ? sanitize_text_field( $request['score'] ) : '';
        $search_filter       = isset( $request['search'] ) ? sanitize_text_field( $request['search'] ) : '';
        $paged               = isset( $request['paged'] ) ? max( 1, (int) $request['paged'] ) : 1;

        if ( empty( $filters_enabled['letter'] ) ) {
            $letter_filter = '';
        }
        if ( empty( $filters_enabled['category'] ) ) {
            $category_filter = '';
        }
        if ( empty( $filters_enabled['platform'] ) ) {
            $platform_filter = '';
        }
        if ( empty( $filters_enabled['developer'] ) ) {
            $developer_filter = '';
        }
        if ( empty( $filters_enabled['publisher'] ) ) {
            $publisher_filter = '';
        }
        if ( empty( $filters_enabled['availability'] ) ) {
            $availability_filter = '';
        }
        if ( empty( $filters_enabled['year'] ) ) {
            $year_filter_raw = '';
        }
        if ( empty( $filters_enabled['score'] ) ) {
            $score_filter_raw = '';
        }
        if ( empty( $filters_enabled['search'] ) ) {
            $search_filter = '';
        }

        $forced_category = self::resolve_category_id( $atts['categorie'] );
        if ( $forced_category > 0 ) {
            $category_filter = (string) $forced_category;
        }

        $forced_platform = self::resolve_platform_slug( $atts['plateforme'] );
        if ( $forced_platform !== '' ) {
            $platform_filter = $forced_platform;
        }

        $forced_letter = self::normalize_letter( $atts['lettre'] );
        if ( $forced_letter !== '' ) {
            $letter_filter = $forced_letter;
        }

        $forced_developer = isset( $atts['developpeur'] ) ? trim( sanitize_text_field( $atts['developpeur'] ) ) : '';
        if ( $forced_developer !== '' ) {
            $developer_filter     = $forced_developer;
            $developer_filter_key = self::normalize_text_key( $developer_filter );
        }

        $forced_publisher = isset( $atts['editeur'] ) ? trim( sanitize_text_field( $atts['editeur'] ) ) : '';
        if ( $forced_publisher !== '' ) {
            $publisher_filter     = $forced_publisher;
            $publisher_filter_key = self::normalize_text_key( $publisher_filter );
        }

        $forced_year = isset( $atts['annee'] ) ? sanitize_text_field( $atts['annee'] ) : '';
        if ( $forced_year !== '' ) {
            $year_filter_raw = $forced_year;
        }

        $forced_search = isset( $atts['recherche'] ) ? sanitize_text_field( $atts['recherche'] ) : '';
        if ( $forced_search !== '' ) {
            $search_filter = $forced_search;
        }

        $forced_score = isset( $atts['note_min'] ) ? sanitize_text_field( $atts['note_min'] ) : '';
        if ( $forced_score !== '' ) {
            $score_filter_raw = $forced_score;
        }

        $developer_filter_key = $developer_filter !== '' ? self::normalize_text_key( $developer_filter ) : '';
        $publisher_filter_key = $publisher_filter !== '' ? self::normalize_text_key( $publisher_filter ) : '';
        $score_filter         = self::normalize_score_filter( $score_filter_raw, $score_max );

        $allowed_orderby = self::get_allowed_sort_keys();
        if ( empty( $allowed_orderby ) ) {
            $allowed_orderby = array( 'date', 'score', 'title' );
        }
        if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
            $orderby = 'date';
        }

        $snapshot = self::get_filters_snapshot();
        if ( empty( $snapshot['posts'] ) ) {
            $message = '<p>' . esc_html__( 'Aucun test noté n\'est disponible pour le moment.', 'notation-jlg' ) . '</p>';

            return array(
                'error'   => true,
                'message' => $message,
                'atts'    => $atts,
            );
        }

        $letters_map = isset( $snapshot['letters_map'] ) && is_array( $snapshot['letters_map'] ) ? $snapshot['letters_map'] : array();
        $letters     = self::build_letter_navigation( $letters_map );

        $categories_map  = isset( $snapshot['categories_map'] ) && is_array( $snapshot['categories_map'] ) ? $snapshot['categories_map'] : array();
        $categories_list = array();
        if ( ! empty( $categories_map ) ) {
            asort( $categories_map, SORT_NATURAL | SORT_FLAG_CASE );
            foreach ( $categories_map as $id => $name ) {
                $categories_list[] = array(
                    'value' => (string) $id,
                    'label' => $name,
                );
            }
        }

        $developers_map = isset( $snapshot['developers_map'] ) && is_array( $snapshot['developers_map'] ) ? $snapshot['developers_map'] : array();
        if ( $developer_filter_key !== '' && $developer_filter !== '' && ! isset( $developers_map[ $developer_filter_key ] ) ) {
            $developers_map[ $developer_filter_key ] = $developer_filter;
        }

        $developers_list = array();
        if ( ! empty( $developers_map ) ) {
            asort( $developers_map, SORT_NATURAL | SORT_FLAG_CASE );
            foreach ( $developers_map as $developer_key => $label ) {
                $developers_list[] = array(
                    'value' => $label,
                    'label' => $label,
                );
            }
        }

        $publishers_map = isset( $snapshot['publishers_map'] ) && is_array( $snapshot['publishers_map'] ) ? $snapshot['publishers_map'] : array();
        if ( $publisher_filter_key !== '' && $publisher_filter !== '' && ! isset( $publishers_map[ $publisher_filter_key ] ) ) {
            $publishers_map[ $publisher_filter_key ] = $publisher_filter;
        }

        $publishers_list = array();
        if ( ! empty( $publishers_map ) ) {
            asort( $publishers_map, SORT_NATURAL | SORT_FLAG_CASE );
            foreach ( $publishers_map as $publisher_key => $label ) {
                $publishers_list[] = array(
                    'value' => $label,
                    'label' => $label,
                );
            }
        }

        $platforms_map  = isset( $snapshot['platforms_map'] ) && is_array( $snapshot['platforms_map'] ) ? $snapshot['platforms_map'] : array();
        $platforms_list = array();
        if ( ! empty( $platforms_map ) ) {
            $registered_platforms = Helpers::get_registered_platform_labels();
            foreach ( $registered_platforms as $slug => $label ) {
                $normalized_slug = self::resolve_platform_slug( $label );
                if ( $normalized_slug !== '' && isset( $platforms_map[ $normalized_slug ] ) ) {
                    $platforms_list[ $normalized_slug ] = $platforms_map[ $normalized_slug ];
                }
            }
            foreach ( $platforms_map as $slug => $label ) {
                if ( ! isset( $platforms_list[ $slug ] ) ) {
                    $platforms_list[ $slug ] = $label;
                }
            }
            natcasesort( $platforms_list );
        }

        $platform_entries = array();
        foreach ( $platforms_list as $slug => $label ) {
            $platform_entries[] = array(
                'value' => $slug,
                'label' => $label,
            );
        }

        $availability_options = array(
            'available' => esc_html__( 'Disponible', 'notation-jlg' ),
            'upcoming'  => esc_html__( 'À venir', 'notation-jlg' ),
            'unknown'   => esc_html__( 'À confirmer', 'notation-jlg' ),
        );

        $years_meta_raw   = isset( $snapshot['years'] ) && is_array( $snapshot['years'] ) ? $snapshot['years'] : array();
        $year_buckets_raw = isset( $years_meta_raw['buckets'] ) && is_array( $years_meta_raw['buckets'] ) ? $years_meta_raw['buckets'] : array();
        $year_buckets     = array();
        foreach ( $year_buckets_raw as $bucket_year => $bucket_count ) {
            if ( is_int( $bucket_year ) ) {
                $year_key = $bucket_year;
            } elseif ( is_string( $bucket_year ) && ctype_digit( $bucket_year ) ) {
                $year_key = (int) $bucket_year;
            } else {
                $year_key = 0;
            }

            if ( $year_key <= 0 ) {
                continue;
            }

            $year_buckets[ $year_key ] = (int) $bucket_count;
        }

        $year_min = isset( $years_meta_raw['min'] ) && is_numeric( $years_meta_raw['min'] ) ? (int) $years_meta_raw['min'] : null;
        $year_max = isset( $years_meta_raw['max'] ) && is_numeric( $years_meta_raw['max'] ) ? (int) $years_meta_raw['max'] : null;

        if ( $year_min !== null && $year_min < 0 ) {
            $year_min = null;
        }
        if ( $year_max !== null && $year_max < 0 ) {
            $year_max = null;
        }

        $year_filter = self::normalize_year( $year_filter_raw, $year_buckets, $year_min, $year_max );

        $years_list = array();
        if ( ! empty( $year_buckets ) ) {
            $year_values = array_keys( $year_buckets );
            rsort( $year_values, SORT_NUMERIC );

            foreach ( $year_values as $year_value ) {
                $count = isset( $year_buckets[ $year_value ] ) ? (int) $year_buckets[ $year_value ] : 0;
                $label = (string) $year_value;

                if ( $count > 0 ) {
                    $label = sprintf(
                        /* translators: 1: Release year, 2: Number of games. */
                        _n( '%1$s – %2$d jeu', '%1$s – %2$d jeux', $count, 'notation-jlg' ),
                        $year_value,
                        $count
                    );
                }

                $years_list[] = array(
                    'value' => (string) $year_value,
                    'label' => $label,
                    'count' => $count,
                );
            }
        }

        $scores_list = array();
        if ( ! empty( $filters_enabled['score'] ) ) {
            $scores_list = self::build_score_filter_options( $score_max, $score_filter );
        }

        $scores_meta = array(
            'max'       => (int) round( is_numeric( $score_max ) ? (float) $score_max : Helpers::get_score_max() ),
            'precision' => self::get_score_filter_precision( $score_max ),
        );

        $search_tokens_map  = isset( $snapshot['search_tokens'] ) && is_array( $snapshot['search_tokens'] ) ? $snapshot['search_tokens'] : array();
        $search_suggestions = array();
        if ( ! empty( $search_tokens_map ) ) {
            arsort( $search_tokens_map, SORT_NUMERIC );
            $search_suggestions = array_slice( array_keys( $search_tokens_map ), 0, 10 );
        }

        $developer_suggestions = array();
        foreach ( $developers_list as $developer_entry ) {
            if ( isset( $developer_entry['label'] ) ) {
                $developer_suggestions[] = (string) $developer_entry['label'];
            }
        }
        $developer_suggestions = array_slice( array_values( array_unique( $developer_suggestions ) ), 0, 10 );

        $publisher_suggestions = array();
        foreach ( $publishers_list as $publisher_entry ) {
            if ( isset( $publisher_entry['label'] ) ) {
                $publisher_suggestions[] = (string) $publisher_entry['label'];
            }
        }
        $publisher_suggestions = array_slice( array_values( array_unique( $publisher_suggestions ) ), 0, 10 );

        $platform_suggestions = array();
        foreach ( $platform_entries as $platform_entry ) {
            if ( isset( $platform_entry['label'] ) ) {
                $platform_suggestions[] = (string) $platform_entry['label'];
            }
        }
        $platform_suggestions = array_slice( array_values( array_unique( $platform_suggestions ) ), 0, 10 );

        $category_filter_id   = 0;
        $category_filter_slug = '';
        if ( $category_filter !== '' ) {
            if ( ctype_digit( (string) $category_filter ) ) {
                $category_filter_id = (int) $category_filter;
            } else {
                $category_filter_slug = sanitize_title( $category_filter );
            }
        }

        $platform_filter_slug = $platform_filter !== '' ? self::resolve_platform_slug( $platform_filter ) : '';
        $letter_filter        = is_string( $letter_filter ) ? $letter_filter : '';
        $availability_filter  = is_string( $availability_filter ) ? $availability_filter : '';
        $year_filter          = is_string( $year_filter ) ? $year_filter : '';
        $search_filter        = is_string( $search_filter ) ? $search_filter : '';

        $category_filter_value = $category_filter_id > 0 ? (string) $category_filter_id : $category_filter_slug;
        $search_terms          = self::tokenize_search_terms( $search_filter );

        $query_filters = array(
            'letter'        => $letter_filter,
            'category_id'   => $category_filter_id,
            'category_slug' => $category_filter_slug,
            'platform_slug' => $platform_filter_slug,
            'developer_key' => $developer_filter_key,
            'publisher_key' => $publisher_filter_key,
            'availability'  => $availability_filter,
            'year'          => $year_filter,
            'score_min'     => $score_filter,
            'search_terms'  => $search_terms,
        );

        $requested_paged    = $paged;
        $query_cache_status = 'miss';
        $total_items        = 0;
        $total_pages        = 0;
        $query_args         = array();

        $cached_query = self::maybe_get_cached_query_result(
            $query_filters,
            $orderby,
            $order,
            $posts_per_page,
            $requested_paged
        );

        if ( is_array( $cached_query ) ) {
            $query_cache_status = 'hit';
            $cached_post_ids    = isset( $cached_query['post_ids'] ) ? (array) $cached_query['post_ids'] : array();
            $cached_post_ids    = array_values( array_filter( array_map( 'intval', $cached_post_ids ) ) );

            $total_items = isset( $cached_query['total_items'] ) ? (int) $cached_query['total_items'] : 0;
            if ( $total_items < 0 ) {
                $total_items = 0;
            }

            $total_pages = isset( $cached_query['total_pages'] ) ? (int) $cached_query['total_pages'] : 0;
            if ( $total_pages < 0 ) {
                $total_pages = 0;
            }

            $cached_paged = isset( $cached_query['adjusted_paged'] ) ? (int) $cached_query['adjusted_paged'] : $requested_paged;
            if ( $cached_paged < 1 ) {
                $cached_paged = 1;
            }
            $paged = $cached_paged;

            $query_args = array(
                'post_type'           => Helpers::get_allowed_post_types(),
                'post__in'            => $cached_post_ids,
                'orderby'             => 'post__in',
                'posts_per_page'      => count( $cached_post_ids ),
                'paged'               => 1,
                'ignore_sticky_posts' => true,
            );

            $query                = new \WP_Query( $query_args );
            $query->found_posts   = $total_items;
            $query->max_num_pages = $total_pages;
        } else {
            $query_args = self::build_query_args_from_filters(
                $query_filters,
                $orderby,
                $order,
                $posts_per_page,
                $paged
            );

            $query = new \WP_Query( $query_args );

            $total_items = isset( $query->found_posts ) ? (int) $query->found_posts : (int) $query->post_count;
            if ( $total_items < 0 ) {
                $total_items = 0;
            }

            $total_pages = isset( $query->max_num_pages ) ? (int) $query->max_num_pages : 0;
            if ( $total_pages < 1 && $total_items > 0 && $posts_per_page > 0 ) {
                $total_pages = (int) ceil( $total_items / $posts_per_page );
            }
            if ( $total_pages < 0 ) {
                $total_pages = 0;
            }
            $adjusted_paged = $paged;
            if ( $total_pages > 0 && $adjusted_paged > $total_pages ) {
                $adjusted_paged = $total_pages;
            }
            if ( $adjusted_paged < 1 ) {
                $adjusted_paged = 1;
            }

            if ( $adjusted_paged !== $query_args['paged'] ) {
                $query_args['paged'] = $adjusted_paged;
                $query               = new \WP_Query( $query_args );
                $total_items         = isset( $query->found_posts ) ? (int) $query->found_posts : (int) $query->post_count;
                if ( $total_items < 0 ) {
                    $total_items = 0;
                }
                $total_pages = isset( $query->max_num_pages ) ? (int) $query->max_num_pages : $total_pages;
                if ( $total_pages < 1 && $total_items > 0 && $posts_per_page > 0 ) {
                    $total_pages = (int) ceil( $total_items / $posts_per_page );
                }
            }

            $paged = $adjusted_paged;
        }

        $no_results_message = '<p>' . esc_html__( 'Aucun jeu ne correspond à vos filtres actuels.', 'notation-jlg' ) . '</p>';

        $games = array();
        if ( $query->have_posts() ) {
            foreach ( $query->posts as $post ) {
                $post_id   = (int) $post->ID;
                $post_info = isset( $snapshot['posts'][ $post_id ] ) ? $snapshot['posts'][ $post_id ] : array();

                $title = Helpers::get_game_title( $post_id );
                if ( $title === '' ) {
                    $title = get_the_title( $post_id );
                }

                $score_data    = Helpers::get_resolved_average_score( $post_id );
                $score_value   = isset( $score_data['value'] ) ? $score_data['value'] : null;
                $has_score     = is_numeric( $score_value );
                $score_display = isset( $score_data['formatted'] ) && $score_data['formatted'] !== ''
                    ? $score_data['formatted']
                    : esc_html__( 'N/A', 'notation-jlg' );
                $score_color   = $score_value !== null
                    ? Helpers::calculate_color_from_note( $score_value, $options )
                    : ( $options['color_mid'] ?? '#f97316' );

                $cover_meta = get_post_meta( $post_id, '_jlg_cover_image_url', true );
                $cover_url  = '';
                if ( is_string( $cover_meta ) && $cover_meta !== '' ) {
                    $cover_url = esc_url_raw( $cover_meta );
                }
                if ( $cover_url === '' ) {
                    $thumbnail = get_the_post_thumbnail_url( $post_id, 'large' );
                    if ( $thumbnail ) {
                        $cover_url = $thumbnail;
                    }
                }

                $release_iso     = isset( $post_info['release_iso'] ) ? $post_info['release_iso'] : '';
                $release_display = '';
                if ( $release_iso !== '' ) {
                    $release_display = date_i18n( get_option( 'date_format' ), strtotime( $release_iso . ' 00:00:00' ) );
                }
                $availability_data   = self::determine_availability( $release_iso );
                $availability_status = isset( $post_info['availability'] ) ? $post_info['availability'] : $availability_data['status'];

                $developer = isset( $post_info['developer'] ) ? $post_info['developer'] : '';
                $publisher = isset( $post_info['publisher'] ) ? $post_info['publisher'] : '';

                $platform_labels = isset( $post_info['platform_labels'] ) ? $post_info['platform_labels'] : array();
                $platform_slugs  = isset( $post_info['platform_slugs'] ) ? $post_info['platform_slugs'] : array();

                $category_ids   = isset( $post_info['category_ids'] ) ? $post_info['category_ids'] : array();
                $category_slugs = isset( $post_info['category_slugs'] ) ? $post_info['category_slugs'] : array();
                $primary_genre  = isset( $post_info['primary_genre'] ) ? $post_info['primary_genre'] : '';

                $excerpt = get_the_excerpt( $post_id );
                if ( ! is_string( $excerpt ) || $excerpt === '' ) {
                    $excerpt = wp_trim_words( wp_strip_all_tags( $post->post_content ), 24, '…' );
                }

                $letter = isset( $post_info['letter'] ) && $post_info['letter'] !== '' ? $post_info['letter'] : self::normalize_letter( $title );

                $games[] = array(
                    'post_id'            => $post_id,
                    'title'              => $title,
                    'permalink'          => get_permalink( $post_id ),
                    'score_value'        => $score_value,
                    'has_score'          => $has_score,
                    'score_display'      => $score_display,
                    'score_color'        => $score_color,
                    'cover_url'          => $cover_url,
                    'release_date'       => $release_iso,
                    'release_display'    => $release_display,
                    'developer'          => $developer,
                    'publisher'          => $publisher,
                    'platforms'          => $platform_labels,
                    'platform_slugs'     => $platform_slugs,
                    'category_ids'       => $category_ids,
                    'category_slugs'     => $category_slugs,
                    'genre'              => $primary_genre,
                    'excerpt'            => $excerpt,
                    'letter'             => $letter,
                    'availability'       => $availability_status,
                    'availability_label' => $availability_data['label'],
                    'timestamp'          => get_post_time( 'U', true, $post ),
                    'search_index'       => isset( $post_info['search_index'] ) ? $post_info['search_index'] : '',
                    'search_haystack'    => isset( $post_info['search_index'] ) ? $post_info['search_index'] : '',
                    'popularity'         => isset( $post_info['popularity'] ) ? (int) $post_info['popularity'] : 0,
                );
            }
            wp_reset_postdata();
        }

        if ( $query_cache_status === 'miss' ) {
            $post_ids_for_cache = array();

            if ( isset( $query->posts ) && is_array( $query->posts ) ) {
                foreach ( $query->posts as $post_object ) {
                    if ( isset( $post_object->ID ) ) {
                        $post_ids_for_cache[] = (int) $post_object->ID;
                    }
                }
            }

            self::store_query_result_cache(
                $query_filters,
                $orderby,
                $order,
                $posts_per_page,
                $requested_paged,
                array(
                    'post_ids'       => $post_ids_for_cache,
                    'total_items'    => $total_items,
                    'total_pages'    => $total_pages,
                    'adjusted_paged' => $paged,
                )
            );
        }

        if ( empty( $games ) ) {
            $total_items = 0;
            $total_pages = 0;
            $paged       = 1;
        }

        $message = $total_items === 0 ? $no_results_message : '';

        $config_payload = array(
            'atts'        => array(
                'id'             => $atts['id'],
                'posts_per_page' => $posts_per_page,
                'columns'        => $columns,
                'score_position' => $score_position,
                'filters'        => implode( ',', array_keys( array_filter( $filters_enabled ) ) ),
                'categorie'      => $atts['categorie'],
                'plateforme'     => $atts['plateforme'],
                'lettre'         => $atts['lettre'],
                'developpeur'    => $atts['developpeur'],
                'editeur'        => $atts['editeur'],
                'annee'          => $atts['annee'],
                'note_min'       => $atts['note_min'],
                'recherche'      => $atts['recherche'],
            ),
            'state'       => array(
                'orderby'      => $orderby,
                'order'        => $order,
                'letter'       => $letter_filter,
                'category'     => $category_filter_value,
                'platform'     => $platform_filter_slug,
                'developer'    => $developer_filter,
                'publisher'    => $publisher_filter,
                'availability' => $availability_filter,
                'year'         => $year_filter,
                'score'        => $score_filter,
                'search'       => $search_filter,
                'paged'        => $paged,
                'total_items'  => $total_items,
                'total_pages'  => $total_pages,
            ),
            'request'     => array(
                'prefix' => $request_prefix,
                'keys'   => $request_keys,
            ),
            'cache'       => array(
                'query' => $query_cache_status,
            ),
            'meta'        => array(
                'years'  => array(
                    'min'     => $year_min,
                    'max'     => $year_max,
                    'buckets' => $year_buckets,
                ),
                'scores' => $scores_meta,
            ),
            'sorts'       => array(
                'options' => self::get_sort_options(),
                'active'  => array(
                    'orderby' => $orderby,
                    'order'   => $order,
                ),
            ),
            'suggestions' => array(
                'search'     => $search_suggestions,
                'developers' => $developer_suggestions,
                'publishers' => $publisher_suggestions,
                'platforms'  => $platform_suggestions,
            ),
        );

        return array(
            'atts'                 => array_merge(
                $atts,
                array(
                    'posts_per_page' => $posts_per_page,
                    'columns'        => $columns,
                    'score_position' => $score_position,
                )
            ),
            'games'                => array_values( $games ),
            'letters'              => $letters,
            'filters_enabled'      => $filters_enabled,
            'current_filters'      => array(
                'letter'       => $letter_filter,
                'category'     => $category_filter_value,
                'platform'     => $platform_filter_slug,
                'developer'    => $developer_filter,
                'publisher'    => $publisher_filter,
                'availability' => $availability_filter,
                'year'         => $year_filter,
                'score'        => $score_filter,
                'search'       => $search_filter,
            ),
            'sort_options'         => self::get_sort_options(),
            'sort_key'             => $orderby,
            'sort_order'           => $order,
            'pagination'           => array(
                'current' => $paged,
                'total'   => $total_pages,
            ),
            'categories_list'      => $categories_list,
            'developers_list'      => $developers_list,
            'publishers_list'      => $publishers_list,
            'platforms_list'       => $platform_entries,
            'availability_options' => $availability_options,
            'scores_list'          => $scores_list,
            'total_items'          => $total_items,
            'message'              => $message,
            'config_payload'       => $config_payload,
            'request_prefix'       => $request_prefix,
            'request_keys'         => $request_keys,
            'score_position'       => $score_position,
            'years_list'           => $years_list,
            'years_meta'           => array(
                'min'     => $year_min,
                'max'     => $year_max,
                'buckets' => $year_buckets,
            ),
            'scores_meta'          => $scores_meta,
            'search_suggestions'   => $search_suggestions,
            'cache_status'         => array(
                'query' => $query_cache_status,
            ),
        );
    }

    private static function normalize_container_id( $raw_id ) {
        $raw_id    = is_string( $raw_id ) ? $raw_id : '';
        $sanitized = sanitize_html_class( $raw_id );

        if ( $sanitized === '' ) {
            $sanitized = 'jlg-game-explorer-' . uniqid();
        }

        return $sanitized;
    }

    private static function get_request_prefix( $container_id ) {
        $container_id = is_string( $container_id ) ? $container_id : '';
        $prefix       = sanitize_title( $container_id );

        if ( $prefix === '' ) {
            $prefix = 'jlg-game-explorer';
        }

        return $prefix;
    }

    private static function build_request_keys( $prefix ) {
        $keys = array();

        foreach ( self::REQUEST_KEYS as $key ) {
            $keys[ $key ] = $prefix !== '' ? $key . '__' . $prefix : $key;
        }

        return $keys;
    }

    private static function extract_request_params( $request, array $request_keys ) {
        if ( ! is_array( $request ) ) {
            return array();
        }

        if ( function_exists( 'wp_unslash' ) ) {
            $request = wp_unslash( $request );
        }

        $normalized = array();

        foreach ( $request_keys as $key => $namespaced_key ) {
            if ( array_key_exists( $namespaced_key, $request ) ) {
                $normalized[ $key ] = $request[ $namespaced_key ];
                continue;
            }

            if ( array_key_exists( $key, $request ) ) {
                $normalized[ $key ] = $request[ $key ];
            }
        }

        return $normalized;
    }
}

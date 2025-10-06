<?php
/**
 * Classe Utilitaire (Helper) - Version 5.0
 * Contient toutes les fonctions logiques réutilisables du plugin.
 */

namespace JLG\Notation;

use JLG\Notation\Utils\Validator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helpers {

    public const SCORE_SCALE_MIGRATION_OPTION = 'jlg_score_scale_migration';
    public const SCORE_SCALE_QUEUE_OPTION     = 'jlg_score_scale_queue';
    public const SCORE_SCALE_EVENT_HOOK       = 'jlg_process_score_scale_migration';

    private const GAME_EXPLORER_DEFAULT_SCORE_POSITION = 'bottom-right';
    private const GAME_EXPLORER_ALLOWED_FILTERS        = array( 'letter', 'category', 'platform', 'developer', 'publisher', 'availability', 'year', 'search' );
    private const GAME_EXPLORER_DEFAULT_FILTERS        = array( 'letter', 'category', 'platform', 'developer', 'publisher', 'availability', 'year', 'search' );
    private const PLATFORM_TAG_OPTION                  = 'jlg_platform_tag_map';
    private const LEGACY_CATEGORY_SUFFIXES             = array( 'cat1', 'cat2', 'cat3', 'cat4', 'cat5', 'cat6' );

    private static $option_name                   = 'notation_jlg_settings';
    private static $options_cache                 = null;
    private static $default_settings_cache        = null;
    private static $category_definition_cache     = null;
    private static $rating_meta_keys_cache        = null;
    private static $game_explorer_score_positions = array(
        'top-left',
        'top-right',
        'middle-left',
        'middle-right',
        'bottom-left',
        'bottom-right',
    );

    private static function get_rating_meta_keys() {
        if ( is_array( self::$rating_meta_keys_cache ) ) {
            return self::$rating_meta_keys_cache;
        }

        $meta_keys = array();

        foreach ( self::get_rating_category_definitions() as $definition ) {
            if ( ! empty( $definition['meta_key'] ) ) {
                $meta_keys[] = (string) $definition['meta_key'];
            }

            if ( ! empty( $definition['legacy_meta_keys'] ) && is_array( $definition['legacy_meta_keys'] ) ) {
                foreach ( $definition['legacy_meta_keys'] as $legacy_meta_key ) {
                    if ( $legacy_meta_key !== '' ) {
                        $meta_keys[] = (string) $legacy_meta_key;
                    }
                }
            }
        }

        self::$rating_meta_keys_cache = array_values( array_unique( $meta_keys ) );

        return self::$rating_meta_keys_cache;
    }

    public static function get_review_video_embed_data( $video_url, $provider = '' ) {
        $video_url = is_string( $video_url ) ? trim( $video_url ) : '';
        $provider  = Validator::sanitize_video_provider( $provider );

        if ( $video_url === '' ) {
            return self::get_empty_video_embed_data();
        }

        $sanitized_url = esc_url_raw( $video_url );

        if ( $sanitized_url === '' ) {
            return self::get_empty_video_embed_data( __( 'Impossible de préparer le lecteur vidéo pour cette URL.', 'notation-jlg' ) );
        }

        $detected_provider = Validator::detect_video_provider_from_url( $sanitized_url );
        if ( $provider === '' ) {
            $provider = $detected_provider;
        }

        if ( $provider === '' ) {
            return self::get_empty_video_embed_data( __( 'Impossible d’identifier le fournisseur vidéo pour cette URL.', 'notation-jlg' ) );
        }

        $provider_label = Validator::get_video_provider_label( $provider );
        $iframe_src     = '';
        $fallback       = self::get_video_fallback_message( $provider_label );

        if ( $provider === 'youtube' ) {
            $video_id = self::extract_youtube_video_id( $sanitized_url );

            if ( $video_id === '' ) {
                return self::get_empty_video_embed_data( $fallback );
            }

            $iframe_src = add_query_arg(
                array(
                    'rel'            => 0,
                    'modestbranding' => 1,
                    'enablejsapi'    => 1,
                ),
                'https://www.youtube-nocookie.com/embed/' . rawurlencode( $video_id )
            );
        } elseif ( $provider === 'vimeo' ) {
            $video_id = self::extract_vimeo_video_id( $sanitized_url );

            if ( $video_id === '' ) {
                return self::get_empty_video_embed_data( $fallback );
            }

            $iframe_src = add_query_arg(
                array(
                    'dnt' => 1,
                ),
                'https://player.vimeo.com/video/' . rawurlencode( $video_id )
            );
        } elseif ( $provider === 'twitch' ) {
            $twitch_params = self::extract_twitch_embed_parameters( $sanitized_url );

            if ( empty( $twitch_params ) ) {
                return self::get_empty_video_embed_data( $fallback );
            }

            $iframe_src = self::build_twitch_iframe_src( $twitch_params );
        } elseif ( $provider === 'dailymotion' ) {
            $video_id = self::extract_dailymotion_video_id( $sanitized_url );

            if ( $video_id === '' ) {
                return self::get_empty_video_embed_data( $fallback );
            }

            $iframe_src = add_query_arg(
                array(
                    'autoplay' => '0',
                ),
                'https://www.dailymotion.com/embed/video/' . rawurlencode( $video_id )
            );
        } else {
            return self::get_empty_video_embed_data( $fallback );
        }

        if ( ! is_string( $iframe_src ) || $iframe_src === '' ) {
            return self::get_empty_video_embed_data( $fallback );
        }

        $title = sprintf(
            /* translators: %s: video provider name. */
            __( 'Lecteur vidéo %s', 'notation-jlg' ),
            $provider_label !== '' ? $provider_label : strtoupper( $provider )
        );

        return array(
            'has_embed'              => true,
            'provider'               => $provider,
            'provider_label'         => $provider_label,
            'iframe_src'             => esc_url( $iframe_src ),
            'iframe_title'           => $title,
            'iframe_allow'           => 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share',
            'iframe_allowfullscreen' => true,
            'iframe_loading'         => 'lazy',
            'iframe_referrerpolicy'  => 'strict-origin-when-cross-origin',
            'fallback_message'       => '',
            'original_url'           => esc_url( $sanitized_url ),
        );
    }

    private static function get_video_fallback_message( $provider_label = '' ) {
        if ( $provider_label === '' ) {
            return __( 'Impossible de préparer le lecteur vidéo pour cette URL.', 'notation-jlg' );
        }

        return sprintf(
            /* translators: %s: video provider name. */
            __( 'Impossible de préparer le lecteur vidéo pour %s.', 'notation-jlg' ),
            $provider_label
        );
    }

    private static function get_empty_video_embed_data( $message = '' ) {
        return array(
            'has_embed'              => false,
            'provider'               => '',
            'provider_label'         => '',
            'iframe_src'             => '',
            'iframe_title'           => '',
            'iframe_allow'           => '',
            'iframe_allowfullscreen' => false,
            'iframe_loading'         => 'lazy',
            'iframe_referrerpolicy'  => 'strict-origin-when-cross-origin',
            'fallback_message'       => is_string( $message ) ? $message : '',
            'original_url'           => '',
        );
    }

    private static function extract_youtube_video_id( $url ) {
        $parts = wp_parse_url( $url );
        if ( ! is_array( $parts ) ) {
            return '';
        }

        $candidate = '';

        if ( isset( $parts['query'] ) ) {
            parse_str( $parts['query'], $query_vars );

            if ( isset( $query_vars['v'] ) && is_string( $query_vars['v'] ) ) {
                $candidate = $query_vars['v'];
            }
        }

        if ( $candidate === '' && isset( $parts['path'] ) ) {
            $path     = trim( $parts['path'], '/' );
            $segments = explode( '/', $path );

            if ( isset( $segments[0] ) ) {
                if ( $segments[0] === 'embed' && isset( $segments[1] ) ) {
                    $candidate = $segments[1];
                } elseif ( $segments[0] === 'shorts' && isset( $segments[1] ) ) {
                    $candidate = $segments[1];
                } else {
                    $candidate = $segments[ count( $segments ) - 1 ];
                }
            }
        }

        if ( ! is_string( $candidate ) ) {
            return '';
        }

        $candidate = trim( $candidate );

        return preg_match( '/^[A-Za-z0-9_-]{6,}$/', $candidate ) ? $candidate : '';
    }

    private static function extract_vimeo_video_id( $url ) {
        $parts = wp_parse_url( $url );
        if ( ! is_array( $parts ) || ! isset( $parts['path'] ) ) {
            return '';
        }

        $path     = trim( $parts['path'], '/' );
        $segments = array_values( array_filter( explode( '/', $path ) ) );

        if ( empty( $segments ) ) {
            return '';
        }

        $candidate = end( $segments );
        $candidate = is_string( $candidate ) ? trim( $candidate ) : '';

        return preg_match( '/^[0-9]{6,}$/', $candidate ) ? $candidate : '';
    }

    private static function extract_twitch_embed_parameters( $url ) {
        $parts = wp_parse_url( $url );

        if ( ! is_array( $parts ) ) {
            return array();
        }

        $path     = isset( $parts['path'] ) ? trim( $parts['path'], '/' ) : '';
        $segments = $path !== '' ? array_values( array_filter( explode( '/', $path ) ) ) : array();
        $host     = isset( $parts['host'] ) ? strtolower( (string) $parts['host'] ) : '';
        $query    = array();

        if ( isset( $parts['query'] ) ) {
            parse_str( (string) $parts['query'], $query );
        }

        if ( isset( $query['video'] ) && is_string( $query['video'] ) ) {
            $candidate = preg_replace( '/^v/i', '', trim( $query['video'] ) );

            if ( is_string( $candidate ) && preg_match( '/^[0-9]{6,}$/', $candidate ) ) {
                $parameters = array(
                    'type' => 'video',
                    'id'   => $candidate,
                );

                if ( isset( $query['collection'] ) && is_string( $query['collection'] ) ) {
                    $collection = preg_replace( '/[^A-Za-z0-9_-]/', '', $query['collection'] );
                    if ( $collection !== '' ) {
                        $parameters['collection'] = $collection;
                    }
                }

                return $parameters;
            }
        }

        $video_index = array_search( 'videos', $segments, true );
        if ( false !== $video_index && isset( $segments[ $video_index + 1 ] ) ) {
            $candidate = preg_replace( '/^v/i', '', trim( (string) $segments[ $video_index + 1 ] ) );

            if ( preg_match( '/^[0-9]{6,}$/', $candidate ) ) {
                return array(
                    'type' => 'video',
                    'id'   => $candidate,
                );
            }
        }

        if ( isset( $query['clip'] ) && is_string( $query['clip'] ) ) {
            $candidate = trim( $query['clip'] );

            if ( preg_match( '/^[A-Za-z0-9_-]{4,100}$/', $candidate ) ) {
                return array(
                    'type' => 'clip',
                    'id'   => $candidate,
                );
            }
        }

        if ( $host === 'clips.twitch.tv' && isset( $segments[0] ) ) {
            $candidate = trim( (string) $segments[0] );

            if ( preg_match( '/^[A-Za-z0-9_-]{4,100}$/', $candidate ) ) {
                return array(
                    'type' => 'clip',
                    'id'   => $candidate,
                );
            }
        }

        $clip_index = array_search( 'clip', $segments, true );
        if ( false !== $clip_index && isset( $segments[ $clip_index + 1 ] ) ) {
            $candidate = trim( (string) $segments[ $clip_index + 1 ] );

            if ( preg_match( '/^[A-Za-z0-9_-]{4,100}$/', $candidate ) ) {
                return array(
                    'type' => 'clip',
                    'id'   => $candidate,
                );
            }
        }

        if ( isset( $query['channel'] ) && is_string( $query['channel'] ) ) {
            $candidate = trim( $query['channel'] );

            if ( preg_match( '/^[A-Za-z0-9_]{3,30}$/', $candidate ) ) {
                return array(
                    'type' => 'channel',
                    'id'   => strtolower( $candidate ),
                );
            }
        }

        if ( count( $segments ) === 1 && isset( $segments[0] ) ) {
            $candidate = trim( (string) $segments[0] );

            if ( preg_match( '/^[A-Za-z0-9_]{3,30}$/', $candidate ) ) {
                return array(
                    'type' => 'channel',
                    'id'   => strtolower( $candidate ),
                );
            }
        }

        return array();
    }

    private static function build_twitch_iframe_src( array $parameters ) {
        if ( empty( $parameters['type'] ) ) {
            return '';
        }

        $parent = self::get_twitch_parent_domain();

        if ( $parent === '' ) {
            return '';
        }

        if ( $parameters['type'] === 'clip' ) {
            return add_query_arg(
                array(
                    'clip'     => $parameters['id'],
                    'autoplay' => 'false',
                    'parent'   => $parent,
                ),
                'https://clips.twitch.tv/embed'
            );
        }

        $query = array(
            'autoplay' => 'false',
            'muted'    => 'false',
            'parent'   => $parent,
        );

        if ( $parameters['type'] === 'video' ) {
            $query['video'] = 'v' . ltrim( (string) $parameters['id'], 'vV' );

            if ( ! empty( $parameters['collection'] ) && is_string( $parameters['collection'] ) ) {
                $query['collection'] = $parameters['collection'];
            }
        } elseif ( $parameters['type'] === 'channel' ) {
            $query['channel'] = $parameters['id'];
        } else {
            return '';
        }

        return add_query_arg( $query, 'https://player.twitch.tv/' );
    }

    private static function get_twitch_parent_domain() {
        $home_url = home_url();
        $host     = wp_parse_url( $home_url, PHP_URL_HOST );

        if ( ! is_string( $host ) || $host === '' ) {
            $parts = wp_parse_url( $home_url );
            if ( is_array( $parts ) && isset( $parts['host'] ) ) {
                $host = $parts['host'];
            }
        }

        if ( ! is_string( $host ) ) {
            return '';
        }

        $host = trim( $host );

        return $host !== '' ? $host : '';
    }

    private static function extract_dailymotion_video_id( $url ) {
        $parts = wp_parse_url( $url );

        if ( ! is_array( $parts ) ) {
            return '';
        }

        $path     = isset( $parts['path'] ) ? trim( $parts['path'], '/' ) : '';
        $segments = $path !== '' ? array_values( array_filter( explode( '/', $path ) ) ) : array();

        if ( empty( $segments ) ) {
            return '';
        }

        $candidate = end( $segments );

        if ( is_string( $candidate ) && strpos( $candidate, '_' ) !== false ) {
            $candidate = substr( $candidate, 0, strpos( $candidate, '_' ) );
        }

        if ( isset( $segments[0] ) && $segments[0] === 'video' && isset( $segments[1] ) ) {
            $candidate = $segments[1];
        }

        $candidate = is_string( $candidate ) ? trim( $candidate ) : '';

        if ( $candidate === '' && isset( $parts['query'] ) ) {
            parse_str( (string) $parts['query'], $query_vars );

            if ( isset( $query_vars['video'] ) && is_string( $query_vars['video'] ) ) {
                $candidate = trim( $query_vars['video'] );
            }
        }

        if ( $candidate === '' ) {
            return '';
        }

        $candidate = preg_replace( '/[^A-Za-z0-9]/', '', $candidate );

        return preg_match( '/^[A-Za-z0-9]{6,}$/', $candidate ) ? $candidate : '';
    }

    private static function get_theme_defaults() {
        return array(
            'light' => array(
                'bg_color'             => '#ffffff',
                'bg_color_secondary'   => '#f9fafb',
                'border_color'         => '#e5e7eb',
                'text_color'           => '#111827',
                'text_color_secondary' => '#6b7280',
                'tagline_bg_color'     => '#f3f4f6',
                'tagline_text_color'   => '#4b5563',
            ),
            'dark'  => array(
                'bg_color'             => '#18181b',
                'bg_color_secondary'   => '#27272a',
                'border_color'         => '#3f3f46',
                'text_color'           => '#fafafa',
                'text_color_secondary' => '#a1a1aa',
                'tagline_bg_color'     => '#1f2937',
                'tagline_text_color'   => '#d1d5db',
            ),
        );
    }

    private static function get_default_platform_definitions() {
        return array(
            'pc'              => array(
				'name'  => 'PC',
				'order' => 1,
			),
            'playstation-5'   => array(
				'name'  => 'PlayStation 5',
				'order' => 2,
			),
            'xbox-series-x'   => array(
				'name'  => 'Xbox Series S/X',
				'order' => 3,
			),
            'nintendo-switch' => array(
				'name'  => 'Nintendo Switch',
				'order' => 4,
			),
            'playstation-4'   => array(
				'name'  => 'PlayStation 4',
				'order' => 5,
			),
            'xbox-one'        => array(
				'name'  => 'Xbox One',
				'order' => 6,
			),
            'steam-deck'      => array(
				'name'  => 'Steam Deck',
				'order' => 7,
			),
        );
    }

    private static function get_default_category_definitions() {
        return array(
            array(
                'id'         => 'gameplay',
                'label'      => 'Gameplay',
                'legacy_ids' => array( 'cat1' ),
                'position'   => 1,
                'weight'     => 1.0,
            ),
            array(
                'id'         => 'graphismes',
                'label'      => 'Graphismes',
                'legacy_ids' => array( 'cat2' ),
                'position'   => 2,
                'weight'     => 1.0,
            ),
            array(
                'id'         => 'bande-son',
                'label'      => 'Bande-son',
                'legacy_ids' => array( 'cat3' ),
                'position'   => 3,
                'weight'     => 1.0,
            ),
            array(
                'id'         => 'duree-de-vie',
                'label'      => 'Durée de vie',
                'legacy_ids' => array( 'cat4' ),
                'position'   => 4,
                'weight'     => 1.0,
            ),
            array(
                'id'         => 'scenario',
                'label'      => 'Scénario',
                'legacy_ids' => array( 'cat5' ),
                'position'   => 5,
                'weight'     => 1.0,
            ),
            array(
                'id'         => 'originalite',
                'label'      => 'Originalité',
                'legacy_ids' => array( 'cat6' ),
                'position'   => 6,
                'weight'     => 1.0,
            ),
        );
    }

    public static function normalize_category_weight( $weight, $fallback_weight = 1.0 ) {
        if ( is_array( $weight ) ) {
            return (float) $fallback_weight;
        }

        if ( is_string( $weight ) ) {
            $weight = str_replace( ',', '.', $weight );
            $weight = trim( $weight );
        }

        if ( $weight === '' || $weight === null ) {
            $weight = $fallback_weight;
        }

        if ( ! is_numeric( $weight ) ) {
            return (float) $fallback_weight;
        }

        $normalized = (float) $weight;

        if ( $normalized < 0 ) {
            $normalized = 0.0;
        }

        return round( $normalized, 3 );
    }

    private static function prepare_category_definitions( array $categories ) {
        $prepared  = array();
        $used_ids  = array();
        $fallbacks = self::LEGACY_CATEGORY_SUFFIXES;

        foreach ( array_values( $categories ) as $index => $category ) {
            $label       = '';
            $id          = '';
            $legacy_ids  = array();
            $original_id = '';
            $position    = $index + 1;
            $weight      = 1.0;

            if ( is_array( $category ) ) {
                if ( isset( $category['label'] ) ) {
                    $label = sanitize_text_field( $category['label'] );
                }

                if ( isset( $category['id'] ) ) {
                    $id = sanitize_key( $category['id'] );
                }

                if ( isset( $category['legacy_ids'] ) && is_array( $category['legacy_ids'] ) ) {
                    foreach ( $category['legacy_ids'] as $legacy_id ) {
                        $sanitized_legacy = sanitize_key( $legacy_id );
                        if ( $sanitized_legacy !== '' ) {
                            $legacy_ids[] = $sanitized_legacy;
                        }
                    }
                }

                if ( isset( $category['original_id'] ) ) {
                    $original_id = sanitize_key( $category['original_id'] );
                }

                if ( isset( $category['position'] ) ) {
                    $position_candidate = is_numeric( $category['position'] ) ? (int) $category['position'] : $position;
                    if ( $position_candidate > 0 ) {
                        $position = $position_candidate;
                    }
                }

                if ( isset( $category['weight'] ) ) {
                    $weight = self::normalize_category_weight( $category['weight'], 1.0 );
                }
            } elseif ( is_string( $category ) ) {
                $label = sanitize_text_field( $category );
            }

            if ( $label === '' ) {
                $label = sprintf( __( 'Catégorie %d', 'notation-jlg' ), $index + 1 );
            }

            if ( $id === '' ) {
                $id = sanitize_key( sanitize_title( $label ) );
            }

            if ( $id === '' ) {
                $id = isset( $fallbacks[ $index ] ) ? $fallbacks[ $index ] : 'cat' . ( $index + 1 );
            }

            $base_id = $id;
            $suffix  = 2;
            while ( in_array( $id, $used_ids, true ) ) {
                $id = $base_id . '-' . $suffix;
                ++$suffix;
            }

            if ( $original_id !== '' && $original_id !== $id ) {
                $legacy_ids[] = $original_id;
            }

            if ( empty( $legacy_ids ) && isset( $fallbacks[ $index ] ) ) {
                $legacy_ids[] = $fallbacks[ $index ];
            }

            $legacy_ids       = array_values( array_unique( array_filter( $legacy_ids ) ) );
            $legacy_meta_keys = array();

            foreach ( $legacy_ids as $legacy_id ) {
                $legacy_meta_keys[] = '_note_' . $legacy_id;
            }

            $prepared[] = array(
                'id'               => $id,
                'label'            => $label,
                'legacy_ids'       => $legacy_ids,
                'position'         => $position,
                'meta_key'         => '_note_' . $id,
                'legacy_meta_keys' => array_values( array_unique( $legacy_meta_keys ) ),
                'weight'           => $weight,
            );

            $used_ids[] = $id;
        }

        if ( empty( $prepared ) ) {
            return $prepared;
        }

        usort(
            $prepared,
            static function ( $a, $b ) {
                $a_position = isset( $a['position'] ) ? (int) $a['position'] : 0;
                $b_position = isset( $b['position'] ) ? (int) $b['position'] : 0;

                if ( $a_position === $b_position ) {
                    $a_label = isset( $a['label'] ) ? (string) $a['label'] : '';
                    $b_label = isset( $b['label'] ) ? (string) $b['label'] : '';

                    return strnatcasecmp( $a_label, $b_label );
                }

                return ( $a_position < $b_position ) ? -1 : 1;
            }
        );

        foreach ( $prepared as $index => &$definition ) {
            $definition['position'] = $index + 1;
        }
        unset( $definition );

        return $prepared;
    }

    public static function get_rating_category_definitions() {
        if ( is_array( self::$category_definition_cache ) ) {
            return self::$category_definition_cache;
        }

        $options        = self::get_plugin_options();
        $raw_categories = array();

        if ( isset( $options['rating_categories'] ) && is_array( $options['rating_categories'] ) ) {
            $raw_categories = $options['rating_categories'];
        }

        if ( empty( $raw_categories ) ) {
            $raw_categories = self::get_default_category_definitions();
        }

        $prepared = self::prepare_category_definitions( $raw_categories );

        if ( empty( $prepared ) ) {
            $prepared = self::prepare_category_definitions( self::get_default_category_definitions() );
        }

        self::$category_definition_cache = $prepared;

        return self::$category_definition_cache;
    }

    public static function get_rating_categories() {
        $categories = array();

        foreach ( self::get_rating_category_definitions() as $definition ) {
            $categories[ $definition['id'] ] = $definition['label'];
        }

        return $categories;
    }

    public static function resolve_category_meta_value( $post_id, array $definition, $as_float = true ) {
        $post_id = (int) $post_id;

        if ( $post_id <= 0 ) {
            return $as_float ? null : '';
        }

        $meta_key = isset( $definition['meta_key'] ) ? (string) $definition['meta_key'] : '';

        if ( $meta_key === '' ) {
            return $as_float ? null : '';
        }

        $raw_value = get_post_meta( $post_id, $meta_key, true );
        $numeric   = self::normalize_score_candidate( $raw_value );

        if ( $numeric !== null ) {
            return $as_float ? $numeric : $raw_value;
        }

        if ( isset( $definition['legacy_meta_keys'] ) && is_array( $definition['legacy_meta_keys'] ) ) {
            foreach ( $definition['legacy_meta_keys'] as $legacy_meta_key ) {
                $legacy_value  = get_post_meta( $post_id, $legacy_meta_key, true );
                $legacy_number = self::normalize_score_candidate( $legacy_value );

                if ( $legacy_number !== null ) {
                    return $as_float ? $legacy_number : $legacy_value;
                }
            }
        }

        return $as_float ? null : '';
    }

    public static function get_post_category_scores( $post_id ) {
        $scores = array();

        foreach ( self::get_rating_category_definitions() as $definition ) {
            $numeric = self::resolve_category_meta_value( $post_id, $definition, true );

            if ( $numeric !== null ) {
                $category_id = isset( $definition['id'] ) ? (string) $definition['id'] : '';
                $weight      = isset( $definition['weight'] )
                    ? self::normalize_category_weight( $definition['weight'], 1.0 )
                    : 1.0;

                if ( $category_id === '' ) {
                    continue;
                }

                $scores[ $category_id ] = array(
                    'score'  => round( (float) $numeric, 1 ),
                    'weight' => $weight,
                );
            }
        }

        return $scores;
    }

    public static function get_category_scores_for_display( $post_id ) {
        $scores      = self::get_post_category_scores( $post_id );
        $definitions = self::get_rating_category_definitions();
        $display     = array();

        foreach ( $definitions as $definition ) {
            $category_id = $definition['id'];

            if ( ! array_key_exists( $category_id, $scores ) ) {
                continue;
            }

            $score_entry = $scores[ $category_id ];

            if ( ! is_array( $score_entry ) || ! isset( $score_entry['score'] ) ) {
                continue;
            }

            $weight = isset( $score_entry['weight'] )
                ? self::normalize_category_weight( $score_entry['weight'], 1.0 )
                : 1.0;

            $display[] = array(
                'id'       => $category_id,
                'label'    => $definition['label'],
                'score'    => $score_entry['score'],
                'weight'   => $weight,
                'meta_key' => $definition['meta_key'],
            );
        }

        return $display;
    }

    private static function normalize_score_candidate( $value ) {
        if ( is_array( $value ) ) {
            return null;
        }

        if ( is_string( $value ) ) {
            $value = trim( $value );
        }

        if ( $value === '' || $value === null ) {
            return null;
        }

        if ( is_string( $value ) && strpos( $value, ',' ) !== false && strpos( $value, '.' ) === false ) {
            $value = str_replace( ',', '.', $value );
        }

        if ( is_numeric( $value ) ) {
            return (float) $value;
        }

        return null;
    }

    public static function get_default_settings() {
        if ( is_array( self::$default_settings_cache ) ) {
            return self::$default_settings_cache;
        }

        $themes         = self::get_theme_defaults();
        $dark_defaults  = $themes['dark'];
        $light_defaults = $themes['light'];

        self::$default_settings_cache = array(
            // Options générales
            'visual_theme'                       => 'dark',
            'score_layout'                       => 'text',
            'score_max'                          => 10,
            'enable_animations'                  => 1,
            'allowed_post_types'                 => array( 'post' ),
            'tagline_font_size'                  => 16,
            'rating_badge_enabled'               => 0,
            'rating_badge_threshold'             => 8,

            // Couleurs de Thème Sombre personnalisables
            'dark_bg_color'                      => $dark_defaults['bg_color'],
            'dark_bg_color_secondary'            => $dark_defaults['bg_color_secondary'],
            'dark_border_color'                  => $dark_defaults['border_color'],
            'dark_text_color'                    => $dark_defaults['text_color'],
            'dark_text_color_secondary'          => $dark_defaults['text_color_secondary'],
            'tagline_bg_color'                   => $dark_defaults['tagline_bg_color'],
            'tagline_text_color'                 => $dark_defaults['tagline_text_color'],

            // Couleurs de Thème Clair personnalisables
            'light_bg_color'                     => $light_defaults['bg_color'],
            'light_bg_color_secondary'           => $light_defaults['bg_color_secondary'],
            'light_border_color'                 => $light_defaults['border_color'],
            'light_text_color'                   => $light_defaults['text_color'],
            'light_text_color_secondary'         => $light_defaults['text_color_secondary'],

            // Couleurs sémantiques et de marque
            'score_gradient_1'                   => '#60a5fa',
            'score_gradient_2'                   => '#c084fc',
            'color_low'                          => '#ef4444',
            'color_mid'                          => '#f97316',
            'color_high'                         => '#22c55e',
            'user_rating_star_color'             => '#f59e0b',
            'user_rating_text_color'             => '#a1a1aa',
            'user_rating_title_color'            => '#fafafa',

            // Options cercle
            'circle_dynamic_bg_enabled'          => 0,
            'circle_border_enabled'              => 1,
            'circle_border_width'                => 5,
            'circle_border_color'                => '#60a5fa',

            // Options glow pour mode texte
            'text_glow_enabled'                  => 0,
            'text_glow_color_mode'               => 'dynamic',
            'text_glow_custom_color'             => '#ffffff',
            'text_glow_intensity'                => 15,
            'text_glow_pulse'                    => 0,
            'text_glow_speed'                    => 2.5,

            // Options glow pour mode cercle
            'circle_glow_enabled'                => 0,
            'circle_glow_color_mode'             => 'dynamic',
            'circle_glow_custom_color'           => '#ffffff',
            'circle_glow_intensity'              => 15,
            'circle_glow_pulse'                  => 0,
            'circle_glow_speed'                  => 2.5,

            // Options des modules
            'tagline_enabled'                    => 1,
            'user_rating_enabled'                => 1,
            'user_rating_requires_login'         => 0,
            'user_rating_weighting_enabled'      => 0,
            'user_rating_guest_weight_start'     => 0.5,
            'user_rating_guest_weight_increment' => 0.1,
            'user_rating_guest_weight_max'       => 1.0,
            'table_zebra_striping'               => 0,
            'table_border_style'                 => 'horizontal',
            'table_border_width'                 => 1,
            'table_header_bg_color'              => '#3f3f46',
            'table_header_text_color'            => '#ffffff',
            'table_row_bg_color'                 => 'transparent', // Must remain literal "transparent" so CSS vars keep default transparency
            'table_row_text_color'               => '#a1a1aa',
            'table_zebra_bg_color'               => '#27272a',
            'thumb_text_color'                   => '#ffffff',
            'thumb_font_size'                    => 14,
            'thumb_padding'                      => 8,
            'thumb_border_radius'                => 4,

            // Options Game Explorer
            'game_explorer_columns'              => 3,
            'game_explorer_posts_per_page'       => 12,
            'game_explorer_filters'              => self::GAME_EXPLORER_DEFAULT_FILTERS,
            'game_explorer_score_position'       => self::GAME_EXPLORER_DEFAULT_SCORE_POSITION,

            // Libellés & catégories de notation
            'rating_categories'                  => self::get_default_category_definitions(),

            // Options techniques et diverses
            'custom_css'                         => '',
            'seo_schema_enabled'                 => 1,
            'debug_mode_enabled'                 => 0,
            'rawg_api_key'                       => '',
        );

        return self::$default_settings_cache;
    }

    public static function get_plugin_options( $force_refresh = false ) {
        if ( ! $force_refresh && is_array( self::$options_cache ) ) {
            return self::$options_cache;
        }

        $defaults            = self::get_default_settings();
        $saved_options       = get_option( self::$option_name, $defaults );
        self::$options_cache = wp_parse_args( $saved_options, $defaults );

        $filters = isset( self::$options_cache['game_explorer_filters'] )
            ? self::$options_cache['game_explorer_filters']
            : self::get_default_game_explorer_filters();

        self::$options_cache['game_explorer_filters'] = self::normalize_game_explorer_filters(
            $filters,
            self::get_default_game_explorer_filters()
        );

        $score_position = isset( self::$options_cache['game_explorer_score_position'] )
            ? self::$options_cache['game_explorer_score_position']
            : self::GAME_EXPLORER_DEFAULT_SCORE_POSITION;

        self::$options_cache['game_explorer_score_position'] = self::normalize_game_explorer_score_position( $score_position );

        $score_max                        = isset( self::$options_cache['score_max'] ) ? self::$options_cache['score_max'] : null;
        self::$options_cache['score_max'] = self::normalize_score_max( $score_max, self::$default_settings_cache['score_max'] ?? 10 );

        return self::$options_cache;
    }

    public static function schedule_score_scale_migration( $old_max, $new_max ) {
        $old_max = self::normalize_score_max( $old_max, $old_max );
        $new_max = self::normalize_score_max( $new_max, $new_max );

        if ( $old_max <= 0 || $new_max <= 0 || abs( $old_max - $new_max ) < 0.0001 ) {
            return;
        }

        $post_ids = self::get_rated_post_ids();

        $migration_payload = array(
            'old_max' => $old_max,
            'new_max' => $new_max,
            'ratio'   => $new_max / $old_max,
        );

        update_option( self::SCORE_SCALE_MIGRATION_OPTION, $migration_payload, false );
        update_option( self::SCORE_SCALE_QUEUE_OPTION, $post_ids, false );

        self::clear_rated_post_ids_cache();

        if ( ! function_exists( 'wp_schedule_single_event' ) ) {
            return;
        }

        $hook = self::SCORE_SCALE_EVENT_HOOK;

        if ( function_exists( 'wp_next_scheduled' ) && wp_next_scheduled( $hook ) ) {
            return;
        }

        wp_schedule_single_event( time() + 1, $hook );
    }

    public static function rescale_post_scores_for_scale_change( $post_id, $old_max, $new_max ) {
        $post_id = (int) $post_id;

        if ( $post_id <= 0 ) {
            return;
        }

        $old_max = self::normalize_score_max( $old_max, $old_max );
        $new_max = self::normalize_score_max( $new_max, $new_max );

        if ( $old_max <= 0 || $new_max <= 0 || abs( $old_max - $new_max ) < 0.0001 ) {
            return;
        }

        $ratio          = $new_max / $old_max;
        $definitions    = self::get_rating_category_definitions();
        $updated_scores = false;

        foreach ( $definitions as $definition ) {
            $meta_key = isset( $definition['meta_key'] ) ? (string) $definition['meta_key'] : '';

            if ( $meta_key === '' ) {
                continue;
            }

            $raw_value = get_post_meta( $post_id, $meta_key, true );

            if ( $raw_value === '' || $raw_value === null ) {
                continue;
            }

            $numeric_value = self::normalize_score_candidate( $raw_value );

            if ( $numeric_value === null ) {
                continue;
            }

            $scaled = round( max( 0, min( $new_max, $numeric_value * $ratio ) ), 1 );

            if ( abs( $scaled - (float) $numeric_value ) < 0.05 ) {
                continue;
            }

            update_post_meta( $post_id, $meta_key, $scaled );
            $updated_scores = true;
        }

        if ( $updated_scores ) {
            return;
        }

        $stored_average = get_post_meta( $post_id, '_jlg_average_score', true );

        if ( $stored_average === '' || $stored_average === null || ! is_numeric( $stored_average ) ) {
            return;
        }

        $average = (float) $stored_average;
        $scaled  = round( max( 0, min( $new_max, $average * $ratio ) ), 1 );

        if ( abs( $scaled - $average ) < 0.05 ) {
            return;
        }

        update_post_meta( $post_id, '_jlg_average_score', $scaled );
    }

    public static function get_score_max( $options = null ) {
        if ( $options === null ) {
            $options = self::get_plugin_options();
        }

        $raw_value = is_array( $options ) ? ( $options['score_max'] ?? null ) : null;

        return self::normalize_score_max( $raw_value, self::get_default_settings()['score_max'] ?? 10 );
    }

    private static function normalize_score_max( $value, $fallback = 10 ) {
        $min = 5;
        $max = 100;

        if ( ! is_numeric( $value ) ) {
            $normalized = is_numeric( $fallback ) ? (float) $fallback : 10.0;
        } else {
            $normalized = (float) $value;
        }

        $normalized = max( $min, min( $max, $normalized ) );

        return (int) round( $normalized );
    }

    public static function migrate_legacy_rating_configuration() {
        $options = get_option( self::$option_name );

        if ( empty( $options ) || ! is_array( $options ) ) {
            return;
        }

        $has_rating_categories = ! empty( $options['rating_categories'] ) && is_array( $options['rating_categories'] );

        $legacy_labels = array();
        foreach ( self::LEGACY_CATEGORY_SUFFIXES as $legacy_suffix ) {
            $option_key = 'label_' . $legacy_suffix;
            if ( isset( $options[ $option_key ] ) && $options[ $option_key ] !== '' ) {
                $legacy_labels[ $legacy_suffix ] = (string) $options[ $option_key ];
            }
        }

        if ( $has_rating_categories ) {
            $options['rating_categories'] = self::prepare_category_definitions( $options['rating_categories'] );

            foreach ( self::LEGACY_CATEGORY_SUFFIXES as $legacy_suffix ) {
                unset( $options[ 'label_' . $legacy_suffix ] );
            }

            update_option( self::$option_name, $options );
            self::flush_plugin_options_cache();

            return;
        }

        if ( empty( $legacy_labels ) ) {
            return;
        }

        $default_definitions  = self::get_default_category_definitions();
        $category_definitions = array();
        $used_ids             = array();

        foreach ( self::LEGACY_CATEGORY_SUFFIXES as $index => $legacy_suffix ) {
            $label_option = isset( $legacy_labels[ $legacy_suffix ] ) ? $legacy_labels[ $legacy_suffix ] : '';
            $label        = is_string( $label_option ) ? trim( $label_option ) : '';

            if ( $label === '' ) {
                $label = $default_definitions[ $index ]['label'] ?? sprintf( __( 'Catégorie %d', 'notation-jlg' ), $index + 1 );
            }

            $id = sanitize_key( sanitize_title( $label ) );

            if ( $id === '' && isset( $default_definitions[ $index ]['id'] ) ) {
                $id = sanitize_key( $default_definitions[ $index ]['id'] );
            }

            if ( $id === '' ) {
                $id = $legacy_suffix;
            }

            $base_id = $id;
            $suffix  = 2;
            while ( in_array( $id, $used_ids, true ) ) {
                $id = $base_id . '-' . $suffix;
                ++$suffix;
            }

            $used_ids[] = $id;

            $category_definitions[] = array(
                'id'         => $id,
                'label'      => $label,
                'legacy_ids' => array( $legacy_suffix ),
                'position'   => $index + 1,
            );
        }

        if ( empty( $category_definitions ) ) {
            return;
        }

        $options['rating_categories'] = self::prepare_category_definitions( $category_definitions );
        $category_definitions         = $options['rating_categories'];

        foreach ( self::LEGACY_CATEGORY_SUFFIXES as $legacy_suffix ) {
            unset( $options[ 'label_' . $legacy_suffix ] );
        }

        update_option( self::$option_name, $options );
        self::flush_plugin_options_cache();

        global $wpdb;

        if ( ! isset( $wpdb->postmeta ) ) {
            return;
        }

        $postmeta_table = $wpdb->postmeta;

        foreach ( $category_definitions as $definition ) {
            $new_meta_key = '_note_' . $definition['id'];

            foreach ( $definition['legacy_ids'] as $legacy_suffix ) {
                $legacy_meta_key = '_note_' . $legacy_suffix;

                if ( $legacy_meta_key === $new_meta_key ) {
                    continue;
                }

                $rows = $wpdb->get_results(
                    $wpdb->prepare(
                        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name sourced from $wpdb.
                        "SELECT post_id, meta_value FROM {$postmeta_table} WHERE meta_key = %s",
                        $legacy_meta_key
                    ),
                    ARRAY_A
                );

                if ( empty( $rows ) ) {
                    continue;
                }

                foreach ( $rows as $row ) {
                    $post_id = isset( $row['post_id'] ) ? (int) $row['post_id'] : 0;

                    if ( $post_id <= 0 ) {
                        continue;
                    }

                    $value = isset( $row['meta_value'] ) ? $row['meta_value'] : '';

                    if ( $value === '' || $value === null ) {
                        continue;
                    }

                    $existing_value = get_post_meta( $post_id, $new_meta_key, true );

                    if ( $existing_value === '' || $existing_value === null ) {
                        update_post_meta( $post_id, $new_meta_key, $value );
                    }
                }
            }
        }
    }

    public static function get_game_explorer_score_positions() {
        return self::$game_explorer_score_positions;
    }

    public static function normalize_game_explorer_score_position( $position ) {
        if ( is_string( $position ) ) {
            $position = strtolower( trim( $position ) );
        } else {
            $position = '';
        }

        if ( in_array( $position, self::$game_explorer_score_positions, true ) ) {
            return $position;
        }

        return self::GAME_EXPLORER_DEFAULT_SCORE_POSITION;
    }

    public static function get_game_explorer_allowed_filters() {
        return self::GAME_EXPLORER_ALLOWED_FILTERS;
    }

    public static function get_default_game_explorer_filters() {
        return self::GAME_EXPLORER_DEFAULT_FILTERS;
    }

    public static function normalize_game_explorer_filters( $filters, array $fallback = array() ) {
        $allowed = self::get_game_explorer_allowed_filters();

        if ( empty( $fallback ) ) {
            $fallback = self::get_default_game_explorer_filters();
        }

        if ( is_string( $filters ) ) {
            $filters = explode( ',', $filters );
        }

        if ( ! is_array( $filters ) ) {
            $filters = array();
        }

        $filters = array_map( 'sanitize_key', array_filter( array_map( 'trim', $filters ) ) );

        $normalized = array();

        foreach ( $allowed as $filter_key ) {
            if ( in_array( $filter_key, $filters, true ) ) {
                $normalized[] = $filter_key;
            }
        }

        if ( empty( $normalized ) ) {
            return array_values( array_intersect( $allowed, $fallback ) );
        }

        return $normalized;
    }

    /**
     * Retrieve the post types allowed for ratings and shortcodes.
     *
     * @return string[] List of sanitized post type identifiers.
     */
    public static function get_allowed_post_types() {
        $defaults = self::get_default_settings();

        $default_post_types = self::sanitize_post_type_list(
            isset( $defaults['allowed_post_types'] ) ? $defaults['allowed_post_types'] : array( 'post' )
        );

        if ( empty( $default_post_types ) ) {
            $default_post_types = array( 'post' );
        }

        $options          = self::get_plugin_options();
        $configured_types = isset( $options['allowed_post_types'] )
            ? $options['allowed_post_types']
            : $default_post_types;

        $base_post_types = self::sanitize_post_type_list( $configured_types );

        if ( empty( $base_post_types ) ) {
            $base_post_types = $default_post_types;
        }

        $filtered_post_types = apply_filters( 'jlg_rated_post_types', $base_post_types );

        if ( ! is_array( $filtered_post_types ) ) {
            $filtered_post_types = $base_post_types;
        } else {
            $filtered_post_types = self::sanitize_post_type_list( $filtered_post_types );

            if ( empty( $filtered_post_types ) ) {
                $filtered_post_types = $base_post_types;
            }
        }

        if ( empty( $filtered_post_types ) ) {
            $filtered_post_types = $default_post_types;
        }

        if ( empty( $filtered_post_types ) ) {
            $filtered_post_types = array( 'post' );
        }

        return $filtered_post_types;
    }

    private static function sanitize_post_type_list( $post_types ) {
        if ( is_string( $post_types ) || is_numeric( $post_types ) ) {
            $post_types = array( $post_types );
        }

        if ( ! is_array( $post_types ) ) {
            return array();
        }

        $sanitized = array();

        foreach ( $post_types as $type ) {
            if ( is_array( $type ) ) {
                continue;
            }

            $key = sanitize_key( (string) $type );

            if ( $key === '' ) {
                continue;
            }

            $sanitized[] = $key;
        }

        return array_values( array_unique( $sanitized ) );
    }

    public static function flush_plugin_options_cache() {
        self::$options_cache             = null;
        self::$category_definition_cache = null;
        self::$rating_meta_keys_cache    = null;
    }

    /**
     * Retrieve the preferred title for a review.
     *
     * @param int $post_id The post identifier.
     * @return string The stored game title if available, otherwise the WordPress post title.
     */
    public static function get_game_title( $post_id ) {
        $post_id = (int) $post_id;

        if ( $post_id <= 0 ) {
            return '';
        }

        $raw_meta_title = get_post_meta( $post_id, '_jlg_game_title', true );
        $resolved_title = '';

        if ( is_string( $raw_meta_title ) ) {
            $meta_title = sanitize_text_field( $raw_meta_title );
            if ( $meta_title !== '' ) {
                $resolved_title = $meta_title;
            }
        }

        if ( $resolved_title === '' ) {
            $fallback_title = get_the_title( $post_id );
            if ( is_string( $fallback_title ) ) {
                $resolved_title = $fallback_title;
            }
        }

        return apply_filters( 'jlg_game_title', (string) $resolved_title, $post_id, $raw_meta_title );
    }

    public static function get_color_palette() {
        $options        = self::get_plugin_options();
        $theme          = $options['visual_theme'] ?? 'dark';
        $theme_defaults = self::get_theme_defaults();
        $palette        = ( $theme === 'light' ) ? $theme_defaults['light'] : $theme_defaults['dark'];

        if ( $theme === 'light' ) {
            $palette['bg_color']             = $options['light_bg_color'] ?? $theme_defaults['light']['bg_color'];
            $palette['bg_color_secondary']   = $options['light_bg_color_secondary'] ?? $theme_defaults['light']['bg_color_secondary'];
            $palette['border_color']         = $options['light_border_color'] ?? $theme_defaults['light']['border_color'];
            $palette['text_color']           = $options['light_text_color'] ?? $theme_defaults['light']['text_color'];
            $palette['text_color_secondary'] = $options['light_text_color_secondary'] ?? $theme_defaults['light']['text_color_secondary'];
        } else {
            $palette['bg_color']             = $options['dark_bg_color'] ?? $theme_defaults['dark']['bg_color'];
            $palette['bg_color_secondary']   = $options['dark_bg_color_secondary'] ?? $theme_defaults['dark']['bg_color_secondary'];
            $palette['border_color']         = $options['dark_border_color'] ?? $theme_defaults['dark']['border_color'];
            $palette['text_color']           = $options['dark_text_color'] ?? $theme_defaults['dark']['text_color'];
            $palette['text_color_secondary'] = $options['dark_text_color_secondary'] ?? $theme_defaults['dark']['text_color_secondary'];
        }

        $tagline_bg_option   = isset( $options['tagline_bg_color'] ) ? (string) $options['tagline_bg_color'] : '';
        $tagline_text_option = isset( $options['tagline_text_color'] ) ? (string) $options['tagline_text_color'] : '';

        $palette['tagline_bg_color']     = $tagline_bg_option !== '' ? $tagline_bg_option : ( $theme === 'light' ? $theme_defaults['light']['tagline_bg_color'] : $theme_defaults['dark']['tagline_bg_color'] );
        $palette['tagline_text_color']   = $tagline_text_option !== '' ? $tagline_text_option : ( $theme === 'light' ? $theme_defaults['light']['tagline_text_color'] : $theme_defaults['dark']['tagline_text_color'] );
        $palette['table_zebra_color']    = $palette['bg_color_secondary'];
        $palette['main_text_color']      = $palette['text_color'];
        $palette['secondary_text_color'] = $palette['text_color_secondary'];
        $palette['bar_bg_color']         = $palette['bg_color_secondary'];

        return $palette;
    }

    public static function get_average_score_for_post( $post_id ) {
        $scores = self::get_post_category_scores( $post_id );

        if ( empty( $scores ) ) {
            return null;
        }

        $weighted_sum = 0.0;
        $weight_total = 0.0;

        foreach ( $scores as $score_entry ) {
            if ( ! is_array( $score_entry ) || ! isset( $score_entry['score'] ) ) {
                continue;
            }

            $weight = isset( $score_entry['weight'] )
                ? self::normalize_category_weight( $score_entry['weight'], 1.0 )
                : 1.0;

            if ( $weight <= 0 ) {
                continue;
            }

            $weighted_sum += (float) $score_entry['score'] * $weight;
            $weight_total += $weight;
        }

        if ( $weight_total <= 0 ) {
            return null;
        }

        return round( $weighted_sum / $weight_total, 1 );
    }

    /**
     * Retrieve the stored average score, falling back to a computed value when necessary.
     *
     * @param int $post_id The post ID.
     * @return array{value: float|null, formatted: string|null}
     */
    public static function get_resolved_average_score( $post_id ) {
        $stored_score = get_post_meta( $post_id, '_jlg_average_score', true );

        if ( $stored_score !== '' && $stored_score !== null && is_numeric( $stored_score ) ) {
            $score_value = (float) $stored_score;

            return array(
                'value'     => $score_value,
                'formatted' => number_format_i18n( $score_value, 1 ),
            );
        }

        $fallback_score = self::get_average_score_for_post( $post_id );

        if ( $fallback_score !== null && is_numeric( $fallback_score ) ) {
            update_post_meta( $post_id, '_jlg_average_score', $fallback_score );
            $fallback_value = (float) $fallback_score;

            return array(
                'value'     => $fallback_value,
                'formatted' => number_format_i18n( $fallback_value, 1 ),
            );
        }

        return array(
            'value'     => null,
            'formatted' => null,
        );
    }

    /**
     * Clear the cached average score when one of the rating metas is changed.
     */
    public static function maybe_handle_rating_meta_change( $meta_id, $post_id, $meta_key, $meta_value = null ) {
        unset( $meta_id, $meta_value );

        if ( ! is_string( $meta_key ) || ! in_array( $meta_key, self::get_rating_meta_keys(), true ) ) {
            return;
        }

        self::invalidate_average_score_cache( $post_id );
    }

    /**
     * Delete the stored average and queue a rebuild for the provided post.
     */
    public static function invalidate_average_score_cache( $post_id ) {
        $post_id = (int) $post_id;

        if ( $post_id <= 0 ) {
            return;
        }

        delete_post_meta( $post_id, '_jlg_average_score' );
        self::clear_rated_post_ids_cache();
        self::queue_average_score_rebuild( $post_id );
    }

    public static function get_rated_post_ids() {
        $transient_key   = 'jlg_rated_post_ids_v1';
        $cached_post_ids = get_transient( $transient_key );

        if ( $cached_post_ids !== false && is_array( $cached_post_ids ) ) {
            return array_map( 'intval', $cached_post_ids );
        }

        $post_ids = self::query_rated_post_ids();

        $expiration = apply_filters( 'jlg_rated_post_ids_cache_ttl', 15 * ( defined( 'MINUTE_IN_SECONDS' ) ? MINUTE_IN_SECONDS : 60 ) );

        if ( $expiration > 0 ) {
            set_transient( $transient_key, $post_ids, $expiration );
        }

        return $post_ids;
    }

    public static function get_rated_post_ids_batch( $after_post_id = 0, $limit = 200 ) {
        $after_post_id = max( 0, (int) $after_post_id );
        $limit         = (int) $limit;

        if ( $limit === 0 ) {
            $limit = 200;
        }

        global $wpdb;

        $meta_keys = self::get_rating_meta_keys();

        if ( empty( $meta_keys ) ) {
            $meta_keys = array();
            foreach ( self::get_rating_category_definitions() as $definition ) {
                if ( ! empty( $definition['meta_key'] ) ) {
                    $meta_keys[] = $definition['meta_key'];
                }
            }
        }

        $meta_keys[] = '_jlg_average_score';
        $meta_keys   = array_values( array_unique( array_filter( $meta_keys ) ) );

        if ( empty( $meta_keys ) ) {
            return array();
        }

        $post_types = self::get_allowed_post_types();

        $post_statuses = apply_filters( 'jlg_rated_post_statuses', array( 'publish' ) );
        if ( ! is_array( $post_statuses ) || empty( $post_statuses ) ) {
            $post_statuses = array( 'publish' );
        }
        $post_statuses = array_values( array_filter( array_map( 'sanitize_key', $post_statuses ) ) );

        $meta_placeholders = implode( ', ', array_fill( 0, count( $meta_keys ), '%s' ) );
        $type_placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
        $status_clause     = '';
        $prepare_args      = array_merge( $meta_keys, $post_types );

        if ( ! empty( $post_statuses ) ) {
            $status_placeholders = implode( ', ', array_fill( 0, count( $post_statuses ), '%s' ) );
            $status_clause       = " AND p.post_status IN ($status_placeholders)";
            $prepare_args        = array_merge( $prepare_args, $post_statuses );
        }

        $postmeta_table = isset( $wpdb->postmeta ) ? $wpdb->postmeta : ( isset( $wpdb->prefix ) ? $wpdb->prefix . 'postmeta' : 'wp_postmeta' );
        $posts_table    = isset( $wpdb->posts ) ? $wpdb->posts : ( isset( $wpdb->prefix ) ? $wpdb->prefix . 'posts' : 'wp_posts' );

        $postmeta_table = preg_match( '/^[A-Za-z0-9_]+$/', (string) $postmeta_table ) ? $postmeta_table : 'wp_postmeta';
        $posts_table    = preg_match( '/^[A-Za-z0-9_]+$/', (string) $posts_table ) ? $posts_table : 'wp_posts';

        $after_clause = '';
        if ( $after_post_id > 0 ) {
            $after_clause = 'AND pm.post_id > %d';
            array_splice( $prepare_args, count( $meta_keys ), 0, array( $after_post_id ) );
        }

        $limit_clause = '';
        if ( $limit > 0 && $limit < PHP_INT_MAX ) {
            $limit_clause   = 'LIMIT %d';
            $prepare_args[] = $limit;
        }

        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Table names are validated earlier.
        $query = "SELECT DISTINCT pm.post_id
            FROM {$postmeta_table} pm
            INNER JOIN {$posts_table} p ON p.ID = pm.post_id
            WHERE pm.meta_key IN ($meta_placeholders)
                AND pm.meta_value != ''
                AND pm.meta_value IS NOT NULL
                $after_clause
                AND p.post_type IN ($type_placeholders)
                $status_clause
            ORDER BY pm.post_id ASC";

        if ( $limit_clause !== '' ) {
            $query .= "\n            $limit_clause";
        }

        $prepared = $wpdb->prepare( $query, ...$prepare_args );

        return array_map( 'intval', $wpdb->get_col( $prepared ) );
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
    }

    private static function query_rated_post_ids() {
        return self::get_rated_post_ids_batch( 0, -1 );
    }

    private static function resolve_post_id_from_mixed( $post ) {
        if ( is_object( $post ) && isset( $post->ID ) ) {
            return (int) $post->ID;
        }

        if ( is_array( $post ) && isset( $post['ID'] ) ) {
            return (int) $post['ID'];
        }

        if ( is_numeric( $post ) ) {
            return (int) $post;
        }

        return 0;
    }

    private static function post_has_rating_values( $post_id ) {
        foreach ( self::get_rating_meta_keys() as $meta_key ) {
            $value = get_post_meta( $post_id, $meta_key, true );

            if ( $value !== '' && $value !== null && $value !== false ) {
                return true;
            }
        }

        $average = get_post_meta( $post_id, '_jlg_average_score', true );

        if ( $average !== '' && $average !== null && $average !== false ) {
            return true;
        }

        return false;
    }

    public static function maybe_clear_rated_post_ids_cache_for_status_change( $new_status, $old_status, $post ) {
        $new_status = is_string( $new_status ) ? strtolower( $new_status ) : '';
        $old_status = is_string( $old_status ) ? strtolower( $old_status ) : '';

        if ( $new_status === $old_status ) {
            return;
        }

        if ( $new_status !== 'publish' && $old_status !== 'publish' ) {
            return;
        }

        $post_id = self::resolve_post_id_from_mixed( $post );

        if ( $post_id <= 0 ) {
            return;
        }

        $post_type = null;

        if ( function_exists( 'get_post_type' ) ) {
            $post_type = get_post_type( $post );
        }

        if ( ! is_string( $post_type ) || $post_type === '' ) {
            if ( is_object( $post ) && isset( $post->post_type ) ) {
                $post_type = (string) $post->post_type;
            } elseif ( is_array( $post ) && isset( $post['post_type'] ) ) {
                $post_type = (string) $post['post_type'];
            }
        }

        if ( is_string( $post_type ) && $post_type !== '' ) {
            $allowed_types = self::get_allowed_post_types();

            if ( ! in_array( $post_type, $allowed_types, true ) ) {
                return;
            }
        }

        if ( ! self::post_has_rating_values( $post_id ) ) {
            return;
        }

        self::clear_rated_post_ids_cache();
    }

    public static function queue_average_score_rebuild( $post_ids ) {
        if ( empty( $post_ids ) ) {
            return;
        }

        if ( ! is_array( $post_ids ) ) {
            $post_ids = array( $post_ids );
        }

        $post_ids = array_filter(
            array_map( 'intval', $post_ids ),
            static function ( $post_id ) {
                return $post_id > 0;
            }
        );

        if ( empty( $post_ids ) ) {
            return;
        }

        /**
         * Permet aux développeurs de traiter la mise à jour asynchrone des moyennes.
         *
         * @param int[] $post_ids Liste des IDs d'articles.
         */
        do_action( 'jlg_queue_average_rebuild', $post_ids );
    }

    public static function clear_rated_post_ids_cache() {
        delete_transient( 'jlg_rated_post_ids_v1' );
    }

    public static function adjust_hex_brightness( $hex, $steps ) {
        $hex = str_replace( '#', '', $hex );

        if ( strlen( $hex ) === 3 ) {
            $hex = str_repeat( substr( $hex, 0, 1 ), 2 ) .
                    str_repeat( substr( $hex, 1, 1 ), 2 ) .
                    str_repeat( substr( $hex, 2, 1 ), 2 );
        }

        $r = max( 0, min( 255, hexdec( substr( $hex, 0, 2 ) ) + $steps ) );
        $g = max( 0, min( 255, hexdec( substr( $hex, 2, 2 ) ) + $steps ) );
        $b = max( 0, min( 255, hexdec( substr( $hex, 4, 2 ) ) + $steps ) );

        return '#' . str_pad( dechex( $r ), 2, '0', STR_PAD_LEFT ) .
                str_pad( dechex( $g ), 2, '0', STR_PAD_LEFT ) .
                str_pad( dechex( $b ), 2, '0', STR_PAD_LEFT );
    }

    public static function calculate_color_from_note( $note, $options = null ) {
        if ( $options === null ) {
            $options = self::get_plugin_options();
        }

        // S'assurer que la note est un nombre
        $note      = floatval( $note );
        $score_max = max( 1, self::get_score_max( $options ) );

        $note = max( 0, min( $score_max, $note ) );

        // Récupérer les couleurs définies dans les options
        $color_low  = $options['color_low'] ?? '#ef4444';
        $color_mid  = $options['color_mid'] ?? '#f97316';
        $color_high = $options['color_high'] ?? '#22c55e';

        // Parser les couleurs hexadécimales
        $parsed_low  = sscanf( $color_low, '#%02x%02x%02x' );
        $parsed_mid  = sscanf( $color_mid, '#%02x%02x%02x' );
        $parsed_high = sscanf( $color_high, '#%02x%02x%02x' );

        // Vérifier que le parsing a fonctionné
        if ( ! $parsed_low || count( $parsed_low ) !== 3 ) {
			$parsed_low = array( 239, 68, 68 );
        }
        if ( ! $parsed_mid || count( $parsed_mid ) !== 3 ) {
			$parsed_mid = array( 249, 115, 22 );
        }
        if ( ! $parsed_high || count( $parsed_high ) !== 3 ) {
			$parsed_high = array( 34, 197, 94 );
        }

        $midpoint = $score_max / 2;

        // Calculer l'interpolation selon la note
        if ( $note <= $midpoint ) {
            $divider = $midpoint > 0 ? $midpoint : 1;
            $ratio   = $note / $divider;
            $r       = round( $parsed_low[0] + ( $parsed_mid[0] - $parsed_low[0] ) * $ratio );
            $g       = round( $parsed_low[1] + ( $parsed_mid[1] - $parsed_low[1] ) * $ratio );
            $b       = round( $parsed_low[2] + ( $parsed_mid[2] - $parsed_low[2] ) * $ratio );
        } else {
            $divider = $midpoint > 0 ? $midpoint : 1;
            $ratio   = ( $note - $midpoint ) / $divider;
            $r       = round( $parsed_mid[0] + ( $parsed_high[0] - $parsed_mid[0] ) * $ratio );
            $g       = round( $parsed_mid[1] + ( $parsed_high[1] - $parsed_mid[1] ) * $ratio );
            $b       = round( $parsed_mid[2] + ( $parsed_high[2] - $parsed_mid[2] ) * $ratio );
        }

        // S'assurer que les valeurs sont dans les limites
        $r = max( 0, min( 255, $r ) );
        $g = max( 0, min( 255, $g ) );
        $b = max( 0, min( 255, $b ) );

        return sprintf( '#%02x%02x%02x', $r, $g, $b );
    }

    public static function get_glow_css( $type, $average_score, $options = null ) {
        if ( $options === null ) {
            $options = self::get_plugin_options();
        }

        // Vérifier si l'effet est activé pour ce type
        $enabled_key = "{$type}_glow_enabled";
        if ( empty( $options[ $enabled_key ] ) ) {
            return '';
        }

        // Déterminer la couleur du glow
        $color_mode_key   = "{$type}_glow_color_mode";
        $custom_color_key = "{$type}_glow_custom_color";

        // Vérifier explicitement si on est en mode dynamique
        $is_dynamic = isset( $options[ $color_mode_key ] ) && $options[ $color_mode_key ] === 'dynamic';

        if ( $is_dynamic ) {
            // Mode dynamique : calculer la couleur selon la note
            $glow_color = self::calculate_color_from_note( $average_score, $options );
        } else {
            // Mode personnalisé : utiliser la couleur définie
            $glow_color = $options[ $custom_color_key ] ?? '#ffffff';
        }

        // Paramètres d'intensité et de vitesse
        $intensity_key = "{$type}_glow_intensity";
        $pulse_key     = "{$type}_glow_pulse";
        $speed_key     = "{$type}_glow_speed";

        $intensity = isset( $options[ $intensity_key ] ) ? intval( $options[ $intensity_key ] ) : 15;
        $has_pulse = ! empty( $options[ $pulse_key ] );
        $speed     = isset( $options[ $speed_key ] ) ? floatval( $options[ $speed_key ] ) : 2.5;

        // Calculer les tailles de shadow
        $s1 = round( $intensity * 0.5 );
        $s2 = $intensity;
        $s3 = round( $intensity * 1.5 );

        // Pour la pulsation
        $ps1 = round( $intensity * 0.7 );
        $ps2 = round( $intensity * 1.5 );
        $ps3 = round( $intensity * 2.5 );

        $css = '';

        if ( $type === 'text' ) {
            // CSS pour le mode texte
            $css .= '.review-box-jlg .score-value { ';
            $css .= 'text-shadow: ';
            $css .= "0 0 {$s1}px {$glow_color}, ";
            $css .= "0 0 {$s2}px {$glow_color}, ";
            $css .= "0 0 {$s3}px {$glow_color}";
            $css .= ' !important; '; // Force l'application
            $css .= '} ';

            // Animation de pulsation si activée
            if ( $has_pulse ) {
                $css .= '@keyframes jlg-text-glow-pulse { ';
                $css .= '0%, 100% { ';
                $css .= "text-shadow: 0 0 {$s1}px {$glow_color}, 0 0 {$s2}px {$glow_color}, 0 0 {$s3}px {$glow_color}; ";
                $css .= '} ';
                $css .= '50% { ';
                $css .= "text-shadow: 0 0 {$ps1}px {$glow_color}, 0 0 {$ps2}px {$glow_color}, 0 0 {$ps3}px {$glow_color}; ";
                $css .= '} ';
                $css .= '} ';
                $css .= '.review-box-jlg .score-value { ';
                $css .= "animation: jlg-text-glow-pulse {$speed}s infinite ease-in-out !important; ";
                $css .= '} ';
            }
		} elseif ( $type === 'circle' ) {
            // CSS pour le mode cercle
            $css .= '.review-box-jlg .score-circle { ';
            $css .= 'box-shadow: ';
            $css .= "0 0 {$s1}px {$glow_color}, ";
            $css .= "0 0 {$s2}px {$glow_color}, ";
            $css .= "0 0 {$s3}px {$glow_color}, ";
            $css .= "inset 0 0 {$s1}px rgba(255,255,255,0.1)";
            $css .= ' !important; '; // Force l'application
            $css .= '} ';

            // Animation de pulsation si activée
            if ( $has_pulse ) {
                $css .= '@keyframes jlg-circle-glow-pulse { ';
                $css .= '0%, 100% { ';
                $css .= 'box-shadow: ';
                $css .= "0 0 {$s1}px {$glow_color}, ";
                $css .= "0 0 {$s2}px {$glow_color}, ";
                $css .= "0 0 {$s3}px {$glow_color}, ";
                $css .= "inset 0 0 {$s1}px rgba(255,255,255,0.1); ";
                $css .= '} ';
                $css .= '50% { ';
                $css .= 'box-shadow: ';
                $css .= "0 0 {$ps1}px {$glow_color}, ";
                $css .= "0 0 {$ps2}px {$glow_color}, ";
                $css .= "0 0 {$ps3}px {$glow_color}, ";
                $css .= "inset 0 0 {$ps1}px rgba(255,255,255,0.15); ";
                $css .= '} ';
                $css .= '} ';
                $css .= '.review-box-jlg .score-circle { ';
                $css .= "animation: jlg-circle-glow-pulse {$speed}s infinite ease-in-out !important; ";
                $css .= '} ';
			}
        }

        // Mode debug (optionnel) - décommentez pour voir les valeurs
        if ( ! empty( $options['debug_mode_enabled'] ) ) {
            $css .= '/* DEBUG GLOW: ';
            $css .= "Type: {$type}, ";
            $css .= 'Mode: ' . ( $is_dynamic ? 'dynamic' : 'custom' ) . ', ';
            $css .= "Score: {$average_score}, ";
            $css .= "Color: {$glow_color}, ";
            $css .= "Intensity: {$intensity}, ";
            $css .= 'Pulse: ' . ( $has_pulse ? 'yes' : 'no' );
            $css .= ' */ ';
        }

        return $css;
    }

    public static function get_registered_platform_labels() {
        $defaults  = self::get_default_platform_definitions();
        $stored    = get_option( 'jlg_platforms_list', array() );
        $platforms = $defaults;
        $order_map = array();

        if ( is_array( $stored ) ) {
            if ( isset( $stored['custom_platforms'] ) || isset( $stored['order'] ) ) {
                $custom_platforms = isset( $stored['custom_platforms'] ) && is_array( $stored['custom_platforms'] )
                    ? $stored['custom_platforms']
                    : array();

                foreach ( $custom_platforms as $key => $platform ) {
                    if ( ! is_array( $platform ) ) {
                        continue;
                    }

                    $name = isset( $platform['name'] ) ? sanitize_text_field( $platform['name'] ) : '';

                    if ( $name === '' ) {
                        continue;
                    }

                    $platforms[ $key ] = array(
                        'name'  => $name,
                        'order' => isset( $platform['order'] ) ? (int) $platform['order'] : (int) ( ( $platforms[ $key ]['order'] ?? count( $platforms ) + 1 ) ),
                    );
                }

                if ( isset( $stored['order'] ) && is_array( $stored['order'] ) ) {
                    $order_map = array_map( 'intval', $stored['order'] );
                }
            } else {
                foreach ( $stored as $key => $platform ) {
                    if ( ! is_array( $platform ) ) {
                        continue;
                    }

                    $name = isset( $platform['name'] ) ? sanitize_text_field( $platform['name'] ) : '';

                    if ( $name === '' ) {
                        continue;
                    }

                    $platforms[ $key ] = array(
                        'name'  => $name,
                        'order' => isset( $platform['order'] ) ? (int) $platform['order'] : (int) ( ( $defaults[ $key ]['order'] ?? count( $platforms ) + 1 ) ),
                    );

                    if ( isset( $platform['order'] ) ) {
                        $order_map[ $key ] = (int) $platform['order'];
                    }
                }
            }
        }

        $keys = array_keys( $platforms );

        usort(
            $keys,
            function ( $a, $b ) use ( $order_map, $platforms ) {
				$order_a = isset( $order_map[ $a ] ) ? $order_map[ $a ] : (int) ( $platforms[ $a ]['order'] ?? PHP_INT_MAX );
				$order_b = isset( $order_map[ $b ] ) ? $order_map[ $b ] : (int) ( $platforms[ $b ]['order'] ?? PHP_INT_MAX );

				if ( $order_a === $order_b ) {
					return strcmp( (string) $a, (string) $b );
				}

				return $order_a <=> $order_b;
			}
        );

        $labels = array();

        foreach ( $keys as $key ) {
            $name = isset( $platforms[ $key ]['name'] ) ? (string) $platforms[ $key ]['name'] : '';

            if ( $name === '' ) {
                continue;
            }

            $labels[ $key ] = $name;
        }

        if ( empty( $labels ) ) {
            foreach ( $defaults as $key => $platform ) {
                if ( isset( $platform['name'] ) && $platform['name'] !== '' ) {
                    $labels[ $key ] = $platform['name'];
                }
            }
        }

        return $labels;
    }

    /**
     * Calcule des statistiques globales sur les notes attribuées aux articles.
     *
     * @param int[]|null $post_ids Identifiants d'articles à analyser. Par défaut tous les articles notés.
     *
     * @return array{
     *     total:int,
     *     mean:array{value:float|null,formatted:string|null},
     *     median:array{value:float|null,formatted:string|null},
     *     distribution:array<int, array{
     *         label:string,
     *         from:float,
     *         to:float,
     *         count:int,
     *         percentage:float
     *     }>,
     *     platform_rankings:array<int, array{
     *         slug:string,
     *         label:string,
     *         count:int,
     *         average:float|null,
     *         average_formatted:string|null
     *     }>
     * }
     */
    public static function get_posts_score_insights( $post_ids = null ) {
        if ( $post_ids === null ) {
            $post_ids = self::get_rated_post_ids();
        }

        if ( ! is_array( $post_ids ) ) {
            $post_ids = array( $post_ids );
        }

        $post_ids = array_values(
            array_filter(
                array_map( 'intval', $post_ids ),
                static function ( $post_id ) {
                    return $post_id > 0;
                }
            )
        );

        $score_max       = max( 1, (float) self::get_score_max() );
        $scores          = array();
        $platforms       = array();
        $badge_threshold = (float) apply_filters( 'jlg_score_insights_badge_threshold', 1.5 );
        $badge_limit     = (int) apply_filters( 'jlg_score_insights_badge_limit', 4 );

        if ( $badge_threshold < 0 ) {
            $badge_threshold = 0.0;
        }

        if ( $badge_limit < 1 ) {
            $badge_limit = 1;
        }

        $divergence_candidates = array();

        $registered_platforms = self::get_registered_platform_labels();
        $unknown_slug         = 'sans-plateforme';
        $unknown_label        = _x( 'Sans plateforme', 'Fallback platform label', 'notation-jlg' );

        foreach ( $post_ids as $post_id ) {
            $score_data = self::get_resolved_average_score( $post_id );
            $score      = isset( $score_data['value'] ) && is_numeric( $score_data['value'] )
                ? (float) $score_data['value']
                : null;

            if ( $score === null ) {
                continue;
            }

            $scores[] = $score;

            $user_rating_average_raw = get_post_meta( $post_id, '_jlg_user_rating_avg', true );
            $user_rating_average     = is_numeric( $user_rating_average_raw ) ? (float) $user_rating_average_raw : null;
            $user_rating_count       = (int) get_post_meta( $post_id, '_jlg_user_rating_count', true );

            if ( $user_rating_average !== null && $user_rating_count > 0 ) {
                $delta          = $user_rating_average - $score;
                $absolute_delta = abs( $delta );

                if ( $absolute_delta >= $badge_threshold ) {
                    $editorial_score = round( $score, 1 );
                    $user_score      = round( $user_rating_average, 1 );
                    $delta_value     = round( $delta, 1 );

                    $divergence_candidates[] = array(
                        'post_id'                   => $post_id,
                        'editorial_score'           => $editorial_score,
                        'editorial_score_formatted' => number_format_i18n( $editorial_score, 1 ),
                        'user_score'                => $user_score,
                        'user_score_formatted'      => number_format_i18n( $user_score, 1 ),
                        'delta'                     => $delta_value,
                        'delta_formatted'           => self::format_signed_score_delta( $delta_value ),
                        'absolute_delta'            => round( $absolute_delta, 1 ),
                        'direction'                 => $delta_value >= 0 ? 'positive' : 'negative',
                        'user_rating_count'         => $user_rating_count,
                        'absolute_delta_raw'        => $absolute_delta,
                    );
                }
            }

            $platform_meta = get_post_meta( $post_id, '_jlg_plateformes', true );
            $labels        = array();

            if ( is_array( $platform_meta ) ) {
                foreach ( $platform_meta as $value ) {
                    if ( ! is_string( $value ) ) {
                        continue;
                    }

                    $label = sanitize_text_field( $value );

                    if ( $label === '' ) {
                        continue;
                    }

                    $labels[] = $label;
                }
            } elseif ( is_string( $platform_meta ) && $platform_meta !== '' ) {
                $pieces = array_map( 'trim', explode( ',', $platform_meta ) );

                foreach ( $pieces as $piece ) {
                    if ( $piece === '' ) {
                        continue;
                    }

                    $labels[] = sanitize_text_field( $piece );
                }
            }

            if ( empty( $labels ) ) {
                $labels = array( $unknown_label );
            }

            foreach ( $labels as $label ) {
                $slug = sanitize_title( $label );

                if ( $slug === '' ) {
                    $slug  = $unknown_slug;
                    $label = $unknown_label;
                }

                if ( isset( $registered_platforms[ $slug ] ) ) {
                    $label = $registered_platforms[ $slug ];
                }

                if ( ! isset( $platforms[ $slug ] ) ) {
                    $platforms[ $slug ] = array(
                        'slug'  => $slug,
                        'label' => $label,
                        'count' => 0,
                        'sum'   => 0.0,
                    );
                }

                ++$platforms[ $slug ]['count'];
                $platforms[ $slug ]['sum'] += $score;
            }
        }

        $total_scores = count( $scores );

        if ( $total_scores === 0 ) {
            return array(
                'total'             => 0,
                'mean'              => array(
                    'value'     => null,
                    'formatted' => null,
                ),
                'median'            => array(
                    'value'     => null,
                    'formatted' => null,
                ),
                'distribution'      => self::build_score_distribution( array(), $score_max ),
                'platform_rankings' => array(),
                'divergence_badges' => array(),
                'badge_threshold'   => $badge_threshold,
            );
        }

        sort( $scores );

        $mean_value   = array_sum( $scores ) / $total_scores;
        $median_value = self::calculate_median_from_sorted_scores( $scores );

        $distribution = self::build_score_distribution( $scores, $score_max );

        $platform_rankings = array();
        foreach ( $platforms as $data ) {
            $average             = $data['count'] > 0 ? $data['sum'] / $data['count'] : null;
            $platform_rankings[] = array(
                'slug'              => $data['slug'],
                'label'             => $data['label'],
                'count'             => $data['count'],
                'average'           => $average !== null ? round( $average, 1 ) : null,
                'average_formatted' => $average !== null ? number_format_i18n( $average, 1 ) : null,
            );
        }

        usort(
            $platform_rankings,
            static function ( $a, $b ) {
                $avg_a = $a['average'] ?? null;
                $avg_b = $b['average'] ?? null;

                if ( $avg_a === $avg_b ) {
                    if ( $a['count'] === $b['count'] ) {
                        return strcmp( (string) $a['label'], (string) $b['label'] );
                    }

                    return $b['count'] <=> $a['count'];
                }

                if ( $avg_a === null ) {
                    return 1;
                }

                if ( $avg_b === null ) {
                    return -1;
                }

                return $avg_b <=> $avg_a;
            }
        );

        usort(
            $divergence_candidates,
            static function ( $a, $b ) {
                $abs_a = $a['absolute_delta_raw'] ?? 0;
                $abs_b = $b['absolute_delta_raw'] ?? 0;

                if ( $abs_a === $abs_b ) {
                    $count_a = $a['user_rating_count'] ?? 0;
                    $count_b = $b['user_rating_count'] ?? 0;

                    if ( $count_a === $count_b ) {
                        return ( $a['post_id'] ?? 0 ) <=> ( $b['post_id'] ?? 0 );
                    }

                    return $count_b <=> $count_a;
                }

                return $abs_b <=> $abs_a;
            }
        );

        $divergence_candidates = array_slice( $divergence_candidates, 0, $badge_limit );

        foreach ( $divergence_candidates as $index => $candidate ) {
            unset( $divergence_candidates[ $index ]['absolute_delta_raw'] );
        }

        return array(
            'total'             => $total_scores,
            'mean'              => array(
                'value'     => round( $mean_value, 1 ),
                'formatted' => number_format_i18n( $mean_value, 1 ),
            ),
            'median'            => array(
                'value'     => $median_value,
                'formatted' => $median_value !== null ? number_format_i18n( $median_value, 1 ) : null,
            ),
            'distribution'      => $distribution,
            'platform_rankings' => $platform_rankings,
            'divergence_badges' => $divergence_candidates,
            'badge_threshold'   => $badge_threshold,
        );
    }

    private static function calculate_median_from_sorted_scores( array $scores ) {
        $count = count( $scores );

        if ( $count === 0 ) {
            return null;
        }

        $middle = (int) floor( $count / 2 );

        if ( $count % 2 === 0 ) {
            $median = ( $scores[ $middle - 1 ] + $scores[ $middle ] ) / 2;
        } else {
            $median = $scores[ $middle ];
        }

        return round( $median, 1 );
    }

    private static function build_score_distribution( array $scores, $score_max ) {
        $bucket_count = 5;
        $step         = $bucket_count > 0 ? max( 0.1, $score_max / $bucket_count ) : $score_max;
        $buckets      = array();

        for ( $i = 0; $i < $bucket_count; $i++ ) {
            $from  = $i * $step;
            $to    = ( $i === $bucket_count - 1 ) ? $score_max : ( $from + $step );
            $label = sprintf(
                /* translators: 1: start of the score range, 2: end of the score range. */
                _x( '%1$s – %2$s', 'Score range label', 'notation-jlg' ),
                number_format_i18n( $from, 0 ),
                number_format_i18n( $to, 0 )
            );

            $buckets[ $i ] = array(
                'label'      => $label,
                'from'       => (float) $from,
                'to'         => (float) $to,
                'count'      => 0,
                'percentage' => 0.0,
            );
        }

        $total_scores = count( $scores );

        if ( $total_scores === 0 ) {
            return array_values( $buckets );
        }

        foreach ( $scores as $score ) {
            $index = (int) floor( $score / $step );

            if ( $index < 0 ) {
                $index = 0;
            } elseif ( $index >= $bucket_count ) {
                $index = $bucket_count - 1;
            }

            ++$buckets[ $index ]['count'];
        }

        foreach ( $buckets as $i => $bucket ) {
            if ( $bucket['count'] > 0 ) {
                $percentage                  = ( $bucket['count'] / $total_scores ) * 100;
                $buckets[ $i ]['percentage'] = round( $percentage, 1 );
            }
        }

        return array_values( $buckets );
    }

    private static function format_signed_score_delta( $value ) {
        if ( ! is_numeric( $value ) ) {
            return null;
        }

        $normalized  = round( (float) $value, 1 );
        $absolute    = abs( $normalized );
        $formatted   = number_format_i18n( $absolute, 1 );
        $has_sign    = $normalized > 0 || $normalized < 0;
        $sign_prefix = '';

        if ( $normalized > 0 ) {
            $sign_prefix = '+';
        } elseif ( $normalized < 0 ) {
            $sign_prefix = '-';
        }

        return $has_sign ? $sign_prefix . $formatted : $formatted;
    }

    /**
     * Récupère les tags associés à une plateforme donnée.
     *
     * @param string $platform_key Identifiant de la plateforme.
     *
     * @return WP_Term[]
     */
    public static function get_platform_tags( $platform_key ) {
        $platform_key = sanitize_key( $platform_key );

        if ( $platform_key === '' ) {
            return array();
        }

        $map = get_option( self::PLATFORM_TAG_OPTION, array() );

        if ( ! is_array( $map ) || ! isset( $map[ $platform_key ] ) ) {
            return array();
        }

        $stored_tags = $map[ $platform_key ];

        if ( ! is_array( $stored_tags ) ) {
            $stored_tags = $stored_tags === '' ? array() : array( $stored_tags );
        }

        $ids   = array();
        $slugs = array();

        foreach ( $stored_tags as $value ) {
            if ( is_numeric( $value ) ) {
                $tag_id = (int) $value;

                if ( $tag_id > 0 ) {
                    $ids[] = $tag_id;
                }
            } else {
                $slug = sanitize_title( $value );

                if ( $slug !== '' ) {
                    $slugs[] = $slug;
                }
            }
        }

        $terms = array();

        if ( ! empty( $ids ) ) {
            $terms_by_id = get_terms(
                array(
                    'taxonomy'   => 'post_tag',
                    'hide_empty' => false,
                    'include'    => $ids,
                )
            );

            if ( ! is_wp_error( $terms_by_id ) ) {
                $terms = array_merge( $terms, $terms_by_id );
            }
        }

        if ( ! empty( $slugs ) ) {
            $terms_by_slug = get_terms(
                array(
                    'taxonomy'   => 'post_tag',
                    'hide_empty' => false,
                    'slug'       => $slugs,
                )
            );

            if ( ! is_wp_error( $terms_by_slug ) ) {
                $terms = array_merge( $terms, $terms_by_slug );
            }
        }

        if ( empty( $terms ) ) {
            return array();
        }

        $unique_terms = array();

        foreach ( $terms as $term ) {
            if ( $term instanceof WP_Term ) {
                $unique_terms[ $term->term_id ] = $term;
            }
        }

        return array_values( $unique_terms );
    }

    /**
     * Réinitialise toutes les options du plugin
     */
    public static function reset_all_settings() {
        delete_option( self::$option_name );
        return true;
    }
}

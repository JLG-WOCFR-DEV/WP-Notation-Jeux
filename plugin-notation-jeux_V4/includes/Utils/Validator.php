<?php

namespace JLG\Notation\Utils;

use DateTime;
use JLG\Notation\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Validator {
    private static $allowed_pegi_values     = array( '3', '7', '12', '16', '18' );
    private static $allowed_video_providers = array(
        'youtube'     => array(
            'label'   => 'YouTube',
            'domains' => array(
                'youtube.com',
                'www.youtube.com',
                'm.youtube.com',
                'youtu.be',
                'www.youtu.be',
                'youtube-nocookie.com',
                'www.youtube-nocookie.com',
            ),
        ),
        'vimeo'       => array(
            'label'   => 'Vimeo',
            'domains' => array(
                'vimeo.com',
                'www.vimeo.com',
                'player.vimeo.com',
            ),
        ),
        'twitch'      => array(
            'label'   => 'Twitch',
            'domains' => array(
                'twitch.tv',
                'www.twitch.tv',
                'm.twitch.tv',
                'player.twitch.tv',
                'clips.twitch.tv',
            ),
        ),
        'dailymotion' => array(
            'label'   => 'Dailymotion',
            'domains' => array(
                'dailymotion.com',
                'www.dailymotion.com',
                'dai.ly',
            ),
        ),
    );

    public static function is_valid_score( $score, $allow_empty = true ) {
        if ( ( $score === '' || $score === null ) && $allow_empty ) {
            return true;
        }

        if ( ! is_numeric( $score ) ) {
            return false;
        }

        $numeric_score = floatval( $score );
        $score_max     = Helpers::get_score_max();

        return $numeric_score >= 0 && $numeric_score <= $score_max;
    }

    public static function sanitize_score( $score ) {
        if ( ! self::is_valid_score( $score ) ) {
            return null;
        }

        if ( $score === '' || $score === null ) {
            return '';
        }

        $score_max = Helpers::get_score_max();
        $value     = max( 0, min( $score_max, floatval( $score ) ) );

        return round( $value, 1 );
    }

    public static function validate_notation_data( $post_data ) {
        $sanitized_data = array();
        $errors         = array();

        $meta_key_map = array();

        if ( class_exists( Helpers::class ) ) {
            foreach ( Helpers::get_rating_category_definitions() as $definition ) {
                $primary_meta_key = isset( $definition['meta_key'] ) ? (string) $definition['meta_key'] : '';

                if ( $primary_meta_key === '' ) {
                    continue;
                }

                $meta_key_map[ $primary_meta_key ] = $primary_meta_key;

                if ( ! empty( $definition['legacy_meta_keys'] ) && is_array( $definition['legacy_meta_keys'] ) ) {
                    foreach ( $definition['legacy_meta_keys'] as $legacy_meta_key ) {
                        $legacy_meta_key = (string) $legacy_meta_key;

                        if ( $legacy_meta_key === '' ) {
                            continue;
                        }

                        $meta_key_map[ $legacy_meta_key ] = $primary_meta_key;
                    }
                }
            }
        }

        if ( empty( $meta_key_map ) ) {
            foreach ( array( 'cat1', 'cat2', 'cat3', 'cat4', 'cat5', 'cat6' ) as $legacy_suffix ) {
                $meta_key_map[ '_note_' . $legacy_suffix ] = '_note_' . $legacy_suffix;
            }
        }

        foreach ( $meta_key_map as $input_key => $normalized_key ) {
            if ( ! isset( $post_data[ $input_key ] ) ) {
                continue;
            }

            $score = $post_data[ $input_key ];

            if ( self::is_valid_score( $score ) ) {
                $sanitized_score = self::sanitize_score( $score );

                if ( $sanitized_score === null ) {
                    continue;
                }

                $sanitized_data[ $normalized_key ] = $sanitized_score;
            } else {
                $errors[ $input_key ] = 'Score invalide';
            }
        }

        return array(
            'is_valid'       => empty( $errors ),
            'errors'         => $errors,
            'sanitized_data' => $sanitized_data,
        );
    }

    public static function sanitize_platforms( $platforms ) {
        if ( ! is_array( $platforms ) ) {
            return array();
        }

        $extract_labels = static function ( $definitions ) {
            if ( ! is_array( $definitions ) ) {
                return array();
            }

            $labels = array();

            foreach ( $definitions as $definition ) {
                if ( is_string( $definition ) ) {
                    $labels[] = sanitize_text_field( $definition );
                } elseif ( is_array( $definition ) && isset( $definition['name'] ) && is_string( $definition['name'] ) ) {
                    $labels[] = sanitize_text_field( $definition['name'] );
                }
            }

            $labels = array_filter(
                $labels,
                static function ( $label ) {
					return $label !== '';
				}
            );

            return array_values( array_unique( $labels ) );
        };

        $allowed_platforms = array();

        if ( class_exists( \JLG\Notation\Admin\Platforms::class ) ) {
            $platform_manager = \JLG\Notation\Admin\Platforms::get_instance();
            if ( $platform_manager && method_exists( $platform_manager, 'get_platform_names' ) ) {
                $platform_names = $platform_manager->get_platform_names();
                if ( is_array( $platform_names ) ) {
                    $allowed_platforms = array_map( 'sanitize_text_field', array_values( $platform_names ) );
                }
            }
        }

        if ( empty( $allowed_platforms ) && class_exists( \JLG\Notation\Helpers::class ) && method_exists( \JLG\Notation\Helpers::class, 'get_registered_platform_labels' ) ) {
            $default_definitions = \JLG\Notation\Helpers::get_registered_platform_labels();

            $allowed_platforms = $extract_labels( $default_definitions );
        }

        if ( empty( $allowed_platforms ) && class_exists( \JLG\Notation\Helpers::class ) && method_exists( \JLG\Notation\Helpers::class, 'get_default_platform_definitions' ) ) {
            $default_definitions = array();

            if ( is_callable( array( \JLG\Notation\Helpers::class, 'get_default_platform_definitions' ) ) ) {
                $default_definitions = \JLG\Notation\Helpers::get_default_platform_definitions();
            } else {
                try {
                    $reflection = new \ReflectionMethod( \JLG\Notation\Helpers::class, 'get_default_platform_definitions' );

                    if ( ! $reflection->isPublic() ) {
                        $reflection->setAccessible( true );
                    }

                    $default_definitions = $reflection->invoke( null );
                } catch ( \ReflectionException $exception ) {
                    $default_definitions = array();
                }
            }

            $allowed_platforms = $extract_labels( $default_definitions );
        }

        if ( empty( $allowed_platforms ) ) {
            $allowed_platforms = array_map(
                'sanitize_text_field',
                array(
					'PC',
					'PlayStation 5',
					'Xbox Series S/X',
					'Nintendo Switch',
					'PlayStation 4',
					'Xbox One',
					'Steam Deck',
				)
            );
        }

        $sanitized = array_filter(
            array_map( 'sanitize_text_field', $platforms ),
            static function ( $platform ) {
                return is_string( $platform ) && $platform !== '';
            }
        );

        if ( empty( $sanitized ) ) {
            return array();
        }

        $allowed_map = array();

        foreach ( $allowed_platforms as $allowed_platform ) {
            $normalized_key = strtolower( sanitize_text_field( $allowed_platform ) );

            if ( $normalized_key === '' || isset( $allowed_map[ $normalized_key ] ) ) {
                continue;
            }

            $allowed_map[ $normalized_key ] = sanitize_text_field( $allowed_platform );
        }

        if ( empty( $allowed_map ) ) {
            return array();
        }

        $normalized_results = array();

        foreach ( $sanitized as $platform ) {
            $normalized_key = strtolower( $platform );

            if ( isset( $allowed_map[ $normalized_key ] ) ) {
                $normalized_results[] = $allowed_map[ $normalized_key ];
            }
        }

        return array_values( array_unique( $normalized_results ) );
    }

    public static function get_allowed_video_providers() {
        return array_keys( self::$allowed_video_providers );
    }

    public static function get_video_provider_label( $provider ) {
        $provider = self::sanitize_video_provider( $provider );

        if ( $provider === '' ) {
            return '';
        }

        return isset( self::$allowed_video_providers[ $provider ]['label'] )
            ? self::$allowed_video_providers[ $provider ]['label']
            : '';
    }

    public static function sanitize_video_provider( $provider ) {
        if ( ! is_string( $provider ) ) {
            return '';
        }

        $provider = strtolower( trim( $provider ) );

        return isset( self::$allowed_video_providers[ $provider ] ) ? $provider : '';
    }

    public static function detect_video_provider_from_url( $url ) {
        if ( ! is_string( $url ) || $url === '' ) {
            return '';
        }

        $host = wp_parse_url( $url, PHP_URL_HOST );

        if ( ! is_string( $host ) || $host === '' ) {
            $parts = wp_parse_url( $url );
            if ( is_array( $parts ) && isset( $parts['host'] ) ) {
                $host = $parts['host'];
            }
        }

        if ( ! is_string( $host ) || $host === '' ) {
            return '';
        }

        $host = strtolower( $host );
        $host = preg_replace( '/^www\./', '', $host );

        foreach ( self::$allowed_video_providers as $provider => $definition ) {
            foreach ( $definition['domains'] as $domain ) {
                $domain = strtolower( $domain );

                if ( $host === $domain || substr( $host, - strlen( $domain ) ) === $domain ) {
                    return $provider;
                }
            }
        }

        return '';
    }

    public static function sanitize_review_video_data( $url, $provider = '' ) {
        $raw_url            = is_string( $url ) ? trim( $url ) : '';
        $sanitized_provider = self::sanitize_video_provider( $provider );

        if ( $raw_url === '' ) {
            return array(
                'url'      => '',
                'provider' => $sanitized_provider,
                'error'    => null,
            );
        }

        $sanitized_url = esc_url_raw( $raw_url );

        if ( $sanitized_url === '' || ! self::is_valid_http_url( $sanitized_url ) ) {
            return array(
                'url'      => '',
                'provider' => '',
                'error'    => __( 'URL de vidéo invalide. Utilisez un lien complet commençant par http ou https.', 'notation-jlg' ),
            );
        }

        $detected_provider = self::detect_video_provider_from_url( $sanitized_url );

        if ( $sanitized_provider !== '' && $detected_provider !== '' && $sanitized_provider !== $detected_provider ) {
            return array(
                'url'      => '',
                'provider' => '',
                'error'    => __( 'Le fournisseur sélectionné ne correspond pas à l\'URL fournie.', 'notation-jlg' ),
            );
        }

        if ( $sanitized_provider === '' ) {
            $sanitized_provider = $detected_provider;
        }

        if ( $sanitized_provider === '' ) {
            $labels = array();

            foreach ( self::$allowed_video_providers as $definition ) {
                if ( isset( $definition['label'] ) ) {
                    $labels[] = $definition['label'];
                }
            }

            return array(
                'url'      => '',
                'provider' => '',
                'error'    => sprintf(
                    /* translators: %s: list of allowed video providers. */
                    __( 'Fournisseur vidéo non reconnu. Fournisseurs acceptés : %s.', 'notation-jlg' ),
                    implode( ', ', $labels )
                ),
            );
        }

        return array(
            'url'      => $sanitized_url,
            'provider' => $sanitized_provider,
            'error'    => null,
        );
    }

    public static function is_valid_http_url( $url ) {
        if ( ! is_string( $url ) || $url === '' ) {
            return false;
        }

        if ( function_exists( 'wp_http_validate_url' ) ) {
            return (bool) wp_http_validate_url( $url );
        }

        return filter_var( $url, FILTER_VALIDATE_URL ) !== false;
    }

    public static function validate_date( $date, $allow_empty = true ) {
        if ( $date === '' || $date === null ) {
            return $allow_empty;
        }

        $date_time = DateTime::createFromFormat( 'Y-m-d', $date );

        return $date_time instanceof DateTime && $date_time->format( 'Y-m-d' ) === $date;
    }

    public static function sanitize_date( $date ) {
        if ( ! self::validate_date( $date, false ) ) {
            return null;
        }

        $date_time = DateTime::createFromFormat( 'Y-m-d', $date );

        return $date_time ? $date_time->format( 'Y-m-d' ) : null;
    }

    public static function get_allowed_pegi_values() {
        return self::$allowed_pegi_values;
    }

    private static function normalize_pegi_value( $pegi ) {
        if ( $pegi === '' || $pegi === null ) {
            return null;
        }

        $normalized = strtoupper( trim( $pegi ) );
        $normalized = str_replace( 'PEGI', '', $normalized );
        $normalized = str_replace( '+', '', $normalized );
        $normalized = preg_replace( '/[^0-9]/', '', $normalized );

        if ( $normalized === '' ) {
            return null;
        }

        return in_array( $normalized, self::$allowed_pegi_values, true ) ? $normalized : null;
    }

    public static function validate_pegi( $pegi, $allow_empty = true ) {
        if ( $pegi === '' || $pegi === null ) {
            return $allow_empty;
        }

        return self::normalize_pegi_value( $pegi ) !== null;
    }

    public static function sanitize_pegi( $pegi ) {
        $normalized = self::normalize_pegi_value( $pegi );

        if ( $normalized === null ) {
            return null;
        }

        return 'PEGI ' . $normalized;
    }
}

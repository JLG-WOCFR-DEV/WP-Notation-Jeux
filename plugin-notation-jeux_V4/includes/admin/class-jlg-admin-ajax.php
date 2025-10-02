<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class JLG_Admin_Ajax {

    public function __construct() {
        add_action( 'wp_ajax_jlg_search_rawg_games', array( $this, 'handle_rawg_search' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_ajax_assets' ) );
    }

    public static function process_rawg_search( array $params ) {
        $search_term = isset( $params['search'] ) ? sanitize_text_field( (string) self::maybe_scalar( $params['search'] ) ) : '';

        if ( $search_term === '' ) {
            return new WP_Error(
                'jlg_rawg_empty_search',
                __( 'Terme de recherche vide.', 'notation-jlg' ),
                array( 'status' => 400 )
            );
        }

        $page = isset( $params['page'] ) ? absint( self::maybe_scalar( $params['page'] ) ) : 1;
        if ( $page < 1 ) {
            $page = 1;
        }

        $options = JLG_Helpers::get_plugin_options();
        $api_key = isset( $options['rawg_api_key'] ) ? sanitize_text_field( (string) $options['rawg_api_key'] ) : '';

        if ( $api_key === '' ) {
            $mock_games = array(
                array(
                    'name'         => $search_term . ' - Résultat simulé',
                    'release_date' => '2024-01-15',
                    'developers'   => 'Studio Exemple',
                    'publishers'   => 'Éditeur Exemple',
                    'platforms'    => array( 'PC', 'PlayStation 5' ),
                    'pegi'         => 'PEGI 12',
                ),
            );

            $normalized_games = array_map( array( __CLASS__, 'normalize_game_fields' ), $mock_games );

            return array(
                'games'   => $normalized_games,
                'message' => __( 'Recherche simulée. Configurez une vraie clé API RAWG dans les réglages pour des résultats réels.', 'notation-jlg' ),
            );
        }

        $cache_key      = 'jlg_rawg_search_' . md5( wp_json_encode( array( $search_term, $page ) ) );
        $cached_payload = get_transient( $cache_key );

        if ( $cached_payload !== false && is_array( $cached_payload ) ) {
            return $cached_payload;
        }

        $endpoint   = 'https://api.rawg.io/api/games';
        $query_args = array(
            'search'    => $search_term,
            'page'      => $page,
            'page_size' => 20,
            'key'       => $api_key,
        );

        $request_args = apply_filters(
            'jlg_rawg_http_request_args',
            array(
                'timeout' => 10,
                'headers' => array(
                    'Accept' => 'application/json',
                ),
            ),
            $search_term,
            $page
        );

        $response = wp_remote_get( add_query_arg( $query_args, $endpoint ), $request_args );

        if ( is_wp_error( $response ) ) {
            return new WP_Error(
                'jlg_rawg_http_error',
                __( 'Erreur de communication avec RAWG.io. Veuillez réessayer ultérieurement.', 'notation-jlg' ),
                array( 'status' => 502 )
            );
        }

        $status_code = (int) wp_remote_retrieve_response_code( $response );

        if ( $status_code < 200 || $status_code >= 300 ) {
            return new WP_Error(
                'jlg_rawg_bad_status',
                sprintf(
                    /* translators: %d: HTTP status code returned by RAWG.io */
                    __( 'La requête RAWG.io a échoué avec le code HTTP %d.', 'notation-jlg' ),
                    $status_code
                ),
                array( 'status' => $status_code ?: 500 )
            );
        }

        $body    = wp_remote_retrieve_body( $response );
        $decoded = json_decode( $body, true );

        if ( ! is_array( $decoded ) ) {
            return new WP_Error(
                'jlg_rawg_invalid_body',
                __( 'Réponse RAWG.io invalide : données JSON non valides.', 'notation-jlg' ),
                array( 'status' => 502 )
            );
        }

        $raw_results = array();
        if ( ! empty( $decoded['results'] ) && is_array( $decoded['results'] ) ) {
            $raw_results = array_map( array( __CLASS__, 'transform_rawg_game' ), $decoded['results'] );
        }

        $normalized_games = array_map( array( __CLASS__, 'normalize_game_fields' ), $raw_results );

        $payload = array(
            'games'      => $normalized_games,
            'pagination' => array(
                'current_page'  => $page,
                'next_page'     => self::extract_rawg_page( $decoded['next'] ?? '' ),
                'prev_page'     => self::extract_rawg_page( $decoded['previous'] ?? '' ),
                'total_results' => isset( $decoded['count'] ) ? (int) $decoded['count'] : null,
            ),
            'message'    => __( 'Résultats récupérés depuis RAWG.io.', 'notation-jlg' ),
        );

        set_transient( $cache_key, $payload, 15 * MINUTE_IN_SECONDS );

        return $payload;
    }

    public function enqueue_admin_ajax_assets( $hook_suffix ) {
        $allowed_hooks = array(
            'post.php',
            'post-new.php',
            'toplevel_page_notation_jlg_settings',
        );

        if ( ! in_array( $hook_suffix, $allowed_hooks, true ) ) {
            return;
        }

        $script_handle = 'jlg-admin-api';
        $script_url    = JLG_NOTATION_PLUGIN_URL . 'assets/js/jlg-admin-api.js';
        $version       = defined( 'JLG_NOTATION_VERSION' ) ? JLG_NOTATION_VERSION : false;

        wp_register_script( $script_handle, $script_url, array( 'jquery' ), $version, true );

        wp_localize_script(
            $script_handle,
            'jlg_admin_ajax',
            array(
                                'nonce'     => wp_create_nonce( 'jlg_admin_ajax_nonce' ),
                                'ajax_url'  => admin_url( 'admin-ajax.php' ),
                                'restUrl'   => esc_url_raw( rest_url( 'jlg/v1/rawg-search' ) ),
                                'restPath'  => 'jlg/v1/rawg-search',
                                'restNonce' => wp_create_nonce( 'wp_rest' ),
                        )
        );

        wp_enqueue_script( $script_handle );
    }

    public function handle_rawg_search() {
        check_ajax_referer( 'jlg_admin_ajax_nonce', 'nonce' );

        // Sécurité basique
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error(
                array(
                                        'message' => __( 'Permissions insuffisantes.', 'notation-jlg' ),
                ),
                403
            );
        }

        $payload = isset( $_POST ) ? wp_unslash( $_POST ) : array();
        if ( ! is_array( $payload ) ) {
            $payload = array();
        }

        $result = self::process_rawg_search( $payload );

        if ( is_wp_error( $result ) ) {
            $status = $result->get_error_data();

            if ( is_array( $status ) && isset( $status['status'] ) ) {
                $status = (int) $status['status'];
            } elseif ( ! is_numeric( $status ) ) {
                $status = 400;
            }

            wp_send_json_error(
                array(
                                        'message' => $result->get_error_message(),
                ),
                (int) $status
            );
        }

        wp_send_json_success( $result );
    }

    private static function maybe_scalar( $value ) {
        if ( is_array( $value ) ) {
            $value = reset( $value );
        }

        if ( is_scalar( $value ) || $value === null ) {
            return $value;
        }

        return '';
    }

    private static function transform_rawg_game( array $raw_game ) {
        $developers = array();
        if ( ! empty( $raw_game['developers'] ) && is_array( $raw_game['developers'] ) ) {
            foreach ( $raw_game['developers'] as $developer ) {
                if ( is_array( $developer ) && isset( $developer['name'] ) ) {
                    $developers[] = $developer['name'];
                } elseif ( is_string( $developer ) ) {
                    $developers[] = $developer;
                }
            }
        }

        $publishers = array();
        if ( ! empty( $raw_game['publishers'] ) && is_array( $raw_game['publishers'] ) ) {
            foreach ( $raw_game['publishers'] as $publisher ) {
                if ( is_array( $publisher ) && isset( $publisher['name'] ) ) {
                    $publishers[] = $publisher['name'];
                } elseif ( is_string( $publisher ) ) {
                    $publishers[] = $publisher;
                }
            }
        }

        $platforms = array();
        if ( ! empty( $raw_game['platforms'] ) && is_array( $raw_game['platforms'] ) ) {
            foreach ( $raw_game['platforms'] as $platform ) {
                if ( is_array( $platform ) ) {
                    if ( isset( $platform['platform']['name'] ) ) {
                        $platforms[] = $platform['platform']['name'];
                    } elseif ( isset( $platform['name'] ) ) {
                        $platforms[] = $platform['name'];
                    }
                } elseif ( is_string( $platform ) ) {
                    $platforms[] = $platform;
                }
            }
        }

        $pegi = '';
        if ( ! empty( $raw_game['age_rating']['name'] ) ) {
            $pegi = $raw_game['age_rating']['name'];
        } elseif ( ! empty( $raw_game['esrb_rating']['name'] ) ) {
            $pegi = $raw_game['esrb_rating']['name'];
        }

        $cover_image = '';
        $cover_candidates = array();

        if ( ! empty( $raw_game['background_image'] ) ) {
            $cover_candidates[] = $raw_game['background_image'];
        }

        if ( ! empty( $raw_game['background_image_additional'] ) ) {
            $cover_candidates[] = $raw_game['background_image_additional'];
        }

        foreach ( $cover_candidates as $candidate ) {
            if ( is_string( $candidate ) ) {
                $candidate = trim( $candidate );
                if ( $candidate !== '' ) {
                    $cover_image = $candidate;
                    break;
                }
            }
        }

        return array(
            'name'         => $raw_game['name'] ?? '',
            'release_date' => $raw_game['released'] ?? '',
            'developers'   => $developers,
            'publishers'   => $publishers,
            'platforms'    => $platforms,
            'pegi'         => $pegi,
            'cover_image'  => $cover_image,
        );
    }

    private static function normalize_game_fields( array $game ) {
        $defaults = array(
            'name'         => '',
            'release_date' => '',
            'developers'   => '',
            'publishers'   => '',
            'platforms'    => array(),
            'pegi'         => '',
            'cover_image'  => '',
        );

        $game = array_merge( $defaults, $game );

        $sanitize_scalar = static function ( $value ) {
            if ( is_scalar( $value ) ) {
                return sanitize_text_field( (string) $value );
            }

            return '';
        };

        $game['name'] = $sanitize_scalar( $game['name'] );

        foreach ( array( 'developers', 'publishers' ) as $field ) {
            if ( is_array( $game[ $field ] ) ) {
                $game[ $field ] = array_values( array_filter( array_map( $sanitize_scalar, $game[ $field ] ), 'strlen' ) );
            } else {
                $game[ $field ] = $sanitize_scalar( $game[ $field ] );
            }
        }

        if ( is_array( $game['platforms'] ) ) {
            $game['platforms'] = array_values( array_filter( array_map( $sanitize_scalar, $game['platforms'] ), 'strlen' ) );
        } else {
            $game['platforms'] = $sanitize_scalar( $game['platforms'] );
            $game['platforms'] = $game['platforms'] === '' ? array() : array( $game['platforms'] );
        }

        if ( $game['release_date'] !== '' ) {
            $sanitized_date       = JLG_Validator::sanitize_date( $game['release_date'] );
            $game['release_date'] = $sanitized_date !== null ? $sanitized_date : '';
        }

        if ( $game['pegi'] !== '' ) {
            $sanitized_pegi = JLG_Validator::sanitize_pegi( $game['pegi'] );
            $game['pegi']   = $sanitized_pegi !== null ? $sanitized_pegi : '';
        }

        if ( $game['cover_image'] !== '' ) {
            if ( is_scalar( $game['cover_image'] ) ) {
                $cover_value      = trim( (string) $game['cover_image'] );
                $sanitized_cover  = esc_url_raw( $cover_value );
                $is_valid_sanitize = is_string( $sanitized_cover ) && $sanitized_cover !== '' && filter_var( $sanitized_cover, FILTER_VALIDATE_URL );
                $game['cover_image'] = $is_valid_sanitize ? $sanitized_cover : '';
            } else {
                $game['cover_image'] = '';
            }
        }

        return $game;
    }

    private static function extract_rawg_page( $url ) {
        if ( ! is_string( $url ) || $url === '' ) {
            return null;
        }

        $parts = wp_parse_url( $url );

        if ( ! is_array( $parts ) || empty( $parts['query'] ) ) {
            return null;
        }

        parse_str( $parts['query'], $query_args );

        if ( empty( $query_args['page'] ) ) {
            return null;
        }

        $page = absint( $query_args['page'] );

        return $page > 0 ? $page : null;
    }
}

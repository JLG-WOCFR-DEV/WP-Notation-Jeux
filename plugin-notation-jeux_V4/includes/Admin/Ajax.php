<?php

namespace JLG\Notation\Admin;

use JLG\Notation\Helpers;
use JLG\Notation\Utils\OpenCriticClient;
use JLG\Notation\Utils\Validator;

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

class Ajax {

    public function __construct() {
        add_action( 'wp_ajax_jlg_search_rawg_games', array( $this, 'handle_rawg_search' ) );
        add_action( 'wp_ajax_jlg_search_opencritic_games', array( $this, 'handle_opencritic_search' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_ajax_assets' ) );
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
				'nonce'    => wp_create_nonce( 'jlg_admin_ajax_nonce' ),
				'ajax_url' => admin_url( 'admin-ajax.php' ),
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

        $search_term = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

        if ( empty( $search_term ) ) {
            wp_send_json_error(
                array(
					'message' => __( 'Terme de recherche vide.', 'notation-jlg' ),
                ),
                400
            );
        }

        $page = isset( $_POST['page'] ) ? max( 1, absint( wp_unslash( $_POST['page'] ) ) ) : 1;

        $options = Helpers::get_plugin_options();
        $api_key = isset( $options['rawg_api_key'] ) ? sanitize_text_field( (string) $options['rawg_api_key'] ) : '';

        if ( $api_key === '' ) {
            // Simulation de réponse si aucune clé n'est configurée.
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

            $normalized_games = array_map( array( $this, 'normalize_game_fields' ), $mock_games );

            wp_send_json_success(
                array(
					'games'   => $normalized_games,
					'message' => __( 'Recherche simulée. Configurez une vraie clé API RAWG dans les réglages pour des résultats réels.', 'notation-jlg' ),
                )
            );
        }

        $cache_key      = 'jlg_rawg_search_' . md5( wp_json_encode( array( $search_term, $page ) ) );
        $cached_payload = get_transient( $cache_key );

        if ( $cached_payload !== false && is_array( $cached_payload ) ) {
            wp_send_json_success( $cached_payload );
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

        if ( function_exists( 'is_wp_error' ) && is_wp_error( $response ) ) {
            wp_send_json_error(
                array(
					'message' => __( 'Erreur de communication avec RAWG.io. Veuillez réessayer ultérieurement.', 'notation-jlg' ),
                ),
                502
            );
        }

        $status_code = (int) wp_remote_retrieve_response_code( $response );

        if ( $status_code < 200 || $status_code >= 300 ) {
            wp_send_json_error(
                array(
					'message' => sprintf(
						/* translators: %d: HTTP status code returned by RAWG.io */
						__( 'La requête RAWG.io a échoué avec le code HTTP %d.', 'notation-jlg' ),
						$status_code
					),
                ),
                $status_code ?: 500
            );
        }

        $body    = wp_remote_retrieve_body( $response );
        $decoded = json_decode( $body, true );

        if ( ! is_array( $decoded ) ) {
            wp_send_json_error(
                array(
					'message' => __( 'Réponse RAWG.io invalide : données JSON non valides.', 'notation-jlg' ),
                ),
                502
            );
        }

        $raw_results = array();
        if ( ! empty( $decoded['results'] ) && is_array( $decoded['results'] ) ) {
            $raw_results = array_map( array( $this, 'transform_rawg_game' ), $decoded['results'] );
        }

        $normalized_games = array_map( array( $this, 'normalize_game_fields' ), $raw_results );

        $payload = array(
            'games'      => $normalized_games,
            'pagination' => array(
                'current_page'  => $page,
                'next_page'     => $this->extract_rawg_page( $decoded['next'] ?? '' ),
                'prev_page'     => $this->extract_rawg_page( $decoded['previous'] ?? '' ),
                'total_results' => isset( $decoded['count'] ) ? (int) $decoded['count'] : null,
            ),
            'message'    => __( 'Résultats récupérés depuis RAWG.io.', 'notation-jlg' ),
        );

        set_transient( $cache_key, $payload, 15 * MINUTE_IN_SECONDS );

        wp_send_json_success( $payload );
    }

    public function handle_opencritic_search() {
        check_ajax_referer( 'jlg_admin_ajax_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Permissions insuffisantes.', 'notation-jlg' ),
                ),
                403
            );
        }

        $search_term = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

        if ( mb_strlen( $search_term ) < 3 ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Veuillez saisir au moins 3 caractères.', 'notation-jlg' ),
                ),
                400
            );
        }

        $options = Helpers::get_plugin_options();
        $api_key = isset( $options['opencritic_api_key'] ) ? (string) $options['opencritic_api_key'] : '';

        $client = new OpenCriticClient( $api_key );
        $limit  = isset( $_POST['limit'] ) ? absint( wp_unslash( $_POST['limit'] ) ) : 6;
        $limit  = $limit > 0 ? $limit : 6;

        $results = $client->search_games( $search_term, $limit );

        if ( is_wp_error( $results ) ) {
            wp_send_json_error(
                array(
                    'message' => $results->get_error_message(),
                ),
                502
            );
        }

        $games = array();

        foreach ( $results as $entry ) {
            if ( ! is_array( $entry ) ) {
                continue;
            }

            $id   = isset( $entry['id'] ) ? absint( $entry['id'] ) : 0;
            $name = isset( $entry['name'] ) ? sanitize_text_field( $entry['name'] ) : '';
            $slug = isset( $entry['slug'] ) ? sanitize_title( $entry['slug'] ) : '';

            $score = null;
            if ( isset( $entry['topCriticScore'] ) ) {
                $raw_score = $entry['topCriticScore'];
                if ( is_string( $raw_score ) ) {
                    $raw_score = str_replace( ',', '.', $raw_score );
                }
                if ( is_numeric( $raw_score ) ) {
                    $score = round( max( 0, min( 100, (float) $raw_score ) ), 1 );
                }
            }

            $release_year = null;
            if ( isset( $entry['releaseYear'] ) && is_numeric( $entry['releaseYear'] ) ) {
                $release_year = (int) $entry['releaseYear'];
            }

            $games[] = array(
                'id'           => $id,
                'name'         => $name,
                'slug'         => $slug,
                'score'        => $score,
                'tier'         => isset( $entry['tier'] ) ? sanitize_text_field( $entry['tier'] ) : '',
                'release_year' => $release_year,
                'url'          => isset( $entry['url'] ) ? esc_url_raw( $entry['url'] ) : '',
            );
        }

        if ( empty( $games ) ) {
            wp_send_json_success(
                array(
                    'games'   => array(),
                    'message' => __( 'Aucun jeu correspondant n’a été trouvé sur OpenCritic.', 'notation-jlg' ),
                )
            );
        }

        $message = $client->is_mock_mode()
            ? __( 'Résultats simulés. Ajoutez une clé API OpenCritic pour obtenir les données réelles.', 'notation-jlg' )
            : __( 'Résultats OpenCritic récupérés.', 'notation-jlg' );

        wp_send_json_success(
            array(
                'games'   => $games,
                'message' => $message,
            )
        );
    }

    private function transform_rawg_game( array $raw_game ) {
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

    private function normalize_game_fields( array $game ) {
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
            $sanitized_date       = Validator::sanitize_date( $game['release_date'] );
            $game['release_date'] = $sanitized_date !== null ? $sanitized_date : '';
        }

        if ( $game['pegi'] !== '' ) {
            $sanitized_pegi = Validator::sanitize_pegi( $game['pegi'] );
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

    private function extract_rawg_page( $url ) {
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

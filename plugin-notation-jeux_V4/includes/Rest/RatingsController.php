<?php

namespace JLG\Notation\Rest;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use JLG\Notation\Frontend;
use JLG\Notation\Helpers;
use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RatingsController {

    private $namespace = 'jlg/v1';

    private $rest_base = 'ratings';

    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        if ( ! function_exists( 'register_rest_route' ) ) {
            return;
        }

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'handle_get_ratings' ),
                    'permission_callback' => array( $this, 'permissions_check' ),
                    'args'                => $this->get_collection_params(),
                ),
            )
        );
    }

    public function permissions_check( $request ) {
        $is_public = apply_filters( 'jlg_ratings_rest_is_public', false, $request );

        if ( $is_public ) {
            return true;
        }

        $can_check_caps = function_exists( 'current_user_can' );
        $is_logged_in   = function_exists( 'is_user_logged_in' ) ? is_user_logged_in() : false;

        if ( $can_check_caps && $is_logged_in && current_user_can( 'read' ) ) {
            return true;
        }

        if ( $can_check_caps && isset( $GLOBALS['jlg_test_current_user_can'] ) && current_user_can( 'read' ) ) {
            return true;
        }

        $status = function_exists( 'rest_authorization_required_code' )
            ? rest_authorization_required_code()
            : 401;

        return new \WP_Error(
            'jlg_ratings_rest_forbidden',
            __( 'Vous n’avez pas l’autorisation d’accéder aux notes.', 'notation-jlg' ),
            array( 'status' => $status )
        );
    }

    public function handle_get_ratings( $request ) {
        $params = $this->prepare_request_params( $request );

        $query_result    = $this->collect_candidate_posts( $params );
        $candidate_posts = isset( $query_result['posts'] ) ? $query_result['posts'] : array();
        $total_items     = isset( $query_result['found_posts'] ) ? (int) $query_result['found_posts'] : count( $candidate_posts );
        $total_pages     = isset( $query_result['max_num_pages'] ) ? (int) $query_result['max_num_pages'] : ( $params['per_page'] > 0 ? (int) ceil( count( $candidate_posts ) / $params['per_page'] ) : 0 );

        $registered = Helpers::get_registered_platform_labels();
        $score_max  = max( 1.0, (float) Helpers::get_score_max() );
        $records    = array();

        foreach ( $candidate_posts as $post ) {
            if ( ! ( $post instanceof \WP_Post ) ) {
                continue;
            }

            $post_id = (int) $post->ID;

            if ( $post_id <= 0 ) {
                continue;
            }

            $score_data = Helpers::get_resolved_average_score( $post_id );
            $score      = isset( $score_data['value'] ) && is_numeric( $score_data['value'] )
                ? (float) $score_data['value']
                : null;

            if ( $score === null ) {
                continue;
            }

            $title = get_the_title( $post_id );
            if ( ! is_string( $title ) ) {
                $title = '';
            }

            $title = wp_strip_all_tags( $title );

            if ( $params['search'] !== '' && ! $this->matches_search_filter( $title, $post->post_name ?? '', $params['search'] ) ) {
                continue;
            }

            $platform_labels = $this->get_post_platform_labels( $post_id );

            if ( $params['platform'] !== '' && ! $this->matches_platform_filter( $platform_labels, $params['platform'], $registered ) ) {
                continue;
            }

            $user_average_raw = get_post_meta( $post_id, '_jlg_user_rating_avg', true );
            $user_average     = is_numeric( $user_average_raw ) ? round( (float) $user_average_raw, 1 ) : null;
            $user_count       = (int) get_post_meta( $post_id, '_jlg_user_rating_count', true );

            $delta_value = null;
            if ( $user_average !== null ) {
                $delta_value = round( $score - $user_average, 1 );
            }

            $histogram_raw = array();
            if ( class_exists( Frontend::class ) ) {
                $histogram_raw = Frontend::get_user_rating_breakdown_for_post( $post_id );
            }

            $histogram = $this->build_histogram_payload( $histogram_raw, $user_count );

            $platform_payload = $this->normalize_platform_payload( $platform_labels, $registered );
            $platform_matrix  = Helpers::get_platform_breakdown_for_post( $post_id );
            $review_status    = Helpers::get_review_status_for_post( $post_id );
            $timestamps       = array(
                'published' => $this->resolve_post_datetime( $post_id, 'date' ),
                'modified'  => $this->resolve_post_datetime( $post_id, 'modified' ),
            );

            $records[] = array(
                'id'                   => $post_id,
                'title'                => $title,
                'slug'                 => isset( $post->post_name ) ? sanitize_title( $post->post_name ) : sanitize_title( $title ),
                'permalink'            => esc_url_raw( get_permalink( $post_id ) ),
                'editorial_value'      => $score,
                'editorial_formatted'  => isset( $score_data['formatted'] ) ? (string) $score_data['formatted'] : number_format_i18n( $score, 1 ),
                'user_average'         => $user_average,
                'user_formatted'       => $user_average !== null ? number_format_i18n( $user_average, 1 ) : '',
                'user_votes'           => max( 0, $user_count ),
                'delta_value'          => $delta_value,
                'delta_formatted'      => $this->format_delta_label( $delta_value ),
                'histogram'            => $histogram,
                'platform_labels'      => $platform_labels,
                'platform_payload'     => $platform_payload,
                'platform_breakdown'   => $platform_matrix,
                'review_status'        => $review_status,
                'timestamps'           => $timestamps,
                'score_percentage'     => $score_max > 0 ? round( ( $score / $score_max ) * 100, 1 ) : null,
                'sort_timestamp'       => $timestamps['published']['timestamp'] ?? strtotime( $post->post_date_gmt ?? $post->post_date ?? 'now' ),
                'searchable_platforms' => $platform_labels,
                'searchable_slug'      => isset( $post->post_name ) ? (string) $post->post_name : '',
            );
        }

        if ( $params['post_id'] > 0 || $params['slug'] !== '' ) {
            $records = array_values(
                array_filter(
                    $records,
                    function ( $record ) use ( $params ) {
                        if ( $params['post_id'] > 0 && (int) $record['id'] !== $params['post_id'] ) {
                            return false;
                        }

                        if ( $params['slug'] !== '' && $record['slug'] !== $params['slug'] ) {
                            return false;
                        }

                        return true;
                    }
                )
            );

            if ( empty( $records ) ) {
                return new \WP_Error(
                    'jlg_ratings_rest_not_found',
                    __( 'Aucun test noté trouvé pour ce contenu.', 'notation-jlg' ),
                    array( 'status' => 404 )
                );
            }
        }

        if ( empty( $records ) ) {
            $empty_summary = Helpers::get_posts_score_insights( array() );

            return rest_ensure_response(
                array(
                    'items'        => array(),
                    'pagination'   => array(
                        'total'       => 0,
                        'total_pages' => 0,
                        'per_page'    => $params['per_page'],
                        'page'        => 1,
                    ),
                    'summary'      => $empty_summary,
                    'filters'      => $this->expose_filters( $params ),
                    'score_max'    => $score_max,
                    'platforms'    => $registered,
                    'generated_at' => gmdate( 'c', $this->current_gmt_timestamp() ),
                )
            );
        }

        $this->sort_records( $records, $params['orderby'], $params['order'] );

        if ( $total_pages > 0 ) {
            $params['page'] = min( max( 1, $params['page'] ), $total_pages );
        } else {
            $params['page'] = 1;
        }

        $items = array();
        foreach ( $records as $record ) {
            $items[] = array(
                'id'            => $record['id'],
                'title'         => $record['title'],
                'slug'          => $record['slug'],
                'permalink'     => $record['permalink'],
                'editorial'     => array(
                    'score'      => $record['editorial_value'],
                    'formatted'  => $record['editorial_formatted'],
                    'percentage' => $record['score_percentage'],
                    'scale'      => $score_max,
                ),
                'readers'       => array(
                    'average'   => $record['user_average'],
                    'formatted' => $record['user_formatted'],
                    'votes'     => $record['user_votes'],
                    'delta'     => array(
                        'value'     => $record['delta_value'],
                        'formatted' => $record['delta_formatted'],
                    ),
                    'histogram' => $record['histogram'],
                ),
                'review_status' => $record['review_status'],
                'platforms'     => array(
                    'labels'    => $record['platform_labels'],
                    'items'     => $record['platform_payload'],
                    'breakdown' => $record['platform_breakdown'],
                ),
                'timestamps'    => array(
                    'published' => $record['timestamps']['published']['iso8601'] ?? '',
                    'modified'  => $record['timestamps']['modified']['iso8601'] ?? '',
                ),
                'links'         => array(
                    'self' => $record['permalink'],
                ),
            );
        }

        $summary_post_ids = array();

        foreach ( $records as $record ) {
            $summary_post_ids[] = (int) $record['id'];
        }

        $summary = Helpers::get_posts_score_insights( $summary_post_ids );
        if ( is_array( $summary ) ) {
            $summary['total'] = $total_items;
        }

        return rest_ensure_response(
            array(
                'items'        => $items,
                'pagination'   => array(
                    'total'       => $total_items,
                    'total_pages' => $total_pages,
                    'per_page'    => $params['per_page'],
                    'page'        => $params['page'],
                ),
                'summary'      => $summary,
                'filters'      => $this->expose_filters( $params ),
                'score_max'    => $score_max,
                'platforms'    => $registered,
                'generated_at' => gmdate( 'c', $this->current_gmt_timestamp() ),
            )
        );
    }

    private function get_collection_params() {
        return array(
            'post_id'  => array(
                'description' => __( 'Identifiant de la review à retourner.', 'notation-jlg' ),
                'type'        => 'integer',
                'required'    => false,
            ),
            'slug'     => array(
                'description' => __( 'Slug de l’article ciblé.', 'notation-jlg' ),
                'type'        => 'string',
                'required'    => false,
            ),
            'platform' => array(
                'description' => __( 'Filtre les reviews par plateforme (slug).', 'notation-jlg' ),
                'type'        => 'string',
                'required'    => false,
            ),
            'search'   => array(
                'description' => __( 'Filtre les résultats par titre ou slug.', 'notation-jlg' ),
                'type'        => 'string',
                'required'    => false,
            ),
            'status'   => array(
                'description' => __( 'Limite les statuts de publication considérés (séparés par des virgules).', 'notation-jlg' ),
                'type'        => 'string',
                'required'    => false,
            ),
            'from'     => array(
                'description' => __( 'Inclut uniquement les reviews publiées à partir de cette date (YYYY-MM-DD).', 'notation-jlg' ),
                'type'        => 'string',
                'required'    => false,
            ),
            'to'       => array(
                'description' => __( 'Limite la période aux reviews publiées avant cette date (YYYY-MM-DD).', 'notation-jlg' ),
                'type'        => 'string',
                'required'    => false,
            ),
            'per_page' => array(
                'description' => __( 'Nombre de reviews retournées par page.', 'notation-jlg' ),
                'type'        => 'integer',
                'default'     => 10,
                'minimum'     => 1,
                'maximum'     => 50,
            ),
            'page'     => array(
                'description' => __( 'Indice de pagination (1-indexé).', 'notation-jlg' ),
                'type'        => 'integer',
                'default'     => 1,
                'minimum'     => 1,
            ),
            'orderby'  => array(
                'description' => __( 'Champ utilisé pour le tri.', 'notation-jlg' ),
                'type'        => 'string',
                'default'     => 'date',
                'enum'        => array( 'date', 'editorial', 'reader', 'title', 'user_votes' ),
            ),
            'order'    => array(
                'description' => __( 'Ordre de tri.', 'notation-jlg' ),
                'type'        => 'string',
                'default'     => 'desc',
                'enum'        => array( 'asc', 'desc' ),
            ),
        );
    }

    private function prepare_request_params( $request ) {
        $params = array(
            'post_id'  => $this->extract_param( $request, 'post_id', 0 ),
            'slug'     => $this->extract_param( $request, 'slug', '' ),
            'platform' => $this->extract_param( $request, 'platform', '' ),
            'search'   => $this->extract_param( $request, 'search', '' ),
            'per_page' => $this->extract_param( $request, 'per_page', 10 ),
            'page'     => $this->extract_param( $request, 'page', 1 ),
            'orderby'  => $this->extract_param( $request, 'orderby', 'date' ),
            'order'    => $this->extract_param( $request, 'order', 'desc' ),
            'status'   => $this->extract_param( $request, 'status', '' ),
            'from'     => $this->extract_param( $request, 'from', '' ),
            'to'       => $this->extract_param( $request, 'to', '' ),
        );

        $params['post_id']  = max( 0, (int) $params['post_id'] );
        $params['slug']     = sanitize_title( (string) $params['slug'] );
        $params['platform'] = sanitize_title( (string) $params['platform'] );
        $params['search']   = $this->normalize_search_term( $params['search'] );

        $params['per_page'] = (int) $params['per_page'];
        if ( $params['per_page'] < 1 ) {
            $params['per_page'] = 1;
        } elseif ( $params['per_page'] > 50 ) {
            $params['per_page'] = 50;
        }

        $params['page'] = max( 1, (int) $params['page'] );

        $allowed_orderby = array( 'date', 'editorial', 'reader', 'title', 'user_votes' );
        if ( ! in_array( $params['orderby'], $allowed_orderby, true ) ) {
            $params['orderby'] = 'date';
        }

        $order = strtolower( (string) $params['order'] );
        if ( $order !== 'asc' && $order !== 'desc' ) {
            $order = 'desc';
        }
        $params['order'] = $order;

        $params['statuses'] = $this->normalize_statuses( $params['status'] );
        unset( $params['status'] );

        $params['from'] = $this->normalize_date_boundary( $params['from'], false );
        $params['to']   = $this->normalize_date_boundary( $params['to'], true );

        return $params;
    }

    private function extract_param( $request, $key, $fallback = null ) {
        if ( is_object( $request ) ) {
            if ( method_exists( $request, 'get_param' ) ) {
                $value = $request->get_param( $key );

                if ( $value !== null ) {
                    return $value;
                }
            }

            if ( method_exists( $request, 'get_params' ) ) {
                $params = $request->get_params();

                if ( is_array( $params ) && array_key_exists( $key, $params ) ) {
                    return $params[ $key ];
                }
            }
        }

        if ( is_array( $request ) && array_key_exists( $key, $request ) ) {
            return $request[ $key ];
        }

        return $fallback;
    }

    private function collect_candidate_posts( array $params ) {
        $post__in = array();
        if ( $params['post_id'] > 0 ) {
            $post__in[] = $params['post_id'];
        }

        $statuses = $params['statuses'];

        if ( empty( $statuses ) ) {
            $statuses = apply_filters( 'jlg_ratings_rest_post_statuses', array( 'publish' ) );
        }

        if ( empty( $statuses ) ) {
            $statuses = array( 'publish' );
        }

        $order      = strtoupper( $params['order'] );
        $order_args = $this->resolve_query_orderby( $params );

        $query_args = array(
            'post_type'              => Helpers::get_allowed_post_types(),
            'post_status'            => $statuses,
            'posts_per_page'         => max( 1, (int) $params['per_page'] ),
            'paged'                  => max( 1, (int) $params['page'] ),
            'orderby'                => $order_args['orderby'],
            'order'                  => $order,
            'post__in'               => $post__in,
            'no_found_rows'          => false,
            'update_post_term_cache' => false,
        );

        if ( isset( $order_args['meta_key'] ) ) {
            $query_args['meta_key'] = $order_args['meta_key'];
        }

        if ( $params['slug'] !== '' ) {
            $query_args['name'] = $params['slug'];
        }

        if ( $params['search'] !== '' ) {
            $query_args['s']              = $params['search'];
            $query_args['search_columns'] = array( 'post_title', 'post_name' );
        }

        $date_query = array();

        if ( isset( $params['from']['query'] ) ) {
            $date_query['after'] = $params['from']['query'];
        }

        if ( isset( $params['to']['query'] ) ) {
            $date_query['before'] = $params['to']['query'];
        }

        if ( ! empty( $date_query ) ) {
            $date_query['inclusive']  = true;
            $date_query['column']     = 'post_date_gmt';
            $query_args['date_query'] = array( $date_query );
        }

        $platform_meta_query = $this->build_platform_meta_query( $params['platform'] );
        if ( ! empty( $platform_meta_query ) ) {
            $query_args['meta_query'] = array( $platform_meta_query );
        }

        if ( class_exists( WP_Query::class ) ) {
            $query = new WP_Query( $query_args );

            if ( isset( $query->posts ) && is_array( $query->posts ) ) {
                $filtered_posts = array();

                foreach ( $query->posts as $post ) {
                    if ( ! ( $post instanceof \WP_Post ) ) {
                        continue;
                    }

                    if ( ! $this->passes_date_filters( $post, $params ) ) {
                        continue;
                    }

                    $filtered_posts[] = $post;
                }

                return array(
                    'posts'         => $filtered_posts,
                    'found_posts'   => isset( $query->found_posts ) ? (int) $query->found_posts : count( $filtered_posts ),
                    'max_num_pages' => isset( $query->max_num_pages ) ? (int) $query->max_num_pages : 0,
                    'query_args'    => $query_args,
                );
            }

            return array(
                'posts'         => array(),
                'found_posts'   => isset( $query->found_posts ) ? (int) $query->found_posts : 0,
                'max_num_pages' => isset( $query->max_num_pages ) ? (int) $query->max_num_pages : 0,
                'query_args'    => $query_args,
            );
        }

        $store = array();
        foreach ( $GLOBALS['jlg_test_posts'] ?? array() as $post ) {
            if ( ! ( $post instanceof \WP_Post ) ) {
                continue;
            }

            if ( ! empty( $post__in ) && ! in_array( (int) $post->ID, $post__in, true ) ) {
                continue;
            }

            if ( ! empty( $statuses ) ) {
                $post_status = isset( $post->post_status ) ? sanitize_key( (string) $post->post_status ) : '';
                if ( $post_status === '' || ! in_array( $post_status, $statuses, true ) ) {
                    continue;
                }
            }

            if ( $params['slug'] !== '' && sanitize_title( $post->post_name ?? '' ) !== $params['slug'] ) {
                continue;
            }

            if ( $params['search'] !== '' && ! $this->matches_search_filter( $post->post_title ?? '', $post->post_name ?? '', $params['search'] ) ) {
                continue;
            }

            if ( ! $this->passes_date_filters( $post, $params ) ) {
                continue;
            }

            $store[] = $post;
        }

        if ( $params['platform'] !== '' ) {
            $registered = Helpers::get_registered_platform_labels();
            $store      = array_values(
                array_filter(
                    $store,
                    function ( $post ) use ( $registered, $params ) {
                        if ( ! ( $post instanceof \WP_Post ) ) {
                            return false;
                        }

                        $labels = $this->get_post_platform_labels( (int) $post->ID );

                        return $this->matches_platform_filter( $labels, $params['platform'], $registered );
                    }
                )
            );
        }

        $store = $this->fallback_sort_posts( $store, $params );

        $total      = count( $store );
        $per_page   = max( 1, (int) $params['per_page'] );
        $total_page = $per_page > 0 ? (int) ceil( $total / $per_page ) : 0;
        $page       = min( max( 1, (int) $params['page'] ), max( 1, $total_page ) );
        $offset     = ( $page - 1 ) * $per_page;

        return array(
            'posts'         => array_slice( $store, $offset, $per_page ),
            'found_posts'   => $total,
            'max_num_pages' => $total_page,
            'query_args'    => $query_args,
        );
    }

    private function resolve_query_orderby( array $params ) {
        $orderby = $params['orderby'];

        if ( ! empty( $params['post_id'] ) ) {
            return array(
                'orderby' => 'post__in',
            );
        }

        switch ( $orderby ) {
            case 'title':
                return array(
                    'orderby' => 'title',
                );
            case 'editorial':
                return array(
                    'orderby'  => 'meta_value_num',
                    'meta_key' => '_jlg_average_score',
                );
            case 'reader':
                return array(
                    'orderby'  => 'meta_value_num',
                    'meta_key' => '_jlg_user_rating_avg',
                );
            case 'user_votes':
                return array(
                    'orderby'  => 'meta_value_num',
                    'meta_key' => '_jlg_user_rating_count',
                );
            case 'date':
            default:
                return array(
                    'orderby' => 'date',
                );
        }
    }

    private function build_platform_meta_query( $platform_slug ) {
        if ( ! is_string( $platform_slug ) || $platform_slug === '' ) {
            return array();
        }

        $registered = Helpers::get_registered_platform_labels();
        $normalized = sanitize_title( $platform_slug );

        $lookup = array();
        foreach ( $registered as $slug => $label ) {
            $lookup[ sanitize_title( $slug ) ] = $label;
            if ( is_string( $label ) ) {
                $lookup[ sanitize_title( $label ) ] = $label;
            }
        }

        $target_label = $lookup[ $normalized ] ?? $lookup[ sanitize_title( $platform_slug ) ] ?? '';

        if ( $target_label === '' ) {
            $target_label = $platform_slug;
        }

        return array(
            'key'     => '_jlg_plateformes',
            'value'   => $target_label,
            'compare' => 'LIKE',
        );
    }

    private function fallback_sort_posts( array $posts, array $params ) {
        $order_args = $this->resolve_query_orderby( $params );
        $order      = strtoupper( $params['order'] ) === 'ASC' ? 'ASC' : 'DESC';

        if ( empty( $posts ) ) {
            return $posts;
        }

        if ( isset( $order_args['orderby'] ) && $order_args['orderby'] === 'post__in' ) {
            return $posts;
        }

        usort(
            $posts,
            function ( $a, $b ) use ( $order_args, $order ) {
                if ( ! ( $a instanceof \WP_Post ) || ! ( $b instanceof \WP_Post ) ) {
                    return 0;
                }

                switch ( $order_args['orderby'] ) {
                    case 'title':
                        $a_value = strtolower( $a->post_title ?? '' );
                        $b_value = strtolower( $b->post_title ?? '' );
                        $result  = $a_value <=> $b_value;
                        break;
                    case 'meta_value_num':
                        $meta_key = $order_args['meta_key'] ?? '';
                        $a_value  = (float) get_post_meta( (int) $a->ID, $meta_key, true );
                        $b_value  = (float) get_post_meta( (int) $b->ID, $meta_key, true );
                        $result   = $a_value <=> $b_value;
                        break;
                    case 'date':
                    default:
                        $a_value = strtotime( $a->post_date_gmt ?? $a->post_date ?? 'now' );
                        $b_value = strtotime( $b->post_date_gmt ?? $b->post_date ?? 'now' );
                        $result  = $a_value <=> $b_value;
                        break;
                }

                if ( 'DESC' === $order ) {
                    $result = -$result;
                }

                return $result;
            }
        );

        return $posts;
    }

    private function matches_platform_filter( array $labels, $platform_slug, array $registered ) {
        if ( $platform_slug === '' ) {
            return true;
        }

        $normalized_slug = sanitize_title( $platform_slug );
        $registered_map  = array();

        foreach ( $registered as $slug => $label ) {
            $registered_map[ sanitize_title( $slug ) ] = $slug;
            if ( is_string( $label ) ) {
                $registered_map[ sanitize_title( $label ) ] = $slug;
            }
        }

        $target_slug = $registered_map[ $normalized_slug ] ?? $normalized_slug;

        foreach ( $labels as $label ) {
            $candidate = sanitize_title( $label );
            if ( $candidate === $normalized_slug || $candidate === $target_slug ) {
                return true;
            }
        }

        return false;
    }

    private function normalize_platform_payload( array $labels, array $registered ) {
        if ( empty( $labels ) ) {
            return array();
        }

        $lookup = array();
        foreach ( $registered as $slug => $label ) {
            $lookup[ strtolower( $label ) ] = $slug;
        }

        $payload = array();
        foreach ( $labels as $label ) {
            $normalized_label = sanitize_text_field( $label );
            $slug             = $lookup[ strtolower( $normalized_label ) ] ?? sanitize_title( $normalized_label );

            $payload[] = array(
                'label' => $normalized_label,
                'slug'  => $slug,
            );
        }

        return $payload;
    }

    private function get_post_platform_labels( $post_id ) {
        $raw    = get_post_meta( $post_id, '_jlg_plateformes', true );
        $labels = array();

        if ( is_array( $raw ) ) {
            foreach ( $raw as $value ) {
                if ( ! is_string( $value ) ) {
                    continue;
                }

                $label = sanitize_text_field( $value );
                if ( $label === '' ) {
                    continue;
                }

                $labels[] = $label;
            }
        } elseif ( is_string( $raw ) && $raw !== '' ) {
            $pieces = array_map( 'trim', explode( ',', $raw ) );
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

        $labels = array_values( array_unique( $labels ) );

        return $labels;
    }

    private function build_histogram_payload( array $breakdown, $total_votes ) {
        $payload = array();
        $total   = max( 0, (int) $total_votes );

        for ( $stars = 5; $stars >= 1; $stars-- ) {
            $count      = isset( $breakdown[ $stars ] ) ? (int) $breakdown[ $stars ] : 0;
            $percentage = 0.0;

            if ( $total > 0 && $count > 0 ) {
                $percentage = round( ( $count / $total ) * 100, 1 );
            }

            $payload[] = array(
                'stars'      => $stars,
                'count'      => $count,
                'percentage' => $percentage,
            );
        }

        return $payload;
    }

    private function resolve_post_datetime( $post_id, $context ) {
        $field = $context === 'modified' ? 'post_modified_gmt' : 'post_date_gmt';
        $value = get_post_field( $field, $post_id );

        $is_gmt = true;
        if ( ! is_string( $value ) || $value === '' || $value === '0000-00-00 00:00:00' ) {
            $field  = $context === 'modified' ? 'post_modified' : 'post_date';
            $value  = get_post_field( $field, $post_id );
            $is_gmt = false;
        }

        if ( ! is_string( $value ) || $value === '' || $value === '0000-00-00 00:00:00' ) {
            return array(
                'raw'       => '',
                'iso8601'   => '',
                'timestamp' => null,
            );
        }

        $timestamp = strtotime( $value . ( $is_gmt ? ' GMT' : '' ) );
        if ( $timestamp === false ) {
            $timestamp = strtotime( $value );
        }

        if ( $timestamp === false ) {
            return array(
                'raw'       => $value,
                'iso8601'   => '',
                'timestamp' => null,
            );
        }

        return array(
            'raw'       => $value,
            'iso8601'   => gmdate( 'c', $timestamp ),
            'timestamp' => $timestamp,
        );
    }

    private function sort_records( array &$records, $orderby, $order ) {
        usort(
            $records,
            function ( $a, $b ) use ( $orderby, $order ) {
                switch ( $orderby ) {
                    case 'editorial':
                        $a_value = $a['editorial_value'];
                        $b_value = $b['editorial_value'];
                        break;
                    case 'reader':
                        $a_value = $a['user_average'];
                        $b_value = $b['user_average'];
                        break;
                    case 'title':
                        $a_value = strtolower( $a['title'] );
                        $b_value = strtolower( $b['title'] );
                        $result  = $a_value <=> $b_value;
                        if ( 'desc' === $order ) {
                            $result = -$result;
                        }
                        if ( 0 === $result ) {
                            return 'desc' === $order
                                ? ( (int) $b['id'] <=> (int) $a['id'] )
                                : ( (int) $a['id'] <=> (int) $b['id'] );
                        }
                        return $result;
                    case 'user_votes':
                        $a_value = $a['user_votes'];
                        $b_value = $b['user_votes'];
                        break;
                    default:
                        $a_value = $a['sort_timestamp'];
                        $b_value = $b['sort_timestamp'];
                        break;
                }

                $a_is_null = $a_value === null;
                $b_is_null = $b_value === null;

                if ( $a_is_null && $b_is_null ) {
                    $result = 0;
                } elseif ( $a_is_null ) {
                    $result = 1;
                } elseif ( $b_is_null ) {
                    $result = -1;
                } else {
                    $result = $a_value <=> $b_value;
                }

                if ( 'desc' === $order ) {
                    $result = -$result;
                }

                if ( 0 === $result ) {
                    return 'desc' === $order
                        ? ( (int) $b['id'] <=> (int) $a['id'] )
                        : ( (int) $a['id'] <=> (int) $b['id'] );
                }

                return $result;
            }
        );
    }

    private function expose_filters( array $params ) {
        return array(
            'post_id'  => $params['post_id'] > 0 ? $params['post_id'] : null,
            'slug'     => $params['slug'] !== '' ? $params['slug'] : null,
            'platform' => $params['platform'] !== '' ? $params['platform'] : null,
            'search'   => $params['search'] !== '' ? $params['search'] : null,
            'orderby'  => $params['orderby'],
            'order'    => $params['order'],
            'status'   => ! empty( $params['statuses'] ) ? implode( ',', $params['statuses'] ) : null,
            'from'     => isset( $params['from']['timestamp'] ) ? gmdate( 'Y-m-d', $params['from']['timestamp'] ) : null,
            'to'       => isset( $params['to']['timestamp'] ) ? gmdate( 'Y-m-d', $params['to']['timestamp'] ) : null,
        );
    }

    private function normalize_search_term( $value ) {
        $value = sanitize_text_field( (string) $value );
        $value = trim( $value );

        if ( $value === '' ) {
            return '';
        }

        if ( function_exists( 'remove_accents' ) ) {
            $value = remove_accents( $value );
        }

        if ( function_exists( 'mb_strtolower' ) ) {
            $value = mb_strtolower( $value );
        } else {
            $value = strtolower( $value );
        }

        return $value;
    }

    private function matches_search_filter( $title, $slug, $search ) {
        if ( $search === '' ) {
            return true;
        }

        $haystack = $title . ' ' . $slug;

        if ( function_exists( 'remove_accents' ) ) {
            $haystack = remove_accents( $haystack );
        }

        if ( function_exists( 'mb_strtolower' ) ) {
            $haystack = mb_strtolower( $haystack );
        } else {
            $haystack = strtolower( $haystack );
        }

        return strpos( $haystack, $search ) !== false;
    }

    private function normalize_statuses( $value ) {
        if ( is_array( $value ) ) {
            $candidates = $value;
        } else {
            $candidates = array_map( 'trim', explode( ',', (string) $value ) );
        }

        $statuses = array();

        foreach ( $candidates as $candidate ) {
            if ( ! is_string( $candidate ) || $candidate === '' ) {
                continue;
            }

            $status = sanitize_key( $candidate );

            if ( $status === '' ) {
                continue;
            }

            $statuses[] = $status;
        }

        return array_values( array_unique( $statuses ) );
    }

    private function normalize_date_boundary( $value, $is_end = false ) {
        if ( ! is_string( $value ) ) {
            return null;
        }

        $value = trim( $value );

        if ( $value === '' ) {
            return null;
        }

        try {
            $timezone = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'UTC' );
            $date     = new DateTimeImmutable( $value, $timezone );
        } catch ( Exception $exception ) {
            return null;
        }

        $date = $is_end ? $date->setTime( 23, 59, 59 ) : $date->setTime( 0, 0, 0 );
        $utc  = $date->setTimezone( new DateTimeZone( 'UTC' ) );

        return array(
            'query'     => $utc->format( 'Y-m-d H:i:s' ),
            'timestamp' => $utc->getTimestamp(),
        );
    }

    private function passes_date_filters( $post, array $params ) {
        if ( empty( $params['from'] ) && empty( $params['to'] ) ) {
            return true;
        }

        $timestamp = $this->extract_post_timestamp( $post );

        if ( $timestamp === null ) {
            return false;
        }

        if ( isset( $params['from']['timestamp'] ) && $timestamp < $params['from']['timestamp'] ) {
            return false;
        }

        if ( isset( $params['to']['timestamp'] ) && $timestamp > $params['to']['timestamp'] ) {
            return false;
        }

        return true;
    }

    private function extract_post_timestamp( $post ) {
        if ( ! ( $post instanceof \WP_Post ) ) {
            return null;
        }

        $date_gmt = isset( $post->post_date_gmt ) ? (string) $post->post_date_gmt : '';
        if ( $date_gmt !== '' && $date_gmt !== '0000-00-00 00:00:00' ) {
            $timestamp = strtotime( $date_gmt . ' GMT' );
            if ( $timestamp !== false ) {
                return (int) $timestamp;
            }
        }

        $date_local = isset( $post->post_date ) ? (string) $post->post_date : '';
        if ( $date_local !== '' && $date_local !== '0000-00-00 00:00:00' ) {
            $timestamp = strtotime( $date_local );
            if ( $timestamp !== false ) {
                return (int) $timestamp;
            }
        }

        return null;
    }

    private function format_delta_label( $value ) {
        if ( ! is_numeric( $value ) ) {
            return null;
        }

        $normalized = round( (float) $value, 1 );
        $formatted  = number_format_i18n( abs( $normalized ), 1 );
        $separator  = $this->get_decimal_separator();

        if ( $separator !== '.' ) {
            $formatted = str_replace( '.', $separator, $formatted );
        }

        if ( $normalized > 0 ) {
            return '+' . $formatted;
        }

        if ( $normalized < 0 ) {
            return '-' . $formatted;
        }

        $zero_formatted = number_format_i18n( 0, 1 );

        if ( $separator !== '.' ) {
            $zero_formatted = str_replace( '.', $separator, $zero_formatted );
        }

        return $zero_formatted;
    }

    private function get_decimal_separator() {
        $separator = apply_filters( 'jlg_ratings_rest_decimal_separator', null );

        if ( is_string( $separator ) && $separator !== '' ) {
            return $separator;
        }

        if ( function_exists( 'get_locale' ) ) {
            $locale = get_locale();
            if ( is_string( $locale ) && $locale !== '' ) {
                if ( stripos( $locale, 'fr' ) === 0 ) {
                    return ',';
                }

                if ( stripos( $locale, 'en' ) === 0 ) {
                    return '.';
                }
            }
        }

        if ( defined( 'WPLANG' ) && is_string( WPLANG ) ) {
            if ( stripos( WPLANG, 'fr' ) === 0 ) {
                return ',';
            }
            if ( stripos( WPLANG, 'en' ) === 0 ) {
                return '.';
            }
        }

        if ( function_exists( 'localeconv' ) ) {
            $conv = localeconv();
            if ( is_array( $conv ) && ! empty( $conv['decimal_point'] ) && $conv['decimal_point'] !== '.' ) {
                return (string) $conv['decimal_point'];
            }
        }

        return ',';
    }

    private function current_gmt_timestamp() {
        $timestamp = time();

        if ( function_exists( 'apply_filters' ) ) {
            $filtered = apply_filters( 'jlg_current_gmt_timestamp', $timestamp );

            if ( is_numeric( $filtered ) ) {
                return (int) $filtered;
            }
        }

        return $timestamp;
    }
}

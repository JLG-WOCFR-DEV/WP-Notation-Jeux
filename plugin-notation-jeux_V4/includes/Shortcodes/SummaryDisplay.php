<?php
/**
 * Shortcode pour le tableau/grille récapitulatif
 *
 * @package JLG_Notation
 * @version 5.0
 */

namespace JLG\Notation\Shortcodes;

use JLG\Notation\Frontend;
use JLG\Notation\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

class SummaryDisplay {

    private const MAX_SYNC_AVERAGE_REBUILDS = 10;
    private const REQUEST_KEYS              = array(
        'orderby',
        'order',
        'cat_filter',
        'letter_filter',
        'genre_filter',
        'paged',
    );

    public function __construct() {
        add_shortcode( 'jlg_tableau_recap', array( $this, 'render' ) );
    }

    public function render( $atts, $content = '', $shortcode_tag = '' ) {
        $context = self::get_render_context( $atts, $_GET, true );

        if ( ! empty( $context['error'] ) && ! empty( $context['message'] ) ) {
            return $context['message'];
        }

        Frontend::mark_shortcode_rendered( $shortcode_tag ?: 'jlg_tableau_recap' );

        return Frontend::get_template_html( 'shortcode-summary-display', $context );
    }

    public static function get_render_context( $atts, $request = array(), $use_global_paged = false ) {
        $default_atts = self::get_default_atts();
        $atts         = shortcode_atts( $default_atts, $atts, 'jlg_tableau_recap' );

        $atts['id']     = self::normalize_table_id( $atts['id'] ?? '' );
        $request_prefix = self::get_request_prefix( $atts['id'] );
        $request_keys   = self::build_request_keys( $request_prefix );

        $request = self::extract_request_params( $request, $request_keys );

        $default_posts_per_page = isset( $default_atts['posts_per_page'] ) ? intval( $default_atts['posts_per_page'] ) : 12;
        if ( $default_posts_per_page < 1 ) {
            $default_posts_per_page = 1;
        }

        $posts_per_page = isset( $atts['posts_per_page'] ) ? intval( $atts['posts_per_page'] ) : $default_posts_per_page;
        if ( $posts_per_page < 1 ) {
            $posts_per_page = $default_posts_per_page;
        }
        $posts_per_page = max( 1, min( $posts_per_page, 50 ) );

        $atts['posts_per_page'] = $posts_per_page;
        $atts['id']             = sanitize_html_class( $atts['id'] );
        if ( $atts['id'] === '' ) {
            $atts['id'] = 'jlg-table-' . uniqid();
        }
        if ( ! in_array( $atts['layout'], array( 'table', 'grid' ), true ) ) {
            $atts['layout'] = 'table';
        }

        $request = is_array( $request ) ? $request : array();

        $orderby       = ( isset( $request['orderby'] ) && is_string( $request['orderby'] ) ) ? sanitize_key( $request['orderby'] ) : 'date';
        $order         = isset( $request['order'] ) && in_array( strtoupper( $request['order'] ), array( 'ASC', 'DESC' ), true )
            ? strtoupper( $request['order'] )
            : 'DESC';
        $cat_filter    = isset( $request['cat_filter'] ) ? intval( $request['cat_filter'] ) : 0;
        $letter_filter = '';
        if ( isset( $request['letter_filter'] ) ) {
            $letter_filter = self::normalize_letter_filter( $request['letter_filter'] );
        } elseif ( ! empty( $atts['letter_filter'] ) ) {
            $letter_filter = self::normalize_letter_filter( $atts['letter_filter'] );
        }

        $genre_filter = '';
        if ( isset( $request['genre_filter'] ) ) {
            $genre_filter = sanitize_text_field( $request['genre_filter'] );
        } elseif ( ! empty( $atts['genre_filter'] ) ) {
            $genre_filter = sanitize_text_field( $atts['genre_filter'] );
        }

        $atts['letter_filter'] = $letter_filter;
        $atts['genre_filter']  = $genre_filter;

        $paged = isset( $request['paged'] ) ? intval( $request['paged'] ) : 0;
        if ( $paged < 1 ) {
            $paged = ( $use_global_paged && get_query_var( 'paged' ) ) ? intval( get_query_var( 'paged' ) ) : 1;
        }

        $sorting_options = self::get_sorting_options();
        if ( ! isset( $sorting_options[ $orderby ] ) ) {
            $orderby = 'date';
        }

        $sorting = $sorting_options[ $orderby ];
        $orderby = $sorting['key'];

        $rated_post_ids     = Helpers::get_rated_post_ids();
        $allowed_post_types = Helpers::get_allowed_post_types();
        $allowed_post_types = array_values(
            array_unique(
                array_filter(
                    $allowed_post_types,
                    static function ( $type ) {
						return is_string( $type ) && $type !== '';
					}
                )
            )
        );

        if ( empty( $allowed_post_types ) ) {
            $allowed_post_types = array( 'post' );
        }

        if ( ! empty( $rated_post_ids ) && isset( $sorting['meta_key'] ) && $sorting['meta_key'] === '_jlg_average_score' ) {
            $max_sync = apply_filters( 'jlg_summary_max_sync_average_rebuilds', self::MAX_SYNC_AVERAGE_REBUILDS, $atts );
            $max_sync = max( 0, intval( $max_sync ) );

            $posts_missing_average = get_posts(
                array(
					'post_type'      => $allowed_post_types,
					'post__in'       => $rated_post_ids,
					'fields'         => 'ids',
					'posts_per_page' => $max_sync + 1,
					'orderby'        => 'ID',
					'order'          => 'ASC',
					'no_found_rows'  => true,
					'meta_query'     => array(
						'relation' => 'OR',
						array(
							'key'     => '_jlg_average_score',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => '_jlg_average_score',
							'value'   => '',
							'compare' => '=',
						),
					),
                )
            );

            if ( ! empty( $posts_missing_average ) ) {
                $sync_targets = array_slice( $posts_missing_average, 0, $max_sync );

                foreach ( $sync_targets as $post_id ) {
                    Helpers::get_resolved_average_score( $post_id );
                }

                $async_targets = array_diff( $posts_missing_average, $sync_targets );

                if ( ! empty( $async_targets ) ) {
                    Helpers::queue_average_score_rebuild( $async_targets );
                }
            }
        }
        if ( empty( $rated_post_ids ) ) {
            $no_results = '<p>' . esc_html__( 'Aucun article noté trouvé.', 'notation-jlg' ) . '</p>';

            return array(
                'error'                => true,
                'message'              => $no_results,
                'atts'                 => $atts,
                'paged'                => $paged,
                'orderby'              => $orderby,
                'order'                => $order,
                'cat_filter'           => $cat_filter,
                'letter_filter'        => $letter_filter,
                'genre_filter'         => $genre_filter,
                'colonnes'             => self::prepare_columns( $atts ),
                'colonnes_disponibles' => self::get_available_columns(),
                'error_message'        => $no_results,
                'request_prefix'       => $request_prefix,
                'request_keys'         => $request_keys,
            );
        }

        $args = array(
            'post_type'      => $allowed_post_types,
            'posts_per_page' => $posts_per_page,
            'paged'          => $paged,
            'order'          => $order,
        );

        $max_post_in = apply_filters( 'jlg_summary_max_post__in', 500, $atts );
        $max_post_in = max( 0, intval( $max_post_in ) );

        if ( $max_post_in === 0 || count( $rated_post_ids ) <= $max_post_in ) {
            $args['post__in'] = $rated_post_ids;
        } else {
            $args['meta_query'][] = array(
                'key'     => '_jlg_average_score',
                'compare' => 'EXISTS',
            );
        }

        if ( $orderby === 'average_score' || $orderby === 'note' ) {
            $args['orderby']  = 'meta_value_num';
            $args['meta_key'] = '_jlg_average_score';
        } elseif ( ! empty( $sorting['meta_key'] ) ) {
            $args['orderby']  = $sorting['orderby'];
            $args['meta_key'] = $sorting['meta_key'];

            if ( ! empty( $sorting['type'] ) ) {
                $args['meta_type'] = $sorting['type'];
            }
        } else {
            $args['orderby'] = $sorting['orderby'];
        }

        if ( ! empty( $atts['categorie'] ) ) {
            $args['category_name'] = sanitize_text_field( $atts['categorie'] );
        } elseif ( $cat_filter > 0 ) {
            $args['cat'] = $cat_filter;
        }

        if ( $genre_filter !== '' ) {
            $genre_taxonomy = apply_filters( 'jlg_summary_genre_taxonomy', 'jlg_game_genre' );

            if ( ! empty( $genre_taxonomy ) && taxonomy_exists( $genre_taxonomy ) ) {
                if ( ! isset( $args['tax_query'] ) || ! is_array( $args['tax_query'] ) ) {
                    $args['tax_query'] = array();
                }

                if ( ! isset( $args['tax_query']['relation'] ) ) {
                    $args['tax_query']['relation'] = 'AND';
                }

                $args['tax_query'][] = array(
                    'taxonomy' => $genre_taxonomy,
                    'field'    => 'slug',
                    'terms'    => $genre_filter,
                );
            } else {
                $genre_meta_key = apply_filters( 'jlg_summary_genre_meta_key', '_jlg_genre' );

                if ( ! empty( $genre_meta_key ) ) {
                    if ( ! isset( $args['meta_query'] ) || ! is_array( $args['meta_query'] ) ) {
                        $args['meta_query'] = array();
                    }

                    if ( ! isset( $args['meta_query']['relation'] ) ) {
                        $args['meta_query']['relation'] = 'AND';
                    }

                    $args['meta_query'][] = array(
                        'key'     => $genre_meta_key,
                        'value'   => $genre_filter,
                        'compare' => 'LIKE',
                    );
                }
            }
        }

        $letter_filter_active = ( $letter_filter !== '' );

        if ( $letter_filter_active ) {
            self::set_letter_filter( $letter_filter );
        }

        try {
            $query = new \WP_Query( $args );
        } finally {
            if ( $letter_filter_active ) {
                self::clear_letter_filter();
            }
        }

        return array(
            'query'                => $query,
            'atts'                 => $atts,
            'paged'                => $paged,
            'orderby'              => $orderby,
            'order'                => $order,
            'cat_filter'           => $cat_filter,
            'letter_filter'        => $letter_filter,
            'genre_filter'         => $genre_filter,
            'colonnes'             => self::prepare_columns( $atts ),
            'colonnes_disponibles' => self::get_available_columns(),
            'error'                => false,
            'error_message'        => '',
            'atts_defaults'        => $default_atts,
            'request_prefix'       => $request_prefix,
            'request_keys'         => $request_keys,
        );
    }

    public static function get_default_atts() {
        return array(
            'posts_per_page' => 12,
            'layout'         => 'table',
            'categorie'      => '',
            'colonnes'       => 'titre,date,note',
            'id'             => 'jlg-table-' . uniqid(),
            'letter_filter'  => '',
            'genre_filter'   => '',
        );
    }

    public static function normalize_letter_filter( $value ) {
        if ( $value === null ) {
            return '';
        }

        $value = sanitize_text_field( $value );
        $value = trim( $value );

        if ( $value === '' ) {
            return '';
        }

        $char = substr( $value, 0, 1 );

        if ( $char === '#' ) {
            return '#';
        }

        if ( ctype_digit( $char ) ) {
            return '#';
        }

        if ( ctype_alpha( $char ) ) {
            return strtoupper( $char );
        }

        return '';
    }

    protected static function get_available_columns() {
        $columns = array(
            'titre'       => array(
                'label'    => __( 'Titre du jeu', 'notation-jlg' ),
                'sortable' => true,
                'sort'     => array(
                    'key'     => 'title',
                    'orderby' => 'title',
                    'aliases' => array( 'titre' ),
                ),
            ),
            'date'        => array(
                'label'    => __( 'Date', 'notation-jlg' ),
                'sortable' => true,
                'sort'     => array(
                    'key'     => 'date',
                    'orderby' => 'date',
                ),
            ),
            'note'        => array(
                'label'    => __( 'Note', 'notation-jlg' ),
                'sortable' => true,
                'sort'     => array(
                    'key'      => 'average_score',
                    'orderby'  => 'meta_value_num',
                    'meta_key' => '_jlg_average_score',
                    'aliases'  => array( 'note' ),
                ),
            ),
            'developpeur' => array(
                'label'    => __( 'Développeur', 'notation-jlg' ),
                'sortable' => true,
                'sort'     => array(
                    'key'      => 'meta__jlg_developpeur',
                    'orderby'  => 'meta_value',
                    'meta_key' => '_jlg_developpeur',
                    'type'     => 'CHAR',
                    'aliases'  => array( 'developpeur' ),
                ),
            ),
            'editeur'     => array(
                'label'    => __( 'Éditeur', 'notation-jlg' ),
                'sortable' => true,
                'sort'     => array(
                    'key'      => 'meta__jlg_editeur',
                    'orderby'  => 'meta_value',
                    'meta_key' => '_jlg_editeur',
                    'type'     => 'CHAR',
                    'aliases'  => array( 'editeur' ),
                ),
            ),
        );

        foreach ( Helpers::get_rating_category_definitions() as $definition ) {
            $category_id = isset( $definition['id'] ) ? sanitize_key( $definition['id'] ) : '';
            $label       = isset( $definition['label'] ) ? (string) $definition['label'] : '';
            $meta_key    = isset( $definition['meta_key'] ) ? (string) $definition['meta_key'] : '';

            if ( $category_id === '' || $meta_key === '' ) {
                continue;
            }

            $column_key   = 'note_' . $category_id;
            $sorting_key  = sanitize_key( $column_key );
            $column_label = $label !== '' ? $label : ucfirst( str_replace( '-', ' ', $category_id ) );

            $weight = Helpers::normalize_category_weight(
                $definition['weight'] ?? 1.0,
                1.0
            );

            $columns[ $column_key ] = array(
                'label'      => $column_label,
                'sortable'   => true,
                'sort'       => array(
                    'key'      => $sorting_key,
                    'orderby'  => 'meta_value_num',
                    'meta_key' => $meta_key,
                    'type'     => 'NUMERIC',
                    'aliases'  => array( $column_key, $category_id, $sorting_key ),
                ),
                'type'       => 'rating_category',
                'definition' => $definition,
                'weight'     => $weight,
            );
        }

        return $columns;
    }

    protected static function get_sorting_options() {
        $columns = self::get_available_columns();
        $options = array();

        foreach ( $columns as $column_key => $column ) {
            if ( empty( $column['sortable'] ) ) {
                continue;
            }

            $sort        = isset( $column['sort'] ) && is_array( $column['sort'] ) ? $column['sort'] : array();
            $primary_key = isset( $sort['key'] ) ? sanitize_key( $sort['key'] ) : sanitize_key( $column_key );
            $option      = array(
                'key'     => $primary_key,
                'orderby' => isset( $sort['orderby'] ) ? $sort['orderby'] : $primary_key,
            );

            if ( ! empty( $sort['meta_key'] ) ) {
                $option['meta_key'] = $sort['meta_key'];
            }

            if ( ! empty( $sort['type'] ) ) {
                $option['type'] = $sort['type'];
            }

            $options[ $primary_key ] = $option;

            $aliases = array();

            if ( ! empty( $sort['aliases'] ) && is_array( $sort['aliases'] ) ) {
                $aliases = array_map( 'sanitize_key', $sort['aliases'] );
            }

            $aliases[] = sanitize_key( $column_key );
            $aliases   = array_unique( array_filter( $aliases ) );

            foreach ( $aliases as $alias ) {
                $options[ $alias ] = $option;
            }
        }

        if ( ! isset( $options['date'] ) ) {
            $options['date'] = array(
                'key'     => 'date',
                'orderby' => 'date',
            );
        }

        return $options;
    }

    public static function get_allowed_sort_keys() {
        $options = self::get_sorting_options();
        $allowed = array();

        foreach ( $options as $key => $option ) {
            $allowed[] = sanitize_key( $key );
            if ( isset( $option['key'] ) ) {
                $allowed[] = sanitize_key( $option['key'] );
            }
        }

        $allowed[] = 'date';

        return array_values( array_unique( array_filter( $allowed ) ) );
    }

    protected static function prepare_columns( $atts ) {
        $requested = array_filter( array_map( 'trim', explode( ',', $atts['colonnes'] ) ) );
        $available = self::get_available_columns();
        $columns   = array();

        foreach ( $requested as $column ) {
            if ( isset( $available[ $column ] ) ) {
                $columns[] = $column;
            }
        }

        if ( empty( $columns ) ) {
            $columns = array( 'titre', 'date', 'note' );
        }

        return $columns;
    }

    protected static function set_letter_filter( $letter ) {
        self::$active_letter_filter = $letter;

        if ( ! self::$letter_filter_hooked ) {
            add_filter( 'posts_where', array( __CLASS__, 'filter_letter_where' ) );
            self::$letter_filter_hooked = true;
        }
    }

    protected static function clear_letter_filter() {
        if ( self::$letter_filter_hooked ) {
            remove_filter( 'posts_where', array( __CLASS__, 'filter_letter_where' ) );
            self::$letter_filter_hooked = false;
        }

        self::$active_letter_filter = '';
    }

    public static function filter_letter_where( $where ) {
        global $wpdb;

        if ( self::$active_letter_filter === '' ) {
            return $where;
        }

        $meta_subquery = "(SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = {$wpdb->posts}.ID AND meta_key = '_jlg_game_title' ORDER BY meta_id DESC LIMIT 1)";

        if ( self::$active_letter_filter === '#' ) {
            $condition = "REGEXP '^[0-9]'";
            $where    .= " AND (({$meta_subquery} {$condition}) OR ({$wpdb->posts}.post_title {$condition}))";

            return $where;
        }

        $like            = $wpdb->esc_like( self::$active_letter_filter ) . '%';
        $meta_condition  = $wpdb->prepare( 'LIKE %s', $like );
        $title_condition = $wpdb->prepare( 'LIKE %s', $like );

        $where .= " AND (({$meta_subquery} {$meta_condition}) OR ({$wpdb->posts}.post_title {$title_condition}))";

        return $where;
    }

    private static $active_letter_filter = '';
    private static $letter_filter_hooked = false;

    private static function normalize_table_id( $raw_id ) {
        $raw_id    = is_string( $raw_id ) ? $raw_id : '';
        $sanitized = sanitize_html_class( $raw_id );

        if ( $sanitized === '' ) {
            $sanitized = 'jlg-table-' . uniqid();
        }

        return $sanitized;
    }

    private static function get_request_prefix( $table_id ) {
        $table_id = is_string( $table_id ) ? $table_id : '';
        $prefix   = sanitize_title( $table_id );

        if ( $prefix === '' ) {
            $prefix = 'jlg-table';
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

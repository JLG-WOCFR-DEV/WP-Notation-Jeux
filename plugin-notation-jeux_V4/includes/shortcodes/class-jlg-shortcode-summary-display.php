<?php
/**
 * Shortcode pour le tableau/grille récapitulatif
 * 
 * @package JLG_Notation
 * @version 5.0
 */

if (!defined('ABSPATH')) exit;

class JLG_Shortcode_Summary_Display {
    
    public function __construct() {
        add_shortcode('jlg_tableau_recap', [$this, 'render']);
    }

    public function render($atts) {
        $context = self::get_render_context($atts, $_GET, true);

        if (!empty($context['error']) && !empty($context['message'])) {
            return $context['message'];
        }

        JLG_Frontend::mark_shortcode_rendered();

        return JLG_Frontend::get_template_html('shortcode-summary-display', $context);
    }

    public static function get_render_context($atts, $request = [], $use_global_paged = false) {
        $default_atts = self::get_default_atts();
        $atts = shortcode_atts($default_atts, $atts, 'jlg_tableau_recap');

        $default_posts_per_page = isset($default_atts['posts_per_page']) ? intval($default_atts['posts_per_page']) : 12;
        if ($default_posts_per_page < 1) {
            $default_posts_per_page = 1;
        }

        $posts_per_page = isset($atts['posts_per_page']) ? intval($atts['posts_per_page']) : $default_posts_per_page;
        if ($posts_per_page < 1) {
            $posts_per_page = $default_posts_per_page;
        }
        $posts_per_page = max(1, min($posts_per_page, 50));

        $atts['posts_per_page'] = $posts_per_page;
        $atts['id'] = sanitize_html_class($atts['id']);
        if ($atts['id'] === '') {
            $atts['id'] = 'jlg-table-' . uniqid();
        }
        if (!in_array($atts['layout'], ['table', 'grid'], true)) {
            $atts['layout'] = 'table';
        }

        $request = is_array($request) ? $request : [];

        $orderby = isset($request['orderby']) ? sanitize_key($request['orderby']) : 'date';
        $order = isset($request['order']) && in_array(strtoupper($request['order']), ['ASC', 'DESC'], true)
            ? strtoupper($request['order'])
            : 'DESC';
        $cat_filter = isset($request['cat_filter']) ? intval($request['cat_filter']) : 0;
        $letter_filter = '';
        if (isset($request['letter_filter'])) {
            $letter_filter = self::normalize_letter_filter($request['letter_filter']);
        } elseif (!empty($atts['letter_filter'])) {
            $letter_filter = self::normalize_letter_filter($atts['letter_filter']);
        }

        $genre_filter = '';
        if (isset($request['genre_filter'])) {
            $genre_filter = sanitize_text_field($request['genre_filter']);
        } elseif (!empty($atts['genre_filter'])) {
            $genre_filter = sanitize_text_field($atts['genre_filter']);
        }

        $atts['letter_filter'] = $letter_filter;
        $atts['genre_filter'] = $genre_filter;

        $paged = isset($request['paged']) ? intval($request['paged']) : 0;
        if ($paged < 1) {
            $paged = ($use_global_paged && get_query_var('paged')) ? intval(get_query_var('paged')) : 1;
        }

        $sorting_options = self::get_sorting_options();
        if (!isset($sorting_options[$orderby])) {
            $orderby = 'date';
        }

        $sorting = $sorting_options[$orderby];
        $orderby = $sorting['key'];

        $rated_post_ids = JLG_Helpers::get_rated_post_ids();

        if (!empty($rated_post_ids) && isset($sorting['meta_key']) && $sorting['meta_key'] === '_jlg_average_score') {
            $posts_missing_average = get_posts([
                'post_type'      => 'post',
                'post__in'       => $rated_post_ids,
                'fields'         => 'ids',
                'posts_per_page' => -1,
                'meta_query'     => [
                    'relation' => 'OR',
                    [
                        'key'     => '_jlg_average_score',
                        'compare' => 'NOT EXISTS',
                    ],
                    [
                        'key'     => '_jlg_average_score',
                        'value'   => '',
                        'compare' => '=',
                    ],
                ],
            ]);

            if (!empty($posts_missing_average)) {
                foreach ($posts_missing_average as $post_id) {
                    JLG_Helpers::get_resolved_average_score($post_id);
                }
            }
        }
        if (empty($rated_post_ids)) {
            $no_results = '<p>' . esc_html__('Aucun article noté trouvé.', 'notation-jlg') . '</p>';

            return [
                'error'        => true,
                'message'      => $no_results,
                'atts'         => $atts,
                'paged'        => $paged,
                'orderby'      => $orderby,
                'order'        => $order,
                'cat_filter'   => $cat_filter,
                'letter_filter'=> $letter_filter,
                'genre_filter' => $genre_filter,
                'colonnes'     => self::prepare_columns($atts),
                'colonnes_disponibles' => self::get_available_columns(),
                'error_message' => $no_results,
            ];
        }

        $args = [
            'post_type'      => 'post',
            'posts_per_page' => $posts_per_page,
            'post__in'       => $rated_post_ids,
            'paged'          => $paged,
            'order'          => $order,
        ];

        if ($orderby === 'average_score' || $orderby === 'note') {
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = '_jlg_average_score';
        } elseif (!empty($sorting['meta_key'])) {
            $args['orderby'] = $sorting['orderby'];
            $args['meta_key'] = $sorting['meta_key'];

            if (!empty($sorting['type'])) {
                $args['meta_type'] = $sorting['type'];
            }
        } else {
            $args['orderby'] = $sorting['orderby'];
        }

        if (!empty($atts['categorie'])) {
            $args['category_name'] = sanitize_text_field($atts['categorie']);
        } elseif ($cat_filter > 0) {
            $args['cat'] = $cat_filter;
        }

        if ($genre_filter !== '') {
            $genre_taxonomy = apply_filters('jlg_summary_genre_taxonomy', 'jlg_game_genre');

            if (!empty($genre_taxonomy) && taxonomy_exists($genre_taxonomy)) {
                if (!isset($args['tax_query']) || !is_array($args['tax_query'])) {
                    $args['tax_query'] = [];
                }

                if (!isset($args['tax_query']['relation'])) {
                    $args['tax_query']['relation'] = 'AND';
                }

                $args['tax_query'][] = [
                    'taxonomy' => $genre_taxonomy,
                    'field'    => 'slug',
                    'terms'    => $genre_filter,
                ];
            } else {
                $genre_meta_key = apply_filters('jlg_summary_genre_meta_key', '_jlg_genre');

                if (!empty($genre_meta_key)) {
                    if (!isset($args['meta_query']) || !is_array($args['meta_query'])) {
                        $args['meta_query'] = [];
                    }

                    if (!isset($args['meta_query']['relation'])) {
                        $args['meta_query']['relation'] = 'AND';
                    }

                    $args['meta_query'][] = [
                        'key'     => $genre_meta_key,
                        'value'   => $genre_filter,
                        'compare' => 'LIKE',
                    ];
                }
            }
        }

        $letter_filter_active = ($letter_filter !== '');

        if ($letter_filter_active) {
            self::set_letter_filter($letter_filter);
        }

        try {
            $query = new WP_Query($args);
        } finally {
            if ($letter_filter_active) {
                self::clear_letter_filter();
            }
        }

        return [
            'query'                => $query,
            'atts'                 => $atts,
            'paged'                => $paged,
            'orderby'              => $orderby,
            'order'                => $order,
            'cat_filter'           => $cat_filter,
            'letter_filter'        => $letter_filter,
            'genre_filter'         => $genre_filter,
            'colonnes'             => self::prepare_columns($atts),
            'colonnes_disponibles' => self::get_available_columns(),
            'error_message'        => '',
        ];
    }

    public static function get_default_atts() {
        return [
            'posts_per_page' => 12,
            'layout'         => 'table',
            'categorie'      => '',
            'colonnes'       => 'titre,date,note',
            'id'             => 'jlg-table-' . uniqid(),
            'letter_filter'  => '',
            'genre_filter'   => '',
        ];
    }

    public static function normalize_letter_filter($value) {
        if ($value === null) {
            return '';
        }

        $value = sanitize_text_field($value);
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $char = substr($value, 0, 1);

        if ($char === '#') {
            return '#';
        }

        if (ctype_digit($char)) {
            return '#';
        }

        if (ctype_alpha($char)) {
            return strtoupper($char);
        }

        return '';
    }

    protected static function get_available_columns() {
        return [
            'titre' => [
                'label'    => __('Titre du jeu', 'notation-jlg'),
                'sortable' => true,
                'sort'     => [
                    'key'      => 'title',
                    'orderby'  => 'title',
                    'aliases'  => ['titre'],
                ],
            ],
            'date' => [
                'label'    => __('Date', 'notation-jlg'),
                'sortable' => true,
                'sort'     => [
                    'key'     => 'date',
                    'orderby' => 'date',
                ],
            ],
            'note' => [
                'label'    => __('Note', 'notation-jlg'),
                'sortable' => true,
                'sort'     => [
                    'key'      => 'average_score',
                    'orderby'  => 'meta_value_num',
                    'meta_key' => '_jlg_average_score',
                    'aliases'  => ['note'],
                ],
            ],
            'developpeur' => [
                'label'    => __('Développeur', 'notation-jlg'),
                'sortable' => true,
                'sort'     => [
                    'key'      => 'meta__jlg_developpeur',
                    'orderby'  => 'meta_value',
                    'meta_key' => '_jlg_developpeur',
                    'type'     => 'CHAR',
                    'aliases'  => ['developpeur'],
                ],
            ],
            'editeur' => [
                'label'    => __('Éditeur', 'notation-jlg'),
                'sortable' => true,
                'sort'     => [
                    'key'      => 'meta__jlg_editeur',
                    'orderby'  => 'meta_value',
                    'meta_key' => '_jlg_editeur',
                    'type'     => 'CHAR',
                    'aliases'  => ['editeur'],
                ],
            ],
        ];
    }

    protected static function get_sorting_options() {
        $columns = self::get_available_columns();
        $options = [];

        foreach ($columns as $column_key => $column) {
            if (empty($column['sortable'])) {
                continue;
            }

            $sort = isset($column['sort']) && is_array($column['sort']) ? $column['sort'] : [];
            $primary_key = isset($sort['key']) ? sanitize_key($sort['key']) : sanitize_key($column_key);
            $option = [
                'key'     => $primary_key,
                'orderby' => isset($sort['orderby']) ? $sort['orderby'] : $primary_key,
            ];

            if (!empty($sort['meta_key'])) {
                $option['meta_key'] = $sort['meta_key'];
            }

            if (!empty($sort['type'])) {
                $option['type'] = $sort['type'];
            }

            $options[$primary_key] = $option;

            $aliases = [];

            if (!empty($sort['aliases']) && is_array($sort['aliases'])) {
                $aliases = array_map('sanitize_key', $sort['aliases']);
            }

            $aliases[] = sanitize_key($column_key);
            $aliases = array_unique(array_filter($aliases));

            foreach ($aliases as $alias) {
                $options[$alias] = $option;
            }
        }

        if (!isset($options['date'])) {
            $options['date'] = [
                'key'     => 'date',
                'orderby' => 'date',
            ];
        }

        return $options;
    }

    protected static function prepare_columns($atts) {
        $requested = array_filter(array_map('trim', explode(',', $atts['colonnes'])));
        $available = self::get_available_columns();
        $columns = [];

        foreach ($requested as $column) {
            if (isset($available[$column])) {
                $columns[] = $column;
            }
        }

        if (empty($columns)) {
            $columns = ['titre', 'date', 'note'];
        }

        return $columns;
    }

    protected static function set_letter_filter($letter) {
        self::$active_letter_filter = $letter;

        if (!self::$letter_filter_hooked) {
            add_filter('posts_where', [__CLASS__, 'filter_letter_where']);
            self::$letter_filter_hooked = true;
        }
    }

    protected static function clear_letter_filter() {
        if (self::$letter_filter_hooked) {
            remove_filter('posts_where', [__CLASS__, 'filter_letter_where']);
            self::$letter_filter_hooked = false;
        }

        self::$active_letter_filter = '';
    }

    public static function filter_letter_where($where) {
        global $wpdb;

        if (self::$active_letter_filter === '') {
            return $where;
        }

        $meta_subquery = "(SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = {$wpdb->posts}.ID AND meta_key = '_jlg_game_title' ORDER BY meta_id DESC LIMIT 1)";

        if (self::$active_letter_filter === '#') {
            $condition = "REGEXP '^[0-9]'";
            $where    .= " AND (({$meta_subquery} {$condition}) OR ({$wpdb->posts}.post_title {$condition}))";

            return $where;
        }

        $like = $wpdb->esc_like(self::$active_letter_filter) . '%';
        $meta_condition = $wpdb->prepare('LIKE %s', $like);
        $title_condition = $wpdb->prepare('LIKE %s', $like);

        $where .= " AND (({$meta_subquery} {$meta_condition}) OR ({$wpdb->posts}.post_title {$title_condition}))";

        return $where;
    }

    private static $active_letter_filter = '';
    private static $letter_filter_hooked = false;
}
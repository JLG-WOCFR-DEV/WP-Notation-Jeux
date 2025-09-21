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

        $paged = isset($request['paged']) ? intval($request['paged']) : 0;
        if ($paged < 1) {
            $paged = ($use_global_paged && get_query_var('paged')) ? intval(get_query_var('paged')) : 1;
        }

        $rated_post_ids = JLG_Helpers::get_rated_post_ids();

        if (($orderby === 'average_score' || $orderby === 'note') && !empty($rated_post_ids)) {
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
        } elseif ($orderby === 'title' || $orderby === 'titre') {
            $args['orderby'] = 'title';
        } else {
            $args['orderby'] = $orderby;
        }

        if (!empty($atts['categorie'])) {
            $args['category_name'] = sanitize_text_field($atts['categorie']);
        } elseif ($cat_filter > 0) {
            $args['cat'] = $cat_filter;
        }

        $query = new WP_Query($args);

        return [
            'query'                => $query,
            'atts'                 => $atts,
            'paged'                => $paged,
            'orderby'              => $orderby,
            'order'                => $order,
            'cat_filter'           => $cat_filter,
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
        ];
    }

    protected static function get_available_columns() {
        return [
            'titre' => [
                'label'    => __('Titre du jeu', 'notation-jlg'),
                'sortable' => true,
                'key'      => 'title',
            ],
            'date' => [
                'label'    => __('Date', 'notation-jlg'),
                'sortable' => true,
                'key'      => 'date',
            ],
            'note' => [
                'label'    => __('Note', 'notation-jlg'),
                'sortable' => true,
                'key'      => 'average_score',
            ],
            'developpeur' => [
                'label'    => __('Développeur', 'notation-jlg'),
                'sortable' => false,
            ],
            'editeur' => [
                'label'    => __('Éditeur', 'notation-jlg'),
                'sortable' => false,
            ],
        ];
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
}
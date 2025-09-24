<?php
if (!defined('ABSPATH')) exit;

class JLG_Shortcode_Game_Explorer {

    public function __construct() {
        add_shortcode('jlg_game_explorer', [$this, 'render']);
    }

    public function render($atts, $content = '', $shortcode_tag = '') {
        $context = self::get_render_context($atts, $_GET);

        if (!empty($context['error']) && !empty($context['message'])) {
            return $context['message'];
        }

        JLG_Frontend::mark_shortcode_rendered($shortcode_tag ?: 'jlg_game_explorer');

        return JLG_Frontend::get_template_html('shortcode-game-explorer', $context);
    }

    public static function get_default_atts() {
        $options = JLG_Helpers::get_plugin_options();
        $posts_per_page = isset($options['game_explorer_posts_per_page']) ? (int) $options['game_explorer_posts_per_page'] : 12;
        if ($posts_per_page < 1) {
            $posts_per_page = 12;
        }

        $columns = isset($options['game_explorer_columns']) ? (int) $options['game_explorer_columns'] : 3;
        if ($columns < 1) {
            $columns = 3;
        }

        $filters = isset($options['game_explorer_filters']) ? $options['game_explorer_filters'] : 'letter,category,platform,availability';

        return [
            'id'             => 'jlg-game-explorer-' . uniqid(),
            'posts_per_page' => $posts_per_page,
            'columns'        => $columns,
            'filters'        => $filters,
            'categorie'      => '',
            'plateforme'     => '',
            'lettre'         => '',
        ];
    }

    protected static function get_sort_options() {
        return [
            [
                'value'   => 'date|DESC',
                'orderby' => 'date',
                'order'   => 'DESC',
                'label'   => esc_html__('Plus récents', 'notation-jlg'),
            ],
            [
                'value'   => 'date|ASC',
                'orderby' => 'date',
                'order'   => 'ASC',
                'label'   => esc_html__('Plus anciens', 'notation-jlg'),
            ],
            [
                'value'   => 'score|DESC',
                'orderby' => 'score',
                'order'   => 'DESC',
                'label'   => esc_html__('Meilleures notes', 'notation-jlg'),
            ],
            [
                'value'   => 'score|ASC',
                'orderby' => 'score',
                'order'   => 'ASC',
                'label'   => esc_html__('Notes les plus basses', 'notation-jlg'),
            ],
            [
                'value'   => 'title|ASC',
                'orderby' => 'title',
                'order'   => 'ASC',
                'label'   => esc_html__('Titre (A-Z)', 'notation-jlg'),
            ],
            [
                'value'   => 'title|DESC',
                'orderby' => 'title',
                'order'   => 'DESC',
                'label'   => esc_html__('Titre (Z-A)', 'notation-jlg'),
            ],
        ];
    }

    public static function get_allowed_sort_keys() {
        $options = self::get_sort_options();
        $keys = [];

        foreach ($options as $option) {
            if (isset($option['orderby'])) {
                $keys[] = sanitize_key($option['orderby']);
            }
        }

        return array_values(array_unique(array_filter($keys)));
    }

    protected static function normalize_filters($filters_string) {
        $allowed = ['letter', 'category', 'platform', 'availability', 'search'];
        $normalized = [];

        if (is_string($filters_string)) {
            $parts = array_filter(array_map('trim', explode(',', strtolower($filters_string))));
            foreach ($parts as $part) {
                if (in_array($part, $allowed, true)) {
                    $normalized[$part] = true;
                }
            }
        }

        if (empty($normalized)) {
            $normalized = array_fill_keys(['letter', 'category', 'platform', 'availability'], true);
        }

        return $normalized;
    }

    protected static function normalize_letter($value) {
        if (!is_string($value)) {
            return '';
        }

        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $value = remove_accents($value);
        $first = mb_substr($value, 0, 1, 'UTF-8');
        $first_upper = mb_strtoupper($first, 'UTF-8');

        if (preg_match('/[A-Z]/u', $first_upper)) {
            return $first_upper;
        }

        if (preg_match('/[0-9]/u', $first_upper)) {
            return '#';
        }

        return '#';
    }

    protected static function resolve_category_id($value) {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_numeric($value)) {
            $id = (int) $value;
            return $id > 0 ? $id : 0;
        }

        $slug = sanitize_title($value);
        if ($slug === '') {
            return 0;
        }

        $term = get_category_by_slug($slug);
        if ($term && !is_wp_error($term)) {
            return (int) $term->term_id;
        }

        return 0;
    }

    protected static function resolve_platform_slug($value) {
        if (!is_string($value)) {
            return '';
        }

        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return sanitize_title($value);
    }

    protected static function normalize_date($raw_date) {
        if (!is_string($raw_date)) {
            return '';
        }

        $raw_date = trim($raw_date);
        if ($raw_date === '') {
            return '';
        }

        $date = DateTime::createFromFormat('Y-m-d', $raw_date);
        if ($date instanceof DateTime) {
            return $date->format('Y-m-d');
        }

        $timestamp = strtotime($raw_date);
        if ($timestamp) {
            return gmdate('Y-m-d', $timestamp);
        }

        return '';
    }

    protected static function determine_availability($date_iso) {
        if ($date_iso === '') {
            return [
                'status' => 'unknown',
                'label'  => esc_html__('À confirmer', 'notation-jlg'),
            ];
        }

        $timestamp = strtotime($date_iso . ' 00:00:00');
        if ($timestamp && $timestamp > current_time('timestamp')) {
            return [
                'status' => 'upcoming',
                'label'  => esc_html__('À venir', 'notation-jlg'),
            ];
        }

        return [
            'status' => 'available',
            'label'  => esc_html__('Disponible', 'notation-jlg'),
        ];
    }

    protected static function build_letter_navigation($letters_map) {
        $letters_map = is_array($letters_map) ? $letters_map : [];
        $alphabet = range('A', 'Z');
        $letters = [];

        foreach ($alphabet as $letter) {
            $letters[] = [
                'value'   => $letter,
                'label'   => $letter,
                'enabled' => !empty($letters_map[$letter]),
            ];
        }

        $letters[] = [
            'value'   => '#',
            'label'   => '#',
            'enabled' => !empty($letters_map['#']),
        ];

        return $letters;
    }

    public static function get_render_context($atts, $request = []) {
        $defaults = self::get_default_atts();
        $atts = shortcode_atts($defaults, $atts, 'jlg_game_explorer');

        $options = JLG_Helpers::get_plugin_options();
        $posts_per_page = isset($atts['posts_per_page']) ? (int) $atts['posts_per_page'] : $defaults['posts_per_page'];
        if ($posts_per_page < 1) {
            $posts_per_page = isset($options['game_explorer_posts_per_page']) ? (int) $options['game_explorer_posts_per_page'] : 12;
        }
        $posts_per_page = max(1, min($posts_per_page, 60));

        $columns = isset($atts['columns']) ? (int) $atts['columns'] : $defaults['columns'];
        if ($columns < 1) {
            $columns = isset($options['game_explorer_columns']) ? (int) $options['game_explorer_columns'] : 3;
        }
        $columns = max(1, min($columns, 4));

        $filters_enabled = self::normalize_filters($atts['filters']);

        $request = is_array($request) ? $request : [];
        $orderby = (isset($request['orderby']) && is_string($request['orderby'])) ? sanitize_key($request['orderby']) : 'date';
        $order = isset($request['order']) ? strtoupper(sanitize_text_field($request['order'])) : 'DESC';
        if (!in_array($order, ['ASC', 'DESC'], true)) {
            $order = 'DESC';
        }

        $letter_filter = isset($request['letter']) ? self::normalize_letter($request['letter']) : '';
        $category_filter = isset($request['category']) ? sanitize_text_field($request['category']) : '';
        $platform_filter = isset($request['platform']) ? sanitize_text_field($request['platform']) : '';
        $availability_filter = (isset($request['availability']) && is_string($request['availability'])) ? sanitize_key($request['availability']) : '';
        $search_filter = isset($request['search']) ? sanitize_text_field($request['search']) : '';
        $paged = isset($request['paged']) ? max(1, (int) $request['paged']) : 1;

        if (empty($filters_enabled['letter'])) {
            $letter_filter = '';
        }
        if (empty($filters_enabled['category'])) {
            $category_filter = '';
        }
        if (empty($filters_enabled['platform'])) {
            $platform_filter = '';
        }
        if (empty($filters_enabled['availability'])) {
            $availability_filter = '';
        }
        if (empty($filters_enabled['search'])) {
            $search_filter = '';
        }

        $forced_category = self::resolve_category_id($atts['categorie']);
        if ($forced_category > 0) {
            $category_filter = (string) $forced_category;
        }

        $forced_platform = self::resolve_platform_slug($atts['plateforme']);
        if ($forced_platform !== '') {
            $platform_filter = $forced_platform;
        }

        $forced_letter = self::normalize_letter($atts['lettre']);
        if ($forced_letter !== '') {
            $letter_filter = $forced_letter;
        }

        $rated_posts = JLG_Helpers::get_rated_post_ids();
        if (empty($rated_posts)) {
            $message = '<p>' . esc_html__('Aucun test noté n\'est disponible pour le moment.', 'notation-jlg') . '</p>';

            return [
                'error'   => true,
                'message' => $message,
                'atts'    => $atts,
            ];
        }

        $games = [];
        $letters_map = [];
        $categories_map = [];
        $platforms_map = [];
        $allowed_orderby = ['date', 'score', 'title'];

        foreach ($rated_posts as $post_id) {
            $post_id = (int) $post_id;
            if ($post_id <= 0) {
                continue;
            }

            $post = get_post($post_id);
            if (!$post || $post->post_status !== 'publish') {
                continue;
            }

            $title = JLG_Helpers::get_game_title($post_id);
            if ($title === '') {
                $title = get_the_title($post_id);
            }

            $letter = self::normalize_letter($title);
            if ($letter !== '') {
                $letters_map[$letter] = true;
            }

            $score_data = JLG_Helpers::get_resolved_average_score($post_id);
            $score_value = isset($score_data['value']) ? $score_data['value'] : null;
            $score_display = isset($score_data['formatted']) && $score_data['formatted'] !== ''
                ? $score_data['formatted']
                : esc_html__('N/A', 'notation-jlg');
            $score_color = $score_value !== null
                ? JLG_Helpers::calculate_color_from_note($score_value, $options)
                : ($options['color_mid'] ?? '#f97316');

            $cover_meta = get_post_meta($post_id, '_jlg_cover_image_url', true);
            $cover_url = '';
            if (is_string($cover_meta) && $cover_meta !== '') {
                $cover_url = esc_url_raw($cover_meta);
            }
            if ($cover_url === '') {
                $thumbnail = get_the_post_thumbnail_url($post_id, 'large');
                if ($thumbnail) {
                    $cover_url = $thumbnail;
                }
            }

            $release_raw = get_post_meta($post_id, '_jlg_date_sortie', true);
            $release_iso = self::normalize_date($release_raw);
            $release_display = '';
            if ($release_iso !== '') {
                $release_display = date_i18n(get_option('date_format'), strtotime($release_iso . ' 00:00:00'));
            }

            $availability = self::determine_availability($release_iso);

            $developer = get_post_meta($post_id, '_jlg_developpeur', true);
            $publisher = get_post_meta($post_id, '_jlg_editeur', true);
            $developer = is_string($developer) ? sanitize_text_field($developer) : '';
            $publisher = is_string($publisher) ? sanitize_text_field($publisher) : '';

            $platform_meta = get_post_meta($post_id, '_jlg_plateformes', true);
            $platform_labels = [];
            $platform_slugs = [];
            if (is_array($platform_meta)) {
                foreach ($platform_meta as $platform_item) {
                    if (!is_string($platform_item)) {
                        continue;
                    }
                    $label = sanitize_text_field($platform_item);
                    if ($label === '') {
                        continue;
                    }
                    $slug = self::resolve_platform_slug($label);
                    $platform_labels[] = $label;
                    if ($slug !== '') {
                        $platform_slugs[] = $slug;
                        $platforms_map[$slug] = $label;
                    }
                }
            } elseif (is_string($platform_meta) && $platform_meta !== '') {
                $label = sanitize_text_field($platform_meta);
                $slug = self::resolve_platform_slug($label);
                $platform_labels[] = $label;
                if ($slug !== '') {
                    $platform_slugs[] = $slug;
                    $platforms_map[$slug] = $label;
                }
            }

            $terms = get_the_terms($post_id, 'category');
            $category_ids = [];
            $category_slugs = [];
            $primary_genre = '';
            if ($terms && !is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $categories_map[$term->term_id] = $term->name;
                    $category_ids[] = (int) $term->term_id;
                    $category_slugs[] = $term->slug;
                    if ($primary_genre === '') {
                        $primary_genre = $term->name;
                    }
                }
            }

            $excerpt = get_the_excerpt($post_id);
            if (!is_string($excerpt) || $excerpt === '') {
                $excerpt = wp_trim_words(wp_strip_all_tags($post->post_content), 24, '…');
            }

            $timestamp = get_post_time('U', true, $post);

            $search_tokens = strtolower(
                wp_strip_all_tags($title . ' ' . $developer . ' ' . $publisher . ' ' . $primary_genre . ' ' . implode(' ', $platform_labels))
            );

            $games[] = [
                'post_id'             => $post_id,
                'title'               => $title,
                'permalink'           => get_permalink($post_id),
                'score_value'         => $score_value,
                'score_display'       => $score_display,
                'score_color'         => $score_color,
                'cover_url'           => $cover_url,
                'release_date'        => $release_iso,
                'release_display'     => $release_display,
                'developer'           => $developer,
                'publisher'           => $publisher,
                'platforms'           => $platform_labels,
                'platform_slugs'      => $platform_slugs,
                'category_ids'        => $category_ids,
                'category_slugs'      => $category_slugs,
                'genre'               => $primary_genre,
                'excerpt'             => $excerpt,
                'letter'              => $letter,
                'availability'        => $availability['status'],
                'availability_label'  => $availability['label'],
                'timestamp'           => $timestamp,
                'search_haystack'     => $search_tokens,
            ];
        }

        if (empty($games)) {
            $message = '<p>' . esc_html__('Aucun test noté n\'est disponible pour le moment.', 'notation-jlg') . '</p>';

            return [
                'error'   => true,
                'message' => $message,
                'atts'    => $atts,
            ];
        }

        $filtered_games = array_filter($games, function($game) use ($letter_filter, $category_filter, $platform_filter, $availability_filter, $search_filter) {
            if ($letter_filter !== '' && $game['letter'] !== $letter_filter) {
                return false;
            }

            if ($category_filter !== '') {
                $category_id = (int) $category_filter;
                if ($category_id > 0) {
                    if (!in_array($category_id, $game['category_ids'], true)) {
                        return false;
                    }
                } else {
                    $category_slug = sanitize_title($category_filter);
                    if ($category_slug !== '' && !in_array($category_slug, $game['category_slugs'], true)) {
                        return false;
                    }
                }
            }

            if ($platform_filter !== '') {
                $normalized_platform = sanitize_title($platform_filter);
                if ($normalized_platform === '' || !in_array($normalized_platform, $game['platform_slugs'], true)) {
                    return false;
                }
            }

            if ($availability_filter !== '') {
                if ($game['availability'] !== $availability_filter) {
                    return false;
                }
            }

            if ($search_filter !== '') {
                $haystack = $game['search_haystack'];
                if (strpos($haystack, strtolower($search_filter)) === false) {
                    return false;
                }
            }

            return true;
        });

        if (!in_array($orderby, $allowed_orderby, true)) {
            $orderby = 'date';
        }

        usort($filtered_games, function($a, $b) use ($orderby, $order) {
            switch ($orderby) {
                case 'score':
                    $value_a = ($a['score_value'] !== null) ? (float) $a['score_value'] : -1;
                    $value_b = ($b['score_value'] !== null) ? (float) $b['score_value'] : -1;
                    break;
                case 'title':
                    $value_a = strtolower($a['title']);
                    $value_b = strtolower($b['title']);
                    break;
                case 'date':
                default:
                    $value_a = (int) $a['timestamp'];
                    $value_b = (int) $b['timestamp'];
                    break;
            }

            if ($value_a == $value_b) {
                return 0;
            }

            if ($order === 'ASC') {
                return ($value_a < $value_b) ? -1 : 1;
            }

            return ($value_a > $value_b) ? -1 : 1;
        });

        $total_items = count($filtered_games);
        $total_pages = ($total_items > 0) ? (int) ceil($total_items / $posts_per_page) : 0;
        if ($total_pages > 0 && $paged > $total_pages) {
            $paged = $total_pages;
        }
        if ($total_pages === 0) {
            $paged = 1;
        }

        $offset = ($paged - 1) * $posts_per_page;
        $page_games = array_slice($filtered_games, $offset, $posts_per_page);

        $categories_list = [];
        if (!empty($categories_map)) {
            asort($categories_map, SORT_NATURAL | SORT_FLAG_CASE);
            foreach ($categories_map as $id => $name) {
                $categories_list[] = [
                    'value' => (string) $id,
                    'label' => $name,
                ];
            }
        }

        $platforms_list = [];
        if (!empty($platforms_map)) {
            $registered_platforms = JLG_Helpers::get_registered_platform_labels();
            foreach ($registered_platforms as $slug => $label) {
                $normalized_slug = self::resolve_platform_slug($label);
                if ($normalized_slug !== '' && isset($platforms_map[$normalized_slug])) {
                    $platforms_list[$normalized_slug] = $platforms_map[$normalized_slug];
                }
            }
            foreach ($platforms_map as $slug => $label) {
                if (!isset($platforms_list[$slug])) {
                    $platforms_list[$slug] = $label;
                }
            }
            natcasesort($platforms_list);
        }

        $platform_entries = [];
        foreach ($platforms_list as $slug => $label) {
            $platform_entries[] = [
                'value' => $slug,
                'label' => $label,
            ];
        }

        $availability_options = [
            'available'  => esc_html__('Disponible', 'notation-jlg'),
            'upcoming'   => esc_html__('À venir', 'notation-jlg'),
            'unknown'    => esc_html__('À confirmer', 'notation-jlg'),
        ];

        $message = '<p>' . esc_html__('Aucun jeu ne correspond à vos filtres actuels.', 'notation-jlg') . '</p>';

        $config_payload = [
            'atts' => [
                'id'             => $atts['id'],
                'posts_per_page' => $posts_per_page,
                'columns'        => $columns,
                'filters'        => implode(',', array_keys(array_filter($filters_enabled))),
                'categorie'      => $atts['categorie'],
                'plateforme'     => $atts['plateforme'],
                'lettre'         => $atts['lettre'],
            ],
            'state' => [
                'orderby'      => $orderby,
                'order'        => $order,
                'letter'       => $letter_filter,
                'category'     => $category_filter,
                'platform'     => $platform_filter,
                'availability' => $availability_filter,
                'search'       => $search_filter,
                'paged'        => $paged,
            ],
        ];

        return [
            'atts'               => array_merge($atts, [
                'posts_per_page' => $posts_per_page,
                'columns'        => $columns,
            ]),
            'games'              => array_values($page_games),
            'letters'            => self::build_letter_navigation($letters_map),
            'filters_enabled'    => $filters_enabled,
            'current_filters'    => [
                'letter'       => $letter_filter,
                'category'     => $category_filter,
                'platform'     => $platform_filter,
                'availability' => $availability_filter,
                'search'       => $search_filter,
            ],
            'sort_options'       => self::get_sort_options(),
            'sort_key'           => $orderby,
            'sort_order'         => $order,
            'pagination'         => [
                'current' => $paged,
                'total'   => $total_pages,
            ],
            'categories_list'    => $categories_list,
            'platforms_list'     => $platform_entries,
            'availability_options' => $availability_options,
            'total_items'        => $total_items,
            'message'            => $message,
            'config_payload'     => $config_payload,
        ];
    }
}

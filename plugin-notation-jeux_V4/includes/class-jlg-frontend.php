<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class JLG_Frontend {

    /**
     * Contiendra les erreurs de chargement des shortcodes pour affichage.
     * @var array
     */
    private static $shortcode_errors = [];

    /**
     * Instance courante du frontend.
     *
     * @var self|null
     */
    private static $instance = null;

    /**
     * Indique si au moins un shortcode du plugin a été rendu.
     *
     * @var bool
     */
    private static $shortcode_rendered = false;

    /**
     * Indique si les assets frontend ont déjà été chargés pour la requête.
     *
     * @var bool
     */
    private static $assets_enqueued = false;

    /**
     * Indique si l'impression différée des styles frontend a été programmée.
     *
     * @var bool
     */
    private static $deferred_styles_hooked = false;

    public function __construct() {
        self::$instance = $this;
        // On charge les shortcodes via le hook 'init' pour s'assurer que WordPress est prêt
        add_action('init', [$this, 'initialize_shortcodes']);

        add_action('wp_enqueue_scripts', [$this, 'enqueue_jlg_scripts']);
        add_filter('do_shortcode_tag', [$this, 'track_shortcode_usage'], 10, 4);
        add_action('wp_ajax_jlg_rate_post', [$this, 'handle_user_rating']);
        add_action('wp_ajax_nopriv_jlg_rate_post', [$this, 'handle_user_rating']);
        add_action('wp_ajax_jlg_summary_sort', [$this, 'handle_summary_sort']);
        add_action('wp_ajax_nopriv_jlg_summary_sort', [$this, 'handle_summary_sort']);
        add_action('wp_head', [$this, 'inject_review_schema']);
    }

    /**
     * Initialise les shortcodes une fois WordPress prêt
     */
    public function initialize_shortcodes() {
        $this->load_shortcodes();
    }

    /**
     * Charge tous les shortcodes du plugin
     */
    private function load_shortcodes() {
        $shortcodes = [
            'Rating_Block',
            'Pros_Cons',
            'Game_Info',
            'User_Rating',
            'Tagline',
            'Summary_Display',
            'All_In_One'  // NOUVEAU SHORTCODE AJOUTÉ ICI
        ];
        
        $errors = [];

        foreach ($shortcodes as $shortcode) {
            $class_name = 'JLG_Shortcode_' . $shortcode;
            $file_path = JLG_NOTATION_PLUGIN_DIR . 'includes/shortcodes/class-jlg-shortcode-' . strtolower(str_replace('_', '-', $shortcode)) . '.php';
            
            if (file_exists($file_path)) {
                require_once $file_path;
                if (!class_exists($class_name)) {
                    $errors[] = "Le fichier existe <code>{$file_path}</code>, mais la classe <code>{$class_name}</code> n'a pas été trouvée à l'intérieur.";
                } else {
                    new $class_name();
                }
            } else {
                $errors[] = "Fichier de shortcode manquant : <code>{$file_path}</code>";
            }
        }

        // On vérifie les erreurs et on les affiche seulement pour les admins
        if (!empty($errors)) {
            self::$shortcode_errors = $errors;
            // On diffère la vérification des capacités utilisateur
            add_action('admin_notices', [$this, 'display_shortcode_errors']);
        }
    }

    /**
     * Affiche les erreurs de chargement des shortcodes dans l'admin
     */
    public function display_shortcode_errors() {
        // Maintenant on peut vérifier les capacités car WordPress est complètement chargé
        if (!empty(self::$shortcode_errors) && current_user_can('manage_options')) {
            echo '<div class="notice notice-error"><p><strong>Plugin Notation - JLG : Erreur de chargement des shortcodes !</strong></p><ul>';
            foreach (self::$shortcode_errors as $error) {
                printf('<li>%s</li>', esc_html($error));
            }
            echo '</ul></div>';
        }
    }

    /**
     * Retourne la liste des shortcodes disponibles.
     *
     * @return array
     */
    private function get_plugin_shortcodes() {
        return [
            'bloc_notation_jeu',
            'jlg_points_forts_faibles',
            'jlg_fiche_technique',
            'tagline_notation_jlg',
            'jlg_tableau_recap',
            'notation_utilisateurs_jlg',
            'jlg_bloc_complet',
            'bloc_notation_complet',
        ];
    }

    /**
     * Marque l'utilisation d'un shortcode du plugin.
     */
    public static function mark_shortcode_rendered() {
        self::$shortcode_rendered = true;

        if (!self::$assets_enqueued && did_action('wp_enqueue_scripts') && self::$instance instanceof self) {
            self::$instance->enqueue_jlg_scripts(true);

            if (did_action('wp_print_styles') && !wp_style_is('jlg-frontend', 'done')) {
                if (!self::$deferred_styles_hooked) {
                    self::$deferred_styles_hooked = true;
                    add_action('wp_footer', [self::$instance, 'print_deferred_styles']);
                }
            }
        }
    }

    /**
     * Imprime la feuille de style frontend si elle n'a pas déjà été imprimée.
     */
    public function print_deferred_styles() {
        if (!wp_style_is('jlg-frontend', 'done')) {
            wp_print_styles('jlg-frontend');
        }

        if (wp_style_is('jlg-frontend', 'done')) {
            remove_action('wp_footer', [self::$instance, 'print_deferred_styles']);
            self::$deferred_styles_hooked = false;
        }
    }

    /**
     * Détecte l'exécution des shortcodes du plugin lors de leur rendu.
     */
    public function track_shortcode_usage($output, $tag, $attr, $m) {
        if (in_array($tag, $this->get_plugin_shortcodes(), true)) {
            self::mark_shortcode_rendered();
        }

        return $output;
    }

    /**
     * Vérifie si un contenu contient l'un des shortcodes du plugin.
     *
     * @param string $content
     * @return bool
     */
    private function content_has_plugin_shortcode($content) {
        if (!is_string($content) || $content === '') {
            return false;
        }

        foreach ($this->get_plugin_shortcodes() as $shortcode) {
            if (has_shortcode($content, $shortcode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si un article possède des métadonnées du plugin.
     *
     * @param int $post_id
     * @return bool
     */
    private function post_has_plugin_metadata($post_id) {
        if (!$post_id) {
            return false;
        }

        $meta = get_post_meta($post_id);

        if (empty($meta) || !is_array($meta)) {
            return false;
        }

        foreach ($meta as $meta_key => $values) {
            if (strpos($meta_key, '_jlg_') !== 0) {
                continue;
            }

            $values = is_array($values) ? $values : [$values];

            foreach ($values as $value) {
                if ($this->is_meta_value_filled($value)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Détermine si une valeur de métadonnée contient des informations exploitables.
     *
     * @param mixed $value
     * @return bool
     */
    private function is_meta_value_filled($value) {
        if (is_array($value)) {
            foreach ($value as $item) {
                if ($this->is_meta_value_filled($item)) {
                    return true;
                }
            }

            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        return $value !== null && $value !== '';
    }

    /**
     * Charge les scripts JavaScript nécessaires
     */
    public function enqueue_jlg_scripts($force = false) {
        if (self::$assets_enqueued) {
            return;
        }

        $should_enqueue = $force || self::$shortcode_rendered;

        if (!$should_enqueue) {
            $queried_object = get_queried_object();

            if ($queried_object instanceof WP_Post) {
                if ($this->content_has_plugin_shortcode($queried_object->post_content ?? '')) {
                    $should_enqueue = true;
                } elseif ($this->post_has_plugin_metadata($queried_object->ID)) {
                    $should_enqueue = true;
                }
            }
        }

        if (!$should_enqueue) {
            return;
        }

        self::$assets_enqueued = true;
        self::$shortcode_rendered = true;

        $options = JLG_Helpers::get_plugin_options();
        $palette = JLG_Helpers::get_color_palette();
        $post_id = get_queried_object_id();
        $average_score = JLG_Helpers::get_average_score_for_post($post_id);

        // Feuille de styles principale
        wp_enqueue_style(
            'jlg-frontend',
            JLG_NOTATION_PLUGIN_URL . 'assets/css/jlg-frontend.css',
            [],
            JLG_NOTATION_VERSION
        );

        $inline_css = JLG_Dynamic_CSS::build_frontend_css($options, $palette, $average_score);

        wp_add_inline_style('jlg-frontend', $inline_css);

        // Script pour la notation utilisateur
        if (!empty($options['user_rating_enabled'])) {
            wp_enqueue_script(
                'jlg-user-rating',
                JLG_NOTATION_PLUGIN_URL . 'assets/js/user-rating.js',
                ['jquery'],
                JLG_NOTATION_VERSION,
                true
            );
            $cookie_name = 'jlg_user_rating_token';
            $token = self::get_user_rating_token_from_cookie();

            if ($token === '') {
                try {
                    $token = bin2hex(random_bytes(32));
                } catch (Exception $e) {
                    $token = md5(uniqid('', true));
                }

                $cookie_options = [
                    'expires'  => time() + MONTH_IN_SECONDS,
                    'path'     => defined('COOKIEPATH') ? COOKIEPATH : '/',
                    'secure'   => is_ssl(),
                    'httponly' => true,
                    'samesite' => 'Lax',
                ];

                if (defined('COOKIE_DOMAIN') && COOKIE_DOMAIN) {
                    $cookie_options['domain'] = COOKIE_DOMAIN;
                }

                if (!headers_sent()) {
                    setcookie($cookie_name, $token, $cookie_options);
                }
                $_COOKIE[$cookie_name] = $token;
            }

            $nonce = wp_create_nonce('jlg_user_rating_nonce_' . $token);

            wp_localize_script('jlg-user-rating', 'jlg_rating_ajax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => $nonce,
                'token'    => $token,
            ]);
        }

        // Script pour le changement de langue des taglines
        if (!empty($options['tagline_enabled'])) {
            wp_enqueue_script(
                'jlg-tagline-switcher',
                JLG_NOTATION_PLUGIN_URL . 'assets/js/tagline-switcher.js',
                ['jquery'],
                JLG_NOTATION_VERSION,
                true
            );
        }

        // Script pour les animations
        if (!empty($options['enable_animations'])) {
            wp_enqueue_script(
                'jlg-animations',
                JLG_NOTATION_PLUGIN_URL . 'assets/js/jlg-animations.js',
                [],
                JLG_NOTATION_VERSION,
                true
            );
        }

        wp_enqueue_script(
            'jlg-summary-table-sort',
            JLG_NOTATION_PLUGIN_URL . 'assets/js/summary-table-sort.js',
            ['jquery'],
            JLG_NOTATION_VERSION,
            true
        );

        wp_localize_script('jlg-summary-table-sort', 'jlgSummarySort', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('jlg_summary_sort'),
            'strings'  => [
                'genericError' => esc_html__('Une erreur est survenue. Merci de réessayer plus tard.', 'notation-jlg'),
            ],
        ]);
    }

    /**
     * Gère la notation AJAX des utilisateurs
     */
    public function handle_user_rating() {
        $cookie_name = 'jlg_user_rating_token';
        $token = '';

        if (isset($_POST['token'])) {
            $token = self::normalize_user_rating_token(wp_unslash($_POST['token']));
        }

        if ($token === '' && isset($_COOKIE[$cookie_name])) {
            $token = self::normalize_user_rating_token(wp_unslash($_COOKIE[$cookie_name]));
        }

        if ($token === '') {
            wp_send_json_error(['message' => esc_html__('Jeton de sécurité manquant ou invalide.', 'notation-jlg')], 400);
        }

        if (!check_ajax_referer('jlg_user_rating_nonce_' . $token, 'nonce', false)) {
            wp_send_json_error(['message' => esc_html__('La vérification de sécurité a échoué.', 'notation-jlg')], 403);
        }

        $options = JLG_Helpers::get_plugin_options();

        if (empty($options['user_rating_enabled'])) {
            wp_send_json_error([
                'message' => esc_html__('La notation des lecteurs est désactivée.', 'notation-jlg'),
            ], 403);
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

        if (!$post_id) {
            wp_send_json_error(['message' => esc_html__('Données invalides.', 'notation-jlg')]);
        }

        $post = get_post($post_id);

        if (!$post || 'post' !== $post->post_type || 'trash' === $post->post_status || 'publish' !== $post->post_status) {
            wp_send_json_error(['message' => esc_html__('Article introuvable ou non disponible pour la notation.', 'notation-jlg')], 404);
        }

        $allows_user_rating = apply_filters(
            'jlg_post_allows_user_rating',
            $this->post_allows_user_rating($post, $options),
            $post
        );

        if (!$allows_user_rating) {
            wp_send_json_error(['message' => esc_html__('La notation des lecteurs est désactivée pour ce contenu.', 'notation-jlg')], 403);
        }

        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;

        if ($rating < 1 || $rating > 5) {
            wp_send_json_error(['message' => esc_html__('Données invalides.', 'notation-jlg')]);
        }

        $user_ip = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);
        $user_ip_hash = $user_ip ? wp_hash($user_ip) : '';

        $token_hash = self::hash_user_rating_token($token);

        $ratings_meta = [];
        $ratings = self::get_post_user_rating_tokens($post_id, $ratings_meta);

        if (isset($ratings[$token_hash])) {
            wp_send_json_error(['message' => esc_html__('Vous avez déjà voté !', 'notation-jlg')]);
        }

        $ratings[$token_hash] = $rating;
        self::store_post_user_rating_tokens($post_id, $ratings, $ratings_meta);

        if ($user_ip_hash) {
            self::update_user_rating_ip_log($post_id, $user_ip_hash, $token_hash, $rating);
        }

        list($new_average, $ratings_count) = self::calculate_user_rating_stats($ratings);

        update_post_meta($post_id, '_jlg_user_rating_avg', $new_average);
        update_post_meta($post_id, '_jlg_user_rating_count', $ratings_count);

        wp_send_json_success([
            'new_average' => number_format_i18n($new_average, 2),
            'new_count' => $ratings_count
        ]);
    }

    private static function normalize_user_rating_token($token) {
        if (!is_string($token)) {
            return '';
        }

        $token = sanitize_text_field($token);

        if (!preg_match('/^[A-Fa-f0-9]{32,128}$/', $token)) {
            return '';
        }

        return $token;
    }

    public static function get_user_rating_token_from_cookie() {
        $cookie_name = 'jlg_user_rating_token';

        if (!isset($_COOKIE[$cookie_name])) {
            return '';
        }

        return self::normalize_user_rating_token(wp_unslash($_COOKIE[$cookie_name]));
    }

    private static function hash_user_rating_token($token) {
        return wp_hash($token);
    }

    private static function get_post_user_rating_tokens($post_id, &$meta = null) {
        $meta_key = '_jlg_user_ratings';
        $stored = get_post_meta($post_id, $meta_key, true);

        if (!is_array($stored)) {
            $stored = [];
        }

        $meta_data = [];

        if (isset($stored['__meta']) && is_array($stored['__meta'])) {
            $meta_data = $stored['__meta'];
            unset($stored['__meta']);
        }

        $normalized = [];

        foreach ($stored as $key => $value) {
            if (!is_string($key) || !preg_match('/^[A-Fa-f0-9]{32,128}$/', $key)) {
                continue;
            }

            if (!is_numeric($value)) {
                continue;
            }

            $normalized[$key] = (float) $value;
        }

        $needs_meta_update = !isset($meta_data['version']) || (int) $meta_data['version'] < 2;

        if ($needs_meta_update) {
            $meta_data['version'] = 2;
            $to_store = $normalized;
            $to_store['__meta'] = $meta_data;
            update_post_meta($post_id, $meta_key, $to_store);
            self::ensure_ip_log_for_legacy_votes($post_id, $normalized);
        }

        if ($meta !== null) {
            $meta = $meta_data;
        }

        return $normalized;
    }

    private static function store_post_user_rating_tokens($post_id, array $ratings, array $meta = []) {
        if (!isset($meta['version']) || (int) $meta['version'] < 2) {
            $meta['version'] = 2;
        }

        $data = $ratings;
        $data['__meta'] = $meta;

        update_post_meta($post_id, '_jlg_user_ratings', $data);
    }

    private static function ensure_ip_log_for_legacy_votes($post_id, array $ratings) {
        if (empty($ratings)) {
            return;
        }

        $ip_meta_key = '_jlg_user_rating_ips';
        $ip_log = get_post_meta($post_id, $ip_meta_key, true);

        if (!is_array($ip_log)) {
            $ip_log = [];
        }

        $updated = false;
        $timestamp = current_time('timestamp');

        foreach ($ratings as $hash => $value) {
            if (!isset($ip_log[$hash]) || !is_array($ip_log[$hash])) {
                $ip_log[$hash] = [
                    'rating'    => (float) $value,
                    'votes'     => 1,
                    'last_vote' => $timestamp,
                    'legacy'    => true,
                ];
                $updated = true;
                continue;
            }

            $existing = $ip_log[$hash];

            if (!isset($existing['rating'])) {
                $existing['rating'] = (float) $value;
            }

            if (!isset($existing['votes'])) {
                $existing['votes'] = 1;
            }

            $existing['legacy'] = true;
            $existing['last_vote'] = isset($existing['last_vote']) ? $existing['last_vote'] : $timestamp;

            $ip_log[$hash] = $existing;
            $updated = true;
        }

        if ($updated) {
            update_post_meta($post_id, $ip_meta_key, $ip_log);
        }
    }

    private static function update_user_rating_ip_log($post_id, $ip_hash, $token_hash, $rating) {
        $ip_meta_key = '_jlg_user_rating_ips';
        $ip_log = get_post_meta($post_id, $ip_meta_key, true);

        if (!is_array($ip_log)) {
            $ip_log = [];
        }

        $timestamp = current_time('timestamp');
        $entry = isset($ip_log[$ip_hash]) && is_array($ip_log[$ip_hash]) ? $ip_log[$ip_hash] : [];

        $entry['rating'] = (float) $rating;
        $entry['token'] = $token_hash;
        $entry['last_vote'] = $timestamp;
        $entry['votes'] = isset($entry['votes']) ? (int) $entry['votes'] + 1 : 1;

        unset($entry['legacy']);

        $ip_log[$ip_hash] = $entry;

        update_post_meta($post_id, $ip_meta_key, $ip_log);
    }

    private static function calculate_user_rating_stats(array $ratings) {
        $values = [];

        foreach ($ratings as $value) {
            if (is_numeric($value)) {
                $values[] = (float) $value;
            }
        }

        $count = count($values);

        if ($count === 0) {
            return [0.0, 0];
        }

        $average = round(array_sum($values) / $count, 2);

        return [$average, $count];
    }

    public static function get_user_vote_for_post($post_id, $token = '') {
        $post_id = intval($post_id);

        if ($post_id <= 0) {
            return [false, 0];
        }

        if ($token === '') {
            $token = self::get_user_rating_token_from_cookie();
        } else {
            $token = self::normalize_user_rating_token($token);
        }

        if ($token === '') {
            return [false, 0];
        }

        $token_hash = self::hash_user_rating_token($token);
        $ratings = self::get_post_user_rating_tokens($post_id);

        if (isset($ratings[$token_hash])) {
            return [true, $ratings[$token_hash]];
        }

        return [false, 0];
    }

    /**
     * Détermine si un article est éligible aux votes des lecteurs.
     */
    private function post_allows_user_rating($post, $options = null) {
        if (!($post instanceof WP_Post)) {
            return false;
        }

        if (!is_array($options)) {
            $options = JLG_Helpers::get_plugin_options();
        }

        if (empty($options['user_rating_enabled'])) {
            return false;
        }

        $content = $post->post_content ?? '';

        foreach (['notation_utilisateurs_jlg', 'jlg_bloc_complet', 'bloc_notation_complet'] as $shortcode) {
            if (has_shortcode($content, $shortcode)) {
                return true;
            }
        }

        return false;
    }

    public function handle_summary_sort() {
        if (!check_ajax_referer('jlg_summary_sort', 'nonce', false)) {
            wp_send_json_error(['message' => esc_html__('La vérification de sécurité a échoué.', 'notation-jlg')], 403);
        }

        if (!class_exists('JLG_Shortcode_Summary_Display')) {
            wp_send_json_error(['message' => esc_html__('Le shortcode requis est indisponible.', 'notation-jlg')], 500);
        }

        $default_atts = JLG_Shortcode_Summary_Display::get_default_atts();
        $default_posts_per_page = isset($default_atts['posts_per_page']) ? intval($default_atts['posts_per_page']) : 12;
        if ($default_posts_per_page < 1) {
            $default_posts_per_page = 1;
        }

        $posts_per_page_input = isset($_POST['posts_per_page']) ? wp_unslash($_POST['posts_per_page']) : null;
        $posts_per_page = null;

        if ($posts_per_page_input !== null && !is_array($posts_per_page_input)) {
            $posts_per_page = intval($posts_per_page_input);
        }

        if ($posts_per_page === null || $posts_per_page < 1) {
            $posts_per_page = $default_posts_per_page;
        }

        $posts_per_page = max(1, min($posts_per_page, 50));

        $atts = [
            'posts_per_page' => $posts_per_page,
            'layout'         => isset($_POST['layout']) ? sanitize_text_field(wp_unslash($_POST['layout'])) : 'table',
            'categorie'      => isset($_POST['categorie']) ? sanitize_text_field(wp_unslash($_POST['categorie'])) : '',
            'colonnes'       => isset($_POST['colonnes']) ? sanitize_text_field(wp_unslash($_POST['colonnes'])) : 'titre,date,note',
            'id'             => isset($_POST['table_id']) ? sanitize_html_class(wp_unslash($_POST['table_id'])) : 'jlg-table-' . uniqid(),
        ];

        $request = [
            'orderby'    => isset($_POST['orderby']) ? sanitize_key(wp_unslash($_POST['orderby'])) : 'date',
            'order'      => isset($_POST['order']) ? sanitize_text_field(wp_unslash($_POST['order'])) : 'DESC',
            'cat_filter' => isset($_POST['cat_filter']) ? intval($_POST['cat_filter']) : 0,
            'paged'      => isset($_POST['paged']) ? intval($_POST['paged']) : 1,
        ];

        $current_url = isset($_POST['current_url']) ? esc_url_raw(wp_unslash($_POST['current_url'])) : '';
        $original_request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

        if (!empty($current_url)) {
            $parsed_url = wp_parse_url($current_url);
            if (!empty($parsed_url['path'])) {
                $request_uri = $parsed_url['path'];
                if (!empty($parsed_url['query'])) {
                    $request_uri .= '?' . $parsed_url['query'];
                }
                $_SERVER['REQUEST_URI'] = $request_uri;
            }
        }

        $context = JLG_Shortcode_Summary_Display::get_render_context($atts, $request, false);

        $state = [
            'orderby'    => $context['orderby'] ?? 'date',
            'order'      => $context['order'] ?? 'DESC',
            'paged'      => $context['paged'] ?? 1,
            'cat_filter' => $context['cat_filter'] ?? 0,
            'total_pages' => 0,
        ];

        if (!empty($context['error']) && !empty($context['message'])) {
            if ($original_request_uri !== '') {
                $_SERVER['REQUEST_URI'] = $original_request_uri;
            }

            wp_send_json_success([
                'html'  => $context['message'],
                'state' => $state,
            ]);
        }

        $html = JLG_Frontend::get_template_html('summary-table-fragment', $context);

        if (isset($context['query']) && $context['query'] instanceof WP_Query) {
            $state['total_pages'] = intval($context['query']->max_num_pages);
        }

        if ($original_request_uri !== '') {
            $_SERVER['REQUEST_URI'] = $original_request_uri;
        }

        wp_send_json_success([
            'html'  => $html,
            'state' => $state,
        ]);
    }

    /**
     * Injecte le schema de notation pour le SEO
     */
    public function inject_review_schema() {
        $options = JLG_Helpers::get_plugin_options();
        
        if (empty($options['seo_schema_enabled']) || !is_singular('post')) { 
            return; 
        }
        
        $post_id = get_the_ID();
        $average_score = JLG_Helpers::get_average_score_for_post($post_id);
        
        if ($average_score === null) { 
            return; 
        }
        
        $review_rating_bounds = apply_filters(
            'jlg_review_rating_bounds',
            [
                'min' => 0,
                'max' => 10,
            ],
            $post_id
        );

        $review_best_rating  = isset($review_rating_bounds['max']) ? floatval($review_rating_bounds['max']) : 10;
        $review_worst_rating = isset($review_rating_bounds['min']) ? floatval($review_rating_bounds['min']) : 0;

        $schema = [
            '@context'      => 'https://schema.org',
            '@type'         => 'Game',
            'name'          => JLG_Helpers::get_game_title($post_id),
            'review'        => [
                '@type'         => 'Review',
                'reviewRating'  => [
                    '@type'       => 'Rating',
                    'ratingValue' => $average_score,
                    'bestRating'  => $review_best_rating,
                    'worstRating' => $review_worst_rating,
                ],
                'author'        => [
                    '@type' => 'Person',
                    'name'  => get_the_author_meta('display_name', get_post_field('post_author', $post_id))
                ],
                'datePublished' => get_the_date('c', $post_id),
            ],
        ];
        
        $user_rating_count = (int) get_post_meta($post_id, '_jlg_user_rating_count', true);
        
        $user_rating_enabled = isset($options['user_rating_enabled'])
            ? $options['user_rating_enabled']
            : (JLG_Helpers::get_default_settings()['user_rating_enabled'] ?? 0);

        if (!empty($user_rating_enabled) && $user_rating_count > 0) {
            $aggregate_rating_value = (float) get_post_meta($post_id, '_jlg_user_rating_avg', true);

            $user_rating_bounds = apply_filters(
                'jlg_user_rating_bounds',
                [
                    'min' => 1,
                    'max' => 5,
                ],
                $post_id
            );

            $aggregate_best_rating  = isset($user_rating_bounds['max']) ? floatval($user_rating_bounds['max']) : 5.0;
            $aggregate_worst_rating = isset($user_rating_bounds['min']) ? floatval($user_rating_bounds['min']) : 1.0;

            $schema['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => $aggregate_rating_value,
                'ratingCount' => $user_rating_count,
                'bestRating'  => $aggregate_best_rating,
                'worstRating' => $aggregate_worst_rating,
            ];
        }
        
        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }

    /**
     * Charge un fichier template en lui passant des variables.
     * 
     * @param string $template_name Le nom du fichier template.
     * @param array $args Les variables à passer au template.
     * @return string Le contenu HTML du template.
     */
    public static function get_template_html($template_name, $args = []) {
        // S'assurer que $args est bien un tableau
        if (!is_array($args)) {
            $args = [];
        }

        // Construire le chemin du template
        $template_path = JLG_NOTATION_PLUGIN_DIR . 'templates/' . $template_name . '.php';

        // Démarrer la capture de sortie
        ob_start();

        // Inclure le template s'il existe
        if (file_exists($template_path)) {
            // Valeurs par défaut pour les variables utilisées par les templates existants.
            $template_defaults = [
                'options'            => [],
                'average_score'      => null,
                'scores'             => [],
                'categories'         => [],
                'pros_list'          => [],
                'cons_list'          => [],
                'titre'              => '',
                'champs_a_afficher'  => [],
                'tagline_fr'         => '',
                'tagline_en'         => '',
                'query'              => null,
                'atts'               => [],
                'block_classes'      => '',
                'css_variables'      => '',
                'score_layout'       => 'text',
                'animations_enabled' => false,
                'paged'              => 1,
                'orderby'            => '',
                'order'              => '',
                'colonnes'           => [],
                'colonnes_disponibles' => [],
                'error_message'      => '',
                'cat_filter'         => 0,
                'table_id'           => '',
                'widget_args'        => [],
                'title'              => '',
                'latest_reviews'     => null,
                'post_id'            => null,
                'avg_rating'         => null,
                'count'              => 0,
                'has_voted'          => false,
                'user_vote'          => 0,
            ];

            // Fusionner les arguments fournis avec les valeurs par défaut.
            $prepared_args = array_merge($template_defaults, $args);

            // Rendre chaque variable explicitement disponible pour le template.
            foreach ($template_defaults as $var_name => $default_value) {
                ${$var_name} = $prepared_args[$var_name];
            }

            // Permettre aux templates d'accéder directement au tableau complet si nécessaire.
            $args = $prepared_args;

            include $template_path;
        } else {
            // Afficher un message d'erreur seulement pour les administrateurs
            if (current_user_can('manage_options')) {
                echo '<div class="notice notice-error"><p>Template manquant : <code>' . esc_html($template_path) . '</code></p></div>';
            }
        }
        
        // Retourner le contenu capturé
        return ob_get_clean();
    }
}
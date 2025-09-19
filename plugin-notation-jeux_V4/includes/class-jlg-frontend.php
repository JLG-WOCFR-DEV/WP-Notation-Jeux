<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class JLG_Frontend {

    /**
     * Contiendra les erreurs de chargement des shortcodes pour affichage.
     * @var array
     */
    private static $shortcode_errors = [];

    public function __construct() {
        // On charge les shortcodes via le hook 'init' pour s'assurer que WordPress est prêt
        add_action('init', [$this, 'initialize_shortcodes']);
        
        add_action('wp_enqueue_scripts', [$this, 'enqueue_jlg_scripts']);
        add_action('wp_ajax_jlg_rate_post', [$this, 'handle_user_rating']);
        add_action('wp_ajax_nopriv_jlg_rate_post', [$this, 'handle_user_rating']);
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
     * Charge les scripts JavaScript nécessaires
     */
    public function enqueue_jlg_scripts() {
        $should_enqueue = is_singular('post');

        if (!$should_enqueue) {
            $queried_object = get_queried_object();

            if ($queried_object instanceof WP_Post && $this->content_has_plugin_shortcode($queried_object->post_content ?? '')) {
                $should_enqueue = true;
            }
        }

        if (!$should_enqueue) {
            return;
        }

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
            $token = '';

            if (isset($_COOKIE[$cookie_name])) {
                $cookie_token = sanitize_text_field(wp_unslash($_COOKIE[$cookie_name]));
                if (preg_match('/^[A-Fa-f0-9]{32,128}$/', $cookie_token)) {
                    $token = $cookie_token;
                }
            }

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
    }

    /**
     * Gère la notation AJAX des utilisateurs
     */
    public function handle_user_rating() {
        $cookie_name = 'jlg_user_rating_token';
        $token = '';

        if (isset($_POST['token'])) {
            $token = sanitize_text_field(wp_unslash($_POST['token']));
        }

        if ($token === '' && isset($_COOKIE[$cookie_name])) {
            $cookie_token = sanitize_text_field(wp_unslash($_COOKIE[$cookie_name]));
            if (preg_match('/^[A-Fa-f0-9]{32,128}$/', $cookie_token)) {
                $token = $cookie_token;
            }
        }

        if ($token === '' || !preg_match('/^[A-Fa-f0-9]{32,128}$/', $token)) {
            wp_send_json_error(['message' => 'Jeton de sécurité manquant ou invalide.'], 400);
        }

        if (!check_ajax_referer('jlg_user_rating_nonce_' . $token, 'nonce', false)) {
            wp_send_json_error(['message' => 'La vérification de sécurité a échoué.'], 403);
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;

        if (!$post_id || $rating < 1 || $rating > 5) {
            wp_send_json_error(['message' => 'Données invalides.']); 
        }
        
        $user_ip = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);
        if (!$user_ip) {
            wp_send_json_error(['message' => 'Adresse IP invalide.']);
        }

        $user_ip_hash = wp_hash($user_ip);

        $meta_key = '_jlg_user_ratings';
        $ratings = get_post_meta($post_id, $meta_key, true);

        if (!is_array($ratings)) {
            $ratings = [];
        }

        if (isset($ratings[$user_ip_hash])) {
            wp_send_json_error(['message' => 'Vous avez déjà voté !']);
        }

        $ratings[$user_ip_hash] = $rating;
        update_post_meta($post_id, $meta_key, $ratings);
        
        $new_average = round(array_sum($ratings) / count($ratings), 2);
        update_post_meta($post_id, '_jlg_user_rating_avg', $new_average);
        update_post_meta($post_id, '_jlg_user_rating_count', count($ratings));
        
        wp_send_json_success([
            'new_average' => number_format($new_average, 2), 
            'new_count' => count($ratings)
        ]);
    }

    /**
     * Injecte le schema de notation pour le SEO
     */
    public function inject_review_schema() {
        $options = get_option('notation_jlg_settings', JLG_Helpers::get_default_settings());
        
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
            'name'          => get_the_title($post_id),
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
        
        $user_rating_count = get_post_meta($post_id, '_jlg_user_rating_count', true);
        
        if (!empty($options['user_rating_enabled']) && $user_rating_count > 0) {
            $schema['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => get_post_meta($post_id, '_jlg_user_rating_avg', true),
                'ratingCount' => $user_rating_count,
                'bestRating'  => '5',
                'worstRating' => '1',
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
                'paged'              => 1,
                'orderby'            => '',
                'order'              => '',
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
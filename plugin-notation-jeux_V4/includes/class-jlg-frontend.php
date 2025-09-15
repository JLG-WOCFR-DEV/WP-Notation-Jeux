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
                echo '<li>' . $error . '</li>';
            }
            echo '</ul></div>';
        }
    }

    /**
     * Charge les scripts JavaScript nécessaires
     */
    public function enqueue_jlg_scripts() {
        if (!is_singular('post')) {
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

        $inline_css  = ':root{' .
            '--jlg-bg-color:' . $palette['bg_color'] . ';' .
            '--jlg-bg-color-secondary:' . $palette['bg_color_secondary'] . ';' .
            '--jlg-border-color:' . $palette['border_color'] . ';' .
            '--jlg-main-text-color:' . $palette['main_text_color'] . ';' .
            '--jlg-secondary-text-color:' . $palette['secondary_text_color'] . ';' .
            '--jlg-bar-bg-color:' . $palette['bar_bg_color'] . ';' .
            '--jlg-score-gradient-1:' . $options['score_gradient_1'] . ';' .
            '--jlg-score-gradient-2:' . $options['score_gradient_2'] . ';' .
            '--jlg-color-high:' . $options['color_high'] . ';' .
            '--jlg-color-low:' . $options['color_low'] . ';' .
            '--jlg-tagline-bg-color:' . $palette['tagline_bg_color'] . ';' .
            '--jlg-tagline-text-color:' . $palette['tagline_text_color'] . ';' .
            '--jlg-tagline-font-size:' . intval($options['tagline_font_size']) . 'px;' .
            '--jlg-user-rating-text-color:' . $options['user_rating_text_color'] . ';' .
            '--jlg-user-rating-star-color:' . $options['user_rating_star_color'] . ';' .
            '--jlg-table-header-bg-color:' . $options['table_header_bg_color'] . ';' .
            '--jlg-table-header-text-color:' . $options['table_header_text_color'] . ';' .
            '--jlg-table-row-bg-color:' . $options['table_row_bg_color'] . ';' .
            '--jlg-table-row-text-color:' . $options['table_row_text_color'] . ';' .
            '--jlg-table-row-hover-color:' . JLG_Helpers::adjust_hex_brightness($palette['bg_color_secondary'], 5) . ';' .
            '--jlg-table-link-color:' . JLG_Helpers::adjust_hex_brightness($options['table_row_text_color'], 20) . ';' .
            '--jlg-score-gradient-1-hover:' . JLG_Helpers::adjust_hex_brightness($options['score_gradient_1'], 20) . ';' .
            '--jlg-table-zebra-bg-color:' . $options['table_zebra_bg_color'] . ';' .
        '}';

        if (!empty($options['table_zebra_striping'])) {
            $inline_css .= '.jlg-summary-table tbody tr:nth-child(even){background-color:var(--jlg-table-zebra-bg-color);}';
            $inline_css .= '.jlg-summary-table tbody tr:nth-child(even):hover{background-color:' . JLG_Helpers::adjust_hex_brightness($options['table_zebra_bg_color'], 5) . ';}';
        }

        switch ($options['table_border_style']) {
            case 'horizontal':
                $inline_css .= '.jlg-summary-table th,.jlg-summary-table td{border-bottom:' . intval($options['table_border_width']) . 'px solid ' . $palette['border_color'] . ';}';
                break;
            case 'full':
                $inline_css .= '.jlg-summary-table th,.jlg-summary-table td{border:' . intval($options['table_border_width']) . 'px solid ' . $palette['border_color'] . ';}';
                break;
        }

        if ($options['score_layout'] === 'circle') {
            if (!empty($options['circle_dynamic_bg_enabled'])) {
                $dynamic_color = JLG_Helpers::calculate_color_from_note($average_score, $options);
                $darker_color = JLG_Helpers::adjust_hex_brightness($dynamic_color, -30);
            } else {
                $dynamic_color = $options['score_gradient_1'];
                $darker_color = $options['score_gradient_2'];
            }
            $inline_css .= '.review-box-jlg .score-circle{background-image:linear-gradient(135deg,' . $dynamic_color . ',' . $darker_color . ');';
            if (!empty($options['circle_border_enabled'])) {
                $inline_css .= 'border:' . intval($options['circle_border_width']) . 'px solid ' . $options['circle_border_color'] . ';';
            }
            $inline_css .= '}';
        }

        if ($options['score_layout'] === 'text') {
            $inline_css .= JLG_Helpers::get_glow_css('text', $average_score, $options);
        } elseif ($options['score_layout'] === 'circle') {
            $inline_css .= JLG_Helpers::get_glow_css('circle', $average_score, $options);
        }

        if (!empty($options['custom_css'])) {
            $inline_css .= wp_strip_all_tags($options['custom_css']);
        }

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
            wp_localize_script('jlg-user-rating', 'jlg_rating_ajax', [
                'ajax_url' => admin_url('admin-ajax.php'), 
                'nonce' => wp_create_nonce('jlg_user_rating_nonce')
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
        check_ajax_referer('jlg_user_rating_nonce', 'nonce');
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
        
        if (!$post_id || $rating < 1 || $rating > 5) { 
            wp_send_json_error(['message' => 'Données invalides.']); 
        }
        
        $user_ip = $_SERVER['REMOTE_ADDR'];
        $meta_key = '_jlg_user_ratings';
        $ratings = get_post_meta($post_id, $meta_key, true);
        
        if (!is_array($ratings)) { 
            $ratings = []; 
        }
        
        if (isset($ratings[$user_ip])) { 
            wp_send_json_error(['message' => 'Vous avez déjà voté !']); 
        }
        
        $ratings[$user_ip] = $rating;
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
        
        $schema = [
            '@context'      => 'https://schema.org',
            '@type'         => 'Game',
            'name'          => get_the_title($post_id),
            'review'        => [
                '@type'         => 'Review',
                'reviewRating'  => [
                    '@type'       => 'Rating',
                    'ratingValue' => $average_score,
                    'bestRating'  => '10',
                    'worstRating' => '1',
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
        
        // Extraire les variables du tableau pour les rendre disponibles dans le template
        extract($args);
        
        // Démarrer la capture de sortie
        ob_start();
        
        // Construire le chemin du template
        $template_path = JLG_NOTATION_PLUGIN_DIR . 'templates/' . $template_name . '.php';
        
        // Inclure le template s'il existe
        if (file_exists($template_path)) {
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
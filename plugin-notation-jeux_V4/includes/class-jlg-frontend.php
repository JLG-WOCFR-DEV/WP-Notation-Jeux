<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class JLG_Frontend {

    private const USER_RATING_MAX_STORED_VOTES = 250;
    private const USER_RATING_RETENTION_DAYS   = 180;

    /**
     * Contiendra les erreurs de chargement des shortcodes pour affichage.
     * @var array
     */
    private static $shortcode_errors = array();

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

    /**
     * Liste des shortcodes exécutés durant la requête courante.
     *
     * @var array<string, bool>
     */
    private static $rendered_shortcodes = array();

    /**
     * Mémoïsation locale de la détection de métadonnées utilisées par le plugin.
     *
     * @var array<int, bool>
     */
    private $metadata_usage_cache = array();

    public function __construct() {
        self::$instance = $this;
        // On charge les shortcodes via le hook 'init' pour s'assurer que WordPress est prêt
        add_action( 'init', array( $this, 'initialize_shortcodes' ) );

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_jlg_scripts' ) );
        add_filter( 'do_shortcode_tag', array( $this, 'track_shortcode_usage' ), 10, 4 );
        add_action( 'wp_ajax_jlg_rate_post', array( $this, 'handle_user_rating' ) );
        add_action( 'wp_ajax_nopriv_jlg_rate_post', array( $this, 'handle_user_rating' ) );
        add_action( 'wp_ajax_jlg_summary_sort', array( $this, 'handle_summary_sort' ) );
        add_action( 'wp_ajax_nopriv_jlg_summary_sort', array( $this, 'handle_summary_sort' ) );
        add_action( 'wp_ajax_jlg_game_explorer_sort', array( $this, 'handle_game_explorer_sort' ) );
        add_action( 'wp_ajax_nopriv_jlg_game_explorer_sort', array( $this, 'handle_game_explorer_sort' ) );
        add_action( 'wp_head', array( $this, 'inject_review_schema' ) );
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
        $shortcodes = array(
            'Rating_Block',
            'Pros_Cons',
            'Game_Info',
            'User_Rating',
            'Tagline',
            'Summary_Display',
            'All_In_One',  // NOUVEAU SHORTCODE AJOUTÉ ICI
            'Game_Explorer',
        );

        $errors = array();

        foreach ( $shortcodes as $shortcode ) {
            $class_name = 'JLG_Shortcode_' . $shortcode;
            $file_path  = JLG_NOTATION_PLUGIN_DIR . 'includes/shortcodes/class-jlg-shortcode-' . strtolower( str_replace( '_', '-', $shortcode ) ) . '.php';

            if ( file_exists( $file_path ) ) {
                require_once $file_path;
                if ( ! class_exists( $class_name ) ) {
                    $errors[] = "Le fichier existe <code>{$file_path}</code>, mais la classe <code>{$class_name}</code> n'a pas été trouvée à l'intérieur.";
                } else {
                    new $class_name();
                }
            } else {
                $errors[] = "Fichier de shortcode manquant : <code>{$file_path}</code>";
            }
        }

        // On vérifie les erreurs et on les affiche seulement pour les admins
        if ( ! empty( $errors ) ) {
            self::$shortcode_errors = $errors;
            // On diffère la vérification des capacités utilisateur
            add_action( 'admin_notices', array( $this, 'display_shortcode_errors' ) );
        }
    }

    /**
     * Affiche les erreurs de chargement des shortcodes dans l'admin
     */
    public function display_shortcode_errors() {
        // Maintenant on peut vérifier les capacités car WordPress est complètement chargé
        if ( ! empty( self::$shortcode_errors ) && current_user_can( 'manage_options' ) ) {
            echo '<div class="notice notice-error"><p><strong>Plugin Notation - JLG : Erreur de chargement des shortcodes !</strong></p><ul>';
            foreach ( self::$shortcode_errors as $error ) {
                printf( '<li>%s</li>', wp_kses_post( $error ) );
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
        return array(
            'bloc_notation_jeu',
            'jlg_points_forts_faibles',
            'jlg_fiche_technique',
            'tagline_notation_jlg',
            'jlg_tableau_recap',
            'notation_utilisateurs_jlg',
            'jlg_bloc_complet',
            'bloc_notation_complet',
            'jlg_game_explorer',
        );
    }

    /**
     * Marque l'utilisation d'un shortcode du plugin.
     */
    public static function mark_shortcode_rendered( $shortcode = null ) {
        if ( is_string( $shortcode ) && $shortcode !== '' ) {
            self::$rendered_shortcodes[ $shortcode ] = true;
        }

        self::$shortcode_rendered = true;

        if ( ! self::$assets_enqueued && did_action( 'wp_enqueue_scripts' ) && self::$instance instanceof self ) {
            self::$instance->enqueue_jlg_scripts( true );

            if ( did_action( 'wp_print_styles' ) && ! wp_style_is( 'jlg-frontend', 'done' ) ) {
                if ( ! self::$deferred_styles_hooked ) {
                    self::$deferred_styles_hooked = true;
                    add_action( 'wp_footer', array( self::$instance, 'print_deferred_styles' ) );
                }
            }
        }
    }

    /**
     * Indique si un shortcode précis a déjà été exécuté.
     *
     * @param string $shortcode
     * @return bool
     */
    public static function has_rendered_shortcode( $shortcode ) {
        if ( $shortcode === '' ) {
            return false;
        }

        return isset( self::$rendered_shortcodes[ $shortcode ] );
    }

    /**
     * Imprime la feuille de style frontend si elle n'a pas déjà été imprimée.
     */
    public function print_deferred_styles() {
        if ( ! wp_style_is( 'jlg-frontend', 'done' ) ) {
            wp_print_styles( 'jlg-frontend' );
        }

        if ( wp_style_is( 'jlg-frontend', 'done' ) ) {
            remove_action( 'wp_footer', array( self::$instance, 'print_deferred_styles' ) );
            self::$deferred_styles_hooked = false;
        }
    }

    /**
     * Détecte l'exécution des shortcodes du plugin lors de leur rendu.
     */
    public function track_shortcode_usage( $output, $tag, $attr, $m ) {
        if ( in_array( $tag, $this->get_plugin_shortcodes(), true ) ) {
            self::mark_shortcode_rendered( $tag );
        }

        return $output;
    }

    /**
     * Vérifie si un contenu contient l'un des shortcodes du plugin.
     *
     * @param string $content
     * @return bool
     */
    private function content_has_plugin_shortcode( $content ) {
        if ( ! is_string( $content ) || $content === '' ) {
            return false;
        }

        foreach ( $this->get_plugin_shortcodes() as $shortcode ) {
            if ( has_shortcode( $content, $shortcode ) ) {
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
    private function post_has_plugin_metadata( $post_id ) {
        $post_id = (int) $post_id;

        if ( $post_id <= 0 ) {
            return false;
        }

        if ( isset( $this->metadata_usage_cache[ $post_id ] ) ) {
            return $this->metadata_usage_cache[ $post_id ];
        }

        $meta_keys = array(
            '_jlg_average_score',
            '_jlg_game_title',
            '_jlg_cover_image_url',
            '_jlg_date_sortie',
            '_jlg_developpeur',
            '_jlg_editeur',
            '_jlg_plateformes',
            '_jlg_points_forts',
            '_jlg_points_faibles',
            '_jlg_tagline_fr',
            '_jlg_tagline_en',
            '_jlg_user_rating_avg',
        );

        foreach ( array_keys( JLG_Helpers::get_rating_categories() ) as $category_key ) {
            $meta_keys[] = '_note_' . $category_key;
        }

        $has_metadata = false;

        foreach ( $meta_keys as $meta_key ) {
            if ( ! metadata_exists( 'post', $post_id, $meta_key ) ) {
                continue;
            }

            $value = get_post_meta( $post_id, $meta_key, true );

            if ( $this->is_meta_value_filled( $value ) ) {
                $has_metadata = true;
                break;
            }
        }

        $this->metadata_usage_cache[ $post_id ] = $has_metadata;

        return $has_metadata;
    }

    /**
     * Détermine si une valeur de métadonnée contient des informations exploitables.
     *
     * @param mixed $value
     * @return bool
     */
    private function is_meta_value_filled( $value ) {
        if ( is_array( $value ) ) {
            foreach ( $value as $item ) {
                if ( $this->is_meta_value_filled( $item ) ) {
                    return true;
                }
            }

            return false;
        }

        if ( is_string( $value ) ) {
            return trim( $value ) !== '';
        }

        return $value !== null && $value !== '';
    }

    /**
     * Charge les scripts JavaScript nécessaires
     */
    public function enqueue_jlg_scripts( $force = false ) {
        if ( self::$assets_enqueued ) {
            return;
        }

        $summary_ajax       = $this->is_summary_sort_ajax_context();
        $game_explorer_ajax = $this->is_game_explorer_ajax_context();
        $should_enqueue     = $force || self::$shortcode_rendered;

        if ( ! $should_enqueue ) {
            $queried_object = get_queried_object();

            if ( $queried_object instanceof WP_Post ) {
                if ( $this->content_has_plugin_shortcode( $queried_object->post_content ?? '' ) ) {
                    $should_enqueue = true;
                } elseif ( $this->post_has_plugin_metadata( $queried_object->ID ) ) {
                    $should_enqueue = true;
                }
            }
        }

        if ( ! $should_enqueue ) {
            return;
        }

        self::$assets_enqueued    = true;
        self::$shortcode_rendered = true;

        $options       = JLG_Helpers::get_plugin_options();
        $palette       = JLG_Helpers::get_color_palette();
        $post_id       = get_queried_object_id();
        $average_score = JLG_Helpers::get_average_score_for_post( $post_id );

        // Feuille de styles principale
        wp_enqueue_style(
            'jlg-frontend',
            JLG_NOTATION_PLUGIN_URL . 'assets/css/jlg-frontend.css',
            array(),
            JLG_NOTATION_VERSION
        );

        $inline_css = JLG_Dynamic_CSS::build_frontend_css( $options, $palette, $average_score );

        wp_add_inline_style( 'jlg-frontend', $inline_css );

        if ( ! wp_style_is( 'jlg-game-explorer', 'registered' ) ) {
            wp_register_style(
                'jlg-game-explorer',
                JLG_NOTATION_PLUGIN_URL . 'assets/css/game-explorer.css',
                array( 'jlg-frontend' ),
                JLG_NOTATION_VERSION
            );
        }

        $queried_object = isset( $queried_object ) ? $queried_object : get_queried_object();
        $post_content   = '';

        if ( $queried_object instanceof WP_Post ) {
            $post_content = $queried_object->post_content ?? '';
        }

        $summary_shortcode_used = self::has_rendered_shortcode( 'jlg_tableau_recap' );
        if ( ! $summary_shortcode_used ) {
            $summary_shortcode_used = $this->content_has_specific_shortcode( $post_content, 'jlg_tableau_recap' );
        }

        $game_explorer_shortcode_used = self::has_rendered_shortcode( 'jlg_game_explorer' );
        if ( ! $game_explorer_shortcode_used ) {
            $game_explorer_shortcode_used = $this->content_has_specific_shortcode( $post_content, 'jlg_game_explorer' );
        }

        $should_enqueue_summary_script       = $summary_shortcode_used || $summary_ajax;
        $should_enqueue_game_explorer_script = $game_explorer_shortcode_used || $game_explorer_ajax;
        $should_enqueue_game_explorer_assets = $should_enqueue_game_explorer_script || $game_explorer_ajax;

        if ( $should_enqueue_game_explorer_assets ) {
            wp_enqueue_style( 'jlg-game-explorer' );

            if ( wp_style_is( 'jlg-game-explorer', 'enqueued' ) ) {
                $game_explorer_css = $this->build_game_explorer_css( $options, $palette );

                if ( ! empty( $game_explorer_css ) ) {
                    wp_add_inline_style( 'jlg-game-explorer', $game_explorer_css );
                }
            }
        }

        // Script pour la notation utilisateur
        if ( ! empty( $options['user_rating_enabled'] ) ) {
            wp_enqueue_script(
                'jlg-user-rating',
                JLG_NOTATION_PLUGIN_URL . 'assets/js/user-rating.js',
                array( 'jquery' ),
                JLG_NOTATION_VERSION,
                true
            );
            $cookie_name = 'jlg_user_rating_token';
            $token       = self::get_user_rating_token_from_cookie();

            if ( $token === '' ) {
                try {
                    $token = bin2hex( random_bytes( 32 ) );
                } catch ( Exception $e ) {
                    $token = md5( uniqid( '', true ) );
                }

                $cookie_options = array(
                    'expires'  => time() + MONTH_IN_SECONDS,
                    'path'     => defined( 'COOKIEPATH' ) ? COOKIEPATH : '/',
                    'secure'   => is_ssl(),
                    'httponly' => true,
                    'samesite' => 'Lax',
                );

                if ( defined( 'COOKIE_DOMAIN' ) && COOKIE_DOMAIN ) {
                    $cookie_options['domain'] = COOKIE_DOMAIN;
                }

                if ( ! headers_sent() ) {
                    setcookie( $cookie_name, $token, $cookie_options );
                }
                $_COOKIE[ $cookie_name ] = $token;
            }

            $nonce = wp_create_nonce( 'jlg_user_rating_nonce_' . $token );

            wp_localize_script(
                'jlg-user-rating',
                'jlg_rating_ajax',
                array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => $nonce,
					'token'    => $token,
				)
            );
        }

        // Script pour le changement de langue des taglines
        if ( ! empty( $options['tagline_enabled'] ) ) {
            wp_enqueue_script(
                'jlg-tagline-switcher',
                JLG_NOTATION_PLUGIN_URL . 'assets/js/tagline-switcher.js',
                array( 'jquery' ),
                JLG_NOTATION_VERSION,
                true
            );
        }

        // Script pour les animations
        if ( ! empty( $options['enable_animations'] ) ) {
            wp_enqueue_script(
                'jlg-animations',
                JLG_NOTATION_PLUGIN_URL . 'assets/js/jlg-animations.js',
                array(),
                JLG_NOTATION_VERSION,
                true
            );
        }

        if ( $should_enqueue_summary_script ) {
            if ( ! wp_script_is( 'jlg-summary-table-sort', 'registered' ) ) {
                wp_register_script(
                    'jlg-summary-table-sort',
                    JLG_NOTATION_PLUGIN_URL . 'assets/js/summary-table-sort.js',
                    array( 'jquery' ),
                    JLG_NOTATION_VERSION,
                    true
                );
            }

            wp_enqueue_script( 'jlg-summary-table-sort' );

            wp_localize_script(
                'jlg-summary-table-sort',
                'jlgSummarySort',
                array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'jlg_summary_sort' ),
					'strings'  => array(
						'genericError' => esc_html__( 'Une erreur est survenue. Merci de réessayer plus tard.', 'notation-jlg' ),
					),
				)
            );
        }

        if ( $should_enqueue_game_explorer_script && ! wp_script_is( 'jlg-game-explorer', 'enqueued' ) ) {
            if ( ! wp_script_is( 'jlg-game-explorer', 'registered' ) ) {
                wp_register_script(
                    'jlg-game-explorer',
                    JLG_NOTATION_PLUGIN_URL . 'assets/js/game-explorer.js',
                    array(),
                    JLG_NOTATION_VERSION,
                    true
                );
            }

            wp_enqueue_script( 'jlg-game-explorer' );
        }
    }

    /**
     * Vérifie si un contenu contient un shortcode spécifique.
     *
     * @param string $content
     * @param string $shortcode
     * @return bool
     */
    private function content_has_specific_shortcode( $content, $shortcode ) {
        if ( ! is_string( $content ) || $content === '' || $shortcode === '' ) {
            return false;
        }

        if ( ! function_exists( 'has_shortcode' ) ) {
            return false;
        }

        return has_shortcode( $content, $shortcode );
    }

    /**
     * Détermine si la requête courante correspond à l'AJAX de tri du tableau récapitulatif.
     *
     * @return bool
     */
    private function is_summary_sort_ajax_context() {
        $doing_ajax = function_exists( 'wp_doing_ajax' ) ? wp_doing_ajax() : ( defined( 'DOING_AJAX' ) && DOING_AJAX );

        return $doing_ajax && isset( $_REQUEST['action'] ) && $_REQUEST['action'] === 'jlg_summary_sort';
    }

    /**
     * Détermine si la requête courante correspond à l'AJAX de tri/filtrage du Game Explorer.
     *
     * @return bool
     */
    private function is_game_explorer_ajax_context() {
        $doing_ajax = function_exists( 'wp_doing_ajax' ) ? wp_doing_ajax() : ( defined( 'DOING_AJAX' ) && DOING_AJAX );

        return $doing_ajax && isset( $_REQUEST['action'] ) && $_REQUEST['action'] === 'jlg_game_explorer_sort';
    }

    private function build_game_explorer_css( $options, $palette ) {
        $card_bg          = $palette['bg_color_secondary'] ?? '#1f2937';
        $border           = $palette['border_color'] ?? '#3f3f46';
        $text             = $palette['text_color'] ?? '#fafafa';
        $secondary        = $palette['text_color_secondary'] ?? '#9ca3af';
        $accent_primary   = $options['score_gradient_1'] ?? '#60a5fa';
        $accent_secondary = $options['score_gradient_2'] ?? '#c084fc';

        $css = "
.jlg-game-explorer{--jlg-ge-card-bg: {$card_bg};--jlg-ge-card-border: {$border};--jlg-ge-text: {$text};--jlg-ge-text-muted: {$secondary};--jlg-ge-accent: {$accent_primary};--jlg-ge-accent-alt: {$accent_secondary};}
";

        return trim( $css );
    }

    /**
     * Récupère et valide l'adresse IP associée à la requête courante.
     *
     * @return string Adresse IP valide ou chaîne vide.
     */
    private function get_request_ip_address() {
        $ip_address = '';

        if ( function_exists( 'rest_get_ip_address' ) ) {
            $ip_address = rest_get_ip_address();
        } elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
            $ip_address = $_SERVER['REMOTE_ADDR'];
        }

        if ( ! is_string( $ip_address ) ) {
            $ip_address = '';
        }

        $ip_address = apply_filters( 'jlg_user_rating_request_ip', $ip_address );

        if ( ! is_string( $ip_address ) ) {
            $ip_address = '';
        }

        $ip_address = trim( $ip_address );
        $validated  = $ip_address !== '' ? filter_var( $ip_address, FILTER_VALIDATE_IP ) : false;

        return $validated ? $validated : '';
    }

    /**
     * Gère la notation AJAX des utilisateurs
     */
    public function handle_user_rating() {
        $cookie_name = 'jlg_user_rating_token';
        $token       = '';

        if ( isset( $_POST['token'] ) ) {
            $token = self::normalize_user_rating_token( wp_unslash( $_POST['token'] ) );
        }

        if ( $token === '' && isset( $_COOKIE[ $cookie_name ] ) ) {
            $token = self::normalize_user_rating_token( wp_unslash( $_COOKIE[ $cookie_name ] ) );
        }

        if ( $token === '' ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Jeton de sécurité manquant ou invalide.', 'notation-jlg' ) ), 400 );
        }

        if ( ! check_ajax_referer( 'jlg_user_rating_nonce_' . $token, 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'La vérification de sécurité a échoué.', 'notation-jlg' ) ), 403 );
        }

        $options = JLG_Helpers::get_plugin_options();

        if ( empty( $options['user_rating_enabled'] ) ) {
            wp_send_json_error(
                array(
					'message' => esc_html__( 'La notation des lecteurs est désactivée.', 'notation-jlg' ),
                ),
                403
            );
        }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

        if ( ! $post_id ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Données invalides.', 'notation-jlg' ) ), 400 );
        }

        $post = get_post( $post_id );

        if ( ! ( $post instanceof WP_Post ) || 'trash' === $post->post_status || 'publish' !== $post->post_status ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Article introuvable ou non disponible pour la notation.', 'notation-jlg' ) ), 404 );
        }

        $allows_user_rating = apply_filters(
            'jlg_post_allows_user_rating',
            $this->post_allows_user_rating( $post, $options ),
            $post
        );

        if ( ! $allows_user_rating ) {
            wp_send_json_error( array( 'message' => esc_html__( 'La notation des lecteurs est désactivée pour ce contenu.', 'notation-jlg' ) ), 403 );
        }

        $rating = isset( $_POST['rating'] ) ? intval( $_POST['rating'] ) : 0;

        if ( $rating < 1 || $rating > 5 ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Données invalides.', 'notation-jlg' ) ), 422 );
        }

        $user_ip      = $this->get_request_ip_address();
        $user_ip_hash = $user_ip ? self::hash_user_ip( $user_ip ) : '';
        $ip_log       = array();

        if ( $user_ip_hash !== '' ) {
            $stored_ip_log = get_post_meta( $post_id, '_jlg_user_rating_ips', true );

            if ( is_array( $stored_ip_log ) ) {
                $ip_log = $stored_ip_log;
            }

            if ( isset( $ip_log[ $user_ip_hash ] ) && ( ! is_array( $ip_log[ $user_ip_hash ] ) || empty( $ip_log[ $user_ip_hash ]['legacy'] ) ) ) {
                wp_send_json_error(
                    array(
						'message' => esc_html__( 'Un vote depuis cette adresse IP a déjà été enregistré.', 'notation-jlg' ),
                    ),
                    409
                );
            }
        }

        $token_hash = self::hash_user_rating_token( $token );

        $ratings_meta = array();
        $ratings      = self::get_post_user_rating_tokens( $post_id, $ratings_meta );

        if ( isset( $ratings[ $token_hash ] ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Vous avez déjà voté !', 'notation-jlg' ) ), 409 );
        }

        $ratings[ $token_hash ]                    = $rating;
        $ratings_meta['timestamps'][ $token_hash ] = current_time( 'timestamp' );

        self::store_post_user_rating_tokens( $post_id, $ratings, $ratings_meta );

        if ( $user_ip_hash ) {
            self::update_user_rating_ip_log( $post_id, $user_ip_hash, $token_hash, $rating );
        }

        $fresh_ratings                     = self::get_post_user_rating_tokens( $post_id );
        list($new_average, $ratings_count) = self::calculate_user_rating_stats( $fresh_ratings );

        update_post_meta( $post_id, '_jlg_user_rating_avg', $new_average );
        update_post_meta( $post_id, '_jlg_user_rating_count', $ratings_count );

        wp_send_json_success(
            array(
				'new_average' => number_format_i18n( $new_average, 2 ),
				'new_count'   => $ratings_count,
            )
        );
    }

    private static function normalize_user_rating_token( $token ) {
        if ( ! is_string( $token ) ) {
            return '';
        }

        $token = sanitize_text_field( $token );

        if ( ! preg_match( '/^[A-Fa-f0-9]{32,128}$/', $token ) ) {
            return '';
        }

        return $token;
    }

    public static function get_user_rating_token_from_cookie() {
        $cookie_name = 'jlg_user_rating_token';

        if ( ! isset( $_COOKIE[ $cookie_name ] ) ) {
            return '';
        }

        return self::normalize_user_rating_token( wp_unslash( $_COOKIE[ $cookie_name ] ) );
    }

    private static function hash_user_rating_token( $token ) {
        $hashed = hash( 'sha256', (string) $token );

        return is_string( $hashed ) ? $hashed : '';
    }

    private static function hash_user_ip( $ip_address ) {
        $context = apply_filters( 'jlg_user_rating_ip_hash_context', site_url() );
        $context = is_string( $context ) ? $context : '';

        $hashed = hash( 'sha256', $ip_address . '|' . $context );

        return is_string( $hashed ) ? $hashed : '';
    }

    private static function get_post_user_rating_tokens( $post_id, &$meta = null ) {
        $meta_key = '_jlg_user_ratings';
        $stored   = get_post_meta( $post_id, $meta_key, true );

        if ( ! is_array( $stored ) ) {
            $stored = array();
        }

        $meta_data = array();

        if ( isset( $stored['__meta'] ) && is_array( $stored['__meta'] ) ) {
            $meta_data = $stored['__meta'];
            unset( $stored['__meta'] );
        }

        $normalized = array();
        $now        = current_time( 'timestamp' );

        $needs_meta_update    = ! isset( $meta_data['version'] ) || (int) $meta_data['version'] < 2;
        $meta_data['version'] = 2;

        if ( ! isset( $meta_data['timestamps'] ) || ! is_array( $meta_data['timestamps'] ) ) {
            $meta_data['timestamps'] = array();
        }

        $meta_changed = $needs_meta_update;

        foreach ( $stored as $key => $value ) {
            if ( ! is_string( $key ) || ! preg_match( '/^[A-Fa-f0-9]{32,128}$/', $key ) ) {
                continue;
            }

            if ( ! is_numeric( $value ) ) {
                continue;
            }

            $normalized[ $key ] = (float) $value;

            if ( ! isset( $meta_data['timestamps'][ $key ] ) || ! is_numeric( $meta_data['timestamps'][ $key ] ) ) {
                $meta_data['timestamps'][ $key ] = $now;
                $meta_changed                    = true;
            }
        }

        foreach ( array_keys( $meta_data['timestamps'] ) as $hash ) {
            if ( ! isset( $normalized[ $hash ] ) ) {
                unset( $meta_data['timestamps'][ $hash ] );
                $meta_changed = true;
            }
        }

        if ( $meta !== null ) {
            $meta = $meta_data;
        }

        if ( $needs_meta_update ) {
            self::ensure_ip_log_for_legacy_votes( $post_id, $normalized );
        }

        if ( $meta_changed ) {
            self::write_user_rating_store( $post_id, $normalized, $meta_data, false );

            if ( $meta !== null ) {
                $meta = $meta_data;
            }
        }

        return $normalized;
    }

    private static function store_post_user_rating_tokens( $post_id, array $ratings, array $meta = array() ) {
        if ( ! isset( $meta['version'] ) || (int) $meta['version'] < 2 ) {
            $meta['version'] = 2;
        }

        if ( ! isset( $meta['timestamps'] ) || ! is_array( $meta['timestamps'] ) ) {
            $meta['timestamps'] = array();
        }

        self::write_user_rating_store( $post_id, $ratings, $meta, true );
    }

    private static function write_user_rating_store( $post_id, array $ratings, array $meta, $run_prune = true ) {
        if ( $run_prune ) {
            self::prune_user_rating_store( $post_id, $ratings, $meta );
        }

        $data           = $ratings;
        $data['__meta'] = $meta;

        update_post_meta( $post_id, '_jlg_user_ratings', $data );
    }

    private static function prune_user_rating_store( $post_id, array &$ratings, array &$meta ) {
        $timestamps     = isset( $meta['timestamps'] ) && is_array( $meta['timestamps'] ) ? $meta['timestamps'] : array();
        $now            = current_time( 'timestamp' );
        $retention      = self::get_user_rating_retention_window();
        $removed_tokens = array();

        foreach ( $timestamps as $hash => $timestamp ) {
            if ( ! isset( $ratings[ $hash ] ) ) {
                unset( $timestamps[ $hash ] );
                continue;
            }

            $timestamp = intval( $timestamp );

            if ( $timestamp <= 0 ) {
                $timestamps[ $hash ] = $now;
                continue;
            }

            if ( $retention > 0 && ( $now - $timestamp ) > $retention ) {
                unset( $ratings[ $hash ], $timestamps[ $hash ] );
                $removed_tokens[] = $hash;
            }
        }

        $max_entries = intval( apply_filters( 'jlg_user_rating_max_entries', self::USER_RATING_MAX_STORED_VOTES, $post_id ) );

        if ( $max_entries > 0 && count( $ratings ) > $max_entries ) {
            asort( $timestamps );

            foreach ( $timestamps as $hash => $timestamp ) {
                if ( ! isset( $ratings[ $hash ] ) ) {
                    continue;
                }

                if ( count( $ratings ) <= $max_entries ) {
                    break;
                }

                unset( $ratings[ $hash ], $timestamps[ $hash ] );
                $removed_tokens[] = $hash;
            }
        }

        $meta['timestamps'] = $timestamps;

        if ( ! empty( $removed_tokens ) ) {
            self::prune_user_rating_ip_tokens( $post_id, $removed_tokens, true );
        } else {
            self::prune_user_rating_ip_tokens( $post_id, array(), true );
        }
    }

    private static function get_user_rating_retention_window() {
        $days = intval( apply_filters( 'jlg_user_rating_retention_days', self::USER_RATING_RETENTION_DAYS ) );

        if ( $days <= 0 ) {
            return 0;
        }

        $day_in_seconds = defined( 'DAY_IN_SECONDS' ) ? DAY_IN_SECONDS : 86400;

        return $days * $day_in_seconds;
    }

    private static function ensure_ip_log_for_legacy_votes( $post_id, array $ratings ) {
        if ( empty( $ratings ) ) {
            return;
        }

        $ip_meta_key = '_jlg_user_rating_ips';
        $ip_log      = get_post_meta( $post_id, $ip_meta_key, true );

        if ( ! is_array( $ip_log ) ) {
            $ip_log = array();
        }

        $updated   = false;
        $timestamp = current_time( 'timestamp' );

        foreach ( $ratings as $hash => $value ) {
            if ( ! isset( $ip_log[ $hash ] ) || ! is_array( $ip_log[ $hash ] ) ) {
                $ip_log[ $hash ] = array(
                    'rating'    => (float) $value,
                    'votes'     => 1,
                    'last_vote' => $timestamp,
                    'legacy'    => true,
                );
                $updated         = true;
                continue;
            }

            $existing = $ip_log[ $hash ];

            if ( ! isset( $existing['rating'] ) ) {
                $existing['rating'] = (float) $value;
            }

            if ( ! isset( $existing['votes'] ) ) {
                $existing['votes'] = 1;
            }

            $existing['legacy']    = true;
            $existing['last_vote'] = isset( $existing['last_vote'] ) ? $existing['last_vote'] : $timestamp;

            $ip_log[ $hash ] = $existing;
            $updated         = true;
        }

        if ( $updated ) {
            update_post_meta( $post_id, $ip_meta_key, $ip_log );
        }
    }

    private static function update_user_rating_ip_log( $post_id, $ip_hash, $token_hash, $rating ) {
        $ip_meta_key = '_jlg_user_rating_ips';
        $ip_log      = get_post_meta( $post_id, $ip_meta_key, true );

        if ( ! is_array( $ip_log ) ) {
            $ip_log = array();
        }

        $timestamp = current_time( 'timestamp' );
        $entry     = isset( $ip_log[ $ip_hash ] ) && is_array( $ip_log[ $ip_hash ] ) ? $ip_log[ $ip_hash ] : array();

        $entry['rating']    = (float) $rating;
        $entry['token']     = $token_hash;
        $entry['last_vote'] = $timestamp;
        $entry['votes']     = isset( $entry['votes'] ) ? (int) $entry['votes'] + 1 : 1;

        unset( $entry['legacy'] );

        $ip_log[ $ip_hash ] = $entry;

        update_post_meta( $post_id, $ip_meta_key, $ip_log );
        self::prune_user_rating_ip_tokens( $post_id, array(), true );
    }

    private static function prune_user_rating_ip_tokens( $post_id, array $token_hashes = array(), $check_retention = true ) {
        $ip_meta_key = '_jlg_user_rating_ips';
        $ip_log      = get_post_meta( $post_id, $ip_meta_key, true );

        if ( ! is_array( $ip_log ) || empty( $ip_log ) ) {
            return;
        }

        $now       = current_time( 'timestamp' );
        $retention = $check_retention ? self::get_user_rating_retention_window() : 0;
        $threshold = ( $retention > 0 ) ? $now - $retention : null;
        $tokens    = array();

        foreach ( $token_hashes as $hash ) {
            $tokens[ $hash ] = true;
        }

        $updated = false;

        foreach ( $ip_log as $ip => $entry ) {
            if ( ! is_array( $entry ) ) {
                unset( $ip_log[ $ip ] );
                $updated = true;
                continue;
            }

            if ( ! empty( $tokens ) && isset( $entry['token'] ) && isset( $tokens[ $entry['token'] ] ) ) {
                unset( $ip_log[ $ip ] );
                $updated = true;
                continue;
            }

            if ( $threshold !== null && isset( $entry['last_vote'] ) ) {
                $last_vote = intval( $entry['last_vote'] );

                if ( $last_vote > 0 && $last_vote < $threshold ) {
                    unset( $ip_log[ $ip ] );
                    $updated = true;
                }
            }
        }

        if ( $updated ) {
            update_post_meta( $post_id, $ip_meta_key, $ip_log );
        }
    }

    private static function calculate_user_rating_stats( array $ratings ) {
        $values = array();

        foreach ( $ratings as $value ) {
            if ( is_numeric( $value ) ) {
                $values[] = (float) $value;
            }
        }

        $count = count( $values );

        if ( $count === 0 ) {
            return array( 0.0, 0 );
        }

        $average = round( array_sum( $values ) / $count, 2 );

        return array( $average, $count );
    }

    public static function get_user_vote_for_post( $post_id, $token = '' ) {
        $post_id = intval( $post_id );

        if ( $post_id <= 0 ) {
            return array( false, 0 );
        }

        if ( $token === '' ) {
            $token = self::get_user_rating_token_from_cookie();
        } else {
            $token = self::normalize_user_rating_token( $token );
        }

        if ( $token === '' ) {
            return array( false, 0 );
        }

        $token_hash = self::hash_user_rating_token( $token );
        $ratings    = self::get_post_user_rating_tokens( $post_id );

        if ( isset( $ratings[ $token_hash ] ) ) {
            return array( true, $ratings[ $token_hash ] );
        }

        return array( false, 0 );
    }

    /**
     * Détermine si un article est éligible aux votes des lecteurs.
     */
    private function post_allows_user_rating( $post, $options = null ) {
        if ( ! ( $post instanceof WP_Post ) ) {
            return false;
        }

        $allowed_post_types = JLG_Helpers::get_allowed_post_types();

        if ( ! in_array( $post->post_type, $allowed_post_types, true ) ) {
            return false;
        }

        if ( ! is_array( $options ) ) {
            $options = JLG_Helpers::get_plugin_options();
        }

        if ( empty( $options['user_rating_enabled'] ) ) {
            return false;
        }

        $content = $post->post_content ?? '';

        foreach ( array( 'notation_utilisateurs_jlg', 'jlg_bloc_complet', 'bloc_notation_complet' ) as $shortcode ) {
            if ( has_shortcode( $content, $shortcode ) || self::has_rendered_shortcode( $shortcode ) ) {
                return true;
            }
        }

        return false;
    }

    public function handle_summary_sort() {
        if ( ! check_ajax_referer( 'jlg_summary_sort', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'La vérification de sécurité a échoué.', 'notation-jlg' ) ), 403 );
        }

        if ( ! class_exists( 'JLG_Shortcode_Summary_Display' ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Le shortcode requis est indisponible.', 'notation-jlg' ) ), 500 );
        }

        $default_atts           = JLG_Shortcode_Summary_Display::get_default_atts();
        $default_posts_per_page = isset( $default_atts['posts_per_page'] ) ? intval( $default_atts['posts_per_page'] ) : 12;
        if ( $default_posts_per_page < 1 ) {
            $default_posts_per_page = 1;
        }

        $posts_per_page_input = isset( $_POST['posts_per_page'] ) ? wp_unslash( $_POST['posts_per_page'] ) : null;
        $posts_per_page       = null;

        if ( $posts_per_page_input !== null && ! is_array( $posts_per_page_input ) ) {
            $posts_per_page = intval( $posts_per_page_input );
        }

        if ( $posts_per_page === null || $posts_per_page < 1 ) {
            $posts_per_page = $default_posts_per_page;
        }

        $posts_per_page = max( 1, min( $posts_per_page, 50 ) );

        $atts = array(
            'posts_per_page' => $posts_per_page,
            'layout'         => isset( $_POST['layout'] ) ? sanitize_text_field( wp_unslash( $_POST['layout'] ) ) : 'table',
            'categorie'      => isset( $_POST['categorie'] ) ? sanitize_text_field( wp_unslash( $_POST['categorie'] ) ) : '',
            'colonnes'       => isset( $_POST['colonnes'] ) ? sanitize_text_field( wp_unslash( $_POST['colonnes'] ) ) : 'titre,date,note',
            'id'             => isset( $_POST['table_id'] ) ? sanitize_html_class( wp_unslash( $_POST['table_id'] ) ) : 'jlg-table-' . uniqid(),
            'letter_filter'  => '',
            'genre_filter'   => '',
        );

        $raw_request = isset( $_POST ) ? wp_unslash( $_POST ) : array();
        if ( ! is_array( $raw_request ) ) {
            $raw_request = array();
        }

        $request_prefix = sanitize_title( $atts['id'] );
        $letter_keys    = array();
        $genre_keys     = array();

        if ( $request_prefix !== '' ) {
            $letter_keys[] = 'letter_filter__' . $request_prefix;
            $genre_keys[]  = 'genre_filter__' . $request_prefix;
        }

        $letter_keys[] = 'letter_filter';
        $genre_keys[]  = 'genre_filter';

        foreach ( $letter_keys as $key ) {
            if ( isset( $raw_request[ $key ] ) && ! is_array( $raw_request[ $key ] ) ) {
                $atts['letter_filter'] = JLG_Shortcode_Summary_Display::normalize_letter_filter( $raw_request[ $key ] );
                break;
            }
        }

        foreach ( $genre_keys as $key ) {
            if ( isset( $raw_request[ $key ] ) && ! is_array( $raw_request[ $key ] ) ) {
                $atts['genre_filter'] = sanitize_text_field( $raw_request[ $key ] );
                break;
            }
        }

        $current_url = isset( $_POST['current_url'] ) ? wp_unslash( $_POST['current_url'] ) : '';
        $base_url    = $this->sanitize_internal_url( $current_url );

        if ( $base_url === '' ) {
            $base_url = $this->sanitize_internal_url( wp_get_referer() );
        }

        $context             = JLG_Shortcode_Summary_Display::get_render_context( $atts, $raw_request, false );
        $context['base_url'] = $base_url;

        $state = array(
            'orderby'       => $context['orderby'] ?? 'date',
            'order'         => $context['order'] ?? 'DESC',
            'paged'         => $context['paged'] ?? 1,
            'cat_filter'    => $context['cat_filter'] ?? 0,
            'letter_filter' => $context['letter_filter'] ?? '',
            'genre_filter'  => $context['genre_filter'] ?? '',
            'total_pages'   => 0,
        );

        if ( ! empty( $context['error'] ) && ! empty( $context['message'] ) ) {
            wp_send_json_success(
                array(
					'html'  => $context['message'],
					'state' => $state,
                )
            );
        }

        $html = self::get_template_html( 'summary-table-fragment', $context );

        if ( isset( $context['query'] ) && $context['query'] instanceof WP_Query ) {
            $state['total_pages'] = intval( $context['query']->max_num_pages );
        }

        wp_send_json_success(
            array(
				'html'  => $html,
				'state' => $state,
            )
        );
    }

    public function handle_game_explorer_sort() {
        if ( ! check_ajax_referer( 'jlg_game_explorer', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'La vérification de sécurité a échoué.', 'notation-jlg' ) ), 403 );
        }

        if ( ! class_exists( 'JLG_Shortcode_Game_Explorer' ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Le shortcode requis est indisponible.', 'notation-jlg' ) ), 500 );
        }

        $default_atts = JLG_Shortcode_Game_Explorer::get_default_atts();

        $atts = array(
            'id'             => isset( $_POST['container_id'] ) ? sanitize_html_class( wp_unslash( $_POST['container_id'] ) ) : ( $default_atts['id'] ?? 'jlg-game-explorer-' . uniqid() ),
            'posts_per_page' => isset( $_POST['posts_per_page'] ) ? intval( wp_unslash( $_POST['posts_per_page'] ) ) : ( $default_atts['posts_per_page'] ?? 12 ),
            'columns'        => isset( $_POST['columns'] ) ? intval( wp_unslash( $_POST['columns'] ) ) : ( $default_atts['columns'] ?? 3 ),
            'filters'        => isset( $_POST['filters'] ) ? sanitize_text_field( wp_unslash( $_POST['filters'] ) ) : ( $default_atts['filters'] ?? '' ),
            'score_position' => isset( $_POST['score_position'] )
                ? JLG_Helpers::normalize_game_explorer_score_position( wp_unslash( $_POST['score_position'] ) )
                : ( $default_atts['score_position'] ?? '' ),
            'categorie'      => isset( $_POST['categorie'] ) ? sanitize_text_field( wp_unslash( $_POST['categorie'] ) ) : ( $default_atts['categorie'] ?? '' ),
            'plateforme'     => isset( $_POST['plateforme'] ) ? sanitize_text_field( wp_unslash( $_POST['plateforme'] ) ) : ( $default_atts['plateforme'] ?? '' ),
            'lettre'         => isset( $_POST['lettre'] ) ? sanitize_text_field( wp_unslash( $_POST['lettre'] ) ) : ( $default_atts['lettre'] ?? '' ),
        );

        if ( $atts['posts_per_page'] < 1 ) {
            $atts['posts_per_page'] = $default_atts['posts_per_page'] ?? 12;
        }

        if ( $atts['columns'] < 1 ) {
            $atts['columns'] = $default_atts['columns'] ?? 3;
        }

        $raw_request = isset( $_POST ) ? wp_unslash( $_POST ) : array();
        if ( ! is_array( $raw_request ) ) {
            $raw_request = array();
        }

        $context = JLG_Shortcode_Game_Explorer::get_render_context( $atts, $raw_request );

        $state = array(
            'orderby'      => $context['sort_key'] ?? 'date',
            'order'        => $context['sort_order'] ?? 'DESC',
            'letter'       => $context['current_filters']['letter'] ?? '',
            'category'     => $context['current_filters']['category'] ?? '',
            'platform'     => $context['current_filters']['platform'] ?? '',
            'availability' => $context['current_filters']['availability'] ?? '',
            'search'       => $context['current_filters']['search'] ?? '',
            'paged'        => $context['pagination']['current'] ?? 1,
            'total_pages'  => $context['pagination']['total'] ?? 0,
            'total_items'  => $context['total_items'] ?? 0,
        );

        if ( ! empty( $context['error'] ) && ! empty( $context['message'] ) ) {
            wp_send_json_success(
                array(
                    'html'  => $context['message'],
                    'state' => $state,
                    'config' => $context['config_payload'] ?? array(),
                )
            );
        }

        $html = self::get_template_html( 'game-explorer-fragment', $context );

        wp_send_json_success(
            array(
                'html'   => $html,
                'state'  => $state,
                'config' => $context['config_payload'] ?? array(),
            )
        );
    }

    private function sanitize_internal_url( $url ) {
        $canonical_home = home_url( '/' );

        if ( ! is_string( $url ) ) {
            return $canonical_home;
        }

        $url = trim( $url );
        if ( $url === '' ) {
            return $canonical_home;
        }

        $sanitized_url = esc_url_raw( $url );
        if ( $sanitized_url === '' ) {
            return $canonical_home;
        }

        $parsed_url = wp_parse_url( $sanitized_url );
        if ( $parsed_url === false ) {
            return $canonical_home;
        }

        if ( ! empty( $parsed_url['scheme'] ) && ! in_array( $parsed_url['scheme'], array( 'http', 'https' ), true ) ) {
            return $canonical_home;
        }

        $site_url    = wp_parse_url( $canonical_home );
        $site_host   = is_array( $site_url ) && isset( $site_url['host'] ) ? strtolower( $site_url['host'] ) : '';
        $site_scheme = is_array( $site_url ) && isset( $site_url['scheme'] ) ? $site_url['scheme'] : '';
        $site_port   = is_array( $site_url ) && isset( $site_url['port'] ) ? intval( $site_url['port'] ) : null;

        $normalize_host = static function ( $host ) {
            $host = strtolower( (string) $host );
            if ( $host === '' ) {
                return '';
            }

            return preg_replace( '/^www\./', '', $host );
        };

        $target_host = isset( $parsed_url['host'] ) ? strtolower( $parsed_url['host'] ) : '';

        if ( $target_host === '' ) {
            $target_host = $site_host;
        }

        $normalized_site_host   = $normalize_host( $site_host );
        $normalized_target_host = $normalize_host( $target_host );

        if ( $normalized_site_host === '' || $normalized_target_host === '' ) {
            return $canonical_home;
        }

        if ( $normalized_target_host !== $normalized_site_host ) {
            return $canonical_home;
        }

        $scheme = $site_scheme !== '' ? $site_scheme : ( $parsed_url['scheme'] ?? '' );
        if ( $scheme === '' && isset( $parsed_url['scheme'] ) ) {
            $scheme = $parsed_url['scheme'];
        }

        $path = $parsed_url['path'] ?? '';
        if ( $path === '' ) {
            $path = '/';
        } else {
            $path = '/' . ltrim( $path, '/' );
            $path = preg_replace( '#/+#', '/', $path );
        }

        $normalized_url = '';

        if ( $scheme !== '' ) {
            $normalized_url .= $scheme . '://';
        }

        $normalized_url .= $site_host;

        $target_port = null;
        if ( isset( $parsed_url['port'] ) ) {
            $target_port = intval( $parsed_url['port'] );
        } elseif ( $site_port !== null ) {
            $target_port = $site_port;
        }

        if ( $target_port !== null ) {
            $normalized_url .= ':' . $target_port;
        }

        $normalized_url .= $path;

        if ( ! empty( $parsed_url['query'] ) ) {
            $normalized_url .= '?' . $parsed_url['query'];
        }

        if ( ! empty( $parsed_url['fragment'] ) ) {
            $normalized_url .= '#' . $parsed_url['fragment'];
        }

        return $normalized_url;
    }

    /**
     * Injecte le schema de notation pour le SEO
     */
    public function inject_review_schema() {
        $options = JLG_Helpers::get_plugin_options();

        $allowed_post_types = array_filter(
            JLG_Helpers::get_allowed_post_types(),
            static function ( $post_type ) {
                if ( ! is_string( $post_type ) || $post_type === '' ) {
                    return false;
                }

                if ( function_exists( 'post_type_exists' ) ) {
                    return post_type_exists( $post_type );
                }

                return true;
            }
        );

        if ( empty( $allowed_post_types ) ) {
            $allowed_post_types = array( 'post' );
        }

        if ( empty( $options['seo_schema_enabled'] ) || ! is_singular( $allowed_post_types ) ) {
            return;
        }

        $post_id       = get_the_ID();
        $score_data    = JLG_Helpers::get_resolved_average_score( $post_id );
        $average_score = is_array( $score_data ) ? ( $score_data['value'] ?? null ) : null;

        if ( $average_score === null ) {
            return;
        }

        $review_rating_bounds = apply_filters(
            'jlg_review_rating_bounds',
            array(
                'min' => 0,
                'max' => 10,
            ),
            $post_id
        );

        $review_best_rating  = isset( $review_rating_bounds['max'] ) ? floatval( $review_rating_bounds['max'] ) : 10;
        $review_worst_rating = isset( $review_rating_bounds['min'] ) ? floatval( $review_rating_bounds['min'] ) : 0;

        $schema = array(
            '@context' => 'https://schema.org',
            '@type'    => 'Game',
            'name'     => JLG_Helpers::get_game_title( $post_id ),
            'review'   => array(
                '@type'         => 'Review',
                'reviewRating'  => array(
                    '@type'       => 'Rating',
                    'ratingValue' => $average_score,
                    'bestRating'  => $review_best_rating,
                    'worstRating' => $review_worst_rating,
                ),
                'author'        => array(
                    '@type' => 'Person',
                    'name'  => get_the_author_meta( 'display_name', get_post_field( 'post_author', $post_id ) ),
                ),
                'datePublished' => get_the_date( 'c', $post_id ),
            ),
        );

        $user_rating_count = (int) get_post_meta( $post_id, '_jlg_user_rating_count', true );

        $user_rating_enabled = isset( $options['user_rating_enabled'] )
            ? $options['user_rating_enabled']
            : ( JLG_Helpers::get_default_settings()['user_rating_enabled'] ?? 0 );

        if ( ! empty( $user_rating_enabled ) && $user_rating_count > 0 ) {
            $aggregate_rating_value = (float) get_post_meta( $post_id, '_jlg_user_rating_avg', true );

            $user_rating_bounds = apply_filters(
                'jlg_user_rating_bounds',
                array(
                    'min' => 1,
                    'max' => 5,
                ),
                $post_id
            );

            $aggregate_best_rating  = isset( $user_rating_bounds['max'] ) ? floatval( $user_rating_bounds['max'] ) : 5.0;
            $aggregate_worst_rating = isset( $user_rating_bounds['min'] ) ? floatval( $user_rating_bounds['min'] ) : 1.0;

            $schema['aggregateRating'] = array(
                '@type'       => 'AggregateRating',
                'ratingValue' => $aggregate_rating_value,
                'ratingCount' => $user_rating_count,
                'bestRating'  => $aggregate_best_rating,
                'worstRating' => $aggregate_worst_rating,
            );
        }

        echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';
    }

    /**
     * Charge un fichier template en lui passant des variables.
     *
     * Cherche en priorité une surcharge dans le thème actif (`notation-jlg/{template}.php`) avant de
     * revenir au template fourni par le plugin. Deux filtres (`jlg_frontend_template_candidates` et
     * `jlg_frontend_template_path`) permettent aux intégrateurs d'ajuster le chemin utilisé.
     *
     * @param string $template_name Le nom du fichier template.
     * @param array $args Les variables à passer au template.
     * @return string Le contenu HTML du template.
     */
    public static function get_template_html( $template_name, $args = array() ) {
        // S'assurer que $args est bien un tableau
        if ( ! is_array( $args ) ) {
            $args = array();
        }

        // Construire le chemin du template du plugin et rechercher une éventuelle surcharge dans le thème.
        $plugin_template_path = JLG_NOTATION_PLUGIN_DIR . 'templates/' . $template_name . '.php';

        $template_candidates = array(
            'notation-jlg/' . $template_name . '.php',
        );

        /**
         * Permet d'ajouter ou de modifier les chemins de surcharge cherchés dans le thème.
         *
         * @param string[] $template_candidates  Liste de chemins passés à locate_template().
         * @param string   $template_name        Nom du template demandé par le plugin.
         * @param array    $args                 Arguments transmis au rendu du template.
         */
        $template_candidates = apply_filters( 'jlg_frontend_template_candidates', $template_candidates, $template_name, $args );

        if ( ! is_array( $template_candidates ) ) {
            $template_candidates = (array) $template_candidates;
        }

        $template_candidates = array_values( array_filter( array_map( 'strval', $template_candidates ) ) );

        $located_template = '';

        if ( ! empty( $template_candidates ) && function_exists( 'locate_template' ) ) {
            $located_template = locate_template( $template_candidates );
        }

        $template_path = $located_template !== '' ? $located_template : $plugin_template_path;

        /**
         * Filtre le chemin final du template à inclure.
         *
         * @param string $template_path        Chemin absolu du template retenu.
         * @param string $template_name        Nom du template demandé par le plugin.
         * @param array  $args                 Arguments transmis au rendu du template.
         * @param string $located_template     Chemin localisé via locate_template() si une surcharge a été trouvée.
         * @param string $plugin_template_path Chemin par défaut fourni par le plugin.
         */
        $template_path = (string) apply_filters( 'jlg_frontend_template_path', $template_path, $template_name, $args, $located_template, $plugin_template_path );

        // Démarrer la capture de sortie
        ob_start();

        // Inclure le template s'il existe
        if ( file_exists( $template_path ) ) {
            // Valeurs par défaut pour les variables utilisées par les templates existants.
            $template_defaults = array(
                'options'              => array(),
                'average_score'        => null,
                'scores'               => array(),
                'categories'           => array(),
                'pros_list'            => array(),
                'cons_list'            => array(),
                'titre'                => '',
                'champs_a_afficher'    => array(),
                'tagline_fr'           => '',
                'tagline_en'           => '',
                'query'                => null,
                'atts'                 => array(),
                'block_classes'        => '',
                'css_variables'        => '',
                'score_layout'         => 'text',
                'animations_enabled'   => false,
                'paged'                => 1,
                'orderby'              => '',
                'order'                => '',
                'colonnes'             => array(),
                'colonnes_disponibles' => array(),
                'error_message'        => '',
                'cat_filter'           => 0,
                'table_id'             => '',
                'widget_args'          => array(),
                'title'                => '',
                'latest_reviews'       => null,
                'post_id'              => null,
                'avg_rating'           => null,
                'count'                => 0,
                'has_voted'            => false,
                'user_vote'            => 0,
                'games'                => array(),
                'letters'              => array(),
                'filters'              => array(),
                'current_filters'      => array(),
                'pagination'           => array(
					'current' => 1,
					'total'   => 0,
				),
                'sort_options'         => array(),
                'sort_key'             => 'date',
                'sort_order'           => 'DESC',
                'filters_enabled'      => array(),
                'categories_list'      => array(),
                'platforms_list'       => array(),
                'availability_options' => array(),
                'base_url'             => '',
                'request_prefix'       => '',
                'request_keys'         => array(),
                'total_items'          => 0,
                'config_payload'       => array(),
                'message'              => '',
                'score_position'       => JLG_Helpers::normalize_game_explorer_score_position( '' ),
            );

            // Fusionner les arguments fournis avec les valeurs par défaut.
            $prepared_args = array_merge( $template_defaults, $args );

            // Rendre chaque variable explicitement disponible pour le template.
            foreach ( $template_defaults as $var_name => $default_value ) {
                ${$var_name} = $prepared_args[ $var_name ];
            }

            // Permettre aux templates d'accéder directement au tableau complet si nécessaire.
            $args = $prepared_args;

            include $template_path;
        } else {
            // Afficher un message d'erreur seulement pour les administrateurs
            if ( current_user_can( 'manage_options' ) ) {
                echo '<div class="notice notice-error"><p>Template manquant : <code>' . esc_html( $template_path ) . '</code></p></div>';
            }
        }

        // Retourner le contenu capturé
        return ob_get_clean();
    }
}

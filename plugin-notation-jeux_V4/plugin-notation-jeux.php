<?php
/**
 * Plugin Name: Notation - JLG (Version 5.0)
 * Plugin URI: https://votresite.com/
 * Description: Système de notation complet et personnalisable pour les tests de jeux vidéo - Version refactorisée et optimisée.
 * Version: 5.0
 * Author: Jérôme Le Gousse
 * License: GPL v2 or later
 * Text Domain: notation-jlg
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) exit;

// Constantes
define('JLG_NOTATION_VERSION', '5.0');
define('JLG_NOTATION_PLUGIN_FILE', __FILE__);
define('JLG_NOTATION_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('JLG_NOTATION_PLUGIN_URL', plugin_dir_url(__FILE__));
define('JLG_NOTATION_PLUGIN_BASENAME', plugin_basename(__FILE__));

add_action('plugins_loaded', function() {
    load_plugin_textdomain('notation-jlg', false, dirname(JLG_NOTATION_PLUGIN_BASENAME) . '/languages');
});

// Vérifications de compatibilité
if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p><strong>JLG Notation:</strong> PHP 7.4+ requis. Version actuelle: ' . esc_html(PHP_VERSION) . '</p></div>';
    });
    return;
}

if (version_compare(get_bloginfo('version'), '5.0', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p><strong>JLG Notation:</strong> WordPress 5.0+ requis. Version actuelle: ' . esc_html(get_bloginfo('version')) . '</p></div>';
    });
    return;
}

/**
 * Classe principale refactorisée
 */
final class JLG_Plugin_De_Notation_Main {
    private static $instance = null;
    private $admin = null;
    private $assets = null;
    private $frontend = null;
    private const MIGRATION_BATCH_SIZE = 50;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_components();
        add_action('jlg_process_v5_migration', [$this, 'process_migration_batch']);
        add_action('jlg_queue_average_rebuild', [$this, 'queue_additional_posts_for_migration']);
        add_action('init', [$this, 'ensure_migration_schedule']);
        register_activation_hook(__FILE__, [$this, 'on_activation']);
        register_deactivation_hook(__FILE__, [$this, 'on_deactivation']);
    }

    private function load_dependencies() {
        // Helpers (requis par tous)
        require_once JLG_NOTATION_PLUGIN_DIR . 'includes/class-jlg-helpers.php';
        require_once JLG_NOTATION_PLUGIN_DIR . 'includes/class-jlg-assets.php';
        require_once JLG_NOTATION_PLUGIN_DIR . 'functions.php';

        add_action('update_option_notation_jlg_settings', ['JLG_Helpers', 'flush_plugin_options_cache'], 10, 0);
        add_action('add_option_notation_jlg_settings', ['JLG_Helpers', 'flush_plugin_options_cache'], 10, 0);
        add_action('delete_option_notation_jlg_settings', ['JLG_Helpers', 'flush_plugin_options_cache'], 10, 0);

        // Frontend (toujours)
        require_once JLG_NOTATION_PLUGIN_DIR . 'includes/class-jlg-dynamic-css.php';
        require_once JLG_NOTATION_PLUGIN_DIR . 'includes/class-jlg-frontend.php';
        require_once JLG_NOTATION_PLUGIN_DIR . 'includes/class-jlg-widget.php';
        
        // Admin seulement si nécessaire
        if (is_admin()) {
            $this->load_admin_classes();
        }
    }

    private function load_admin_classes() {
        // Utilitaires admin
        $utils_files = [
            'includes/utils/class-jlg-form-renderer.php',
            'includes/utils/class-jlg-template-loader.php',
            'includes/utils/class-jlg-validator.php'
        ];

        // Classes admin
        $admin_files = [
            'includes/admin/class-jlg-admin-core.php'
        ];

        foreach (array_merge($utils_files, $admin_files) as $file) {
            $path = JLG_NOTATION_PLUGIN_DIR . $file;
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }

    private function init_components() {
        // Assets communs
        if (class_exists('JLG_Assets')) {
            $this->assets = JLG_Assets::get_instance();
        }

        // Frontend
        $doing_ajax = function_exists('wp_doing_ajax') ? wp_doing_ajax() : (defined('DOING_AJAX') && DOING_AJAX);
        if ((!is_admin() || $doing_ajax) && class_exists('JLG_Frontend')) {
            $this->frontend = new JLG_Frontend();
        }

        // Widget
        if (class_exists('JLG_Latest_Reviews_Widget')) {
            add_action('widgets_init', function() {
                register_widget('JLG_Latest_Reviews_Widget');
            });
        }

        // Admin
        if (is_admin() && class_exists('JLG_Admin_Core')) {
            $this->admin = JLG_Admin_Core::get_instance();
        }
    }

    public function on_activation() {
        // Migration automatique depuis v4
        $current_version = get_option('jlg_notation_version', '4.0');

        if (version_compare($current_version, '5.0', '<')) {
            $this->queue_migration_from_v4();
        }

        // Options par défaut
        if (!get_option('notation_jlg_settings')) {
            add_option('notation_jlg_settings', JLG_Helpers::get_default_settings());
        }

        update_option('jlg_notation_version', JLG_NOTATION_VERSION);
        flush_rewrite_rules();
    }

    public function on_deactivation() {
        if (function_exists('wp_clear_scheduled_hook')) {
            wp_clear_scheduled_hook('jlg_process_v5_migration');
        }

        delete_option('jlg_migration_v5_queue');
        delete_option('jlg_migration_v5_completed');
    }

    private function queue_migration_from_v4() {
        $rated_post_ids = array_filter(
            array_map('intval', JLG_Helpers::get_rated_post_ids() ?? []),
            static function ($post_id) {
                return $post_id > 0;
            }
        );

        if (empty($rated_post_ids)) {
            update_option('jlg_migration_v5_completed', current_time('mysql'));
            delete_option('jlg_migration_v5_queue');

            return;
        }

        update_option('jlg_migration_v5_queue', array_values($rated_post_ids));
        $this->schedule_next_migration_event();
    }

    public function ensure_migration_schedule() {
        $queue = get_option('jlg_migration_v5_queue');

        if (empty($queue) || !is_array($queue)) {
            return;
        }

        if (!function_exists('wp_next_scheduled') || !function_exists('wp_schedule_single_event')) {
            return;
        }

        if (!wp_next_scheduled('jlg_process_v5_migration')) {
            $this->schedule_next_migration_event();
        }
    }

    public function process_migration_batch() {
        $queue = get_option('jlg_migration_v5_queue', []);

        if (!is_array($queue) || empty($queue)) {
            delete_option('jlg_migration_v5_queue');
            update_option('jlg_migration_v5_completed', current_time('mysql'));

            return;
        }

        $batch = array_splice($queue, 0, self::MIGRATION_BATCH_SIZE);

        foreach ($batch as $post_id) {
            $post_id = intval($post_id);

            if ($post_id > 0) {
                JLG_Helpers::get_resolved_average_score($post_id);
            }
        }

        if (empty($queue)) {
            delete_option('jlg_migration_v5_queue');
            update_option('jlg_migration_v5_completed', current_time('mysql'));
        } else {
            update_option('jlg_migration_v5_queue', array_values($queue));
            $this->schedule_next_migration_event();
        }
    }

    private function schedule_next_migration_event() {
        if (!function_exists('wp_schedule_single_event') || !function_exists('wp_next_scheduled')) {
            return;
        }

        if (wp_next_scheduled('jlg_process_v5_migration')) {
            return;
        }

        $delay = defined('MINUTE_IN_SECONDS') ? MINUTE_IN_SECONDS : 60;
        wp_schedule_single_event(time() + $delay, 'jlg_process_v5_migration');
    }

    public function queue_additional_posts_for_migration($post_ids) {
        if (!is_array($post_ids)) {
            $post_ids = [$post_ids];
        }

        $post_ids = array_filter(
            array_map('intval', $post_ids),
            static function ($post_id) {
                return $post_id > 0;
            }
        );

        if (empty($post_ids)) {
            return;
        }

        $queue = get_option('jlg_migration_v5_queue', []);

        if (!is_array($queue)) {
            $queue = [];
        }

        $queue = array_map('intval', $queue);
        $updated_queue = array_values(array_unique(array_merge($queue, $post_ids)));

        if ($updated_queue === $queue) {
            return;
        }

        update_option('jlg_migration_v5_queue', $updated_queue);
        $this->schedule_next_migration_event();
    }
}

// Fonctions helper pour développeurs
function jlg_notation() {
    return JLG_Plugin_De_Notation_Main::get_instance();
}

function jlg_get_post_rating($post_id = null) {
    if (!$post_id) $post_id = get_the_ID();
    return JLG_Helpers::get_average_score_for_post($post_id);
}

function jlg_display_post_rating($post_id = null) {
    $score = jlg_get_post_rating($post_id);
    if ($score !== null) {
        echo '<span class="jlg-post-rating">' . esc_html(number_format_i18n($score, 1)) . '/10</span>';
    }
}

// Initialisation
JLG_Plugin_De_Notation_Main::get_instance();
<?php
if (!defined('ABSPATH')) exit;

class JLG_Template_Loader {
    private static $templates_dir = '';
    private static $template_cache = [];

    public static function init() {
        self::$templates_dir = JLG_NOTATION_PLUGIN_DIR . 'templates/';
    }

    public static function get_template($template_name, $variables = []) {
        if (empty(self::$templates_dir)) {
            self::init();
        }

        $template_path = self::get_template_path($template_name);
        
        if (!file_exists($template_path)) {
            return self::handle_missing_template($template_name);
        }

        return self::load_template_file($template_path, $variables);
    }

    private static function get_template_path($template_name) {
        $template_name = ltrim($template_name, '/');
        if (!str_ends_with($template_name, '.php')) {
            $template_name .= '.php';
        }
        return self::$templates_dir . $template_name;
    }

    private static function load_template_file($template_path, $variables) {
        if (!empty($variables) && is_array($variables)) {
            extract($variables, EXTR_SKIP);
        }

        ob_start();
        include $template_path;
        return ob_get_clean();
    }

    private static function handle_missing_template($template_name) {
        if (current_user_can('manage_options')) {
            return sprintf(
                '<div class="notice notice-warning"><p>Template manquant : <code>%s</code></p></div>',
                esc_html($template_name)
            );
        }
        return '<!-- Template JLG manquant -->';
    }

    public static function display_template($template_name, $variables = []) {
        echo self::get_template($template_name, $variables);
    }
}
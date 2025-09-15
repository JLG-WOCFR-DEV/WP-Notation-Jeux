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

        return self::load_template_from_directory(self::$templates_dir, $template_name, $variables);
    }

    public static function get_admin_template($template_name, $variables = []) {
        $directory = JLG_NOTATION_PLUGIN_DIR . 'admin/templates/';
        return self::load_template_from_directory($directory, $template_name, $variables);
    }

    public static function get_template_from_directory($directory, $template_name, $variables = []) {
        return self::load_template_from_directory($directory, $template_name, $variables);
    }

    private static function load_template_from_directory($directory, $template_name, $variables) {
        $template_path = self::build_template_path($directory, $template_name);

        if (!file_exists($template_path)) {
            $relative_name = self::get_relative_template_name($directory, $template_name);
            return self::handle_missing_template($relative_name);
        }

        return self::load_template_file($template_path, $variables);
    }

    private static function build_template_path($directory, $template_name) {
        $directory = rtrim($directory, '/\\') . '/';
        $template_name = ltrim($template_name, '/');

        if (!str_ends_with($template_name, '.php')) {
            $template_name .= '.php';
        }

        return $directory . $template_name;
    }

    private static function get_relative_template_name($directory, $template_name) {
        $directory = rtrim($directory, '/\\') . '/';
        $relative_base = str_replace(JLG_NOTATION_PLUGIN_DIR, '', $directory);
        $relative_base = trim($relative_base, '/');

        $template_name = ltrim($template_name, '/');

        if (!empty($relative_base)) {
            return $relative_base . '/' . $template_name;
        }

        return $template_name;
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

    public static function display_admin_template($template_name, $variables = []) {
        echo self::get_admin_template($template_name, $variables);
    }

    public static function display_template_from_directory($directory, $template_name, $variables = []) {
        echo self::get_template_from_directory($directory, $template_name, $variables);
    }
}

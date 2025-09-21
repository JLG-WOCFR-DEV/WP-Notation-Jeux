<?php

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__);
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback) {
        // No-op stub for WordPress hook registration in tests.
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
        // No-op stub for WordPress filter registration in tests.
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($hook, $value) {
        return $value;
    }
}

if (!function_exists('register_setting')) {
    function register_setting($option_group, $option_name, $args = []) {
        // No-op stub used during tests.
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        $options = $GLOBALS['jlg_test_options'] ?? [];

        return $options[$option] ?? $default;
    }
}

if (!function_exists('wp_parse_args')) {
    function wp_parse_args($args, $defaults = []) {
        if (is_object($args)) {
            $args = get_object_vars($args);
        }

        if (is_array($args)) {
            return array_merge($defaults, $args);
        }

        parse_str((string) $args, $parsed_args);

        return array_merge($defaults, $parsed_args);
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return (string) $text;
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default') {
        return esc_html(__($text, $domain));
    }
}

if (!function_exists('esc_html_e')) {
    function esc_html_e($text, $domain = 'default') {
        echo esc_html__($text, $domain);
    }
}

if (!function_exists('add_settings_section')) {
    function add_settings_section($id, $title, $callback, $page) {
        // No-op stub used during tests.
    }
}

if (!function_exists('add_settings_field')) {
    function add_settings_field($id, $title, $callback, $page, $section, $args = []) {
        // No-op stub used during tests.
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        if (!is_scalar($str)) {
            return '';
        }

        $filtered = filter_var($str, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW);

        return is_string($filtered) ? trim($filtered) : '';
    }
}

if (!function_exists('number_format_i18n')) {
    function number_format_i18n($number, $decimals = 0) {
        $number   = (float) $number;
        $decimals = (int) $decimals;

        return number_format($number, $decimals, '.', ',');
    }
}

if (!function_exists('wp_unslash')) {
    function wp_unslash($value) {
        if (is_array($value)) {
            return array_map('wp_unslash', $value);
        }

        return is_string($value) ? stripslashes($value) : $value;
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability) {
        return true;
    }
}

if (!function_exists('sanitize_hex_color')) {
    function sanitize_hex_color($color) {
        if (!is_string($color)) {
            return '';
        }

        $color = trim($color);

        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color)) {
            return strtolower($color);
        }

        return '';
    }
}

if (!function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags($string) {
        return is_string($string) ? strip_tags($string) : '';
    }
}

if (!function_exists('check_ajax_referer')) {
    function check_ajax_referer($action, $query_arg = false, $die = true) {
        return true;
    }
}

if (!function_exists('wp_hash')) {
    function wp_hash($data) {
        return md5((string) $data);
    }
}

if (!function_exists('has_shortcode')) {
    function has_shortcode($content, $tag) {
        if (!is_string($content) || $content === '') {
            return false;
        }

        return strpos($content, '[' . $tag) !== false;
    }
}

if (!class_exists('WP_Post')) {
    class WP_Post
    {
        public function __construct(array $data = [])
        {
            foreach ($data as $key => $value) {
                $this->$key = $value;
            }
        }
    }
}

if (!function_exists('get_post')) {
    function get_post($post_id)
    {
        $posts = $GLOBALS['jlg_test_posts'] ?? [];

        return $posts[$post_id] ?? null;
    }
}

if (!function_exists('get_post_meta')) {
    function get_post_meta($post_id, $key, $single = false)
    {
        $meta = $GLOBALS['jlg_test_meta'] ?? [];

        if (!isset($meta[$post_id][$key])) {
            return $single ? '' : [];
        }

        $value = $meta[$post_id][$key];

        if ($single && is_array($value)) {
            return reset($value);
        }

        return $value;
    }
}

if (!function_exists('update_post_meta')) {
    function update_post_meta($post_id, $key, $value)
    {
        if (!isset($GLOBALS['jlg_test_meta_updates'])) {
            $GLOBALS['jlg_test_meta_updates'] = [];
        }

        $GLOBALS['jlg_test_meta_updates'][] = [
            'post_id' => $post_id,
            'key'     => $key,
            'value'   => $value,
        ];

        $GLOBALS['jlg_test_meta'][$post_id][$key] = $value;

        return true;
    }
}

class WP_Send_Json_Exception extends Exception
{
    public $data;
    public $status;
    public $success;

    public function __construct($data, $status = null, $success = false)
    {
        parent::__construct('JSON response sent.');
        $this->data    = $data;
        $this->status  = $status;
        $this->success = $success;
    }
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null, $status_code = null)
    {
        throw new WP_Send_Json_Exception($data, $status_code, false);
    }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null, $status_code = null)
    {
        throw new WP_Send_Json_Exception($data, $status_code, true);
    }
}

require_once __DIR__ . '/../includes/class-jlg-helpers.php';
require_once __DIR__ . '/../includes/admin/class-jlg-admin-settings.php';
require_once __DIR__ . '/../includes/class-jlg-frontend.php';
require_once __DIR__ . '/../includes/utils/class-jlg-validator.php';
require_once __DIR__ . '/../includes/admin/class-jlg-admin-ajax.php';

<?php

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__);
}

if (!defined('JLG_NOTATION_VERSION')) {
    define('JLG_NOTATION_VERSION', 'test');
}

if (!defined('JLG_NOTATION_PLUGIN_URL')) {
    define('JLG_NOTATION_PLUGIN_URL', 'https://example.com/plugin/');
}

if (!defined('JLG_NOTATION_PLUGIN_DIR')) {
    define('JLG_NOTATION_PLUGIN_DIR', dirname(__DIR__) . '/');
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        // No-op stub for WordPress hook registration in tests.
    }
}

if (!function_exists('add_shortcode')) {
    function add_shortcode($tag, $callback) {
        // No-op stub for shortcode registration during tests.
    }
}

if (!function_exists('shortcode_atts')) {
    function shortcode_atts($pairs, $atts, $shortcode = '') {
        if (!is_array($atts)) {
            $atts = [];
        }

        return array_merge($pairs, $atts);
    }
}

if (!function_exists('get_the_ID')) {
    function get_the_ID() {
        return isset($GLOBALS['jlg_test_current_post_id']) ? (int) $GLOBALS['jlg_test_current_post_id'] : 0;
    }
}

if (!function_exists('is_singular')) {
    function is_singular($post_types = '') {
        $post_id = get_the_ID();
        if ($post_id <= 0) {
            return false;
        }

        $post = get_post($post_id);
        if (!$post instanceof WP_Post) {
            return false;
        }

        if ($post_types === '') {
            return true;
        }

        if (is_array($post_types)) {
            return in_array($post->post_type ?? '', $post_types, true);
        }

        return ($post->post_type ?? '') === $post_types;
    }
}

if (!function_exists('get_the_title')) {
    function get_the_title($post = 0) {
        if (is_object($post) && isset($post->post_title)) {
            return (string) $post->post_title;
        }

        if (is_array($post) && isset($post['post_title'])) {
            return (string) $post['post_title'];
        }

        $post_id = (int) $post;
        if ($post_id <= 0) {
            return '';
        }

        $posts = $GLOBALS['jlg_test_posts'] ?? [];

        if (isset($posts[$post_id])) {
            $stored_post = $posts[$post_id];

            if (is_object($stored_post) && isset($stored_post->post_title)) {
                return (string) $stored_post->post_title;
            }

            if (is_array($stored_post) && isset($stored_post['post_title'])) {
                return (string) $stored_post['post_title'];
            }
        }

        return '';
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

if (!function_exists('did_action')) {
    function did_action($hook) {
        unset($hook);

        return 0;
    }
}

if (!function_exists('do_action')) {
    function do_action($hook, ...$args) {
        if (!isset($GLOBALS['jlg_test_actions'])) {
            $GLOBALS['jlg_test_actions'] = [];
        }

        $GLOBALS['jlg_test_actions'][] = [$hook, $args];
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

if (!function_exists('date_i18n')) {
    function date_i18n($format, $timestamp) {
        if (!is_int($timestamp)) {
            $timestamp = is_numeric($timestamp) ? (int) $timestamp : strtotime((string) $timestamp);
        }

        if ($timestamp === false) {
            return '';
        }

        if (!is_string($format) || $format === '') {
            $format = 'F j, Y';
        }

        return date($format, $timestamp);
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value, $autoload = null) {
        if (!isset($GLOBALS['jlg_test_options'])) {
            $GLOBALS['jlg_test_options'] = [];
        }

        $GLOBALS['jlg_test_options'][$option] = $value;

        return true;
    }
}

if (!function_exists('delete_option')) {
    function delete_option($option) {
        if (isset($GLOBALS['jlg_test_options'][$option])) {
            unset($GLOBALS['jlg_test_options'][$option]);
        }

        return true;
    }
}

if (!function_exists('set_transient')) {
    function set_transient($transient, $value, $expiration = 0) {
        if (!isset($GLOBALS['jlg_test_transients'])) {
            $GLOBALS['jlg_test_transients'] = [];
        }

        $GLOBALS['jlg_test_transients'][$transient] = $value;

        return true;
    }
}

if (!function_exists('get_transient')) {
    function get_transient($transient) {
        return $GLOBALS['jlg_test_transients'][$transient] ?? false;
    }
}

if (!function_exists('delete_transient')) {
    function delete_transient($transient) {
        if (isset($GLOBALS['jlg_test_transients'][$transient])) {
            unset($GLOBALS['jlg_test_transients'][$transient]);
        }

        return true;
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

if (!function_exists('wp_kses_post')) {
    function wp_kses_post($data) {
        return (string) $data;
    }
}

if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default') {
        return esc_html(__($text, $domain));
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_url_raw')) {
    function esc_url_raw($url) {
        if (!is_string($url)) {
            return '';
        }

        $sanitized = filter_var($url, FILTER_SANITIZE_URL);

        return is_string($sanitized) ? $sanitized : '';
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url) {
        return esc_url_raw($url);
    }
}

if (!function_exists('home_url')) {
    function home_url($path = '', $scheme = null) {
        unset($scheme);

        $base = 'https://public.example';

        if (!is_string($path)) {
            $path = '';
        }

        if ($path === '' || $path === '/') {
            return $base . '/';
        }

        if ($path[0] !== '/') {
            $path = '/' . $path;
        }

        return $base . $path;
    }
}

if (!function_exists('add_query_arg')) {
    function add_query_arg($args, $url = '') {
        if (!is_array($args)) {
            $args = [];
        }

        $base = $url === '' ? 'https://example.com/' : (string) $url;
        $query = http_build_query($args);

        if ($query === '') {
            return $base;
        }

        return $base . (strpos($base, '?') === false ? '?' : '&') . $query;
    }
}

if (!function_exists('remove_query_arg')) {
    function remove_query_arg($key, $url) {
        unset($key);

        return (string) $url;
    }
}

if (!function_exists('wp_parse_url')) {
    function wp_parse_url($url) {
        if (!is_string($url) || $url === '') {
            return false;
        }

        return parse_url($url);
    }
}

if (!function_exists('taxonomy_exists')) {
    function taxonomy_exists($taxonomy) {
        unset($taxonomy);

        return false;
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        unset($thing);

        return false;
    }
}

if (!function_exists('get_category')) {
    function get_category($id) {
        unset($id);

        return null;
    }
}

if (!function_exists('get_term_by')) {
    function get_term_by($field, $value, $taxonomy, $output = 'OBJECT', $filter = 'raw') {
        unset($field, $value, $taxonomy, $output, $filter);

        return false;
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

if (!function_exists('absint')) {
    function absint($maybeint) {
        if (is_numeric($maybeint)) {
            return (int) abs($maybeint);
        }

        return 0;
    }
}

if (!function_exists('sanitize_key')) {
    function sanitize_key($key) {
        if (!is_string($key)) {
            return '';
        }

        $key = strtolower($key);
        $key = preg_replace('/[^a-z0-9_]/', '', $key);

        return (string) $key;
    }
}

if (!function_exists('sanitize_title')) {
    function sanitize_title($title) {
        $title = strtolower((string) $title);
        $title = preg_replace('/[^a-z0-9\s-]/', '', $title);
        $title = preg_replace('/[\s-]+/', '-', $title);

        return trim($title, '-');
    }
}

if (!function_exists('sanitize_html_class')) {
    function sanitize_html_class($class, $fallback = '') {
        if (!is_string($class)) {
            $class = '';
        }

        $sanitized = preg_replace('/[^A-Za-z0-9_-]/', '', $class);

        if ($sanitized === '' && $fallback !== '') {
            return sanitize_html_class($fallback);
        }

        return $sanitized;
    }
}

if (!function_exists('number_format_i18n')) {
    function number_format_i18n($number, $decimals = 0) {
        $number   = (float) $number;
        $decimals = (int) $decimals;

        return number_format($number, $decimals, '.', ',');
    }
}

if (!function_exists('current_time')) {
    function current_time($type, $gmt = 0) {
        $timestamp = time();

        if ($type === 'timestamp' || $type === 'U') {
            return $timestamp;
        }

        if ($gmt) {
            return gmdate($type === 'mysql' ? 'Y-m-d H:i:s' : (is_string($type) && $type !== '' ? $type : 'Y-m-d H:i:s'), $timestamp);
        }

        if ($type === 'mysql') {
            return date('Y-m-d H:i:s', $timestamp);
        }

        if (is_string($type) && $type !== '') {
            return date($type, $timestamp);
        }

        return $timestamp;
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

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action = -1) {
        return true;
    }
}

if (!function_exists('wp_die')) {
    function wp_die($message = '') {
        throw new RuntimeException((string) $message);
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability, ...$args) {
        if (isset($GLOBALS['jlg_test_current_user_can']) && is_callable($GLOBALS['jlg_test_current_user_can'])) {
            return (bool) call_user_func($GLOBALS['jlg_test_current_user_can'], $capability, ...$args);
        }

        return true;
    }
}

if (!function_exists('get_post_type')) {
    function get_post_type($post = null) {
        if ($post instanceof WP_Post) {
            return $post->post_type ?? null;
        }

        if ($post === null) {
            return null;
        }

        $resolved_post = get_post($post);

        return $resolved_post instanceof WP_Post ? ($resolved_post->post_type ?? null) : null;
    }
}

if (!function_exists('wp_register_style')) {
    function wp_register_style($handle, $src, $deps = [], $ver = false) {
        if (!isset($GLOBALS['jlg_test_styles'])) {
            $GLOBALS['jlg_test_styles'] = [
                'registered' => [],
                'enqueued'   => [],
            ];
        }

        $GLOBALS['jlg_test_styles']['registered'][$handle] = [
            'src'  => $src,
            'deps' => $deps,
            'ver'  => $ver,
        ];

        return true;
    }
}

if (!function_exists('wp_style_is')) {
    function wp_style_is($handle, $list = 'enqueued') {
        $styles = $GLOBALS['jlg_test_styles'] ?? [
            'registered' => [],
            'enqueued'   => [],
        ];

        if ($list === 'registered') {
            return array_key_exists($handle, $styles['registered']);
        }

        if ($list === 'enqueued') {
            return array_key_exists($handle, $styles['enqueued']);
        }

        return false;
    }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style($handle) {
        if (!isset($GLOBALS['jlg_test_styles'])) {
            $GLOBALS['jlg_test_styles'] = [
                'registered' => [],
                'enqueued'   => [],
            ];
        }

        $GLOBALS['jlg_test_styles']['enqueued'][$handle] = true;

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

if (!function_exists('site_url')) {
    function site_url($path = '', $scheme = null) {
        unset($path, $scheme);

        return 'https://example.com';
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
    #[\AllowDynamicProperties]
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

        if ($single) {
            return $value;
        }

        if (is_array($value)) {
            return $value;
        }

        return [$value];
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

if (!function_exists('delete_post_meta')) {
    function delete_post_meta($post_id, $key, $value = '') {
        unset($value);

        if (isset($GLOBALS['jlg_test_meta'][$post_id][$key])) {
            unset($GLOBALS['jlg_test_meta'][$post_id][$key]);
        }

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
require_once __DIR__ . '/../includes/admin/class-jlg-admin-platforms.php';
require_once __DIR__ . '/../includes/class-jlg-frontend.php';
require_once __DIR__ . '/../includes/utils/class-jlg-validator.php';
require_once __DIR__ . '/../includes/admin/class-jlg-admin-ajax.php';
require_once __DIR__ . '/../includes/shortcodes/class-jlg-shortcode-summary-display.php';

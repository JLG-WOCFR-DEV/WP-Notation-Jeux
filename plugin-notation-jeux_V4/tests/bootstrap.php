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

if (!defined('JLG_NOTATION_TEST_ENV')) {
    define('JLG_NOTATION_TEST_ENV', true);
}

if (!defined('MINUTE_IN_SECONDS')) {
    define('MINUTE_IN_SECONDS', 60);
}

if (!function_exists('plugin_dir_path')) {
    /**
     * Lightweight replacement for plugin_dir_path used during tests.
     *
     * Ensures plugin bootstrap code can resolve filesystem paths without
     * requiring the full WordPress stack.
     */
    function plugin_dir_path($file) {
        return rtrim(dirname((string) $file), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }
}

if (!function_exists('plugin_dir_url')) {
    /**
     * Minimal plugin_dir_url stub that mirrors WordPress behaviour closely
     * enough for unit tests that only need a deterministic URL string.
     */
    function plugin_dir_url($file) {
        $basename = trim(basename((string) $file));

        return 'https://example.com/wp-content/plugins/' . ($basename !== '' ? $basename . '/' : '');
    }
}

if (!function_exists('trailingslashit')) {
    function trailingslashit($value) {
        $value = (string) $value;

        if ($value === '') {
            return '/';
        }

        return rtrim($value, '/\\') . '/';
    }
}

if (!function_exists('wp_login_url')) {
    /**
     * Fournit une URL de connexion déterministe avec prise en charge du redirect.
     */
    function wp_login_url($redirect = '') {
        $base = 'https://example.com/wp-login.php';

        if (is_string($redirect) && $redirect !== '') {
            return $base . '?redirect_to=' . rawurlencode($redirect);
        }

        return $base;
    }
}

if (!function_exists('plugin_basename')) {
    /**
     * Provide plugin_basename so activation hooks can register without errors.
     */
    function plugin_basename($file) {
        return trim(basename((string) $file));
    }
}

if (!function_exists('register_activation_hook')) {
    /**
     * Activation hooks are no-ops in the isolated test environment.
     */
    function register_activation_hook($file, $callback) {
        unset($file, $callback);
    }
}

if (!function_exists('register_deactivation_hook')) {
    /**
     * Deactivation hooks are safely ignored during unit tests.
     */
    function register_deactivation_hook($file, $callback) {
        unset($file, $callback);
    }
}

if (!function_exists('flush_rewrite_rules')) {
    /**
     * Flush rewrite rules stub used to satisfy plugin activation logic.
     */
    function flush_rewrite_rules() {
    }
}

if (!function_exists('load_plugin_textdomain')) {
    /**
     * Text domain loading is skipped in tests.
     */
    function load_plugin_textdomain($domain, $deprecated = false, $path = '') {
        unset($domain, $deprecated, $path);
    }
}

if (!function_exists('get_bloginfo')) {
    /**
     * Simplified get_bloginfo stub returning deterministic values for tests.
     */
    function get_bloginfo($show = '', $filter = 'raw') {
        unset($filter);

        if ($show === 'version') {
            return '6.4';
        }

        return 'Notation Test Blog';
    }
}

if (!function_exists('is_admin')) {
    /**
     * Allow tests to toggle admin context via $GLOBALS['jlg_test_is_admin'].
     */
    function is_admin() {
        return !empty($GLOBALS['jlg_test_is_admin']);
    }
}

if (!function_exists('is_user_logged_in')) {
    /**
     * Simule l'état de connexion en fonction du drapeau global dédié aux tests.
     */
    function is_user_logged_in() {
        return !empty($GLOBALS['jlg_test_is_user_logged_in']);
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return isset($GLOBALS['jlg_test_current_user_id']) ? (int) $GLOBALS['jlg_test_current_user_id'] : 0;
    }
}

if (!function_exists('get_user_meta')) {
    function get_user_meta($user_id, $key, $single = false)
    {
        $user_id = (int) $user_id;

        if (!isset($GLOBALS['jlg_test_user_meta'])) {
            $GLOBALS['jlg_test_user_meta'] = [];
        }

        $value = $GLOBALS['jlg_test_user_meta'][$user_id][$key] ?? null;

        if ($value === null) {
            return $single ? '' : [];
        }

        if ($single) {
            if (is_array($value)) {
                return reset($value);
            }

            return $value;
        }

        return is_array($value) ? $value : [$value];
    }
}

if (!function_exists('update_user_meta')) {
    function update_user_meta($user_id, $key, $value)
    {
        $user_id = (int) $user_id;

        if (!isset($GLOBALS['jlg_test_user_meta'])) {
            $GLOBALS['jlg_test_user_meta'] = [];
        }

        if (!isset($GLOBALS['jlg_test_user_meta'][$user_id])) {
            $GLOBALS['jlg_test_user_meta'][$user_id] = [];
        }

        $GLOBALS['jlg_test_user_meta'][$user_id][$key] = $value;

        return true;
    }
}

if (!function_exists('delete_user_meta')) {
    function delete_user_meta($user_id, $key)
    {
        $user_id = (int) $user_id;

        if (isset($GLOBALS['jlg_test_user_meta'][$user_id][$key])) {
            unset($GLOBALS['jlg_test_user_meta'][$user_id][$key]);
        }

        return true;
    }
}

if (!function_exists('delete_metadata')) {
    function delete_metadata($type, $object_id, $meta_key, $meta_value = '', $delete_all = false)
    {
        unset($meta_value);

        if ($type !== 'user') {
            return true;
        }

        if (!isset($GLOBALS['jlg_test_user_meta'])) {
            $GLOBALS['jlg_test_user_meta'] = [];
        }

        if ($delete_all) {
            foreach ($GLOBALS['jlg_test_user_meta'] as $id => $meta) {
                if (isset($meta[$meta_key])) {
                    unset($GLOBALS['jlg_test_user_meta'][$id][$meta_key]);
                }
            }

            return true;
        }

        $object_id = (int) $object_id;

        if ($object_id > 0 && isset($GLOBALS['jlg_test_user_meta'][$object_id][$meta_key])) {
            unset($GLOBALS['jlg_test_user_meta'][$object_id][$meta_key]);
        }

        return true;
    }
}

if (!function_exists('get_users')) {
    function get_users($args = [])
    {
        $users = $GLOBALS['jlg_test_users'] ?? [];

        if (!is_array($users)) {
            return [];
        }

        $fields = 'all';
        if (is_array($args) && isset($args['fields'])) {
            $fields = $args['fields'];
        }

        if ($fields === 'ID') {
            return array_map(function ($user) {
                if (is_object($user) && isset($user->ID)) {
                    return (int) $user->ID;
                }

                if (is_array($user) && isset($user['ID'])) {
                    return (int) $user['ID'];
                }

                return (int) $user;
            }, $users);
        }

        if (is_array($fields)) {
            return array_map(function ($user) use ($fields) {
                $result = [];

                foreach ($fields as $field) {
                    if ($field === 'ID') {
                        if (is_object($user) && isset($user->ID)) {
                            $result['ID'] = (int) $user->ID;
                        } elseif (is_array($user) && isset($user['ID'])) {
                            $result['ID'] = (int) $user['ID'];
                        } else {
                            $result['ID'] = (int) $user;
                        }
                    }
                }

                return $result;
            }, $users);
        }

        return $users;
    }
}

if (!function_exists('doing_filter')) {
    /**
     * Minimal doing_filter stub so tests can emulate REST rendering context.
     */
    function doing_filter($hook = null) {
        if (!is_string($hook) || $hook === '') {
            return false;
        }

        $flags = $GLOBALS['jlg_test_doing_filters'] ?? [];

        return !empty($flags[$hook]);
    }
}

if (!function_exists('wp_next_scheduled')) {
    /**
     * Expose scheduled events for assertions via $GLOBALS['jlg_test_scheduled_events'].
     * Reset the global between tests to keep scenarios isolated.
     */
    function wp_next_scheduled($hook) {
        $events = $GLOBALS['jlg_test_scheduled_events'] ?? [];

        foreach ($events as $event) {
            if (($event['hook'] ?? '') === $hook) {
                return $event['timestamp'];
            }
        }

        return false;
    }
}

if (!function_exists('wp_schedule_event')) {
    /**
     * Capture recurring cron events so tests can assert scheduling logic.
     */
    function wp_schedule_event($timestamp, $recurrence, $hook, $args = []) {
        if (!isset($GLOBALS['jlg_test_scheduled_events'])) {
            $GLOBALS['jlg_test_scheduled_events'] = [];
        }

        $GLOBALS['jlg_test_scheduled_events'][] = [
            'timestamp'  => (int) $timestamp,
            'hook'       => (string) $hook,
            'recurrence' => (string) $recurrence,
            'args'       => is_array($args) ? $args : [$args],
        ];

        return true;
    }
}

if (!function_exists('wp_schedule_single_event')) {
    /**
     * Capture single-event schedules so tests can validate cron behaviour.
     */
    function wp_schedule_single_event($timestamp, $hook, $args = []) {
        if (!isset($GLOBALS['jlg_test_scheduled_events'])) {
            $GLOBALS['jlg_test_scheduled_events'] = [];
        }

        $GLOBALS['jlg_test_scheduled_events'][] = [
            'timestamp' => (int) $timestamp,
            'hook' => (string) $hook,
            'args' => is_array($args) ? $args : [$args],
        ];

        return true;
    }
}

if (!function_exists('wp_clear_scheduled_hook')) {
    /**
     * Remove queued hooks from the shared test registry.
     */
    function wp_clear_scheduled_hook($hook) {
        $events = $GLOBALS['jlg_test_scheduled_events'] ?? [];
        $retained = [];
        $removed = 0;

        foreach ($events as $event) {
            if (($event['hook'] ?? '') === $hook) {
                $removed++;
                continue;
            }

            $retained[] = $event;
        }

        $GLOBALS['jlg_test_scheduled_events'] = $retained;

        return $removed;
    }
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        // No-op stub for WordPress hook registration in tests.
    }
}

if (!function_exists('add_meta_box')) {
    function add_meta_box($id, $title, $callback, $screen, $context = 'advanced', $priority = 'default', $callback_args = null) {
        if (!isset($GLOBALS['jlg_test_meta_boxes'])) {
            $GLOBALS['jlg_test_meta_boxes'] = [];
        }

        $GLOBALS['jlg_test_meta_boxes'][] = [
            'id'            => $id,
            'title'         => $title,
            'callback'      => $callback,
            'screen'        => $screen,
            'context'       => $context,
            'priority'      => $priority,
            'callback_args' => $callback_args,
        ];
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

if (!function_exists('register_post_type')) {
    function register_post_type($post_type, $args = []) {
        if (!isset($GLOBALS['jlg_test_registered_post_types'])) {
            $GLOBALS['jlg_test_registered_post_types'] = [];
        }

        $post_type = sanitize_key($post_type);

        if ($post_type === '') {
            return false;
        }

        $GLOBALS['jlg_test_registered_post_types'][$post_type] = is_array($args) ? $args : [];

        return true;
    }
}

if (!function_exists('get_post_types')) {
    function get_post_types($args = [], $output = 'names', $operator = 'and') {
        unset($operator);

        $args   = is_array($args) ? $args : [];
        $output = $output === 'objects' ? 'objects' : 'names';

        $registered = $GLOBALS['jlg_test_registered_post_types'] ?? [];

        $built_in = [
            'post' => [
                'public' => true,
                'labels' => [
                    'name'          => 'Articles',
                    'singular_name' => 'Article',
                ],
            ],
            'page' => [
                'public' => true,
                'labels' => [
                    'name'          => 'Pages',
                    'singular_name' => 'Page',
                ],
            ],
        ];

        $all     = array_merge($built_in, $registered);
        $results = [];

        foreach ($all as $slug => $data) {
            $slug = sanitize_key($slug);

            if ($slug === '') {
                continue;
            }

            $data = is_array($data) ? $data : [];

            $labels = $data['labels'] ?? [];
            if (!is_array($labels)) {
                $labels = [];
            }

            $default_label = ucwords(str_replace(['-', '_'], ' ', $slug));
            $labels = array_merge(
                [
                    'name'          => $default_label,
                    'singular_name' => $default_label,
                ],
                $labels
            );

            $object = (object) array_merge(
                [
                    'name'   => $slug,
                    'label'  => $labels['name'],
                    'labels' => (object) $labels,
                    'public' => $data['public'] ?? true,
                ],
                $data
            );

            $matches = true;
            foreach ($args as $key => $value) {
                $property = $object->$key ?? null;

                if (is_bool($value)) {
                    if ((bool) $property !== $value) {
                        $matches = false;
                        break;
                    }
                    continue;
                }

                if (is_array($value)) {
                    if (!in_array($property, $value, true)) {
                        $matches = false;
                        break;
                    }
                    continue;
                }

                if ($property !== $value) {
                    $matches = false;
                    break;
                }
            }

            if (!$matches) {
                continue;
            }

            if ($output === 'objects') {
                $results[$slug] = $object;
            } else {
                $results[$slug] = $slug;
            }
        }

        return $results;
    }
}

if (!function_exists('post_type_exists')) {
    function post_type_exists($post_type) {
        $post_type = sanitize_key($post_type);

        if ($post_type === '') {
            return false;
        }

        $registered = $GLOBALS['jlg_test_registered_post_types'] ?? [];

        if (isset($registered[$post_type])) {
            return true;
        }

        $built_in = ['post', 'page'];

        return in_array($post_type, $built_in, true);
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

if (!function_exists('get_post_field')) {
    function get_post_field($field, $post = null, $context = 'display') {
        unset($context);

        if ($post instanceof WP_Post) {
            $source = $post;
        } elseif (is_array($post)) {
            $source = (object) $post;
        } else {
            $post_id = is_numeric($post) ? (int) $post : get_the_ID();
            $source = get_post($post_id);
        }

        if ($source instanceof WP_Post && isset($source->$field)) {
            return $source->$field;
        }

        return '';
    }
}

if (!function_exists('get_the_author_meta')) {
    function get_the_author_meta($field, $user_id = 0) {
        $users = $GLOBALS['jlg_test_users'] ?? [];

        if ($user_id === 0) {
            $user_id = (int) get_post_field('post_author', get_the_ID());
        }

        if (isset($users[$user_id])) {
            $user_data = $users[$user_id];

            if (is_array($user_data) && isset($user_data[$field])) {
                return $user_data[$field];
            }

            if (is_array($user_data) && $field === 'display_name' && isset($user_data['display_name'])) {
                return $user_data['display_name'];
            }
        }

        return '';
    }
}

if (!function_exists('get_the_date')) {
    function get_the_date($format = 'F j, Y', $post = 0) {
        if (!is_string($format) || $format === '') {
            $format = 'F j, Y';
        }

        if ($post === 0) {
            $post = get_the_ID();
        }

        $post_object = get_post($post);

        if (!$post_object instanceof WP_Post) {
            return '';
        }

        $raw_date = $post_object->post_date ?? '';

        if ($raw_date === '') {
            return '';
        }

        return date_i18n($format, $raw_date);
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
        if (!isset($GLOBALS['jlg_test_filters'])) {
            $GLOBALS['jlg_test_filters'] = [];
        }

        if (!isset($GLOBALS['jlg_test_filters'][$hook])) {
            $GLOBALS['jlg_test_filters'][$hook] = [];
        }

        if (!isset($GLOBALS['jlg_test_filters'][$hook][$priority])) {
            $GLOBALS['jlg_test_filters'][$hook][$priority] = [];
        }

        $GLOBALS['jlg_test_filters'][$hook][$priority][] = [
            'callback'      => $callback,
            'accepted_args' => (int) $accepted_args,
        ];

        return true;
    }
}

if (!function_exists('remove_filter')) {
    function remove_filter($hook, $callback, $priority = 10) {
        if (empty($GLOBALS['jlg_test_filters'][$hook][$priority])) {
            return false;
        }

        $removed = false;

        foreach ($GLOBALS['jlg_test_filters'][$hook][$priority] as $index => $data) {
            if (($data['callback'] ?? null) === $callback) {
                unset($GLOBALS['jlg_test_filters'][$hook][$priority][$index]);
                $removed = true;
            }
        }

        if ($removed) {
            $GLOBALS['jlg_test_filters'][$hook][$priority] = array_values($GLOBALS['jlg_test_filters'][$hook][$priority]);

            if (empty($GLOBALS['jlg_test_filters'][$hook][$priority])) {
                unset($GLOBALS['jlg_test_filters'][$hook][$priority]);
            }

            if (empty($GLOBALS['jlg_test_filters'][$hook])) {
                unset($GLOBALS['jlg_test_filters'][$hook]);
            }
        }

        return $removed;
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($hook, $value) {
        $args = func_get_args();

        if (empty($GLOBALS['jlg_test_filters'][$hook])) {
            return $value;
        }

        ksort($GLOBALS['jlg_test_filters'][$hook]);

        foreach ($GLOBALS['jlg_test_filters'][$hook] as $callbacks) {
            foreach ($callbacks as $data) {
                if (!is_callable($data['callback'] ?? null)) {
                    continue;
                }

                $accepted_args = max(1, (int) ($data['accepted_args'] ?? 1));
                $call_args = array_slice($args, 1);
                if (empty($call_args)) {
                    $call_args = [$value];
                } else {
                    $call_args[0] = $value;
                }

                $call_args = array_slice($call_args, 0, $accepted_args);
                if (count($call_args) < $accepted_args) {
                    $call_args = array_pad($call_args, $accepted_args, null);
                }

                $value = call_user_func_array($data['callback'], $call_args);
                $args[1] = $value;
            }
        }

        return $value;
    }
}

if (!function_exists('remove_all_filters')) {
    function remove_all_filters($hook) {
        if (isset($GLOBALS['jlg_test_filters'][$hook])) {
            unset($GLOBALS['jlg_test_filters'][$hook]);
        }

        return true;
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

if (!function_exists('wp_doing_ajax')) {
    function wp_doing_ajax() {
        return !empty($GLOBALS['jlg_test_doing_ajax']);
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

if (!function_exists('wp_timezone_string')) {
    function wp_timezone_string() {
        $timezone_string = get_option('timezone_string');

        if (is_string($timezone_string) && $timezone_string !== '') {
            return $timezone_string;
        }

        return 'UTC';
    }
}

if (!function_exists('wp_timezone')) {
    function wp_timezone() {
        $timezone_string = wp_timezone_string();

        try {
            return new DateTimeZone($timezone_string);
        } catch (Exception $exception) {
            return new DateTimeZone('UTC');
        }
    }
}

if (!function_exists('wp_date')) {
    function wp_date($format, $timestamp, $timezone = null) {
        if (!is_int($timestamp)) {
            $timestamp = is_numeric($timestamp) ? (int) $timestamp : strtotime((string) $timestamp);
        }

        if (!is_int($timestamp)) {
            return '';
        }

        if (!($timezone instanceof DateTimeZone)) {
            try {
                $timezone = wp_timezone();
            } catch (Exception $exception) {
                $timezone = new DateTimeZone('UTC');
            }
        }

        try {
            $date = new DateTimeImmutable('@' . $timestamp);
            $date = $date->setTimezone($timezone);
        } catch (Exception $exception) {
            return '';
        }

        $format = is_string($format) && $format !== '' ? $format : 'Y-m-d H:i:s';

        return $date->format($format);
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

if (!function_exists('rest_url')) {
    function rest_url($path = '', $scheme = 'rest') {
        unset($scheme);

        $path = ltrim((string) $path, '/');

        return 'https://example.com/wp-json/' . $path;
    }
}

if (!function_exists('register_rest_route')) {
    function register_rest_route($namespace, $route, $args = [], $override = false) {
        if (!isset($GLOBALS['jlg_test_rest_routes'])) {
            $GLOBALS['jlg_test_rest_routes'] = [];
        }

        $GLOBALS['jlg_test_rest_routes'][] = [
            'namespace' => $namespace,
            'route'     => $route,
            'args'      => $args,
            'override'  => $override,
        ];

        return true;
    }
}

if (!function_exists('rest_ensure_response')) {
    function rest_ensure_response($response) {
        return $response;
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

if (!function_exists('wp_list_pluck')) {
    function wp_list_pluck($input_list, $field, $index_key = null) {
        if (!is_array($input_list)) {
            return [];
        }

        $output = [];

        foreach ($input_list as $key => $value) {
            $value_field = null;

            if (is_array($value) && array_key_exists($field, $value)) {
                $value_field = $value[$field];
            } elseif (is_object($value) && isset($value->$field)) {
                $value_field = $value->$field;
            }

            if ($value_field === null) {
                continue;
            }

            if ($index_key !== null) {
                if (is_array($value) && array_key_exists($index_key, $value)) {
                    $key = $value[$index_key];
                } elseif (is_object($value) && isset($value->$index_key)) {
                    $key = $value->$index_key;
                }
            }

            $output[$key] = $value_field;
        }

        return $output;
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return (string) $text;
    }
}

if (!function_exists('_x')) {
    function _x($text, $context, $domain = 'default')
    {
        unset($context, $domain);

        return (string) $text;
    }
}

if (!function_exists('__return_empty_string')) {
    function __return_empty_string() {
        return '';
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

if (!function_exists('wp_kses')) {
    function wp_kses($string, $allowed_html = [], $allowed_protocols = []) {
        unset($allowed_html, $allowed_protocols);

        return (string) $string;
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

if (!function_exists('_n')) {
    function _n($single, $plural, $number, $domain = 'default') {
        unset($domain);

        return ($number === 1) ? (string) $single : (string) $plural;
    }
}

if (!function_exists('esc_attr_e')) {
    function esc_attr_e($text, $domain = 'default') {
        echo esc_attr(__($text, $domain));
    }
}

if (!function_exists('esc_attr__')) {
    function esc_attr__($text, $domain = 'default') {
        return esc_attr(__($text, $domain));
    }
}

if (!class_exists('Walker')) {
    /**
     * Minimal Walker base class emulating the WordPress structure for tests.
     */
    class Walker
    {
        public $tree_type = '';
        public $db_fields = [];

        public function start_el(&$output, $data_object, $depth = 0, $args = [], $current_object_id = 0)
        {
            unset($output, $data_object, $depth, $args, $current_object_id);
        }

        public function end_el(&$output, $data_object, $depth = 0, $args = [])
        {
            unset($output, $data_object, $depth, $args);
        }

        public function start_lvl(&$output, $depth = 0, $args = [])
        {
            unset($output, $depth, $args);
        }

        public function end_lvl(&$output, $depth = 0, $args = [])
        {
            unset($output, $depth, $args);
        }
    }
}

if (!class_exists('Walker_CategoryDropdown')) {
    /**
     * Simplified Walker_CategoryDropdown implementation for unit tests.
     */
    class Walker_CategoryDropdown extends Walker
    {
        public $tree_type = 'category';

        public $db_fields = [
            'parent' => 'parent',
            'id'     => 'term_id',
        ];

        public function start_el(&$output, $data_object, $depth = 0, $args = [], $current_object_id = 0)
        {
            unset($current_object_id);

            $value_field = isset($args['value_field'], $data_object->{$args['value_field']})
                ? $args['value_field']
                : 'term_id';

            $value = (string) ($data_object->{$value_field} ?? '');

            $output .= "\t<option class=\"level-$depth\" value=\"" . esc_attr($value) . '"';

            if ((string) ($args['selected'] ?? '') === $value) {
                $output .= ' selected="selected"';
            }

            $output .= '>';
            $output .= apply_filters('list_cats', $data_object->name ?? '', $data_object);

            if (!empty($args['show_count'])) {
                $count = isset($data_object->count) ? (int) $data_object->count : 0;
                $output .= '&nbsp;&nbsp;(' . number_format_i18n($count) . ')';
            }

            $output .= "</option>\n";
        }
    }
}

if (!function_exists('wp_dropdown_categories')) {
    function wp_dropdown_categories($args = []) {
        $defaults = [
            'show_option_all'   => '',
            'show_option_none'  => '',
            'option_none_value' => '',
            'name'              => 'cat',
            'id'                => '',
            'selected'          => '',
            'class'             => '',
            'multiple'          => false,
            'echo'              => 1,
        ];

        if (!is_array($args)) {
            $args = [];
        }

        $parsed = array_merge($defaults, $args);

        $id_attr = $parsed['id'] !== ''
            ? ' id="' . esc_attr($parsed['id']) . '"'
            : '';

        $multiple_attr = !empty($parsed['multiple']) ? ' multiple="multiple"' : '';

        $options = [];

        if ($parsed['show_option_all'] !== '') {
            $options[] = '<option value="">' . esc_html($parsed['show_option_all']) . '</option>';
        }

        if ($parsed['show_option_none'] !== '') {
            $options[] = '<option value="' . esc_attr((string) $parsed['option_none_value']) . '">' . esc_html($parsed['show_option_none']) . '</option>';
        }

        $selected_attr = ((string) $parsed['selected'] !== '') ? ' selected="selected"' : '';
        $option_value   = (string) $parsed['selected'];
        $options[]      = '<option value="' . esc_attr($option_value) . '"' . $selected_attr . '>' . esc_html($option_value) . '</option>';

        $select = '<select name="' . esc_attr($parsed['name']) . '"' . $id_attr . ' class="' . esc_attr($parsed['class']) . '"' . $multiple_attr . '>' . implode('', $options) . '</select>';

        $select = apply_filters('wp_dropdown_cats', $select, $parsed);

        if (!empty($parsed['echo'])) {
            echo $select;
        }

        return $select;
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
    $GLOBALS['wp_test_home_url_base'] = $GLOBALS['wp_test_home_url_base'] ?? 'https://public.example';

    function home_url($path = '', $scheme = null) {
        unset($scheme);

        $base = isset($GLOBALS['wp_test_home_url_base']) ? (string) $GLOBALS['wp_test_home_url_base'] : 'https://public.example';

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
        $argument_count = func_num_args();

        if ($argument_count >= 3) {
            $key   = $args;
            $value = func_get_arg(1);
            $url   = func_get_arg(2);
            $args  = [$key => $value];
        }

        if (!is_array($args)) {
            $args = [];
        }

        $base = $url === '' ? 'https://example.com/' : (string) $url;
        $fragment = '';

        $hash_position = strpos($base, '#');
        if ($hash_position !== false) {
            $fragment = substr($base, $hash_position);
            $base     = substr($base, 0, $hash_position);
        }

        $parts = explode('?', $base, 2);
        $base_path = $parts[0];
        $existing  = [];

        if (isset($parts[1]) && $parts[1] !== '') {
            parse_str($parts[1], $existing);
        }

        $merged = array_merge($existing, $args);
        $filtered = [];
        foreach ($merged as $key => $value) {
            if ($value === null || $value === false) {
                continue;
            }

            $filtered[$key] = $value;
        }

        $query = http_build_query($filtered);

        return $base_path . ($query === '' ? '' : '?' . $query) . $fragment;
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

if (!function_exists('wp_reset_postdata')) {
    function wp_reset_postdata() {
        // No-op stub for tests.
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

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($str) {
        return sanitize_text_field($str);
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
        $key = preg_replace('/[^a-z0-9_\-]/', '', $key);

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

if (!function_exists('remove_accents')) {
    function remove_accents($string) {
        if (!is_string($string)) {
            return '';
        }

        $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT', $string);
        if (is_string($transliterated) && $transliterated !== '') {
            return $transliterated;
        }

        return $string;
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
        $timezone = $gmt ? new DateTimeZone('UTC') : (function_exists('wp_timezone') ? wp_timezone() : new DateTimeZone(date_default_timezone_get()));
        $now = new DateTimeImmutable('now', $timezone);

        if ($type === 'timestamp' || $type === 'U') {
            return $now->getTimestamp();
        }

        $format = 'Y-m-d H:i:s';
        if ($type === 'mysql') {
            $format = 'Y-m-d H:i:s';
        } elseif (is_string($type) && $type !== '') {
            $format = $type;
        }

        return $now->format($format);
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

if (!function_exists('wp_add_inline_style')) {
    function wp_add_inline_style($handle, $data) {
        if (!isset($GLOBALS['jlg_test_inline_styles'])) {
            $GLOBALS['jlg_test_inline_styles'] = [];
        }

        if (!isset($GLOBALS['jlg_test_inline_styles'][$handle])) {
            $GLOBALS['jlg_test_inline_styles'][$handle] = [];
        }

        $GLOBALS['jlg_test_inline_styles'][$handle][] = $data;

        return true;
    }
}

if (!function_exists('wp_add_inline_script')) {
    function wp_add_inline_script($handle, $data, $position = 'after') {
        if (!isset($GLOBALS['jlg_test_scripts'])) {
            $GLOBALS['jlg_test_scripts'] = [
                'registered' => [],
                'enqueued'   => [],
                'localized'  => [],
                'inline'     => [],
            ];
        }

        if (!isset($GLOBALS['jlg_test_scripts']['inline'])) {
            $GLOBALS['jlg_test_scripts']['inline'] = [];
        }

        if (!isset($GLOBALS['jlg_test_scripts']['inline'][$handle])) {
            $GLOBALS['jlg_test_scripts']['inline'][$handle] = [];
        }

        $GLOBALS['jlg_test_scripts']['inline'][$handle][] = [
            'code'     => (string) $data,
            'position' => $position,
        ];

        return true;
    }
}

if (!function_exists('wp_register_script')) {
    function wp_register_script($handle, $src = '', $deps = [], $ver = false, $in_footer = false) {
        if (!isset($GLOBALS['jlg_test_scripts'])) {
            $GLOBALS['jlg_test_scripts'] = [
                'registered' => [],
                'enqueued'   => [],
                'localized'  => [],
                'inline'     => [],
            ];
        }

        if (!isset($GLOBALS['jlg_test_scripts']['inline'])) {
            $GLOBALS['jlg_test_scripts']['inline'] = [];
        }

        $GLOBALS['jlg_test_scripts']['registered'][$handle] = [
            'src'       => $src,
            'deps'      => $deps,
            'ver'       => $ver,
            'in_footer' => $in_footer,
        ];

        return true;
    }
}

if (!function_exists('wp_script_is')) {
    function wp_script_is($handle, $list = 'enqueued') {
        $scripts = $GLOBALS['jlg_test_scripts'] ?? [
            'registered' => [],
            'enqueued'   => [],
            'localized'  => [],
        ];

        if ($list === 'registered') {
            return array_key_exists($handle, $scripts['registered']);
        }

        if ($list === 'enqueued') {
            return array_key_exists($handle, $scripts['enqueued']);
        }

        return false;
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle) {
        if (!isset($GLOBALS['jlg_test_scripts'])) {
            $GLOBALS['jlg_test_scripts'] = [
                'registered' => [],
                'enqueued'   => [],
                'localized'  => [],
                'inline'     => [],
            ];
        }

        if (!isset($GLOBALS['jlg_test_scripts']['inline'])) {
            $GLOBALS['jlg_test_scripts']['inline'] = [];
        }

        $GLOBALS['jlg_test_scripts']['enqueued'][$handle] = true;

        return true;
    }
}

if (!function_exists('wp_localize_script')) {
    function wp_localize_script($handle, $object_name, $l10n) {
        if (!isset($GLOBALS['jlg_test_scripts'])) {
            $GLOBALS['jlg_test_scripts'] = [
                'registered' => [],
                'enqueued'   => [],
                'localized'  => [],
            ];
        }

        if (!isset($GLOBALS['jlg_test_scripts']['localized'][$handle])) {
            $GLOBALS['jlg_test_scripts']['localized'][$handle] = [];
        }

        $GLOBALS['jlg_test_scripts']['localized'][$handle][$object_name] = $l10n;

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
    function wp_strip_all_tags($string, $remove_breaks = false) {
        if (!is_string($string)) {
            return '';
        }

        $string = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', $string);
        $string = strip_tags($string);

        if ($remove_breaks) {
            $string = preg_replace('/[\r\n\t ]+/', ' ', $string);
        }

        return trim((string) $string);
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

if (!function_exists('is_ssl')) {
    function is_ssl() {
        return false;
    }
}

if (!function_exists('admin_url')) {
    function admin_url($path = '') {
        $path = ltrim((string) $path, '/');

        return 'https://example.com/wp-admin/' . $path;
    }
}

if (!function_exists('wp_safe_redirect')) {
    function wp_safe_redirect($location, $status = 302, $x_redirect_by = 'WordPress') {
        if (!isset($GLOBALS['jlg_test_redirects'])) {
            $GLOBALS['jlg_test_redirects'] = [];
        }

        $GLOBALS['jlg_test_redirects'][] = [
            'location'      => (string) $location,
            'status'        => (int) $status,
            'x_redirect_by' => (string) $x_redirect_by,
        ];

        return true;
    }
}

if (!function_exists('wp_hash')) {
    function wp_hash($data) {
        return md5((string) $data);
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action = -1) {
        return 'nonce-' . (string) $action;
    }
}

if (!function_exists('wp_nonce_field')) {
    function wp_nonce_field($action = -1, $name = '_wpnonce', $referer = true, $echo = true) {
        $field = '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr(wp_create_nonce($action)) . '" />';

        if ($referer) {
            $field .= '<input type="hidden" name="_wp_http_referer" value="" />';
        }

        if ($echo) {
            echo $field;
        }

        return $field;
    }
}

if (!function_exists('get_terms')) {
    function get_terms($args = []) {
        unset($args);

        return [];
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

if (!class_exists('WP_Query')) {
    class WP_Query
    {
        public $args = [];
        public $posts = [];
        public $post_count = 0;
        public $current_post = -1;
        public $max_num_pages = 0;
        public $found_posts = 0;

        public function __construct($args = [])
        {
            $this->args = is_array($args) ? $args : [];

            $posts_store = $GLOBALS['jlg_test_posts'] ?? [];
            $filtered    = [];

            $post_in = [];
            if (isset($this->args['post__in'])) {
                $post_in = array_values(array_map('intval', (array) $this->args['post__in']));
            }

            foreach ($posts_store as $post) {
                if (!$post instanceof WP_Post) {
                    continue;
                }

                if (!empty($post_in) && !in_array((int) ($post->ID ?? 0), $post_in, true)) {
                    continue;
                }

                if (!$this->matches_post($post)) {
                    continue;
                }

                $filtered[] = $post;
            }

            if (!empty($post_in) && $this->should_preserve_post_in_order()) {
                $order_map = array_flip($post_in);
                usort($filtered, function ($a, $b) use ($order_map) {
                    $a_index = $order_map[(int) ($a->ID ?? 0)] ?? PHP_INT_MAX;
                    $b_index = $order_map[(int) ($b->ID ?? 0)] ?? PHP_INT_MAX;

                    return $a_index <=> $b_index;
                });
            } else {
                $this->apply_sorting($filtered);
            }

            $paged    = isset($this->args['paged']) ? max(1, (int) $this->args['paged']) : 1;
            $per_page = isset($this->args['posts_per_page']) ? (int) $this->args['posts_per_page'] : count($filtered);
            if ($per_page <= 0) {
                $per_page = max(1, count($filtered));
            }

            $this->found_posts   = count($filtered);
            $this->max_num_pages = $per_page > 0 ? (int) ceil($this->found_posts / $per_page) : 0;

            $offset = ($paged - 1) * $per_page;
            if ($offset < 0) {
                $offset = 0;
            }

            $this->posts      = array_slice($filtered, $offset, $per_page);
            $this->post_count = count($this->posts);

            if ($this->max_num_pages < 1 && $this->found_posts > 0) {
                $this->max_num_pages = 1;
            }
        }

        public function have_posts()
        {
            return ($this->current_post + 1) < $this->post_count;
        }

        public function the_post()
        {
            if (!$this->have_posts()) {
                return false;
            }

            $this->current_post++;
            $GLOBALS['post'] = $this->posts[$this->current_post];

            return $GLOBALS['post'];
        }

        private function should_preserve_post_in_order(): bool
        {
            if (!isset($this->args['post__in'])) {
                return false;
            }

            if (!isset($this->args['orderby'])) {
                return true;
            }

            $orderby = $this->args['orderby'];

            if (is_array($orderby)) {
                return false;
            }

            return $orderby === 'post__in';
        }

        private function matches_post(WP_Post $post): bool
        {
            if (!$this->matches_post_type($post)) {
                return false;
            }

            $post_statuses = isset($this->args['post_status']) ? (array) $this->args['post_status'] : ['publish'];
            if (!in_array($post->post_status ?? 'publish', $post_statuses, true)) {
                return false;
            }

            $meta_query = $this->args['meta_query'] ?? [];
            if (!$this->matches_meta_query($post->ID ?? 0, $meta_query)) {
                return false;
            }

            $tax_query = $this->args['tax_query'] ?? [];
            if (!$this->matches_tax_query($post->ID ?? 0, $tax_query)) {
                return false;
            }

            return true;
        }

        private function matches_post_type(WP_Post $post): bool
        {
            if (!isset($this->args['post_type'])) {
                return true;
            }

            $allowed = $this->args['post_type'];
            if (!is_array($allowed)) {
                $allowed = [$allowed];
            }

            $allowed = array_filter(array_map('strval', $allowed));
            if (empty($allowed)) {
                return true;
            }

            $post_type = (string) ($post->post_type ?? '');

            return in_array($post_type, $allowed, true);
        }

        private function matches_meta_query(int $post_id, $query): bool
        {
            if (empty($query)) {
                return true;
            }

            $clauses  = $query;
            $relation = 'AND';

            if (isset($clauses['relation'])) {
                $relation = strtoupper($clauses['relation']);
                unset($clauses['relation']);
            }

            $results = [];

            foreach ($clauses as $clause) {
                if (isset($clause['relation'])) {
                    $results[] = $this->matches_meta_query($post_id, $clause);
                    continue;
                }

                if (!is_array($clause)) {
                    continue;
                }

                $results[] = $this->evaluate_meta_clause($post_id, $clause);
            }

            if ($relation === 'OR') {
                return in_array(true, $results, true);
            }

            foreach ($results as $result) {
                if (!$result) {
                    return false;
                }
            }

            return true;
        }

        private function evaluate_meta_clause(int $post_id, array $clause): bool
        {
            $meta_key = $clause['key'] ?? '';
            $compare  = strtoupper($clause['compare'] ?? '=');
            $value    = $clause['value'] ?? '';
            $type     = strtoupper($clause['type'] ?? '');

            $meta_store = $GLOBALS['jlg_test_meta'][$post_id] ?? [];
            $meta_value = $meta_store[$meta_key] ?? null;

            if ($compare === 'NOT EXISTS') {
                return $meta_value === null;
            }

            if ($meta_value === null) {
                return false;
            }

            if (is_array($meta_value)) {
                $meta_value = implode(' ', array_map('strval', $meta_value));
            }

            if ($type === 'NUMERIC') {
                $meta_value = (float) $meta_value;
                $value      = is_array($value) ? array_map('floatval', $value) : (float) $value;
            }

            switch ($compare) {
                case '=':
                    return (string) $meta_value === (string) $value;
                case 'LIKE':
                    return stripos((string) $meta_value, (string) $value) !== false;
                case 'IN':
                    $value_list = is_array($value) ? $value : [$value];

                    return in_array((string) $meta_value, array_map('strval', $value_list), true);
                case '>=':
                    return $meta_value >= $value;
                case '<=':
                    return $meta_value <= $value;
                default:
                    return (string) $meta_value === (string) $value;
            }
        }

        private function matches_tax_query(int $post_id, $tax_query): bool
        {
            if (empty($tax_query)) {
                return true;
            }

            $clauses  = $tax_query;
            $relation = 'AND';

            if (isset($clauses['relation'])) {
                $relation = strtoupper($clauses['relation']);
                unset($clauses['relation']);
            }

            $results = [];

            foreach ($clauses as $clause) {
                $taxonomy = $clause['taxonomy'] ?? '';
                $field    = $clause['field'] ?? 'term_id';
                $terms    = isset($clause['terms']) ? (array) $clause['terms'] : [];

                $terms_store = $GLOBALS['jlg_test_terms'][$post_id][$taxonomy] ?? [];
                $matched     = false;

                foreach ($terms_store as $term) {
                    if (is_array($term)) {
                        $term = (object) $term;
                    }

                    if (!is_object($term)) {
                        continue;
                    }

                    if ($field === 'term_id' && in_array((int) ($term->term_id ?? 0), array_map('intval', $terms), true)) {
                        $matched = true;
                        break;
                    }

                    if ($field === 'slug' && in_array((string) ($term->slug ?? ''), array_map('strval', $terms), true)) {
                        $matched = true;
                        break;
                    }
                }

                $results[] = $matched;
            }

            if ($relation === 'OR') {
                return in_array(true, $results, true);
            }

            foreach ($results as $result) {
                if (!$result) {
                    return false;
                }
            }

            return true;
        }

        private function apply_sorting(array &$posts): void
        {
            $orderby = $this->args['orderby'] ?? 'date';
            $order   = strtoupper($this->args['order'] ?? 'DESC');

            if (is_array($orderby) && isset($orderby['meta_value_num']) && isset($this->args['meta_key'])) {
                $meta_key       = $this->args['meta_key'];
                $meta_direction = strtoupper($orderby['meta_value_num']);
                $date_direction = strtoupper($orderby['date'] ?? 'DESC');

                usort($posts, function ($a, $b) use ($meta_key, $meta_direction, $date_direction) {
                    $a_value = (float) ($GLOBALS['jlg_test_meta'][$a->ID][$meta_key] ?? 0);
                    $b_value = (float) ($GLOBALS['jlg_test_meta'][$b->ID][$meta_key] ?? 0);

                    if ($a_value === $b_value) {
                        $a_date = strtotime($a->post_date ?? 'now');
                        $b_date = strtotime($b->post_date ?? 'now');

                        return $date_direction === 'DESC' ? $b_date <=> $a_date : $a_date <=> $b_date;
                    }

                    return $meta_direction === 'DESC' ? $b_value <=> $a_value : $a_value <=> $b_value;
                });

                return;
            }

            if ($orderby === 'meta_value_num' && isset($this->args['meta_key'])) {
                $meta_key = $this->args['meta_key'];
                usort($posts, function ($a, $b) use ($meta_key, $order) {
                    $a_value = (float) ($GLOBALS['jlg_test_meta'][$a->ID][$meta_key] ?? 0);
                    $b_value = (float) ($GLOBALS['jlg_test_meta'][$b->ID][$meta_key] ?? 0);

                    if ($a_value === $b_value) {
                        return 0;
                    }

                    return $order === 'ASC' ? $a_value <=> $b_value : $b_value <=> $a_value;
                });

                return;
            }

            if ($orderby === 'meta_value' && isset($this->args['meta_key'])) {
                $meta_key = $this->args['meta_key'];
                usort($posts, function ($a, $b) use ($meta_key, $order) {
                    $a_value = (string) ($GLOBALS['jlg_test_meta'][$a->ID][$meta_key] ?? '');
                    $b_value = (string) ($GLOBALS['jlg_test_meta'][$b->ID][$meta_key] ?? '');

                    return $order === 'ASC' ? strcasecmp($a_value, $b_value) : strcasecmp($b_value, $a_value);
                });

                return;
            }

            if ($orderby === 'title') {
                usort($posts, function ($a, $b) use ($order) {
                    $a_title = strtolower($a->post_title ?? '');
                    $b_title = strtolower($b->post_title ?? '');

                    return $order === 'ASC' ? $a_title <=> $b_title : $b_title <=> $a_title;
                });

                return;
            }

            usort($posts, function ($a, $b) use ($order) {
                $a_date = strtotime($a->post_date ?? 'now');
                $b_date = strtotime($b->post_date ?? 'now');

                return $order === 'ASC' ? $a_date <=> $b_date : $b_date <=> $a_date;
            });
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

if (!function_exists('get_post_modified_time')) {
    function get_post_modified_time($format = 'U', $gmt = false, $post = null, $translate = true)
    {
        unset($translate);

        $post_id = 0;
        if ($post instanceof WP_Post) {
            $post_id = (int) ($post->ID ?? 0);
        } elseif (is_numeric($post)) {
            $post_id = (int) $post;
        }

        if ($post_id <= 0) {
            return false;
        }

        $store = $GLOBALS['jlg_test_post_modified'] ?? [];
        $entry = $store[$post_id] ?? null;

        if (!is_array($entry)) {
            return false;
        }

        $timestamp = $gmt ? ($entry['gmt'] ?? null) : ($entry['local'] ?? null);

        if (!is_int($timestamp)) {
            return false;
        }

        if ($format === 'U') {
            return $timestamp;
        }

        return gmdate($format, $timestamp);
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink($post = 0)
    {
        if ($post instanceof WP_Post) {
            $post_id = (int) ($post->ID ?? 0);
        } else {
            $post_id = is_numeric($post) ? (int) $post : 0;
        }

        if ($post_id <= 0) {
            return 'https://example.com/';
        }

        $permalinks = $GLOBALS['jlg_test_permalinks'] ?? [];

        if (isset($permalinks[$post_id]) && is_string($permalinks[$post_id])) {
            return $permalinks[$post_id];
        }

        return 'https://example.com/?p=' . $post_id;
    }
}

if (!function_exists('get_queried_object')) {
    function get_queried_object()
    {
        $post_id = isset($GLOBALS['jlg_test_current_post_id']) ? (int) $GLOBALS['jlg_test_current_post_id'] : 0;
        $posts = $GLOBALS['jlg_test_posts'] ?? [];

        return ($post_id > 0 && isset($posts[$post_id])) ? $posts[$post_id] : null;
    }
}

if (!function_exists('get_queried_object_id')) {
    function get_queried_object_id()
    {
        return isset($GLOBALS['jlg_test_current_post_id']) ? (int) $GLOBALS['jlg_test_current_post_id'] : 0;
    }
}

if (!function_exists('metadata_exists')) {
    function metadata_exists($meta_type, $object_id, $meta_key)
    {
        if ($meta_type !== 'post') {
            return false;
        }

        $meta = $GLOBALS['jlg_test_meta'] ?? [];
        $object_id = (int) $object_id;

        return isset($meta[$object_id]) && array_key_exists($meta_key, $meta[$object_id]);
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

if (!function_exists('get_post_modified_time')) {
    function get_post_modified_time($d = 'U', $gmt = false, $post = null, $translate = true)
    {
        unset($translate);

        if ($post === null) {
            $post = get_post();
        }

        if (!$post instanceof WP_Post) {
            return false;
        }

        $field = $gmt ? 'post_modified_gmt' : 'post_modified';
        $value = isset($post->$field) ? (string) $post->$field : '';

        if ($value === '') {
            return false;
        }

        try {
            $timezone = $gmt ? new DateTimeZone('UTC') : wp_timezone();
            $date     = new DateTimeImmutable($value, $timezone);
        } catch (Exception $exception) {
            return false;
        }

        if ($d === 'U' || $d === 'G') {
            return $date->getTimestamp();
        }

        $format = is_string($d) && $d !== '' ? $d : 'Y-m-d H:i:s';

        return $date->format($format);
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

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data, $options = 0, $depth = 512) {
        $encoded = json_encode($data, $options, $depth);

        if ($encoded === false) {
            return 'null';
        }

        return $encoded;
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

if (!class_exists('WP_Widget')) {
    /**
     * Basic stand-in for WP_Widget so widget classes can load in tests.
     */
    #[\AllowDynamicProperties]
    class WP_Widget
    {
        public function __construct($id_base = '', $name = '', $widget_options = [], $control_options = [])
        {
            unset($id_base, $name, $widget_options, $control_options);
        }

        public function widget($args, $instance)
        {
            unset($args, $instance);
        }

        public function update($new_instance, $old_instance)
        {
            unset($old_instance);

            return $new_instance;
        }

        public function form($instance)
        {
            unset($instance);
        }
    }
}

require_once __DIR__ . '/../includes/Helpers.php';
require_once __DIR__ . '/../includes/DynamicCss.php';
require_once __DIR__ . '/../includes/Admin/Settings.php';
require_once __DIR__ . '/../includes/Admin/Platforms.php';
require_once __DIR__ . '/../includes/Frontend.php';
require_once __DIR__ . '/../includes/Utils/Validator.php';
require_once __DIR__ . '/../includes/Admin/Ajax.php';
require_once __DIR__ . '/../includes/Shortcodes/SummaryDisplay.php';

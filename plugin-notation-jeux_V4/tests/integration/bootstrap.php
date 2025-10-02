<?php
$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
}
if (!is_dir($_tests_dir)) {
    fwrite(STDERR, "Could not find the WordPress test library in {$_tests_dir}.\n");
    exit(1);
}
require_once $_tests_dir . '/includes/functions.php';

tests_add_filter('muplugins_loaded', static function () {
    require dirname(__DIR__, 2) . '/plugin-notation-jeux.php';
});

tests_add_filter('setup_theme', static function () {
    // Ensure the plugin's constants are defined consistently during tests.
    if (!defined('WP_DEBUG')) {
        define('WP_DEBUG', true);
    }
});

require $_tests_dir . '/includes/bootstrap.php';

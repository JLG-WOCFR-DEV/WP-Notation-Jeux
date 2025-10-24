<?php
\define('WP_UNINSTALL_PLUGIN', true);

// Minimal WordPress stubs required by uninstall.php.
if (!function_exists('wp_clear_scheduled_hook')) {
    function wp_clear_scheduled_hook($hook) {
        $GLOBALS['jlg_test_hooks'][] = $hook;
    }
}

if (!function_exists('delete_option')) {
    function delete_option($option) {
        $GLOBALS['jlg_test_deleted_options'][] = $option;

        return true;
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        unset($option);

        return $default;
    }
}

if (!function_exists('get_users')) {
    function get_users($args = array()) {
        unset($args);

        return array(
            array('ID' => 42),
        );
    }
}

if (!function_exists('delete_user_meta')) {
    function delete_user_meta($user_id, $meta_key) {
        $GLOBALS['jlg_test_deleted_user_meta'][] = array($user_id, $meta_key);

        return true;
    }
}

if (!function_exists('delete_metadata')) {
    function delete_metadata($type, $object_id, $meta_key, $meta_value = '', $delete_all = false) {
        $GLOBALS['jlg_test_deleted_metadata'][] = array($type, $object_id, $meta_key, $meta_value, $delete_all);

        return true;
    }
}

// Basic $wpdb stand-in used by uninstall cleanup.
if (!isset($GLOBALS['wpdb'])) {
    $GLOBALS['wpdb'] = new class {
        public $postmeta = 'wp_postmeta';
        public $options  = 'wp_options';

        public function delete($table, $where) {
            $GLOBALS['jlg_test_deleted_rows'][] = array($table, $where);

            return 1;
        }

        public function query($sql) {
            $GLOBALS['jlg_test_queries'][] = $sql;

            return 1;
        }
    };
}

require __DIR__ . '/../../uninstall.php';

fwrite(STDOUT, "uninstall-success\n");

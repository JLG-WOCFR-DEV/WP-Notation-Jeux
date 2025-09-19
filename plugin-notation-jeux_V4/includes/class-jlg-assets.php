<?php
if (!defined('ABSPATH')) exit;

class JLG_Assets {
    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function enqueue_admin_assets($hook_suffix) {
        if ($hook_suffix !== 'toplevel_page_notation_jlg_settings') {
            return;
        }

        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'reglages';
        if ($active_tab !== 'plateformes') {
            return;
        }

        wp_enqueue_script('jquery-ui-sortable');

        $handle = 'jlg-platforms-order';
        $src = JLG_NOTATION_PLUGIN_URL . 'assets/js/jlg-platforms-order.js';
        $deps = ['jquery', 'jquery-ui-sortable'];
        $version = defined('JLG_NOTATION_VERSION') ? JLG_NOTATION_VERSION : false;

        wp_register_script($handle, $src, $deps, $version, true);

        wp_localize_script($handle, 'jlgPlatformsOrder', [
            'listSelector' => '#platforms-list',
            'positionSelector' => '.jlg-platform-position',
            'handleSelector' => '.jlg-sort-handle',
            'rowSelector' => 'tr[data-key]',
            'inputSelector' => 'input[name="platform_order[]"]',
            'placeholderClass' => 'jlg-sortable-placeholder',
        ]);

        wp_enqueue_script($handle);
    }
}

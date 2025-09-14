<?php
if (!defined('ABSPATH')) exit;

class JLG_Admin_Core {
    private static $instance = null;
    private $menu;
    private $metaboxes;
    private $settings;
    private $platforms;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_admin_dependencies();
        $this->init_admin_components();
    }

    private function load_admin_dependencies() {
        $admin_files = [
            'includes/admin/class-jlg-admin-menu.php',
            'includes/admin/class-jlg-admin-metaboxes.php',
            'includes/admin/class-jlg-admin-settings.php',
            'includes/admin/class-jlg-admin-ajax.php',
            'includes/admin/class-jlg-admin-platforms.php'  // Ajout de la classe Platforms
        ];

        foreach ($admin_files as $file) {
            $path = JLG_NOTATION_PLUGIN_DIR . $file;
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }

    private function init_admin_components() {
        if (class_exists('JLG_Admin_Menu')) {
            $this->menu = new JLG_Admin_Menu();
        }
        
        if (class_exists('JLG_Admin_Metaboxes')) {
            $this->metaboxes = new JLG_Admin_Metaboxes();
        }
        
        if (class_exists('JLG_Admin_Settings')) {
            $this->settings = new JLG_Admin_Settings();
        }

        if (class_exists('JLG_Admin_Ajax')) {
            new JLG_Admin_Ajax();
        }
        
        // Initialiser la classe Platforms en mode singleton
        if (class_exists('JLG_Admin_Platforms')) {
            $this->platforms = JLG_Admin_Platforms::get_instance();
        }
    }

    public function get_component($component_name) {
        switch ($component_name) {
            case 'menu': return $this->menu;
            case 'metaboxes': return $this->metaboxes;
            case 'settings': return $this->settings;
            case 'platforms': return $this->platforms;
            default: return null;
        }
    }
}
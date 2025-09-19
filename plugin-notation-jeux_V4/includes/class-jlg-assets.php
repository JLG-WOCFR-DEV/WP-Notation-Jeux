<?php
if (!defined('ABSPATH')) exit;

class JLG_Assets {
    /**
     * Plugin version used for asset versioning.
     *
     * @var string|bool
     */
    private $version;

    public function __construct($version = null) {
        if ($version !== null) {
            $this->version = $version;
        } elseif (defined('JLG_NOTATION_VERSION')) {
            $this->version = JLG_NOTATION_VERSION;
        } else {
            $this->version = false;
        }
    }

    /**
     * Enregistre les assets front-end du plugin.
     */
    public function enqueue_frontend() {
        $this->register_frontend_assets();
    }

    /**
     * Enregistre les assets admin du plugin.
     */
    public function enqueue_admin() {
        $this->register_admin_assets();
    }

    /**
     * S'assure que la feuille de style principale est en file.
     */
    public function enqueue_frontend_style() {
        if (!wp_style_is('jlg-frontend', 'registered')) {
            $this->register_frontend_assets();
        }

        wp_enqueue_style('jlg-frontend');
    }

    /**
     * Ajoute le script de notation utilisateur et le localise.
     *
     * @param array $localized_data
     */
    public function enqueue_user_rating(array $localized_data = []) {
        if (!wp_script_is('jlg-user-rating', 'registered')) {
            $this->register_frontend_assets();
        }

        wp_enqueue_script('jlg-user-rating');

        if (!empty($localized_data)) {
            wp_localize_script('jlg-user-rating', 'jlg_rating_ajax', $localized_data);
        }
    }

    /**
     * Ajoute le script de changement de langue des taglines.
     */
    public function enqueue_tagline_switcher() {
        if (!wp_script_is('jlg-tagline-switcher', 'registered')) {
            $this->register_frontend_assets();
        }

        wp_enqueue_script('jlg-tagline-switcher');
    }

    /**
     * Ajoute le script des animations front-end.
     */
    public function enqueue_animations() {
        if (!wp_script_is('jlg-animations', 'registered')) {
            $this->register_frontend_assets();
        }

        wp_enqueue_script('jlg-animations');
    }

    /**
     * Ajoute le script AJAX côté admin et le localise.
     *
     * @param array $localized_data
     */
    public function enqueue_admin_ajax(array $localized_data = []) {
        if (!wp_script_is('jlg-admin-api', 'registered')) {
            $this->register_admin_assets();
        }

        wp_enqueue_script('jlg-admin-api');

        if (!empty($localized_data)) {
            wp_localize_script('jlg-admin-api', 'jlg_admin_ajax', $localized_data);
        }
    }

    /**
     * Enregistre les assets front-end.
     */
    private function register_frontend_assets() {
        $version = $this->get_version();

        wp_register_style(
            'jlg-frontend',
            JLG_NOTATION_PLUGIN_URL . 'assets/css/jlg-frontend.css',
            [],
            $version
        );

        wp_register_script(
            'jlg-user-rating',
            JLG_NOTATION_PLUGIN_URL . 'assets/js/user-rating.js',
            ['jquery'],
            $version,
            true
        );

        wp_register_script(
            'jlg-tagline-switcher',
            JLG_NOTATION_PLUGIN_URL . 'assets/js/tagline-switcher.js',
            ['jquery'],
            $version,
            true
        );

        wp_register_script(
            'jlg-animations',
            JLG_NOTATION_PLUGIN_URL . 'assets/js/jlg-animations.js',
            [],
            $version,
            true
        );
    }

    /**
     * Enregistre les assets admin.
     */
    private function register_admin_assets() {
        $version = $this->get_version();

        wp_register_script(
            'jlg-admin-api',
            JLG_NOTATION_PLUGIN_URL . 'assets/js/jlg-admin-api.js',
            ['jquery'],
            $version,
            true
        );
    }

    /**
     * Retourne la version utilisée pour les assets.
     *
     * @return string|bool
     */
    private function get_version() {
        return $this->version;
    }
}

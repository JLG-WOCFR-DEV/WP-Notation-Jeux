<?php
if (!defined('ABSPATH')) exit;

class JLG_Admin_Ajax {
    
    public function __construct() {
        add_action('wp_ajax_jlg_search_rawg_games', [$this, 'handle_rawg_search']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_ajax_assets']);
    }

    public function enqueue_admin_ajax_assets($hook_suffix) {
        $allowed_hooks = [
            'post.php',
            'post-new.php',
            'toplevel_page_notation_jlg_settings',
        ];

        if (!in_array($hook_suffix, $allowed_hooks, true)) {
            return;
        }

        $script_handle = 'jlg-admin-api';
        $script_url = JLG_NOTATION_PLUGIN_URL . 'assets/js/jlg-admin-api.js';
        $version = defined('JLG_NOTATION_VERSION') ? JLG_NOTATION_VERSION : false;

        wp_register_script($script_handle, $script_url, ['jquery'], $version, true);

        wp_localize_script($script_handle, 'jlg_admin_ajax', [
            'nonce' => wp_create_nonce('jlg_admin_ajax_nonce'),
            'ajax_url' => admin_url('admin-ajax.php'),
        ]);

        wp_enqueue_script($script_handle);
    }

    public function handle_rawg_search() {
        check_ajax_referer('jlg_admin_ajax_nonce', 'nonce');

        // Sécurité basique
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permissions insuffisantes.', 403);
        }

        $search_term = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
        
        if (empty($search_term)) {
            wp_send_json_error('Terme de recherche vide.', 400);
        }

        // Simulation de réponse (remplacez par vraie API si vous avez une clé)
        $mock_games = [
            [
                'name' => $search_term . ' - Résultat simulé',
                'release_date' => '2024-01-15',
                'developers' => 'Studio Exemple',
                'publishers' => 'Éditeur Exemple',
                'platforms' => ['PC', 'PlayStation 5'],
                'pegi' => 'PEGI 12',
            ]
        ];

        $normalized_games = array_map(function($game) {
            if (!empty($game['release_date'])) {
                $sanitized_date = JLG_Validator::sanitize_date($game['release_date']);
                $game['release_date'] = $sanitized_date !== null ? $sanitized_date : '';
            }

            if (!empty($game['pegi'])) {
                $sanitized_pegi = JLG_Validator::sanitize_pegi($game['pegi']);
                $game['pegi'] = $sanitized_pegi !== null ? $sanitized_pegi : '';
            }

            return $game;
        }, $mock_games);

        wp_send_json_success([
            'games' => $normalized_games,
            'message' => 'Recherche simulée. Configurez une vraie clé API RAWG dans les réglages pour des résultats réels.'
        ]);
    }
}
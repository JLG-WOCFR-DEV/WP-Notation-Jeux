<?php
if (!defined('ABSPATH')) exit;

class JLG_Admin_Ajax {
    
    public function __construct() {
        add_action('wp_ajax_jlg_search_rawg_games', [$this, 'handle_rawg_search']);
    }

    public function handle_rawg_search() {
        // Sécurité basique
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permissions insuffisantes.');
        }

        $search_term = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        
        if (empty($search_term)) {
            wp_send_json_error('Terme de recherche vide.');
        }

        // Simulation de réponse (remplacez par vraie API si vous avez une clé)
        $mock_games = [
            [
                'name' => $search_term . ' - Résultat simulé',
                'release_date' => '2024-01-15',
                'developers' => 'Studio Exemple',
                'publishers' => 'Éditeur Exemple',
                'platforms' => ['PC', 'PlayStation 5']
            ]
        ];

        wp_send_json_success([
            'games' => $mock_games,
            'message' => 'Recherche simulée. Configurez une vraie clé API RAWG dans les réglages pour des résultats réels.'
        ]);
    }
}
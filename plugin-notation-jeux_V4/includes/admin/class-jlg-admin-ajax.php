<?php
if (!defined('ABSPATH')) exit;

class JLG_Admin_Ajax {

    /**
     * Gestionnaire centralisé des assets.
     *
     * @var JLG_Assets
     */
    private $assets;

    public function __construct(JLG_Assets $assets) {
        $this->assets = $assets;
        add_action('wp_ajax_jlg_search_rawg_games', [$this, 'handle_rawg_search']);
        add_action('wp_ajax_nopriv_jlg_search_rawg_games', [$this, 'handle_rawg_search']);
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

        $this->assets->enqueue_admin_ajax([
            'nonce' => wp_create_nonce('jlg_admin_ajax_nonce'),
            'ajax_url' => admin_url('admin-ajax.php'),
        ]);
    }

    public function handle_rawg_search() {
        check_ajax_referer('jlg_admin_ajax_nonce', 'nonce');

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
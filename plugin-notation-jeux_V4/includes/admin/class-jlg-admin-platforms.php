<?php
/**
 * Gestion des plateformes/consoles
 * 
 * @package JLG_Notation
 * @version 5.0
 */

if (!defined('ABSPATH')) exit;

class JLG_Admin_Platforms {
    
    private $option_name = 'jlg_platforms_list';
    private static $debug_messages = [];
    private static $instance = null;
    
    /**
     * Singleton pattern pour s'assurer qu'une seule instance existe
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        // Important: Hook sur admin_init pour traiter les actions POST
        add_action('admin_init', [$this, 'handle_platform_actions'], 5);
        self::$debug_messages[] = "✅ Classe JLG_Admin_Platforms initialisée";
    }
    
    /**
     * Obtenir la liste des plateformes
     */
    public function get_platforms() {
        $saved_platforms = get_option($this->option_name, []);
        
        // Plateformes par défaut
        $default_platforms = [
            'pc' => ['name' => 'PC', 'icon' => '💻', 'order' => 1],
            'playstation-5' => ['name' => 'PlayStation 5', 'icon' => '🎮', 'order' => 2],
            'xbox-series-x' => ['name' => 'Xbox Series S/X', 'icon' => '🎮', 'order' => 3],
            'nintendo-switch' => ['name' => 'Nintendo Switch', 'icon' => '🎮', 'order' => 4],
            'playstation-4' => ['name' => 'PlayStation 4', 'icon' => '🎮', 'order' => 5],
            'xbox-one' => ['name' => 'Xbox One', 'icon' => '🎮', 'order' => 6],
            'steam-deck' => ['name' => 'Steam Deck', 'icon' => '🎮', 'order' => 7],
        ];
        
        // Fusionner avec les plateformes sauvegardées
        $platforms = array_merge($default_platforms, $saved_platforms);
        
        // Trier par ordre
        uasort($platforms, function($a, $b) {
            return ($a['order'] ?? 999) - ($b['order'] ?? 999);
        });
        
        return $platforms;
    }
    
    /**
     * Obtenir uniquement les noms des plateformes (pour les metaboxes)
     */
    public function get_platform_names() {
        $platforms = $this->get_platforms();
        $names = [];
        foreach ($platforms as $key => $platform) {
            $names[$key] = $platform['name'];
        }
        return $names;
    }
    
    /**
     * Gérer les actions (ajout, suppression, modification)
     */
    public function handle_platform_actions() {
        // Ne traiter que sur la page des plateformes
        if (!isset($_GET['page']) || $_GET['page'] !== 'notation_jlg_settings') {
            return;
        }
        
        if (!isset($_GET['tab']) || $_GET['tab'] !== 'plateformes') {
            return;
        }
        
        // Debug : Enregistrer l'appel de la méthode
        self::$debug_messages[] = "🔄 handle_platform_actions() appelé";
        self::$debug_messages[] = "📍 Page actuelle : " . ($_GET['page'] ?? 'non définie');
        self::$debug_messages[] = "📍 Onglet actuel : " . ($_GET['tab'] ?? 'non défini');
        
        // Debug : Vérifier les données POST
        if (!empty($_POST)) {
            self::$debug_messages[] = "📨 Données POST reçues : " . json_encode(array_keys($_POST));
        }
        
        if (!isset($_POST['jlg_platform_action'])) {
            if (!empty($_POST)) {
                self::$debug_messages[] = "❌ jlg_platform_action non trouvé dans POST";
            }
            return;
        }
        
        self::$debug_messages[] = "✅ Action détectée : " . $_POST['jlg_platform_action'];
        
        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            self::$debug_messages[] = "❌ Permissions insuffisantes";
            wp_die('Permissions insuffisantes');
        }
        self::$debug_messages[] = "✅ Permissions OK";
        
        // Vérifier le nonce
        if (!isset($_POST['jlg_platform_nonce'])) {
            self::$debug_messages[] = "❌ Nonce non trouvé";
            return;
        }
        
        if (!wp_verify_nonce($_POST['jlg_platform_nonce'], 'jlg_platform_action')) {
            self::$debug_messages[] = "❌ Nonce invalide : " . $_POST['jlg_platform_nonce'];
            wp_die('Erreur de sécurité');
        }
        self::$debug_messages[] = "✅ Nonce valide";
        
        $action = sanitize_text_field($_POST['jlg_platform_action']);
        $platforms = get_option($this->option_name, []);
        self::$debug_messages[] = "📦 Plateformes actuelles dans la DB : " . count($platforms) . " personnalisées";
        
        $success = false;
        $message = '';
        
        switch ($action) {
            case 'add':
                $result = $this->add_platform($platforms);
                $success = $result['success'];
                $message = $result['message'];
                break;
                
            case 'delete':
                $result = $this->delete_platform($platforms);
                $success = $result['success'];
                $message = $result['message'];
                break;
                
            case 'update_order':
                $result = $this->update_platform_order($platforms);
                $success = $result['success'];
                $message = $result['message'];
                break;
                
            case 'reset':
                delete_option($this->option_name);
                $success = true;
                $message = 'Plateformes réinitialisées avec succès !';
                self::$debug_messages[] = "🔄 Option supprimée de la DB";
                break;
        }
        
        // Stocker le message pour l'affichage
        if ($success) {
            set_transient('jlg_platforms_message', ['type' => 'success', 'message' => $message], 30);
            self::$debug_messages[] = "✅ Action réussie : " . $message;
        } else {
            set_transient('jlg_platforms_message', ['type' => 'error', 'message' => $message], 30);
            self::$debug_messages[] = "❌ Erreur : " . $message;
        }
        
        // Stocker les messages de debug dans un transient
        set_transient('jlg_platforms_debug', self::$debug_messages, 60);
    }
    
    /**
     * Ajouter une nouvelle plateforme
     */
    private function add_platform(&$platforms) {
        self::$debug_messages[] = "🎯 Tentative d'ajout de plateforme";
        
        if (empty($_POST['new_platform_name'])) {
            self::$debug_messages[] = "❌ Nom de plateforme vide";
            return ['success' => false, 'message' => 'Le nom de la plateforme est requis.'];
        }
        
        $name = sanitize_text_field($_POST['new_platform_name']);
        $icon = sanitize_text_field($_POST['new_platform_icon'] ?? '🎮');
        
        self::$debug_messages[] = "📝 Nom : $name, Icône : $icon";
        
        // Générer une clé unique
        $key = sanitize_title($name);
        if (empty($key)) {
            self::$debug_messages[] = "❌ Clé générée vide pour le nom : $name";
            return ['success' => false, 'message' => 'Nom de plateforme invalide.'];
        }
        
        // Ajouter un suffixe si la clé existe déjà
        $original_key = $key;
        $suffix = 1;
        $all_platforms = $this->get_platforms();
        while (isset($all_platforms[$key])) {
            $key = $original_key . '-' . $suffix;
            $suffix++;
        }
        
        self::$debug_messages[] = "🔑 Clé générée : $key";
        
        // Trouver l'ordre maximum
        $max_order = 0;
        foreach ($all_platforms as $platform) {
            $max_order = max($max_order, $platform['order'] ?? 0);
        }
        
        // Ajouter la nouvelle plateforme
        $platforms[$key] = [
            'name' => $name,
            'icon' => $icon,
            'order' => $max_order + 1,
            'custom' => true // Marquer comme personnalisée
        ];
        
        self::$debug_messages[] = "💾 Tentative de sauvegarde dans la DB";
        self::$debug_messages[] = "📊 Données à sauvegarder : " . json_encode($platforms[$key]);
        
        $result = update_option($this->option_name, $platforms);
        
        if ($result || get_option($this->option_name) !== false) {
            self::$debug_messages[] = "✅ Plateforme ajoutée et sauvegardée";
            
            // Vérification que la sauvegarde a bien fonctionné
            $saved = get_option($this->option_name);
            if (isset($saved[$key])) {
                self::$debug_messages[] = "✅ Vérification : plateforme bien présente dans la DB";
            } else {
                self::$debug_messages[] = "⚠️ La plateforme a été sauvegardée mais n'apparaît pas dans la vérification";
            }
            
            return ['success' => true, 'message' => "Plateforme '$name' ajoutée avec succès !"];
        } else {
            self::$debug_messages[] = "❌ Échec de la sauvegarde dans la DB";
            return ['success' => false, 'message' => "Erreur lors de la sauvegarde."];
        }
    }
    
    /**
     * Supprimer une plateforme
     */
    private function delete_platform(&$platforms) {
        self::$debug_messages[] = "🗑️ Tentative de suppression de plateforme";
        
        if (empty($_POST['platform_key'])) {
            self::$debug_messages[] = "❌ Clé de plateforme manquante";
            return ['success' => false, 'message' => 'Clé de plateforme manquante.'];
        }
        
        $key = sanitize_text_field($_POST['platform_key']);
        self::$debug_messages[] = "🔑 Clé à supprimer : $key";
        
        // Vérifier si c'est une plateforme personnalisée
        if (!isset($platforms[$key])) {
            self::$debug_messages[] = "❌ Plateforme non trouvée ou non personnalisée";
            return ['success' => false, 'message' => 'Cette plateforme ne peut pas être supprimée.'];
        }
        
        $platform_name = $platforms[$key]['name'] ?? 'Inconnue';
        unset($platforms[$key]);
        
        $result = update_option($this->option_name, $platforms);
        
        if ($result || get_option($this->option_name) !== false) {
            self::$debug_messages[] = "✅ Plateforme '$platform_name' supprimée";
            return ['success' => true, 'message' => "Plateforme '$platform_name' supprimée avec succès !"];
        } else {
            self::$debug_messages[] = "❌ Échec de la suppression";
            return ['success' => false, 'message' => 'Erreur lors de la suppression.'];
        }
    }
    
    /**
     * Mettre à jour l'ordre des plateformes
     */
    private function update_platform_order(&$platforms) {
        self::$debug_messages[] = "🔄 Mise à jour de l'ordre des plateformes";
        
        if (!isset($_POST['platform_order']) || !is_array($_POST['platform_order'])) {
            self::$debug_messages[] = "❌ Données d'ordre manquantes";
            return ['success' => false, 'message' => 'Données d\'ordre manquantes.'];
        }
        
        $all_platforms = $this->get_platforms();
        $updated_count = 0;
        
        foreach ($_POST['platform_order'] as $key => $order) {
            $key = sanitize_text_field($key);
            $order = intval($order);
            
            if (isset($all_platforms[$key])) {
                if (isset($platforms[$key])) {
                    // Plateforme personnalisée
                    $platforms[$key]['order'] = $order;
                    $updated_count++;
                } else {
                    // Plateforme par défaut - on doit la sauvegarder pour modifier son ordre
                    $platforms[$key] = $all_platforms[$key];
                    $platforms[$key]['order'] = $order;
                    $updated_count++;
                }
            }
        }
        
        self::$debug_messages[] = "📊 $updated_count plateformes mises à jour";
        
        $result = update_option($this->option_name, $platforms);
        
        if ($result || get_option($this->option_name) !== false) {
            self::$debug_messages[] = "✅ Ordre sauvegardé";
            return ['success' => true, 'message' => 'Ordre des plateformes mis à jour !'];
        } else {
            self::$debug_messages[] = "❌ Échec de la sauvegarde de l'ordre";
            return ['success' => false, 'message' => 'Erreur lors de la mise à jour.'];
        }
    }
    
    /**
     * Afficher la page de gestion
     */
    public function render_platforms_page() {
        // Récupérer et afficher le message s'il existe
        $message = get_transient('jlg_platforms_message');
        if ($message) {
            $class = $message['type'] === 'success' ? 'notice-success' : 'notice-error';
            echo '<div class="notice ' . $class . ' is-dismissible"><p>' . esc_html($message['message']) . '</p></div>';
            delete_transient('jlg_platforms_message');
        }
        
        $platforms = $this->get_platforms();
        ?>
        <div class="wrap">
            <h2>🎮 Gestion des Plateformes</h2>
            
            <!-- ZONE DE DEBUG AMÉLIORÉE -->
            <?php 
            $show_debug = isset($_GET['debug']) || !empty(get_option('notation_jlg_settings')['debug_mode_enabled']);
            if ($show_debug) :
                $debug_messages = get_transient('jlg_platforms_debug');
            ?>
            <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 5px;">
                <h3 style="margin-top: 0;">🐛 Mode Debug - Informations de diagnostic</h3>
                
                <?php if ($debug_messages && !empty($debug_messages)) : ?>
                <div style="background: #fff; padding: 10px; border-radius: 3px; font-family: monospace; font-size: 12px; max-height: 300px; overflow-y: auto;">
                    <?php foreach ($debug_messages as $msg): ?>
                        <div style="margin: 5px 0;"><?php echo $msg; ?></div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p>Aucun message de debug pour le moment. Essayez d'effectuer une action.</p>
                <?php endif; ?>
                
                <hr style="margin: 15px 0;">
                
                <details>
                    <summary style="cursor: pointer; font-weight: bold;">📊 Informations système</summary>
                    <ul style="font-family: monospace; font-size: 12px; margin-top: 10px;">
                        <li>PHP Version : <?php echo PHP_VERSION; ?></li>
                        <li>WordPress Version : <?php echo get_bloginfo('version'); ?></li>
                        <li>Page actuelle : <?php echo esc_html($_GET['page'] ?? 'non définie'); ?></li>
                        <li>Onglet actuel : <?php echo esc_html($_GET['tab'] ?? 'non défini'); ?></li>
                        <li>URL actuelle : <?php echo esc_url($_SERVER['REQUEST_URI']); ?></li>
                        <li>Méthode HTTP : <?php echo $_SERVER['REQUEST_METHOD']; ?></li>
                        <li>Plateformes sauvegardées : <?php echo count(get_option($this->option_name, [])); ?> personnalisées</li>
                        <li>Total plateformes : <?php echo count($platforms); ?></li>
                        <li>Hook admin_init exécuté : <?php echo did_action('admin_init'); ?> fois</li>
                        <li>Utilisateur peut gérer les options : <?php echo current_user_can('manage_options') ? 'Oui' : 'Non'; ?></li>
                    </ul>
                </details>
                
                <?php if (!empty($_POST)): ?>
                <details style="margin-top: 15px;">
                    <summary style="cursor: pointer; font-weight: bold;">📨 Données POST reçues</summary>
                    <pre style="background: #f5f5f5; padding: 10px; overflow: auto; margin-top: 10px; font-size: 11px;"><?php print_r($_POST); ?></pre>
                </details>
                <?php endif; ?>
                
                <p style="margin-top: 15px; margin-bottom: 0;">
                    <a href="<?php echo esc_url(add_query_arg('debug', '0')); ?>" class="button button-small">Masquer le debug</a>
                </p>
            </div>
            <?php 
            delete_transient('jlg_platforms_debug');
            else: 
            ?>
            <p style="text-align: right;">
                <a href="<?php echo esc_url(add_query_arg('debug', '1')); ?>" class="button button-small">🐛 Activer le mode debug</a>
            </p>
            <?php endif; ?>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                
                <!-- Liste des plateformes -->
                <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h3>Plateformes actuelles</h3>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('jlg_platform_action', 'jlg_platform_nonce'); ?>
                        <input type="hidden" name="jlg_platform_action" value="update_order">
                        
                        <table class="wp-list-table widefat striped">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">Ordre</th>
                                    <th style="width: 50px;">Icône</th>
                                    <th>Nom</th>
                                    <th style="width: 100px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="platforms-list">
                                <?php foreach ($platforms as $key => $platform): ?>
                                <tr data-key="<?php echo esc_attr($key); ?>">
                                    <td>
                                        <input type="number" 
                                               name="platform_order[<?php echo esc_attr($key); ?>]" 
                                               value="<?php echo esc_attr($platform['order'] ?? 999); ?>" 
                                               style="width: 50px;">
                                    </td>
                                    <td style="text-align: center; font-size: 20px;">
                                        <?php echo esc_html($platform['icon'] ?? '🎮'); ?>
                                    </td>
                                    <td>
                                        <strong><?php echo esc_html($platform['name']); ?></strong>
                                        <?php if (isset($platform['custom']) && $platform['custom']): ?>
                                            <span style="color: #666; font-size: 12px;">(Personnalisée)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (isset($platform['custom']) && $platform['custom']): ?>
                                            <button type="button" 
                                                    class="button button-small delete-platform" 
                                                    data-key="<?php echo esc_attr($key); ?>"
                                                    data-name="<?php echo esc_attr($platform['name']); ?>">
                                                ❌ Supprimer
                                            </button>
                                        <?php else: ?>
                                            <span style="color: #999;">Par défaut</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <p style="margin-top: 15px;">
                            <input type="submit" class="button button-primary" value="💾 Enregistrer l'ordre">
                        </p>
                    </form>
                </div>
                
                <!-- Formulaire d'ajout -->
                <div>
                    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                        <h3>➕ Ajouter une plateforme</h3>
                        
                        <form method="post" action="">
                            <?php wp_nonce_field('jlg_platform_action', 'jlg_platform_nonce'); ?>
                            <input type="hidden" name="jlg_platform_action" value="add">
                            
                            <table class="form-table">
                                <tr>
                                    <th><label for="new_platform_name">Nom de la plateforme <span style="color:red;">*</span></label></th>
                                    <td>
                                        <input type="text" 
                                               id="new_platform_name" 
                                               name="new_platform_name" 
                                               class="regular-text" 
                                               placeholder="Ex: PlayStation 6"
                                               required>
                                        <p class="description">Entrez le nom de la nouvelle plateforme</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="new_platform_icon">Icône</label></th>
                                    <td>
                                        <select id="new_platform_icon" name="new_platform_icon" style="font-size: 20px;">
                                            <option value="🎮" selected>🎮 Manette</option>
                                            <option value="💻">💻 PC</option>
                                            <option value="📱">📱 Mobile</option>
                                            <option value="🕹️">🕹️ Arcade</option>
                                            <option value="🎯">🎯 Cible</option>
                                            <option value="🎲">🎲 Dé</option>
                                            <option value="🖥️">🖥️ Écran</option>
                                            <option value="⌨️">⌨️ Clavier</option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                            
                            <p>
                                <input type="submit" name="submit" class="button button-primary" value="➕ Ajouter la plateforme">
                            </p>
                        </form>
                    </div>
                    
                    <!-- Actions supplémentaires -->
                    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <h3>⚙️ Actions</h3>
                        
                        <form method="post" action="" onsubmit="return confirm('Êtes-vous sûr de vouloir réinitialiser toutes les plateformes ?');">
                            <?php wp_nonce_field('jlg_platform_action', 'jlg_platform_nonce'); ?>
                            <input type="hidden" name="jlg_platform_action" value="reset">
                            
                            <p>
                                <input type="submit" class="button" value="🔄 Réinitialiser aux plateformes par défaut">
                            </p>
                            <p class="description">
                                Cette action supprimera toutes les plateformes personnalisées et restaurera les plateformes par défaut.
                            </p>
                        </form>
                    </div>
                    
                    <!-- Instructions -->
                    <div style="background: #f0f6fc; padding: 20px; border-radius: 8px; margin-top: 20px;">
                        <h3>💡 Instructions</h3>
                        <ul style="margin-left: 20px;">
                            <li>Les plateformes par défaut ne peuvent pas être supprimées</li>
                            <li>Vous pouvez réorganiser toutes les plateformes en changeant leur ordre</li>
                            <li>Les plateformes personnalisées peuvent être supprimées à tout moment</li>
                            <li>Les icônes ajoutent une touche visuelle dans l'interface admin</li>
                            <li>Après chaque action, rafraîchissez la page si nécessaire</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- JavaScript pour la suppression -->
        <script>
        jQuery(document).ready(function($) {
            $('.delete-platform').on('click', function() {
                var key = $(this).data('key');
                var name = $(this).data('name');
                
                if (confirm('Êtes-vous sûr de vouloir supprimer la plateforme "' + name + '" ?')) {
                    var form = $('<form>', {
                        method: 'POST',
                        action: ''
                    });
                    
                    form.append($('<input>', {
                        type: 'hidden',
                        name: 'jlg_platform_action',
                        value: 'delete'
                    }));
                    
                    form.append($('<input>', {
                        type: 'hidden',
                        name: 'platform_key',
                        value: key
                    }));
                    
                    form.append($('<input>', {
                        type: 'hidden',
                        name: 'jlg_platform_nonce',
                        value: '<?php echo wp_create_nonce('jlg_platform_action'); ?>'
                    }));
                    
                    $('body').append(form);
                    form.submit();
                }
            });
        });
        </script>
        <?php
    }
}

// Initialiser la classe en mode singleton et s'assurer que le hook admin_init est bien enregistré
add_action('plugins_loaded', function() {
    JLG_Admin_Platforms::get_instance();
});
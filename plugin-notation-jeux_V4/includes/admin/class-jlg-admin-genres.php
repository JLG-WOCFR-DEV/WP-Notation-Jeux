<?php
if (!defined('ABSPATH')) {
    exit;
}

class JLG_Admin_Genres {
    private static $instance = null;
    private $option_name = 'jlg_genres_list';

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        add_action('admin_init', [$this, 'handle_genre_actions'], 5);
        add_action('admin_init', [$this, 'handle_notice_dismissal'], 1);
        add_action('admin_notices', [$this, 'maybe_display_migration_notice']);
    }

    private function get_default_genre_definitions() {
        if (method_exists('JLG_Helpers', 'get_default_genre_definitions')) {
            return JLG_Helpers::get_default_genre_definitions();
        }

        return [];
    }

    private function get_stored_genre_data() {
        $stored = get_option($this->option_name, []);

        if (!is_array($stored)) {
            $stored = [];
        }

        $defaults = [
            'custom_genres' => [],
            'order' => [],
        ];

        return wp_parse_args($stored, $defaults);
    }

    private function ensure_storage_structure(&$data) {
        if (!isset($data['custom_genres']) || !is_array($data['custom_genres'])) {
            $data['custom_genres'] = [];
        }

        if (!isset($data['order']) || !is_array($data['order'])) {
            $data['order'] = [];
        }
    }

    public function get_genres() {
        $defaults = $this->get_default_genre_definitions();
        $stored = $this->get_stored_genre_data();
        $this->ensure_storage_structure($stored);

        $genres = [];

        foreach ($defaults as $slug => $genre) {
            if (!is_array($genre)) {
                continue;
            }

            $genres[$slug] = array_merge(
                [
                    'slug'   => $slug,
                    'name'   => isset($genre['name']) ? sanitize_text_field($genre['name']) : $slug,
                    'color'  => isset($genre['color']) ? sanitize_hex_color($genre['color']) : '#4b5563',
                    'badge'  => isset($genre['badge']) ? sanitize_text_field($genre['badge']) : '',
                    'order'  => isset($genre['order']) ? (int) $genre['order'] : PHP_INT_MAX,
                    'custom' => false,
                ],
                $genre
            );
        }

        foreach ($stored['custom_genres'] as $slug => $genre) {
            if (!is_array($genre)) {
                continue;
            }

            $slug = sanitize_title($slug);
            if ($slug === '') {
                continue;
            }

            $genres[$slug] = array_merge(
                [
                    'slug'   => $slug,
                    'name'   => isset($genre['name']) ? sanitize_text_field($genre['name']) : $slug,
                    'color'  => isset($genre['color']) ? sanitize_hex_color($genre['color']) : '#2563eb',
                    'badge'  => isset($genre['badge']) ? sanitize_text_field($genre['badge']) : '',
                    'order'  => isset($genre['order']) ? (int) $genre['order'] : PHP_INT_MAX,
                    'custom' => true,
                ],
                $genre
            );
        }

        $order_map = [];
        foreach ($genres as $slug => $genre) {
            $order_map[$slug] = isset($stored['order'][$slug])
                ? (int) $stored['order'][$slug]
                : (isset($genre['order']) ? (int) $genre['order'] : PHP_INT_MAX);
        }

        uasort($genres, function($a, $b) use ($order_map) {
            $order_a = isset($order_map[$a['slug']]) ? $order_map[$a['slug']] : PHP_INT_MAX;
            $order_b = isset($order_map[$b['slug']]) ? $order_map[$b['slug']] : PHP_INT_MAX;

            if ($order_a === $order_b) {
                return strcmp($a['name'], $b['name']);
            }

            return $order_a <=> $order_b;
        });

        return $genres;
    }

    public function get_genre_choices() {
        $genres = $this->get_genres();
        $choices = [];

        foreach ($genres as $slug => $genre) {
            $choices[$slug] = $genre['name'];
        }

        return $choices;
    }

    public function handle_genre_actions() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'notation_jlg_settings') {
            return;
        }

        if (!isset($_GET['tab']) || $_GET['tab'] !== 'genres') {
            return;
        }

        if (!isset($_POST['jlg_genre_action'])) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Permissions insuffisantes', 'notation-jlg'));
        }

        $action = sanitize_key(wp_unslash($_POST['jlg_genre_action']));
        $nonce  = isset($_POST['jlg_genre_nonce']) ? sanitize_text_field(wp_unslash($_POST['jlg_genre_nonce'])) : '';

        if (!wp_verify_nonce($nonce, 'jlg_genre_action')) {
            wp_die(esc_html__('La vérification de sécurité a échoué.', 'notation-jlg'));
        }

        $stored = $this->get_stored_genre_data();
        $this->ensure_storage_structure($stored);
        $message = ['type' => 'error', 'message' => esc_html__('Action inconnue.', 'notation-jlg')];

        switch ($action) {
            case 'add':
                $message = $this->add_genre($stored);
                break;
            case 'delete':
                $message = $this->delete_genre($stored);
                break;
            case 'update':
                $message = $this->update_genre($stored);
                break;
            case 'update_order':
                $message = $this->update_order($stored);
                break;
            case 'reset':
                $message = $this->reset_genres();
                break;
        }

        set_transient('jlg_genres_message', $message, 30);

        wp_safe_redirect(add_query_arg(['page' => 'notation_jlg_settings', 'tab' => 'genres'], admin_url('admin.php')));
        exit;
    }

    private function add_genre(&$stored) {
        $name  = isset($_POST['new_genre_name']) ? sanitize_text_field(wp_unslash($_POST['new_genre_name'])) : '';
        $slug  = isset($_POST['new_genre_slug']) ? sanitize_title(wp_unslash($_POST['new_genre_slug'])) : '';
        $color = isset($_POST['new_genre_color']) ? sanitize_hex_color(wp_unslash($_POST['new_genre_color'])) : '';
        $badge = isset($_POST['new_genre_badge']) ? sanitize_text_field(wp_unslash($_POST['new_genre_badge'])) : '';

        if ($name === '') {
            return ['type' => 'error', 'message' => esc_html__('Le nom du genre est requis.', 'notation-jlg')];
        }

        if ($slug === '') {
            $slug = sanitize_title($name);
        }

        if ($slug === '') {
            return ['type' => 'error', 'message' => esc_html__('Le slug du genre est invalide.', 'notation-jlg')];
        }

        $existing = $this->get_genres();
        if (isset($existing[$slug])) {
            return ['type' => 'error', 'message' => esc_html__('Ce slug de genre existe déjà.', 'notation-jlg')];
        }

        if ($color === '' || !preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = '#2563eb';
        }

        $stored['custom_genres'][$slug] = [
            'name'  => $name,
            'color' => $color,
            'badge' => $badge,
            'custom' => true,
        ];

        $max_order = 0;
        foreach ($stored['order'] as $value) {
            $max_order = max($max_order, (int) $value);
        }
        $stored['order'][$slug] = $max_order + 1;

        update_option($this->option_name, $stored);

        return ['type' => 'success', 'message' => esc_html__('Genre ajouté avec succès.', 'notation-jlg')];
    }

    private function delete_genre(&$stored) {
        $slug = isset($_POST['genre_slug']) ? sanitize_title(wp_unslash($_POST['genre_slug'])) : '';
        if ($slug === '') {
            return ['type' => 'error', 'message' => esc_html__('Genre introuvable.', 'notation-jlg')];
        }

        if (!isset($stored['custom_genres'][$slug])) {
            return ['type' => 'error', 'message' => esc_html__('Seuls les genres personnalisés peuvent être supprimés.', 'notation-jlg')];
        }

        unset($stored['custom_genres'][$slug]);
        unset($stored['order'][$slug]);
        update_option($this->option_name, $stored);

        return ['type' => 'success', 'message' => esc_html__('Genre supprimé avec succès.', 'notation-jlg')];
    }

    private function update_genre(&$stored) {
        $original_slug = isset($_POST['edit_genre_original_slug']) ? sanitize_title(wp_unslash($_POST['edit_genre_original_slug'])) : '';
        if ($original_slug === '') {
            return ['type' => 'error', 'message' => esc_html__('Genre introuvable.', 'notation-jlg')];
        }

        if (!isset($stored['custom_genres'][$original_slug])) {
            return ['type' => 'error', 'message' => esc_html__('Seuls les genres personnalisés peuvent être modifiés.', 'notation-jlg')];
        }

        $name  = isset($_POST['edit_genre_name']) ? sanitize_text_field(wp_unslash($_POST['edit_genre_name'])) : '';
        $slug  = isset($_POST['edit_genre_slug']) ? sanitize_title(wp_unslash($_POST['edit_genre_slug'])) : '';
        $color = isset($_POST['edit_genre_color']) ? sanitize_hex_color(wp_unslash($_POST['edit_genre_color'])) : '';
        $badge = isset($_POST['edit_genre_badge']) ? sanitize_text_field(wp_unslash($_POST['edit_genre_badge'])) : '';

        if ($name === '') {
            return ['type' => 'error', 'message' => esc_html__('Le nom du genre est requis.', 'notation-jlg')];
        }

        if ($slug === '') {
            $slug = sanitize_title($name);
        }

        if ($slug === '') {
            return ['type' => 'error', 'message' => esc_html__('Le slug du genre est invalide.', 'notation-jlg')];
        }

        $existing = $this->get_genres();
        if ($slug !== $original_slug && isset($existing[$slug])) {
            return ['type' => 'error', 'message' => esc_html__('Ce slug de genre existe déjà.', 'notation-jlg')];
        }

        if ($color === '' || !preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = '#2563eb';
        }

        $updated_data = [
            'name'  => $name,
            'color' => $color,
            'badge' => $badge,
            'custom' => true,
        ];

        if ($slug !== $original_slug) {
            $stored['custom_genres'][$slug] = $updated_data;
            unset($stored['custom_genres'][$original_slug]);

            if (isset($stored['order'][$original_slug])) {
                $stored['order'][$slug] = $stored['order'][$original_slug];
                unset($stored['order'][$original_slug]);
            }
        } else {
            $stored['custom_genres'][$slug] = array_merge($stored['custom_genres'][$slug], $updated_data);
        }

        update_option($this->option_name, $stored);

        return ['type' => 'success', 'message' => esc_html__('Genre mis à jour avec succès.', 'notation-jlg')];
    }

    private function update_order(&$stored) {
        if (!isset($_POST['genre_order']) || !is_array($_POST['genre_order'])) {
            return ['type' => 'error', 'message' => esc_html__('Aucun ordre reçu.', 'notation-jlg')];
        }

        $raw_order = wp_unslash($_POST['genre_order']);
        $new_order = [];

        foreach ($raw_order as $slug => $position) {
            $slug = sanitize_title($slug);
            if ($slug === '') {
                continue;
            }

            $new_order[$slug] = max(1, intval($position));
        }

        if (empty($new_order)) {
            return ['type' => 'error', 'message' => esc_html__('Impossible de mettre à jour l\'ordre.', 'notation-jlg')];
        }

        asort($new_order, SORT_NUMERIC);

        $normalized = [];
        $position = 1;
        foreach ($new_order as $slug => $value) {
            $normalized[$slug] = $position++;
        }

        $stored['order'] = $normalized;
        update_option($this->option_name, $stored);

        return ['type' => 'success', 'message' => esc_html__('Ordre des genres mis à jour.', 'notation-jlg')];
    }

    private function reset_genres() {
        delete_option($this->option_name);
        return ['type' => 'success', 'message' => esc_html__('Genres réinitialisés avec succès.', 'notation-jlg')];
    }

    public function render_genres_page() {
        $message = get_transient('jlg_genres_message');
        if (!empty($message)) {
            $class = ($message['type'] ?? '') === 'success' ? 'notice-success' : 'notice-error';
            echo '<div class="notice ' . esc_attr($class) . ' is-dismissible"><p>' . esc_html($message['message']) . '</p></div>';
            delete_transient('jlg_genres_message');
        }

        $genres = $this->get_genres();
        $custom_genres = $this->get_stored_genre_data()['custom_genres'];
        ?>
        <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;">
            <div style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                <h3><?php esc_html_e('🎭 Liste des genres disponibles', 'notation-jlg'); ?></h3>
                <form method="post" action="">
                    <?php wp_nonce_field('jlg_genre_action', 'jlg_genre_nonce'); ?>
                    <input type="hidden" name="jlg_genre_action" value="update_order">
                    <table class="wp-list-table widefat striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Ordre', 'notation-jlg'); ?></th>
                                <th><?php esc_html_e('Nom', 'notation-jlg'); ?></th>
                                <th><?php esc_html_e('Slug', 'notation-jlg'); ?></th>
                                <th><?php esc_html_e('Couleur', 'notation-jlg'); ?></th>
                                <th><?php esc_html_e('Badge', 'notation-jlg'); ?></th>
                                <th><?php esc_html_e('Type', 'notation-jlg'); ?></th>
                                <th><?php esc_html_e('Actions', 'notation-jlg'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($genres as $slug => $genre) :
                                $order = isset($genre['order']) ? (int) $genre['order'] : '';
                                $color = !empty($genre['color']) ? $genre['color'] : '#4b5563';
                                ?>
                                <tr>
                                    <td>
                                        <input type="number" name="genre_order[<?php echo esc_attr($slug); ?>]" value="<?php echo esc_attr($order); ?>" min="1" class="small-text">
                                    </td>
                                    <td>
                                        <span style="display:inline-flex;align-items:center;gap:6px;">
                                            <span style="display:inline-block;width:14px;height:14px;border-radius:999px;background:<?php echo esc_attr($color); ?>;"></span>
                                            <strong><?php echo esc_html($genre['name']); ?></strong>
                                        </span>
                                    </td>
                                    <td><code><?php echo esc_html($slug); ?></code></td>
                                    <td><?php echo esc_html($color); ?></td>
                                    <td><?php echo esc_html($genre['badge']); ?></td>
                                    <td>
                                        <?php echo !empty($genre['custom']) ? esc_html__('Personnalisé', 'notation-jlg') : esc_html__('Par défaut', 'notation-jlg'); ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($genre['custom'])) : ?>
                                            <button type="button" class="button button-small" data-genre-edit="<?php echo esc_attr($slug); ?>"><?php esc_html_e('Modifier', 'notation-jlg'); ?></button>
                                            <button type="button" class="button button-small button-link-delete" name="jlg_genre_action_delete" value="<?php echo esc_attr($slug); ?>"><?php esc_html_e('Supprimer', 'notation-jlg'); ?></button>
                                        <?php else : ?>
                                            <span style="color:#6b7280;">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p>
                        <button type="submit" class="button button-primary"><?php esc_html_e('💾 Enregistrer l\'ordre', 'notation-jlg'); ?></button>
                    </p>
                </form>
            </div>
            <div style="display:flex;flex-direction:column;gap:20px;">
                <div style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h3><?php esc_html_e('➕ Ajouter un genre', 'notation-jlg'); ?></h3>
                    <form method="post" action="">
                        <?php wp_nonce_field('jlg_genre_action', 'jlg_genre_nonce'); ?>
                        <input type="hidden" name="jlg_genre_action" value="add">
                        <p>
                            <label for="new_genre_name"><strong><?php esc_html_e('Nom du genre', 'notation-jlg'); ?> *</strong></label><br>
                            <input type="text" id="new_genre_name" name="new_genre_name" class="regular-text" required>
                        </p>
                        <p>
                            <label for="new_genre_slug"><strong><?php esc_html_e('Slug', 'notation-jlg'); ?></strong></label><br>
                            <input type="text" id="new_genre_slug" name="new_genre_slug" class="regular-text" placeholder="action-aventure">
                            <span class="description"><?php esc_html_e('Laissez vide pour générer automatiquement à partir du nom.', 'notation-jlg'); ?></span>
                        </p>
                        <p>
                            <label for="new_genre_color"><strong><?php esc_html_e('Couleur', 'notation-jlg'); ?></strong></label><br>
                            <input type="color" id="new_genre_color" name="new_genre_color" value="#2563eb">
                        </p>
                        <p>
                            <label for="new_genre_badge"><strong><?php esc_html_e('Badge (emoji ou texte court)', 'notation-jlg'); ?></strong></label><br>
                            <input type="text" id="new_genre_badge" name="new_genre_badge" class="regular-text" placeholder="🔥">
                        </p>
                        <p>
                            <button type="submit" class="button button-primary"><?php esc_html_e('Ajouter le genre', 'notation-jlg'); ?></button>
                        </p>
                    </form>
                </div>
                <div style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h3><?php esc_html_e('✏️ Modifier un genre personnalisé', 'notation-jlg'); ?></h3>
                    <form method="post" action="" id="jlg-edit-genre-form">
                        <?php wp_nonce_field('jlg_genre_action', 'jlg_genre_nonce'); ?>
                        <input type="hidden" name="jlg_genre_action" value="update">
                        <input type="hidden" name="edit_genre_original_slug" id="edit_genre_original_slug" value="">
                        <p>
                            <label for="edit_genre_selector"><strong><?php esc_html_e('Sélectionnez un genre', 'notation-jlg'); ?></strong></label><br>
                            <select id="edit_genre_selector" name="edit_genre_selector" class="regular-text">
                                <option value=""><?php esc_html_e('— Choisir —', 'notation-jlg'); ?></option>
                                <?php foreach ($custom_genres as $slug => $genre_data) : ?>
                                    <option value="<?php echo esc_attr($slug); ?>" data-name="<?php echo esc_attr($genre_data['name'] ?? $slug); ?>" data-color="<?php echo esc_attr($genre_data['color'] ?? '#2563eb'); ?>" data-badge="<?php echo esc_attr($genre_data['badge'] ?? ''); ?>">
                                        <?php echo esc_html($genre_data['name'] ?? $slug); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </p>
                        <p>
                            <label for="edit_genre_name"><strong><?php esc_html_e('Nom', 'notation-jlg'); ?></strong></label><br>
                            <input type="text" id="edit_genre_name" name="edit_genre_name" class="regular-text">
                        </p>
                        <p>
                            <label for="edit_genre_slug"><strong><?php esc_html_e('Slug', 'notation-jlg'); ?></strong></label><br>
                            <input type="text" id="edit_genre_slug" name="edit_genre_slug" class="regular-text">
                        </p>
                        <p>
                            <label for="edit_genre_color"><strong><?php esc_html_e('Couleur', 'notation-jlg'); ?></strong></label><br>
                            <input type="color" id="edit_genre_color" name="edit_genre_color" value="#2563eb">
                        </p>
                        <p>
                            <label for="edit_genre_badge"><strong><?php esc_html_e('Badge', 'notation-jlg'); ?></strong></label><br>
                            <input type="text" id="edit_genre_badge" name="edit_genre_badge" class="regular-text">
                        </p>
                        <p>
                            <button type="submit" class="button button-primary"><?php esc_html_e('Mettre à jour le genre', 'notation-jlg'); ?></button>
                        </p>
                    </form>
                </div>
                <div style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h3><?php esc_html_e('⚙️ Actions', 'notation-jlg'); ?></h3>
                    <form method="post" action="" onsubmit="return confirm('<?php echo esc_js(__('Êtes-vous sûr de vouloir réinitialiser les genres ? Cette action supprimera toutes les entrées personnalisées.', 'notation-jlg')); ?>');">
                        <?php wp_nonce_field('jlg_genre_action', 'jlg_genre_nonce'); ?>
                        <input type="hidden" name="jlg_genre_action" value="reset">
                        <button type="submit" class="button"><?php esc_html_e('🔄 Réinitialiser les genres', 'notation-jlg'); ?></button>
                    </form>
                </div>
            </div>
        </div>
        <script>
        (function($){
            $('#jlg-edit-genre-form').on('submit', function(){
                return $('#edit_genre_original_slug').val() !== '';
            });

            $('[data-genre-edit]').on('click', function(){
                var slug = $(this).data('genre-edit');
                var $option = $('#edit_genre_selector option[value="' + slug + '"]');
                if (!$option.length) {
                    return;
                }
                $('#edit_genre_selector').val(slug);
                $('#edit_genre_original_slug').val(slug);
                $('#edit_genre_name').val($option.data('name') || slug);
                $('#edit_genre_slug').val(slug);
                $('#edit_genre_color').val($option.data('color') || '#2563eb');
                $('#edit_genre_badge').val($option.data('badge') || '');
            });

            $('#edit_genre_selector').on('change', function(){
                var slug = $(this).val();
                var $option = $(this).find('option:selected');
                if (!slug) {
                    $('#edit_genre_original_slug').val('');
                    $('#edit_genre_name').val('');
                    $('#edit_genre_slug').val('');
                    $('#edit_genre_color').val('#2563eb');
                    $('#edit_genre_badge').val('');
                    return;
                }
                $('#edit_genre_original_slug').val(slug);
                $('#edit_genre_name').val($option.data('name') || slug);
                $('#edit_genre_slug').val(slug);
                $('#edit_genre_color').val($option.data('color') || '#2563eb');
                $('#edit_genre_badge').val($option.data('badge') || '');
            });

            $('button[name="jlg_genre_action_delete"]').on('click', function(event){
                event.preventDefault();
                var slug = $(this).val();
                if (!slug) {
                    return;
                }
                if (!confirm('<?php echo esc_js(__('Confirmer la suppression de ce genre ?', 'notation-jlg')); ?>')) {
                    return;
                }
                var $form = $('<form>', { method: 'POST', action: '' });
                $form.append($('<input>', { type: 'hidden', name: 'jlg_genre_action', value: 'delete' }));
                $form.append($('<input>', { type: 'hidden', name: 'genre_slug', value: slug }));
                $form.append($('<input>', { type: 'hidden', name: 'jlg_genre_nonce', value: '<?php echo wp_create_nonce('jlg_genre_action'); ?>' }));
                $('body').append($form);
                $form.trigger('submit');
            });
        })(jQuery);
        </script>
        <?php
    }

    public function maybe_display_migration_notice() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $unmapped = get_option('jlg_genres_migration_unmapped', []);
        if (empty($unmapped) || !is_array($unmapped)) {
            return;
        }

        if (get_user_meta(get_current_user_id(), 'jlg_genres_notice_dismissed', true)) {
            return;
        }

        $dismiss_url = wp_nonce_url(add_query_arg('jlg-dismiss-genre-notice', '1'), 'jlg_dismiss_genre_notice');
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>' . esc_html__('Notation JLG – Migration des genres', 'notation-jlg') . '</strong></p>';
        echo '<p>' . esc_html__('Certaines valeurs historiques de genres n\'ont pas pu être converties automatiquement. Merci de les mapper manuellement dans l\'onglet "Types de jeu".', 'notation-jlg') . '</p>';
        echo '<p>' . esc_html__('Valeurs détectées :', 'notation-jlg') . ' <code>' . esc_html(implode(', ', array_slice($unmapped, 0, 5))) . '</code></p>';
        echo '<p><a class="button" href="' . esc_url(admin_url('admin.php?page=notation_jlg_settings&tab=genres')) . '">' . esc_html__('Ouvrir la gestion des genres', 'notation-jlg') . '</a> ';
        echo '<a class="button button-link" href="' . esc_url($dismiss_url) . '">' . esc_html__('Ignorer cette notification', 'notation-jlg') . '</a></p>';
        echo '</div>';
    }

    public function handle_notice_dismissal() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (!isset($_GET['jlg-dismiss-genre-notice'])) {
            return;
        }

        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'jlg_dismiss_genre_notice')) {
            return;
        }

        update_user_meta(get_current_user_id(), 'jlg_genres_notice_dismissed', 1);
        delete_option('jlg_genres_migration_unmapped');

        wp_safe_redirect(remove_query_arg(['jlg-dismiss-genre-notice', '_wpnonce']));
        exit;
    }
}

<?php
if (!defined('ABSPATH')) exit;

class JLG_Admin_Metaboxes {

    public function __construct() {
        add_action('add_meta_boxes', [$this, 'register_metaboxes']);
        add_action('save_post', [$this, 'save_meta_data']);
    }

    public function register_metaboxes() {
        // Vérifier qu'on est bien sur un post
        global $post;
        if (!$post || $post->post_type !== 'post') {
            return;
        }
        
        add_meta_box(
            'notation_jlg_metabox',
            '⭐ Notation du Jeu Vidéo',
            [$this, 'render_notation_metabox'],
            'post',
            'side',
            'high'
        );
        
        add_meta_box(
            'jlg_details_metabox',
            '🎮 Détails du Test',
            [$this, 'render_details_metabox'],
            'post',
            'normal',
            'high'
        );
    }

    public function render_notation_metabox($post) {
        // Vérification de sécurité
        if (!$post || $post->post_type !== 'post') {
            return;
        }
        
        wp_nonce_field('jlg_save_notes_data', 'jlg_notation_nonce');
        
        // Récupérer les catégories de notation
        $categories = [
            'cat1' => 'Gameplay',
            'cat2' => 'Graphismes',
            'cat3' => 'Bande-son',
            'cat4' => 'Durée de vie',
            'cat5' => 'Scénario',
            'cat6' => 'Originalité'
        ];
        
        // Si la classe Helpers existe, on l'utilise
        if (class_exists('JLG_Helpers')) {
            $categories = JLG_Helpers::get_rating_categories();
            $average_score = JLG_Helpers::get_average_score_for_post($post->ID);
        } else {
            $average_score = null;
        }
        
        echo '<div class="jlg-metabox-notation">';
        echo '<p>Entrez les notes sur 10. Laissez vide si non pertinent.</p>';
        
        foreach ($categories as $key => $label) {
            $value = get_post_meta($post->ID, '_note_' . $key, true);
            echo '<div style="margin-bottom:10px;">';
            echo '<label><strong>' . esc_html($label) . ' :</strong></label><br>';
            echo '<input type="number" step="0.1" min="0" max="10" name="_note_' . esc_attr($key) . '" value="' . esc_attr($value) . '" style="width:80px;" /> / 10';
            echo '</div>';
        }
        
        if ($average_score !== null) {
            echo '<div style="background:#f0f6fc; padding:10px; margin-top:15px; border-radius:4px;">';
            echo '<strong>Note moyenne :</strong> <span style="color:#0073aa; font-size:16px;">' . number_format($average_score, 1) . ' / 10</span>';
            echo '</div>';
        }
        
        echo '</div>';
    }

    public function render_details_metabox($post) {
        // Vérification de sécurité
        if (!$post || $post->post_type !== 'post') {
            return;
        }
        
        wp_nonce_field('jlg_save_details_data', 'jlg_details_nonce');
        
        // Récupérer les métadonnées
        $meta = [];
        $keys = ['tagline_fr', 'tagline_en', 'points_forts', 'points_faibles', 'developpeur', 'editeur', 'date_sortie', 'version', 'pegi', 'temps_de_jeu', 'plateformes', 'cover_image_url'];
        foreach ($keys as $key) {
            $meta[$key] = get_post_meta($post->ID, '_jlg_' . $key, true);
        }
        
        echo '<div class="jlg-metabox-details">';
        
        // Fiche technique
        echo '<h3>📋 Fiche Technique</h3>';
        echo '<div style="display:grid; grid-template-columns:repeat(3,1fr); gap:15px; margin-bottom:20px;">';
        
        $fields = [
            'developpeur' => 'Développeur(s)',
            'editeur' => 'Éditeur(s)',
            'date_sortie' => 'Date de sortie',
            'version' => 'Version testée',
            'pegi' => 'PEGI',
            'temps_de_jeu' => 'Temps de jeu',
            'cover_image_url' => 'URL de la jaquette'
        ];

        foreach ($fields as $key => $label) {
            $type = ($key === 'date_sortie') ? 'date' : 'text';
            echo '<div>';
            echo '<label><strong>' . esc_html($label) . ' :</strong></label><br>';
            $id_attribute = ($key === 'cover_image_url') ? ' id="jlg_cover_image_url"' : '';
            echo '<input type="' . $type . '" name="jlg_' . esc_attr($key) . '"' . $id_attribute . ' value="' . esc_attr($meta[$key] ?? '') . '" style="width:100%;">';
            echo '</div>';
        }
        
        echo '</div>';
        
        // Plateformes
        echo '<div style="margin-bottom:20px;">';
        echo '<p><strong>Plateformes :</strong></p>';
        
        // Récupérer les plateformes depuis la classe JLG_Admin_Platforms
        $platforms_list = ['PC', 'PlayStation 5', 'Xbox Series S/X', 'Nintendo Switch', 'PlayStation 4', 'Xbox One'];
        
        if (class_exists('JLG_Admin_Platforms')) {
            $platforms_manager = JLG_Admin_Platforms::get_instance();
            $all_platforms = $platforms_manager->get_platform_names();
            if (!empty($all_platforms)) {
                $platforms_list = array_values($all_platforms);
            }
        }
        
        $selected = is_array($meta['plateformes']) ? $meta['plateformes'] : [];
        
        foreach ($platforms_list as $platform) {
            $checked = in_array($platform, $selected) ? 'checked' : '';
            echo '<label style="margin-right:15px;">';
            echo '<input type="checkbox" name="jlg_plateformes[]" value="' . esc_attr($platform) . '" ' . $checked . '> ';
            echo esc_html($platform);
            echo '</label>';
        }
        echo '</div>';
        
        // Taglines
        echo '<h3>💬 Taglines</h3>';
        echo '<div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:20px;">';
        echo '<div>';
        echo '<label><strong>Française :</strong></label><br>';
        echo '<textarea name="jlg_tagline_fr" rows="3" style="width:100%;">' . esc_textarea($meta['tagline_fr'] ?? '') . '</textarea>';
        echo '</div>';
        echo '<div>';
        echo '<label><strong>Anglaise :</strong></label><br>';
        echo '<textarea name="jlg_tagline_en" rows="3" style="width:100%;">' . esc_textarea($meta['tagline_en'] ?? '') . '</textarea>';
        echo '</div>';
        echo '</div>';
        
        // Points forts/faibles
        echo '<h3>⚖️ Points Forts & Faibles</h3>';
        echo '<p style="font-style:italic;">Un point par ligne</p>';
        echo '<div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">';
        echo '<div>';
        echo '<label><strong>Points Forts :</strong></label><br>';
        echo '<textarea name="jlg_points_forts" rows="8" style="width:100%;" placeholder="Gameplay addictif&#10;Graphismes superbes">' . esc_textarea($meta['points_forts'] ?? '') . '</textarea>';
        echo '</div>';
        echo '<div>';
        echo '<label><strong>Points Faibles :</strong></label><br>';
        echo '<textarea name="jlg_points_faibles" rows="8" style="width:100%;" placeholder="Durée de vie courte&#10;Bugs occasionnels">' . esc_textarea($meta['points_faibles'] ?? '') . '</textarea>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
    }

    public function save_meta_data($post_id) {
        // Vérifications de base
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Vérifier le type de post
        if (get_post_type($post_id) !== 'post') {
            return;
        }

        // Sauvegarder les notes
        if (isset($_POST['jlg_notation_nonce']) && wp_verify_nonce($_POST['jlg_notation_nonce'], 'jlg_save_notes_data')) {
            $categories = ['cat1', 'cat2', 'cat3', 'cat4', 'cat5', 'cat6'];
            
            foreach ($categories as $key) {
                $field_name = '_note_' . $key;
                if (isset($_POST[$field_name])) {
                    $value = sanitize_text_field($_POST[$field_name]);
                    
                    if ($value === '') {
                        delete_post_meta($post_id, $field_name);
                    } elseif (is_numeric($value) && $value >= 0 && $value <= 10) {
                        update_post_meta($post_id, $field_name, round(floatval($value), 1));
                    }
                }
            }
            
            // Recalculer la moyenne si la classe Helpers existe
            if (class_exists('JLG_Helpers')) {
                $average = JLG_Helpers::get_average_score_for_post($post_id);
                if ($average !== null) {
                    update_post_meta($post_id, '_jlg_average_score', $average);
                } else {
                    delete_post_meta($post_id, '_jlg_average_score');
                }
            }
        }

        // Sauvegarder les détails
        if (isset($_POST['jlg_details_nonce']) && wp_verify_nonce($_POST['jlg_details_nonce'], 'jlg_save_details_data')) {
            // Champs texte simples
            $text_fields = ['developpeur', 'editeur', 'date_sortie', 'version', 'pegi', 'temps_de_jeu'];
            foreach ($text_fields as $field) {
                if (isset($_POST['jlg_' . $field])) {
                    $value = sanitize_text_field($_POST['jlg_' . $field]);
                    if (!empty($value)) {
                        update_post_meta($post_id, '_jlg_' . $field, $value);
                    } else {
                        delete_post_meta($post_id, '_jlg_' . $field);
                    }
                }
            }

            if (isset($_POST['jlg_cover_image_url'])) {
                $cover_image_url = esc_url_raw($_POST['jlg_cover_image_url']);
                if (!empty($cover_image_url)) {
                    update_post_meta($post_id, '_jlg_cover_image_url', $cover_image_url);
                } else {
                    delete_post_meta($post_id, '_jlg_cover_image_url');
                }
            }

            // Champs textarea
            $textarea_fields = ['tagline_fr', 'tagline_en', 'points_forts', 'points_faibles'];
            foreach ($textarea_fields as $field) {
                if (isset($_POST['jlg_' . $field])) {
                    $value = sanitize_textarea_field($_POST['jlg_' . $field]);
                    if (!empty($value)) {
                        update_post_meta($post_id, '_jlg_' . $field, $value);
                    } else {
                        delete_post_meta($post_id, '_jlg_' . $field);
                    }
                }
            }
            
            // Plateformes (checkboxes)
            if (isset($_POST['jlg_plateformes']) && is_array($_POST['jlg_plateformes'])) {
                $platforms = array_map('sanitize_text_field', $_POST['jlg_plateformes']);
                update_post_meta($post_id, '_jlg_plateformes', $platforms);
            } else {
                delete_post_meta($post_id, '_jlg_plateformes');
            }
        }
    }
}
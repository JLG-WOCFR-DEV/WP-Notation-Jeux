<?php
if (!defined('ABSPATH')) exit;

class JLG_Admin_Metaboxes {
    private $error_transient_key = '';

    public function __construct() {
        add_action('add_meta_boxes', [$this, 'register_metaboxes'], 10, 2);
        add_action('save_post', [$this, 'save_meta_data']);
        add_action('admin_notices', [$this, 'display_validation_errors']);
    }

    private function get_error_transient_key() {
        if ($this->error_transient_key === '') {
            $user_id = get_current_user_id();
            $this->error_transient_key = 'jlg_metabox_errors_' . (int) $user_id;
        }

        return $this->error_transient_key;
    }

    public function display_validation_errors() {
        $errors = get_transient($this->get_error_transient_key());

        if (empty($errors) || !is_array($errors)) {
            return;
        }

        delete_transient($this->get_error_transient_key());

        echo '<div class="notice notice-error"><p><strong>' . esc_html__('Notation JLG', 'notation-jlg') . ' :</strong></p><ul>';
        foreach ($errors as $error) {
            echo '<li>' . esc_html($error) . '</li>';
        }
        echo '</ul></div>';
    }

    public function register_metaboxes($post_type, $post = null) {
        // Vérifier qu'on est bien sur un post
        if ($post_type !== 'post') {
            return;
        }

        add_meta_box(
            'notation_jlg_metabox',
            esc_html__('⭐ Notation du Jeu Vidéo', 'notation-jlg'),
            [$this, 'render_notation_metabox'],
            $post_type,
            'side',
            'high'
        );

        add_meta_box(
            'jlg_details_metabox',
            esc_html__('🎮 Détails du Test', 'notation-jlg'),
            [$this, 'render_details_metabox'],
            $post_type,
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
            'cat1' => __('Gameplay', 'notation-jlg'),
            'cat2' => __('Graphismes', 'notation-jlg'),
            'cat3' => __('Bande-son', 'notation-jlg'),
            'cat4' => __('Durée de vie', 'notation-jlg'),
            'cat5' => __('Scénario', 'notation-jlg'),
            'cat6' => __('Originalité', 'notation-jlg')
        ];
        
        // Si la classe Helpers existe, on l'utilise
        if (class_exists('JLG_Helpers')) {
            $categories = JLG_Helpers::get_rating_categories();
            $average_score = JLG_Helpers::get_average_score_for_post($post->ID);
        } else {
            $average_score = null;
        }
        
        echo '<div class="jlg-metabox-notation">';
        echo '<p>' . esc_html__('Entrez les notes sur 10. Laissez vide si non pertinent.', 'notation-jlg') . '</p>';
        
        foreach ($categories as $key => $label) {
            $value = get_post_meta($post->ID, '_note_' . $key, true);
            echo '<div style="margin-bottom:10px;">';
            echo '<label><strong>' . esc_html($label) . ' :</strong></label><br>';
            echo '<input type="number" step="0.1" min="0" max="10" name="_note_' . esc_attr($key) . '" value="' . esc_attr($value) . '" style="width:80px;" /> ' . esc_html_x('/ 10', 'score input suffix', 'notation-jlg');
            echo '</div>';
        }
        
        if ($average_score !== null) {
            echo '<div style="background:#f0f6fc; padding:10px; margin-top:15px; border-radius:4px;">';
            $average_display = sprintf(
                /* translators: %s: average score value. */
                __('%s / 10', 'notation-jlg'),
                number_format_i18n($average_score, 1)
            );
            echo '<strong>' . esc_html__('Note moyenne :', 'notation-jlg') . '</strong> <span style="color:#0073aa; font-size:16px;">' . esc_html($average_display) . '</span>';
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
        $keys = ['game_title', 'tagline_fr', 'tagline_en', 'points_forts', 'points_faibles', 'developpeur', 'editeur', 'date_sortie', 'version', 'pegi', 'temps_de_jeu', 'plateformes', 'cover_image_url'];
        foreach ($keys as $key) {
            $meta[$key] = get_post_meta($post->ID, '_jlg_' . $key, true);
        }

        echo '<div class="jlg-metabox-details">';

        echo '<div style="margin-bottom:20px;">';
        echo '<label for="jlg_game_title"><strong>' . esc_html__('Nom du jeu', 'notation-jlg') . ' :</strong></label><br>';
        echo '<input type="text" id="jlg_game_title" name="jlg_game_title" value="' . esc_attr($meta['game_title'] ?? '') . '" style="width:100%;">';
        echo '<p class="description" style="margin:5px 0 0;">' . esc_html__('Cette valeur est utilisée dans les tableaux, widgets et données structurées lorsque renseignée.', 'notation-jlg') . '</p>';
        echo '</div>';

        // Fiche technique
        echo '<h3>' . esc_html__('📋 Fiche Technique', 'notation-jlg') . '</h3>';
        echo '<div style="display:grid; grid-template-columns:repeat(3,1fr); gap:15px; margin-bottom:20px;">';

        $fields = [
            'developpeur' => __('Développeur(s)', 'notation-jlg'),
            'editeur' => __('Éditeur(s)', 'notation-jlg'),
            'date_sortie' => __('Date de sortie', 'notation-jlg'),
            'version' => __('Version testée', 'notation-jlg'),
            'pegi' => __('PEGI', 'notation-jlg'),
            'temps_de_jeu' => __('Temps de jeu', 'notation-jlg'),
            'cover_image_url' => __('URL de la jaquette', 'notation-jlg')
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
        echo '<p><strong>' . esc_html__('Plateformes :', 'notation-jlg') . '</strong></p>';
        
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
        echo '<h3>' . esc_html__('💬 Taglines', 'notation-jlg') . '</h3>';
        echo '<div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:20px;">';
        echo '<div>';
        echo '<label><strong>' . esc_html__('Française :', 'notation-jlg') . '</strong></label><br>';
        echo '<textarea name="jlg_tagline_fr" rows="3" style="width:100%;">' . esc_textarea($meta['tagline_fr'] ?? '') . '</textarea>';
        echo '</div>';
        echo '<div>';
        echo '<label><strong>' . esc_html__('Anglaise :', 'notation-jlg') . '</strong></label><br>';
        echo '<textarea name="jlg_tagline_en" rows="3" style="width:100%;">' . esc_textarea($meta['tagline_en'] ?? '') . '</textarea>';
        echo '</div>';
        echo '</div>';

        // Points forts/faibles
        echo '<h3>' . esc_html__('⚖️ Points Forts & Faibles', 'notation-jlg') . '</h3>';
        echo '<p style="font-style:italic;">' . esc_html__('Un point par ligne', 'notation-jlg') . '</p>';
        echo '<div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">';
        echo '<div>';
        echo '<label><strong>' . esc_html__('Points Forts :', 'notation-jlg') . '</strong></label><br>';
        echo '<textarea name="jlg_points_forts" rows="8" style="width:100%;" placeholder="' . esc_attr__("Gameplay addictif\nGraphismes superbes", 'notation-jlg') . '">' . esc_textarea($meta['points_forts'] ?? '') . '</textarea>';
        echo '</div>';
        echo '<div>';
        echo '<label><strong>' . esc_html__('Points Faibles :', 'notation-jlg') . '</strong></label><br>';
        echo '<textarea name="jlg_points_faibles" rows="8" style="width:100%;" placeholder="' . esc_attr__("Durée de vie courte\nBugs occasionnels", 'notation-jlg') . '">' . esc_textarea($meta['points_faibles'] ?? '') . '</textarea>';
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
                    $raw_value = wp_unslash($_POST[$field_name]);
                    $value = sanitize_text_field($raw_value);
                    
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

        $validation_errors = [];

        // Sauvegarder les détails
        if (isset($_POST['jlg_details_nonce']) && wp_verify_nonce($_POST['jlg_details_nonce'], 'jlg_save_details_data')) {
            // Champs texte simples
            $text_fields = [
                'game_title' => __('Nom du jeu', 'notation-jlg'),
                'developpeur' => __('Développeur(s)', 'notation-jlg'),
                'editeur' => __('Éditeur(s)', 'notation-jlg'),
                'date_sortie' => __('Date de sortie', 'notation-jlg'),
                'version' => __('Version testée', 'notation-jlg'),
                'pegi' => __('PEGI', 'notation-jlg'),
                'temps_de_jeu' => __('Temps de jeu', 'notation-jlg'),
            ];
            foreach ($text_fields as $field => $label) {
                if (isset($_POST['jlg_' . $field])) {
                    $raw_value = wp_unslash($_POST['jlg_' . $field]);
                    $value = sanitize_text_field($raw_value);
                    if ($field === 'game_title' && $value !== '') {
                        if (function_exists('mb_substr')) {
                            $value = mb_substr($value, 0, 150);
                        } else {
                            $value = substr($value, 0, 150);
                        }
                    }
                    if ($value === '') {
                        delete_post_meta($post_id, '_jlg_' . $field);
                        continue;
                    }

                    if ($field === 'date_sortie') {
                        $sanitized_date = JLG_Validator::sanitize_date($value);
                        if ($sanitized_date === null) {
                            delete_post_meta($post_id, '_jlg_' . $field);
                            $validation_errors[] = sprintf(
                                /* translators: %s is the field label. */
                                __('%s : format de date invalide. Utilisez AAAA-MM-JJ.', 'notation-jlg'),
                                $label
                            );
                            continue;
                        }

                        update_post_meta($post_id, '_jlg_' . $field, $sanitized_date);
                        continue;
                    }

                    if ($field === 'pegi') {
                        if (!JLG_Validator::validate_pegi($value, false)) {
                            delete_post_meta($post_id, '_jlg_' . $field);
                            $validation_errors[] = sprintf(
                                /* translators: %s is a list of allowed PEGI values */
                                __('PEGI invalide. Valeurs acceptées : %s.', 'notation-jlg'),
                                implode(', ', array_map(function($rating) {
                                    return 'PEGI ' . $rating;
                                }, JLG_Validator::get_allowed_pegi_values()))
                            );
                            continue;
                        }

                        $sanitized_pegi = JLG_Validator::sanitize_pegi($value);
                        update_post_meta($post_id, '_jlg_' . $field, $sanitized_pegi);
                        continue;
                    }

                    update_post_meta($post_id, '_jlg_' . $field, $value);
                }
            }

            if (isset($_POST['jlg_cover_image_url'])) {
                $cover_image_url = esc_url_raw(wp_unslash($_POST['jlg_cover_image_url']));
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
                    $raw_value = wp_unslash($_POST['jlg_' . $field]);
                    $value = sanitize_textarea_field($raw_value);
                    if (!empty($value)) {
                        update_post_meta($post_id, '_jlg_' . $field, $value);
                    } else {
                        delete_post_meta($post_id, '_jlg_' . $field);
                    }
                }
            }
            
            // Plateformes (checkboxes)
            if (isset($_POST['jlg_plateformes']) && is_array($_POST['jlg_plateformes'])) {
                $raw_platforms = wp_unslash($_POST['jlg_plateformes']);
                $raw_platforms = is_array($raw_platforms) ? $raw_platforms : [];
                $platforms = JLG_Validator::sanitize_platforms($raw_platforms);
                update_post_meta($post_id, '_jlg_plateformes', $platforms);
            } else {
                delete_post_meta($post_id, '_jlg_plateformes');
            }
        }

        if (!empty($validation_errors)) {
            set_transient($this->get_error_transient_key(), $validation_errors, MINUTE_IN_SECONDS);
        }
    }
}

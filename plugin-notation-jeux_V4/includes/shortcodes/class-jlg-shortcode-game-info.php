<?php
if (!defined('ABSPATH')) exit;

class JLG_Shortcode_Game_Info {
    
    public function __construct() {
        add_shortcode('jlg_fiche_technique', [$this, 'render']);
    }

    public function render($atts = [], $content = '', $shortcode_tag = '') {
        // Définition de tous les champs possibles avec leur libellé
        $all_possible_fields = [
            'developpeur'  => 'Développeur',
            'editeur'      => 'Éditeur',
            'date_sortie'  => 'Date de sortie',
            'version'      => 'Version',
            'pegi'         => 'PEGI',
            'temps_de_jeu' => 'Temps de jeu',
            'plateformes'  => 'Plateformes',
        ];
        
        // Attributs du shortcode
        $default_fields_order = implode(',', array_keys($all_possible_fields));
        $atts = shortcode_atts([
            'titre'  => 'Fiche Technique',
            'champs' => $default_fields_order,
            'post_id' => '',
        ], $atts, 'jlg_fiche_technique');

        $post_id = $this->resolve_target_post_id($atts['post_id']);
        if (!$post_id) {
            return '';
        }

        // On transforme la liste de l'attribut en tableau
        $requested_fields = array_map('trim', explode(',', $atts['champs']));

        $data_to_display = [];
        foreach ($requested_fields as $field_key) {
            // On vérifie si le champ demandé est valide
            if (array_key_exists($field_key, $all_possible_fields)) {
                $meta_value = $this->sanitize_meta_value(get_post_meta($post_id, '_jlg_' . $field_key, true));

                if ($this->has_displayable_value($meta_value)) {
                    $data_to_display[$field_key] = [
                        'label' => $all_possible_fields[$field_key],
                        'value' => $meta_value,
                    ];
                }
            }
        }

        if (empty($data_to_display)) {
            return '';
        }
        
        JLG_Frontend::mark_shortcode_rendered($shortcode_tag ?: 'jlg_fiche_technique');

        return JLG_Frontend::get_template_html('shortcode-game-info', [
            'titre'             => sanitize_text_field($atts['titre']),
            'champs_a_afficher' => $data_to_display,
        ]);
    }

    private function resolve_target_post_id($post_id_attribute) {
        $post_id = absint($post_id_attribute);
        $allowed_types = JLG_Helpers::get_allowed_post_types();

        if ($post_id && $this->is_valid_target_post($post_id, $allowed_types)) {
            return $post_id;
        }

        if ($post_id_attribute !== '' && $post_id === 0) {
            return 0;
        }

        $current_post_id = get_the_ID();
        if (!$current_post_id) {
            return 0;
        }

        if (function_exists('is_singular') && !is_singular($allowed_types)) {
            return 0;
        }

        return $this->is_valid_target_post($current_post_id, $allowed_types) ? $current_post_id : 0;
    }

    private function is_valid_target_post($post_id, ?array $allowed_types = null) {
        if ($allowed_types === null) {
            $allowed_types = JLG_Helpers::get_allowed_post_types();
        }

        $post = get_post($post_id);
        if (!$post instanceof WP_Post) {
            return false;
        }

        if (!in_array($post->post_type ?? '', $allowed_types, true)) {
            return false;
        }

        $status = $post->post_status ?? '';

        if ($status === 'publish') {
            return true;
        }

        return current_user_can('read_post', $post_id);
    }

    private function sanitize_meta_value($meta_value) {
        if (is_array($meta_value)) {
            $sanitized = array_filter(array_map(static function ($value) {
                if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
                    $clean_value = sanitize_text_field((string) $value);

                    return wp_strip_all_tags($clean_value);
                }

                return '';
            }, $meta_value));

            return array_values(array_filter($sanitized, static function ($value) {
                return $value !== '';
            }));
        }

        if (is_scalar($meta_value) || (is_object($meta_value) && method_exists($meta_value, '__toString'))) {
            return wp_strip_all_tags(sanitize_text_field((string) $meta_value));
        }

        return '';
    }

    private function has_displayable_value($value) {
        if (is_array($value)) {
            return !empty($value);
        }

        return $value !== '';
    }
}
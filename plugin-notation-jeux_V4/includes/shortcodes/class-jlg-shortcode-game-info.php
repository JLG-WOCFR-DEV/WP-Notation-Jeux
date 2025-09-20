<?php
if (!defined('ABSPATH')) exit;

class JLG_Shortcode_Game_Info {
    
    public function __construct() {
        add_shortcode('jlg_fiche_technique', [$this, 'render']);
    }

    public function render($atts) {
        if (!is_singular('post')) {
            return '';
        }

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
        ], $atts, 'jlg_fiche_technique');

        $post_id = get_the_ID();
        
        // On transforme la liste de l'attribut en tableau
        $requested_fields = array_map('trim', explode(',', $atts['champs']));
        
        $data_to_display = [];
        foreach ($requested_fields as $field_key) {
            // On vérifie si le champ demandé est valide
            if (array_key_exists($field_key, $all_possible_fields)) {
                $meta_value = get_post_meta($post_id, '_jlg_' . $field_key, true);
                
                // On n'ajoute le champ que s'il a une valeur
                if (!empty($meta_value)) {
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
        
        JLG_Frontend::mark_shortcode_rendered();

        return JLG_Frontend::get_template_html('shortcode-game-info', [
            'titre'             => sanitize_text_field($atts['titre']),
            'champs_a_afficher' => $data_to_display,
        ]);
    }
}
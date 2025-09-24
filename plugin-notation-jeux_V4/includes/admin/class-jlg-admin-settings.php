<?php
/**
 * Gestion des réglages du plugin
 * 
 * @package JLG_Notation
 * @version 5.0
 */

if (!defined('ABSPATH')) exit;

class JLG_Admin_Settings {

    private $option_name = 'notation_jlg_settings';
    private $field_constraints = [];

    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function register_settings() {
        register_setting('notation_jlg_page', $this->option_name, [$this, 'sanitize_options']);
        $this->register_all_sections();
    }

    public function sanitize_options($input) {
        if (!is_array($input)) {
            return JLG_Helpers::get_default_settings();
        }

        $sanitized = [];
        $defaults = JLG_Helpers::get_default_settings();

        // IMPORTANT: Traiter d'abord les champs select pour les modes de couleur
        // Ces champs doivent être traités spécialement pour conserver leur valeur
        $select_fields = [
            'visual_theme',
            'score_layout',
            'text_glow_color_mode',
            'circle_glow_color_mode',
            'table_border_style'
        ];
        
        foreach ($select_fields as $field) {
            if (isset($input[$field])) {
                $sanitized[$field] = sanitize_text_field($input[$field]);
            } else {
                $sanitized[$field] = $defaults[$field] ?? '';
            }
        }

        // Traiter les autres champs
        foreach ($defaults as $key => $default_value) {
            // Skip les champs déjà traités
            if (in_array($key, $select_fields)) {
                continue;
            }

            if (isset($input[$key])) {
                $sanitized[$key] = $this->sanitize_option_value($key, $input[$key], $default_value);
            } else {
                // Pour les checkboxes non cochées
                if (
                    strpos($key, 'enabled') !== false ||
                    strpos($key, 'pulse') !== false ||
                    strpos($key, 'striping') !== false ||
                    strpos($key, 'enable_') === 0
                ) {
                    $sanitized[$key] = 0;
                } else {
                    $sanitized[$key] = $default_value;
                }
            }
        }

        JLG_Helpers::flush_plugin_options_cache();

        return $sanitized;
    }

    private function sanitize_option_value($key, $value, $default_value = '') {
        if (isset($this->field_constraints[$key])) {
            return $this->normalize_numeric_value($key, $value, $default_value);
        }

        // Couleurs
        if (strpos($key, 'color') !== false && strpos($key, 'color_mode') === false) {
            $allow_transparent_fields = [
                'table_row_bg_color',
                'table_zebra_bg_color',
            ];

            $trimmed_value = is_string($value) ? strtolower(trim($value)) : '';

            if (
                $trimmed_value === 'transparent'
                && in_array($key, $allow_transparent_fields, true)
            ) {
                return 'transparent';
            }

            $sanitized_color = sanitize_hex_color($value);

            if (!empty($sanitized_color)) {
                return $sanitized_color;
            }

            $default_trimmed = is_string($default_value) ? strtolower(trim($default_value)) : '';

            if (
                $default_trimmed === 'transparent'
                && in_array($key, $allow_transparent_fields, true)
            ) {
                return 'transparent';
            }

            $sanitized_default = is_string($default_value) ? sanitize_hex_color($default_value) : '';

            return $sanitized_default ? $sanitized_default : '';
        }
        
        // Nombres
        if (strpos($key, 'size') !== false || strpos($key, 'width') !== false ||
            strpos($key, 'padding') !== false || strpos($key, 'radius') !== false ||
            strpos($key, 'intensity') !== false || strpos($key, 'speed') !== false) {
            return is_numeric($value) ? floatval($value) : (is_numeric($default_value) ? floatval($default_value) : 0);
        }
        
        // Checkboxes
        if (
            strpos($key, 'enabled') !== false ||
            strpos($key, 'pulse') !== false ||
            strpos($key, 'striping') !== false ||
            strpos($key, 'enable_') === 0
        ) {
            return !empty($value) ? 1 : 0;
        }

        // CSS personnalisé
        if ($key === 'custom_css') {
            return wp_strip_all_tags($value);
        }

        // API Key
        if ($key === 'rawg_api_key') {
            return sanitize_text_field($value);
        }
        
        // Texte par défaut
        return sanitize_text_field($value);
    }

    private function normalize_numeric_value($key, $value, $default_value) {
        $constraints = $this->field_constraints[$key];

        $min = isset($constraints['min']) ? floatval($constraints['min']) : null;
        $max = isset($constraints['max']) ? floatval($constraints['max']) : null;
        $step = isset($constraints['step']) ? floatval($constraints['step']) : null;

        if (!is_numeric($value)) {
            if (is_numeric($default_value)) {
                $number = floatval($default_value);
            } elseif ($min !== null) {
                $number = $min;
            } else {
                $number = 0;
            }
        } else {
            $number = floatval($value);
        }

        if ($min !== null) {
            $number = max($number, $min);
        }

        if ($max !== null) {
            $number = min($number, $max);
        }

        if ($step !== null && $step > 0) {
            $base = ($min !== null) ? $min : 0.0;
            $steps = round(($number - $base) / $step);
            $number = $base + ($steps * $step);
            $number = $this->round_to_step_precision($number, $step);

            if ($min !== null) {
                $number = max($number, $min);
            }

            if ($max !== null) {
                $number = min($number, $max);
            }
        }

        if ($this->should_cast_to_int($step, $min, $max, $default_value)) {
            return (int) round($number);
        }

        return $number;
    }

    private function round_to_step_precision($value, $step) {
        $precision = $this->get_step_precision($step);

        if ($precision > 0) {
            return round($value, $precision);
        }

        return round($value);
    }

    private function get_step_precision($step) {
        $formatted = rtrim(rtrim(sprintf('%.10F', $step), '0'), '.');
        $decimal_position = strpos($formatted, '.');

        if ($decimal_position === false) {
            return 0;
        }

        return strlen($formatted) - $decimal_position - 1;
    }

    private function should_cast_to_int($step, $min, $max, $default_value) {
        $step = $step ?? 1.0;

        if (!$this->is_integer_like($step)) {
            return false;
        }

        foreach ([$min, $max, $default_value] as $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (!$this->is_integer_like($value)) {
                return false;
            }
        }

        return true;
    }

    private function is_integer_like($value) {
        if (!is_numeric($value)) {
            return false;
        }

        return abs($value - round($value)) < 0.000001;
    }

    private function store_field_constraints(array $args) {
        if (($args['type'] ?? '') !== 'number' || empty($args['id'])) {
            return;
        }

        $this->field_constraints[$args['id']] = [
            'min' => $args['min'] ?? null,
            'max' => $args['max'] ?? null,
            'step' => $args['step'] ?? null,
        ];
    }

    private function register_all_sections() {
        // Section 1: Libellés
        add_settings_section('jlg_labels', '1. 📝 Libellés des Catégories', null, 'notation_jlg_page');
        for ($i = 1; $i <= 6; $i++) {
            add_settings_field(
                'label_cat' . $i,
                'Catégorie ' . $i,
                [$this, 'render_field'],
                'notation_jlg_page',
                'jlg_labels',
                ['id' => 'label_cat' . $i, 'type' => 'text', 'placeholder' => $this->get_default_label($i)]
            );
        }

        // Section 2: Présentation de la Note Globale
        add_settings_section('jlg_layout', '2. 🎨 Présentation de la Note Globale', null, 'notation_jlg_page');
        add_settings_field('score_layout', 'Style d\'affichage', [$this, 'render_field'], 'notation_jlg_page', 'jlg_layout',
            ['id' => 'score_layout', 'type' => 'select', 'options' => ['text' => 'Texte simple', 'circle' => 'Dans un cercle']]
        );
        add_settings_field('circle_dynamic_bg_enabled', 'Fond dynamique (Mode Cercle)', [$this, 'render_field'], 'notation_jlg_page', 'jlg_layout',
            ['id' => 'circle_dynamic_bg_enabled', 'type' => 'checkbox', 'desc' => 'La couleur du cercle change selon la note']
        );
        add_settings_field('circle_border_enabled', 'Activer la bordure (Mode Cercle)', [$this, 'render_field'], 'notation_jlg_page', 'jlg_layout',
            ['id' => 'circle_border_enabled', 'type' => 'checkbox']
        );
        $circle_border_width_args = ['id' => 'circle_border_width', 'type' => 'number', 'min' => 1, 'max' => 20];
        add_settings_field('circle_border_width', 'Épaisseur bordure (px)', [$this, 'render_field'], 'notation_jlg_page', 'jlg_layout',
            $circle_border_width_args
        );
        $this->store_field_constraints($circle_border_width_args);
        add_settings_field('circle_border_color', 'Couleur de la bordure', [$this, 'render_field'], 'notation_jlg_page', 'jlg_layout',
            ['id' => 'circle_border_color', 'type' => 'color']
        );

        // Section 3: Couleurs & Thèmes
        add_settings_section('jlg_colors', '3. 🌈 Couleurs & Thèmes', null, 'notation_jlg_page');
        add_settings_field('visual_theme', 'Thème Visuel Principal', [$this, 'render_field'], 'notation_jlg_page', 'jlg_colors',
            ['id' => 'visual_theme', 'type' => 'select', 'options' => ['dark' => 'Thème Sombre', 'light' => 'Thème Clair']]
        );
        
        // Couleurs thème sombre
        add_settings_field('dark_theme_header', '<h4>Couleurs du Thème Sombre</h4>', function(){}, 'notation_jlg_page', 'jlg_colors');
        $dark_colors = [
            'dark_bg_color' => 'Fond principal',
            'dark_bg_color_secondary' => 'Fond secondaire',
            'dark_text_color' => 'Texte principal',
            'dark_text_color_secondary' => 'Texte secondaire',
            'dark_border_color' => 'Bordures'
        ];
        foreach ($dark_colors as $id => $label) {
            add_settings_field($id, $label, [$this, 'render_field'], 'notation_jlg_page', 'jlg_colors',
                ['id' => $id, 'type' => 'color']
            );
        }

        // Couleurs thème clair
        add_settings_field('light_theme_header', '<h4>Couleurs du Thème Clair</h4>', function(){}, 'notation_jlg_page', 'jlg_colors');
        $light_colors = [
            'light_bg_color' => 'Fond principal',
            'light_bg_color_secondary' => 'Fond secondaire',
            'light_text_color' => 'Texte principal',
            'light_text_color_secondary' => 'Texte secondaire',
            'light_border_color' => 'Bordures'
        ];
        foreach ($light_colors as $id => $label) {
            add_settings_field($id, $label, [$this, 'render_field'], 'notation_jlg_page', 'jlg_colors',
                ['id' => $id, 'type' => 'color']
            );
        }

        // Couleurs sémantiques
        add_settings_field('semantic_colors_header', '<h4>Couleurs Sémantiques</h4>', function(){}, 'notation_jlg_page', 'jlg_colors');
        $semantic_colors = [
            'score_gradient_1' => 'Dégradé Note 1',
            'score_gradient_2' => 'Dégradé Note 2',
            'color_low' => 'Notes Faibles (0-3)',
            'color_mid' => 'Notes Moyennes (4-7)',
            'color_high' => 'Notes Élevées (8-10)'
        ];
        foreach ($semantic_colors as $id => $label) {
            add_settings_field($id, $label, [$this, 'render_field'], 'notation_jlg_page', 'jlg_colors',
                ['id' => $id, 'type' => 'color']
            );
        }

        // Section 4: Effet Glow/Neon (Mode Texte)
        add_settings_section('jlg_glow_text', '4. ✨ Effet Neon - Mode Texte', null, 'notation_jlg_page');
        add_settings_field('text_glow_enabled', 'Activer l\'effet Neon', [$this, 'render_field'], 'notation_jlg_page', 'jlg_glow_text',
            ['id' => 'text_glow_enabled', 'type' => 'checkbox', 'desc' => 'Ajoute un halo lumineux à la note en mode texte']
        );
        add_settings_field('text_glow_color_mode', 'Mode de couleur', [$this, 'render_field'], 'notation_jlg_page', 'jlg_glow_text',
            ['id' => 'text_glow_color_mode', 'type' => 'select', 
             'options' => ['dynamic' => 'Dynamique (selon la note)', 'custom' => 'Couleur fixe'],
             'desc' => 'Dynamique = couleur change selon la note (vert/orange/rouge), Fixe = couleur personnalisée']
        );
        add_settings_field('text_glow_custom_color', 'Couleur personnalisée', [$this, 'render_field'], 'notation_jlg_page', 'jlg_glow_text',
            ['id' => 'text_glow_custom_color', 'type' => 'color', 'desc' => 'Utilisée uniquement si mode "Couleur fixe" est sélectionné']
        );
        $text_glow_intensity_args = ['id' => 'text_glow_intensity', 'type' => 'number', 'min' => 5, 'max' => 50];
        add_settings_field('text_glow_intensity', 'Intensité (px)', [$this, 'render_field'], 'notation_jlg_page', 'jlg_glow_text',
            $text_glow_intensity_args
        );
        $this->store_field_constraints($text_glow_intensity_args);
        add_settings_field('text_glow_pulse', 'Activer la pulsation', [$this, 'render_field'], 'notation_jlg_page', 'jlg_glow_text',
            ['id' => 'text_glow_pulse', 'type' => 'checkbox', 'desc' => 'Animation de pulsation du halo']
        );
        $text_glow_speed_args = ['id' => 'text_glow_speed', 'type' => 'number', 'min' => 0.5, 'max' => 10, 'step' => 0.1];
        add_settings_field('text_glow_speed', 'Vitesse pulsation (sec)', [$this, 'render_field'], 'notation_jlg_page', 'jlg_glow_text',
            $text_glow_speed_args
        );
        $this->store_field_constraints($text_glow_speed_args);

        // Section 5: Effet Glow/Neon (Mode Cercle)
        add_settings_section('jlg_glow_circle', '5. ✨ Effet Neon - Mode Cercle', null, 'notation_jlg_page');
        add_settings_field('circle_glow_enabled', 'Activer l\'effet Neon', [$this, 'render_field'], 'notation_jlg_page', 'jlg_glow_circle',
            ['id' => 'circle_glow_enabled', 'type' => 'checkbox', 'desc' => 'Ajoute un halo lumineux au cercle']
        );
        add_settings_field('circle_glow_color_mode', 'Mode de couleur', [$this, 'render_field'], 'notation_jlg_page', 'jlg_glow_circle',
            ['id' => 'circle_glow_color_mode', 'type' => 'select',
             'options' => ['dynamic' => 'Dynamique (selon la note)', 'custom' => 'Couleur fixe'],
             'desc' => 'Dynamique = couleur change selon la note (vert/orange/rouge), Fixe = couleur personnalisée']
        );
        add_settings_field('circle_glow_custom_color', 'Couleur personnalisée', [$this, 'render_field'], 'notation_jlg_page', 'jlg_glow_circle',
            ['id' => 'circle_glow_custom_color', 'type' => 'color', 'desc' => 'Utilisée uniquement si mode "Couleur fixe" est sélectionné']
        );
        $circle_glow_intensity_args = ['id' => 'circle_glow_intensity', 'type' => 'number', 'min' => 5, 'max' => 50];
        add_settings_field('circle_glow_intensity', 'Intensité (px)', [$this, 'render_field'], 'notation_jlg_page', 'jlg_glow_circle',
            $circle_glow_intensity_args
        );
        $this->store_field_constraints($circle_glow_intensity_args);
        add_settings_field('circle_glow_pulse', 'Activer la pulsation', [$this, 'render_field'], 'notation_jlg_page', 'jlg_glow_circle',
            ['id' => 'circle_glow_pulse', 'type' => 'checkbox']
        );
        $circle_glow_speed_args = ['id' => 'circle_glow_speed', 'type' => 'number', 'min' => 0.5, 'max' => 10, 'step' => 0.1];
        add_settings_field('circle_glow_speed', 'Vitesse pulsation (sec)', [$this, 'render_field'], 'notation_jlg_page', 'jlg_glow_circle',
            $circle_glow_speed_args
        );
        $this->store_field_constraints($circle_glow_speed_args);

        // Section 6: Modules
        add_settings_section('jlg_modules', '6. 🧩 Modules', null, 'notation_jlg_page');
        $module_fields = [
            'user_rating_enabled' => 'Notation utilisateurs',
            'tagline_enabled' => 'Taglines bilingues',
            'seo_schema_enabled' => 'Schéma SEO (étoiles Google)',
            'enable_animations' => 'Animations des barres'
        ];
        foreach ($module_fields as $id => $title) {
            add_settings_field($id, $title, [$this, 'render_field'], 'notation_jlg_page', 'jlg_modules',
                ['id' => $id, 'type' => 'checkbox']
            );
        }

        // Section 7: Modules - Tagline
        add_settings_section('jlg_tagline_section', '7. 💬 Module Tagline', null, 'notation_jlg_page');
        $tagline_font_size_args = ['id' => 'tagline_font_size', 'type' => 'number', 'min' => 12, 'max' => 32];
        add_settings_field('tagline_font_size', 'Taille de police (px)', [$this, 'render_field'], 'notation_jlg_page', 'jlg_tagline_section',
            $tagline_font_size_args
        );
        $this->store_field_constraints($tagline_font_size_args);
        add_settings_field('tagline_bg_color', 'Fond de la tagline', [$this, 'render_field'], 'notation_jlg_page', 'jlg_tagline_section',
            ['id' => 'tagline_bg_color', 'type' => 'color']
        );
        add_settings_field('tagline_text_color', 'Texte de la tagline', [$this, 'render_field'], 'notation_jlg_page', 'jlg_tagline_section',
            ['id' => 'tagline_text_color', 'type' => 'color']
        );

        // Section 8: Modules - Notation Utilisateurs
        add_settings_section('jlg_user_rating_section', '8. ⭐ Module Notation Utilisateurs', null, 'notation_jlg_page');
        add_settings_field('user_rating_title_color', 'Couleur du titre', [$this, 'render_field'], 'notation_jlg_page', 'jlg_user_rating_section',
            ['id' => 'user_rating_title_color', 'type' => 'color']
        );
        add_settings_field('user_rating_text_color', 'Couleur du texte', [$this, 'render_field'], 'notation_jlg_page', 'jlg_user_rating_section',
            ['id' => 'user_rating_text_color', 'type' => 'color']
        );
        add_settings_field('user_rating_star_color', 'Couleur des étoiles', [$this, 'render_field'], 'notation_jlg_page', 'jlg_user_rating_section',
            ['id' => 'user_rating_star_color', 'type' => 'color']
        );

        // Section 9: Tableau Récapitulatif
        add_settings_section('jlg_table', '9. 📊 Tableau Récapitulatif', null, 'notation_jlg_page');
        add_settings_field('table_header_bg_color', 'Fond de l\'en-tête', [$this, 'render_field'], 'notation_jlg_page', 'jlg_table',
            ['id' => 'table_header_bg_color', 'type' => 'color']
        );
        add_settings_field('table_header_text_color', 'Texte de l\'en-tête', [$this, 'render_field'], 'notation_jlg_page', 'jlg_table',
            ['id' => 'table_header_text_color', 'type' => 'color']
        );
        add_settings_field('table_row_bg_color', 'Fond des lignes', [$this, 'render_field'], 'notation_jlg_page', 'jlg_table',
            ['id' => 'table_row_bg_color', 'type' => 'color']
        );
        add_settings_field('table_row_text_color', 'Texte des lignes', [$this, 'render_field'], 'notation_jlg_page', 'jlg_table',
            ['id' => 'table_row_text_color', 'type' => 'color']
        );
        add_settings_field('table_zebra_striping', 'Alternance de couleurs', [$this, 'render_field'], 'notation_jlg_page', 'jlg_table',
            ['id' => 'table_zebra_striping', 'type' => 'checkbox']
        );
        add_settings_field('table_zebra_bg_color', 'Fond lignes alternées', [$this, 'render_field'], 'notation_jlg_page', 'jlg_table',
            ['id' => 'table_zebra_bg_color', 'type' => 'color']
        );
        add_settings_field('table_border_style', 'Style des bordures', [$this, 'render_field'], 'notation_jlg_page', 'jlg_table',
            ['id' => 'table_border_style', 'type' => 'select',
             'options' => ['none' => 'Aucune', 'horizontal' => 'Horizontales', 'full' => 'Grille complète']]
        );
        $table_border_width_args = ['id' => 'table_border_width', 'type' => 'number', 'min' => 0, 'max' => 10];
        add_settings_field('table_border_width', 'Épaisseur bordures (px)', [$this, 'render_field'], 'notation_jlg_page', 'jlg_table',
            $table_border_width_args
        );
        $this->store_field_constraints($table_border_width_args);

        // Section 10: Style des Vignettes
        add_settings_section('jlg_thumbnail_section', '10. 🖼️ Style des Vignettes', null, 'notation_jlg_page');
        add_settings_field('thumb_text_color', 'Couleur du texte', [$this, 'render_field'], 'notation_jlg_page', 'jlg_thumbnail_section',
            ['id' => 'thumb_text_color', 'type' => 'color']
        );
        $thumb_font_size_args = ['id' => 'thumb_font_size', 'type' => 'number', 'min' => 10, 'max' => 24];
        add_settings_field('thumb_font_size', 'Taille de police (px)', [$this, 'render_field'], 'notation_jlg_page', 'jlg_thumbnail_section',
            $thumb_font_size_args
        );
        $this->store_field_constraints($thumb_font_size_args);

        $thumb_padding_args = ['id' => 'thumb_padding', 'type' => 'number', 'min' => 2, 'max' => 20];
        add_settings_field('thumb_padding', 'Espacement intérieur (px)', [$this, 'render_field'], 'notation_jlg_page', 'jlg_thumbnail_section',
            $thumb_padding_args
        );
        $this->store_field_constraints($thumb_padding_args);

        $thumb_border_radius_args = ['id' => 'thumb_border_radius', 'type' => 'number', 'min' => 0, 'max' => 50];
        add_settings_field('thumb_border_radius', 'Arrondi des coins (px)', [$this, 'render_field'], 'notation_jlg_page', 'jlg_thumbnail_section',
            $thumb_border_radius_args
        );
        $this->store_field_constraints($thumb_border_radius_args);

        // Section 11: CSS Personnalisé
        add_settings_section('jlg_custom', '11. 🎨 CSS Personnalisé', null, 'notation_jlg_page');
        add_settings_field('custom_css', 'Votre CSS', [$this, 'render_field'], 'notation_jlg_page', 'jlg_custom',
            ['id' => 'custom_css', 'type' => 'textarea', 'placeholder' => '.review-box-jlg { margin: 50px 0; }']
        );

        // Section 12: SEO
        add_settings_section('jlg_seo_section', '12. 🔍 SEO', null, 'notation_jlg_page');
        add_settings_field('seo_schema_enabled', 'Activer le schéma de notation (JSON-LD)', [$this, 'render_field'], 'notation_jlg_page', 'jlg_seo_section',
            ['id' => 'seo_schema_enabled', 'type' => 'checkbox', 'desc' => 'Aide Google à afficher des étoiles de notation']
        );

        // Section 13: API
        add_settings_section('jlg_api_section', '13. 🌐 API', null, 'notation_jlg_page');
        add_settings_field('rawg_api_key', 'Clé API RAWG.io', [$this, 'render_field'], 'notation_jlg_page', 'jlg_api_section',
            ['id' => 'rawg_api_key', 'type' => 'text', 'desc' => 'Obtenez votre clé API gratuite sur rawg.io/apidocs']
        );

        // Section 14: Debug
        add_settings_section('jlg_debug_section', '14. 🔧 Debug', null, 'notation_jlg_page');
        add_settings_field('debug_mode_enabled', 'Activer le mode debug', [$this, 'render_field'], 'notation_jlg_page', 'jlg_debug_section',
            ['id' => 'debug_mode_enabled', 'type' => 'checkbox', 'desc' => 'Affiche des informations de diagnostic dans le code source']
        );

        // Ajout d'un bouton de debug pour voir les options actuelles
        add_settings_field('debug_current_options', 'Options actuelles', [$this, 'render_debug_info'], 'notation_jlg_page', 'jlg_debug_section');

        // Section 15: Game Explorer
        add_settings_section('jlg_game_explorer', '15. 🧭 Game Explorer', null, 'notation_jlg_page');

        $game_explorer_columns_args = ['id' => 'game_explorer_columns', 'type' => 'number', 'min' => 2, 'max' => 4];
        add_settings_field('game_explorer_columns', 'Colonnes (desktop)', [$this, 'render_field'], 'notation_jlg_page', 'jlg_game_explorer',
            $game_explorer_columns_args
        );
        $this->store_field_constraints($game_explorer_columns_args);

        $game_explorer_ppp_args = ['id' => 'game_explorer_posts_per_page', 'type' => 'number', 'min' => 6, 'max' => 36];
        add_settings_field('game_explorer_posts_per_page', 'Jeux par page', [$this, 'render_field'], 'notation_jlg_page', 'jlg_game_explorer',
            $game_explorer_ppp_args
        );
        $this->store_field_constraints($game_explorer_ppp_args);

        add_settings_field('game_explorer_filters', 'Filtres disponibles', [$this, 'render_field'], 'notation_jlg_page', 'jlg_game_explorer',
            [
                'id' => 'game_explorer_filters',
                'type' => 'text',
                'placeholder' => 'letter,category,platform,availability',
                'desc' => __('Liste séparée par des virgules. Options disponibles : letter, category, platform, availability.', 'notation-jlg'),
            ]
        );
    }

    public function render_field($args) {
        $type = $args['type'] ?? 'text';
        $method = $type . '_field';
        
        if (method_exists('JLG_Form_Renderer', $method)) {
            call_user_func(['JLG_Form_Renderer', $method], $args);
        } else {
            // Fallback pour les autres types
            $options = JLG_Helpers::get_plugin_options();
            
            if ($type === 'textarea') {
                printf(
                    '<textarea name="%s[%s]" rows="10" cols="50" class="large-text code" placeholder="%s">%s</textarea>',
                    esc_attr($this->option_name),
                    esc_attr($args['id']),
                    esc_attr($args['placeholder'] ?? ''),
                    esc_textarea($options[$args['id']] ?? '')
                );
            } elseif ($type === 'number') {
                $min = $args['min'] ?? 0;
                $max = $args['max'] ?? 100;
                $step = $args['step'] ?? 1;
                printf(
                    '<input type="number" class="small-text" name="%s[%s]" value="%s" min="%s" max="%s" step="%s" />',
                    esc_attr($this->option_name),
                    esc_attr($args['id']),
                    esc_attr($options[$args['id']] ?? $min),
                    esc_attr($min),
                    esc_attr($max),
                    esc_attr($step)
                );
            } elseif ($type === 'select') {
                $value = $options[$args['id']] ?? '';
                printf('<select name="%s[%s]" id="%s">', 
                    esc_attr($this->option_name), 
                    esc_attr($args['id']),
                    esc_attr($args['id'])
                );
                foreach ($args['options'] as $key => $label) {
                    printf('<option value="%s"%s>%s</option>', 
                        esc_attr($key), 
                        selected($value, $key, false), 
                        esc_html($label)
                    );
                }
                echo '</select>';
            } elseif ($type === 'checkbox') {
                printf(
                    '<input type="checkbox" name="%s[%s]" id="%s" value="1" %s />',
                    esc_attr($this->option_name),
                    esc_attr($args['id']),
                    esc_attr($args['id']),
                    checked(1, $options[$args['id']] ?? 0, false)
                );
            } elseif ($type === 'color') {
                printf(
                    '<input type="color" name="%s[%s]" id="%s" value="%s" />',
                    esc_attr($this->option_name),
                    esc_attr($args['id']),
                    esc_attr($args['id']),
                    esc_attr($options[$args['id']] ?? '#000000')
                );
            } else {
                // Type text par défaut
                printf(
                    '<input type="text" class="regular-text" name="%s[%s]" id="%s" value="%s" placeholder="%s" />',
                    esc_attr($this->option_name),
                    esc_attr($args['id']),
                    esc_attr($args['id']),
                    esc_attr($options[$args['id']] ?? ''),
                    esc_attr($args['placeholder'] ?? '')
                );
            }
            
            if (isset($args['desc'])) {
                printf('<p class="description">%s</p>', wp_kses_post($args['desc']));
            }
        }
    }
    
    public function render_debug_info() {
        $options = get_option($this->option_name);
        if (!empty($options['debug_mode_enabled'])) {
            echo '<details style="background:#f5f5f5; padding:10px; border:1px solid #ccc; border-radius:4px;">';
            echo '<summary style="cursor:pointer; font-weight:bold;">Voir les valeurs des options Glow/Neon</summary>';
            echo '<pre style="font-size:11px; overflow:auto;">';
            echo 'text_glow_enabled: ' . (isset($options['text_glow_enabled']) ? $options['text_glow_enabled'] : 'not set') . "\n";
            echo 'text_glow_color_mode: ' . (isset($options['text_glow_color_mode']) ? $options['text_glow_color_mode'] : 'not set') . "\n";
            echo 'text_glow_custom_color: ' . (isset($options['text_glow_custom_color']) ? $options['text_glow_custom_color'] : 'not set') . "\n";
            echo "\n";
            echo 'circle_glow_enabled: ' . (isset($options['circle_glow_enabled']) ? $options['circle_glow_enabled'] : 'not set') . "\n";
            echo 'circle_glow_color_mode: ' . (isset($options['circle_glow_color_mode']) ? $options['circle_glow_color_mode'] : 'not set') . "\n";
            echo 'circle_glow_custom_color: ' . (isset($options['circle_glow_custom_color']) ? $options['circle_glow_custom_color'] : 'not set') . "\n";
            echo "\n";
            echo 'color_low: ' . (isset($options['color_low']) ? $options['color_low'] : 'not set') . "\n";
            echo 'color_mid: ' . (isset($options['color_mid']) ? $options['color_mid'] : 'not set') . "\n";
            echo 'color_high: ' . (isset($options['color_high']) ? $options['color_high'] : 'not set') . "\n";
            echo '</pre>';
            echo '</details>';
        }
    }

    private function get_default_label($index) {
        $defaults = ['Gameplay', 'Graphismes', 'Bande-son', 'Durée de vie', 'Scénario', 'Originalité'];
        return $defaults[$index - 1] ?? 'Catégorie ' . $index;
    }
}
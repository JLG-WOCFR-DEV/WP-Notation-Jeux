<?php
/**
 * Classe Utilitaire (Helper) - Version 5.0
 * Contient toutes les fonctions logiques réutilisables du plugin.
 */

if (!defined('ABSPATH')) exit;

class JLG_Helpers {

    private static $option_name = 'notation_jlg_settings';
    private static $category_keys = ['cat1', 'cat2', 'cat3', 'cat4', 'cat5', 'cat6'];

    private static function get_theme_defaults() {
        return [
            'light' => [
                'bg_color'          => '#ffffff',
                'bg_color_secondary'=> '#f9fafb',
                'border_color'      => '#e5e7eb',
                'text_color'        => '#111827',
                'text_color_secondary' => '#6b7280',
                'tagline_bg_color'  => '#f3f4f6',
                'tagline_text_color'=> '#4b5563',
            ],
            'dark' => [
                'bg_color'          => '#18181b',
                'bg_color_secondary'=> '#27272a',
                'border_color'      => '#3f3f46',
                'text_color'        => '#fafafa',
                'text_color_secondary' => '#a1a1aa',
                'tagline_bg_color'  => '#1f2937',
                'tagline_text_color'=> '#d1d5db',
            ]
        ];
    }

    private static function get_default_platform_definitions() {
        return [
            'pc' => ['name' => 'PC', 'order' => 1],
            'playstation-5' => ['name' => 'PlayStation 5', 'order' => 2],
            'xbox-series-x' => ['name' => 'Xbox Series S/X', 'order' => 3],
            'nintendo-switch' => ['name' => 'Nintendo Switch', 'order' => 4],
            'playstation-4' => ['name' => 'PlayStation 4', 'order' => 5],
            'xbox-one' => ['name' => 'Xbox One', 'order' => 6],
            'steam-deck' => ['name' => 'Steam Deck', 'order' => 7],
        ];
    }

    public static function get_default_settings() {
        $dark_defaults = self::get_theme_defaults()['dark'];
        $light_defaults = self::get_theme_defaults()['light'];
        
        return [
            // Options générales
            'visual_theme'      => 'dark',
            'score_layout'      => 'text',
            'enable_animations' => 1,
            'tagline_font_size' => 16,
            
            // Couleurs de Thème Sombre personnalisables
            'dark_bg_color'           => $dark_defaults['bg_color'],
            'dark_bg_color_secondary' => $dark_defaults['bg_color_secondary'],
            'dark_border_color'       => $dark_defaults['border_color'],
            'dark_text_color'         => $dark_defaults['text_color'],
            'dark_text_color_secondary' => $dark_defaults['text_color_secondary'],
            
            // Couleurs de Thème Clair personnalisables
            'light_bg_color'           => $light_defaults['bg_color'],
            'light_bg_color_secondary' => $light_defaults['bg_color_secondary'],
            'light_border_color'       => $light_defaults['border_color'],
            'light_text_color'         => $light_defaults['text_color'],
            'light_text_color_secondary' => $light_defaults['text_color_secondary'],

            // Couleurs sémantiques et de marque
            'score_gradient_1'      => '#60a5fa',
            'score_gradient_2'      => '#c084fc',
            'color_low'             => '#ef4444',
            'color_mid'             => '#f97316',
            'color_high'            => '#22c55e',
            'user_rating_star_color'=> '#f59e0b',
            'user_rating_text_color'=> '#a1a1aa',
            'user_rating_title_color' => '#fafafa',
            
            // Options cercle
            'circle_dynamic_bg_enabled' => 0,
            'circle_border_enabled' => 1,
            'circle_border_width' => 5,
            'circle_border_color' => '#60a5fa',
            
            // Options glow pour mode texte
            'text_glow_enabled' => 0,
            'text_glow_color_mode' => 'dynamic',
            'text_glow_custom_color' => '#ffffff',
            'text_glow_intensity' => 15,
            'text_glow_pulse' => 0,
            'text_glow_speed' => 2.5,
            
            // Options glow pour mode cercle
            'circle_glow_enabled' => 0,
            'circle_glow_color_mode' => 'dynamic',
            'circle_glow_custom_color' => '#ffffff',
            'circle_glow_intensity' => 15,
            'circle_glow_pulse' => 0,
            'circle_glow_speed' => 2.5,
            
            // Options des modules
            'tagline_enabled'      => 1,
            'user_rating_enabled'  => 1,
            'table_zebra_striping' => 0,
            'table_border_style'   => 'horizontal',
            'table_border_width'   => 1,
            'table_header_bg_color'   => '#3f3f46',
            'table_header_text_color' => '#ffffff',
            'table_row_bg_color'      => 'transparent', // Must remain literal "transparent" so CSS vars keep default transparency
            'table_row_text_color'    => '#a1a1aa',
            'table_zebra_bg_color'    => '#27272a',
            'thumb_text_color'      => '#ffffff',
            'thumb_font_size'       => 14,
            'thumb_padding'         => 8,
            'thumb_border_radius'   => 4,

            // Options Game Explorer
            'game_explorer_columns'        => 3,
            'game_explorer_posts_per_page' => 12,
            'game_explorer_filters'        => 'letter,category,platform,availability',

            // Libellés
            'label_cat1' => 'Gameplay',
            'label_cat2' => 'Graphismes',
            'label_cat3' => 'Bande-son',
            'label_cat4' => 'Durée de vie',
            'label_cat5' => 'Scénario',
            'label_cat6' => 'Originalité',

            // Options techniques et diverses
            'custom_css' => '',
            'seo_schema_enabled' => 1,
            'debug_mode_enabled' => 0,
            'rawg_api_key' => '',
        ];
    }

    public static function get_plugin_options() {
        $defaults = self::get_default_settings();
        $saved_options = get_option(self::$option_name, $defaults);
        return wp_parse_args($saved_options, $defaults);
    }

    /**
     * Retrieve the preferred title for a review.
     *
     * @param int $post_id The post identifier.
     * @return string The stored game title if available, otherwise the WordPress post title.
     */
    public static function get_game_title($post_id) {
        $post_id = (int) $post_id;

        if ($post_id <= 0) {
            return '';
        }

        $raw_meta_title = get_post_meta($post_id, '_jlg_game_title', true);
        $resolved_title = '';

        if (is_string($raw_meta_title)) {
            $meta_title = sanitize_text_field($raw_meta_title);
            if ($meta_title !== '') {
                $resolved_title = $meta_title;
            }
        }

        if ($resolved_title === '') {
            $fallback_title = get_the_title($post_id);
            if (is_string($fallback_title)) {
                $resolved_title = $fallback_title;
            }
        }

        return apply_filters('jlg_game_title', (string) $resolved_title, $post_id, $raw_meta_title);
    }

    public static function get_color_palette() {
        $options = self::get_plugin_options();
        $theme = $options['visual_theme'] ?? 'dark';
        $theme_defaults = self::get_theme_defaults();
        $palette = ($theme === 'light') ? $theme_defaults['light'] : $theme_defaults['dark'];
        
        if ($theme === 'light') {
            $palette['bg_color']           = $options['light_bg_color'] ?? $theme_defaults['light']['bg_color'];
            $palette['bg_color_secondary'] = $options['light_bg_color_secondary'] ?? $theme_defaults['light']['bg_color_secondary'];
            $palette['border_color']       = $options['light_border_color'] ?? $theme_defaults['light']['border_color'];
            $palette['text_color']         = $options['light_text_color'] ?? $theme_defaults['light']['text_color'];
            $palette['text_color_secondary'] = $options['light_text_color_secondary'] ?? $theme_defaults['light']['text_color_secondary'];
        } else {
            $palette['bg_color']           = $options['dark_bg_color'] ?? $theme_defaults['dark']['bg_color'];
            $palette['bg_color_secondary'] = $options['dark_bg_color_secondary'] ?? $theme_defaults['dark']['bg_color_secondary'];
            $palette['border_color']       = $options['dark_border_color'] ?? $theme_defaults['dark']['border_color'];
            $palette['text_color']         = $options['dark_text_color'] ?? $theme_defaults['dark']['text_color'];
            $palette['text_color_secondary'] = $options['dark_text_color_secondary'] ?? $theme_defaults['dark']['text_color_secondary'];
        }
        
        $palette['tagline_bg_color']     = $palette['bg_color_secondary'];
        $palette['tagline_text_color']   = $palette['text_color_secondary'];
        $palette['table_zebra_color']    = $palette['bg_color_secondary'];
        $palette['main_text_color']      = $palette['text_color'];
        $palette['secondary_text_color'] = $palette['text_color_secondary'];
        $palette['bar_bg_color']         = $palette['bg_color_secondary'];

        return $palette;
    }

    public static function get_average_score_for_post($post_id) {
        $total_score = 0;
        $count = 0;

        foreach (self::$category_keys as $key) {
            $score = get_post_meta($post_id, '_note_' . $key, true);
            if ($score !== '' && is_numeric($score)) {
                $total_score += floatval($score);
                $count++;
            }
        }

        return ($count > 0) ? round($total_score / $count, 1) : null;
    }

    /**
     * Retrieve the stored average score, falling back to a computed value when necessary.
     *
     * @param int $post_id The post ID.
     * @return array{value: float|null, formatted: string|null}
     */
    public static function get_resolved_average_score($post_id) {
        $stored_score = get_post_meta($post_id, '_jlg_average_score', true);

        if ($stored_score !== '' && $stored_score !== null && is_numeric($stored_score)) {
            $score_value = (float) $stored_score;

            return [
                'value' => $score_value,
                'formatted' => number_format_i18n($score_value, 1),
            ];
        }

        $fallback_score = self::get_average_score_for_post($post_id);

        if ($fallback_score !== null && is_numeric($fallback_score)) {
            update_post_meta($post_id, '_jlg_average_score', $fallback_score);
            $fallback_value = (float) $fallback_score;

            return [
                'value' => $fallback_value,
                'formatted' => number_format_i18n($fallback_value, 1),
            ];
        }

        return [
            'value' => null,
            'formatted' => null,
        ];
    }

    public static function get_rating_categories() {
        $options = self::get_plugin_options();
        $categories = [];
        
        foreach (self::$category_keys as $key) {
            $label_key = 'label_' . $key;
            $categories[$key] = !empty($options[$label_key]) ? $options[$label_key] : 'Catégorie';
        }
        
        return $categories;
    }
    
    public static function get_rated_post_ids() {
        global $wpdb;
        
        $meta_keys = array_map(function($key) {
            return '_note_' . $key;
        }, self::$category_keys);
        
        $placeholders = implode(', ', array_fill(0, count($meta_keys), '%s'));
        
        $query = $wpdb->prepare(
            "SELECT DISTINCT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key IN ($placeholders) 
            AND meta_value != '' 
            AND meta_value IS NOT NULL",
            ...$meta_keys
        );
        
        return $wpdb->get_col($query);
    }
    
    public static function adjust_hex_brightness($hex, $steps) {
        $hex = str_replace('#', '', $hex);
        
        if (strlen($hex) == 3) {
            $hex = str_repeat(substr($hex,0,1), 2) . 
                   str_repeat(substr($hex,1,1), 2) . 
                   str_repeat(substr($hex,2,1), 2);
        }
        
        $r = max(0, min(255, hexdec(substr($hex,0,2)) + $steps));
        $g = max(0, min(255, hexdec(substr($hex,2,2)) + $steps));
        $b = max(0, min(255, hexdec(substr($hex,4,2)) + $steps));
        
        return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) . 
               str_pad(dechex($g), 2, '0', STR_PAD_LEFT) . 
               str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
    }

    public static function calculate_color_from_note($note, $options = null) {
        if ($options === null) {
            $options = self::get_plugin_options();
        }
        
        // S'assurer que la note est un nombre
        $note = floatval($note);
        
        // Récupérer les couleurs définies dans les options
        $color_low = $options['color_low'] ?? '#ef4444';
        $color_mid = $options['color_mid'] ?? '#f97316';
        $color_high = $options['color_high'] ?? '#22c55e';
        
        // Parser les couleurs hexadécimales
        $parsed_low = sscanf($color_low, "#%02x%02x%02x");
        $parsed_mid = sscanf($color_mid, "#%02x%02x%02x");
        $parsed_high = sscanf($color_high, "#%02x%02x%02x");
        
        // Vérifier que le parsing a fonctionné
        if (!$parsed_low || count($parsed_low) !== 3) $parsed_low = [239, 68, 68];
        if (!$parsed_mid || count($parsed_mid) !== 3) $parsed_mid = [249, 115, 22];
        if (!$parsed_high || count($parsed_high) !== 3) $parsed_high = [34, 197, 94];
        
        // Calculer l'interpolation selon la note
        if ($note <= 5) {
            // Entre 0 et 5 : interpolation entre low et mid
            $ratio = $note / 5.0;
            $r = round($parsed_low[0] + ($parsed_mid[0] - $parsed_low[0]) * $ratio);
            $g = round($parsed_low[1] + ($parsed_mid[1] - $parsed_low[1]) * $ratio);
            $b = round($parsed_low[2] + ($parsed_mid[2] - $parsed_low[2]) * $ratio);
        } else {
            // Entre 5 et 10 : interpolation entre mid et high
            $ratio = ($note - 5.0) / 5.0;
            $r = round($parsed_mid[0] + ($parsed_high[0] - $parsed_mid[0]) * $ratio);
            $g = round($parsed_mid[1] + ($parsed_high[1] - $parsed_mid[1]) * $ratio);
            $b = round($parsed_mid[2] + ($parsed_high[2] - $parsed_mid[2]) * $ratio);
        }
        
        // S'assurer que les valeurs sont dans les limites
        $r = max(0, min(255, $r));
        $g = max(0, min(255, $g));
        $b = max(0, min(255, $b));
        
        return sprintf("#%02x%02x%02x", $r, $g, $b);
    }

    public static function get_glow_css($type, $average_score, $options = null) {
        if ($options === null) {
            $options = self::get_plugin_options();
        }
        
        // Vérifier si l'effet est activé pour ce type
        $enabled_key = "{$type}_glow_enabled";
        if (empty($options[$enabled_key])) {
            return '';
        }
        
        // Déterminer la couleur du glow
        $color_mode_key = "{$type}_glow_color_mode";
        $custom_color_key = "{$type}_glow_custom_color";
        
        // Vérifier explicitement si on est en mode dynamique
        $is_dynamic = isset($options[$color_mode_key]) && $options[$color_mode_key] === 'dynamic';
        
        if ($is_dynamic) {
            // Mode dynamique : calculer la couleur selon la note
            $glow_color = self::calculate_color_from_note($average_score, $options);
        } else {
            // Mode personnalisé : utiliser la couleur définie
            $glow_color = $options[$custom_color_key] ?? '#ffffff';
        }
        
        // Paramètres d'intensité et de vitesse
        $intensity_key = "{$type}_glow_intensity";
        $pulse_key = "{$type}_glow_pulse";
        $speed_key = "{$type}_glow_speed";
        
        $intensity = isset($options[$intensity_key]) ? intval($options[$intensity_key]) : 15;
        $has_pulse = !empty($options[$pulse_key]);
        $speed = isset($options[$speed_key]) ? floatval($options[$speed_key]) : 2.5;
        
        // Calculer les tailles de shadow
        $s1 = round($intensity * 0.5);
        $s2 = $intensity;
        $s3 = round($intensity * 1.5);
        
        // Pour la pulsation
        $ps1 = round($intensity * 0.7);
        $ps2 = round($intensity * 1.5);
        $ps3 = round($intensity * 2.5);
        
        $css = '';
        
        if ($type === 'text') {
            // CSS pour le mode texte
            $css .= ".review-box-jlg .score-value { ";
            $css .= "text-shadow: ";
            $css .= "0 0 {$s1}px {$glow_color}, ";
            $css .= "0 0 {$s2}px {$glow_color}, ";
            $css .= "0 0 {$s3}px {$glow_color}";
            $css .= " !important; "; // Force l'application
            $css .= "} ";
            
            // Animation de pulsation si activée
            if ($has_pulse) {
                $css .= "@keyframes jlg-text-glow-pulse { ";
                $css .= "0%, 100% { ";
                $css .= "text-shadow: 0 0 {$s1}px {$glow_color}, 0 0 {$s2}px {$glow_color}, 0 0 {$s3}px {$glow_color}; ";
                $css .= "} ";
                $css .= "50% { ";
                $css .= "text-shadow: 0 0 {$ps1}px {$glow_color}, 0 0 {$ps2}px {$glow_color}, 0 0 {$ps3}px {$glow_color}; ";
                $css .= "} ";
                $css .= "} ";
                $css .= ".review-box-jlg .score-value { ";
                $css .= "animation: jlg-text-glow-pulse {$speed}s infinite ease-in-out !important; ";
                $css .= "} ";
            }
            
        } elseif ($type === 'circle') {
            // CSS pour le mode cercle
            $css .= ".review-box-jlg .score-circle { ";
            $css .= "box-shadow: ";
            $css .= "0 0 {$s1}px {$glow_color}, ";
            $css .= "0 0 {$s2}px {$glow_color}, ";
            $css .= "0 0 {$s3}px {$glow_color}, ";
            $css .= "inset 0 0 {$s1}px rgba(255,255,255,0.1)";
            $css .= " !important; "; // Force l'application
            $css .= "} ";
            
            // Animation de pulsation si activée
            if ($has_pulse) {
                $css .= "@keyframes jlg-circle-glow-pulse { ";
                $css .= "0%, 100% { ";
                $css .= "box-shadow: ";
                $css .= "0 0 {$s1}px {$glow_color}, ";
                $css .= "0 0 {$s2}px {$glow_color}, ";
                $css .= "0 0 {$s3}px {$glow_color}, ";
                $css .= "inset 0 0 {$s1}px rgba(255,255,255,0.1); ";
                $css .= "} ";
                $css .= "50% { ";
                $css .= "box-shadow: ";
                $css .= "0 0 {$ps1}px {$glow_color}, ";
                $css .= "0 0 {$ps2}px {$glow_color}, ";
                $css .= "0 0 {$ps3}px {$glow_color}, ";
                $css .= "inset 0 0 {$ps1}px rgba(255,255,255,0.15); ";
                $css .= "} ";
                $css .= "} ";
                $css .= ".review-box-jlg .score-circle { ";
                $css .= "animation: jlg-circle-glow-pulse {$speed}s infinite ease-in-out !important; ";
                $css .= "} ";
            }
        }
        
        // Mode debug (optionnel) - décommentez pour voir les valeurs
        if (!empty($options['debug_mode_enabled'])) {
            $css .= "/* DEBUG GLOW: ";
            $css .= "Type: {$type}, ";
            $css .= "Mode: " . ($is_dynamic ? 'dynamic' : 'custom') . ", ";
            $css .= "Score: {$average_score}, ";
            $css .= "Color: {$glow_color}, ";
            $css .= "Intensity: {$intensity}, ";
            $css .= "Pulse: " . ($has_pulse ? 'yes' : 'no');
            $css .= " */ ";
        }
        
        return $css;
    }
    
    public static function get_registered_platform_labels() {
        $defaults = self::get_default_platform_definitions();
        $stored = get_option('jlg_platforms_list', []);
        $platforms = $defaults;
        $order_map = [];

        if (is_array($stored)) {
            if (isset($stored['custom_platforms']) || isset($stored['order'])) {
                $custom_platforms = isset($stored['custom_platforms']) && is_array($stored['custom_platforms'])
                    ? $stored['custom_platforms']
                    : [];

                foreach ($custom_platforms as $key => $platform) {
                    if (!is_array($platform)) {
                        continue;
                    }

                    $name = isset($platform['name']) ? sanitize_text_field($platform['name']) : '';

                    if ($name === '') {
                        continue;
                    }

                    $platforms[$key] = [
                        'name'  => $name,
                        'order' => isset($platform['order']) ? (int) $platform['order'] : (int) (($platforms[$key]['order'] ?? count($platforms) + 1)),
                    ];
                }

                if (isset($stored['order']) && is_array($stored['order'])) {
                    $order_map = array_map('intval', $stored['order']);
                }
            } else {
                foreach ($stored as $key => $platform) {
                    if (!is_array($platform)) {
                        continue;
                    }

                    $name = isset($platform['name']) ? sanitize_text_field($platform['name']) : '';

                    if ($name === '') {
                        continue;
                    }

                    $platforms[$key] = [
                        'name'  => $name,
                        'order' => isset($platform['order']) ? (int) $platform['order'] : (int) (($defaults[$key]['order'] ?? count($platforms) + 1)),
                    ];

                    if (isset($platform['order'])) {
                        $order_map[$key] = (int) $platform['order'];
                    }
                }
            }
        }

        $keys = array_keys($platforms);

        usort($keys, function($a, $b) use ($order_map, $platforms) {
            $order_a = isset($order_map[$a]) ? $order_map[$a] : (int) ($platforms[$a]['order'] ?? PHP_INT_MAX);
            $order_b = isset($order_map[$b]) ? $order_map[$b] : (int) ($platforms[$b]['order'] ?? PHP_INT_MAX);

            if ($order_a === $order_b) {
                return strcmp((string) $a, (string) $b);
            }

            return $order_a <=> $order_b;
        });

        $labels = [];

        foreach ($keys as $key) {
            $name = isset($platforms[$key]['name']) ? (string) $platforms[$key]['name'] : '';

            if ($name === '') {
                continue;
            }

            $labels[$key] = $name;
        }

        if (empty($labels)) {
            foreach ($defaults as $key => $platform) {
                if (isset($platform['name']) && $platform['name'] !== '') {
                    $labels[$key] = $platform['name'];
                }
            }
        }

        return $labels;
    }

    /**
     * Réinitialise toutes les options du plugin
     */
    public static function reset_all_settings() {
        delete_option(self::$option_name);
        return true;
    }
}
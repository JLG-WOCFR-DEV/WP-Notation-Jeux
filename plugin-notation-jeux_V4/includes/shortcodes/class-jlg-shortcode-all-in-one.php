<?php
/**
 * Shortcode All-in-One : Bloc complet de notation
 * Combine : Notation + Points forts/faibles + Tagline
 *
 * @package JLG_Notation
 * @version 5.0
 */

if (!defined('ABSPATH')) exit;

class JLG_Shortcode_All_In_One {

    /**
     * Handle used to register/enqueue the stylesheet for the shortcode.
     *
     * @var string
     */
    private $style_handle = 'jlg-shortcode-all-in-one';

    public function __construct() {
        add_shortcode('jlg_bloc_complet', [$this, 'render']);
        add_shortcode('bloc_notation_complet', [$this, 'render']); // Alias
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
    }

    /**
     * Register assets used by the shortcode.
     */
    public function register_assets() {
        if (!wp_style_is($this->style_handle, 'registered')) {
            wp_register_style(
                $this->style_handle,
                JLG_NOTATION_PLUGIN_URL . 'assets/css/jlg-shortcode-all-in-one.css',
                [],
                JLG_NOTATION_VERSION
            );
        }
    }

    public function render($atts) {
        // Attributs du shortcode
        $atts = shortcode_atts([
            'post_id' => get_the_ID(),
            'afficher_notation' => 'oui',
            'afficher_points' => 'oui',
            'afficher_tagline' => 'oui',
            'titre_points_forts' => 'Points Forts',
            'titre_points_faibles' => 'Points Faibles',
            'style' => 'moderne', // moderne, classique, compact
            'couleur_accent' => '', // Permet de surcharger la couleur d'accent
        ], $atts, 'jlg_bloc_complet');

        $post_id = intval($atts['post_id']);
        $atts['afficher_notation'] = sanitize_text_field($atts['afficher_notation']);
        $atts['afficher_points'] = sanitize_text_field($atts['afficher_points']);
        $atts['afficher_tagline'] = sanitize_text_field($atts['afficher_tagline']);
        $atts['titre_points_forts'] = sanitize_text_field($atts['titre_points_forts']);
        $atts['titre_points_faibles'] = sanitize_text_field($atts['titre_points_faibles']);
        $atts['style'] = sanitize_text_field($atts['style']);
        $atts['couleur_accent'] = sanitize_hex_color($atts['couleur_accent']);

        $allowed_styles = ['moderne', 'classique', 'compact'];
        if (!in_array($atts['style'], $allowed_styles, true)) {
            $atts['style'] = 'moderne';
        }

        if (!$post_id) {
            return '';
        }

        $post = get_post($post_id);
        if (!$post instanceof WP_Post || ($post->post_type ?? '') !== 'post') {
            return '';
        }

        if (($post->post_status ?? '') !== 'publish' && !current_user_can('read_post', $post_id)) {
            return '';
        }

        // Vérifier qu'il y a des données à afficher
        $average_score = JLG_Helpers::get_average_score_for_post($post_id);
        $tagline_fr = get_post_meta($post_id, '_jlg_tagline_fr', true);
        $tagline_en = get_post_meta($post_id, '_jlg_tagline_en', true);
        $pros = get_post_meta($post_id, '_jlg_points_forts', true);
        $cons = get_post_meta($post_id, '_jlg_points_faibles', true);

        // Si aucune donnée, ne rien afficher
        if ($average_score === null && empty($tagline_fr) && empty($tagline_en) && empty($pros) && empty($cons)) {
            return '';
        }

        // Récupération des options et configuration
        $options = JLG_Helpers::get_plugin_options();
        $palette = JLG_Helpers::get_color_palette();
        $categories = JLG_Helpers::get_rating_categories();
        $defaults = JLG_Helpers::get_default_settings();

        // Couleur d'accent (utilise la couleur définie ou celle des options)
        $accent_color = $atts['couleur_accent'] ?: ($options['score_gradient_1'] ?? '');
        $accent_color = sanitize_hex_color($accent_color);
        if (empty($accent_color)) {
            $accent_color = sanitize_hex_color($defaults['score_gradient_1']);
        }

        $score_gradient_1 = sanitize_hex_color($options['score_gradient_1'] ?? '');
        if (empty($score_gradient_1)) {
            $score_gradient_1 = sanitize_hex_color($defaults['score_gradient_1']);
        }

        $score_gradient_2 = sanitize_hex_color($options['score_gradient_2'] ?? '');
        if (empty($score_gradient_2)) {
            $score_gradient_2 = sanitize_hex_color($defaults['score_gradient_2']);
        }

        $color_high = sanitize_hex_color($options['color_high'] ?? '');
        if (empty($color_high)) {
            $color_high = sanitize_hex_color($defaults['color_high']);
        }

        $color_low = sanitize_hex_color($options['color_low'] ?? '');
        if (empty($color_low)) {
            $color_low = sanitize_hex_color($defaults['color_low']);
        }

        // Récupérer les scores détaillés
        $scores = [];
        if ($average_score !== null) {
            foreach (array_keys($categories) as $key) {
                $score_value = get_post_meta($post_id, '_note_' . $key, true);
                if ($score_value !== '' && is_numeric($score_value)) {
                    $scores[$key] = floatval($score_value);
                }
            }
        }

        // Préparer les listes de points
        $pros_list = !empty($pros) ? array_filter(array_map('trim', explode("\n", $pros))) : [];
        $cons_list = !empty($cons) ? array_filter(array_map('trim', explode("\n", $cons))) : [];

        // Enregistrer/charger la feuille de style
        if (!wp_style_is($this->style_handle, 'registered')) {
            $this->register_assets();
        }
        wp_enqueue_style($this->style_handle);

        $tagline_font_size = isset($options['tagline_font_size']) ? absint($options['tagline_font_size']) : 16;
        if ($tagline_font_size <= 0) {
            $tagline_font_size = absint($defaults['tagline_font_size']);
        }

        $score_layout = $options['score_layout'] ?? 'text';

        $css_variables = [
            '--jlg-aio-bg' => $palette['bg_color'] ?? '',
            '--jlg-aio-bg-secondary' => $palette['bg_color_secondary'] ?? '',
            '--jlg-aio-border-color' => $palette['border_color'] ?? '',
            '--jlg-aio-text-color' => $palette['text_color'] ?? '',
            '--jlg-aio-text-color-secondary' => $palette['text_color_secondary'] ?? '',
            '--jlg-aio-header-bg' => $this->build_header_background($accent_color, $score_gradient_2 ?: $score_gradient_1),
            '--jlg-aio-tagline-font-size' => $tagline_font_size . 'px',
            '--jlg-aio-tagline-color' => $palette['text_color'] ?? '',
            '--jlg-aio-score-gradient' => $this->build_score_gradient($accent_color, $score_gradient_2 ?: $score_gradient_1),
            '--jlg-aio-score-label-color' => $palette['text_color_secondary'] ?? '',
            '--jlg-aio-score-number-color' => $palette['text_color_secondary'] ?? '',
            '--jlg-aio-bar-bg' => $palette['bg_color_secondary'] ?? '',
            '--jlg-aio-points-divider' => $palette['border_color'] ?? '',
            '--jlg-aio-points-bg' => $palette['bg_color'] ?? '',
            '--jlg-aio-points-title-color' => $palette['text_color'] ?? '',
            '--jlg-aio-points-text-secondary' => $palette['text_color_secondary'] ?? '',
            '--jlg-aio-pros-icon-bg' => $this->hex_to_rgba($color_high, 0.125),
            '--jlg-aio-pros-icon-color' => $color_high,
            '--jlg-aio-cons-icon-bg' => $this->hex_to_rgba($color_low, 0.125),
            '--jlg-aio-cons-icon-color' => $color_low,
            '--jlg-aio-circle-border' => 'none',
            '--jlg-aio-circle-shadow' => $this->build_circle_shadow($accent_color),
        ];

        if ($score_layout === 'circle') {
            $css_variables['--jlg-aio-circle-bg'] = $this->build_circle_background($options, $accent_color, $average_score, $score_gradient_2 ?: $score_gradient_1);
            $css_variables['--jlg-aio-circle-border'] = $this->build_circle_border($options, $defaults);
        }

        // Effet Glow/Neon
        if ($score_layout !== 'circle' && !empty($options['text_glow_enabled'])) {
            $glow_mode = isset($options['text_glow_color_mode']) ? $options['text_glow_color_mode'] : 'dynamic';

            if ($glow_mode === 'dynamic' && $average_score !== null) {
                $glow_color = JLG_Helpers::calculate_color_from_note($average_score, $options);
            } else {
                $glow_color = isset($options['text_glow_custom_color']) ? $options['text_glow_custom_color'] : '#60a5fa';
            }

            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $glow_color)) {
                $glow_color = '#60a5fa';
            }

            $intensity = isset($options['text_glow_intensity']) ? max(0, intval($options['text_glow_intensity'])) : 15;
            $has_pulse = !empty($options['text_glow_pulse']);
            $speed = isset($options['text_glow_speed']) ? floatval($options['text_glow_speed']) : 2.5;

            $s1 = round($intensity * 0.5);
            $s2 = $intensity;
            $s3 = round($intensity * 1.5);

            $css_variables['--jlg-aio-text-shadow'] = sprintf(
                '0 0 %1$spx %4$s, 0 0 %2$spx %4$s, 0 0 %3$spx %4$s',
                $s1,
                $s2,
                $s3,
                $glow_color
            );

            if ($has_pulse) {
                $ps1 = round($intensity * 0.7);
                $ps2 = round($intensity * 1.5);
                $ps3 = round($intensity * 2.5);

                $css_variables['--jlg-aio-text-glow-color'] = $glow_color;
                $css_variables['--jlg-aio-text-glow-s1'] = $s1 . 'px';
                $css_variables['--jlg-aio-text-glow-s2'] = $s2 . 'px';
                $css_variables['--jlg-aio-text-glow-s3'] = $s3 . 'px';
                $css_variables['--jlg-aio-text-glow-ps1'] = $ps1 . 'px';
                $css_variables['--jlg-aio-text-glow-ps2'] = $ps2 . 'px';
                $css_variables['--jlg-aio-text-glow-ps3'] = $ps3 . 'px';
                $css_variables['--jlg-aio-text-animation'] = 'jlg-aio-text-glow-pulse ' . $speed . 's infinite ease-in-out !important';
            }
        } elseif ($score_layout === 'circle' && !empty($options['circle_glow_enabled'])) {
            $glow_mode = isset($options['circle_glow_color_mode']) ? $options['circle_glow_color_mode'] : 'dynamic';

            if ($glow_mode === 'dynamic' && $average_score !== null) {
                $glow_color = JLG_Helpers::calculate_color_from_note($average_score, $options);
            } else {
                $glow_color = isset($options['circle_glow_custom_color']) ? $options['circle_glow_custom_color'] : '#60a5fa';
            }

            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $glow_color)) {
                $glow_color = '#60a5fa';
            }

            $intensity = isset($options['circle_glow_intensity']) ? max(0, intval($options['circle_glow_intensity'])) : 15;
            $has_pulse = !empty($options['circle_glow_pulse']);
            $speed = isset($options['circle_glow_speed']) ? floatval($options['circle_glow_speed']) : 2.5;

            $s1 = round($intensity * 0.5);
            $s2 = $intensity;
            $s3 = round($intensity * 1.5);

            $css_variables['--jlg-aio-circle-shadow'] = sprintf(
                '0 0 %1$spx %4$s, 0 0 %2$spx %4$s, 0 0 %3$spx %4$s, inset 0 0 %1$spx rgba(255,255,255,0.1)',
                $s1,
                $s2,
                $s3,
                $glow_color
            );

            if ($has_pulse) {
                $ps1 = round($intensity * 0.7);
                $ps2 = round($intensity * 1.5);
                $ps3 = round($intensity * 2.5);

                $css_variables['--jlg-aio-circle-glow-color'] = $glow_color;
                $css_variables['--jlg-aio-circle-glow-s1'] = $s1 . 'px';
                $css_variables['--jlg-aio-circle-glow-s2'] = $s2 . 'px';
                $css_variables['--jlg-aio-circle-glow-s3'] = $s3 . 'px';
                $css_variables['--jlg-aio-circle-glow-ps1'] = $ps1 . 'px';
                $css_variables['--jlg-aio-circle-glow-ps2'] = $ps2 . 'px';
                $css_variables['--jlg-aio-circle-glow-ps3'] = $ps3 . 'px';
                $css_variables['--jlg-aio-circle-animation'] = 'jlg-aio-circle-glow-pulse ' . $speed . 's infinite ease-in-out !important';
            }
        }

        $block_classes = [
            'jlg-all-in-one-block',
            'style-' . $atts['style'],
        ];

        if (!empty($options['enable_animations'])) {
            $block_classes[] = 'animate-in';
        }

        $block_classes = implode(' ', array_map('sanitize_html_class', array_filter($block_classes)));

        $css_variables_string = $this->format_css_variables($css_variables);

        JLG_Frontend::mark_shortcode_rendered();

        return JLG_Frontend::get_template_html('shortcode-all-in-one', [
            'options' => $options,
            'average_score' => $average_score,
            'scores' => $scores,
            'categories' => $categories,
            'pros_list' => $pros_list,
            'cons_list' => $cons_list,
            'tagline_fr' => $tagline_fr,
            'tagline_en' => $tagline_en,
            'atts' => $atts,
            'block_classes' => $block_classes,
            'css_variables' => $css_variables_string,
            'score_layout' => $score_layout,
            'animations_enabled' => !empty($options['enable_animations']),
        ]);
    }

    /**
     * Convert an hex color to rgba string with alpha.
     */
    private function hex_to_rgba($hex, $alpha) {
        $hex = sanitize_hex_color($hex);
        if (empty($hex)) {
            return '';
        }

        $alpha = max(0, min(1, floatval($alpha)));

        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = substr($hex, 0, 1) . substr($hex, 0, 1)
                 . substr($hex, 1, 1) . substr($hex, 1, 1)
                 . substr($hex, 2, 1) . substr($hex, 2, 1);
        }

        if (strlen($hex) !== 6) {
            return '';
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return sprintf('rgba(%d, %d, %d, %s)', $r, $g, $b, $alpha);
    }

    private function build_header_background($accent_color, $secondary_color) {
        $start = $this->hex_to_rgba($accent_color, 0.08);
        $end = $this->hex_to_rgba($secondary_color, 0.06);

        if ($start && $end) {
            return 'linear-gradient(135deg, ' . $start . ' 0%, ' . $end . ' 100%)';
        }

        return '';
    }

    private function build_score_gradient($from_color, $to_color) {
        $from_color = sanitize_hex_color($from_color);
        $to_color = sanitize_hex_color($to_color);

        if ($from_color && $to_color) {
            return 'linear-gradient(135deg, ' . $from_color . ', ' . $to_color . ')';
        }

        return '';
    }

    private function build_circle_background($options, $accent_color, $average_score, $fallback_color) {
        if (($options['score_layout'] ?? 'text') !== 'circle') {
            return '';
        }

        if (!empty($options['circle_dynamic_bg_enabled']) && $average_score !== null) {
            $dynamic_color = JLG_Helpers::calculate_color_from_note($average_score, $options);
            $dynamic_color = sanitize_hex_color($dynamic_color);
            $darker_color = $dynamic_color ? JLG_Helpers::adjust_hex_brightness($dynamic_color, -30) : '';
            $darker_color = sanitize_hex_color($darker_color);

            if ($dynamic_color && $darker_color) {
                return 'linear-gradient(135deg, ' . $dynamic_color . ', ' . $darker_color . ')';
            }
        }

        $accent = sanitize_hex_color($accent_color);
        $fallback = sanitize_hex_color($fallback_color);

        if ($accent && $fallback) {
            return 'linear-gradient(135deg, ' . $accent . ', ' . $fallback . ')';
        }

        return '';
    }

    private function build_circle_shadow($accent_color) {
        $rgba = $this->hex_to_rgba($accent_color, 0.25);
        if ($rgba) {
            return '0 10px 25px -5px ' . $rgba;
        }

        return 'none';
    }

    private function build_circle_border($options, $defaults) {
        if (empty($options['circle_border_enabled'])) {
            return 'none';
        }

        $width = isset($options['circle_border_width']) ? absint($options['circle_border_width']) : absint($defaults['circle_border_width']);
        $color = sanitize_hex_color($options['circle_border_color'] ?? '');
        if (empty($color)) {
            $color = sanitize_hex_color($defaults['circle_border_color']);
        }

        if ($width > 0 && $color) {
            return $width . 'px solid ' . $color;
        }

        return 'none';
    }

    private function format_css_variables(array $variables) {
        $declarations = [];

        foreach ($variables as $name => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $declarations[] = $name . ': ' . $value;
        }

        return implode('; ', $declarations);
    }
}

// L'initialisation est désormais gérée par JLG_Frontend::load_shortcodes()

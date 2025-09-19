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

    public function __construct() {
        add_shortcode('jlg_bloc_complet', [$this, 'render']);
        add_shortcode('bloc_notation_complet', [$this, 'render']); // Alias
    }

    public function render($atts) {
        $atts = shortcode_atts([
            'post_id' => get_the_ID(),
            'afficher_notation' => 'oui',
            'afficher_points' => 'oui',
            'afficher_tagline' => 'oui',
            'titre_points_forts' => 'Points Forts',
            'titre_points_faibles' => 'Points Faibles',
            'style' => 'moderne',
            'couleur_accent' => '',
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

        if (!$post_id || 'post' !== get_post_type($post_id)) {
            return '';
        }

        $average_score = JLG_Helpers::get_average_score_for_post($post_id);
        $tagline_fr = get_post_meta($post_id, '_jlg_tagline_fr', true);
        $tagline_en = get_post_meta($post_id, '_jlg_tagline_en', true);
        $pros = get_post_meta($post_id, '_jlg_points_forts', true);
        $cons = get_post_meta($post_id, '_jlg_points_faibles', true);

        if ($average_score === null && empty($tagline_fr) && empty($tagline_en) && empty($pros) && empty($cons)) {
            return '';
        }

        $options = JLG_Helpers::get_plugin_options();
        $palette = JLG_Helpers::get_color_palette();
        $categories = JLG_Helpers::get_rating_categories();

        $accent_color = $atts['couleur_accent'] ?: ($options['score_gradient_1'] ?? '');

        $scores = [];
        if ($average_score !== null) {
            foreach (array_keys($categories) as $key) {
                $score_value = get_post_meta($post_id, '_note_' . $key, true);
                if ($score_value !== '' && is_numeric($score_value)) {
                    $scores[$key] = floatval($score_value);
                }
            }
        }

        $pros_list = !empty($pros) ? array_filter(explode("\n", $pros)) : [];
        $cons_list = !empty($cons) ? array_filter(explode("\n", $cons)) : [];

        $style_handle = 'jlg-shortcode-all-in-one';
        if (!wp_style_is($style_handle, 'registered')) {
            wp_register_style(
                $style_handle,
                JLG_NOTATION_PLUGIN_URL . 'assets/css/jlg-shortcode-all-in-one.css',
                ['jlg-frontend'],
                JLG_NOTATION_VERSION
            );
        }

        wp_enqueue_style($style_handle);

        $score_gradient_1 = $this->sanitize_color_value($options['score_gradient_1'] ?? '', '#3b82f6');
        $accent_color = $this->sanitize_color_value($accent_color, $score_gradient_1);

        $css_variables = $this->build_css_variables($palette, $options, $accent_color, $average_score);

        $block_id = 'jlg-aio-' . uniqid('', false);
        $has_multiple_taglines = (!empty($tagline_fr) && !empty($tagline_en));

        return JLG_Frontend::get_template_html('shortcode-all-in-one', [
            'atts'                  => $atts,
            'options'               => $options,
            'average_score'         => $average_score,
            'scores'                => $scores,
            'categories'            => $categories,
            'pros_list'             => $pros_list,
            'cons_list'             => $cons_list,
            'tagline_fr'            => $tagline_fr,
            'tagline_en'            => $tagline_en,
            'block_id'              => $block_id,
            'css_variables'         => $css_variables,
            'has_multiple_taglines' => $has_multiple_taglines,
        ]);
    }

    private function sanitize_color_value($value, $fallback = '', $allow_transparent = false) {
        $sanitized = sanitize_hex_color($value);

        if (!empty($sanitized)) {
            return $sanitized;
        }

        if ($allow_transparent && is_string($value) && strtolower(trim($value)) === 'transparent') {
            return 'transparent';
        }

        $fallback_sanitized = sanitize_hex_color($fallback);
        if (!empty($fallback_sanitized)) {
            return $fallback_sanitized;
        }

        if ($allow_transparent && is_string($fallback) && strtolower(trim($fallback)) === 'transparent') {
            return 'transparent';
        }

        return '';
    }

    private function append_alpha_channel($color, $alpha_hex, $fallback = '') {
        $base = $this->sanitize_color_value($color, $fallback);
        if ($base === '') {
            return '';
        }

        $hex = ltrim($base, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        return '#' . $hex . $alpha_hex;
    }

    private function format_float($value, $precision = 2) {
        $formatted = number_format((float) $value, $precision, '.', '');
        return rtrim(rtrim($formatted, '0'), '.');
    }

    private function build_css_variables(array $palette, array $options, $accent_color, $average_score) {
        $bg_color = $this->sanitize_color_value($palette['bg_color'] ?? '', '#18181b');
        $bg_color_secondary = $this->sanitize_color_value($palette['bg_color_secondary'] ?? '', '#27272a');
        $border_color = $this->sanitize_color_value($palette['border_color'] ?? '', '#3f3f46');
        $text_color = $this->sanitize_color_value($palette['text_color'] ?? '', '#fafafa');
        $text_color_secondary = $this->sanitize_color_value($palette['text_color_secondary'] ?? '', '#a1a1aa');
        $score_gradient_2 = $this->sanitize_color_value($options['score_gradient_2'] ?? '', '#2563eb');
        $color_high = $this->sanitize_color_value($options['color_high'] ?? '', '#22c55e');
        $color_low = $this->sanitize_color_value($options['color_low'] ?? '', '#ef4444');

        $css_vars = [
            '--jlg-aio-bg-color'               => $bg_color,
            '--jlg-aio-bg-color-secondary'     => $bg_color_secondary,
            '--jlg-aio-bg-gradient-start'      => $bg_color,
            '--jlg-aio-bg-gradient-end'        => $bg_color_secondary,
            '--jlg-aio-header-gradient-start'  => $this->append_alpha_channel($accent_color, '15', $accent_color ?: '#3b82f6'),
            '--jlg-aio-header-gradient-end'    => $this->append_alpha_channel($score_gradient_2, '10', $score_gradient_2 ?: $accent_color),
            '--jlg-aio-tagline-font-size'      => intval($options['tagline_font_size'] ?? 16) . 'px',
            '--jlg-aio-text-color'             => $text_color,
            '--jlg-aio-text-color-secondary'   => $text_color_secondary,
            '--jlg-aio-accent-color'           => $accent_color,
            '--jlg-aio-score-gradient-2'       => $score_gradient_2,
            '--jlg-aio-rating-bg-color'        => $bg_color,
            '--jlg-aio-score-label-color'      => $text_color,
            '--jlg-aio-score-number-color'     => $text_color_secondary,
            '--jlg-aio-score-bar-bg-color'     => $bg_color_secondary,
            '--jlg-aio-points-border-color'    => $border_color,
            '--jlg-aio-points-bg-color'        => $bg_color,
            '--jlg-aio-points-title-color'     => $text_color,
            '--jlg-aio-points-text-color'      => $text_color_secondary,
            '--jlg-aio-color-high'             => $color_high,
            '--jlg-aio-color-high-soft'        => $this->append_alpha_channel($color_high, '20', $color_high),
            '--jlg-aio-color-low'              => $color_low,
            '--jlg-aio-color-low-soft'         => $this->append_alpha_channel($color_low, '20', $color_low),
            '--jlg-aio-points-bullet-pros'     => $color_high,
            '--jlg-aio-points-bullet-cons'     => $color_low,
            '--jlg-aio-circle-shadow-color'    => $this->append_alpha_channel($accent_color, '40', $accent_color ?: '#3b82f6'),
            '--jlg-aio-circle-border'          => 'none',
            '--jlg-aio-circle-glow'            => '0 0 0 0 rgba(0,0,0,0)',
            '--jlg-aio-circle-glow-start'      => '0 0 0 0 rgba(0,0,0,0)',
            '--jlg-aio-circle-glow-mid'        => '0 0 0 0 rgba(0,0,0,0)',
            '--jlg-aio-circle-glow-animation'  => 'none',
            '--jlg-aio-text-glow'              => 'none',
            '--jlg-aio-text-glow-start'        => 'none',
            '--jlg-aio-text-glow-mid'          => 'none',
            '--jlg-aio-text-glow-animation'    => 'none',
        ];

        $circle_start = $accent_color;
        $circle_end = $score_gradient_2 !== '' ? $score_gradient_2 : $accent_color;

        if (($options['score_layout'] ?? 'text') === 'circle') {
            if (!empty($options['circle_dynamic_bg_enabled'])) {
                $dynamic_color = $this->sanitize_color_value(JLG_Helpers::calculate_color_from_note($average_score, $options), $accent_color);
                $darker_color = $this->sanitize_color_value(JLG_Helpers::adjust_hex_brightness($dynamic_color, -30), $circle_end);
                $circle_start = $dynamic_color ?: $accent_color;
                $circle_end = $darker_color ?: $circle_end;
            }

            if (!empty($options['circle_border_enabled'])) {
                $border_width = max(0, intval($options['circle_border_width'] ?? 0));
                $border_color = $this->sanitize_color_value($options['circle_border_color'] ?? '', $accent_color);
                if ($border_width > 0 && $border_color !== '') {
                    $css_vars['--jlg-aio-circle-border'] = $border_width . 'px solid ' . $border_color;
                }
            }

            if (!empty($options['circle_glow_enabled'])) {
                $css_vars = $this->apply_circle_glow($css_vars, $average_score, $options);
            }
        } else {
            if (!empty($options['text_glow_enabled'])) {
                $css_vars = $this->apply_text_glow($css_vars, $average_score, $options);
            }
        }

        $css_vars['--jlg-aio-circle-background'] = sprintf('linear-gradient(135deg, %s, %s)', $circle_start, $circle_end);

        return $css_vars;
    }

    private function apply_text_glow(array $css_vars, $average_score, array $options) {
        $glow_mode = $options['text_glow_color_mode'] ?? 'dynamic';
        $glow_color = ($glow_mode === 'dynamic')
            ? $this->sanitize_color_value(JLG_Helpers::calculate_color_from_note($average_score, $options), '#60a5fa')
            : $this->sanitize_color_value($options['text_glow_custom_color'] ?? '', '#60a5fa');

        if ($glow_color === '') {
            $glow_color = '#60a5fa';
        }

        $intensity = isset($options['text_glow_intensity']) ? max(0, intval($options['text_glow_intensity'])) : 15;
        $s1 = round($intensity * 0.5);
        $s2 = $intensity;
        $s3 = round($intensity * 1.5);

        $base_shadow = sprintf('0 0 %1$dpx %2$s, 0 0 %3$dpx %2$s, 0 0 %4$dpx %2$s', $s1, $glow_color, $s2, $s3);
        $css_vars['--jlg-aio-text-glow'] = $base_shadow;
        $css_vars['--jlg-aio-text-glow-start'] = $base_shadow;
        $css_vars['--jlg-aio-text-glow-mid'] = $base_shadow;

        if (!empty($options['text_glow_pulse'])) {
            $ps1 = round($intensity * 0.7);
            $ps2 = round($intensity * 1.5);
            $ps3 = round($intensity * 2.5);
            $mid_shadow = sprintf('0 0 %1$dpx %2$s, 0 0 %3$dpx %2$s, 0 0 %4$dpx %2$s', $ps1, $glow_color, $ps2, $ps3);
            $css_vars['--jlg-aio-text-glow-mid'] = $mid_shadow;
            $speed = isset($options['text_glow_speed']) ? $this->format_float($options['text_glow_speed']) : '2.5';
            $css_vars['--jlg-aio-text-glow-animation'] = 'jlg-aio-text-glow-pulse ' . $speed . 's infinite ease-in-out';
        }

        return $css_vars;
    }

    private function apply_circle_glow(array $css_vars, $average_score, array $options) {
        $glow_mode = $options['circle_glow_color_mode'] ?? 'dynamic';
        $glow_color = ($glow_mode === 'dynamic')
            ? $this->sanitize_color_value(JLG_Helpers::calculate_color_from_note($average_score, $options), '#60a5fa')
            : $this->sanitize_color_value($options['circle_glow_custom_color'] ?? '', '#60a5fa');

        if ($glow_color === '') {
            $glow_color = '#60a5fa';
        }

        $intensity = isset($options['circle_glow_intensity']) ? max(0, intval($options['circle_glow_intensity'])) : 15;
        $s1 = round($intensity * 0.5);
        $s2 = $intensity;
        $s3 = round($intensity * 1.5);

        $base_glow = sprintf(
            '0 0 %1$dpx %2$s, 0 0 %3$dpx %2$s, 0 0 %4$dpx %2$s, inset 0 0 %1$dpx rgba(255,255,255,0.1)',
            $s1,
            $glow_color,
            $s2,
            $s3
        );

        $css_vars['--jlg-aio-circle-glow'] = $base_glow;
        $css_vars['--jlg-aio-circle-glow-start'] = $base_glow;
        $css_vars['--jlg-aio-circle-glow-mid'] = $base_glow;

        if (!empty($options['circle_glow_pulse'])) {
            $ps1 = round($intensity * 0.7);
            $ps2 = round($intensity * 1.5);
            $ps3 = round($intensity * 2.5);
            $mid_glow = sprintf(
                '0 0 %1$dpx %2$s, 0 0 %3$dpx %2$s, 0 0 %4$dpx %2$s, inset 0 0 %1$dpx rgba(255,255,255,0.15)',
                $ps1,
                $glow_color,
                $ps2,
                $ps3
            );
            $css_vars['--jlg-aio-circle-glow-mid'] = $mid_glow;
            $speed = isset($options['circle_glow_speed']) ? $this->format_float($options['circle_glow_speed']) : '2.5';
            $css_vars['--jlg-aio-circle-glow-animation'] = 'jlg-aio-circle-glow-pulse ' . $speed . 's infinite ease-in-out';
        }

        return $css_vars;
    }
}

// L'initialisation est désormais gérée par JLG_Frontend::load_shortcodes()

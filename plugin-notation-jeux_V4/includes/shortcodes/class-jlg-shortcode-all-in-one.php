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

        // Sécurité : ne s'exécute que sur les articles ('post')
        if (!$post_id || 'post' !== get_post_type($post_id)) {
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
        
        // Couleur d'accent (utilise la couleur définie ou celle des options)
        $accent_color = $atts['couleur_accent'] ?: $options['score_gradient_1'];
        
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
        $pros_list = !empty($pros) ? array_filter(explode("\n", $pros)) : [];
        $cons_list = !empty($cons) ? array_filter(explode("\n", $cons)) : [];

        // Construction du HTML
        ob_start();
        ?>
        <style>
            /* Conteneur principal du bloc complet */
            .jlg-all-in-one-block {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                background: <?php echo esc_attr($palette['bg_color']); ?>;
                border: 1px solid <?php echo esc_attr($palette['border_color']); ?>;
                border-radius: 16px;
                overflow: hidden;
                margin: 40px auto;
                max-width: 750px;
                box-shadow: 0 20px 25px -5px rgba(0,0,0,.1), 0 10px 10px -5px rgba(0,0,0,.04);
            }

            /* Style moderne avec effet gradient */
            .jlg-all-in-one-block.style-moderne {
                background: linear-gradient(135deg, 
                    <?php echo esc_attr($palette['bg_color']); ?> 0%, 
                    <?php echo esc_attr($palette['bg_color_secondary']); ?> 100%);
            }

            /* Header avec tagline */
            .jlg-aio-header {
                position: relative;
                padding: 25px 30px;
                background: linear-gradient(135deg, 
                    <?php echo esc_attr($accent_color); ?>15 0%, 
                    <?php echo esc_attr($options['score_gradient_2']); ?>10 100%);
                border-bottom: 1px solid <?php echo esc_attr($palette['border_color']); ?>;
            }

            .jlg-aio-tagline {
                font-size: <?php echo intval($options['tagline_font_size'] ?? 16); ?>px;
                font-style: italic;
                color: <?php echo esc_attr($palette['text_color']); ?>;
                text-align: center;
                position: relative;
                padding: 0 50px;
            }

            /* Drapeaux de langue */
            .jlg-aio-flags {
                position: absolute;
                top: 15px;
                right: 15px;
                display: flex;
                gap: 8px;
                z-index: 10; /* Assure que les drapeaux sont au-dessus */
            }

            .jlg-aio-flag {
                width: 24px;
                height: auto;
                cursor: pointer;
                opacity: 0.4;
                transition: all 0.2s ease;
                filter: grayscale(50%);
                display: block; /* Force le display block pour une meilleure hitbox */
                min-height: 18px; /* Hauteur minimum pour la hitbox */
            }

            .jlg-aio-flag.active {
                opacity: 1;
                filter: grayscale(0%);
                transform: scale(1.1);
            }

            .jlg-aio-flag:hover {
                opacity: 0.8;
                filter: grayscale(0%);
            }

            /* Section notation */
            .jlg-aio-rating {
                padding: 30px;
                background: <?php echo esc_attr($palette['bg_color']); ?>;
            }

            /* Score global */
            .jlg-aio-main-score {
                text-align: center;
                margin-bottom: 30px;
            }

            .jlg-aio-score-value {
                font-size: 4rem;
                font-weight: 800;
                background: linear-gradient(135deg, <?php echo esc_attr($accent_color); ?>, <?php echo esc_attr($options['score_gradient_2']); ?>);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
                line-height: 1;
                margin-bottom: 8px;
            }

            /* Mode cercle optionnel */
            <?php if ($options['score_layout'] === 'circle'): ?>
            .jlg-aio-score-circle {
                width: 140px;
                height: 140px;
                margin: 0 auto 20px;
                border-radius: 50%;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                <?php 
                // Fond dynamique ou gradient standard
                if (!empty($options['circle_dynamic_bg_enabled'])) {
                    $dynamic_color = JLG_Helpers::calculate_color_from_note($average_score, $options);
                    $darker_color = JLG_Helpers::adjust_hex_brightness($dynamic_color, -30);
                    echo "background: linear-gradient(135deg, " . esc_attr($dynamic_color) . ", " . esc_attr($darker_color) . ");";
                } else {
                    echo "background: linear-gradient(135deg, " . esc_attr($accent_color) . ", " . esc_attr($options['score_gradient_2']) . ");";
                }
                ?>
                box-shadow: 0 10px 25px -5px <?php echo esc_attr($accent_color); ?>40;
                <?php
                // Bordure du cercle si activée
                if (!empty($options['circle_border_enabled'])) {
                    echo "border: " . intval($options['circle_border_width']) . "px solid " . esc_attr($options['circle_border_color']) . ";";
                }
                ?>
            }

            .jlg-aio-score-circle .jlg-aio-score-value {
                font-size: 3rem;
                color: white;
                background: none;
                -webkit-text-fill-color: white;
            }
            <?php endif; ?>
            
            /* Effet Glow/Neon */
            <?php 
            if ($options['score_layout'] !== 'circle') {
                // Mode texte - Glow sur le score
                if (!empty($options['text_glow_enabled'])) {
                    // Déterminer la couleur du glow
                    $glow_mode = isset($options['text_glow_color_mode']) ? $options['text_glow_color_mode'] : 'dynamic';
                    
                    // Calculer la couleur selon le mode
                    if ($glow_mode === 'dynamic') {
                        // Mode dynamique : couleur selon la note
                        $glow_color = JLG_Helpers::calculate_color_from_note($average_score, $options);
                    } else {
                        // Mode custom : utiliser la couleur personnalisée
                        $glow_color = isset($options['text_glow_custom_color']) && !empty($options['text_glow_custom_color']) 
                            ? $options['text_glow_custom_color'] 
                            : '#60a5fa'; // Fallback to a default color
                    }
                    
                    // S'assurer que la couleur est valide
                    if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $glow_color)) {
                        $glow_color = '#60a5fa'; // Couleur par défaut si invalide
                    }
                    
                    $intensity = isset($options['text_glow_intensity']) ? intval($options['text_glow_intensity']) : 15;
                    $has_pulse = !empty($options['text_glow_pulse']);
                    $speed = isset($options['text_glow_speed']) ? floatval($options['text_glow_speed']) : 2.5;
                    
                    $s1 = round($intensity * 0.5);
                    $s2 = $intensity;
                    $s3 = round($intensity * 1.5);
                    
                    // Utiliser une classe spécifique pour éviter les conflits
                    echo ".jlg-all-in-one-block .jlg-aio-score-value { ";
                    echo "text-shadow: ";
                    echo "0 0 {$s1}px {$glow_color}, ";
                    echo "0 0 {$s2}px {$glow_color}, ";
                    echo "0 0 {$s3}px {$glow_color} !important; ";
                    echo "} ";
                    
                    if ($has_pulse) {
                        $ps1 = round($intensity * 0.7);
                        $ps2 = round($intensity * 1.5);
                        $ps3 = round($intensity * 2.5);
                        
                        echo "@keyframes jlg-aio-text-glow-pulse { ";
                        echo "0%, 100% { ";
                        echo "text-shadow: 0 0 {$s1}px {$glow_color}, 0 0 {$s2}px {$glow_color}, 0 0 {$s3}px {$glow_color}; ";
                        echo "} ";
                        echo "50% { ";
                        echo "text-shadow: 0 0 {$ps1}px {$glow_color}, 0 0 {$ps2}px {$glow_color}, 0 0 {$ps3}px {$glow_color}; ";
                        echo "} ";
                        echo "} ";
                        echo ".jlg-all-in-one-block .jlg-aio-score-value { ";
                        echo "animation: jlg-aio-text-glow-pulse {$speed}s infinite ease-in-out !important; ";
                        echo "} ";
                    }
                    
                    // Debug
                    echo "/* AIO Text Glow: Mode={$glow_mode}, Color={$glow_color}, Score={$average_score} */ ";
                }
            } else {
                // Mode cercle - Glow sur le cercle
                if (!empty($options['circle_glow_enabled'])) {
                    // Déterminer la couleur du glow
                    $glow_mode = isset($options['circle_glow_color_mode']) ? $options['circle_glow_color_mode'] : 'dynamic';
                    
                    // Calculer la couleur selon le mode
                    if ($glow_mode === 'dynamic') {
                        // Mode dynamique : couleur selon la note
                        $glow_color = JLG_Helpers::calculate_color_from_note($average_score, $options);
                    } else {
                        // Mode custom : utiliser la couleur personnalisée
                        $glow_color = isset($options['circle_glow_custom_color']) && !empty($options['circle_glow_custom_color']) 
                            ? $options['circle_glow_custom_color'] 
                            : '#60a5fa'; // Fallback to a default color
                    }
                    
                    // S'assurer que la couleur est valide
                    if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $glow_color)) {
                        $glow_color = '#60a5fa'; // Couleur par défaut si invalide
                    }
                    
                    $intensity = isset($options['circle_glow_intensity']) ? intval($options['circle_glow_intensity']) : 15;
                    $has_pulse = !empty($options['circle_glow_pulse']);
                    $speed = isset($options['circle_glow_speed']) ? floatval($options['circle_glow_speed']) : 2.5;
                    
                    $s1 = round($intensity * 0.5);
                    $s2 = $intensity;
                    $s3 = round($intensity * 1.5);
                    
                    // Utiliser une classe spécifique pour éviter les conflits
                    echo ".jlg-all-in-one-block .jlg-aio-score-circle { ";
                    echo "box-shadow: ";
                    echo "0 0 {$s1}px {$glow_color}, ";
                    echo "0 0 {$s2}px {$glow_color}, ";
                    echo "0 0 {$s3}px {$glow_color}, ";
                    echo "inset 0 0 {$s1}px rgba(255,255,255,0.1) !important; ";
                    echo "} ";
                    
                    if ($has_pulse) {
                        $ps1 = round($intensity * 0.7);
                        $ps2 = round($intensity * 1.5);
                        $ps3 = round($intensity * 2.5);
                        
                        echo "@keyframes jlg-aio-circle-glow-pulse { ";
                        echo "0%, 100% { ";
                        echo "box-shadow: ";
                        echo "0 0 {$s1}px {$glow_color}, ";
                        echo "0 0 {$s2}px {$glow_color}, ";
                        echo "0 0 {$s3}px {$glow_color}, ";
                        echo "inset 0 0 {$s1}px rgba(255,255,255,0.1); ";
                        echo "} ";
                        echo "50% { ";
                        echo "box-shadow: ";
                        echo "0 0 {$ps1}px {$glow_color}, ";
                        echo "0 0 {$ps2}px {$glow_color}, ";
                        echo "0 0 {$ps3}px {$glow_color}, ";
                        echo "inset 0 0 {$ps1}px rgba(255,255,255,0.15); ";
                        echo "} ";
                        echo "} ";
                        echo ".jlg-all-in-one-block .jlg-aio-score-circle { ";
                        echo "animation: jlg-aio-circle-glow-pulse {$speed}s infinite ease-in-out !important; ";
                        echo "} ";
                    }
                    
                    // Debug
                    echo "/* AIO Circle Glow: Mode={$glow_mode}, Color={$glow_color}, Score={$average_score} */ ";
                }
            }
            ?>

            .jlg-aio-score-label {
                font-size: 0.875rem;
                text-transform: uppercase;
                letter-spacing: 2px;
                color: <?php echo esc_attr($palette['text_color_secondary']); ?>;
                font-weight: 600;
            }

            /* Barres de notation détaillées */
            .jlg-aio-scores-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 20px;
                margin-top: 25px;
            }

            .jlg-aio-score-item {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }

            .jlg-aio-score-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                font-size: 0.875rem;
            }

            .jlg-aio-score-label {
                font-weight: 600;
                color: <?php echo esc_attr($palette['text_color']); ?>;
            }

            .jlg-aio-score-number {
                color: <?php echo esc_attr($palette['text_color_secondary']); ?>;
                font-weight: 500;
            }

            .jlg-aio-score-bar-bg {
                height: 8px;
                background: <?php echo esc_attr($palette['bg_color_secondary']); ?>;
                border-radius: 999px;
                overflow: hidden;
                box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
            }

            .jlg-aio-score-bar {
                height: 100%;
                border-radius: 999px;
                transition: width 0.8s cubic-bezier(0.25, 1, 0.5, 1);
                background: var(--bar-color);
            }

            /* Section points forts/faibles */
            .jlg-aio-points {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 2px;
                background: <?php echo esc_attr($palette['border_color']); ?>;
                border-top: 2px solid <?php echo esc_attr($palette['border_color']); ?>;
            }

            .jlg-aio-points-col {
                background: <?php echo esc_attr($palette['bg_color']); ?>;
                padding: 25px;
            }

            .jlg-aio-points-col.pros {
                border-right: 1px solid <?php echo esc_attr($palette['border_color']); ?>;
            }

            .jlg-aio-points-title {
                display: flex;
                align-items: center;
                gap: 10px;
                font-size: 1.125rem;
                font-weight: 600;
                margin-bottom: 15px;
                color: <?php echo esc_attr($palette['text_color']); ?>;
            }

            .jlg-aio-points-icon {
                width: 32px;
                height: 32px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.25rem;
                font-weight: bold;
            }

            .jlg-aio-points-icon.pros {
                background: <?php echo esc_attr($options['color_high']); ?>20;
                color: <?php echo esc_attr($options['color_high']); ?>;
            }

            .jlg-aio-points-icon.cons {
                background: <?php echo esc_attr($options['color_low']); ?>20;
                color: <?php echo esc_attr($options['color_low']); ?>;
            }

            .jlg-aio-points-list {
                list-style: none;
                padding: 0;
                margin: 0;
            }

            .jlg-aio-points-list li {
                position: relative;
                padding-left: 24px;
                margin-bottom: 12px;
                color: <?php echo esc_attr($palette['text_color_secondary']); ?>;
                line-height: 1.5;
            }

            .jlg-aio-points-list li::before {
                content: '▸';
                position: absolute;
                left: 0;
                font-weight: bold;
            }

            .jlg-aio-points-col.pros .jlg-aio-points-list li::before {
                color: <?php echo esc_attr($options['color_high']); ?>;
            }

            .jlg-aio-points-col.cons .jlg-aio-points-list li::before {
                color: <?php echo esc_attr($options['color_low']); ?>;
            }

            /* Responsive */
            @media (max-width: 640px) {
                .jlg-aio-points {
                    grid-template-columns: 1fr;
                }
                
                .jlg-aio-points-col.pros {
                    border-right: none;
                    border-bottom: 1px solid <?php echo esc_attr($palette['border_color']); ?>;
                }

                .jlg-aio-tagline {
                    padding: 0 40px;
                }

                .jlg-aio-score-value {
                    font-size: 3rem;
                }
            }

            /* Animations */
            <?php if ($options['enable_animations']): ?>
            .jlg-all-in-one-block.animate-in .jlg-aio-score-bar {
                width: 0 !important;
            }

            .jlg-all-in-one-block.animate-in.is-visible .jlg-aio-score-bar {
                width: var(--bar-width) !important;
            }

            .jlg-all-in-one-block.animate-in .jlg-aio-score-value {
                opacity: 0;
                transform: scale(0.8);
                transition: all 0.6s cubic-bezier(0.25, 1, 0.5, 1);
            }

            .jlg-all-in-one-block.animate-in.is-visible .jlg-aio-score-value {
                opacity: 1;
                transform: scale(1);
            }

            @keyframes slideInLeft {
                from {
                    opacity: 0;
                    transform: translateX(-20px);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }

            @keyframes slideInRight {
                from {
                    opacity: 0;
                    transform: translateX(20px);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }

            .jlg-all-in-one-block.animate-in.is-visible .jlg-aio-points-col.pros {
                animation: slideInLeft 0.6s ease-out;
            }

            .jlg-all-in-one-block.animate-in.is-visible .jlg-aio-points-col.cons {
                animation: slideInRight 0.6s ease-out;
            }
            <?php endif; ?>

            /* Style compact */
            .jlg-all-in-one-block.style-compact {
                border-radius: 12px;
            }

            .jlg-all-in-one-block.style-compact .jlg-aio-header {
                padding: 20px;
            }

            .jlg-all-in-one-block.style-compact .jlg-aio-rating {
                padding: 20px;
            }

            .jlg-all-in-one-block.style-compact .jlg-aio-points-col {
                padding: 20px;
            }

            .jlg-all-in-one-block.style-compact .jlg-aio-score-value {
                font-size: 3rem;
            }

            /* Style classique */
            .jlg-all-in-one-block.style-classique {
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                border-radius: 8px;
            }

            .jlg-all-in-one-block.style-classique .jlg-aio-header {
                background: <?php echo esc_attr($palette['bg_color_secondary']); ?>;
            }
        </style>

        <div class="jlg-all-in-one-block style-<?php echo esc_attr($atts['style']); ?> <?php echo $options['enable_animations'] ? 'animate-in' : ''; ?>">
            
            <?php if ($atts['afficher_tagline'] === 'oui' && (!empty($tagline_fr) || !empty($tagline_en))): ?>
            <!-- Header avec Tagline -->
            <div class="jlg-aio-header">
                <?php if (!empty($tagline_fr) && !empty($tagline_en)): ?>
                <div class="jlg-aio-flags">
                    <img src="<?php echo JLG_NOTATION_PLUGIN_URL . 'assets/flags/fr.svg'; ?>" 
                         class="jlg-aio-flag active" 
                         data-lang="fr" 
                         alt="Français">
                    <img src="<?php echo JLG_NOTATION_PLUGIN_URL . 'assets/flags/gb.svg'; ?>" 
                         class="jlg-aio-flag" 
                         data-lang="en" 
                         alt="English">
                </div>
                <?php endif; ?>
                
                <?php if (!empty($tagline_fr)): ?>
                <div class="jlg-aio-tagline" data-lang="fr">
                    <?php echo wp_kses_post($tagline_fr); ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($tagline_en)): ?>
                <div class="jlg-aio-tagline" data-lang="en" style="display:none;">
                    <?php echo wp_kses_post($tagline_en); ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($atts['afficher_notation'] === 'oui' && $average_score !== null): ?>
            <!-- Section Notation -->
            <div class="jlg-aio-rating">
                <div class="jlg-aio-main-score">
                    <?php if ($options['score_layout'] === 'circle'): ?>
                    <div class="jlg-aio-score-circle">
                        <div class="jlg-aio-score-value"><?php echo number_format($average_score, 1, ',', ' '); ?></div>
                        <div class="jlg-aio-score-label">Note Globale</div>
                    </div>
                    <?php else: ?>
                    <div class="jlg-aio-score-value"><?php echo number_format($average_score, 1, ',', ' '); ?></div>
                    <div class="jlg-aio-score-label">Note Globale</div>
                    <?php endif; ?>
                </div>

                <!-- Barres de notation détaillées -->
                <div class="jlg-aio-scores-grid">
                    <?php foreach ($scores as $key => $score_value): 
                        $bar_color = JLG_Helpers::calculate_color_from_note($score_value, $options);
                    ?>
                    <div class="jlg-aio-score-item">
                        <div class="jlg-aio-score-header">
                            <span class="jlg-aio-score-label"><?php echo esc_html($categories[$key]); ?></span>
                            <span class="jlg-aio-score-number"><?php echo number_format($score_value, 1, ',', ' '); ?> / 10</span>
                        </div>
                        <div class="jlg-aio-score-bar-bg">
                            <div class="jlg-aio-score-bar" 
                                 style="--bar-color: <?php echo esc_attr($bar_color); ?>; --bar-width: <?php echo esc_attr($score_value * 10); ?>%; width: <?php echo esc_attr($score_value * 10); ?>%;">
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($atts['afficher_points'] === 'oui' && (!empty($pros_list) || !empty($cons_list))): ?>
            <!-- Section Points Forts/Faibles -->
            <div class="jlg-aio-points">
                <?php if (!empty($pros_list)): ?>
                <div class="jlg-aio-points-col pros">
                    <div class="jlg-aio-points-title">
                        <span class="jlg-aio-points-icon pros">+</span>
                        <span><?php echo esc_html($atts['titre_points_forts']); ?></span>
                    </div>
                    <ul class="jlg-aio-points-list">
                        <?php foreach ($pros_list as $pro): ?>
                        <li><?php echo esc_html(trim($pro)); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($cons_list)): ?>
                <div class="jlg-aio-points-col cons">
                    <div class="jlg-aio-points-title">
                        <span class="jlg-aio-points-icon cons">−</span>
                        <span><?php echo esc_html($atts['titre_points_faibles']); ?></span>
                    </div>
                    <ul class="jlg-aio-points-list">
                        <?php foreach ($cons_list as $con): ?>
                        <li><?php echo esc_html(trim($con)); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($tagline_fr) && !empty($tagline_en)): ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Gestion du changement de langue
            const allFlags = document.querySelectorAll('.jlg-aio-flag');

            allFlags.forEach(flag => {
                const block = flag.closest('.jlg-all-in-one-block');
                if (!block) {
                    return;
                }

                const blockFlags = block.querySelectorAll('.jlg-aio-flag');
                const blockTaglines = block.querySelectorAll('.jlg-aio-tagline');

                flag.addEventListener('click', function() {
                    const selectedLang = this.dataset.lang;

                    // Mettre à jour les drapeaux actifs du bloc courant
                    blockFlags.forEach(f => f.classList.remove('active'));
                    this.classList.add('active');

                    // Afficher la bonne tagline pour le bloc courant
                    blockTaglines.forEach(t => {
                        if (t.dataset.lang === selectedLang) {
                            t.style.display = 'block';
                        } else {
                            t.style.display = 'none';
                        }
                    });
                });
            });

            <?php if ($options['enable_animations']): ?>
            // Animation à l'apparition
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.2
            });

            const animatedBlocks = document.querySelectorAll('.jlg-all-in-one-block.animate-in');
            animatedBlocks.forEach(block => observer.observe(block));
            <?php endif; ?>
        });
        </script>
        <?php endif; ?>

        <?php
        return ob_get_clean();
    }
}

// L'initialisation est désormais gérée par JLG_Frontend::load_shortcodes()

<?php
/**
 * Template pour le bloc de notation principal
 * 
 * Variables disponibles :
 * - $options : Options du plugin
 * - $average_score : Note moyenne calculée
 * - $scores : Tableau des scores par catégorie
 * - $categories : Libellés des catégories
 */

if (!defined('ABSPATH')) exit;

$options = JLG_Helpers::get_plugin_options(); 
$palette = JLG_Helpers::get_color_palette();
?>
<style>
    /* --- Conteneur Principal --- */
    .review-box-jlg {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        background-color: <?php echo esc_attr($palette['bg_color']); ?>;
        border: 1px solid <?php echo esc_attr($palette['border_color']); ?>;
        color: <?php echo esc_attr($palette['secondary_text_color']); ?>;
        border-radius: 12px;
        padding: 24px;
        margin: 32px auto;
        max-width: 650px;
        box-shadow: 0 10px 15px -3px rgba(0,0,0,.1), 0 4px 6px -2px rgba(0,0,0,.05);
    }
    .review-box-jlg hr { 
        border: 0; 
        height: 1px; 
        background-color: <?php echo esc_attr($palette['border_color']); ?>; 
        margin: 24px 0; 
    }
    
    /* --- Note Globale --- */
    .review-box-jlg .global-score-wrapper { 
        text-align: center; 
        margin-bottom: 24px; 
    }
    .review-box-jlg .score-value {
        font-size: 4.5rem; 
        font-weight: 800; 
        line-height: 1; 
        color: <?php echo esc_attr($palette['main_text_color']); ?>;
        background: linear-gradient(45deg, <?php echo esc_attr($options['score_gradient_1']); ?>, <?php echo esc_attr($options['score_gradient_2']); ?>);
        -webkit-background-clip: text; 
        -webkit-text-fill-color: transparent; 
        background-clip: text; 
        text-fill-color: transparent;
    }
    .review-box-jlg .score-label { 
        font-size: 1.125rem; 
        font-weight: 600; 
        text-transform: uppercase; 
        letter-spacing: 1.5px; 
        margin-top: 4px; 
    }
    
    /* --- Breakdown des notes --- */
    .review-box-jlg .rating-breakdown { 
        display: grid; 
        grid-template-columns: 1fr; 
        gap: 16px; 
    }
    @media (min-width: 520px) { 
        .review-box-jlg .rating-breakdown { 
            grid-template-columns: 1fr 1fr; 
        } 
    }
    .review-box-jlg .rating-item { 
        display: flex; 
        flex-direction: column; 
    }
    .review-box-jlg .rating-label { 
        display: flex; 
        justify-content: space-between; 
        align-items: baseline; 
        margin-bottom: 8px; 
        font-size: .9rem; 
        font-weight: 500; 
    }
    .review-box-jlg .rating-label span:first-child { 
        font-weight: 600; 
        color: <?php echo esc_attr($palette['main_text_color']); ?>; 
    }
    .review-box-jlg .rating-bar-container { 
        background-color: <?php echo esc_attr($palette['bar_bg_color']); ?>; 
        border-radius: 9999px; 
        height: 10px; 
        width: 100%; 
        overflow: hidden; 
        box-shadow: inset 0 2px 4px rgba(0,0,0,.2); 
    }
    .review-box-jlg .rating-bar { 
        height: 100%; 
        border-radius: 9999px; 
        transition: width .6s cubic-bezier(.25,1,.5,1), background-color .3s ease; 
        background-color: var(--bar-color, <?php echo esc_attr($options['score_gradient_1']); ?>); 
    }
    
    /* --- Mode Cercle --- */
    <?php if ($options['score_layout'] === 'circle') : ?>
    .review-box-jlg .global-score-wrapper { 
        display: flex; 
        justify-content: center; 
        align-items: center; 
    }
    .review-box-jlg .score-circle { 
        width: 150px; 
        height: 150px; 
        border-radius: 50%; 
        display: flex; 
        flex-direction: column; 
        justify-content: center; 
        align-items: center; 
        <?php 
        if (!empty($options['circle_dynamic_bg_enabled'])) {
            $dynamic_color = JLG_Helpers::calculate_color_from_note($average_score, $options);
            $darker_color = JLG_Helpers::adjust_hex_brightness($dynamic_color, -30);
            echo "background-image: linear-gradient(135deg, " . esc_attr($dynamic_color) . ", " . esc_attr($darker_color) . ");";
        } else { 
            echo "background-image: linear-gradient(135deg, " . esc_attr($options['score_gradient_1']) . ", " . esc_attr($options['score_gradient_2']) . ");"; 
        }
        if (!empty($options['circle_border_enabled'])) { 
            echo "border: " . intval($options['circle_border_width']) . "px solid " . esc_attr($options['circle_border_color']) . ";"; 
        }
        ?>
    }
    .review-box-jlg .score-circle .score-value { 
        font-size: 3.5rem; 
        background: <?php echo esc_attr($palette['main_text_color']); ?>; 
        -webkit-background-clip: text; 
        background-clip: text; 
        text-shadow: none !important; 
    }
    .review-box-jlg .score-circle .score-label { 
        font-size: 0.8rem; 
        color: <?php echo esc_attr($palette['secondary_text_color']); ?>; 
    }
    <?php endif; ?>
    
    /* --- Effet Glow/Neon --- */
    <?php 
    // Générer le CSS pour l'effet Glow selon le mode
    if ($options['score_layout'] === 'text') { 
        echo JLG_Helpers::get_glow_css('text', $average_score, $options); 
    } elseif ($options['score_layout'] === 'circle') { 
        echo JLG_Helpers::get_glow_css('circle', $average_score, $options); 
    }
    ?>
    
    /* --- CSS Personnalisé --- */
    <?php if (!empty($options['custom_css'])) {
        echo wp_strip_all_tags($options['custom_css']); 
    } ?>
    
    /* --- Animations --- */
    <?php if ($options['enable_animations']) : ?>
    .review-box-jlg.jlg-animate .rating-bar { 
        width: 0; 
    }
    .review-box-jlg.jlg-animate.is-in-view .rating-bar { 
        width: var(--rating-percent, 0%); 
    }
    .review-box-jlg.jlg-animate .score-circle, 
    .review-box-jlg.jlg-animate .global-score-text { 
        opacity: 0; 
        transition: transform 0.6s ease, opacity 0.6s ease; 
        transform: scale(0.9); 
    }
    .review-box-jlg.jlg-animate.is-in-view .score-circle, 
    .review-box-jlg.jlg-animate.is-in-view .global-score-text { 
        transform: scale(1); 
        opacity: 1; 
    }
    <?php endif; ?>
</style>

<div class="review-box-jlg <?php if ($options['enable_animations']) echo 'jlg-animate'; ?>">
    <div class="global-score-wrapper">
        <?php if($options['score_layout'] === 'circle'): ?>
            <div class="score-circle">
                <div class="score-value"><?php echo esc_html(number_format($average_score, 1, ',', ' ')); ?></div>
                <div class="score-label">Note Globale</div>
            </div>
        <?php else: ?>
            <div class="global-score-text">
                <div class="score-value"><?php echo esc_html(number_format($average_score, 1, ',', ' ')); ?></div>
                <div class="score-label">Note Globale</div>
            </div>
        <?php endif; ?>
    </div>
    
    <hr>
    
    <div class="rating-breakdown">
        <?php foreach ($scores as $key => $score_value) :
            $bar_color = JLG_Helpers::calculate_color_from_note($score_value, $options);
        ?>
            <div class="rating-item">
                <div class="rating-label">
                    <span><?php echo esc_html($categories[$key]); ?></span>
                    <span><?php echo esc_html(number_format($score_value, 1, ',', ' ')); ?> / 10</span>
                </div>
                <div class="rating-bar-container">
                    <div class="rating-bar" style="--rating-percent:<?php echo esc_attr($score_value * 10); ?>%; --bar-color: <?php echo esc_attr($bar_color); ?>;"></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
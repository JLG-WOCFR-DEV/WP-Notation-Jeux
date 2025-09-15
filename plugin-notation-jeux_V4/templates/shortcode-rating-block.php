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
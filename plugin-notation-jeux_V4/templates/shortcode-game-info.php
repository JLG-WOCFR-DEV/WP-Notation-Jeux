<?php
if (!defined('ABSPATH')) exit;

$palette = JLG_Helpers::get_color_palette();
$options = JLG_Helpers::get_plugin_options();
?>
<style>
    .jlg-game-info-box { background-color: <?php echo esc_attr($palette['bar_bg_color']); ?>; border-left: 4px solid <?php echo esc_attr($options['score_gradient_1']); ?>; padding: 20px; margin: 32px auto; max-width: 650px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; border-radius: 8px; }
    .jlg-game-info-box h3 { margin-top: 0; color: <?php echo esc_attr($palette['main_text_color']); ?>; }
    .jlg-game-info-box dl { display: grid; grid-template-columns: 150px 1fr; gap: 10px 20px; }
    .jlg-game-info-box dt { font-weight: 600; color: <?php echo esc_attr($palette['main_text_color']); ?>; }
    .jlg-game-info-box dd { margin: 0; color: <?php echo esc_attr($palette['secondary_text_color']); ?>; }
    .jlg-game-info-box .platforms-list span { display: inline-block; background: <?php echo esc_attr($palette['border_color']); ?>; padding: 3px 8px; border-radius: 4px; font-size: 0.9em; margin: 2px; }
</style>
<div class="jlg-game-info-box">
    <h3><?php echo esc_html($titre); ?></h3>
    <dl>
        <?php foreach ($champs_a_afficher as $key => $data): ?>
            <dt><?php echo esc_html($data['label']); ?></dt>
            <dd>
                <?php
                // Traitement spécial pour les plateformes
                if ($key === 'plateformes' && is_array($data['value'])) {
                    echo '<div class="platforms-list">';
                    foreach($data['value'] as $p) {
                        echo '<span>' . esc_html($p) . '</span>';
                    }
                    echo '</div>';
                } 
                // Traitement spécial pour la date
                elseif ($key === 'date_sortie') {
                    echo esc_html(date_i18n(get_option('date_format'), strtotime($data['value'])));
                } 
                // Affichage standard pour les autres champs
                else {
                    echo esc_html($data['value']);
                }
                ?>
            </dd>
        <?php endforeach; ?>
    </dl>
</div>
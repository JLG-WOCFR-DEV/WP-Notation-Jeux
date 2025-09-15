<?php
if (!defined('ABSPATH')) exit;
?>

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

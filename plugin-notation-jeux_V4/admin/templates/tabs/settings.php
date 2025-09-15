<?php
if (!defined('ABSPATH')) exit;

$settings_page = $settings_page ?? '';
?>
<h2>🎨 Configuration du Plugin</h2>
<p>Personnalisez l'apparence et le comportement du système de notation.</p>
<form action="options.php" method="post">
    <?php if (!empty($settings_page)) : ?>
        <?php settings_fields($settings_page); ?>
        <?php do_settings_sections($settings_page); ?>
    <?php endif; ?>
    <?php submit_button('💾 Enregistrer les modifications'); ?>
</form>


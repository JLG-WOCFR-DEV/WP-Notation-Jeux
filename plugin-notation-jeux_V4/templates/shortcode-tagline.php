<?php
if (!defined('ABSPATH')) exit;

$has_fr = !empty($tagline_fr);
$has_en = !empty($tagline_en);
$default_lang = $has_fr ? 'fr' : 'en';
?>

<div class="jlg-tagline-block">
    <?php if ($has_fr && $has_en): ?>
        <div class="jlg-tagline-flags">
            <img src="<?php echo esc_url(JLG_NOTATION_PLUGIN_URL . 'assets/flags/fr.svg'); ?>" data-lang="fr" class="jlg-lang-flag <?php if($default_lang == 'fr') echo 'active'; ?>" alt="<?php echo esc_attr__('Français', 'notation-jlg'); ?>">
            <img src="<?php echo esc_url(JLG_NOTATION_PLUGIN_URL . 'assets/flags/gb.svg'); ?>" data-lang="en" class="jlg-lang-flag <?php if($default_lang == 'en') echo 'active'; ?>" alt="<?php echo esc_attr__('English', 'notation-jlg'); ?>">
        </div>
    <?php endif; ?>

    <?php if ($has_fr): ?>
        <div class="jlg-tagline-text" data-lang="fr" <?php if($default_lang !== 'fr') echo 'style="display:none;"'; ?>>
            "<?php echo wp_kses_post($tagline_fr); ?>"
        </div>
    <?php endif; ?>
     <?php if ($has_en): ?>
        <div class="jlg-tagline-text" data-lang="en" <?php if($default_lang !== 'en') echo 'style="display:none;"'; ?>>
            "<?php echo wp_kses_post($tagline_en); ?>"
        </div>
    <?php endif; ?>
</div>

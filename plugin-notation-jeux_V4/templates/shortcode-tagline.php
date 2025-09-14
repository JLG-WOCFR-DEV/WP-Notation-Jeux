<?php
if (!defined('ABSPATH')) exit;

// On récupère la palette qui contient les bonnes couleurs pour le thème actif
$palette = JLG_Helpers::get_color_palette();

$has_fr = !empty($tagline_fr);
$has_en = !empty($tagline_en);
$default_lang = $has_fr ? 'fr' : 'en';
?>
<style>
    .jlg-tagline-block {
        /* CORRECTION : On utilise la variable $palette ici */
        background-color: <?php echo esc_attr($palette['tagline_bg_color']); ?>;
        color: <?php echo esc_attr($palette['tagline_text_color']); ?>;
        
        font-size: <?php echo intval($options['tagline_font_size']); ?>px;
        padding: 20px 25px;
        border-radius: 8px;
        margin: 32px auto;
        max-width: 650px;
        text-align: center;
        position: relative;
        box-sizing: border-box;
    }
    .jlg-tagline-text {
        font-style: italic;
    }
    .jlg-tagline-flags {
        position: absolute;
        top: 8px;
        right: 12px;
    }
    .jlg-lang-flag {
        width: 24px;
        height: auto;
        cursor: pointer;
        opacity: 0.5;
        transition: opacity 0.2s ease-in-out;
        margin-left: 5px;
    }
    .jlg-lang-flag.active {
        opacity: 1;
    }
</style>
<div class="jlg-tagline-block">
    <?php if ($has_fr && $has_en): ?>
        <div class="jlg-tagline-flags">
            <img src="<?php echo JLG_NOTATION_PLUGIN_URL . 'assets/flags/fr.svg'; ?>" data-lang="fr" class="jlg-lang-flag <?php if($default_lang == 'fr') echo 'active'; ?>" alt="Français">
            <img src="<?php echo JLG_NOTATION_PLUGIN_URL . 'assets/flags/gb.svg'; ?>" data-lang="en" class="jlg-lang-flag <?php if($default_lang == 'en') echo 'active'; ?>" alt="English">
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
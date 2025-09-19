<?php
if (!defined('ABSPATH')) exit;

$block_classes = ['jlg-all-in-one-block'];
$style_key = isset($atts['style']) ? sanitize_html_class($atts['style']) : 'moderne';
$block_classes[] = 'style-' . $style_key;

if (!empty($options['enable_animations'])) {
    $block_classes[] = 'animate-in';
}

$style_attr = '';
if (!empty($css_variables) && is_array($css_variables)) {
    $style_parts = [];
    foreach ($css_variables as $name => $value) {
        if ($value === '' || $value === null) {
            continue;
        }
        $style_parts[] = $name . ':' . $value;
    }

    if (!empty($style_parts)) {
        $style_attr = implode(';', $style_parts) . ';';
    }
}
?>
<div id="<?php echo esc_attr($block_id); ?>" class="<?php echo esc_attr(implode(' ', array_filter($block_classes))); ?>"<?php if ($style_attr) : ?> style="<?php echo esc_attr($style_attr); ?>"<?php endif; ?>>
    <?php if ($atts['afficher_tagline'] === 'oui' && (!empty($tagline_fr) || !empty($tagline_en))) : ?>
    <div class="jlg-aio-header">
        <?php if ($has_multiple_taglines) : ?>
        <div class="jlg-aio-flags">
            <img src="<?php echo esc_url(JLG_NOTATION_PLUGIN_URL . 'assets/flags/fr.svg'); ?>"
                 class="jlg-aio-flag active"
                 data-lang="fr"
                 alt="<?php esc_attr_e('Français', 'notation-jlg'); ?>">
            <img src="<?php echo esc_url(JLG_NOTATION_PLUGIN_URL . 'assets/flags/gb.svg'); ?>"
                 class="jlg-aio-flag"
                 data-lang="en"
                 alt="<?php esc_attr_e('English', 'notation-jlg'); ?>">
        </div>
        <?php endif; ?>

        <?php if (!empty($tagline_fr)) : ?>
        <div class="jlg-aio-tagline" data-lang="fr"><?php echo wp_kses_post($tagline_fr); ?></div>
        <?php endif; ?>

        <?php if (!empty($tagline_en)) : ?>
        <div class="jlg-aio-tagline" data-lang="en"<?php if ($has_multiple_taglines) : ?> style="display:none;"<?php endif; ?>><?php echo wp_kses_post($tagline_en); ?></div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($atts['afficher_notation'] === 'oui' && $average_score !== null) : ?>
    <div class="jlg-aio-rating">
        <div class="jlg-aio-main-score">
            <?php if (($options['score_layout'] ?? 'text') === 'circle') : ?>
            <div class="jlg-aio-score-circle">
                <div class="jlg-aio-score-value"><?php echo esc_html(number_format($average_score, 1, ',', ' ')); ?></div>
                <div class="jlg-aio-score-label"><?php esc_html_e('Note Globale', 'notation-jlg'); ?></div>
            </div>
            <?php else : ?>
            <div class="jlg-aio-score-value"><?php echo esc_html(number_format($average_score, 1, ',', ' ')); ?></div>
            <div class="jlg-aio-score-label"><?php esc_html_e('Note Globale', 'notation-jlg'); ?></div>
            <?php endif; ?>
        </div>

        <?php if (!empty($scores)) : ?>
        <div class="jlg-aio-scores-grid">
            <?php foreach ($scores as $key => $score_value) :
                if (!isset($categories[$key])) {
                    continue;
                }
                $bar_color = JLG_Helpers::calculate_color_from_note($score_value, $options);
                $width = max(0, min(100, $score_value * 10));
            ?>
            <div class="jlg-aio-score-item">
                <div class="jlg-aio-score-header">
                    <span class="jlg-aio-score-label"><?php echo esc_html($categories[$key]); ?></span>
                    <span class="jlg-aio-score-number"><?php echo esc_html(number_format($score_value, 1, ',', ' ')); ?> / 10</span>
                </div>
                <div class="jlg-aio-score-bar-bg">
                    <div class="jlg-aio-score-bar" style="--bar-color: <?php echo esc_attr($bar_color); ?>; --bar-width: <?php echo esc_attr($width); ?>%; width: <?php echo esc_attr($width); ?>%;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($atts['afficher_points'] === 'oui' && (!empty($pros_list) || !empty($cons_list))) : ?>
    <div class="jlg-aio-points">
        <?php if (!empty($pros_list)) : ?>
        <div class="jlg-aio-points-col pros">
            <div class="jlg-aio-points-title">
                <span class="jlg-aio-points-icon pros">+</span>
                <span><?php echo esc_html($atts['titre_points_forts']); ?></span>
            </div>
            <ul class="jlg-aio-points-list">
                <?php foreach ($pros_list as $pro) : ?>
                <li><?php echo esc_html(trim($pro)); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if (!empty($cons_list)) : ?>
        <div class="jlg-aio-points-col cons">
            <div class="jlg-aio-points-title">
                <span class="jlg-aio-points-icon cons">−</span>
                <span><?php echo esc_html($atts['titre_points_faibles']); ?></span>
            </div>
            <ul class="jlg-aio-points-list">
                <?php foreach ($cons_list as $con) : ?>
                <li><?php echo esc_html(trim($con)); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php if ($has_multiple_taglines || !empty($options['enable_animations'])) : ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const block = document.getElementById('<?php echo esc_js($block_id); ?>');
    if (!block) {
        return;
    }

    <?php if ($has_multiple_taglines) : ?>
    const flags = block.querySelectorAll('.jlg-aio-flag');
    const taglines = block.querySelectorAll('.jlg-aio-tagline');

    flags.forEach(function(flag) {
        flag.addEventListener('click', function() {
            const selectedLang = this.dataset.lang;

            flags.forEach(function(currentFlag) {
                currentFlag.classList.toggle('active', currentFlag === flag);
            });

            taglines.forEach(function(tagline) {
                tagline.style.display = (tagline.dataset.lang === selectedLang) ? 'block' : 'none';
            });
        });
    });
    <?php endif; ?>

    <?php if (!empty($options['enable_animations'])) : ?>
    if (block.classList.contains('animate-in')) {
        const observer = new IntersectionObserver(function(entries, obs) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    obs.unobserve(entry.target);
                }
            });
        }, { threshold: 0.2 });

        observer.observe(block);
    }
    <?php endif; ?>
});
</script>
<?php endif; ?>

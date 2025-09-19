<?php
if (!defined('ABSPATH')) exit;

$style_attribute = '';
if (!empty($css_variables)) {
    $style_attribute = ' style="' . esc_attr($css_variables) . '"';
}

$has_tagline = ($atts['afficher_tagline'] === 'oui' && (!empty($tagline_fr) || !empty($tagline_en)));
$has_dual_tagline = (!empty($tagline_fr) && !empty($tagline_en));
$show_rating = ($atts['afficher_notation'] === 'oui' && $average_score !== null);
$show_points = ($atts['afficher_points'] === 'oui' && (!empty($pros_list) || !empty($cons_list)));
?>

<div class="<?php echo esc_attr($block_classes); ?>"<?php echo $style_attribute; ?>>
    <?php if ($has_tagline): ?>
    <div class="jlg-aio-header">
        <?php if ($has_dual_tagline): ?>
        <div class="jlg-aio-flags">
            <img src="<?php echo esc_url(JLG_NOTATION_PLUGIN_URL . 'assets/flags/fr.svg'); ?>"
                 class="jlg-aio-flag active"
                 data-lang="fr"
                 alt="<?php echo esc_attr__('Français', 'notation-jlg'); ?>">
            <img src="<?php echo esc_url(JLG_NOTATION_PLUGIN_URL . 'assets/flags/gb.svg'); ?>"
                 class="jlg-aio-flag"
                 data-lang="en"
                 alt="<?php echo esc_attr__('English', 'notation-jlg'); ?>">
        </div>
        <?php endif; ?>

        <?php if (!empty($tagline_fr)): ?>
        <div class="jlg-aio-tagline" data-lang="fr">
            <?php echo wp_kses_post($tagline_fr); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($tagline_en)): ?>
        <div class="jlg-aio-tagline" data-lang="en"<?php echo $has_dual_tagline ? ' style="display:none;"' : ''; ?>>
            <?php echo wp_kses_post($tagline_en); ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($show_rating): ?>
    <div class="jlg-aio-rating">
        <div class="jlg-aio-main-score">
            <?php if ($score_layout === 'circle'): ?>
            <div class="jlg-aio-score-circle">
                <div class="jlg-aio-score-value"><?php echo esc_html(number_format($average_score, 1, ',', ' ')); ?></div>
                <div class="jlg-aio-score-label"><?php echo esc_html__('Note Globale', 'notation-jlg'); ?></div>
            </div>
            <?php else: ?>
            <div class="jlg-aio-score-value"><?php echo esc_html(number_format($average_score, 1, ',', ' ')); ?></div>
            <div class="jlg-aio-score-label"><?php echo esc_html__('Note Globale', 'notation-jlg'); ?></div>
            <?php endif; ?>
        </div>

        <div class="jlg-aio-scores-grid">
            <?php foreach ($scores as $key => $score_value): ?>
            <?php $bar_color = JLG_Helpers::calculate_color_from_note($score_value, $options); ?>
            <div class="jlg-aio-score-item">
                <div class="jlg-aio-score-header">
                    <span class="jlg-aio-score-label"><?php echo esc_html($categories[$key]); ?></span>
                    <span class="jlg-aio-score-number"><?php echo esc_html(number_format($score_value, 1, ',', ' ')); ?> / 10</span>
                </div>
                <div class="jlg-aio-score-bar-bg">
                    <div class="jlg-aio-score-bar"
                         style="--bar-color: <?php echo esc_attr($bar_color); ?>; --bar-width: <?php echo esc_attr($score_value * 10); ?>%; width: <?php echo esc_attr($score_value * 10); ?>%;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($show_points): ?>
    <div class="jlg-aio-points">
        <?php if (!empty($pros_list)): ?>
        <div class="jlg-aio-points-col pros">
            <div class="jlg-aio-points-title">
                <span class="jlg-aio-points-icon pros">+</span>
                <span><?php echo esc_html($atts['titre_points_forts']); ?></span>
            </div>
            <ul class="jlg-aio-points-list">
                <?php foreach ($pros_list as $pro): ?>
                <li><?php echo esc_html($pro); ?></li>
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
                <li><?php echo esc_html($con); ?></li>
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

            blockFlags.forEach(f => f.classList.remove('active'));
            this.classList.add('active');

            blockTaglines.forEach(t => {
                if (t.dataset.lang === selectedLang) {
                    t.style.display = 'block';
                } else {
                    t.style.display = 'none';
                }
            });
        });
    });

    <?php if ($animations_enabled): ?>
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

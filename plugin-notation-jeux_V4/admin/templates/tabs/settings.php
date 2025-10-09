<?php
if (! defined('ABSPATH')) {
    exit;
}

$settings_page     = $variables['settings_page'] ?? '';
$sections_overview = isset($variables['sections_overview']) && is_array($variables['sections_overview'])
    ? $variables['sections_overview']
    : array();
$preview_snapshot  = isset($variables['preview_snapshot']) && is_array($variables['preview_snapshot'])
    ? $variables['preview_snapshot']
    : array();
?>

<div class="jlg-settings-layout" data-jlg-settings-page="<?php echo esc_attr($settings_page); ?>">
    <aside class="jlg-settings-sidebar" aria-label="<?php esc_attr_e('Navigation des réglages', 'notation-jlg'); ?>">
        <?php if (! empty($sections_overview)) : ?>
            <nav class="jlg-settings-card jlg-settings-card--sidebar" aria-label="<?php esc_attr_e('Sommaire des sections de réglages', 'notation-jlg'); ?>">
                <h2 class="jlg-settings-card__title"><?php esc_html_e('Navigation rapide', 'notation-jlg'); ?></h2>
                <ol class="jlg-settings-toc">
                    <?php foreach ($sections_overview as $section) :
                        $section_id = sanitize_key($section['id'] ?? '');

                        if ($section_id === '') {
                            continue;
                        }

                        $icon    = isset($section['icon']) ? wp_strip_all_tags((string) $section['icon']) : '';
                        $title   = isset($section['title']) ? (string) $section['title'] : '';
                        $summary = isset($section['summary']) ? (string) $section['summary'] : '';
                        ?>
                        <li class="jlg-settings-toc__item">
                            <a href="#section-<?php echo esc_attr($section_id); ?>" class="jlg-settings-toc__link" data-section-id="<?php echo esc_attr($section_id); ?>">
                                <?php if ($icon !== '') : ?>
                                    <span class="jlg-settings-toc__icon" aria-hidden="true"><?php echo esc_html($icon); ?></span>
                                <?php endif; ?>
                                <span class="jlg-settings-toc__label"><?php echo esc_html($title); ?></span>
                                <?php if ($summary !== '') : ?>
                                    <span class="jlg-settings-toc__summary"><?php echo esc_html($summary); ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </nav>
        <?php endif; ?>

        <section class="jlg-settings-card jlg-settings-card--preview" aria-labelledby="jlg-theme-preview-title">
            <h2 id="jlg-theme-preview-title" class="jlg-settings-card__title"><?php esc_html_e('Aperçu instantané', 'notation-jlg'); ?></h2>
            <p class="jlg-settings-card__intro"><?php esc_html_e('Visualisez les couleurs, contrastes et effets néon appliqués à la note globale.', 'notation-jlg'); ?></p>
            <div class="jlg-theme-preview" data-theme-preview data-label-dark="<?php esc_attr_e('Thème sombre actif', 'notation-jlg'); ?>" data-label-light="<?php esc_attr_e('Thème clair actif', 'notation-jlg'); ?>">
                <div class="jlg-theme-preview__controls" role="group" aria-label="<?php esc_attr_e('Prévisualiser le thème', 'notation-jlg'); ?>">
                    <button type="button" class="button button-secondary jlg-theme-preview__switch is-active" data-preview-theme="dark"><?php esc_html_e('Thème sombre', 'notation-jlg'); ?></button>
                    <button type="button" class="button button-secondary jlg-theme-preview__switch" data-preview-theme="light"><?php esc_html_e('Thème clair', 'notation-jlg'); ?></button>
                </div>
                <div class="jlg-theme-preview__surface" data-preview-surface>
                    <div class="jlg-theme-preview__grid">
                        <div class="jlg-theme-preview__score-circle" data-preview-circle>
                            <span class="jlg-theme-preview__score-value" aria-hidden="true">89</span>
                            <span class="screen-reader-text"><?php esc_html_e('Aperçu de la note cercle', 'notation-jlg'); ?></span>
                            <span class="jlg-theme-preview__caption"><?php esc_html_e('Score cercle', 'notation-jlg'); ?></span>
                        </div>
                        <div class="jlg-theme-preview__score-text" data-preview-text>
                            <span class="jlg-theme-preview__score-value" data-preview-text-value>18 / 20</span>
                            <span class="jlg-theme-preview__caption"><?php esc_html_e('Score texte', 'notation-jlg'); ?></span>
                        </div>
                    </div>
                    <div class="jlg-theme-preview__progress" aria-hidden="true">
                        <span class="jlg-theme-preview__progress-bar" data-preview-bar></span>
                    </div>
                    <footer class="jlg-theme-preview__footer">
                        <span class="jlg-theme-preview__badge" data-preview-theme-indicator><?php esc_html_e('Thème sombre actif', 'notation-jlg'); ?></span>
                        <span class="jlg-theme-preview__hint"><?php esc_html_e('Les gradients et halos reflètent vos réglages actuels.', 'notation-jlg'); ?></span>
                    </footer>
                </div>
            </div>
        </section>
    </aside>

    <div class="jlg-settings-main">
        <h2 class="jlg-settings-title"><?php esc_html_e('Configuration du plugin', 'notation-jlg'); ?></h2>
        <p class="jlg-settings-subtitle"><?php esc_html_e('Personnalisez l’apparence et le comportement du système de notation.', 'notation-jlg'); ?></p>

        <form action="options.php" method="post" class="jlg-settings-form">
            <?php if (! empty($settings_page)) : ?>
                <?php settings_fields($settings_page); ?>
                <?php do_settings_sections($settings_page); ?>
            <?php endif; ?>
            <?php submit_button(__('Enregistrer les modifications', 'notation-jlg')); ?>
        </form>
    </div>
</div>

<?php if (! empty($preview_snapshot)) : ?>
    <script type="application/json" id="jlg-settings-preview-snapshot">
        <?php echo wp_json_encode($preview_snapshot); ?>
    </script>
<?php endif; ?>


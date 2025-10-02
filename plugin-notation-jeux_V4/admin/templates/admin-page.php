<?php
if (!defined('ABSPATH')) exit;

$page_title = $variables['page_title'] ?? '';
$tab_navigation = $variables['tab_navigation'] ?? '';
$tab_content = $variables['tab_content'] ?? '';
?>
<div class="wrap jlg-admin-page"<?php echo ! empty( $page_title ) ? ' role="region" aria-label="' . esc_attr( $page_title ) . '"' : ''; ?>>
    <?php if (!empty($page_title)) : ?>
        <h1><?php echo esc_html($page_title); ?></h1>
    <?php endif; ?>
    <?php echo $tab_navigation; ?>
    <div class="components-card jlg-admin-card" role="presentation">
        <div class="components-card__body">
            <?php echo $tab_content; ?>
        </div>
    </div>
</div>


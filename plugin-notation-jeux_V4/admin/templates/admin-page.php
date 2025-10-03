<?php
if (!defined('ABSPATH')) exit;

$page_title = $variables['page_title'] ?? '';
$tab_navigation = $variables['tab_navigation'] ?? '';
$tab_content = $variables['tab_content'] ?? '';
?>
<div class="wrap">
    <?php if (!empty($page_title)) : ?>
        <h1><?php echo esc_html($page_title); ?></h1>
    <?php endif; ?>
    <?php echo $tab_navigation; ?>
    <div class="jlg-admin-card">
        <?php echo $tab_content; ?>
    </div>
</div>


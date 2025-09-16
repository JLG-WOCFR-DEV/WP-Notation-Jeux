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
    <div style="background:#fff; padding:20px; margin-top:20px; border-radius:8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <?php echo $tab_content; ?>
    </div>
</div>


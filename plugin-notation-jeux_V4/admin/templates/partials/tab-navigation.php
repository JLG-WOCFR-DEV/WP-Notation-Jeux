<?php
if (!defined('ABSPATH')) exit;

$tabs = isset($variables['tabs']) && is_array($variables['tabs']) ? $variables['tabs'] : [];
$active_tab = $variables['active_tab'] ?? '';
$page_slug = $variables['page_slug'] ?? '';

if (empty($tabs)) {
    return;
}
?>
<h2 class="nav-tab-wrapper">
    <?php foreach ($tabs as $tab_key => $tab_label) :
        $active_class = ($active_tab === $tab_key) ? 'nav-tab-active' : '';
        $url = add_query_arg([
            'page' => $page_slug,
            'tab' => $tab_key,
        ], admin_url('admin.php'));
        ?>
        <a href="<?php echo esc_url($url); ?>" class="nav-tab <?php echo esc_attr($active_class); ?>">
            <?php echo esc_html($tab_label); ?>
        </a>
    <?php endforeach; ?>
</h2>


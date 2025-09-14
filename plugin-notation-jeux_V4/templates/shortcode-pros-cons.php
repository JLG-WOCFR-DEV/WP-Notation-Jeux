<?php
if (!defined('ABSPATH')) exit;

$palette = JLG_Helpers::get_color_palette();
$options = JLG_Helpers::get_plugin_options();
?>
<style>
    .jlg-pros-cons-wrapper {
        display: flex; gap: 30px; margin: 32px auto; max-width: 650px; 
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; 
        flex-wrap: wrap; background-color: <?php echo esc_attr($palette['bar_bg_color']); ?>; 
        padding: 20px; border-radius: 8px;
    }
    .jlg-pros-cons-col { flex: 1; min-width: 250px; }
    .jlg-pros-cons-col h3 {
        font-size: 1.2rem; display: flex; align-items: center; 
        border-bottom: 2px solid <?php echo esc_attr($palette['border_color']); ?>; 
        padding-bottom: 8px; margin-top: 0; color: <?php echo esc_attr($palette['main_text_color']); ?>;
    }
    .jlg-pros-cons-col .icon { margin-right: 10px; font-weight: bold; font-size: 1.5rem; }
    .jlg-pros-cons-col ul { list-style: none; padding: 0; margin: 15px 0 0 0; }
    .jlg-pros-cons-col li { color: <?php echo esc_attr($palette['secondary_text_color']); ?>; margin-bottom: 10px; line-height: 1.5; }
    .icon-pros { color: <?php echo esc_attr($options['color_high']); ?>; }
    .icon-cons { color: <?php echo esc_attr($options['color_low']); ?>; }
</style>
<div class="jlg-pros-cons-wrapper">
    <?php if (!empty($pros_list)): ?>
    <div class="jlg-pros-cons-col">
        <h3><span class="icon icon-pros">+</span> Points Forts</h3>
        <ul><?php foreach ($pros_list as $pro) echo '<li>' . esc_html(trim($pro)) . '</li>'; ?></ul>
    </div>
    <?php endif; ?>
    <?php if (!empty($cons_list)): ?>
    <div class="jlg-pros-cons-col">
        <h3><span class="icon icon-cons">-</span> Points Faibles</h3>
        <ul><?php foreach ($cons_list as $con) echo '<li>' . esc_html(trim($con)) . '</li>'; ?></ul>
    </div>
    <?php endif; ?>
</div>
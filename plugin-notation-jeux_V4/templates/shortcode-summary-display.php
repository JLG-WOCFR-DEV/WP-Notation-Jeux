<?php
/**
 * Template pour l'affichage du tableau/grille récapitulatif
 *
 * Variables disponibles :
 * - $query : WP_Query object
 * - $atts : Attributs du shortcode
 * - $paged : Page actuelle
 * - $orderby : Tri actuel
 * - $order : Ordre actuel (ASC/DESC)
 */

if (!defined('ABSPATH')) exit;

$table_id = isset($atts['id']) ? sanitize_html_class($atts['id']) : 'jlg-table-' . uniqid();
$layout = isset($atts['layout']) ? $atts['layout'] : 'table';
$columns = is_array($colonnes) ? $colonnes : [];
$available_columns = is_array($colonnes_disponibles) ? $colonnes_disponibles : [];
$current_orderby = !empty($orderby) ? $orderby : 'date';
$current_order = !empty($order) ? $order : 'DESC';
$current_cat_filter = isset($cat_filter) ? intval($cat_filter) : 0;
$columns_attr = !empty($columns) ? implode(',', array_map('sanitize_key', $columns)) : '';
?>

<div
    id="<?php echo esc_attr($table_id); ?>"
    class="jlg-summary-wrapper"
    data-posts-per-page="<?php echo esc_attr(intval($atts['posts_per_page'])); ?>"
    data-layout="<?php echo esc_attr($layout); ?>"
    data-categorie="<?php echo esc_attr($atts['categorie']); ?>"
    data-colonnes="<?php echo esc_attr($columns_attr); ?>"
    data-orderby="<?php echo esc_attr($current_orderby); ?>"
    data-order="<?php echo esc_attr($current_order); ?>"
    data-paged="<?php echo esc_attr(intval($paged)); ?>"
    data-cat-filter="<?php echo esc_attr($current_cat_filter); ?>"
>
    
    <?php if ($atts['layout'] === 'table') : ?>
        <!-- Filtres -->
        <div class="jlg-summary-filters">
            <form method="get" action="">
                <input type="hidden" name="orderby" value="<?php echo esc_attr($current_orderby); ?>">
                <input type="hidden" name="order" value="<?php echo esc_attr($current_order); ?>">
                <?php
                wp_dropdown_categories([
                    'show_option_all' => __('Toutes les catégories', 'notation-jlg'),
                    'orderby' => 'name',
                    'hide_empty' => 1,
                    'name' => 'cat_filter',
                    'id' => 'jlg_cat_filter',
                    'selected' => $current_cat_filter,
                    'hierarchical' => true
                ]);
                ?>
                <input type="submit" value="<?php echo esc_attr__('Filtrer', 'notation-jlg'); ?>">
            </form>
        </div>
    <?php endif; ?>

    <div class="jlg-summary-content">
        <?php
        echo JLG_Frontend::get_template_html('summary-table-fragment', [
            'query'                => $query,
            'atts'                 => $atts,
            'paged'                => $paged,
            'orderby'              => $orderby,
            'order'                => $order,
            'colonnes'             => $columns,
            'colonnes_disponibles' => $available_columns,
            'error_message'        => isset($error_message) ? $error_message : '',
            'cat_filter'           => $current_cat_filter,
            'table_id'             => $table_id,
        ]);
        ?>
    </div>
</div>

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
$current_letter_filter = isset($letter_filter) ? sanitize_text_field($letter_filter) : (isset($atts['letter_filter']) ? sanitize_text_field($atts['letter_filter']) : '');
$current_genre_filter = isset($genre_filter) ? sanitize_text_field($genre_filter) : (isset($atts['genre_filter']) ? sanitize_text_field($atts['genre_filter']) : '');
$show_filters = empty($atts['categorie']);
$current_cat_filter = ($show_filters && isset($cat_filter)) ? intval($cat_filter) : 0;
$columns_attr = !empty($columns) ? implode(',', array_map('sanitize_key', $columns)) : '';
$genre_taxonomy = apply_filters('jlg_summary_genre_taxonomy', 'jlg_game_genre');
$has_genre_taxonomy = !empty($genre_taxonomy) && taxonomy_exists($genre_taxonomy);
$genre_terms = [];

if ($show_filters && $has_genre_taxonomy) {
    $genre_terms = get_terms([
        'taxonomy'   => $genre_taxonomy,
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ]);

    if (is_wp_error($genre_terms)) {
        $genre_terms = [];
    }
}

$letters = range('A', 'Z');
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
    data-letter-filter="<?php echo esc_attr($current_letter_filter); ?>"
    data-genre-filter="<?php echo esc_attr($current_genre_filter); ?>"
>

    <?php if ($show_filters) : ?>
        <!-- Filtres -->
        <div class="jlg-summary-filters">
            <div class="jlg-summary-letter-filter" role="group" aria-label="<?php esc_attr_e('Filtrer par lettre', 'notation-jlg'); ?>">
                <button type="button" class="<?php echo $current_letter_filter === '' ? 'is-active' : ''; ?>" data-letter="">
                    <?php esc_html_e('Tous', 'notation-jlg'); ?>
                </button>
                <?php foreach ($letters as $letter) : ?>
                    <button type="button" data-letter="<?php echo esc_attr($letter); ?>" class="<?php echo ($current_letter_filter === $letter) ? 'is-active' : ''; ?>">
                        <?php echo esc_html($letter); ?>
                    </button>
                <?php endforeach; ?>
                <button type="button" data-letter="#" class="<?php echo ($current_letter_filter === '#') ? 'is-active' : ''; ?>">
                    <?php esc_html_e('0-9', 'notation-jlg'); ?>
                </button>
            </div>

            <form method="get" action="" class="jlg-summary-filters-form">
                <input type="hidden" name="orderby" value="<?php echo esc_attr($current_orderby); ?>">
                <input type="hidden" name="order" value="<?php echo esc_attr($current_order); ?>">
                <input type="hidden" name="letter_filter" value="<?php echo esc_attr($current_letter_filter); ?>">
                <?php
                wp_dropdown_categories([
                    'show_option_all' => __('Toutes les catégories', 'notation-jlg'),
                    'orderby' => 'name',
                    'hide_empty' => 1,
                    'name' => 'cat_filter',
                    'id' => $table_id . '_cat_filter',
                    'selected' => $current_cat_filter,
                    'hierarchical' => true,
                    'class' => 'jlg-cat-filter-select',
                ]);
                ?>
                <?php if ($has_genre_taxonomy && !empty($genre_terms)) : ?>
                    <label for="<?php echo esc_attr($table_id . '_genre_filter'); ?>" class="screen-reader-text">
                        <?php esc_html_e('Filtrer par genre', 'notation-jlg'); ?>
                    </label>
                    <select name="genre_filter" id="<?php echo esc_attr($table_id . '_genre_filter'); ?>" class="jlg-genre-filter-select">
                        <option value="" <?php selected($current_genre_filter, ''); ?>><?php esc_html_e('Tous les genres', 'notation-jlg'); ?></option>
                        <?php foreach ($genre_terms as $term) : ?>
                            <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($current_genre_filter, $term->slug); ?>>
                                <?php echo esc_html($term->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else : ?>
                    <input type="hidden" name="genre_filter" value="<?php echo esc_attr($current_genre_filter); ?>">
                <?php endif; ?>
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

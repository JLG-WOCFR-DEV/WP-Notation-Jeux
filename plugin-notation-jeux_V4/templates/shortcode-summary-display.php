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

// Récupération des options et palette
$options = JLG_Helpers::get_plugin_options();
$palette = JLG_Helpers::get_color_palette();

// Parser les colonnes demandées
$colonnes = array_map('trim', explode(',', $atts['colonnes']));

// Définir les colonnes disponibles
$colonnes_disponibles = [
    'titre' => ['label' => 'Titre du jeu', 'sortable' => true, 'key' => 'title'],
    'date' => ['label' => 'Date', 'sortable' => true, 'key' => 'date'],
    'note' => ['label' => 'Note', 'sortable' => true, 'key' => 'average_score'],
    'developpeur' => ['label' => 'Développeur', 'sortable' => false],
    'editeur' => ['label' => 'Éditeur', 'sortable' => false]
];

// ID unique pour le tableau
$table_id = esc_attr($atts['id']);

// Fonction helper locale pour créer les en-têtes triables
if (!function_exists('jlg_print_sortable_header')) {
    function jlg_print_sortable_header($col, $col_info, $current_orderby, $current_order, $table_id) {
        if ($col_info['sortable']) {
            $sort_key = isset($col_info['key']) ? $col_info['key'] : $col;
            $new_order = ($current_orderby === $sort_key && $current_order === 'ASC') ? 'DESC' : 'ASC';
            $url = add_query_arg([
                'orderby' => $sort_key,
                'order' => $new_order
            ], '#' . $table_id);
            
            $indicator = '';
            if ($current_orderby === $sort_key || $current_orderby === $col) {
                $indicator = $current_order === 'ASC' ? ' ▲' : ' ▼';
            }
            
            $class = 'sortable';
            if ($current_orderby === $sort_key || $current_orderby === $col) {
                $class .= ' sorted ' . strtolower($current_order);
            }
            
            echo '<th class="' . esc_attr($class) . '">';
            echo '<a href="' . esc_url($url) . '">' . esc_html($col_info['label']) . $indicator . '</a>';
            echo '</th>';
        } else {
            echo '<th>' . esc_html($col_info['label']) . '</th>';
        }
    }
}
?>

<style>
    /* Styles pour les filtres */
    .jlg-summary-filters { 
        margin-bottom: 20px; 
        text-align: right; 
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; 
    }
    .jlg-summary-filters select, 
    .jlg-summary-filters input[type="submit"] {
        padding: 8px 12px; 
        border-radius: 4px; 
        border: 1px solid <?php echo esc_attr($palette['border_color']); ?>;
        background-color: <?php echo esc_attr($palette['bg_color']); ?>; 
        color: <?php echo esc_attr($palette['main_text_color']); ?>;
        vertical-align: middle; 
        transition: all 0.2s ease;
    }
    .jlg-summary-filters input[type="submit"] {
        background-color: <?php echo esc_attr($options['score_gradient_1']); ?>; 
        color: #fff;
        border-color: <?php echo esc_attr($options['score_gradient_1']); ?>; 
        cursor: pointer;
    }
    .jlg-summary-filters input[type="submit"]:hover {
        background-color: <?php echo esc_attr(JLG_Helpers::adjust_hex_brightness($options['score_gradient_1'], 20)); ?>;
        border-color: <?php echo esc_attr(JLG_Helpers::adjust_hex_brightness($options['score_gradient_1'], 20)); ?>;
    }
    
    /* Styles pour le tableau */
    .jlg-summary-table-wrapper { 
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; 
    }
    .jlg-summary-table { 
        width: 100%; 
        border-collapse: collapse; 
        margin: 2em 0; 
        font-size: 0.9em; 
        color: <?php echo esc_attr($options['table_row_text_color']); ?>;
        background-color: <?php echo esc_attr($options['table_row_bg_color']); ?>;
    }
    .jlg-summary-table th, .jlg-summary-table td { 
        padding: 12px 15px; 
        text-align: left; 
    }
    .jlg-summary-table thead th { 
        background-color: <?php echo esc_attr($options['table_header_bg_color']); ?>; 
        color: <?php echo esc_attr($options['table_header_text_color']); ?>; 
        font-weight: 600; 
    }
    .jlg-summary-table th.sortable a { 
        color: inherit; 
        text-decoration: none; 
        display: block; 
    }
    .jlg-summary-table th.sorted a { 
        color: <?php echo esc_attr($options['score_gradient_1']); ?>; 
    }
    .jlg-summary-table tbody tr:hover { 
        background-color: <?php echo esc_attr(JLG_Helpers::adjust_hex_brightness($palette['bg_color_secondary'], 5)); ?>; 
    }
    .jlg-summary-table td a { 
        color: <?php echo esc_attr(JLG_Helpers::adjust_hex_brightness($options['table_row_text_color'], 20)); ?>; 
        font-weight: 500; 
        text-decoration: none; 
    }

    <?php if (!empty($options['table_zebra_striping'])) : ?>
    .jlg-summary-table tbody tr:nth-child(even) { 
        background-color: <?php echo esc_attr($options['table_zebra_bg_color']); ?>; 
    }
    .jlg-summary-table tbody tr:nth-child(even):hover { 
        background-color: <?php echo esc_attr(JLG_Helpers::adjust_hex_brightness($options['table_zebra_bg_color'], 5)); ?>; 
    }
    <?php endif; ?>

    <?php 
    $border_color_for_table = $palette['border_color'];
    switch ($options['table_border_style']) {
        case 'horizontal': 
            echo '.jlg-summary-table th, .jlg-summary-table td { border-bottom:' . intval($options['table_border_width']) . 'px solid ' . esc_attr($border_color_for_table) . ';}'; 
            break;
        case 'full': 
            echo '.jlg-summary-table th, .jlg-summary-table td { border:' . intval($options['table_border_width']) . 'px solid ' . esc_attr($border_color_for_table) . ';}'; 
            break;
    } 
    ?>

    /* Styles pour la grille */
    .jlg-summary-grid-wrapper { 
        display: grid; 
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); 
        gap: 20px; 
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; 
    }
    .jlg-game-card { 
        position: relative; 
        display: block; 
        overflow: hidden; 
        border-radius: 8px; 
        box-shadow: 0 4px 6px rgba(0,0,0,.3); 
        transition: all 0.2s ease; 
    }
    .jlg-game-card:hover { 
        transform: translateY(-5px); 
        box-shadow: 0 8px 12px rgba(0,0,0,.4); 
    }
    .jlg-game-card img { 
        width: 100%; 
        height: 100%; 
        object-fit: cover; 
        transition: transform 0.3s ease; 
    }
    .jlg-game-card:hover img { 
        transform: scale(1.05); 
    }
    .jlg-game-card-score { 
        position: absolute; 
        top: 10px; 
        right: 10px; 
        z-index: 2; 
        background: rgba(0,0,0,.8); 
        color: #fff; 
        font-weight: bold; 
        font-size: 1.2rem; 
        padding: 5px 10px; 
        border-radius: 6px; 
        backdrop-filter: blur(5px); 
    }
    .jlg-game-card-title { 
        position: absolute; 
        bottom: 0; 
        left: 0; 
        right: 0; 
        z-index: 2; 
        background: linear-gradient(to top, rgba(0,0,0,.9) 0%, rgba(0,0,0,0) 100%); 
        padding: 30px 15px 15px; 
    }
    .jlg-game-card-title span { 
        color: #fff; 
        font-weight: 600; 
        text-decoration: none; 
        font-size: 1.1rem; 
    }
    
    /* Styles pour la pagination */
    .jlg-pagination { 
        text-align: center; 
        margin-top: 20px; 
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; 
    }
    .jlg-pagination .page-numbers { 
        padding: 8px 12px; 
        margin: 0 2px; 
        border: 1px solid <?php echo esc_attr($palette['border_color']); ?>; 
        background-color: <?php echo esc_attr($palette['bg_color_secondary']); ?>; 
        color: <?php echo esc_attr($palette['secondary_text_color']); ?>; 
        text-decoration: none; 
        border-radius: 4px; 
    }
    .jlg-pagination .page-numbers:hover, 
    .jlg-pagination .page-numbers.current { 
        background-color: <?php echo esc_attr($options['score_gradient_1']); ?>; 
        border-color: <?php echo esc_attr($options['score_gradient_1']); ?>; 
        color: #fff; 
    }
</style>

<div id="<?php echo $table_id; ?>" class="jlg-summary-wrapper">
    
    <?php if ($atts['layout'] === 'table') : ?>
        <!-- Filtres -->
        <div class="jlg-summary-filters">
            <form method="get" action="">
                <?php
                if (isset($_GET['orderby'])) echo '<input type="hidden" name="orderby" value="' . esc_attr($_GET['orderby']) . '">';
                if (isset($_GET['order'])) echo '<input type="hidden" name="order" value="' . esc_attr($_GET['order']) . '">';
                wp_dropdown_categories([
                    'show_option_all' => 'Toutes les catégories', 
                    'orderby' => 'name', 
                    'hide_empty' => 1, 
                    'name' => 'cat_filter', 
                    'id' => 'jlg_cat_filter', 
                    'selected' => isset($_GET['cat_filter']) ? intval($_GET['cat_filter']) : 0, 
                    'hierarchical' => true
                ]);
                ?>
                <input type="submit" value="Filtrer">
            </form>
        </div>
    <?php endif; ?>

    <?php if ($atts['layout'] === 'grid') : ?>
        <!-- Affichage en grille -->
        <div class="jlg-summary-grid-wrapper">
            <?php if ($query->have_posts()) : 
                while ($query->have_posts()) : $query->the_post(); 
                    $post_id = get_the_ID();
                    $score = get_post_meta($post_id, '_jlg_average_score', true);
                    $cover_url = get_post_meta($post_id, '_jlg_cover_image_url', true);
                    if (empty($cover_url)) { 
                        $cover_url = get_the_post_thumbnail_url($post_id, 'medium_large'); 
                    } 
            ?>
                <a href="<?php the_permalink(); ?>" class="jlg-game-card">
                    <div class="jlg-game-card-score"><?php echo esc_html($score); ?></div>
                    <?php if ($cover_url) : ?>
                        <img src="<?php echo esc_url($cover_url); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy">
                    <?php endif; ?>
                    <div class="jlg-game-card-title">
                        <span><?php the_title(); ?></span>
                    </div>
                </a>
            <?php endwhile; 
            else : ?>
                <p>Aucun article trouvé pour cette sélection.</p>
            <?php endif; ?>
        </div>
    <?php else : ?>
        <!-- Affichage en tableau -->
        <div class="jlg-summary-table-wrapper">
            <table class="jlg-summary-table">
                <thead>
                    <tr>
                        <?php foreach ($colonnes as $col) : 
                            if (!isset($colonnes_disponibles[$col])) continue;
                            jlg_print_sortable_header($col, $colonnes_disponibles[$col], $orderby, $order, $table_id);
                        endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($query->have_posts()) : 
                        while ($query->have_posts()) : $query->the_post(); 
                            $post_id = get_the_ID();
                    ?>
                        <tr>
                            <?php foreach ($colonnes as $col) : 
                                if (!isset($colonnes_disponibles[$col])) continue;
                                echo '<td data-label="' . esc_attr($colonnes_disponibles[$col]['label']) . '">';
                                
                                switch ($col) {
                                    case 'titre':
                                        echo '<a href="' . get_permalink() . '">' . get_the_title() . '</a>';
                                        break;
                                    case 'date':
                                        echo get_the_date();
                                        break;
                                    case 'note':
                                        $score = get_post_meta($post_id, '_jlg_average_score', true);
                                        echo '<strong>' . esc_html($score ?: 'N/A') . '</strong> / 10';
                                        break;
                                    case 'developpeur':
                                        echo esc_html(get_post_meta($post_id, '_jlg_developpeur', true) ?: '-');
                                        break;
                                    case 'editeur':
                                        echo esc_html(get_post_meta($post_id, '_jlg_editeur', true) ?: '-');
                                        break;
                                }
                                echo '</td>';
                            endforeach; ?>
                        </tr>
                    <?php endwhile; 
                    else : ?>
                        <tr>
                            <td colspan="<?php echo count($colonnes); ?>">Aucun article trouvé pour cette sélection.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php
    // Pagination
    $pagination_links = paginate_links([
        'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))) . '#' . $table_id, 
        'format' => '?paged=%#%', 
        'current' => max(1, $paged), 
        'total' => $query->max_num_pages, 
        'prev_text' => '« Précédent', 
        'next_text' => 'Suivant »'
    ]);
    
    if ($pagination_links) {
        echo '<nav class="jlg-pagination">' . $pagination_links . '</nav>';
    }
    
    wp_reset_postdata();
    ?>
</div>
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

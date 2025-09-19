<?php
/**
 * Fragment HTML pour l'affichage du tableau ou de la grille récapitulative.
 */

if (!defined('ABSPATH')) {
    exit;
}

$columns = is_array($colonnes) ? $colonnes : [];
$available_columns = is_array($colonnes_disponibles) ? $colonnes_disponibles : [];
$table_id = !empty($table_id) ? sanitize_html_class($table_id) : '';
if ($table_id === '' && isset($atts['id'])) {
    $table_id = sanitize_html_class($atts['id']);
}
$layout = isset($atts['layout']) ? $atts['layout'] : 'table';
$current_orderby = !empty($orderby) ? $orderby : 'date';
$current_order = !empty($order) ? strtoupper($order) : 'DESC';
$current_cat_filter = isset($cat_filter) ? intval($cat_filter) : 0;
$default_empty_message = '<p>' . esc_html__('Aucun article trouvé pour cette sélection.', 'notation-jlg') . '</p>';
$empty_message = !empty($error_message) ? $error_message : $default_empty_message;
$columns_count = count($columns);

if ($columns_count === 0) {
    $columns_count = 1;
}

if (!function_exists('jlg_print_sortable_header')) {
    function jlg_print_sortable_header($col, $col_info, $current_orderby, $current_order, $table_id) {
        if (!isset($col_info['sortable']) || !$col_info['sortable']) {
            echo '<th>' . esc_html($col_info['label']) . '</th>';
            return;
        }

        $sort_key = isset($col_info['key']) ? $col_info['key'] : $col;
        $new_order = ($current_orderby === $sort_key && $current_order === 'ASC') ? 'DESC' : 'ASC';
        $url = add_query_arg([
            'orderby' => $sort_key,
            'order'   => $new_order,
        ]);

        if (!empty($table_id)) {
            $url .= '#' . $table_id;
        }

        $indicator = '';
        if ($current_orderby === $sort_key || $current_orderby === $col) {
            $indicator = $current_order === 'ASC'
                ? esc_html__(' ▲', 'notation-jlg')
                : esc_html__(' ▼', 'notation-jlg');
        }

        $class = 'sortable';
        if ($current_orderby === $sort_key || $current_orderby === $col) {
            $class .= ' sorted ' . strtolower($current_order);
        }

        echo '<th class="' . esc_attr($class) . '">';
        echo '<a href="' . esc_url($url) . '">' . esc_html($col_info['label']) . $indicator . '</a>';
        echo '</th>';
    }
}

if ($layout === 'grid') :
    ?>
    <div class="jlg-summary-grid-wrapper">
        <?php if ($query instanceof WP_Query && $query->have_posts()) :
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
        else :
            echo wp_kses_post($empty_message);
        endif; ?>
    </div>
<?php
else :
    ?>
    <div class="jlg-summary-table-wrapper">
        <table class="jlg-summary-table">
            <thead>
                <tr>
                    <?php
                    foreach ($columns as $col) {
                        if (!isset($available_columns[$col])) {
                            continue;
                        }
                        jlg_print_sortable_header($col, $available_columns[$col], $current_orderby, $current_order, $table_id);
                    }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($query instanceof WP_Query && $query->have_posts()) :
                    while ($query->have_posts()) : $query->the_post();
                        $post_id = get_the_ID();
                        ?>
                        <tr>
                            <?php
                            foreach ($columns as $col) {
                                if (!isset($available_columns[$col])) {
                                    continue;
                                }
                                echo '<td data-label="' . esc_attr($available_columns[$col]['label']) . '">';

                                switch ($col) {
                                    case 'titre':
                                        echo '<a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a>';
                                        break;
                                    case 'date':
                                        echo esc_html(get_the_date());
                                        break;
                                    case 'note':
                                        $score = get_post_meta($post_id, '_jlg_average_score', true);
                                        /* translators: Abbreviation meaning that the average score is not available. */
                                        $score_display = $score ?: __('N/A', 'notation-jlg');
                                        echo '<strong>' . esc_html($score_display) . '</strong> ';
                                        printf(
                                            /* translators: %s: Maximum possible rating value. */
                                            esc_html__('/ %s', 'notation-jlg'),
                                            10
                                        );
                                        break;
                                    case 'developpeur':
                                        $developer = get_post_meta($post_id, '_jlg_developpeur', true) ?: __('-', 'notation-jlg');
                                        echo esc_html($developer);
                                        break;
                                    case 'editeur':
                                        $publisher = get_post_meta($post_id, '_jlg_editeur', true) ?: __('-', 'notation-jlg');
                                        echo esc_html($publisher);
                                        break;
                                }
                                echo '</td>';
                            }
                            ?>
                        </tr>
                    <?php endwhile;
                else :
                    ?>
                    <tr>
                        <td colspan="<?php echo esc_attr($columns_count); ?>"><?php echo wp_kses_post($empty_message); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php
endif;

if ($query instanceof WP_Query) {
    $total_pages = intval($query->max_num_pages);
} else {
    $total_pages = 0;
}

if ($query instanceof WP_Query) {
    wp_reset_postdata();
}

if ($total_pages > 1) {
    $pagination_args = [
        'base'      => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
        'format'    => '?paged=%#%',
        'current'   => max(1, intval($paged)),
        'total'     => $total_pages,
        'prev_text' => __('« Précédent', 'notation-jlg'),
        'next_text' => __('Suivant »', 'notation-jlg'),
        'add_args'  => [
            'orderby' => $current_orderby,
            'order'   => $current_order,
        ],
    ];

    if ($current_cat_filter > 0) {
        $pagination_args['add_args']['cat_filter'] = $current_cat_filter;
    }

    $pagination_links = paginate_links($pagination_args);

    if (!empty($pagination_links) && !empty($table_id)) {
        $pagination_links = preg_replace(
            '/href="([^"]+)"/i',
            'href="$1#' . $table_id . '"',
            $pagination_links
        );
    }

    if (!empty($pagination_links)) {
        echo '<nav class="jlg-pagination">' . $pagination_links . '</nav>';
    }
}

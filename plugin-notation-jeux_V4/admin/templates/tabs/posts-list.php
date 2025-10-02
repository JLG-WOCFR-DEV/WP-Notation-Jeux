<?php
if (!defined('ABSPATH')) exit;

$has_rated_posts = $variables['has_rated_posts'] ?? false;
$empty_state = isset($variables['empty_state']) && is_array($variables['empty_state']) ? $variables['empty_state'] : [];
$stats = isset($variables['stats']) && is_array($variables['stats']) ? $variables['stats'] : [];
$columns = isset($variables['columns']) && is_array($variables['columns']) ? $variables['columns'] : [];
$posts = isset($variables['posts']) && is_array($variables['posts']) ? $variables['posts'] : [];
$pagination = $variables['pagination'] ?? '';
$print_button_label = $variables['print_button_label'] ?? '';
$column_count = count($columns) + 2;
?>
<h2>📊 Vos Articles avec Notation</h2>

<?php if (!$has_rated_posts) : ?>
    <div class="components-card jlg-empty-state" role="status" aria-live="polite">
        <div class="components-card__body">
            <h3 class="jlg-empty-state__title">🎮 Aucun test trouvé</h3>
            <p class="jlg-empty-state__description">Créez votre premier article avec notation !</p>
            <a href="<?php echo esc_url($empty_state['create_post_url'] ?? admin_url('post-new.php')); ?>" class="button button-primary jlg-empty-state__action">✏️ Créer un Test</a>
        </div>
    </div>
<?php else : ?>
    <?php if (!empty($stats)) : ?>
        <div class="notice notice-info jlg-stats-notice" role="status" aria-live="polite">
            <p>
                <?php
                printf(
                    /* translators: 1: total posts, 2: current page, 3: total pages, 4: posts displayed */
                    '<strong>%1$d</strong> articles avec notation trouvés • Page <strong>%2$d</strong> sur <strong>%3$d</strong> • Affichage de <strong>%4$d</strong> articles',
                    intval($stats['total_items'] ?? 0),
                    intval($stats['current_page'] ?? 1),
                    max(1, intval($stats['total_pages'] ?? 1)),
                    intval($stats['display_count'] ?? 0)
                );
                ?>
            </p>
        </div>
    <?php endif; ?>

    <table class="wp-list-table widefat striped" role="table">
        <thead>
            <tr>
                <?php foreach ($columns as $column) : ?>
                    <th scope="col" class="<?php echo esc_attr($column['class'] ?? ''); ?>" aria-sort="<?php echo esc_attr($column['aria_sort'] ?? 'none'); ?>">
                        <a href="<?php echo esc_url($column['url'] ?? '#'); ?>" class="jlg-link--reset">
                            <span><?php echo esc_html($column['label'] ?? ''); ?></span>
                            <span class="sorting-indicator" aria-hidden="true"></span>
                        </a>
                    </th>
                <?php endforeach; ?>
                <th scope="col"><?php echo esc_html('Catégories'); ?></th>
                <th scope="col"><?php echo esc_html('Actions'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($posts)) : ?>
                <?php foreach ($posts as $post) :
                    $categories = isset($post['categories']) && is_array($post['categories']) ? $post['categories'] : [];
                    $score_color = $post['score_color'] ?? '#0073aa';
                    $score_style = sprintf(' style="--jlg-score-color:%s;"', esc_attr($score_color));
                    ?>
                    <tr>
                        <td>
                            <strong>
                                <a href="<?php echo esc_url($post['edit_link'] ?? '#'); ?>" class="jlg-link--reset">
                                    <?php echo esc_html($post['title'] ?? ''); ?>
                                </a>
                            </strong>
                        </td>
                        <td><?php echo esc_html($post['date'] ?? ''); ?></td>
                        <td>
                            <strong class="jlg-score"<?php echo $score_style; ?>><?php echo esc_html($post['score_display'] ?? ''); ?></strong>/10
                        </td>
                        <td><?php echo !empty($categories) ? esc_html(implode(', ', $categories)) : '-'; ?></td>
                        <td class="jlg-admin-actions jlg-admin-actions--inline">
                            <a href="<?php echo esc_url($post['view_link'] ?? '#'); ?>" target="_blank" rel="noopener noreferrer" class="jlg-link--action">👁 Voir</a>
                            <span aria-hidden="true">|</span>
                            <a href="<?php echo esc_url($post['edit_link'] ?? '#'); ?>" class="jlg-link--action">✏️ Modifier</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="<?php echo (int) max(1, $column_count); ?>">Aucun article trouvé pour cette page.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if (!empty($pagination)) : ?>
        <div class="tablenav bottom" role="navigation" aria-label="Pagination des articles notés">
            <div class="tablenav-pages">
                <?php echo wp_kses_post($pagination); ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($print_button_label)) : ?>
        <div class="jlg-admin-actions jlg-admin-actions--end">
            <a href="#" class="button jlg-admin-actions__print" onclick="window.print(); return false;">
                <?php echo esc_html($print_button_label); ?>
            </a>
        </div>
    <?php endif; ?>
<?php endif; ?>

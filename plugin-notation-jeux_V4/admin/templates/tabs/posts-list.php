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
    <div style="text-align:center; padding:40px; background:#f9f9f9; border-radius:8px; margin-top:20px;">
        <h3>🎮 Aucun test trouvé</h3>
        <p>Créez votre premier article avec notation !</p>
        <a href="<?php echo esc_url($empty_state['create_post_url'] ?? admin_url('post-new.php')); ?>" class="button button-primary">✏️ Créer un Test</a>
    </div>
<?php else : ?>
    <?php if (!empty($stats)) : ?>
        <div style="background:#f0f6fc; padding:15px; border-radius:4px; margin-bottom:20px;">
            <?php
            printf(
                '<strong>%d</strong> articles avec notation trouvés • Page <strong>%d</strong> sur <strong>%d</strong> • Affichage de <strong>%d</strong> articles',
                intval($stats['total_items'] ?? 0),
                intval($stats['current_page'] ?? 1),
                max(1, intval($stats['total_pages'] ?? 1)),
                intval($stats['display_count'] ?? 0)
            );
            ?>
        </div>
    <?php endif; ?>

    <table class="wp-list-table widefat striped">
        <thead>
            <tr>
                <?php foreach ($columns as $column) : ?>
                    <th scope="col" class="<?php echo esc_attr($column['class'] ?? ''); ?>" aria-sort="<?php echo esc_attr($column['aria_sort'] ?? 'none'); ?>">
                        <a href="<?php echo esc_url($column['url'] ?? '#'); ?>">
                            <span><?php echo esc_html($column['label'] ?? ''); ?></span>
                            <span class="sorting-indicator" aria-hidden="true"></span>
                        </a>
                    </th>
                <?php endforeach; ?>
                <th><?php echo esc_html('Catégories'); ?></th>
                <th><?php echo esc_html('Actions'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($posts)) : ?>
                <?php foreach ($posts as $post) :
                    $categories = isset($post['categories']) && is_array($post['categories']) ? $post['categories'] : [];
                    ?>
                    <tr>
                        <td><strong><a href="<?php echo esc_url($post['edit_link'] ?? '#'); ?>"><?php echo esc_html($post['title'] ?? ''); ?></a></strong></td>
                        <td><?php echo esc_html($post['date'] ?? ''); ?></td>
                        <td><strong style="color:<?php echo esc_attr($post['score_color'] ?? '#0073aa'); ?>;"><?php echo esc_html($post['score_display'] ?? ''); ?></strong>/10</td>
                        <td><?php echo !empty($categories) ? esc_html(implode(', ', $categories)) : '-'; ?></td>
                        <td>
                            <a href="<?php echo esc_url($post['view_link'] ?? '#'); ?>" target="_blank" rel="noopener noreferrer">👁 Voir</a>
                            |
                            <a href="<?php echo esc_url($post['edit_link'] ?? '#'); ?>">✏️ Modifier</a>
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
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php echo wp_kses_post($pagination); ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($print_button_label)) : ?>
        <div style="margin-top:20px;">
            <p><a href="#" class="button" onclick="window.print(); return false;"><?php echo esc_html($print_button_label); ?></a></p>
        </div>
    <?php endif; ?>
<?php endif; ?>


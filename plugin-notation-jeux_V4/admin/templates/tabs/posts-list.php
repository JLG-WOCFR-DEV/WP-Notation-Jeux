<?php
if (!defined('ABSPATH')) exit;

$score_max       = \JLG\Notation\Helpers::get_score_max();
$score_max_label = number_format_i18n($score_max);

$has_rated_posts = $variables['has_rated_posts'] ?? false;
$empty_state = isset($variables['empty_state']) && is_array($variables['empty_state']) ? $variables['empty_state'] : [];
$stats = isset($variables['stats']) && is_array($variables['stats']) ? $variables['stats'] : [];
$insights = isset($variables['insights']) && is_array($variables['insights']) ? $variables['insights'] : [];
$columns = isset($variables['columns']) && is_array($variables['columns']) ? $variables['columns'] : [];
$posts = isset($variables['posts']) && is_array($variables['posts']) ? $variables['posts'] : [];
$pagination = $variables['pagination'] ?? '';
$print_button_label = $variables['print_button_label'] ?? '';
$column_count = count($columns) + 3;
$editorial_queue = isset($variables['editorial_queue']) && is_array($variables['editorial_queue']) ? $variables['editorial_queue'] : [];
$editorial_counts = isset($editorial_queue['counts']) && is_array($editorial_queue['counts']) ? $editorial_queue['counts'] : [];
$editorial_attention = isset($editorial_queue['attention']) ? (int) $editorial_queue['attention'] : 0;
?>
<h2>📊 Vos Articles avec Notation</h2>

<?php if (!$has_rated_posts) : ?>
    <div style="text-align:center; padding:40px; background:#f9f9f9; border-radius:8px; margin-top:20px;">
        <h3>🎮 Aucun test trouvé</h3>
        <p>Créez votre premier article avec notation !</p>
        <a href="<?php echo esc_url($empty_state['create_post_url'] ?? admin_url('post-new.php')); ?>" class="button button-primary">✏️ Créer un Test</a>
    </div>
<?php else : ?>
    <?php
    $insight_total = isset($insights['total']) ? (int) $insights['total'] : 0;
    $insight_mean = isset($insights['mean']['formatted']) ? $insights['mean']['formatted'] : null;
    $insight_median = isset($insights['median']['formatted']) ? $insights['median']['formatted'] : null;
    $distribution = isset($insights['distribution']) && is_array($insights['distribution']) ? $insights['distribution'] : [];
    $platform_rankings = isset($insights['platform_rankings']) && is_array($insights['platform_rankings']) ? $insights['platform_rankings'] : [];
    ?>
    <?php if (!empty($editorial_counts)) : ?>
        <div class="notice <?php echo $editorial_attention > 0 ? 'notice-warning' : 'notice-success'; ?> inline">
            <p>
                <?php
                printf(
                    esc_html__('File éditoriale : %1$d brouillon(s), %2$d mise(s) à jour, %3$d version(s) finale(s).', 'notation-jlg'),
                    isset($editorial_counts['draft']) ? (int) $editorial_counts['draft'] : 0,
                    isset($editorial_counts['in_progress']) ? (int) $editorial_counts['in_progress'] : 0,
                    isset($editorial_counts['final']) ? (int) $editorial_counts['final'] : 0
                );
                ?>
            </p>
        </div>
    <?php endif; ?>
    <section class="jlg-admin-insights" role="region" aria-labelledby="jlg-admin-insights-title">
        <h3 id="jlg-admin-insights-title"><?php echo esc_html__('Synthèse des notes', 'notation-jlg'); ?></h3>
        <p class="jlg-admin-insights__description">
            <?php
            printf(
                esc_html(_n('%d article analysé.', '%d articles analysés.', $insight_total, 'notation-jlg')),
                (int) $insight_total
            );
            ?>
        </p>
        <div class="jlg-admin-insights__grid">
            <article class="jlg-admin-insight-card" aria-labelledby="jlg-insight-mean-title" aria-describedby="jlg-insight-mean-desc">
                <h4 id="jlg-insight-mean-title"><?php echo esc_html__('Score moyen', 'notation-jlg'); ?></h4>
                <p class="jlg-admin-insight-card__value" aria-live="polite"><?php echo $insight_mean !== null ? esc_html($insight_mean) : esc_html__('N/A', 'notation-jlg'); ?></p>
                <p id="jlg-insight-mean-desc" class="jlg-admin-insight-card__legend"><?php esc_html_e('Moyenne pondérée des notes finales.', 'notation-jlg'); ?></p>
            </article>
            <article class="jlg-admin-insight-card" aria-labelledby="jlg-insight-median-title" aria-describedby="jlg-insight-median-desc">
                <h4 id="jlg-insight-median-title"><?php echo esc_html__('Médiane', 'notation-jlg'); ?></h4>
                <p class="jlg-admin-insight-card__value"><?php echo $insight_median !== null ? esc_html($insight_median) : esc_html__('N/A', 'notation-jlg'); ?></p>
                <p id="jlg-insight-median-desc" class="jlg-admin-insight-card__legend"><?php esc_html_e('Score central sur l’ensemble des publications.', 'notation-jlg'); ?></p>
            </article>
            <article class="jlg-admin-insight-card" aria-labelledby="jlg-insight-distribution-title">
                <h4 id="jlg-insight-distribution-title"><?php echo esc_html__('Répartition des notes', 'notation-jlg'); ?></h4>
                <ul class="jlg-score-distribution" role="list" aria-describedby="jlg-insight-distribution-title">
                    <?php foreach ($distribution as $bucket) :
                        $bucket_label = $bucket['label'] ?? '';
                        $bucket_count = isset($bucket['count']) ? (int) $bucket['count'] : 0;
                        $bucket_percentage = isset($bucket['percentage']) ? (float) $bucket['percentage'] : 0.0;
                        $bar_width = max(0, min(100, $bucket_percentage));
                        $sr_text = sprintf(
                            esc_html__('%1$s : %2$d articles, %3$s%% du total', 'notation-jlg'),
                            $bucket_label,
                            $bucket_count,
                            number_format_i18n($bucket_percentage, 1)
                        );
                        ?>
                        <li class="jlg-score-distribution__item">
                            <span class="jlg-score-distribution__label"><?php echo esc_html($bucket_label); ?></span>
                            <span class="jlg-score-distribution__bar" aria-hidden="true">
                                <span class="jlg-score-distribution__bar-fill" style="width: <?php echo esc_attr($bar_width); ?>%;"></span>
                            </span>
                            <span class="jlg-score-distribution__value" aria-hidden="true"><?php echo esc_html(number_format_i18n($bucket_percentage, 1)); ?>%</span>
                            <span class="screen-reader-text"><?php echo esc_html($sr_text); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </article>
            <article class="jlg-admin-insight-card" aria-labelledby="jlg-insight-platform-title">
                <h4 id="jlg-insight-platform-title"><?php echo esc_html__('Classement par plateforme', 'notation-jlg'); ?></h4>
                <?php $rank_slice = array_slice($platform_rankings, 0, 5); ?>
                <table class="jlg-platform-ranking-table">
                    <caption class="screen-reader-text"><?php echo esc_html__('Classement des plateformes les mieux notées', 'notation-jlg'); ?></caption>
                    <thead>
                        <tr>
                            <th scope="col"><?php echo esc_html__('Plateforme', 'notation-jlg'); ?></th>
                            <th scope="col" class="jlg-platform-ranking-table__score-header"><?php echo esc_html__('Note moyenne', 'notation-jlg'); ?></th>
                            <th scope="col" class="jlg-platform-ranking-table__count-header"><?php echo esc_html(_n('Jeu noté', 'Jeux notés', 2, 'notation-jlg')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($rank_slice)) : ?>
                            <?php foreach ($rank_slice as $platform) :
                                $platform_label = $platform['label'] ?? '';
                                $platform_avg = $platform['average_formatted'] ?? null;
                                $platform_count = isset($platform['count']) ? (int) $platform['count'] : 0;
                                $count_label = sprintf(
                                    esc_html(_n('%d jeu noté', '%d jeux notés', $platform_count, 'notation-jlg')),
                                    $platform_count
                                );
                                ?>
                                <tr>
                                    <th scope="row" class="jlg-platform-ranking-table__name"><?php echo esc_html($platform_label); ?></th>
                                    <td class="jlg-platform-ranking-table__score"><?php echo $platform_avg !== null ? esc_html($platform_avg) : esc_html__('N/A', 'notation-jlg'); ?></td>
                                    <td class="jlg-platform-ranking-table__count"><?php echo esc_html($count_label); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="3" class="jlg-platform-ranking-table__empty"><?php esc_html_e('Pas encore de plateforme renseignée.', 'notation-jlg'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </article>
        </div>
    </section>
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
                <th><?php echo esc_html__('Statut', 'notation-jlg'); ?></th>
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
                        <td><strong style="color:<?php echo esc_attr($post['score_color'] ?? '#0073aa'); ?>;"><?php echo esc_html($post['score_display'] ?? ''); ?></strong><?php printf( esc_html__( '/%s', 'notation-jlg' ), esc_html( $score_max_label ) ); ?></td>
                        <td><?php echo !empty($categories) ? esc_html(implode(', ', $categories)) : '-'; ?></td>
                        <td><?php echo esc_html(!empty($post['review_status']) ? $post['review_status'] : '—'); ?></td>
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


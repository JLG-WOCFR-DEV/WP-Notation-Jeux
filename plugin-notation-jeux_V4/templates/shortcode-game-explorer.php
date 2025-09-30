<?php
if (!defined('ABSPATH')) exit;

$container_id = isset($atts['id']) ? sanitize_html_class($atts['id']) : 'jlg-game-explorer-' . uniqid();
if ($container_id === '') {
    $container_id = 'jlg-game-explorer-' . uniqid();
}

$columns = isset($atts['columns']) ? (int) $atts['columns'] : 3;
$columns = max(1, min($columns, 4));
$filters_enabled = is_array($filters_enabled) ? $filters_enabled : [];
$current_filters = is_array($current_filters) ? $current_filters : [];
$letters = is_array($letters) ? $letters : [];
$sort_options = is_array($sort_options) ? $sort_options : [];
$categories_list = is_array($categories_list) ? $categories_list : [];
$platforms_list = is_array($platforms_list) ? $platforms_list : [];
$availability_options = is_array($availability_options) ? $availability_options : [];
$total_items = isset($total_items) ? (int) $total_items : 0;
$sort_key = isset($sort_key) ? $sort_key : 'date';
$sort_order = isset($sort_order) ? $sort_order : 'DESC';
$pagination = is_array($pagination) ? $pagination : ['current' => 1, 'total' => 0];
$config_payload = is_array($config_payload) ? $config_payload : [];
$request_prefix = isset($request_prefix) ? (string) $request_prefix : '';
$config_json = wp_json_encode($config_payload);
if ($config_json === false) {
    $config_json = '{}';
}

$has_category_filter = !empty($filters_enabled['category']) && !empty($categories_list);
$has_platform_filter = !empty($filters_enabled['platform']) && !empty($platforms_list);
$has_availability_filter = !empty($filters_enabled['availability']);
$has_filters = $has_category_filter || $has_platform_filter || $has_availability_filter;
$letter_active = isset($current_filters['letter']) ? $current_filters['letter'] : '';
$category_active = isset($current_filters['category']) ? $current_filters['category'] : '';
$platform_active = isset($current_filters['platform']) ? $current_filters['platform'] : '';
$availability_active = isset($current_filters['availability']) ? $current_filters['availability'] : '';
?>

<div
    id="<?php echo esc_attr($container_id); ?>"
    class="jlg-game-explorer jlg-ge-cols-<?php echo esc_attr($columns); ?>"
    data-columns="<?php echo esc_attr($columns); ?>"
    data-config="<?php echo esc_attr($config_json); ?>"
    data-posts-per-page="<?php echo esc_attr($atts['posts_per_page']); ?>"
    data-total-items="<?php echo esc_attr($total_items); ?>"
    data-request-prefix="<?php echo esc_attr($request_prefix); ?>"
>
    <div class="jlg-ge-toolbar">
        <div class="jlg-ge-toolbar__left">
            <?php if (!empty($filters_enabled['letter']) && !empty($letters)) : ?>
                <nav class="jlg-ge-letter-nav" aria-label="<?php esc_attr_e('Filtrer par lettre', 'notation-jlg'); ?>">
                    <ul>
                        <li>
                            <?php
                            $all_letters_classes = [];
                            if ($letter_active === '') {
                                $all_letters_classes[] = 'is-active';
                            }
                            ?>
                            <button
                                type="button"
                                class="<?php echo esc_attr(implode(' ', array_map('sanitize_html_class', $all_letters_classes))); ?>"
                                data-letter=""
                                aria-pressed="<?php echo esc_attr($letter_active === '' ? 'true' : 'false'); ?>"
                            >
                                <?php esc_html_e('Tous', 'notation-jlg'); ?>
                            </button>
                        </li>
                        <?php foreach ($letters as $letter_item) :
                            $value = isset($letter_item['value']) ? $letter_item['value'] : '';
                            $enabled = !empty($letter_item['enabled']);
                            $is_active = ($value !== '' && $value === $letter_active);
                            ?>
                            <li>
                                <?php
                                $letter_button_classes = [];
                                if ($is_active) {
                                    $letter_button_classes[] = 'is-active';
                                }
                                ?>
                                <button
                                    type="button"
                                    data-letter="<?php echo esc_attr($value); ?>"
                                    class="<?php echo esc_attr(implode(' ', array_map('sanitize_html_class', $letter_button_classes))); ?>"
                                    <?php disabled(!$enabled); ?>
                                    aria-pressed="<?php echo esc_attr($is_active ? 'true' : 'false'); ?>"
                                >
                                    <?php echo esc_html($letter_item['label'] ?? $value); ?>
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
        <div class="jlg-ge-toolbar__right">
            <div class="jlg-ge-sort">
                <label for="<?php echo esc_attr($container_id); ?>-sort">
                    <?php esc_html_e('Trier par', 'notation-jlg'); ?>
                </label>
                <select id="<?php echo esc_attr($container_id); ?>-sort" data-role="sort">
                    <?php foreach ($sort_options as $option) :
                        $value = isset($option['value']) ? $option['value'] : '';
                        $option_orderby = isset($option['orderby']) ? $option['orderby'] : '';
                        $option_order = isset($option['order']) ? $option['order'] : '';
                        $selected = ($option_orderby === $sort_key && $option_order === $sort_order);
                        ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($selected); ?>>
                            <?php echo esc_html($option['label']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="jlg-ge-count">
                <?php
                $formatted_total_items = number_format_i18n($total_items);

                printf(
                    esc_html(_n('%s jeu', '%s jeux', $total_items, 'notation-jlg')),
                    $formatted_total_items
                );
                ?>
            </div>
        </div>
    </div>

    <?php if ($has_filters) : ?>
        <div class="jlg-ge-filters" data-role="filters">
            <?php if ($has_category_filter) : ?>
                <label for="<?php echo esc_attr($container_id); ?>-category" class="screen-reader-text">
                    <?php esc_html_e('Filtrer par catégorie', 'notation-jlg'); ?>
                </label>
                <select id="<?php echo esc_attr($container_id); ?>-category" data-role="category">
                    <option value="">
                        <?php esc_html_e('Toutes les catégories', 'notation-jlg'); ?>
                    </option>
                    <?php foreach ($categories_list as $category) :
                        $value = isset($category['value']) ? (string) $category['value'] : '';
                        $label = isset($category['label']) ? $category['label'] : $value;
                        ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($category_active, $value); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

            <?php if ($has_platform_filter) : ?>
                <label for="<?php echo esc_attr($container_id); ?>-platform" class="screen-reader-text">
                    <?php esc_html_e('Filtrer par plateforme', 'notation-jlg'); ?>
                </label>
                <select id="<?php echo esc_attr($container_id); ?>-platform" data-role="platform">
                    <option value="">
                        <?php esc_html_e('Toutes les plateformes', 'notation-jlg'); ?>
                    </option>
                    <?php foreach ($platforms_list as $platform) :
                        $value = isset($platform['value']) ? (string) $platform['value'] : '';
                        $label = isset($platform['label']) ? $platform['label'] : $value;
                        ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($platform_active, $value); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

            <?php if ($has_availability_filter) : ?>
                <label for="<?php echo esc_attr($container_id); ?>-availability" class="screen-reader-text">
                    <?php esc_html_e('Filtrer par disponibilité', 'notation-jlg'); ?>
                </label>
                <select id="<?php echo esc_attr($container_id); ?>-availability" data-role="availability">
                    <option value="">
                        <?php esc_html_e('Toutes les sorties', 'notation-jlg'); ?>
                    </option>
                    <?php foreach ($availability_options as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($availability_active, $value); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

            <button type="button" class="jlg-ge-reset" data-role="reset">
                <?php esc_html_e('Réinitialiser', 'notation-jlg'); ?>
            </button>
        </div>
    <?php endif; ?>

    <div class="jlg-ge-results" data-role="results">
        <?php
        echo JLG_Frontend::get_template_html('game-explorer-fragment', [
            'games'      => $games,
            'message'    => isset($message) ? $message : '',
            'pagination' => $pagination,
            'total_items'=> $total_items,
        ]);
        ?>
    </div>
</div>

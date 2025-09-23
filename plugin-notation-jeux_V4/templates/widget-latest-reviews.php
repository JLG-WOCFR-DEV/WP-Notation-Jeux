<?php
if (!defined('ABSPATH')) exit;

echo $widget_args['before_widget'];

if (!empty($title)) {
    echo $widget_args['before_title'] . esc_html($title) . $widget_args['after_title'];
}

if ($latest_reviews->have_posts()) {
    echo '<ul>';
    while ($latest_reviews->have_posts()) {
        $latest_reviews->the_post();
        $post_id = get_the_ID();
        $game_title = JLG_Helpers::get_game_title($post_id);
        echo '<li>';
        echo '<a href="' . esc_url(get_permalink()) . '">' . esc_html($game_title) . '</a>';
        $genre_markup = class_exists('JLG_Helpers') ? JLG_Helpers::get_genre_badges_markup($post_id, false) : '';
        if (!empty($genre_markup)) {
            echo '<div class="jlg-widget-genre-badges" style="margin-top:4px;display:flex;flex-wrap:wrap;gap:4px;">' . wp_kses_post($genre_markup) . '</div>';
        }
        echo '</li>';
    }
    echo '</ul>';
} else {
    echo '<p>' . esc_html__('Aucun test trouvé.', 'notation-jlg') . '</p>';
}
wp_reset_postdata();

echo $widget_args['after_widget'];

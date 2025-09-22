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
        echo '<li><a href="' . esc_url(get_permalink()) . '">' . esc_html($game_title) . '</a></li>';
    }
    echo '</ul>';
} else {
    echo '<p>' . esc_html__('Aucun test trouvé.', 'notation-jlg') . '</p>';
}
wp_reset_postdata();

echo $widget_args['after_widget'];

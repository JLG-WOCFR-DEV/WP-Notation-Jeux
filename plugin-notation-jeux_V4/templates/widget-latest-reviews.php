<?php
if (!defined('ABSPATH')) exit;

echo $widget_args['before_widget'];

if (!empty($title)) {
    echo $widget_args['before_title'] . $title . $widget_args['after_title'];
}

if ($latest_reviews->have_posts()) {
    echo '<ul>';
    while ($latest_reviews->have_posts()) {
        $latest_reviews->the_post();
        echo '<li><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></li>';
    }
    echo '</ul>';
} else {
    echo '<p>' . esc_html__('Aucun test trouvé.', 'notation-jlg') . '</p>';
}
wp_reset_postdata();

echo $widget_args['after_widget'];

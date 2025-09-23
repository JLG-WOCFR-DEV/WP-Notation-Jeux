<?php
if (!defined('ABSPATH')) exit;

class JLG_Latest_Reviews_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'jlg_latest_reviews_widget',
            __('Notation JLG : Derniers Tests', 'notation-jlg'),
            ['description' => __('Affiche les derniers articles ayant reçu une note.', 'notation-jlg')]
        );
    }

    public function widget($args, $instance) {
        $title = apply_filters('widget_title', $instance['title'] ?? __('Derniers Tests', 'notation-jlg'));
        $number = (!empty($instance['number'])) ? absint($instance['number']) : 5;
        $genre_slug = !empty($instance['genre']) ? sanitize_title($instance['genre']) : '';

        $genre_options = class_exists('JLG_Helpers') ? JLG_Helpers::get_registered_genres() : [];
        if ($genre_slug !== '' && !isset($genre_options[$genre_slug])) {
            $genre_slug = '';
        }

        $rated_post_ids = JLG_Helpers::get_rated_post_ids();

        if (empty($rated_post_ids)) {
            echo $args['before_widget'];
            if (!empty($title)) {
                echo $args['before_title'] . $title . $args['after_title'];
            }
            echo '<p>' . esc_html__('Aucun test trouvé.', 'notation-jlg') . '</p>';
            echo $args['after_widget'];
            return;
        }

        $query_args = [
            'post_type' => 'post',
            'posts_per_page' => $number,
            'post__in' => $rated_post_ids,
            'orderby' => 'date',
            'order' => 'DESC',
            'ignore_sticky_posts' => 1
        ];

        if ($genre_slug !== '') {
            $query_args['meta_query'] = [
                [
                    'key'     => '_jlg_genres',
                    'value'   => '"' . $genre_slug . '"',
                    'compare' => 'LIKE',
                ],
            ];
        }
        
        $latest_reviews = new WP_Query($query_args);

        // Utilisation d'un fichier template pour l'affichage
        echo JLG_Frontend::get_template_html('widget-latest-reviews', [
            'widget_args' => $args,
            'title' => $title,
            'latest_reviews' => $latest_reviews,
            'selected_genre' => $genre_slug,
        ]);
    }

    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Derniers Tests', 'notation-jlg');
        $number = isset($instance['number']) ? absint($instance['number']) : 5;
        $selected_genre = isset($instance['genre']) ? sanitize_title($instance['genre']) : '';

        $genre_choices = [];
        if (class_exists('JLG_Helpers')) {
            foreach (JLG_Helpers::get_registered_genres() as $slug => $genre) {
                if (!is_array($genre)) {
                    continue;
                }

                $label = isset($genre['name']) ? $genre['name'] : $slug;
                $label = sanitize_text_field($label);
                if ($label === '') {
                    continue;
                }

                $genre_choices[$slug] = $label;
            }
        }

        if ($selected_genre !== '' && !isset($genre_choices[$selected_genre])) {
            $selected_genre = '';
        }
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php echo esc_html__('Titre :', 'notation-jlg'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>"
                   type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('number')); ?>"><?php echo esc_html__('Nombre d\'articles à afficher :', 'notation-jlg'); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr($this->get_field_id('number')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('number')); ?>"
                   type="number" step="1" min="1" value="<?php echo esc_attr($number); ?>" size="3">
        </p>
        <?php if (!empty($genre_choices)) : ?>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('genre')); ?>"><?php echo esc_html__('Filtrer par genre :', 'notation-jlg'); ?></label>
                <select class="widefat"
                        id="<?php echo esc_attr($this->get_field_id('genre')); ?>"
                        name="<?php echo esc_attr($this->get_field_name('genre')); ?>">
                    <option value="" <?php selected($selected_genre, ''); ?>><?php echo esc_html__('Tous les genres', 'notation-jlg'); ?></option>
                    <?php foreach ($genre_choices as $slug => $label) : ?>
                        <option value="<?php echo esc_attr($slug); ?>" <?php selected($selected_genre, $slug); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
        <?php endif; ?>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['number'] = (!empty($new_instance['number'])) ? absint($new_instance['number']) : 5;
        $instance['genre'] = '';

        if (!empty($new_instance['genre'])) {
            $genre_slug = sanitize_title($new_instance['genre']);

            if ($genre_slug !== '' && class_exists('JLG_Helpers')) {
                $registered_genres = JLG_Helpers::get_registered_genres();
                if (!isset($registered_genres[$genre_slug])) {
                    $genre_slug = '';
                }
            }

            $instance['genre'] = $genre_slug;
        }
        return $instance;
    }
}

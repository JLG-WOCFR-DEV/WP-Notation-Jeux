<?php
if (!defined('ABSPATH')) exit;

class JLG_Shortcode_Rating_Block {
    
    public function __construct() {
        add_shortcode('bloc_notation_jeu', [$this, 'render']);
    }

    public function render($atts, $content = '', $shortcode_tag = '') {
        $atts = shortcode_atts([
            'post_id' => get_the_ID()
        ], $atts, 'bloc_notation_jeu');
        
        $post_id = intval($atts['post_id']);

        if (!$post_id) {
            return '';
        }

        $post = get_post($post_id);
        if (!$post instanceof WP_Post || ($post->post_type ?? '') !== 'post') {
            return '';
        }

        if (($post->post_status ?? '') !== 'publish' && !current_user_can('read_post', $post_id)) {
            return '';
        }

        // Sécurité : ne s'exécute que si des notes existent
        $average_score = JLG_Helpers::get_average_score_for_post($post_id);
        if ($average_score === null) {
            return '';
        }

        $categories = JLG_Helpers::get_rating_categories();
        $scores = [];
        
        foreach (array_keys($categories) as $key) {
            $score_value = get_post_meta($post_id, '_note_' . $key, true);
            if ($score_value !== '' && is_numeric($score_value)) {
                $scores[$key] = floatval($score_value);
            }
        }
        
        JLG_Frontend::mark_shortcode_rendered($shortcode_tag ?: 'bloc_notation_jeu');

        return JLG_Frontend::get_template_html('shortcode-rating-block', [
            'options'       => JLG_Helpers::get_plugin_options(),
            'average_score' => $average_score,
            'scores'        => $scores,
            'categories'    => $categories,
        ]);
    }
}
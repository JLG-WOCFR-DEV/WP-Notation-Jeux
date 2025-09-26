<?php
if (!defined('ABSPATH')) exit;

class JLG_Shortcode_User_Rating {
    
    public function __construct() {
        add_shortcode('notation_utilisateurs_jlg', [$this, 'render']);
    }

    public function render($atts = [], $content = '', $shortcode_tag = '') {
        $allowed_types = JLG_Helpers::get_allowed_post_types();

        if (!is_singular($allowed_types)) {
            return '';
        }

        $options = JLG_Helpers::get_plugin_options();
        if (empty($options['user_rating_enabled'])) {
            return '';
        }

        $post_id = get_the_ID();
        list($has_voted, $user_vote) = JLG_Frontend::get_user_vote_for_post($post_id);

        JLG_Frontend::mark_shortcode_rendered($shortcode_tag ?: 'notation_utilisateurs_jlg');

        return JLG_Frontend::get_template_html('shortcode-user-rating', [
            'options' => $options,
            'post_id' => $post_id,
            'avg_rating' => get_post_meta($post_id, '_jlg_user_rating_avg', true),
            'count' => get_post_meta($post_id, '_jlg_user_rating_count', true),
            'has_voted' => $has_voted,
            'user_vote' => $user_vote
        ]);
    }
}
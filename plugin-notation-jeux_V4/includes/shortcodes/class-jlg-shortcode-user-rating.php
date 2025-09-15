<?php
if (!defined('ABSPATH')) exit;

class JLG_Shortcode_User_Rating {
    
    public function __construct() {
        add_shortcode('notation_utilisateurs_jlg', [$this, 'render']);
    }

    public function render() {
        if (!is_singular('post')) {
            return '';
        }

        $options = get_option('notation_jlg_settings', JLG_Helpers::get_default_settings());
        if (empty($options['user_rating_enabled'])) {
            return '';
        }

        $post_id = get_the_ID();
        $ratings = get_post_meta($post_id, '_jlg_user_ratings', true);
        $has_voted = (is_array($ratings) && isset($ratings[$_SERVER['REMOTE_ADDR']]));
        
        $html = JLG_Frontend::get_template_html('shortcode-user-rating', [
            'options' => $options,
            'post_id' => $post_id,
            'avg_rating' => get_post_meta($post_id, '_jlg_user_rating_avg', true),
            'count' => get_post_meta($post_id, '_jlg_user_rating_count', true),
            'has_voted' => $has_voted,
            'user_vote' => $has_voted ? $ratings[$_SERVER['REMOTE_ADDR']] : 0
        ]);
        return is_wp_error($html) ? '' : $html;
    }
}
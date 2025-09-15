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
        $user_ip = isset($_SERVER['REMOTE_ADDR']) ? filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP) : false;
        $user_ip_hash = $user_ip ? wp_hash($user_ip) : '';
        $has_voted = (is_array($ratings) && $user_ip_hash && isset($ratings[$user_ip_hash]));

        return JLG_Frontend::get_template_html('shortcode-user-rating', [
            'options' => $options,
            'post_id' => $post_id,
            'avg_rating' => get_post_meta($post_id, '_jlg_user_rating_avg', true),
            'count' => get_post_meta($post_id, '_jlg_user_rating_count', true),
            'has_voted' => $has_voted,
            'user_vote' => $has_voted ? $ratings[$user_ip_hash] : 0
        ]);
    }
}
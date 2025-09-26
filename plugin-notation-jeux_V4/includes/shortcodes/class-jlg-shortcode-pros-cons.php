<?php
if (!defined('ABSPATH')) exit;

class JLG_Shortcode_Pros_Cons {
    
    public function __construct() {
        add_shortcode('jlg_points_forts_faibles', [$this, 'render']);
    }

    public function render($atts = [], $content = '', $shortcode_tag = '') {
        $allowed_types = JLG_Helpers::get_allowed_post_types();

        // Sécurité : ne s'exécute que sur les contenus autorisés
        if (!is_singular($allowed_types)) {
            return '';
        }

        $pros = get_post_meta(get_the_ID(), '_jlg_points_forts', true);
        $cons = get_post_meta(get_the_ID(), '_jlg_points_faibles', true);

        // Sécurité : ne s'exécute que si les données existent
        if (empty($pros) && empty($cons)) {
            return '';
        }
        
        JLG_Frontend::mark_shortcode_rendered($shortcode_tag ?: 'jlg_points_forts_faibles');

        return JLG_Frontend::get_template_html('shortcode-pros-cons', [
            'pros_list' => !empty($pros) ? array_filter(explode("\n", $pros)) : [],
            'cons_list' => !empty($cons) ? array_filter(explode("\n", $cons)) : [],
        ]);
    }
}
<?php
if (!defined('ABSPATH')) exit;

class JLG_Shortcode_Pros_Cons {
    
    public function __construct() {
        add_shortcode('jlg_points_forts_faibles', [$this, 'render']);
    }

    public function render() {
        // Sécurité : ne s'exécute que sur les articles ('post') ou pages singulières
        if (!is_singular('post')) {
            return '';
        }

        $pros = get_post_meta(get_the_ID(), '_jlg_points_forts', true);
        $cons = get_post_meta(get_the_ID(), '_jlg_points_faibles', true);

        // Sécurité : ne s'exécute que si les données existent
        if (empty($pros) && empty($cons)) {
            return '';
        }
        
        return JLG_Frontend::get_template_html('shortcode-pros-cons', [
            'pros_list' => !empty($pros) ? array_filter(explode("\n", $pros)) : [],
            'cons_list' => !empty($cons) ? array_filter(explode("\n", $cons)) : [],
        ]);
    }
}
// Fonction pour afficher la note sur les vignettes
if (!function_exists('jlg_display_thumbnail_score')) {
    function jlg_display_thumbnail_score($post_id = null) {
        if (class_exists('JLG_Frontend')) {
            $template = JLG_Frontend::get_template_html('widget-thumbnail-score', ['post_id' => $post_id]);
            if (!is_wp_error($template)) {
                echo $template;
            }
        }
    }
}
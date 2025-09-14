<?php
/**
 * Shortcode pour le tableau/grille récapitulatif
 * 
 * @package JLG_Notation
 * @version 5.0
 */

if (!defined('ABSPATH')) exit;

class JLG_Shortcode_Summary_Display {
    
    public function __construct() {
        add_shortcode('jlg_tableau_recap', [$this, 'render']);
    }

    public function render($atts) {
        // Attributs avec valeurs par défaut
        $atts = shortcode_atts([
            'posts_per_page' => 12,
            'layout'         => 'table',
            'categorie'      => '',
            'colonnes'       => 'titre,date,note',
            'id'             => 'jlg-table-' . uniqid()
        ], $atts, 'jlg_tableau_recap');

        // Variables de tri et pagination
        $paged = get_query_var('paged') ? get_query_var('paged') : 1;
        $orderby = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'date';
        $order = isset($_GET['order']) && in_array(strtoupper($_GET['order']), ['ASC', 'DESC']) ? strtoupper($_GET['order']) : 'DESC';
        $cat_filter = isset($_GET['cat_filter']) ? intval($_GET['cat_filter']) : 0;

        // Récupérer les articles notés
        $rated_post_ids = JLG_Helpers::get_rated_post_ids();
        if (empty($rated_post_ids)) {
            return '<p>Aucun article noté trouvé.</p>';
        }

        // Arguments de la requête
        $args = [
            'post_type'      => 'post',
            'posts_per_page' => intval($atts['posts_per_page']),
            'post__in'       => $rated_post_ids,
            'paged'          => $paged,
            'order'          => $order,
        ];

        // Gestion du tri
        if ($orderby === 'average_score' || $orderby === 'note') {
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = '_jlg_average_score';
        } elseif ($orderby === 'title' || $orderby === 'titre') {
            $args['orderby'] = 'title';
        } else {
            $args['orderby'] = $orderby;
        }

        // Filtrage par catégorie
        if (!empty($atts['categorie'])) {
            $args['category_name'] = sanitize_text_field($atts['categorie']);
        } elseif ($cat_filter > 0) {
            $args['cat'] = $cat_filter;
        }

        $query = new WP_Query($args);
        
        // Utiliser le template pour le rendu
        return JLG_Frontend::get_template_html('shortcode-summary-display', [
            'query' => $query,
            'atts'  => $atts,
            'paged' => $paged,
            'orderby' => $orderby,
            'order' => $order
        ]);
    }
}
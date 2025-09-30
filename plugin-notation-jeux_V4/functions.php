<?php
// Fonction pour afficher la note sur les vignettes
if ( ! function_exists( 'jlg_display_thumbnail_score' ) ) {
    function jlg_display_thumbnail_score( $post_id = null ) {
        if ( class_exists( 'JLG_Frontend' ) ) {
            echo JLG_Frontend::get_template_html( 'widget-thumbnail-score', array( 'post_id' => $post_id ) );
        }
    }
}

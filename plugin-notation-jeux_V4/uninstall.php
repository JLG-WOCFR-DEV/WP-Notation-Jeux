<?php
/**
 * Désinstallation du plugin Notation JLG
 * 
 * Ce fichier est exécuté lors de la suppression du plugin
 * pour nettoyer la base de données si l'utilisateur le souhaite.
 */

// Sécurité : vérifier que c'est WordPress qui appelle ce fichier
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

if (function_exists('wp_clear_scheduled_hook')) {
    wp_clear_scheduled_hook('jlg_process_v5_migration');
}

delete_option('jlg_migration_v5_queue');
delete_option('jlg_migration_v5_scan_state');
delete_option('jlg_migration_v5_completed');

// Option pour vérifier si l'utilisateur veut supprimer les données
$delete_data = get_option('jlg_notation_delete_data_on_uninstall', false);

if ($delete_data) {
    global $wpdb;
    
    // Supprimer les options du plugin
    delete_option('notation_jlg_settings');
    delete_option('jlg_notation_version');
    delete_option('jlg_migration_v5_completed');
    delete_option('jlg_migration_v5_scan_state');
    delete_option('jlg_platforms_list');
    delete_option('jlg_notation_delete_data_on_uninstall');
    
    // Supprimer toutes les métadonnées des posts
    $meta_keys = [
        '_note_cat1', '_note_cat2', '_note_cat3', 
        '_note_cat4', '_note_cat5', '_note_cat6',
        '_jlg_average_score',
        '_jlg_tagline_fr', '_jlg_tagline_en',
        '_jlg_points_forts', '_jlg_points_faibles',
        '_jlg_developpeur', '_jlg_editeur',
        '_jlg_date_sortie', '_jlg_plateformes',
        '_jlg_cover_image_url', '_jlg_version',
        '_jlg_pegi', '_jlg_temps_de_jeu',
        '_jlg_user_ratings', '_jlg_user_rating_avg',
        '_jlg_user_rating_count', '_jlg_user_rating_ips'
    ];
    
    foreach ($meta_keys as $meta_key) {
        $wpdb->delete($wpdb->postmeta, ['meta_key' => $meta_key]);
    }
    
    // Nettoyer les transients éventuels
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_jlg_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_jlg_%'");
}

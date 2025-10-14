<?php
/**
 * Désinstallation du plugin Notation JLG
 *
 * Ce fichier est exécuté lors de la suppression du plugin
 * pour nettoyer la base de données si l'utilisateur le souhaite.
 */

// Sécurité : vérifier que c'est WordPress qui appelle ce fichier
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

if ( function_exists( 'wp_clear_scheduled_hook' ) ) {
    wp_clear_scheduled_hook( 'jlg_process_v5_migration' );
}

delete_option( 'jlg_migration_v5_queue' );
delete_option( 'jlg_migration_v5_scan_state' );
delete_option( 'jlg_migration_v5_completed' );

// Option pour vérifier si l'utilisateur veut supprimer les données
$delete_data = get_option( 'jlg_notation_delete_data_on_uninstall', false );

if ( $delete_data ) {
    global $wpdb;

    // Supprimer les options du plugin
    delete_option( 'notation_jlg_settings' );
    delete_option( 'jlg_notation_version' );
    delete_option( 'jlg_migration_v5_completed' );
    delete_option( 'jlg_migration_v5_scan_state' );
    delete_option( 'jlg_platforms_list' );
    delete_option( 'jlg_notation_delete_data_on_uninstall' );

    // Supprimer toutes les métadonnées des posts
    $category_meta_keys = array();

    if ( ! class_exists( '\\JLG\\Notation\\Helpers' ) ) {
        $helpers_file = __DIR__ . '/includes/Helpers.php';

        if ( file_exists( $helpers_file ) ) {
            require_once $helpers_file;
        }
    }

    if ( class_exists( '\\JLG\\Notation\\Helpers' ) ) {
        $definitions = \JLG\Notation\Helpers::get_rating_category_definitions();

        if ( function_exists( 'delete_metadata' ) ) {
            delete_metadata( 'user', 0, \JLG\Notation\Helpers::SETTINGS_VIEW_MODE_META_KEY, '', true );
        }

        foreach ( $definitions as $definition ) {
            if ( ! empty( $definition['meta_key'] ) ) {
                $category_meta_keys[] = (string) $definition['meta_key'];
            }

            if ( ! empty( $definition['legacy_meta_keys'] ) && is_array( $definition['legacy_meta_keys'] ) ) {
                foreach ( $definition['legacy_meta_keys'] as $legacy_meta_key ) {
                    $category_meta_keys[] = (string) $legacy_meta_key;
                }
            }
        }
    }

    if ( empty( $category_meta_keys ) ) {
        $category_meta_keys = array(
            '_note_cat1',
            '_note_cat2',
            '_note_cat3',
            '_note_cat4',
            '_note_cat5',
            '_note_cat6',
        );
    }

    $meta_keys = array_merge(
        array_values( array_unique( $category_meta_keys ) ),
        array(
            '_jlg_average_score',
            '_jlg_game_title',
            '_jlg_tagline_fr',
            '_jlg_tagline_en',
            '_jlg_points_forts',
            '_jlg_points_faibles',
            '_jlg_developpeur',
            '_jlg_editeur',
            '_jlg_date_sortie',
            '_jlg_plateformes',
            '_jlg_cover_image_url',
            '_jlg_version',
            '_jlg_pegi',
            '_jlg_temps_de_jeu',
            '_jlg_review_video_url',
            '_jlg_review_video_provider',
            '_jlg_user_ratings',
            '_jlg_user_rating_avg',
            '_jlg_user_rating_count',
            '_jlg_user_rating_ips',
        )
    );

    foreach ( $meta_keys as $meta_key ) {
        $wpdb->delete( $wpdb->postmeta, array( 'meta_key' => $meta_key ) );
    }

    // Nettoyer les transients éventuels
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_jlg_%'" );
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_jlg_%'" );
}

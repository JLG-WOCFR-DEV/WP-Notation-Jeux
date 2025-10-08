<?php
/**
 * Gestion des plateformes/consoles
 *
 * @package JLG_Notation
 * @version 5.0
 */

namespace JLG\Notation\Admin;

use JLG\Notation\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Platforms {

    private $option_name           = 'jlg_platforms_list';
    private $tag_map_option        = 'jlg_platform_tag_map';
    private $default_platforms     = array(
        'pc'              => array(
			'name'   => 'PC',
			'icon'   => '💻',
			'order'  => 1,
			'custom' => false,
		),
        'playstation-5'   => array(
			'name'   => 'PlayStation 5',
			'icon'   => '🎮',
			'order'  => 2,
			'custom' => false,
		),
        'xbox-series-x'   => array(
			'name'   => 'Xbox Series S/X',
			'icon'   => '🎮',
			'order'  => 3,
			'custom' => false,
		),
        'nintendo-switch' => array(
			'name'   => 'Nintendo Switch',
			'icon'   => '🎮',
			'order'  => 4,
			'custom' => false,
		),
        'playstation-4'   => array(
			'name'   => 'PlayStation 4',
			'icon'   => '🎮',
			'order'  => 5,
			'custom' => false,
		),
        'xbox-one'        => array(
			'name'   => 'Xbox One',
			'icon'   => '🎮',
			'order'  => 6,
			'custom' => false,
		),
        'steam-deck'      => array(
			'name'   => 'Steam Deck',
			'icon'   => '🎮',
			'order'  => 7,
			'custom' => false,
		),
    );
    private static $debug_messages = array();
    private static $instance       = null;
    private $debug_enabled         = null;

    /**
     * Singleton pattern pour s'assurer qu'une seule instance existe
     */
    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // Important: Hook sur admin_init pour traiter les actions POST
        add_action( 'admin_init', array( $this, 'handle_platform_actions' ), 5 );
        $this->log_debug( '✅ Classe Platforms initialisée' );
    }

    /**
     * Obtenir la liste des plateformes
     */
    public function get_platforms() {
        $stored_platforms  = $this->get_stored_platform_data();
        $default_platforms = $this->get_default_platform_definitions();

        $platforms = $default_platforms;

        foreach ( $stored_platforms['custom_platforms'] as $key => $custom_platform ) {
            if ( ! is_array( $custom_platform ) ) {
                continue;
            }

            $platforms[ $key ] = array_merge(
                array(
					'name'   => '',
					'icon'   => '🎮',
					'custom' => true,
                ),
                $custom_platform
            );
        }

        $order_map     = $stored_platforms['order'];
        $platform_keys = array_keys( $platforms );

        usort(
            $platform_keys,
            function ( $a, $b ) use ( $order_map, $platforms ) {
				$order_a = isset( $order_map[ $a ] ) ? (int) $order_map[ $a ] : (int) ( $platforms[ $a ]['order'] ?? PHP_INT_MAX );
				$order_b = isset( $order_map[ $b ] ) ? (int) $order_map[ $b ] : (int) ( $platforms[ $b ]['order'] ?? PHP_INT_MAX );

				if ( $order_a === $order_b ) {
					return strcmp( $a, $b );
				}

				return $order_a <=> $order_b;
			}
        );

        $ordered_platforms = array();
        foreach ( $platform_keys as $index => $key ) {
            $platform                  = $platforms[ $key ];
            $platform['order']         = isset( $order_map[ $key ] )
                ? (int) $order_map[ $key ]
                : (int) ( $platform['order'] ?? ( $index + 1 ) );
            $ordered_platforms[ $key ] = $platform;
        }

        return $ordered_platforms;
    }

    /**
     * Retourne la carte des associations plateforme -> tags.
     */
    public function get_platform_tag_map() {
        $stored_map = get_option( $this->tag_map_option, array() );

        if ( ! is_array( $stored_map ) ) {
            $stored_map = array();
        }

        $platforms  = $this->get_platforms();
        $normalized = array();

        foreach ( $platforms as $key => $platform ) {
            $tags = array();

            if ( isset( $stored_map[ $key ] ) ) {
                $raw_tags = is_array( $stored_map[ $key ] ) ? $stored_map[ $key ] : array( $stored_map[ $key ] );

                foreach ( $raw_tags as $tag_value ) {
                    if ( is_numeric( $tag_value ) ) {
                        $tag_id = (int) $tag_value;
                        if ( $tag_id > 0 ) {
                            $tags[] = $tag_id;
                        }
                    } else {
                        $tag_slug = sanitize_title( $tag_value );

                        if ( $tag_slug === '' ) {
                            continue;
                        }

                        $term = term_exists( $tag_slug, 'post_tag' );

                        if ( is_array( $term ) && isset( $term['term_id'] ) ) {
                            $tags[] = (int) $term['term_id'];
                        } elseif ( is_numeric( $term ) ) {
                            $tags[] = (int) $term;
                        }
                    }
                }
            }

            $normalized[ $key ] = array_values( array_unique( $tags ) );
        }

        return $normalized;
    }

    /**
     * Sauvegarde la carte plateforme -> tags.
     *
     * @param array $map Carte des tags par plateforme.
     */
    public function save_platform_tag_map( $map ) {
        if ( ! is_array( $map ) ) {
            $map = array();
        }

        $normalized = array();

        foreach ( $this->get_platforms() as $key => $platform ) {
            $values = isset( $map[ $key ] ) && is_array( $map[ $key ] ) ? $map[ $key ] : array();

            $values = array_filter(
                array_map(
                    static function ( $tag_id ) {
                        $tag_id = (int) $tag_id;
                        return $tag_id > 0 ? $tag_id : 0;
                    },
                    $values
                )
            );

            $normalized[ $key ] = array_values( array_unique( $values ) );
        }

        return update_option( $this->tag_map_option, $normalized );
    }

    private function get_default_platform_definitions() {
        return $this->default_platforms;
    }

    private function get_stored_platform_data() {
        $stored = get_option( $this->option_name, array() );

        if ( ! is_array( $stored ) ) {
            $stored = array();
        }

        if ( isset( $stored['custom_platforms'] ) || isset( $stored['order'] ) ) {
            $custom = isset( $stored['custom_platforms'] ) && is_array( $stored['custom_platforms'] )
                ? $stored['custom_platforms']
                : array();
            $order  = isset( $stored['order'] ) && is_array( $stored['order'] )
                ? array_map( 'intval', $stored['order'] )
                : array();

            return array(
                'custom_platforms' => $custom,
                'order'            => $order,
            );
        }

        $custom = array();
        $order  = array();
        foreach ( $stored as $key => $platform ) {
            if ( ! is_array( $platform ) ) {
                continue;
            }

            if ( ! empty( $platform['custom'] ) ) {
                $custom[ $key ] = array(
                    'name'   => $platform['name'] ?? '',
                    'icon'   => $platform['icon'] ?? '🎮',
                    'custom' => true,
                );
            }

            if ( isset( $platform['order'] ) ) {
                $order[ $key ] = (int) $platform['order'];
            }
        }

        return array(
            'custom_platforms' => $custom,
            'order'            => $order,
        );
    }

    private function ensure_storage_structure( &$platforms ) {
        if ( ! isset( $platforms['custom_platforms'] ) || ! is_array( $platforms['custom_platforms'] ) ) {
            $platforms['custom_platforms'] = array();
        }

        if ( ! isset( $platforms['order'] ) || ! is_array( $platforms['order'] ) ) {
            $platforms['order'] = array();
        }
    }

    /**
     * Obtenir uniquement les noms des plateformes (pour les metaboxes)
     */
    public function get_platform_names() {
        $platforms = $this->get_platforms();
        $names     = array();
        foreach ( $platforms as $key => $platform ) {
            $names[ $key ] = $platform['name'];
        }
        return $names;
    }

    /**
     * Gérer les actions (ajout, suppression, modification)
     */
    public function handle_platform_actions() {
        // Ne traiter que sur la page des plateformes
        if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'notation_jlg_settings' ) {
            return;
        }

        if ( ! isset( $_GET['tab'] ) || $_GET['tab'] !== 'plateformes' ) {
            return;
        }

        // Debug : Enregistrer l'appel de la méthode
        $this->log_debug( '🔄 handle_platform_actions() appelé' );
        $this->log_debug( '📍 Page actuelle : ' . ( $_GET['page'] ?? 'non définie' ) );
        $this->log_debug( '📍 Onglet actuel : ' . ( $_GET['tab'] ?? 'non défini' ) );

        // Debug : Vérifier les données POST
        if ( ! empty( $_POST ) ) {
            $this->log_debug( '📨 Données POST reçues : ' . wp_json_encode( array_keys( $_POST ) ) );
        }

        if ( ! isset( $_POST['jlg_platform_action'] ) ) {
            if ( ! empty( $_POST ) ) {
                $this->log_debug( '❌ jlg_platform_action non trouvé dans POST' );
            }
            return;
        }

        $posted_action    = wp_unslash( $_POST['jlg_platform_action'] );
        $sanitized_action = sanitize_text_field( $posted_action );
        $this->log_debug( '✅ Action détectée : ' . $sanitized_action );

        // Vérifier les permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            $this->log_debug( '❌ Permissions insuffisantes' );
            wp_die( esc_html__( 'Permissions insuffisantes', 'notation-jlg' ) );
        }
        $this->log_debug( '✅ Permissions OK' );

        // Vérifier le nonce
        if ( ! isset( $_POST['jlg_platform_nonce'] ) ) {
            $this->log_debug( '❌ Nonce non trouvé' );
            return;
        }

        $posted_nonce    = wp_unslash( $_POST['jlg_platform_nonce'] );
        $sanitized_nonce = sanitize_text_field( $posted_nonce );
        if ( ! wp_verify_nonce( $posted_nonce, 'jlg_platform_action' ) ) {
            $this->log_debug( '❌ Nonce invalide : ' . $sanitized_nonce );
            wp_die( esc_html__( 'Erreur de sécurité', 'notation-jlg' ) );
        }
        $this->log_debug( '✅ Nonce valide' );

        $action    = $sanitized_action;
        $platforms = $this->get_stored_platform_data();
        $this->log_debug( '📦 Plateformes actuelles dans la DB : ' . count( $platforms['custom_platforms'] ) . ' personnalisées' );

        $success = false;
        $message = '';

        switch ( $action ) {
            case 'add':
                $result  = $this->add_platform( $platforms );
                $success = $result['success'];
                $message = $result['message'];
                break;

            case 'delete':
                $result  = $this->delete_platform( $platforms );
                $success = $result['success'];
                $message = $result['message'];
                break;

            case 'update_order':
                $result  = $this->update_platform_order( $platforms );
                $success = $result['success'];
                $message = $result['message'];
                break;

            case 'update_tag_links':
                $result  = $this->update_platform_tag_links();
                $success = $result['success'];
                $message = $result['message'];
                break;

            case 'reset':
                delete_option( $this->option_name );
                delete_option( $this->tag_map_option );
                $success = true;
                $message = esc_html__( 'Plateformes réinitialisées avec succès !', 'notation-jlg' );
                $this->log_debug( '🔄 Option supprimée de la DB' );
                break;
        }

        // Stocker le message pour l'affichage
        if ( $success ) {
            $this->log_debug( '✅ Action réussie : ' . $message );
        } else {
            $this->log_debug( '❌ Erreur : ' . $message );
        }

        $message_data = array(
            'type'    => $success ? 'success' : 'error',
            'message' => $message,
        );

        set_transient( 'jlg_platforms_message', $message_data, 30 );

        $redirect_args = array(
            'page' => 'notation_jlg_settings',
            'tab'  => 'plateformes',
        );

        if ( isset( $_GET['debug'] ) ) {
            $redirect_args['debug'] = sanitize_text_field( wp_unslash( $_GET['debug'] ) );
        }

        $redirect_url = add_query_arg( $redirect_args, admin_url( 'admin.php' ) );

        // Stocker les messages de debug dans un transient
        if ( $this->is_debug_enabled() && ! empty( self::$debug_messages ) ) {
            set_transient( 'jlg_platforms_debug', self::$debug_messages, 60 );
        }

        wp_safe_redirect( $redirect_url );
        exit;
    }

    /**
     * Ajouter une nouvelle plateforme
     */
    private function add_platform( &$platforms ) {
        $this->log_debug( "🎯 Tentative d'ajout de plateforme" );

        $this->ensure_storage_structure( $platforms );

        if ( empty( $_POST['new_platform_name'] ) ) {
            $this->log_debug( '❌ Nom de plateforme vide' );
            return array(
				'success' => false,
				'message' => esc_html__( 'Le nom de la plateforme est requis.', 'notation-jlg' ),
			);
        }

        $name       = sanitize_text_field( wp_unslash( $_POST['new_platform_name'] ) );
        $icon_input = isset( $_POST['new_platform_icon'] ) ? wp_unslash( $_POST['new_platform_icon'] ) : '🎮';
        $icon       = sanitize_text_field( $icon_input );

        $this->log_debug( "📝 Nom : $name, Icône : $icon" );

        // Générer une clé unique
        $key = sanitize_title( $name );
        if ( empty( $key ) ) {
            $this->log_debug( "❌ Clé générée vide pour le nom : $name" );
            return array(
				'success' => false,
				'message' => esc_html__( 'Nom de plateforme invalide.', 'notation-jlg' ),
			);
        }

        // Ajouter un suffixe si la clé existe déjà
        $original_key  = $key;
        $suffix        = 1;
        $all_platforms = $this->get_platforms();
        while ( isset( $all_platforms[ $key ] ) ) {
            $key = $original_key . '-' . $suffix;
            ++$suffix;
        }

        $this->log_debug( "🔑 Clé générée : $key" );

        // Trouver l'ordre maximum
        $max_order = 0;
        foreach ( $all_platforms as $platform ) {
            $max_order = max( $max_order, $platform['order'] ?? 0 );
        }

        // Ajouter la nouvelle plateforme
        $platforms['custom_platforms'][ $key ] = array(
            'name'   => $name,
            'icon'   => $icon,
            'custom' => true,
        );
        $platforms['order'][ $key ]            = $max_order + 1;

        $this->log_debug( '💾 Tentative de sauvegarde dans la DB' );
        $this->log_debug( '📊 Données à sauvegarder : ' . wp_json_encode( $platforms['custom_platforms'][ $key ] ) );

        $result = update_option( $this->option_name, $platforms );

        if ( $result || get_option( $this->option_name ) !== false ) {
            $this->log_debug( '✅ Plateforme ajoutée et sauvegardée' );

            // Vérification que la sauvegarde a bien fonctionné
            $saved = get_option( $this->option_name );
            if ( isset( $saved['custom_platforms'][ $key ] ) ) {
                $this->log_debug( '✅ Vérification : plateforme bien présente dans la DB' );
            } else {
                $this->log_debug( "⚠️ La plateforme a été sauvegardée mais n'apparaît pas dans la vérification" );
            }

            return array(
                'success' => true,
                'message' => sprintf(
                    /* translators: %s: platform name. */
                    esc_html__( "Plateforme '%s' ajoutée avec succès !", 'notation-jlg' ),
                    $name
                ),
            );
        } else {
            $this->log_debug( '❌ Échec de la sauvegarde dans la DB' );
            return array(
				'success' => false,
				'message' => esc_html__( 'Erreur lors de la sauvegarde.', 'notation-jlg' ),
			);
        }
    }

    /**
     * Supprimer une plateforme
     */
    private function delete_platform( &$platforms ) {
        $this->log_debug( '🗑️ Tentative de suppression de plateforme' );

        $this->ensure_storage_structure( $platforms );

        if ( empty( $_POST['platform_key'] ) ) {
            $this->log_debug( '❌ Clé de plateforme manquante' );
            return array(
				'success' => false,
				'message' => esc_html__( 'Clé de plateforme manquante.', 'notation-jlg' ),
			);
        }

        $key = sanitize_text_field( wp_unslash( $_POST['platform_key'] ) );
        $this->log_debug( "🔑 Clé à supprimer : $key" );

        $all_platforms = $this->get_platforms();
        if ( ! isset( $all_platforms[ $key ] ) ) {
            $this->log_debug( '❌ Plateforme introuvable' );
            return array(
				'success' => false,
				'message' => esc_html__( 'Plateforme introuvable.', 'notation-jlg' ),
			);
        }

        if ( ! isset( $platforms['custom_platforms'][ $key ] ) || empty( $platforms['custom_platforms'][ $key ]['custom'] ) ) {
            $platform_name = $all_platforms[ $key ]['name'] ?? 'Inconnue';
            $this->log_debug( "❌ Suppression refusée pour la plateforme non personnalisée '$platform_name'" );
            return array(
                'success' => false,
                'message' => sprintf(
                    /* translators: %s: platform name. */
                    esc_html__( "La plateforme '%s' est une plateforme par défaut et ne peut pas être supprimée.", 'notation-jlg' ),
                    $platform_name
                ),
            );
        }

        $platform_name = $platforms['custom_platforms'][ $key ]['name'] ?? $all_platforms[ $key ]['name'] ?? 'Inconnue';
        unset( $platforms['custom_platforms'][ $key ] );
        unset( $platforms['order'][ $key ] );

        $result = update_option( $this->option_name, $platforms );

        if ( $result || get_option( $this->option_name ) !== false ) {
            $this->log_debug( "✅ Plateforme '$platform_name' supprimée" );
            return array(
                'success' => true,
                'message' => sprintf(
                    /* translators: %s: platform name. */
                    esc_html__( "Plateforme '%s' supprimée avec succès !", 'notation-jlg' ),
                    $platform_name
                ),
            );
        } else {
            $this->log_debug( '❌ Échec de la suppression' );
            return array(
				'success' => false,
				'message' => esc_html__( 'Erreur lors de la suppression.', 'notation-jlg' ),
			);
        }
    }

    /**
     * Mettre à jour l'ordre des plateformes
     */
    private function update_platform_order( &$platforms ) {
        $this->log_debug( "🔄 Mise à jour de l'ordre des plateformes" );

        $this->ensure_storage_structure( $platforms );

        if ( ! isset( $_POST['platform_order'] ) || ! is_array( $_POST['platform_order'] ) ) {
            $this->log_debug( "❌ Données d'ordre manquantes" );
            return array(
				'success' => false,
				'message' => esc_html__( 'Données d\'ordre manquantes.', 'notation-jlg' ),
			);
        }

        $raw_order       = array_filter( wp_unslash( $_POST['platform_order'] ), 'strlen' );
        $submitted_order = array_map(
            function ( $value ) {
				return sanitize_text_field( $value );
			},
            array_values( $raw_order )
        );
        if ( empty( $submitted_order ) ) {
            $this->log_debug( '❌ Ordre soumis vide' );
            return array(
				'success' => false,
				'message' => esc_html__( 'Ordre soumis invalide.', 'notation-jlg' ),
			);
        }

        $all_platforms = $this->get_platforms();
        $ordered_keys  = array();

        foreach ( $submitted_order as $key ) {
            if ( ! isset( $all_platforms[ $key ] ) ) {
                $this->log_debug( "⚠️ Plateforme inconnue ignorée : $key" );
                continue;
            }

            if ( in_array( $key, $ordered_keys, true ) ) {
                $this->log_debug( "⚠️ Doublon ignoré dans l'ordre : $key" );
                continue;
            }

            $ordered_keys[] = $key;
        }

        if ( empty( $ordered_keys ) ) {
            $this->log_debug( "❌ Aucun élément valide dans l'ordre soumis" );
            return array(
				'success' => false,
				'message' => esc_html__( 'Aucune plateforme valide reçue.', 'notation-jlg' ),
			);
        }

        foreach ( $all_platforms as $key => $platform_data ) {
            if ( ! in_array( $key, $ordered_keys, true ) ) {
                $ordered_keys[] = $key;
            }
        }

        $new_order = array();
        foreach ( $ordered_keys as $position => $key ) {
            $new_order[ $key ] = $position + 1;
        }

        $platforms['order'] = $new_order;
        $this->log_debug( '📊 ' . count( $new_order ) . ' positions sauvegardées' );

        $result = update_option( $this->option_name, $platforms );

        if ( $result || get_option( $this->option_name ) !== false ) {
            $this->log_debug( '✅ Ordre sauvegardé' );
            return array(
				'success' => true,
				'message' => esc_html__( 'Ordre des plateformes mis à jour !', 'notation-jlg' ),
			);
        } else {
            $this->log_debug( "❌ Échec de la sauvegarde de l'ordre" );
            return array(
				'success' => false,
				'message' => esc_html__( 'Erreur lors de la mise à jour.', 'notation-jlg' ),
			);
        }
    }

    /**
     * Met à jour les liens plateformes -> tags.
     */
    private function update_platform_tag_links() {
        $this->log_debug( '🔗 Mise à jour des associations plateforme -> tags' );

        $raw_map = isset( $_POST['platform_tags'] ) ? wp_unslash( $_POST['platform_tags'] ) : array();

        $sanitized_map = $this->sanitize_platform_tag_map_input( $raw_map );
        $this->log_debug( '📊 Carte tags normalisée : ' . wp_json_encode( $sanitized_map ) );

        $saved       = $this->save_platform_tag_map( $sanitized_map );
        $current_map = $this->get_platform_tag_map();
        $success     = $saved || $current_map === $sanitized_map;

        if ( $success ) {
            return array(
                'success' => true,
                'message' => esc_html__( 'Associations de tags mises à jour avec succès !', 'notation-jlg' ),
            );
        }

        return array(
            'success' => false,
            'message' => esc_html__( 'Erreur lors de la mise à jour des tags.', 'notation-jlg' ),
        );
    }

    /**
     * Nettoie et valide les données envoyées pour les associations de tags.
     *
     * @param mixed $raw_map Données brutes issues du formulaire.
     *
     * @return array
     */
    private function sanitize_platform_tag_map_input( $raw_map ) {
        if ( ! is_array( $raw_map ) ) {
            $raw_map = array();
        }

        $platforms = $this->get_platforms();
        $sanitized = array();

        foreach ( $platforms as $key => $platform ) {
            $submitted_tags = array();

            if ( isset( $raw_map[ $key ] ) ) {
                $candidate_tags = $raw_map[ $key ];

                if ( ! is_array( $candidate_tags ) ) {
                    $candidate_tags = $candidate_tags === '' ? array() : array( $candidate_tags );
                }

                foreach ( $candidate_tags as $tag_value ) {
                    if ( is_numeric( $tag_value ) ) {
                        $tag_id = (int) $tag_value;

                        if ( $tag_id > 0 && term_exists( $tag_id, 'post_tag' ) ) {
                            $submitted_tags[] = $tag_id;
                        }
                    } else {
                        $tag_slug = sanitize_title( $tag_value );

                        if ( $tag_slug === '' ) {
                            continue;
                        }

                        $term = term_exists( $tag_slug, 'post_tag' );

                        if ( is_array( $term ) && isset( $term['term_id'] ) ) {
                            $submitted_tags[] = (int) $term['term_id'];
                        } elseif ( is_numeric( $term ) ) {
                            $submitted_tags[] = (int) $term;
                        }
                    }
                }
            }

            $submitted_tags = array_values( array_unique( array_filter( $submitted_tags ) ) );
            sort( $submitted_tags );

            $sanitized[ $key ] = $submitted_tags;
        }

        return $sanitized;
    }

    /**
     * Afficher la page de gestion
     */
    public function render_platforms_page() {
        // Récupérer et afficher le message s'il existe
        $message = get_transient( 'jlg_platforms_message' );
        if ( $message ) {
            $class = $message['type'] === 'success' ? 'notice-success' : 'notice-error';
            echo '<div class="notice ' . $class . ' is-dismissible"><p>' . esc_html( $message['message'] ) . '</p></div>';
            delete_transient( 'jlg_platforms_message' );
        }

        $platforms = $this->get_platforms();
        $tag_map   = $this->get_platform_tag_map();
        ?>

        <!-- ZONE DE DEBUG AMÉLIORÉE -->
        <?php
        $show_debug = $this->is_debug_enabled();
        if ( $show_debug ) :
            $debug_messages = get_transient( 'jlg_platforms_debug' );
			?>
            <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 5px;">
                <h3 style="margin-top: 0;">🐛 Mode Debug - Informations de diagnostic</h3>

                <?php if ( $debug_messages && ! empty( $debug_messages ) ) : ?>
                <div style="background: #fff; padding: 10px; border-radius: 3px; font-family: monospace; font-size: 12px; max-height: 300px; overflow-y: auto;">
                    <?php foreach ( $debug_messages as $msg ) : ?>
                        <div style="margin: 5px 0;"><?php echo esc_html( $msg ); ?></div>
                    <?php endforeach; ?>
                </div>
                <?php else : ?>
                <p>Aucun message de debug pour le moment. Essayez d'effectuer une action.</p>
                <?php endif; ?>
                
                <hr style="margin: 15px 0;">
                
                <details>
                    <summary style="cursor: pointer; font-weight: bold;">📊 Informations système</summary>
                    <ul style="font-family: monospace; font-size: 12px; margin-top: 10px;">
                        <li>PHP Version : <?php echo PHP_VERSION; ?></li>
                        <li>WordPress Version : <?php echo get_bloginfo( 'version' ); ?></li>
                        <li>Page actuelle : <?php echo esc_html( $_GET['page'] ?? 'non définie' ); ?></li>
                        <li>Onglet actuel : <?php echo esc_html( $_GET['tab'] ?? 'non défini' ); ?></li>
                        <li>URL actuelle : <?php echo esc_url( $_SERVER['REQUEST_URI'] ); ?></li>
                        <li>Méthode HTTP : <?php echo esc_html( $_SERVER['REQUEST_METHOD'] ?? 'inconnue' ); ?></li>
                        <li>Plateformes sauvegardées : <?php echo count( $this->get_stored_platform_data()['custom_platforms'] ); ?> personnalisées</li>
                        <li>Total plateformes : <?php echo count( $platforms ); ?></li>
                        <li>Hook admin_init exécuté : <?php echo did_action( 'admin_init' ); ?> fois</li>
                        <li>Utilisateur peut gérer les options : <?php echo current_user_can( 'manage_options' ) ? 'Oui' : 'Non'; ?></li>
                    </ul>
                </details>
                
                <?php
                if ( ! empty( $_POST ) ) :
                    $post_payload = wp_unslash( $_POST );
                    $post_json    = wp_json_encode( $post_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
                    ?>
                <details style="margin-top: 15px;">
                    <summary style="cursor: pointer; font-weight: bold;">📨 Données POST reçues</summary>
                    <pre style="background: #f5f5f5; padding: 10px; overflow: auto; margin-top: 10px; font-size: 11px;">
                        <?php echo esc_html( $post_json ? $post_json : '' ); ?>
                    </pre>
                </details>
                <?php endif; ?>
                
                <p style="margin-top: 15px; margin-bottom: 0;">
                    <a href="<?php echo esc_url( add_query_arg( 'debug', '0' ) ); ?>" class="button button-small">Masquer le debug</a>
                </p>
            </div>
			<?php
			delete_transient( 'jlg_platforms_debug' );
        else :
			?>
        <p style="text-align: right;">
            <a href="<?php echo esc_url( add_query_arg( 'debug', '1' ) ); ?>" class="button button-small">🐛 Activer le mode debug</a>
        </p>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">

            <!-- Liste des plateformes -->
            <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h3>Plateformes actuelles</h3>

                <form method="post" action="">
                    <?php wp_nonce_field( 'jlg_platform_action', 'jlg_platform_nonce' ); ?>
                    <input type="hidden" name="jlg_platform_action" value="update_order">

                    <table class="wp-list-table widefat striped jlg-platforms-table">
                        <thead>
                            <tr>
                                <th scope="col" class="manage-column column-handle" style="width: 40px;">
                                    <span class="screen-reader-text">Réordonner les plateformes</span>
                                </th>
                                <th scope="col" class="manage-column column-order" style="width: 60px;">Ordre</th>
                                <th scope="col" class="manage-column column-icon" style="width: 50px;">Icône</th>
                                <th scope="col" class="manage-column column-primary">Nom</th>
                                <th scope="col" class="manage-column column-actions" style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="platforms-list" class="jlg-sortable-list">
                            <?php
                            $position = 1;
                            foreach ( $platforms as $key => $platform ) :
                                $linked_tag_terms = array();

                                if ( class_exists( Helpers::class ) ) {
                                    $linked_tag_terms = Helpers::get_platform_tags( $key );
                                } elseif ( isset( $tag_map[ $key ] ) && ! empty( $tag_map[ $key ] ) ) {
                                    $fallback_terms = get_terms(
                                        array(
                                            'taxonomy'   => 'post_tag',
                                            'hide_empty' => false,
                                            'include'    => $tag_map[ $key ],
                                        )
                                    );

                                    if ( ! is_wp_error( $fallback_terms ) ) {
                                        $linked_tag_terms = $fallback_terms;
                                    }
                                }
                                ?>
                            <tr class="jlg-platform-row" data-key="<?php echo esc_attr( $key ); ?>">
                                <td class="jlg-sort-handle" style="cursor: move; text-align: center;" title="Glissez pour réordonner">
                                    <span class="dashicons dashicons-menu" aria-hidden="true"></span>
                                    <span class="screen-reader-text">Réordonner <?php echo esc_html( $platform['name'] ); ?></span>
                                </td>
                                <td class="jlg-platform-position">
                                    <?php echo esc_html( $position ); ?>
                                </td>
                                <td style="text-align: center; font-size: 20px;">
                                    <?php echo esc_html( $platform['icon'] ?? '🎮' ); ?>
                                </td>
                                <td>
                                    <strong><?php echo esc_html( $platform['name'] ); ?></strong>
                                    <?php if ( isset( $platform['custom'] ) && $platform['custom'] ) : ?>
                                        <span style="color: #666; font-size: 12px;">(Personnalisée)</span>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $linked_tag_terms ) ) : ?>
                                        <ul class="jlg-platform-tags" style="margin: 8px 0 0; padding: 0; list-style: none; display: flex; flex-wrap: wrap; gap: 6px;">
                                            <?php
                                            foreach ( $linked_tag_terms as $term ) :
												if ( $term instanceof WP_Term ) :
													?>
                                                <li style="background: #f0f6fc; border: 1px solid #d0e3f3; color: #1d4f73; padding: 2px 8px; border-radius: 999px; font-size: 11px;">
                                                    <?php echo esc_html( $term->name ); ?>
                                                </li>
													<?php
                                            endif;
endforeach;
											?>
                                        </ul>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ( isset( $platform['custom'] ) && $platform['custom'] ) : ?>
                                        <button type="button"
                                                class="button button-small delete-platform"
                                                data-key="<?php echo esc_attr( $key ); ?>"
                                                data-name="<?php echo esc_attr( $platform['name'] ); ?>">
                                            ❌ Supprimer
                                        </button>
                                    <?php else : ?>
                                        <span style="color: #999;">Par défaut</span>
                                    <?php endif; ?>
                                    <input type="hidden" name="platform_order[]" value="<?php echo esc_attr( $key ); ?>">
                                </td>
                            </tr>
								<?php
                                ++$position;
                            endforeach;
                            ?>
                        </tbody>
                    </table>

                    <p class="description" style="margin-top: 10px;">
                        Faites glisser les lignes à l'aide de la poignée pour réorganiser les plateformes. L'ordre est enregistré automatiquement lors de la sauvegarde.
                    </p>

                    <p style="margin-top: 15px;">
                        <input type="submit" class="button button-primary" value="💾 Enregistrer l'ordre">
                    </p>
                </form>
            </div>

            <!-- Formulaires et actions complémentaires -->
            <div>
                <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                    <h3>🏷️ Associer des tags aux plateformes</h3>
                    <p class="description" style="margin-bottom: 15px;">
                        Reliez vos plateformes aux tags WordPress pour alimenter vos filtres et contenus dynamiques.
                    </p>

                    <form method="post" action="">
                        <?php wp_nonce_field( 'jlg_platform_action', 'jlg_platform_nonce' ); ?>
                        <input type="hidden" name="jlg_platform_action" value="update_tag_links">

                        <table class="form-table">
                            <?php
                            foreach ( $platforms as $key => $platform ) :
                                $preselected = isset( $tag_map[ $key ] ) ? array_map( 'intval', (array) $tag_map[ $key ] ) : array();
                                $dropdown    = wp_dropdown_categories(
                                    array(
                                        'taxonomy'         => 'post_tag',
                                        'hide_empty'       => false,
                                        'name'             => 'platform_tags[' . $key . '][]',
                                        'id'               => 'platform_tags_' . $key,
                                        'selected'         => '',
                                        'echo'             => false,
                                        'multiple'         => true,
                                        'show_option_none' => false,
                                        'value_field'      => 'term_id',
                                        'class'            => 'widefat',
                                        'orderby'          => 'name',
                                        'walker'           => new MultiSelectCategoryDropdownWalker(),
                                    )
                                );
                                if ( ! empty( $preselected ) ) {
                                    $dropdown = $this->apply_multiple_dropdown_selection( $dropdown, $preselected );
                                }
                                ?>
                            <tr>
                                <th scope="row"><?php echo esc_html( $platform['name'] ); ?></th>
                                <td>
                                    <?php echo $dropdown; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                    <p class="description" style="margin-top: 6px;">
                                        <?php esc_html_e( 'Utilisez Ctrl/Cmd pour sélectionner plusieurs tags.', 'notation-jlg' ); ?>
                                    </p>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </table>

                        <p class="description" style="margin-top: 10px;">
                            <?php esc_html_e( 'Les associations sauvegardées pourront être exploitées dans vos templates front-office.', 'notation-jlg' ); ?>
                        </p>

                        <p>
                            <input type="submit" class="button button-primary" value="💾 Enregistrer les associations de tags">
                        </p>
                    </form>
                </div>

                <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                    <h3>➕ Ajouter une plateforme</h3>

                    <form method="post" action="">
                        <?php wp_nonce_field( 'jlg_platform_action', 'jlg_platform_nonce' ); ?>
                        <input type="hidden" name="jlg_platform_action" value="add">

                        <table class="form-table">
                            <tr>
                                <th><label for="new_platform_name">Nom de la plateforme <span style="color:red;">*</span></label></th>
                                <td>
                                    <input type="text"
                                            id="new_platform_name"
                                            name="new_platform_name"
                                            class="regular-text"
                                            placeholder="Ex: PlayStation 6"
                                            required>
                                    <p class="description">Entrez le nom de la nouvelle plateforme</p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="new_platform_icon">Icône</label></th>
                                <td>
                                    <select id="new_platform_icon" name="new_platform_icon" style="font-size: 20px;">
                                        <option value="🎮" selected>🎮 Manette</option>
                                        <option value="💻">💻 PC</option>
                                        <option value="📱">📱 Mobile</option>
                                        <option value="🕹️">🕹️ Arcade</option>
                                        <option value="🎯">🎯 Cible</option>
                                        <option value="🎲">🎲 Dé</option>
                                        <option value="🖥️">🖥️ Écran</option>
                                        <option value="⌨️">⌨️ Clavier</option>
                                    </select>
                                </td>
                            </tr>
                        </table>

                        <p>
                            <input type="submit" name="submit" class="button button-primary" value="➕ Ajouter la plateforme">
                        </p>
                    </form>
                </div>

                <!-- Actions supplémentaires -->
                <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h3>⚙️ Actions</h3>

                    <form method="post" action="" class="jlg-reset-platforms-form">
                        <?php wp_nonce_field( 'jlg_platform_action', 'jlg_platform_nonce' ); ?>
                        <input type="hidden" name="jlg_platform_action" value="reset">

                        <p>
                            <input type="submit" class="button" value="🔄 Réinitialiser aux plateformes par défaut">
                        </p>
                        <p class="description">
                            Cette action supprimera toutes les plateformes personnalisées et restaurera les plateformes par défaut.
                        </p>
                    </form>
                </div>

                <!-- Instructions -->
                <div style="background: #f0f6fc; padding: 20px; border-radius: 8px; margin-top: 20px;">
                    <h3>💡 Instructions</h3>
                    <ul style="margin-left: 20px;">
                        <li>Les plateformes par défaut ne peuvent pas être supprimées</li>
                        <li>Vous pouvez réorganiser toutes les plateformes en changeant leur ordre</li>
                        <li>Les plateformes personnalisées peuvent être supprimées à tout moment</li>
                        <li>Les icônes ajoutent une touche visuelle dans l'interface admin</li>
                        <li>Après chaque action, rafraîchissez la page si nécessaire</li>
                    </ul>
                </div>
            </div>
        </div>

        <?php
    }

    /**
     * Injecte les attributs selected="selected" dans un dropdown multi-sélection.
     *
     * @param string $dropdown      Markup HTML retourné par wp_dropdown_categories().
     * @param array  $selected_ids  Identifiants de termes à présélectionner.
     *
     * @return string
     */
    private function apply_multiple_dropdown_selection( $dropdown, array $selected_ids ) {
        if ( ! is_string( $dropdown ) || $dropdown === '' ) {
            return (string) $dropdown;
        }

        $normalized = array();

        foreach ( $selected_ids as $id ) {
            if ( is_numeric( $id ) ) {
                $normalized[] = (string) (int) $id;
            }
        }

        if ( empty( $normalized ) ) {
            return $dropdown;
        }

        $normalized = array_values( array_unique( $normalized ) );

        foreach ( $normalized as $value ) {
            $pattern = '/(<option\b[^>]*\bvalue="' . preg_quote( $value, '/' ) . '"[^>]*)(>)/i';
            $count   = 0;

            $dropdown = preg_replace_callback(
                $pattern,
                static function ( $matches ) {
                    if ( stripos( $matches[1], 'selected=' ) !== false ) {
                        return $matches[0];
                    }

                    return $matches[1] . ' selected="selected"' . $matches[2];
                },
                $dropdown,
                1,
                $count
            );

            if ( $dropdown === null || 0 === $count ) {
                continue;
            }
        }

        return $dropdown;
    }

    private function is_debug_enabled() {
        if ( $this->debug_enabled !== null ) {
            return $this->debug_enabled;
        }

        $options = Helpers::get_plugin_options();
        $enabled = ! empty( $options['debug_mode_enabled'] );

        if ( isset( $_GET['debug'] ) ) {
            $raw_debug = sanitize_text_field( wp_unslash( $_GET['debug'] ) );
            if ( $raw_debug === '0' || strtolower( $raw_debug ) === 'false' ) {
                $enabled = false;
            } else {
                $enabled = true;
            }
        }

        $this->debug_enabled = (bool) $enabled;

        return $this->debug_enabled;
    }

    private function log_debug( $message ) {
        if ( ! $this->is_debug_enabled() ) {
            return;
        }

        if ( is_scalar( $message ) ) {
            self::$debug_messages[] = (string) $message;
            return;
        }

        self::$debug_messages[] = wp_json_encode( $message );
    }
}

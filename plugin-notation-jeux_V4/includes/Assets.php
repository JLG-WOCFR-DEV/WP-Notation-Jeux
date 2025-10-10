<?php

namespace JLG\Notation;

use JLG\Notation\Admin\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Assets {
    private static $instance = null;
    private $localizations   = array();
    private $text_domain     = 'notation-jlg';
    private $languages_path;

    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        $this->languages_path = JLG_NOTATION_PLUGIN_DIR . 'languages';
        $this->register_default_localizations();

        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'maybe_localize_scripts' ), 20, 0 );
        add_action( 'wp_enqueue_scripts', array( $this, 'maybe_localize_scripts' ), 20, 0 );
    }

    public function enqueue_admin_assets( $hook_suffix ) {
        $plugin_pages = array(
            'toplevel_page_notation_jlg_settings',
            'notation-jlg_page_notation_jlg_settings',
            'notation_jlg_page_notation_jlg_settings',
        );

        if ( ! in_array( $hook_suffix, $plugin_pages, true ) ) {
            return;
        }

        $version = defined( 'JLG_NOTATION_VERSION' ) ? JLG_NOTATION_VERSION : false;

        wp_enqueue_style( 'wp-color-picker' );

        wp_register_style(
            'jlg-admin-styles',
            JLG_NOTATION_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            $version
        );

        wp_enqueue_style( 'jlg-admin-styles' );
        wp_enqueue_script( 'wp-color-picker' );

        wp_enqueue_script(
            'jlg-admin-color-picker',
            JLG_NOTATION_PLUGIN_URL . 'assets/js/admin-color-picker.js',
            array( 'wp-color-picker' ),
            $version,
            true
        );

        $sections_overview  = array();
        $field_dependencies = array();
        $preview_snapshot   = array();

        $core_instance = Core::get_instance();
        if ( $core_instance ) {
            $settings_component = $core_instance->get_component( 'settings' );

            if ( $settings_component && method_exists( $settings_component, 'get_sections_overview' ) ) {
                $sections_overview = $settings_component->get_sections_overview();
            }

            if ( $settings_component && method_exists( $settings_component, 'get_field_dependencies' ) ) {
                $field_dependencies = $settings_component->get_field_dependencies();
            }

            if ( $settings_component && method_exists( $settings_component, 'get_preview_snapshot' ) ) {
                $preview_snapshot = $settings_component->get_preview_snapshot();
            }
        }

        wp_enqueue_script(
            'jlg-admin-settings',
            JLG_NOTATION_PLUGIN_URL . 'assets/js/admin-settings.js',
            array(),
            $version,
            true
        );

        wp_localize_script(
            'jlg-admin-settings',
            'jlgAdminSettingsData',
            array(
                'sections'     => $sections_overview,
                'dependencies' => $field_dependencies,
                'preview'      => $preview_snapshot,
                'i18n'         => array(
                    'dependencyInactive' => __( 'Activez l’option associée pour modifier ce réglage.', 'notation-jlg' ),
                    'filterNoResult'     => __( 'Aucun réglage ne correspond à votre recherche.', 'notation-jlg' ),
                    'contrastExcellent'  => __( 'Contraste AAA : lecture optimale', 'notation-jlg' ),
                    'contrastGood'       => __( 'Contraste AA : conforme aux recommandations', 'notation-jlg' ),
                    'contrastWarning'    => __( 'Contraste limite : privilégiez une taille de police élevée', 'notation-jlg' ),
                    'contrastFail'       => __( 'Contraste insuffisant : ajustez vos couleurs', 'notation-jlg' ),
                ),
            )
        );

        $active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'reglages';

        if ( $active_tab === 'diagnostics' ) {
            wp_enqueue_script(
                'jlg-admin-diagnostics',
                JLG_NOTATION_PLUGIN_URL . 'assets/js/admin-diagnostics.js',
                array(),
                $version,
                true
            );
        }

        if ( $active_tab !== 'plateformes' ) {
            return;
        }

        wp_enqueue_script( 'jquery-ui-sortable' );

        $handle = 'jlg-platforms-order';
        $src    = JLG_NOTATION_PLUGIN_URL . 'assets/js/jlg-platforms-order.js';
        $deps   = array( 'jquery', 'jquery-ui-sortable' );

        wp_register_script( $handle, $src, $deps, $version, true );

        wp_localize_script(
            $handle,
            'jlgPlatformsOrder',
            array(
                'listSelector'     => '#platforms-list',
                'positionSelector' => '.jlg-platform-position',
                'handleSelector'   => '.jlg-sort-handle',
                'rowSelector'      => 'tr[data-key]',
                'inputSelector'    => 'input[name="platform_order[]"]',
                'placeholderClass' => 'jlg-sortable-placeholder',
            )
        );

        wp_localize_script(
            $handle,
            'jlgPlatformsOrderL10n',
            array(
                'confirmReset'  => esc_html__( 'Êtes-vous sûr de vouloir réinitialiser toutes les plateformes ?', 'notation-jlg' ),
                'confirmDelete' => esc_html__( 'Êtes-vous sûr de vouloir supprimer la plateforme "%s" ?', 'notation-jlg' ),
                'nonce'         => wp_create_nonce( 'jlg_platform_action' ),
                'nonceField'    => 'jlg_platform_nonce',
                'actionField'   => 'jlg_platform_action',
                'deleteAction'  => 'delete',
			)
        );

        wp_enqueue_script( $handle );
    }

    public function register_localization( $handle, $object_name, callable $data_callback, $text_domain = null ) {
        if ( empty( $handle ) || empty( $object_name ) ) {
            return;
        }

        $this->localizations[ $handle ] = array(
            'object_name'   => $object_name,
            'data_callback' => $data_callback,
            'text_domain'   => $text_domain ?: $this->text_domain,
        );
    }

    public function maybe_localize_scripts() {
        if ( empty( $this->localizations ) ) {
            return;
        }

        foreach ( $this->localizations as $handle => $config ) {
            if ( ! wp_script_is( $handle, 'enqueued' ) ) {
                continue;
            }

            $data = array();
            if ( is_callable( $config['data_callback'] ) ) {
                $data = call_user_func( $config['data_callback'] );
            }

            if ( ! empty( $data ) ) {
                wp_localize_script( $handle, $config['object_name'], $data );
            }

            if ( function_exists( 'wp_set_script_translations' ) && is_dir( $this->languages_path ) ) {
                $text_domain = $config['text_domain'] ?? $this->text_domain;
                wp_set_script_translations( $handle, $text_domain, $this->languages_path );
            }
        }
    }

    private function register_default_localizations() {
        $this->register_localization(
            'jlg-user-rating',
            'jlgUserRatingL10n',
            function () {
                return array(
					'successMessage'       => __( 'Merci pour votre vote !', 'notation-jlg' ),
					'genericErrorMessage'  => __( 'Erreur. Veuillez réessayer.', 'notation-jlg' ),
					'alreadyVotedMessage'  => __( 'Vous avez déjà voté !', 'notation-jlg' ),
					'loginRequiredMessage' => __( 'Connectez-vous pour voter.', 'notation-jlg' ),
					'loginLinkLabel'       => __( 'Se connecter', 'notation-jlg' ),
				);
			}
        );

        $this->register_localization(
            'jlg-admin-api',
            'jlgAdminApiL10n',
            function () {
				return array(
					'invalidAjaxConfig'  => __( 'Configuration AJAX invalide.', 'notation-jlg' ),
					'missingNonce'       => __( 'Nonce de sécurité manquant. Actualisez la page.', 'notation-jlg' ),
					'minCharsMessage'    => __( 'Veuillez entrer au moins 3 caractères.', 'notation-jlg' ),
					'searchingText'      => __( 'Recherche...', 'notation-jlg' ),
					'loadingText'        => __( 'Chargement...', 'notation-jlg' ),
					'searchButtonLabel'  => __( 'Rechercher', 'notation-jlg' ),
					'securityFailed'     => __( 'Vérification de sécurité échouée. Actualisez la page.', 'notation-jlg' ),
					'selectLabel'        => __( 'Choisir', 'notation-jlg' ),
					'communicationError' => __( 'Erreur de communication.', 'notation-jlg' ),
					'filledMessage'      => __( 'Fiche technique remplie !', 'notation-jlg' ),
					'notAvailableLabel'  => __( 'N/A', 'notation-jlg' ),
				);
			}
        );

        $this->register_localization(
            'jlg-game-explorer',
            'jlgGameExplorerL10n',
            function () {
                return array(
                    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                    'nonce'   => wp_create_nonce( 'jlg_game_explorer' ),
                    'strings' => array(
                        'loading'                => esc_html__( 'Chargement des jeux...', 'notation-jlg' ),
                        'noResults'              => esc_html__( 'Aucun jeu ne correspond à votre sélection.', 'notation-jlg' ),
                        'reset'                  => esc_html__( 'Réinitialiser les filtres', 'notation-jlg' ),
                        'genericError'           => esc_html__( 'Impossible de charger les jeux pour le moment.', 'notation-jlg' ),
                        'countSingular'          => esc_html__( '%d jeu', 'notation-jlg' ),
                        'countPlural'            => esc_html__( '%d jeux', 'notation-jlg' ),
                        'resultsUpdatedSingular' => esc_html__( '%d résultat mis à jour', 'notation-jlg' ),
                        'resultsUpdatedPlural'   => esc_html__( '%d résultats mis à jour', 'notation-jlg' ),
                        'resultsUpdatedZero'     => esc_html__( 'Aucun résultat disponible', 'notation-jlg' ),
                    ),
                );
            }
        );

        $this->register_localization(
            'jlg-live-announcer',
            'jlgLiveAnnouncerL10n',
            function () {
                return array(
                    'dismissLabel'          => esc_html__( 'Fermer la notification', 'notation-jlg' ),
                    'hideAnnouncementLabel' => esc_html__( 'Notification masquée', 'notation-jlg' ),
                );
            }
        );

        $this->register_localization(
            'jlg-score-insights',
            'jlgScoreInsightsL10n',
            function () {
                return array(
                    'updatedSingular' => esc_html__( 'Score Insights mis à jour — %d test analysé', 'notation-jlg' ),
                    'updatedPlural'   => esc_html__( 'Score Insights mis à jour — %d tests analysés', 'notation-jlg' ),
                    'updatedZero'     => esc_html__( 'Score Insights mis à jour — aucun test disponible', 'notation-jlg' ),
                );
            }
        );
    }
}

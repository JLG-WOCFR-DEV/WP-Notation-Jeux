<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class JLG_Assets {
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
        if ( $hook_suffix !== 'toplevel_page_notation_jlg_settings' ) {
            return;
        }

        $active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'reglages';
        if ( $active_tab !== 'plateformes' ) {
            return;
        }

        wp_enqueue_script( 'jquery-ui-sortable' );

        $handle  = 'jlg-platforms-order';
        $src     = JLG_NOTATION_PLUGIN_URL . 'assets/js/jlg-platforms-order.js';
        $deps    = array( 'jquery', 'jquery-ui-sortable' );
        $version = defined( 'JLG_NOTATION_VERSION' ) ? JLG_NOTATION_VERSION : false;

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
					'successMessage'      => __( 'Merci pour votre vote !', 'notation-jlg' ),
					'genericErrorMessage' => __( 'Erreur. Veuillez réessayer.', 'notation-jlg' ),
					'alreadyVotedMessage' => __( 'Vous avez déjà voté !', 'notation-jlg' ),
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
                    'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
                    'nonce'     => wp_create_nonce( 'jlg_game_explorer' ),
                    'restUrl'   => esc_url_raw( rest_url( 'jlg/v1/game-explorer' ) ),
                    'restPath'  => 'jlg/v1/game-explorer',
                    'restNonce' => wp_create_nonce( 'wp_rest' ),
                    'strings' => array(
                        'loading'       => esc_html__( 'Chargement des jeux...', 'notation-jlg' ),
                        'noResults'     => esc_html__( 'Aucun jeu ne correspond à votre sélection.', 'notation-jlg' ),
						'reset'         => esc_html__( 'Réinitialiser les filtres', 'notation-jlg' ),
						'genericError'  => esc_html__( 'Impossible de charger les jeux pour le moment.', 'notation-jlg' ),
						'countSingular' => esc_html__( '%d jeu', 'notation-jlg' ),
						'countPlural'   => esc_html__( '%d jeux', 'notation-jlg' ),
					),
				);
			}
        );
    }
}

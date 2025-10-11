<?php
/**
 * Contrôleur de l'assistant de démarrage.
 *
 * @package JLG_Notation
 */

namespace JLG\Notation\Admin\Onboarding;

use JLG\Notation\Helpers;
use JLG\Notation\Utils\TemplateLoader;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class OnboardingController {
    private const OPTION_COMPLETED   = 'jlg_onboarding_completed';
    private const SETTINGS_OPTION    = 'notation_jlg_settings';
    private const TRANSIENT_REDIRECT = 'jlg_onboarding_redirect';
    private const FORM_ACTION        = 'jlg_onboarding_save';
    private const PAGE_SLUG          = 'jlg-notation-onboarding';

    /**
     * Plugin basename utilisé pour détecter l'activation.
     *
     * @var string
     */
    private $plugin_basename;

    /**
     * Définition des étapes affichées dans l'assistant.
     *
     * @var array[]
     */
    private $steps = array();

    public function __construct( $plugin_basename = null ) {
        $this->plugin_basename = is_string( $plugin_basename ) && $plugin_basename !== ''
            ? $plugin_basename
            : ( defined( 'JLG_NOTATION_PLUGIN_BASENAME' ) ? JLG_NOTATION_PLUGIN_BASENAME : '' );

        $this->steps = array(
            array(
                'id'          => 1,
                'title'       => __( 'Types de contenus', 'notation-jlg' ),
                'description' => __( 'Sélectionnez les contenus éligibles à la notation.', 'notation-jlg' ),
            ),
            array(
                'id'          => 2,
                'title'       => __( 'Modules recommandés', 'notation-jlg' ),
                'description' => __( 'Activez les fonctionnalités qui accompagneront vos tests.', 'notation-jlg' ),
            ),
            array(
                'id'          => 3,
                'title'       => __( 'Préréglages visuels', 'notation-jlg' ),
                'description' => __( 'Choisissez l’ambiance visuelle des widgets.', 'notation-jlg' ),
            ),
            array(
                'id'          => 4,
                'title'       => __( 'Connexion RAWG', 'notation-jlg' ),
                'description' => __( 'Ajoutez votre clé API pour synchroniser les données éditeur.', 'notation-jlg' ),
            ),
        );

        add_action( 'activated_plugin', array( $this, 'handle_plugin_activation' ), 10, 2 );
        add_action( 'admin_menu', array( $this, 'register_onboarding_page' ) );
        add_action( 'admin_init', array( $this, 'maybe_redirect_to_onboarding' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_post_' . self::FORM_ACTION, array( $this, 'handle_form_submission' ) );
    }

    public function handle_plugin_activation( $plugin, $network_wide ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
        if ( $this->plugin_basename === '' || $plugin !== $this->plugin_basename ) {
            return;
        }

        if ( ! get_option( self::OPTION_COMPLETED ) ) {
            update_option( self::OPTION_COMPLETED, 0 );
        }

        set_transient( self::TRANSIENT_REDIRECT, 1, MINUTE_IN_SECONDS );
    }

    public function register_onboarding_page() {
        add_submenu_page(
            null,
            __( 'Assistant de démarrage Notation JLG', 'notation-jlg' ),
            __( 'Assistant Notation JLG', 'notation-jlg' ),
            'manage_options',
            self::PAGE_SLUG,
            array( $this, 'render_onboarding_page' )
        );
    }

    public function maybe_redirect_to_onboarding() {
        if ( ! is_admin() ) {
            return;
        }

        if ( wp_doing_ajax() || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( get_option( self::OPTION_COMPLETED ) ) {
            return;
        }

        $current_action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';
        if ( $current_action === self::FORM_ACTION ) {
            return;
        }

        $current_page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        if ( $current_page === self::PAGE_SLUG ) {
            return;
        }

        $redirect = add_query_arg( 'page', self::PAGE_SLUG, admin_url( 'admin.php' ) );

        wp_safe_redirect( $redirect );

        if ( ! defined( 'JLG_NOTATION_TEST_ENV' ) ) {
            exit;
        }
    }

    public function enqueue_assets() {
        $current_page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        if ( $current_page !== self::PAGE_SLUG ) {
            return;
        }

        $handle = 'jlg-admin-onboarding';
        wp_register_script(
            $handle,
            JLG_NOTATION_PLUGIN_URL . 'assets/js/admin/onboarding.js',
            array(),
            JLG_NOTATION_VERSION,
            true
        );

        $modules = $this->get_available_modules();

        wp_localize_script(
            $handle,
            'jlgOnboarding',
            array(
                'stepCount' => count( $this->steps ),
                'i18n'      => array(
                    'selectPostType' => __( 'Sélectionnez au moins un type de contenu pour continuer.', 'notation-jlg' ),
                    'selectPreset'   => __( 'Choisissez un préréglage avant de continuer.', 'notation-jlg' ),
                    'missingRawgKey' => __( 'Renseignez une clé RAWG valide ou cochez l’option indiquant que vous la fournirez plus tard.', 'notation-jlg' ),
                    'moduleReminder' => __( 'Sélectionnez au moins un module pour enrichir votre expérience de test.', 'notation-jlg' ),
                ),
                'modules'   => wp_list_pluck( $modules, 'option_key' ),
            )
        );

        wp_enqueue_script( $handle );
    }

    public function render_onboarding_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Accès refusé.', 'notation-jlg' ) );
        }

        $available_post_types = $this->get_available_post_types();
        $modules              = $this->get_available_modules();
        $presets              = $this->get_visual_presets();
        $options              = Helpers::get_plugin_options();

        $selected_modules = array();
        foreach ( $modules as $module ) {
            $option_key = $module['option_key'];
            if ( ! empty( $options[ $option_key ] ) ) {
                $selected_modules[] = $option_key;
            }
        }

        $context = array(
            'page_title'           => __( 'Assistant de configuration Notation JLG', 'notation-jlg' ),
            'steps'                => $this->steps,
            'available_post_types' => $available_post_types,
            'selected_post_types'  => $options['allowed_post_types'] ?? array( 'post' ),
            'modules'              => $modules,
            'selected_modules'     => $selected_modules,
            'presets'              => $presets,
            'current_preset'       => $options['visual_preset'] ?? 'signature',
            'current_theme'        => $options['visual_theme'] ?? 'dark',
            'rawg_api_key'         => $options['rawg_api_key'] ?? '',
            'form_action'          => self::FORM_ACTION,
            'completion_message'   => isset( $_GET['completed'] ) ? __( 'Configuration sauvegardée ! Vos réglages ont été appliqués.', 'notation-jlg' ) : '',
            'errors'               => $this->consume_errors(),
        );

        echo '<div class="wrap jlg-onboarding-wrap">';
        echo '<h1>' . esc_html( $context['page_title'] ) . '</h1>';
        if ( $context['completion_message'] ) {
            printf(
                '<div class="notice notice-success"><p>%s</p></div>',
                esc_html( $context['completion_message'] )
            );
        }
        if ( ! empty( $context['errors'] ) ) {
            printf(
                '<div class="notice notice-error"><p>%s</p></div>',
                esc_html( implode( ' ', $context['errors'] ) )
            );
        }
        $this->render_progress_overview( $this->steps );
        $this->render_form( $context );
        echo '</div>';
    }

    public function handle_form_submission() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Accès refusé.', 'notation-jlg' ) );
        }

        $nonce = isset( $_POST['jlg_onboarding_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['jlg_onboarding_nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, self::FORM_ACTION ) ) {
            wp_die( esc_html__( 'Nonce de sécurité invalide.', 'notation-jlg' ) );
        }

        $allowed_post_types = isset( $_POST['allowed_post_types'] ) ? (array) wp_unslash( $_POST['allowed_post_types'] ) : array();
        $modules            = isset( $_POST['modules'] ) ? (array) wp_unslash( $_POST['modules'] ) : array();
        $preset             = isset( $_POST['visual_preset'] ) ? sanitize_key( wp_unslash( $_POST['visual_preset'] ) ) : '';
        $theme              = isset( $_POST['visual_theme'] ) ? sanitize_key( wp_unslash( $_POST['visual_theme'] ) ) : '';
        $rawg_key           = isset( $_POST['rawg_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['rawg_api_key'] ) ) : '';
        $rawg_skip          = ! empty( $_POST['rawg_skip'] );

        $sanitized_post_types = $this->sanitize_post_types( $allowed_post_types );
        if ( empty( $sanitized_post_types ) ) {
            $this->register_error( __( 'Sélectionnez au moins un type de contenu valide.', 'notation-jlg' ) );
            $this->redirect_back();
            return;
        }

        $available_modules = $this->get_available_modules();
        $module_keys       = wp_list_pluck( $available_modules, 'option_key' );
        $selected_modules  = array_values( array_intersect( $module_keys, array_map( 'sanitize_key', $modules ) ) );

        if ( empty( $selected_modules ) ) {
            $this->register_error( __( 'Choisissez au moins un module pour continuer.', 'notation-jlg' ) );
            $this->redirect_back();
            return;
        }

        $presets = array_keys( $this->get_visual_presets() );
        if ( ! in_array( $preset, $presets, true ) ) {
            $this->register_error( __( 'Préréglage inconnu, veuillez en sélectionner un parmi la liste proposée.', 'notation-jlg' ) );
            $this->redirect_back();
            return;
        }

        if ( ! in_array( $theme, array( 'dark', 'light' ), true ) ) {
            $theme = 'dark';
        }

        if ( ! $rawg_skip ) {
            $rawg_key = trim( $rawg_key );
            if ( $rawg_key === '' || strlen( $rawg_key ) < 10 ) {
                $this->register_error( __( 'La clé RAWG doit contenir au moins 10 caractères.', 'notation-jlg' ) );
                $this->redirect_back();
                return;
            }
        } else {
            $rawg_key = '';
        }

        $options                       = Helpers::get_plugin_options();
        $options['allowed_post_types'] = $sanitized_post_types;
        $options['visual_preset']      = $preset;
        $options['visual_theme']       = $theme;
        $options['rawg_api_key']       = $rawg_key;

        foreach ( $available_modules as $module ) {
            $option_key             = $module['option_key'];
            $options[ $option_key ] = in_array( $option_key, $selected_modules, true ) ? 1 : 0;
        }

        update_option( self::SETTINGS_OPTION, $options );
        Helpers::flush_plugin_options_cache();
        update_option( self::OPTION_COMPLETED, 1 );
        delete_transient( self::TRANSIENT_REDIRECT );

        $redirect = add_query_arg(
            array(
                'page'      => self::PAGE_SLUG,
                'completed' => 1,
            ),
            admin_url( 'admin.php' )
        );

        wp_safe_redirect( $redirect );

        if ( ! defined( 'JLG_NOTATION_TEST_ENV' ) ) {
            exit;
        }
    }

    private function render_progress_overview( array $steps ) {
        if ( empty( $steps ) ) {
            return;
        }

        echo '<ol class="jlg-onboarding-progress" aria-label="' . esc_attr__( 'Étapes de la configuration', 'notation-jlg' ) . '">';
        foreach ( $steps as $step ) {
            printf(
                '<li class="jlg-onboarding-progress__item" data-step="%1$d"><span class="jlg-onboarding-progress__index">%1$d</span><span class="jlg-onboarding-progress__label">%2$s</span><span class="jlg-onboarding-progress__description">%3$s</span></li>',
                (int) $step['id'],
                esc_html( $step['title'] ),
                esc_html( $step['description'] )
            );
        }
        echo '</ol>';
    }

    private function render_form( array $context ) {
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" id="jlg-onboarding-form" class="jlg-onboarding-form">';
        echo '<input type="hidden" name="action" value="' . esc_attr( self::FORM_ACTION ) . '" />';
        echo '<input type="hidden" name="current_step" id="jlg-onboarding-current-step" value="1" />';
        wp_nonce_field( self::FORM_ACTION, 'jlg_onboarding_nonce' );

        echo '<div class="jlg-onboarding-feedback" role="alert" aria-live="assertive"></div>';

        echo '<div class="jlg-onboarding-steps">';
        echo TemplateLoader::get_admin_template(
            'onboarding/step-1',
            array(
                'available_post_types' => $context['available_post_types'],
                'selected_post_types'  => $context['selected_post_types'],
            )
        );
        echo TemplateLoader::get_admin_template(
            'onboarding/step-2',
            array(
                'modules'          => $context['modules'],
                'selected_modules' => $context['selected_modules'],
            )
        );
        echo TemplateLoader::get_admin_template(
            'onboarding/step-3',
            array(
                'presets'        => $context['presets'],
                'current_preset' => $context['current_preset'],
                'current_theme'  => $context['current_theme'],
            )
        );
        echo TemplateLoader::get_admin_template(
            'onboarding/step-4',
            array(
                'rawg_api_key' => $context['rawg_api_key'],
            )
        );
        echo '</div>';

        echo '<div class="jlg-onboarding-navigation">';
        echo '<button type="button" class="button button-secondary jlg-onboarding-prev" aria-label="' . esc_attr__( 'Étape précédente', 'notation-jlg' ) . '">' . esc_html__( 'Précédent', 'notation-jlg' ) . '</button>';
        echo '<button type="button" class="button button-primary jlg-onboarding-next" aria-label="' . esc_attr__( 'Étape suivante', 'notation-jlg' ) . '">' . esc_html__( 'Continuer', 'notation-jlg' ) . '</button>';
        echo '<button type="submit" class="button button-primary jlg-onboarding-submit">' . esc_html__( 'Terminer la configuration', 'notation-jlg' ) . '</button>';
        echo '</div>';

        echo '</form>';
        $this->render_inline_styles();
    }

    private function render_inline_styles() {
        ?>
        <style>
            .jlg-onboarding-wrap{max-width:960px;}
            .jlg-onboarding-progress{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin:24px 0;padding:0;list-style:none;counter-reset:jlg-step-counter;}
            .jlg-onboarding-progress__item{background:#fff;border:1px solid #d4d4d8;border-radius:8px;padding:16px;display:flex;flex-direction:column;gap:8px;position:relative;}
            .jlg-onboarding-progress__index{font-size:20px;font-weight:600;color:#1f2937;}
            .jlg-onboarding-progress__label{font-weight:600;color:#111827;}
            .jlg-onboarding-progress__description{color:#4b5563;font-size:13px;}
            .jlg-onboarding-form{background:#fff;border:1px solid #d4d4d8;border-radius:8px;padding:24px;box-shadow:0 1px 2px rgb(15 23 42 / 0.08);}
            .jlg-onboarding-step{display:none;}
            .jlg-onboarding-step.is-active{display:block;}
            .jlg-onboarding-fieldset{margin-bottom:24px;}
            .jlg-onboarding-fieldset legend{font-weight:600;margin-bottom:16px;}
            .jlg-onboarding-options{display:grid;gap:12px;}
            .jlg-onboarding-option{border:1px solid #e5e7eb;border-radius:6px;padding:16px;display:flex;align-items:flex-start;gap:12px;background:#f9fafb;}
            .jlg-onboarding-option input[type="checkbox"],.jlg-onboarding-option input[type="radio"]{margin-top:4px;}
            .jlg-onboarding-navigation{display:flex;gap:12px;justify-content:flex-end;margin-top:24px;}
            .jlg-onboarding-submit{display:none;}
            .jlg-onboarding-feedback{margin-bottom:16px;font-weight:600;color:#b91c1c;}
            .jlg-onboarding-step small{display:block;color:#6b7280;margin-top:4px;}
        </style>
        <?php
    }

    private function get_available_post_types() {
        $post_types = get_post_types( array( 'public' => true ), 'objects' );

        $formatted = array();
        foreach ( $post_types as $slug => $post_type ) {
            $label       = isset( $post_type->labels->name ) ? $post_type->labels->name : $slug;
            $formatted[] = array(
                'slug'  => $slug,
                'label' => $label,
            );
        }

        return $formatted;
    }

    private function get_available_modules() {
        return array(
            array(
                'option_key'  => 'review_status_enabled',
                'label'       => __( 'Suivi des statuts de tests', 'notation-jlg' ),
                'description' => __( 'Automatise le suivi rédactionnel (à relire, publié, à mettre à jour).', 'notation-jlg' ),
            ),
            array(
                'option_key'  => 'verdict_module_enabled',
                'label'       => __( 'Verdict & points clés', 'notation-jlg' ),
                'description' => __( 'Affiche un résumé éditorial et les plus/moins de chaque test.', 'notation-jlg' ),
            ),
            array(
                'option_key'  => 'related_guides_enabled',
                'label'       => __( 'Guides associés', 'notation-jlg' ),
                'description' => __( 'Propose automatiquement des astuces ou guides liés au jeu.', 'notation-jlg' ),
            ),
            array(
                'option_key'  => 'deals_enabled',
                'label'       => __( 'Bons plans partenaires', 'notation-jlg' ),
                'description' => __( 'Affiche une sélection de promotions issues de vos affiliés.', 'notation-jlg' ),
            ),
            array(
                'option_key'  => 'user_rating_enabled',
                'label'       => __( 'Notes lecteurs', 'notation-jlg' ),
                'description' => __( 'Autorise la communauté à partager sa propre note et ses retours.', 'notation-jlg' ),
            ),
        );
    }

    private function get_visual_presets() {
        return array(
            'signature' => array(
                'label'       => __( 'Signature (contrasté)', 'notation-jlg' ),
                'description' => __( 'Palette sombre emblématique avec animations dynamiques.', 'notation-jlg' ),
            ),
            'minimal'   => array(
                'label'       => __( 'Minimal (clair)', 'notation-jlg' ),
                'description' => __( 'Interface lumineuse adaptée aux chartes éditoriales sobres.', 'notation-jlg' ),
            ),
            'contrast'  => array(
                'label'       => __( 'Contraste élevé', 'notation-jlg' ),
                'description' => __( 'Accentue la lisibilité pour les interfaces très denses.', 'notation-jlg' ),
            ),
        );
    }

    private function sanitize_post_types( array $post_types ) {
        $sanitized = array();

        foreach ( $post_types as $post_type ) {
            $slug = sanitize_key( $post_type );
            if ( $slug === '' ) {
                continue;
            }

            if ( ! post_type_exists( $slug ) ) {
                continue;
            }

            $sanitized[] = $slug;
        }

        return array_values( array_unique( $sanitized ) );
    }

    private function register_error( $message ) {
        if ( ! isset( $GLOBALS['jlg_onboarding_errors'] ) ) {
            $GLOBALS['jlg_onboarding_errors'] = array();
        }

        $GLOBALS['jlg_onboarding_errors'][] = (string) $message;
    }

    private function consume_errors() {
        if ( empty( $GLOBALS['jlg_onboarding_errors'] ) || ! is_array( $GLOBALS['jlg_onboarding_errors'] ) ) {
            return array();
        }

        $errors = $GLOBALS['jlg_onboarding_errors'];
        unset( $GLOBALS['jlg_onboarding_errors'] );

        return array_map( 'sanitize_text_field', $errors );
    }

    private function redirect_back() {
        $redirect = add_query_arg( 'page', self::PAGE_SLUG, admin_url( 'admin.php' ) );
        wp_safe_redirect( $redirect );

        if ( ! defined( 'JLG_NOTATION_TEST_ENV' ) ) {
            exit;
        }
    }
}

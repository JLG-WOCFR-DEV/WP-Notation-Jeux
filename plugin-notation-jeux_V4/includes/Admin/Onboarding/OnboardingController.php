<?php
/**
 * Contrôleur de l'assistant de démarrage.
 *
 * @package JLG_Notation
 */

namespace JLG\Notation\Admin\Onboarding;

use JLG\Notation\Helpers;
use JLG\Notation\Telemetry;
use JLG\Notation\Utils\TemplateLoader;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class OnboardingController {
    private const OPTION_COMPLETED         = 'jlg_onboarding_completed';
    private const SETTINGS_OPTION          = 'notation_jlg_settings';
    private const TRANSIENT_REDIRECT       = 'jlg_onboarding_redirect';
    private const FORM_ACTION              = 'jlg_onboarding_save';
    private const PAGE_SLUG                = 'jlg-notation-onboarding';
    private const ERRORS_TRANSIENT_PREFIX  = 'jlg_onboarding_errors_';
    private const FORM_STATE_TRANSIENT_KEY = 'jlg_onboarding_state_';
    private const TRACKING_ACTION          = 'jlg_onboarding_track';

    /**
     * Plugin basename utilisé pour détecter l'activation.
     *
     * @var string
     */
    private $plugin_basename;

    /**
     * Définition des étapes affichées dans l'assistant.
     *
     * @var array[]|null
     */
    private $steps = null;

    public function __construct( $plugin_basename = null ) {
        $this->plugin_basename = is_string( $plugin_basename ) && $plugin_basename !== ''
            ? $plugin_basename
            : ( defined( 'JLG_NOTATION_PLUGIN_BASENAME' ) ? JLG_NOTATION_PLUGIN_BASENAME : '' );

        add_action( 'activated_plugin', array( $this, 'handle_plugin_activation' ), 10, 2 );
        add_action( 'admin_menu', array( $this, 'register_onboarding_page' ) );
        add_action( 'admin_init', array( $this, 'maybe_redirect_to_onboarding' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_post_' . self::FORM_ACTION, array( $this, 'handle_form_submission' ) );
        add_action( 'wp_ajax_' . self::TRACKING_ACTION, array( $this, 'handle_tracking_event' ) );
    }

    public function handle_plugin_activation( $plugin, $network_wide ) {
        unset( $network_wide );

        if ( $this->plugin_basename === '' || $plugin !== $this->plugin_basename ) {
            return;
        }

        if ( ! get_option( self::OPTION_COMPLETED ) ) {
            update_option( self::OPTION_COMPLETED, 0 );
        }

        set_transient( self::TRANSIENT_REDIRECT, 1, MINUTE_IN_SECONDS );
    }

    public function register_onboarding_page() {
        $hook_suffix = add_submenu_page(
            'notation_jlg_settings',
            __( 'Assistant de démarrage Notation JLG', 'notation-jlg' ),
            __( 'Assistant Notation JLG', 'notation-jlg' ),
            'manage_options',
            self::PAGE_SLUG,
            array( $this, 'render_onboarding_page' )
        );

        if ( function_exists( 'remove_submenu_page' ) ) {
            $is_completed = (int) get_option( self::OPTION_COMPLETED );

            if ( 1 === $is_completed ) {
                $current_page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

                if ( self::PAGE_SLUG !== $current_page ) {
                    remove_submenu_page( 'notation_jlg_settings', self::PAGE_SLUG );
                }
            }
        }

        return $hook_suffix;
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

        $should_redirect   = false;
        $redirect_flag     = get_transient( self::TRANSIENT_REDIRECT );
        $plugin_page_slugs = array( self::SETTINGS_OPTION );

        if ( $redirect_flag ) {
            $should_redirect = true;
        }

        if ( ! $should_redirect && $current_page && in_array( $current_page, $plugin_page_slugs, true ) ) {
            $should_redirect = true;
        }

        if ( ! $should_redirect ) {
            return;
        }

        $redirect = add_query_arg( 'page', self::PAGE_SLUG, admin_url( 'admin.php' ) );

        if ( $redirect_flag ) {
            delete_transient( self::TRANSIENT_REDIRECT );
        }

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

        $steps = $this->get_steps();

        wp_localize_script(
            $handle,
            'jlgOnboarding',
            array(
                'stepCount' => count( $steps ),
                'i18n'      => array(
                    'selectPostType' => __( 'Sélectionnez au moins un type de contenu pour continuer.', 'notation-jlg' ),
                    'selectPreset'   => __( 'Choisissez un préréglage avant de continuer.', 'notation-jlg' ),
                    'missingRawgKey' => __( 'Renseignez une clé RAWG valide ou cochez l’option indiquant que vous la fournirez plus tard.', 'notation-jlg' ),
                    'moduleReminder' => __( 'Sélectionnez au moins un module pour enrichir votre expérience de test.', 'notation-jlg' ),
                ),
                'modules'   => wp_list_pluck( $modules, 'option_key' ),
                'telemetry' => array(
                    'endpoint' => admin_url( 'admin-ajax.php' ),
                    'action'   => self::TRACKING_ACTION,
                    'nonce'    => wp_create_nonce( self::TRACKING_ACTION ),
                    'debug'    => defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG,
                ),
            )
        );

        wp_enqueue_script( $handle );
    }

    public function render_onboarding_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Accès refusé.', 'notation-jlg' ) );
        }

        $steps                = $this->get_steps();
        $available_post_types = $this->get_available_post_types();
        $modules              = $this->get_available_modules();
        $presets              = $this->get_visual_presets();
        $options              = Helpers::get_plugin_options();
        $form_state           = $this->consume_form_state();

        $selected_post_types = $options['allowed_post_types'] ?? array( 'post' );
        if ( array_key_exists( 'allowed_post_types', $form_state ) ) {
            $selected_post_types = $form_state['allowed_post_types'];
        }

        $selected_modules = array();
        foreach ( $modules as $module ) {
            $option_key = $module['option_key'];
            if ( ! empty( $options[ $option_key ] ) ) {
                $selected_modules[] = $option_key;
            }
        }

        if ( array_key_exists( 'modules', $form_state ) ) {
            $selected_modules = $form_state['modules'];
        }

        $context = array(
            'page_title'           => __( 'Assistant de configuration Notation JLG', 'notation-jlg' ),
            'steps'                => $steps,
            'available_post_types' => $available_post_types,
            'selected_post_types'  => $selected_post_types,
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

        if ( array_key_exists( 'visual_preset', $form_state ) && $form_state['visual_preset'] !== '' ) {
            $context['current_preset'] = $form_state['visual_preset'];
        }

        if ( array_key_exists( 'visual_theme', $form_state ) ) {
            $context['current_theme'] = $form_state['visual_theme'];
        }

        if ( array_key_exists( 'rawg_api_key', $form_state ) ) {
            $context['rawg_api_key'] = $form_state['rawg_api_key'];
        }

        $context['rawg_skip']    = ! empty( $form_state['rawg_skip'] );
        $context['current_step'] = $form_state['current_step'] ?? 1;

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
        $this->render_progress_overview( $steps );
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
        $available_modules    = $this->get_available_modules();
        $module_keys          = wp_list_pluck( $available_modules, 'option_key' );
        $selected_modules     = array_values( array_intersect( $module_keys, array_map( 'sanitize_key', $modules ) ) );
        $presets              = array_keys( $this->get_visual_presets() );
        $theme                = in_array( $theme, array( 'dark', 'light' ), true ) ? $theme : 'dark';
        $rawg_key             = trim( $rawg_key );

        $form_state = array(
            'allowed_post_types' => $sanitized_post_types,
            'modules'            => $selected_modules,
            'visual_preset'      => $preset,
            'visual_theme'       => $theme,
            'rawg_api_key'       => $rawg_skip ? '' : $rawg_key,
            'rawg_skip'          => $rawg_skip ? 1 : 0,
        );

        if ( empty( $sanitized_post_types ) ) {
            $this->register_error( __( 'Sélectionnez au moins un type de contenu valide.', 'notation-jlg' ) );
            $this->redirect_back( $form_state, 1 );
            return;
        }

        if ( empty( $selected_modules ) ) {
            $this->register_error( __( 'Choisissez au moins un module pour continuer.', 'notation-jlg' ) );
            $this->redirect_back( $form_state, 2 );
            return;
        }

        if ( ! in_array( $preset, $presets, true ) ) {
            $this->register_error( __( 'Préréglage inconnu, veuillez en sélectionner un parmi la liste proposée.', 'notation-jlg' ) );
            $this->redirect_back( $form_state, 3 );
            return;
        }

        if ( ! $rawg_skip && ( $rawg_key === '' || strlen( $rawg_key ) < 10 ) ) {
            $this->register_error( __( 'La clé RAWG doit contenir au moins 10 caractères.', 'notation-jlg' ) );
            $this->redirect_back( $form_state, 4 );
            return;
        }

        if ( $rawg_skip ) {
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
        $this->clear_form_state();
        $this->clear_errors();

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

    public function handle_tracking_event() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Accès refusé.', 'notation-jlg' ) ), 403 );
        }

        check_ajax_referer( self::TRACKING_ACTION, 'nonce' );

        $event = isset( $_POST['event'] ) ? sanitize_key( wp_unslash( $_POST['event'] ) ) : '';
        if ( $event === '' ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Événement invalide.', 'notation-jlg' ) ), 400 );
        }

        $payload       = array();
        $raw_payload   = isset( $_POST['payload'] ) ? wp_unslash( $_POST['payload'] ) : '';
        $decoded_entry = json_decode( is_string( $raw_payload ) ? $raw_payload : '', true );
        if ( is_array( $decoded_entry ) ) {
            $payload = $decoded_entry;
        }

        $status   = isset( $payload['status'] ) && $payload['status'] === 'error' ? 'error' : 'success';
        $duration = isset( $payload['duration'] ) ? max( 0, (float) $payload['duration'] ) : 0.0;
        $step     = isset( $payload['step'] ) ? max( 0, (int) $payload['step'] ) : 0;

        $feedback_code    = isset( $payload['feedback_code'] ) ? sanitize_key( $payload['feedback_code'] ) : '';
        $feedback_message = isset( $payload['feedback_message'] )
            ? wp_strip_all_tags( (string) $payload['feedback_message'] )
            : '';

        $context = array(
            'event' => $event,
        );

        if ( $step > 0 ) {
            $context['step'] = $step;
        }

        if ( $feedback_code !== '' ) {
            $context['feedback_code'] = $feedback_code;
        }

        if ( $feedback_message !== '' ) {
            $context['feedback_message'] = $feedback_message;
        }

        $direction = isset( $payload['direction'] ) ? sanitize_key( $payload['direction'] ) : '';
        if ( $direction !== '' ) {
            $context['direction'] = $direction;
        }

        $reason = isset( $payload['reason'] ) ? sanitize_key( $payload['reason'] ) : '';
        if ( $reason !== '' ) {
            $context['reason'] = $reason;
        }

        if ( isset( $payload['metadata'] ) && is_array( $payload['metadata'] ) ) {
            foreach ( $payload['metadata'] as $meta_key => $meta_value ) {
                $meta_key = sanitize_key( (string) $meta_key );

                if ( $meta_key === '' ) {
                    continue;
                }

                if ( is_scalar( $meta_value ) ) {
                    $context[ 'meta_' . $meta_key ] = is_string( $meta_value )
                        ? wp_strip_all_tags( (string) $meta_value )
                        : $meta_value;
                }
            }
        }

        Telemetry::record_event(
            'onboarding',
            array(
                'duration' => $duration,
                'status'   => $status,
                'message'  => $status === 'error' ? $feedback_message : '',
                'context'  => $context,
            )
        );

        wp_send_json_success(
            array(
                'recorded' => true,
            )
        );
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
        $current_step = isset( $context['current_step'] ) ? (int) $context['current_step'] : 1;
        $current_step = $this->normalize_step( $current_step );

        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" id="jlg-onboarding-form" class="jlg-onboarding-form">';
        echo '<input type="hidden" name="action" value="' . esc_attr( self::FORM_ACTION ) . '" />';
        echo '<input type="hidden" name="current_step" id="jlg-onboarding-current-step" value="' . esc_attr( $current_step ) . '" />';
        wp_nonce_field( self::FORM_ACTION, 'jlg_onboarding_nonce' );

        echo '<div class="jlg-onboarding-feedback" role="alert" aria-live="assertive"></div>';

        echo '<div class="jlg-onboarding-steps">';
        echo TemplateLoader::get_admin_template(
            'onboarding/step-1',
            array(
                'available_post_types' => $context['available_post_types'],
                'selected_post_types'  => $context['selected_post_types'],
                'is_active'            => $current_step === 1,
            )
        );
        echo TemplateLoader::get_admin_template(
            'onboarding/step-2',
            array(
                'modules'          => $context['modules'],
                'selected_modules' => $context['selected_modules'],
                'is_active'        => $current_step === 2,
            )
        );
        echo TemplateLoader::get_admin_template(
            'onboarding/step-3',
            array(
                'presets'        => $context['presets'],
                'current_preset' => $context['current_preset'],
                'current_theme'  => $context['current_theme'],
                'is_active'      => $current_step === 3,
            )
        );
        echo TemplateLoader::get_admin_template(
            'onboarding/step-4',
            array(
                'rawg_api_key' => $context['rawg_api_key'],
                'rawg_skip'    => $context['rawg_skip'],
                'is_active'    => $current_step === 4,
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
        $message = sanitize_text_field( (string) $message );
        if ( $message === '' ) {
            return;
        }

        $key    = $this->get_errors_transient_key();
        $errors = get_transient( $key );

        if ( ! is_array( $errors ) ) {
            $errors = array();
        }

        $errors[] = $message;

        set_transient( $key, $errors, MINUTE_IN_SECONDS );
    }

    private function consume_errors() {
        $key    = $this->get_errors_transient_key();
        $errors = get_transient( $key );
        delete_transient( $key );

        if ( ! is_array( $errors ) ) {
            return array();
        }

        $sanitized = array();

        foreach ( $errors as $error ) {
            $message = sanitize_text_field( (string) $error );
            if ( $message === '' ) {
                continue;
            }

            $sanitized[] = $message;
        }

        return $sanitized;
    }

    private function redirect_back( array $state = array(), $step = null ) {
        if ( $step !== null ) {
            $state['current_step'] = $this->normalize_step( (int) $step );
        }

        if ( ! empty( $state ) ) {
            $this->store_form_state( $state );
        }

        $redirect = add_query_arg( 'page', self::PAGE_SLUG, admin_url( 'admin.php' ) );
        wp_safe_redirect( $redirect );

        if ( ! defined( 'JLG_NOTATION_TEST_ENV' ) ) {
            exit;
        }
    }

    private function clear_form_state() {
        delete_transient( $this->get_form_state_transient_key() );
    }

    private function clear_errors() {
        delete_transient( $this->get_errors_transient_key() );
    }

    private function store_form_state( array $state ) {
        $normalized = array(
            'allowed_post_types' => $this->sanitize_post_types( $state['allowed_post_types'] ?? array() ),
            'modules'            => $this->sanitize_modules( $state['modules'] ?? array() ),
            'visual_preset'      => $this->sanitize_preset( $state['visual_preset'] ?? '' ),
            'visual_theme'       => in_array( $state['visual_theme'] ?? 'dark', array( 'dark', 'light' ), true ) ? $state['visual_theme'] : 'dark',
            'rawg_api_key'       => sanitize_text_field( $state['rawg_api_key'] ?? '' ),
            'rawg_skip'          => ! empty( $state['rawg_skip'] ) ? 1 : 0,
            'current_step'       => $this->normalize_step( $state['current_step'] ?? 1 ),
        );

        set_transient( $this->get_form_state_transient_key(), $normalized, MINUTE_IN_SECONDS );
    }

    private function consume_form_state() {
        $key   = $this->get_form_state_transient_key();
        $state = get_transient( $key );
        delete_transient( $key );

        if ( ! is_array( $state ) ) {
            return array();
        }

        return array(
            'allowed_post_types' => $this->sanitize_post_types( $state['allowed_post_types'] ?? array() ),
            'modules'            => $this->sanitize_modules( $state['modules'] ?? array() ),
            'visual_preset'      => $this->sanitize_preset( $state['visual_preset'] ?? '' ),
            'visual_theme'       => in_array( $state['visual_theme'] ?? 'dark', array( 'dark', 'light' ), true ) ? $state['visual_theme'] : 'dark',
            'rawg_api_key'       => sanitize_text_field( $state['rawg_api_key'] ?? '' ),
            'rawg_skip'          => ! empty( $state['rawg_skip'] ) ? 1 : 0,
            'current_step'       => $this->normalize_step( $state['current_step'] ?? 1 ),
        );
    }

    private function sanitize_modules( array $modules ) {
        $available   = $this->get_available_modules();
        $module_keys = wp_list_pluck( $available, 'option_key' );

        return array_values( array_intersect( $module_keys, array_map( 'sanitize_key', $modules ) ) );
    }

    private function sanitize_preset( $preset ) {
        $preset  = sanitize_key( $preset );
        $presets = array_keys( $this->get_visual_presets() );

        if ( ! in_array( $preset, $presets, true ) ) {
            return '';
        }

        return $preset;
    }

    private function normalize_step( $step ) {
        $step = (int) $step;

        if ( $step < 1 ) {
            return 1;
        }

        $max = count( $this->get_steps() );

        if ( $step > $max ) {
            return $max;
        }

        return $step;
    }

    private function get_steps() {
        if ( $this->steps === null ) {
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
        }

        return $this->steps;
    }

    private function get_errors_transient_key() {
        $user_id = get_current_user_id();

        return self::ERRORS_TRANSIENT_PREFIX . ( $user_id > 0 ? $user_id : 0 );
    }

    private function get_form_state_transient_key() {
        $user_id = get_current_user_id();

        return self::FORM_STATE_TRANSIENT_KEY . ( $user_id > 0 ? $user_id : 0 );
    }
}

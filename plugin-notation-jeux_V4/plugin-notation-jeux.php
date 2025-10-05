<?php
/**
 * Plugin Name: Notation - JLG (Version 5.0)
 * Plugin URI: https://votresite.com/
 * Description: Système de notation complet et personnalisable pour les tests de jeux vidéo - Version refactorisée et optimisée.
 * Version: 5.0
 * Author: Jérôme Le Gousse
 * License: GPL v2 or later
 * Text Domain: notation-jlg
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

$autoload_path = __DIR__ . '/vendor/autoload.php';
if ( file_exists( $autoload_path ) ) {
        require $autoload_path;
}

require_once __DIR__ . '/functions.php';

if ( ! defined( 'JLG_NOTATION_FALLBACK_AUTOLOADER' ) ) {
    define( 'JLG_NOTATION_FALLBACK_AUTOLOADER', true );

    spl_autoload_register(
        static function ( $class ) {
            $prefixes = array(
                'JLG\\Notation\\Admin\\'      => 'includes/Admin/',
                'JLG\\Notation\\Utils\\'      => 'includes/Utils/',
                'JLG\\Notation\\Shortcodes\\' => 'includes/Shortcodes/',
                'JLG\\Notation\\'              => 'includes/',
            );

            foreach ( $prefixes as $prefix => $directory ) {
                if ( strpos( $class, $prefix ) !== 0 ) {
                    continue;
                }

                $relative_class = substr( $class, strlen( $prefix ) );
                if ( $relative_class === false ) {
                    return;
                }

                $relative_path = str_replace( '\\', '/', $relative_class );
                $file          = __DIR__ . '/' . $directory . $relative_path . '.php';

                if ( file_exists( $file ) ) {
                    require_once $file;
                }

                return;
            }
        },
        true,
        true
    );
}

// Constantes
define( 'JLG_NOTATION_VERSION', '5.0' );
define( 'JLG_NOTATION_PLUGIN_FILE', __FILE__ );
define( 'JLG_NOTATION_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'JLG_NOTATION_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'JLG_NOTATION_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

add_action(
    'plugins_loaded',
    function () {
		load_plugin_textdomain( 'notation-jlg', false, dirname( JLG_NOTATION_PLUGIN_BASENAME ) . '/languages' );
	}
);

// Vérifications de compatibilité
if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
    add_action(
        'admin_notices',
        function () {
			echo '<div class="notice notice-error"><p><strong>JLG Notation:</strong> PHP 7.4+ requis. Version actuelle: ' . esc_html( PHP_VERSION ) . '</p></div>';
		}
    );
    return;
}

if ( version_compare( get_bloginfo( 'version' ), '5.0', '<' ) ) {
    add_action(
        'admin_notices',
        function () {
			echo '<div class="notice notice-error"><p><strong>JLG Notation:</strong> WordPress 5.0+ requis. Version actuelle: ' . esc_html( get_bloginfo( 'version' ) ) . '</p></div>';
		}
    );
    return;
}

/**
 * Classe principale refactorisée
 */
final class JLG_Plugin_De_Notation_Main {
    private static $instance                = null;
    private $admin                          = null;
    private $assets                         = null;
    private $frontend                       = null;
    private $blocks                         = null;
    private const MIGRATION_BATCH_SIZE      = 50;
    private const MIGRATION_SCAN_BATCH_SIZE = 200;
    private const SCORE_SCALE_MIGRATION_BATCH_SIZE = 40;

    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        if ( class_exists( \JLG\Notation\Helpers::class ) ) {
            \JLG\Notation\Helpers::migrate_legacy_rating_configuration();
        }
        $this->init_components();
        add_action( 'jlg_process_v5_migration', array( $this, 'process_migration_batch' ) );
        add_action( 'jlg_queue_average_rebuild', array( $this, 'queue_additional_posts_for_migration' ) );
        add_action( \JLG\Notation\Helpers::SCORE_SCALE_EVENT_HOOK, array( $this, 'process_score_scale_migration_batch' ) );
        add_action( 'init', array( $this, 'ensure_migration_schedule' ) );
        register_activation_hook( __FILE__, array( $this, 'on_activation' ) );
        register_deactivation_hook( __FILE__, array( $this, 'on_deactivation' ) );
    }

    private function load_dependencies() {
        $helpers_class = \JLG\Notation\Helpers::class;

        add_action( 'update_option_notation_jlg_settings', array( $helpers_class, 'flush_plugin_options_cache' ), 10, 0 );
        add_action( 'add_option_notation_jlg_settings', array( $helpers_class, 'flush_plugin_options_cache' ), 10, 0 );
        add_action( 'delete_option_notation_jlg_settings', array( $helpers_class, 'flush_plugin_options_cache' ), 10, 0 );
        add_action( 'added_post_meta', array( $helpers_class, 'maybe_handle_rating_meta_change' ), 10, 4 );
        add_action( 'updated_post_meta', array( $helpers_class, 'maybe_handle_rating_meta_change' ), 10, 4 );
        add_action( 'deleted_post_meta', array( $helpers_class, 'maybe_handle_rating_meta_change' ), 10, 4 );
        add_action( 'transition_post_status', array( $helpers_class, 'maybe_clear_rated_post_ids_cache_for_status_change' ), 20, 3 );

        $game_explorer_class = \JLG\Notation\Shortcodes\GameExplorer::class;

        if ( class_exists( $game_explorer_class ) ) {
            add_action( 'added_post_meta', array( $game_explorer_class, 'maybe_clear_filters_snapshot_for_meta' ), 20, 4 );
            add_action( 'updated_post_meta', array( $game_explorer_class, 'maybe_clear_filters_snapshot_for_meta' ), 20, 4 );
            add_action( 'deleted_post_meta', array( $game_explorer_class, 'maybe_clear_filters_snapshot_for_meta' ), 20, 4 );
            add_action( 'save_post', array( $game_explorer_class, 'maybe_clear_filters_snapshot_for_post' ), 20, 3 );
            add_action( 'transition_post_status', array( $game_explorer_class, 'maybe_clear_filters_snapshot_for_status_change' ), 20, 3 );
            add_action( 'set_object_terms', array( $game_explorer_class, 'maybe_clear_filters_snapshot_for_terms' ), 20, 4 );
            add_action( 'created_term', array( $game_explorer_class, 'maybe_clear_filters_snapshot_for_term_event' ), 20, 3 );
            add_action( 'edited_term', array( $game_explorer_class, 'maybe_clear_filters_snapshot_for_term_event' ), 20, 3 );
            add_action( 'delete_term', array( $game_explorer_class, 'maybe_clear_filters_snapshot_for_term_event' ), 20, 4 );
        }
    }

    private function init_components() {
        // Assets communs
        if ( class_exists( \JLG\Notation\Assets::class ) ) {
            $this->assets = \JLG\Notation\Assets::get_instance();
        }

        // Frontend
        $doing_ajax = function_exists( 'wp_doing_ajax' ) ? wp_doing_ajax() : ( defined( 'DOING_AJAX' ) && DOING_AJAX );
        if ( ( ! is_admin() || $doing_ajax ) && class_exists( \JLG\Notation\Frontend::class ) ) {
            $this->frontend = new \JLG\Notation\Frontend();
        }

        // Widget
        if ( class_exists( \JLG\Notation\LatestReviewsWidget::class ) ) {
            add_action(
                'widgets_init',
                function () {
                                        register_widget( \JLG\Notation\LatestReviewsWidget::class );
                                }
            );
        }

        // Blocks
        if ( class_exists( \JLG\Notation\Blocks::class ) ) {
            $this->blocks = new \JLG\Notation\Blocks();
        }

        // Admin
        if ( is_admin() && class_exists( \JLG\Notation\Admin\Core::class ) ) {
            $this->admin = \JLG\Notation\Admin\Core::get_instance();
        }
    }

    public function on_activation() {
        // Migration automatique depuis v4
        $current_version = get_option( 'jlg_notation_version', '4.0' );

        if ( class_exists( \JLG\Notation\Helpers::class ) ) {
            \JLG\Notation\Helpers::migrate_legacy_rating_configuration();
        }

        if ( version_compare( $current_version, '5.0', '<' ) ) {
            $this->queue_migration_from_v4();
        }

        // Options par défaut
        if ( ! get_option( 'notation_jlg_settings' ) ) {
            add_option( 'notation_jlg_settings', \JLG\Notation\Helpers::get_default_settings() );
        }

        update_option( 'jlg_notation_version', JLG_NOTATION_VERSION );
        flush_rewrite_rules();
    }

    public function on_deactivation() {
        if ( function_exists( 'wp_clear_scheduled_hook' ) ) {
            wp_clear_scheduled_hook( 'jlg_process_v5_migration' );
            wp_clear_scheduled_hook( \JLG\Notation\Helpers::SCORE_SCALE_EVENT_HOOK );
        }

        delete_option( 'jlg_migration_v5_queue' );
        delete_option( 'jlg_migration_v5_scan_state' );
        delete_option( 'jlg_migration_v5_completed' );
        delete_option( \JLG\Notation\Helpers::SCORE_SCALE_QUEUE_OPTION );
        delete_option( \JLG\Notation\Helpers::SCORE_SCALE_MIGRATION_OPTION );
    }

    private function queue_migration_from_v4() {
        $this->store_migration_queue( array() );
        $this->store_migration_scan_state(
            array(
				'last_post_id' => 0,
				'complete'     => false,
            )
        );

        $this->schedule_next_migration_event();
    }

    public function ensure_migration_schedule() {
        if ( ! function_exists( 'wp_next_scheduled' ) || ! function_exists( 'wp_schedule_single_event' ) ) {
            return;
        }

        $queue      = $this->get_migration_queue();
        $scan_state = $this->get_migration_scan_state();

        if ( empty( $queue ) && ! empty( $scan_state['complete'] ) ) {
            return;
        }

        if ( ! wp_next_scheduled( 'jlg_process_v5_migration' ) ) {
            $this->schedule_next_migration_event();
        }
    }

    public function process_migration_batch() {
        $queue = $this->get_migration_queue();

        if ( empty( $queue ) ) {
            $queue = $this->populate_migration_queue_batch();
        }

        if ( empty( $queue ) ) {
            if ( $this->is_migration_scan_complete() ) {
                $this->finalize_migration();
            } else {
                $this->schedule_next_migration_event();
            }

            return;
        }

        $batch = array_splice( $queue, 0, self::MIGRATION_BATCH_SIZE );

        foreach ( $batch as $post_id ) {
            $post_id = intval( $post_id );

            if ( $post_id > 0 ) {
                \JLG\Notation\Helpers::get_resolved_average_score( $post_id );
            }
        }

        $this->store_migration_queue( $queue );

        if ( ! empty( $queue ) || ! $this->is_migration_scan_complete() ) {
            $this->schedule_next_migration_event();
        } else {
            $this->finalize_migration();
        }
    }

    private function schedule_next_migration_event() {
        if ( ! function_exists( 'wp_schedule_single_event' ) || ! function_exists( 'wp_next_scheduled' ) ) {
            return;
        }

        if ( wp_next_scheduled( 'jlg_process_v5_migration' ) ) {
            return;
        }

        $delay = defined( 'MINUTE_IN_SECONDS' ) ? MINUTE_IN_SECONDS : 60;
        wp_schedule_single_event( time() + $delay, 'jlg_process_v5_migration' );
    }

    private function populate_migration_queue_batch() {
        $queue      = $this->get_migration_queue();
        $scan_state = $this->get_migration_scan_state();

        if ( ! empty( $scan_state['complete'] ) ) {
            return $queue;
        }

        $batch_size = (int) apply_filters( 'jlg_migration_v5_scan_batch_size', self::MIGRATION_SCAN_BATCH_SIZE );
        if ( $batch_size <= 0 ) {
            $batch_size = self::MIGRATION_SCAN_BATCH_SIZE;
        }

        $last_post_id = isset( $scan_state['last_post_id'] ) ? (int) $scan_state['last_post_id'] : 0;
        $fetched      = \JLG\Notation\Helpers::get_rated_post_ids_batch( $last_post_id, $batch_size );

        if ( empty( $fetched ) ) {
            $scan_state['complete'] = true;
            $this->store_migration_scan_state( $scan_state );

            return $queue;
        }

        $scan_state['last_post_id'] = max( $fetched );

        if ( $batch_size > 0 && count( $fetched ) < $batch_size ) {
            $scan_state['complete'] = true;
        }

        $queue = array_values( array_unique( array_merge( $queue, $fetched ) ) );
        $this->store_migration_queue( $queue );
        $this->store_migration_scan_state( $scan_state );

        return $queue;
    }

    private function get_migration_queue() {
        $queue = get_option( 'jlg_migration_v5_queue', array() );

        if ( ! is_array( $queue ) ) {
            return array();
        }

        $queue = array_values(
            array_filter(
                array_map( 'intval', $queue ),
                static function ( $post_id ) {
					return $post_id > 0;
                }
            )
        );

        sort( $queue );

        return $queue;
    }

    private function store_migration_queue( array $queue ) {
        $queue = array_values(
            array_filter(
                array_map( 'intval', $queue ),
                static function ( $post_id ) {
					return $post_id > 0;
                }
            )
        );

        sort( $queue );

        if ( empty( $queue ) ) {
            delete_option( 'jlg_migration_v5_queue' );

            return;
        }

        update_option( 'jlg_migration_v5_queue', $queue, false );
    }

    private function get_migration_scan_state() {
        $state = get_option( 'jlg_migration_v5_scan_state', array() );

        if ( ! is_array( $state ) ) {
            $state = array();
        }

        $has_complete_flag = is_array( $state ) && array_key_exists( 'complete', $state );

        return array(
            'last_post_id' => isset( $state['last_post_id'] ) ? (int) $state['last_post_id'] : 0,
            'complete'     => $has_complete_flag ? ! empty( $state['complete'] ) : false,
        );
    }

    private function store_migration_scan_state( array $state ) {
        $normalized = array(
            'last_post_id' => isset( $state['last_post_id'] ) ? max( 0, (int) $state['last_post_id'] ) : 0,
            'complete'     => ! empty( $state['complete'] ),
        );

        update_option( 'jlg_migration_v5_scan_state', $normalized, false );
    }

    private function is_migration_scan_complete() {
        $state = $this->get_migration_scan_state();

        return ! empty( $state['complete'] );
    }

    private function finalize_migration() {
        $this->store_migration_queue( array() );
        delete_option( 'jlg_migration_v5_scan_state' );
        update_option( 'jlg_migration_v5_completed', current_time( 'mysql' ), false );
    }

    public function process_score_scale_migration_batch() {
        $migration = get_option( \JLG\Notation\Helpers::SCORE_SCALE_MIGRATION_OPTION, array() );

        if ( ! is_array( $migration ) || empty( $migration['old_max'] ) || empty( $migration['new_max'] ) ) {
            delete_option( \JLG\Notation\Helpers::SCORE_SCALE_MIGRATION_OPTION );
            delete_option( \JLG\Notation\Helpers::SCORE_SCALE_QUEUE_OPTION );
            return;
        }

        $queue = get_option( \JLG\Notation\Helpers::SCORE_SCALE_QUEUE_OPTION, array() );

        if ( ! is_array( $queue ) ) {
            $queue = array();
        }

        $queue = array_values(
            array_filter(
                array_map( 'intval', $queue ),
                static function ( $post_id ) {
                    return $post_id > 0;
                }
            )
        );

        if ( empty( $queue ) ) {
            delete_option( \JLG\Notation\Helpers::SCORE_SCALE_QUEUE_OPTION );
            delete_option( \JLG\Notation\Helpers::SCORE_SCALE_MIGRATION_OPTION );
            return;
        }

        $batch = array_splice( $queue, 0, self::SCORE_SCALE_MIGRATION_BATCH_SIZE );

        foreach ( $batch as $post_id ) {
            \JLG\Notation\Helpers::rescale_post_scores_for_scale_change(
                $post_id,
                $migration['old_max'],
                $migration['new_max']
            );
        }

        update_option( \JLG\Notation\Helpers::SCORE_SCALE_QUEUE_OPTION, $queue, false );

        if ( empty( $queue ) ) {
            delete_option( \JLG\Notation\Helpers::SCORE_SCALE_QUEUE_OPTION );
            delete_option( \JLG\Notation\Helpers::SCORE_SCALE_MIGRATION_OPTION );
            \JLG\Notation\Helpers::clear_rated_post_ids_cache();
            return;
        }

        if ( function_exists( 'wp_schedule_single_event' ) ) {
            wp_schedule_single_event( time() + 1, \JLG\Notation\Helpers::SCORE_SCALE_EVENT_HOOK );
        }
    }

    public function queue_additional_posts_for_migration( $post_ids ) {
        if ( ! is_array( $post_ids ) ) {
            $post_ids = array( $post_ids );
        }

        $post_ids = array_filter(
            array_map( 'intval', $post_ids ),
            static function ( $post_id ) {
                return $post_id > 0;
            }
        );

        if ( empty( $post_ids ) ) {
            return;
        }

        $queue         = $this->get_migration_queue();
        $updated_queue = array_values( array_unique( array_merge( $queue, $post_ids ) ) );

        if ( $updated_queue === $queue ) {
            return;
        }

        $this->store_migration_queue( $updated_queue );
        $this->schedule_next_migration_event();
    }
}

// Fonctions helper pour développeurs
function jlg_notation() {
    return JLG_Plugin_De_Notation_Main::get_instance();
}

function jlg_get_post_rating( $post_id = null ) {
    if ( ! $post_id ) {
		$post_id = get_the_ID();
    }
    return \JLG\Notation\Helpers::get_average_score_for_post( $post_id );
}

function jlg_display_post_rating( $post_id = null ) {
    $score = jlg_get_post_rating( $post_id );
    if ( $score !== null ) {
        $score_max_label = number_format_i18n( \JLG\Notation\Helpers::get_score_max() );
        echo '<span class="jlg-post-rating">' . esc_html( number_format_i18n( $score, 1 ) ) . ' ';
        printf(
            /* translators: %s: Maximum possible rating value. */
            esc_html__( '/%s', 'notation-jlg' ),
            esc_html( $score_max_label )
        );
        echo '</span>';
    }
}

// Initialisation
JLG_Plugin_De_Notation_Main::get_instance();

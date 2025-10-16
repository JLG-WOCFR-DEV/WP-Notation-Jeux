<?php
/**
 * Gestion des réglages du plugin
 *
 * @package JLG_Notation
 * @version 5.0
 */

namespace JLG\Notation\Admin;

use JLG\Notation\Admin\Settings\SettingsRepository;
use JLG\Notation\Helpers;
use JLG\Notation\Utils\FormRenderer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings {

    private $option_name         = 'notation_jlg_settings';
    private $field_constraints   = array();
    private $section_definitions = array();
    private $field_dependencies  = array();
    private $section_counter     = 0;
    private $repository;

    public function __construct() {
        $this->repository = new SettingsRepository();
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
    }

    public function get_sections_overview() {
        return $this->get_sorted_sections();
    }

    public function get_section_panels() {
        return $this->repository->build_panels_payload( $this->get_sorted_sections() );
    }

    public function get_settings_modes() {
        return $this->repository->get_modes();
    }

    public function get_active_mode() {
        return $this->repository->get_user_mode();
    }

    public function get_repository() {
        return $this->repository;
    }

    public function get_serialized_options_for_mode( $mode ) {
        return $this->repository->serialize_options_for_mode( $mode );
    }

    public function get_field_dependencies() {
        return array_values( $this->field_dependencies );
    }

    public function get_preview_snapshot() {
        $options  = Helpers::get_plugin_options();
        $defaults = Helpers::get_default_settings();

        $fields = array(
            'visual_theme',
            'visual_preset',
            'score_layout',
            'text_glow_enabled',
            'text_glow_color_mode',
            'text_glow_custom_color',
            'text_glow_intensity',
            'circle_glow_enabled',
            'circle_glow_color_mode',
            'circle_glow_custom_color',
            'circle_glow_intensity',
            'circle_glow_pulse',
            'circle_glow_speed',
            'dark_bg_color',
            'dark_bg_color_secondary',
            'dark_text_color',
            'dark_text_color_secondary',
            'dark_border_color',
            'light_bg_color',
            'light_bg_color_secondary',
            'light_text_color',
            'light_text_color_secondary',
            'light_border_color',
            'score_gradient_1',
            'score_gradient_2',
            'color_low',
            'color_mid',
            'color_high',
        );

        $snapshot = array();

        foreach ( $fields as $field ) {
            if ( isset( $options[ $field ] ) ) {
                $snapshot[ $field ] = $options[ $field ];
            } elseif ( isset( $defaults[ $field ] ) ) {
                $snapshot[ $field ] = $defaults[ $field ];
            }
        }

        return $snapshot;
    }

    public function register_settings() {
        register_setting( 'notation_jlg_page', $this->option_name, array( $this, 'sanitize_options' ) );
        $this->register_all_sections();
    }

    public function register_rest_routes() {
        if ( ! function_exists( 'register_rest_route' ) ) {
            return;
        }

        register_rest_route(
            'notation-jlg/v1',
            '/settings-mode',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'rest_get_settings_mode' ),
                    'permission_callback' => array( $this, 'rest_permissions_check' ),
                ),
                array(
                    'methods'             => 'POST',
                    'callback'            => array( $this, 'rest_update_settings_mode' ),
                    'permission_callback' => array( $this, 'rest_permissions_check' ),
                    'args'                => array(
                        'mode' => array(
                            'type'              => 'string',
                            'required'          => true,
                            'sanitize_callback' => 'sanitize_key',
                        ),
                    ),
                ),
            )
        );
    }

    public function rest_permissions_check() {
        return current_user_can( 'manage_options' );
    }

    public function rest_get_settings_mode( $request ) {
        unset( $request );

        $mode = $this->repository->get_user_mode();

        return rest_ensure_response(
            array(
                'mode'    => $mode,
                'panels'  => $this->get_section_panels(),
                'options' => $this->repository->serialize_options_for_mode( $mode ),
            )
        );
    }

    public function rest_update_settings_mode( $request ) {
        $mode = '';

        if ( $request instanceof \WP_REST_Request ) {
            $mode = $request->get_param( 'mode' );
        } elseif ( is_array( $request ) ) {
            $mode = $request['mode'] ?? '';
        }

        $normalized = SettingsRepository::normalize_mode( $mode );
        $this->repository->set_user_mode( $normalized );

        return rest_ensure_response(
            array(
                'mode'   => $normalized,
                'panels' => $this->get_section_panels(),
            )
        );
    }

    private function register_section( $section_id, $label, $icon = '', $summary = '', $callback = null ) {
        $section_id = sanitize_key( $section_id );

        if ( $section_id === '' ) {
            return;
        }

        ++$this->section_counter;

        $this->section_definitions[ $section_id ] = array(
            'id'      => $section_id,
            'title'   => $label,
            'icon'    => $icon,
            'summary' => $summary,
            'order'   => $this->section_counter,
        );

        $title = sprintf(
            /* translators: 1: section order, 2: section label */
            _x( '%1$s. %2$s', 'Settings section heading', 'notation-jlg' ),
            number_format_i18n( $this->section_counter ),
            $label
        );

        add_settings_section( $section_id, $title, $callback, 'notation_jlg_page' );
    }

    private function get_sorted_sections() {
        $sections = $this->section_definitions;

        uasort(
            $sections,
            static function ( $a, $b ) {
                $order_a = isset( $a['order'] ) ? (int) $a['order'] : 0;
                $order_b = isset( $b['order'] ) ? (int) $b['order'] : 0;

                if ( $order_a === $order_b ) {
                    return 0;
                }

                return ( $order_a < $order_b ) ? -1 : 1;
            }
        );

        return array_values( $sections );
    }

    private function add_field_dependency( $controller, $targets, array $config = array() ) {
        $controller = sanitize_key( $controller );
        $targets    = array_filter(
            array_map(
                'sanitize_key',
                is_array( $targets ) ? $targets : array( $targets )
            )
        );

        if ( $controller === '' || empty( $targets ) ) {
            return;
        }

        $defaults = array(
            'expected_value' => '1',
            'comparison'     => 'equals',
            'message'        => '',
        );

        $config = wp_parse_args( $config, $defaults );

        $this->field_dependencies[] = array(
            'controller'    => $controller,
            'targets'       => $targets,
            'expectedValue' => (string) $config['expected_value'],
            'comparison'    => $config['comparison'],
            'message'       => $config['message'],
        );
    }

    public function sanitize_options( $input ) {
        if ( ! is_array( $input ) ) {
            return Helpers::get_default_settings();
        }

        $schema          = SettingsRepository::get_option_schema();
        $defaults        = Helpers::get_default_settings();
        $current_options = Helpers::get_plugin_options();
        $sanitized       = array();

        foreach ( $schema as $key => $definition ) {
            $sanitized[ $key ] = $this->sanitize_field( $key, $definition, $input, $defaults, $current_options );
        }

        foreach ( $defaults as $key => $default_value ) {
            if ( ! array_key_exists( $key, $sanitized ) ) {
                $sanitized[ $key ] = $default_value;
            }
        }

        $sanitized = $this->apply_post_processing( $sanitized, $input, $schema, $defaults, $current_options );

        Helpers::flush_plugin_options_cache();

        return $sanitized;
    }

    private function sanitize_field( $key, array $definition, array $input, array $defaults, array $current_options ) {
        $type          = $definition['type'] ?? 'text';
        $default_value = $definition['default'] ?? ( $defaults[ $key ] ?? '' );
        $raw_value     = array_key_exists( $key, $input ) ? $input[ $key ] : null;

        switch ( $type ) {
            case 'rating_categories':
                $raw_categories     = is_array( $raw_value ) ? $raw_value : array();
                $current_categories = isset( $current_options['rating_categories'] ) && is_array( $current_options['rating_categories'] )
                    ? $current_options['rating_categories']
                    : array();
                $default_categories = is_array( $default_value ) ? $default_value : array();

                $value = $this->sanitize_rating_categories( $raw_categories, $default_categories, $current_categories );
                break;
            case 'post_types':
                $available_slugs = $this->get_public_post_type_slugs();
                $raw_post_types  = $raw_value;

                if ( $raw_post_types === null ) {
                    if ( isset( $current_options['allowed_post_types'] ) ) {
                        $raw_post_types = $current_options['allowed_post_types'];
                    } else {
                        $raw_post_types = $default_value;
                    }
                }

                $default_post_types = is_array( $default_value ) ? $default_value : array( 'post' );

                $value = $this->sanitize_allowed_post_types( $raw_post_types, $default_post_types, $available_slugs );
                break;
            case 'game_explorer_filters':
                $default_filters = is_array( $default_value ) ? $default_value : Helpers::get_default_game_explorer_filters();
                $current_filters = isset( $current_options['game_explorer_filters'] )
                    ? $current_options['game_explorer_filters']
                    : $default_filters;

                $raw_filters = array_key_exists( $key, $input ) ? $raw_value : null;

                $value = $this->sanitize_game_explorer_filters( $raw_filters, $default_filters, $current_filters );
                break;
            case 'checkbox':
                $value = $this->sanitize_checkbox( $raw_value );
                break;
            case 'enum':
                $choices = $definition['choices'] ?? array();
                $value   = $this->sanitize_enum( $raw_value, $choices, $default_value );
                break;
            case 'number':
                $value = $this->sanitize_number( $key, $raw_value, $default_value, $definition );
                break;
            case 'color':
                $allow_transparent = ! empty( $definition['allow_transparent'] );
                $value             = $this->sanitize_color( $raw_value, $default_value, $allow_transparent );
                break;
            case 'css':
                $value = is_string( $raw_value ) ? wp_strip_all_tags( $raw_value ) : '';
                break;
            case 'csv':
                $value = $this->sanitize_csv( $raw_value, $default_value );
                break;
            case 'text':
            default:
                $value = $this->sanitize_text( $raw_value, $default_value );
                break;
        }

        if ( isset( $definition['sanitize_callback'] ) && is_callable( $definition['sanitize_callback'] ) ) {
            $value = call_user_func( $definition['sanitize_callback'], $value );
        }

        return $value;
    }

    private function sanitize_checkbox( $value ) {
        if ( is_array( $value ) ) {
            $value = reset( $value );
        }

        return ! empty( $value ) ? 1 : 0;
    }

    private function sanitize_enum( $value, array $choices, $default_value ) {
        $normalized_choices = array_map( 'sanitize_key', $choices );
        $normalized_choices = array_values( array_unique( $normalized_choices ) );

        $candidate = is_scalar( $value ) ? sanitize_key( (string) $value ) : '';

        if ( in_array( $candidate, $normalized_choices, true ) ) {
            return $candidate;
        }

        $default_value = is_scalar( $default_value ) ? sanitize_key( (string) $default_value ) : '';

        if ( in_array( $default_value, $normalized_choices, true ) ) {
            return $default_value;
        }

        return $normalized_choices[0] ?? '';
    }

    private function sanitize_number( $key, $value, $default_value, array $definition ) {
        $constraints = array(
            'min'  => $definition['min'] ?? null,
            'max'  => $definition['max'] ?? null,
            'step' => $definition['step'] ?? null,
        );

        if ( isset( $definition['cast'] ) ) {
            $constraints['cast'] = $definition['cast'];
        }

        return $this->normalize_numeric_value( $key, $value, $default_value, $constraints );
    }

    private function sanitize_color( $value, $default_value, $allow_transparent ) {
        $raw = is_string( $value ) ? strtolower( trim( $value ) ) : '';

        if ( $allow_transparent && $raw === 'transparent' ) {
            return 'transparent';
        }

        $sanitized = is_string( $value ) ? sanitize_hex_color( $value ) : '';

        if ( $sanitized ) {
            return $sanitized;
        }

        $default_raw = is_string( $default_value ) ? strtolower( trim( $default_value ) ) : '';

        if ( $allow_transparent && $default_raw === 'transparent' ) {
            return 'transparent';
        }
    }

        $default_color = is_string( $default_value ) ? sanitize_hex_color( $default_value ) : '';

        return $default_color ?: '';
    }

    private function sanitize_csv( $value, $default_value ) {
        if ( is_string( $value ) ) {
            $items = explode( ',', $value );
        } elseif ( is_array( $value ) ) {
            $items = $value;
        } else {
            $items = array();
        }

        $sanitized = array();

        foreach ( $items as $item ) {
            $normalized = sanitize_key( (string) $item );

            if ( $normalized !== '' ) {
                $sanitized[] = $normalized;
            }
        }

        if ( empty( $sanitized ) ) {
            if ( is_string( $default_value ) ) {
                $sanitized = array_filter( array_map( 'sanitize_key', explode( ',', $default_value ) ) );
            } elseif ( is_array( $default_value ) ) {
                $sanitized = array_filter( array_map( 'sanitize_key', $default_value ) );
            }
        }

        return implode( ',', array_unique( $sanitized ) );
    }

    private function sanitize_text( $value, $default_value ) {
        if ( $value === null ) {
            return is_scalar( $default_value ) ? sanitize_text_field( (string) $default_value ) : '';
        }

        if ( is_scalar( $value ) ) {
            return sanitize_text_field( (string) $value );
        }

        return is_scalar( $default_value ) ? sanitize_text_field( (string) $default_value ) : '';
    }

    private function apply_post_processing( array $sanitized, array $input, array $schema, array $defaults, array $current_options ) {
        foreach ( $schema as $key => $definition ) {
            if ( empty( $definition['postProcess'] ) ) {
                continue;
            }

            $callbacks = (array) $definition['postProcess'];

            foreach ( $callbacks as $callback ) {
                $method = 'post_process_' . $callback;

                if ( method_exists( $this, $method ) ) {
                    $sanitized = $this->{$method}( $sanitized, $input, $defaults, $current_options, $definition );
                }
            }
        }

        return $sanitized;
    }

    private function post_process_schedule_score_scale_migration( array $sanitized, array $input, array $defaults, array $current_options, array $definition = array() ) {
        unset( $definition );

        $previous_max = isset( $current_options['score_max'] )
            ? Helpers::get_score_max( array( 'score_max' => $current_options['score_max'] ) )
            : ( $defaults['score_max'] ?? 10 );

        $new_max = isset( $sanitized['score_max'] )
            ? Helpers::get_score_max( array( 'score_max' => $sanitized['score_max'] ) )
            : $previous_max;

        if ( $previous_max !== $new_max ) {
            Helpers::schedule_score_scale_migration( $previous_max, $new_max );
        }

        return $sanitized;
    }

    private function post_process_clamp_rating_badge_threshold( array $sanitized, array $input, array $defaults, array $current_options, array $definition ) {
        if ( ! array_key_exists( 'rating_badge_threshold', $sanitized ) ) {
            return $sanitized;
        }

        $raw_threshold = array_key_exists( 'rating_badge_threshold', $input )
            ? $input['rating_badge_threshold']
            : $sanitized['rating_badge_threshold'];

        if ( is_string( $raw_threshold ) ) {
            $raw_threshold = trim( $raw_threshold );
        }

        if ( ! is_numeric( $raw_threshold ) ) {
            $raw_threshold = is_numeric( $sanitized['rating_badge_threshold'] )
                ? (float) $sanitized['rating_badge_threshold']
                : (float) ( $defaults['rating_badge_threshold'] ?? 0 );
        } else {
            $raw_threshold = (float) $raw_threshold;
        }

        $raw_threshold = max( 0.0, (float) $raw_threshold );

        $score_max_reference = $sanitized['score_max'] ?? ( $defaults['score_max'] ?? 10 );

        if ( ! is_numeric( $score_max_reference ) ) {
            $score_max_reference = Helpers::get_score_max( array( 'score_max' => $score_max_reference ) );
        }
    }

    private function maybe_schedule_score_scale_migration( array $sanitized, array $context ) {
        $current_options = $context['current'] ?? array();
        $defaults        = $context['defaults'] ?? Helpers::get_default_settings();

        $old_reference = isset( $current_options['score_max'] )
            ? array( 'score_max' => $current_options['score_max'] )
            : array( 'score_max' => $defaults['score_max'] ?? 10 );
        $new_reference = array( 'score_max' => $sanitized['score_max'] ?? ( $defaults['score_max'] ?? 10 ) );

        if ( is_numeric( $score_max_reference ) ) {
            $raw_threshold = min( $raw_threshold, (float) $score_max_reference );
        }

        $constraints = array(
            'min'  => 0,
            'max'  => is_numeric( $score_max_reference ) ? (float) $score_max_reference : null,
            'step' => $definition['step'] ?? null,
        );

        $sanitized['rating_badge_threshold'] = $this->normalize_numeric_value(
            'rating_badge_threshold',
            $raw_threshold,
            $defaults['rating_badge_threshold'] ?? 0,
            $constraints
        );

        return $sanitized;
    }

    private function sanitize_game_explorer_filters( $raw_filters, array $default_filters, $current_filters ) {
        $fallback = ! empty( $default_filters ) ? $default_filters : Helpers::get_default_game_explorer_filters();

        if ( $raw_filters === null ) {
            $raw_filters = $current_filters;
        }

        $normalized = Helpers::normalize_game_explorer_filters( $raw_filters, $fallback );

        if ( empty( $normalized ) ) {
            return Helpers::get_default_game_explorer_filters();
        }

        return $normalized;
    }

    private function sanitize_allowed_post_types( $raw_value, array $defaults, array $allowed_slugs ) {
        if ( is_string( $raw_value ) || is_numeric( $raw_value ) ) {
            $raw_value = array( $raw_value );
        }

        if ( ! is_array( $raw_value ) ) {
            $raw_value = array();
        }

        $sanitized = array();

        foreach ( $raw_value as $value ) {
            if ( is_array( $value ) ) {
                continue;
            }

            $unslashed = wp_unslash( (string) $value );
            $key       = sanitize_key( $unslashed );

            if ( $key !== '' ) {
                $sanitized[] = $key;
            }
        }

        if ( ! empty( $allowed_slugs ) ) {
            $sanitized = array_values( array_intersect( $sanitized, $allowed_slugs ) );
        } else {
            $sanitized = array_values( array_unique( $sanitized ) );
        }

        if ( empty( $sanitized ) ) {
            $fallback = array_values( array_intersect( $defaults, $allowed_slugs ) );

            if ( empty( $fallback ) ) {
                $fallback = $defaults;
            }

            if ( empty( $fallback ) ) {
                $fallback = array( 'post' );
            }

            $sanitized = array_values( array_unique( $fallback ) );
        }

        return $sanitized;
    }

    private function get_public_post_type_choices() {
        if ( ! function_exists( 'get_post_types' ) ) {
            return array();
        }

        $post_types = \get_post_types( array( 'public' => true ), 'objects' );

        if ( ! is_array( $post_types ) ) {
            return array();
        }

        $choices = array();

        foreach ( $post_types as $slug => $object ) {
            $slug = sanitize_key( (string) $slug );

            if ( $slug === '' ) {
                continue;
            }

            $label = '';

            if ( isset( $object->labels->singular_name ) && is_string( $object->labels->singular_name ) ) {
                $label = trim( $object->labels->singular_name );
            }

            if ( $label === '' && isset( $object->label ) && is_string( $object->label ) ) {
                $label = trim( $object->label );
            }

            if ( $label === '' ) {
                $label = ucwords( str_replace( array( '-', '_' ), ' ', $slug ) );
            }

            $choices[ $slug ] = $label;
        }

        return $choices;
    }

    private function get_public_post_type_slugs() {
        $choices = $this->get_public_post_type_choices();

        if ( empty( $choices ) ) {
            return array();
        }

        return array_keys( $choices );
    }

    private function normalize_numeric_value( $key, $value, $default_value, ?array $constraints = null ) {
        if ( $constraints === null ) {
            $constraints = isset( $this->field_constraints[ $key ] ) ? $this->field_constraints[ $key ] : array();
        }

        $min  = array_key_exists( 'min', $constraints ) && $constraints['min'] !== null ? floatval( $constraints['min'] ) : null;
        $max  = array_key_exists( 'max', $constraints ) && $constraints['max'] !== null ? floatval( $constraints['max'] ) : null;
        $step = array_key_exists( 'step', $constraints ) && $constraints['step'] !== null ? floatval( $constraints['step'] ) : null;
        $cast = $constraints['cast'] ?? null;

        if ( ! is_numeric( $value ) ) {
            if ( is_numeric( $default_value ) ) {
                $number = floatval( $default_value );
            } elseif ( $min !== null ) {
                $number = $min;
            } else {
                $number = 0;
            }
        } else {
            $number = floatval( $value );
        }

        if ( $min !== null ) {
            $number = max( $number, $min );
        }

        if ( $max !== null ) {
            $number = min( $number, $max );
        }

        if ( $step !== null && $step > 0 ) {
            $base   = ( $min !== null ) ? $min : 0.0;
            $steps  = round( ( $number - $base ) / $step );
            $number = $base + ( $steps * $step );
            $number = $this->round_to_step_precision( $number, $step );

            if ( $min !== null ) {
                $number = max( $number, $min );
            }

            if ( $max !== null ) {
                $number = min( $number, $max );
            }
        }

        if ( $cast === 'int' ) {
            return (int) round( $number );
        }

        if ( $cast === 'float' ) {
            return (float) $number;
        }

        if ( $this->should_cast_to_int( $step, $min, $max, $default_value ) ) {
            return (int) round( $number );
        }

        return $number;
    }

    private function round_to_step_precision( $value, $step ) {
        $precision = $this->get_step_precision( $step );

        if ( $precision > 0 ) {
            return round( $value, $precision );
        }

        return round( $value );
    }

    private function get_step_precision( $step ) {
        $formatted        = rtrim( rtrim( sprintf( '%.10F', $step ), '0' ), '.' );
        $decimal_position = strpos( $formatted, '.' );

        if ( $decimal_position === false ) {
            return 0;
        }

        return strlen( $formatted ) - $decimal_position - 1;
    }

    private function should_cast_to_int( $step, $min, $max, $default_value ) {
        $step = $step ?? 1.0;

        if ( ! $this->is_integer_like( $step ) ) {
            return false;
        }

        foreach ( array( $min, $max, $default_value ) as $value ) {
            if ( $value === null || $value === '' ) {
                continue;
            }

            if ( ! $this->is_integer_like( $value ) ) {
                return false;
            }
        }

        return true;
    }

    private function is_integer_like( $value ) {
        if ( ! is_numeric( $value ) ) {
            return false;
        }

        return abs( $value - round( $value ) ) < 0.000001;
    }

    private function store_field_constraints( array $args ) {
        if ( ( $args['type'] ?? '' ) !== 'number' || empty( $args['id'] ) ) {
            return;
        }

        $this->field_constraints[ $args['id'] ] = array(
            'min'  => $args['min'] ?? null,
            'max'  => $args['max'] ?? null,
            'step' => $args['step'] ?? null,
        );
    }

    private function register_all_sections() {
        // Section 1: Libellés
        $this->register_section(
            'jlg_labels',
            __( 'Libellés des catégories', 'notation-jlg' ),
            '📝',
            __( 'Ajustez les intitulés et la pondération de vos critères de test.', 'notation-jlg' )
        );
        add_settings_field(
            'rating_categories',
            __( 'Catégories de notation', 'notation-jlg' ),
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_labels',
            array(
                'id'   => 'rating_categories',
                'type' => 'rating_categories',
                'desc' => __( 'Ajoutez, réorganisez ou renommez les catégories utilisées pour vos notes détaillées.', 'notation-jlg' ),
            )
        );

        // Section 2: Contenus
        $this->register_section(
            'jlg_content',
            __( 'Contenus', 'notation-jlg' ),
            '📚',
            __( 'Choisissez les types de contenus WordPress compatibles avec la notation.', 'notation-jlg' ),
            function () {
                echo '<p class="description">' . esc_html__( 'Sélectionnez les types de contenus qui peuvent utiliser les notations du plugin.', 'notation-jlg' ) . '</p>';
            }
        );
        add_settings_field(
            'allowed_post_types',
            __( 'Types de contenus autorisés', 'notation-jlg' ),
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_content',
            array(
                'id'      => 'allowed_post_types',
                'type'    => 'post_types',
                'choices' => $this->get_public_post_type_choices(),
                'desc'    => __( 'Maintenez Ctrl (ou Cmd sur Mac) pour sélectionner plusieurs types.', 'notation-jlg' ),
            )
        );

        // Section 3: Présentation de la note globale
        $this->register_section(
            'jlg_layout',
            __( 'Présentation de la note globale', 'notation-jlg' ),
            '🎨',
            __( 'Définissez le barème et la façon dont la note principale est affichée.', 'notation-jlg' )
        );
        $score_max_field_args = array(
            'id'   => 'score_max',
            'type' => 'number',
            'min'  => 5,
            'max'  => 100,
            'step' => 1,
            'desc' => __( 'Définissez la note maximale utilisée pour vos tests (par exemple 10, 20 ou 100).', 'notation-jlg' ),
        );
        add_settings_field(
            'score_max',
            __( 'Barème maximum', 'notation-jlg' ),
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_layout',
            $score_max_field_args
        );
        $this->store_field_constraints( $score_max_field_args );

        add_settings_field(
            'visual_preset',
            __( 'Preset visuel', 'notation-jlg' ),
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_layout',
            array(
                'id'      => 'visual_preset',
                'type'    => 'select',
                'options' => array(
                    'signature' => __( 'Signature (dégradé dynamique)', 'notation-jlg' ),
                    'minimal'   => __( 'Minimal (interfaces sobres)', 'notation-jlg' ),
                    'editorial' => __( 'Éditorial (contraste élevé)', 'notation-jlg' ),
                ),
                'desc'    => __( 'Applique instantanément une combinaison de couleurs et d’espacements.', 'notation-jlg' ),
            )
        );

        add_settings_field(
            'score_layout',
            'Style d\'affichage',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_layout',
            array(
				'id'      => 'score_layout',
				'type'    => 'select',
				'options' => array(
					'text'   => 'Texte simple',
					'circle' => 'Dans un cercle',
				),
			)
        );
        add_settings_field(
            'circle_dynamic_bg_enabled',
            'Fond dynamique (Mode Cercle)',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_layout',
            array(
				'id'   => 'circle_dynamic_bg_enabled',
				'type' => 'checkbox',
				'desc' => 'La couleur du cercle change selon la note',
			)
        );
        add_settings_field(
            'circle_border_enabled',
            'Activer la bordure (Mode Cercle)',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_layout',
            array(
				'id'   => 'circle_border_enabled',
				'type' => 'checkbox',
			)
        );
        $circle_border_width_args = array(
			'id'   => 'circle_border_width',
			'type' => 'number',
			'min'  => 1,
			'max'  => 20,
		);
        add_settings_field(
            'circle_border_width',
            'Épaisseur bordure (px)',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_layout',
            $circle_border_width_args
        );
        $this->store_field_constraints( $circle_border_width_args );
        add_settings_field(
            'circle_border_color',
            'Couleur de la bordure',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_layout',
            array(
                'id'   => 'circle_border_color',
                'type' => 'color',
                'desc' => 'Cliquez pour ouvrir le sélecteur ou saisissez un code hexadécimal personnalisé.',
            )
        );

        // Section 4: Couleurs & Thèmes
        $this->register_section(
            'jlg_colors',
            __( 'Couleurs et thèmes', 'notation-jlg' ),
            '🌈',
            __( 'Paramétrez les palettes clair/sombre et les couleurs sémantiques.', 'notation-jlg' )
        );
        add_settings_field(
            'visual_theme',
            'Thème Visuel Principal',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_colors',
            array(
				'id'      => 'visual_theme',
				'type'    => 'select',
				'options' => array(
					'dark'  => 'Thème Sombre',
					'light' => 'Thème Clair',
				),
			)
        );

        // Couleurs thème sombre
        add_settings_field( 'dark_theme_header', '<h4>Couleurs du Thème Sombre</h4>', function () {}, 'notation_jlg_page', 'jlg_colors' );
        $dark_colors = array(
            'dark_bg_color'             => 'Fond principal',
            'dark_bg_color_secondary'   => 'Fond secondaire',
            'dark_text_color'           => 'Texte principal',
            'dark_text_color_secondary' => 'Texte secondaire',
            'dark_border_color'         => 'Bordures',
        );
        foreach ( $dark_colors as $id => $label ) {
            add_settings_field(
                $id,
                $label,
                array( $this, 'render_field' ),
                'notation_jlg_page',
                'jlg_colors',
                array(
					'id'   => $id,
					'type' => 'color',
				)
            );
        }

        // Couleurs thème clair
        add_settings_field( 'light_theme_header', '<h4>Couleurs du Thème Clair</h4>', function () {}, 'notation_jlg_page', 'jlg_colors' );
        $light_colors = array(
            'light_bg_color'             => 'Fond principal',
            'light_bg_color_secondary'   => 'Fond secondaire',
            'light_text_color'           => 'Texte principal',
            'light_text_color_secondary' => 'Texte secondaire',
            'light_border_color'         => 'Bordures',
        );
        foreach ( $light_colors as $id => $label ) {
            add_settings_field(
                $id,
                $label,
                array( $this, 'render_field' ),
                'notation_jlg_page',
                'jlg_colors',
                array(
					'id'   => $id,
					'type' => 'color',
				)
            );
        }

        // Couleurs sémantiques
        add_settings_field( 'semantic_colors_header', '<h4>Couleurs Sémantiques</h4>', function () {}, 'notation_jlg_page', 'jlg_colors' );
        $semantic_colors = array(
            'score_gradient_1' => 'Dégradé Note 1',
            'score_gradient_2' => 'Dégradé Note 2',
            'color_low'        => 'Notes Faibles (0-3)',
            'color_mid'        => 'Notes Moyennes (4-7)',
            'color_high'       => 'Notes Élevées (haut du barème)',
        );
        foreach ( $semantic_colors as $id => $label ) {
            add_settings_field(
                $id,
                $label,
                array( $this, 'render_field' ),
                'notation_jlg_page',
                'jlg_colors',
                array(
					'id'   => $id,
					'type' => 'color',
				)
            );
        }

        // Section 5: Effet Glow/Neon (Mode Texte)
        $this->register_section(
            'jlg_glow_text',
            __( 'Effet néon – Mode texte', 'notation-jlg' ),
            '✨',
            __( 'Activez et personnalisez le halo autour de la note en version texte.', 'notation-jlg' )
        );
        add_settings_field(
            'text_glow_enabled',
            'Activer l\'effet Neon',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_glow_text',
            array(
				'id'   => 'text_glow_enabled',
				'type' => 'checkbox',
				'desc' => 'Ajoute un halo lumineux à la note en mode texte',
			)
        );
        add_settings_field(
            'text_glow_color_mode',
            'Mode de couleur',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_glow_text',
            array(
				'id'      => 'text_glow_color_mode',
				'type'    => 'select',
				'options' => array(
					'dynamic' => 'Dynamique (selon la note)',
					'custom'  => 'Couleur fixe',
				),
				'desc'    => 'Dynamique = couleur change selon la note (vert/orange/rouge), Fixe = couleur personnalisée',
			)
        );
        add_settings_field(
            'text_glow_custom_color',
            'Couleur personnalisée',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_glow_text',
            array(
				'id'   => 'text_glow_custom_color',
				'type' => 'color',
				'desc' => 'Utilisée uniquement si mode "Couleur fixe" est sélectionné',
			)
        );
        $text_glow_intensity_args = array(
			'id'   => 'text_glow_intensity',
			'type' => 'number',
			'min'  => 5,
			'max'  => 50,
		);
        add_settings_field(
            'text_glow_intensity',
            'Intensité (px)',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_glow_text',
            $text_glow_intensity_args
        );
        $this->store_field_constraints( $text_glow_intensity_args );
        add_settings_field(
            'text_glow_pulse',
            'Activer la pulsation',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_glow_text',
            array(
				'id'   => 'text_glow_pulse',
				'type' => 'checkbox',
				'desc' => 'Animation de pulsation du halo',
			)
        );
        $text_glow_speed_args = array(
			'id'   => 'text_glow_speed',
			'type' => 'number',
			'min'  => 0.5,
			'max'  => 10,
			'step' => 0.1,
		);
        add_settings_field(
            'text_glow_speed',
            'Vitesse pulsation (sec)',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_glow_text',
            $text_glow_speed_args
        );
        $this->store_field_constraints( $text_glow_speed_args );

        $this->add_field_dependency(
            'text_glow_enabled',
            array( 'text_glow_color_mode', 'text_glow_custom_color', 'text_glow_intensity', 'text_glow_pulse', 'text_glow_speed' ),
            array(
                'message' => __( 'Activez l’effet néon en mode texte pour modifier ces réglages.', 'notation-jlg' ),
            )
        );

        $this->add_field_dependency(
            'text_glow_color_mode',
            array( 'text_glow_custom_color' ),
            array(
                'expected_value' => 'custom',
                'message'        => __( 'Sélectionnez « Couleur fixe » pour personnaliser ce paramètre.', 'notation-jlg' ),
            )
        );

        $this->add_field_dependency(
            'text_glow_pulse',
            array( 'text_glow_speed' ),
            array(
                'message' => __( 'Activez la pulsation pour ajuster sa vitesse.', 'notation-jlg' ),
            )
        );

        // Section 6: Effet Glow/Neon (Mode Cercle)
        $this->register_section(
            'jlg_glow_circle',
            __( 'Effet néon – Mode cercle', 'notation-jlg' ),
            '💡',
            __( 'Paramétrez le halo dynamique du score circulaire.', 'notation-jlg' )
        );
        add_settings_field(
            'circle_glow_enabled',
            'Activer l\'effet Neon',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_glow_circle',
            array(
				'id'   => 'circle_glow_enabled',
				'type' => 'checkbox',
				'desc' => 'Ajoute un halo lumineux au cercle',
			)
        );
        add_settings_field(
            'circle_glow_color_mode',
            'Mode de couleur',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_glow_circle',
            array(
				'id'      => 'circle_glow_color_mode',
				'type'    => 'select',
				'options' => array(
					'dynamic' => 'Dynamique (selon la note)',
					'custom'  => 'Couleur fixe',
				),
				'desc'    => 'Dynamique = couleur change selon la note (vert/orange/rouge), Fixe = couleur personnalisée',
			)
        );
        add_settings_field(
            'circle_glow_custom_color',
            'Couleur personnalisée',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_glow_circle',
            array(
				'id'   => 'circle_glow_custom_color',
				'type' => 'color',
				'desc' => 'Utilisée uniquement si mode "Couleur fixe" est sélectionné',
			)
        );
        $circle_glow_intensity_args = array(
			'id'   => 'circle_glow_intensity',
			'type' => 'number',
			'min'  => 5,
			'max'  => 50,
		);
        add_settings_field(
            'circle_glow_intensity',
            'Intensité (px)',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_glow_circle',
            $circle_glow_intensity_args
        );
        $this->store_field_constraints( $circle_glow_intensity_args );
        add_settings_field(
            'circle_glow_pulse',
            'Activer la pulsation',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_glow_circle',
            array(
				'id'   => 'circle_glow_pulse',
				'type' => 'checkbox',
			)
        );
        $circle_glow_speed_args = array(
			'id'   => 'circle_glow_speed',
			'type' => 'number',
			'min'  => 0.5,
			'max'  => 10,
			'step' => 0.1,
		);
        add_settings_field(
            'circle_glow_speed',
            'Vitesse pulsation (sec)',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_glow_circle',
            $circle_glow_speed_args
        );
        $this->store_field_constraints( $circle_glow_speed_args );

        $this->add_field_dependency(
            'circle_glow_enabled',
            array( 'circle_glow_color_mode', 'circle_glow_custom_color', 'circle_glow_intensity', 'circle_glow_pulse', 'circle_glow_speed' ),
            array(
                'message' => __( 'Activez l’effet néon en mode cercle pour modifier ces réglages.', 'notation-jlg' ),
            )
        );

        $this->add_field_dependency(
            'circle_glow_color_mode',
            array( 'circle_glow_custom_color' ),
            array(
                'expected_value' => 'custom',
                'message'        => __( 'Sélectionnez « Couleur fixe » pour personnaliser ce paramètre.', 'notation-jlg' ),
            )
        );

        $this->add_field_dependency(
            'circle_glow_pulse',
            array( 'circle_glow_speed' ),
            array(
                'message' => __( 'Activez la pulsation pour ajuster sa vitesse.', 'notation-jlg' ),
            )
        );

        // Section 7: Modules
        $this->register_section(
            'jlg_modules',
            __( 'Modules', 'notation-jlg' ),
            '🧩',
            __( 'Activez les fonctionnalités additionnelles (votes, badges, animations…).', 'notation-jlg' )
        );
        $module_fields = array(
            'user_rating_enabled'                 => 'Notation utilisateurs',
            'rating_badge_enabled'                => 'Badge « Coup de cœur »',
            'review_status_enabled'               => 'Statut de review',
            'review_status_auto_finalize_enabled' => __( 'Finalisation auto du statut', 'notation-jlg' ),
            'related_guides_enabled'              => 'Guides associés',
            'deals_enabled'                       => __( 'Deals & disponibilités', 'notation-jlg' ),
            'tagline_enabled'                     => 'Taglines bilingues',
            'seo_schema_enabled'                  => 'Schéma SEO (étoiles Google)',
            'enable_animations'                   => 'Animations des barres',
        );
        foreach ( $module_fields as $id => $title ) {
            add_settings_field(
                $id,
                $title,
                array( $this, 'render_field' ),
                'notation_jlg_page',
                'jlg_modules',
                array(
					'id'   => $id,
					'type' => 'checkbox',
				)
            );
        }

        $auto_finalize_days_args = array(
            'id'   => 'review_status_auto_finalize_days',
            'type' => 'number',
            'min'  => 1,
            'max'  => 60,
            'desc' => __( 'Nombre de jours à attendre après le dernier patch vérifié avant de repasser en « Version finale ».', 'notation-jlg' ),
        );
        add_settings_field(
            'review_status_auto_finalize_days',
            __( 'Délai avant finalisation (jours)', 'notation-jlg' ),
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_modules',
            $auto_finalize_days_args
        );
        $this->store_field_constraints( $auto_finalize_days_args );

        $rating_badge_threshold_args = array(
            'id'   => 'rating_badge_threshold',
            'type' => 'number',
            'min'  => 0,
            'max'  => Helpers::get_score_max(),
            'step' => 0.1,
            'desc' => __( 'Le badge apparaît lorsque la note globale atteint ce seuil. Utilisez le même barème que vos tests (ex. 8 pour un barème sur 10).', 'notation-jlg' ),
        );
        add_settings_field(
            'rating_badge_threshold',
            __( 'Seuil du badge', 'notation-jlg' ),
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_modules',
            $rating_badge_threshold_args
        );
        $this->store_field_constraints( $rating_badge_threshold_args );

        $related_guides_limit_args = array(
            'id'   => 'related_guides_limit',
            'type' => 'number',
            'min'  => 1,
            'max'  => 6,
            'step' => 1,
            'desc' => __( 'Nombre maximum de guides associés affichés sous la note.', 'notation-jlg' ),
        );
        add_settings_field(
            'related_guides_limit',
            __( 'Nombre de guides associés', 'notation-jlg' ),
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_modules',
            $related_guides_limit_args
        );
        $this->store_field_constraints( $related_guides_limit_args );

        add_settings_field(
            'related_guides_taxonomies',
            __( 'Taxonomies ciblées', 'notation-jlg' ),
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_modules',
            array(
                'id'          => 'related_guides_taxonomies',
                'type'        => 'text',
                'placeholder' => 'guide,astuce,category,post_tag',
                'desc'        => __( 'Renseignez les slugs de taxonomie séparés par des virgules pour identifier les guides pertinents (ex. guide,astuce).', 'notation-jlg' ),
            )
        );

        $deals_limit_args = array(
            'id'   => 'deals_limit',
            'type' => 'number',
            'min'  => 1,
            'max'  => 6,
            'step' => 1,
            'desc' => __( 'Nombre maximum d’offres affichées dans le module deals.', 'notation-jlg' ),
        );
        add_settings_field(
            'deals_limit',
            __( 'Nombre de deals', 'notation-jlg' ),
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_modules',
            $deals_limit_args
        );
        $this->store_field_constraints( $deals_limit_args );

        add_settings_field(
            'deals_button_rel',
            __( 'Attributs « rel » des liens', 'notation-jlg' ),
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_modules',
            array(
                'id'          => 'deals_button_rel',
                'type'        => 'text',
                'placeholder' => 'sponsored noopener',
                'desc'        => __( 'Séparez les valeurs par un espace (ex. sponsored noopener).', 'notation-jlg' ),
            )
        );

        add_settings_field(
            'deals_disclaimer',
            __( 'Message de transparence', 'notation-jlg' ),
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_modules',
            array(
                'id'          => 'deals_disclaimer',
                'type'        => 'textarea',
                'rows'        => 3,
                'placeholder' => __( 'Les liens ci-dessous peuvent être affiliés.', 'notation-jlg' ),
                'desc'        => __( 'Affiché sous la liste d’offres pour respecter les obligations de transparence.', 'notation-jlg' ),
            )
        );

        $this->add_field_dependency(
            'rating_badge_enabled',
            array( 'rating_badge_threshold' ),
            array(
                'message' => __( 'Activez le badge « Coup de cœur » pour définir son seuil.', 'notation-jlg' ),
            )
        );

        $this->add_field_dependency(
            'review_status_auto_finalize_enabled',
            array( 'review_status_auto_finalize_days' ),
            array(
                'message' => __( 'Activez la finalisation automatique pour ajuster le délai.', 'notation-jlg' ),
            )
        );

        $this->add_field_dependency(
            'related_guides_enabled',
            array( 'related_guides_limit', 'related_guides_taxonomies' ),
            array(
                'message' => __( 'Activez les guides associés pour configurer leurs options.', 'notation-jlg' ),
            )
        );

        $this->add_field_dependency(
            'deals_enabled',
            array( 'deals_limit', 'deals_button_rel', 'deals_disclaimer' ),
            array(
                'message' => __( 'Activez le module deals pour personnaliser ces réglages.', 'notation-jlg' ),
            )
        );

        // Section 8: Modules - Tagline
        $this->register_section(
            'jlg_tagline_section',
            __( 'Module Tagline', 'notation-jlg' ),
            '💬',
            __( 'Personnalisez l’accroche éditoriale affichée sous la note.', 'notation-jlg' )
        );
        $tagline_font_size_args = array(
			'id'   => 'tagline_font_size',
			'type' => 'number',
			'min'  => 12,
			'max'  => 32,
		);
        add_settings_field(
            'tagline_font_size',
            'Taille de police (px)',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_tagline_section',
            $tagline_font_size_args
        );
        $this->store_field_constraints( $tagline_font_size_args );
        add_settings_field(
            'tagline_bg_color',
            'Fond de la tagline',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_tagline_section',
            array(
				'id'   => 'tagline_bg_color',
				'type' => 'color',
			)
        );
        add_settings_field(
            'tagline_text_color',
            'Texte de la tagline',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_tagline_section',
            array(
				'id'   => 'tagline_text_color',
				'type' => 'color',
			)
        );

        $this->add_field_dependency(
            'tagline_enabled',
            array( 'tagline_font_size', 'tagline_bg_color', 'tagline_text_color' ),
            array(
                'message' => __( 'Activez le module Tagline dans l’onglet « Modules » pour accéder à ces options.', 'notation-jlg' ),
            )
        );

        // Section 9: Modules - Notation Utilisateurs
        $this->register_section(
            'jlg_user_rating_section',
            __( 'Module notation utilisateurs', 'notation-jlg' ),
            '⭐',
            __( 'Réglez la palette et le comportement du module dédié aux lecteurs.', 'notation-jlg' )
        );
        add_settings_field(
            'user_rating_title_color',
            'Couleur du titre',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_user_rating_section',
            array(
				'id'   => 'user_rating_title_color',
				'type' => 'color',
			)
        );
        add_settings_field(
            'user_rating_text_color',
            'Couleur du texte',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_user_rating_section',
            array(
				'id'   => 'user_rating_text_color',
				'type' => 'color',
			)
        );
        add_settings_field(
            'user_rating_star_color',
            'Couleur des étoiles',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_user_rating_section',
            array(
				'id'   => 'user_rating_star_color',
				'type' => 'color',
			)
        );
        add_settings_field(
            'user_rating_requires_login',
            __( 'Connexion obligatoire avant le vote', 'notation-jlg' ),
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_user_rating_section',
            array(
				'id'   => 'user_rating_requires_login',
				'type' => 'checkbox',
				'desc' => __( 'Empêche les visiteurs non connectés de voter et leur affiche un lien de connexion.', 'notation-jlg' ),
			)
        );

        $this->add_field_dependency(
            'user_rating_enabled',
            array( 'user_rating_title_color', 'user_rating_text_color', 'user_rating_star_color', 'user_rating_requires_login' ),
            array(
                'message' => __( 'Activez la notation utilisateurs pour ajuster ces préférences.', 'notation-jlg' ),
            )
        );

        // Section 10: Tableau Récapitulatif
        $this->register_section(
            'jlg_table',
            __( 'Tableau récapitulatif', 'notation-jlg' ),
            '📊',
            __( 'Ajustez les colonnes et le style du tableau comparatif.', 'notation-jlg' )
        );
        add_settings_field(
            'table_header_bg_color',
            'Fond de l\'en-tête',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_table',
            array(
				'id'   => 'table_header_bg_color',
				'type' => 'color',
			)
        );
        add_settings_field(
            'table_header_text_color',
            'Texte de l\'en-tête',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_table',
            array(
				'id'   => 'table_header_text_color',
				'type' => 'color',
			)
        );
        add_settings_field(
            'table_row_bg_color',
            'Fond des lignes',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_table',
            array(
                'id'                => 'table_row_bg_color',
                'type'              => 'color',
                'allow_transparent' => true,
                'desc'              => 'Le sélecteur WordPress accepte le clic ou la saisie libre. Tapez "transparent" pour conserver la valeur par défaut.',
            )
        );
        add_settings_field(
            'table_row_text_color',
            'Texte des lignes',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_table',
            array(
				'id'   => 'table_row_text_color',
				'type' => 'color',
			)
        );
        add_settings_field(
            'table_zebra_striping',
            'Alternance de couleurs',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_table',
            array(
				'id'   => 'table_zebra_striping',
				'type' => 'checkbox',
			)
        );
        add_settings_field(
            'table_zebra_bg_color',
            'Fond lignes alternées',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_table',
            array(
                'id'                => 'table_zebra_bg_color',
                'type'              => 'color',
                'allow_transparent' => true,
                'desc'              => 'Utilisez le sélecteur WordPress ou saisissez "transparent" pour désactiver la couleur alternée.',
            )
        );

        $this->add_field_dependency(
            'table_zebra_striping',
            array( 'table_zebra_bg_color' ),
            array(
                'message' => __( 'Activez l’alternance de couleurs pour personnaliser la teinte associée.', 'notation-jlg' ),
            )
        );
        add_settings_field(
            'table_border_style',
            'Style des bordures',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_table',
            array(
				'id'      => 'table_border_style',
				'type'    => 'select',
				'options' => array(
					'none'       => 'Aucune',
					'horizontal' => 'Horizontales',
					'full'       => 'Grille complète',
				),
			)
        );
        $table_border_width_args = array(
			'id'   => 'table_border_width',
			'type' => 'number',
			'min'  => 0,
			'max'  => 10,
		);
        add_settings_field(
            'table_border_width',
            'Épaisseur bordures (px)',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_table',
            $table_border_width_args
        );
        $this->store_field_constraints( $table_border_width_args );

        // Section 11: Style des Vignettes
        $this->register_section(
            'jlg_thumbnail_section',
            __( 'Style des vignettes', 'notation-jlg' ),
            '🖼️',
            __( 'Définissez la présentation des visuels associés aux jeux.', 'notation-jlg' )
        );
        add_settings_field(
            'thumb_text_color',
            'Couleur du texte',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_thumbnail_section',
            array(
				'id'   => 'thumb_text_color',
				'type' => 'color',
			)
        );
        $thumb_font_size_args = array(
			'id'   => 'thumb_font_size',
			'type' => 'number',
			'min'  => 10,
			'max'  => 24,
		);
        add_settings_field(
            'thumb_font_size',
            'Taille de police (px)',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_thumbnail_section',
            $thumb_font_size_args
        );
        $this->store_field_constraints( $thumb_font_size_args );

        $thumb_padding_args = array(
			'id'   => 'thumb_padding',
			'type' => 'number',
			'min'  => 2,
			'max'  => 20,
		);
        add_settings_field(
            'thumb_padding',
            'Espacement intérieur (px)',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_thumbnail_section',
            $thumb_padding_args
        );
        $this->store_field_constraints( $thumb_padding_args );

        $thumb_border_radius_args = array(
			'id'   => 'thumb_border_radius',
			'type' => 'number',
			'min'  => 0,
			'max'  => 50,
		);
        add_settings_field(
            'thumb_border_radius',
            'Arrondi des coins (px)',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_thumbnail_section',
            $thumb_border_radius_args
        );
        $this->store_field_constraints( $thumb_border_radius_args );

        // Section 12: CSS Personnalisé
        $this->register_section(
            'jlg_custom',
            __( 'CSS personnalisé', 'notation-jlg' ),
            '🎨',
            __( 'Injectez vos styles additionnels tout en conservant les mises à jour.', 'notation-jlg' )
        );
        add_settings_field(
            'custom_css',
            'Votre CSS',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_custom',
            array(
				'id'          => 'custom_css',
				'type'        => 'textarea',
				'placeholder' => '.review-box-jlg { margin: 50px 0; }',
			)
        );

        // Section 13: SEO
        $this->register_section(
            'jlg_seo_section',
            __( 'SEO', 'notation-jlg' ),
            '🔍',
            __( 'Générez des métadonnées enrichies et gardez les rich snippets cohérents.', 'notation-jlg' )
        );
        add_settings_field(
            'seo_schema_enabled',
            'Activer le schéma de notation (JSON-LD)',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_seo_section',
            array(
				'id'   => 'seo_schema_enabled',
				'type' => 'checkbox',
				'desc' => 'Aide Google à afficher des étoiles de notation',
			)
        );

        // Section 14: API
        $this->register_section(
            'jlg_api_section',
            __( 'API', 'notation-jlg' ),
            '🌐',
            __( 'Gérez l’intégration RAWG et vérifiez la connectivité.', 'notation-jlg' )
        );
        add_settings_field(
            'rawg_api_key',
            'Clé API RAWG.io',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_api_section',
            array(
				'id'   => 'rawg_api_key',
				'type' => 'text',
				'desc' => 'Obtenez votre clé API gratuite sur rawg.io/apidocs',
			)
        );

        // Section 15: Debug
        $this->register_section(
            'jlg_debug_section',
            __( 'Diagnostic', 'notation-jlg' ),
            '🔧',
            __( 'Outils de purge et d’analyse pour fiabiliser l’installation.', 'notation-jlg' )
        );
        add_settings_field(
            'debug_mode_enabled',
            'Activer le mode debug',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_debug_section',
            array(
				'id'   => 'debug_mode_enabled',
				'type' => 'checkbox',
				'desc' => 'Affiche des informations de diagnostic dans le code source',
			)
        );

        // Ajout d'un bouton de debug pour voir les options actuelles
        add_settings_field( 'debug_current_options', 'Options actuelles', array( $this, 'render_debug_info' ), 'notation_jlg_page', 'jlg_debug_section' );

        // Section 16: Game Explorer
        $this->register_section(
            'jlg_game_explorer',
            __( 'Game Explorer', 'notation-jlg' ),
            '🧭',
            __( 'Configurez les filtres, la pagination et la mise en page du listing de jeux.', 'notation-jlg' )
        );

        $game_explorer_columns_args = array(
			'id'   => 'game_explorer_columns',
			'type' => 'number',
			'min'  => 2,
			'max'  => 4,
		);
        add_settings_field(
            'game_explorer_columns',
            'Colonnes (desktop)',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_game_explorer',
            $game_explorer_columns_args
        );
        $this->store_field_constraints( $game_explorer_columns_args );

        $game_explorer_ppp_args = array(
			'id'   => 'game_explorer_posts_per_page',
			'type' => 'number',
			'min'  => 6,
			'max'  => 36,
		);
        add_settings_field(
            'game_explorer_posts_per_page',
            'Jeux par page',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_game_explorer',
            $game_explorer_ppp_args
        );
        $this->store_field_constraints( $game_explorer_ppp_args );

        add_settings_field(
            'game_explorer_filters',
            'Filtres disponibles',
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_game_explorer',
            array(
                'id'      => 'game_explorer_filters',
                'type'    => 'checkbox_group',
                'options' => array(
                    'letter'       => __( 'Lettre', 'notation-jlg' ),
                    'category'     => __( 'Catégorie', 'notation-jlg' ),
                    'platform'     => __( 'Plateforme', 'notation-jlg' ),
                    'availability' => __( 'Disponibilité', 'notation-jlg' ),
                    'search'       => __( 'Recherche', 'notation-jlg' ),
                ),
                'desc'    => __( 'Sélectionnez les filtres à afficher dans l’explorateur de jeux.', 'notation-jlg' ),
            )
        );

        $score_position_options = array(
            'top-left'     => __( 'En haut à gauche', 'notation-jlg' ),
            'top-right'    => __( 'En haut à droite', 'notation-jlg' ),
            'middle-left'  => __( 'Au centre à gauche', 'notation-jlg' ),
            'middle-right' => __( 'Au centre à droite', 'notation-jlg' ),
            'bottom-left'  => __( 'En bas à gauche', 'notation-jlg' ),
            'bottom-right' => __( 'En bas à droite', 'notation-jlg' ),
        );

        add_settings_field(
            'game_explorer_score_position',
            __( 'Position de la note', 'notation-jlg' ),
            array( $this, 'render_field' ),
            'notation_jlg_page',
            'jlg_game_explorer',
            array(
                'id'      => 'game_explorer_score_position',
                'type'    => 'select',
                'options' => $score_position_options,
            )
        );
    }

    private function sanitize_rating_categories( array $categories, array $defaults, array $current_categories ) {
        $sanitized = array();
        $used_ids  = array();

        foreach ( array_values( $categories ) as $index => $category ) {
            if ( ! is_array( $category ) ) {
                continue;
            }

            $label       = isset( $category['label'] ) ? sanitize_text_field( $category['label'] ) : '';
            $id          = isset( $category['id'] ) ? sanitize_key( $category['id'] ) : '';
            $original_id = isset( $category['original_id'] ) ? sanitize_key( $category['original_id'] ) : '';
            $legacy_ids  = array();
            $position    = isset( $category['position'] ) ? intval( $category['position'] ) : ( $index + 1 );
            $weight      = isset( $category['weight'] )
                ? Helpers::normalize_category_weight( $category['weight'], 1.0 )
                : 1.0;

            if ( $position < 1 ) {
                $position = $index + 1;
            }

            if ( isset( $category['legacy_ids'] ) && is_array( $category['legacy_ids'] ) ) {
                foreach ( $category['legacy_ids'] as $legacy_id ) {
                    $sanitized_legacy = sanitize_key( $legacy_id );
                    if ( $sanitized_legacy !== '' ) {
                        $legacy_ids[] = $sanitized_legacy;
                    }
                }
            }

            if ( $label === '' ) {
                $label = sprintf( __( 'Catégorie %d', 'notation-jlg' ), $index + 1 );
            }

            if ( $id === '' ) {
                $id = sanitize_key( sanitize_title( $label ) );
            }

            if ( $id === '' && isset( $defaults[ $index ]['id'] ) ) {
                $id = sanitize_key( $defaults[ $index ]['id'] );
            }

            if ( $id === '' ) {
                $id = 'cat' . ( $index + 1 );
            }

            $base_id = $id;
            $suffix  = 2;
            while ( in_array( $id, $used_ids, true ) ) {
                $id = $base_id . '-' . $suffix;
                ++$suffix;
            }

            $used_ids[] = $id;

            if ( $original_id !== '' && $original_id !== $id ) {
                $legacy_ids[] = $original_id;
            }

            if ( empty( $legacy_ids ) && isset( $defaults[ $index ]['legacy_ids'] ) && is_array( $defaults[ $index ]['legacy_ids'] ) ) {
                foreach ( $defaults[ $index ]['legacy_ids'] as $legacy_default ) {
                    $legacy_ids[] = sanitize_key( $legacy_default );
                }
            }

            if ( isset( $current_categories[ $index ]['legacy_ids'] ) && is_array( $current_categories[ $index ]['legacy_ids'] ) ) {
                foreach ( $current_categories[ $index ]['legacy_ids'] as $legacy_current ) {
                    $legacy_ids[] = sanitize_key( $legacy_current );
                }
            }

            $legacy_ids = array_values( array_unique( array_filter( $legacy_ids ) ) );

            $sanitized[] = array(
                'id'         => $id,
                'label'      => $label,
                'legacy_ids' => $legacy_ids,
                'position'   => $position,
                'weight'     => $weight,
            );
        }

        if ( empty( $sanitized ) ) {
            return $defaults;
        }

        usort(
            $sanitized,
            static function ( $a, $b ) {
                $a_position = isset( $a['position'] ) ? (int) $a['position'] : 0;
                $b_position = isset( $b['position'] ) ? (int) $b['position'] : 0;

                if ( $a_position === $b_position ) {
                    return 0;
                }

                return ( $a_position < $b_position ) ? -1 : 1;
            }
        );

        foreach ( $sanitized as $index => &$category ) {
            $category['position'] = $index + 1;
        }
        unset( $category );

        return $sanitized;
    }

    public function render_field( $args ) {
        $type   = $args['type'] ?? 'text';
        $method = $type . '_field';

        if ( method_exists( FormRenderer::class, $method ) ) {
            call_user_func( array( FormRenderer::class, $method ), $args );
        } else {
            // Fallback pour les autres types
            $options = Helpers::get_plugin_options();

            if ( $type === 'rating_categories' ) {
                $this->render_rating_categories_field( $args, $options );

                return;
            }

            if ( $type === 'textarea' ) {
                printf(
                    '<textarea name="%s[%s]" rows="10" cols="50" class="large-text code" placeholder="%s">%s</textarea>',
                    esc_attr( $this->option_name ),
                    esc_attr( $args['id'] ),
                    esc_attr( $args['placeholder'] ?? '' ),
                    esc_textarea( $options[ $args['id'] ] ?? '' )
                );
            } elseif ( $type === 'number' ) {
                $min  = $args['min'] ?? 0;
                $max  = $args['max'] ?? 100;
                $step = $args['step'] ?? 1;
                printf(
                    '<input type="number" class="small-text" name="%s[%s]" value="%s" min="%s" max="%s" step="%s" />',
                    esc_attr( $this->option_name ),
                    esc_attr( $args['id'] ),
                    esc_attr( $options[ $args['id'] ] ?? $min ),
                    esc_attr( $min ),
                    esc_attr( $max ),
                    esc_attr( $step )
                );
            } elseif ( $type === 'select' ) {
                $value = $options[ $args['id'] ] ?? '';
                printf(
                    '<select name="%s[%s]" id="%s">',
                    esc_attr( $this->option_name ),
                    esc_attr( $args['id'] ),
                    esc_attr( $args['id'] )
                );
                foreach ( $args['options'] as $key => $label ) {
                    printf(
                        '<option value="%s"%s>%s</option>',
                        esc_attr( $key ),
                        selected( $value, $key, false ),
                        esc_html( $label )
                    );
                }
                echo '</select>';
            } elseif ( $type === 'checkbox' ) {
                printf(
                    '<input type="checkbox" name="%s[%s]" id="%s" value="1" %s />',
                    esc_attr( $this->option_name ),
                    esc_attr( $args['id'] ),
                    esc_attr( $args['id'] ),
                    checked( 1, $options[ $args['id'] ] ?? 0, false )
                );
            } elseif ( $type === 'checkbox_group' ) {
                FormRenderer::checkbox_group_field( $args );
            } elseif ( $type === 'color' ) {
                $defaults          = Helpers::get_default_settings();
                $field_id          = $args['id'];
                $allow_transparent = ! empty( $args['allow_transparent'] );
                $default_value     = $defaults[ $field_id ] ?? '#000000';
                $current_value     = $options[ $field_id ] ?? $default_value;
                $current_value     = is_string( $current_value ) ? $current_value : ( is_string( $default_value ) ? $default_value : '' );
                $classes           = array( 'wp-color-picker', 'jlg-color-picker' );
                $data_attributes   = array();

                if ( $allow_transparent ) {
                    $classes[]                                 = 'jlg-color-picker--allow-transparent';
                    $data_attributes['data-allow-transparent'] = 'true';
                }

                $default_attr_value                    = is_string( $default_value ) ? $default_value : '';
                $data_attributes['data-default-color'] = $default_attr_value;

                $attributes = '';
                foreach ( $data_attributes as $attribute => $value ) {
                    $attributes .= sprintf( ' %s="%s"', esc_attr( $attribute ), esc_attr( $value ) );
                }

                printf(
                    '<input type="text" class="%s" name="%s[%s]" id="%s" value="%s"%s />',
                    esc_attr( implode( ' ', $classes ) ),
                    esc_attr( $this->option_name ),
                    esc_attr( $field_id ),
                    esc_attr( $field_id ),
                    esc_attr( $current_value ),
                    $attributes
                );
            } else {
                // Type text par défaut
                printf(
                    '<input type="text" class="regular-text" name="%s[%s]" id="%s" value="%s" placeholder="%s" />',
                    esc_attr( $this->option_name ),
                    esc_attr( $args['id'] ),
                    esc_attr( $args['id'] ),
                    esc_attr( $options[ $args['id'] ] ?? '' ),
                    esc_attr( $args['placeholder'] ?? '' )
                );
            }

            if ( isset( $args['desc'] ) ) {
                printf( '<p class="description">%s</p>', wp_kses_post( $args['desc'] ) );
            }
        }
    }

    private function render_rating_categories_field( $args, $options ) {
        unset( $options );

        $field_id       = $args['id'] ?? 'rating_categories';
        $option_name    = $this->option_name;
        $definitions    = Helpers::get_rating_category_definitions();
        $wrapper_id     = $field_id . '_manager';
        $next_index     = count( $definitions );
        $move_up_text   = esc_html__( 'Monter', 'notation-jlg' );
        $move_down_text = esc_html__( 'Descendre', 'notation-jlg' );
        $move_up_aria   = esc_attr__( 'Monter la catégorie', 'notation-jlg' );
        $move_down_aria = esc_attr__( 'Descendre la catégorie', 'notation-jlg' );

        static $styles_printed = false;

        if ( ! $styles_printed ) {
            echo '<style>';
            echo '.jlg-rating-categories__list{display:flex;flex-direction:column;gap:12px;margin-bottom:12px;}';
            echo '.jlg-rating-category{border:1px solid #dcdcde;background:#fff;padding:12px;border-radius:4px;}';
            echo '.jlg-rating-category__grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;}';
            echo '.jlg-rating-category__actions{display:flex;flex-wrap:wrap;align-items:center;justify-content:flex-end;gap:8px;grid-column:1/-1;}';
            echo '.jlg-rating-category__actions .button{margin:0;}';
            echo '.jlg-rating-category__remove{color:#a00;}';
            echo '@media (max-width:782px){.jlg-rating-category__grid{grid-template-columns:1fr;}.jlg-rating-category__actions{justify-content:flex-start;}}';
            echo '</style>';
            $styles_printed = true;
        }

        echo '<div id="' . esc_attr( $wrapper_id ) . '" class="jlg-rating-categories" data-next-index="' . esc_attr( $next_index ) . '">';
        echo '<div class="jlg-rating-categories__list">';

        foreach ( $definitions as $index => $definition ) {
            $label          = isset( $definition['label'] ) ? $definition['label'] : '';
            $id             = isset( $definition['id'] ) ? $definition['id'] : '';
            $position       = isset( $definition['position'] ) ? (int) $definition['position'] : ( $index + 1 );
            $legacy_ids     = isset( $definition['legacy_ids'] ) && is_array( $definition['legacy_ids'] ) ? $definition['legacy_ids'] : array();
            $weight         = isset( $definition['weight'] )
                ? Helpers::normalize_category_weight( $definition['weight'], 1.0 )
                : 1.0;
            $label_field    = sprintf( '%s[%s][%d][label]', $option_name, $field_id, $index );
            $id_field       = sprintf( '%s[%s][%d][id]', $option_name, $field_id, $index );
            $position_field = sprintf( '%s[%s][%d][position]', $option_name, $field_id, $index );
            $weight_field   = sprintf( '%s[%s][%d][weight]', $option_name, $field_id, $index );
            $weight_value   = number_format( $weight, 1, '.', '' );

            echo '<div class="jlg-rating-category" data-index="' . esc_attr( $index ) . '">';
            echo '<div class="jlg-rating-category__grid">';
            echo '<div>';
            echo '<label for="' . esc_attr( $field_id . '_label_' . $index ) . '"><strong>' . esc_html__( 'Libellé', 'notation-jlg' ) . '</strong></label>';
            echo '<input type="text" class="regular-text" id="' . esc_attr( $field_id . '_label_' . $index ) . '" name="' . esc_attr( $label_field ) . '" value="' . esc_attr( $label ) . '" />';
            echo '</div>';
            echo '<div>';
            echo '<label for="' . esc_attr( $field_id . '_id_' . $index ) . '"><strong>' . esc_html__( 'Identifiant', 'notation-jlg' ) . '</strong></label>';
            echo '<input type="text" class="regular-text" id="' . esc_attr( $field_id . '_id_' . $index ) . '" name="' . esc_attr( $id_field ) . '" value="' . esc_attr( $id ) . '" />';
            echo '<p class="description">' . esc_html__( 'Utilisé pour la clé méta (_note_identifiant). Lettres minuscules, chiffres, tirets et soulignés uniquement.', 'notation-jlg' ) . '</p>';
            echo '</div>';
            echo '<div>';
            echo '<label for="' . esc_attr( $field_id . '_weight_' . $index ) . '"><strong>' . esc_html__( 'Pondération', 'notation-jlg' ) . '</strong></label>';
            echo '<input type="number" class="small-text jlg-rating-category__weight" id="' . esc_attr( $field_id . '_weight_' . $index ) . '" name="' . esc_attr( $weight_field ) . '" value="' . esc_attr( $weight_value ) . '" min="0" step="0.1" />';
            echo '<p class="description">' . esc_html__( 'Coefficient utilisé pour la moyenne pondérée.', 'notation-jlg' ) . '</p>';
            echo '</div>';
            echo '<div class="jlg-rating-category__actions">';
            echo '<button type="button" class="button button-secondary jlg-rating-category__move-up" aria-label="' . esc_attr( $move_up_aria ) . '">' . esc_html( $move_up_text ) . '</button>';
            echo '<button type="button" class="button button-secondary jlg-rating-category__move-down" aria-label="' . esc_attr( $move_down_aria ) . '">' . esc_html( $move_down_text ) . '</button>';
            echo '<button type="button" class="button button-link-delete jlg-rating-category__remove">' . esc_html__( 'Supprimer', 'notation-jlg' ) . '</button>';
            echo '</div>';
            echo '</div>';

            echo '<input type="hidden" name="' . esc_attr( sprintf( '%s[%s][%d][original_id]', $option_name, $field_id, $index ) ) . '" value="' . esc_attr( $id ) . '" />';
            echo '<input type="hidden" class="jlg-rating-category__position" name="' . esc_attr( $position_field ) . '" value="' . esc_attr( $position ) . '" />';

            if ( ! empty( $legacy_ids ) ) {
                foreach ( $legacy_ids as $legacy_id ) {
                    echo '<input type="hidden" name="' . esc_attr( sprintf( '%s[%s][%d][legacy_ids][]', $option_name, $field_id, $index ) ) . '" value="' . esc_attr( $legacy_id ) . '" />';
                }
            }

            echo '</div>';
        }

        echo '</div>';
        echo '<button type="button" class="button jlg-rating-categories__add">' . esc_html__( 'Ajouter une catégorie', 'notation-jlg' ) . '</button>';

        if ( isset( $args['desc'] ) ) {
            printf( '<p class="description">%s</p>', wp_kses_post( $args['desc'] ) );
        }

        echo '</div>';

        $template_label  = esc_attr__( 'Libellé', 'notation-jlg' );
        $template_id     = esc_attr__( 'Identifiant', 'notation-jlg' );
        $template_remove = esc_attr__( 'Supprimer', 'notation-jlg' );
        $template_desc   = esc_html__( 'Utilisé pour la clé méta (_note_identifiant). Lettres minuscules, chiffres, tirets et soulignés uniquement.', 'notation-jlg' );

        echo '<template id="' . esc_attr( $wrapper_id ) . '_template">';
        echo '<div class="jlg-rating-category" data-index="__INDEX__">';
        echo '<div class="jlg-rating-category__grid">';
        echo '<div>';
        echo '<label for="' . esc_attr( $field_id . '_label___INDEX__' ) . '"><strong>' . esc_html( $template_label ) . '</strong></label>';
        echo '<input type="text" class="regular-text" id="' . esc_attr( $field_id . '_label___INDEX__' ) . '" name="' . esc_attr( sprintf( '%s[%s][__INDEX__][label]', $option_name, $field_id ) ) . '" value="" />';
        echo '</div>';
        echo '<div>';
        echo '<label for="' . esc_attr( $field_id . '_id___INDEX__' ) . '"><strong>' . esc_html( $template_id ) . '</strong></label>';
        echo '<input type="text" class="regular-text" id="' . esc_attr( $field_id . '_id___INDEX__' ) . '" name="' . esc_attr( sprintf( '%s[%s][__INDEX__][id]', $option_name, $field_id ) ) . '" value="" />';
        echo '<p class="description">' . esc_html( $template_desc ) . '</p>';
        echo '</div>';
        echo '<div>';
        echo '<label for="' . esc_attr( $field_id . '_weight___INDEX__' ) . '"><strong>' . esc_html__( 'Pondération', 'notation-jlg' ) . '</strong></label>';
        echo '<input type="number" class="small-text jlg-rating-category__weight" id="' . esc_attr( $field_id . '_weight___INDEX__' ) . '" name="' . esc_attr( sprintf( '%s[%s][__INDEX__][weight]', $option_name, $field_id ) ) . '" value="1" min="0" step="0.1" />';
        echo '<p class="description">' . esc_html__( 'Coefficient utilisé pour la moyenne pondérée.', 'notation-jlg' ) . '</p>';
        echo '</div>';
        echo '<div class="jlg-rating-category__actions">';
        echo '<button type="button" class="button button-secondary jlg-rating-category__move-up" aria-label="' . esc_attr( $move_up_aria ) . '">' . esc_html( $move_up_text ) . '</button>';
        echo '<button type="button" class="button button-secondary jlg-rating-category__move-down" aria-label="' . esc_attr( $move_down_aria ) . '">' . esc_html( $move_down_text ) . '</button>';
        echo '<button type="button" class="button button-link-delete jlg-rating-category__remove">' . esc_html( $template_remove ) . '</button>';
        echo '</div>';
        echo '</div>';
        echo '<input type="hidden" name="' . esc_attr( sprintf( '%s[%s][__INDEX__][original_id]', $option_name, $field_id ) ) . '" value="" />';
        echo '<input type="hidden" class="jlg-rating-category__position" name="' . esc_attr( sprintf( '%s[%s][__INDEX__][position]', $option_name, $field_id ) ) . '" value="" />';
        echo '</div>';
        echo '</template>';

        echo '<script>';
        echo '(function(){';
        echo 'const container=document.getElementById(' . wp_json_encode( $wrapper_id ) . ');';
        echo 'if(!container){return;}';
        echo 'const list=container.querySelector(".jlg-rating-categories__list");';
        echo 'const template=document.getElementById(' . wp_json_encode( $wrapper_id . '_template' ) . ');';
        echo 'const addButton=container.querySelector(".jlg-rating-categories__add");';
        echo 'let nextIndex=parseInt(container.getAttribute("data-next-index"),10);';
        echo 'if(!Number.isFinite(nextIndex)){nextIndex=list?list.children.length:0;}';
        echo 'function renumberRows(){if(!list){return;}Array.prototype.forEach.call(list.children,function(row,index){row.setAttribute("data-index",String(index));row.querySelectorAll("[name]").forEach(function(element){if(typeof element.name!=="string"){return;}element.name=element.name.replace(/\[\d+\]/g,"["+index+"]");});row.querySelectorAll("[id]").forEach(function(element){if(typeof element.id!=="string"||!/_\d+$/.test(element.id)){return;}element.id=element.id.replace(/_\d+$/,"_"+index);} );row.querySelectorAll("label[for]").forEach(function(label){if(typeof label.htmlFor!=="string"||!/_\d+$/.test(label.htmlFor)){return;}label.htmlFor=label.htmlFor.replace(/_\d+$/,"_"+index);} );const position=row.querySelector(".jlg-rating-category__position");if(position){position.value=String(index+1);}});nextIndex=list.children.length;container.setAttribute("data-next-index",String(nextIndex));}';
        echo 'function bindRow(row){if(!row){return;}const remove=row.querySelector(".jlg-rating-category__remove");if(remove){remove.addEventListener("click",function(event){event.preventDefault();row.remove();renumberRows();});}const moveUp=row.querySelector(".jlg-rating-category__move-up");if(moveUp&&list){moveUp.addEventListener("click",function(event){event.preventDefault();const previous=row.previousElementSibling;if(previous){list.insertBefore(row,previous);}renumberRows();});}const moveDown=row.querySelector(".jlg-rating-category__move-down");if(moveDown&&list){moveDown.addEventListener("click",function(event){event.preventDefault();const next=row.nextElementSibling;if(next){list.insertBefore(next,row);}renumberRows();});}}';
        echo 'if(list){Array.prototype.forEach.call(list.children,bindRow);renumberRows();}';
        echo 'if(addButton&&template&&list){addButton.addEventListener("click",function(event){event.preventDefault();const fragment=template.content.cloneNode(true);const row=fragment.querySelector(".jlg-rating-category");const index=nextIndex++;if(!row){return;}row.setAttribute("data-index",String(index));fragment.querySelectorAll("[name]").forEach(function(element){element.name=element.name.replace(/__INDEX__/g,index);});fragment.querySelectorAll("[id]").forEach(function(element){element.id=element.id.replace(/__INDEX__/g,index);});fragment.querySelectorAll("label[for]").forEach(function(label){label.htmlFor=label.htmlFor.replace(/__INDEX__/g,index);});list.appendChild(fragment);const appendedRow=list.lastElementChild;if(!appendedRow){return;}bindRow(appendedRow);const weightInput=appendedRow.querySelector(".jlg-rating-category__weight");if(weightInput&&weightInput.value===""){weightInput.value="1";}renumberRows();});}';
        echo '})();';
        echo '</script>';
    }

    public function render_debug_info() {
        $options = get_option( $this->option_name );
        if ( ! empty( $options['debug_mode_enabled'] ) ) {
            echo '<details style="background:#f5f5f5; padding:10px; border:1px solid #ccc; border-radius:4px;">';
            echo '<summary style="cursor:pointer; font-weight:bold;">Voir les valeurs des options Glow/Neon</summary>';
            echo '<pre style="font-size:11px; overflow:auto;">';
            echo 'text_glow_enabled: ' . ( isset( $options['text_glow_enabled'] ) ? $options['text_glow_enabled'] : 'not set' ) . "\n";
            echo 'text_glow_color_mode: ' . ( isset( $options['text_glow_color_mode'] ) ? $options['text_glow_color_mode'] : 'not set' ) . "\n";
            echo 'text_glow_custom_color: ' . ( isset( $options['text_glow_custom_color'] ) ? $options['text_glow_custom_color'] : 'not set' ) . "\n";
            echo "\n";
            echo 'circle_glow_enabled: ' . ( isset( $options['circle_glow_enabled'] ) ? $options['circle_glow_enabled'] : 'not set' ) . "\n";
            echo 'circle_glow_color_mode: ' . ( isset( $options['circle_glow_color_mode'] ) ? $options['circle_glow_color_mode'] : 'not set' ) . "\n";
            echo 'circle_glow_custom_color: ' . ( isset( $options['circle_glow_custom_color'] ) ? $options['circle_glow_custom_color'] : 'not set' ) . "\n";
            echo "\n";
            echo 'color_low: ' . ( isset( $options['color_low'] ) ? $options['color_low'] : 'not set' ) . "\n";
            echo 'color_mid: ' . ( isset( $options['color_mid'] ) ? $options['color_mid'] : 'not set' ) . "\n";
            echo 'color_high: ' . ( isset( $options['color_high'] ) ? $options['color_high'] : 'not set' ) . "\n";
            echo '</pre>';
            echo '</details>';
        }
    }
}

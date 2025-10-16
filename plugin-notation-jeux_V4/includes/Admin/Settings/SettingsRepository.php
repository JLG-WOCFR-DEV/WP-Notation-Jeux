<?php

namespace JLG\Notation\Admin\Settings;

use JLG\Notation\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SettingsRepository {
    public const USER_META_KEY = '_jlg_settings_view_mode';

    public const MODE_SIMPLE = 'simple';
    public const MODE_EXPERT = 'expert';

    private const DEFAULT_MODE = self::MODE_EXPERT;

    private const SIMPLE_SECTIONS = array(
        'jlg_layout',
        'jlg_colors',
        'jlg_glow_text',
        'jlg_glow_circle',
        'jlg_modules',
        'jlg_tagline_section',
        'jlg_user_rating_section',
        'jlg_table',
        'jlg_thumbnail_section',
    );

    private const SIMPLE_OPTION_WHITELIST = array(
        'visual_theme',
        'visual_preset',
        'score_layout',
        'score_max',
        'enable_animations',
        'circle_dynamic_bg_enabled',
        'circle_border_enabled',
        'circle_border_width',
        'circle_border_color',
        'text_glow_enabled',
        'text_glow_color_mode',
        'text_glow_custom_color',
        'text_glow_intensity',
        'text_glow_pulse',
        'text_glow_speed',
        'circle_glow_enabled',
        'circle_glow_color_mode',
        'circle_glow_custom_color',
        'circle_glow_intensity',
        'circle_glow_pulse',
        'circle_glow_speed',
        'dark_bg_color',
        'dark_bg_color_secondary',
        'dark_border_color',
        'dark_text_color',
        'dark_text_color_secondary',
        'light_bg_color',
        'light_bg_color_secondary',
        'light_border_color',
        'light_text_color',
        'light_text_color_secondary',
        'score_gradient_1',
        'score_gradient_2',
        'color_low',
        'color_mid',
        'color_high',
        'tagline_enabled',
        'tagline_font_size',
        'tagline_bg_color',
        'tagline_text_color',
        'rating_badge_enabled',
        'rating_badge_threshold',
        'user_rating_enabled',
        'user_rating_requires_login',
        'table_zebra_striping',
        'table_border_style',
        'table_border_width',
        'table_header_bg_color',
        'table_header_text_color',
        'table_row_bg_color',
        'table_row_text_color',
        'table_zebra_bg_color',
        'thumb_text_color',
        'thumb_font_size',
        'thumb_padding',
        'thumb_border_radius',
    );

    /**
     * Declarative schema describing every persisted option.
     *
     * Each entry defines the field type as well as the constraints used during
     * sanitization. Complex fields can also expose callbacks that will be
     * executed after the generic normalization logic.
     *
     * @var array<string, array<string, mixed>>
     */
    private const BASE_OPTION_SCHEMA = array(
        'visual_theme'                        => array(
            'type'    => 'enum',
            'choices' => array( 'dark', 'light' ),
        ),
        'visual_preset'                       => array(
            'type'    => 'enum',
            'choices' => array( 'signature', 'minimal', 'editorial' ),
        ),
        'score_layout'                        => array(
            'type'    => 'enum',
            'choices' => array( 'text', 'circle' ),
        ),
        'score_max'                           => array(
            'type'        => 'number',
            'min'         => 5,
            'max'         => 100,
            'step'        => 1,
            'cast'        => 'int',
            'postProcess' => array( 'schedule_score_scale_migration' ),
        ),
        'enable_animations'                   => array(
            'type' => 'checkbox',
        ),
        'allowed_post_types'                  => array(
            'type'        => 'post_types',
            'postProcess' => array(),
        ),
        'tagline_font_size'                   => array(
            'type' => 'number',
            'min'  => 12,
            'max'  => 32,
            'step' => 1,
            'cast' => 'int',
        ),
        'rating_badge_enabled'                => array(
            'type' => 'checkbox',
        ),
        'rating_badge_threshold'              => array(
            'type'        => 'number',
            'min'         => 0,
            'step'        => 0.1,
            'postProcess' => array( 'clamp_rating_badge_threshold' ),
        ),
        'review_status_enabled'               => array(
            'type' => 'checkbox',
        ),
        'review_status_auto_finalize_enabled' => array(
            'type'    => 'checkbox',
            'default' => 0,
        ),
        'review_status_auto_finalize_days'    => array(
            'type'    => 'number',
            'min'     => 1,
            'max'     => 60,
            'step'    => 1,
            'cast'    => 'int',
            'default' => 7,
        ),
        'verdict_module_enabled'              => array(
            'type' => 'checkbox',
        ),
        'related_guides_enabled'              => array(
            'type' => 'checkbox',
        ),
        'related_guides_limit'                => array(
            'type' => 'number',
            'min'  => 1,
            'max'  => 6,
            'step' => 1,
            'cast' => 'int',
        ),
        'related_guides_taxonomies'           => array(
            'type' => 'csv',
        ),
        'deals_enabled'                       => array(
            'type' => 'checkbox',
        ),
        'deals_limit'                         => array(
            'type' => 'number',
            'min'  => 1,
            'max'  => 6,
            'step' => 1,
            'cast' => 'int',
        ),
        'deals_button_rel'                    => array(
            'type' => 'text',
        ),
        'deals_disclaimer'                    => array(
            'type' => 'text',
        ),
        'dark_bg_color'                       => array(
            'type' => 'color',
        ),
        'dark_bg_color_secondary'             => array(
            'type' => 'color',
        ),
        'dark_border_color'                   => array(
            'type' => 'color',
        ),
        'dark_text_color'                     => array(
            'type' => 'color',
        ),
        'dark_text_color_secondary'           => array(
            'type' => 'color',
        ),
        'tagline_bg_color'                    => array(
            'type' => 'color',
        ),
        'tagline_text_color'                  => array(
            'type' => 'color',
        ),
        'light_bg_color'                      => array(
            'type' => 'color',
        ),
        'light_bg_color_secondary'            => array(
            'type' => 'color',
        ),
        'light_border_color'                  => array(
            'type' => 'color',
        ),
        'light_text_color'                    => array(
            'type' => 'color',
        ),
        'light_text_color_secondary'          => array(
            'type' => 'color',
        ),
        'score_gradient_1'                    => array(
            'type' => 'color',
        ),
        'score_gradient_2'                    => array(
            'type' => 'color',
        ),
        'color_low'                           => array(
            'type' => 'color',
        ),
        'color_mid'                           => array(
            'type' => 'color',
        ),
        'color_high'                          => array(
            'type' => 'color',
        ),
        'user_rating_star_color'              => array(
            'type' => 'color',
        ),
        'user_rating_text_color'              => array(
            'type' => 'color',
        ),
        'user_rating_title_color'             => array(
            'type' => 'color',
        ),
        'circle_dynamic_bg_enabled'           => array(
            'type' => 'checkbox',
        ),
        'circle_border_enabled'               => array(
            'type' => 'checkbox',
        ),
        'circle_border_width'                 => array(
            'type' => 'number',
            'min'  => 1,
            'max'  => 20,
            'step' => 1,
            'cast' => 'int',
        ),
        'circle_border_color'                 => array(
            'type' => 'color',
        ),
        'text_glow_enabled'                   => array(
            'type' => 'checkbox',
        ),
        'text_glow_color_mode'                => array(
            'type'    => 'enum',
            'choices' => array( 'dynamic', 'custom' ),
        ),
        'text_glow_custom_color'              => array(
            'type' => 'color',
        ),
        'text_glow_intensity'                 => array(
            'type' => 'number',
            'min'  => 5,
            'max'  => 50,
            'step' => 1,
            'cast' => 'int',
        ),
        'text_glow_pulse'                     => array(
            'type' => 'checkbox',
        ),
        'text_glow_speed'                     => array(
            'type' => 'number',
            'min'  => 0.5,
            'max'  => 10,
            'step' => 0.1,
        ),
        'circle_glow_enabled'                 => array(
            'type' => 'checkbox',
        ),
        'circle_glow_color_mode'              => array(
            'type'    => 'enum',
            'choices' => array( 'dynamic', 'custom' ),
        ),
        'circle_glow_custom_color'            => array(
            'type' => 'color',
        ),
        'circle_glow_intensity'               => array(
            'type' => 'number',
            'min'  => 5,
            'max'  => 50,
            'step' => 1,
            'cast' => 'int',
        ),
        'circle_glow_pulse'                   => array(
            'type' => 'checkbox',
        ),
        'circle_glow_speed'                   => array(
            'type' => 'number',
            'min'  => 0.5,
            'max'  => 10,
            'step' => 0.1,
        ),
        'tagline_enabled'                     => array(
            'type' => 'checkbox',
        ),
        'user_rating_enabled'                 => array(
            'type' => 'checkbox',
        ),
        'user_rating_requires_login'          => array(
            'type' => 'checkbox',
        ),
        'user_rating_weighting_enabled'       => array(
            'type' => 'checkbox',
        ),
        'user_rating_guest_weight_start'      => array(
            'type' => 'number',
            'min'  => 0,
            'max'  => 1,
            'step' => 0.1,
        ),
        'user_rating_guest_weight_increment'  => array(
            'type' => 'number',
            'min'  => 0,
            'max'  => 1,
            'step' => 0.01,
        ),
        'user_rating_guest_weight_max'        => array(
            'type' => 'number',
            'min'  => 0,
            'max'  => 1,
            'step' => 0.1,
        ),
        'table_zebra_striping'                => array(
            'type' => 'checkbox',
        ),
        'table_border_style'                  => array(
            'type'    => 'enum',
            'choices' => array( 'none', 'horizontal', 'full' ),
        ),
        'table_border_width'                  => array(
            'type' => 'number',
            'min'  => 0,
            'max'  => 10,
            'step' => 1,
            'cast' => 'int',
        ),
        'table_header_bg_color'               => array(
            'type' => 'color',
        ),
        'table_header_text_color'             => array(
            'type' => 'color',
        ),
        'table_row_bg_color'                  => array(
            'type'              => 'color',
            'allow_transparent' => true,
        ),
        'table_row_text_color'                => array(
            'type' => 'color',
        ),
        'table_zebra_bg_color'                => array(
            'type'              => 'color',
            'allow_transparent' => true,
        ),
        'thumb_text_color'                    => array(
            'type' => 'color',
        ),
        'thumb_font_size'                     => array(
            'type' => 'number',
            'min'  => 10,
            'max'  => 24,
            'step' => 1,
            'cast' => 'int',
        ),
        'thumb_padding'                       => array(
            'type' => 'number',
            'min'  => 2,
            'max'  => 20,
            'step' => 1,
            'cast' => 'int',
        ),
        'thumb_border_radius'                 => array(
            'type' => 'number',
            'min'  => 0,
            'max'  => 50,
            'step' => 1,
            'cast' => 'int',
        ),
        'game_explorer_columns'               => array(
            'type' => 'number',
            'min'  => 2,
            'max'  => 4,
            'step' => 1,
            'cast' => 'int',
        ),
        'game_explorer_posts_per_page'        => array(
            'type' => 'number',
            'min'  => 6,
            'max'  => 36,
            'step' => 1,
            'cast' => 'int',
        ),
        'game_explorer_filters'               => array(
            'type' => 'game_explorer_filters',
        ),
        'game_explorer_score_position'        => array(
            'type'              => 'enum',
            'choices_callback'  => array( Helpers::class, 'get_game_explorer_score_positions' ),
            'sanitize_callback' => array( Helpers::class, 'normalize_game_explorer_score_position' ),
        ),
        'rating_categories'                   => array(
            'type' => 'rating_categories',
        ),
        'custom_css'                          => array(
            'type' => 'css',
        ),
        'seo_schema_enabled'                  => array(
            'type' => 'checkbox',
        ),
        'debug_mode_enabled'                  => array(
            'type' => 'checkbox',
        ),
        'rawg_api_key'                        => array(
            'type' => 'text',
        ),
    );

    /**
     * Cached representation of the expanded option schema.
     *
     * @var array<string, array<string, mixed>>|null
     */
    private static $option_schema = null;

    /**
     * Returns the declarative option schema used during sanitization.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function get_option_schema() {
        if ( self::$option_schema !== null ) {
            return self::$option_schema;
        }

        $schema = self::BASE_OPTION_SCHEMA;

        foreach ( $schema as $field => &$definition ) {
            if ( isset( $definition['choices_callback'] ) && is_callable( $definition['choices_callback'] ) ) {
                $choices = call_user_func( $definition['choices_callback'] );

                if ( is_array( $choices ) ) {
                    $definition['choices'] = array_values( array_map( 'sanitize_key', $choices ) );
                }

                unset( $definition['choices_callback'] );
            }
        }

        unset( $definition );

        self::$option_schema = $schema;

        return self::$option_schema;
    }

    /**
     * Returns the list of available modes with labels and description keys.
     *
     * @return array<string, array<string, string>>
     */
    public function get_modes() {
        return array(
            self::MODE_SIMPLE => array(
                'label'       => _x( 'Mode simple', 'Settings view mode label', 'notation-jlg' ),
                'description' => _x( 'Affiche les réglages essentiels pour une configuration rapide.', 'Settings view mode description', 'notation-jlg' ),
            ),
            self::MODE_EXPERT => array(
                'label'       => _x( 'Mode expert', 'Settings view mode label', 'notation-jlg' ),
                'description' => _x( 'Expose l’intégralité des sections et options avancées.', 'Settings view mode description', 'notation-jlg' ),
            ),
        );
    }

    /**
     * Normalize the provided mode ensuring it falls back to the default one.
     *
     * @param string|null $mode Raw mode value.
     *
     * @return string
     */
    public static function normalize_mode( $mode ) {
        $mode = sanitize_key( (string) $mode );

        if ( in_array( $mode, array( self::MODE_SIMPLE, self::MODE_EXPERT ), true ) ) {
            return $mode;
        }

        return self::DEFAULT_MODE;
    }

    /**
     * Returns the default mode.
     *
     * @return string
     */
    public static function get_default_mode() {
        return self::DEFAULT_MODE;
    }

    /**
     * Returns the persisted mode for the specified user.
     *
     * @param int|null $user_id Optional user identifier. Defaults to current user.
     *
     * @return string
     */
    public function get_user_mode( $user_id = null ) {
        if ( $user_id === null ) {
            $user_id = (int) \get_current_user_id();
        }

        if ( $user_id <= 0 ) {
            return self::DEFAULT_MODE;
        }

        $stored = \get_user_meta( $user_id, self::USER_META_KEY, true );

        if ( $stored === '' || $stored === null ) {
            return self::DEFAULT_MODE;
        }

        return self::normalize_mode( $stored );
    }

    /**
     * Persist the selected mode for the specified user.
     *
     * @param string   $mode    Requested mode.
     * @param int|null $user_id Optional user identifier.
     *
     * @return bool
     */
    public function set_user_mode( $mode, $user_id = null ) {
        $mode = self::normalize_mode( $mode );

        if ( $user_id === null ) {
            $user_id = (int) \get_current_user_id();
        }

        if ( $user_id <= 0 ) {
            return false;
        }

        return (bool) \update_user_meta( $user_id, self::USER_META_KEY, $mode );
    }

    /**
     * Builds the panels payload listing the sections attached to each mode.
     *
     * @param array<int, array<string, mixed>> $sections All registered sections.
     *
     * @return array<string, array<string, mixed>>
     */
    public function build_panels_payload( array $sections ) {
        $normalized_sections = array();

        foreach ( $sections as $section ) {
            $section_id = sanitize_key( $section['id'] ?? '' );

            if ( $section_id === '' ) {
                continue;
            }

            $normalized_sections[ $section_id ] = array(
                'id'      => $section_id,
                'title'   => isset( $section['title'] ) ? (string) $section['title'] : '',
                'icon'    => isset( $section['icon'] ) ? (string) $section['icon'] : '',
                'summary' => isset( $section['summary'] ) ? (string) $section['summary'] : '',
            );
        }

        $simple_ids = self::SIMPLE_SECTIONS;
        $all_ids    = array_keys( $normalized_sections );

        $modes = $this->get_modes();

        return array(
            self::MODE_SIMPLE => array(
                'mode'     => self::MODE_SIMPLE,
                'label'    => $modes[ self::MODE_SIMPLE ]['label'],
                'sections' => $this->filter_sections( $normalized_sections, $simple_ids ),
            ),
            self::MODE_EXPERT => array(
                'mode'     => self::MODE_EXPERT,
                'label'    => $modes[ self::MODE_EXPERT ]['label'],
                'sections' => $this->filter_sections( $normalized_sections, $all_ids ),
            ),
        );
    }

    /**
     * Serialize options for the given mode using a whitelist approach in simple mode.
     *
     * @param string               $mode    Requested mode.
     * @param array<string, mixed> $options Optional options payload.
     *
     * @return array<string, mixed>
     */
    public function serialize_options_for_mode( $mode, ?array $options = null ) {
        $mode    = self::normalize_mode( $mode );
        $options = $options ?? Helpers::get_plugin_options();

        if ( $mode !== self::MODE_SIMPLE ) {
            return $options;
        }

        $whitelist = array_fill_keys( self::SIMPLE_OPTION_WHITELIST, true );

        return array_intersect_key( $options, $whitelist );
    }

    /**
     * Filters the normalized sections using the provided allowed identifiers.
     *
     * @param array<string, array<string, string>> $sections Registered section payload.
     * @param array<int, string>                   $allowed  Allowed identifiers.
     *
     * @return array<int, array<string, string>>
     */
    private function filter_sections( array $sections, array $allowed ) {
        $allowed_map = array();

        foreach ( $allowed as $identifier ) {
            $key = sanitize_key( $identifier );

            if ( $key === '' ) {
                continue;
            }

            $allowed_map[ $key ] = true;
        }

        $filtered = array();

        foreach ( $sections as $identifier => $section ) {
            if ( isset( $allowed_map[ $identifier ] ) ) {
                $filtered[] = $section;
            }
        }

        return $filtered;
    }
}

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
     * Returns the sanitization schema describing each settings field.
     *
     * @return array<string, array<string, mixed>>
     */
    public function get_sanitization_schema() {
        $schema = array(
            'visual_theme'                     => array(
                'type'    => 'select',
                'choices' => array( 'dark', 'light' ),
            ),
            'visual_preset'                    => array(
                'type'    => 'select',
                'choices' => array( 'signature', 'minimal', 'editorial' ),
            ),
            'score_layout'                     => array(
                'type'    => 'select',
                'choices' => array( 'text', 'circle' ),
            ),
            'text_glow_color_mode'             => array(
                'type'    => 'select',
                'choices' => array( 'dynamic', 'custom' ),
            ),
            'circle_glow_color_mode'           => array(
                'type'    => 'select',
                'choices' => array( 'dynamic', 'custom' ),
            ),
            'table_border_style'               => array(
                'type'    => 'select',
                'choices' => array( 'none', 'horizontal', 'full' ),
            ),
            'game_explorer_score_position'     => array(
                'type'    => 'select',
                'choices' => Helpers::get_game_explorer_score_positions(),
            ),
            'allowed_post_types'               => array(
                'type'              => 'custom',
                'sanitize_callback' => 'allowed_post_types',
                'fallback'          => 'current_or_default',
            ),
            'game_explorer_filters'            => array(
                'type'              => 'custom',
                'sanitize_callback' => 'game_explorer_filters',
                'fallback'          => 'current',
            ),
            'rating_categories'                => array(
                'type'              => 'custom',
                'sanitize_callback' => 'rating_categories',
            ),
            'related_guides_taxonomies'        => array(
                'type' => 'csv',
            ),
            'custom_css'                       => array(
                'type' => 'css',
            ),
            'deals_button_rel'                 => array(
                'type' => 'text',
            ),
            'deals_disclaimer'                 => array(
                'type' => 'text',
            ),
            'rawg_api_key'                     => array(
                'type' => 'text',
            ),
            'score_max'                        => array(
                'type'         => 'number',
                'post_process' => array( 'score_scale_migration' ),
            ),
            'rating_badge_threshold'           => array(
                'type'         => 'number',
                'post_process' => array( 'clamp_rating_badge_threshold' ),
            ),
            'review_status_auto_finalize_days' => array(
                'type'    => 'number',
                'default' => 7,
            ),
        );

        $boolean_fields = array(
            'enable_animations',
            'circle_dynamic_bg_enabled',
            'circle_border_enabled',
            'text_glow_enabled',
            'text_glow_pulse',
            'circle_glow_enabled',
            'circle_glow_pulse',
            'tagline_enabled',
            'user_rating_enabled',
            'user_rating_requires_login',
            'user_rating_weighting_enabled',
            'table_zebra_striping',
            'rating_badge_enabled',
            'review_status_enabled',
            'review_status_auto_finalize_enabled',
            'verdict_module_enabled',
            'related_guides_enabled',
            'deals_enabled',
            'seo_schema_enabled',
            'debug_mode_enabled',
        );

        foreach ( $boolean_fields as $field ) {
            $schema[ $field ] = array(
                'type'               => 'boolean',
                'default_if_missing' => 0,
            );
        }

        $color_fields = array(
            'dark_bg_color',
            'dark_bg_color_secondary',
            'dark_border_color',
            'dark_text_color',
            'dark_text_color_secondary',
            'tagline_bg_color',
            'tagline_text_color',
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
            'user_rating_star_color',
            'user_rating_text_color',
            'user_rating_title_color',
            'circle_border_color',
            'text_glow_custom_color',
            'circle_glow_custom_color',
            'table_header_bg_color',
            'table_header_text_color',
            'table_row_bg_color',
            'table_row_text_color',
            'table_zebra_bg_color',
            'thumb_text_color',
        );

        foreach ( $color_fields as $field ) {
            $schema[ $field ] = array(
                'type' => 'color',
            );
        }

        foreach ( array( 'table_row_bg_color', 'table_zebra_bg_color' ) as $field ) {
            $schema[ $field ]['allow_transparent'] = true;
        }

        $number_fields = array(
            'circle_border_width',
            'text_glow_intensity',
            'text_glow_speed',
            'circle_glow_intensity',
            'circle_glow_speed',
            'tagline_font_size',
            'table_border_width',
            'thumb_font_size',
            'thumb_padding',
            'thumb_border_radius',
            'game_explorer_columns',
            'game_explorer_posts_per_page',
            'related_guides_limit',
            'deals_limit',
            'user_rating_guest_weight_start',
            'user_rating_guest_weight_increment',
            'user_rating_guest_weight_max',
        );

        foreach ( $number_fields as $field ) {
            $schema[ $field ] = array(
                'type' => 'number',
            );
        }

        return $schema;
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

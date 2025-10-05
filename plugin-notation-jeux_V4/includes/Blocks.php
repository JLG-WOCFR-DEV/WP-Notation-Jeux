<?php

namespace JLG\Notation;

use JLG\Notation\Helpers;
use JLG\Notation\Frontend;
use JLG\Notation\Utils\Validator;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Blocks {
    /**
     * Handle used for the shared utilities script.
     *
     * @var string
     */
    private $shared_script_handle = 'notation-jlg-blocks-shared';

    /**
     * Handle used for the editor stylesheet shared by blocks.
     *
     * @var string
     */
    private $editor_style_handle = 'notation-jlg-block-editor';

    /**
     * Path to the languages directory.
     *
     * @var string
     */
    private $languages_path;

    /**
     * List of dynamic blocks managed by the plugin.
     *
     * @var array<string, array<string, mixed>>
     */
    private $blocks = array(
        'rating-block'    => array(
            'name'      => 'notation-jlg/rating-block',
            'shortcode' => 'bloc_notation_jeu',
            'script'    => 'notation-jlg-rating-block-editor',
            'callback'  => 'render_rating_block',
            'deps'      => array( 'notation-jlg-rating-meta-utils' ),
        ),
        'pros-cons'       => array(
            'name'      => 'notation-jlg/pros-cons',
            'shortcode' => 'jlg_points_forts_faibles',
            'script'    => 'notation-jlg-pros-cons-editor',
            'callback'  => 'render_pros_cons_block',
        ),
        'tagline'         => array(
            'name'      => 'notation-jlg/tagline',
            'shortcode' => 'tagline_notation_jlg',
            'script'    => 'notation-jlg-tagline-editor',
            'callback'  => 'render_tagline_block',
        ),
        'game-info'       => array(
            'name'      => 'notation-jlg/game-info',
            'shortcode' => 'jlg_fiche_technique',
            'script'    => 'notation-jlg-game-info-editor',
            'callback'  => 'render_game_info_block',
        ),
        'user-rating'     => array(
            'name'      => 'notation-jlg/user-rating',
            'shortcode' => 'notation_utilisateurs_jlg',
            'script'    => 'notation-jlg-user-rating-editor',
            'callback'  => 'render_user_rating_block',
        ),
        'summary-display' => array(
            'name'      => 'notation-jlg/summary-display',
            'shortcode' => 'jlg_tableau_recap',
            'script'    => 'notation-jlg-summary-display-editor',
            'callback'  => 'render_summary_display_block',
        ),
        'all-in-one'      => array(
            'name'      => 'notation-jlg/all-in-one',
            'shortcode' => 'jlg_bloc_complet',
            'script'    => 'notation-jlg-all-in-one-editor',
            'callback'  => 'render_all_in_one_block',
        ),
        'game-explorer'   => array(
            'name'      => 'notation-jlg/game-explorer',
            'shortcode' => 'jlg_game_explorer',
            'script'    => 'notation-jlg-game-explorer-editor',
            'callback'  => 'render_game_explorer_block',
        ),
        'score-insights'  => array(
            'name'      => 'notation-jlg/score-insights',
            'shortcode' => 'jlg_score_insights',
            'script'    => 'notation-jlg-score-insights-editor',
            'callback'  => 'render_score_insights_block',
        ),
    );

    public function __construct() {
        $this->languages_path = trailingslashit( JLG_NOTATION_PLUGIN_DIR ) . 'languages';

        add_action( 'init', array( $this, 'register_block_editor_assets' ) );
        add_action( 'init', array( $this, 'register_blocks' ) );
        add_action( 'init', array( $this, 'register_rating_meta' ) );
        add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
    }

    public function register_block_editor_assets() {
        $scripts_dir = trailingslashit( JLG_NOTATION_PLUGIN_DIR ) . 'assets/js/blocks/';
        $scripts_url = trailingslashit( JLG_NOTATION_PLUGIN_URL ) . 'assets/js/blocks/';
        $style_path  = trailingslashit( JLG_NOTATION_PLUGIN_DIR ) . 'assets/css/blocks-editor.css';
        $style_url   = trailingslashit( JLG_NOTATION_PLUGIN_URL ) . 'assets/css/blocks-editor.css';

        if ( file_exists( $style_path ) ) {
            wp_register_style(
                $this->editor_style_handle,
                $style_url,
                array( 'wp-edit-blocks' ),
                $this->get_file_version( $style_path )
            );
        }

        $shared_script_path = $scripts_dir . 'shared.js';
        if ( file_exists( $shared_script_path ) ) {
            $shared_deps = array(
                'wp-blocks',
                'wp-components',
                'wp-element',
                'wp-i18n',
                'wp-block-editor',
                'wp-data',
                'wp-html-entities',
                'wp-server-side-render',
                'wp-compose',
            );

            wp_register_script(
                $this->shared_script_handle,
                $scripts_url . 'shared.js',
                $shared_deps,
                $this->get_file_version( $shared_script_path ),
                true
            );

            $settings = array(
                'allowedPostTypes'  => $this->get_allowed_post_types_for_editor(),
                'postsQueryPerPage' => 20,
            );

            wp_localize_script( $this->shared_script_handle, 'jlgBlockEditorSettings', $settings );
            $this->set_script_translations( $this->shared_script_handle );
        }

        $rating_utils_handle = 'notation-jlg-rating-meta-utils';
        $rating_utils_path   = $scripts_dir . 'rating-meta-utils.js';

        if ( file_exists( $rating_utils_path ) ) {
            wp_register_script(
                $rating_utils_handle,
                $scripts_url . 'rating-meta-utils.js',
                array(),
                $this->get_file_version( $rating_utils_path ),
                true
            );
        }

        foreach ( $this->blocks as $slug => $config ) {
            $script_handle = isset( $config['script'] ) ? $config['script'] : '';
            $script_file   = $scripts_dir . $slug . '.js';

            if ( $script_handle === '' || ! file_exists( $script_file ) ) {
                continue;
            }

            $deps = array( $this->shared_script_handle );

            if ( ! empty( $config['deps'] ) && is_array( $config['deps'] ) ) {
                foreach ( $config['deps'] as $additional_dep ) {
                    if ( is_string( $additional_dep ) && $additional_dep !== '' ) {
                        $deps[] = $additional_dep;
                    }
                }
            }

            wp_register_script(
                $script_handle,
                $scripts_url . $slug . '.js',
                $deps,
                $this->get_file_version( $script_file ),
                true
            );

            $this->set_script_translations( $script_handle );

            if ( $slug === 'rating-block' ) {
                $this->localize_rating_block_settings( $script_handle );
            }
        }
    }

    private function localize_rating_block_settings( $script_handle ) {
        $definitions = Helpers::get_rating_category_definitions();
        $categories  = array();

        foreach ( $definitions as $definition ) {
            $meta_key = isset( $definition['meta_key'] ) ? (string) $definition['meta_key'] : '';

            if ( $meta_key === '' ) {
                continue;
            }

            $categories[] = array(
                'id'      => isset( $definition['id'] ) ? (string) $definition['id'] : $meta_key,
                'label'   => isset( $definition['label'] ) ? (string) $definition['label'] : $meta_key,
                'metaKey' => $meta_key,
            );
        }

        $score_max = Helpers::get_score_max();
        if ( ! is_numeric( $score_max ) || (float) $score_max <= 0 ) {
            $score_max = 10.0;
        }

        $badge_options = array(
            array(
                'value' => 'auto',
                'label' => __( 'Automatique (seuil global)', 'notation-jlg' ),
            ),
            array(
                'value' => 'force-on',
                'label' => __( 'Toujours afficher', 'notation-jlg' ),
            ),
            array(
                'value' => 'force-off',
                'label' => __( 'Toujours masquer', 'notation-jlg' ),
            ),
        );

        $settings = array(
            'scoreMax'             => (float) $score_max,
            'categoryDefinitions'  => $categories,
            'badgeOverrideMetaKey' => '_jlg_rating_badge_override',
            'badgeOptions'         => $badge_options,
        );

        wp_localize_script( $script_handle, 'jlgRatingBlockSettings', $settings );
    }

    public function register_rating_meta() {
        if ( ! function_exists( 'register_post_meta' ) ) {
            return;
        }

        $post_types = Helpers::get_allowed_post_types();
        if ( empty( $post_types ) || ! is_array( $post_types ) ) {
            $post_types = array( 'post' );
        }

        $definitions = Helpers::get_rating_category_definitions();

        foreach ( $post_types as $post_type ) {
            $post_type = sanitize_key( $post_type );

            if ( $post_type === '' ) {
                continue;
            }

            foreach ( $definitions as $definition ) {
                $meta_key = isset( $definition['meta_key'] ) ? (string) $definition['meta_key'] : '';

                if ( $meta_key === '' ) {
                    continue;
                }

                register_post_meta(
                    $post_type,
                    $meta_key,
                    array(
                        'single'            => true,
                        'type'              => 'number',
                        'show_in_rest'      => array(
                            'schema' => array(
                                'type'    => 'number',
                                'context' => array( 'edit' ),
                            ),
                        ),
                        'sanitize_callback' => array( $this, 'sanitize_category_score_meta' ),
                        'auth_callback'     => array( $this, 'can_edit_registered_meta' ),
                    )
                );
            }

            register_post_meta(
                $post_type,
                '_jlg_rating_badge_override',
                array(
                    'single'            => true,
                    'type'              => 'string',
                    'show_in_rest'      => array(
                        'schema' => array(
                            'type'    => 'string',
                            'enum'    => array( 'auto', 'force-on', 'force-off' ),
                            'context' => array( 'edit' ),
                        ),
                    ),
                    'sanitize_callback' => array( $this, 'sanitize_badge_override_meta' ),
                    'auth_callback'     => array( $this, 'can_edit_registered_meta' ),
                )
            );
        }
    }

    public function sanitize_category_score_meta( $value, $meta_key = '', $object_type = '' ) {
        unset( $meta_key, $object_type );

        if ( $value === null || $value === '' ) {
            return null;
        }

        if ( is_string( $value ) ) {
            $value = trim( str_replace( ',', '.', $value ) );
        }

        if ( $value === '' ) {
            return null;
        }

        if ( ! is_numeric( $value ) ) {
            return null;
        }

        $score = round( (float) $value, 1 );

        if ( $score < 0 ) {
            $score = 0.0;
        }

        $score_max = Helpers::get_score_max();
        if ( is_numeric( $score_max ) && (float) $score_max > 0 ) {
            $score = min( $score, (float) $score_max );
        }

        return $score;
    }

    public function sanitize_badge_override_meta( $value, $meta_key = '', $object_type = '' ) {
        unset( $meta_key, $object_type );

        if ( $value === null ) {
            return 'auto';
        }

        if ( is_string( $value ) ) {
            $value = strtolower( trim( $value ) );
        }

        $allowed = array( 'auto', 'force-on', 'force-off' );

        if ( in_array( $value, $allowed, true ) ) {
            return $value;
        }

        return 'auto';
    }

    public function can_edit_registered_meta( $allowed, $meta_key, $post_id, $user_id, $cap = '', $caps = array() ) {
        unset( $allowed, $meta_key, $cap, $caps );

        $post_id = (int) $post_id;

        if ( $post_id <= 0 ) {
            return false;
        }

        if ( function_exists( 'user_can' ) && $user_id ) {
            return user_can( (int) $user_id, 'edit_post', $post_id );
        }

        return current_user_can( 'edit_post', $post_id );
    }

    public function register_blocks() {
        if ( ! function_exists( 'register_block_type_from_metadata' ) ) {
            return;
        }

        foreach ( $this->blocks as $slug => $config ) {
            $metadata_path = trailingslashit( JLG_NOTATION_PLUGIN_DIR ) . 'assets/blocks/' . $slug;
            $callback      = isset( $config['callback'] ) ? $config['callback'] : '';

            if ( ! is_dir( $metadata_path ) || ! file_exists( trailingslashit( $metadata_path ) . 'block.json' ) ) {
                continue;
            }

            $args = array();
            if ( $callback !== '' && method_exists( $this, $callback ) ) {
                $args['render_callback'] = array( $this, $callback );
            }

            register_block_type_from_metadata( $metadata_path, $args );
        }
    }

    public function enqueue_block_editor_assets() {
        if ( ! function_exists( 'get_current_screen' ) ) {
            return;
        }

        $current_screen = get_current_screen();
        if ( ! $current_screen || $current_screen->id !== 'post' ) {
            return;
        }

        $frontend_handle = Frontend::FRONTEND_STYLE_HANDLE;
        if ( ! wp_style_is( $frontend_handle, 'registered' ) ) { 
            wp_register_style(
                $frontend_handle,
                trailingslashit( JLG_NOTATION_PLUGIN_URL ) . 'assets/css/jlg-frontend.css',
                array(),
                JLG_NOTATION_VERSION
            );
        }

        if ( ! wp_style_is( $frontend_handle, 'enqueued' ) ) {
            wp_enqueue_style( $frontend_handle );
        }

        if ( wp_style_is( $frontend_handle, 'enqueued' ) ) {
            $options = Helpers::get_plugin_options();
            $palette = Helpers::get_color_palette();

            $post_id = 0;
            if ( isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $post_id = absint( $_GET['post'] );
            }

            if ( $post_id <= 0 ) {
                $post_id = get_the_ID();
            }

            $average_score = null;
            if ( $post_id > 0 ) {
                $average_score = Helpers::get_average_score_for_post( $post_id );
            }

            $inline_css = DynamicCss::build_frontend_css( $options, $palette, $average_score );

            if ( $inline_css !== '' ) {
                wp_add_inline_style( $frontend_handle, $inline_css );
            }
        }

        $game_explorer_handle = Frontend::GAME_EXPLORER_STYLE_HANDLE;
        if ( ! wp_style_is( $game_explorer_handle, 'registered' ) ) { 
            wp_register_style(
                $game_explorer_handle,
                trailingslashit( JLG_NOTATION_PLUGIN_URL ) . 'assets/css/game-explorer.css',
                array( $frontend_handle ),
                JLG_NOTATION_VERSION
            );
        }

        if ( wp_style_is( $game_explorer_handle, 'registered' ) && ! wp_style_is( $game_explorer_handle, 'enqueued' ) ) {
            wp_enqueue_style( $game_explorer_handle );
        }
    }

    private function get_allowed_post_types_for_editor() {
        if ( ! class_exists( Helpers::class ) ) {
            return array();
        }

        $types = Helpers::get_allowed_post_types();
        if ( ! is_array( $types ) ) {
            $types = array();
        }

        $types  = array_map( 'sanitize_key', array_filter( $types ) );
        $types  = array_values( array_unique( $types ) );
        $result = array();

        foreach ( $types as $type ) {
            $object = get_post_type_object( $type );
            $label  = $object && ! empty( $object->labels->singular_name )
                ? $object->labels->singular_name
                : ucwords( str_replace( array( '-', '_' ), ' ', $type ) );

            $result[] = array(
                'slug'  => $type,
                'label' => $label,
            );
        }

        if ( empty( $result ) ) {
            $result[] = array(
                'slug'  => 'post',
                'label' => __( 'Articles', 'notation-jlg' ),
            );
        }

        return $result;
    }

    private function set_script_translations( $handle ) {
        if ( ! function_exists( 'wp_set_script_translations' ) ) {
            return;
        }

        if ( ! wp_script_is( $handle, 'registered' ) ) {
            return;
        }

        if ( ! is_dir( $this->languages_path ) ) {
            return;
        }

        wp_set_script_translations( $handle, 'notation-jlg', $this->languages_path );
    }

    private function get_file_version( $path ) {
        $mtime = file_exists( $path ) ? filemtime( $path ) : false;

        if ( $mtime ) {
            return (string) $mtime;
        }

        return defined( 'JLG_NOTATION_VERSION' ) ? JLG_NOTATION_VERSION : '1.0.0';
    }

    private function render_shortcode( $shortcode, array $atts = array() ) {
        if ( function_exists( 'shortcode_exists' ) && ! shortcode_exists( $shortcode ) ) {
            return '';
        }

        if ( class_exists( Frontend::class ) ) {
            Frontend::mark_shortcode_rendered( $shortcode );
        }

        $attributes_string = '';

        foreach ( $atts as $key => $value ) {
            if ( $value === null ) {
                continue;
            }

            if ( is_bool( $value ) ) {
                $value = $value ? 'oui' : 'non';
            } elseif ( is_array( $value ) ) {
                $value = implode( ',', array_filter( array_map( 'strval', $value ) ) );
            }

            if ( $value === '' ) {
                continue;
            }

            $attributes_string .= sprintf( ' %s="%s"', sanitize_key( $key ), esc_attr( $value ) );
        }

        return do_shortcode( sprintf( '[%s%s]', sanitize_key( $shortcode ), $attributes_string ) );
    }

    public function render_rating_block( $attributes ) {
        $post_id = isset( $attributes['postId'] ) ? absint( $attributes['postId'] ) : 0;
        $atts    = array();

        if ( $post_id > 0 ) {
            $atts['post_id'] = $post_id;
        }

        if ( ! empty( $attributes['scoreLayout'] ) && is_string( $attributes['scoreLayout'] ) ) {
            $layout = sanitize_key( $attributes['scoreLayout'] );
            if ( in_array( $layout, array( 'text', 'circle' ), true ) ) {
                $atts['score_layout'] = $layout;
            }
        }

        if ( ! empty( $attributes['scoreDisplay'] ) && is_string( $attributes['scoreDisplay'] ) ) {
            $display_mode = sanitize_key( $attributes['scoreDisplay'] );
            if ( in_array( $display_mode, array( 'absolute', 'percent' ), true ) ) {
                $atts['display_mode'] = $display_mode;
            }
        }

        if ( array_key_exists( 'showAnimations', $attributes ) ) {
            $is_enabled          = (bool) $attributes['showAnimations'];
            $atts['animations'] = $is_enabled ? 'oui' : 'non';
        }

        if ( ! empty( $attributes['accentColor'] ) && is_string( $attributes['accentColor'] ) ) {
            $color = sanitize_hex_color( $attributes['accentColor'] );
            if ( ! empty( $color ) ) {
                $atts['accent_color'] = $color;
            }
        }

        if ( ! empty( $attributes['previewMeta'] ) && is_array( $attributes['previewMeta'] ) ) {
            $definitions   = Helpers::get_rating_category_definitions();
            $allowed_keys  = array();
            $score_max_raw = Helpers::get_score_max();
            $score_max     = ( is_numeric( $score_max_raw ) && (float) $score_max_raw > 0 ) ? (float) $score_max_raw : 10.0;

            foreach ( $definitions as $definition ) {
                $meta_key = isset( $definition['meta_key'] ) ? (string) $definition['meta_key'] : '';

                if ( $meta_key === '' ) {
                    continue;
                }

                $allowed_keys[ $meta_key ] = true;
            }

            $allowed_keys['_jlg_rating_badge_override'] = true;

            $preview_meta = array();

            foreach ( $attributes['previewMeta'] as $meta_key => $value ) {
                if ( ! is_string( $meta_key ) || ! isset( $allowed_keys[ $meta_key ] ) ) {
                    continue;
                }

                if ( '_jlg_rating_badge_override' === $meta_key ) {
                    $normalized_badge = $this->sanitize_badge_override_meta( $value );
                    if ( is_string( $normalized_badge ) && $normalized_badge !== '' ) {
                        $preview_meta[ $meta_key ] = $normalized_badge;
                    }
                    continue;
                }

                if ( $value === null || $value === '' ) {
                    continue;
                }

                if ( is_string( $value ) ) {
                    $value = str_replace( ',', '.', $value );
                }

                if ( ! is_numeric( $value ) ) {
                    continue;
                }

                $score_value = round( (float) $value, 1 );

                if ( $score_value < 0 ) {
                    $score_value = 0.0;
                }

                if ( $score_max > 0 ) {
                    $score_value = min( $score_value, $score_max );
                }

                $preview_meta[ $meta_key ] = $score_value;
            }

            if ( ! empty( $preview_meta ) ) {
                $atts['preview_meta'] = wp_json_encode( $preview_meta );
            }
        }

        if ( class_exists( Frontend::class ) ) {
            Frontend::mark_shortcode_rendered( 'bloc_notation_jeu' );
        }

        return $this->render_shortcode( 'bloc_notation_jeu', $atts );
    }

    public function render_pros_cons_block( $attributes ) {
        unset( $attributes );

        return $this->render_shortcode( 'jlg_points_forts_faibles' );
    }

    public function render_tagline_block( $attributes ) {
        unset( $attributes );

        return $this->render_shortcode( 'tagline_notation_jlg' );
    }

    public function render_game_info_block( $attributes ) {
        $atts = array();

        if ( isset( $attributes['postId'] ) ) {
            $post_id = absint( $attributes['postId'] );
            if ( $post_id > 0 ) {
                $atts['post_id'] = $post_id;
            }
        }

        if ( ! empty( $attributes['fields'] ) && is_array( $attributes['fields'] ) ) {
            $fields = array_map( 'sanitize_key', array_filter( $attributes['fields'] ) );
            if ( ! empty( $fields ) ) {
                $atts['champs'] = implode( ',', $fields );
            }
        }

        if ( ! empty( $attributes['title'] ) && is_string( $attributes['title'] ) ) {
            $atts['titre'] = sanitize_text_field( $attributes['title'] );
        }

        return $this->render_shortcode( 'jlg_fiche_technique', $atts );
    }

    public function render_user_rating_block( $attributes ) {
        unset( $attributes );

        return $this->render_shortcode( 'notation_utilisateurs_jlg' );
    }

    public function render_summary_display_block( $attributes ) {
        $atts = array();

        if ( isset( $attributes['postsPerPage'] ) ) {
            $posts_per_page         = max( 1, absint( $attributes['postsPerPage'] ) );
            $atts['posts_per_page'] = $posts_per_page;
        }

        if ( ! empty( $attributes['layout'] ) && is_string( $attributes['layout'] ) ) {
            $layout = sanitize_key( $attributes['layout'] );
            if ( in_array( $layout, array( 'table', 'grid' ), true ) ) {
                $atts['layout'] = $layout;
            }
        }

        if ( ! empty( $attributes['columns'] ) && is_array( $attributes['columns'] ) ) {
            $columns = array_map( 'sanitize_key', array_filter( $attributes['columns'] ) );
            if ( ! empty( $columns ) ) {
                $atts['colonnes'] = implode( ',', $columns );
            }
        }

        if ( ! empty( $attributes['category'] ) && is_string( $attributes['category'] ) ) {
            $atts['categorie'] = sanitize_text_field( $attributes['category'] );
        }

        if ( ! empty( $attributes['letterFilter'] ) && is_string( $attributes['letterFilter'] ) ) {
            $atts['letter_filter'] = sanitize_text_field( $attributes['letterFilter'] );
        }

        if ( ! empty( $attributes['genreFilter'] ) && is_string( $attributes['genreFilter'] ) ) {
            $atts['genre_filter'] = sanitize_text_field( $attributes['genreFilter'] );
        }

        return $this->render_shortcode( 'jlg_tableau_recap', $atts );
    }

    public function render_all_in_one_block( $attributes ) {
        $atts = array();

        if ( isset( $attributes['postId'] ) ) {
            $post_id = absint( $attributes['postId'] );
            if ( $post_id > 0 ) {
                $atts['post_id'] = $post_id;
            }
        }

        $bool_attributes = array(
            'showRating'   => 'afficher_notation',
            'showProsCons' => 'afficher_points',
            'showTagline'  => 'afficher_tagline',
            'showVideo'    => 'afficher_video',
        );

        foreach ( $bool_attributes as $attr_key => $shortcode_key ) {
            if ( isset( $attributes[ $attr_key ] ) ) {
                $atts[ $shortcode_key ] = (bool) $attributes[ $attr_key ];
            }
        }

        if ( ! empty( $attributes['style'] ) && is_string( $attributes['style'] ) ) {
            $style = sanitize_key( $attributes['style'] );
            if ( in_array( $style, array( 'moderne', 'classique', 'compact' ), true ) ) {
                $atts['style'] = $style;
            }
        }

        if ( ! empty( $attributes['scoreDisplay'] ) && is_string( $attributes['scoreDisplay'] ) ) {
            $display_mode = sanitize_key( $attributes['scoreDisplay'] );
            if ( in_array( $display_mode, array( 'absolute', 'percent' ), true ) ) {
                $atts['display_mode'] = $display_mode;
            }
        }

        if ( ! empty( $attributes['accentColor'] ) && is_string( $attributes['accentColor'] ) ) {
            $color = sanitize_hex_color( $attributes['accentColor'] );
            if ( ! empty( $color ) ) {
                $atts['couleur_accent'] = $color;
            }
        }

        if ( ! empty( $attributes['prosTitle'] ) && is_string( $attributes['prosTitle'] ) ) {
            $atts['titre_points_forts'] = sanitize_text_field( $attributes['prosTitle'] );
        }

        if ( ! empty( $attributes['consTitle'] ) && is_string( $attributes['consTitle'] ) ) {
            $atts['titre_points_faibles'] = sanitize_text_field( $attributes['consTitle'] );
        }

        if ( ! empty( $attributes['ctaLabel'] ) && is_string( $attributes['ctaLabel'] ) ) {
            $atts['cta_label'] = sanitize_text_field( $attributes['ctaLabel'] );
        }

        if ( ! empty( $attributes['ctaUrl'] ) && is_string( $attributes['ctaUrl'] ) ) {
            $url = esc_url_raw( $attributes['ctaUrl'] );
            if ( $url !== '' && Validator::is_valid_http_url( $url ) ) {
                $atts['cta_url'] = $url;
            }
        }

        if ( ! empty( $attributes['ctaRole'] ) && is_string( $attributes['ctaRole'] ) ) {
            $role = sanitize_key( $attributes['ctaRole'] );
            if ( $role !== '' ) {
                $atts['cta_role'] = $role;
            }
        }

        if ( isset( $attributes['ctaRel'] ) && is_string( $attributes['ctaRel'] ) ) {
            $rel = sanitize_text_field( $attributes['ctaRel'] );
            if ( $rel !== '' ) {
                $atts['cta_rel'] = $rel;
            }
        }

        return $this->render_shortcode( 'jlg_bloc_complet', $atts );
    }

    public function render_game_explorer_block( $attributes ) {
        $atts = array();

        if ( isset( $attributes['postsPerPage'] ) ) {
            $posts_per_page         = max( 1, absint( $attributes['postsPerPage'] ) );
            $atts['posts_per_page'] = $posts_per_page;
        }

        if ( isset( $attributes['columns'] ) ) {
            $columns         = max( 1, absint( $attributes['columns'] ) );
            $atts['columns'] = $columns;
        }

        if ( ! empty( $attributes['scorePosition'] ) && is_string( $attributes['scorePosition'] ) ) {
            $atts['score_position'] = Helpers::normalize_game_explorer_score_position( $attributes['scorePosition'] );
        }

        if ( ! empty( $attributes['filters'] ) && is_array( $attributes['filters'] ) ) {
            $filters = array_map( 'sanitize_key', array_filter( $attributes['filters'] ) );
            if ( ! empty( $filters ) ) {
                $atts['filters'] = implode( ',', $filters );
            }
        }

        if ( ! empty( $attributes['category'] ) && is_string( $attributes['category'] ) ) {
            $atts['categorie'] = sanitize_text_field( $attributes['category'] );
        }

        if ( ! empty( $attributes['platform'] ) && is_string( $attributes['platform'] ) ) {
            $atts['plateforme'] = sanitize_text_field( $attributes['platform'] );
        }

        if ( ! empty( $attributes['letter'] ) && is_string( $attributes['letter'] ) ) {
            $atts['lettre'] = sanitize_text_field( $attributes['letter'] );
        }

        $sort_override = null;
        if ( ! empty( $attributes['sort'] ) && is_string( $attributes['sort'] ) ) {
            $sort    = sanitize_text_field( $attributes['sort'] );
            $parts   = explode( '|', $sort );
            $orderby = isset( $parts[0] ) ? sanitize_key( $parts[0] ) : '';
            $order   = isset( $parts[1] ) ? strtoupper( sanitize_key( $parts[1] ) ) : '';

            if ( in_array( $orderby, array( 'date', 'score', 'title' ), true ) ) {
                if ( ! in_array( $order, array( 'ASC', 'DESC' ), true ) ) {
                    $order = 'DESC';
                }

                $sort_override = array(
                    'orderby' => $orderby,
                    'order'   => $order,
                );
            }
        }

        $previous_orderby = null;
        $previous_order   = null;

        if ( $sort_override !== null ) {
            $previous_orderby = isset( $_GET['orderby'] ) ? $_GET['orderby'] : null;
            $previous_order   = isset( $_GET['order'] ) ? $_GET['order'] : null;

            $_GET['orderby'] = $sort_override['orderby'];
            $_GET['order']   = $sort_override['order'];
        }

        $output = $this->render_shortcode( 'jlg_game_explorer', $atts );

        if ( $sort_override !== null ) {
            if ( $previous_orderby === null ) {
                unset( $_GET['orderby'] );
            } else {
                $_GET['orderby'] = $previous_orderby;
            }

            if ( $previous_order === null ) {
                unset( $_GET['order'] );
            } else {
                $_GET['order'] = $previous_order;
            }
        }

        return $output;
    }

    public function render_score_insights_block( $attributes ) {
        $atts = array();

        if ( ! empty( $attributes['title'] ) && is_string( $attributes['title'] ) ) {
            $atts['title'] = sanitize_text_field( $attributes['title'] );
        }

        if ( ! empty( $attributes['timeRange'] ) && is_string( $attributes['timeRange'] ) ) {
            $atts['time_range'] = sanitize_key( $attributes['timeRange'] );
        }

        if ( isset( $attributes['platform'] ) && is_string( $attributes['platform'] ) ) {
            $platform = sanitize_title( $attributes['platform'] );
            if ( $platform !== '' ) {
                $atts['platform'] = $platform;
            }
        }

        if ( isset( $attributes['platformLimit'] ) ) {
            $limit = (int) $attributes['platformLimit'];
            if ( $limit > 0 ) {
                $atts['platform_limit'] = min( 10, $limit );
            }
        }

        return $this->render_shortcode( 'jlg_score_insights', $atts );
    }
}

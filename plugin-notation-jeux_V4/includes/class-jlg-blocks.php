<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class JLG_Blocks {
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
    );

    public function __construct() {
        $this->languages_path = trailingslashit( JLG_NOTATION_PLUGIN_DIR ) . 'languages';

        add_action( 'init', array( $this, 'register_block_editor_assets' ) );
        add_action( 'init', array( $this, 'register_blocks' ) );
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

        foreach ( $this->blocks as $slug => $config ) {
            $script_handle = isset( $config['script'] ) ? $config['script'] : '';
            $script_file   = $scripts_dir . $slug . '.js';

            if ( $script_handle === '' || ! file_exists( $script_file ) ) {
                continue;
            }

            $deps = array( $this->shared_script_handle );
            wp_register_script(
                $script_handle,
                $scripts_url . $slug . '.js',
                $deps,
                $this->get_file_version( $script_file ),
                true
            );

            $this->set_script_translations( $script_handle );
        }
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

    private function get_allowed_post_types_for_editor() {
        if ( ! class_exists( 'JLG_Helpers' ) ) {
            return array();
        }

        $types = JLG_Helpers::get_allowed_post_types();
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
        if ( ! shortcode_exists( $shortcode ) ) {
            return '';
        }

        if ( class_exists( 'JLG_Frontend' ) ) {
            JLG_Frontend::mark_shortcode_rendered( $shortcode );
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
}

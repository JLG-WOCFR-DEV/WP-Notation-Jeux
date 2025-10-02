<?php
/**
 * Gestion des réglages du plugin
 *
 * @package JLG_Notation
 * @version 5.0
 */

namespace JLG\Notation\Admin;

use JLG\Notation\Helpers;
use JLG\Notation\Utils\FormRenderer;

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

class Settings {

    private $option_name       = 'notation_jlg_settings';
    private $field_constraints = array();

    public function __construct() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function register_settings() {
        register_setting( 'notation_jlg_page', $this->option_name, array( $this, 'sanitize_options' ) );
        $this->register_all_sections();
    }

    public function sanitize_options( $input ) {
        if ( ! is_array( $input ) ) {
            return Helpers::get_default_settings();
        }

        $sanitized = array();
        $defaults  = Helpers::get_default_settings();

        // IMPORTANT: Traiter d'abord les champs select pour les modes de couleur
        // Ces champs doivent être traités spécialement pour conserver leur valeur
        $select_fields = array(
            'visual_theme'                 => array( 'dark', 'light' ),
            'score_layout'                 => array( 'text', 'circle' ),
            'text_glow_color_mode'         => array( 'dynamic', 'custom' ),
            'circle_glow_color_mode'       => array( 'dynamic', 'custom' ),
            'table_border_style'           => array( 'none', 'horizontal', 'full' ),
            'game_explorer_score_position' => Helpers::get_game_explorer_score_positions(),
        );

        foreach ( $select_fields as $field => $allowed_values ) {
            if ( isset( $input[ $field ] ) ) {
                $value = sanitize_key( wp_unslash( $input[ $field ] ) );

                if ( in_array( $value, $allowed_values, true ) ) {
                    $sanitized[ $field ] = $value;
                    continue;
                }
            }

            $sanitized[ $field ] = $defaults[ $field ] ?? '';
        }

        $current_options    = Helpers::get_plugin_options();
        $raw_categories     = isset( $input['rating_categories'] ) && is_array( $input['rating_categories'] )
            ? $input['rating_categories']
            : array();
        $current_categories = isset( $current_options['rating_categories'] ) && is_array( $current_options['rating_categories'] )
            ? $current_options['rating_categories']
            : array();
        $default_categories = isset( $defaults['rating_categories'] ) && is_array( $defaults['rating_categories'] )
            ? $defaults['rating_categories']
            : array();

        $sanitized['rating_categories'] = $this->sanitize_rating_categories( $raw_categories, $default_categories, $current_categories );

        // Traiter les autres champs
        foreach ( $defaults as $key => $default_value ) {
            // Skip les champs déjà traités
            if ( array_key_exists( $key, $select_fields ) ) {
                continue;
            }

            if ( $key === 'rating_categories' ) {
                continue;
            }

            if ( isset( $input[ $key ] ) ) {
                $sanitized[ $key ] = $this->sanitize_option_value( $key, $input[ $key ], $default_value );
            } else {
                // Pour les checkboxes non cochées
                if (
                    strpos( $key, 'enabled' ) !== false ||
                    strpos( $key, 'pulse' ) !== false ||
                    strpos( $key, 'striping' ) !== false ||
                    strpos( $key, 'enable_' ) === 0
                ) {
                    $sanitized[ $key ] = 0;
                } else {
                    $sanitized[ $key ] = $default_value;
                }
            }
        }

        Helpers::flush_plugin_options_cache();

        return $sanitized;
    }

    private function sanitize_option_value( $key, $value, $default_value = '' ) {
        if ( isset( $this->field_constraints[ $key ] ) ) {
            return $this->normalize_numeric_value( $key, $value, $default_value );
        }

        // Couleurs
        if ( strpos( $key, 'color' ) !== false && strpos( $key, 'color_mode' ) === false ) {
            $allow_transparent_fields = array(
                'table_row_bg_color',
                'table_zebra_bg_color',
            );

            $trimmed_value = is_string( $value ) ? strtolower( trim( $value ) ) : '';

            if (
                $trimmed_value === 'transparent'
                && in_array( $key, $allow_transparent_fields, true )
            ) {
                return 'transparent';
            }

            $sanitized_color = sanitize_hex_color( $value );

            if ( ! empty( $sanitized_color ) ) {
                return $sanitized_color;
            }

            $default_trimmed = is_string( $default_value ) ? strtolower( trim( $default_value ) ) : '';

            if (
                $default_trimmed === 'transparent'
                && in_array( $key, $allow_transparent_fields, true )
            ) {
                return 'transparent';
            }

            $sanitized_default = is_string( $default_value ) ? sanitize_hex_color( $default_value ) : '';

            return $sanitized_default ? $sanitized_default : '';
        }

        // Nombres
        if ( strpos( $key, 'size' ) !== false || strpos( $key, 'width' ) !== false ||
            strpos( $key, 'padding' ) !== false || strpos( $key, 'radius' ) !== false ||
            strpos( $key, 'intensity' ) !== false || strpos( $key, 'speed' ) !== false ) {
            return is_numeric( $value ) ? floatval( $value ) : ( is_numeric( $default_value ) ? floatval( $default_value ) : 0 );
        }

        // Checkboxes
        if (
            strpos( $key, 'enabled' ) !== false ||
            strpos( $key, 'pulse' ) !== false ||
            strpos( $key, 'striping' ) !== false ||
            strpos( $key, 'enable_' ) === 0
        ) {
            return ! empty( $value ) ? 1 : 0;
        }

        // CSS personnalisé
        if ( $key === 'custom_css' ) {
            return wp_strip_all_tags( $value );
        }

        // API Key
        if ( $key === 'rawg_api_key' ) {
            return sanitize_text_field( $value );
        }

        // Texte par défaut
        return sanitize_text_field( $value );
    }

    private function normalize_numeric_value( $key, $value, $default_value ) {
        $constraints = $this->field_constraints[ $key ];

        $min  = isset( $constraints['min'] ) ? floatval( $constraints['min'] ) : null;
        $max  = isset( $constraints['max'] ) ? floatval( $constraints['max'] ) : null;
        $step = isset( $constraints['step'] ) ? floatval( $constraints['step'] ) : null;

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
        add_settings_section( 'jlg_labels', '1. 📝 Libellés des Catégories', null, 'notation_jlg_page' );
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

        // Section 2: Présentation de la Note Globale
        add_settings_section( 'jlg_layout', '2. 🎨 Présentation de la Note Globale', null, 'notation_jlg_page' );
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

        // Section 3: Couleurs & Thèmes
        add_settings_section( 'jlg_colors', '3. 🌈 Couleurs & Thèmes', null, 'notation_jlg_page' );
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
            'color_high'       => 'Notes Élevées (8-10)',
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

        // Section 4: Effet Glow/Neon (Mode Texte)
        add_settings_section( 'jlg_glow_text', '4. ✨ Effet Neon - Mode Texte', null, 'notation_jlg_page' );
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

        // Section 5: Effet Glow/Neon (Mode Cercle)
        add_settings_section( 'jlg_glow_circle', '5. ✨ Effet Neon - Mode Cercle', null, 'notation_jlg_page' );
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

        // Section 6: Modules
        add_settings_section( 'jlg_modules', '6. 🧩 Modules', null, 'notation_jlg_page' );
        $module_fields = array(
            'user_rating_enabled' => 'Notation utilisateurs',
            'tagline_enabled'     => 'Taglines bilingues',
            'seo_schema_enabled'  => 'Schéma SEO (étoiles Google)',
            'enable_animations'   => 'Animations des barres',
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

        // Section 7: Modules - Tagline
        add_settings_section( 'jlg_tagline_section', '7. 💬 Module Tagline', null, 'notation_jlg_page' );
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

        // Section 8: Modules - Notation Utilisateurs
        add_settings_section( 'jlg_user_rating_section', '8. ⭐ Module Notation Utilisateurs', null, 'notation_jlg_page' );
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

        // Section 9: Tableau Récapitulatif
        add_settings_section( 'jlg_table', '9. 📊 Tableau Récapitulatif', null, 'notation_jlg_page' );
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

        // Section 10: Style des Vignettes
        add_settings_section( 'jlg_thumbnail_section', '10. 🖼️ Style des Vignettes', null, 'notation_jlg_page' );
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

        // Section 11: CSS Personnalisé
        add_settings_section( 'jlg_custom', '11. 🎨 CSS Personnalisé', null, 'notation_jlg_page' );
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

        // Section 12: SEO
        add_settings_section( 'jlg_seo_section', '12. 🔍 SEO', null, 'notation_jlg_page' );
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

        // Section 13: API
        add_settings_section( 'jlg_api_section', '13. 🌐 API', null, 'notation_jlg_page' );
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

        // Section 14: Debug
        add_settings_section( 'jlg_debug_section', '14. 🔧 Debug', null, 'notation_jlg_page' );
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

        // Section 15: Game Explorer
        add_settings_section( 'jlg_game_explorer', '15. 🧭 Game Explorer', null, 'notation_jlg_page' );

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
                'id'          => 'game_explorer_filters',
                'type'        => 'text',
                'placeholder' => 'letter,category,platform,availability',
                'desc'        => __( 'Liste séparée par des virgules. Options disponibles : letter, category, platform, availability.', 'notation-jlg' ),
            )
        );

        $score_position_options = array(
            'top-left'      => __( 'En haut à gauche', 'notation-jlg' ),
            'top-right'     => __( 'En haut à droite', 'notation-jlg' ),
            'middle-left'   => __( 'Au centre à gauche', 'notation-jlg' ),
            'middle-right'  => __( 'Au centre à droite', 'notation-jlg' ),
            'bottom-left'   => __( 'En bas à gauche', 'notation-jlg' ),
            'bottom-right'  => __( 'En bas à droite', 'notation-jlg' ),
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
            );
        }

        if ( empty( $sanitized ) ) {
            return $defaults;
        }

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
                    $classes[] = 'jlg-color-picker--allow-transparent';
                    $data_attributes['data-allow-transparent'] = 'true';
                }

                $default_attr_value                  = is_string( $default_value ) ? $default_value : '';
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

        $field_id    = $args['id'] ?? 'rating_categories';
        $option_name = $this->option_name;
        $definitions = Helpers::get_rating_category_definitions();
        $wrapper_id  = $field_id . '_manager';
        $next_index  = count( $definitions );

        static $styles_printed = false;

        if ( ! $styles_printed ) {
            echo '<style>';
            echo '.jlg-rating-categories__list{display:flex;flex-direction:column;gap:12px;margin-bottom:12px;}';
            echo '.jlg-rating-category{border:1px solid #dcdcde;background:#fff;padding:12px;border-radius:4px;}';
            echo '.jlg-rating-category__grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;}';
            echo '.jlg-rating-category__actions{display:flex;align-items:flex-end;justify-content:flex-end;}';
            echo '.jlg-rating-category__remove{color:#a00;}';
            echo '@media (max-width:782px){.jlg-rating-category__grid{grid-template-columns:1fr;}}';
            echo '</style>';
            $styles_printed = true;
        }

        echo '<div id="' . esc_attr( $wrapper_id ) . '" class="jlg-rating-categories" data-next-index="' . esc_attr( $next_index ) . '">';
        echo '<div class="jlg-rating-categories__list">';

        foreach ( $definitions as $index => $definition ) {
            $label       = isset( $definition['label'] ) ? $definition['label'] : '';
            $id          = isset( $definition['id'] ) ? $definition['id'] : '';
            $legacy_ids  = isset( $definition['legacy_ids'] ) && is_array( $definition['legacy_ids'] ) ? $definition['legacy_ids'] : array();
            $label_field = sprintf( '%s[%s][%d][label]', $option_name, $field_id, $index );
            $id_field    = sprintf( '%s[%s][%d][id]', $option_name, $field_id, $index );

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
            echo '<div class="jlg-rating-category__actions">';
            echo '<button type="button" class="button button-link-delete jlg-rating-category__remove">' . esc_html__( 'Supprimer', 'notation-jlg' ) . '</button>';
            echo '</div>';
            echo '</div>';

            echo '<input type="hidden" name="' . esc_attr( sprintf( '%s[%s][%d][original_id]', $option_name, $field_id, $index ) ) . '" value="' . esc_attr( $id ) . '" />';

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

        $template_label = esc_attr__( 'Libellé', 'notation-jlg' );
        $template_id    = esc_attr__( 'Identifiant', 'notation-jlg' );
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
        echo '<div class="jlg-rating-category__actions">';
        echo '<button type="button" class="button button-link-delete jlg-rating-category__remove">' . esc_html( $template_remove ) . '</button>';
        echo '</div>';
        echo '</div>';
        echo '<input type="hidden" name="' . esc_attr( sprintf( '%s[%s][__INDEX__][original_id]', $option_name, $field_id ) ) . '" value="" />';
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
        echo 'function bindRow(row){if(!row){return;}const remove=row.querySelector(".jlg-rating-category__remove");if(remove){remove.addEventListener("click",function(event){event.preventDefault();row.remove();});}}';
        echo 'if(list){Array.prototype.forEach.call(list.children,bindRow);}';
        echo 'if(addButton&&template&&list){addButton.addEventListener("click",function(event){event.preventDefault();const fragment=template.content.cloneNode(true);const row=fragment.querySelector(".jlg-rating-category");const index=nextIndex++;if(!row){return;}row.setAttribute("data-index",String(index));fragment.querySelectorAll("[name]").forEach(function(element){element.name=element.name.replace(/__INDEX__/g,index);});fragment.querySelectorAll("[id]").forEach(function(element){element.id=element.id.replace(/__INDEX__/g,index);});fragment.querySelectorAll("label[for]").forEach(function(label){label.htmlFor=label.htmlFor.replace(/__INDEX__/g,index);});list.appendChild(fragment);bindRow(list.lastElementChild);container.setAttribute("data-next-index",String(nextIndex));});}';
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

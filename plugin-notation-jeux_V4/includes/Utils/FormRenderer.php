<?php
/**
 * Rendu des champs de formulaire
 *
 * @package JLG_Notation
 * @version 5.0
 */

namespace JLG\Notation\Utils;

use JLG\Notation\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

class FormRenderer {
    private static $option_name = 'notation_jlg_settings';

    public static function text_field( $args ) {
        $options = Helpers::get_plugin_options();
        printf(
            '<input type="text" class="regular-text" name="%s[%s]" value="%s" placeholder="%s" />',
            esc_attr( self::$option_name ),
            esc_attr( $args['id'] ),
            esc_attr( $options[ $args['id'] ] ?? '' ),
            esc_attr( $args['placeholder'] ?? '' )
        );
        self::render_description( $args );
    }

    public static function color_field( $args ) {
        $options           = Helpers::get_plugin_options();
        $defaults          = Helpers::get_default_settings();
        $field_id          = $args['id'];
        $allow_transparent = ! empty( $args['allow_transparent'] );
        $default_value     = $defaults[ $field_id ] ?? '#000000';
        $current_value     = $options[ $field_id ] ?? $default_value;
        $current_value     = is_string( $current_value ) ? $current_value : ( is_string( $default_value ) ? $default_value : '' );
        $data_attributes   = array();

        $classes = array( 'wp-color-picker', 'jlg-color-picker' );
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
            esc_attr( self::$option_name ),
            esc_attr( $field_id ),
            esc_attr( $field_id ),
            esc_attr( $current_value ),
            $attributes
        );
        self::render_description( $args );
    }

    public static function checkbox_field( $args ) {
        $options = Helpers::get_plugin_options();
        printf(
            '<input type="checkbox" name="%s[%s]" value="1" %s />',
            esc_attr( self::$option_name ),
            esc_attr( $args['id'] ),
            checked( 1, $options[ $args['id'] ] ?? 0, false )
        );
        self::render_description( $args );
    }

    public static function checkbox_group_field( $args ) {
        $options  = Helpers::get_plugin_options();
        $defaults = Helpers::get_default_settings();
        $field_id = $args['id'];

        $choices = isset( $args['options'] ) && is_array( $args['options'] )
            ? $args['options']
            : array();

        $selected = $options[ $field_id ] ?? ( $defaults[ $field_id ] ?? array() );

        if ( $field_id === 'game_explorer_filters' ) {
            $selected = Helpers::normalize_game_explorer_filters(
                $selected,
                Helpers::get_default_game_explorer_filters()
            );
        } else {
            if ( is_string( $selected ) ) {
                $selected = array( $selected );
            }

            if ( ! is_array( $selected ) ) {
                $selected = array();
            }

            $selected = array_values( array_unique( array_filter( array_map( 'sanitize_key', $selected ) ) ) );
        }

        if ( empty( $choices ) ) {
            return;
        }

        echo '<div class="jlg-checkbox-group">';

        foreach ( $choices as $choice_key => $choice_label ) {
            $choice_id = sprintf( '%s_%s', $field_id, $choice_key );
            printf(
                '<label for="%1$s" class="jlg-checkbox-group__item"><input type="checkbox" name="%2$s[%3$s][]" id="%1$s" value="%4$s" %5$s /> %6$s</label>',
                esc_attr( $choice_id ),
                esc_attr( self::$option_name ),
                esc_attr( $field_id ),
                esc_attr( $choice_key ),
                checked( in_array( $choice_key, $selected, true ), true, false ),
                esc_html( $choice_label )
            );
        }

        echo '</div>';

        self::render_description( $args );
    }

    public static function select_field( $args ) {
        $options = Helpers::get_plugin_options();
        $value   = $options[ $args['id'] ] ?? '';

        printf( '<select name="%s[%s]">', esc_attr( self::$option_name ), esc_attr( $args['id'] ) );
        foreach ( $args['options'] as $key => $label ) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr( $key ),
                selected( $value, $key, false ),
                esc_html( $label )
            );
        }
        echo '</select>';
        self::render_description( $args );
    }

    public static function post_types_field( $args ) {
        $field_id = $args['id'];
        $options  = Helpers::get_plugin_options();
        $defaults = Helpers::get_default_settings();

        $selected = $options[ $field_id ] ?? ( $defaults[ $field_id ] ?? array() );

        if ( is_string( $selected ) ) {
            $selected = array( $selected );
        }

        if ( ! is_array( $selected ) ) {
            $selected = array();
        }

        $selected = array_map( 'sanitize_key', $selected );

        $choices = array();

        if ( isset( $args['choices'] ) && is_array( $args['choices'] ) ) {
            foreach ( $args['choices'] as $slug => $label ) {
                $slug = sanitize_key( (string) $slug );

                if ( $slug === '' ) {
                    continue;
                }

                if ( ! is_string( $label ) ) {
                    $label = '';
                }

                $label = trim( $label );

                if ( $label === '' ) {
                    $label = ucwords( str_replace( array( '-', '_' ), ' ', $slug ) );
                }

                $choices[ $slug ] = $label;
            }
        }

        if ( empty( $choices ) && function_exists( 'get_post_types' ) ) {
            $post_types = \get_post_types( array( 'public' => true ), 'objects' );

            if ( is_array( $post_types ) ) {
                foreach ( $post_types as $slug => $post_type ) {
                    $slug = sanitize_key( (string) $slug );

                    if ( $slug === '' ) {
                        continue;
                    }

                    $label = '';

                    if ( isset( $post_type->labels->singular_name ) && is_string( $post_type->labels->singular_name ) ) {
                        $label = trim( $post_type->labels->singular_name );
                    }

                    if ( $label === '' && isset( $post_type->label ) && is_string( $post_type->label ) ) {
                        $label = trim( $post_type->label );
                    }

                    if ( $label === '' ) {
                        $label = ucwords( str_replace( array( '-', '_' ), ' ', $slug ) );
                    }

                    $choices[ $slug ] = $label;
                }
            }
        }

        if ( empty( $choices ) ) {
            $choices = array( 'post' => 'post' );
        }

        $size = count( $choices );
        if ( $size < 4 ) {
            $size = 4;
        } elseif ( $size > 12 ) {
            $size = 12;
        }

        printf(
            '<select name="%s[%s][]" id="%s" multiple="multiple" size="%d" class="regular-text">',
            esc_attr( self::$option_name ),
            esc_attr( $field_id ),
            esc_attr( $field_id ),
            (int) $size
        );

        foreach ( $choices as $slug => $label ) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr( $slug ),
                selected( in_array( $slug, $selected, true ), true, false ),
                esc_html( $label )
            );
        }

        echo '</select>';

        self::render_description( $args );
    }

    public static function number_field( $args ) {
        $options = Helpers::get_plugin_options();
        $min     = $args['min'] ?? 0;
        $max     = $args['max'] ?? 100;
        $step    = $args['step'] ?? 1;
        printf(
            '<input type="number" class="small-text" name="%s[%s]" value="%s" min="%s" max="%s" step="%s" />',
            esc_attr( self::$option_name ),
            esc_attr( $args['id'] ),
            esc_attr( $options[ $args['id'] ] ?? $min ),
            esc_attr( $min ),
            esc_attr( $max ),
            esc_attr( $step )
        );
        self::render_description( $args );
    }

    public static function textarea_field( $args ) {
        $options = Helpers::get_plugin_options();
        printf(
            '<textarea name="%s[%s]" rows="10" cols="50" class="large-text code" placeholder="%s">%s</textarea>',
            esc_attr( self::$option_name ),
            esc_attr( $args['id'] ),
            esc_attr( $args['placeholder'] ?? '' ),
            esc_textarea( $options[ $args['id'] ] ?? '' )
        );
        self::render_description( $args );
    }

    private static function render_description( $args ) {
        if ( isset( $args['desc'] ) ) {
            printf( '<p class="description">%s</p>', wp_kses_post( $args['desc'] ) );
        }
    }
}

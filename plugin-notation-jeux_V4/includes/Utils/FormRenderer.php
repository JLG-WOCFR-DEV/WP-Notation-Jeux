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
        $options         = Helpers::get_plugin_options();
        $defaults        = Helpers::get_default_settings();
        $field_id        = $args['id'] ?? '';
        $current_value   = $options[ $field_id ] ?? ( $defaults[ $field_id ] ?? '#000000' );
        $default_value   = $defaults[ $field_id ] ?? '#000000';
        $additional      = isset( $args['class'] ) ? explode( ' ', (string) $args['class'] ) : array();
        $class_names     = array_merge( array( 'jlg-color-picker', 'wp-color-picker' ), $additional );
        $class_names     = array_filter( array_map( 'sanitize_html_class', $class_names ) );
        $allow_transparent = ! empty( $args['allow_transparent'] );

        $attributes = array(
            'type'               => 'text',
            'class'              => implode( ' ', array_unique( $class_names ) ),
            'name'               => sprintf( '%s[%s]', self::$option_name, $field_id ),
            'value'              => $current_value,
            'data-default-color' => $default_value,
        );

        if ( $allow_transparent ) {
            $attributes['data-allow-transparent'] = 'true';
        }

        $attribute_string = '';
        foreach ( $attributes as $attr => $value ) {
            if ( $value === '' && $attr !== 'value' ) {
                continue;
            }

            $attribute_string .= sprintf( ' %s="%s"', esc_attr( $attr ), esc_attr( $value ) );
        }

        printf( '<input%s />', $attribute_string );
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

<?php

namespace JLG\Notation\Shortcodes;

use JLG\Notation\Frontend;
use JLG\Notation\Helpers;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PlatformBreakdown {
    public const SHORTCODE = 'jlg_platform_breakdown';

    public function __construct() {
        add_shortcode( self::SHORTCODE, array( $this, 'render' ) );
    }

    public function render( $atts = array(), $content = '', $shortcode_tag = '' ) {
        unset( $content );

        $defaults = array(
            'post_id'               => '',
            'title'                 => '',
            'show_best_badge'       => 'yes',
            'highlight_badge_label' => '',
            'empty_message'         => __( 'Aucun comparatif plateforme pour le moment.', 'notation-jlg' ),
        );

        $atts = shortcode_atts( $defaults, $atts, self::SHORTCODE );

        $post_id = $this->resolve_target_post_id( $atts['post_id'] );
        if ( ! $post_id ) {
            return '';
        }

        $context = self::build_view_context(
            $post_id,
            array(
                'title'                 => $atts['title'],
                'show_best_badge'       => strtolower( (string) $atts['show_best_badge'] ) !== 'no',
                'highlight_badge_label' => $atts['highlight_badge_label'],
                'empty_message'         => $atts['empty_message'],
            )
        );

        if ( empty( $context['has_entries'] ) && $context['empty_message'] === '' ) {
            return '';
        }

        Frontend::mark_shortcode_rendered( $shortcode_tag ?: self::SHORTCODE );

        return Frontend::get_template_html( 'shortcode-platform-breakdown', $context );
    }

    public static function build_view_context( $post_id, array $args ) {
        $post_id = (int) $post_id;
        $title   = isset( $args['title'] ) ? sanitize_text_field( $args['title'] ) : '';

        if ( function_exists( 'mb_substr' ) ) {
            $title = mb_substr( $title, 0, 120 );
        } else {
            $title = substr( $title, 0, 120 );
        }
        $title = trim( $title );

        $empty_message = isset( $args['empty_message'] ) ? sanitize_textarea_field( $args['empty_message'] ) : '';
        if ( function_exists( 'mb_substr' ) ) {
            $empty_message = mb_substr( $empty_message, 0, 180 );
        } else {
            $empty_message = substr( $empty_message, 0, 180 );
        }
        $empty_message = trim( $empty_message );

        $entries         = Helpers::get_platform_breakdown_for_post( $post_id );
        $has_entries     = ! empty( $entries );
        $show_best_badge = ! empty( $args['show_best_badge'] ) && $has_entries;

        $highlight_label = '';
        if ( isset( $args['highlight_badge_label'] ) && $args['highlight_badge_label'] !== '' ) {
            $highlight_label = sanitize_text_field( $args['highlight_badge_label'] );
            if ( function_exists( 'mb_substr' ) ) {
                $highlight_label = mb_substr( $highlight_label, 0, 80 );
            } else {
                $highlight_label = substr( $highlight_label, 0, 80 );
            }
            $highlight_label = trim( $highlight_label );
        }

        if ( $highlight_label === '' ) {
            $highlight_label = Helpers::get_platform_breakdown_badge_label( $post_id );
        }

        $active_index = null;
        foreach ( $entries as $index => $entry ) {
            if ( ! empty( $entry['is_best'] ) ) {
                $active_index = $index;
                break;
            }
        }

        if ( $active_index === null && $has_entries ) {
            $active_index = 0;
        }

        foreach ( $entries as $index => &$entry ) {
            $entry['is_active']      = ( $active_index !== null && $index === $active_index );
            $entry['is_highlighted'] = $show_best_badge && ( $active_index !== null && $index === $active_index );
        }
        unset( $entry );

        return array(
            'post_id'               => $post_id,
            'title'                 => $title,
            'entries'               => $entries,
            'has_entries'           => $has_entries,
            'show_best_badge'       => $show_best_badge,
            'highlight_badge_label' => $highlight_label,
            'empty_message'         => $empty_message,
            'active_index'          => $active_index !== null ? $active_index : 0,
        );
    }

    private function resolve_target_post_id( $post_id_attribute ) {
        $post_id       = absint( $post_id_attribute );
        $allowed_types = Helpers::get_allowed_post_types();

        if ( $post_id && $this->is_valid_target_post( $post_id, $allowed_types ) ) {
            return $post_id;
        }

        if ( $post_id_attribute !== '' && $post_id === 0 ) {
            return 0;
        }

        $current_post_id = get_the_ID();
        if ( ! $current_post_id ) {
            return 0;
        }

        if ( $this->is_valid_target_post( $current_post_id, $allowed_types ) ) {
            if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
                return $current_post_id;
            }

            if ( ! function_exists( 'is_singular' ) || is_singular( $allowed_types ) ) {
                return $current_post_id;
            }
        }

        return 0;
    }

    private function is_valid_target_post( $post_id, array $allowed_types ) {
        $post = get_post( $post_id );
        if ( ! $post instanceof WP_Post ) {
            return false;
        }

        if ( ! in_array( $post->post_type ?? '', $allowed_types, true ) ) {
            return false;
        }

        $status = $post->post_status ?? '';
        if ( $status === 'publish' ) {
            return true;
        }

        return current_user_can( 'read_post', $post_id );
    }
}

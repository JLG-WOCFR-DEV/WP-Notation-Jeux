<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class JLG_Dynamic_CSS {
    /**
     * Build the dynamic CSS that was previously generated inline.
     *
     * @param array $options
     * @param array $palette
     * @param float|null $average_score
     * @return string
     */
    public static function build_frontend_css( $options, $palette, $average_score ) {
        $builder = new self();

        return $builder->generate_css(
            is_array( $options ) ? $options : array(),
            is_array( $palette ) ? $palette : array(),
            $average_score
        );
    }

    /**
     * Generate the CSS string for the frontend.
     *
     * @param array $options
     * @param array $palette
     * @param float|null $average_score
     * @return string
     */
    private function generate_css( array $options, array $palette, $average_score ) {
        $palette_colors = $this->sanitize_color_options(
            array(
				'bg_color',
				'bg_color_secondary',
				'border_color',
				'main_text_color',
				'secondary_text_color',
				'bar_bg_color',
				'tagline_bg_color',
				'tagline_text_color',
            ),
            $palette
        );

        $bg_color             = $palette_colors['bg_color'] ?? '';
        $bg_color_secondary   = $palette_colors['bg_color_secondary'] ?? '';
        $border_color         = $palette_colors['border_color'] ?? '';
        $main_text_color      = $palette_colors['main_text_color'] ?? '';
        $secondary_text_color = $palette_colors['secondary_text_color'] ?? '';
        $bar_bg_color         = $palette_colors['bar_bg_color'] ?? '';
        $tagline_bg_color     = $palette_colors['tagline_bg_color'] ?? '';
        $tagline_text_color   = $palette_colors['tagline_text_color'] ?? '';

        $option_colors = $this->sanitize_color_options(
            array(
				'score_gradient_1',
				'score_gradient_2',
				'color_high',
				'color_low',
				'user_rating_text_color',
				'user_rating_star_color',
				'table_header_bg_color',
				'table_header_text_color',
				'table_row_bg_color'   => array( 'allow_transparent' => true ),
				'table_row_text_color',
				'table_zebra_bg_color' => array( 'allow_transparent' => true ),
				'circle_border_color',
            ),
            $options
        );

        $score_gradient_1        = $option_colors['score_gradient_1'] ?? '';
        $score_gradient_2        = $option_colors['score_gradient_2'] ?? '';
        $color_high              = $option_colors['color_high'] ?? '';
        $color_low               = $option_colors['color_low'] ?? '';
        $user_rating_text_color  = $option_colors['user_rating_text_color'] ?? '';
        $user_rating_star_color  = $option_colors['user_rating_star_color'] ?? '';
        $table_header_bg_color   = $option_colors['table_header_bg_color'] ?? '';
        $table_header_text_color = $option_colors['table_header_text_color'] ?? '';
        $table_row_bg_color      = $option_colors['table_row_bg_color'] ?? '';
        $table_row_text_color    = $option_colors['table_row_text_color'] ?? '';
        $table_zebra_bg_color    = $option_colors['table_zebra_bg_color'] ?? '';
        $circle_border_color     = $option_colors['circle_border_color'] ?? '';

        $default_settings = JLG_Helpers::get_default_settings();
        $default_colors   = $this->sanitize_color_options(
            array(
				'default_score_gradient_1'           => 'score_gradient_1',
				'default_score_gradient_2'           => 'score_gradient_2',
				'default_color_high'                 => 'color_high',
				'default_color_low'                  => 'color_low',
				'default_user_rating_text_color'     => 'user_rating_text_color',
				'default_user_rating_star_color'     => 'user_rating_star_color',
				'default_table_header_bg_color'      => 'table_header_bg_color',
				'default_table_header_text_color'    => 'table_header_text_color',
				'default_table_row_bg_color'         => array(
					'key'               => 'table_row_bg_color',
					'allow_transparent' => true,
				),
				'default_table_row_text_color'       => 'table_row_text_color',
				'default_table_zebra_bg_color'       => array(
					'key'               => 'table_zebra_bg_color',
					'allow_transparent' => true,
				),
				'default_circle_border_color'        => 'circle_border_color',
				'default_light_bg_color'             => 'light_bg_color',
				'default_light_bg_color_secondary'   => 'light_bg_color_secondary',
				'default_light_border_color'         => 'light_border_color',
				'default_light_text_color'           => 'light_text_color',
				'default_light_text_color_secondary' => 'light_text_color_secondary',
				'default_dark_bg_color'              => 'dark_bg_color',
				'default_dark_bg_color_secondary'    => 'dark_bg_color_secondary',
				'default_dark_border_color'          => 'dark_border_color',
				'default_dark_text_color'            => 'dark_text_color',
				'default_dark_text_color_secondary'  => 'dark_text_color_secondary',
            ),
            $default_settings
        );

        $default_score_gradient_1           = $default_colors['default_score_gradient_1'] ?? '';
        $default_score_gradient_2           = $default_colors['default_score_gradient_2'] ?? '';
        $default_color_high                 = $default_colors['default_color_high'] ?? '';
        $default_color_low                  = $default_colors['default_color_low'] ?? '';
        $default_user_rating_text_color     = $default_colors['default_user_rating_text_color'] ?? '';
        $default_user_rating_star_color     = $default_colors['default_user_rating_star_color'] ?? '';
        $default_table_header_bg_color      = $default_colors['default_table_header_bg_color'] ?? '';
        $default_table_header_text_color    = $default_colors['default_table_header_text_color'] ?? '';
        $default_table_row_bg_color         = $default_colors['default_table_row_bg_color'] ?? '';
        $default_table_row_text_color       = $default_colors['default_table_row_text_color'] ?? '';
        $default_table_zebra_bg_color       = $default_colors['default_table_zebra_bg_color'] ?? '';
        $default_circle_border_color        = $default_colors['default_circle_border_color'] ?? '';
        $default_light_bg_color             = $default_colors['default_light_bg_color'] ?? '';
        $default_light_bg_color_secondary   = $default_colors['default_light_bg_color_secondary'] ?? '';
        $default_light_border_color         = $default_colors['default_light_border_color'] ?? '';
        $default_light_text_color           = $default_colors['default_light_text_color'] ?? '';
        $default_light_text_color_secondary = $default_colors['default_light_text_color_secondary'] ?? '';
        $default_dark_bg_color              = $default_colors['default_dark_bg_color'] ?? '';
        $default_dark_bg_color_secondary    = $default_colors['default_dark_bg_color_secondary'] ?? '';
        $default_dark_border_color          = $default_colors['default_dark_border_color'] ?? '';
        $default_dark_text_color            = $default_colors['default_dark_text_color'] ?? '';
        $default_dark_text_color_secondary  = $default_colors['default_dark_text_color_secondary'] ?? '';

        $default_score_gradient_1 = $default_score_gradient_1 !== '' ? $default_score_gradient_1 : '#000000';
        $default_score_gradient_2 = $default_score_gradient_2 !== '' ? $default_score_gradient_2 : '#000000';

        $theme = $options['visual_theme'] ?? 'dark';
        if ( $theme === 'light' ) {
            $default_bg_color             = $default_light_bg_color !== '' ? $default_light_bg_color : '#ffffff';
            $default_bg_color_secondary   = $default_light_bg_color_secondary !== '' ? $default_light_bg_color_secondary : '#f9fafb';
            $default_border_color         = $default_light_border_color !== '' ? $default_light_border_color : '#e5e7eb';
            $default_text_color           = $default_light_text_color !== '' ? $default_light_text_color : '#111827';
            $default_secondary_text_color = $default_light_text_color_secondary !== '' ? $default_light_text_color_secondary : '#6b7280';
        } else {
            $default_bg_color             = $default_dark_bg_color !== '' ? $default_dark_bg_color : '#18181b';
            $default_bg_color_secondary   = $default_dark_bg_color_secondary !== '' ? $default_dark_bg_color_secondary : '#27272a';
            $default_border_color         = $default_dark_border_color !== '' ? $default_dark_border_color : '#3f3f46';
            $default_text_color           = $default_dark_text_color !== '' ? $default_dark_text_color : '#fafafa';
            $default_secondary_text_color = $default_dark_text_color_secondary !== '' ? $default_dark_text_color_secondary : '#a1a1aa';
        }

        $default_color_high              = $default_color_high !== '' ? $default_color_high : '#22c55e';
        $default_color_low               = $default_color_low !== '' ? $default_color_low : '#ef4444';
        $default_user_rating_text_color  = $default_user_rating_text_color !== '' ? $default_user_rating_text_color : '#a1a1aa';
        $default_user_rating_star_color  = $default_user_rating_star_color !== '' ? $default_user_rating_star_color : '#f59e0b';
        $default_table_header_bg_color   = $default_table_header_bg_color !== '' ? $default_table_header_bg_color : $default_bg_color_secondary;
        $default_table_header_text_color = $default_table_header_text_color !== '' ? $default_table_header_text_color : $default_text_color;
        $default_table_row_bg_color      = $default_table_row_bg_color !== '' ? $default_table_row_bg_color : 'transparent';
        $default_table_row_text_color    = $default_table_row_text_color !== '' ? $default_table_row_text_color : $default_secondary_text_color;
        $default_table_zebra_bg_color    = $default_table_zebra_bg_color !== '' ? $default_table_zebra_bg_color : 'transparent';
        $default_circle_border_color     = $default_circle_border_color !== '' ? $default_circle_border_color : 'transparent';

        foreach ( array(
            'score_gradient_1'        => $default_score_gradient_1,
            'score_gradient_2'        => $default_score_gradient_2,
            'bg_color'                => $default_bg_color,
            'bg_color_secondary'      => $default_bg_color_secondary,
            'border_color'            => $default_border_color,
            'main_text_color'         => $default_text_color,
            'secondary_text_color'    => $default_secondary_text_color,
            'color_high'              => $default_color_high,
            'color_low'               => $default_color_low,
            'user_rating_text_color'  => $default_user_rating_text_color,
            'user_rating_star_color'  => $default_user_rating_star_color,
            'table_header_bg_color'   => $default_table_header_bg_color,
            'table_header_text_color' => $default_table_header_text_color,
            'table_row_bg_color'      => $default_table_row_bg_color,
            'table_row_text_color'    => $default_table_row_text_color,
            'table_zebra_bg_color'    => $default_table_zebra_bg_color,
            'circle_border_color'     => $default_circle_border_color,
        ) as $variable => $fallback ) {
            if ( $$variable === '' ) {
                $$variable = $fallback;
            }
        }

        if ( $bar_bg_color === '' ) {
            $bar_bg_color = $bg_color_secondary;
        }
        if ( $tagline_bg_color === '' ) {
            $tagline_bg_color = $bg_color_secondary;
        }
        if ( $tagline_text_color === '' ) {
            $tagline_text_color = $secondary_text_color;
        }

        $table_row_hover_color  = $this->sanitize_color_value( JLG_Helpers::adjust_hex_brightness( $bg_color_secondary, 5 ) );
        $table_link_color       = $this->sanitize_color_value( JLG_Helpers::adjust_hex_brightness( $table_row_text_color, 20 ) );
        $score_gradient_1_hover = $this->sanitize_color_value( JLG_Helpers::adjust_hex_brightness( $score_gradient_1, 20 ) );

        $inline_css = ':root{'
            . '--jlg-bg-color:' . $bg_color . ';'
            . '--jlg-bg-color-secondary:' . $bg_color_secondary . ';'
            . '--jlg-border-color:' . $border_color . ';'
            . '--jlg-main-text-color:' . $main_text_color . ';'
            . '--jlg-secondary-text-color:' . $secondary_text_color . ';'
            . '--jlg-bar-bg-color:' . $bar_bg_color . ';'
            . '--jlg-score-gradient-1:' . $score_gradient_1 . ';'
            . '--jlg-score-gradient-2:' . $score_gradient_2 . ';'
            . '--jlg-color-high:' . $color_high . ';'
            . '--jlg-color-low:' . $color_low . ';'
            . '--jlg-tagline-bg-color:' . $tagline_bg_color . ';'
            . '--jlg-tagline-text-color:' . $tagline_text_color . ';'
            . '--jlg-tagline-font-size:' . intval( $options['tagline_font_size'] ?? 0 ) . 'px;'
            . '--jlg-user-rating-text-color:' . $user_rating_text_color . ';'
            . '--jlg-user-rating-star-color:' . $user_rating_star_color . ';'
            . '--jlg-table-header-bg-color:' . $table_header_bg_color . ';'
            . '--jlg-table-header-text-color:' . $table_header_text_color . ';'
            . '--jlg-table-row-bg-color:' . $table_row_bg_color . ';'
            . '--jlg-table-row-text-color:' . $table_row_text_color . ';'
            . '--jlg-table-row-hover-color:' . $table_row_hover_color . ';'
            . '--jlg-table-link-color:' . $table_link_color . ';'
            . '--jlg-score-gradient-1-hover:' . $score_gradient_1_hover . ';'
            . '--jlg-table-zebra-bg-color:' . $table_zebra_bg_color . ';'
            . '}';

        $inline_css .= $this->build_table_zebra_css( $options, $table_zebra_bg_color );
        $inline_css .= $this->build_table_border_css( $options, $border_color );
        $inline_css .= $this->build_score_circle_css(
            $options,
            $average_score,
            $score_gradient_1,
            $score_gradient_2,
            $default_score_gradient_1,
            $default_score_gradient_2,
            $circle_border_color
        );
        $inline_css .= $this->build_glow_css( $options, $average_score );
        $inline_css .= $this->build_custom_css( $options );

        return $inline_css;
    }

    /**
     * Generate the zebra striping CSS block.
     *
     * @param array $options
     * @param string $table_zebra_bg_color
     * @return string
     */
    private function build_table_zebra_css( array $options, $table_zebra_bg_color ) {
        if ( empty( $options['table_zebra_striping'] ) ) {
            return '';
        }

        $zebra_base_color = $this->sanitize_color_value( $table_zebra_bg_color, true );

        if ( $zebra_base_color === '' ) {
            return '';
        }

        $css = '.jlg-summary-table tbody tr:nth-child(even){background-color:var(--jlg-table-zebra-bg-color);}';

        if ( $zebra_base_color === 'transparent' ) {
            return $css;
        }

        $zebra_hover_color = $this->sanitize_color_value(
            JLG_Helpers::adjust_hex_brightness( $zebra_base_color, 5 )
        );

        if ( $zebra_hover_color === '' ) {
            return $css;
        }

        return $css . '.jlg-summary-table tbody tr:nth-child(even):hover{background-color:' . $zebra_hover_color . ';}';
    }

    /**
     * Generate the table border CSS according to the selected style.
     *
     * @param array $options
     * @param string $border_color
     * @return string
     */
    private function build_table_border_css( array $options, $border_color ) {
        $border_style           = $options['table_border_style'] ?? '';
        $border_width           = intval( $options['table_border_width'] ?? 0 );
        $sanitized_border_color = $this->sanitize_color_value( $border_color, true );

        if ( $sanitized_border_color === '' ) {
            return '';
        }

        switch ( $border_style ) {
            case 'horizontal':
                return '.jlg-summary-table th,.jlg-summary-table td{border-bottom:' . $border_width . 'px solid ' . $sanitized_border_color . ';}';
            case 'full':
                return '.jlg-summary-table th,.jlg-summary-table td{border:' . $border_width . 'px solid ' . $sanitized_border_color . ';}';
            default:
                return '';
        }
    }

    /**
     * Generate the CSS for the score circle layout.
     *
     * @param array $options
     * @param float|null $average_score
     * @param string $score_gradient_1
     * @param string $score_gradient_2
     * @param string $default_score_gradient_1
     * @param string $default_score_gradient_2
     * @param string $circle_border_color
     * @return string
     */
    private function build_score_circle_css(
        array $options,
        $average_score,
        $score_gradient_1,
        $score_gradient_2,
        $default_score_gradient_1,
        $default_score_gradient_2,
        $circle_border_color
    ) {
        if ( ( $options['score_layout'] ?? '' ) !== 'circle' ) {
            return '';
        }

        if ( ! empty( $options['circle_dynamic_bg_enabled'] ) ) {
            $dynamic_color   = $this->sanitize_color_value( JLG_Helpers::calculate_color_from_note( $average_score, $options ) );
            $base_for_darker = $dynamic_color ?: $score_gradient_1;
            $darker_color    = $base_for_darker !== ''
                ? $this->sanitize_color_value( JLG_Helpers::adjust_hex_brightness( $base_for_darker, -30 ) )
                : '';
        } else {
            $dynamic_color = $score_gradient_1;
            $darker_color  = $score_gradient_2;
        }

        if ( $dynamic_color === '' ) {
            $dynamic_color = $default_score_gradient_1;
        }
        if ( $darker_color === '' ) {
            $darker_color = $default_score_gradient_2;
        }

        $css = '.review-box-jlg .score-circle{background-image:linear-gradient(135deg,' . $dynamic_color . ',' . $darker_color . ');';

        if ( ! empty( $options['circle_border_enabled'] ) ) {
            $css .= 'border:' . intval( $options['circle_border_width'] ?? 0 ) . 'px solid ' . $circle_border_color . ';';
        }

        return $css . '}';
    }

    /**
     * Generate the glow CSS for text or circle layouts.
     *
     * @param array $options
     * @param float|null $average_score
     * @return string
     */
    private function build_glow_css( array $options, $average_score ) {
        $layout = $options['score_layout'] ?? '';

        if ( $layout === 'text' ) {
            return JLG_Helpers::get_glow_css( 'text', $average_score, $options );
        }

        if ( $layout === 'circle' ) {
            return JLG_Helpers::get_glow_css( 'circle', $average_score, $options );
        }

        return '';
    }

    /**
     * Append any custom CSS defined in the options.
     *
     * @param array $options
     * @return string
     */
    private function build_custom_css( array $options ) {
        if ( empty( $options['custom_css'] ) ) {
            return '';
        }

        return wp_strip_all_tags( $options['custom_css'] );
    }

    /**
     * Sanitize a color value, allowing optional transparency.
     *
     * @param mixed $value
     * @param bool $allow_transparent
     * @return string
     */
    private function sanitize_color_value( $value, $allow_transparent = false ) {
        $sanitized = sanitize_hex_color( $value );

        if ( ! empty( $sanitized ) ) {
            return $sanitized;
        }

        if ( $allow_transparent && is_string( $value ) && 'transparent' === strtolower( trim( $value ) ) ) {
            return 'transparent';
        }

        return '';
    }

    /**
     * Sanitize a set of colors from a source array.
     *
     * @param array $definitions
     * @param array $source
     * @return array
     */
    private function sanitize_color_options( array $definitions, array $source ) {
        $sanitized = array();

        foreach ( $definitions as $target => $definition ) {
            $allow_transparent = false;

            if ( is_int( $target ) ) {
                $target_key = $definition;
                $source_key = $definition;
            } elseif ( is_string( $definition ) ) {
                $target_key = $target;
                $source_key = $definition;
            } elseif ( is_array( $definition ) ) {
                $target_key        = $target;
                $source_key        = isset( $definition['key'] ) ? $definition['key'] : $target;
                $allow_transparent = ! empty( $definition['allow_transparent'] );
            } else {
                continue;
            }

            $value                    = isset( $source[ $source_key ] ) ? $source[ $source_key ] : '';
            $sanitized[ $target_key ] = $this->sanitize_color_value( $value, $allow_transparent );
        }

        return $sanitized;
    }
}

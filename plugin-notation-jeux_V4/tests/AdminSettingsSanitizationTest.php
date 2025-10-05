<?php

use PHPUnit\Framework\TestCase;

class AdminSettingsSanitizationTest extends TestCase
{
    private \JLG\Notation\Admin\Settings $settings;

    protected function setUp(): void
    {
        $this->settings = new \JLG\Notation\Admin\Settings();
        $this->settings->register_settings();
    }

    public function test_numeric_fields_are_bounded(): void
    {
        $input = [
            'circle_border_width'   => -10,
            'text_glow_speed'       => 100,
            'circle_glow_speed'     => 0.66,
            'tagline_font_size'     => 120,
            'thumb_padding'         => -5,
            'thumb_border_radius'   => 999,
            'circle_glow_intensity' => 100,
            'text_glow_intensity'   => 'abc',
        ];

        $sanitized = $this->settings->sanitize_options($input);

        $this->assertSame(1, $sanitized['circle_border_width']);
        $this->assertSame(10.0, $sanitized['text_glow_speed']);
        $this->assertSame(0.7, $sanitized['circle_glow_speed']);
        $this->assertSame(32, $sanitized['tagline_font_size']);
        $this->assertSame(2, $sanitized['thumb_padding']);
        $this->assertSame(50, $sanitized['thumb_border_radius']);
        $this->assertSame(50, $sanitized['circle_glow_intensity']);
        $this->assertSame(15, $sanitized['text_glow_intensity']);
    }

    public function test_color_fields_accept_custom_hex_and_transparent(): void
    {
        $input = [
            'table_header_bg_color' => '#ABCDEF',
            'table_row_bg_color'    => 'Transparent',
            'table_zebra_bg_color'  => '#123456',
            'thumb_text_color'      => '#zzzzzz',
        ];

        $sanitized = $this->settings->sanitize_options($input);

        $this->assertSame('#abcdef', $sanitized['table_header_bg_color']);
        $this->assertSame('transparent', $sanitized['table_row_bg_color']);
        $this->assertSame('#123456', $sanitized['table_zebra_bg_color']);
        $this->assertSame('#ffffff', $sanitized['thumb_text_color']);
    }

    public function test_transparent_is_preserved_for_zebra_background(): void
    {
        $input = [
            'table_row_bg_color'   => 'transparent',
            'table_zebra_bg_color' => 'transparent',
        ];

        $sanitized = $this->settings->sanitize_options($input);

        $this->assertSame('transparent', $sanitized['table_row_bg_color']);
        $this->assertSame('transparent', $sanitized['table_zebra_bg_color']);
    }

    public function test_rating_badge_threshold_respects_new_score_max(): void
    {
        $previous_wpdb = $GLOBALS['wpdb'] ?? null;

        $GLOBALS['wpdb'] = new class() {
            public $postmeta = 'wp_postmeta';
            public $posts    = 'wp_posts';

            public function prepare($query, ...$args)
            {
                return $query;
            }

            public function get_col($prepared)
            {
                return [];
            }
        };

        $defaults = \JLG\Notation\Helpers::get_default_settings();

        update_option('notation_jlg_settings', array_merge($defaults, [
            'score_max'              => 20,
            'rating_badge_threshold' => 18,
        ]));
        \JLG\Notation\Helpers::flush_plugin_options_cache();

        $input = [
            'score_max'              => 20,
            'rating_badge_threshold' => 18,
        ];

        $sanitized = $this->settings->sanitize_options($input);

        $this->assertSame(20, $sanitized['score_max']);
        $this->assertSame(18.0, $sanitized['rating_badge_threshold']);

        $input = [
            'rating_badge_threshold' => 18,
        ];

        $sanitized = $this->settings->sanitize_options($input);

        $this->assertSame(10.0, $sanitized['rating_badge_threshold']);

        if ($previous_wpdb === null) {
            unset($GLOBALS['wpdb']);
        } else {
            $GLOBALS['wpdb'] = $previous_wpdb;
        }
    }
}

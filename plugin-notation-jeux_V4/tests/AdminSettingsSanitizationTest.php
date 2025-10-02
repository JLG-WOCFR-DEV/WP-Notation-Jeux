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
}

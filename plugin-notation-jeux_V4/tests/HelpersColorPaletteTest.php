<?php

use PHPUnit\Framework\TestCase;

class HelpersColorPaletteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        JLG_Helpers::flush_plugin_options_cache();
        $GLOBALS['jlg_test_options'] = [];
    }

    public function test_uses_custom_tagline_colors_when_defined(): void
    {
        $defaults = JLG_Helpers::get_default_settings();
        $options = $defaults;
        $options['visual_theme'] = 'dark';
        $options['tagline_bg_color'] = '#123456';
        $options['tagline_text_color'] = '#abcdef';

        update_option('notation_jlg_settings', $options);

        $palette = JLG_Helpers::get_color_palette();

        $this->assertSame('#123456', $palette['tagline_bg_color']);
        $this->assertSame('#abcdef', $palette['tagline_text_color']);
    }

    public function test_falls_back_to_theme_defaults_when_tagline_colors_missing(): void
    {
        $defaults = JLG_Helpers::get_default_settings();
        $options = $defaults;
        $options['visual_theme'] = 'light';
        $options['tagline_bg_color'] = '';
        $options['tagline_text_color'] = '';

        update_option('notation_jlg_settings', $options);

        $palette = JLG_Helpers::get_color_palette();

        $this->assertSame('#f3f4f6', $palette['tagline_bg_color']);
        $this->assertSame('#4b5563', $palette['tagline_text_color']);
    }
}

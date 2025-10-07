<?php

use PHPUnit\Framework\TestCase;

class HelpersFocusRingTest extends TestCase
{
    public function test_returns_dark_contrast_for_light_colors(): void
    {
        $this->assertSame('#000000', \JLG\Notation\Helpers::get_high_contrast_color('#fafafa'));
    }

    public function test_returns_light_contrast_for_dark_colors(): void
    {
        $this->assertSame('#ffffff', \JLG\Notation\Helpers::get_high_contrast_color('#111111'));
    }

    public function test_falls_back_to_light_when_color_invalid(): void
    {
        $this->assertSame('#ffffff', \JLG\Notation\Helpers::get_high_contrast_color('not-a-color'));
    }
}

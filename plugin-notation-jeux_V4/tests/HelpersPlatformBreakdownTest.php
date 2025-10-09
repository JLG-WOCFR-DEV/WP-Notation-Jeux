<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class HelpersPlatformBreakdownTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['jlg_test_meta'] = [];
    }

    public function test_returns_normalized_entries_with_defaults(): void
    {
        $post_id = 321;
        $GLOBALS['jlg_test_meta'][$post_id] = [
            '_jlg_platform_breakdown_entries' => [
                [
                    'platform'     => 'pc',
                    'performance'  => '4K60, DLSS équilibré',
                    'comment'      => 'Expérience la plus stable',
                    'is_best'      => true,
                ],
                [
                    'platform'     => 'non-existant',
                    'custom_label' => 'Steam Deck (mode dock)',
                    'performance'  => '1080p30',
                    'comment'      => '',
                    'is_best'      => false,
                ],
                [
                    'platform'     => '',
                    'custom_label' => '',
                    'performance'  => '',
                    'comment'      => '',
                    'is_best'      => false,
                ],
            ],
        ];

        $entries = \JLG\Notation\Helpers::get_platform_breakdown_for_post($post_id);

        $this->assertCount(2, $entries);
        $this->assertSame('pc', $entries[0]['platform']);
        $this->assertSame('PC', $entries[0]['label']);
        $this->assertTrue($entries[0]['is_best']);
        $this->assertSame('Expérience la plus stable', $entries[0]['comment']);

        $this->assertSame('Steam Deck (mode dock)', $entries[1]['label']);
        $this->assertSame('', $entries[1]['platform']);
        $this->assertFalse($entries[1]['is_best']);
    }

    public function test_badge_label_falls_back_to_default(): void
    {
        $post_id = 654;
        $GLOBALS['jlg_test_meta'][$post_id] = [];

        $this->assertSame(
            'Meilleure expérience',
            \JLG\Notation\Helpers::get_platform_breakdown_badge_label($post_id)
        );
    }

    public function test_badge_label_uses_custom_value(): void
    {
        $post_id = 987;
        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_platform_breakdown_highlight_label'] = ' Edition Premium ';

        $this->assertSame(
            'Edition Premium',
            \JLG\Notation\Helpers::get_platform_breakdown_badge_label($post_id)
        );
    }
}

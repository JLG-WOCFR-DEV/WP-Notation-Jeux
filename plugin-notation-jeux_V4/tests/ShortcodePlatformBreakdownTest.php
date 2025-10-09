<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/Shortcodes/PlatformBreakdown.php';

class ShortcodePlatformBreakdownTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['jlg_test_posts'] = [];
        $GLOBALS['jlg_test_meta'] = [];
        $GLOBALS['jlg_test_current_post_id'] = 0;
    }

    public function test_render_outputs_tabs_and_badge(): void
    {
        $post_id = 222;
        $GLOBALS['jlg_test_posts'][$post_id] = new WP_Post([
            'ID'          => $post_id,
            'post_type'   => 'post',
            'post_status' => 'publish',
        ]);

        $GLOBALS['jlg_test_meta'][$post_id] = [
            '_jlg_platform_breakdown_entries' => [
                [
                    'platform'    => 'pc',
                    'performance' => '4K60',
                    'comment'     => 'Latence minimale',
                    'is_best'     => true,
                ],
                [
                    'platform'    => 'playstation-5',
                    'performance' => 'Mode performance 60 fps',
                    'comment'     => 'Idéal sur TV 120 Hz',
                    'is_best'     => false,
                ],
            ],
            '_jlg_platform_breakdown_highlight_label' => 'Best Setup',
        ];

        $shortcode = new \JLG\Notation\Shortcodes\PlatformBreakdown();
        $output = $shortcode->render([
            'post_id'         => (string) $post_id,
            'title'           => 'Comparatif plateformes',
            'show_best_badge' => 'yes',
        ]);

        $this->assertNotSame('', $output);
        $this->assertStringContainsString('jlg-platform-breakdown', $output);
        $this->assertStringContainsString('Comparatif plateformes', $output);
        $this->assertStringContainsString('Best Setup', $output);
        $this->assertStringContainsString('Latence minimale', $output);
        $this->assertStringContainsString('PlayStation 5', $output);
    }

    public function test_render_returns_empty_message_when_no_entries(): void
    {
        $post_id = 333;
        $GLOBALS['jlg_test_posts'][$post_id] = new WP_Post([
            'ID'          => $post_id,
            'post_type'   => 'post',
            'post_status' => 'publish',
        ]);

        $GLOBALS['jlg_test_meta'][$post_id] = [];

        $shortcode = new \JLG\Notation\Shortcodes\PlatformBreakdown();
        $output = $shortcode->render([
            'post_id'       => (string) $post_id,
            'empty_message' => 'Aucune plateforme saisie.',
        ]);

        $this->assertStringContainsString('jlg-platform-breakdown', $output);
        $this->assertStringContainsString('Aucune plateforme saisie.', $output);
    }
}

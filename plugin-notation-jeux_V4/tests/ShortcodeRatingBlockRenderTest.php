<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/Helpers.php';
require_once __DIR__ . '/../includes/Frontend.php';
require_once __DIR__ . '/../includes/Shortcodes/RatingBlock.php';

class ShortcodeRatingBlockRenderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['jlg_test_posts'] = [];
        $GLOBALS['jlg_test_meta'] = [];
        $GLOBALS['jlg_test_options'] = [];
        $GLOBALS['jlg_test_current_post_id'] = 0;

        \JLG\Notation\Helpers::flush_plugin_options_cache();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset(
            $GLOBALS['jlg_test_posts'],
            $GLOBALS['jlg_test_meta'],
            $GLOBALS['jlg_test_options'],
            $GLOBALS['jlg_test_current_post_id']
        );

        \JLG\Notation\Helpers::flush_plugin_options_cache();
    }

    public function test_render_respects_shortcode_overrides(): void
    {
        $post_id = 1201;
        $this->seedPost($post_id);
        $this->seedRatings($post_id, [
            'gameplay'    => 8.4,
            'graphismes'  => 9.1,
        ]);

        $shortcode = new \JLG\Notation\Shortcodes\RatingBlock();
        $output = $shortcode->render([
            'post_id'       => (string) $post_id,
            'score_layout'  => 'circle',
            'animations'    => 'non',
            'accent_color'  => '#FF6600',
        ]);

        $this->assertNotSame('', $output, 'Shortcode output should not be empty.');
        $this->assertStringContainsString('review-box-jlg', $output);
        $this->assertStringContainsString('score-circle', $output, 'Circle layout should be rendered when overridden.');
        $this->assertStringNotContainsString('jlg-animate', $output, 'Animations should be disabled when requested.');
        $this->assertMatchesRegularExpression('/style=\"[^\"]*--jlg-score-gradient-1:#ff6600/i', $output, 'Accent color should be propagated to CSS variables.');
        $this->assertMatchesRegularExpression('/style=\"[^\"]*--jlg-color-mid:/i', $output, 'Derived accent colors should be applied.');
        $this->assertStringContainsString('8.8', $output, 'Average score should be displayed with decimal precision.');
    }

    public function test_render_uses_plugin_defaults_without_overrides(): void
    {
        $post_id = 1202;
        $this->seedPost($post_id);
        $this->seedRatings($post_id, [
            'gameplay'    => 7.5,
            'graphismes'  => 8.0,
        ]);

        $this->setPluginOptions([
            'score_layout'    => 'circle',
            'enable_animations' => 1,
            'score_gradient_1' => '#123123',
            'score_gradient_2' => '#456456',
            'color_high'       => '#abcdef',
            'color_mid'        => '#bcd234',
            'color_low'        => '#345678',
        ]);

        $shortcode = new \JLG\Notation\Shortcodes\RatingBlock();
        $output = $shortcode->render([
            'post_id' => (string) $post_id,
        ]);

        $this->assertNotSame('', $output);
        $this->assertStringContainsString('score-circle', $output, 'Plugin option should control layout when overrides absent.');
        $this->assertStringContainsString('jlg-animate', $output, 'Animations should follow plugin settings.');
        $this->assertMatchesRegularExpression('/style=\"[^\"]*--jlg-score-gradient-1:#123123/i', $output, 'Default accent colors should surface in CSS variables.');
        $this->assertMatchesRegularExpression('/style=\"[^\"]*--jlg-color-low:#345678/i', $output);
    }

    private function seedPost(int $post_id): void
    {
        $GLOBALS['jlg_test_posts'][$post_id] = new WP_Post([
            'ID'          => $post_id,
            'post_type'   => 'post',
            'post_status' => 'publish',
        ]);
    }

    private function seedRatings(int $post_id, array $scores): void
    {
        foreach ($scores as $category => $score) {
            $meta_key = '_note_' . $category;
            $GLOBALS['jlg_test_meta'][$post_id][$meta_key] = $score;
        }
    }

    private function setPluginOptions(array $overrides): void
    {
        $defaults = \JLG\Notation\Helpers::get_default_settings();
        $GLOBALS['jlg_test_options']['notation_jlg_settings'] = array_merge($defaults, $overrides);
        \JLG\Notation\Helpers::flush_plugin_options_cache();
    }
}

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
        $GLOBALS['jlg_test_terms'] = [];
        $GLOBALS['jlg_test_permalinks'] = [];
        $GLOBALS['jlg_test_post_modified'] = [];

        \JLG\Notation\Helpers::flush_plugin_options_cache();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset(
            $GLOBALS['jlg_test_posts'],
            $GLOBALS['jlg_test_meta'],
            $GLOBALS['jlg_test_options'],
            $GLOBALS['jlg_test_current_post_id'],
            $GLOBALS['jlg_test_terms'],
            $GLOBALS['jlg_test_permalinks'],
            $GLOBALS['jlg_test_post_modified'],
            $GLOBALS['jlg_test_is_admin'],
            $GLOBALS['jlg_test_doing_ajax'],
            $GLOBALS['jlg_test_doing_filters']
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

    public function test_preview_overrides_apply_theme_and_animations(): void
    {
        $post_id = 1210;
        $this->seedPost($post_id);
        $this->seedRatings($post_id, [
            'gameplay'   => 9.0,
            'graphismes' => 8.5,
        ]);

        $this->setPluginOptions([
            'enable_animations'     => 0,
            'light_bg_color'        => '#ffffff',
            'light_bg_color_secondary' => '#f9fafb',
            'dark_bg_color'         => '#18181b',
            'dark_bg_color_secondary' => '#27272a',
        ]);

        $shortcode = new \JLG\Notation\Shortcodes\RatingBlock();

        $forced_light = $shortcode->render([
            'post_id'             => (string) $post_id,
            'preview_theme'       => 'light',
            'preview_animations'  => 'enabled',
        ]);

        $this->assertStringContainsString('review-box-jlg--preview-theme-light', $forced_light, 'Light preview theme class should be present.');
        $this->assertStringContainsString('jlg-animate', $forced_light, 'Animations should be forced on when requested.');
        $this->assertMatchesRegularExpression('/style=\"[^\"]*--jlg-bg-color:#ffffff/i', $forced_light, 'Light theme variables should surface in inline style.');

        $forced_dark = $shortcode->render([
            'post_id'             => (string) $post_id,
            'preview_theme'       => 'dark',
            'preview_animations'  => 'disabled',
        ]);

        $this->assertStringContainsString('review-box-jlg--preview-theme-dark', $forced_dark, 'Dark preview theme class should be present.');
        $this->assertStringNotContainsString('jlg-animate', $forced_dark, 'Animations should be suppressed when disabled.');
        $this->assertMatchesRegularExpression('/style=\"[^\"]*--jlg-bg-color:#18181b/i', $forced_dark, 'Dark theme variables should surface in inline style.');
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

    public function test_render_outputs_placeholder_in_editor_when_scores_missing(): void
    {
        $post_id = 1203;
        $this->seedPost($post_id);

        $GLOBALS['jlg_test_is_admin'] = true;
        $GLOBALS['jlg_test_doing_filters'] = [
            'rest_request_after_callbacks' => true,
        ];

        $shortcode = new \JLG\Notation\Shortcodes\RatingBlock();
        $output = $shortcode->render([
            'post_id' => (string) $post_id,
        ]);

        $this->assertNotSame('', $output, 'Editor preview should render placeholder markup.');
        $this->assertStringContainsString('jlg-rating-block-empty', $output, 'Placeholder class should be present.');
        $this->assertStringContainsString('Notation JLG', $output, 'Metabox reference should guide editors.');
    }

    public function test_render_hides_badge_when_threshold_not_met(): void
    {
        $post_id = 1205;
        $this->seedPost($post_id);
        $this->seedRatings($post_id, [
            'gameplay'   => 8.5,
            'graphismes' => 8.5,
        ]);

        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_user_rating_avg'] = '8.2';

        $this->setPluginOptions([
            'rating_badge_enabled'   => 1,
            'rating_badge_threshold' => 9.0,
        ]);

        $editorial_average = \JLG\Notation\Helpers::get_average_score_for_post($post_id);
        $this->assertNotNull($editorial_average, 'Average score should exist when ratings are provided.');

        $shortcode = new \JLG\Notation\Shortcodes\RatingBlock();
        $output    = $shortcode->render([
            'post_id' => (string) $post_id,
        ]);

        $this->assertStringNotContainsString('rating-badge', $output, 'Badge markup should stay hidden below the configured threshold.');
        $this->assertStringContainsString('Note des lecteurs', $output, 'User rating summary should display when average exists.');

        $delta_value    = 8.2 - (float) $editorial_average;
        $expected_delta = number_format_i18n($delta_value, 1);
        if ($delta_value > 0) {
            $expected_delta = '+' . $expected_delta;
        }

        $this->assertStringContainsString($expected_delta, $output, 'Delta should remain visible even if the badge is hidden.');
    }

    public function test_render_displays_badge_and_user_rating_when_threshold_met(): void
    {
        $post_id = 1204;
        $this->seedPost($post_id);
        $this->seedRatings($post_id, [
            'gameplay'   => 9.4,
            'graphismes' => 9.6,
        ]);

        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_user_rating_avg'] = '9.9';

        $this->setPluginOptions([
            'rating_badge_enabled'   => 1,
            'rating_badge_threshold' => 9.0,
        ]);

        $editorial_average = \JLG\Notation\Helpers::get_average_score_for_post($post_id);
        $this->assertNotNull($editorial_average, 'Average score should be available for delta expectations.');

        $shortcode = new \JLG\Notation\Shortcodes\RatingBlock();
        $output    = $shortcode->render([
            'post_id' => (string) $post_id,
        ]);

        $this->assertStringContainsString('rating-badge', $output, 'Badge markup should be present when threshold is met.');
        $this->assertStringContainsString('Note des lecteurs', $output, 'User rating summary should be displayed.');
        $this->assertStringContainsString('Δ vs rédaction', $output, 'Delta label should be shown when both scores exist.');
        $delta_value = 9.9 - (float) $editorial_average;
        $expected_delta = number_format_i18n($delta_value, 1);
        if ($delta_value > 0) {
            $expected_delta = '+' . $expected_delta;
        }
        $this->assertStringContainsString($expected_delta, $output, 'Delta should include the signed difference when readers rate higher.');
    }

    public function test_render_outputs_review_status_and_related_guides_when_enabled(): void
    {
        $post_id = 1310;
        $this->seedPost($post_id);
        $this->seedRatings($post_id, [
            'gameplay'   => 8.5,
            'graphismes' => 8.0,
        ]);

        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_review_status'] = 'in_progress';
        $GLOBALS['jlg_test_terms'][$post_id]['guide']             = [
            ['term_id' => 77],
        ];

        $this->setPluginOptions([
            'review_status_enabled'     => 1,
            'related_guides_enabled'    => 1,
            'related_guides_limit'      => 2,
            'related_guides_taxonomies' => 'guide',
            'verdict_module_enabled'    => 0,
        ]);

        $this->seedPost(1410, [
            'post_title' => 'Soluce experte',
            'post_date'  => '2024-03-11 09:00:00',
        ]);
        $GLOBALS['jlg_test_terms'][1410]['guide'] = [
            ['term_id' => 77],
        ];
        $GLOBALS['jlg_test_permalinks'][1410] = 'https://example.com/guides/soluce-experte';

        $this->seedPost(1411, [
            'post_title' => 'Stratégies coop',
            'post_date'  => '2024-03-09 09:00:00',
        ]);
        $GLOBALS['jlg_test_terms'][1411]['guide'] = [
            ['term_id' => 77],
        ];
        $GLOBALS['jlg_test_permalinks'][1411] = 'https://example.com/guides/strategies-coop';

        $this->seedPost(1412, [
            'post_title' => 'Guide hors sujet',
            'post_date'  => '2024-03-08 09:00:00',
        ]);
        $GLOBALS['jlg_test_terms'][1412]['guide'] = [
            ['term_id' => 91],
        ];
        $GLOBALS['jlg_test_permalinks'][1412] = 'https://example.com/guides/hors-sujet';

        $shortcode = new \JLG\Notation\Shortcodes\RatingBlock();
        $output    = $shortcode->render([
            'post_id' => (string) $post_id,
        ]);

        $this->assertStringContainsString('jlg-review-status', $output);
        $this->assertStringContainsString('Statut du test', $output);
        $this->assertStringContainsString('Mise à jour en cours', $output);
        $this->assertStringContainsString('Guides associés', $output);
        $this->assertStringContainsString('Soluce experte', $output);
        $this->assertStringContainsString('https://example.com/guides/soluce-experte', $output);
        $this->assertStringNotContainsString('Guide hors sujet', $output);
    }

    public function test_render_includes_verdict_section_when_available(): void
    {
        $post_id = 1320;
        $this->seedPost($post_id);
        $this->seedRatings($post_id, [
            'gameplay'   => 8.8,
            'graphismes' => 8.2,
        ]);

        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_verdict_summary']   = 'Synthèse accessible pour les lecteurs.';
        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_verdict_cta_label'] = 'Lire le verdict complet';
        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_verdict_cta_url']   = 'https://example.com/tests/verdict';
        $GLOBALS['jlg_test_post_modified'][$post_id] = [
            'gmt'   => 1714822800,
            'local' => 1714826400,
        ];

        $this->setPluginOptions([
            'verdict_module_enabled' => 1,
        ]);

        $shortcode = new \JLG\Notation\Shortcodes\RatingBlock();
        $output    = $shortcode->render([
            'post_id'          => (string) $post_id,
            'show_verdict'     => 'oui',
            'verdict_summary'  => 'Verdict personnalisé pour le test.',
            'verdict_cta_label'=> 'Découvrir le test',
            'verdict_cta_url'  => 'https://example.com/tests/verdict-personnalise',
        ]);

        $this->assertStringContainsString('review-box-jlg__verdict', $output);
        $this->assertStringContainsString('Verdict personnalisé pour le test.', $output);
        $this->assertStringContainsString('Découvrir le test', $output);
        $this->assertStringContainsString('https://example.com/tests/verdict-personnalise', $output);
        $this->assertMatchesRegularExpression('/<time[^>]+datetime="[^"]+"/', $output);
    }

    public function test_render_hides_verdict_when_forced_off(): void
    {
        $post_id = 1321;
        $this->seedPost($post_id);
        $this->seedRatings($post_id, [
            'gameplay'   => 7.9,
            'graphismes' => 8.1,
        ]);

        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_verdict_summary'] = 'Résumé par défaut.';
        $GLOBALS['jlg_test_post_modified'][$post_id] = [
            'gmt'   => 1714822800,
            'local' => 1714826400,
        ];

        $this->setPluginOptions([
            'verdict_module_enabled' => 1,
        ]);

        $shortcode = new \JLG\Notation\Shortcodes\RatingBlock();
        $output    = $shortcode->render([
            'post_id'      => (string) $post_id,
            'show_verdict' => 'non',
        ]);

        $this->assertStringNotContainsString('review-box-jlg__verdict', $output);
    }

    private function seedPost(int $post_id, array $overrides = []): void
    {
        $defaults = [
            'ID'          => $post_id,
            'post_type'   => 'post',
            'post_status' => 'publish',
            'post_title'  => 'Post ' . $post_id,
            'post_date'   => '2024-01-01 00:00:00',
        ];

        $GLOBALS['jlg_test_posts'][$post_id] = new WP_Post(array_merge($defaults, $overrides));
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

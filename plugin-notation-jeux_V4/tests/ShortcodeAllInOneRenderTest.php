<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/Helpers.php';
require_once __DIR__ . '/../includes/Frontend.php';
require_once __DIR__ . '/../includes/Shortcodes/AllInOne.php';
require_once __DIR__ . '/../includes/Shortcodes/RatingBlock.php';

if (!function_exists('esc_attr__')) {
    function esc_attr__($text, $domain = 'default') {
        unset($domain);

        return esc_attr($text);
    }
}

class ShortcodeAllInOneRenderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['jlg_test_posts'] = [];
        $GLOBALS['jlg_test_meta'] = [];
        $GLOBALS['jlg_test_styles'] = [
            'registered' => [],
            'enqueued'   => [],
        ];
        $GLOBALS['jlg_test_options'] = [];
        $GLOBALS['jlg_test_current_post_id'] = 0;
        $GLOBALS['jlg_test_filters'] = [];

        \JLG\Notation\Helpers::flush_plugin_options_cache();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset(
            $GLOBALS['jlg_test_posts'],
            $GLOBALS['jlg_test_meta'],
            $GLOBALS['jlg_test_styles'],
            $GLOBALS['jlg_test_options'],
            $GLOBALS['jlg_test_current_post_id'],
            $GLOBALS['jlg_test_filters']
        );
    }

    public function test_render_outputs_dual_taglines_and_inline_script(): void
    {
        $post_id = 2001;
        $this->seedPost($post_id);
        $this->setPluginOptions([
            'score_layout'      => 'text',
            'visual_theme'      => 'dark',
            'score_gradient_1'  => '#336699',
            'score_gradient_2'  => '#9933cc',
            'color_high'        => '#22c55e',
            'color_low'         => '#ef4444',
            'tagline_font_size' => 18,
            'enable_animations' => 0,
        ]);

        $shortcode = new \JLG\Notation\Shortcodes\AllInOne();
        $output = $shortcode->render([
            'post_id' => (string) $post_id,
        ]);

        $this->assertNotSame('', $output);
        $this->assertMatchesRegularExpression(
            '/<button[^>]+class="jlg-aio-flag active"[^>]+data-lang="fr"[^>]*>\\s*<img[^>]+>/i',
            $output
        );
        $this->assertMatchesRegularExpression(
            '/<button[^>]+class="jlg-aio-flag"[^>]+data-lang="en"[^>]*>\\s*<img[^>]+>/i',
            $output
        );
        $this->assertMatchesRegularExpression('/<div class="jlg-aio-tagline" data-lang="fr"[^>]*>/i', $output);
        $this->assertMatchesRegularExpression('/<div class="jlg-aio-tagline" data-lang="en"[^>]*>/', $output);
        $scripts = $GLOBALS['jlg_test_scripts'] ?? [];
        $this->assertArrayHasKey('jlg-all-in-one', $scripts['enqueued'] ?? [], 'Main All-in-One script should be enqueued.');
        $this->assertArrayHasKey('jlg-all-in-one', $scripts['registered'] ?? [], 'All-in-One script should be registered.');

        $registered_src = $scripts['registered']['jlg-all-in-one']['src'] ?? '';
        $this->assertStringContainsString('assets/js/jlg-all-in-one.js', $registered_src, 'Registered script should point to the all-in-one asset.');

        $inline_scripts = $scripts['inline']['jlg-all-in-one'] ?? [];
        $this->assertNotEmpty($inline_scripts, 'Inline settings should be attached to the All-in-One script handle.');
        $this->assertStringContainsString('window.jlgAllInOneSettings', $inline_scripts[0]['code'] ?? '', 'Inline settings should initialize the global settings object.');
        $this->assertMatchesRegularExpression('/style=\"[^\"]*--jlg-aio-score-gradient: [^;]+;?/i', $output);
    }

    public function test_circle_layout_renders_border_and_glow_variables(): void
    {
        $post_id = 2002;
        $this->seedPost($post_id);
        $this->setPluginOptions([
            'score_layout'             => 'circle',
            'visual_theme'             => 'dark',
            'score_gradient_1'         => '#112233',
            'score_gradient_2'         => '#445566',
            'color_high'               => '#22c55e',
            'color_low'                => '#ef4444',
            'circle_border_enabled'    => 1,
            'circle_border_width'      => 4,
            'circle_border_color'      => '#123456',
            'circle_glow_enabled'      => 1,
            'circle_glow_color_mode'   => 'custom',
            'circle_glow_custom_color' => '#abcdef',
            'circle_glow_intensity'    => 12,
            'circle_glow_pulse'        => 1,
            'circle_glow_speed'        => 1.5,
        ]);

        $shortcode = new \JLG\Notation\Shortcodes\AllInOne();
        $output = $shortcode->render([
            'post_id' => (string) $post_id,
        ]);

        $this->assertNotSame('', $output);
        $this->assertMatchesRegularExpression('/--jlg-aio-circle-border: 4px solid #123456/i', $output);
        $this->assertMatchesRegularExpression('/--jlg-aio-circle-glow-color: #abcdef/i', $output);
        $this->assertMatchesRegularExpression('/--jlg-aio-circle-shadow: [^;]+rgba\(/i', $output);
    }

    public function test_render_supports_custom_type_from_filter(): void
    {
        add_filter('jlg_rated_post_types', static function ($types) {
            $types[] = 'jlg_review';

            return $types;
        });

        $post_id = 2003;
        $this->seedPost($post_id, 'jlg_review');
        $this->setPluginOptions([
            'score_layout'      => 'text',
            'visual_theme'      => 'dark',
            'score_gradient_1'  => '#336699',
            'score_gradient_2'  => '#9933cc',
            'color_high'        => '#22c55e',
            'color_low'         => '#ef4444',
            'tagline_font_size' => 18,
            'enable_animations' => 0,
        ]);

        $shortcode = new \JLG\Notation\Shortcodes\AllInOne();

        try {
            $output = $shortcode->render([
                'post_id' => (string) $post_id,
            ]);

            $this->assertNotSame('', $output);
            $this->assertStringContainsString("Meilleur jeu de l'année", $output);
            $this->assertStringContainsString('Univers immersif', $output);
        } finally {
            unset($GLOBALS['jlg_test_filters']['jlg_rated_post_types']);
        }
    }

    public function test_render_adds_animation_class_when_enabled(): void
    {
        $post_id = 2004;
        $this->seedPost($post_id);
        $this->setPluginOptions([
            'score_layout'      => 'text',
            'visual_theme'      => 'dark',
            'score_gradient_1'  => '#336699',
            'score_gradient_2'  => '#9933cc',
            'color_high'        => '#22c55e',
            'color_low'         => '#ef4444',
            'tagline_font_size' => 18,
            'enable_animations' => 1,
        ]);

        $shortcode = new \JLG\Notation\Shortcodes\RatingBlock();
        $output = $shortcode->render([
            'post_id' => (string) $post_id,
        ]);

        $this->assertNotSame('', $output);
        $this->assertStringContainsString('class="review-box-jlg jlg-animate"', $output);
    }

    public function test_render_includes_review_video_when_configured(): void
    {
        $post_id = 2005;
        $this->seedPost($post_id);
        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_review_video_url'] = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_review_video_provider'] = 'youtube';

        $this->setPluginOptions([
            'score_layout'      => 'text',
            'visual_theme'      => 'dark',
            'score_gradient_1'  => '#336699',
            'score_gradient_2'  => '#9933cc',
            'color_high'        => '#22c55e',
            'color_low'         => '#ef4444',
            'tagline_font_size' => 18,
            'enable_animations' => 0,
        ]);

        $shortcode = new \JLG\Notation\Shortcodes\AllInOne();
        $output    = $shortcode->render([
            'post_id'        => (string) $post_id,
            'afficher_video' => 'oui',
        ]);

        $this->assertNotSame('', $output);
        $this->assertStringContainsString('youtube-nocookie.com/embed', $output);
        $this->assertMatchesRegularExpression('/aria-labelledby="jlg-aio-video-label-[^"]+"/', $output);
        $this->assertMatchesRegularExpression('/<iframe[^>]+allowfullscreen/i', $output);
        $this->assertStringContainsString('Vidéo de test hébergée par YouTube', $output);
    }

    public function test_render_outputs_verdict_section_when_summary_provided(): void
    {
        $post_id = 2006;
        $this->seedPost($post_id);
        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_verdict_summary']    = 'Une aventure magistrale qui culmine avec un final mémorable.';
        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_verdict_cta_label']  = 'Lire notre verdict complet';
        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_verdict_cta_url']    = 'https://example.com/review';
        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_review_status']      = 'in_progress';

        $this->setPluginOptions([
            'score_layout'      => 'text',
            'visual_theme'      => 'dark',
            'score_gradient_1'  => '#336699',
            'score_gradient_2'  => '#9933cc',
            'color_high'        => '#22c55e',
            'color_low'         => '#ef4444',
            'tagline_font_size' => 18,
            'enable_animations' => 0,
        ]);

        $shortcode = new \JLG\Notation\Shortcodes\AllInOne();
        $output    = $shortcode->render([
            'post_id'          => (string) $post_id,
            'afficher_verdict' => 'oui',
        ]);

        $this->assertNotSame('', $output);
        $this->assertMatchesRegularExpression('/class="jlg-aio-verdict"/', $output);
        $this->assertStringContainsString('Lire notre verdict complet', $output);
        $this->assertMatchesRegularExpression('/jlg-aio-verdict__status--in_progress/', $output);
        $this->assertMatchesRegularExpression('/Mise à jour le/', $output);
    }

    private function seedPost(int $post_id, string $post_type = 'post'): void
    {
        $GLOBALS['jlg_test_posts'][$post_id] = new WP_Post([
            'ID'          => $post_id,
            'post_type'   => $post_type,
            'post_status' => 'publish',
            'post_modified' => '2025-10-05 12:34:00',
            'post_modified_gmt' => '2025-10-05 10:34:00',
        ]);

        $GLOBALS['jlg_test_meta'][$post_id] = [
            '_jlg_tagline_fr'            => 'Meilleur jeu de l\'année',
            '_jlg_tagline_en'            => 'Game of the year contender',
            '_jlg_points_forts'          => "Univers immersif\nCombats dynamiques",
            '_jlg_points_faibles'        => "Quêtes répétitives\nQuelques bugs",
            '_jlg_cta_label'             => 'Découvrir',
            '_jlg_cta_url'               => 'https://example.com',
            '_jlg_review_video_url'      => '',
            '_jlg_review_video_provider' => '',
        ];

        $definitions = \JLG\Notation\Helpers::get_rating_category_definitions();
        $values      = [8.5, 7.0, 9.0, 8.0, 7.5, 6.5];

        foreach ($definitions as $index => $definition) {
            if (!isset($definition['meta_key'], $values[$index])) {
                continue;
            }

            $GLOBALS['jlg_test_meta'][$post_id][$definition['meta_key']] = $values[$index];
        }
    }

    private function setPluginOptions(array $overrides): void
    {
        $defaults = \JLG\Notation\Helpers::get_default_settings();
        $options  = array_merge($defaults, $overrides);

        $GLOBALS['jlg_test_options']['notation_jlg_settings'] = $options;
        \JLG\Notation\Helpers::flush_plugin_options_cache();
    }
}

<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/Helpers.php';
require_once __DIR__ . '/../includes/Frontend.php';
require_once __DIR__ . '/../includes/Shortcodes/Tagline.php';
require_once __DIR__ . '/../includes/Shortcodes/RatingBlock.php';
require_once __DIR__ . '/../includes/Shortcodes/GameInfo.php';
require_once __DIR__ . '/../includes/Shortcodes/UserRating.php';

class ShortcodeAllowedPostTypesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['jlg_test_posts'] = [];
        $GLOBALS['jlg_test_meta'] = [];
        $GLOBALS['jlg_test_options'] = [];
        $GLOBALS['jlg_test_current_post_id'] = 0;
        $GLOBALS['jlg_test_filters'] = [];
        $GLOBALS['jlg_test_registered_post_types'] = [];

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
            $GLOBALS['jlg_test_filters'],
            $GLOBALS['jlg_test_registered_post_types']
        );
    }

    public function test_tagline_renders_for_custom_allowed_type(): void
    {
        register_post_type('jlg_review', [
            'public' => true,
            'labels' => [
                'singular_name' => 'Critique JLG',
            ],
        ]);

        $this->configureOptions([
            'allowed_post_types' => ['post', 'jlg_review'],
            'tagline_enabled'    => 1,
        ]);

        $post_id = 501;

        $GLOBALS['jlg_test_posts'][$post_id] = new WP_Post([
            'ID'          => $post_id,
            'post_type'   => 'jlg_review',
            'post_status' => 'publish',
        ]);

        $GLOBALS['jlg_test_meta'][$post_id] = [
            '_jlg_tagline_fr' => 'Tagline personnalisée FR',
            '_jlg_tagline_en' => 'Custom tagline EN',
        ];

        $GLOBALS['jlg_test_current_post_id'] = $post_id;

        $shortcode = new \JLG\Notation\Shortcodes\Tagline();
        $output = $shortcode->render();

        $this->assertNotSame('', $output);
        $this->assertStringContainsString('Tagline personnalisée FR', $output);
        $this->assertStringContainsString('Custom tagline EN', $output);
    }

    public function test_rating_block_rejects_disallowed_type(): void
    {
        $this->configureOptions([]);

        $post_id = 777;

        $GLOBALS['jlg_test_posts'][$post_id] = new WP_Post([
            'ID'          => $post_id,
            'post_type'   => 'page',
            'post_status' => 'publish',
        ]);

        $definitions      = \JLG\Notation\Helpers::get_rating_category_definitions();
        $primary_meta_key = $definitions[0]['meta_key'] ?? '_note_gameplay';

        $GLOBALS['jlg_test_meta'][$post_id] = [
            $primary_meta_key => 8.0,
        ];

        $shortcode = new \JLG\Notation\Shortcodes\RatingBlock();
        $output = $shortcode->render([
            'post_id' => (string) $post_id,
        ]);

        $this->assertSame('', $output);
    }

    public function test_game_info_renders_for_custom_allowed_type(): void
    {
        register_post_type('jlg_review', [
            'public' => true,
            'labels' => [
                'singular_name' => 'Critique JLG',
            ],
        ]);

        $this->configureOptions([
            'allowed_post_types' => ['post', 'jlg_review'],
        ]);

        $post_id = 888;

        $GLOBALS['jlg_test_posts'][$post_id] = new WP_Post([
            'ID'          => $post_id,
            'post_type'   => 'jlg_review',
            'post_status' => 'publish',
        ]);

        $GLOBALS['jlg_test_meta'][$post_id] = [
            '_jlg_developpeur' => 'Studio Test',
            '_jlg_editeur'     => 'Publisher Test',
        ];

        $GLOBALS['jlg_test_current_post_id'] = $post_id;

        $shortcode = new \JLG\Notation\Shortcodes\GameInfo();
        $output     = $shortcode->render([
            'post_id' => (string) $post_id,
        ]);

        $this->assertNotSame('', $output);
        $this->assertStringContainsString('Studio Test', $output);
        $this->assertStringContainsString('Publisher Test', $output);
    }

    public function test_game_info_rejects_disallowed_type(): void
    {
        $this->configureOptions([]);

        $post_id = 889;

        $GLOBALS['jlg_test_posts'][$post_id] = new WP_Post([
            'ID'          => $post_id,
            'post_type'   => 'page',
            'post_status' => 'publish',
        ]);

        $GLOBALS['jlg_test_meta'][$post_id] = [
            '_jlg_developpeur' => 'Studio Test',
        ];

        $GLOBALS['jlg_test_current_post_id'] = $post_id;

        $shortcode = new \JLG\Notation\Shortcodes\GameInfo();
        $output = $shortcode->render();

        $this->assertSame('', $output);
    }

    public function test_user_rating_renders_for_custom_allowed_type(): void
    {
        register_post_type('jlg_review', [
            'public' => true,
            'labels' => [
                'singular_name' => 'Critique JLG',
            ],
        ]);

        $this->configureOptions([
            'allowed_post_types' => ['post', 'jlg_review'],
        ]);

        $post_id = 990;

        $GLOBALS['jlg_test_posts'][$post_id] = new WP_Post([
            'ID'          => $post_id,
            'post_type'   => 'jlg_review',
            'post_status' => 'publish',
        ]);

        $GLOBALS['jlg_test_meta'][$post_id] = [
            '_jlg_user_rating_avg'   => '4.5',
            '_jlg_user_rating_count' => '120',
        ];

        $GLOBALS['jlg_test_current_post_id'] = $post_id;

        $shortcode = new \JLG\Notation\Shortcodes\UserRating();
        $output     = $shortcode->render();

        $this->assertNotSame('', $output);
        $this->assertStringContainsString('4.5', $output);
        $this->assertStringContainsString('120', $output);
    }

    private function configureOptions(array $overrides): void
    {
        $defaults = \JLG\Notation\Helpers::get_default_settings();
        $options  = array_merge($defaults, $overrides);

        if (isset($options['allowed_post_types'])) {
            $allowed = $options['allowed_post_types'];
            if (!is_array($allowed)) {
                $allowed = [$allowed];
            }

            $options['allowed_post_types'] = array_values($allowed);
        }

        $GLOBALS['jlg_test_options']['notation_jlg_settings'] = $options;

        \JLG\Notation\Helpers::flush_plugin_options_cache();
    }
}

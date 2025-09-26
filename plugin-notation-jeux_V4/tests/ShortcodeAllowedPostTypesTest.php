<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/class-jlg-helpers.php';
require_once __DIR__ . '/../includes/class-jlg-frontend.php';
require_once __DIR__ . '/../includes/shortcodes/class-jlg-shortcode-tagline.php';
require_once __DIR__ . '/../includes/shortcodes/class-jlg-shortcode-rating-block.php';
require_once __DIR__ . '/../includes/shortcodes/class-jlg-shortcode-game-info.php';
require_once __DIR__ . '/../includes/shortcodes/class-jlg-shortcode-user-rating.php';

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

        JLG_Helpers::flush_plugin_options_cache();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset(
            $GLOBALS['jlg_test_posts'],
            $GLOBALS['jlg_test_meta'],
            $GLOBALS['jlg_test_options'],
            $GLOBALS['jlg_test_current_post_id'],
            $GLOBALS['jlg_test_filters']
        );
    }

    public function test_tagline_renders_for_custom_allowed_type(): void
    {
        add_filter('jlg_rated_post_types', static function ($types) {
            $types[] = 'jlg_review';

            return $types;
        });

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

        $defaults = JLG_Helpers::get_default_settings();
        $GLOBALS['jlg_test_options']['notation_jlg_settings'] = array_merge($defaults, [
            'tagline_enabled' => 1,
        ]);
        JLG_Helpers::flush_plugin_options_cache();

        $GLOBALS['jlg_test_current_post_id'] = $post_id;

        $shortcode = new JLG_Shortcode_Tagline();
        $output = $shortcode->render();

        $this->assertNotSame('', $output);
        $this->assertStringContainsString('Tagline personnalisée FR', $output);
        $this->assertStringContainsString('Custom tagline EN', $output);
    }

    public function test_rating_block_rejects_disallowed_type(): void
    {
        $post_id = 777;

        $GLOBALS['jlg_test_posts'][$post_id] = new WP_Post([
            'ID'          => $post_id,
            'post_type'   => 'page',
            'post_status' => 'publish',
        ]);

        $GLOBALS['jlg_test_meta'][$post_id] = [
            '_note_cat1' => 8.0,
        ];

        $shortcode = new JLG_Shortcode_Rating_Block();
        $output = $shortcode->render([
            'post_id' => (string) $post_id,
        ]);

        $this->assertSame('', $output);
    }

    public function test_game_info_renders_for_custom_allowed_type(): void
    {
        add_filter('jlg_rated_post_types', static function ($types) {
            $types[] = 'jlg_review';

            return $types;
        });

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

        $shortcode = new JLG_Shortcode_Game_Info();

        try {
            $output = $shortcode->render();

            $this->assertNotSame('', $output);
            $this->assertStringContainsString('Studio Test', $output);
            $this->assertStringContainsString('Publisher Test', $output);
        } finally {
            unset($GLOBALS['jlg_test_filters']['jlg_rated_post_types']);
        }
    }

    public function test_game_info_rejects_disallowed_type(): void
    {
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

        $shortcode = new JLG_Shortcode_Game_Info();
        $output = $shortcode->render();

        $this->assertSame('', $output);
    }

    public function test_user_rating_renders_for_custom_allowed_type(): void
    {
        add_filter('jlg_rated_post_types', static function ($types) {
            $types[] = 'jlg_review';

            return $types;
        });

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

        $shortcode = new JLG_Shortcode_User_Rating();

        try {
            $output = $shortcode->render();

            $this->assertNotSame('', $output);
            $this->assertStringContainsString('4.5', $output);
            $this->assertStringContainsString('120', $output);
        } finally {
            unset($GLOBALS['jlg_test_filters']['jlg_rated_post_types']);
        }
    }
}

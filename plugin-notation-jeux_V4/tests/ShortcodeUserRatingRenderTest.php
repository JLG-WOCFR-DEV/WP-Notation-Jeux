<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/class-jlg-helpers.php';
require_once __DIR__ . '/../includes/class-jlg-frontend.php';
require_once __DIR__ . '/../includes/shortcodes/class-jlg-shortcode-user-rating.php';

class ShortcodeUserRatingRenderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['jlg_test_posts']         = [];
        $GLOBALS['jlg_test_meta']          = [];
        $GLOBALS['jlg_test_options']       = [];
        $GLOBALS['jlg_test_current_post_id'] = 0;
        $GLOBALS['jlg_test_filters']       = [];
        $_COOKIE                           = [];

        JLG_Helpers::flush_plugin_options_cache();
    }

    protected function tearDown(): void
    {
        unset(
            $GLOBALS['jlg_test_posts'],
            $GLOBALS['jlg_test_meta'],
            $GLOBALS['jlg_test_options'],
            $GLOBALS['jlg_test_current_post_id'],
            $GLOBALS['jlg_test_filters']
        );

        $_COOKIE = [];

        parent::tearDown();
    }

    public function test_render_adds_has_voted_class_when_user_has_voted(): void
    {
        $post_id = 1401;
        $token   = 'ABCDEF1234567890ABCDEF1234567890';
        $hash    = hash('sha256', $token);

        $_COOKIE['jlg_user_rating_token'] = $token;

        $GLOBALS['jlg_test_posts'][$post_id] = new WP_Post([
            'ID'           => $post_id,
            'post_type'    => 'post',
            'post_status'  => 'publish',
            'post_content' => '[notation_utilisateurs_jlg]',
        ]);

        $GLOBALS['jlg_test_meta'][$post_id] = [
            '_jlg_user_rating_avg'   => '4.2',
            '_jlg_user_rating_count' => '11',
            '_jlg_user_ratings'      => [
                $hash    => 5,
                '__meta' => [
                    'version'    => 2,
                    'timestamps' => [
                        $hash => current_time('timestamp'),
                    ],
                ],
            ],
        ];

        $defaults = JLG_Helpers::get_default_settings();
        $GLOBALS['jlg_test_options']['notation_jlg_settings'] = array_merge($defaults, [
            'user_rating_enabled' => 1,
        ]);
        JLG_Helpers::flush_plugin_options_cache();

        $GLOBALS['jlg_test_current_post_id'] = $post_id;

        $shortcode = new JLG_Shortcode_User_Rating();
        $output    = $shortcode->render();

        $this->assertNotSame('', $output);
        $this->assertStringContainsString('class="jlg-user-rating-block has-voted"', $output);
    }
}

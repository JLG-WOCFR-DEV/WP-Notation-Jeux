<?php

use PHPUnit\Framework\TestCase;

class FrontendAssetsEnqueueTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetEnvironment();
    }

    protected function tearDown(): void
    {
        $this->resetEnvironment();
        parent::tearDown();
    }

    public function test_game_explorer_assets_skipped_when_not_needed(): void
    {
        $this->configurePluginOptions();
        $post_id = 101;
        $this->registerPost($post_id, '[jlg_tableau_recap]');

        $frontend = new JLG_Frontend();
        $frontend->enqueue_jlg_scripts();

        $styles = $GLOBALS['jlg_test_styles']['enqueued'] ?? [];
        $inline_styles = $GLOBALS['jlg_test_inline_styles'] ?? [];

        $this->assertArrayHasKey('jlg-frontend', $styles, 'Main frontend stylesheet should always load when other assets are needed.');
        $this->assertArrayNotHasKey('jlg-game-explorer', $styles, 'Game Explorer stylesheet should be skipped when unused.');
        $this->assertArrayNotHasKey('jlg-game-explorer', $inline_styles, 'Inline Game Explorer styles should not be generated when the stylesheet is not enqueued.');
    }

    public function test_game_explorer_assets_load_when_shortcode_present(): void
    {
        $this->configurePluginOptions();
        $post_id = 202;
        $this->registerPost($post_id, '[jlg_game_explorer]');

        $frontend = new JLG_Frontend();
        $frontend->enqueue_jlg_scripts();

        $styles = $GLOBALS['jlg_test_styles']['enqueued'] ?? [];
        $inline_styles = $GLOBALS['jlg_test_inline_styles'] ?? [];

        $this->assertArrayHasKey('jlg-game-explorer', $styles, 'Game Explorer stylesheet should load when the shortcode is present.');
        $this->assertArrayHasKey('jlg-game-explorer', $inline_styles, 'Inline styles should accompany the Game Explorer stylesheet when it is enqueued.');
        $this->assertNotEmpty($inline_styles['jlg-game-explorer'][0] ?? '', 'Generated Game Explorer CSS should not be empty.');
    }

    private function registerPost(int $post_id, string $content): void
    {
        $GLOBALS['jlg_test_posts'][$post_id] = new WP_Post([
            'ID'           => $post_id,
            'post_type'    => 'post',
            'post_status'  => 'publish',
            'post_content' => $content,
        ]);

        $GLOBALS['jlg_test_current_post_id'] = $post_id;
    }

    private function configurePluginOptions(): void
    {
        $GLOBALS['jlg_test_options']['notation_jlg_settings'] = [
            'user_rating_enabled' => 0,
            'tagline_enabled'     => 0,
            'enable_animations'   => 0,
        ];

        JLG_Helpers::flush_plugin_options_cache();
    }

    private function resetEnvironment(): void
    {
        $GLOBALS['jlg_test_styles'] = [
            'registered' => [],
            'enqueued'   => [],
        ];
        $GLOBALS['jlg_test_inline_styles'] = [];
        $GLOBALS['jlg_test_scripts'] = [
            'registered' => [],
            'enqueued'   => [],
            'localized'  => [],
        ];
        $GLOBALS['jlg_test_posts'] = [];
        $GLOBALS['jlg_test_meta'] = [];
        $GLOBALS['jlg_test_options'] = [];
        $GLOBALS['jlg_test_current_post_id'] = 0;
        $GLOBALS['jlg_test_doing_ajax'] = false;
        $_REQUEST = [];

        $this->resetFrontendStatics();
        JLG_Helpers::flush_plugin_options_cache();
    }

    private function resetFrontendStatics(): void
    {
        $reflection = new ReflectionClass(JLG_Frontend::class);
        $properties = [
            'shortcode_errors'     => [],
            'instance'             => null,
            'shortcode_rendered'   => false,
            'assets_enqueued'      => false,
            'deferred_styles_hooked' => false,
            'rendered_shortcodes'  => [],
        ];

        foreach ($properties as $property => $value) {
            if ($reflection->hasProperty($property)) {
                $property_reflection = $reflection->getProperty($property);
                $property_reflection->setAccessible(true);
                $property_reflection->setValue(null, $value);
            }
        }
    }
}

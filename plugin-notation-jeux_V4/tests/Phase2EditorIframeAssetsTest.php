<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/includes/Blocks.php';

class Phase2EditorIframeAssetsTest extends TestCase
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

    public function test_blocks_register_canvas_assets_on_enqueue_block_assets(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__) . '/includes/Blocks.php');

        $this->assertStringContainsString(
            "add_action( 'enqueue_block_assets', array( \$this, 'enqueue_block_canvas_assets' ) )",
            $source,
            'Canvas CSS must load on enqueue_block_assets so WP 7.1 copies it into the editor iframe.'
        );
        $this->assertTrue(
            method_exists(\JLG\Notation\Blocks::class, 'enqueue_block_canvas_assets'),
            'Blocks must expose enqueue_block_canvas_assets().'
        );
    }

    public function test_canvas_preview_css_enqueues_in_admin_via_block_assets(): void
    {
        $GLOBALS['jlg_test_is_admin'] = true;
        $this->configurePluginOptions();

        $blocks = new \JLG\Notation\Blocks();
        $blocks->enqueue_block_canvas_assets();

        $styles = $GLOBALS['jlg_test_styles']['enqueued'] ?? [];
        $inline = $GLOBALS['jlg_test_inline_styles'] ?? [];

        $this->assertArrayHasKey(
            'jlg-frontend',
            $styles,
            'Frontend CSS must load on enqueue_block_assets so the iframed canvas can preview rating blocks.'
        );
        $this->assertArrayHasKey(
            'jlg-game-explorer',
            $styles,
            'Game Explorer CSS belongs in the iframe, not the parent editor chrome.'
        );
        $this->assertNotEmpty($inline['jlg-frontend'][0] ?? '', 'Dynamic theme CSS must be inlined on the canvas stylesheet.');
    }

    public function test_canvas_preview_css_is_not_enqueued_on_frontend(): void
    {
        $GLOBALS['jlg_test_is_admin'] = false;
        $this->configurePluginOptions();

        $blocks = new \JLG\Notation\Blocks();
        $blocks->enqueue_block_canvas_assets();

        $styles = $GLOBALS['jlg_test_styles']['enqueued'] ?? [];

        $this->assertArrayNotHasKey(
            'jlg-frontend',
            $styles,
            'Canvas CSS is editor-only and must not load on the frontend via enqueue_block_assets.'
        );
    }

    public function test_editor_parent_hook_does_not_enqueue_canvas_css(): void
    {
        $GLOBALS['jlg_test_is_admin'] = true;
        $GLOBALS['jlg_test_current_screen'] = (object) [
            'id'             => 'post',
            'is_block_editor' => true,
        ];
        $this->configurePluginOptions();

        $blocks = new \JLG\Notation\Blocks();
        $blocks->enqueue_block_editor_assets();

        $styles = $GLOBALS['jlg_test_styles']['enqueued'] ?? [];

        $this->assertArrayNotHasKey(
            'jlg-frontend',
            $styles,
            'enqueue_block_editor_assets prints into the parent document; canvas CSS must not live there in WP 7.1.'
        );
        $this->assertArrayNotHasKey('jlg-game-explorer', $styles);
    }

    public function test_frontend_scripts_are_skipped_in_block_editor_preview(): void
    {
        $this->configurePluginOptions([
            'user_rating_enabled' => 1,
            'tagline_enabled'     => 1,
            'enable_animations'   => 1,
        ]);
        $this->registerPost(404, '[bloc_notation_jeu][jlg_game_explorer]');

        $GLOBALS['jlg_test_is_admin'] = false;
        $_GET['canvas'] = 'edit';

        $frontend = new \JLG\Notation\Frontend();
        $frontend->enqueue_jlg_scripts(true);

        $scripts = $GLOBALS['jlg_test_scripts']['enqueued'] ?? [];
        $styles  = $GLOBALS['jlg_test_styles']['enqueued'] ?? [];

        $this->assertArrayHasKey('jlg-frontend', $styles, 'Preview HTML can still receive frontend CSS.');
        $this->assertArrayNotHasKey('jlg-user-rating', $scripts);
        $this->assertArrayNotHasKey('jlg-tagline-switcher', $scripts);
        $this->assertArrayNotHasKey('jlg-animations', $scripts);
        $this->assertArrayNotHasKey('jlg-game-explorer', $scripts);
    }

    public function test_rest_edit_context_is_treated_as_editor_preview(): void
    {
        $this->configurePluginOptions([
            'user_rating_enabled' => 1,
            'enable_animations'   => 1,
        ]);
        $this->registerPost(405, '[bloc_notation_jeu]');

        $_REQUEST['context'] = 'edit';
        $GLOBALS['jlg_test_doing_filters']['rest_request'] = true;

        $this->assertTrue(
            \JLG\Notation\Frontend::is_block_editor_preview_request(),
            'REST context=edit must be treated as an editor preview.'
        );

        $frontend = new \JLG\Notation\Frontend();
        $frontend->enqueue_jlg_scripts(true);

        $scripts = $GLOBALS['jlg_test_scripts']['enqueued'] ?? [];
        $this->assertArrayNotHasKey('jlg-user-rating', $scripts);
        $this->assertArrayNotHasKey('jlg-animations', $scripts);
    }

    public function test_front_requests_still_enqueue_interactive_scripts(): void
    {
        $this->configurePluginOptions([
            'user_rating_enabled' => 1,
            'enable_animations'   => 1,
        ]);
        $this->registerPost(406, '[bloc_notation_jeu]');

        $GLOBALS['jlg_test_is_admin'] = false;

        $this->assertFalse(\JLG\Notation\Frontend::is_block_editor_preview_request());

        $frontend = new \JLG\Notation\Frontend();
        $frontend->enqueue_jlg_scripts(true);

        $scripts = $GLOBALS['jlg_test_scripts']['enqueued'] ?? [];
        $this->assertArrayHasKey('jlg-user-rating', $scripts);
        $this->assertArrayHasKey('jlg-animations', $scripts);
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

    private function configurePluginOptions(array $overrides = []): void
    {
        $GLOBALS['jlg_test_options']['notation_jlg_settings'] = array_merge(
            [
                'user_rating_enabled' => 0,
                'tagline_enabled'     => 0,
                'enable_animations'   => 0,
            ],
            $overrides
        );

        \JLG\Notation\Helpers::flush_plugin_options_cache();
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
            'inline'     => [],
        ];
        $GLOBALS['jlg_test_posts'] = [];
        $GLOBALS['jlg_test_meta'] = [];
        $GLOBALS['jlg_test_options'] = [];
        $GLOBALS['jlg_test_current_post_id'] = 0;
        $GLOBALS['jlg_test_doing_ajax'] = false;
        $GLOBALS['jlg_test_is_admin'] = false;
        $GLOBALS['jlg_test_is_block_editor'] = false;
        $GLOBALS['jlg_test_current_screen'] = null;
        $GLOBALS['jlg_test_doing_filters'] = [];
        $_GET = [];
        $_REQUEST = [];

        $this->resetFrontendStatics();
        \JLG\Notation\Helpers::flush_plugin_options_cache();
    }

    private function resetFrontendStatics(): void
    {
        $reflection = new ReflectionClass(\JLG\Notation\Frontend::class);
        $properties = [
            'shortcode_errors'       => [],
            'instance'               => null,
            'shortcode_rendered'     => false,
            'assets_enqueued'        => false,
            'deferred_styles_hooked' => false,
            'rendered_shortcodes'    => [],
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

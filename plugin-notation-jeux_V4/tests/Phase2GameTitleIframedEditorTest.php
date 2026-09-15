<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/Admin/Metaboxes.php';

class Phase2GameTitleIframedEditorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['jlg_test_registered_post_meta'] = [];
        $GLOBALS['jlg_test_scripts'] = [
            'registered' => [],
            'enqueued'   => [],
            'localized'  => [],
            'inline'     => [],
        ];
        $GLOBALS['jlg_test_is_block_editor'] = false;
        $GLOBALS['jlg_test_posts'] = [];
        $GLOBALS['jlg_test_meta'] = [];
        $GLOBALS['jlg_test_options'] = [];
        $_GET = [];

        \JLG\Notation\Helpers::flush_plugin_options_cache();
    }

    protected function tearDown(): void
    {
        $GLOBALS['jlg_test_is_block_editor'] = false;
        $GLOBALS['jlg_test_registered_post_meta'] = [];
        $_GET = [];

        \JLG\Notation\Helpers::flush_plugin_options_cache();

        parent::tearDown();
    }

    public function test_game_title_meta_is_exposed_to_block_editor_rest(): void
    {
        $metaboxes = new \JLG\Notation\Admin\Metaboxes();
        $metaboxes->register_game_title_meta();

        $registered = $GLOBALS['jlg_test_registered_post_meta']['post']['_jlg_game_title'] ?? null;

        $this->assertIsArray($registered, '_jlg_game_title must be registered for the post type.');
        $this->assertTrue(
            !empty($registered['show_in_rest']),
            'Gutenberg can only edit the game title in the parent document when the meta is in REST.'
        );
        $this->assertTrue(!empty($registered['single']));
        $this->assertSame('string', $registered['type'] ?? null);
        $this->assertArrayHasKey('auth_callback', $registered);
        $this->assertArrayHasKey('sanitize_callback', $registered);
    }

    public function test_document_panel_script_exposes_game_title_outside_the_iframe(): void
    {
        $script = dirname(__DIR__) . '/assets/js/admin/game-title-document-panel.js';

        $this->assertFileExists(
            $script,
            'A parent-document Gutenberg plugin must expose #jlg_game_title outside the iframed canvas.'
        );

        $source = (string) file_get_contents($script);

        $this->assertStringContainsString('registerPlugin', $source);
        $this->assertStringContainsString('PluginDocumentSettingPanel', $source);
        $this->assertStringContainsString('jlg_game_title', $source);
        $this->assertStringContainsString('_jlg_game_title', $source);
        $this->assertStringContainsString('wp.editPost', $source);
    }

    public function test_document_panel_is_enqueued_on_parent_editor_assets_hook(): void
    {
        $php = (string) file_get_contents(dirname(__DIR__) . '/includes/Admin/Metaboxes.php');

        $this->assertStringContainsString(
            "add_action( 'enqueue_block_editor_assets', array( \$this, 'enqueue_game_title_document_panel' ) )",
            $php,
            'The game title panel must load on enqueue_block_editor_assets (parent document), not the iframe hook.'
        );

        $metaboxes = new \JLG\Notation\Admin\Metaboxes();
        $metaboxes->enqueue_game_title_document_panel();

        $registered = $GLOBALS['jlg_test_scripts']['registered']['notation-jlg-game-title-document-panel'] ?? null;
        $enqueued   = $GLOBALS['jlg_test_scripts']['enqueued']['notation-jlg-game-title-document-panel'] ?? null;

        $this->assertIsArray($registered);
        $this->assertNotEmpty($enqueued);
        $this->assertContains('wp-plugins', $registered['deps']);
        $this->assertContains('wp-edit-post', $registered['deps']);
        $this->assertContains('wp-data', $registered['deps']);
        $this->assertContains('wp-element', $registered['deps']);
        $this->assertStringContainsString('game-title-document-panel.js', (string) $registered['src']);
    }

    public function test_block_editor_metabox_does_not_keep_the_covered_game_title_id(): void
    {
        $GLOBALS['jlg_test_is_block_editor'] = true;

        $metaboxes = new \JLG\Notation\Admin\Metaboxes();
        $php       = (string) file_get_contents(dirname(__DIR__) . '/includes/Admin/Metaboxes.php');

        $this->assertSame(
            'jlg_game_title_metabox',
            $metaboxes->get_game_title_input_id(),
            'The PHP metabox under the iframe must not occupy #jlg_game_title in the block editor.'
        );
        $this->assertStringContainsString('get_game_title_input_id()', $php);
        $this->assertStringContainsString('name="jlg_game_title"', $php);
    }

    public function test_classic_editor_still_renders_game_title_input(): void
    {
        $GLOBALS['jlg_test_is_block_editor'] = false;

        $metaboxes = new \JLG\Notation\Admin\Metaboxes();
        $php       = (string) file_get_contents(dirname(__DIR__) . '/includes/Admin/Metaboxes.php');

        $this->assertSame('jlg_game_title', $metaboxes->get_game_title_input_id());
        $this->assertStringContainsString('get_game_title_input_id()', $php);
        $this->assertStringContainsString('name="jlg_game_title"', $php);
    }
}

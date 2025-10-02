<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/shortcodes/class-jlg-shortcode-game-info.php';

class ShortcodeGameInfoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['jlg_test_posts'] = [];
        $GLOBALS['jlg_test_meta'] = [];
        $GLOBALS['jlg_test_current_post_id'] = 0;
        $GLOBALS['jlg_test_current_user_can'] = static function () {
            return false;
        };
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['jlg_test_current_user_can']);

        parent::tearDown();
    }

    public function test_render_with_valid_post_id_returns_template(): void
    {
        $post_id = 321;
        $this->register_post($post_id, 'publish');

        $GLOBALS['jlg_test_meta'][$post_id] = [
            '_jlg_developpeur' => 'Studio <b>X</b>',
            '_jlg_plateformes' => ['PC', 'Switch ', '<script>'],
        ];

        $shortcode = new \JLG\Notation\Shortcodes\GameInfo();
        $output = $shortcode->render([
            'post_id' => (string) $post_id,
            'champs'  => 'developpeur,plateformes',
        ]);

        $this->assertNotSame('', $output);
        $this->assertStringContainsString('Studio X', $output);
        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringContainsString('<span>PC</span>', $output);
        $this->assertStringContainsString('<span>Switch</span>', $output);
    }

    public function test_render_with_draft_post_returns_empty(): void
    {
        $post_id = 654;
        $this->register_post($post_id, 'draft');
        $GLOBALS['jlg_test_meta'][$post_id] = [
            '_jlg_developpeur' => 'Studio Y',
        ];

        $shortcode = new \JLG\Notation\Shortcodes\GameInfo();
        $output = $shortcode->render(['post_id' => $post_id]);

        $this->assertSame('', $output);
    }

    public function test_render_with_draft_post_and_authorized_user_returns_template(): void
    {
        $post_id = 987;
        $this->register_post($post_id, 'draft');
        $GLOBALS['jlg_test_meta'][$post_id] = [
            '_jlg_developpeur' => 'Studio Draft',
        ];

        $previous_user_can = $GLOBALS['jlg_test_current_user_can'];
        $GLOBALS['jlg_test_current_user_can'] = static function ($capability, $target_post_id = null) use ($post_id) {
            return $capability === 'read_post' && (int) $target_post_id === $post_id;
        };

        try {
            $shortcode = new \JLG\Notation\Shortcodes\GameInfo();
            $output = $shortcode->render([
                'post_id' => (string) $post_id,
                'champs'  => 'developpeur',
            ]);

            $this->assertNotSame('', $output);
            $this->assertStringContainsString('Studio Draft', $output);
        } finally {
            $GLOBALS['jlg_test_current_user_can'] = $previous_user_can;
        }
    }

    public function test_render_uses_current_context_when_valid(): void
    {
        $post_id = 777;
        $this->register_post($post_id, 'publish');
        $GLOBALS['jlg_test_meta'][$post_id] = [
            '_jlg_editeur' => 'Publisher Z',
        ];

        $GLOBALS['jlg_test_current_post_id'] = $post_id;

        $shortcode = new \JLG\Notation\Shortcodes\GameInfo();
        $output = $shortcode->render(['champs' => 'editeur']);

        $this->assertNotSame('', $output);
        $this->assertStringContainsString('Publisher Z', $output);
        $this->assertStringContainsString('Éditeur', $output);
    }

    private function register_post(int $post_id, string $status): void
    {
        $GLOBALS['jlg_test_posts'][$post_id] = new WP_Post([
            'ID'          => $post_id,
            'post_type'   => 'post',
            'post_status' => $status,
        ]);
    }
}

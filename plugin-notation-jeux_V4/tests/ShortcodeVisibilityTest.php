<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/Shortcodes/AllInOne.php';
require_once __DIR__ . '/../includes/Shortcodes/RatingBlock.php';

class ShortcodeVisibilityTest extends TestCase
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

        unset($GLOBALS['jlg_test_current_user_can']);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['jlg_test_current_user_can']);

        parent::tearDown();
    }

    public function test_all_in_one_returns_empty_for_draft_without_permission(): void
    {
        $post_id = 101;
        $this->registerSamplePost($post_id, 'draft');
        $this->deny_read_permissions();

        $shortcode = new \JLG\Notation\Shortcodes\AllInOne();
        $output = $shortcode->render(['post_id' => $post_id]);

        $this->assertSame('', $output);
    }

    public function test_rating_block_returns_empty_for_private_without_permission(): void
    {
        $post_id = 202;
        $this->registerSamplePost($post_id, 'private');
        $this->deny_read_permissions();

        $shortcode = new \JLG\Notation\Shortcodes\RatingBlock();
        $output = $shortcode->render(['post_id' => $post_id]);

        $this->assertSame('', $output);
    }

    private function registerSamplePost(int $post_id, string $status): void
    {
        $GLOBALS['jlg_test_posts'][$post_id] = new WP_Post([
            'ID'          => $post_id,
            'post_type'   => 'post',
            'post_status' => $status,
        ]);

        $GLOBALS['jlg_test_meta'][$post_id] = [
            '_jlg_tagline_fr'     => 'Un super résumé',
            '_jlg_tagline_en'     => 'A great summary',
            '_jlg_points_forts'   => "Point fort 1\nPoint fort 2",
            '_jlg_points_faibles' => "Point faible 1\nPoint faible 2",
        ];

        $definitions = \JLG\Notation\Helpers::get_rating_category_definitions();
        $values      = ['8', '7', '9', '6', '8', '7'];

        foreach ($definitions as $index => $definition) {
            if (!isset($definition['meta_key'], $values[$index])) {
                continue;
            }

            $GLOBALS['jlg_test_meta'][$post_id][$definition['meta_key']] = $values[$index];
        }
    }

    private function deny_read_permissions(): void
    {
        $GLOBALS['jlg_test_current_user_can'] = static function ($capability, $post_id = null) {
            if ($capability === 'read_post') {
                return false;
            }

            return true;
        };
    }
}

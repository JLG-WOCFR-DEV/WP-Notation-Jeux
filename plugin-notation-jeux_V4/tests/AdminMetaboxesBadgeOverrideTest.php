<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/Helpers.php';
require_once __DIR__ . '/../includes/Admin/Metaboxes.php';

class AdminMetaboxesBadgeOverrideTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['jlg_test_posts'] = [];
        $GLOBALS['jlg_test_meta'] = [];
        $_POST = [];

        \JLG\Notation\Helpers::flush_plugin_options_cache();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($GLOBALS['jlg_test_posts'], $GLOBALS['jlg_test_meta']);
        $_POST = [];
    }

    public function test_save_meta_data_persists_force_on_override(): void
    {
        $post_id = 2101;
        $this->seedPost($post_id);

        $_POST['_jlg_rating_badge_override'] = 'force-on';
        $_POST['jlg_details_nonce'] = 'nonce';

        $metaboxes = new \JLG\Notation\Admin\Metaboxes();
        $metaboxes->save_meta_data($post_id);

        $this->assertSame('force-on', get_post_meta($post_id, '_jlg_rating_badge_override', true));
    }

    public function test_save_meta_data_deletes_auto_override(): void
    {
        $post_id = 2102;
        $this->seedPost($post_id);

        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_rating_badge_override'] = 'force-off';

        $_POST['_jlg_rating_badge_override'] = 'auto';
        $_POST['jlg_details_nonce'] = 'nonce';

        $metaboxes = new \JLG\Notation\Admin\Metaboxes();
        $metaboxes->save_meta_data($post_id);

        $this->assertSame('', get_post_meta($post_id, '_jlg_rating_badge_override', true));
    }

    private function seedPost(int $post_id): void
    {
        $GLOBALS['jlg_test_posts'][$post_id] = new WP_Post([
            'ID'          => $post_id,
            'post_type'   => 'post',
            'post_status' => 'publish',
        ]);
    }
}

<?php

use PHPUnit\Framework\TestCase;

class HelpersRatingCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['jlg_test_meta'] = [];
        $GLOBALS['jlg_test_transients'] = [];
        $GLOBALS['jlg_test_actions'] = [];
    }

    public function test_invalidate_average_score_cache_clears_transient_and_meta(): void
    {
        $post_id = 123;

        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_average_score'] = 8.7;
        set_transient('jlg_rated_post_ids_v1', [11, 22]);

        \JLG\Notation\Helpers::invalidate_average_score_cache($post_id);

        $this->assertArrayNotHasKey('_jlg_average_score', $GLOBALS['jlg_test_meta'][$post_id] ?? []);
        $this->assertFalse(get_transient('jlg_rated_post_ids_v1'));
        $this->assertSame([
            ['jlg_queue_average_rebuild', [[123]]],
        ], $GLOBALS['jlg_test_actions']);
    }

    public function test_maybe_handle_rating_meta_change_triggers_invalidation_for_ratings(): void
    {
        $post_id = 456;

        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_average_score'] = 9.1;
        set_transient('jlg_rated_post_ids_v1', [77]);

        \JLG\Notation\Helpers::maybe_handle_rating_meta_change(0, $post_id, '_note_cat1', '9.5');

        $this->assertArrayNotHasKey('_jlg_average_score', $GLOBALS['jlg_test_meta'][$post_id] ?? []);
        $this->assertFalse(get_transient('jlg_rated_post_ids_v1'));
    }

    public function test_maybe_handle_rating_meta_change_ignores_other_meta_keys(): void
    {
        $post_id = 789;

        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_average_score'] = 5.5;
        set_transient('jlg_rated_post_ids_v1', [88]);

        \JLG\Notation\Helpers::maybe_handle_rating_meta_change(0, $post_id, '_some_other_meta');

        $this->assertArrayHasKey('_jlg_average_score', $GLOBALS['jlg_test_meta'][$post_id] ?? []);
        $this->assertSame([88], get_transient('jlg_rated_post_ids_v1'));
    }

    public function test_status_transition_from_publish_clears_rated_cache(): void
    {
        $post_id = 987;

        $post = new WP_Post([
            'ID' => $post_id,
            'post_type' => 'post',
        ]);

        $GLOBALS['jlg_test_posts'][$post_id] = $post;
        $GLOBALS['jlg_test_meta'][$post_id]['_note_cat1'] = '9.0';

        set_transient('jlg_rated_post_ids_v1', [123, 456]);

        \JLG\Notation\Helpers::maybe_clear_rated_post_ids_cache_for_status_change('draft', 'publish', $post);

        $this->assertFalse(get_transient('jlg_rated_post_ids_v1'));
    }
}

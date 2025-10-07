<?php

use PHPUnit\Framework\TestCase;

class HelpersRelatedGuidesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['jlg_test_posts']      = [];
        $GLOBALS['jlg_test_terms']      = [];
        $GLOBALS['jlg_test_permalinks'] = [];

        \JLG\Notation\Helpers::flush_plugin_options_cache();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset(
            $GLOBALS['jlg_test_posts'],
            $GLOBALS['jlg_test_terms'],
            $GLOBALS['jlg_test_permalinks']
        );

        \JLG\Notation\Helpers::flush_plugin_options_cache();
    }

    public function test_get_related_guides_returns_empty_when_feature_disabled(): void
    {
        $post_id = 910;
        $this->seedPost($post_id);

        $options = array_merge(
            \JLG\Notation\Helpers::get_default_settings(),
            [
                'related_guides_enabled' => 0,
            ]
        );

        $this->assertSame([], \JLG\Notation\Helpers::get_related_guides_for_post($post_id, $options));
    }

    public function test_get_related_guides_limits_results_and_returns_payload(): void
    {
        $post_id = 915;
        $this->seedPost($post_id);
        $this->assignTerms($post_id, 'guide', [11]);

        $options = array_merge(
            \JLG\Notation\Helpers::get_default_settings(),
            [
                'related_guides_enabled'   => 1,
                'related_guides_limit'     => 2,
                'related_guides_taxonomies' => 'guide',
            ]
        );

        $this->seedGuide(2010, 'Soluce 100%', '2024-02-12 10:00:00', [11]);
        $this->seedGuide(2011, 'Build PvP', '2024-02-10 08:00:00', [11]);
        $this->seedGuide(2012, 'Astuces multi', '2024-02-05 09:00:00', [22]);
        $this->seedGuide(2013, 'Guide endgame', '2024-02-08 12:00:00', [11]);

        $guides = \JLG\Notation\Helpers::get_related_guides_for_post($post_id, $options);

        $this->assertCount(2, $guides, 'Only the configured number of guides should be returned.');

        $ids = array_column($guides, 'id');
        $this->assertSame([2010, 2011], $ids, 'Results should be sorted by recency and limited.');

        $this->assertSame('Soluce 100%', $guides[0]['title']);
        $this->assertSame('https://example.com/guides/soluce-100', $guides[0]['url']);
    }

    private function seedPost(int $post_id, array $overrides = []): void
    {
        $defaults = [
            'ID'          => $post_id,
            'post_type'   => 'post',
            'post_status' => 'publish',
            'post_title'  => 'Test post ' . $post_id,
            'post_date'   => '2024-01-01 00:00:00',
        ];

        $GLOBALS['jlg_test_posts'][$post_id] = new WP_Post(array_merge($defaults, $overrides));
    }

    private function assignTerms(int $post_id, string $taxonomy, array $term_ids): void
    {
        $GLOBALS['jlg_test_terms'][$post_id][$taxonomy] = array_map(
            static function ($term_id) {
                return [
                    'term_id' => (int) $term_id,
                ];
            },
            $term_ids
        );
    }

    private function seedGuide(int $post_id, string $title, string $date, array $term_ids): void
    {
        $this->seedPost($post_id, [
            'post_title' => $title,
            'post_date'  => $date,
        ]);
        $this->assignTerms($post_id, 'guide', $term_ids);
        $GLOBALS['jlg_test_permalinks'][$post_id] = sprintf('https://example.com/guides/%s', sanitize_title($title));
    }
}

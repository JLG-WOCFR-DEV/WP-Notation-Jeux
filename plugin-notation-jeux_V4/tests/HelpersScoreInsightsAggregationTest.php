<?php

use PHPUnit\Framework\TestCase;

class HelpersScoreInsightsAggregationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['jlg_test_meta'] = [];
        $GLOBALS['jlg_test_options'] = [];

        \JLG\Notation\Helpers::flush_plugin_options_cache();
    }

    protected function tearDown(): void
    {
        \JLG\Notation\Helpers::flush_plugin_options_cache();

        unset($GLOBALS['jlg_test_meta'], $GLOBALS['jlg_test_options']);

        parent::tearDown();
    }

    public function test_platform_rankings_are_sorted_and_labelled(): void
    {
        $post_ids = [101, 202, 303];

        $GLOBALS['jlg_test_meta'][101] = [
            '_jlg_average_score' => '9.2',
            '_jlg_plateformes'   => ['PlayStation 5', 'PC'],
        ];

        $GLOBALS['jlg_test_meta'][202] = [
            '_jlg_average_score' => '8.4',
            '_jlg_plateformes'   => 'PlayStation 5, Xbox Series X',
        ];

        $GLOBALS['jlg_test_meta'][303] = [
            '_jlg_average_score' => '7.5',
        ];

        $insights = \JLG\Notation\Helpers::get_posts_score_insights($post_ids);

        $this->assertSame(3, $insights['total']);
        $this->assertSame(8.4, $insights['mean']['value']);
        $this->assertSame('8.4', $insights['mean']['formatted']);
        $this->assertSame(8.4, $insights['median']['value']);

        $distribution_total = array_sum(array_map(static function ($bucket) {
            return $bucket['count'];
        }, $insights['distribution']));
        $this->assertSame(3, $distribution_total, 'Distribution buckets should cover every scored post.');

        $this->assertCount(4, $insights['platform_rankings']);

        $top_platform = $insights['platform_rankings'][0];
        $this->assertSame('pc', $top_platform['slug']);
        $this->assertSame('PC', $top_platform['label']);
        $this->assertSame(1, $top_platform['count']);
        $this->assertSame(9.2, $top_platform['average']);
        $this->assertSame('9.2', $top_platform['average_formatted']);

        $playstation = $insights['platform_rankings'][1];
        $this->assertSame('playstation-5', $playstation['slug']);
        $this->assertSame(2, $playstation['count']);
        $this->assertSame(8.8, $playstation['average']);
        $this->assertSame('8.8', $playstation['average_formatted']);

        $xbox = $insights['platform_rankings'][2];
        $this->assertSame('xbox-series-x', $xbox['slug']);
        $this->assertSame('Xbox Series S/X', $xbox['label']);
        $this->assertSame(1, $xbox['count']);
        $this->assertSame(8.4, $xbox['average']);

        $unknown = $insights['platform_rankings'][3];
        $this->assertSame('sans-plateforme', $unknown['slug']);
        $this->assertSame('Sans plateforme', $unknown['label']);
        $this->assertSame(1, $unknown['count']);
        $this->assertSame(7.5, $unknown['average']);
        $this->assertSame('7.5', $unknown['average_formatted']);
    }

    public function test_returns_empty_summary_when_no_scores(): void
    {
        $insights = \JLG\Notation\Helpers::get_posts_score_insights([999]);

        $this->assertSame(0, $insights['total']);
        $this->assertNull($insights['mean']['value']);
        $this->assertNull($insights['median']['value']);
        $this->assertSame([], $insights['platform_rankings']);

        $distribution_total = array_sum(array_map(static function ($bucket) {
            return $bucket['count'];
        }, $insights['distribution']));
        $this->assertSame(0, $distribution_total, 'Distribution should be empty when no scores are present.');
    }
}

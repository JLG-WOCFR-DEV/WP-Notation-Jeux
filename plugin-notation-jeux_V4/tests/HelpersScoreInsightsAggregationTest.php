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
            '_jlg_average_score'     => '9.2',
            '_jlg_plateformes'       => ['PlayStation 5', 'PC'],
            '_jlg_user_rating_avg'   => '9.3',
            '_jlg_user_rating_count' => '86',
        ];

        $GLOBALS['jlg_test_meta'][202] = [
            '_jlg_average_score'     => '8.4',
            '_jlg_plateformes'       => 'PlayStation 5, Xbox Series X',
            '_jlg_user_rating_avg'   => '6.0',
            '_jlg_user_rating_count' => '120',
        ];

        $GLOBALS['jlg_test_meta'][303] = [
            '_jlg_average_score'     => '7.5',
            '_jlg_user_rating_avg'   => '9.8',
            '_jlg_user_rating_count' => '54',
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

        $this->assertSame(1.5, $insights['badge_threshold']);
        $this->assertCount(2, $insights['divergence_badges']);

        $consensus = $insights['consensus'];
        $this->assertTrue($consensus['available']);
        $this->assertSame('medium', $consensus['level']);
        $this->assertSame('Consensus partagé', $consensus['level_label']);
        $this->assertSame('Quelques écarts existent entre les critiques : surveillez les mises à jour.', $consensus['message']);
        $this->assertSame('0.7', $consensus['deviation_formatted']);
        $this->assertSame('Écart-type : 0.7', $consensus['deviation_label']);
        $this->assertSame('Notes entre 7.5 et 9.2 (écart de 1.7 point(s)).', $consensus['range']['label']);
        $this->assertSame(3, $consensus['sample']['count']);
        $this->assertSame('Basé sur 3 tests publiés', $consensus['sample']['label']);

        $largest_gap = $insights['divergence_badges'][0];
        $this->assertSame(202, $largest_gap['post_id']);
        $this->assertSame('negative', $largest_gap['direction']);
        $this->assertSame(120, $largest_gap['user_rating_count']);
        $this->assertEqualsWithDelta(-2.4, $largest_gap['delta'], 0.01);
        $this->assertEqualsWithDelta(2.4, $largest_gap['absolute_delta'], 0.01);
        $this->assertSame('-2.4', $largest_gap['delta_formatted']);
        $this->assertSame('6.0', $largest_gap['user_score_formatted']);
        $this->assertSame('8.4', $largest_gap['editorial_score_formatted']);

        $positive_gap = $insights['divergence_badges'][1];
        $this->assertSame(303, $positive_gap['post_id']);
        $this->assertSame('positive', $positive_gap['direction']);
        $this->assertSame(54, $positive_gap['user_rating_count']);
        $this->assertEqualsWithDelta(2.3, $positive_gap['delta'], 0.01);
        $this->assertEqualsWithDelta(2.3, $positive_gap['absolute_delta'], 0.01);
        $this->assertSame('+2.3', $positive_gap['delta_formatted']);
        $this->assertSame('9.8', $positive_gap['user_score_formatted']);
        $this->assertSame('7.5', $positive_gap['editorial_score_formatted']);
    }

    public function test_returns_empty_summary_when_no_scores(): void
    {
        $insights = \JLG\Notation\Helpers::get_posts_score_insights([999]);

        $this->assertSame(0, $insights['total']);
        $this->assertNull($insights['mean']['value']);
        $this->assertNull($insights['median']['value']);
        $this->assertSame([], $insights['platform_rankings']);
        $this->assertSame([], $insights['divergence_badges']);
        $this->assertSame(1.5, $insights['badge_threshold']);
        $this->assertFalse($insights['consensus']['available']);
        $this->assertSame('Aucun test', $insights['consensus']['level_label']);
        $this->assertSame(0, $insights['consensus']['sample']['count']);
        $this->assertSame('Aucun test pris en compte', $insights['consensus']['sample']['label']);

        $distribution_total = array_sum(array_map(static function ($bucket) {
            return $bucket['count'];
        }, $insights['distribution']));
        $this->assertSame(0, $distribution_total, 'Distribution should be empty when no scores are present.');
    }

    public function test_badge_threshold_filter_is_applied(): void
    {
        $callback = static function () {
            return 3.0;
        };

        add_filter('jlg_score_insights_badge_threshold', $callback);

        $GLOBALS['jlg_test_meta'][404] = [
            '_jlg_average_score'     => '7.0',
            '_jlg_user_rating_avg'   => '9.5',
            '_jlg_user_rating_count' => '40',
        ];

        $insights = \JLG\Notation\Helpers::get_posts_score_insights([404]);

        $this->assertSame(3.0, $insights['badge_threshold']);
        $this->assertSame([], $insights['divergence_badges'], 'Badge threshold filter should hide smaller deltas.');
        $this->assertFalse($insights['consensus']['available']);
        $this->assertSame('Échantillon limité', $insights['consensus']['level_label']);
        $this->assertSame('Notes entre 7.0 et 7.0 (écart de 0.0 point(s)).', $insights['consensus']['range']['label']);
        $this->assertSame(1, $insights['consensus']['sample']['count']);
        $this->assertSame('Basé sur 1 test publié', $insights['consensus']['sample']['label']);

        remove_filter('jlg_score_insights_badge_threshold', $callback);
    }
}

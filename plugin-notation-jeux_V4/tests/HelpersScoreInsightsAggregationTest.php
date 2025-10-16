<?php

use PHPUnit\Framework\TestCase;

class HelpersScoreInsightsAggregationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['jlg_test_meta'] = [];
        $GLOBALS['jlg_test_options'] = [];
        $GLOBALS['jlg_test_posts'] = [];
        $GLOBALS['jlg_test_permalinks'] = [];

        \JLG\Notation\Helpers::flush_plugin_options_cache();
        \JLG\Notation\Helpers::flush_score_insights_cache();
    }

    protected function tearDown(): void
    {
        \JLG\Notation\Helpers::flush_plugin_options_cache();
        \JLG\Notation\Helpers::flush_score_insights_cache();

        unset($GLOBALS['jlg_test_meta'], $GLOBALS['jlg_test_options'], $GLOBALS['jlg_test_posts'], $GLOBALS['jlg_test_permalinks']);

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
            '_jlg_points_forts'      => "Immersion totale\nDirection artistique",
        ];

        $GLOBALS['jlg_test_meta'][202] = [
            '_jlg_average_score'     => '8.4',
            '_jlg_plateformes'       => 'PlayStation 5, Xbox Series X',
            '_jlg_user_rating_avg'   => '6.0',
            '_jlg_user_rating_count' => '120',
            '_jlg_points_forts'      => "Immersion totale\nScénario prenant",
            '_jlg_points_faibles'    => "Loadings longs\nBugs critiques",
        ];

        $GLOBALS['jlg_test_meta'][303] = [
            '_jlg_average_score'     => '7.5',
            '_jlg_user_rating_avg'   => '9.8',
            '_jlg_user_rating_count' => '54',
            '_jlg_points_faibles'    => "Bugs critiques\nInterface confuse",
        ];

        $GLOBALS['jlg_test_posts'][101] = (object) [
            'ID'            => 101,
            'post_title'    => 'Chronique PS5',
            'post_date'     => '2025-01-10 10:00:00',
            'post_date_gmt' => '2025-01-10 09:00:00',
        ];
        $GLOBALS['jlg_test_posts'][202] = (object) [
            'ID'            => 202,
            'post_title'    => 'Comparatif multi-plateformes',
            'post_date'     => '2025-01-18 18:45:00',
            'post_date_gmt' => '2025-01-18 17:45:00',
        ];
        $GLOBALS['jlg_test_posts'][303] = (object) [
            'ID'            => 303,
            'post_title'    => 'Review sans plateforme',
            'post_date'     => '2025-02-05 09:30:00',
            'post_date_gmt' => '2025-02-05 08:30:00',
        ];

        $GLOBALS['jlg_test_permalinks'][101] = 'https://example.com/tests/chronique-ps5';
        $GLOBALS['jlg_test_permalinks'][202] = 'https://example.com/tests/comparatif-multi-plateformes';
        $GLOBALS['jlg_test_permalinks'][303] = 'https://example.com/tests/review-sans-plateforme';

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
        $this->assertSame('medium', $consensus['confidence']['level']);
        $this->assertSame('Confiance modérée', $consensus['confidence']['label']);
        $this->assertStringContainsString('Les tendances se dessinent', $consensus['confidence']['message']);

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

        $segments = $insights['segments'];
        $this->assertTrue($segments['available']);
        $this->assertSame(3, $segments['editorial']['count']);
        $this->assertSame('8.4', $segments['editorial']['average_formatted']);
        $this->assertSame('7.9', $segments['readers']['average_formatted']);
        $this->assertSame(260, $segments['readers']['votes']);
        $this->assertSame(-0.5, $segments['delta']['value']);
        $this->assertSame('-0.5', $segments['delta']['formatted']);
        $this->assertSame('negative', $segments['delta']['direction']);

        $timeline = $insights['timeline'];
        $this->assertTrue($timeline['available']);
        $this->assertCount(3, $timeline['points']);
        $this->assertSame('January 10, 2025', $timeline['points'][0]['date_label']);
        $this->assertNotSame('', $timeline['sparkline']['editorial_path']);

        $sentiments = $insights['sentiments'];
        $this->assertTrue($sentiments['available']);
        $this->assertSame('Immersion totale', $sentiments['pros'][0]['label']);
        $this->assertSame(2, $sentiments['pros'][0]['count']);
        $this->assertSame('Bugs critiques', $sentiments['cons'][0]['label']);
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
        $this->assertSame('none', $insights['consensus']['confidence']['level']);
        $this->assertSame('Confiance indisponible', $insights['consensus']['confidence']['label']);

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
        $this->assertSame('low', $insights['consensus']['confidence']['level']);
        $this->assertSame('Confiance limitée', $insights['consensus']['confidence']['label']);

        remove_filter('jlg_score_insights_badge_threshold', $callback);
    }

    public function test_confidence_threshold_filter_adjusts_level(): void
    {
        $threshold_filter = static function () {
            return array(
                'medium' => 2,
                'high'   => 4,
            );
        };

        add_filter('jlg_score_insights_confidence_thresholds', $threshold_filter);

        $post_ids = [11, 12, 13, 14];

        foreach ($post_ids as $index => $post_id) {
            $score = 7.0 + ($index * 0.2);
            $GLOBALS['jlg_test_meta'][$post_id] = [
                '_jlg_average_score'     => (string) $score,
                '_jlg_user_rating_avg'   => '7.0',
                '_jlg_user_rating_count' => '10',
            ];
        }

        $insights = \JLG\Notation\Helpers::get_posts_score_insights($post_ids);

        $this->assertSame(4, $insights['consensus']['sample']['count']);
        $this->assertSame('high', $insights['consensus']['confidence']['level']);
        $this->assertSame('Confiance élevée', $insights['consensus']['confidence']['label']);

        remove_filter('jlg_score_insights_confidence_thresholds', $threshold_filter);
    }

    public function test_score_insights_cache_is_refreshed_after_invalidation(): void
    {
        $ttl_filter = static function ($ttl, $post_ids, $time_range) {
            unset($ttl, $post_ids, $time_range);

            return 3600;
        };

        add_filter('jlg_score_insights_cache_ttl', $ttl_filter, 10, 3);

        $post_id = 909;

        $GLOBALS['jlg_test_meta'][$post_id] = [
            '_jlg_average_score'     => '8.0',
            '_jlg_user_rating_avg'   => '7.5',
            '_jlg_user_rating_count' => '42',
            '_jlg_plateformes'       => ['PC'],
        ];

        $GLOBALS['jlg_test_posts'][$post_id] = (object) [
            'ID'            => $post_id,
            'post_title'    => 'Cache invalidation test',
            'post_date'     => '2025-03-01 12:00:00',
            'post_date_gmt' => '2025-03-01 11:00:00',
        ];

        $GLOBALS['jlg_test_permalinks'][$post_id] = 'https://example.com/tests/cache-invalidation';

        $initial = \JLG\Notation\Helpers::get_posts_score_insights([$post_id], 'last_30_days', 'pc');

        $this->assertSame(1, $initial['total']);
        $this->assertSame(8.0, $initial['mean']['value']);

        // Modifier la note éditoriale mais conserver le cache intact.
        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_average_score'] = '6.0';

        $cached = \JLG\Notation\Helpers::get_posts_score_insights([$post_id], 'last_30_days', 'pc');

        $this->assertSame(8.0, $cached['mean']['value'], 'Le cache doit conserver la valeur précédente avant invalidation.');

        // Simuler une mise à jour de métadonnée qui invalide le cache.
        \JLG\Notation\Helpers::maybe_handle_rating_meta_change(0, $post_id, '_jlg_average_score');

        $refreshed = \JLG\Notation\Helpers::get_posts_score_insights([$post_id], 'last_30_days', 'pc');

        $this->assertSame(6.0, $refreshed['mean']['value'], 'Les insights doivent être recalculés après invalidation.');

        remove_filter('jlg_score_insights_cache_ttl', $ttl_filter, 10);
    }
}

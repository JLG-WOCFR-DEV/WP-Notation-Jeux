<?php

use PHPUnit\Framework\TestCase;

class ShortcodeScoreInsightsTemplateTest extends TestCase
{
    public function test_histogram_progress_elements_are_accessible()
    {
        $atts = array('title' => 'Score Insights');
        $insights = array(
            'total' => 5,
            'mean' => array(
                'formatted' => '8,2',
            ),
            'median' => array(
                'formatted' => '8,0',
            ),
            'distribution' => array(
                array(
                    'label' => '0 – 2',
                    'count' => 1,
                    'percentage' => 20.0,
                ),
                array(
                    'label' => '2 – 4',
                    'count' => 2,
                    'percentage' => 40.0,
                ),
            ),
            'platform_rankings' => array(
                array(
                    'label' => 'PC',
                    'average_formatted' => '8,5',
                    'count' => 3,
                ),
            ),
            'consensus' => array(
                'available' => true,
                'level' => 'high',
                'level_label' => 'Consensus fort',
                'message' => 'Les notes publiées sont très proches : verdict homogène.',
                'deviation_label' => 'Écart-type : 0,3',
                'range' => array(
                    'label' => 'Notes entre 8,0 et 8,5 (écart de 0,5 point(s)).',
                ),
            ),
        );

        $trend = array(
            'available' => true,
            'comparison_label' => 'Période précédente (30 derniers jours)',
            'previous_total_formatted' => '4',
            'mean' => array(
                'delta_formatted' => '+0,5',
                'direction' => 'up',
                'direction_label' => 'Tendance en hausse',
                'previous_formatted' => '7,7',
            ),
        );

        $time_range = 'last_30_days';
        $time_range_label = '30 derniers jours';
        $platform_slug = '';
        $platform_label = '';
        $platform_limit = 5;

        ob_start();
        require dirname(__DIR__) . '/templates/shortcode-score-insights.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('role="region"', $output);
        $this->assertMatchesRegularExpression('/<progress[^>]*aria-label="[^"]+"/i', $output, 'Histogram buckets should expose accessible aria-labels.');
        $this->assertMatchesRegularExpression('/<progress[^>]*value="40"/i', $output, 'Histogram buckets should expose numeric progress values.');
        $this->assertMatchesRegularExpression('/<span class="screen-reader-text">[^<]+PC[^<]+tests/i', $output, 'Platform ranking should provide a screen reader summary.');
        $this->assertStringContainsString('Période précédente (30 derniers jours)', $output, 'Trend label should be rendered.');
        $this->assertStringContainsString('Tendance en hausse', $output, 'Trend direction label should be present.');
        $this->assertMatchesRegularExpression('/jlg-score-insights__trend-value--up/', $output, 'Trend block should reflect positive direction class.');
        $this->assertStringContainsString('Niveau de consensus', $output, 'Consensus block title should be rendered.');
        $this->assertMatchesRegularExpression('/jlg-score-insights__consensus-chip--high/', $output, 'Consensus chip should reflect level class.');
        $this->assertStringContainsString('Écart-type : 0,3', $output, 'Consensus deviation label should be printed.');
    }
}

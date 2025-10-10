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
                'sample' => array(
                    'count' => 5,
                    'label' => 'Basé sur 5 tests publiés',
                    'confidence' => array(
                        'level' => 'high',
                        'label' => 'Confiance élevée',
                        'message' => 'Échantillon solide : communiquez sereinement votre verdict.',
                    ),
                ),
                'range' => array(
                    'label' => 'Notes entre 8,0 et 8,5 (écart de 0,5 point(s)).',
                ),
                'confidence' => array(
                    'level' => 'high',
                    'label' => 'Confiance élevée',
                    'message' => 'Échantillon solide : communiquez sereinement votre verdict.',
                ),
            ),
            'segments' => array(
                'available' => true,
                'editorial' => array(
                    'average_formatted' => '8,2',
                    'median_formatted'  => '8,0',
                    'count'             => 5,
                ),
                'readers'   => array(
                    'average_formatted' => '7,8',
                    'votes'             => 320,
                    'sample'            => 4,
                ),
                'delta'     => array(
                    'formatted' => '-0,4',
                    'direction' => 'negative',
                    'label'     => 'Écart lecteurs vs rédaction',
                ),
            ),
            'timeline' => array(
                'available' => true,
                'points'    => array(
                    array(
                        'date_label'          => '12 mars 2025',
                        'editorial_formatted' => '8,2',
                        'reader_formatted'    => '7,8',
                        'reader_votes'        => 120,
                        'title'               => 'Test A',
                        'permalink'           => 'https://example.com/a',
                        'post_id'             => 11,
                    ),
                    array(
                        'date_label'          => '22 mars 2025',
                        'editorial_formatted' => '8,4',
                        'reader_formatted'    => '8,0',
                        'reader_votes'        => 90,
                        'title'               => 'Test B',
                        'permalink'           => '',
                        'post_id'             => 12,
                    ),
                ),
                'sparkline' => array(
                    'editorial_path'  => 'M0,40 L80,20',
                    'reader_path'     => 'M0,35 L80,25',
                    'view_box'        => '0 0 120 48',
                    'width'           => 120,
                    'height'          => 48,
                    'aria_label'      => 'Évolution des notes rédaction vs lecteurs',
                    'editorial_label' => 'Rédaction',
                    'reader_label'    => 'Lecteurs',
                    'y_min_label'     => '0',
                    'y_max_label'     => '10',
                ),
            ),
            'sentiments' => array(
                'available' => true,
                'pros'      => array(
                    array(
                        'label' => 'Immersion totale',
                        'count' => 2,
                    ),
                ),
                'cons'      => array(
                    array(
                        'label' => 'Bugs critiques',
                        'count' => 1,
                    ),
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
        $this->assertStringContainsString('Basé sur 5 tests publiés', $output, 'Consensus sample size should be rendered.');
        $this->assertStringContainsString('Indice de confiance', $output, 'Confidence title should be present.');
        $this->assertMatchesRegularExpression('/jlg-score-insights__confidence-chip--high/', $output, 'Confidence chip should reflect level class.');
        $this->assertStringContainsString('Échantillon solide : communiquez sereinement votre verdict.', $output, 'Confidence message should be rendered.');
        $this->assertStringContainsString('Rédaction vs Lecteurs', $output, 'Segments heading should be displayed.');
        $this->assertMatchesRegularExpression('/jlg-score-insights__segment-delta-value--negative/', $output, 'Segment delta should reflect direction.');
        $this->assertStringContainsString('Évolution des scores', $output, 'Timeline heading should be rendered.');
        $this->assertStringContainsString('Points les plus cités', $output, 'Sentiments heading should be rendered.');
    }
}

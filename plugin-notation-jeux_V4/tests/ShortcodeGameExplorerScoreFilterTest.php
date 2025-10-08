<?php

use PHPUnit\Framework\TestCase;

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data, $options = 0, $depth = 512) {
        return json_encode($data, $options, $depth);
    }
}

require_once __DIR__ . '/../includes/Frontend.php';

class ShortcodeGameExplorerScoreFilterTest extends TestCase
{
    public function test_score_filter_renders_with_options_and_hint(): void
    {
        $output = \JLG\Notation\Frontend::get_template_html('shortcode-game-explorer', [
            'atts' => [
                'id' => 'score-test',
                'posts_per_page' => 9,
                'columns' => 3,
            ],
            'filters_enabled' => [
                'letter' => false,
                'category' => false,
                'platform' => false,
                'developer' => false,
                'publisher' => false,
                'availability' => false,
                'year' => false,
                'score' => true,
                'search' => false,
            ],
            'current_filters' => [
                'letter' => '',
                'category' => '',
                'platform' => '',
                'developer' => '',
                'publisher' => '',
                'availability' => '',
                'year' => '',
                'score' => '8',
                'search' => '',
            ],
            'scores_list' => [
                ['value' => '5', 'label' => 'Note ≥ 5 / 10'],
                ['value' => '8', 'label' => 'Note ≥ 8 / 10'],
            ],
            'scores_meta' => [
                'max' => 10,
                'precision' => 1,
            ],
            'letters' => [],
            'sort_options' => [],
            'games' => [],
            'pagination' => [
                'current' => 1,
                'total' => 0,
            ],
            'total_items' => 0,
            'config_payload' => [
                'atts' => [
                    'id' => 'score-test',
                    'posts_per_page' => 9,
                    'columns' => 3,
                    'filters' => 'score',
                    'categorie' => '',
                    'plateforme' => '',
                    'lettre' => '',
                    'developpeur' => '',
                    'editeur' => '',
                    'annee' => '',
                    'recherche' => '',
                    'note_min' => '8',
                ],
                'state' => [
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'letter' => '',
                    'category' => '',
                    'platform' => '',
                    'developer' => '',
                    'publisher' => '',
                    'availability' => '',
                    'year' => '',
                    'score' => '8',
                    'search' => '',
                    'paged' => 1,
                    'total_items' => 0,
                ],
                'meta' => [
                    'scores' => [
                        'max' => 10,
                        'precision' => 1,
                    ],
                ],
            ],
            'request_keys' => [
                'score' => 'score',
            ],
        ]);

        $this->assertStringContainsString('id="score-test-score"', $output, 'Score select should be rendered with the container prefix.');
        $this->assertStringContainsString('name="score"', $output, 'Score select should expose the request parameter.');
        $this->assertStringContainsString('Note ≥ 8 / 10', $output, 'Score option label should be rendered.');
        $this->assertMatchesRegularExpression(
            '/<option value="8"[^>]*selected/',
            $output,
            'The current score filter value should be selected.'
        );
        $this->assertStringContainsString('Échelle actuelle', $output, 'Score hint should display the current scale.');
    }
}

<?php

use PHPUnit\Framework\TestCase;

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data, $options = 0, $depth = 512) {
        return json_encode($data, $options, $depth);
    }
}

require_once __DIR__ . '/../includes/class-jlg-frontend.php';

class ShortcodeGameExplorerScoreDisplayTest extends TestCase
{
    public function test_does_not_render_outof_suffix_when_score_is_missing(): void
    {
        $output = $this->renderExplorerWithGame([
            'score_display' => 'N/A',
            'score_value' => null,
            'has_score' => false,
        ]);

        $this->assertStringContainsString(
            'jlg-ge-card__score',
            $output,
            'The score container should still be rendered even without a numeric score.'
        );

        $this->assertStringNotContainsString(
            'jlg-ge-card__score-outof',
            $output,
            'The "/10" suffix should not be displayed when no numeric score is available.'
        );
    }

    public function test_renders_outof_suffix_when_score_is_numeric(): void
    {
        $output = $this->renderExplorerWithGame([
            'score_display' => '8.5',
            'score_value' => 8.5,
            'has_score' => true,
        ]);

        $this->assertStringContainsString(
            '<span class="jlg-ge-card__score-outof">/10</span>',
            $output,
            'The "/10" suffix should be rendered when a numeric score is available.'
        );
    }

    private function renderExplorerWithGame(array $overrides): string
    {
        $game = array_merge(
            [
                'post_id' => 123,
                'title' => 'Test Game',
                'permalink' => 'https://example.com/game',
                'score_color' => '#f97316',
                'score_display' => 'N/A',
                'score_value' => null,
                'has_score' => false,
                'cover_url' => '',
                'release_display' => '',
                'developer' => '',
                'publisher' => '',
                'platforms' => [],
                'genre' => '',
                'availability_label' => '',
                'availability' => '',
                'excerpt' => '',
            ],
            $overrides
        );

        return JLG_Frontend::get_template_html('shortcode-game-explorer', [
            'atts' => [
                'id' => 'test-explorer',
                'posts_per_page' => 12,
                'columns' => 3,
            ],
            'config_payload' => [
                'atts' => [
                    'id' => 'test-explorer',
                    'posts_per_page' => 12,
                    'columns' => 3,
                    'score_position' => 'bottom-right',
                    'filters' => '',
                    'categorie' => '',
                    'plateforme' => '',
                    'lettre' => '',
                ],
                'state' => [
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'letter' => '',
                    'category' => '',
                    'platform' => '',
                    'availability' => '',
                    'search' => '',
                    'paged' => 1,
                    'total_items' => 1,
                ],
            ],
            'games' => [$game],
            'pagination' => [
                'current' => 1,
                'total' => 1,
            ],
            'filters_enabled' => [],
            'current_filters' => [],
            'letters' => [],
            'sort_options' => [],
            'categories_list' => [],
            'platforms_list' => [],
            'availability_options' => [],
            'total_items' => 1,
        ]);
    }
}

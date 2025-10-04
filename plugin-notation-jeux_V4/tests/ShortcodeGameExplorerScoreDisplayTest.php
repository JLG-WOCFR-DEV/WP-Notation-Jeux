<?php

use PHPUnit\Framework\TestCase;

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data, $options = 0, $depth = 512) {
        return json_encode($data, $options, $depth);
    }
}

if (!function_exists('_n')) {
    function _n($single, $plural, $number, $domain = 'default')
    {
        return $number === 1 ? $single : $plural;
    }
}

require_once __DIR__ . '/../includes/Frontend.php';

class ShortcodeGameExplorerScoreDisplayTest extends TestCase
{
    public function test_does_not_render_outof_suffix_when_score_is_missing(): void
    {
        $output = $this->renderExplorerWithGame([
            'score_display' => 'N/A',
            'score_value' => null,
            'has_score' => false,
            'user_rating_count' => 0,
            'user_rating_avg' => null,
        ]);

        $this->assertStringContainsString(
            'jlg-ge-card__ratings',
            $output,
            'The score container should still be rendered even without a numeric score.'
        );

        $this->assertStringNotContainsString(
            'jlg-ge-card__score-outof',
            $output,
            'The score suffix should not be displayed when no numeric score is available.'
        );
    }

    public function test_renders_outof_suffix_when_score_is_numeric(): void
    {
        $output = $this->renderExplorerWithGame([
            'score_display' => '8.5',
            'score_value' => 8.5,
            'has_score' => true,
            'user_rating_count' => 0,
        ]);

        $score_max_label = number_format_i18n( \JLG\Notation\Helpers::get_score_max() );

        $this->assertStringContainsString(
            '/' . $score_max_label,
            $output,
            'The score suffix should be rendered when a numeric score is available.'
        );

        $this->assertStringContainsString(
            'Aucun vote',
            $output,
            'When no audience votes exist the template should surface the placeholder label.'
        );
    }

    public function test_displays_reader_score_with_vote_count(): void
    {
        $output = $this->renderExplorerWithGame([
            'user_rating_avg' => 4.2,
            'user_rating_count' => 37,
            'user_rating_max' => 5.0,
        ]);

        $this->assertStringContainsString(
            'Lecteurs',
            $output,
            'Reader badge label should be rendered when ratings are available.'
        );

        $this->assertStringContainsString(
            '/5',
            $output,
            'Reader badge should display the rating scale when votes are present.'
        );

        $this->assertStringContainsString(
            sprintf(
                _n('%s vote', '%s votes', 37, 'notation-jlg'),
                number_format_i18n(37)
            ),
            $output,
            'The vote counter should be announced next to the reader badge.'
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
                'user_rating_avg' => null,
                'user_rating_count' => 0,
                'user_rating_min' => 1.0,
                'user_rating_max' => 5.0,
                'user_rating_color' => '#f59e0b',
            ],
            $overrides
        );

        return \JLG\Notation\Frontend::get_template_html('shortcode-game-explorer', [
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

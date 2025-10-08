<?php

use PHPUnit\Framework\TestCase;

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data, $options = 0, $depth = 512) {
        return json_encode($data, $options, $depth);
    }
}

require_once __DIR__ . '/../includes/Frontend.php';

class ShortcodeGameExplorerSearchFilterTest extends TestCase
{
    public function test_search_filter_renders_and_exports_state(): void
    {
        $search_value = 'Metroid';

        $output = \JLG\Notation\Frontend::get_template_html('shortcode-game-explorer', [
            'atts' => [
                'id' => 'explorer-test',
                'posts_per_page' => 9,
            ],
            'filters_enabled' => [
                'score' => false,
                'search' => true,
            ],
            'current_filters' => [
                'score' => '',
                'search' => $search_value,
            ],
            'config_payload' => [
                'atts' => [
                    'id' => 'explorer-test',
                    'posts_per_page' => 9,
                    'columns' => 3,
                    'score_position' => 'bottom-right',
                    'filters' => 'search',
                    'categorie' => '',
                    'plateforme' => '',
                    'lettre' => '',
                    'note_min' => '',
                ],
                'state' => [
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'letter' => '',
                    'category' => '',
                    'platform' => '',
                    'availability' => '',
                    'score' => '',
                    'search' => $search_value,
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
            'games' => [],
            'pagination' => [
                'current' => 1,
                'total' => 0,
            ],
            'total_items' => 0,
            'scores_list' => [],
            'scores_meta' => [
                'max' => 10,
                'precision' => 1,
            ],
        ]);

        $this->assertStringContainsString('data-role="search"', $output, 'The search input should be rendered when enabled.');
        $this->assertStringContainsString('id="explorer-test-search"', $output, 'The search input should use the container identifier.');
        $this->assertStringContainsString(htmlspecialchars($search_value, ENT_QUOTES), $output, 'The search input should reflect the current search value.');

        $this->assertMatchesRegularExpression(
            '/<label[^>]+for="explorer-test-search"[^>]*>[^<]*Rechercher un jeu[^<]*<\/label>/u',
            $output,
            'The search input should have an accessible label.'
        );

        preg_match('/data-config="([^"]+)"/', $output, $matches);
        $this->assertNotEmpty($matches, 'The component should expose its configuration.');

        $config = json_decode(htmlspecialchars_decode($matches[1], ENT_QUOTES), true);
        $this->assertIsArray($config, 'The configuration payload should be valid JSON.');
        $this->assertSame($search_value, $config['state']['search'] ?? null, 'Search state should be exported in the configuration payload.');
    }

    public function test_search_config_exports_suggestions_and_sorts(): void
    {
        $output = \JLG\Notation\Frontend::get_template_html('shortcode-game-explorer', [
            'atts' => [
                'id' => 'explorer-test',
                'posts_per_page' => 6,
            ],
            'filters_enabled' => [
                'score' => false,
                'search' => true,
            ],
            'current_filters' => [
                'score' => '',
                'search' => 'épopée légende',
            ],
            'config_payload' => [
                'atts' => [
                    'id' => 'explorer-test',
                    'posts_per_page' => 6,
                    'columns' => 3,
                    'score_position' => 'bottom-right',
                    'filters' => 'search,category',
                    'categorie' => '',
                    'plateforme' => '',
                    'lettre' => '',
                    'note_min' => '',
                ],
                'state' => [
                    'orderby' => 'popularity',
                    'order' => 'DESC',
                    'letter' => '',
                    'category' => '',
                    'platform' => '',
                    'score' => '',
                    'search' => 'épopée légende',
                    'paged' => 1,
                    'total_items' => 2,
                    'total_pages' => 1,
                ],
                'suggestions' => [
                    'search' => ['epopee', 'legende'],
                    'developers' => ['Studio Élan'],
                    'publishers' => ['Éditions Futur'],
                    'platforms' => ['PC'],
                ],
                'sorts' => [
                    'options' => [
                        ['value' => 'date|DESC', 'label' => 'Plus récents'],
                        ['value' => 'popularity|DESC', 'label' => 'Popularité (plus de votes)'],
                    ],
                    'active' => [
                        'orderby' => 'popularity',
                        'order' => 'DESC',
                    ],
                ],
                'meta' => [
                    'scores' => [
                        'max' => 10,
                        'precision' => 1,
                    ],
                ],
            ],
            'games' => [],
            'pagination' => [
                'current' => 1,
                'total' => 1,
            ],
            'total_items' => 0,
            'scores_list' => [],
            'scores_meta' => [
                'max' => 10,
                'precision' => 1,
            ],
        ]);

        preg_match('/data-config="([^"]+)"/', $output, $matches);
        $this->assertNotEmpty($matches, 'Configuration payload should be printed as data attribute.');

        $config = json_decode(htmlspecialchars_decode($matches[1], ENT_QUOTES), true);
        $this->assertIsArray($config, 'JSON configuration should decode into an array.');
        $this->assertSame('popularity', $config['state']['orderby'] ?? null, 'Active orderby should be exported.');
        $this->assertContains('epopee', $config['suggestions']['search'] ?? [], 'Accentless search suggestion should be exposed.');
        $this->assertSame('DESC', $config['sorts']['active']['order'] ?? null, 'Active sort order should be exported.');
    }
}

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
                'search' => true,
            ],
            'current_filters' => [
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
                ],
                'state' => [
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'letter' => '',
                    'category' => '',
                    'platform' => '',
                    'availability' => '',
                    'search' => $search_value,
                    'paged' => 1,
                    'total_items' => 0,
                ],
            ],
            'games' => [],
            'pagination' => [
                'current' => 1,
                'total' => 0,
            ],
            'total_items' => 0,
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
}

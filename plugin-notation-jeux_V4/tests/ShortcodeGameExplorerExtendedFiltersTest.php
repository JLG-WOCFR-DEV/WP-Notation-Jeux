<?php

use PHPUnit\Framework\TestCase;

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data, $options = 0, $depth = 512) {
        return json_encode($data, $options, $depth);
    }
}

require_once __DIR__ . '/../includes/Frontend.php';

class ShortcodeGameExplorerExtendedFiltersTest extends TestCase
{
    public function test_developer_and_publisher_filters_render(): void
    {
        $output = \JLG\Notation\Frontend::get_template_html('shortcode-game-explorer', [
            'atts' => [
                'id' => 'explorer-filters',
                'posts_per_page' => 9,
                'columns' => 3,
            ],
            'filters_enabled' => [
                'letter' => false,
                'category' => false,
                'platform' => false,
                'developer' => true,
                'publisher' => true,
                'availability' => false,
                'year' => false,
                'search' => true,
            ],
            'current_filters' => [
                'letter' => '',
                'category' => '',
                'platform' => '',
                'developer' => 'studio-alpha',
                'publisher' => 'publisher-beta',
                'availability' => '',
                'year' => '',
                'search' => '',
            ],
            'developers_list' => [
                ['value' => 'studio-alpha', 'label' => 'Studio Alpha'],
                ['value' => 'studio-beta', 'label' => 'Studio Beta'],
            ],
            'publishers_list' => [
                ['value' => 'publisher-beta', 'label' => 'Publisher Beta'],
            ],
            'games' => [],
            'letters' => [],
            'sort_options' => [],
            'pagination' => [
                'current' => 1,
                'total' => 0,
            ],
            'total_items' => 0,
            'config_payload' => [
                'atts' => [
                    'id' => 'explorer-filters',
                    'posts_per_page' => 9,
                    'columns' => 3,
                    'filters' => 'developer,publisher,search',
                    'categorie' => '',
                    'plateforme' => '',
                    'lettre' => '',
                    'developpeur' => '',
                    'editeur' => '',
                    'annee' => '',
                    'recherche' => '',
                ],
                'state' => [
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'letter' => '',
                    'category' => '',
                    'platform' => '',
                    'developer' => 'studio-alpha',
                    'publisher' => 'publisher-beta',
                    'availability' => '',
                    'year' => '',
                    'search' => '',
                    'paged' => 1,
                ],
            ],
        ]);

        $this->assertStringContainsString('data-role="filters-toggle"', $output, 'Filters toggle should be present.');

        preg_match('/data-config="([^"]+)"/', $output, $configMatches);
        $this->assertNotEmpty($configMatches, 'Configuration payload should be exposed.');

        $config = json_decode(htmlspecialchars_decode($configMatches[1], ENT_QUOTES), true);
        $this->assertIsArray($config, 'Configuration payload should decode to an array.');

        $this->assertSame('studio-alpha', $config['state']['developer'] ?? null, 'Developer state should persist in config.');
        $this->assertSame('publisher-beta', $config['state']['publisher'] ?? null, 'Publisher state should persist in config.');
        $this->assertStringContainsString('developer', $config['atts']['filters'] ?? '', 'Developer filter should be exported.');
        $this->assertStringContainsString('publisher', $config['atts']['filters'] ?? '', 'Publisher filter should be exported.');
        $this->assertArrayHasKey('developpeur', $config['atts'], 'Developer pre-filter attribute should be present.');
        $this->assertArrayHasKey('editeur', $config['atts'], 'Publisher pre-filter attribute should be present.');
    }

    public function test_year_filter_renders_with_hint_and_state(): void
    {
        $output = \JLG\Notation\Frontend::get_template_html('shortcode-game-explorer', [
            'atts' => [
                'id' => 'explorer-year',
                'posts_per_page' => 6,
                'columns' => 3,
            ],
            'filters_enabled' => [
                'letter' => false,
                'category' => false,
                'platform' => false,
                'developer' => false,
                'publisher' => false,
                'availability' => false,
                'year' => true,
                'search' => true,
            ],
            'current_filters' => [
                'letter' => '',
                'category' => '',
                'platform' => '',
                'developer' => '',
                'publisher' => '',
                'availability' => '',
                'year' => '2023',
                'search' => '',
            ],
            'years_list' => [
                ['value' => '2022', 'label' => '2022'],
                ['value' => '2023', 'label' => '2023'],
            ],
            'years_meta' => [
                'min' => 2015,
                'max' => 2024,
            ],
            'games' => [],
            'letters' => [],
            'sort_options' => [],
            'pagination' => [
                'current' => 1,
                'total' => 0,
            ],
            'total_items' => 0,
            'config_payload' => [
                'atts' => [
                    'id' => 'explorer-year',
                    'posts_per_page' => 6,
                    'columns' => 3,
                    'filters' => 'year,search',
                    'categorie' => '',
                    'plateforme' => '',
                    'lettre' => '',
                    'developpeur' => '',
                    'editeur' => '',
                    'annee' => '2023',
                    'recherche' => '',
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
                    'year' => '2023',
                    'search' => '',
                    'paged' => 1,
                ],
                'meta' => [
                    'years' => [
                        'min' => 2015,
                        'max' => 2024,
                        'buckets' => [2015 => 2, 2023 => 5],
                    ],
                ],
            ],
        ]);

        $this->assertStringContainsString('data-role="filters-toggle"', $output, 'Filters toggle should be rendered.');

        preg_match('/data-config="([^"]+)"/', $output, $configMatches);
        $this->assertNotEmpty($configMatches, 'Configuration payload should be available.');

        $config = json_decode(htmlspecialchars_decode($configMatches[1], ENT_QUOTES), true);
        $this->assertIsArray($config, 'Configuration payload should decode to an array.');

        $this->assertSame('2023', $config['state']['year'] ?? null, 'Year state should be persisted.');
        $this->assertSame('2023', $config['atts']['annee'] ?? null, 'Year pre-filter should be exported.');
        $this->assertStringContainsString('year', $config['atts']['filters'] ?? '', 'Year filter should be part of exported filters.');
        $this->assertArrayHasKey('meta', $config, 'Meta information should accompany the payload.');
        $this->assertSame(2015, $config['meta']['years']['min'] ?? null, 'Year metadata should expose the minimum bucket.');
        $this->assertSame(2024, $config['meta']['years']['max'] ?? null, 'Year metadata should expose the maximum bucket.');
    }
}

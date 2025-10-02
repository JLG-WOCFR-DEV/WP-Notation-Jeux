<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class DynamicCssBuilderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \JLG\Notation\Helpers::flush_plugin_options_cache();
        $GLOBALS['jlg_test_options'] = [];
    }

    /**
     * @dataProvider provideFrontendCssRootScenarios
     */
    public function test_build_frontend_css_generates_expected_root_variables(array $options, array $palette, ?float $average_score, array $expected): void
    {
        $css = \JLG\Notation\DynamicCss::build_frontend_css($options, $palette, $average_score);

        $variables = $this->extractRootVariables($css);

        foreach ($expected as $name => $value) {
            $this->assertArrayHasKey($name, $variables, sprintf('Failed asserting that %s is present in the root CSS variables.', $name));
            $this->assertSame($value, $variables[$name], sprintf('Unexpected value for %s.', $name));
        }
    }

    public function provideFrontendCssRootScenarios(): array
    {
        $defaults = \JLG\Notation\Helpers::get_default_settings();

        return [
            'dark theme with explicit overrides' => [
                array_merge($defaults, [
                    'visual_theme' => 'dark',
                    'tagline_font_size' => 18,
                    'score_gradient_1' => '#111111',
                    'score_gradient_2' => '#222222',
                    'color_high' => '#333333',
                    'color_low' => '#444444',
                    'user_rating_text_color' => '#555555',
                    'user_rating_star_color' => '#666666',
                    'table_header_bg_color' => '#777777',
                    'table_header_text_color' => '#888888',
                    'table_row_bg_color' => '#999999',
                    'table_row_text_color' => '#aaaaaa',
                    'table_zebra_bg_color' => '#bbbbbb',
                    'circle_border_color' => '#cccccc',
                ]),
                [
                    'bg_color' => '#101010',
                    'bg_color_secondary' => '#202020',
                    'border_color' => '#303030',
                    'main_text_color' => '#404040',
                    'secondary_text_color' => '#505050',
                    'bar_bg_color' => '#606060',
                    'tagline_bg_color' => '#707070',
                    'tagline_text_color' => '#808080',
                ],
                8.7,
                [
                    '--jlg-bg-color' => '#101010',
                    '--jlg-bg-color-secondary' => '#202020',
                    '--jlg-border-color' => '#303030',
                    '--jlg-main-text-color' => '#404040',
                    '--jlg-secondary-text-color' => '#505050',
                    '--jlg-bar-bg-color' => '#606060',
                    '--jlg-score-gradient-1' => '#111111',
                    '--jlg-score-gradient-2' => '#222222',
                    '--jlg-color-high' => '#333333',
                    '--jlg-color-low' => '#444444',
                    '--jlg-tagline-bg-color' => '#707070',
                    '--jlg-tagline-text-color' => '#808080',
                    '--jlg-tagline-font-size' => '18px',
                    '--jlg-user-rating-text-color' => '#555555',
                    '--jlg-user-rating-star-color' => '#666666',
                    '--jlg-table-header-bg-color' => '#777777',
                    '--jlg-table-header-text-color' => '#888888',
                    '--jlg-table-row-bg-color' => '#999999',
                    '--jlg-table-row-text-color' => '#aaaaaa',
                    '--jlg-table-row-hover-color' => '#252525',
                    '--jlg-table-link-color' => '#bebebe',
                    '--jlg-score-gradient-1-hover' => '#252525',
                    '--jlg-table-zebra-bg-color' => '#bbbbbb',
                ],
            ],
            'light theme falling back to defaults when colors missing' => [
                array_merge($defaults, [
                    'visual_theme' => 'light',
                    'score_gradient_1' => 'invalid',
                    'score_gradient_2' => '',
                    'color_high' => '',
                    'color_low' => '#nothex',
                    'user_rating_text_color' => null,
                    'user_rating_star_color' => '',
                    'table_header_bg_color' => '',
                    'table_header_text_color' => '',
                    'table_row_bg_color' => 'not-a-color',
                    'table_row_text_color' => '',
                    'table_zebra_bg_color' => '',
                ]),
                [],
                null,
                [
                    '--jlg-bg-color' => '#ffffff',
                    '--jlg-bg-color-secondary' => '#f9fafb',
                    '--jlg-border-color' => '#e5e7eb',
                    '--jlg-main-text-color' => '#111827',
                    '--jlg-secondary-text-color' => '#6b7280',
                    '--jlg-bar-bg-color' => '#f9fafb',
                    '--jlg-score-gradient-1' => '#60a5fa',
                    '--jlg-score-gradient-2' => '#c084fc',
                    '--jlg-color-high' => '#22c55e',
                    '--jlg-color-low' => '#ef4444',
                    '--jlg-tagline-bg-color' => '#f9fafb',
                    '--jlg-tagline-text-color' => '#6b7280',
                    '--jlg-tagline-font-size' => '16px',
                    '--jlg-user-rating-text-color' => '#a1a1aa',
                    '--jlg-user-rating-star-color' => '#f59e0b',
                    '--jlg-table-header-bg-color' => '#3f3f46',
                    '--jlg-table-header-text-color' => '#ffffff',
                    '--jlg-table-row-bg-color' => 'transparent',
                    '--jlg-table-row-text-color' => '#a1a1aa',
                    '--jlg-table-row-hover-color' => '#feffff',
                    '--jlg-table-link-color' => '#b5b5be',
                    '--jlg-score-gradient-1-hover' => '#74b9ff',
                    '--jlg-table-zebra-bg-color' => '#27272a',
                ],
            ],
            'transparent palette entries keep permitted values' => [
                array_merge($defaults, [
                    'visual_theme' => 'dark',
                    'tagline_font_size' => 20,
                    'score_gradient_1' => '#0f0f0f',
                    'score_gradient_2' => '#202020',
                    'table_row_bg_color' => 'transparent',
                    'table_row_text_color' => '#123456',
                    'table_zebra_bg_color' => 'transparent',
                ]),
                [
                    'bg_color' => '#090909',
                    'bg_color_secondary' => '#1a1a1a',
                    'border_color' => '#0a0a0a',
                    'main_text_color' => '#aaaaaa',
                    'secondary_text_color' => '#bbbbbb',
                    'bar_bg_color' => 'invalid',
                    'tagline_bg_color' => '',
                    'tagline_text_color' => '',
                ],
                6.4,
                [
                    '--jlg-bg-color' => '#090909',
                    '--jlg-bg-color-secondary' => '#1a1a1a',
                    '--jlg-border-color' => '#0a0a0a',
                    '--jlg-main-text-color' => '#aaaaaa',
                    '--jlg-secondary-text-color' => '#bbbbbb',
                    '--jlg-bar-bg-color' => '#1a1a1a',
                    '--jlg-score-gradient-1' => '#0f0f0f',
                    '--jlg-score-gradient-2' => '#202020',
                    '--jlg-color-high' => '#22c55e',
                    '--jlg-color-low' => '#ef4444',
                    '--jlg-tagline-bg-color' => '#1a1a1a',
                    '--jlg-tagline-text-color' => '#bbbbbb',
                    '--jlg-tagline-font-size' => '20px',
                    '--jlg-user-rating-text-color' => '#a1a1aa',
                    '--jlg-user-rating-star-color' => '#f59e0b',
                    '--jlg-table-header-bg-color' => '#3f3f46',
                    '--jlg-table-header-text-color' => '#ffffff',
                    '--jlg-table-row-bg-color' => 'transparent',
                    '--jlg-table-row-text-color' => '#123456',
                    '--jlg-table-row-hover-color' => '#1f1f1f',
                    '--jlg-table-link-color' => '#26486a',
                    '--jlg-score-gradient-1-hover' => '#232323',
                    '--jlg-table-zebra-bg-color' => 'transparent',
                ],
            ],
        ];
    }

    private function extractRootVariables(string $css): array
    {
        if (!preg_match('/:root\{([^}]*)\}/', $css, $matches)) {
            $this->fail('The generated CSS does not contain a :root declaration.');
        }

        $declarations = array_filter(array_map('trim', explode(';', $matches[1])));
        $variables = [];

        foreach ($declarations as $declaration) {
            [$name, $value] = array_map('trim', explode(':', $declaration, 2));
            $variables[$name] = $value;
        }

        return $variables;
    }
}

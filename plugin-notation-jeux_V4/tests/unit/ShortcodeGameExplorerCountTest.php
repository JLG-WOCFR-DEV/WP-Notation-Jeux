<?php

use PHPUnit\Framework\TestCase;

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data, $options = 0, $depth = 512) {
        return json_encode($data, $options, $depth);
    }
}

if (!function_exists('_n')) {
    function _n($single, $plural, $number, $domain = 'default') {
        unset($domain);

        return (int) $number === 1 ? $single : $plural;
    }
}

require_once dirname(__DIR__, 2) . '/includes/class-jlg-frontend.php';

class ShortcodeGameExplorerCountTest extends TestCase
{
    public function test_renders_singular_label_with_formatted_count(): void
    {
        $output = $this->renderTemplateWithTotal(1);

        $this->assertMatchesRegularExpression(
            '/<div class="jlg-ge-count">\s*1 jeu\s*<\/div>/',
            $output,
            'The singular label should be rendered when there is only one game.'
        );
    }

    public function test_renders_plural_label_with_formatted_count(): void
    {
        $output = $this->renderTemplateWithTotal(2);

        $this->assertMatchesRegularExpression(
            '/<div class="jlg-ge-count">\s*2 jeux\s*<\/div>/',
            $output,
            'The plural label should be rendered when there are multiple games.'
        );
    }

    public function test_uses_number_format_i18n_for_large_counts(): void
    {
        $output = $this->renderTemplateWithTotal(12345);

        $this->assertStringContainsString(
            '12,345 jeux',
            $output,
            'The game count should be formatted using number_format_i18n().' 
        );
    }

    private function renderTemplateWithTotal(int $total): string
    {
        return JLG_Frontend::get_template_html('shortcode-game-explorer', [
            'total_items' => $total,
            'atts' => [
                'posts_per_page' => 12,
            ],
        ]);
    }
}


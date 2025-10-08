<?php

use PHPUnit\Framework\TestCase;

if (!function_exists('selected')) {
    function selected($selected, $current = true, $echo = true)
    {
        $result = ($selected == $current)
            ? ' selected="selected"'
            : '';

        if ($echo) {
            echo $result;
        }

        return $result;
    }
}

class ShortcodeGameExplorerTemplateTest extends TestCase
{
    private function renderTemplate(array $context): string
    {
        $templateFilter = static function ($path, $template_name, $args, $located_template, $plugin_template_path) {
            unset($path, $template_name, $args, $located_template, $plugin_template_path);

            return __DIR__ . '/fixtures/empty-template.php';
        };

        add_filter('jlg_frontend_template_path', $templateFilter, 10, 5);

        extract($context, EXTR_SKIP);

        try {
            ob_start();
            require dirname(__DIR__) . '/templates/shortcode-game-explorer.php';

            return (string) ob_get_clean();
        } finally {
            remove_filter('jlg_frontend_template_path', $templateFilter, 10);
        }
    }

    private function getDefaultContext(): array
    {
        return [
            'atts'               => [
                'posts_per_page' => 12,
            ],
            'container_id'       => 'jlg-game-explorer-test',
            'columns'            => 3,
            'filters_enabled'    => [
                'letter'      => true,
                'category'    => false,
                'platform'    => false,
                'developer'   => false,
                'publisher'   => false,
                'availability'=> false,
                'year'        => false,
                'score'       => false,
                'search'      => false,
            ],
            'current_filters'    => [
                'letter' => 'A',
            ],
            'letters'            => [
                [
                    'value'   => 'A',
                    'label'   => 'A',
                    'enabled' => true,
                ],
                [
                    'value'   => 'B',
                    'label'   => 'B',
                    'enabled' => false,
                ],
            ],
            'sort_options'       => [
                [
                    'value'   => 'date|desc',
                    'label'   => 'Date décroissante',
                    'orderby' => 'date',
                    'order'   => 'DESC',
                ],
            ],
            'categories_list'    => [],
            'developers_list'    => [],
            'publishers_list'    => [],
            'platforms_list'     => [],
            'scores_list'        => [],
            'years_list'         => [],
            'years_meta'         => [],
            'scores_meta'        => [],
            'availability_options' => [],
            'total_items'        => 0,
            'sort_key'           => 'date',
            'sort_order'         => 'DESC',
            'pagination'         => [
                'current' => 1,
                'total'   => 1,
            ],
            'config_payload'     => [],
            'request_prefix'     => '',
            'request_keys'       => [],
            'games'              => [],
            'message'            => '',
        ];
    }

    public function test_disabled_letter_button_exposes_accessibility_attributes(): void
    {
        $context = $this->getDefaultContext();

        $output = $this->renderTemplate($context);

        $this->assertMatchesRegularExpression(
            '/<button[^>]*data-letter="B"[^>]*disabled[^>]*aria-disabled="true"[^>]*tabindex="-1"/i',
            $output,
            'Disabled letter buttons should not be focusable and must expose aria-disabled.'
        );

        $this->assertStringContainsString(
            'title="Aucun jeu disponible pour B."',
            $output,
            'Disabled letter buttons should expose a tooltip explaining the disabled state.'
        );
    }

    public function test_results_container_announces_status_for_screen_readers(): void
    {
        $context = $this->getDefaultContext();

        $output = $this->renderTemplate($context);

        $this->assertMatchesRegularExpression(
            '/<div[^>]*class="[^"]*jlg-ge-results[^"]*"[^>]*role="status"/i',
            $output,
            'Results container should expose role="status" to communicate updates to assistive tech.'
        );

        $this->assertMatchesRegularExpression(
            '/<div[^>]*class="[^"]*jlg-ge-results[^"]*"[^>]*aria-live="polite"/i',
            $output,
            'Results container should announce updates politely to screen readers.'
        );

        $this->assertMatchesRegularExpression(
            '/<div[^>]*class="[^"]*jlg-ge-results[^"]*"[^>]*aria-busy="false"/i',
            $output,
            'Results container should initialise aria-busy state.'
        );
    }
}

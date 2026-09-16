<?php

use PHPUnit\Framework\TestCase;

class AdminRatingCategoriesLayoutTest extends TestCase
{
    private function pluginRoot(): string
    {
        return dirname(__DIR__);
    }

    private function renderCategoriesField(): string
    {
        $settings = new \JLG\Notation\Admin\Settings();
        $method   = new ReflectionMethod(\JLG\Notation\Admin\Settings::class, 'render_rating_categories_field');
        $method->setAccessible(true);

        ob_start();
        $method->invoke($settings, ['id' => 'rating_categories'], []);

        return (string) ob_get_clean();
    }

    public function test_category_fields_stack_labels_above_inputs_without_fixed_wp_widths(): void
    {
        $html = $this->renderCategoriesField();

        $this->assertStringContainsString('jlg-rating-categories', $html);
        $this->assertStringContainsString('jlg-rating-category__grid', $html);
        $this->assertStringContainsString('jlg-rating-category__field', $html);
        $this->assertStringContainsString('jlg-rating-category__label', $html);
        $this->assertStringContainsString('jlg-rating-category__input', $html);
        $this->assertStringContainsString('jlg-rating-category__weight', $html);

        $this->assertDoesNotMatchRegularExpression(
            '/jlg-rating-category__grid[\s\S]*class="regular-text"/',
            $html,
            'Les champs Libellé/Identifiant ne doivent pas utiliser regular-text (largeur 25em) dans la grille.'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/jlg-rating-category__weight[^>]*small-text/',
            $html,
            'La pondération ne doit pas rester en small-text inline à côté du libellé.'
        );
        $this->assertStringNotContainsString(
            'grid-template-columns:repeat(auto-fit,minmax(200px,1fr))',
            $html
        );
    }

    public function test_admin_css_keeps_category_grid_from_overflowing(): void
    {
        $css = (string) file_get_contents($this->pluginRoot() . '/assets/css/admin.css');

        $this->assertStringContainsString('.jlg-rating-category__field', $css);
        $this->assertStringContainsString('.jlg-rating-category__grid', $css);
        $this->assertStringContainsString('minmax(0,', $css);
        $this->assertStringContainsString('flex-direction: column', $css);
        $this->assertMatchesRegularExpression(
            '/\.jlg-rating-category__field\s*\{[^}]*min-width:\s*0/',
            $css
        );
        $this->assertMatchesRegularExpression(
            '/\.jlg-rating-category__field\s+[^{]*input[^{]*\{[^}]*max-width:\s*100%/',
            $css
        );
    }
}

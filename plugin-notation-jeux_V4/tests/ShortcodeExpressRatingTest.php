<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/Frontend.php';
require_once __DIR__ . '/../includes/Shortcodes/ExpressRating.php';

class ShortcodeExpressRatingTest extends TestCase
{
    public function test_render_outputs_card_with_sanitized_attributes(): void
    {
        $shortcode = new \JLG\Notation\Shortcodes\ExpressRating();

        $output = $shortcode->render([
            'score'       => '9.25',
            'score_max'   => '10',
            'show_badge'  => 'oui',
            'badge_label' => 'Coup <em>de</em> cœur',
            'cta_label'   => 'Acheter maintenant',
            'cta_url'     => 'https://example.com/shop?ref=newsletter',
            'cta_new_tab' => 'oui',
        ]);

        $this->assertStringContainsString('notation-jlg-express-rating', $output);
        $this->assertStringContainsString('data-score-ready="1"', $output);
        $this->assertStringContainsString('Acheter maintenant', $output);
        $this->assertStringContainsString('Coup de cœur', $output);
        $this->assertStringContainsString('https://example.com/shop?ref=newsletter', $output);
        $this->assertStringContainsString('--jlg-express-progress:92.50%', $output);
        $this->assertStringContainsString('Note express', $output, 'ARIA label should be present to describe the score.');
    }

    public function test_render_without_score_displays_placeholder_and_hides_cta(): void
    {
        $shortcode = new \JLG\Notation\Shortcodes\ExpressRating();

        $output = $shortcode->render([
            'badge_label' => 'Badge',
            'show_badge'  => 'oui',
            'cta_label'   => 'En savoir plus',
            'cta_url'     => 'javascript:alert(1)',
        ]);

        $this->assertStringContainsString('data-score-ready="0"', $output);
        $this->assertStringContainsString('Saisissez une note pour activer la prévisualisation.', $output);
        $this->assertStringContainsString('Badge', $output);
        $this->assertStringNotContainsString('En savoir plus', $output, 'CTA should be hidden when URL is rejected.');
    }
}

<?php

declare(strict_types=1);

use JLG\Notation\DealsWidget;
use JLG\Notation\Helpers;
use PHPUnit\Framework\TestCase;

final class DealsWidgetTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        Helpers::flush_plugin_options_cache();
        unset(
            $GLOBALS['jlg_test_options'],
            $GLOBALS['jlg_test_meta'],
            $GLOBALS['jlg_test_posts'],
            $GLOBALS['jlg_test_current_post_id']
        );
    }

    public function test_parse_instance_settings_returns_safe_defaults(): void
    {
        $widget     = new DealsWidget();
        $reflection = new \ReflectionMethod($widget, 'parse_instance_settings');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($widget, null);

        $this->assertSame('Bonnes affaires', $result['title']);
        $this->assertFalse($result['display_empty_message']);
        $this->assertSame('Aucune offre disponible pour le moment.', $result['empty_message']);
    }

    public function test_update_strips_html_and_normalises_checkbox(): void
    {
        $widget = new DealsWidget();

        $dirty = [
            'title'                 => "<strong>Deals</strong> <script>alert('x');</script>",
            'display_empty_message' => '1',
            'empty_message'         => "Pas d'offre <em>pour le moment</em>",
        ];

        $result = $widget->update($dirty, []);

        $this->assertSame('Deals', $result['title']);
        $this->assertTrue($result['display_empty_message']);
        $this->assertSame("Pas d'offre pour le moment", $result['empty_message']);
    }

    public function test_widget_outputs_deals_when_module_enabled(): void
    {
        $post_id = 501;

        $GLOBALS['jlg_test_posts'][$post_id] = new WP_Post([
            'ID'         => $post_id,
            'post_title' => 'Test Game',
            'post_type'  => 'post',
        ]);

        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_deals_entries'] = [
            [
                'retailer'     => 'Steam',
                'url'          => 'https://store.steampowered.com/app/123',
                'price'        => '49.99',
                'currency'     => 'EUR',
                'availability' => 'Disponible immédiatement',
                'cta_label'    => 'Acheter sur Steam',
                'highlight'    => true,
            ],
            [
                'retailer'     => 'GOG',
                'url'          => 'https://gog.com/game/demo',
                'price'        => '45.00',
                'currency'     => 'EUR',
                'availability' => 'Clé numérique',
            ],
        ];

        $GLOBALS['jlg_test_options']['notation_jlg_settings'] = array_merge(
            Helpers::get_default_settings(),
            [
                'deals_enabled'     => 1,
                'deals_limit'       => 4,
                'deals_button_rel'  => 'sponsored noopener',
                'deals_disclaimer'  => 'Liens affiliés test',
            ]
        );

        Helpers::flush_plugin_options_cache();

        $GLOBALS['jlg_test_current_post_id'] = $post_id;

        $options = Helpers::get_plugin_options(true);
        $this->assertSame(1, $options['deals_enabled']);
        $this->assertNotEmpty(Helpers::get_deals_for_post($post_id, $options));

        $widget = new DealsWidget();

        $args = [
            'before_widget' => '<section class="widget">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2>',
            'after_title'   => '</h2>',
        ];

        ob_start();
        $widget->widget($args, ['title' => 'Nos deals']);
        $output = ob_get_clean();

        $this->assertStringContainsString('Nos deals', $output);
        $this->assertStringContainsString('Steam', $output);
        $this->assertStringContainsString('Acheter sur Steam', $output);
        $this->assertStringContainsString('Liens affiliés test', $output);
        $this->assertStringContainsString('jlg-widget-deals__item--highlight', $output);
    }
}

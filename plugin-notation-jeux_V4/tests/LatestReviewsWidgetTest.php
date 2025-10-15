<?php

use JLG\Notation\LatestReviewsWidget;
use PHPUnit\Framework\TestCase;

final class LatestReviewsWidgetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->resetCacheVersion();
        unset($GLOBALS['jlg_test_transients']);
        $GLOBALS['jlg_test_actions'] = [];
        delete_option('jlg_latest_reviews_widget_cache_version');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->resetCacheVersion();
        unset($GLOBALS['jlg_test_transients'], $GLOBALS['jlg_test_actions']);
        delete_option('jlg_latest_reviews_widget_cache_version');
    }

    public function test_update_removes_script_content_from_title(): void
    {
        $widget = new LatestReviewsWidget();

        $dirty_instance = [
            'title'  => "Top <strong>Jeu</strong> <script>alert('x');</script> Edition",
            'number' => '-8',
        ];

        $clean_instance = $widget->update($dirty_instance, ['title' => 'Ancien', 'number' => 2]);

        $this->assertArrayHasKey('title', $clean_instance);
        $this->assertStringNotContainsString('alert', $clean_instance['title']);
        $this->assertSame('Top Jeu Edition', preg_replace('/\s+/', ' ', $clean_instance['title']));
        $this->assertSame(8, $clean_instance['number']);
    }

    public function test_parse_instance_settings_defaults_to_safe_values(): void
    {
        $widget = new LatestReviewsWidget();

        $reflection = new \ReflectionMethod($widget, 'parse_instance_settings');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($widget, ['title' => '', 'number' => 0]);

        $this->assertSame('Derniers Tests', $result['title']);
        $this->assertSame(5, $result['number']);
    }

    public function test_build_query_args_limits_post_ids_and_adds_optimisations(): void
    {
        $widget = new LatestReviewsWidget();

        $method = new \ReflectionMethod($widget, 'build_query_args');
        $method->setAccessible(true);

        $post_ids = array_map('strval', range(1, 50));

        $args = $method->invoke($widget, 3, $post_ids, ['post', 'custom_review']);

        $this->assertSame(3, $args['posts_per_page']);
        $this->assertSame(['post', 'custom_review'], $args['post_type']);
        $this->assertCount(9, $args['post__in']);
        $this->assertTrue($args['ignore_sticky_posts']);
        $this->assertTrue($args['no_found_rows']);
        $this->assertFalse($args['update_post_meta_cache']);
        $this->assertFalse($args['update_post_term_cache']);
        $this->assertFalse($args['lazy_load_term_meta']);
    }

    public function test_cache_key_depends_on_widget_identifier(): void
    {
        $widget = new LatestReviewsWidget();

        $method = new \ReflectionMethod($widget, 'get_widget_cache_key');
        $method->setAccessible(true);

        $settings = ['title' => 'Derniers Tests', 'number' => 5];
        $types    = ['post', 'custom_review'];

        $keyA = $method->invoke($widget, $settings, ['widget_id' => 'widget-1'], $types);
        $keyB = $method->invoke($widget, $settings, ['widget_id' => 'widget-2'], $types);

        $this->assertStringStartsWith('jlg_latest_reviews_', $keyA);
        $this->assertNotSame($keyA, $keyB);
    }

    public function test_flush_widget_cache_increments_version_and_triggers_action(): void
    {
        $this->assertSame('', get_option('jlg_latest_reviews_widget_cache_version', ''));

        LatestReviewsWidget::flush_widget_cache();

        $this->assertSame('2', get_option('jlg_latest_reviews_widget_cache_version'));
        $this->assertNotEmpty($GLOBALS['jlg_test_actions']);

        $lastAction = end($GLOBALS['jlg_test_actions']);
        $this->assertSame('jlg_latest_reviews_widget_cache_flushed', $lastAction[0]);
    }

    private function resetCacheVersion(): void
    {
        $property = new \ReflectionProperty(LatestReviewsWidget::class, 'cache_version');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }
}

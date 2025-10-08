<?php

use JLG\Notation\LatestReviewsWidget;
use PHPUnit\Framework\TestCase;

final class LatestReviewsWidgetTest extends TestCase
{
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

    public function test_build_query_args_prioritises_most_recent_rated_posts(): void
    {
        $widget = new LatestReviewsWidget();

        $method = new \ReflectionMethod($widget, 'build_query_args');
        $method->setAccessible(true);

        $post_ids = range(1, 30);

        $args = $method->invoke($widget, 5, $post_ids, ['post']);

        $this->assertSame(range(16, 30), $args['post__in']);
    }
}

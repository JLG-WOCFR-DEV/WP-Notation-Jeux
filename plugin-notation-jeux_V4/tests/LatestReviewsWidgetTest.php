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
}

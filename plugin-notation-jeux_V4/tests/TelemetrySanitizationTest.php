<?php

use JLG\Notation\Telemetry;
use PHPUnit\Framework\TestCase;

final class TelemetrySanitizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Telemetry::reset_metrics();
        unset($GLOBALS['jlg_test_transients'], $GLOBALS['jlg_test_options']);
    }

    protected function tearDown(): void
    {
        Telemetry::reset_metrics();
        unset($GLOBALS['jlg_test_transients'], $GLOBALS['jlg_test_options']);

        parent::tearDown();
    }

    public function test_record_event_sanitizes_nested_context(): void
    {
        Telemetry::record_event('user_rating', [
            'duration' => 0.42,
            'status'   => 'error',
            'message'  => '<strong>Vote refusé</strong>',
            'context'  => [
                'feedback_code' => 'duplicate_vote<script>',
                'raw_html'      => '<img src=x onerror=alert(1)>',
                'nested'        => [
                    'html'  => '<a href="javascript:alert(1)">click</a>',
                    'int'   => 123,
                    'float' => 0.75,
                ],
            ],
        ]);

        $metrics = Telemetry::get_metrics_summary();
        $this->assertArrayHasKey('user_rating', $metrics);

        $context = $metrics['user_rating']['last_event']['context'];
        $this->assertIsArray($context);
        $this->assertSame('duplicate_vote', $context['feedback_code']);
        $this->assertSame('', $context['raw_html']);
        $this->assertSame('click', $context['nested']['html']);
        $this->assertSame(123, $context['nested']['int']);
        $this->assertSame(0.75, $context['nested']['float']);

        $history = $metrics['user_rating']['history'];
        $this->assertNotEmpty($history, 'The sanitized context should also be reflected in the history.');
        $this->assertSame($context, $history[0]['context']);
    }
}

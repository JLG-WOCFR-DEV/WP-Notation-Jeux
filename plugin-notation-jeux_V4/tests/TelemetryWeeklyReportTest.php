<?php

use JLG\Notation\Telemetry;
use PHPUnit\Framework\TestCase;

final class TelemetryWeeklyReportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->resetEnvironment();
    }

    protected function tearDown(): void
    {
        $this->resetEnvironment();

        parent::tearDown();
    }

    private function resetEnvironment(): void
    {
        Telemetry::reset_metrics();
        unset($GLOBALS['jlg_test_transients'], $GLOBALS['jlg_test_options']);
    }

    public function test_weekly_report_aggregates_recent_events_only(): void
    {
        $now = time();

        $recentEvent = [
            'timestamp' => $now - 120,
            'duration'  => 0.45,
            'status'    => 'success',
            'message'   => 'Vote enregistré',
            'context'   => ['feedback_code' => 'vote_recorded'],
        ];

        $staleEvent = [
            'timestamp' => $now - (9 * 24 * 60 * 60),
            'duration'  => 1.75,
            'status'    => 'error',
            'message'   => 'Ancien',
            'context'   => ['feedback_code' => 'network_error'],
        ];

        update_option('jlg_notation_metrics_v1', [
            'user_rating' => [
                'history'            => [$recentEvent, $staleEvent],
                'count'              => 2,
                'success'            => 1,
                'failures'           => 1,
                'avg_duration'       => 1.1,
                'max_duration'       => 1.75,
                'last_status'        => 'error',
                'last_event'         => $recentEvent,
                'last_error_message' => 'Ancien',
            ],
        ]);

        $report = Telemetry::get_weekly_report();

        $this->assertArrayHasKey('channels', $report);
        $this->assertArrayHasKey('user_rating', $report['channels']);

        $channel = $report['channels']['user_rating'];

        $this->assertSame(1, $channel['events'], 'Only the recent event should be counted.');
        $this->assertSame(1, $channel['success']);
        $this->assertSame(0, $channel['errors']);
        $this->assertEqualsWithDelta(1.0, $channel['success_rate'], 0.0001);
        $this->assertEqualsWithDelta(0.45, $channel['average_duration'], 0.0001);
        $this->assertEqualsWithDelta(0.45, $channel['max_duration'], 0.0001);
        $this->assertSame(['vote_recorded' => 1], $channel['feedback_codes']);
        $this->assertSame($recentEvent['timestamp'], $channel['last_event']['timestamp']);

        $cached = get_transient('jlg_notation_weekly_report');
        $this->assertIsString($cached, 'The weekly report should be cached as JSON.');

        $decoded = json_decode($cached, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('totals', $decoded);
        $this->assertSame(1, $decoded['totals']['events']);
        $this->assertEqualsWithDelta(1.0, $decoded['totals']['success_rate'], 0.0001);
    }

    public function test_record_event_flushes_cached_weekly_report(): void
    {
        set_transient('jlg_notation_weekly_report', 'stale-json');
        $this->assertSame('stale-json', get_transient('jlg_notation_weekly_report'));

        Telemetry::record_event('user_rating', [
            'duration' => 0.2,
            'status'   => 'success',
        ]);

        $this->assertFalse(get_transient('jlg_notation_weekly_report'), 'Recording a new event should invalidate the cache.');
    }
}


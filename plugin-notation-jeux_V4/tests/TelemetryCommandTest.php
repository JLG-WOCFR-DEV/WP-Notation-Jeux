<?php

use JLG\Notation\CLI\TelemetryCommand;
use JLG\Notation\Telemetry;
use PHPUnit\Framework\TestCase;

class TelemetryCommandTest extends TestCase
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
        $GLOBALS['jlg_test_wp_cli_messages'] = [];
        $GLOBALS['jlg_test_wp_cli_lines']    = [];
    }

    public function test_reset_command_clears_metrics(): void
    {
        Telemetry::record_event('live_announcer', [
            'duration' => 0.75,
            'status'   => 'success',
            'message'  => 'ok',
            'context'  => ['scenario' => 'test'],
        ]);

        $command = new TelemetryCommand();
        $command->reset([], []);

        $this->assertSame([], Telemetry::get_metrics_summary(), 'All telemetry metrics should be cleared after reset.');
        $this->assertSame('success', $GLOBALS['jlg_test_wp_cli_messages']['type'] ?? null, 'WP-CLI should report a success message.');
    }

    public function test_summary_outputs_table_lines(): void
    {
        Telemetry::record_event('ajax_flow', [
            'duration' => 1.25,
            'status'   => 'error',
            'message'  => 'timeout',
            'context'  => ['code' => 'timeout'],
        ]);

        $command = new TelemetryCommand();
        $command->summary([], []);

        $lines = $GLOBALS['jlg_test_wp_cli_lines'] ?? [];
        $this->assertNotEmpty($lines, 'Summary should print at least one line.');
        $this->assertStringContainsString('ajax_flow', implode('\n', $lines), 'Summary output should mention the telemetry channel.');
    }
}

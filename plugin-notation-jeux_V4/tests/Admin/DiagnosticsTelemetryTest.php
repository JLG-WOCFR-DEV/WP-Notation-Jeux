<?php

use JLG\Notation\Admin\Diagnostics;
use JLG\Notation\Telemetry;
use PHPUnit\Framework\TestCase;

final class DiagnosticsTelemetryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Telemetry::reset_metrics();
    }

    public function test_get_onboarding_summary_provides_step_metrics(): void
    {
        Telemetry::record_event('onboarding', [
            'status'  => 'success',
            'duration' => 0.0,
            'context' => [
                'event' => 'step_enter',
                'step'  => 1,
            ],
        ]);

        Telemetry::record_event('onboarding', [
            'status'  => 'success',
            'duration' => 3.5,
            'context' => [
                'event' => 'step_leave',
                'step'  => 1,
            ],
        ]);

        Telemetry::record_event('onboarding', [
            'status'  => 'success',
            'duration' => 0.0,
            'context' => [
                'event'         => 'validation',
                'step'          => 1,
                'feedback_code' => 'valid',
            ],
        ]);

        Telemetry::record_event('onboarding', [
            'status'  => 'success',
            'duration' => 0.0,
            'context' => [
                'event' => 'step_enter',
                'step'  => 2,
            ],
        ]);

        Telemetry::record_event('onboarding', [
            'status'  => 'error',
            'duration' => 0.0,
            'message' => 'Choisissez un module',
            'context' => [
                'event'            => 'validation',
                'step'             => 2,
                'feedback_code'    => 'missing_module',
                'feedback_message' => 'Choisissez un module',
            ],
        ]);

        Telemetry::record_event('onboarding', [
            'status'  => 'error',
            'duration' => 0.0,
            'message' => 'Choisissez un module',
            'context' => [
                'event'            => 'submission',
                'step'             => 2,
                'feedback_code'    => 'missing_module',
                'feedback_message' => 'Choisissez un module',
            ],
        ]);

        Telemetry::record_event('onboarding', [
            'status'  => 'success',
            'duration' => 1.2,
            'context' => [
                'event'         => 'submission',
                'step'          => 4,
                'feedback_code' => 'submitted',
            ],
        ]);

        $diagnostics = new Diagnostics();
        $summary     = $diagnostics->get_onboarding_summary();

        $this->assertArrayHasKey('steps', $summary);
        $this->assertNotEmpty($summary['steps']);

        $step1 = null;
        $step2 = null;
        foreach ($summary['steps'] as $stepData) {
            if (($stepData['step'] ?? 0) === 1) {
                $step1 = $stepData;
            }
            if (($stepData['step'] ?? 0) === 2) {
                $step2 = $stepData;
            }
        }

        $this->assertNotNull($step1);
        $this->assertSame(1, $step1['entries']);
        $this->assertSame(1, $step1['exits']);
        $this->assertSame(1, $step1['validation_success']);
        $this->assertSame(0, $step1['validation_errors']);
        $this->assertEqualsWithDelta(3.5, $step1['total_time'], 0.001);
        $this->assertEqualsWithDelta(3.5, $step1['avg_time'], 0.001);

        $this->assertNotNull($step2);
        $this->assertSame(1, $step2['entries']);
        $this->assertSame(0, $step2['validation_success']);
        $this->assertSame(1, $step2['validation_errors']);
        $this->assertSame('missing_module', $step2['last_feedback_code']);
        $this->assertSame('Choisissez un module', $step2['last_feedback_message']);

        $submission = $summary['submission'];
        $this->assertSame(2, $submission['attempts']);
        $this->assertSame(1, $submission['success']);
        $this->assertSame(1, $submission['errors']);
        $this->assertSame('submitted', $submission['last_feedback_code']);
    }
}

<?php

use PHPUnit\Framework\TestCase;

final class MigrationScheduleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['jlg_test_options'] = [];
        $GLOBALS['jlg_test_scheduled_events'] = [];
        $GLOBALS['jlg_test_meta'] = [];
        $GLOBALS['jlg_test_meta_updates'] = [];
        $GLOBALS['jlg_test_meta_calls'] = [];
    }

    protected function tearDown(): void
    {
        unset(
            $GLOBALS['jlg_test_options'],
            $GLOBALS['jlg_test_scheduled_events'],
            $GLOBALS['jlg_test_meta'],
            $GLOBALS['jlg_test_meta_updates'],
            $GLOBALS['jlg_test_meta_calls']
        );

        parent::tearDown();
    }

    private function getPlugin(): JLG_Plugin_De_Notation_Main
    {
        return JLG_Plugin_De_Notation_Main::get_instance();
    }

    public function test_ensure_migration_schedule_schedules_when_scan_incomplete(): void
    {
        update_option('jlg_migration_v5_queue', []);
        update_option('jlg_migration_v5_scan_state', [
            'last_post_id' => 50,
            'complete' => false,
        ]);

        $this->getPlugin()->ensure_migration_schedule();

        $this->assertArrayHasKey('jlg_process_v5_migration', $GLOBALS['jlg_test_scheduled_events']);
        $this->assertIsInt($GLOBALS['jlg_test_scheduled_events']['jlg_process_v5_migration']['timestamp']);
    }

    public function test_ensure_migration_schedule_schedules_when_queue_not_empty(): void
    {
        update_option('jlg_migration_v5_queue', [123, 456]);
        update_option('jlg_migration_v5_scan_state', [
            'last_post_id' => 456,
            'complete' => true,
        ]);

        $this->getPlugin()->ensure_migration_schedule();

        $this->assertArrayHasKey('jlg_process_v5_migration', $GLOBALS['jlg_test_scheduled_events']);
    }

    public function test_ensure_migration_schedule_skips_when_queue_empty_and_scan_complete(): void
    {
        update_option('jlg_migration_v5_queue', []);
        update_option('jlg_migration_v5_scan_state', [
            'last_post_id' => 500,
            'complete' => true,
        ]);

        $this->getPlugin()->ensure_migration_schedule();

        $this->assertArrayNotHasKey('jlg_process_v5_migration', $GLOBALS['jlg_test_scheduled_events']);
    }

    public function test_process_migration_batch_consumes_queue_and_reschedules_when_scan_pending(): void
    {
        update_option('jlg_migration_v5_queue', [101, 102, 103]);
        update_option('jlg_migration_v5_scan_state', [
            'last_post_id' => 103,
            'complete' => false,
        ]);

        $GLOBALS['jlg_test_meta'] = [
            101 => ['_jlg_average_score' => '8.1'],
            102 => ['_jlg_average_score' => '7.4'],
            103 => ['_jlg_average_score' => '9.0'],
        ];

        $this->getPlugin()->process_migration_batch();

        $this->assertSame([], get_option('jlg_migration_v5_queue', []));

        $averageScoreCalls = array_filter(
            $GLOBALS['jlg_test_meta_calls'],
            static function (array $entry): bool {
                return $entry['action'] === 'get' && $entry['key'] === '_jlg_average_score';
            }
        );
        $this->assertCount(3, $averageScoreCalls);

        $this->assertNotFalse(wp_next_scheduled('jlg_process_v5_migration'));
    }

    public function test_process_migration_batch_finalizes_when_queue_empty_and_scan_complete(): void
    {
        update_option('jlg_migration_v5_queue', [201]);
        update_option('jlg_migration_v5_scan_state', [
            'last_post_id' => 201,
            'complete' => true,
        ]);

        $GLOBALS['jlg_test_meta'] = [
            201 => ['_jlg_average_score' => '6.5'],
        ];

        $this->getPlugin()->process_migration_batch();

        $this->assertSame([], get_option('jlg_migration_v5_queue', []));
        $this->assertArrayNotHasKey('jlg_migration_v5_queue', $GLOBALS['jlg_test_options']);
        $this->assertArrayNotHasKey('jlg_migration_v5_scan_state', $GLOBALS['jlg_test_options']);

        $this->assertNotEmpty(get_option('jlg_migration_v5_completed'));
        $this->assertFalse(wp_next_scheduled('jlg_process_v5_migration'));

        $averageScoreCalls = array_filter(
            $GLOBALS['jlg_test_meta_calls'],
            static function (array $entry): bool {
                return $entry['action'] === 'get' && $entry['key'] === '_jlg_average_score';
            }
        );
        $this->assertCount(1, $averageScoreCalls);
    }
}

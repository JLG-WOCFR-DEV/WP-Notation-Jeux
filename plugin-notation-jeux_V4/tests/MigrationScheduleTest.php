<?php

use PHPUnit\Framework\TestCase;

class MigrationScheduleTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // Ensure the plugin file is loaded for class availability.
        @require_once __DIR__ . '/../plugin-notation-jeux.php';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['jlg_test_options'] = [];
        $GLOBALS['jlg_test_scheduled_events'] = [];
        $GLOBALS['jlg_test_meta'] = [];
        $GLOBALS['jlg_test_meta_updates'] = [];
        $GLOBALS['jlg_test_actions'] = [];

        $this->resetPluginSingleton();
    }

    private function resetPluginSingleton(): void
    {
        if (!class_exists(JLG_Plugin_De_Notation_Main::class)) {
            return;
        }

        $reflection = new ReflectionClass(JLG_Plugin_De_Notation_Main::class);

        if ($reflection->hasProperty('instance')) {
            $instance = $reflection->getProperty('instance');
            $instance->setAccessible(true);
            $instance->setValue(null, null);
        }
    }

    private function bootPlugin(): JLG_Plugin_De_Notation_Main
    {
        return JLG_Plugin_De_Notation_Main::get_instance();
    }

    private function assertHookScheduled(string $hook): void
    {
        $events = $GLOBALS['jlg_test_scheduled_events'] ?? [];
        $matches = array_filter($events, static fn ($event) => ($event['hook'] ?? '') === $hook);

        $this->assertNotEmpty($matches, sprintf('Expected hook "%s" to be scheduled.', $hook));
    }

    private function assertHookNotScheduled(string $hook): void
    {
        $events = $GLOBALS['jlg_test_scheduled_events'] ?? [];
        $matches = array_filter($events, static fn ($event) => ($event['hook'] ?? '') === $hook);

        $this->assertCount(0, $matches, sprintf('Did not expect hook "%s" to be scheduled.', $hook));
    }

    public function testEnsureMigrationScheduleQueuesEventWhenScanIncomplete(): void
    {
        $plugin = $this->bootPlugin();

        update_option('jlg_migration_v5_scan_state', [
            'last_post_id' => 0,
            'complete' => false,
        ]);

        $plugin->ensure_migration_schedule();

        $this->assertHookScheduled('jlg_process_v5_migration');
    }

    public function testEnsureMigrationScheduleQueuesEventWhenCompleteFlagMissing(): void
    {
        $plugin = $this->bootPlugin();

        update_option('jlg_migration_v5_scan_state', [
            'last_post_id' => 123,
        ]);

        $plugin->ensure_migration_schedule();

        $this->assertHookScheduled('jlg_process_v5_migration');
    }

    public function testEnsureMigrationScheduleQueuesEventWhenQueueHasEntries(): void
    {
        $plugin = $this->bootPlugin();

        update_option('jlg_migration_v5_queue', [101]);
        update_option('jlg_migration_v5_scan_state', [
            'last_post_id' => 101,
            'complete' => true,
        ]);

        $plugin->ensure_migration_schedule();

        $this->assertHookScheduled('jlg_process_v5_migration');
    }

    public function testEnsureMigrationScheduleSkipsWhenWorkComplete(): void
    {
        $plugin = $this->bootPlugin();

        update_option('jlg_migration_v5_scan_state', [
            'last_post_id' => 500,
            'complete' => true,
        ]);

        $plugin->ensure_migration_schedule();

        $this->assertHookNotScheduled('jlg_process_v5_migration');
    }

    public function testQueueAdditionalPostsForMigrationMergesAndSchedules(): void
    {
        $plugin = $this->bootPlugin();

        update_option('jlg_migration_v5_queue', [300, 150]);

        $this->assertSame([], $GLOBALS['jlg_test_scheduled_events']);

        $plugin->queue_additional_posts_for_migration([150, 450, 275]);

        $this->assertSame([150, 275, 300, 450], get_option('jlg_migration_v5_queue'));

        $events = $GLOBALS['jlg_test_scheduled_events'] ?? [];
        $this->assertNotEmpty($events, 'Expected a migration event to be scheduled when new posts were enqueued.');

        $scheduled_hooks = array_map(static fn ($event) => $event['hook'] ?? '', $events);
        $this->assertContains('jlg_process_v5_migration', $scheduled_hooks);
    }

    public function testProcessMigrationBatchConsumesQueueAndFinalizes(): void
    {
        $plugin = $this->bootPlugin();

        update_option('jlg_migration_v5_queue', [111, 222]);
        update_option('jlg_migration_v5_scan_state', [
            'last_post_id' => 222,
            'complete' => true,
        ]);

        $GLOBALS['jlg_test_meta'] = [
            111 => [
                '_note_cat1' => 8,
                '_note_cat2' => 6,
            ],
            222 => [
                '_note_cat1' => 9,
                '_note_cat2' => 7,
            ],
        ];

        $plugin->process_migration_batch();

        $this->assertSame([], get_option('jlg_migration_v5_queue', []));
        $this->assertFalse(get_option('jlg_migration_v5_scan_state', false));

        $completed = get_option('jlg_migration_v5_completed', '');
        $this->assertIsString($completed);
        $this->assertNotSame('', $completed);

        $score_updates = array_values(array_filter(
            $GLOBALS['jlg_test_meta_updates'] ?? [],
            static fn ($entry) => ($entry['key'] ?? '') === '_jlg_average_score'
        ));

        $this->assertCount(2, $score_updates);

        $updated_posts = array_map(static fn ($entry) => $entry['post_id'], $score_updates);
        sort($updated_posts);
        $this->assertSame([111, 222], $updated_posts);

        $this->assertHookNotScheduled('jlg_process_v5_migration');
    }
}


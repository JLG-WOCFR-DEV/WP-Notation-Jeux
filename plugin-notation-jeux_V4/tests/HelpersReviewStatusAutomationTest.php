<?php

use PHPUnit\Framework\TestCase;

class HelpersReviewStatusAutomationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        \JLG\Notation\Helpers::flush_plugin_options_cache();
        $GLOBALS['jlg_test_scheduled_events'] = [];
        $GLOBALS['jlg_test_actions']          = [];
        $GLOBALS['jlg_test_meta_updates']     = [];
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset(
            $GLOBALS['jlg_test_scheduled_events'],
            $GLOBALS['jlg_test_actions'],
            $GLOBALS['jlg_test_meta_updates'],
            $GLOBALS['jlg_test_meta'],
            $GLOBALS['jlg_test_posts'],
            $GLOBALS['jlg_test_options']
        );

        \JLG\Notation\Helpers::flush_plugin_options_cache();
    }

    private function enableAutomation(array $overrides = []): void
    {
        $defaults = \JLG\Notation\Helpers::get_default_settings();
        $options  = array_merge(
            $defaults,
            [
                'review_status_enabled'               => 1,
                'review_status_auto_finalize_enabled' => 1,
                'review_status_auto_finalize_days'    => 7,
            ],
            $overrides
        );

        $GLOBALS['jlg_test_options']['notation_jlg_settings'] = $options;
        \JLG\Notation\Helpers::flush_plugin_options_cache();
    }

    public function test_maybe_schedule_review_status_automation_schedules_event_when_enabled(): void
    {
        $this->enableAutomation();

        $scheduled = \JLG\Notation\Helpers::maybe_schedule_review_status_automation(true);

        $this->assertTrue($scheduled);
        $this->assertNotEmpty($GLOBALS['jlg_test_scheduled_events']);

        $event = $GLOBALS['jlg_test_scheduled_events'][0];

        $this->assertSame(\JLG\Notation\Helpers::REVIEW_STATUS_CRON_HOOK, $event['hook']);
        $this->assertSame('daily', $event['recurrence']);
        $this->assertGreaterThan(time(), $event['timestamp']);
    }

    public function test_activate_review_status_automation_forces_scheduling(): void
    {
        $this->enableAutomation();

        $scheduled = \JLG\Notation\Helpers::activate_review_status_automation();

        $this->assertTrue($scheduled);
        $this->assertNotEmpty($GLOBALS['jlg_test_scheduled_events']);

        $event = $GLOBALS['jlg_test_scheduled_events'][0];

        $this->assertSame(\JLG\Notation\Helpers::REVIEW_STATUS_CRON_HOOK, $event['hook']);
    }

    public function test_deactivate_review_status_automation_clears_scheduled_hook(): void
    {
        $this->enableAutomation();
        \JLG\Notation\Helpers::activate_review_status_automation();

        $this->assertNotEmpty($GLOBALS['jlg_test_scheduled_events']);

        $cleared = \JLG\Notation\Helpers::deactivate_review_status_automation();

        $this->assertTrue($cleared);
        $this->assertEmpty($GLOBALS['jlg_test_scheduled_events']);
    }

    public function test_run_review_status_automation_updates_posts_and_triggers_action(): void
    {
        $this->enableAutomation(['allowed_post_types' => ['post']]);

        $now       = (int) current_time('timestamp');
        $oldDate   = gmdate('Y-m-d', $now - (10 * 86400));
        $recent    = gmdate('Y-m-d', $now - (2 * 86400));

        $GLOBALS['jlg_test_posts'] = [
            101 => new WP_Post([
                'ID'         => 101,
                'post_type'  => 'post',
                'post_status'=> 'publish',
                'post_date'  => '2025-01-01 10:00:00',
            ]),
            202 => new WP_Post([
                'ID'         => 202,
                'post_type'  => 'post',
                'post_status'=> 'publish',
                'post_date'  => '2025-01-05 10:00:00',
            ]),
        ];

        $GLOBALS['jlg_test_meta'] = [
            101 => [
                \JLG\Notation\Helpers::REVIEW_STATUS_META_KEY            => 'in_progress',
                \JLG\Notation\Helpers::REVIEW_STATUS_LAST_PATCH_META_KEY => $oldDate,
            ],
            202 => [
                \JLG\Notation\Helpers::REVIEW_STATUS_META_KEY            => 'in_progress',
                \JLG\Notation\Helpers::REVIEW_STATUS_LAST_PATCH_META_KEY => $recent,
            ],
        ];

        $updated = \JLG\Notation\Helpers::run_review_status_automation();

        $this->assertSame(1, $updated);
        $this->assertSame('final', $GLOBALS['jlg_test_meta'][101][\JLG\Notation\Helpers::REVIEW_STATUS_META_KEY]);
        $this->assertSame('in_progress', $GLOBALS['jlg_test_meta'][202][\JLG\Notation\Helpers::REVIEW_STATUS_META_KEY]);

        $this->assertNotEmpty($GLOBALS['jlg_test_meta_updates']);
        $lastUpdate = end($GLOBALS['jlg_test_meta_updates']);
        $this->assertSame(101, $lastUpdate['post_id']);
        $this->assertSame(\JLG\Notation\Helpers::REVIEW_STATUS_META_KEY, $lastUpdate['key']);
        $this->assertSame('final', $lastUpdate['value']);

        $this->assertNotEmpty($GLOBALS['jlg_test_actions']);
        $lastAction = end($GLOBALS['jlg_test_actions']);
        $this->assertSame('jlg_review_status_transition', $lastAction[0]);
        $this->assertSame([101, 'in_progress', 'final', 'auto_finalize'], $lastAction[1]);
    }

    public function test_run_review_status_automation_exits_when_disabled(): void
    {
        $this->enableAutomation(['review_status_auto_finalize_enabled' => 0]);

        $result = \JLG\Notation\Helpers::run_review_status_automation();

        $this->assertSame(0, $result);
        $this->assertEmpty($GLOBALS['jlg_test_actions']);
    }
}

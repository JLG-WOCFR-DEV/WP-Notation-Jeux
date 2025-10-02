<?php
/**
 * Integration tests around plugin activation using the real WordPress stack.
 */
class ActivationTest extends WP_UnitTestCase {
    protected function setUp(): void {
        parent::setUp();

        delete_option('notation_jlg_settings');
        delete_option('jlg_notation_version');
        delete_option('jlg_migration_v5_queue');
        delete_option('jlg_migration_v5_scan_state');
        delete_option('jlg_migration_v5_completed');
        wp_clear_scheduled_hook('jlg_process_v5_migration');
    }

    public function test_activation_populates_default_options_and_version(): void {
        $instance = JLG_Plugin_De_Notation_Main::get_instance();
        $instance->on_activation();

        $settings = get_option('notation_jlg_settings');
        $this->assertIsArray($settings, 'Default settings should be created on activation.');
        $this->assertArrayHasKey('user_rating_enabled', $settings, 'Expected key missing from default settings.');
        $this->assertSame(JLG_NOTATION_VERSION, get_option('jlg_notation_version'));
    }

    public function test_activation_schedules_migration_hook(): void {
        $instance = JLG_Plugin_De_Notation_Main::get_instance();
        $instance->on_activation();

        $timestamp = wp_next_scheduled('jlg_process_v5_migration');
        $this->assertNotFalse($timestamp, 'Activation should schedule the migration cron hook.');
        $this->assertGreaterThan(time() - MINUTE_IN_SECONDS, $timestamp, 'Scheduled event should be in the future.');
    }
}

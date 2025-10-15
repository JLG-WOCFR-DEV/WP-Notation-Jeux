<?php

use JLG\Notation\Admin\Settings\SettingsRepository;
use JLG\Notation\Helpers;
use PHPUnit\Framework\TestCase;

class HelpersSettingsViewModeTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['jlg_test_user_meta']  = [];
        $GLOBALS['jlg_test_users']      = [
            (object) ['ID' => 5],
            (object) ['ID' => 9],
        ];
    }

    public function test_ensure_user_settings_view_mode_seeds_missing_meta(): void
    {
        $updated = Helpers::ensure_user_settings_view_mode(9, SettingsRepository::MODE_SIMPLE);

        $this->assertTrue($updated);
        $this->assertSame(
            SettingsRepository::MODE_SIMPLE,
            get_user_meta(9, SettingsRepository::USER_META_KEY, true)
        );
    }

    public function test_ensure_user_settings_view_mode_normalizes_invalid_value(): void
    {
        $GLOBALS['jlg_test_user_meta'][5][SettingsRepository::USER_META_KEY] = 'advanced';

        $updated = Helpers::ensure_user_settings_view_mode(5);

        $this->assertTrue($updated);
        $this->assertSame(
            SettingsRepository::MODE_EXPERT,
            get_user_meta(5, SettingsRepository::USER_META_KEY, true)
        );
    }

    public function test_seed_settings_view_mode_respects_existing_preferences(): void
    {
        $GLOBALS['jlg_test_user_meta'][5][SettingsRepository::USER_META_KEY] = SettingsRepository::MODE_SIMPLE;

        $updates = Helpers::seed_settings_view_mode();

        $this->assertSame(1, $updates);
        $this->assertSame(
            SettingsRepository::MODE_SIMPLE,
            get_user_meta(5, SettingsRepository::USER_META_KEY, true)
        );
        $this->assertSame(
            SettingsRepository::MODE_EXPERT,
            get_user_meta(9, SettingsRepository::USER_META_KEY, true)
        );
    }
}

<?php

use PHPUnit\Framework\TestCase;

class SettingsModeRestTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['jlg_test_user_meta'] = [];
        $GLOBALS['jlg_test_current_user_id'] = 12;
    }

    public function test_rest_endpoints_return_panels_and_update_mode(): void
    {
        $settings = new \JLG\Notation\Admin\Settings();
        $settings->register_settings();

        $initial = $settings->rest_get_settings_mode([]);

        $this->assertIsArray($initial);
        $this->assertSame(\JLG\Notation\Admin\Settings\SettingsRepository::MODE_EXPERT, $initial['mode']);
        $this->assertArrayHasKey('panels', $initial);
        $this->assertArrayHasKey('options', $initial);
        $this->assertArrayHasKey(\JLG\Notation\Admin\Settings\SettingsRepository::MODE_SIMPLE, $initial['panels']);

        $update = $settings->rest_update_settings_mode(['mode' => 'simple']);

        $this->assertSame(\JLG\Notation\Admin\Settings\SettingsRepository::MODE_SIMPLE, $update['mode']);
        $this->assertSame(\JLG\Notation\Admin\Settings\SettingsRepository::MODE_SIMPLE, $settings->get_active_mode());
        $this->assertSame(\JLG\Notation\Admin\Settings\SettingsRepository::MODE_SIMPLE, $settings->get_repository()->get_user_mode());
    }
}

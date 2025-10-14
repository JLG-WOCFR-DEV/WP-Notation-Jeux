<?php

use PHPUnit\Framework\TestCase;
use JLG\Notation\Admin\Settings\SettingsRepository;

class AdminSettingsRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['jlg_test_user_meta'] = [];
        $GLOBALS['jlg_test_current_user_id'] = 7;
    }

    public function test_normalize_mode_defaults_to_expert(): void
    {
        $this->assertSame(SettingsRepository::MODE_EXPERT, SettingsRepository::normalize_mode('')); 
        $this->assertSame(SettingsRepository::MODE_EXPERT, SettingsRepository::normalize_mode('advanced'));
    }

    public function test_serialize_options_filters_simple_mode(): void
    {
        $repository = new SettingsRepository();

        $options = [
            'visual_theme' => 'dark',
            'score_max'    => 20,
            'custom_css'   => '.dummy { color: red; }',
        ];

        $filtered = $repository->serialize_options_for_mode(SettingsRepository::MODE_SIMPLE, $options);

        $this->assertArrayHasKey('visual_theme', $filtered);
        $this->assertArrayHasKey('score_max', $filtered);
        $this->assertArrayNotHasKey('custom_css', $filtered);
    }

    public function test_panels_payload_separates_simple_sections(): void
    {
        $repository = new SettingsRepository();

        $panels = $repository->build_panels_payload([
            ['id' => 'jlg_layout', 'title' => 'Layout'],
            ['id' => 'jlg_debug_section', 'title' => 'Debug'],
        ]);

        $this->assertArrayHasKey(SettingsRepository::MODE_SIMPLE, $panels);
        $this->assertArrayHasKey(SettingsRepository::MODE_EXPERT, $panels);

        $simpleSections = array_column($panels[SettingsRepository::MODE_SIMPLE]['sections'], 'id');
        $expertSections = array_column($panels[SettingsRepository::MODE_EXPERT]['sections'], 'id');

        $this->assertContains('jlg_layout', $simpleSections);
        $this->assertNotContains('jlg_debug_section', $simpleSections);
        $this->assertContains('jlg_layout', $expertSections);
        $this->assertContains('jlg_debug_section', $expertSections);
    }

    public function test_user_mode_persistence_uses_user_meta(): void
    {
        $repository = new SettingsRepository();

        $this->assertSame(SettingsRepository::MODE_EXPERT, $repository->get_user_mode());

        $repository->set_user_mode(SettingsRepository::MODE_SIMPLE);

        $this->assertSame(SettingsRepository::MODE_SIMPLE, $repository->get_user_mode());

        $GLOBALS['jlg_test_current_user_id'] = 0;
        $this->assertSame(SettingsRepository::MODE_EXPERT, $repository->get_user_mode());
    }
}

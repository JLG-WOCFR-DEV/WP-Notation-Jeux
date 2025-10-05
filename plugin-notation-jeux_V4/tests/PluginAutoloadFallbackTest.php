<?php

use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class PluginAutoloadFallbackTest extends TestCase
{
    public function test_plugin_registers_psr4_fallback_when_vendor_autoload_missing(): void
    {
        $this->assertFalse(class_exists('\\JLG\\Notation\\Assets', false));

        @require_once __DIR__ . '/../plugin-notation-jeux.php';

        $this->assertTrue(function_exists('jlg_display_thumbnail_score'));
        $this->assertTrue(class_exists('\\JLG\\Notation\\Assets'));
        $this->assertTrue(class_exists('\\JLG\\Notation\\Admin\\Core'));
        $this->assertTrue(class_exists('\\JLG\\Notation\\Shortcodes\\GameExplorer'));
    }
}

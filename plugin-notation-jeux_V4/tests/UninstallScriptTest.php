<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class UninstallScriptTest extends TestCase
{
    public function test_uninstall_script_bootstraps_dependencies_without_autoloader(): void
    {
        $phpBinary = escapeshellarg(PHP_BINARY);
        $script    = escapeshellarg(__DIR__ . '/fixtures/run-uninstall.php');
        $command   = $phpBinary . ' ' . $script;

        exec($command, $output, $status);

        $this->assertSame(0, $status, "Uninstall script failed: " . implode("\n", $output));
        $this->assertEmpty($output, 'The uninstall script should not emit unexpected output.');
    }
}

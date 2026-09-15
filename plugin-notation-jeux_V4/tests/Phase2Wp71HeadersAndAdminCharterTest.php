<?php

use PHPUnit\Framework\TestCase;

class Phase2Wp71HeadersAndAdminCharterTest extends TestCase
{
    private function pluginRoot(): string
    {
        return dirname(__DIR__);
    }

    private function repoRoot(): string
    {
        return dirname($this->pluginRoot());
    }

    public function test_plugin_headers_declare_wordpress_71_compatibility(): void
    {
        $plugin = (string) file_get_contents($this->pluginRoot() . '/plugin-notation-jeux.php');

        $this->assertStringContainsString('Requires at least: 6.3', $plugin);
        $this->assertStringContainsString('Tested up to: 7.1', $plugin);
        $this->assertStringContainsString('Requires PHP: 7.4', $plugin);
        $this->assertStringContainsString("version_compare( get_bloginfo( 'version' ), '6.3', '<' )", $plugin);
    }

    public function test_readmes_stay_in_sync_for_wordpress_71(): void
    {
        $pluginReadmeMd  = (string) file_get_contents($this->pluginRoot() . '/README.md');
        $pluginReadmeTxt = (string) file_get_contents($this->pluginRoot() . '/README.txt');
        $repoReadme      = (string) file_get_contents($this->repoRoot() . '/README.md');

        $this->assertStringContainsString('**Requires at least:** 6.3', $pluginReadmeMd);
        $this->assertStringContainsString('**Tested up to:** 7.1', $pluginReadmeMd);
        $this->assertStringContainsString('Requires at least: 6.3', $pluginReadmeTxt);
        $this->assertStringContainsString('Tested up to: 7.1', $pluginReadmeTxt);
        $this->assertStringContainsString('WordPress 6.3', $repoReadme);
    }

    public function test_all_blocks_use_api_version_3_after_iframe_check(): void
    {
        $blocksDir = $this->pluginRoot() . '/assets/blocks';
        $files     = glob($blocksDir . '/*/block.json');

        $this->assertNotFalse($files);
        $this->assertCount(11, $files, 'The plugin should keep eleven Gutenberg blocks.');

        foreach ($files as $file) {
            $decoded = json_decode((string) file_get_contents($file), true);
            $this->assertIsArray($decoded, $file . ' must be valid JSON.');
            $this->assertSame(
                3,
                (int) ($decoded['apiVersion'] ?? 0),
                basename(dirname($file)) . ' must declare apiVersion 3 once canvas CSS/JS are iframe-safe.'
            );
            $this->assertSame(
                'notation-jlg-block-editor',
                $decoded['editorStyle'] ?? '',
                basename(dirname($file)) . ' must keep editorStyle on the iframe-safe handle.'
            );
        }
    }

    public function test_block_editor_scripts_use_wp_block_editor_only(): void
    {
        $files = glob($this->pluginRoot() . '/assets/js/blocks/*.js');

        $this->assertNotFalse($files);
        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $source = (string) file_get_contents($file);
            $this->assertStringNotContainsString(
                'wp.editor',
                $source,
                basename($file) . ' must not fall back to the legacy wp.editor alias.'
            );
        }
    }

    public function test_admin_shell_follows_wp_admin_charter(): void
    {
        $adminPage = (string) file_get_contents($this->pluginRoot() . '/admin/templates/admin-page.php');
        $tabs      = (string) file_get_contents($this->pluginRoot() . '/admin/templates/partials/tab-navigation.php');
        $settings  = (string) file_get_contents($this->pluginRoot() . '/admin/templates/tabs/settings.php');
        $adminCss  = (string) file_get_contents($this->pluginRoot() . '/assets/css/admin.css');

        $this->assertStringContainsString('class="wrap"', $adminPage);
        $this->assertStringContainsString('<h1>', $adminPage);
        $this->assertStringNotContainsString('jlg-admin-card', $adminPage);

        $this->assertStringContainsString('nav-tab-wrapper', $tabs);
        $this->assertStringContainsString('nav-tab', $tabs);

        $this->assertStringContainsString('settings_fields', $settings);
        $this->assertStringContainsString('do_settings_sections', $settings);
        $this->assertStringContainsString('submit_button', $settings);
        $this->assertStringContainsString('postbox', $settings);
        $this->assertStringContainsString('button-secondary', $settings);

        $this->assertStringNotContainsString('.button.button-primary', $adminCss);
        $this->assertStringNotContainsString('.button-primary {', $adminCss);
        $this->assertStringNotContainsString('jlg-admin-card .form-table', $adminCss);
        $this->assertStringNotContainsString('jlg-admin-card .wp-list-table', $adminCss);
    }

    public function test_admin_notices_use_core_notice_classes(): void
    {
        $menu       = (string) file_get_contents($this->pluginRoot() . '/includes/Admin/Menu.php');
        $onboarding = (string) file_get_contents($this->pluginRoot() . '/includes/Admin/Onboarding/OnboardingController.php');

        $this->assertStringContainsString('notice notice-warning', $menu);
        $this->assertStringContainsString('button button-primary', $menu);
        $this->assertStringContainsString('notice notice-success', $onboarding);
        $this->assertStringContainsString('notice notice-error', $onboarding);
    }
}

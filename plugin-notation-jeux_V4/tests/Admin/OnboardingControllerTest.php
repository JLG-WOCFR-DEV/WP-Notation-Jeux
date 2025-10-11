<?php

use JLG\Notation\Admin\Onboarding\OnboardingController;
use JLG\Notation\Helpers;
use PHPUnit\Framework\TestCase;

final class OnboardingControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['jlg_test_options']      = [];
        $GLOBALS['jlg_test_transients']   = [];
        $GLOBALS['jlg_test_redirects']    = [];
        $GLOBALS['jlg_onboarding_errors'] = [];
        $GLOBALS['jlg_test_is_admin']     = true;

        $_POST    = [];
        $_GET     = [];
        $_REQUEST = [];

        Helpers::flush_plugin_options_cache();
        $this->resetHelpersDefaultCache();
    }

    private function resetHelpersDefaultCache(): void
    {
        $reflection = new ReflectionProperty(Helpers::class, 'default_settings_cache');
        $reflection->setAccessible(true);
        $reflection->setValue(null, null);
    }

    public function test_handle_plugin_activation_sets_redirect_flag(): void
    {
        $controller = new OnboardingController('plugin-notation-jeux_V4/plugin-notation-jeux.php');

        $controller->handle_plugin_activation('plugin-notation-jeux_V4/plugin-notation-jeux.php', false);

        $this->assertSame(0, get_option('jlg_onboarding_completed'));
        $this->assertSame(1, get_transient('jlg_onboarding_redirect'));
    }

    public function test_maybe_redirect_to_onboarding_forces_admins_into_flow(): void
    {
        $controller = new OnboardingController('plugin-notation-jeux_V4/plugin-notation-jeux.php');

        update_option('jlg_onboarding_completed', 0);
        $_GET     = [];
        $_REQUEST = [];

        $controller->maybe_redirect_to_onboarding();

        $this->assertNotEmpty($GLOBALS['jlg_test_redirects']);
        $firstRedirect = $GLOBALS['jlg_test_redirects'][0];
        $this->assertSame('https://example.com/wp-admin/admin.php?page=jlg-notation-onboarding', $firstRedirect['location']);
    }

    public function test_handle_form_submission_updates_options_and_marks_completion(): void
    {
        $controller = new OnboardingController('plugin-notation-jeux_V4/plugin-notation-jeux.php');

        update_option('notation_jlg_settings', Helpers::get_default_settings());
        update_option('jlg_onboarding_completed', 0);

        $_POST = [
            'jlg_onboarding_nonce' => wp_create_nonce('jlg_onboarding_save'),
            'allowed_post_types'   => ['post', 'page'],
            'modules'              => ['verdict_module_enabled', 'user_rating_enabled'],
            'visual_preset'        => 'minimal',
            'visual_theme'         => 'light',
            'rawg_api_key'         => '1234567890ABCDE',
        ];

        $_REQUEST['action'] = 'jlg_onboarding_save';

        $optionsCache = new ReflectionProperty(Helpers::class, 'options_cache');
        $optionsCache->setAccessible(true);
        $optionsCache->setValue(null, ['cached' => true]);

        $controller->handle_form_submission();

        $savedOptions = get_option('notation_jlg_settings');
        $this->assertSame(['post', 'page'], $savedOptions['allowed_post_types']);
        $this->assertSame('minimal', $savedOptions['visual_preset']);
        $this->assertSame('light', $savedOptions['visual_theme']);
        $this->assertSame('1234567890ABCDE', $savedOptions['rawg_api_key']);
        $this->assertSame(1, $savedOptions['verdict_module_enabled']);
        $this->assertSame(1, $savedOptions['user_rating_enabled']);
        $this->assertSame(0, $savedOptions['review_status_enabled']);
        $this->assertSame(0, $savedOptions['related_guides_enabled']);
        $this->assertSame(0, $savedOptions['deals_enabled']);

        $this->assertSame(1, get_option('jlg_onboarding_completed'));
        $this->assertEmpty(get_transient('jlg_onboarding_redirect'));

        $this->assertNull($optionsCache->getValue());
        $this->assertNotEmpty($GLOBALS['jlg_test_redirects']);
        $redirect = $GLOBALS['jlg_test_redirects'][0];
        $this->assertStringContainsString('completed=1', $redirect['location']);
    }
}

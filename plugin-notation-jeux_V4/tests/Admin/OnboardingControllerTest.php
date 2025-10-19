<?php

use JLG\Notation\Admin\Onboarding\OnboardingController;
use JLG\Notation\Helpers;
use JLG\Notation\Telemetry;
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
        $GLOBALS['jlg_test_current_user_id'] = 1;
        $GLOBALS['jlg_test_menu_pages']      = [];
        $GLOBALS['jlg_test_submenu_pages']   = [];

        $_POST    = [];
        $_GET     = [];
        $_REQUEST = [];

        Helpers::flush_plugin_options_cache();
        $this->resetHelpersDefaultCache();
        Telemetry::reset_metrics();
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

    public function test_maybe_redirect_to_onboarding_ignores_regular_admin_pages_when_no_flag(): void
    {
        $controller = new OnboardingController('plugin-notation-jeux_V4/plugin-notation-jeux.php');

        update_option('jlg_onboarding_completed', 0);

        $controller->maybe_redirect_to_onboarding();

        $this->assertEmpty($GLOBALS['jlg_test_redirects']);
    }

    public function test_maybe_redirect_to_onboarding_redirects_once_after_activation(): void
    {
        $controller = new OnboardingController('plugin-notation-jeux_V4/plugin-notation-jeux.php');

        update_option('jlg_onboarding_completed', 0);
        set_transient('jlg_onboarding_redirect', 1, MINUTE_IN_SECONDS);

        $controller->maybe_redirect_to_onboarding();

        $this->assertNotEmpty($GLOBALS['jlg_test_redirects']);
        $firstRedirect = $GLOBALS['jlg_test_redirects'][0];
        $this->assertSame('https://example.com/wp-admin/admin.php?page=jlg-notation-onboarding', $firstRedirect['location']);
        $this->assertEmpty(get_transient('jlg_onboarding_redirect'));
    }

    public function test_maybe_redirect_to_onboarding_redirects_from_settings_page_while_incomplete(): void
    {
        $controller = new OnboardingController('plugin-notation-jeux_V4/plugin-notation-jeux.php');

        update_option('jlg_onboarding_completed', 0);
        $_GET['page'] = 'notation_jlg_settings';

        $controller->maybe_redirect_to_onboarding();

        $this->assertNotEmpty($GLOBALS['jlg_test_redirects']);
        $firstRedirect = $GLOBALS['jlg_test_redirects'][0];
        $this->assertSame('https://example.com/wp-admin/admin.php?page=jlg-notation-onboarding', $firstRedirect['location']);
    }

    public function test_register_onboarding_page_keeps_menu_visible_until_completion(): void
    {
        $controller = new OnboardingController('plugin-notation-jeux_V4/plugin-notation-jeux.php');

        update_option('jlg_onboarding_completed', 0);

        $hook_suffix = $controller->register_onboarding_page();

        $this->assertSame('notation_jlg_settings_page_jlg-notation-onboarding', $hook_suffix);
        $this->assertArrayHasKey('notation_jlg_settings', $GLOBALS['jlg_test_submenu_pages']);
        $submenu = $GLOBALS['jlg_test_submenu_pages']['notation_jlg_settings'];
        $this->assertArrayHasKey('jlg-notation-onboarding', $submenu);
    }

    public function test_register_onboarding_page_hides_menu_once_completed(): void
    {
        $controller = new OnboardingController('plugin-notation-jeux_V4/plugin-notation-jeux.php');

        update_option('jlg_onboarding_completed', 1);

        $hook_suffix = $controller->register_onboarding_page();

        $this->assertSame('notation_jlg_settings_page_jlg-notation-onboarding', $hook_suffix);
        $this->assertArrayNotHasKey('notation_jlg_settings', $GLOBALS['jlg_test_submenu_pages']);
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

    public function test_handle_form_submission_supports_rawg_opt_out(): void
    {
        $controller = new OnboardingController('plugin-notation-jeux_V4/plugin-notation-jeux.php');

        update_option('notation_jlg_settings', Helpers::get_default_settings());
        update_option('jlg_onboarding_completed', 0);

        $_POST = [
            'jlg_onboarding_nonce' => wp_create_nonce('jlg_onboarding_save'),
            'allowed_post_types'   => ['post'],
            'modules'              => ['verdict_module_enabled'],
            'visual_preset'        => 'signature',
            'visual_theme'         => 'dark',
            'rawg_api_key'         => '',
            'rawg_skip'            => '1',
        ];

        $_REQUEST['action'] = 'jlg_onboarding_save';

        $controller->handle_form_submission();

        $savedOptions = get_option('notation_jlg_settings');
        $this->assertSame('', $savedOptions['rawg_api_key']);
        $this->assertSame(1, get_option('jlg_onboarding_completed'));
        $this->assertEmpty(get_transient('jlg_onboarding_state_1'));
        $this->assertEmpty(get_transient('jlg_onboarding_errors_1'));
    }

    public function test_handle_form_submission_persists_errors_when_rawg_key_missing(): void
    {
        $controller = new OnboardingController('plugin-notation-jeux_V4/plugin-notation-jeux.php');

        update_option('notation_jlg_settings', Helpers::get_default_settings());
        update_option('jlg_onboarding_completed', 0);

        $_POST = [
            'jlg_onboarding_nonce' => wp_create_nonce('jlg_onboarding_save'),
            'allowed_post_types'   => ['post'],
            'modules'              => ['verdict_module_enabled'],
            'visual_preset'        => 'signature',
            'visual_theme'         => 'dark',
            'rawg_api_key'         => '',
        ];

        $_REQUEST['action'] = 'jlg_onboarding_save';

        $controller->handle_form_submission();

        $errorTransient = get_transient('jlg_onboarding_errors_1');
        $this->assertIsArray($errorTransient);
        $this->assertSame(['La clé RAWG doit contenir au moins 10 caractères.'], $errorTransient);

        $stateTransient = get_transient('jlg_onboarding_state_1');
        $this->assertIsArray($stateTransient);
        $this->assertSame(['post'], $stateTransient['allowed_post_types']);
        $this->assertSame(['verdict_module_enabled'], $stateTransient['modules']);
        $this->assertSame('signature', $stateTransient['visual_preset']);
        $this->assertSame('dark', $stateTransient['visual_theme']);
        $this->assertSame('', $stateTransient['rawg_api_key']);
        $this->assertSame(0, $stateTransient['rawg_skip']);
        $this->assertSame(4, $stateTransient['current_step']);
        $this->assertSame(0, get_option('jlg_onboarding_completed'));
    }

    public function test_handle_tracking_event_records_payload(): void
    {
        $controller = new OnboardingController('plugin-notation-jeux_V4/plugin-notation-jeux.php');

        $_POST = [
            'nonce'   => wp_create_nonce('jlg_onboarding_track'),
            'event'   => 'validation',
            'payload' => wp_json_encode([
                'status'           => 'error',
                'step'             => 2,
                'duration'         => 1.5,
                'feedback_code'    => 'missing_module',
                'feedback_message' => 'Choisissez un module',
            ]),
        ];

        try {
            $controller->handle_tracking_event();
            $this->fail('Expected JSON response.');
        } catch (WP_Send_Json_Exception $exception) {
            $this->assertTrue($exception->success);
            $this->assertSame(['recorded' => true], $exception->data);
        }

        $metrics = Telemetry::get_metrics_summary();
        $this->assertArrayHasKey('onboarding', $metrics);
        $channel = $metrics['onboarding'];

        $this->assertSame('error', $channel['last_status']);
        $this->assertSame('validation', $channel['last_event']['context']['event'] ?? '');
        $this->assertSame('missing_module', $channel['last_event']['context']['feedback_code'] ?? '');
        $this->assertSame(2, $channel['last_event']['context']['step'] ?? 0);
    }
}

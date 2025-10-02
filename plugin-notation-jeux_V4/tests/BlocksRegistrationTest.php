<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/Blocks.php';

if (!function_exists('trailingslashit')) {
    function trailingslashit($value) {
        $value = (string) $value;

        if ($value === '') {
            return '/';
        }

        return rtrim($value, "\\/") . '/';
    }
}

if (!function_exists('get_post_type_object')) {
    function get_post_type_object($post_type) {
        $objects = $GLOBALS['jlg_test_post_type_objects'] ?? [];

        if (isset($objects[$post_type])) {
            return $objects[$post_type];
        }

        if ($post_type === 'post') {
            return (object) [
                'labels' => (object) [
                    'singular_name' => 'Article',
                ],
            ];
        }

        return (object) [
            'labels' => (object) [
                'singular_name' => ucwords(str_replace(['-', '_'], ' ', (string) $post_type)),
            ],
        ];
    }
}

if (!function_exists('register_block_type_from_metadata')) {
    function register_block_type_from_metadata($path, $args = []) {
        if (!isset($GLOBALS['jlg_test_registered_blocks'])) {
            $GLOBALS['jlg_test_registered_blocks'] = [];
        }

        $GLOBALS['jlg_test_registered_blocks'][] = [
            'path' => $path,
            'args' => is_array($args) ? $args : [],
        ];

        return true;
    }
}

if (!function_exists('wp_set_script_translations')) {
    function wp_set_script_translations($handle, $domain = 'default', $path = '') {
        if (!isset($GLOBALS['jlg_test_scripts'])) {
            $GLOBALS['jlg_test_scripts'] = [
                'registered' => [],
                'enqueued'   => [],
                'localized'  => [],
                'inline'     => [],
            ];
        }

        if (!isset($GLOBALS['jlg_test_scripts']['translations'])) {
            $GLOBALS['jlg_test_scripts']['translations'] = [];
        }

        $GLOBALS['jlg_test_scripts']['translations'][$handle] = [
            'domain' => $domain,
            'path'   => $path,
        ];

        return true;
    }
}

if (!function_exists('do_shortcode')) {
    function do_shortcode($shortcode) {
        $GLOBALS['jlg_test_last_shortcode'] = $shortcode;

        return '[rendered]' . $shortcode;
    }
}

class BlocksRegistrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['jlg_test_scripts'] = [
            'registered'    => [],
            'enqueued'      => [],
            'localized'     => [],
            'inline'        => [],
            'translations'  => [],
        ];

        $GLOBALS['jlg_test_registered_blocks'] = [];
    }

    public function test_register_blocks_and_editor_assets(): void
    {
        $blocks = new \JLG\Notation\Blocks();

        $blocks->register_block_editor_assets();
        $blocks->register_blocks();

        $localized_settings = $GLOBALS['jlg_test_scripts']['localized']['notation-jlg-blocks-shared']['jlgBlockEditorSettings'] ?? null;
        $this->assertIsArray($localized_settings, 'Shared script should receive localized settings.');
        $this->assertSame(20, $localized_settings['postsQueryPerPage']);
        $this->assertIsArray($localized_settings['allowedPostTypes']);
        $this->assertNotEmpty($localized_settings['allowedPostTypes']);

        $first_allowed = $localized_settings['allowedPostTypes'][0];
        $this->assertSame('post', $first_allowed['slug']);
        $this->assertIsString($first_allowed['label']);
        $this->assertNotSame('', $first_allowed['label']);

        $shared_translations = $GLOBALS['jlg_test_scripts']['translations']['notation-jlg-blocks-shared'] ?? null;
        $this->assertIsArray($shared_translations, 'Shared script should have translations registered.');
        $this->assertSame('notation-jlg', $shared_translations['domain']);
        $this->assertStringContainsString('/languages', $shared_translations['path']);

        $reflection = new ReflectionClass(\JLG\Notation\Blocks::class);
        $blocks_property = $reflection->getProperty('blocks');
        $blocks_property->setAccessible(true);
        $registered_blocks = $blocks_property->getValue($blocks);

        foreach ($registered_blocks as $slug => $config) {
            $script_handle = $config['script'];
            $this->assertArrayHasKey($script_handle, $GLOBALS['jlg_test_scripts']['registered'], sprintf('Script "%s" should be registered.', $script_handle));

            $this->assertArrayHasKey($script_handle, $GLOBALS['jlg_test_scripts']['translations'], sprintf('Translations should be set for script "%s".', $script_handle));
            $this->assertSame('notation-jlg', $GLOBALS['jlg_test_scripts']['translations'][$script_handle]['domain']);

            $metadata_path = trailingslashit(JLG_NOTATION_PLUGIN_DIR) . 'assets/blocks/' . $slug;

            $found_registration = null;
            foreach ($GLOBALS['jlg_test_registered_blocks'] as $registration) {
                if ($registration['path'] === $metadata_path) {
                    $found_registration = $registration;
                    break;
                }
            }

            $this->assertNotNull($found_registration, sprintf('Block "%s" should be registered from metadata.', $slug));

            $this->assertArrayHasKey('render_callback', $found_registration['args']);
            $this->assertSame([$blocks, $config['callback']], $found_registration['args']['render_callback']);
        }
    }

    public function test_render_rating_block_supports_attribute_overrides(): void
    {
        $blocks = new \JLG\Notation\Blocks();

        $GLOBALS['jlg_test_last_shortcode'] = null;

        $result = $blocks->render_rating_block([
            'postId'        => 42,
            'scoreLayout'   => 'circle',
            'showAnimations' => false,
            'accentColor'   => '#ABCDEF',
        ]);

        $expected_shortcode = '[bloc_notation_jeu post_id="42" score_layout="circle" animations="non" accent_color="#abcdef"]';

        $this->assertSame($expected_shortcode, $GLOBALS['jlg_test_last_shortcode']);
        $this->assertSame('[rendered]' . $expected_shortcode, $result);
    }
}

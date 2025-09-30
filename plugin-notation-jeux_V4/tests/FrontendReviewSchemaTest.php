<?php

use PHPUnit\Framework\TestCase;

class FrontendReviewSchemaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetEnvironment();
    }

    protected function tearDown(): void
    {
        $this->resetEnvironment();
        parent::tearDown();
    }

    public function test_injects_schema_for_filtered_post_type(): void
    {
        $this->configurePluginOptions();

        $post_type = 'jlg_review';
        register_post_type($post_type);

        add_filter('jlg_rated_post_types', static function ($post_types) use ($post_type) {
            if (!is_array($post_types)) {
                $post_types = [];
            }

            $post_types[] = $post_type;

            return array_values(array_unique($post_types));
        });

        $post_id = 987;
        $this->registerPost($post_id, $post_type, [
            'post_author' => 42,
            'post_title'  => 'Custom Review Schema',
            'post_date'   => '2024-01-15 08:00:00',
        ]);

        $GLOBALS['jlg_test_users'][42] = [
            'display_name' => 'Schema Tester',
        ];

        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_average_score'] = 8.4;

        $frontend = new JLG_Frontend();

        ob_start();
        $frontend->inject_review_schema();
        $output = ob_get_clean();

        $this->assertNotSame('', $output, 'Schema output should not be empty.');
        $this->assertMatchesRegularExpression(
            '/<script type="application\/ld\+json">(.+)<\/script>/',
            $output,
            'JSON-LD script tag should be rendered.'
        );

        preg_match('/<script type="application\/ld\+json">(.+)<\/script>/', $output, $matches);
        $json = $matches[1] ?? '';
        $data = json_decode($json, true);

        $this->assertIsArray($data, 'JSON-LD payload should decode to an array.');
        $this->assertSame('Game', $data['@type'] ?? null, 'Schema should describe a Game review.');
        $this->assertSame('Custom Review Schema', $data['name'] ?? null, 'Schema should use the post title for the game name.');
        $this->assertSame(8.4, $data['review']['reviewRating']['ratingValue'] ?? null, 'Schema should expose the cached average score.');
        $this->assertSame('Schema Tester', $data['review']['author']['name'] ?? null, 'Schema should include the author display name.');
        $this->assertSame('2024-01-15T08:00:00+00:00', $data['review']['datePublished'] ?? null, 'Schema should include the publication date.');
    }

    private function configurePluginOptions(): void
    {
        $options = JLG_Helpers::get_default_settings();
        $options['seo_schema_enabled'] = 1;

        update_option('notation_jlg_settings', $options);
        JLG_Helpers::flush_plugin_options_cache();
    }

    private function registerPost(int $post_id, string $post_type, array $overrides = []): void
    {
        $defaults = [
            'ID'           => $post_id,
            'post_type'    => $post_type,
            'post_status'  => 'publish',
            'post_author'  => 0,
            'post_content' => '',
            'post_title'   => 'Test Review',
            'post_date'    => '2024-01-01 00:00:00',
        ];

        $post_data = array_merge($defaults, $overrides);

        $GLOBALS['jlg_test_posts'][$post_id] = new WP_Post($post_data);
        $GLOBALS['jlg_test_current_post_id'] = $post_id;
    }

    private function resetEnvironment(): void
    {
        $GLOBALS['jlg_test_posts'] = [];
        $GLOBALS['jlg_test_meta'] = [];
        $GLOBALS['jlg_test_meta_updates'] = [];
        $GLOBALS['jlg_test_options'] = [];
        $GLOBALS['jlg_test_users'] = [];
        $GLOBALS['jlg_test_current_post_id'] = 0;
        $GLOBALS['jlg_test_registered_post_types'] = [];
        unset($GLOBALS['jlg_test_filters']);

        remove_all_filters('jlg_rated_post_types');

        $this->resetFrontendStatics();
        JLG_Helpers::flush_plugin_options_cache();
    }

    private function resetFrontendStatics(): void
    {
        $reflection = new ReflectionClass(JLG_Frontend::class);
        $properties = [
            'shortcode_errors'       => [],
            'instance'               => null,
            'shortcode_rendered'     => false,
            'assets_enqueued'        => false,
            'deferred_styles_hooked' => false,
            'rendered_shortcodes'    => [],
        ];

        foreach ($properties as $property => $value) {
            if ($reflection->hasProperty($property)) {
                $property_reflection = $reflection->getProperty($property);
                $property_reflection->setAccessible(true);
                $property_reflection->setValue(null, $value);
            }
        }
    }
}

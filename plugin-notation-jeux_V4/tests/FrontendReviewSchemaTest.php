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

        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_average_score']          = 8.4;
        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_tagline_fr']             = 'Résumé officiel de la rédaction';
        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_tagline_en']             = 'Official editorial summary';
        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_editeur']                = 'Studio JLG';
        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_plateformes']            = array( 'PlayStation 5', 'PC' );
        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_cover_image_url']        = array( 'https://example.com/covers/schema.jpg' );
        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_review_video_url']       = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_review_video_provider']  = 'youtube';
        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_user_rating_count']      = 7;
        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_user_rating_avg']        = 4.2;
        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_user_rating_breakdown']  = array(
            '5' => 3,
            '4' => 2,
            '3' => 2,
        );

        $frontend = new \JLG\Notation\Frontend();

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
        $this->assertSame('fr-FR', $data['inLanguage'] ?? null, 'Schema should expose the active locale.');
        $this->assertSame('Résumé officiel de la rédaction', $data['review']['reviewBody'] ?? null, 'Schema should expose the localized tagline as reviewBody.');
        $this->assertSame('Studio JLG', $data['publisher']['name'] ?? null, 'Schema should expose the publisher.');
        $this->assertContains('PlayStation 5', $data['availableOnDevice'] ?? [], 'Schema should include platform availability.');
        $this->assertContains('PC', $data['availableOnDevice'] ?? [], 'Schema should include all platforms.');
        $this->assertContains('https://example.com/covers/schema.jpg', (array) ($data['image'] ?? []), 'Schema should include review imagery.');
        $this->assertIsArray($data['video'] ?? null, 'Schema should embed the review video object.');
        $this->assertStringStartsWith('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', $data['video']['embedUrl'] ?? '', 'Schema should expose the privacy-friendly embed URL.');
        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $data['video']['contentUrl'] ?? null, 'Schema should expose the original video URL.');
        $this->assertIsArray($data['aggregateRating'] ?? null, 'Schema should expose multiple aggregate rating scales.');
        $this->assertGreaterThanOrEqual(3, count($data['aggregateRating']), 'Schema should include editorial and user aggregates.');
        $this->assertIsArray($data['interactionStatistic'] ?? null, 'Schema should expose interaction statistics.');
        $this->assertNotEmpty($data['interactionStatistic'], 'Interaction statistics should describe user votes.');
    }

    public function test_schema_falls_back_to_weighted_average_when_cache_missing(): void
    {
        $options            = \JLG\Notation\Helpers::get_default_settings();
        $custom_categories  = $options['rating_categories'];
        $custom_categories[0]['weight'] = 2.5;
        $custom_categories[1]['weight'] = 0.5;

        $this->configurePluginOptions(
            array(
                'rating_categories' => $custom_categories,
            )
        );

        $post_type = 'jlg_review';
        register_post_type($post_type);

        add_filter('jlg_rated_post_types', static function ($post_types) use ($post_type) {
            if (!is_array($post_types)) {
                $post_types = [];
            }

            $post_types[] = $post_type;

            return array_values(array_unique($post_types));
        });

        $post_id = 654;
        $this->registerPost($post_id, $post_type, [
            'post_author' => 13,
            'post_title'  => 'Weighted Review Schema',
        ]);

        $GLOBALS['jlg_test_users'][13] = [
            'display_name' => 'Weighted Tester',
        ];

        $GLOBALS['jlg_test_meta'][$post_id]['_note_gameplay']   = 9;
        $GLOBALS['jlg_test_meta'][$post_id]['_note_graphismes'] = 5;

        $frontend = new \JLG\Notation\Frontend();

        ob_start();
        $frontend->inject_review_schema();
        $output = ob_get_clean();

        $this->assertNotSame('', $output, 'Schema output should not be empty when weighted averages exist.');
        preg_match('/<script type="application\/ld\+json">(.+)<\/script>/', $output, $matches);
        $json = $matches[1] ?? '';
        $data = json_decode($json, true);

        $this->assertIsArray($data, 'JSON-LD payload should decode to an array.');
        $this->assertSame(8.3, $data['review']['reviewRating']['ratingValue'] ?? null, 'Schema should expose the weighted average when cache is missing.');
    }

    public function test_schema_translates_review_body_for_active_locale(): void
    {
        $this->configurePluginOptions();

        $post_type = 'jlg_review';
        register_post_type($post_type);

        add_filter('jlg_rated_post_types', static function ($post_types) use ($post_type) {
            $post_types[] = $post_type;

            return array_values(array_unique((array) $post_types));
        });

        $post_id = 321;
        $this->registerPost($post_id, $post_type, [
            'post_author' => 99,
            'post_title'  => 'Translated Review',
        ]);

        $GLOBALS['jlg_test_users'][99] = [
            'display_name' => 'Translator',
        ];

        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_average_score'] = 9.1;
        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_tagline_fr']    = 'Critique française seulement';

        $locale_callback = static function () {
            return 'en_US';
        };

        add_filter('jlg_schema_locale', $locale_callback);

        $translation_callback = static function ($translated, $text, $source, $target, $context) {
            if ($context === 'tagline' && $target === 'en') {
                return 'French review translated';
            }

            return $translated;
        };

        add_filter('jlg_auto_translate_text', $translation_callback, 10, 5);

        $frontend = new \JLG\Notation\Frontend();

        ob_start();
        $frontend->inject_review_schema();
        $output = ob_get_clean();

        remove_filter('jlg_auto_translate_text', $translation_callback, 10);
        remove_filter('jlg_schema_locale', $locale_callback);

        $this->assertNotSame('', $output, 'Schema output should not be empty for translated locale.');
        preg_match('/<script type="application\/ld\+json">(.+)<\/script>/', $output, $matches);
        $data = json_decode($matches[1] ?? '', true);

        $this->assertSame('en-US', $data['inLanguage'] ?? null, 'Schema should reflect the filtered locale.');
        $this->assertSame('French review translated', $data['review']['reviewBody'] ?? null, 'Schema should expose the translated tagline.');
    }

    private function configurePluginOptions(array $overrides = []): void
    {
        $options = \JLG\Notation\Helpers::get_default_settings();
        $options['seo_schema_enabled'] = 1;

        if (!empty($overrides)) {
            foreach ($overrides as $key => $value) {
                $options[$key] = $value;
            }
        }

        update_option('notation_jlg_settings', $options);
        \JLG\Notation\Helpers::flush_plugin_options_cache();
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
        \JLG\Notation\Helpers::flush_plugin_options_cache();
    }

    private function resetFrontendStatics(): void
    {
        $reflection = new ReflectionClass(\JLG\Notation\Frontend::class);
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

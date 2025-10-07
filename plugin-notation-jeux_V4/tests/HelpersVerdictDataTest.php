<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/Helpers.php';

class HelpersVerdictDataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['jlg_test_posts']        = [];
        $GLOBALS['jlg_test_meta']         = [];
        $GLOBALS['jlg_test_post_modified'] = [];
        $GLOBALS['jlg_test_options']      = [];
        $GLOBALS['jlg_test_permalinks']   = [];

        \JLG\Notation\Helpers::flush_plugin_options_cache();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset(
            $GLOBALS['jlg_test_posts'],
            $GLOBALS['jlg_test_meta'],
            $GLOBALS['jlg_test_post_modified'],
            $GLOBALS['jlg_test_options'],
            $GLOBALS['jlg_test_permalinks'],
            $GLOBALS['jlg_test_filters']
        );

        \JLG\Notation\Helpers::flush_plugin_options_cache();
    }

    public function test_summary_is_trimmed_and_defaults_populated(): void
    {
        $post_id = 3101;
        $this->seedPost($post_id);

        $long_summary = str_repeat('Excellent gameplay. ', 20);
        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_verdict_summary'] = $long_summary;
        $GLOBALS['jlg_test_permalinks'][$post_id] = 'https://example.com/tests/game';
        $this->setModifiedTimestamps($post_id, 1709480400, 1709484000);

        $data = \JLG\Notation\Helpers::get_verdict_data_for_post($post_id, $this->options(['verdict_module_enabled' => 1]));

        $this->assertTrue($data['enabled'], 'Module verdict should be enabled.');
        $this->assertNotSame('', $data['summary'], 'Summary should not be empty after normalization.');
        $lengthCallback = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';
        $this->assertLessThanOrEqual(160, $lengthCallback($data['summary']), 'Summary must respect the default character limit.');
        $this->assertSame('Lire le test complet', $data['cta']['label']);
        $this->assertSame('https://example.com/tests/game', $data['cta']['url']);
        $this->assertTrue($data['cta']['available']);
        $this->assertMatchesRegularExpression('/T\d{2}:\d{2}:\d{2}/', $data['updated']['datetime']);
        $this->assertNotSame('', $data['updated']['display']);
    }

    public function test_overrides_are_respected_with_url_validation(): void
    {
        $post_id = 3102;
        $this->seedPost($post_id);
        $this->setModifiedTimestamps($post_id, 1712148000, 1712151600);

        $overrides = array(
            'context'     => 'rating-block',
            'summary'     => 'Résumé prioritaire pour la carte verdict.',
            'cta_label'   => 'Acheter le jeu',
            'cta_url'     => 'https://store.example.com/game',
        );

        $data = \JLG\Notation\Helpers::get_verdict_data_for_post($post_id, $this->options(['verdict_module_enabled' => 1]), $overrides);

        $this->assertSame('Résumé prioritaire pour la carte verdict.', $data['summary']);
        $this->assertSame('Acheter le jeu', $data['cta']['label']);
        $this->assertSame('https://store.example.com/game', $data['cta']['url']);
        $this->assertTrue($data['cta']['available']);
    }

    public function test_invalid_cta_and_disabled_module_hide_card(): void
    {
        $post_id = 3103;
        $this->seedPost($post_id);
        $this->setModifiedTimestamps($post_id, 1714736400, 1714740000);

        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_verdict_cta_label'] = 'Découvrir';
        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_verdict_cta_url']   = 'javascript:alert(1)';

        $data = \JLG\Notation\Helpers::get_verdict_data_for_post($post_id, $this->options(['verdict_module_enabled' => 0]));

        $this->assertFalse($data['enabled'], 'Module disabled should force enabled flag to false.');
        $this->assertSame($data['permalink'], $data['cta']['url'], 'Fallback permalink should replace invalid CTA URL.');
        $this->assertSame('Découvrir', $data['cta']['label']);
        $this->assertSame('Version finale', $data['status']['label']);
    }

    private function seedPost(int $post_id): void
    {
        $GLOBALS['jlg_test_posts'][$post_id] = new WP_Post([
            'ID'          => $post_id,
            'post_type'   => 'post',
            'post_status' => 'publish',
            'post_title'  => 'Test post #' . $post_id,
        ]);
    }

    private function setModifiedTimestamps(int $post_id, int $gmt, int $local): void
    {
        $GLOBALS['jlg_test_post_modified'][$post_id] = [
            'gmt'   => $gmt,
            'local' => $local,
        ];
    }

    private function options(array $overrides): array
    {
        $defaults = \JLG\Notation\Helpers::get_default_settings();

        return array_merge($defaults, $overrides);
    }
}


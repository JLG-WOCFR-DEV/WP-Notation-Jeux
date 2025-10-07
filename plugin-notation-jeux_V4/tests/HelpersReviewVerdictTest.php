<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/Helpers.php';

class HelpersReviewVerdictTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['jlg_test_posts'] = [];
        $GLOBALS['jlg_test_meta']  = [];
        $GLOBALS['jlg_test_options'] = [];
        \JLG\Notation\Helpers::flush_plugin_options_cache();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset(
            $GLOBALS['jlg_test_posts'],
            $GLOBALS['jlg_test_meta'],
            $GLOBALS['jlg_test_options']
        );
    }

    public function test_payload_defaults_to_permalink_and_status(): void
    {
        $post_id = 321;
        $GLOBALS['jlg_test_posts'][$post_id] = new WP_Post([
            'ID'                => $post_id,
            'post_type'         => 'post',
            'post_status'       => 'publish',
            'post_modified'     => '2025-10-05 18:00:00',
            'post_modified_gmt' => '2025-10-05 16:00:00',
        ]);

        $payload = \JLG\Notation\Helpers::get_review_verdict_for_post($post_id);

        $this->assertSame('final', $payload['status']['slug']);
        $this->assertSame('https://example.com/?p=321', $payload['cta_url']);
        $this->assertSame('Lire le test complet', $payload['cta_label']);
        $this->assertNotEmpty($payload['last_updated']['display']);
        $this->assertNotEmpty($payload['last_updated']['iso']);
    }

    public function test_payload_uses_custom_summary_and_cta(): void
    {
        $post_id = 654;
        $GLOBALS['jlg_test_posts'][$post_id] = new WP_Post([
            'ID'                => $post_id,
            'post_type'         => 'post',
            'post_status'       => 'publish',
            'post_modified'     => '2025-10-07 09:15:00',
            'post_modified_gmt' => '2025-10-07 07:15:00',
        ]);

        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_verdict_summary']   = '<strong>Chef-d\'oeuvre</strong> tactique.';
        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_verdict_cta_label'] = 'Consulter l\'analyse';
        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_verdict_cta_url']   = 'https://example.com/full-review';
        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_review_status']     = 'draft';

        $payload = \JLG\Notation\Helpers::get_review_verdict_for_post($post_id);

        $this->assertSame('draft', $payload['status']['slug']);
        $this->assertSame('Consulter l\'analyse', $payload['cta_label']);
        $this->assertSame('https://example.com/full-review', $payload['cta_url']);
        $this->assertSame('<strong>Chef-d\'oeuvre</strong> tactique.', $payload['summary']);
        $this->assertArrayHasKey('iso', $payload['last_updated']);
    }
}

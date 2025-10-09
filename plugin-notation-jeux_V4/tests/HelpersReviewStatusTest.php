<?php

use PHPUnit\Framework\TestCase;

class HelpersReviewStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['jlg_test_meta'] = [];
        \JLG\Notation\Helpers::flush_plugin_options_cache();
        remove_all_filters('jlg_review_status_default');
        remove_all_filters('jlg_review_status_definitions');
        remove_all_filters('jlg_review_status_display');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        remove_all_filters('jlg_review_status_default');
        remove_all_filters('jlg_review_status_definitions');
        remove_all_filters('jlg_review_status_display');
        unset($GLOBALS['jlg_test_meta']);
        \JLG\Notation\Helpers::flush_plugin_options_cache();
    }

    public function test_default_review_status_slug_uses_filter_and_sanitizes(): void
    {
        add_filter('jlg_review_status_default', function () {
            return '  Draft-Proposed ';
        });

        $this->assertSame('draft-proposed', \JLG\Notation\Helpers::get_default_review_status_slug());
    }

    public function test_default_review_status_slug_falls_back_to_final_when_filter_empty(): void
    {
        add_filter('jlg_review_status_default', function () {
            return '';
        });

        $this->assertSame('final', \JLG\Notation\Helpers::get_default_review_status_slug());
    }

    public function test_get_review_status_definitions_sanitizes_custom_entries(): void
    {
        add_filter('jlg_review_status_definitions', function (array $definitions) {
            $definitions['  my custom '] = [
                'label'       => '',
                'description' => null,
            ];

            return $definitions;
        });

        $definitions = \JLG\Notation\Helpers::get_review_status_definitions();

        $this->assertArrayHasKey('draft', $definitions);
        $this->assertArrayHasKey('in_progress', $definitions);
        $this->assertArrayHasKey('final', $definitions);
        $this->assertArrayHasKey('mycustom', $definitions);
        $this->assertSame('Mycustom', $definitions['mycustom']['label']);
        $this->assertSame('', $definitions['mycustom']['description']);
    }

    public function test_normalize_review_status_returns_filtered_default_when_unknown(): void
    {
        add_filter('jlg_review_status_default', function () {
            return 'draft';
        });

        $this->assertSame('draft', \JLG\Notation\Helpers::normalize_review_status('unknown-status'));
    }

    public function test_get_review_status_for_post_reads_meta_and_returns_payload(): void
    {
        $post_id = 321;
        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_review_status'] = 'in_progress';

        $status = \JLG\Notation\Helpers::get_review_status_for_post($post_id);

        $this->assertSame('in_progress', $status['slug']);
        $this->assertSame('Mise à jour en cours', $status['label']);
        $this->assertNotSame('', $status['description']);
    }

    public function test_get_review_status_for_post_falls_back_to_default_when_invalid(): void
    {
        $post_id = 654;
        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_review_status'] = 'non-existent';

        $status = \JLG\Notation\Helpers::get_review_status_for_post($post_id);

        $this->assertSame('final', $status['slug']);
        $this->assertSame('Version finale', $status['label']);
    }
}

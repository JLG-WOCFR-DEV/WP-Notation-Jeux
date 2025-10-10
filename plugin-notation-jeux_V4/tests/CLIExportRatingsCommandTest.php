<?php

use JLG\Notation\CLI\ExportRatingsCommand;
use JLG\Notation\Helpers;
use PHPUnit\Framework\TestCase;

class CLIExportRatingsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['jlg_test_posts'] = [];
        $GLOBALS['jlg_test_meta']  = [];
        unset($GLOBALS['jlg_test_current_user_can']);

        Helpers::flush_plugin_options_cache();

        $defaults = Helpers::get_default_settings();
        $defaults['rating_badge_enabled']   = 1;
        $defaults['rating_badge_threshold'] = 9.0;

        update_option('notation_jlg_settings', $defaults);
        Helpers::flush_plugin_options_cache();
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['jlg_test_posts'], $GLOBALS['jlg_test_meta'], $GLOBALS['jlg_test_current_user_can']);
        parent::tearDown();
    }

    public function test_collect_rows_returns_badge_information(): void
    {
        $this->seedRatedPost(
            101,
            'Stellar Blade Review',
            'stellar-blade-review',
            9.1,
            8.7,
            142,
            ['PlayStation 5']
        );

        $this->seedRatedPost(
            202,
            'Arcade Frenzy Verdict',
            'arcade-frenzy-verdict',
            7.4,
            7.9,
            63,
            ['Nintendo Switch']
        );

        $GLOBALS['jlg_test_current_user_can'] = static function ($capability) {
            return $capability === 'read';
        };

        $command = new ExportRatingsCommand();

        $params = [
            'orderby'  => 'date',
            'order'    => 'desc',
            'platform' => '',
            'search'   => '',
            'statuses' => [],
            'from'     => '',
            'to'       => '',
            'post_id'  => 0,
            'slug'     => '',
            'badge'    => [
                'enabled'   => true,
                'threshold' => 9.0,
                'label'     => __('Sélection de la rédaction', 'notation-jlg'),
            ],
            'output'    => 'php://stdout',
            'delimiter' => ';',
        ];

        $result = $command->collect_rows($params);

        $this->assertArrayHasKey('items', $result);
        $this->assertCount(2, $result['items']);
        $this->assertSame('arcade-frenzy-verdict', $result['items'][0]['slug']);
        $this->assertSame('no', $result['items'][0]['badge_displayed']);
        $this->assertSame('yes', $result['items'][1]['badge_displayed']);
    }

    public function test_collect_rows_filters_by_platform(): void
    {
        $this->seedRatedPost(
            101,
            'Stellar Blade Review',
            'stellar-blade-review',
            9.1,
            8.7,
            142,
            ['PlayStation 5']
        );

        $this->seedRatedPost(
            202,
            'Arcade Frenzy Verdict',
            'arcade-frenzy-verdict',
            7.4,
            7.9,
            63,
            ['Nintendo Switch']
        );

        $GLOBALS['jlg_test_current_user_can'] = static function ($capability) {
            return $capability === 'read';
        };

        $command = new ExportRatingsCommand();

        $params = [
            'orderby'  => 'date',
            'order'    => 'desc',
            'platform' => 'nintendo-switch',
            'search'   => '',
            'statuses' => [],
            'from'     => '',
            'to'       => '',
            'post_id'  => 0,
            'slug'     => '',
            'badge'    => [
                'enabled'   => true,
                'threshold' => 9.0,
                'label'     => __('Sélection de la rédaction', 'notation-jlg'),
            ],
            'output'    => 'php://stdout',
            'delimiter' => ';',
        ];

        $result = $command->collect_rows($params);

        $this->assertCount(1, $result['items']);
        $this->assertSame('arcade-frenzy-verdict', $result['items'][0]['slug']);
    }

    public function test_invoke_writes_csv_file(): void
    {
        $this->seedRatedPost(
            101,
            'Stellar Blade Review',
            'stellar-blade-review',
            9.1,
            8.7,
            142,
            ['PlayStation 5']
        );

        $GLOBALS['jlg_test_current_user_can'] = static function ($capability) {
            return $capability === 'read';
        };

        $command = new ExportRatingsCommand();

        $outputPath = tempnam(sys_get_temp_dir(), 'jlg-ratings');

        $command->__invoke([], [
            'output'    => $outputPath,
            'delimiter' => ',',
        ]);

        $this->assertFileExists($outputPath);
        $contents = file($outputPath, FILE_IGNORE_NEW_LINES);
        $this->assertNotFalse($contents);
        $this->assertGreaterThanOrEqual(2, count($contents));
        $this->assertStringContainsString('post_id', $contents[0]);

        unlink($outputPath);
    }

    private function seedRatedPost(
        int $postId,
        string $title,
        string $slug,
        float $editorial,
        float $readers,
        int $votes,
        array $platforms
    ): void {
        $GLOBALS['jlg_test_posts'][$postId] = new WP_Post([
            'ID'            => $postId,
            'post_title'    => $title,
            'post_name'     => $slug,
            'post_type'     => 'post',
            'post_status'   => 'publish',
            'post_date'     => '2025-01-10 10:00:00',
            'post_date_gmt' => '2025-01-10 08:00:00',
        ]);

        $GLOBALS['jlg_test_meta'][$postId] = [
            '_jlg_average_score'     => $editorial,
            '_jlg_user_rating_avg'   => $readers,
            '_jlg_user_rating_count' => $votes,
            '_jlg_plateformes'       => $platforms,
        ];
    }
}

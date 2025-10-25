<?php

use PHPUnit\Framework\TestCase;

class RatingsControllerPaginationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['jlg_test_posts']        = [];
        $GLOBALS['jlg_test_meta']         = [];
        $GLOBALS['jlg_test_wp_query_log'] = [];
        unset($GLOBALS['jlg_test_current_user_can'], $GLOBALS['jlg_test_is_user_logged_in']);
        delete_option('notation_jlg_settings');
        \JLG\Notation\Helpers::flush_plugin_options_cache();
    }

    public function test_per_page_limits_results_and_avoids_exhaustive_query(): void
    {
        $this->seedRatedPost(11, 'Post Alpha', 'post-alpha');
        $this->seedRatedPost(22, 'Post Bravo', 'post-bravo');
        $this->seedRatedPost(33, 'Post Charlie', 'post-charlie');

        $GLOBALS['jlg_test_current_user_can'] = static function ($capability) {
            return $capability === 'read';
        };

        $controller = new \JLG\Notation\Rest\RatingsController();
        $request    = new RatingsPaginationTestRequest([
            'per_page' => 1,
            'page'     => 1,
            'orderby'  => 'date',
        ]);

        $response = $controller->handle_get_ratings($request);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('items', $response);
        $this->assertCount(1, $response['items']);
        $this->assertSame(3, $response['pagination']['total']);
        $this->assertSame(3, $response['pagination']['total_pages']);

        $this->assertNotEmpty($GLOBALS['jlg_test_wp_query_log']);
        foreach ($GLOBALS['jlg_test_wp_query_log'] as $args) {
            if (!is_array($args)) {
                continue;
            }

            $this->assertArrayHasKey('posts_per_page', $args);
            $this->assertNotSame(-1, $args['posts_per_page']);
        }
    }

    public function test_query_excludes_posts_without_resolved_score(): void
    {
        $this->seedRatedPost(11, 'Post Alpha', 'post-alpha');
        $this->seedRatedPost(22, 'Post Bravo', 'post-bravo');
        $this->seedUnratedPost(99, 'Post Unrated', 'post-unrated');

        $GLOBALS['jlg_test_current_user_can'] = static function ($capability) {
            return $capability === 'read';
        };

        $controller = new \JLG\Notation\Rest\RatingsController();
        $request    = new RatingsPaginationTestRequest([
            'per_page' => 1,
            'page'     => 1,
            'orderby'  => 'date',
            'order'    => 'desc',
        ]);

        $response = $controller->handle_get_ratings($request);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('items', $response);
        $this->assertCount(1, $response['items']);
        $this->assertSame(2, $response['pagination']['total']);
        $this->assertSame(2, $response['pagination']['total_pages']);

        $firstSlug = $response['items'][0]['slug'] ?? '';
        $this->assertContains($firstSlug, ['post-alpha', 'post-bravo']);

        $this->assertNotEmpty($GLOBALS['jlg_test_wp_query_log']);
        $lastQueryArgs = end($GLOBALS['jlg_test_wp_query_log']);
        $this->assertIsArray($lastQueryArgs);
        $this->assertArrayHasKey('meta_query', $lastQueryArgs);
        $this->assertMetaQueryRequiresScore($lastQueryArgs['meta_query']);
    }

    private function seedRatedPost(int $postId, string $title, string $slug): void
    {
        $GLOBALS['jlg_test_posts'][$postId] = new WP_Post([
            'ID'            => $postId,
            'post_title'    => $title,
            'post_name'     => $slug,
            'post_type'     => 'post',
            'post_status'   => 'publish',
            'post_date'     => '2024-01-0' . ($postId % 9 + 1) . ' 10:00:00',
            'post_date_gmt' => '2024-01-0' . ($postId % 9 + 1) . ' 08:00:00',
        ]);

        $GLOBALS['jlg_test_meta'][$postId] = [
            '_jlg_average_score'         => 7.5,
            '_jlg_user_rating_avg'       => 7.1,
            '_jlg_user_rating_count'     => 12,
            '_jlg_user_rating_breakdown' => [5 => 6, 4 => 4, 3 => 2],
            '_jlg_plateformes'           => ['PC'],
            '_jlg_review_status'         => 'final',
        ];
    }

    private function seedUnratedPost(int $postId, string $title, string $slug): void
    {
        $GLOBALS['jlg_test_posts'][$postId] = new WP_Post([
            'ID'            => $postId,
            'post_title'    => $title,
            'post_name'     => $slug,
            'post_type'     => 'post',
            'post_status'   => 'publish',
            'post_date'     => '2025-03-01 12:00:00',
            'post_date_gmt' => '2025-03-01 11:00:00',
        ]);
    }

    private function assertMetaQueryRequiresScore($metaQuery): void
    {
        $this->assertIsArray($metaQuery);
        $this->assertSame('AND', strtoupper($metaQuery['relation'] ?? ''));

        $clauses = $metaQuery;
        unset($clauses['relation']);

        $foundExists    = false;
        $foundNonEmpty  = false;

        foreach ($clauses as $clause) {
            if (!is_array($clause)) {
                continue;
            }

            if (($clause['key'] ?? '') !== '_jlg_average_score') {
                continue;
            }

            $compare = strtoupper($clause['compare'] ?? '');

            if ($compare === 'EXISTS') {
                $foundExists = true;
            }

            if ($compare === '!=' && (($clause['value'] ?? null) === '')) {
                $foundNonEmpty = true;
            }
        }

        $this->assertTrue($foundExists, 'Meta query should ensure the score meta exists.');
        $this->assertTrue($foundNonEmpty, 'Meta query should exclude empty score values.');
    }
}

class RatingsPaginationTestRequest
{
    private $params;

    public function __construct(array $params = [])
    {
        $this->params = $params;
    }

    public function get_param(string $key)
    {
        return $this->params[$key] ?? null;
    }

    public function get_params(): array
    {
        return $this->params;
    }
}

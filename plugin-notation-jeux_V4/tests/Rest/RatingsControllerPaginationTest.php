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

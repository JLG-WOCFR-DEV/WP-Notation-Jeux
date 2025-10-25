<?php

use PHPUnit\Framework\TestCase;

if (!class_exists('WP_Error')) {
    class WP_Error
    {
        private $code;
        private $message;
        private $data;

        public function __construct($code = '', $message = '', $data = [])
        {
            $this->code    = (string) $code;
            $this->message = (string) $message;
            $this->data    = $data;
        }

        public function get_error_code(): string
        {
            return $this->code;
        }

        public function get_error_message(): string
        {
            return $this->message;
        }

        public function get_error_data()
        {
            return $this->data;
        }
    }
}

class RestRatingsEndpointTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['jlg_test_posts']        = [];
        $GLOBALS['jlg_test_meta']         = [];
        $GLOBALS['jlg_test_rest_routes']  = [];
        unset($GLOBALS['jlg_test_transients'], $GLOBALS['jlg_test_object_cache']);
        unset($GLOBALS['jlg_test_current_user_can'], $GLOBALS['jlg_test_is_user_logged_in']);
        delete_option('notation_jlg_settings');
        delete_option('jlg_ratings_rest_summary_prefix');
        \JLG\Notation\Helpers::flush_plugin_options_cache();
        remove_all_filters('jlg_ratings_rest_is_public');
        remove_all_filters('jlg_ratings_rest_post_statuses');

        $controller = new \JLG\Notation\Rest\RatingsController();
        $controller->flush_rest_summary_cache();
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['jlg_test_current_user_can']);
        parent::tearDown();
    }

    public function test_registers_route_during_rest_api_init(): void
    {
        $controller = new \JLG\Notation\Rest\RatingsController();
        $controller->register_routes();

        $this->assertNotEmpty($GLOBALS['jlg_test_rest_routes']);
        $route = $GLOBALS['jlg_test_rest_routes'][0];
        $this->assertSame('jlg/v1', $route['namespace']);
        $this->assertSame('/ratings', $route['route']);
        $this->assertIsArray($route['args']);
    }

    public function test_permissions_require_read_capability(): void
    {
        $controller = new \JLG\Notation\Rest\RatingsController();

        unset($GLOBALS['jlg_test_current_user_can']);
        $result = $controller->permissions_check(new TestRestRequest());
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('jlg_ratings_rest_forbidden', $result->get_error_code());

        $GLOBALS['jlg_test_current_user_can'] = static function ($capability) {
            return $capability === 'read';
        };

        $result = $controller->permissions_check(new TestRestRequest());
        $this->assertTrue($result);
    }

    public function test_handle_get_ratings_returns_paginated_payload(): void
    {
        $this->seedRatedPost(
            101,
            'Stellar Blade Review',
            'stellar-blade-review',
            9.1,
            8.7,
            142,
            [1 => 2, 2 => 6, 3 => 12, 4 => 40, 5 => 82],
            ['PlayStation 5', 'PC']
        );

        $this->seedRatedPost(
            202,
            'Arcade Frenzy Verdict',
            'arcade-frenzy-verdict',
            7.4,
            7.9,
            63,
            [1 => 1, 2 => 3, 3 => 8, 4 => 18, 5 => 33],
            ['Nintendo Switch'],
            'publish',
            '2025-02-20 09:00:00',
            '2025-02-20 08:00:00',
            '2025-02-22 11:00:00',
            '2025-02-22 10:00:00'
        );

        $GLOBALS['jlg_test_current_user_can'] = static function ($capability) {
            return $capability === 'read';
        };

        $controller = new \JLG\Notation\Rest\RatingsController();
        $request    = new TestRestRequest([
            'per_page' => 1,
            'orderby'  => 'editorial',
            'order'    => 'desc',
        ]);

        $response = $controller->handle_get_ratings($request);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('items', $response);
        $this->assertCount(1, $response['items']);
        $this->assertSame(2, $response['pagination']['total']);
        $this->assertSame(2, $response['pagination']['total_pages']);
        $this->assertSame(1, $response['pagination']['page']);
        $this->assertSame('stellar-blade-review', $response['items'][0]['slug']);
        $this->assertSame(9.1, $response['items'][0]['editorial']['score']);
        $this->assertSame(142, $response['items'][0]['readers']['votes']);
        $this->assertSame('+0,4', $response['items'][0]['readers']['delta']['formatted']);
        $this->assertSame(5, $response['items'][0]['readers']['histogram'][0]['stars']);
        $this->assertGreaterThan(0, $response['items'][0]['readers']['histogram'][0]['percentage']);
        $this->assertSame('in_progress', $response['items'][0]['review_status']['slug']);
        $this->assertArrayHasKey('summary', $response);
        $this->assertSame(2, $response['summary']['total']);
        $this->assertSame(2, $response['summary']['consensus']['sample']['count']);
        $this->assertSame(1, count($response['items']));
        $this->assertSame(8.3, $response['summary']['mean']['value']);

        $secondPage = $controller->handle_get_ratings(
            new TestRestRequest([
                'per_page' => 1,
                'page'     => 2,
                'orderby'  => 'editorial',
                'order'    => 'desc',
            ])
        );

        $this->assertCount(1, $secondPage['items']);
        $this->assertSame('arcade-frenzy-verdict', $secondPage['items'][0]['slug']);
        $this->assertSame(2, $secondPage['summary']['total']);
        $this->assertSame(2, $secondPage['summary']['consensus']['sample']['count']);
        $this->assertSame(8.3, $secondPage['summary']['mean']['value']);
        $this->assertSame(10.0, $response['score_max']);

        $secondPage = $controller->handle_get_ratings(new TestRestRequest([
            'per_page' => 1,
            'page'     => 2,
            'orderby'  => 'editorial',
            'order'    => 'desc',
        ]));

        $this->assertIsArray($secondPage);
        $this->assertCount(1, $secondPage['items']);
        $this->assertSame(2, $secondPage['pagination']['total']);
        $this->assertSame(2, $secondPage['pagination']['total_pages']);
        $this->assertSame(2, $secondPage['pagination']['page']);
        $this->assertSame('arcade-frenzy-verdict', $secondPage['items'][0]['slug']);

        $readerOrder = $controller->handle_get_ratings(new TestRestRequest([
            'per_page' => 2,
            'orderby'  => 'reader',
            'order'    => 'asc',
        ]));

        $this->assertIsArray($readerOrder);
        $this->assertCount(2, $readerOrder['items']);
        $this->assertSame('arcade-frenzy-verdict', $readerOrder['items'][0]['slug']);
        $this->assertSame('stellar-blade-review', $readerOrder['items'][1]['slug']);

        $platformRequest = new TestRestRequest([
            'platform' => 'nintendo-switch',
        ]);
        $filtered = $controller->handle_get_ratings($platformRequest);
        $this->assertIsArray($filtered);
        $this->assertCount(1, $filtered['items']);
        $this->assertSame('arcade-frenzy-verdict', $filtered['items'][0]['slug']);
        $this->assertSame(1, $filtered['summary']['total']);
        $this->assertSame('nintendo-switch', $filtered['filters']['platform']);

        $searchRequest = new TestRestRequest([
            'search' => 'arcade',
        ]);
        $searched = $controller->handle_get_ratings($searchRequest);
        $this->assertCount(1, $searched['items']);
        $this->assertSame('Arcade Frenzy Verdict', $searched['items'][0]['title']);
        $this->assertSame(1, $searched['pagination']['total']);
    }

    public function test_handle_get_ratings_returns_error_for_unknown_post(): void
    {
        $GLOBALS['jlg_test_current_user_can'] = static function ($capability) {
            return $capability === 'read';
        };

        $controller = new \JLG\Notation\Rest\RatingsController();
        $error      = $controller->handle_get_ratings(new TestRestRequest(['post_id' => 404]));

        $this->assertInstanceOf(WP_Error::class, $error);
        $this->assertSame('jlg_ratings_rest_not_found', $error->get_error_code());
    }

    public function test_handle_get_ratings_supports_date_filters(): void
    {
        $this->seedRatedPost(
            101,
            'Stellar Blade Review',
            'stellar-blade-review',
            9.1,
            8.7,
            142,
            [1 => 2, 2 => 6, 3 => 12, 4 => 40, 5 => 82],
            ['PlayStation 5', 'PC']
        );

        $this->seedRatedPost(
            202,
            'Arcade Frenzy Verdict',
            'arcade-frenzy-verdict',
            7.4,
            7.9,
            63,
            [1 => 1, 2 => 3, 3 => 8, 4 => 18, 5 => 33],
            ['Nintendo Switch'],
            'publish',
            '2025-02-20 09:00:00',
            '2025-02-20 08:00:00',
            '2025-02-22 11:00:00',
            '2025-02-22 10:00:00'
        );

        $GLOBALS['jlg_test_current_user_can'] = static function ($capability) {
            return $capability === 'read';
        };

        $controller = new \JLG\Notation\Rest\RatingsController();
        $fromResponse = $controller->handle_get_ratings(new TestRestRequest([
            'from' => '2025-02-01',
        ]));

        $this->assertIsArray($fromResponse);
        $this->assertCount(1, $fromResponse['items']);
        $this->assertSame('arcade-frenzy-verdict', $fromResponse['items'][0]['slug']);

        $toResponse = $controller->handle_get_ratings(new TestRestRequest([
            'to' => '2025-01-31',
        ]));

        $this->assertIsArray($toResponse);
        $this->assertCount(1, $toResponse['items']);
        $this->assertSame('stellar-blade-review', $toResponse['items'][0]['slug']);
    }

    public function test_handle_get_ratings_honours_status_filter(): void
    {
        $this->seedRatedPost(
            101,
            'Stellar Blade Review',
            'stellar-blade-review',
            9.1,
            8.7,
            142,
            [1 => 2, 2 => 6, 3 => 12, 4 => 40, 5 => 82],
            ['PlayStation 5']
        );

        $this->seedRatedPost(
            202,
            'Arcade Frenzy Verdict',
            'arcade-frenzy-verdict',
            7.4,
            7.9,
            63,
            [1 => 1, 2 => 3, 3 => 8, 4 => 18, 5 => 33],
            ['Nintendo Switch'],
            'draft'
        );

        $GLOBALS['jlg_test_current_user_can'] = static function ($capability) {
            return $capability === 'read';
        };

        $controller = new \JLG\Notation\Rest\RatingsController();

        $draftResponse = $controller->handle_get_ratings(new TestRestRequest([
            'status' => 'draft',
        ]));

        $this->assertIsArray($draftResponse);
        $this->assertCount(1, $draftResponse['items']);
        $this->assertSame('arcade-frenzy-verdict', $draftResponse['items'][0]['slug']);

        $publishResponse = $controller->handle_get_ratings(new TestRestRequest([
            'status' => 'publish',
        ]));

        $this->assertIsArray($publishResponse);
        $this->assertCount(1, $publishResponse['items']);
        $this->assertSame('stellar-blade-review', $publishResponse['items'][0]['slug']);
    }

    public function test_handle_get_ratings_uses_summary_cache_on_repeated_requests(): void
    {
        $this->seedRatedPost(
            101,
            'Stellar Blade Review',
            'stellar-blade-review',
            9.1,
            8.7,
            142,
            [1 => 2, 2 => 6, 3 => 12, 4 => 40, 5 => 82],
            ['PlayStation 5']
        );

        $this->seedRatedPost(
            202,
            'Arcade Frenzy Verdict',
            'arcade-frenzy-verdict',
            7.4,
            7.9,
            63,
            [1 => 1, 2 => 3, 3 => 8, 4 => 18, 5 => 33],
            ['Nintendo Switch']
        );

        $GLOBALS['jlg_test_current_user_can'] = static function ($capability) {
            return $capability === 'read';
        };

        add_filter('jlg_ratings_rest_summary_ttl', static function () {
            return 90;
        });

        $controller = new class extends \JLG\Notation\Rest\RatingsController {
            public $insightsCalls = 0;

            protected function resolve_summary_insights(array $post_ids)
            {
                $this->insightsCalls++;

                return parent::resolve_summary_insights($post_ids);
            }
        };

        $request = new TestRestRequest([
            'orderby' => 'editorial',
            'order'   => 'desc',
        ]);

        $firstResponse = $controller->handle_get_ratings($request);
        $this->assertSame(1, $controller->insightsCalls);
        $this->assertArrayHasKey('summary', $firstResponse);

        $secondResponse = $controller->handle_get_ratings($request);
        $this->assertSame(1, $controller->insightsCalls, 'Summary should be retrieved from cache on second call.');
        $this->assertSame($firstResponse['summary'], $secondResponse['summary']);

        remove_all_filters('jlg_ratings_rest_summary_ttl');
    }

    private function seedRatedPost(
        int $postId,
        string $title,
        string $slug,
        float $editorial,
        float $readers,
        int $votes,
        array $histogram,
        array $platforms,
        string $status = 'publish',
        string $published = '2025-01-10 10:00:00',
        string $publishedGmt = '2025-01-10 08:00:00',
        string $modified = '2025-01-12 11:30:00',
        string $modifiedGmt = '2025-01-12 09:30:00'
    ): void {
        $GLOBALS['jlg_test_posts'][$postId] = new WP_Post([
            'ID'               => $postId,
            'post_title'       => $title,
            'post_name'        => $slug,
            'post_type'        => 'post',
            'post_status'      => $status,
            'post_date'        => $published,
            'post_date_gmt'    => $publishedGmt,
            'post_modified'    => $modified,
            'post_modified_gmt'=> $modifiedGmt,
        ]);

        $GLOBALS['jlg_test_meta'][$postId] = [
            '_jlg_average_score'                => $editorial,
            '_jlg_user_rating_avg'              => $readers,
            '_jlg_user_rating_count'            => $votes,
            '_jlg_user_rating_breakdown'        => $histogram,
            '_jlg_plateformes'                  => $platforms,
            '_jlg_review_status'                => $postId === 101 ? 'in_progress' : 'final',
            '_jlg_platform_breakdown_entries'   => [
                [
                    'platform'    => 'pc',
                    'performance' => '60fps',
                    'comment'     => 'Version de référence',
                    'is_best'     => true,
                ],
            ],
        ];
    }
}

class TestRestRequest
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

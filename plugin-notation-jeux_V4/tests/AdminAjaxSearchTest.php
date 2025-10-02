<?php

use PHPUnit\Framework\TestCase;

if (!function_exists('wp_remote_get')) {
    function wp_remote_get($url, $args = []) {
        $pre = apply_filters('pre_http_request', false, $args, $url);

        if ($pre !== false) {
            return $pre;
        }

        return [
            'body' => '',
            'response' => ['code' => 200],
            'headers' => [],
        ];
    }
}

if (!function_exists('wp_remote_retrieve_response_code')) {
    function wp_remote_retrieve_response_code($response) {
        if (is_array($response) && isset($response['response']['code'])) {
            return (int) $response['response']['code'];
        }

        return 0;
    }
}

if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response) {
        if (is_array($response) && isset($response['body'])) {
            return (string) $response['body'];
        }

        return '';
    }
}

class AdminAjaxSearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_POST = [];
        remove_all_filters('pre_http_request');
        $GLOBALS['jlg_test_transients'] = [];
        update_option('notation_jlg_settings', \JLG\Notation\Helpers::get_default_settings());
        \JLG\Notation\Helpers::flush_plugin_options_cache();
    }

    protected function tearDown(): void
    {
        remove_all_filters('pre_http_request');
        $_POST = [];
        $GLOBALS['jlg_test_transients'] = [];
        \JLG\Notation\Helpers::flush_plugin_options_cache();
        parent::tearDown();
    }

    public function test_handle_rawg_search_returns_normalized_results_from_api(): void
    {
        $_POST['search'] = 'Elden Ring';
        $_POST['nonce'] = 'dummy-nonce';
        $_POST['page'] = 2;

        $settings = \JLG\Notation\Helpers::get_default_settings();
        $settings['rawg_api_key'] = 'demo-key';
        update_option('notation_jlg_settings', $settings);
        \JLG\Notation\Helpers::flush_plugin_options_cache();

        $captured_request = null;
        add_filter(
            'pre_http_request',
            static function ($preempt, $args, $url) use (&$captured_request) {
                $captured_request = [
                    'args' => $args,
                    'url' => $url,
                ];

                $payload = [
                    'count' => 123,
                    'next' => 'https://api.rawg.io/api/games?search=Elden+Ring&page=3',
                    'previous' => 'https://api.rawg.io/api/games?search=Elden+Ring&page=1',
                    'results' => [
                        [
                            'name' => 'Elden Ring',
                            'released' => '2022-02-25',
                            'developers' => [
                                ['name' => 'FromSoftware'],
                            ],
                            'publishers' => [
                                ['name' => 'Bandai Namco Entertainment'],
                            ],
                            'platforms' => [
                                ['platform' => ['name' => 'PC']],
                                ['platform' => ['name' => 'PlayStation 5']],
                            ],
                            'esrb_rating' => ['name' => 'Mature'],
                        ],
                    ],
                ];

                return [
                    'body' => wp_json_encode($payload),
                    'response' => ['code' => 200],
                    'headers' => [],
                ];
            },
            10,
            3
        );

        $ajax = new \JLG\Notation\Admin\Ajax();

        try {
            $ajax->handle_rawg_search();
            $this->fail('Expected WP_Send_Json_Exception to be thrown.');
        } catch (WP_Send_Json_Exception $exception) {
            $this->assertTrue($exception->success);
            $this->assertIsArray($exception->data);
            $this->assertArrayHasKey('games', $exception->data);
            $this->assertCount(1, $exception->data['games']);

            $game = $exception->data['games'][0];
            $this->assertSame('Elden Ring', $game['name']);
            $this->assertSame('2022-02-25', $game['release_date']);
            $this->assertSame(['FromSoftware'], $game['developers']);
            $this->assertSame(['Bandai Namco Entertainment'], $game['publishers']);
            $this->assertSame(['PC', 'PlayStation 5'], $game['platforms']);
            $this->assertSame('', $game['pegi']);

            $this->assertArrayHasKey('pagination', $exception->data);
            $this->assertSame(2, $exception->data['pagination']['current_page']);
            $this->assertSame(3, $exception->data['pagination']['next_page']);
            $this->assertSame(1, $exception->data['pagination']['prev_page']);
            $this->assertSame(123, $exception->data['pagination']['total_results']);

            $cache_key = 'jlg_rawg_search_' . md5(wp_json_encode(['Elden Ring', 2]));
            $this->assertSame($exception->data, get_transient($cache_key));

            $this->assertNotNull($captured_request);
            $this->assertSame(10, $captured_request['args']['timeout']);
            $this->assertStringContainsString('key=demo-key', $captured_request['url']);
            $this->assertStringContainsString('search=Elden+Ring', $captured_request['url']);
        }
    }

    public function test_handle_rawg_search_returns_simulated_results_without_api_key(): void
    {
        $_POST['search'] = addslashes('The "Legend" & Co.');
        $_POST['nonce'] = 'dummy-nonce';

        $ajax = new \JLG\Notation\Admin\Ajax();

        try {
            $ajax->handle_rawg_search();
            $this->fail('Expected WP_Send_Json_Exception to be thrown.');
        } catch (WP_Send_Json_Exception $exception) {
            $this->assertTrue($exception->success);
            $this->assertArrayHasKey('games', $exception->data);
            $this->assertNotEmpty($exception->data['games']);
            $this->assertSame('The "Legend" & Co. - Résultat simulé', $exception->data['games'][0]['name']);
        }
    }
}

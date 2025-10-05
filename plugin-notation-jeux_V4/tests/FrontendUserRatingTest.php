<?php

use PHPUnit\Framework\TestCase;

if (!function_exists('did_action')) {
    function did_action($hook_name)
    {
        return 0;
    }
}

class FrontendUserRatingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_POST    = [];
        $_COOKIE  = [];
        $_SERVER  = [];
        $GLOBALS['jlg_test_posts']       = [];
        $GLOBALS['jlg_test_meta']        = [];
        $GLOBALS['jlg_test_meta_updates'] = [];
        unset($GLOBALS['jlg_test_transients'], $GLOBALS['jlg_test_rest_routes'], $GLOBALS['jlg_test_current_user_id']);
        $this->resetShortcodeTracking();
        delete_option('notation_jlg_settings');
        delete_option('jlg_user_rating_reputation');
        delete_option('jlg_user_rating_banned_tokens');
        \JLG\Notation\Helpers::flush_plugin_options_cache();
        unset($GLOBALS['jlg_test_is_user_logged_in']);
    }

    public function test_handle_user_rating_rejects_unavailable_post(): void
    {
        $_POST['token']   = str_repeat('a', 32);
        $_POST['nonce']   = 'nonce';
        $_POST['post_id'] = '999';
        $_POST['rating']  = '5';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $frontend = new \JLG\Notation\Frontend();

        try {
            $frontend->handle_user_rating();
            $this->fail('Une réponse JSON devait être envoyée.');
        } catch (WP_Send_Json_Exception $exception) {
            $this->assertFalse($exception->success);
            $this->assertSame(404, $exception->status);
            $this->assertIsArray($exception->data);
            $this->assertStringContainsString('introuvable', $exception->data['message']);
        }

        $this->assertSame([], $GLOBALS['jlg_test_meta_updates']);
    }

    public function test_handle_user_rating_blocks_second_vote_from_same_ip(): void
    {
        $post_id = 321;
        $GLOBALS['jlg_test_posts'][$post_id] = new WP_Post([
            'ID'           => $post_id,
            'post_type'    => 'post',
            'post_status'  => 'publish',
            'post_content' => '[notation_utilisateurs_jlg]',
        ]);

        $_SERVER['REMOTE_ADDR'] = '198.51.100.42';

        $frontend = new \JLG\Notation\Frontend();

        $_POST = [
            'token'   => str_repeat('a', 32),
            'nonce'   => 'nonce',
            'post_id' => (string) $post_id,
            'rating'  => '4',
        ];

        try {
            $frontend->handle_user_rating();
            $this->fail('Une réponse JSON devait être envoyée.');
        } catch (WP_Send_Json_Exception $exception) {
            $this->assertTrue($exception->success);
            $this->assertNull($exception->status);
            $this->assertIsArray($exception->data);
            $this->assertArrayHasKey('new_weight', $exception->data);
            $this->assertSame(1.0, $exception->data['new_weight']);
            $this->assertArrayHasKey('weight_total', $exception->data);
            $this->assertSame(1.0, $exception->data['weight_total']);
        }

        $ip_hash = hash('sha256', '198.51.100.42|https://example.com');
        $this->assertArrayHasKey($post_id, $GLOBALS['jlg_test_meta']);
        $this->assertArrayHasKey('_jlg_user_rating_ips', $GLOBALS['jlg_test_meta'][$post_id]);
        $this->assertArrayHasKey($ip_hash, $GLOBALS['jlg_test_meta'][$post_id]['_jlg_user_rating_ips']);
        $this->assertArrayNotHasKey('legacy', $GLOBALS['jlg_test_meta'][$post_id]['_jlg_user_rating_ips'][$ip_hash]);

        $first_updates_count = count($GLOBALS['jlg_test_meta_updates']);

        $_POST = [
            'token'   => str_repeat('b', 32),
            'nonce'   => 'nonce',
            'post_id' => (string) $post_id,
            'rating'  => '5',
        ];
        $_COOKIE = [];
        unset($_COOKIE['jlg_user_rating_token']);
        $_SERVER['REMOTE_ADDR'] = '198.51.100.42';

        try {
            $frontend->handle_user_rating();
            $this->fail('Une réponse JSON devait être envoyée.');
        } catch (WP_Send_Json_Exception $exception) {
            $this->assertFalse($exception->success);
            $this->assertSame(409, $exception->status);
            $this->assertIsArray($exception->data);
            $this->assertSame(
                'Un vote depuis cette adresse IP a déjà été enregistré.',
                $exception->data['message']
            );
        }

        $this->assertSame($first_updates_count, count($GLOBALS['jlg_test_meta_updates']));

        $ratings_meta = $GLOBALS['jlg_test_meta'][$post_id]['_jlg_user_ratings'];
        $token_hash   = hash('sha256', str_repeat('a', 32));
        $this->assertArrayHasKey('__meta', $ratings_meta);
        $this->assertArrayHasKey('weights', $ratings_meta['__meta']);
        $this->assertSame(1.0, $ratings_meta['__meta']['weights'][$token_hash]);
    }

    public function test_handle_user_rating_blocks_second_vote_when_ip_filtered(): void
    {
        $post_id = 654321;
        $GLOBALS['jlg_test_posts'][$post_id] = new WP_Post([
            'ID'           => $post_id,
            'post_type'    => 'post',
            'post_status'  => 'publish',
            'post_content' => '[notation_utilisateurs_jlg]',
        ]);

        add_filter('jlg_user_rating_request_ip', static function () {
            return '203.0.113.200';
        });

        $_SERVER['REMOTE_ADDR'] = '192.0.2.10';

        $frontend = new \JLG\Notation\Frontend();

        $_POST = [
            'token'   => str_repeat('e', 32),
            'nonce'   => 'nonce',
            'post_id' => (string) $post_id,
            'rating'  => '4',
        ];

        try {
            $frontend->handle_user_rating();
            $this->fail('Une réponse JSON devait être envoyée.');
        } catch (WP_Send_Json_Exception $exception) {
            $this->assertTrue($exception->success);
            $this->assertNull($exception->status);
        }

        $ip_hash = hash('sha256', '203.0.113.200|https://example.com');
        $this->assertArrayHasKey($post_id, $GLOBALS['jlg_test_meta']);
        $this->assertArrayHasKey('_jlg_user_rating_ips', $GLOBALS['jlg_test_meta'][$post_id]);
        $this->assertArrayHasKey($ip_hash, $GLOBALS['jlg_test_meta'][$post_id]['_jlg_user_rating_ips']);

        $_POST = [
            'token'   => str_repeat('f', 32),
            'nonce'   => 'nonce',
            'post_id' => (string) $post_id,
            'rating'  => '5',
        ];
        $_SERVER['REMOTE_ADDR'] = '198.51.100.21';

        try {
            $frontend->handle_user_rating();
            $this->fail('Une réponse JSON devait être envoyée.');
        } catch (WP_Send_Json_Exception $exception) {
            $this->assertFalse($exception->success);
            $this->assertSame(409, $exception->status);
            $this->assertSame(
                'Un vote depuis cette adresse IP a déjà été enregistré.',
                $exception->data['message']
            );
        } finally {
            remove_all_filters('jlg_user_rating_request_ip');
        }
    }

    public function test_handle_user_rating_accepts_vote_when_shortcode_rendered_outside_content(): void
    {
        $post_id = 654;
        $GLOBALS['jlg_test_posts'][$post_id] = new WP_Post([
            'ID'           => $post_id,
            'post_type'    => 'post',
            'post_status'  => 'publish',
            'post_content' => '',
        ]);

        $_SERVER['REMOTE_ADDR'] = '203.0.113.5';

        $frontend = new \JLG\Notation\Frontend();
        \JLG\Notation\Frontend::mark_shortcode_rendered('notation_utilisateurs_jlg');

        $_POST = [
            'token'   => str_repeat('c', 32),
            'nonce'   => 'nonce',
            'post_id' => (string) $post_id,
            'rating'  => '5',
        ];

        try {
            $frontend->handle_user_rating();
            $this->fail('Une réponse JSON devait être envoyée.');
        } catch (WP_Send_Json_Exception $exception) {
            $this->assertTrue($exception->success);
            $this->assertNull($exception->status);
            $this->assertSame('5.00', $exception->data['new_average']);
            $this->assertSame(1, $exception->data['new_count']);
            $this->assertArrayHasKey('new_breakdown', $exception->data);
            $this->assertSame(
                [
                    1 => 0,
                    2 => 0,
                    3 => 0,
                    4 => 0,
                    5 => 1,
                ],
                $exception->data['new_breakdown']
            );
        }

        $this->assertArrayHasKey($post_id, $GLOBALS['jlg_test_meta']);
        $this->assertArrayHasKey('_jlg_user_rating_breakdown', $GLOBALS['jlg_test_meta'][$post_id]);
        $this->assertSame(
            [
                1 => 0,
                2 => 0,
                3 => 0,
                4 => 0,
                5 => 1,
            ],
            $GLOBALS['jlg_test_meta'][$post_id]['_jlg_user_rating_breakdown']
        );
    }

    public function test_handle_user_rating_accepts_vote_on_custom_post_type(): void
    {
        add_filter('jlg_rated_post_types', static function ($types) {
            $types[] = 'jlg_review';

            return $types;
        });

        $post_id = 789;
        $GLOBALS['jlg_test_posts'][$post_id] = new WP_Post([
            'ID'           => $post_id,
            'post_type'    => 'jlg_review',
            'post_status'  => 'publish',
            'post_content' => '[notation_utilisateurs_jlg]',
        ]);

        $_SERVER['REMOTE_ADDR'] = '198.51.100.99';

        $frontend = new \JLG\Notation\Frontend();

        $_POST = [
            'token'   => str_repeat('d', 32),
            'nonce'   => 'nonce',
            'post_id' => (string) $post_id,
            'rating'  => '3',
        ];

        try {
            $frontend->handle_user_rating();
            $this->fail('Une réponse JSON devait être envoyée.');
        } catch (WP_Send_Json_Exception $exception) {
            $this->assertTrue($exception->success);
            $this->assertNull($exception->status);
            $this->assertSame('3.00', $exception->data['new_average']);
            $this->assertSame(1, $exception->data['new_count']);
            $this->assertArrayHasKey('new_breakdown', $exception->data);
            $this->assertSame(
                [
                    1 => 0,
                    2 => 0,
                    3 => 1,
                    4 => 0,
                    5 => 0,
                ],
                $exception->data['new_breakdown']
            );
        } finally {
            remove_all_filters('jlg_rated_post_types');
        }

        $this->assertArrayHasKey($post_id, $GLOBALS['jlg_test_meta']);
        $this->assertArrayHasKey('_jlg_user_rating_breakdown', $GLOBALS['jlg_test_meta'][$post_id]);
        $this->assertSame(
            [
                1 => 0,
                2 => 0,
                3 => 1,
                4 => 0,
                5 => 0,
            ],
            $GLOBALS['jlg_test_meta'][$post_id]['_jlg_user_rating_breakdown']
        );
    }

    public function test_handle_user_rating_requires_login_when_option_enabled(): void
    {
        $post_id = 4242;
        $GLOBALS['jlg_test_posts'][$post_id] = new WP_Post([
            'ID'           => $post_id,
            'post_type'    => 'post',
            'post_status'  => 'publish',
            'post_content' => '[notation_utilisateurs_jlg]',
        ]);

        update_option('notation_jlg_settings', [
            'user_rating_requires_login' => 1,
            'user_rating_enabled'        => 1,
        ]);
        \JLG\Notation\Helpers::flush_plugin_options_cache();

        $_SERVER['REMOTE_ADDR'] = '198.18.0.1';

        $frontend = new \JLG\Notation\Frontend();

        $_POST = [
            'token'   => str_repeat('e', 32),
            'nonce'   => 'nonce',
            'post_id' => (string) $post_id,
            'rating'  => '4',
        ];

        try {
            $frontend->handle_user_rating();
            $this->fail('Une réponse JSON devait être envoyée.');
        } catch (WP_Send_Json_Exception $exception) {
            $this->assertFalse($exception->success);
            $this->assertSame(401, $exception->status);
            $this->assertIsArray($exception->data);
            $this->assertSame('Connectez-vous pour voter.', $exception->data['message']);
            $this->assertArrayHasKey('requires_login', $exception->data);
            $this->assertTrue($exception->data['requires_login']);
        }
    }

    public function test_handle_user_rating_accepts_vote_when_login_required_and_user_authenticated(): void
    {
        $post_id = 5252;
        $GLOBALS['jlg_test_posts'][$post_id] = new WP_Post([
            'ID'           => $post_id,
            'post_type'    => 'post',
            'post_status'  => 'publish',
            'post_content' => '[notation_utilisateurs_jlg]',
        ]);

        update_option('notation_jlg_settings', [
            'user_rating_requires_login' => 1,
            'user_rating_enabled'        => 1,
        ]);
        \JLG\Notation\Helpers::flush_plugin_options_cache();

        $GLOBALS['jlg_test_is_user_logged_in'] = true;
        $_SERVER['REMOTE_ADDR'] = '198.18.0.5';

        $frontend = new \JLG\Notation\Frontend();

        $_POST = [
            'token'   => str_repeat('f', 32),
            'nonce'   => 'nonce',
            'post_id' => (string) $post_id,
            'rating'  => '5',
        ];

        try {
            $frontend->handle_user_rating();
            $this->fail('Une réponse JSON devait être envoyée.');
        } catch (WP_Send_Json_Exception $exception) {
            $this->assertTrue($exception->success);
            $this->assertNull($exception->status);
            $this->assertSame('5.00', $exception->data['new_average']);
            $this->assertSame(1, $exception->data['new_count']);
            $this->assertArrayHasKey('new_breakdown', $exception->data);
            $this->assertSame(1, (int) ($GLOBALS['jlg_test_meta'][$post_id]['_jlg_user_rating_count'] ?? 0));
        } finally {
            unset($GLOBALS['jlg_test_is_user_logged_in']);
        }
    }

    public function test_get_user_rating_breakdown_retrofills_missing_meta(): void
    {
        $post_id = 2468;
        $token_hash = str_repeat('a', 64);
        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_user_ratings'] = [
            $token_hash => 4,
            '__meta' => [
                'version'    => 2,
                'timestamps' => [
                    $token_hash => time(),
                ],
            ],
        ];

        $frontend = new \JLG\Notation\Frontend();

        $breakdown = \JLG\Notation\Frontend::get_user_rating_breakdown_for_post($post_id);

        $this->assertSame(
            [
                1 => 0,
                2 => 0,
                3 => 0,
                4 => 1,
                5 => 0,
            ],
            $breakdown
        );

        $this->assertArrayHasKey('_jlg_user_rating_breakdown', $GLOBALS['jlg_test_meta'][$post_id]);
        $this->assertSame($breakdown, $GLOBALS['jlg_test_meta'][$post_id]['_jlg_user_rating_breakdown']);
    }

    public function test_handle_user_rating_throttles_fast_retries_for_logged_in_users(): void
    {
        $post_id = 4001;
        $GLOBALS['jlg_test_posts'][$post_id] = new WP_Post([
            'ID'           => $post_id,
            'post_type'    => 'post',
            'post_status'  => 'publish',
            'post_content' => '[notation_utilisateurs_jlg]',
        ]);

        add_filter('jlg_user_rating_request_ip', '__return_empty_string');

        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit/Throttle';
        $GLOBALS['jlg_test_is_user_logged_in'] = true;
        $GLOBALS['jlg_test_current_user_id']   = 84;

        $frontend = new \JLG\Notation\Frontend();

        $_POST = [
            'token'   => str_repeat('1', 32),
            'nonce'   => 'nonce',
            'post_id' => (string) $post_id,
            'rating'  => '5',
        ];

        try {
            $frontend->handle_user_rating();
        } catch (WP_Send_Json_Exception $exception) {
            $this->assertTrue($exception->success);
        }

        $_POST['token']  = str_repeat('2', 32);
        $_POST['rating'] = '4';

        try {
            $frontend->handle_user_rating();
            $this->fail('Le throttling aurait dû bloquer ce second vote.');
        } catch (WP_Send_Json_Exception $exception) {
            $this->assertFalse($exception->success);
            $this->assertSame(429, $exception->status);
            $this->assertStringContainsString('patienter', $exception->data['message']);
        } finally {
            remove_all_filters('jlg_user_rating_request_ip');
            unset($GLOBALS['jlg_test_is_user_logged_in'], $GLOBALS['jlg_test_current_user_id']);
        }
    }

    public function test_handle_user_rating_blocks_same_account_with_rotating_ip(): void
    {
        $post_id = 4002;
        $GLOBALS['jlg_test_posts'][$post_id] = new WP_Post([
            'ID'           => $post_id,
            'post_type'    => 'post',
            'post_status'  => 'publish',
            'post_content' => '[notation_utilisateurs_jlg]',
        ]);

        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit/Throttle-Rotation';
        $GLOBALS['jlg_test_is_user_logged_in'] = true;
        $GLOBALS['jlg_test_current_user_id']   = 123;

        $frontend = new \JLG\Notation\Frontend();

        $_SERVER['REMOTE_ADDR'] = '198.51.100.77';
        $_POST = [
            'token'   => str_repeat('3', 32),
            'nonce'   => 'nonce',
            'post_id' => (string) $post_id,
            'rating'  => '3',
        ];

        try {
            $frontend->handle_user_rating();
        } catch (WP_Send_Json_Exception $exception) {
            $this->assertTrue($exception->success);
        }

        $_SERVER['REMOTE_ADDR'] = '203.0.113.55';
        $_POST['token']         = str_repeat('4', 32);
        $_POST['rating']        = '4';

        try {
            $frontend->handle_user_rating();
            $this->fail('Le throttling utilisateur aurait dû bloquer ce vote.');
        } catch (WP_Send_Json_Exception $exception) {
            $this->assertFalse($exception->success);
            $this->assertSame(429, $exception->status);
            $this->assertStringContainsString('patienter', $exception->data['message']);
        } finally {
            unset($GLOBALS['jlg_test_is_user_logged_in'], $GLOBALS['jlg_test_current_user_id']);
        }
    }

    public function test_handle_user_rating_rejects_banned_token(): void
    {
        $post_id = 4003;
        $GLOBALS['jlg_test_posts'][$post_id] = new WP_Post([
            'ID'           => $post_id,
            'post_type'    => 'post',
            'post_status'  => 'publish',
            'post_content' => '[notation_utilisateurs_jlg]',
        ]);

        $_SERVER['REMOTE_ADDR']     = '192.0.2.88';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit/Ban';
        $frontend                   = new \JLG\Notation\Frontend();
        $token                      = str_repeat('5', 32);
        $token_hash                 = hash('sha256', $token);

        \JLG\Notation\Frontend::ban_user_rating_token_hash($token_hash, ['note' => 'Test ban']);

        $_POST = [
            'token'   => $token,
            'nonce'   => 'nonce',
            'post_id' => (string) $post_id,
            'rating'  => '2',
        ];

        try {
            $frontend->handle_user_rating();
            $this->fail('Les jetons bannis doivent être rejetés.');
        } catch (WP_Send_Json_Exception $exception) {
            $this->assertFalse($exception->success);
            $this->assertSame(403, $exception->status);
            $this->assertStringContainsString('bloqué', $exception->data['message']);
        }
    }

    public function test_weighted_average_is_calculated_from_category_scores(): void
    {
        $options = \JLG\Notation\Helpers::get_default_settings();
        $categories = $options['rating_categories'];
        $categories[0]['weight'] = 2.5;
        $categories[1]['weight'] = 0.5;

        $options['rating_categories'] = $categories;

        update_option('notation_jlg_settings', $options);
        \JLG\Notation\Helpers::flush_plugin_options_cache();

        $post_id = 9876;
        $GLOBALS['jlg_test_meta'][$post_id]['_note_' . $categories[0]['id']] = 9;
        $GLOBALS['jlg_test_meta'][$post_id]['_note_' . $categories[1]['id']] = 5;

        $average = \JLG\Notation\Helpers::get_average_score_for_post($post_id);

        $this->assertSame(8.3, $average, 'Weighted averages should use the configured category weights.');
    }

    private function resetShortcodeTracking(): void
    {
        $reflection = new \ReflectionClass(\JLG\Notation\Frontend::class);

        foreach ([
            'rendered_shortcodes' => [],
            'shortcode_rendered'  => false,
        ] as $property => $value) {
            if ($reflection->hasProperty($property)) {
                $property_reflection = $reflection->getProperty($property);
                $property_reflection->setAccessible(true);
                $property_reflection->setValue(null, $value);
            }
        }
    }
}

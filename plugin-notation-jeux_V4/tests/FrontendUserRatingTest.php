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
        $this->resetShortcodeTracking();
    }

    public function test_handle_user_rating_rejects_unavailable_post(): void
    {
        $_POST['token']   = str_repeat('a', 32);
        $_POST['nonce']   = 'nonce';
        $_POST['post_id'] = '999';
        $_POST['rating']  = '5';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $frontend = new JLG_Frontend();

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

        $frontend = new JLG_Frontend();

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
        }

        $ip_hash = wp_hash('198.51.100.42');
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

        $frontend = new JLG_Frontend();
        JLG_Frontend::mark_shortcode_rendered('notation_utilisateurs_jlg');

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
        }
    }

    private function resetShortcodeTracking(): void
    {
        $reflection = new \ReflectionClass(JLG_Frontend::class);

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

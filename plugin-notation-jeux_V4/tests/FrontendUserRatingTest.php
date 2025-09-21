<?php

use PHPUnit\Framework\TestCase;

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
}

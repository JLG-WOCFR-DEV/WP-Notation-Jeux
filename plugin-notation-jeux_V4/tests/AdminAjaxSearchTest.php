<?php

use PHPUnit\Framework\TestCase;

class AdminAjaxSearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_POST = [];
    }

    protected function tearDown(): void
    {
        $_POST = [];
        parent::tearDown();
    }

    public function test_handle_rawg_search_accepts_special_characters(): void
    {
        $_POST['search'] = addslashes('The "Legend" & Co.');
        $_POST['nonce'] = 'dummy-nonce';

        $ajax = new JLG_Admin_Ajax();

        try {
            $ajax->handle_rawg_search();
            $this->fail('Expected WP_Send_Json_Exception to be thrown.');
        } catch (WP_Send_Json_Exception $exception) {
            $this->assertTrue($exception->success);
            $this->assertArrayHasKey('games', $exception->data);
            $this->assertIsArray($exception->data['games']);
            $this->assertNotEmpty($exception->data['games']);

            $game = $exception->data['games'][0];

            $this->assertArrayHasKey('name', $game);
            $this->assertSame('The "Legend" & Co. - Résultat simulé', $game['name']);
        }
    }
}

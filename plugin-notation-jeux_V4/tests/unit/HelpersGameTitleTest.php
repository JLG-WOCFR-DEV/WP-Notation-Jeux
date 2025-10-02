<?php

use PHPUnit\Framework\TestCase;

class HelpersGameTitleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['jlg_test_posts'] = [];
        $GLOBALS['jlg_test_meta'] = [];
    }

    public function test_returns_custom_game_title_when_meta_present(): void
    {
        $post_id = 42;
        $GLOBALS['jlg_test_meta'][$post_id]['_jlg_game_title'] = '  Custom Game Title  ';

        $this->assertSame('Custom Game Title', JLG_Helpers::get_game_title($post_id));
    }

    public function test_falls_back_to_wordpress_title_when_meta_missing(): void
    {
        $post_id = 101;
        $GLOBALS['jlg_test_posts'][$post_id] = (object) [
            'ID' => $post_id,
            'post_title' => 'Original WP Title',
        ];

        $this->assertSame('Original WP Title', JLG_Helpers::get_game_title($post_id));
    }
}

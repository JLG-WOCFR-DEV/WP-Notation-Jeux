<?php

use PHPUnit\Framework\TestCase;

class RestUserRatingControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_POST   = [];
        $_COOKIE = [];
        $_SERVER = [];
        $GLOBALS['jlg_test_posts'] = [];
        $GLOBALS['jlg_test_meta']  = [];
    }

    public function test_check_permissions_requires_token(): void
    {
        $controller = new \JLG\Notation\REST\UserRatingController();
        $request    = new WP_REST_Request();

        $result = $controller->check_permissions($request);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('jlg_missing_token', $result->get_error_code());
    }

    public function test_create_item_records_vote_and_returns_stats(): void
    {
        $controller = new \JLG\Notation\REST\UserRatingController();
        new \JLG\Notation\Frontend();

        $post_id = 321;
        $GLOBALS['jlg_test_posts'][$post_id] = new WP_Post([
            'ID'           => $post_id,
            'post_type'    => 'post',
            'post_status'  => 'publish',
            'post_content' => '[notation_utilisateurs_jlg]',
        ]);

        $_SERVER['REMOTE_ADDR'] = '198.51.100.42';

        $token = str_repeat('a', 32);
        $nonce = wp_create_nonce('jlg_user_rating_nonce_' . $token);

        $request = new WP_REST_Request([
            'token'   => $token,
            'nonce'   => $nonce,
            'post_id' => (string) $post_id,
            'rating'  => '5',
        ]);

        $response = $controller->create_item($request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $data = $response->get_data();

        $this->assertSame('5.00', $data['new_average'] ?? null);
        $this->assertSame(1, $data['new_count'] ?? null);

        $this->assertArrayHasKey($post_id, $GLOBALS['jlg_test_meta']);
        $this->assertArrayHasKey('_jlg_user_rating_avg', $GLOBALS['jlg_test_meta'][$post_id]);
    }
}

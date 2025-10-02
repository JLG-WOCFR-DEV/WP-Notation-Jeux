<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/rest/class-jlg-rest-controller.php';

if (!class_exists('WP_REST_Server')) {
    class WP_REST_Server
    {
        public const READABLE = 'GET';
        public const CREATABLE = 'POST';
    }
}

if (!class_exists('WP_REST_Response')) {
    class WP_REST_Response
    {
        public $data;
        public $status;

        public function __construct($data = null, $status = 200)
        {
            $this->data = $data;
            $this->status = (int) $status;
        }

        public function get_data()
        {
            return $this->data;
        }

        public function get_status()
        {
            return $this->status;
        }
    }
}

if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request
    {
        private $params;
        private $json_params;
        private $headers;

        public function __construct(array $params = [], $json_params = null, array $headers = [])
        {
            $this->params = $params;
            $this->json_params = is_array($json_params) ? $json_params : [];
            $normalized_headers = [];
            foreach ($headers as $key => $value) {
                $normalized_headers[strtolower($key)] = $value;
            }

            $this->headers = $normalized_headers;
        }

        public function get_param($key)
        {
            return $this->params[$key] ?? null;
        }

        public function get_params()
        {
            return $this->params;
        }

        public function get_json_params()
        {
            return $this->json_params;
        }

        public function get_header($key)
        {
            $key = strtolower($key);

            return $this->headers[$key] ?? '';
        }
    }
}

if (!function_exists('register_rest_route')) {
    function register_rest_route($namespace, $route, $args)
    {
        if (!isset($GLOBALS['jlg_test_rest_routes'])) {
            $GLOBALS['jlg_test_rest_routes'] = [];
        }

        $GLOBALS['jlg_test_rest_routes'][] = [$namespace, $route, $args];

        return true;
    }
}

if (!function_exists('rest_ensure_response')) {
    function rest_ensure_response($data)
    {
        return $data instanceof WP_REST_Response ? $data : new WP_REST_Response($data);
    }
}

class RestControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['jlg_test_rest_routes'] = [];
        unset($GLOBALS['jlg_test_wp_verify_nonce'], $GLOBALS['jlg_test_current_user_can']);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['jlg_test_wp_verify_nonce'], $GLOBALS['jlg_test_current_user_can']);
        parent::tearDown();
    }

    public function test_register_routes_registers_expected_endpoints(): void
    {
        $controller = JLG_REST_Controller::get_instance();
        $controller->register_routes();

        $this->assertCount(4, $GLOBALS['jlg_test_rest_routes']);

        $methods_by_route = [];
        foreach ($GLOBALS['jlg_test_rest_routes'] as $route_definition) {
            [$namespace, $route, $args] = $route_definition;
            $this->assertSame('jlg/v1', $namespace);
            $this->assertIsArray($args);
            $this->assertArrayHasKey('methods', $args);
            $this->assertArrayHasKey('callback', $args);
            $this->assertIsCallable($args['callback']);
            $this->assertArrayHasKey('permission_callback', $args);
            $this->assertIsCallable($args['permission_callback']);

            $methods_by_route[$route] = $args['methods'];
        }

        $this->assertSame(WP_REST_Server::CREATABLE, $methods_by_route['/ratings'] ?? null);
        $this->assertSame(WP_REST_Server::CREATABLE, $methods_by_route['/summary'] ?? null);
        $this->assertSame(WP_REST_Server::CREATABLE, $methods_by_route['/game-explorer'] ?? null);
        $this->assertSame(WP_REST_Server::READABLE, $methods_by_route['/rawg-search'] ?? null);
    }

    public function test_verify_public_rest_nonce_rejects_invalid_nonce(): void
    {
        $controller = JLG_REST_Controller::get_instance();
        $request = new WP_REST_Request([], null, ['X-WP-Nonce' => 'invalid-nonce']);

        $GLOBALS['jlg_test_wp_verify_nonce'] = function ($nonce, $action) {
            TestCase::assertSame('invalid-nonce', $nonce);
            TestCase::assertSame('wp_rest', $action);

            return false;
        };

        $result = $controller->verify_public_rest_nonce($request);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('jlg_rest_invalid_nonce', $result->get_error_code());
        $this->assertSame(403, $result->get_error_data()['status'] ?? null);
    }

    public function test_verify_admin_rest_permissions_requires_capability(): void
    {
        $controller = JLG_REST_Controller::get_instance();
        $request = new WP_REST_Request([], null, ['X-WP-Nonce' => 'valid-nonce']);

        $GLOBALS['jlg_test_wp_verify_nonce'] = fn () => true;
        $GLOBALS['jlg_test_current_user_can'] = static function ($capability) {
            TestCase::assertSame('edit_posts', $capability);

            return false;
        };

        $result = $controller->verify_admin_rest_permissions($request);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('jlg_rest_forbidden', $result->get_error_code());
        $this->assertSame(403, $result->get_error_data()['status'] ?? null);
    }

    public function test_handle_user_rating_returns_error_response_when_submission_fails(): void
    {
        $controller = JLG_REST_Controller::get_instance();
        $request = new WP_REST_Request([
            'post_id'    => 123,
            'rating'     => 5,
            'token'      => '',
            'tokenNonce' => 'nonce-jlg',
        ]);

        $response = $controller->handle_user_rating($request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(400, $response->get_status());
        $this->assertSame('jlg_missing_rating_token', $response->get_data()['code'] ?? null);
        $this->assertStringContainsString('Jeton', $response->get_data()['message'] ?? '');
    }

    public function test_handle_rawg_search_returns_error_response_on_invalid_payload(): void
    {
        $controller = JLG_REST_Controller::get_instance();
        $request = new WP_REST_Request([
            'search' => '',
            'page'   => 1,
        ]);

        $response = $controller->handle_rawg_search($request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(400, $response->get_status());
        $this->assertSame('jlg_rawg_empty_search', $response->get_data()['code'] ?? null);
        $this->assertStringContainsString('Terme de recherche vide', $response->get_data()['message'] ?? '');
    }
}

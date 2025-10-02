<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class JLG_REST_Controller {
    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        register_rest_route(
            'jlg/v1',
            '/ratings',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'handle_user_rating' ),
                'permission_callback' => array( $this, 'verify_public_rest_nonce' ),
                'args'                => array(
                    'post_id'    => array(
                        'type'     => 'integer',
                        'required' => true,
                    ),
                    'rating'     => array(
                        'type'     => 'integer',
                        'required' => true,
                    ),
                    'token'      => array(
                        'type'     => 'string',
                        'required' => true,
                    ),
                    'tokenNonce' => array(
                        'type'     => 'string',
                        'required' => true,
                    ),
                ),
            )
        );

        register_rest_route(
            'jlg/v1',
            '/summary',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'handle_summary_request' ),
                'permission_callback' => array( $this, 'verify_public_rest_nonce' ),
            )
        );

        register_rest_route(
            'jlg/v1',
            '/game-explorer',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'handle_game_explorer_request' ),
                'permission_callback' => array( $this, 'verify_public_rest_nonce' ),
            )
        );

        register_rest_route(
            'jlg/v1',
            '/rawg-search',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'handle_rawg_search' ),
                'permission_callback' => array( $this, 'verify_admin_rest_permissions' ),
                'args'                => array(
                    'search' => array(
                        'type'     => 'string',
                        'required' => true,
                    ),
                    'page'   => array(
                        'type'    => 'integer',
                        'default' => 1,
                    ),
                ),
            )
        );
    }

    public function verify_public_rest_nonce( WP_REST_Request $request ) {
        $nonce = $this->extract_rest_nonce( $request );

        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return new WP_Error(
                'jlg_rest_invalid_nonce',
                __( 'La vérification de sécurité a échoué.', 'notation-jlg' ),
                array( 'status' => 403 )
            );
        }

        return true;
    }

    public function verify_admin_rest_permissions( WP_REST_Request $request ) {
        $nonce_verification = $this->verify_public_rest_nonce( $request );

        if ( is_wp_error( $nonce_verification ) ) {
            return $nonce_verification;
        }

        if ( ! current_user_can( 'edit_posts' ) ) {
            return new WP_Error(
                'jlg_rest_forbidden',
                __( 'Permissions insuffisantes.', 'notation-jlg' ),
                array( 'status' => 403 )
            );
        }

        return true;
    }

    public function handle_user_rating( WP_REST_Request $request ) {
        $payload = array(
            'post_id'     => $request->get_param( 'post_id' ),
            'rating'      => $request->get_param( 'rating' ),
            'token'       => $request->get_param( 'token' ),
            'token_nonce' => $request->get_param( 'tokenNonce' ),
        );

        $result = JLG_Frontend::process_user_rating_submission( $payload );

        if ( is_wp_error( $result ) ) {
            return $this->error_to_response( $result );
        }

        return rest_ensure_response( $result );
    }

    public function handle_summary_request( WP_REST_Request $request ) {
        $payload = $this->prepare_payload( $request );
        $result  = JLG_Frontend::process_summary_request( $payload );

        if ( is_wp_error( $result ) ) {
            return $this->error_to_response( $result );
        }

        return rest_ensure_response( $result );
    }

    public function handle_game_explorer_request( WP_REST_Request $request ) {
        $payload = $this->prepare_payload( $request );
        $result  = JLG_Frontend::process_game_explorer_request( $payload );

        if ( is_wp_error( $result ) ) {
            return $this->error_to_response( $result );
        }

        return rest_ensure_response( $result );
    }

    public function handle_rawg_search( WP_REST_Request $request ) {
        $payload = array(
            'search' => $request->get_param( 'search' ),
            'page'   => $request->get_param( 'page' ),
        );

        $result = JLG_Admin_Ajax::process_rawg_search( $payload );

        if ( is_wp_error( $result ) ) {
            return $this->error_to_response( $result );
        }

        return rest_ensure_response( $result );
    }

    private function extract_rest_nonce( WP_REST_Request $request ) {
        $nonce = $request->get_header( 'X-WP-Nonce' );

        if ( ! $nonce ) {
            $nonce = $request->get_param( '_wpnonce' );
        }

        if ( ! $nonce && isset( $_REQUEST['_wpnonce'] ) ) {
            $nonce = wp_unslash( $_REQUEST['_wpnonce'] );
        }

        return is_string( $nonce ) ? $nonce : '';
    }

    private function prepare_payload( WP_REST_Request $request ) {
        $data = $request->get_json_params();

        if ( ! is_array( $data ) ) {
            $data = array();
        }

        $query_params = $request->get_params();
        if ( is_array( $query_params ) ) {
            $data = array_merge( $query_params, $data );
        }

        return $data;
    }

    private function error_to_response( WP_Error $error ) {
        $status = $error->get_error_data();

        if ( is_array( $status ) && isset( $status['status'] ) ) {
            $status = (int) $status['status'];
        } elseif ( ! is_numeric( $status ) ) {
            $status = 400;
        }

        return new WP_REST_Response(
            array(
                'message' => $error->get_error_message(),
                'code'    => $error->get_error_code(),
            ),
            (int) $status
        );
    }
}

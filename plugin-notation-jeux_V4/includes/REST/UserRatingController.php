<?php

namespace JLG\Notation\REST;

use JLG\Notation\Frontend;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class UserRatingController extends WP_REST_Controller {

    public function __construct() {
        $this->namespace = 'notation-jlg/v1';
        $this->rest_base = 'user-rating';
    }

    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'create_item' ),
                    'permission_callback' => array( $this, 'check_permissions' ),
                ),
            )
        );
    }

    public function check_permissions( WP_REST_Request $request ) {
        $token = Frontend::normalize_user_rating_token( $request->get_param( 'token' ) );
        if ( $token === '' ) {
            return new WP_Error(
                'jlg_missing_token',
                esc_html__( 'Jeton de sécurité manquant ou invalide.', 'notation-jlg' ),
                array( 'status' => 400 )
            );
        }

        $nonce = $request->get_param( 'nonce' );
        if ( ! is_string( $nonce ) || ! wp_verify_nonce( $nonce, 'jlg_user_rating_nonce_' . $token ) ) {
            return new WP_Error(
                'jlg_invalid_nonce',
                esc_html__( 'La vérification de sécurité a échoué.', 'notation-jlg' ),
                array( 'status' => 403 )
            );
        }

        return true;
    }

    public function create_item( WP_REST_Request $request ) {
        $frontend = Frontend::get_instance();
        if ( ! ( $frontend instanceof Frontend ) ) {
            $frontend = new Frontend();
        }

        $payload = array(
            'token'   => $request->get_param( 'token' ),
            'nonce'   => $request->get_param( 'nonce' ),
            'post_id' => $request->get_param( 'post_id' ),
            'rating'  => $request->get_param( 'rating' ),
        );

        $result = $frontend->process_user_rating_submission( $payload );

        if ( is_wp_error( $result ) ) {
            return $this->error_to_response( $result );
        }

        return rest_ensure_response( $result );
    }
}

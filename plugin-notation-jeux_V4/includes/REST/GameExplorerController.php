<?php

namespace JLG\Notation\REST;

use JLG\Notation\Helpers;
use JLG\Notation\Shortcodes\GameExplorer;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GameExplorerController extends WP_REST_Controller {

    public function __construct() {
        $this->namespace = 'notation-jlg/v1';
        $this->rest_base = 'game-explorer';
    }

    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_items' ),
                    'permission_callback' => array( $this, 'check_permissions' ),
                ),
            )
        );
    }

    public function check_permissions( WP_REST_Request $request ) {
        $expected_key = Helpers::get_rest_public_key();

        if ( $expected_key === '' ) {
            return new WP_Error(
                'jlg_rest_public_key_missing',
                esc_html__( 'Aucune clé publique REST n\'est configurée.', 'notation-jlg' ),
                array( 'status' => 403 )
            );
        }

        $provided = $request->get_param( 'public_key' );
        if ( is_string( $provided ) ) {
            $provided = sanitize_text_field( $provided );
        } else {
            $provided = '';
        }

        if ( $provided !== '' && hash_equals( $expected_key, $provided ) ) {
            return true;
        }

        $header = $request->get_header( 'x-jlg-public-key' );
        if ( is_string( $header ) && $header !== '' && hash_equals( $expected_key, sanitize_text_field( $header ) ) ) {
            return true;
        }

        return new WP_Error(
            'jlg_rest_forbidden',
            esc_html__( 'Clé publique REST invalide.', 'notation-jlg' ),
            array( 'status' => 403 )
        );
    }

    public function get_items( WP_REST_Request $request ) {
        if ( ! class_exists( GameExplorer::class ) ) {
            return $this->error_to_response(
                new WP_Error(
                    'jlg_rest_missing_feature',
                    esc_html__( 'Le module Game Explorer est indisponible.', 'notation-jlg' ),
                    array( 'status' => 500 )
                )
            );
        }

        $params = $request->get_params();
        if ( ! is_array( $params ) ) {
            $params = array();
        }

        $atts   = GameExplorer::prepare_interactive_atts( $params );
        $result = GameExplorer::prepare_interactive_response( $atts, $params );

        return rest_ensure_response( $result['response'] );
    }
}

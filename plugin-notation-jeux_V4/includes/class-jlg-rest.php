<?php
if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

class JLG_REST {
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        register_rest_route(
            'jlg/v1',
            '/rating-summary/(?P<post_id>\d+)',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_rating_summary' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'post_id' => array(
                        'validate_callback' => static function ( $value ) {
                            return is_numeric( $value ) && (int) $value > 0;
                        },
                    ),
                ),
            )
        );
    }

    public function get_rating_summary( WP_REST_Request $request ) {
        $post_id = (int) $request['post_id'];
        $post    = get_post( $post_id );

        if ( ! $post || 'trash' === $post->post_status ) {
            return new WP_Error( 'jlg_invalid_post', __( 'Post not found.', 'notation-jlg' ), array( 'status' => 404 ) );
        }

        $average_score = null;
        if ( class_exists( 'JLG_Helpers' ) ) {
            $average_score = JLG_Helpers::get_resolved_average_score( $post_id );
        }

        $user_rating = array(
            'average' => (float) get_post_meta( $post_id, '_jlg_user_rating_avg', true ),
            'count'   => (int) get_post_meta( $post_id, '_jlg_user_rating_count', true ),
        );

        return rest_ensure_response(
            array(
                'post_id'       => $post_id,
                'average_score' => $average_score,
                'user_ratings'  => $user_rating,
                'version'       => defined( 'JLG_NOTATION_VERSION' ) ? JLG_NOTATION_VERSION : null,
            )
        );
    }
}

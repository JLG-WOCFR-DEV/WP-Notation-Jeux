<?php
/**
 * Ensure the REST API surface provided by the plugin is registered and functional.
 */
class RestRoutesTest extends WP_UnitTestCase {
    public function test_rating_summary_route_returns_payload(): void {
        $post_id = self::factory()->post->create(
            array(
                'post_status' => 'publish',
            )
        );

        update_post_meta( $post_id, '_note_cat1', 16 );
        update_post_meta( $post_id, '_note_cat2', 18 );
        update_post_meta( $post_id, '_note_cat3', 14 );
        update_post_meta( $post_id, '_jlg_user_rating_avg', 4.2 );
        update_post_meta( $post_id, '_jlg_user_rating_count', 11 );

        $request  = new WP_REST_Request( 'GET', '/jlg/v1/rating-summary/' . $post_id );
        $response = rest_do_request( $request );

        $this->assertSame( 200, $response->get_status(), 'REST endpoint should answer successfully.' );

        $data = $response->get_data();
        $this->assertSame( $post_id, $data['post_id'] );
        $this->assertArrayHasKey( 'value', $data['average_score'] );
        $this->assertSame( 11, $data['user_ratings']['count'] );
        $this->assertSame( 4.2, $data['user_ratings']['average'] );
    }
}

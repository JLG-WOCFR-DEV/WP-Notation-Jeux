<?php

namespace JLG\Notation\Video\Providers;

class VimeoProvider implements VideoEmbedProviderInterface {

    public function get_slug(): string {
        return 'vimeo';
    }

    public function build_embed_src( string $url ): string {
        $video_id = $this->extract_video_id( $url );

        if ( $video_id === '' ) {
            return '';
        }

        return add_query_arg(
            array(
                'dnt' => 1,
            ),
            'https://player.vimeo.com/video/' . rawurlencode( $video_id )
        );
    }

    private function extract_video_id( string $url ): string {
        $parts = wp_parse_url( $url );

        if ( ! is_array( $parts ) || ! isset( $parts['path'] ) ) {
            return '';
        }

        $path     = trim( $parts['path'], '/' );
        $segments = array_values( array_filter( explode( '/', $path ) ) );

        if ( empty( $segments ) ) {
            return '';
        }

        $candidate = end( $segments );
        $candidate = is_string( $candidate ) ? trim( $candidate ) : '';

        return preg_match( '/^[0-9]{6,}$/', $candidate ) ? $candidate : '';
    }
}

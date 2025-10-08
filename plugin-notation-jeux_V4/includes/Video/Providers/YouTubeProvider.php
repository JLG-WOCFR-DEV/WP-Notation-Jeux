<?php

namespace JLG\Notation\Video\Providers;

class YouTubeProvider implements VideoEmbedProviderInterface {

    public function get_slug(): string {
        return 'youtube';
    }

    public function build_embed_src( string $url ): string {
        $video_id = $this->extract_video_id( $url );

        if ( $video_id === '' ) {
            return '';
        }

        return add_query_arg(
            array(
                'rel'            => 0,
                'modestbranding' => 1,
                'enablejsapi'    => 1,
            ),
            'https://www.youtube-nocookie.com/embed/' . rawurlencode( $video_id )
        );
    }

    private function extract_video_id( string $url ): string {
        $parts = wp_parse_url( $url );

        if ( ! is_array( $parts ) ) {
            return '';
        }

        $candidate = '';

        if ( isset( $parts['query'] ) ) {
            parse_str( $parts['query'], $query_vars );

            if ( isset( $query_vars['v'] ) && is_string( $query_vars['v'] ) ) {
                $candidate = $query_vars['v'];
            }
        }

        if ( $candidate === '' && isset( $parts['path'] ) ) {
            $path     = trim( $parts['path'], '/' );
            $segments = explode( '/', $path );

            if ( isset( $segments[0] ) ) {
                if ( $segments[0] === 'embed' && isset( $segments[1] ) ) {
                    $candidate = $segments[1];
                } elseif ( $segments[0] === 'shorts' && isset( $segments[1] ) ) {
                    $candidate = $segments[1];
                } else {
                    $candidate = $segments[ count( $segments ) - 1 ];
                }
            }
        }

        if ( ! is_string( $candidate ) ) {
            return '';
        }

        $candidate = trim( $candidate );

        return preg_match( '/^[A-Za-z0-9_-]{6,}$/', $candidate ) ? $candidate : '';
    }
}

<?php

namespace JLG\Notation\Video\Providers;

class DailymotionProvider implements VideoEmbedProviderInterface {

    public function get_slug(): string {
        return 'dailymotion';
    }

    public function build_embed_src( string $url ): string {
        $video_id = $this->extract_video_id( $url );

        if ( $video_id === '' ) {
            return '';
        }

        return add_query_arg(
            array(
                'autoplay' => '0',
            ),
            'https://www.dailymotion.com/embed/video/' . rawurlencode( $video_id )
        );
    }

    private function extract_video_id( string $url ): string {
        $parts = wp_parse_url( $url );

        if ( ! is_array( $parts ) ) {
            return '';
        }

        $path     = isset( $parts['path'] ) ? trim( $parts['path'], '/' ) : '';
        $segments = $path !== '' ? array_values( array_filter( explode( '/', $path ) ) ) : array();

        if ( empty( $segments ) ) {
            return '';
        }

        $candidate = end( $segments );

        if ( is_string( $candidate ) && strpos( $candidate, '_' ) !== false ) {
            $candidate = substr( $candidate, 0, strpos( $candidate, '_' ) );
        }

        if ( isset( $segments[0] ) && $segments[0] === 'video' && isset( $segments[1] ) ) {
            $candidate = $segments[1];
        }

        $candidate = is_string( $candidate ) ? trim( $candidate ) : '';

        if ( $candidate === '' && isset( $parts['query'] ) ) {
            parse_str( (string) $parts['query'], $query_vars );

            if ( isset( $query_vars['video'] ) && is_string( $query_vars['video'] ) ) {
                $candidate = trim( $query_vars['video'] );
            }
        }

        if ( $candidate === '' ) {
            return '';
        }

        $candidate = preg_replace( '/[^A-Za-z0-9]/', '', $candidate );

        return preg_match( '/^[A-Za-z0-9]{6,}$/', $candidate ) ? $candidate : '';
    }
}

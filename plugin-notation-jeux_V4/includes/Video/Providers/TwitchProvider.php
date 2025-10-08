<?php

namespace JLG\Notation\Video\Providers;

class TwitchProvider implements VideoEmbedProviderInterface {

    public function get_slug(): string {
        return 'twitch';
    }

    public function build_embed_src( string $url ): string {
        $parameters = $this->extract_parameters( $url );

        if ( empty( $parameters ) ) {
            return '';
        }

        $parent = $this->get_parent_domain();

        if ( $parent === '' ) {
            return '';
        }

        if ( $parameters['type'] === 'clip' ) {
            return add_query_arg(
                array(
                    'clip'     => $parameters['id'],
                    'autoplay' => 'false',
                    'parent'   => $parent,
                ),
                'https://clips.twitch.tv/embed'
            );
        }

        $query = array(
            'autoplay' => 'false',
            'muted'    => 'false',
            'parent'   => $parent,
        );

        if ( $parameters['type'] === 'video' ) {
            $query['video'] = 'v' . ltrim( (string) $parameters['id'], 'vV' );

            if ( ! empty( $parameters['collection'] ) && is_string( $parameters['collection'] ) ) {
                $query['collection'] = $parameters['collection'];
            }
        } elseif ( $parameters['type'] === 'channel' ) {
            $query['channel'] = $parameters['id'];
        } else {
            return '';
        }

        return add_query_arg( $query, 'https://player.twitch.tv/' );
    }

    private function extract_parameters( string $url ): array {
        $parts = wp_parse_url( $url );

        if ( ! is_array( $parts ) ) {
            return array();
        }

        $path     = isset( $parts['path'] ) ? trim( $parts['path'], '/' ) : '';
        $segments = $path !== '' ? array_values( array_filter( explode( '/', $path ) ) ) : array();
        $host     = isset( $parts['host'] ) ? strtolower( (string) $parts['host'] ) : '';
        $query    = array();

        if ( isset( $parts['query'] ) ) {
            parse_str( (string) $parts['query'], $query );
        }

        if ( isset( $query['video'] ) && is_string( $query['video'] ) ) {
            $candidate = preg_replace( '/^v/i', '', trim( $query['video'] ) );

            if ( is_string( $candidate ) && preg_match( '/^[0-9]{6,}$/', $candidate ) ) {
                $parameters = array(
                    'type' => 'video',
                    'id'   => $candidate,
                );

                if ( isset( $query['collection'] ) && is_string( $query['collection'] ) ) {
                    $collection = preg_replace( '/[^A-Za-z0-9_-]/', '', $query['collection'] );
                    if ( $collection !== '' ) {
                        $parameters['collection'] = $collection;
                    }
                }

                return $parameters;
            }
        }

        $video_index = array_search( 'videos', $segments, true );
        if ( false !== $video_index && isset( $segments[ $video_index + 1 ] ) ) {
            $candidate = preg_replace( '/^v/i', '', trim( (string) $segments[ $video_index + 1 ] ) );

            if ( preg_match( '/^[0-9]{6,}$/', $candidate ) ) {
                return array(
                    'type' => 'video',
                    'id'   => $candidate,
                );
            }
        }

        if ( isset( $query['clip'] ) && is_string( $query['clip'] ) ) {
            $candidate = trim( $query['clip'] );

            if ( preg_match( '/^[A-Za-z0-9_-]{4,100}$/', $candidate ) ) {
                return array(
                    'type' => 'clip',
                    'id'   => $candidate,
                );
            }
        }

        if ( $host === 'clips.twitch.tv' && isset( $segments[0] ) ) {
            $candidate = trim( (string) $segments[0] );

            if ( preg_match( '/^[A-Za-z0-9_-]{4,100}$/', $candidate ) ) {
                return array(
                    'type' => 'clip',
                    'id'   => $candidate,
                );
            }
        }

        $clip_index = array_search( 'clip', $segments, true );
        if ( false !== $clip_index && isset( $segments[ $clip_index + 1 ] ) ) {
            $candidate = trim( (string) $segments[ $clip_index + 1 ] );

            if ( preg_match( '/^[A-Za-z0-9_-]{4,100}$/', $candidate ) ) {
                return array(
                    'type' => 'clip',
                    'id'   => $candidate,
                );
            }
        }

        if ( isset( $query['channel'] ) && is_string( $query['channel'] ) ) {
            $candidate = trim( $query['channel'] );

            if ( preg_match( '/^[A-Za-z0-9_]{3,30}$/', $candidate ) ) {
                return array(
                    'type' => 'channel',
                    'id'   => strtolower( $candidate ),
                );
            }
        }

        if ( count( $segments ) === 1 && isset( $segments[0] ) ) {
            $candidate = trim( (string) $segments[0] );

            if ( preg_match( '/^[A-Za-z0-9_]{3,30}$/', $candidate ) ) {
                return array(
                    'type' => 'channel',
                    'id'   => strtolower( $candidate ),
                );
            }
        }

        return array();
    }

    private function get_parent_domain(): string {
        $home_url = home_url();
        $host     = wp_parse_url( $home_url, PHP_URL_HOST );

        if ( ! is_string( $host ) || $host === '' ) {
            $parts = wp_parse_url( $home_url );
            if ( is_array( $parts ) && isset( $parts['host'] ) ) {
                $host = $parts['host'];
            }
        }

        if ( ! is_string( $host ) ) {
            return '';
        }

        $host = trim( $host );

        return $host !== '' ? $host : '';
    }
}

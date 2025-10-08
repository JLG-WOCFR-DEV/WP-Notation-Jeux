<?php

namespace JLG\Notation\Video;

use JLG\Notation\Utils\Validator;
use JLG\Notation\Video\Providers\DailymotionProvider;
use JLG\Notation\Video\Providers\TwitchProvider;
use JLG\Notation\Video\Providers\VideoEmbedProviderInterface;
use JLG\Notation\Video\Providers\VimeoProvider;
use JLG\Notation\Video\Providers\YouTubeProvider;

class VideoEmbedFactory {

    private static $providers = null;

    public static function create_embed_data( $video_url, $provider = '' ) {
        $raw_url            = is_string( $video_url ) ? trim( $video_url ) : '';
        $sanitized_provider = Validator::sanitize_video_provider( $provider );

        if ( $raw_url === '' ) {
            return self::get_empty_video_embed_data();
        }

        $sanitized_url = esc_url_raw( $raw_url );

        if ( $sanitized_url === '' ) {
            return self::get_empty_video_embed_data( __( 'Impossible de préparer le lecteur vidéo pour cette URL.', 'notation-jlg' ) );
        }

        if ( $sanitized_provider === '' ) {
            $sanitized_provider = Validator::detect_video_provider_from_url( $sanitized_url );
        }

        if ( $sanitized_provider === '' ) {
            return self::get_empty_video_embed_data( __( 'Impossible d’identifier le fournisseur vidéo pour cette URL.', 'notation-jlg' ) );
        }

        $provider_instance = self::get_provider( $sanitized_provider );
        $provider_label    = Validator::get_video_provider_label( $sanitized_provider );

        if ( ! $provider_instance instanceof VideoEmbedProviderInterface ) {
            return self::get_empty_video_embed_data( self::get_video_fallback_message( $provider_label ) );
        }

        $iframe_src = $provider_instance->build_embed_src( $sanitized_url );

        if ( $iframe_src === '' ) {
            return self::get_empty_video_embed_data( self::get_video_fallback_message( $provider_label ) );
        }

        $title = sprintf(
            /* translators: %s: video provider name. */
            __( 'Lecteur vidéo %s', 'notation-jlg' ),
            $provider_label !== '' ? $provider_label : strtoupper( $sanitized_provider )
        );

        return array(
            'has_embed'              => true,
            'provider'               => $sanitized_provider,
            'provider_label'         => $provider_label,
            'iframe_src'             => esc_url( $iframe_src ),
            'iframe_title'           => $title,
            'iframe_allow'           => 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share',
            'iframe_allowfullscreen' => true,
            'iframe_loading'         => 'lazy',
            'iframe_referrerpolicy'  => 'strict-origin-when-cross-origin',
            'fallback_message'       => '',
            'original_url'           => esc_url( $sanitized_url ),
        );
    }

    private static function get_provider( string $provider ) {
        $providers = self::get_registered_providers();

        return $providers[ $provider ] ?? null;
    }

    private static function get_registered_providers(): array {
        if ( self::$providers === null ) {
            self::$providers = array();

            foreach ( self::bootstrap_providers() as $provider ) {
                if ( $provider instanceof VideoEmbedProviderInterface ) {
                    self::$providers[ $provider->get_slug() ] = $provider;
                }
            }
        }

        return self::$providers;
    }

    private static function bootstrap_providers(): array {
        return array(
            new YouTubeProvider(),
            new VimeoProvider(),
            new TwitchProvider(),
            new DailymotionProvider(),
        );
    }

    private static function get_video_fallback_message( $provider_label = '' ) {
        if ( $provider_label === '' ) {
            return __( 'Impossible de préparer le lecteur vidéo pour cette URL.', 'notation-jlg' );
        }

        return sprintf(
            /* translators: %s: video provider name. */
            __( 'Impossible de préparer le lecteur vidéo pour %s.', 'notation-jlg' ),
            $provider_label
        );
    }

    private static function get_empty_video_embed_data( $message = '' ) {
        return array(
            'has_embed'              => false,
            'provider'               => '',
            'provider_label'         => '',
            'iframe_src'             => '',
            'iframe_title'           => '',
            'iframe_allow'           => '',
            'iframe_allowfullscreen' => false,
            'iframe_loading'         => 'lazy',
            'iframe_referrerpolicy'  => 'strict-origin-when-cross-origin',
            'fallback_message'       => is_string( $message ) ? $message : '',
            'original_url'           => '',
        );
    }
}

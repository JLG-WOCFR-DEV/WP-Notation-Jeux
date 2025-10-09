<?php

namespace JLG\Notation;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Persist generated CSS on disk so that CDN layers can cache the stylesheet.
 */
class StyleCache {
    private const DIRECTORY = 'notation-jlg';

    /**
     * Ensure the CSS content is saved as a real stylesheet inside uploads.
     *
     * @param string $prefix   Identifier added in front of the generated file.
     * @param string $contents CSS to store.
     *
     * @return array{url:string,version:string}|null
     */
    public static function ensure_stylesheet( $prefix, $contents ) {
        if ( ! is_string( $contents ) || trim( $contents ) === '' ) {
            return null;
        }

        if ( ! function_exists( 'wp_get_upload_dir' ) ) {
            return null;
        }

        $uploads = wp_get_upload_dir();
        if ( empty( $uploads['basedir'] ) || empty( $uploads['baseurl'] ) ) {
            return null;
        }

        $directory = trailingslashit( $uploads['basedir'] ) . self::DIRECTORY;
        if ( ! wp_mkdir_p( $directory ) ) {
            return null;
        }

        $hash      = md5( $contents );
        $prefix    = sanitize_key( $prefix );
        $filename  = ( $prefix !== '' ? $prefix . '-' : '' ) . $hash . '.css';
        $filepath  = trailingslashit( $directory ) . $filename;
        $publicdir = trailingslashit( $uploads['baseurl'] ) . self::DIRECTORY;
        $fileurl   = trailingslashit( $publicdir ) . $filename;

        if ( ! file_exists( $filepath ) ) {
            if ( ! self::write_file( $filepath, $contents ) ) {
                return null;
            }
        }

        return array(
            'url'      => $fileurl,
            'version'  => substr( $hash, 0, 8 ),
            'filename' => $filename,
        );
    }

    private static function write_file( $filepath, $contents ) {
        if ( ! function_exists( 'WP_Filesystem' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        global $wp_filesystem;

        if ( ! $wp_filesystem ) {
            WP_Filesystem();
        }

        if ( ! $wp_filesystem || ! is_object( $wp_filesystem ) ) {
            if ( ! class_exists( '\WP_Filesystem_Direct' ) ) {
                require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
            }

            $wp_filesystem = new \WP_Filesystem_Direct( null );
        }

        if ( ! $wp_filesystem || ! is_object( $wp_filesystem ) ) {
            return false;
        }

        return (bool) $wp_filesystem->put_contents( $filepath, $contents, FS_CHMOD_FILE );
    }
}

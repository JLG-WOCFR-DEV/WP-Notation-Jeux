<?php

namespace JLG\Notation\CLI;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use JLG\Notation\Helpers;
use JLG\Notation\Rest\RatingsController;
use WP_Error;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ExportRatingsCommand {
    private const BATCH_SIZE        = 50;
    private const DEFAULT_DELIMITER = ';';

    public static function register() {
        if ( ! class_exists( '\\WP_CLI' ) ) {
            return;
        }

        \WP_CLI::add_command( 'jlg export:ratings', new self() );
    }

    public function __invoke( $args, $assoc_args ) {
        $params = $this->prepare_params( is_array( $assoc_args ) ? $assoc_args : array() );

        $result = $this->collect_rows( $params );

        if ( isset( $result['error'] ) && $result['error'] instanceof WP_Error ) {
            $this->notify( 'error', $result['error']->get_error_message() );
            return;
        }

        $rows = $result['items'] ?? array();

        if ( empty( $rows ) ) {
            $this->notify( 'warning', __( 'Aucun test noté ne correspond aux filtres fournis.', 'notation-jlg' ) );
            return;
        }

        $written = $this->write_csv( $rows, $params['output'], $params['delimiter'] );

        if ( $written === false ) {
            $this->notify( 'error', __( 'Échec de l’écriture du fichier CSV.', 'notation-jlg' ) );
            return;
        }

        $message = sprintf(
            _n(
                '%1$d review exportée vers %2$s.',
                '%1$d reviews exportées vers %2$s.',
                $written,
                'notation-jlg'
            ),
            $written,
            $params['output']
        );

        $this->notify( 'success', $message );
    }

    public function collect_rows( array $params ) {
        $controller  = new RatingsController();
        $page        = 1;
        $items       = array();
        $total_pages = 1;

        do {
            $request_args = array(
                'per_page' => self::BATCH_SIZE,
                'page'     => $page,
                'orderby'  => $params['orderby'],
                'order'    => $params['order'],
                'platform' => $params['platform'],
                'search'   => $params['search'],
                'status'   => ! empty( $params['statuses'] ) ? implode( ',', $params['statuses'] ) : '',
                'from'     => $params['from'],
                'to'       => $params['to'],
            );

            if ( $params['post_id'] > 0 ) {
                $request_args['post_id'] = $params['post_id'];
            }

            if ( $params['slug'] !== '' ) {
                $request_args['slug'] = $params['slug'];
            }

            $response = $controller->handle_get_ratings( $request_args );

            if ( $response instanceof WP_Error ) {
                return array( 'error' => $response );
            }

            if ( $response instanceof WP_REST_Response ) {
                $data = $response->get_data();
            } else {
                $data = $response;
            }

            $records = isset( $data['items'] ) && is_array( $data['items'] ) ? $data['items'] : array();

            if ( empty( $records ) ) {
                break;
            }

            foreach ( $records as $item ) {
                $items[] = $this->map_item_to_row( $item, $params['badge'] );
            }

            $total_pages = max( 1, (int) ( $data['pagination']['total_pages'] ?? 0 ) );
            ++$page;
        } while ( $page <= $total_pages );

        return array( 'items' => $items );
    }

    private function prepare_params( array $assoc_args ) {
        $defaults = array(
            'output'    => 'php://stdout',
            'delimiter' => self::DEFAULT_DELIMITER,
            'orderby'   => 'date',
            'order'     => 'desc',
            'platform'  => '',
            'search'    => '',
            'status'    => '',
            'from'      => '',
            'to'        => '',
            'post_id'   => 0,
            'slug'      => '',
        );

        $params = wp_parse_args( $assoc_args, $defaults );

        $params['output'] = is_string( $params['output'] ) && $params['output'] !== ''
            ? $params['output']
            : 'php://stdout';

        $params['delimiter'] = is_string( $params['delimiter'] ) && $params['delimiter'] !== ''
            ? $params['delimiter'][0]
            : self::DEFAULT_DELIMITER;

        $params['orderby'] = in_array( $params['orderby'], array( 'date', 'editorial', 'reader', 'title', 'user_votes' ), true )
            ? $params['orderby']
            : 'date';

        $order           = strtolower( (string) $params['order'] );
        $params['order'] = in_array( $order, array( 'asc', 'desc' ), true ) ? $order : 'desc';

        $params['platform'] = sanitize_title( (string) $params['platform'] );
        $params['search']   = $this->normalize_search_term( $params['search'] );
        $params['post_id']  = (int) $params['post_id'];
        $params['slug']     = sanitize_title( (string) $params['slug'] );

        $params['statuses'] = $this->normalize_statuses( $params['status'] );
        unset( $params['status'] );

        $params['from'] = $this->normalize_date( $params['from'], false );
        $params['to']   = $this->normalize_date( $params['to'], true );

        $options   = Helpers::get_plugin_options();
        $defaults  = Helpers::get_default_settings();
        $threshold = isset( $options['rating_badge_threshold'] ) && is_numeric( $options['rating_badge_threshold'] )
            ? (float) $options['rating_badge_threshold']
            : (float) ( $defaults['rating_badge_threshold'] ?? 0 );

        $params['badge'] = array(
            'enabled'   => ! empty( $options['rating_badge_enabled'] ),
            'threshold' => $threshold,
            'label'     => __( 'Sélection de la rédaction', 'notation-jlg' ),
        );

        return $params;
    }

    private function map_item_to_row( array $item, array $badge ) {
        $editorial_score     = isset( $item['editorial']['score'] ) && is_numeric( $item['editorial']['score'] )
            ? (float) $item['editorial']['score']
            : null;
        $editorial_formatted = isset( $item['editorial']['formatted'] ) ? (string) $item['editorial']['formatted'] : '';
        $user_average        = isset( $item['readers']['average'] ) && is_numeric( $item['readers']['average'] )
            ? (float) $item['readers']['average']
            : null;
        $user_formatted      = isset( $item['readers']['formatted'] ) ? (string) $item['readers']['formatted'] : '';
        $user_votes          = isset( $item['readers']['votes'] ) ? (int) $item['readers']['votes'] : 0;
        $delta_value         = isset( $item['readers']['delta']['value'] ) && is_numeric( $item['readers']['delta']['value'] )
            ? (float) $item['readers']['delta']['value']
            : null;
        $delta_formatted     = isset( $item['readers']['delta']['formatted'] ) ? (string) $item['readers']['delta']['formatted'] : '';

        $platform_labels = array();
        $platform_slugs  = array();

        if ( ! empty( $item['platforms']['items'] ) && is_array( $item['platforms']['items'] ) ) {
            foreach ( $item['platforms']['items'] as $platform ) {
                if ( isset( $platform['label'] ) ) {
                    $platform_labels[] = (string) $platform['label'];
                }

                if ( isset( $platform['slug'] ) ) {
                    $platform_slugs[] = (string) $platform['slug'];
                }
            }
        } elseif ( ! empty( $item['platforms']['labels'] ) && is_array( $item['platforms']['labels'] ) ) {
            foreach ( $item['platforms']['labels'] as $label ) {
                $label = (string) $label;
                if ( $label === '' ) {
                    continue;
                }

                $platform_labels[] = $label;
                $platform_slugs[]  = sanitize_title( $label );
            }
        }

        $platform_labels = array_values( array_filter( $platform_labels, 'strlen' ) );
        $platform_slugs  = array_values( array_filter( $platform_slugs, 'strlen' ) );

        $badge_displayed = false;
        if ( ! empty( $badge['enabled'] ) && $editorial_score !== null ) {
            $badge_displayed = $editorial_score >= (float) $badge['threshold'];
        }

        $review_status       = isset( $item['review_status']['slug'] ) ? (string) $item['review_status']['slug'] : '';
        $review_status_label = isset( $item['review_status']['label'] ) ? (string) $item['review_status']['label'] : '';

        $published_at = isset( $item['timestamps']['published'] ) ? (string) $item['timestamps']['published'] : '';
        $updated_at   = isset( $item['timestamps']['modified'] ) ? (string) $item['timestamps']['modified'] : '';

        return array(
            'post_id'             => isset( $item['id'] ) ? (int) $item['id'] : 0,
            'title'               => $this->sanitize_text( $item['title'] ?? '' ),
            'slug'                => isset( $item['slug'] ) ? (string) $item['slug'] : '',
            'permalink'           => isset( $item['permalink'] ) ? (string) $item['permalink'] : '',
            'editorial_score'     => $editorial_score,
            'editorial_formatted' => $editorial_formatted,
            'score_percentage'    => isset( $item['editorial']['percentage'] ) ? $item['editorial']['percentage'] : null,
            'user_average'        => $user_average,
            'user_formatted'      => $user_formatted,
            'user_votes'          => $user_votes,
            'delta'               => $delta_value,
            'delta_formatted'     => $delta_formatted,
            'badge_displayed'     => $badge_displayed ? 'yes' : 'no',
            'badge_threshold'     => $badge['threshold'],
            'badge_label'         => (string) $badge['label'],
            'review_status'       => $review_status,
            'review_status_label' => $review_status_label,
            'platforms'           => implode( '|', $platform_labels ),
            'platform_slugs'      => implode( '|', $platform_slugs ),
            'published_at'        => $published_at,
            'updated_at'          => $updated_at,
        );
    }

    private function write_csv( array $rows, $destination, $delimiter ) {
        if ( empty( $rows ) ) {
            return 0;
        }

        if ( strpos( $destination, 'php://' ) !== 0 ) {
            $directory = dirname( $destination );
            if ( ! file_exists( $directory ) && function_exists( 'wp_mkdir_p' ) ) {
                wp_mkdir_p( $directory );
            }
        }

        $filesystem = $this->get_filesystem();

        if ( ! $filesystem || ! is_object( $filesystem ) || ! method_exists( $filesystem, 'put_contents' ) ) {
            return false;
        }

        $headers = array_keys( $rows[0] );
        $temp    = new \SplTempFileObject();
        $temp->setCsvControl( $delimiter, '"', '\\' );
        $temp->fputcsv( $headers );

        $written = 0;
        foreach ( $rows as $row ) {
            $line = array();
            foreach ( $headers as $header ) {
                $value = $row[ $header ] ?? '';

                if ( is_bool( $value ) ) {
                    $value = $value ? '1' : '0';
                } elseif ( is_float( $value ) ) {
                    $value = number_format( $value, 1, '.', '' );
                } elseif ( $value === null ) {
                    $value = '';
                }

                $line[] = $value;
            }

            $temp->fputcsv( $line );
            ++$written;
        }

        $temp->rewind();
        $csv_content = '';
        while ( ! $temp->eof() ) {
            $line = $temp->fgets();

            if ( $line === false ) {
                break;
            }

            $csv_content .= $line;
        }

        $result = $filesystem->put_contents( $destination, $csv_content );

        if ( false === $result ) {
            return false;
        }

        return $written;
    }

    private function get_filesystem() {
        global $wp_filesystem;

        if ( $wp_filesystem instanceof \WP_Filesystem_Base ) {
            return $wp_filesystem;
        }

        if ( function_exists( 'WP_Filesystem' ) ) {
            if ( WP_Filesystem() && $wp_filesystem instanceof \WP_Filesystem_Base ) {
                return $wp_filesystem;
            }
        } else {
            if ( defined( 'ABSPATH' ) ) {
                $file = rtrim( ABSPATH, '/\\' ) . '/wp-admin/includes/file.php';

                if ( file_exists( $file ) ) {
                    require_once $file;
                }
            }

            if ( function_exists( 'WP_Filesystem' ) && WP_Filesystem() && $wp_filesystem instanceof \WP_Filesystem_Base ) {
                return $wp_filesystem;
            }
        }

        if ( class_exists( '\WP_Filesystem_Direct' ) ) {
            $wp_filesystem = new \WP_Filesystem_Direct( new \stdClass() );

            return $wp_filesystem;
        }

        return new class() {
            public function put_contents( $file, $contents ) {
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
                return file_put_contents( $file, $contents );
            }
        };
    }

    private function normalize_statuses( $value ) {
        if ( is_array( $value ) ) {
            $candidates = $value;
        } else {
            $candidates = array_map( 'trim', explode( ',', (string) $value ) );
        }

        $statuses = array();

        foreach ( $candidates as $candidate ) {
            if ( ! is_string( $candidate ) || $candidate === '' ) {
                continue;
            }

            $status = sanitize_key( $candidate );
            if ( $status === '' ) {
                continue;
            }

            $statuses[] = $status;
        }

        return array_values( array_unique( $statuses ) );
    }

    private function normalize_date( $value, $is_end = false ) {
        if ( ! is_string( $value ) ) {
            return '';
        }

        $value = trim( $value );

        if ( $value === '' ) {
            return '';
        }

        try {
            $timezone = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'UTC' );
            $date     = new DateTimeImmutable( $value, $timezone );
        } catch ( Exception $exception ) {
            return '';
        }

        $date = $is_end ? $date->setTime( 23, 59, 59 ) : $date->setTime( 0, 0, 0 );

        return $date->format( 'Y-m-d' );
    }

    private function normalize_search_term( $value ) {
        $value = sanitize_text_field( (string) $value );
        $value = trim( $value );

        if ( $value === '' ) {
            return '';
        }

        if ( function_exists( 'remove_accents' ) ) {
            $value = remove_accents( $value );
        }

        return function_exists( 'mb_strtolower' ) ? mb_strtolower( $value ) : strtolower( $value );
    }

    private function sanitize_text( $value ) {
        $value = is_string( $value ) ? $value : '';

        return wp_strip_all_tags( $value );
    }

    private function notify( $type, $message ) {
        $message = (string) $message;

        if ( class_exists( '\\WP_CLI' ) ) {
            switch ( $type ) {
                case 'success':
                    \WP_CLI::success( $message );
                    break;
                case 'warning':
                    \WP_CLI::warning( $message );
                    break;
                default:
                    \WP_CLI::error( $message, false );
                    break;
            }
        }
    }
}

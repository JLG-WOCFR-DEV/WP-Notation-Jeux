<?php

namespace JLG\Notation\Admin;

use JLG\Notation\Helpers;
use JLG\Notation\Telemetry;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Diagnostics {
    private const RAWG_PING_ACTION   = 'jlg_diagnostics_rawg_ping';
    private const RESET_METRICS_HOOK = 'jlg_reset_notation_metrics';

    public function __construct() {
        add_action( 'wp_ajax_' . self::RAWG_PING_ACTION, array( $this, 'handle_rawg_ping' ) );
        add_action( 'admin_post_' . self::RESET_METRICS_HOOK, array( $this, 'handle_metrics_reset' ) );
    }

    /**
     * Return telemetry metrics for display.
     */
    public function get_metrics() {
        return Telemetry::get_metrics_summary();
    }

    /**
     * Provide aggregated onboarding insights for the diagnostics UI.
     *
     * @param array|null $metrics Optional telemetry metrics to reuse.
     */
    public function get_onboarding_summary( $metrics = null ) {
        if ( $metrics === null ) {
            $metrics = Telemetry::get_metrics_summary();
        }

        $channel = isset( $metrics['onboarding'] ) && is_array( $metrics['onboarding'] )
            ? $metrics['onboarding']
            : array();

        $history = isset( $channel['history'] ) && is_array( $channel['history'] )
            ? $channel['history']
            : array();

        if ( empty( $history ) ) {
            return array(
                'steps'      => array(),
                'submission' => array(
                    'attempts'              => 0,
                    'success'               => 0,
                    'errors'                => 0,
                    'last_feedback_code'    => '',
                    'last_feedback_message' => '',
                    'last_attempt_at'       => 0,
                ),
            );
        }

        $steps      = array();
        $submission = array(
            'attempts'              => 0,
            'success'               => 0,
            'errors'                => 0,
            'last_feedback_code'    => '',
            'last_feedback_message' => '',
            'last_attempt_at'       => 0,
        );

        foreach ( $history as $event ) {
            $timestamp = isset( $event['timestamp'] ) ? (int) $event['timestamp'] : 0;
            $status    = isset( $event['status'] ) && $event['status'] === 'success' ? 'success' : 'error';
            $duration  = isset( $event['duration'] ) ? max( 0, (float) $event['duration'] ) : 0.0;
            $context   = isset( $event['context'] ) && is_array( $event['context'] ) ? $event['context'] : array();

            $event_type = isset( $context['event'] ) ? (string) $context['event'] : '';
            $step_id    = isset( $context['step'] ) ? (int) $context['step'] : 0;
            $feedback   = isset( $context['feedback_message'] ) ? (string) $context['feedback_message'] : '';
            $code       = isset( $context['feedback_code'] ) ? (string) $context['feedback_code'] : '';

            if ( $step_id > 0 && ! isset( $steps[ $step_id ] ) ) {
                $steps[ $step_id ] = array(
                    'step'                  => $step_id,
                    'entries'               => 0,
                    'exits'                 => 0,
                    'total_time'            => 0.0,
                    'avg_time'              => 0.0,
                    'validation_success'    => 0,
                    'validation_errors'     => 0,
                    'last_feedback_code'    => '',
                    'last_feedback_message' => '',
                    'last_event_at'         => 0,
                );
            }

            switch ( $event_type ) {
                case 'step_enter':
                    if ( $step_id > 0 ) {
                        $steps[ $step_id ]['entries']      += 1;
                        $steps[ $step_id ]['last_event_at'] = max( $steps[ $step_id ]['last_event_at'], $timestamp );
                    }
                    break;
                case 'step_leave':
                    if ( $step_id > 0 ) {
                        $steps[ $step_id ]['exits']        += 1;
                        $steps[ $step_id ]['total_time']   += $duration;
                        $steps[ $step_id ]['last_event_at'] = max( $steps[ $step_id ]['last_event_at'], $timestamp );
                    }
                    break;
                case 'validation':
                    if ( $step_id > 0 ) {
                        if ( $status === 'success' ) {
                            $steps[ $step_id ]['validation_success'] += 1;
                        } else {
                            $steps[ $step_id ]['validation_errors'] += 1;
                        }

                        if ( $code !== '' ) {
                            $steps[ $step_id ]['last_feedback_code'] = sanitize_key( $code );
                        }

                        if ( $feedback !== '' ) {
                            $steps[ $step_id ]['last_feedback_message'] = wp_strip_all_tags( $feedback );
                        }

                        $steps[ $step_id ]['last_event_at'] = max( $steps[ $step_id ]['last_event_at'], $timestamp );
                    }
                    break;
                case 'submission':
                    $submission['attempts'] += 1;
                    if ( $status === 'success' ) {
                        $submission['success'] += 1;
                    } else {
                        $submission['errors'] += 1;
                    }

                    if ( $code !== '' ) {
                        $submission['last_feedback_code'] = sanitize_key( $code );
                    }

                    if ( $feedback !== '' ) {
                        $submission['last_feedback_message'] = wp_strip_all_tags( $feedback );
                    }

                    $submission['last_attempt_at'] = max( $submission['last_attempt_at'], $timestamp );

                    if ( $step_id > 0 && isset( $steps[ $step_id ] ) ) {
                        if ( $code !== '' ) {
                            $steps[ $step_id ]['last_feedback_code'] = sanitize_key( $code );
                        }

                        if ( $feedback !== '' ) {
                            $steps[ $step_id ]['last_feedback_message'] = wp_strip_all_tags( $feedback );
                        }

                        $steps[ $step_id ]['last_event_at'] = max( $steps[ $step_id ]['last_event_at'], $timestamp );
                    }
                    break;
                default:
                    if ( $step_id > 0 && isset( $steps[ $step_id ] ) ) {
                        $steps[ $step_id ]['last_event_at'] = max( $steps[ $step_id ]['last_event_at'], $timestamp );
                    }
                    break;
            }
        }

        foreach ( $steps as $step_id => $data ) {
            $exits                           = max( 1, (int) $data['exits'] );
            $avg                             = $data['exits'] > 0 ? $data['total_time'] / $exits : 0.0;
            $steps[ $step_id ]['avg_time']   = $avg;
            $steps[ $step_id ]['total_time'] = (float) $data['total_time'];
        }

        ksort( $steps );

        return array(
            'steps'      => array_values( $steps ),
            'submission' => $submission,
        );
    }

    /**
     * Provide RAWG connection info for the diagnostics UI.
     */
    public function get_rawg_status() {
        $options = Helpers::get_plugin_options();
        $api_key = isset( $options['rawg_api_key'] ) ? trim( (string) $options['rawg_api_key'] ) : '';

        return array(
            'configured' => $api_key !== '',
            'masked_key' => $api_key !== '' ? $this->mask_key( $api_key ) : '',
        );
    }

    public function get_rawg_ping_action() {
        return self::RAWG_PING_ACTION;
    }

    public function get_reset_metrics_action() {
        return self::RESET_METRICS_HOOK;
    }

    /**
     * AJAX handler pinging the RAWG API with the stored key.
     */
    public function handle_rawg_ping() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Accès refusé.', 'notation-jlg' ) ), 403 );
        }

        check_ajax_referer( self::RAWG_PING_ACTION, 'nonce' );

        $options = Helpers::get_plugin_options();
        $api_key = isset( $options['rawg_api_key'] ) ? trim( (string) $options['rawg_api_key'] ) : '';

        if ( $api_key === '' ) {
            wp_send_json_error(
                array( 'message' => esc_html__( 'Aucune clé API RAWG n’est configurée.', 'notation-jlg' ) ),
                400
            );
        }

        $result = $this->ping_rawg_api( $api_key );

        Telemetry::record_event(
            'rawg_ping',
            array(
                'duration' => $result['duration'],
                'status'   => $result['success'] ? 'success' : 'error',
                'message'  => $result['message'],
                'context'  => array(
                    'http_code' => $result['http_code'],
                ),
            )
        );

        if ( $result['success'] ) {
            wp_send_json_success(
                array(
                    'message'   => $result['message'],
                    'http_code' => $result['http_code'],
                )
            );
        }

        wp_send_json_error(
            array(
                'message'   => $result['message'],
                'http_code' => $result['http_code'],
            ),
            500
        );
    }

    /**
     * Handle manual telemetry reset through a POST action.
     */
    public function handle_metrics_reset() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Accès refusé.', 'notation-jlg' ), 403 );
        }

        check_admin_referer( self::RESET_METRICS_HOOK );

        Telemetry::reset_metrics();

        wp_safe_redirect(
            add_query_arg(
                array(
					'page'  => 'notation_jlg_settings',
					'tab'   => 'diagnostics',
					'reset' => '1',
                ),
                admin_url( 'admin.php' )
            )
        );
        exit;
    }

    private function mask_key( $key ) {
        $length = strlen( $key );

        if ( $length <= 4 ) {
            return str_repeat( '*', $length );
        }

        return substr( $key, 0, 4 ) . str_repeat( '*', max( 0, $length - 8 ) ) . substr( $key, -4 );
    }

    private function ping_rawg_api( $api_key ) {
        $start = microtime( true );

        $response = wp_remote_get(
            add_query_arg(
                array(
                    'key'  => $api_key,
                    'page' => 1,
                ),
                'https://api.rawg.io/api/games'
            ),
            array(
                'timeout' => 5,
                'headers' => array(
                    'Accept' => 'application/json',
                ),
            )
        );

        $duration = microtime( true ) - $start;

        if ( is_wp_error( $response ) ) {
            return array(
                'success'   => false,
                'message'   => $response->get_error_message(),
                'http_code' => 0,
                'duration'  => $duration,
            );
        }

        $code = wp_remote_retrieve_response_code( $response );

        if ( $code >= 200 && $code < 300 ) {
            return array(
                'success'   => true,
                'message'   => esc_html__( 'Connexion RAWG réussie.', 'notation-jlg' ),
                'http_code' => $code,
                'duration'  => $duration,
            );
        }

        $message = wp_remote_retrieve_body( $response );
        if ( is_string( $message ) ) {
            $message = wp_strip_all_tags( $message );
        }

        if ( $message === '' ) {
            /* translators: %d is the HTTP status code returned by RAWG.io */
            $message = sprintf( esc_html__( 'La requête RAWG.io a échoué avec le code HTTP %d.', 'notation-jlg' ), $code );
        }

        return array(
            'success'   => false,
            'message'   => $message,
            'http_code' => $code,
            'duration'  => $duration,
        );
    }
}

<?php

namespace JLG\Notation;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Lightweight telemetry helper that records latency and availability metrics
 * for critical AJAX flows (Game Explorer, RAWG connectivity checks, etc.).
 */
class Telemetry {
    private const OPTION_KEY  = 'jlg_notation_metrics_v1';
    private const MAX_HISTORY = 25;

    /**
     * Record an event for the provided channel.
     *
     * @param string $channel Identifier of the flow (e.g. `game_explorer`).
     * @param array  $payload {
     *     @type float       $duration Seconds spent handling the request.
     *     @type string      $status   Either `success` or `error`.
     *     @type string|null $message  Optional human readable information.
     *     @type array       $context  Additional context saved for the last
     *                                 events (filters, counts, ...).
     * }
     *
     * @return void
     */
    public static function record_event( $channel, array $payload ) {
        $channel = sanitize_key( (string) $channel );

        if ( $channel === '' ) {
            return;
        }

        $metrics = get_option( self::OPTION_KEY, array() );

        if ( ! isset( $metrics[ $channel ] ) || ! is_array( $metrics[ $channel ] ) ) {
            $metrics[ $channel ] = self::get_default_channel_metrics();
        }

        $entry = array(
            'timestamp' => time(),
            'duration'  => isset( $payload['duration'] ) ? max( 0, (float) $payload['duration'] ) : 0.0,
            'status'    => isset( $payload['status'] ) && $payload['status'] === 'success' ? 'success' : 'error',
            'message'   => isset( $payload['message'] ) ? wp_strip_all_tags( (string) $payload['message'] ) : '',
            'context'   => isset( $payload['context'] ) && is_array( $payload['context'] ) ? $payload['context'] : array(),
        );

        $metrics[ $channel ]['history'][] = $entry;

        if ( count( $metrics[ $channel ]['history'] ) > self::MAX_HISTORY ) {
            $metrics[ $channel ]['history'] = array_slice(
                $metrics[ $channel ]['history'],
                -1 * self::MAX_HISTORY,
                self::MAX_HISTORY
            );
        }

        $metrics[ $channel ]['count']       = isset( $metrics[ $channel ]['count'] ) ? (int) $metrics[ $channel ]['count'] + 1 : 1;
        $metrics[ $channel ]['last_status'] = $entry['status'];
        $metrics[ $channel ]['last_event']  = $entry;

        if ( $entry['status'] === 'success' ) {
            $metrics[ $channel ]['success'] = isset( $metrics[ $channel ]['success'] )
                ? (int) $metrics[ $channel ]['success'] + 1
                : 1;
        } else {
            $metrics[ $channel ]['failures']           = isset( $metrics[ $channel ]['failures'] )
                ? (int) $metrics[ $channel ]['failures'] + 1
                : 1;
            $metrics[ $channel ]['last_error_message'] = $entry['message'];
        }

        $durations = array();
        foreach ( $metrics[ $channel ]['history'] as $event ) {
            if ( isset( $event['duration'] ) ) {
                $durations[] = (float) $event['duration'];
            }
        }

        if ( ! empty( $durations ) ) {
            $metrics[ $channel ]['avg_duration'] = array_sum( $durations ) / count( $durations );
            $metrics[ $channel ]['max_duration'] = max( $durations );
        }

        update_option( self::OPTION_KEY, $metrics, false );
    }

    /**
     * Retrieve sanitized metrics for display in the diagnostics tab.
     *
     * @return array
     */
    public static function get_metrics_summary() {
        $metrics = get_option( self::OPTION_KEY, array() );

        if ( ! is_array( $metrics ) ) {
            return array();
        }

        $summary = array();

        foreach ( $metrics as $channel => $channel_metrics ) {
            $channel_key = sanitize_key( (string) $channel );

            if ( $channel_key === '' || ! is_array( $channel_metrics ) ) {
                continue;
            }

            $history = isset( $channel_metrics['history'] ) && is_array( $channel_metrics['history'] )
                ? $channel_metrics['history']
                : array();

            $summary[ $channel_key ] = array(
                'count'        => (int) ( $channel_metrics['count'] ?? count( $history ) ),
                'success'      => (int) ( $channel_metrics['success'] ?? 0 ),
                'failures'     => (int) ( $channel_metrics['failures'] ?? 0 ),
                'avg_duration' => isset( $channel_metrics['avg_duration'] ) ? (float) $channel_metrics['avg_duration'] : 0.0,
                'max_duration' => isset( $channel_metrics['max_duration'] ) ? (float) $channel_metrics['max_duration'] : 0.0,
                'last_status'  => isset( $channel_metrics['last_status'] ) && $channel_metrics['last_status'] === 'success'
                    ? 'success'
                    : ( $channel_metrics['last_status'] ?? 'unknown' ),
                'last_event'   => self::sanitize_event( $channel_metrics['last_event'] ?? array() ),
                'last_error'   => isset( $channel_metrics['last_error_message'] )
                    ? wp_strip_all_tags( (string) $channel_metrics['last_error_message'] )
                    : '',
                'history'      => array_map( array( self::class, 'sanitize_event' ), $history ),
            );
        }

        return $summary;
    }

    /**
     * Reset all collected metrics.
     *
     * @return void
     */
    public static function reset_metrics() {
        delete_option( self::OPTION_KEY );
    }

    private static function get_default_channel_metrics() {
        return array(
            'count'              => 0,
            'success'            => 0,
            'failures'           => 0,
            'avg_duration'       => 0.0,
            'max_duration'       => 0.0,
            'last_status'        => 'unknown',
            'last_event'         => array(),
            'last_error'         => '',
            'last_error_message' => '',
            'history'            => array(),
        );
    }

    private static function sanitize_event( $event ) {
        if ( ! is_array( $event ) ) {
            return array();
        }

        return array(
            'timestamp' => isset( $event['timestamp'] ) ? (int) $event['timestamp'] : 0,
            'duration'  => isset( $event['duration'] ) ? (float) $event['duration'] : 0.0,
            'status'    => isset( $event['status'] ) && $event['status'] === 'success' ? 'success' : 'error',
            'message'   => isset( $event['message'] ) ? wp_strip_all_tags( (string) $event['message'] ) : '',
            'context'   => isset( $event['context'] ) && is_array( $event['context'] ) ? $event['context'] : array(),
        );
    }
}

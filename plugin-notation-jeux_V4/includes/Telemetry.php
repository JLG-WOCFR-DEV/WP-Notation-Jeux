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
    private const OPTION_KEY              = 'jlg_notation_metrics_v1';
    private const WEEKLY_REPORT_TRANSIENT = 'jlg_notation_weekly_report';
    private const MAX_HISTORY             = 25;

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
            'context'   => isset( $payload['context'] ) && is_array( $payload['context'] )
                ? self::sanitize_event_context( $payload['context'] )
                : array(),
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

        if ( function_exists( 'delete_transient' ) ) {
            delete_transient( self::WEEKLY_REPORT_TRANSIENT );
        }
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

        if ( function_exists( 'delete_transient' ) ) {
            delete_transient( self::WEEKLY_REPORT_TRANSIENT );
        }
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
            'context'   => isset( $event['context'] ) && is_array( $event['context'] )
                ? self::sanitize_event_context( $event['context'] )
                : array(),
        );
    }

    /**
     * Sanitize telemetry context payloads while preserving scalar types.
     *
     * @param array $context Raw context payload.
     *
     * @return array<string|int, mixed>
     */
    private static function sanitize_event_context( $context ) {
        if ( ! is_array( $context ) ) {
            return array();
        }

        $sanitized = array();

        foreach ( $context as $key => $value ) {
            $normalized_key = $key;

            if ( is_string( $key ) ) {
                $maybe_key = sanitize_key( $key );
                if ( $maybe_key !== '' ) {
                    $normalized_key = $maybe_key;
                }
            }

            if ( is_array( $value ) ) {
                $sanitized_value = self::sanitize_event_context( $value );
            } elseif ( is_object( $value ) ) {
                $sanitized_value = self::sanitize_event_context( (array) $value );
            } elseif ( is_bool( $value ) || is_int( $value ) || is_float( $value ) ) {
                $sanitized_value = $value;
            } elseif ( $value === null ) {
                $sanitized_value = '';
            } elseif ( is_scalar( $value ) ) {
                $string_value    = (string) $value;
                $stripped        = wp_strip_all_tags( $string_value );
                $sanitized_value = sanitize_text_field( $stripped );
            } else {
                $sanitized_value = sanitize_text_field( (string) $value );
            }

            $sanitized[ $normalized_key ] = $sanitized_value;
        }

        return $sanitized;
    }

    /**
     * Returns the aggregated weekly report for monitoring dashboards.
     *
     * @return array<string, mixed>
     */
    public static function get_weekly_report() {
        $json = self::get_weekly_report_json();

        $decoded = json_decode( $json, true );

        return is_array( $decoded ) ? $decoded : array();
    }

    /**
     * Returns the cached weekly report as a JSON payload.
     *
     * @return string
     */
    public static function get_weekly_report_json() {
        $cached = function_exists( 'get_transient' ) ? get_transient( self::WEEKLY_REPORT_TRANSIENT ) : false;

        if ( is_string( $cached ) && $cached !== '' ) {
            return $cached;
        }

        $report = self::build_weekly_report();
        $json   = wp_json_encode( $report );

        if ( is_string( $json ) && function_exists( 'set_transient' ) ) {
            set_transient( self::WEEKLY_REPORT_TRANSIENT, $json, self::get_week_in_seconds() );
        }

        return is_string( $json ) ? $json : wp_json_encode( array() );
    }

    /**
     * Builds the weekly report structure before it gets cached.
     *
     * @return array<string, mixed>
     */
    private static function build_weekly_report() {
        $summary   = self::get_metrics_summary();
        $now       = time();
        $window    = self::get_week_in_seconds();
        $threshold = $now - $window;

        $channels      = array();
        $total_events  = 0;
        $total_success = 0;
        $total_errors  = 0;

        foreach ( $summary as $channel => $data ) {
            if ( ! is_array( $data ) ) {
                continue;
            }

            $history = isset( $data['history'] ) && is_array( $data['history'] )
                ? $data['history']
                : array();

            $recent_events    = 0;
            $recent_success   = 0;
            $recent_errors    = 0;
            $recent_durations = array();
            $feedback_codes   = array();

            foreach ( $history as $event ) {
                if ( ! is_array( $event ) ) {
                    continue;
                }

                $timestamp = isset( $event['timestamp'] ) ? (int) $event['timestamp'] : 0;

                if ( $timestamp < $threshold ) {
                    continue;
                }

                ++$recent_events;

                $status = isset( $event['status'] ) && $event['status'] === 'success' ? 'success' : 'error';

                if ( $status === 'success' ) {
                    ++$recent_success;
                } else {
                    ++$recent_errors;
                }

                $duration = isset( $event['duration'] ) ? (float) $event['duration'] : 0.0;

                if ( $duration > 0 ) {
                    $recent_durations[] = $duration;
                }

                if ( isset( $event['context'] ) && is_array( $event['context'] ) && isset( $event['context']['feedback_code'] ) ) {
                    $feedback_code = sanitize_key( (string) $event['context']['feedback_code'] );

                    if ( $feedback_code !== '' ) {
                        if ( ! isset( $feedback_codes[ $feedback_code ] ) ) {
                            $feedback_codes[ $feedback_code ] = 0;
                        }

                        ++$feedback_codes[ $feedback_code ];
                    }
                }
            }

            if ( ! empty( $feedback_codes ) ) {
                arsort( $feedback_codes );
            }

            $average_duration = ! empty( $recent_durations )
                ? array_sum( $recent_durations ) / count( $recent_durations )
                : 0.0;

            $max_duration = ! empty( $recent_durations ) ? max( $recent_durations ) : 0.0;

            $success_rate = $recent_events > 0 ? $recent_success / $recent_events : 0.0;

            $channels[ $channel ] = array(
                'events'           => $recent_events,
                'success'          => $recent_success,
                'errors'           => $recent_errors,
                'success_rate'     => $success_rate,
                'average_duration' => $average_duration,
                'max_duration'     => $max_duration,
                'feedback_codes'   => $feedback_codes,
                'last_status'      => isset( $data['last_status'] ) ? (string) $data['last_status'] : 'unknown',
                'last_error'       => isset( $data['last_error'] ) ? (string) $data['last_error'] : '',
                'last_event'       => isset( $data['last_event'] ) && is_array( $data['last_event'] ) ? $data['last_event'] : array(),
            );

            $total_events  += $recent_events;
            $total_success += $recent_success;
            $total_errors  += $recent_errors;
        }

        ksort( $channels );

        return array(
            'generated_at' => gmdate( 'c', $now ),
            'range'        => array(
                'start' => gmdate( 'c', $threshold ),
                'end'   => gmdate( 'c', $now ),
            ),
            'totals'       => array(
                'events'       => $total_events,
                'success'      => $total_success,
                'errors'       => $total_errors,
                'success_rate' => $total_events > 0 ? $total_success / $total_events : 0.0,
            ),
            'channels'     => $channels,
        );
    }

    /**
     * Returns the week window in seconds with sensible fallbacks.
     *
     * @return int
     */
    private static function get_week_in_seconds() {
        if ( defined( 'WEEK_IN_SECONDS' ) ) {
            return (int) WEEK_IN_SECONDS;
        }

        if ( defined( 'DAY_IN_SECONDS' ) ) {
            return (int) DAY_IN_SECONDS * 7;
        }

        return 7 * 24 * 60 * 60;
    }
}

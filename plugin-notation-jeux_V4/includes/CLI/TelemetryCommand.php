<?php

namespace JLG\Notation\CLI;

use JLG\Notation\Telemetry;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TelemetryCommand {
    /**
     * Register the WP-CLI sub-commands for telemetry management.
     *
     * @return void
     */
    public static function register() {
        if ( ! class_exists( '\\WP_CLI' ) ) {
            return;
        }

        \WP_CLI::add_command( 'jlg telemetry', new self() );
    }

    /**
     * Display a summary of the collected telemetry metrics.
     *
     * ## EXAMPLES
     *
     *     wp jlg telemetry summary
     *
     * @param array $args Positional arguments (unused).
     * @param array $assoc_args Associative arguments (unused).
     *
     * @return void
     */
    public function summary( $args, $assoc_args ) {
        unset( $args, $assoc_args );

        $summary = Telemetry::get_metrics_summary();

        if ( empty( $summary ) ) {
            \WP_CLI::warning( __( 'Aucune métrique de télémétrie disponible.', 'notation-jlg' ) );
            return;
        }

        \WP_CLI::line( __( 'Canal | Total | Succès | Échecs | Dernier statut | Durée moy. (s) | Durée max. (s)', 'notation-jlg' ) );

        foreach ( $summary as $channel => $metrics ) {
            $line = sprintf(
                '%1$s | %2$s | %3$s | %4$s | %5$s | %6$s | %7$s',
                $channel,
                number_format_i18n( (int) ( $metrics['count'] ?? 0 ) ),
                number_format_i18n( (int) ( $metrics['success'] ?? 0 ) ),
                number_format_i18n( (int) ( $metrics['failures'] ?? 0 ) ),
                isset( $metrics['last_status'] ) ? (string) $metrics['last_status'] : 'unknown',
                number_format_i18n( (float) ( $metrics['avg_duration'] ?? 0 ), 3 ),
                number_format_i18n( (float) ( $metrics['max_duration'] ?? 0 ), 3 )
            );

            \WP_CLI::line( $line );
        }
    }

    /**
     * Reset all telemetry metrics stored by the plugin.
     *
     * ## EXAMPLES
     *
     *     wp jlg telemetry reset
     *
     * @param array $args Positional arguments (unused).
     * @param array $assoc_args Associative arguments (unused).
     *
     * @return void
     */
    public function reset( $args, $assoc_args ) {
        unset( $args, $assoc_args );

        Telemetry::reset_metrics();

        \WP_CLI::success( __( 'Les métriques de télémétrie ont été réinitialisées.', 'notation-jlg' ) );
    }

    /**
     * Alias for reset to match common CLI vocabulary.
     *
     * @param array $args Arguments passed by WP-CLI.
     * @param array $assoc_args Associative arguments.
     *
     * @return void
     */
    public function clear( $args, $assoc_args ) {
        $this->reset( $args, $assoc_args );
    }
}

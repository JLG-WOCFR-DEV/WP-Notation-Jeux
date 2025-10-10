<?php
/**
 * Template pour le shortcode jlg_score_insights.
 *
 * Variables disponibles :
 * - $atts : Attributs du shortcode.
 * - $insights : Données préparées par Helpers::get_posts_score_insights().
 * - $time_range : Identifiant de la plage temporelle.
 * - $time_range_label : Libellé de la plage temporelle.
 * - $platform_slug : Slug de plateforme filtrée.
 * - $platform_label : Libellé de la plateforme filtrée.
 * - $platform_limit : Nombre maximum de plateformes listées.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$atts             = isset( $atts ) && is_array( $atts ) ? $atts : array();
$insights         = isset( $insights ) && is_array( $insights ) ? $insights : array();
$time_range_label = isset( $time_range_label ) ? (string) $time_range_label : '';
$platform_label   = isset( $platform_label ) ? (string) $platform_label : '';
$platform_slug    = isset( $platform_slug ) ? (string) $platform_slug : '';
$platform_limit   = isset( $platform_limit ) ? intval( $platform_limit ) : 5;

$total_reviews                = isset( $insights['total'] ) ? intval( $insights['total'] ) : 0;
$mean_value                   = $insights['mean']['formatted'] ?? null;
$median_value                 = $insights['median']['formatted'] ?? null;
$distribution                 = isset( $insights['distribution'] ) && is_array( $insights['distribution'] ) ? $insights['distribution'] : array();
$rankings                     = isset( $insights['platform_rankings'] ) && is_array( $insights['platform_rankings'] ) ? $insights['platform_rankings'] : array();
$badges                       = isset( $insights['divergence_badges'] ) && is_array( $insights['divergence_badges'] ) ? $insights['divergence_badges'] : array();
$badge_threshold              = isset( $insights['badge_threshold'] ) ? (float) $insights['badge_threshold'] : 1.5;
$trend                        = isset( $trend ) && is_array( $trend ) ? $trend : array();
$trend_available              = ! empty( $trend['available'] );
$trend_label                  = isset( $trend['comparison_label'] ) ? (string) $trend['comparison_label'] : '';
$trend_mean                   = isset( $trend['mean'] ) && is_array( $trend['mean'] ) ? $trend['mean'] : array();
$trend_delta                  = isset( $trend_mean['delta_formatted'] ) ? (string) $trend_mean['delta_formatted'] : '';
$trend_direction              = isset( $trend_mean['direction'] ) ? (string) $trend_mean['direction'] : 'stable';
$trend_direction_label        = isset( $trend_mean['direction_label'] ) ? (string) $trend_mean['direction_label'] : '';
$trend_previous_mean          = isset( $trend_mean['previous_formatted'] ) ? (string) $trend_mean['previous_formatted'] : '';
$trend_previous_total         = isset( $trend['previous_total_formatted'] ) ? (string) $trend['previous_total_formatted'] : '';
$consensus                    = isset( $insights['consensus'] ) && is_array( $insights['consensus'] ) ? $insights['consensus'] : array();
$consensus_level              = isset( $consensus['level'] ) ? (string) $consensus['level'] : 'insufficient';
$consensus_label              = isset( $consensus['level_label'] ) ? (string) $consensus['level_label'] : '';
$consensus_message            = isset( $consensus['message'] ) ? (string) $consensus['message'] : '';
$consensus_deviation          = isset( $consensus['deviation_label'] ) ? (string) $consensus['deviation_label'] : '';
$consensus_confidence         = isset( $consensus['confidence'] ) && is_array( $consensus['confidence'] ) ? $consensus['confidence'] : array();
$consensus_confidence_label   = isset( $consensus_confidence['label'] ) ? (string) $consensus_confidence['label'] : '';
$consensus_confidence_message = isset( $consensus_confidence['message'] ) ? (string) $consensus_confidence['message'] : '';
$consensus_confidence_level   = isset( $consensus_confidence['level'] ) ? (string) $consensus_confidence['level'] : '';
$consensus_sample             = '';
if ( isset( $consensus['sample'] ) && is_array( $consensus['sample'] ) ) {
    $consensus_sample = isset( $consensus['sample']['label'] ) ? (string) $consensus['sample']['label'] : '';
}
$consensus_range_label = '';
if ( isset( $consensus['range'] ) && is_array( $consensus['range'] ) ) {
    $consensus_range_label = isset( $consensus['range']['label'] ) ? (string) $consensus['range']['label'] : '';
}
$consensus_chip_classes = array( 'jlg-score-insights__consensus-chip' );
if ( $consensus_level !== '' ) {
    $consensus_chip_classes[] = 'jlg-score-insights__consensus-chip--' . sanitize_html_class( $consensus_level );
}
$consensus_chip_class    = implode( ' ', array_map( 'sanitize_html_class', $consensus_chip_classes ) );
$confidence_chip_classes = array( 'jlg-score-insights__confidence-chip' );
if ( $consensus_confidence_level !== '' ) {
    $confidence_chip_classes[] = 'jlg-score-insights__confidence-chip--' . sanitize_html_class( $consensus_confidence_level );
}
$confidence_chip_class = implode( ' ', array_map( 'sanitize_html_class', $confidence_chip_classes ) );

$title = '';
if ( ! empty( $atts['title'] ) ) {
    $title = sanitize_text_field( $atts['title'] );
}

$section_id   = 'jlg-score-insights-' . uniqid();
$heading_id   = $section_id . '-title';
$summary_id   = $section_id . '-summary';
$histogram_id = $section_id . '-histogram';
$platforms_id = $section_id . '-platforms';
$badges_id    = $section_id . '-divergences';

$time_summary_parts = array();
if ( $time_range_label !== '' ) {
    $time_summary_parts[] = $time_range_label;
}
if ( $platform_label !== '' ) {
    $time_summary_parts[] = sprintf(
        /* translators: %s: platform name */
        __( 'Plateforme : %s', 'notation-jlg' ),
        $platform_label
    );
} elseif ( $platform_slug === '' ) {
    $time_summary_parts[] = __( 'Toutes les plateformes', 'notation-jlg' );
}
$time_summary_text = implode( ' · ', $time_summary_parts );
?>

<section
    class="jlg-score-insights"
    role="region"
    aria-labelledby="<?php echo esc_attr( $heading_id ); ?>"
    aria-describedby="<?php echo esc_attr( $summary_id ); ?>"
    aria-live="polite"
    data-total-reviews="<?php echo esc_attr( $total_reviews ); ?>"
>
    <header class="jlg-score-insights__header">
        <h2 id="<?php echo esc_attr( $heading_id ); ?>" class="jlg-score-insights__title">
            <?php echo esc_html( $title !== '' ? $title : __( 'Score Insights', 'notation-jlg' ) ); ?>
        </h2>
        <p id="<?php echo esc_attr( $summary_id ); ?>" class="jlg-score-insights__meta">
            <?php
            echo esc_html(
                sprintf(
                    /* translators: 1: summary (time range/platform), 2: total number of reviews. */
                    __( '%1$s — %2$s tests analysés', 'notation-jlg' ),
                    $time_summary_text !== '' ? $time_summary_text : __( 'Toutes les périodes', 'notation-jlg' ),
                    number_format_i18n( $total_reviews )
                )
            );
            ?>
        </p>
    </header>

    <?php if ( $total_reviews === 0 ) : ?>
        <p class="jlg-score-insights__empty" role="status">
            <?php esc_html_e( 'Aucune note disponible pour ces critères. Ajustez la période ou la plateforme sélectionnée.', 'notation-jlg' ); ?>
        </p>
    <?php else : ?>
        <div class="jlg-score-insights__grid">
            <div class="jlg-score-insights__stats" aria-live="polite">
                <h3 class="jlg-score-insights__subtitle">
                    <?php esc_html_e( 'Tendance centrale', 'notation-jlg' ); ?>
                </h3>
                <dl class="jlg-score-insights__figures">
                    <div class="jlg-score-insights__figure">
                        <dt><?php esc_html_e( 'Moyenne', 'notation-jlg' ); ?></dt>
                        <dd>
                            <?php echo $mean_value !== null ? esc_html( $mean_value ) : '&mdash;'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </dd>
                    </div>
                    <div class="jlg-score-insights__figure">
                        <dt><?php esc_html_e( 'Médiane', 'notation-jlg' ); ?></dt>
                        <dd>
                            <?php echo $median_value !== null ? esc_html( $median_value ) : '&mdash;'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </dd>
                    </div>
                </dl>
                <?php if ( $trend_available ) : ?>
                    <?php
                    $trend_classes = array( 'jlg-score-insights__trend-value' );
                    if ( $trend_direction !== '' ) {
                        $trend_classes[] = 'jlg-score-insights__trend-value--' . sanitize_html_class( $trend_direction );
                    }
                    $trend_value_class = implode( ' ', array_map( 'sanitize_html_class', $trend_classes ) );
                    ?>
                    <div class="jlg-score-insights__trend" role="status">
                        <p class="jlg-score-insights__trend-delta">
                            <span class="<?php echo esc_attr( $trend_value_class ); ?>" aria-hidden="true">
                                <?php echo esc_html( $trend_delta ); ?>
                            </span>
                            <span class="jlg-score-insights__trend-label">
                                <?php echo esc_html( $trend_label ); ?>
                            </span>
                        </p>
                        <p class="jlg-score-insights__trend-details">
                            <?php
                            echo esc_html(
                                sprintf(
                                    /* translators: 1: textual direction (up/down/stable), 2: previous mean, 3: number of reviews. */
                                    __( '%1$s — moyenne précédente %2$s sur %3$s tests.', 'notation-jlg' ),
                                    $trend_direction_label !== '' ? $trend_direction_label : __( 'Tendance stable', 'notation-jlg' ),
                                    $trend_previous_mean !== '' ? $trend_previous_mean : __( 'N/A', 'notation-jlg' ),
                                    $trend_previous_total !== '' ? $trend_previous_total : number_format_i18n( 0 )
                                )
                            );
                            ?>
                        </p>
                    </div>
                <?php endif; ?>

                <div class="jlg-score-insights__consensus" role="status">
                    <h3 class="jlg-score-insights__subtitle">
                        <?php esc_html_e( 'Niveau de consensus', 'notation-jlg' ); ?>
                    </h3>
                    <?php if ( $consensus_label !== '' ) : ?>
                        <p class="jlg-score-insights__consensus-chip-wrapper">
                            <span class="<?php echo esc_attr( $consensus_chip_class ); ?>" aria-hidden="true">
                                <?php echo esc_html( $consensus_label ); ?>
                            </span>
                            <span class="screen-reader-text">
                                <?php echo esc_html( $consensus_label ); ?>
                            </span>
                        </p>
                    <?php endif; ?>
                    <?php if ( $consensus_sample !== '' ) : ?>
                        <p class="jlg-score-insights__consensus-sample">
                            <?php echo esc_html( $consensus_sample ); ?>
                        </p>
                    <?php endif; ?>
                    <?php if ( $consensus_confidence_label !== '' || $consensus_confidence_message !== '' ) : ?>
                        <div class="jlg-score-insights__consensus-confidence" role="status">
                            <span class="jlg-score-insights__consensus-confidence-title">
                                <?php esc_html_e( 'Indice de confiance', 'notation-jlg' ); ?>
                            </span>
                            <?php if ( $consensus_confidence_label !== '' ) : ?>
                                <span class="<?php echo esc_attr( $confidence_chip_class ); ?>" aria-hidden="true">
                                    <?php echo esc_html( $consensus_confidence_label ); ?>
                                </span>
                                <span class="screen-reader-text">
                                    <?php echo esc_html( $consensus_confidence_label ); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ( $consensus_confidence_message !== '' ) : ?>
                                <span class="jlg-score-insights__consensus-confidence-message">
                                    <?php echo esc_html( $consensus_confidence_message ); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ( $consensus_message !== '' ) : ?>
                        <p class="jlg-score-insights__consensus-message">
                            <?php echo esc_html( $consensus_message ); ?>
                        </p>
                    <?php endif; ?>
                    <?php if ( $consensus_deviation !== '' || $consensus_range_label !== '' ) : ?>
                        <p class="jlg-score-insights__consensus-details">
                            <?php
                            $consensus_details = array();
                            if ( $consensus_deviation !== '' ) {
                                $consensus_details[] = $consensus_deviation;
                            }
                            if ( $consensus_range_label !== '' ) {
                                $consensus_details[] = $consensus_range_label;
                            }
                            echo esc_html( implode( ' · ', $consensus_details ) );
                            ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ( ! empty( $badges ) ) : ?>
                <div class="jlg-score-insights__divergences" id="<?php echo esc_attr( $badges_id ); ?>">
                    <h3 class="jlg-score-insights__subtitle">
                        <?php esc_html_e( 'Focus rédaction vs lecteurs', 'notation-jlg' ); ?>
                    </h3>
                    <p class="jlg-score-insights__divergence-intro">
                        <?php
                        echo esc_html(
                            sprintf(
                                /* translators: %s: minimal absolute delta to display a badge. */
                                __( 'Écarts supérieurs à %s point(s).', 'notation-jlg' ),
                                number_format_i18n( $badge_threshold, 1 )
                            )
                        );
                        ?>
                    </p>
                    <ul class="jlg-score-insights__badges" role="list">
                        <?php foreach ( $badges as $badge ) { ?>
                            <?php
                            $post_id             = isset( $badge['post_id'] ) ? (int) $badge['post_id'] : 0;
                            $post_title          = $post_id > 0 ? get_the_title( $post_id ) : '';
                            $permalink           = $post_id > 0 ? get_permalink( $post_id ) : '';
                            $delta_value         = isset( $badge['delta'] ) ? (float) $badge['delta'] : 0.0;
                            $delta_formatted     = isset( $badge['delta_formatted'] ) ? (string) $badge['delta_formatted'] : '';
                            $direction           = isset( $badge['direction'] ) ? sanitize_html_class( $badge['direction'] ) : '';
                            $editorial_formatted = isset( $badge['editorial_score_formatted'] ) ? (string) $badge['editorial_score_formatted'] : '';
                            $user_formatted      = isset( $badge['user_score_formatted'] ) ? (string) $badge['user_score_formatted'] : '';
                            $user_count          = isset( $badge['user_rating_count'] ) ? (int) $badge['user_rating_count'] : 0;
                            $delta_abs_label     = number_format_i18n( abs( $delta_value ), 1 );

                            if ( $delta_value > 0 ) {
                                $delta_description = sprintf(
                                    /* translators: %s: formatted score delta */
                                    __( 'Lecteurs +%s vs rédaction', 'notation-jlg' ),
                                    $delta_abs_label
                                );
                            } elseif ( $delta_value < 0 ) {
                                $delta_description = sprintf(
                                    /* translators: %s: formatted score delta */
                                    __( 'Lecteurs -%s vs rédaction', 'notation-jlg' ),
                                    $delta_abs_label
                                );
                            } else {
                                $delta_description = __( 'Lecteurs au même niveau que la rédaction', 'notation-jlg' );
                            }

                            $votes_label = sprintf(
                                /* translators: %s: number of reader votes */
                                _n( '%s vote lecteur', '%s votes lecteurs', $user_count, 'notation-jlg' ),
                                number_format_i18n( $user_count )
                            );

                            $score_summary = sprintf(
                                /* translators: 1: editorial score, 2: user score, 3: reader votes label */
                                __( 'Rédaction %1$s · Lecteurs %2$s (%3$s)', 'notation-jlg' ),
                                $editorial_formatted !== '' ? $editorial_formatted : __( 'N/A', 'notation-jlg' ),
                                $user_formatted !== '' ? $user_formatted : __( 'N/A', 'notation-jlg' ),
                                $votes_label
                            );

                            $badge_classes = array( 'jlg-score-insights__badge' );
                            if ( $direction !== '' ) {
                                $badge_classes[] = 'jlg-score-insights__badge--' . $direction;
                            }

                            $badge_class_attr = implode( ' ', array_map( 'sanitize_html_class', $badge_classes ) );
                            ?>
                            <li class="<?php echo esc_attr( $badge_class_attr ); ?>">
                                <div class="jlg-score-insights__badge-delta" aria-label="<?php echo esc_attr( $delta_description ); ?>">
                                    <?php echo esc_html( $delta_formatted ); ?>
                                </div>
                                <div class="jlg-score-insights__badge-title">
                                    <?php if ( $permalink !== '' ) : ?>
                                        <a href="<?php echo esc_url( $permalink ); ?>" class="jlg-score-insights__badge-link">
                                            <?php echo esc_html( $post_title !== '' ? $post_title : sprintf( __( 'Test #%d', 'notation-jlg' ), $post_id ) ); ?>
                                        </a>
                                    <?php else : ?>
                                        <span class="jlg-score-insights__badge-label">
                                            <?php echo esc_html( $post_title !== '' ? $post_title : sprintf( __( 'Test #%d', 'notation-jlg' ), $post_id ) ); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="jlg-score-insights__badge-summary">
                                    <?php echo esc_html( $score_summary ); ?>
                                </div>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="jlg-score-insights__histogram" id="<?php echo esc_attr( $histogram_id ); ?>">
                <h3 class="jlg-score-insights__subtitle">
                    <?php esc_html_e( 'Répartition des notes', 'notation-jlg' ); ?>
                </h3>
                <ul class="jlg-score-insights__buckets" role="list">
                    <?php foreach ( $distribution as $bucket ) { ?>
                        <?php
                        $bucket_label      = isset( $bucket['label'] ) ? (string) $bucket['label'] : '';
                        $bucket_percentage = isset( $bucket['percentage'] ) ? (float) $bucket['percentage'] : 0.0;
                        $bucket_count      = isset( $bucket['count'] ) ? intval( $bucket['count'] ) : 0;
                        $summary_text      = sprintf(
                            /* translators: 1: score range label, 2: number of reviews, 3: percentage */
                            __( '%1$s : %2$s tests, %3$s%% des notes', 'notation-jlg' ),
                            $bucket_label,
                            number_format_i18n( $bucket_count ),
                            number_format_i18n( $bucket_percentage, 1 )
                        );
                        ?>
                        <li class="jlg-score-insights__bucket">
                            <div class="jlg-score-insights__bucket-label"><?php echo esc_html( $bucket_label ); ?></div>
                            <progress
                                class="jlg-score-insights__bucket-progress"
                                max="100"
                                value="<?php echo esc_attr( $bucket_percentage ); ?>"
                                aria-label="<?php echo esc_attr( $summary_text ); ?>"
                            >
                                <?php echo esc_html( $summary_text ); ?>
                            </progress>
                            <div class="jlg-score-insights__bucket-value">
                                <?php echo esc_html( sprintf( _x( '%1$s%% · %2$s tests', 'Bucket percentage and count', 'notation-jlg' ), number_format_i18n( $bucket_percentage, 1 ), number_format_i18n( $bucket_count ) ) ); ?>
                            </div>
                        </li>
                    <?php } ?>
                </ul>
            </div>

            <div class="jlg-score-insights__platforms" id="<?php echo esc_attr( $platforms_id ); ?>">
                <h3 class="jlg-score-insights__subtitle">
                    <?php esc_html_e( 'Top plateformes', 'notation-jlg' ); ?>
                </h3>
                <?php if ( empty( $rankings ) ) : ?>
                    <p class="jlg-score-insights__empty-platforms">
                        <?php esc_html_e( 'Aucune plateforme ne se détache encore sur cette période.', 'notation-jlg' ); ?>
                    </p>
                <?php else : ?>
                    <ol class="jlg-score-insights__platform-list" aria-live="polite">
                        <?php foreach ( $rankings as $index => $platform ) { ?>
                            <?php
                            $label       = isset( $platform['label'] ) ? (string) $platform['label'] : '';
                            $average     = isset( $platform['average_formatted'] ) ? (string) $platform['average_formatted'] : '';
                            $count       = isset( $platform['count'] ) ? intval( $platform['count'] ) : 0;
                            $position    = $index + 1;
                            $platform_sr = sprintf(
                                /* translators: 1: ranking position, 2: platform name, 3: average score, 4: number of reviews */
                                __( '%1$s. %2$s — moyenne %3$s sur %4$s tests', 'notation-jlg' ),
                                number_format_i18n( $position ),
                                $label,
                                $average !== '' ? $average : __( 'N/A', 'notation-jlg' ),
                                number_format_i18n( $count )
                            );
                            ?>
                            <li class="jlg-score-insights__platform-item">
                                <span class="jlg-score-insights__platform-rank" aria-hidden="true">
                                    <?php echo esc_html( number_format_i18n( $position ) ); ?>
                                </span>
                                <div class="jlg-score-insights__platform-content">
                                    <span class="jlg-score-insights__platform-label"><?php echo esc_html( $label ); ?></span>
                                    <span class="jlg-score-insights__platform-score">
                                        <?php echo esc_html( $average !== '' ? $average : __( 'N/A', 'notation-jlg' ) ); ?>
                                    </span>
                                    <span class="jlg-score-insights__platform-count">
                                        <?php echo esc_html( sprintf( _n( '%s test', '%s tests', $count, 'notation-jlg' ), number_format_i18n( $count ) ) ); ?>
                                    </span>
                                </div>
                                <span class="screen-reader-text"><?php echo esc_html( $platform_sr ); ?></span>
                            </li>
                        <?php } ?>
                    </ol>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</section>

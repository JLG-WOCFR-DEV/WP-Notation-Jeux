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

$segments                 = isset( $insights['segments'] ) && is_array( $insights['segments'] ) ? $insights['segments'] : array();
$segments_available        = ! empty( $segments['available'] );
$segment_editorial         = isset( $segments['editorial'] ) && is_array( $segments['editorial'] ) ? $segments['editorial'] : array();
$segment_readers           = isset( $segments['readers'] ) && is_array( $segments['readers'] ) ? $segments['readers'] : array();
$segment_delta             = isset( $segments['delta'] ) && is_array( $segments['delta'] ) ? $segments['delta'] : array();
$segment_delta_value       = isset( $segment_delta['formatted'] ) ? (string) $segment_delta['formatted'] : '';
$segment_delta_direction   = isset( $segment_delta['direction'] ) ? (string) $segment_delta['direction'] : 'stable';
$segment_delta_label       = isset( $segment_delta['label'] ) ? (string) $segment_delta['label'] : '';
$segment_editorial_mean    = isset( $segment_editorial['average_formatted'] ) ? (string) $segment_editorial['average_formatted'] : '';
$segment_editorial_median  = isset( $segment_editorial['median_formatted'] ) ? (string) $segment_editorial['median_formatted'] : '';
$segment_editorial_count   = isset( $segment_editorial['count'] ) ? (int) $segment_editorial['count'] : 0;
$segment_readers_mean      = isset( $segment_readers['average_formatted'] ) ? (string) $segment_readers['average_formatted'] : '';
$segment_readers_votes     = isset( $segment_readers['votes'] ) ? (int) $segment_readers['votes'] : 0;
$segment_readers_sample    = isset( $segment_readers['sample'] ) ? (int) $segment_readers['sample'] : 0;

$timeline_data       = isset( $insights['timeline'] ) && is_array( $insights['timeline'] ) ? $insights['timeline'] : array();
$timeline_available  = ! empty( $timeline_data['available'] ) && ! empty( $timeline_data['points'] );
$timeline_points     = isset( $timeline_data['points'] ) && is_array( $timeline_data['points'] ) ? $timeline_data['points'] : array();
$sparkline_data      = isset( $timeline_data['sparkline'] ) && is_array( $timeline_data['sparkline'] ) ? $timeline_data['sparkline'] : array();
$sparkline_view_box  = isset( $sparkline_data['view_box'] ) ? (string) $sparkline_data['view_box'] : '';
$sparkline_width     = isset( $sparkline_data['width'] ) ? (int) $sparkline_data['width'] : 0;
$sparkline_height    = isset( $sparkline_data['height'] ) ? (int) $sparkline_data['height'] : 0;
$sparkline_editorial = isset( $sparkline_data['editorial_path'] ) ? (string) $sparkline_data['editorial_path'] : '';
$sparkline_reader    = isset( $sparkline_data['reader_path'] ) ? (string) $sparkline_data['reader_path'] : '';
$sparkline_aria      = isset( $sparkline_data['aria_label'] ) ? (string) $sparkline_data['aria_label'] : '';
$sparkline_label_a   = isset( $sparkline_data['editorial_label'] ) ? (string) $sparkline_data['editorial_label'] : '';
$sparkline_label_b   = isset( $sparkline_data['reader_label'] ) ? (string) $sparkline_data['reader_label'] : '';
$sparkline_y_min     = isset( $sparkline_data['y_min_label'] ) ? (string) $sparkline_data['y_min_label'] : '';
$sparkline_y_max     = isset( $sparkline_data['y_max_label'] ) ? (string) $sparkline_data['y_max_label'] : '';

$sentiments           = isset( $insights['sentiments'] ) && is_array( $insights['sentiments'] ) ? $insights['sentiments'] : array();
$sentiments_available = ! empty( $sentiments['available'] );
$sentiments_pros      = isset( $sentiments['pros'] ) && is_array( $sentiments['pros'] ) ? $sentiments['pros'] : array();
$sentiments_cons      = isset( $sentiments['cons'] ) && is_array( $sentiments['cons'] ) ? $sentiments['cons'] : array();

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
$segments_id  = $section_id . '-segments';
$timeline_id  = $section_id . '-timeline';
$sentiments_id = $section_id . '-sentiments';

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
            <?php if ( $segments_available ) : ?>
                <section class="jlg-score-insights__segments" id="<?php echo esc_attr( $segments_id ); ?>" aria-labelledby="<?php echo esc_attr( $segments_id ); ?>-title">
                    <h3 id="<?php echo esc_attr( $segments_id ); ?>-title" class="jlg-score-insights__subtitle">
                        <?php esc_html_e( 'Rédaction vs Lecteurs', 'notation-jlg' ); ?>
                    </h3>
                    <div class="jlg-score-insights__segments-grid" role="group" aria-label="<?php esc_attr_e( 'Comparaison des notes rédaction et lecteurs', 'notation-jlg' ); ?>">
                        <article class="jlg-score-insights__segment-card jlg-score-insights__segment-card--editorial">
                            <h4 class="jlg-score-insights__segment-title"><?php esc_html_e( 'Rédaction', 'notation-jlg' ); ?></h4>
                            <p class="jlg-score-insights__segment-value">
                                <?php echo $segment_editorial_mean !== '' ? esc_html( $segment_editorial_mean ) : '&mdash;'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </p>
                            <p class="jlg-score-insights__segment-meta">
                                <?php
                                echo esc_html(
                                    sprintf(
                                        /* translators: 1: formatted median, 2: number of editorial reviews. */
                                        __( 'Médiane %1$s · %2$s tests', 'notation-jlg' ),
                                        $segment_editorial_median !== '' ? $segment_editorial_median : __( 'N/A', 'notation-jlg' ),
                                        number_format_i18n( $segment_editorial_count )
                                    )
                                );
                                ?>
                            </p>
                        </article>
                        <article class="jlg-score-insights__segment-card jlg-score-insights__segment-card--readers">
                            <h4 class="jlg-score-insights__segment-title"><?php esc_html_e( 'Lecteurs', 'notation-jlg' ); ?></h4>
                            <p class="jlg-score-insights__segment-value">
                                <?php echo $segment_readers_mean !== '' ? esc_html( $segment_readers_mean ) : '&mdash;'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </p>
                            <p class="jlg-score-insights__segment-meta">
                                <?php
                                echo esc_html(
                                    sprintf(
                                        /* translators: 1: number of reader votes, 2: number of posts with reader votes. */
                                        _n( '%1$s vote lecteur · %2$s test', '%1$s votes lecteurs · %2$s tests', $segment_readers_sample, 'notation-jlg' ),
                                        number_format_i18n( $segment_readers_votes ),
                                        number_format_i18n( $segment_readers_sample )
                                    )
                                );
                                ?>
                            </p>
                        </article>
                        <article class="jlg-score-insights__segment-card jlg-score-insights__segment-card--delta">
                            <h4 class="jlg-score-insights__segment-title">
                                <?php echo esc_html( $segment_delta_label !== '' ? $segment_delta_label : __( 'Écart lecteurs vs rédaction', 'notation-jlg' ) ); ?>
                            </h4>
                            <?php
                            $delta_classes = array( 'jlg-score-insights__segment-delta-value' );
                            if ( $segment_delta_direction !== '' ) {
                                $delta_classes[] = 'jlg-score-insights__segment-delta-value--' . sanitize_html_class( $segment_delta_direction );
                            }
                            $delta_class_attr = implode( ' ', array_map( 'sanitize_html_class', $delta_classes ) );
                            ?>
                            <p class="<?php echo esc_attr( $delta_class_attr ); ?>">
                                <?php echo $segment_delta_value !== '' ? esc_html( $segment_delta_value ) : '&mdash;'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </p>
                            <p class="jlg-score-insights__segment-meta">
                                <?php
                                echo esc_html(
                                    sprintf(
                                        /* translators: 1: editorial average, 2: reader average. */
                                        __( 'Rédaction %1$s · Lecteurs %2$s', 'notation-jlg' ),
                                        $segment_editorial_mean !== '' ? $segment_editorial_mean : __( 'N/A', 'notation-jlg' ),
                                        $segment_readers_mean !== '' ? $segment_readers_mean : __( 'N/A', 'notation-jlg' )
                                    )
                                );
                                ?>
                            </p>
                        </article>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ( $timeline_available ) : ?>
                <section class="jlg-score-insights__timeline" id="<?php echo esc_attr( $timeline_id ); ?>" aria-labelledby="<?php echo esc_attr( $timeline_id ); ?>-title">
                    <h3 id="<?php echo esc_attr( $timeline_id ); ?>-title" class="jlg-score-insights__subtitle"><?php esc_html_e( 'Évolution des scores', 'notation-jlg' ); ?></h3>
                    <?php if ( ( $sparkline_editorial !== '' || $sparkline_reader !== '' ) && $sparkline_view_box !== '' ) : ?>
                        <figure class="jlg-score-insights__sparkline" role="img" aria-label="<?php echo esc_attr( $sparkline_aria ); ?>">
                            <svg
                                class="jlg-score-insights__sparkline-chart"
                                viewBox="<?php echo esc_attr( $sparkline_view_box ); ?>"
                                <?php echo $sparkline_width > 0 ? 'width="' . esc_attr( $sparkline_width ) . '"' : ''; ?>
                                <?php echo $sparkline_height > 0 ? 'height="' . esc_attr( $sparkline_height ) . '"' : ''; ?>
                            >
                                <?php if ( $sparkline_editorial !== '' ) : ?>
                                    <path class="jlg-score-insights__sparkline-path jlg-score-insights__sparkline-path--editorial" d="<?php echo esc_attr( $sparkline_editorial ); ?>" />
                                <?php endif; ?>
                                <?php if ( $sparkline_reader !== '' ) : ?>
                                    <path class="jlg-score-insights__sparkline-path jlg-score-insights__sparkline-path--readers" d="<?php echo esc_attr( $sparkline_reader ); ?>" />
                                <?php endif; ?>
                            </svg>
                            <figcaption class="jlg-score-insights__sparkline-caption">
                                <?php if ( $sparkline_label_a !== '' ) : ?>
                                    <span class="jlg-score-insights__sparkline-legend jlg-score-insights__sparkline-legend--editorial"><?php echo esc_html( $sparkline_label_a ); ?></span>
                                <?php endif; ?>
                                <?php if ( $sparkline_label_b !== '' ) : ?>
                                    <span class="jlg-score-insights__sparkline-legend jlg-score-insights__sparkline-legend--readers"><?php echo esc_html( $sparkline_label_b ); ?></span>
                                <?php endif; ?>
                                <?php if ( $sparkline_y_min !== '' || $sparkline_y_max !== '' ) : ?>
                                    <span class="jlg-score-insights__sparkline-range">
                                        <?php
                                        echo esc_html(
                                            sprintf(
                                                /* translators: 1: minimal score label, 2: maximal score label. */
                                                __( 'Bornes : %1$s – %2$s', 'notation-jlg' ),
                                                $sparkline_y_min !== '' ? $sparkline_y_min : __( '0', 'notation-jlg' ),
                                                $sparkline_y_max !== '' ? $sparkline_y_max : __( '10', 'notation-jlg' )
                                            )
                                        );
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </figcaption>
                        </figure>
                    <?php endif; ?>
                    <ol class="jlg-score-insights__timeline-list">
                        <?php foreach ( $timeline_points as $point ) { ?>
                            <?php
                            $point_date      = isset( $point['date_label'] ) ? (string) $point['date_label'] : '';
                            $point_editorial = isset( $point['editorial_formatted'] ) ? (string) $point['editorial_formatted'] : '';
                            $point_reader    = isset( $point['reader_formatted'] ) ? (string) $point['reader_formatted'] : '';
                            $point_votes     = isset( $point['reader_votes'] ) ? (int) $point['reader_votes'] : 0;
                            $point_title     = isset( $point['title'] ) ? (string) $point['title'] : '';
                            $point_permalink = isset( $point['permalink'] ) ? (string) $point['permalink'] : '';
                            $point_post_id   = isset( $point['post_id'] ) ? (int) $point['post_id'] : 0;
                            $reader_summary  = $point_reader !== ''
                                ? sprintf(
                                    /* translators: 1: formatted reader score, 2: reader votes count. */
                                    _n( 'Lecteurs %1$s (%2$s vote)', 'Lecteurs %1$s (%2$s votes)', $point_votes, 'notation-jlg' ),
                                    $point_reader,
                                    number_format_i18n( $point_votes )
                                )
                                : __( 'Lecteurs N/A', 'notation-jlg' );
                            ?>
                            <li class="jlg-score-insights__timeline-item">
                                <div class="jlg-score-insights__timeline-date"><?php echo esc_html( $point_date ); ?></div>
                                <div class="jlg-score-insights__timeline-scores">
                                    <span class="jlg-score-insights__timeline-score jlg-score-insights__timeline-score--editorial">
                                        <?php echo esc_html( sprintf( __( 'Rédaction %s', 'notation-jlg' ), $point_editorial !== '' ? $point_editorial : __( 'N/A', 'notation-jlg' ) ) ); ?>
                                    </span>
                                    <span class="jlg-score-insights__timeline-score jlg-score-insights__timeline-score--readers">
                                        <?php echo esc_html( $reader_summary ); ?>
                                    </span>
                                </div>
                                <?php if ( $point_title !== '' ) : ?>
                                    <?php if ( $point_permalink !== '' ) : ?>
                                        <a class="jlg-score-insights__timeline-link" href="<?php echo esc_url( $point_permalink ); ?>"<?php echo $point_post_id > 0 ? ' data-post-id="' . esc_attr( $point_post_id ) . '"' : ''; ?>>
                                            <?php echo esc_html( $point_title ); ?>
                                        </a>
                                    <?php else : ?>
                                        <span class="jlg-score-insights__timeline-link"<?php echo $point_post_id > 0 ? ' data-post-id="' . esc_attr( $point_post_id ) . '"' : ''; ?>>
                                            <?php echo esc_html( $point_title ); ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </li>
                        <?php } ?>
                    </ol>
                </section>
            <?php endif; ?>

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

            <?php if ( $sentiments_available && ( ! empty( $sentiments_pros ) || ! empty( $sentiments_cons ) ) ) : ?>
                <section class="jlg-score-insights__sentiments" id="<?php echo esc_attr( $sentiments_id ); ?>" aria-labelledby="<?php echo esc_attr( $sentiments_id ); ?>-title">
                    <h3 id="<?php echo esc_attr( $sentiments_id ); ?>-title" class="jlg-score-insights__subtitle"><?php esc_html_e( 'Points les plus cités', 'notation-jlg' ); ?></h3>
                    <div class="jlg-score-insights__sentiments-grid">
                        <?php if ( ! empty( $sentiments_pros ) ) : ?>
                            <div class="jlg-score-insights__sentiment-column jlg-score-insights__sentiment-column--pros">
                                <h4 class="jlg-score-insights__sentiment-title"><?php esc_html_e( 'Points forts', 'notation-jlg' ); ?></h4>
                                <ol class="jlg-score-insights__sentiment-list">
                                    <?php foreach ( $sentiments_pros as $index => $entry ) { ?>
                                        <?php
                                        $label = isset( $entry['label'] ) ? (string) $entry['label'] : '';
                                        $count = isset( $entry['count'] ) ? (int) $entry['count'] : 0;
                                        ?>
                                        <li class="jlg-score-insights__sentiment-item">
                                            <span class="jlg-score-insights__sentiment-rank" aria-hidden="true"><?php echo esc_html( number_format_i18n( $index + 1 ) ); ?></span>
                                            <span class="jlg-score-insights__sentiment-label"><?php echo esc_html( $label ); ?></span>
                                            <span class="jlg-score-insights__sentiment-count"><?php echo esc_html( sprintf( _n( '%s mention', '%s mentions', $count, 'notation-jlg' ), number_format_i18n( $count ) ) ); ?></span>
                                        </li>
                                    <?php } ?>
                                </ol>
                            </div>
                        <?php endif; ?>
                        <?php if ( ! empty( $sentiments_cons ) ) : ?>
                            <div class="jlg-score-insights__sentiment-column jlg-score-insights__sentiment-column--cons">
                                <h4 class="jlg-score-insights__sentiment-title"><?php esc_html_e( 'Points faibles', 'notation-jlg' ); ?></h4>
                                <ol class="jlg-score-insights__sentiment-list">
                                    <?php foreach ( $sentiments_cons as $index => $entry ) { ?>
                                        <?php
                                        $label = isset( $entry['label'] ) ? (string) $entry['label'] : '';
                                        $count = isset( $entry['count'] ) ? (int) $entry['count'] : 0;
                                        ?>
                                        <li class="jlg-score-insights__sentiment-item">
                                            <span class="jlg-score-insights__sentiment-rank" aria-hidden="true"><?php echo esc_html( number_format_i18n( $index + 1 ) ); ?></span>
                                            <span class="jlg-score-insights__sentiment-label"><?php echo esc_html( $label ); ?></span>
                                            <span class="jlg-score-insights__sentiment-count"><?php echo esc_html( sprintf( _n( '%s mention', '%s mentions', $count, 'notation-jlg' ), number_format_i18n( $count ) ) ); ?></span>
                                        </li>
                                    <?php } ?>
                                </ol>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>

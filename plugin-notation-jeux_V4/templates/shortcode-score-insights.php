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

$total_reviews = isset( $insights['total'] ) ? intval( $insights['total'] ) : 0;
$mean_value    = $insights['mean']['formatted'] ?? null;
$median_value  = $insights['median']['formatted'] ?? null;
$distribution  = isset( $insights['distribution'] ) && is_array( $insights['distribution'] ) ? $insights['distribution'] : array();
$rankings      = isset( $insights['platform_rankings'] ) && is_array( $insights['platform_rankings'] ) ? $insights['platform_rankings'] : array();

$title = '';
if ( ! empty( $atts['title'] ) ) {
    $title = sanitize_text_field( $atts['title'] );
}

$section_id   = 'jlg-score-insights-' . uniqid();
$heading_id   = $section_id . '-title';
$summary_id   = $section_id . '-summary';
$histogram_id = $section_id . '-histogram';
$platforms_id = $section_id . '-platforms';

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
            </div>

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

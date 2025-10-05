<?php
/**
 * Placeholder template displayed when no rating data is available.
 *
 * @package notation-jlg
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$options = isset( $options ) && is_array( $options ) ? $options : \JLG\Notation\Helpers::get_plugin_options();

$raw_score_max = null;
if ( isset( $score_max ) && is_numeric( $score_max ) ) {
    $raw_score_max = (float) $score_max;
}

$resolved_score_max = $raw_score_max !== null && $raw_score_max > 0
    ? $raw_score_max
    : (float) \JLG\Notation\Helpers::get_score_max( $options );

if ( $resolved_score_max <= 0 ) {
    $resolved_score_max = 10.0;
}

$placeholder_average = isset( $average_score ) && is_numeric( $average_score )
    ? (float) $average_score
    : round( $resolved_score_max / 2, 1 );

$average_percentage_value = isset( $average_score_percentage ) && is_numeric( $average_score_percentage )
    ? max( 0, min( 100, (float) $average_score_percentage ) )
    : max( 0, min( 100, ( $placeholder_average / $resolved_score_max ) * 100 ) );

$resolved_layout = isset( $score_layout ) && in_array( (string) $score_layout, array( 'text', 'circle' ), true )
    ? (string) $score_layout
    : 'text';

$resolved_display_mode = isset( $display_mode ) && in_array( (string) $display_mode, array( 'absolute', 'percent' ), true )
    ? (string) $display_mode
    : 'absolute';

$percentages_map = array();
if ( isset( $category_percentages ) && is_array( $category_percentages ) ) {
    foreach ( $category_percentages as $key => $percentage ) {
        if ( ! is_string( $key ) ) {
            continue;
        }

        if ( is_numeric( $percentage ) ) {
            $percentages_map[ (string) $key ] = max( 0, min( 100, (float) $percentage ) );
        }
    }
}

$categories_for_display = array();

if ( isset( $category_scores ) && is_array( $category_scores ) ) {
    foreach ( $category_scores as $category ) {
        if ( ! is_array( $category ) ) {
            continue;
        }

        $label = isset( $category['label'] ) ? (string) $category['label'] : '';
        if ( $label === '' ) {
            continue;
        }

        $category_id = isset( $category['id'] ) ? (string) $category['id'] : '';
        $score_value = isset( $category['score'] ) && is_numeric( $category['score'] )
            ? (float) $category['score']
            : $placeholder_average;

        $percentage_value = isset( $percentages_map[ $category_id ] )
            ? $percentages_map[ $category_id ]
            : $average_percentage_value;

        $categories_for_display[] = array(
            'label'    => $label,
            'score'    => $score_value,
            'percent'  => $percentage_value,
        );
    }
}

if ( empty( $categories_for_display ) ) {
    foreach ( \JLG\Notation\Helpers::get_rating_category_definitions() as $definition ) {
        $label = isset( $definition['label'] ) ? (string) $definition['label'] : '';

        if ( $label === '' ) {
            continue;
        }

        $categories_for_display[] = array(
            'label'   => $label,
            'score'   => $placeholder_average,
            'percent' => $average_percentage_value,
        );
    }
}

$score_max_label      = esc_html( number_format_i18n( $resolved_score_max ) );
$average_score_label  = esc_html( number_format_i18n( $placeholder_average, 1 ) );
$percentage_label     = esc_html( number_format_i18n( $average_percentage_value, 1 ) );
$user_rating_value    = isset( $user_rating_average ) && is_numeric( $user_rating_average )
    ? (float) $user_rating_average
    : $placeholder_average;
$user_rating_delta     = isset( $user_rating_delta ) && is_numeric( $user_rating_delta )
    ? (float) $user_rating_delta
    : 0.0;
$user_rating_has_delta = abs( $user_rating_delta ) > 0.01;

if ( $user_rating_delta > 0 ) {
    $user_rating_delta_formatted = '+' . number_format_i18n( $user_rating_delta, 1 );
} elseif ( $user_rating_delta < 0 ) {
    $user_rating_delta_formatted = number_format_i18n( $user_rating_delta, 1 );
} else {
    $user_rating_delta_formatted = number_format_i18n( 0, 1 );
}

if ( $resolved_display_mode === 'percent' && $percentage_label !== '' ) {
    $global_visible_value = sprintf(
        /* translators: %s: Global score percentage. */
        esc_html_x( '%s %%', 'global percentage score', 'notation-jlg' ),
        $percentage_label
    );
} else {
    $global_visible_value = $average_score_label;
}

$placeholder_notice = esc_html__( 'Prévisualisation des notes avec des valeurs fictives. Renseignez la metabox « Notation JLG » pour publier vos notes.', 'notation-jlg' );
$css_variables_string = isset( $css_variables ) && is_string( $css_variables ) ? trim( $css_variables ) : '';
$extra_classes_string = isset( $extra_classes ) && is_string( $extra_classes ) ? trim( $extra_classes ) : '';
$extra_classes_list   = $extra_classes_string !== '' ? preg_split( '/\s+/', $extra_classes_string ) : array();
$wrapper_classes      = array_merge( array( 'review-box-jlg', 'is-placeholder' ), is_array( $extra_classes_list ) ? $extra_classes_list : array() );

if ( ! empty( $animations_enabled ) ) {
    $wrapper_classes[] = 'jlg-animate';
}

$wrapper_classes = array_unique( array_filter( array_map( 'trim', $wrapper_classes ) ) );
$wrapper_class_attribute = ! empty( $wrapper_classes ) ? implode( ' ', $wrapper_classes ) : 'review-box-jlg is-placeholder';
$style_attribute         = $css_variables_string !== '' ? ' style="' . esc_attr( $css_variables_string ) . '"' : '';
?>
<div class="jlg-rating-block-placeholder jlg-rating-block-empty">
    <p class="jlg-rating-placeholder-notice"><?php echo $placeholder_notice; ?></p>
    <div class="<?php echo esc_attr( $wrapper_class_attribute ); ?>"<?php echo $style_attribute; ?> aria-hidden="true">
        <div class="global-score-wrapper">
            <?php if ( $resolved_layout === 'circle' ) : ?>
                <div class="score-circle">
                    <div class="score-value"><?php echo esc_html( $global_visible_value ); ?></div>
                    <div class="score-label"><?php esc_html_e( 'Note Globale', 'notation-jlg' ); ?></div>
                </div>
            <?php else : ?>
                <div class="global-score-text">
                    <div class="score-value"><?php echo esc_html( $global_visible_value ); ?></div>
                    <div class="score-label"><?php esc_html_e( 'Note Globale', 'notation-jlg' ); ?></div>
                </div>
            <?php endif; ?>

            <div class="rating-badge rating-badge--placeholder" role="presentation">
                <span class="rating-badge__label"><?php esc_html_e( 'Sélection de la rédaction', 'notation-jlg' ); ?></span>
            </div>

            <div class="user-rating-summary user-rating-summary--placeholder">
                <span class="user-rating-summary__label"><?php esc_html_e( 'Note des lecteurs', 'notation-jlg' ); ?></span>
                <span class="user-rating-summary__value">
                    <?php
                    printf(
                        /* translators: 1: reader score. 2: maximum possible score. */
                        esc_html__( '%1$s / %2$s', 'notation-jlg' ),
                        esc_html( number_format_i18n( $user_rating_value, 1 ) ),
                        $score_max_label
                    );
                    ?>
                </span>
                <?php if ( $user_rating_has_delta ) : ?>
                    <span class="user-rating-summary__delta">
                        <?php
                        printf(
                            /* translators: %s: difference between reader and editorial scores. */
                            esc_html__( 'Δ vs rédaction : %s', 'notation-jlg' ),
                            esc_html( $user_rating_delta_formatted )
                        );
                        ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <hr>

        <div class="rating-breakdown">
            <?php foreach ( $categories_for_display as $category ) : ?>
                <?php
                $category_label = isset( $category['label'] ) ? (string) $category['label'] : '';

                if ( $category_label === '' ) {
                    continue;
                }

                $category_score_value = isset( $category['score'] ) && is_numeric( $category['score'] )
                    ? (float) $category['score']
                    : $placeholder_average;
                $category_percent_value = isset( $category['percent'] ) && is_numeric( $category['percent'] )
                    ? max( 0, min( 100, (float) $category['percent'] ) )
                    : $average_percentage_value;
                $percent_attr = number_format( $category_percent_value, 2, '.', '' );
                ?>
                <div class="rating-item">
                    <div class="rating-label">
                        <span><?php echo esc_html( $category_label ); ?></span>
                        <span>
                            <?php
                            printf(
                                /* translators: 1: category score. 2: maximum score. */
                                esc_html__( '%1$s / %2$s', 'notation-jlg' ),
                                esc_html( number_format_i18n( $category_score_value, 1 ) ),
                                $score_max_label
                            );
                            ?>
                        </span>
                    </div>
                    <div class="rating-bar-container">
                        <div class="rating-bar" style="--rating-percent:<?php echo esc_attr( $percent_attr ); ?>%;"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

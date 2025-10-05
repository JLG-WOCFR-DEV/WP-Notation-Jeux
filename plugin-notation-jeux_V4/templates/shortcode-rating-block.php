<?php
/**
 * Template pour le bloc de notation principal
 *
 * Variables disponibles :
 * - $options : Options du plugin
 * - $average_score : Note moyenne calculée
 * - $scores : Tableau des scores par catégorie
 * - $categories : Libellés des catégories
 * - $should_show_rating_badge : Booléen indiquant si le badge doit être affiché
 * - $user_rating_average : Moyenne des lecteurs si disponible
 * - $user_rating_delta : Écart avec la note rédactionnelle si disponible
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$options = isset( $options ) && is_array( $options )
    ? $options
    : \JLG\Notation\Helpers::get_plugin_options();

$resolved_score_layout = in_array( (string) $score_layout, array( 'text', 'circle' ), true )
    ? $score_layout
    : ( isset( $options['score_layout'] ) && $options['score_layout'] === 'circle' ? 'circle' : 'text' );

$animations_on = (bool) $animations_enabled;

$resolved_score_max = is_numeric( $score_max )
    ? (float) $score_max
    : \JLG\Notation\Helpers::get_score_max( $options );

$score_max_label = number_format_i18n( $resolved_score_max );

$display_mode = isset( $display_mode ) && in_array( (string) $display_mode, array( 'absolute', 'percent' ), true )
    ? $display_mode
    : 'absolute';

$average_percentage_value = isset( $average_score_percentage ) && is_numeric( $average_score_percentage )
    ? max( 0, min( 100, (float) $average_score_percentage ) )
    : null;

$category_percentages = isset( $category_percentages ) && is_array( $category_percentages )
    ? $category_percentages
    : array();

$css_variables_string = is_string( $css_variables ) ? $css_variables : '';
$should_display_badge = ! empty( $should_show_rating_badge );
$user_rating_average_value = isset( $user_rating_average ) && is_numeric( $user_rating_average )
    ? (float) $user_rating_average
    : null;
$user_rating_delta_value = isset( $user_rating_delta ) && is_numeric( $user_rating_delta )
    ? (float) $user_rating_delta
    : null;
$extra_classes_string = isset( $extra_classes ) && is_string( $extra_classes ) ? trim( $extra_classes ) : '';
$extra_classes_list   = $extra_classes_string !== '' ? preg_split( '/\s+/', $extra_classes_string ) : array();
$wrapper_classes      = array_merge( array( 'review-box-jlg' ), is_array( $extra_classes_list ) ? $extra_classes_list : array() );

if ( $animations_on ) {
    $wrapper_classes[] = 'jlg-animate';
}

$wrapper_classes = array_unique( array_filter( array_map( 'trim', $wrapper_classes ) ) );

if ( $css_variables_string === '' ) {
    $style_variables = array(
        '--jlg-score-gradient-1' => isset( $options['score_gradient_1'] ) ? $options['score_gradient_1'] : '',
        '--jlg-score-gradient-2' => isset( $options['score_gradient_2'] ) ? $options['score_gradient_2'] : '',
        '--jlg-color-high'       => isset( $options['color_high'] ) ? $options['color_high'] : '',
        '--jlg-color-mid'        => isset( $options['color_mid'] ) ? $options['color_mid'] : '',
        '--jlg-color-low'        => isset( $options['color_low'] ) ? $options['color_low'] : '',
    );

    $style_rules = array();
    foreach ( $style_variables as $var => $value ) {
        if ( is_string( $value ) && $value !== '' ) {
            $style_rules[] = $var . ':' . $value;
        }
    }

    $css_variables_string = ! empty( $style_rules ) ? implode( ';', $style_rules ) : '';
}

$style_attribute = $css_variables_string !== '' ? ' style="' . esc_attr( $css_variables_string ) . '"' : '';
$wrapper_class_attribute = ! empty( $wrapper_classes ) ? implode( ' ', $wrapper_classes ) : 'review-box-jlg';

$score_max_label_safe       = esc_html( $score_max_label );
$average_score_display      = esc_html( number_format_i18n( $average_score, 1 ) );
$average_percentage_display = $average_percentage_value !== null
    ? esc_html( number_format_i18n( $average_percentage_value, 1 ) )
    : '';

if ( $display_mode === 'percent' && $average_percentage_display !== '' ) {
    $visible_value = sprintf(
        /* translators: %s: Global score percentage. */
        esc_html_x( '%s %%', 'global percentage score', 'notation-jlg' ),
        $average_percentage_display
    );
    $aria_label = sprintf(
        /* translators: 1: Global score percentage. 2: Global score value. 3: Maximum score. */
        __( 'Note globale : %1$s %%, soit %2$s sur %3$s', 'notation-jlg' ),
        $average_percentage_display,
        $average_score_display,
        $score_max_label_safe
    );
    $sr_text = sprintf(
        /* translators: 1: Global score value. 2: Maximum score. */
        __( 'Équivalent à %1$s sur %2$s', 'notation-jlg' ),
        $average_score_display,
        $score_max_label_safe
    );
    $global_score_markup = '<div class="score-value" aria-label="' . esc_attr( $aria_label ) . '">' . $visible_value
        . '<span class="screen-reader-text"> ' . esc_html( $sr_text ) . '</span></div>';
} else {
    $visible_value = $average_score_display;
    $aria_label    = sprintf(
        /* translators: 1: Global score value. 2: Maximum score. */
        __( 'Note globale : %1$s sur %2$s', 'notation-jlg' ),
        $average_score_display,
        $score_max_label_safe
    );
    $global_score_markup = '<div class="score-value" aria-label="' . esc_attr( $aria_label ) . '">' . $visible_value;

    if ( $average_percentage_display !== '' ) {
        $sr_text = sprintf(
            /* translators: %s: Global score percentage. */
            __( 'Correspond à %s %%', 'notation-jlg' ),
            $average_percentage_display
        );
        $global_score_markup .= '<span class="screen-reader-text"> ' . esc_html( $sr_text ) . '</span>';
    }

    $global_score_markup .= '</div>';
}
?>

<div class="<?php echo esc_attr( $wrapper_class_attribute ); ?>"<?php echo $style_attribute; ?>>
    <div class="global-score-wrapper">
        <?php if ( $resolved_score_layout === 'circle' ) : ?>
            <div class="score-circle">
                <?php echo $global_score_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <div class="score-label"><?php esc_html_e( 'Note Globale', 'notation-jlg' ); ?></div>
            </div>
        <?php else : ?>
            <div class="global-score-text">
                <?php echo $global_score_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <div class="score-label"><?php esc_html_e( 'Note Globale', 'notation-jlg' ); ?></div>
            </div>
        <?php endif; ?>
        <?php if ( $should_display_badge ) : ?>
            <div class="rating-badge" role="status">
                <span class="rating-badge__label"><?php esc_html_e( 'Sélection de la rédaction', 'notation-jlg' ); ?></span>
            </div>
        <?php endif; ?>

        <?php if ( $user_rating_average_value !== null ) : ?>
            <div class="user-rating-summary">
                <span class="user-rating-summary__label"><?php esc_html_e( 'Note des lecteurs', 'notation-jlg' ); ?></span>
                <span class="user-rating-summary__value">
                    <?php
                    printf(
                        /* translators: 1: reader score. 2: maximum possible score. */
                        esc_html__( '%1$s / %2$s', 'notation-jlg' ),
                        esc_html( number_format_i18n( $user_rating_average_value, 1 ) ),
                        $score_max_label_safe
                    );
                    ?>
                </span>
                <?php if ( $user_rating_delta_value !== null ) : ?>
                    <?php
                    $delta_value = number_format_i18n( $user_rating_delta_value, 1 );
                    if ( $user_rating_delta_value > 0 ) {
                        $delta_value = '+' . $delta_value;
                    }
                    ?>
                    <span class="user-rating-summary__delta">
                        <?php
                        printf(
                            /* translators: %s: difference between reader and editorial scores. */
                            esc_html__( 'Δ vs rédaction : %s', 'notation-jlg' ),
                            esc_html( $delta_value )
                        );
                        ?>
                    </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <hr>
    
    <div class="rating-breakdown">
        <?php foreach ( $category_scores as $category ) : ?>
            <?php
            $score_value = isset( $category['score'] ) ? (float) $category['score'] : null;

            if ( $score_value === null ) {
                continue;
            }

            $label     = isset( $category['label'] ) ? $category['label'] : '';
            $weight    = isset( $category['weight'] )
                ? \JLG\Notation\Helpers::normalize_category_weight( $category['weight'], 1.0 )
                : 1.0;
            $show_weight = abs( $weight - 1.0 ) > 0.001;
            $bar_color = \JLG\Notation\Helpers::calculate_color_from_note( $score_value, $options );
            $category_id = isset( $category['id'] ) ? (string) $category['id'] : '';
            $percentage_value = ( $category_id !== '' && isset( $category_percentages[ $category_id ] ) )
                ? max( 0, min( 100, (float) $category_percentages[ $category_id ] ) )
                : null;
            $percentage_display = is_numeric( $percentage_value )
                ? esc_html( number_format_i18n( $percentage_value, 1 ) )
                : '';
            $label_text = wp_strip_all_tags( (string) $label );
            ?>
            <div class="rating-item">
                <div class="rating-label">
                    <span>
                        <?php echo esc_html( $label ); ?>
                        <?php if ( $show_weight ) : ?>
                            <span class="rating-weight">
                                <?php
                                printf(
                                    /* translators: %s: weight multiplier for a rating category. */
                                    esc_html_x( '×%s', 'category weight multiplier', 'notation-jlg' ),
                                    esc_html( number_format_i18n( $weight, 1 ) )
                                );
                                ?>
                            </span>
                        <?php endif; ?>
                    </span>
                    <span>
                        <?php
                        $formatted_score_value = esc_html( number_format_i18n( $score_value, 1 ) );
                        $score_max_for_output = esc_html( $score_max_label );

                        if ( $display_mode === 'percent' && $percentage_display !== '' ) {
                            $visible_percentage = sprintf(
                                /* translators: %s: Rating percentage for a category. */
                                esc_html_x( '%s %%', 'category percentage score', 'notation-jlg' ),
                                $percentage_display
                            );
                            $aria_label = sprintf(
                                /* translators: 1: Rating category label. 2: Rating percentage. 3: Rating value. 4: Maximum possible rating. */
                                __( '%1$s : %2$s %%, soit %3$s sur %4$s', 'notation-jlg' ),
                                $label_text,
                                $percentage_display,
                                $formatted_score_value,
                                $score_max_for_output
                            );
                            $sr_equivalent = sprintf(
                                /* translators: 1: Rating value. 2: Maximum possible rating. */
                                __( 'Équivalent à %1$s sur %2$s', 'notation-jlg' ),
                                $formatted_score_value,
                                $score_max_for_output
                            );

                            echo '<span aria-label="' . esc_attr( $aria_label ) . '">' . esc_html( $visible_percentage )
                                . '<span class="screen-reader-text"> ' . esc_html( $sr_equivalent ) . '</span></span>';
                        } else {
                            $visible_absolute = sprintf(
                                /* translators: 1: Rating value. 2: Maximum possible rating. */
                                __( '%1$s / %2$s', 'notation-jlg' ),
                                $formatted_score_value,
                                $score_max_for_output
                            );
                            $aria_label = sprintf(
                                /* translators: 1: Rating category label. 2: Rating value. 3: Maximum possible rating. */
                                __( '%1$s : %2$s sur %3$s', 'notation-jlg' ),
                                $label_text,
                                $formatted_score_value,
                                $score_max_for_output
                            );

                            echo '<span aria-label="' . esc_attr( $aria_label ) . '">' . esc_html( $visible_absolute );

                            if ( $percentage_display !== '' ) {
                                $sr_percentage = sprintf(
                                    /* translators: %s: Rating percentage for a category. */
                                    __( 'Correspond à %s %%', 'notation-jlg' ),
                                    $percentage_display
                                );

                                echo '<span class="screen-reader-text"> ' . esc_html( $sr_percentage ) . '</span>';
                            }

                            echo '</span>';
                        }
                        ?>
                    </span>
                </div>
                <div class="rating-bar-container">
                    <?php
                    $percentage = $resolved_score_max > 0
                        ? max( 0, min( 100, ( $score_value / $resolved_score_max ) * 100 ) )
                        : 0;
                    ?>
                    <div class="rating-bar" style="--rating-percent:<?php echo esc_attr( round( $percentage, 2 ) ); ?>%; --bar-color:<?php echo esc_attr( $bar_color ); ?>;"></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
/**
 * Template pour le bloc de notation principal
 *
 * Variables disponibles :
 * - $options : Options du plugin
 * - $average_score : Note moyenne calculée
 * - $scores : Tableau des scores par catégorie
 * - $categories : Libellés des catégories
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$options = isset( $options ) && is_array( $options )
    ? $options
    : \JLG\Notation\Helpers::get_plugin_options();

$score_max       = \JLG\Notation\Helpers::get_score_max( $options );
$score_max_label = number_format_i18n( $score_max );
$score_layout    = isset( $options['score_layout'] ) && $options['score_layout'] === 'circle' ? 'circle' : 'text';
$animations_on   = ! empty( $options['enable_animations'] );

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

$style_attribute = ! empty( $style_rules ) ? ' style="' . esc_attr( implode( ';', $style_rules ) ) . '"' : '';
?>

<div class="review-box-jlg<?php echo $animations_on ? ' jlg-animate' : ''; ?>"<?php echo $style_attribute; ?>>
    <div class="global-score-wrapper">
        <?php if ( $score_layout === 'circle' ) : ?>
            <div class="score-circle">
                <div class="score-value"><?php echo esc_html( number_format_i18n( $average_score, 1 ) ); ?></div>
                <div class="score-label"><?php esc_html_e( 'Note Globale', 'notation-jlg' ); ?></div>
            </div>
        <?php else : ?>
            <div class="global-score-text">
                <div class="score-value"><?php echo esc_html( number_format_i18n( $average_score, 1 ) ); ?></div>
                <div class="score-label"><?php esc_html_e( 'Note Globale', 'notation-jlg' ); ?></div>
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
            $bar_color = \JLG\Notation\Helpers::calculate_color_from_note( $score_value, $options );
            ?>
            <div class="rating-item">
                <div class="rating-label">
                    <span><?php echo esc_html( $label ); ?></span>
                    <span>
                        <?php
                        $formatted_score_value = esc_html( number_format_i18n( $score_value, 1 ) );
                        printf(
                            /* translators: 1: Rating value for a specific category. 2: Maximum possible rating. */
                            esc_html__( '%1$s / %2$s', 'notation-jlg' ),
                            $formatted_score_value,
                            esc_html( $score_max_label )
                        );
                        ?>
                    </span>
                </div>
                <div class="rating-bar-container">
                    <?php
                    $percentage = $score_max > 0
                        ? max( 0, min( 100, ( $score_value / $score_max ) * 100 ) )
                        : 0;
                    ?>
                    <div class="rating-bar" style="--rating-percent:<?php echo esc_attr( round( $percentage, 2 ) ); ?>%; --bar-color:<?php echo esc_attr( $bar_color ); ?>;"></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

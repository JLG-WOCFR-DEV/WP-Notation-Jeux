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

$options          = \JLG\Notation\Helpers::get_plugin_options();
$score_max        = \JLG\Notation\Helpers::get_score_max( $options );
$score_max_label  = number_format_i18n( $score_max );
?>

<div class="review-box-jlg<?php echo $options['enable_animations'] ? ' jlg-animate' : ''; ?>">
    <div class="global-score-wrapper">
        <?php if ( $options['score_layout'] === 'circle' ) : ?>
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
            $weight    = isset( $category['weight'] )
                ? \JLG\Notation\Helpers::normalize_category_weight( $category['weight'], 1.0 )
                : 1.0;
            $show_weight = abs( $weight - 1.0 ) > 0.001;
            $bar_color = \JLG\Notation\Helpers::calculate_color_from_note( $score_value, $options );
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

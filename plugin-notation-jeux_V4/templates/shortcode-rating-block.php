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

$options = JLG_Helpers::get_plugin_options();
?>

<?php
$classes = array( 'review-box-jlg' );

if ( ! empty( $options['enable_animations'] ) ) {
        $classes[] = 'jlg-animate';
}

$class_attribute = implode( ' ', array_map( 'sanitize_html_class', $classes ) );
?>
<div class="<?php echo esc_attr( $class_attribute ); ?>">
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
        <?php
        foreach ( $scores as $key => $score_value ) :
            $bar_color = JLG_Helpers::calculate_color_from_note( $score_value, $options );
			?>
            <div class="rating-item">
                <div class="rating-label">
                    <span><?php echo esc_html( $categories[ $key ] ); ?></span>
                    <span>
                        <?php
                        $formatted_score_value = esc_html( number_format_i18n( $score_value, 1 ) );
                        printf(
                            /* translators: 1: Rating value for a specific category. 2: Maximum possible rating. */
                            esc_html__( '%1$s / %2$s', 'notation-jlg' ),
                            $formatted_score_value,
                            10
                        );
                        ?>
                    </span>
                </div>
                <div class="rating-bar-container">
                    <div class="rating-bar" style="--rating-percent:<?php echo esc_attr( $score_value * 10 ); ?>%; --bar-color: <?php echo esc_attr( $bar_color ); ?>;"></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$score_max       = isset( $score_max ) ? max( 1, (float) $score_max ) : \JLG\Notation\Helpers::get_score_max();
$score_max_label = number_format_i18n( $score_max );
$style_attribute = '';
if ( ! empty( $css_variables ) ) {
    $style_attribute = ' style="' . esc_attr( $css_variables ) . '"';
}

$has_tagline      = ( $atts['afficher_tagline'] === 'oui' && ( ! empty( $tagline_fr ) || ! empty( $tagline_en ) ) );
$has_dual_tagline = ( ! empty( $tagline_fr ) && ! empty( $tagline_en ) );
$show_rating      = ( $atts['afficher_notation'] === 'oui' && $average_score !== null );
$show_points      = ( $atts['afficher_points'] === 'oui' && ( ! empty( $pros_list ) || ! empty( $cons_list ) ) );
$category_scores  = isset( $category_scores ) && is_array( $category_scores ) ? $category_scores : array();
$has_cta          = ( ! empty( $cta_label ) && ! empty( $cta_url ) );
$cta_role_attr    = ! empty( $cta_role ) ? $cta_role : 'button';
$cta_rel_attr     = isset( $cta_rel ) ? trim( (string) $cta_rel ) : '';
$data_attributes  = sprintf(
    ' data-animations-enabled="%s" data-has-multiple-taglines="%s"',
    esc_attr( $animations_enabled ? 'true' : 'false' ),
    esc_attr( $has_dual_tagline ? 'true' : 'false' )
);
?>

<div class="<?php echo esc_attr( $block_classes ); ?>"<?php echo $style_attribute; ?><?php echo $data_attributes; ?>>
    <?php if ( $has_tagline ) : ?>
    <div class="jlg-aio-header">
        <?php if ( $has_dual_tagline ) : ?>
        <div class="jlg-aio-flags">
            <button
                type="button"
                class="jlg-aio-flag active"
                data-lang="fr"
                aria-pressed="true"
                aria-label="<?php echo esc_attr__( 'Français', 'notation-jlg' ); ?>"
            >
                <img src="<?php echo esc_url( JLG_NOTATION_PLUGIN_URL . 'assets/flags/fr.svg' ); ?>" alt="">
                <span class="jlg-aio-flag-label"><?php echo esc_html__( 'Français', 'notation-jlg' ); ?></span>
            </button>
            <button
                type="button"
                class="jlg-aio-flag"
                data-lang="en"
                aria-pressed="false"
                aria-label="<?php echo esc_attr__( 'English', 'notation-jlg' ); ?>"
            >
                <img src="<?php echo esc_url( JLG_NOTATION_PLUGIN_URL . 'assets/flags/gb.svg' ); ?>" alt="">
                <span class="jlg-aio-flag-label"><?php echo esc_html__( 'English', 'notation-jlg' ); ?></span>
            </button>
        </div>
        <?php endif; ?>

        <?php if ( ! empty( $tagline_fr ) ) : ?>
        <div class="jlg-aio-tagline" data-lang="fr" aria-hidden="false">
            <?php echo wp_kses_post( $tagline_fr ); ?>
        </div>
        <?php endif; ?>

        <?php if ( ! empty( $tagline_en ) ) : ?>
        <div class="jlg-aio-tagline" data-lang="en"<?php echo $has_dual_tagline ? ' hidden aria-hidden="true"' : ' aria-hidden="false"'; ?>>
            <?php echo wp_kses_post( $tagline_en ); ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ( $show_rating ) : ?>
    <div class="jlg-aio-rating">
        <div class="jlg-aio-main-score">
            <?php if ( $score_layout === 'circle' ) : ?>
            <div class="jlg-aio-score-circle">
                <div class="jlg-aio-score-value"><?php echo esc_html( number_format_i18n( $average_score, 1 ) ); ?></div>
                <div class="jlg-aio-score-label"><?php echo esc_html__( 'Note Globale', 'notation-jlg' ); ?></div>
            </div>
            <?php else : ?>
            <div class="jlg-aio-score-value"><?php echo esc_html( number_format_i18n( $average_score, 1 ) ); ?></div>
            <div class="jlg-aio-score-label"><?php echo esc_html__( 'Note Globale', 'notation-jlg' ); ?></div>
            <?php endif; ?>
        </div>

        <div class="jlg-aio-scores-grid">
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
            <div class="jlg-aio-score-item">
                <div class="jlg-aio-score-header">
                    <span class="jlg-aio-score-label">
                        <?php echo esc_html( $label ); ?>
                        <?php if ( $show_weight ) : ?>
                            <span class="jlg-aio-score-weight">
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
                    <span class="jlg-aio-score-number">
                        <?php echo esc_html( number_format_i18n( $score_value, 1 ) ); ?>
                        <?php
                        printf(
                            /* translators: %s: Maximum possible rating value. */
                            esc_html_x( '/ %s', 'score input suffix', 'notation-jlg' ),
                            esc_html( $score_max_label )
                        );
                        ?>
                    </span>
                </div>
                <div class="jlg-aio-score-bar-bg">
                    <?php
                    $percentage = $score_max > 0
                        ? max( 0, min( 100, ( $score_value / $score_max ) * 100 ) )
                        : 0;
                    $percentage_attr = esc_attr( round( $percentage, 2 ) );
                    ?>
                    <div class="jlg-aio-score-bar"
                        style="--bar-color: <?php echo esc_attr( $bar_color ); ?>; --bar-width: <?php echo $percentage_attr; ?>%; width: <?php echo $percentage_attr; ?>%;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ( $show_points ) : ?>
    <div class="jlg-aio-points">
        <?php if ( ! empty( $pros_list ) ) : ?>
        <div class="jlg-aio-points-col pros">
            <div class="jlg-aio-points-title">
                <span class="jlg-aio-points-icon pros">+</span>
                <span><?php echo esc_html( $atts['titre_points_forts'] ); ?></span>
            </div>
            <ul class="jlg-aio-points-list">
                <?php foreach ( $pros_list as $pro ) : ?>
                <li><?php echo esc_html( $pro ); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if ( ! empty( $cons_list ) ) : ?>
        <div class="jlg-aio-points-col cons">
            <div class="jlg-aio-points-title">
                <span class="jlg-aio-points-icon cons">−</span>
                <span><?php echo esc_html( $atts['titre_points_faibles'] ); ?></span>
            </div>
            <ul class="jlg-aio-points-list">
                <?php foreach ( $cons_list as $con ) : ?>
                <li><?php echo esc_html( $con ); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ( $has_cta ) : ?>
    <div class="jlg-aio-cta">
        <a class="jlg-aio-cta-button" href="<?php echo esc_url( $cta_url ); ?>" role="<?php echo esc_attr( $cta_role_attr ); ?>"<?php echo $cta_rel_attr !== '' ? ' rel="' . esc_attr( $cta_rel_attr ) . '"' : ''; ?>>
            <span class="jlg-aio-cta-label"><?php echo esc_html( $cta_label ); ?></span>
        </a>
    </div>
    <?php endif; ?>
</div>

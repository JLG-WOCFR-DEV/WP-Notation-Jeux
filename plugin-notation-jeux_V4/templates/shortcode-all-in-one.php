<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$score_max       = isset( $score_max ) ? max( 1, (float) $score_max ) : \JLG\Notation\Helpers::get_score_max();
$score_max_label = number_format_i18n( $score_max );
$display_mode    = isset( $display_mode ) && in_array( (string) $display_mode, array( 'absolute', 'percent' ), true )
    ? $display_mode
    : ( isset( $atts['display_mode'] ) && in_array( (string) $atts['display_mode'], array( 'absolute', 'percent' ), true )
        ? $atts['display_mode']
        : 'absolute'
    );

$average_percentage_value = isset( $average_score_percentage ) && is_numeric( $average_score_percentage )
    ? max( 0, min( 100, (float) $average_score_percentage ) )
    : null;

$category_percentages = isset( $category_percentages ) && is_array( $category_percentages )
    ? $category_percentages
    : array();
$style_attribute      = '';
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

$score_max_label_safe       = esc_html( $score_max_label );
$average_score_display      = $average_score !== null ? esc_html( number_format_i18n( $average_score, 1 ) ) : '';
$average_percentage_display = $average_percentage_value !== null
    ? esc_html( number_format_i18n( $average_percentage_value, 1 ) )
    : '';

$review_video_data   = isset( $review_video ) && is_array( $review_video ) ? $review_video : array();
$video_has_embed     = ! empty( $review_video_data['has_embed'] );
$video_fallback      = isset( $review_video_data['fallback_message'] ) ? (string) $review_video_data['fallback_message'] : '';
$video_should_render = ( isset( $atts['afficher_video'] ) && $atts['afficher_video'] === 'oui' ) && ( $video_has_embed || $video_fallback !== '' );
$video_iframe_src    = $video_has_embed && ! empty( $review_video_data['iframe_src'] ) ? $review_video_data['iframe_src'] : '';
$video_iframe_title  = $video_has_embed && ! empty( $review_video_data['iframe_title'] ) ? $review_video_data['iframe_title'] : '';
$video_iframe_allow  = $video_has_embed && ! empty( $review_video_data['iframe_allow'] ) ? $review_video_data['iframe_allow'] : '';
$video_iframe_ref    = $video_has_embed && ! empty( $review_video_data['iframe_referrerpolicy'] ) ? $review_video_data['iframe_referrerpolicy'] : '';
$video_provider_name = ! empty( $review_video_data['provider_label'] ) ? $review_video_data['provider_label'] : '';
$video_region_label  = $video_provider_name !== ''
    ? sprintf( /* translators: %s is the video provider label. */ __( 'Vidéo de test hébergée par %s', 'notation-jlg' ), $video_provider_name )
    : __( 'Vidéo de test', 'notation-jlg' );
$video_region_id     = function_exists( 'wp_unique_id' ) ? wp_unique_id( 'jlg-aio-video-label-' ) : 'jlg-aio-video-label-' . uniqid();

if ( $average_score_display !== '' ) {
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

        $global_score_markup = '<div class="jlg-aio-score-value" aria-label="' . esc_attr( $aria_label ) . '">' . $visible_value
            . '<span class="screen-reader-text"> ' . esc_html( $sr_text ) . '</span></div>';
    } else {
        $visible_value = $average_score_display;
        $aria_label    = sprintf(
            /* translators: 1: Global score value. 2: Maximum score. */
            __( 'Note globale : %1$s sur %2$s', 'notation-jlg' ),
            $average_score_display,
            $score_max_label_safe
        );

        $global_score_markup = '<div class="jlg-aio-score-value" aria-label="' . esc_attr( $aria_label ) . '">' . $visible_value;

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
} else {
    $global_score_markup = '';
}
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

    <?php if ( $video_should_render ) : ?>
    <section class="jlg-aio-video" role="group" aria-labelledby="<?php echo esc_attr( $video_region_id ); ?>">
        <span id="<?php echo esc_attr( $video_region_id ); ?>" class="screen-reader-text"><?php echo esc_html( $video_region_label ); ?></span>
        <?php if ( $video_has_embed && $video_iframe_src !== '' ) : ?>
        <div class="jlg-aio-video-wrapper">
            <iframe
                src="<?php echo esc_url( $video_iframe_src ); ?>"
                title="<?php echo esc_attr( $video_iframe_title ); ?>"
                <?php if ( $video_iframe_allow !== '' ) : ?>
                allow="<?php echo esc_attr( $video_iframe_allow ); ?>"
                <?php endif; ?>
                loading="lazy"
                <?php if ( $video_iframe_ref !== '' ) : ?>
                referrerpolicy="<?php echo esc_attr( $video_iframe_ref ); ?>"
                <?php endif; ?>
                allowfullscreen
            ></iframe>
        </div>
        <?php elseif ( $video_fallback !== '' ) : ?>
        <p class="jlg-aio-video-fallback"><?php echo esc_html( $video_fallback ); ?></p>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if ( $show_rating ) : ?>
    <div class="jlg-aio-rating">
        <div class="jlg-aio-main-score">
            <?php if ( $score_layout === 'circle' ) : ?>
            <div class="jlg-aio-score-circle">
                <?php echo $global_score_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <div class="jlg-aio-score-label"><?php echo esc_html__( 'Note Globale', 'notation-jlg' ); ?></div>
            </div>
            <?php else : ?>
				<?php echo $global_score_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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

                $label              = isset( $category['label'] ) ? $category['label'] : '';
                $weight             = isset( $category['weight'] )
                    ? \JLG\Notation\Helpers::normalize_category_weight( $category['weight'], 1.0 )
                    : 1.0;
                $show_weight        = abs( $weight - 1.0 ) > 0.001;
                $bar_color          = \JLG\Notation\Helpers::calculate_color_from_note( $score_value, $options );
                $category_id        = isset( $category['id'] ) ? (string) $category['id'] : '';
                $percentage_value   = ( $category_id !== '' && isset( $category_percentages[ $category_id ] ) )
                    ? max( 0, min( 100, (float) $category_percentages[ $category_id ] ) )
                    : null;
                $percentage_display = is_numeric( $percentage_value )
                    ? esc_html( number_format_i18n( $percentage_value, 1 ) )
                    : '';
                $label_text         = wp_strip_all_tags( (string) $label );
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
                    <?php
                    $formatted_score_value = esc_html( number_format_i18n( $score_value, 1 ) );
                    $score_max_for_output  = esc_html( $score_max_label );

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

                        echo '<span class="jlg-aio-score-number" aria-label="' . esc_attr( $aria_label ) . '">' . esc_html( $visible_percentage )
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

                        echo '<span class="jlg-aio-score-number" aria-label="' . esc_attr( $aria_label ) . '">' . esc_html( $visible_absolute );

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
                </div>
                <div class="jlg-aio-score-bar-bg">
                    <?php
                    if ( $percentage_value !== null ) {
                        $percentage = $percentage_value;
                    } else {
                        $percentage = $score_max > 0
                            ? max( 0, min( 100, ( $score_value / $score_max ) * 100 ) )
                            : 0;
                    }
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

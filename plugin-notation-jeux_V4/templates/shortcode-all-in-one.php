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

$has_tagline           = ( $atts['afficher_tagline'] === 'oui' && ( ! empty( $tagline_fr ) || ! empty( $tagline_en ) ) );
$has_dual_tagline      = ( ! empty( $tagline_fr ) && ! empty( $tagline_en ) );
$show_rating           = ( $atts['afficher_notation'] === 'oui' && $average_score !== null );
$show_points           = ( $atts['afficher_points'] === 'oui' && ( ! empty( $pros_list ) || ! empty( $cons_list ) ) );
$category_scores       = isset( $category_scores ) && is_array( $category_scores ) ? $category_scores : array();
$has_cta               = ( ! empty( $cta_label ) && ! empty( $cta_url ) );
$cta_role_attr         = ! empty( $cta_role ) ? $cta_role : 'button';
$cta_rel_attr          = isset( $cta_rel ) ? trim( (string) $cta_rel ) : '';
$data_attributes       = sprintf(
    ' data-animations-enabled="%s" data-has-multiple-taglines="%s"',
    esc_attr( $animations_enabled ? 'true' : 'false' ),
    esc_attr( $has_dual_tagline ? 'true' : 'false' )
);
$deals_enabled         = ! empty( $deals_enabled );
$deals_list            = isset( $deals ) && is_array( $deals ) ? $deals : array();
$deals_button_rel_attr = isset( $deals_button_rel ) ? trim( (string) $deals_button_rel ) : '';
$deals_disclaimer_text = isset( $deals_disclaimer ) ? trim( (string) $deals_disclaimer ) : '';

$verdict_data             = isset( $verdict ) && is_array( $verdict ) ? $verdict : array();
$verdict_enabled          = ! empty( $verdict_data['enabled'] );
$verdict_summary          = isset( $verdict_data['summary'] ) ? (string) $verdict_data['summary'] : '';
$verdict_cta_data         = isset( $verdict_data['cta'] ) && is_array( $verdict_data['cta'] ) ? $verdict_data['cta'] : array();
$verdict_cta_label        = isset( $verdict_cta_data['label'] ) ? (string) $verdict_cta_data['label'] : '';
$verdict_cta_url          = isset( $verdict_cta_data['url'] ) ? (string) $verdict_cta_data['url'] : '';
$verdict_cta_rel          = isset( $verdict_cta_data['rel'] ) ? (string) $verdict_cta_data['rel'] : '';
$verdict_cta_available    = ! empty( $verdict_cta_data['available'] ) && $verdict_cta_label !== '' && $verdict_cta_url !== '';
$verdict_status_data      = isset( $verdict_data['status'] ) && is_array( $verdict_data['status'] ) ? $verdict_data['status'] : array();
$verdict_status_label     = isset( $verdict_status_data['label'] ) ? (string) $verdict_status_data['label'] : '';
$verdict_status_desc      = isset( $verdict_status_data['description'] ) ? (string) $verdict_status_data['description'] : '';
$verdict_status_slug      = isset( $verdict_status_data['slug'] ) ? (string) $verdict_status_data['slug'] : '';
$verdict_updated_data     = isset( $verdict_data['updated'] ) && is_array( $verdict_data['updated'] ) ? $verdict_data['updated'] : array();
$verdict_updated_display  = isset( $verdict_updated_data['display'] ) ? (string) $verdict_updated_data['display'] : '';
$verdict_updated_datetime = isset( $verdict_updated_data['datetime'] ) ? (string) $verdict_updated_data['datetime'] : '';
$verdict_updated_title    = isset( $verdict_updated_data['title'] ) ? (string) $verdict_updated_data['title'] : '';
$verdict_has_summary      = $verdict_summary !== '';
$verdict_has_meta         = ( $verdict_status_label !== '' || $verdict_status_desc !== '' || $verdict_updated_display !== '' );
$verdict_should_render    = $verdict_enabled && ( $verdict_has_summary || $verdict_has_meta || $verdict_cta_available );
$verdict_section_id       = function_exists( 'wp_unique_id' ) ? wp_unique_id( 'jlg-aio-verdict-' ) : 'jlg-aio-verdict-' . uniqid();
$verdict_summary_id       = $verdict_section_id . '-summary';

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

$verdict_data         = isset( $verdict ) && is_array( $verdict ) ? $verdict : array();
$display_verdict      = ! empty( $display_verdict );
$verdict_summary_raw  = isset( $verdict_data['summary'] ) ? trim( (string) $verdict_data['summary'] ) : '';
$verdict_summary_html = '';
if ( $verdict_summary_raw !== '' ) {
    if ( strpos( $verdict_summary_raw, '<' ) === false ) {
        $verdict_summary_html = '<p>' . str_replace( array( "\r\n", "\r", "\n" ), '</p><p>', esc_html( $verdict_summary_raw ) ) . '</p>';
    } else {
        $verdict_summary_html = wp_kses_post( $verdict_summary_raw );
    }
}
$verdict_status             = isset( $verdict_data['status'] ) && is_array( $verdict_data['status'] ) ? $verdict_data['status'] : array();
$verdict_status_slug        = isset( $verdict_status['slug'] ) ? sanitize_html_class( (string) $verdict_status['slug'] ) : '';
$verdict_status_label       = isset( $verdict_status['label'] ) ? (string) $verdict_status['label'] : '';
$verdict_status_description = isset( $verdict_status['description'] ) ? (string) $verdict_status['description'] : '';
$verdict_cta_label          = isset( $verdict_data['cta_label'] ) ? (string) $verdict_data['cta_label'] : '';
$verdict_cta_url            = isset( $verdict_data['cta_url'] ) ? (string) $verdict_data['cta_url'] : '';
$verdict_cta_rel            = isset( $verdict_data['cta_rel'] ) ? (string) $verdict_data['cta_rel'] : 'bookmark';
$verdict_permalink          = isset( $verdict_data['permalink'] ) ? (string) $verdict_data['permalink'] : '';
$verdict_timestamp          = isset( $verdict_data['last_updated']['timestamp'] ) ? $verdict_data['last_updated']['timestamp'] : null;
$verdict_iso_date           = isset( $verdict_data['last_updated']['iso'] ) ? (string) $verdict_data['last_updated']['iso'] : '';
$verdict_date_label         = isset( $verdict_data['last_updated']['display'] ) ? (string) $verdict_data['last_updated']['display'] : '';
$verdict_section_id         = function_exists( 'wp_unique_id' ) ? wp_unique_id( 'jlg-aio-verdict-' ) : 'jlg-aio-verdict-' . uniqid();

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

    <?php if ( $display_verdict ) : ?>
    <section class="jlg-aio-verdict" aria-labelledby="<?php echo esc_attr( $verdict_section_id ); ?>">
        <div class="jlg-aio-verdict__meta">
            <?php if ( $verdict_status_label !== '' ) : ?>
				<?php
				$status_classes = array( 'jlg-aio-verdict__status' );
				if ( $verdict_status_slug !== '' ) {
					$status_classes[] = 'jlg-aio-verdict__status--' . $verdict_status_slug;
				}
				$status_class_attr = implode( ' ', array_map( 'sanitize_html_class', $status_classes ) );
				?>
            <span class="<?php echo esc_attr( $status_class_attr ); ?>">
                <span class="jlg-aio-verdict__status-indicator" aria-hidden="true"></span>
                <span class="jlg-aio-verdict__status-label"><?php echo esc_html( $verdict_status_label ); ?></span>
                <?php if ( $verdict_status_description !== '' ) : ?>
                <span class="jlg-aio-verdict__status-description"><?php echo esc_html( $verdict_status_description ); ?></span>
                <?php endif; ?>
            </span>
            <?php endif; ?>

            <?php if ( $verdict_date_label !== '' ) : ?>
            <span class="jlg-aio-verdict__updated">
                <?php
                $formatted_update = sprintf(
                    /* translators: %s: formatted update date. */
                    esc_html__( 'Mise à jour le %s', 'notation-jlg' ),
                    $verdict_date_label
                );
                ?>
                <?php if ( $verdict_iso_date !== '' ) : ?>
                    <time datetime="<?php echo esc_attr( $verdict_iso_date ); ?>"><?php echo $formatted_update; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></time>
                <?php else : ?>
                    <?php echo $formatted_update; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php endif; ?>
            </span>
            <?php endif; ?>
        </div>

        <div class="jlg-aio-verdict__body">
            <div class="jlg-aio-verdict__heading">
                <h3 id="<?php echo esc_attr( $verdict_section_id ); ?>" class="jlg-aio-verdict__title"><?php echo esc_html__( 'Verdict de la rédaction', 'notation-jlg' ); ?></h3>
                <?php if ( $verdict_permalink !== '' ) : ?>
                <a class="jlg-aio-verdict__permalink" href="<?php echo esc_url( $verdict_permalink ); ?>">
                    <span class="screen-reader-text"><?php esc_html_e( 'Voir le test complet', 'notation-jlg' ); ?></span>
                </a>
                <?php endif; ?>
            </div>

            <?php if ( $verdict_summary_html !== '' ) : ?>
            <div class="jlg-aio-verdict__summary">
                <?php echo $verdict_summary_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
            <?php endif; ?>

            <?php if ( $verdict_cta_url !== '' && $verdict_cta_label !== '' ) : ?>
            <div class="jlg-aio-verdict__actions">
                <a class="jlg-aio-verdict__cta" href="<?php echo esc_url( $verdict_cta_url ); ?>"<?php echo $verdict_cta_rel !== '' ? ' rel="' . esc_attr( $verdict_cta_rel ) . '"' : ''; ?>>
                    <span class="jlg-aio-verdict__cta-label"><?php echo esc_html( $verdict_cta_label ); ?></span>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </section>
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

    <?php if ( $verdict_should_render ) : ?>
    <section class="jlg-aio-verdict" aria-labelledby="<?php echo esc_attr( $verdict_section_id ); ?>">
        <div class="jlg-aio-verdict-header">
            <h3 id="<?php echo esc_attr( $verdict_section_id ); ?>" class="jlg-aio-verdict-title">
                <?php esc_html_e( 'Verdict de la rédaction', 'notation-jlg' ); ?>
            </h3>
        </div>
        <?php if ( $verdict_has_summary ) : ?>
        <p class="jlg-aio-verdict-summary" id="<?php echo esc_attr( $verdict_summary_id ); ?>">
            <?php echo esc_html( $verdict_summary ); ?>
        </p>
        <?php endif; ?>
        <?php if ( $verdict_has_meta ) : ?>
        <dl class="jlg-aio-verdict-meta"<?php echo $verdict_has_summary ? ' aria-describedby="' . esc_attr( $verdict_summary_id ) . '"' : ''; ?>>
            <?php if ( $verdict_status_label !== '' ) : ?>
				<?php
				$verdict_status_classes = array( 'jlg-aio-verdict-status' );
				if ( $verdict_status_slug !== '' ) {
					$verdict_status_classes[] = 'jlg-aio-verdict-status--' . sanitize_html_class( $verdict_status_slug );
				}
				?>
            <div class="jlg-aio-verdict-meta-item">
                <dt><?php esc_html_e( 'Statut du test', 'notation-jlg' ); ?></dt>
                <dd>
                    <span class="<?php echo esc_attr( implode( ' ', $verdict_status_classes ) ); ?>">
                        <?php echo esc_html( $verdict_status_label ); ?>
                    </span>
                    <?php if ( $verdict_status_desc !== '' ) : ?>
                        <span class="jlg-aio-verdict-status-description"><?php echo esc_html( $verdict_status_desc ); ?></span>
                    <?php endif; ?>
                </dd>
            </div>
            <?php endif; ?>
            <?php if ( $verdict_updated_display !== '' ) : ?>
            <div class="jlg-aio-verdict-meta-item">
                <dt><?php esc_html_e( 'Dernière mise à jour', 'notation-jlg' ); ?></dt>
                <dd>
                    <?php if ( $verdict_updated_datetime !== '' ) : ?>
                        <time datetime="<?php echo esc_attr( $verdict_updated_datetime ); ?>"<?php echo $verdict_updated_title !== '' ? ' title="' . esc_attr( $verdict_updated_title ) . '"' : ''; ?>>
                            <?php echo esc_html( $verdict_updated_display ); ?>
                        </time>
                    <?php else : ?>
                        <span><?php echo esc_html( $verdict_updated_display ); ?></span>
                    <?php endif; ?>
                </dd>
            </div>
            <?php endif; ?>
        </dl>
        <?php endif; ?>
        <?php if ( $verdict_cta_available ) : ?>
        <p class="jlg-aio-verdict-cta">
            <a class="jlg-aio-verdict-button" href="<?php echo esc_url( $verdict_cta_url ); ?>"<?php echo $verdict_cta_rel !== '' ? ' rel="' . esc_attr( $verdict_cta_rel ) . '"' : ''; ?>>
                <span><?php echo esc_html( $verdict_cta_label ); ?></span>
            </a>
        </p>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if ( $deals_enabled && ! empty( $deals_list ) ) : ?>
    <section class="jlg-aio-deals" aria-label="<?php esc_attr_e( 'Deals & disponibilités', 'notation-jlg' ); ?>">
        <h3 class="jlg-aio-deals__title"><?php esc_html_e( 'Deals & disponibilités', 'notation-jlg' ); ?></h3>
        <ul class="jlg-aio-deals__list" role="list">
            <?php foreach ( $deals_list as $deal ) : ?>
                <?php
                $deal_retailer     = isset( $deal['retailer'] ) ? (string) $deal['retailer'] : '';
                $deal_price        = isset( $deal['price_display'] ) ? (string) $deal['price_display'] : '';
                $deal_availability = isset( $deal['availability'] ) ? (string) $deal['availability'] : '';
                $deal_cta_label    = isset( $deal['cta_label'] ) ? (string) $deal['cta_label'] : '';
                $deal_url          = isset( $deal['url'] ) ? (string) $deal['url'] : '';
                $deal_highlight    = ! empty( $deal['is_best'] );
                $deal_item_classes = array( 'jlg-aio-deals__item' );
                if ( $deal_highlight ) {
                    $deal_item_classes[] = 'jlg-aio-deals__item--highlight';
                }
                $deal_item_class_attr = implode( ' ', array_map( 'sanitize_html_class', $deal_item_classes ) );
                ?>
                <li class="<?php echo esc_attr( $deal_item_class_attr ); ?>">
                    <div class="jlg-aio-deals__header">
                        <span class="jlg-aio-deals__retailer"><?php echo esc_html( $deal_retailer ); ?></span>
                        <?php if ( $deal_price !== '' ) : ?>
                            <span class="jlg-aio-deals__price"><?php echo esc_html( $deal_price ); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ( $deal_availability !== '' ) : ?>
                        <p class="jlg-aio-deals__availability"><?php echo esc_html( $deal_availability ); ?></p>
                    <?php endif; ?>
                    <?php if ( $deal_url !== '' ) : ?>
                        <a class="jlg-aio-deals__button" href="<?php echo esc_url( $deal_url ); ?>"<?php echo $deals_button_rel_attr !== '' ? ' rel="' . esc_attr( $deals_button_rel_attr ) . '"' : ''; ?>>
                            <span><?php echo esc_html( $deal_cta_label !== '' ? $deal_cta_label : __( 'Voir l’offre', 'notation-jlg' ) ); ?></span>
                        </a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php if ( $deals_disclaimer_text !== '' ) : ?>
            <p class="jlg-aio-deals__disclaimer"><?php echo esc_html( $deals_disclaimer_text ); ?></p>
        <?php endif; ?>
    </section>
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

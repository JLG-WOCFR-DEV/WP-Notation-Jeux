<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo $widget_args['before_widget'];

if ( ! empty( $title ) ) {
    echo $widget_args['before_title'] . esc_html( $title ) . $widget_args['after_title'];
}

if ( $latest_reviews->have_posts() ) {
    $opencritic_map     = isset( $opencritic_map ) && is_array( $opencritic_map ) ? $opencritic_map : array();
    $opencritic_strings = isset( $opencritic_strings ) && is_array( $opencritic_strings ) ? $opencritic_strings : array();
    $opencritic_view_label = isset( $opencritic_strings['view_label'] ) && $opencritic_strings['view_label'] !== ''
        ? $opencritic_strings['view_label']
        : esc_html__( 'Voir sur OpenCritic', 'notation-jlg' );
    $opencritic_view_label_for = isset( $opencritic_strings['view_label_for'] ) && $opencritic_strings['view_label_for'] !== ''
        ? $opencritic_strings['view_label_for']
        : esc_html__( 'Voir la fiche OpenCritic de %s', 'notation-jlg' );
    $opencritic_score_fallback = isset( $opencritic_strings['score_fallback'] ) && $opencritic_strings['score_fallback'] !== ''
        ? $opencritic_strings['score_fallback']
        : esc_html__( 'N/A', 'notation-jlg' );

    echo '<ul class="jlg-widget-review-list">';
    while ( $latest_reviews->have_posts() ) {
        $latest_reviews->the_post();
        $post_id    = get_the_ID();
        $game_title = \JLG\Notation\Helpers::get_game_title( $post_id );
        $opencritic  = isset( $opencritic_map[ $post_id ] ) ? $opencritic_map[ $post_id ] : \JLG\Notation\Helpers::get_opencritic_display_data( $post_id );
        $status      = isset( $opencritic['status'] ) ? $opencritic['status'] : 'unlinked';
        $label       = isset( $opencritic['status_label'] ) ? $opencritic['status_label'] : '';
        $score       = isset( $opencritic['score_display'] ) ? $opencritic['score_display'] : '';
        $url         = isset( $opencritic['url'] ) ? $opencritic['url'] : '';
        $title_value = isset( $opencritic['title'] ) && $opencritic['title'] !== '' ? $opencritic['title'] : $game_title;
        $color       = isset( $opencritic['status_color'] ) && $opencritic['status_color'] !== '' ? $opencritic['status_color'] : '#94a3b8';
        $bg          = isset( $opencritic['status_background'] ) && $opencritic['status_background'] !== '' ? $opencritic['status_background'] : 'rgba(148, 163, 184, 0.16)';
        $border      = isset( $opencritic['status_border'] ) && $opencritic['status_border'] !== '' ? $opencritic['status_border'] : 'rgba(148, 163, 184, 0.38)';
        $style       = sprintf( '--jlg-opencritic-color:%1$s;--jlg-opencritic-bg:%2$s;--jlg-opencritic-border:%3$s;', $color, $bg, $border );
        $link_label  = $opencritic_view_label;
        if ( $opencritic_view_label_for !== '' && strpos( $opencritic_view_label_for, '%s' ) !== false ) {
            $link_label = sprintf( $opencritic_view_label_for, $title_value );
        }
        $display_opencritic = $status !== 'unlinked';

        echo '<li class="jlg-widget-review">';
        echo '<a class="jlg-widget-review__title" href="' . esc_url( get_permalink() ) . '">' . esc_html( $game_title ) . '</a>';

        if ( $display_opencritic ) {
            echo '<div class="jlg-widget-review__meta" data-opencritic-status="' . esc_attr( $status ) . '">';
            echo '<span class="jlg-opencritic-chip" style="' . esc_attr( $style ) . '">';
            echo '<span class="jlg-opencritic-chip__score">' . esc_html( $score !== '' ? $score : $opencritic_score_fallback ) . '</span>';
            if ( $label !== '' ) {
                echo '<span class="jlg-opencritic-chip__label">' . esc_html( $label ) . '</span>';
            }
            echo '</span>';

            if ( $url !== '' ) {
                echo '<a class="jlg-opencritic-link jlg-widget-review__link" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer" data-opencritic-title="' . esc_attr( $title_value ) . '" aria-label="' . esc_attr( $link_label ) . '">';
                echo esc_html( $opencritic_view_label );
                echo '</a>';
            }

            echo '</div>';
        }

        echo '</li>';
    }
    echo '</ul>';
} else {
    echo '<p>' . esc_html__( 'Aucun test trouvé.', 'notation-jlg' ) . '</p>';
}
wp_reset_postdata();

echo $widget_args['after_widget'];

<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$has_deals              = ! empty( $deals ) && is_array( $deals );
$show_empty_placeholder = ! $has_deals && ! empty( $display_empty_message );

if ( ! $has_deals && ! $show_empty_placeholder ) {
    return;
}

echo $widget_args['before_widget'];

if ( ! empty( $title ) ) {
    echo $widget_args['before_title'] . esc_html( $title ) . $widget_args['after_title'];
}

echo '<div class="jlg-widget-deals" aria-label="' . esc_attr__( 'Deals & disponibilités', 'notation-jlg' ) . '">';

if ( $has_deals ) {
    echo '<ul class="jlg-widget-deals__list" role="list">';
    foreach ( $deals as $deal ) {
        $retailer     = isset( $deal['retailer'] ) ? (string) $deal['retailer'] : '';
        $price        = isset( $deal['price_display'] ) ? (string) $deal['price_display'] : '';
        $availability = isset( $deal['availability'] ) ? (string) $deal['availability'] : '';
        $cta_label    = isset( $deal['cta_label'] ) ? (string) $deal['cta_label'] : '';
        $url          = isset( $deal['url'] ) ? (string) $deal['url'] : '';
        $is_best      = ! empty( $deal['is_best'] );

        $item_classes = array( 'jlg-widget-deals__item' );
        if ( $is_best ) {
            $item_classes[] = 'jlg-widget-deals__item--highlight';
        }

        echo '<li class="' . esc_attr( implode( ' ', array_map( 'sanitize_html_class', $item_classes ) ) ) . '">';
        echo '<div class="jlg-widget-deals__row">';
        echo '<span class="jlg-widget-deals__retailer">' . esc_html( $retailer ) . '</span>';

        if ( $price !== '' ) {
            echo '<span class="jlg-widget-deals__price">' . esc_html( $price ) . '</span>';
        }

        echo '</div>';

        if ( $availability !== '' ) {
            echo '<p class="jlg-widget-deals__availability">' . esc_html( $availability ) . '</p>';
        }

        if ( $url !== '' ) {
            $rel = $rel_attribute !== '' ? $rel_attribute : '';
            echo '<a class="jlg-widget-deals__button" href="' . esc_url( $url ) . '"' . ( $rel !== '' ? ' rel="' . esc_attr( $rel ) . '"' : '' ) . '>';
            $label = $cta_label !== '' ? $cta_label : __( 'Voir l’offre', 'notation-jlg' );
            echo '<span>' . esc_html( $label ) . '</span>';
            echo '</a>';
        }

        echo '</li>';
    }
    echo '</ul>';
}

if ( $show_empty_placeholder ) {
    echo '<p class="jlg-widget-deals__empty">' . esc_html( $empty_message ) . '</p>';
}

if ( $has_deals && ! empty( $disclaimer ) ) {
    echo '<p class="jlg-widget-deals__disclaimer">' . esc_html( $disclaimer ) . '</p>';
}

echo '</div>';

echo $widget_args['after_widget'];

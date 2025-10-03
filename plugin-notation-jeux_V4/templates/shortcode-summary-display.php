<?php
/**
 * Template pour l'affichage du tableau/grille récapitulatif
 *
 * Variables disponibles :
 * - $query : WP_Query object
 * - $atts : Attributs du shortcode
 * - $paged : Page actuelle
 * - $orderby : Tri actuel
 * - $order : Ordre actuel (ASC/DESC)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$table_id              = isset( $atts['id'] ) ? sanitize_html_class( $atts['id'] ) : 'jlg-table-' . uniqid();
$layout                = isset( $atts['layout'] ) ? $atts['layout'] : 'table';
$columns               = is_array( $colonnes ) ? $colonnes : array();
$available_columns     = is_array( $colonnes_disponibles ) ? $colonnes_disponibles : array();
$current_orderby       = ! empty( $orderby ) ? $orderby : 'date';
$current_order         = ! empty( $order ) ? $order : 'DESC';
$current_letter_filter = isset( $letter_filter ) ? sanitize_text_field( $letter_filter ) : ( isset( $atts['letter_filter'] ) ? sanitize_text_field( $atts['letter_filter'] ) : '' );
$current_genre_filter  = isset( $genre_filter ) ? sanitize_text_field( $genre_filter ) : ( isset( $atts['genre_filter'] ) ? sanitize_text_field( $atts['genre_filter'] ) : '' );
$show_filters          = empty( $atts['categorie'] );
$current_cat_filter    = ( $show_filters && isset( $cat_filter ) ) ? intval( $cat_filter ) : 0;
$columns_attr          = ! empty( $columns ) ? implode( ',', array_map( 'sanitize_key', $columns ) ) : '';
$genre_taxonomy        = apply_filters( 'jlg_summary_genre_taxonomy', 'jlg_game_genre' );
$has_genre_taxonomy    = ! empty( $genre_taxonomy ) && taxonomy_exists( $genre_taxonomy );
$genre_terms           = array();
$request_prefix        = isset( $request_prefix ) ? (string) $request_prefix : '';
$request_keys          = isset( $request_keys ) && is_array( $request_keys ) ? $request_keys : array();
$resolve_request_key   = static function ( $key ) use ( $request_prefix, $request_keys ) {
    if ( isset( $request_keys[ $key ] ) && is_string( $request_keys[ $key ] ) ) {
        return $request_keys[ $key ];
    }

    return $request_prefix !== '' ? $key . '__' . $request_prefix : $key;
};

if ( $show_filters && $has_genre_taxonomy ) {
    $genre_terms = get_terms(
        array(
			'taxonomy'   => $genre_taxonomy,
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
        )
    );

    if ( is_wp_error( $genre_terms ) ) {
        $genre_terms = array();
    }
}

$letters = range( 'A', 'Z' );
?>

<div
    id="<?php echo esc_attr( $table_id ); ?>"
    class="jlg-summary-wrapper"
    data-posts-per-page="<?php echo esc_attr( intval( $atts['posts_per_page'] ) ); ?>"
    data-layout="<?php echo esc_attr( $layout ); ?>"
    data-categorie="<?php echo esc_attr( $atts['categorie'] ); ?>"
    data-colonnes="<?php echo esc_attr( $columns_attr ); ?>"
    data-orderby="<?php echo esc_attr( $current_orderby ); ?>"
    data-order="<?php echo esc_attr( $current_order ); ?>"
    data-paged="<?php echo esc_attr( intval( $paged ) ); ?>"
    data-cat-filter="<?php echo esc_attr( $current_cat_filter ); ?>"
    data-letter-filter="<?php echo esc_attr( $current_letter_filter ); ?>"
    data-genre-filter="<?php echo esc_attr( $current_genre_filter ); ?>"
    data-request-prefix="<?php echo esc_attr( $request_prefix ); ?>"
>

    <?php if ( $show_filters ) : ?>
        <!-- Filtres -->
        <div class="jlg-summary-filters">
            <?php $letter_filter_request_name = $resolve_request_key( 'letter_filter' ); ?>
            <form method="get" action="" class="jlg-summary-filters-form" id="<?php echo esc_attr( $table_id . '_filters_form' ); ?>">
                <div class="jlg-summary-letter-filter" role="group" aria-label="<?php esc_attr_e( 'Filtrer par lettre', 'notation-jlg' ); ?>">
                    <input type="hidden" name="<?php echo esc_attr( $letter_filter_request_name ); ?>" value="<?php echo esc_attr( $current_letter_filter ); ?>" class="jlg-summary-letter-filter__value" />
                    <?php
                    $all_letters_classes = array();
                    $all_letters_pressed = 'false';
                    if ( $current_letter_filter === '' ) {
                        $all_letters_classes[] = 'is-active';
                        $all_letters_pressed   = 'true';
                    }
                    ?>
                    <button type="submit" name="<?php echo esc_attr( $letter_filter_request_name ); ?>" value="" class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $all_letters_classes ) ) ); ?>" data-letter="" aria-pressed="<?php echo esc_attr( $all_letters_pressed ); ?>">
                        <?php esc_html_e( 'Tous', 'notation-jlg' ); ?>
                    </button>
                    <?php foreach ( $letters as $letter ) : ?>
                        <?php
                        $letter_button_classes = array();
                        $letter_button_pressed = 'false';
                        if ( $current_letter_filter === $letter ) {
                            $letter_button_classes[] = 'is-active';
                            $letter_button_pressed   = 'true';
                        }
                        ?>
                        <button type="submit" name="<?php echo esc_attr( $letter_filter_request_name ); ?>" value="<?php echo esc_attr( $letter ); ?>" data-letter="<?php echo esc_attr( $letter ); ?>" class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $letter_button_classes ) ) ); ?>" aria-pressed="<?php echo esc_attr( $letter_button_pressed ); ?>">
                            <?php echo esc_html( $letter ); ?>
                        </button>
                    <?php endforeach; ?>
                    <?php
                    $numeric_button_classes = array();
                    $numeric_button_pressed = 'false';
                    if ( $current_letter_filter === '#' ) {
                        $numeric_button_classes[] = 'is-active';
                        $numeric_button_pressed   = 'true';
                    }
                    ?>
                    <button type="submit" name="<?php echo esc_attr( $letter_filter_request_name ); ?>" value="#" data-letter="#" class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $numeric_button_classes ) ) ); ?>" aria-pressed="<?php echo esc_attr( $numeric_button_pressed ); ?>">
                        <?php esc_html_e( '0-9', 'notation-jlg' ); ?>
                    </button>
                </div>
                <input type="hidden" name="<?php echo esc_attr( $resolve_request_key( 'orderby' ) ); ?>" value="<?php echo esc_attr( $current_orderby ); ?>">
                <input type="hidden" name="<?php echo esc_attr( $resolve_request_key( 'order' ) ); ?>" value="<?php echo esc_attr( $current_order ); ?>">
                <label for="<?php echo esc_attr( $table_id . '_cat_filter' ); ?>" class="screen-reader-text">
                    <?php esc_html_e( 'Filtrer par catégorie', 'notation-jlg' ); ?>
                </label>
                <?php
                wp_dropdown_categories(
                    array(
                        'show_option_all' => __( 'Toutes les catégories', 'notation-jlg' ),
                        'orderby'         => 'name',
                        'hide_empty'      => 1,
                        'name'            => $resolve_request_key( 'cat_filter' ),
                        'id'              => $table_id . '_cat_filter',
                        'selected'        => $current_cat_filter,
                        'hierarchical'    => true,
                        'class'           => 'jlg-cat-filter-select',
                    )
                );
                ?>
                <?php if ( $has_genre_taxonomy && ! empty( $genre_terms ) ) : ?>
                    <label for="<?php echo esc_attr( $table_id . '_genre_filter' ); ?>" class="screen-reader-text">
                        <?php esc_html_e( 'Filtrer par genre', 'notation-jlg' ); ?>
                    </label>
                    <select name="<?php echo esc_attr( $resolve_request_key( 'genre_filter' ) ); ?>" id="<?php echo esc_attr( $table_id . '_genre_filter' ); ?>" class="jlg-genre-filter-select">
                        <option value="" <?php selected( $current_genre_filter, '' ); ?>><?php esc_html_e( 'Tous les genres', 'notation-jlg' ); ?></option>
                        <?php foreach ( $genre_terms as $term ) : ?>
                            <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $current_genre_filter, $term->slug ); ?>>
                                <?php echo esc_html( $term->name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else : ?>
                    <input type="hidden" name="<?php echo esc_attr( $resolve_request_key( 'genre_filter' ) ); ?>" value="<?php echo esc_attr( $current_genre_filter ); ?>">
                <?php endif; ?>
                <input type="submit" value="<?php echo esc_attr__( 'Filtrer', 'notation-jlg' ); ?>">
            </form>
        </div>
    <?php endif; ?>

    <div class="jlg-summary-content" role="status" aria-live="polite" aria-busy="false">
        <?php
        echo \JLG\Notation\Frontend::get_template_html(
            'summary-table-fragment',
            array(
				'query'                => $query,
				'atts'                 => $atts,
				'paged'                => $paged,
				'orderby'              => $orderby,
				'order'                => $order,
				'colonnes'             => $columns,
				'colonnes_disponibles' => $available_columns,
				'error_message'        => isset( $error_message ) ? $error_message : '',
				'cat_filter'           => $current_cat_filter,
				'table_id'             => $table_id,
			)
        );
        ?>
    </div>
</div>

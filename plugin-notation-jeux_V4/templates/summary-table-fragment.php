<?php
/**
 * Fragment HTML pour l'affichage du tableau ou de la grille récapitulative.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$score_max         = isset( $score_max ) ? max( 1, (float) $score_max ) : \JLG\Notation\Helpers::get_score_max();
$score_max_label   = number_format_i18n( $score_max );
$columns           = is_array( $colonnes ) ? $colonnes : array();
$available_columns = is_array( $colonnes_disponibles ) ? $colonnes_disponibles : array();
$table_id          = ! empty( $table_id ) ? sanitize_html_class( $table_id ) : '';
if ( $table_id === '' && isset( $atts['id'] ) ) {
    $table_id = sanitize_html_class( $atts['id'] );
}
$layout                = isset( $atts['layout'] ) ? $atts['layout'] : 'table';
$base_url              = isset( $base_url ) ? esc_url_raw( $base_url ) : '';
$current_orderby       = ! empty( $orderby ) ? $orderby : 'date';
$current_order         = ! empty( $order ) ? strtoupper( $order ) : 'DESC';
$current_cat_filter    = isset( $cat_filter ) ? intval( $cat_filter ) : 0;
$current_letter_filter = isset( $letter_filter ) ? sanitize_text_field( $letter_filter ) : ( isset( $atts['letter_filter'] ) ? sanitize_text_field( $atts['letter_filter'] ) : '' );
$current_genre_filter  = isset( $genre_filter ) ? sanitize_text_field( $genre_filter ) : ( isset( $atts['genre_filter'] ) ? sanitize_text_field( $atts['genre_filter'] ) : '' );
$genre_taxonomy        = apply_filters( 'jlg_summary_genre_taxonomy', 'jlg_game_genre' );
$has_genre_taxonomy    = ! empty( $genre_taxonomy ) && taxonomy_exists( $genre_taxonomy );
$default_empty_message = '<p>' . esc_html__( 'Aucun article trouvé pour cette sélection.', 'notation-jlg' ) . '</p>';
$empty_message         = ! empty( $error_message ) ? $error_message : $default_empty_message;
$columns_count         = count( $columns );

$category_column_map = array();

foreach ( $columns as $col_key ) {
    if ( isset( $available_columns[ $col_key ]['type'] ) && $available_columns[ $col_key ]['type'] === 'rating_category' ) {
        $definition = isset( $available_columns[ $col_key ]['definition'] ) ? $available_columns[ $col_key ]['definition'] : array();

        if ( is_array( $definition ) ) {
            $category_column_map[ $col_key ] = $definition;
        }
    }
}

$has_category_columns = ! empty( $category_column_map );

$active_filter_labels = array();

if ( $current_cat_filter > 0 ) {
    $category = get_category( $current_cat_filter );

    if ( $category && ! is_wp_error( $category ) ) {
        $active_filter_labels[] = sprintf(
            /* translators: %s is the category name. */
            esc_html__( 'Catégorie : %s', 'notation-jlg' ),
            $category->name
        );
    }
}

if ( $current_letter_filter !== '' ) {
    $letter_label = $current_letter_filter === '#'
        ? esc_html__( '0-9', 'notation-jlg' )
        : strtoupper( $current_letter_filter );

    $active_filter_labels[] = sprintf(
        /* translators: %s is the first letter used to filter results. */
        esc_html__( 'Lettre : %s', 'notation-jlg' ),
        $letter_label
    );
}

if ( $current_genre_filter !== '' ) {
    $genre_display = $current_genre_filter;

    if ( $has_genre_taxonomy ) {
        $genre_term = get_term_by( 'slug', $current_genre_filter, $genre_taxonomy );

        if ( $genre_term && ! is_wp_error( $genre_term ) ) {
            $genre_display = $genre_term->name;
        }
    }

    $active_filter_labels[] = sprintf(
        /* translators: %s is the selected genre. */
        esc_html__( 'Genre : %s', 'notation-jlg' ),
        $genre_display
    );
}

if ( empty( $error_message ) && ! empty( $active_filter_labels ) ) {
    $message_labels = implode( ', ', array_map( 'esc_html', $active_filter_labels ) );
    $empty_message  = '<p>' . sprintf(
        /* translators: %s is the list of active filters. */
        esc_html__( 'Aucun article ne correspond aux filtres : %s.', 'notation-jlg' ),
        $message_labels
    ) . '</p>';
}

if ( $columns_count === 0 ) {
    $columns_count = 1;
}

if ( ! function_exists( 'jlg_get_summary_column_label' ) ) {
    function jlg_get_summary_column_label( $col_info ) {
        $label = isset( $col_info['label'] ) ? (string) $col_info['label'] : '';

        if ( isset( $col_info['type'] ) && $col_info['type'] === 'rating_category' ) {
            $weight = isset( $col_info['weight'] ) ? $col_info['weight'] : 1.0;
            $weight = \JLG\Notation\Helpers::normalize_category_weight( $weight, 1.0 );

            if ( abs( $weight - 1.0 ) > 0.001 ) {
                $label .= ' ' . sprintf(
                    _x( '×%s', 'category weight multiplier', 'notation-jlg' ),
                    number_format_i18n( $weight, 1 )
                );
            }
        }

        return $label;
    }
}

if ( ! function_exists( 'jlg_print_sortable_header' ) ) {
    function jlg_print_sortable_header( $col, $col_info, $current_orderby, $current_order, $table_id, $extra_params = array() ) {
        $base_url = '';
        if ( isset( $extra_params['base_url'] ) ) {
            $base_url = esc_url_raw( $extra_params['base_url'] );
            unset( $extra_params['base_url'] );
        }

        $display_label = jlg_get_summary_column_label( $col_info );

        if ( ! isset( $col_info['sortable'] ) || ! $col_info['sortable'] ) {
            echo '<th scope="col" aria-sort="none">' . esc_html( $display_label ) . '</th>';
            return;
        }

        $sort_key = $col;
        if ( isset( $col_info['sort']['key'] ) ) {
            $sort_key = $col_info['sort']['key'];
        } elseif ( isset( $col_info['key'] ) ) {
            $sort_key = $col_info['key'];
        }

        $sort_key  = sanitize_key( $sort_key );
        $new_order = ( $current_orderby === $sort_key && $current_order === 'ASC' ) ? 'DESC' : 'ASC';
        $args      = array(
            'orderby' => $sort_key,
            'order'   => $new_order,
        );

        if ( ! empty( $extra_params ) && is_array( $extra_params ) ) {
            $filtered_params = array_filter(
                $extra_params,
                function ( $value ) {
					if ( $value === null || $value === '' ) {
						return false;
					}

					if ( $value === 0 || $value === '0' ) {
						return false;
					}

					return true;
				}
            );

            if ( ! empty( $filtered_params ) ) {
                $args = array_merge( $filtered_params, $args );
            }
        }

        if ( $base_url !== '' ) {
            $url = add_query_arg( $args, remove_query_arg( 'paged', $base_url ) );
        } else {
            $url = add_query_arg( $args );
        }
        $url = remove_query_arg( 'paged', $url );

        if ( ! empty( $table_id ) ) {
            $url .= '#' . $table_id;
        }

        $indicator = '';
        $is_active = ( $current_orderby === $sort_key || $current_orderby === $col );
        if ( ! $is_active && isset( $col_info['sort']['aliases'] ) && is_array( $col_info['sort']['aliases'] ) ) {
            foreach ( $col_info['sort']['aliases'] as $alias ) {
                if ( $current_orderby === sanitize_key( $alias ) ) {
                    $is_active = true;
                    break;
                }
            }
        }

        $aria_sort = 'none';
        if ( $is_active ) {
            $aria_sort = strtolower( $current_order ) === 'asc' ? 'ascending' : 'descending';
            $indicator = $current_order === 'ASC'
                ? esc_html__( ' ▲', 'notation-jlg' )
                : esc_html__( ' ▼', 'notation-jlg' );
        }

        $class = 'sortable';
        if ( $is_active ) {
            $class .= ' sorted ' . strtolower( $current_order );
        }

        $column_label_text      = wp_strip_all_tags( $display_label );
        $next_direction_text    = $new_order === 'ASC'
            ? esc_html__( 'ordre croissant', 'notation-jlg' )
            : esc_html__( 'ordre décroissant', 'notation-jlg' );
        $current_direction_text = $is_active
            ? ( strtolower( $current_order ) === 'asc'
                ? esc_html__( 'ordre croissant', 'notation-jlg' )
                : esc_html__( 'ordre décroissant', 'notation-jlg' ) )
            : '';

        if ( $is_active ) {
            $action_text = sprintf(
                /* translators: %1$s: column label, %2$s: current direction, %3$s: next direction. */
                esc_html__( '%1$s, actuellement trié en %2$s. Activer pour trier en %3$s', 'notation-jlg' ),
                $column_label_text,
                $current_direction_text,
                $next_direction_text
            );
        } else {
            $action_text = sprintf(
                /* translators: %1$s: column label, %2$s: sorting direction. */
                esc_html__( 'Trier par %1$s en %2$s', 'notation-jlg' ),
                $column_label_text,
                $next_direction_text
            );
        }

        echo '<th scope="col" class="' . esc_attr( $class ) . '" aria-sort="' . esc_attr( $aria_sort ) . '">';
        echo '<a href="' . esc_url( $url ) . '" aria-label="' . esc_attr( $action_text ) . '">' . esc_html( $display_label ) . $indicator . '</a>';
        echo '</th>';
    }
}

if ( ! function_exists( 'jlg_render_genre_badges' ) ) {
    function jlg_render_genre_badges( $terms ) {
        if ( empty( $terms ) || is_wp_error( $terms ) ) {
            return '';
        }

        $badges = array();

        foreach ( $terms as $term ) {
            if ( ! ( $term instanceof WP_Term ) ) {
                continue;
            }

            $badges[] = '<span class="jlg-genre-badge">' . esc_html( $term->name ) . '</span>';
        }

        if ( empty( $badges ) ) {
            return '';
        }

        return '<div class="jlg-genre-badges">' . implode( '', $badges ) . '</div>';
    }
}

if ( ! empty( $active_filter_labels ) ) {
    echo '<div class="jlg-active-filters" aria-live="polite">';
    foreach ( $active_filter_labels as $label ) {
        echo '<span class="jlg-active-filter-badge">' . esc_html( $label ) . '</span>';
    }
    echo '</div>';
}

if ( $layout === 'grid' ) :
    ?>
    <div class="jlg-summary-grid-wrapper">
        <?php
        if ( $query instanceof WP_Query && $query->have_posts() ) :
            while ( $query->have_posts() ) :
				$query->the_post();
                $post_id    = get_the_ID();
                $game_title = \JLG\Notation\Helpers::get_game_title( $post_id );
                $score_data = \JLG\Notation\Helpers::get_resolved_average_score( $post_id );
                $cover_url  = get_post_meta( $post_id, '_jlg_cover_image_url', true );
                if ( empty( $cover_url ) ) {
                    $cover_url = get_the_post_thumbnail_url( $post_id, 'medium_large' );
                }
                /* translators: Abbreviation meaning that the average score is not available. */
                $score_display = $score_data['formatted'] ?? '';
                if ( $score_display === '' ) {
                    $score_display = __( 'N/A', 'notation-jlg' );
                }
                $genre_terms       = $has_genre_taxonomy ? get_the_terms( $post_id, $genre_taxonomy ) : array();
                $genre_badges_html = jlg_render_genre_badges( $genre_terms );
                ?>
                <a href="<?php the_permalink(); ?>" class="jlg-game-card">
                    <div class="jlg-game-card-score"><?php echo esc_html( $score_display ); ?></div>
                    <?php if ( $cover_url ) : ?>
                        <img src="<?php echo esc_url( $cover_url ); ?>" alt="<?php echo esc_attr( $game_title ); ?>" loading="lazy">
                    <?php endif; ?>
                    <div class="jlg-game-card-title">
                        <span><?php echo esc_html( $game_title ); ?></span>
                    </div>
                    <?php if ( $genre_badges_html !== '' ) : ?>
                        <?php echo wp_kses_post( $genre_badges_html ); ?>
                    <?php endif; ?>
                </a>
				<?php
            endwhile;
        else :
            echo wp_kses_post( $empty_message );
        endif;
        ?>
    </div>
	<?php
else :
    ?>
    <div class="jlg-summary-table-wrapper">
        <table class="jlg-summary-table">
            <thead>
                <tr>
                    <?php
                    $header_extra_params = array(
                        'cat_filter'    => $current_cat_filter,
                        'letter_filter' => $current_letter_filter,
                        'genre_filter'  => $current_genre_filter,
                        'base_url'      => $base_url,
                    );
                    ?>
                    <?php
                    foreach ( $columns as $col ) {
                        if ( ! isset( $available_columns[ $col ] ) ) {
                            continue;
                        }
                        jlg_print_sortable_header( $col, $available_columns[ $col ], $current_orderby, $current_order, $table_id, $header_extra_params );
                    }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php
                if ( $query instanceof WP_Query && $query->have_posts() ) :
                    while ( $query->have_posts() ) :
						$query->the_post();
                        $post_id           = get_the_ID();
                        $genre_terms       = $has_genre_taxonomy ? get_the_terms( $post_id, $genre_taxonomy ) : array();
                        $genre_badges_html = jlg_render_genre_badges( $genre_terms );
                        ?>
                        <tr>
                            <?php
                            $category_score_map = array();
                            if ( $has_category_columns ) {
                                $category_score_map = \JLG\Notation\Helpers::get_post_category_scores( $post_id );
                            }

                            foreach ( $columns as $col ) {
                                if ( ! isset( $available_columns[ $col ] ) ) {
                                    continue;
                                }
                                $column_info       = $available_columns[ $col ];
                                $column_label_attr = jlg_get_summary_column_label( $column_info );
                                echo '<td data-label="' . esc_attr( $column_label_attr ) . '">';

                                switch ( $col ) {
                                    case 'titre':
                                        $game_title = \JLG\Notation\Helpers::get_game_title( $post_id );
                                        echo '<a href="' . esc_url( get_permalink() ) . '">' . esc_html( $game_title ) . '</a>';
                                        if ( $genre_badges_html !== '' ) {
                                            echo wp_kses_post( $genre_badges_html );
                                        }
                                        break;
                                    case 'date':
                                        echo esc_html( get_the_date() );
                                        break;
                                    case 'note':
                                        $score_data = \JLG\Notation\Helpers::get_resolved_average_score( $post_id );
                                        /* translators: Abbreviation meaning that the average score is not available. */
                                        $score_display = $score_data['formatted'] ?? '';
                                        if ( $score_display === '' ) {
                                            $score_display = __( 'N/A', 'notation-jlg' );
                                        }
                                        echo '<strong>' . esc_html( $score_display ) . '</strong> ';
                                        printf(
                                            /* translators: %s: Maximum possible rating value. */
                                            esc_html__( '/ %s', 'notation-jlg' ),
                                            esc_html( $score_max_label )
                                        );
                                        break;
                                    case 'developpeur':
                                        $developer = get_post_meta( $post_id, '_jlg_developpeur', true ) ?: __( '-', 'notation-jlg' );
                                        echo esc_html( $developer );
                                        break;
                                    case 'editeur':
                                        $publisher = get_post_meta( $post_id, '_jlg_editeur', true ) ?: __( '-', 'notation-jlg' );
                                        echo esc_html( $publisher );
                                        break;
                                    default:
                                        if ( isset( $category_column_map[ $col ] ) ) {
                                            $definition  = $category_column_map[ $col ];
                                            $category_id = isset( $definition['id'] ) ? $definition['id'] : '';
                                            $score_value = null;

                                            if ( $category_id !== '' && isset( $category_score_map[ $category_id ] ) ) {
                                                $stored_entry = $category_score_map[ $category_id ];

                                                if ( is_array( $stored_entry ) && isset( $stored_entry['score'] ) ) {
                                                    $score_value = (float) $stored_entry['score'];
                                                }
                                            } elseif ( $category_id !== '' ) {
                                                $resolved = \JLG\Notation\Helpers::resolve_category_meta_value( $post_id, $definition, true );
                                                if ( $resolved !== null ) {
                                                    $score_value                        = (float) $resolved;
                                                    $category_score_map[ $category_id ] = array(
                                                        'score'  => $score_value,
                                                        'weight' => \JLG\Notation\Helpers::normalize_category_weight(
                                                            $definition['weight'] ?? 1.0,
                                                            1.0
                                                        ),
                                                    );
                                                }
                                            }

                                            if ( $score_value === null ) {
                                                echo esc_html__( 'N/A', 'notation-jlg' );
                                            } else {
                                                $formatted_score = number_format_i18n( $score_value, 1 );

                                                printf(
                                                    /* translators: %1$s: category score value. %2$s: maximum rating value. */
                                                    esc_html__( '%1$s / %2$s', 'notation-jlg' ),
                                                    esc_html( $formatted_score ),
                                                    esc_html( $score_max_label )
                                                );
                                            }

                                            break;
                                        }

                                        echo '&mdash;';
                                        break;
                                }
                                echo '</td>';
                            }
                            ?>
                        </tr>
						<?php
                    endwhile;
                else :
                    ?>
                    <tr>
                        <td colspan="<?php echo esc_attr( $columns_count ); ?>"><?php echo wp_kses_post( $empty_message ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
	<?php
endif;

if ( $query instanceof WP_Query ) {
    $total_pages = intval( $query->max_num_pages );
} else {
    $total_pages = 0;
}

if ( $query instanceof WP_Query ) {
    wp_reset_postdata();
}

if ( $total_pages > 1 ) {
    $pagination_args = array(
        'base'      => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
        'format'    => '?paged=%#%',
        'current'   => max( 1, intval( $paged ) ),
        'total'     => $total_pages,
        'prev_text' => __( '« Précédent', 'notation-jlg' ),
        'next_text' => __( 'Suivant »', 'notation-jlg' ),
        'add_args'  => array(
            'orderby' => $current_orderby,
            'order'   => $current_order,
        ),
    );

    if ( $current_cat_filter > 0 ) {
        $pagination_args['add_args']['cat_filter'] = $current_cat_filter;
    }

    if ( $current_letter_filter !== '' ) {
        $pagination_args['add_args']['letter_filter'] = $current_letter_filter;
    }

    if ( $current_genre_filter !== '' ) {
        $pagination_args['add_args']['genre_filter'] = $current_genre_filter;
    }

    $pagination_links = paginate_links( $pagination_args );

    if ( ! empty( $pagination_links ) && ! empty( $table_id ) ) {
        $pagination_links = preg_replace(
            '/href="([^"]+)"/i',
            'href="$1#' . $table_id . '"',
            $pagination_links
        );
    }

    if ( ! empty( $pagination_links ) ) {
        echo '<nav class="jlg-pagination">' . $pagination_links . '</nav>';
    }
}

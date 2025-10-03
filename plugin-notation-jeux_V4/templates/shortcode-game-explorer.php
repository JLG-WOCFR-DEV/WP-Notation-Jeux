<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$container_id = isset( $atts['id'] ) ? sanitize_html_class( $atts['id'] ) : 'jlg-game-explorer-' . uniqid();
if ( $container_id === '' ) {
    $container_id = 'jlg-game-explorer-' . uniqid();
}

$columns              = isset( $atts['columns'] ) ? (int) $atts['columns'] : 3;
$columns              = max( 1, min( $columns, 4 ) );
$filters_enabled      = is_array( $filters_enabled ) ? $filters_enabled : array();
$current_filters      = is_array( $current_filters ) ? $current_filters : array();
$letters              = is_array( $letters ) ? $letters : array();
$sort_options         = is_array( $sort_options ) ? $sort_options : array();
$categories_list      = is_array( $categories_list ) ? $categories_list : array();
$platforms_list       = is_array( $platforms_list ) ? $platforms_list : array();
$availability_options = is_array( $availability_options ) ? $availability_options : array();
$total_items          = isset( $total_items ) ? (int) $total_items : 0;
$sort_key             = isset( $sort_key ) ? $sort_key : 'date';
$sort_order           = isset( $sort_order ) ? $sort_order : 'DESC';
$pagination           = is_array( $pagination ) ? $pagination : array(
	'current' => 1,
	'total'   => 0,
);
$config_payload       = is_array( $config_payload ) ? $config_payload : array();
$request_prefix       = isset( $request_prefix ) ? (string) $request_prefix : '';
$config_json          = wp_json_encode( $config_payload );
if ( $config_json === false ) {
    $config_json = '{}';
}

$has_category_filter     = ! empty( $filters_enabled['category'] ) && ! empty( $categories_list );
$has_platform_filter     = ! empty( $filters_enabled['platform'] ) && ! empty( $platforms_list );
$has_availability_filter = ! empty( $filters_enabled['availability'] );
$has_search_filter       = ! empty( $filters_enabled['search'] );
$has_filters             = $has_category_filter || $has_platform_filter || $has_availability_filter || $has_search_filter;
$letter_active           = isset( $current_filters['letter'] ) ? $current_filters['letter'] : '';
$category_active         = isset( $current_filters['category'] ) ? $current_filters['category'] : '';
$platform_active         = isset( $current_filters['platform'] ) ? $current_filters['platform'] : '';
$availability_active     = isset( $current_filters['availability'] ) ? $current_filters['availability'] : '';
$search_active           = isset( $current_filters['search'] ) ? $current_filters['search'] : '';
$request_keys            = is_array( $request_keys ) ? $request_keys : array();
$namespaced_keys         = array(
    'orderby'      => isset( $request_keys['orderby'] ) ? $request_keys['orderby'] : 'orderby',
    'order'        => isset( $request_keys['order'] ) ? $request_keys['order'] : 'order',
    'letter'       => isset( $request_keys['letter'] ) ? $request_keys['letter'] : 'letter',
    'category'     => isset( $request_keys['category'] ) ? $request_keys['category'] : 'category',
    'platform'     => isset( $request_keys['platform'] ) ? $request_keys['platform'] : 'platform',
    'availability' => isset( $request_keys['availability'] ) ? $request_keys['availability'] : 'availability',
    'search'       => isset( $request_keys['search'] ) ? $request_keys['search'] : 'search',
    'paged'        => isset( $request_keys['paged'] ) ? $request_keys['paged'] : 'paged',
);

$active_query_params = array();

if ( $sort_key !== '' ) {
    $active_query_params[ $namespaced_keys['orderby'] ] = $sort_key . '|' . $sort_order;
}

if ( $sort_order !== '' ) {
    $active_query_params[ $namespaced_keys['order'] ] = $sort_order;
}

$filter_values = array(
    'letter'       => $letter_active,
    'category'     => $category_active,
    'platform'     => $platform_active,
    'availability' => $availability_active,
    'search'       => $search_active,
);

foreach ( $filter_values as $filter_key => $filter_value ) {
    if ( $filter_value === '' || ! isset( $namespaced_keys[ $filter_key ] ) ) {
        continue;
    }

    $active_query_params[ $namespaced_keys[ $filter_key ] ] = (string) $filter_value;
}

if ( isset( $pagination['current'] ) && (int) $pagination['current'] > 1 && isset( $namespaced_keys['paged'] ) ) {
    $active_query_params[ $namespaced_keys['paged'] ] = (string) (int) $pagination['current'];
}

$prepare_hidden_params = static function( array $exclude = array(), array $overrides = array() ) use ( $active_query_params ) {
    $params = $active_query_params;

    foreach ( $exclude as $exclude_key ) {
        unset( $params[ $exclude_key ] );
    }

    foreach ( $overrides as $key => $value ) {
        if ( $value === null ) {
            unset( $params[ $key ] );
            continue;
        }

        $params[ $key ] = (string) $value;
    }

    return $params;
};

$render_hidden_inputs = static function( array $params ) {
    foreach ( $params as $name => $value ) {
        $value_string = (string) $value;

        if ( $value_string === '' ) {
            continue;
        }

        printf(
            '<input type="hidden" name="%1$s" value="%2$s">',
            esc_attr( $name ),
            esc_attr( $value_string )
        );
    }
};

$reset_url = remove_query_arg( array_values( $namespaced_keys ) );
?>

<div
    id="<?php echo esc_attr( $container_id ); ?>"
    class="jlg-game-explorer jlg-ge-cols-<?php echo esc_attr( $columns ); ?>"
    data-columns="<?php echo esc_attr( $columns ); ?>"
    data-config="<?php echo esc_attr( $config_json ); ?>"
    data-posts-per-page="<?php echo esc_attr( $atts['posts_per_page'] ); ?>"
    data-total-items="<?php echo esc_attr( $total_items ); ?>"
    data-request-prefix="<?php echo esc_attr( $request_prefix ); ?>"
>
    <div class="jlg-ge-toolbar">
        <div class="jlg-ge-toolbar__left">
            <?php if ( ! empty( $filters_enabled['letter'] ) && ! empty( $letters ) ) : ?>
                <nav class="jlg-ge-letter-nav" aria-label="<?php esc_attr_e( 'Filtrer par lettre', 'notation-jlg' ); ?>">
                    <form method="get" class="jlg-ge-letter-nav__form">
                        <?php
                        $letter_hidden_inputs = $prepare_hidden_params(
                            array(
                                $namespaced_keys['letter'],
                                $namespaced_keys['paged'],
                            )
                        );
                        $render_hidden_inputs( $letter_hidden_inputs );
                        ?>
                        <ul>
                            <li>
                                <?php
                                $all_letters_classes = array();
                                if ( $letter_active === '' ) {
                                    $all_letters_classes[] = 'is-active';
                                }
                                ?>
                                <button
                                    type="submit"
                                    class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $all_letters_classes ) ) ); ?>"
                                    data-letter=""
                                    name="<?php echo esc_attr( $namespaced_keys['letter'] ); ?>"
                                    value=""
                                    aria-pressed="<?php echo esc_attr( $letter_active === '' ? 'true' : 'false' ); ?>"
                                >
                                    <?php esc_html_e( 'Tous', 'notation-jlg' ); ?>
                                </button>
                            </li>
                            <?php
                            foreach ( $letters as $letter_item ) :
                                $value     = isset( $letter_item['value'] ) ? $letter_item['value'] : '';
                                $enabled   = ! empty( $letter_item['enabled'] );
                                $is_active = ( $value !== '' && $value === $letter_active );
                                ?>
                                <li>
                                    <?php
                                    $letter_button_classes = array();
                                    if ( $is_active ) {
                                        $letter_button_classes[] = 'is-active';
                                    }
                                    ?>
                                    <button
                                        type="submit"
                                        data-letter="<?php echo esc_attr( $value ); ?>"
                                        class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $letter_button_classes ) ) ); ?>"
                                        name="<?php echo esc_attr( $namespaced_keys['letter'] ); ?>"
                                        value="<?php echo esc_attr( $value ); ?>"
                                        <?php disabled( ! $enabled ); ?>
                                        aria-pressed="<?php echo esc_attr( $is_active ? 'true' : 'false' ); ?>"
                                    >
                                        <?php echo esc_html( $letter_item['label'] ?? $value ); ?>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </form>
                </nav>
            <?php endif; ?>
        </div>
        <div class="jlg-ge-toolbar__right">
            <div class="jlg-ge-sort">
                <form method="get" class="jlg-ge-sort__form">
                    <?php
                    $sort_hidden_inputs = $prepare_hidden_params(
                        array(
                            $namespaced_keys['orderby'],
                            $namespaced_keys['order'],
                            $namespaced_keys['paged'],
                        )
                    );
                    $render_hidden_inputs( $sort_hidden_inputs );
                    ?>
                    <label for="<?php echo esc_attr( $container_id ); ?>-sort">
                        <?php esc_html_e( 'Trier par', 'notation-jlg' ); ?>
                    </label>
                    <select
                        id="<?php echo esc_attr( $container_id ); ?>-sort"
                        name="<?php echo esc_attr( $namespaced_keys['orderby'] ); ?>"
                        data-role="sort"
                    >
                        <?php
                        foreach ( $sort_options as $option ) :
                            $value          = isset( $option['value'] ) ? $option['value'] : '';
                            $option_orderby = isset( $option['orderby'] ) ? $option['orderby'] : '';
                            $option_order   = isset( $option['order'] ) ? $option['order'] : '';
                            $selected       = ( $option_orderby === $sort_key && $option_order === $sort_order );
                            ?>
                            <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $selected ); ?>>
                                <?php echo esc_html( $option['label'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="jlg-ge-sort__submit">
                        <?php esc_html_e( 'Appliquer', 'notation-jlg' ); ?>
                    </button>
                </form>
            </div>
            <div class="jlg-ge-count">
                <?php
                $formatted_total_items = number_format_i18n( $total_items );

                printf(
                    esc_html( _n( '%s jeu', '%s jeux', $total_items, 'notation-jlg' ) ),
                    $formatted_total_items
                );
                ?>
            </div>
        </div>
    </div>

    <?php if ( $has_filters ) : ?>
        <?php $filters_panel_id = $container_id . '-filters'; ?>
        <div class="jlg-ge-filters-wrapper" data-role="filters-wrapper">
            <button
                type="button"
                class="jlg-ge-filters-toggle"
                data-role="filters-toggle"
                aria-expanded="true"
                aria-controls="<?php echo esc_attr( $filters_panel_id ); ?>"
            >
                <span class="jlg-ge-filters-toggle__label"><?php esc_html_e( 'Filtres', 'notation-jlg' ); ?></span>
            </button>
            <div class="jlg-ge-filters-backdrop" data-role="filters-backdrop" aria-hidden="true"></div>
            <div
                class="jlg-ge-filters-panel"
                id="<?php echo esc_attr( $filters_panel_id ); ?>"
                data-role="filters-panel"
                aria-hidden="false"
            >
                <div class="jlg-ge-filters" data-role="filters">
                    <form method="get" class="jlg-ge-filters__form" data-role="filters-form">
                        <?php
                        $filters_hidden_inputs = $prepare_hidden_params(
                            array(
                                $namespaced_keys['category'],
                                $namespaced_keys['platform'],
                                $namespaced_keys['availability'],
                                $namespaced_keys['search'],
                                $namespaced_keys['paged'],
                            )
                        );
                        $render_hidden_inputs( $filters_hidden_inputs );
                        ?>
                        <?php if ( $has_category_filter ) : ?>
                            <label for="<?php echo esc_attr( $container_id ); ?>-category" class="screen-reader-text">
                                <?php esc_html_e( 'Filtrer par catégorie', 'notation-jlg' ); ?>
                            </label>
                            <select
                                id="<?php echo esc_attr( $container_id ); ?>-category"
                                name="<?php echo esc_attr( $namespaced_keys['category'] ); ?>"
                                data-role="category"
                            >
                                <option value="">
                                    <?php esc_html_e( 'Toutes les catégories', 'notation-jlg' ); ?>
                                </option>
                                <?php
                                foreach ( $categories_list as $category ) :
                                    $value = isset( $category['value'] ) ? (string) $category['value'] : '';
                                    $label = isset( $category['label'] ) ? $category['label'] : $value;
                                    ?>
                                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $category_active, $value ); ?>>
                                        <?php echo esc_html( $label ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>

                        <?php if ( $has_platform_filter ) : ?>
                            <label for="<?php echo esc_attr( $container_id ); ?>-platform" class="screen-reader-text">
                                <?php esc_html_e( 'Filtrer par plateforme', 'notation-jlg' ); ?>
                            </label>
                            <select
                                id="<?php echo esc_attr( $container_id ); ?>-platform"
                                name="<?php echo esc_attr( $namespaced_keys['platform'] ); ?>"
                                data-role="platform"
                            >
                                <option value="">
                                    <?php esc_html_e( 'Toutes les plateformes', 'notation-jlg' ); ?>
                                </option>
                                <?php
                                foreach ( $platforms_list as $platform ) :
                                    $value = isset( $platform['value'] ) ? (string) $platform['value'] : '';
                                    $label = isset( $platform['label'] ) ? $platform['label'] : $value;
                                    ?>
                                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $platform_active, $value ); ?>>
                                        <?php echo esc_html( $label ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>

                        <?php if ( $has_availability_filter ) : ?>
                            <label for="<?php echo esc_attr( $container_id ); ?>-availability" class="screen-reader-text">
                                <?php esc_html_e( 'Filtrer par disponibilité', 'notation-jlg' ); ?>
                            </label>
                            <select
                                id="<?php echo esc_attr( $container_id ); ?>-availability"
                                name="<?php echo esc_attr( $namespaced_keys['availability'] ); ?>"
                                data-role="availability"
                            >
                                <option value="">
                                    <?php esc_html_e( 'Toutes les sorties', 'notation-jlg' ); ?>
                                </option>
                                <?php foreach ( $availability_options as $value => $label ) : ?>
                                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $availability_active, $value ); ?>>
                                        <?php echo esc_html( $label ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>

                        <?php if ( $has_search_filter ) : ?>
                            <div class="jlg-ge-search">
                                <label for="<?php echo esc_attr( $container_id ); ?>-search">
                                    <?php esc_html_e( 'Rechercher un jeu', 'notation-jlg' ); ?>
                                </label>
                                <input
                                    type="search"
                                    id="<?php echo esc_attr( $container_id ); ?>-search"
                                    name="<?php echo esc_attr( $namespaced_keys['search'] ); ?>"
                                    data-role="search"
                                    value="<?php echo esc_attr( $search_active ); ?>"
                                    placeholder="<?php echo esc_attr__( 'Rechercher…', 'notation-jlg' ); ?>"
                                >
                            </div>
                        <?php endif; ?>

                        <div class="jlg-ge-filters__actions">
                            <button type="submit" class="jlg-ge-filters__submit">
                                <?php esc_html_e( 'Appliquer les filtres', 'notation-jlg' ); ?>
                            </button>
                            <a class="jlg-ge-reset" data-role="reset" href="<?php echo esc_url( $reset_url ); ?>">
                                <?php esc_html_e( 'Réinitialiser', 'notation-jlg' ); ?>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div
        class="jlg-ge-results"
        data-role="results"
        role="status"
        aria-live="polite"
        aria-busy="false"
    >
        <?php
        echo \JLG\Notation\Frontend::get_template_html(
            'game-explorer-fragment',
            array(
                                'games'           => $games,
                                'message'         => isset( $message ) ? $message : '',
                                'pagination'      => $pagination,
                                'total_items'     => $total_items,
                                'current_filters' => $current_filters,
                                'request_keys'    => $request_keys,
                                'sort_key'        => $sort_key,
                                'sort_order'      => $sort_order,
                                'query_params'    => $active_query_params,
                        )
        );
        ?>
    </div>
</div>

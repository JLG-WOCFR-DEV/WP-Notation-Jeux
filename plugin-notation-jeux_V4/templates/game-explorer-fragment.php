<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$games       = is_array( $games ) ? $games : array();
$message     = isset( $message ) ? $message : '';
$pagination  = is_array( $pagination ) ? $pagination : array(
        'current' => 1,
        'total'   => 0,
);
$total_items = isset( $total_items ) ? (int) $total_items : 0;
$score_position = isset( $score_position )
    ? JLG_Helpers::normalize_game_explorer_score_position( $score_position )
    : JLG_Helpers::normalize_game_explorer_score_position( '' );
$score_classes = array(
    'jlg-ge-card__score',
    'jlg-ge-card__score--' . sanitize_html_class( $score_position ),
);

if ( empty( $games ) ) {
    echo wp_kses_post( $message );
    return;
}
?>
<div class="jlg-ge-grid">
    <?php
    foreach ( $games as $game ) :
        $permalink           = isset( $game['permalink'] ) ? $game['permalink'] : '';
        $title               = isset( $game['title'] ) ? $game['title'] : '';
        $score_display       = isset( $game['score_display'] ) ? $game['score_display'] : '';
        $score_color         = isset( $game['score_color'] ) ? $game['score_color'] : '';
        $has_score           = isset( $game['has_score'] )
            ? (bool) $game['has_score']
            : ( isset( $game['score_value'] ) && is_numeric( $game['score_value'] ) );
        $cover_url           = isset( $game['cover_url'] ) ? $game['cover_url'] : '';
        $release_display     = isset( $game['release_display'] ) ? $game['release_display'] : '';
        $developer           = isset( $game['developer'] ) ? $game['developer'] : '';
        $publisher           = isset( $game['publisher'] ) ? $game['publisher'] : '';
        $platforms           = isset( $game['platforms'] ) && is_array( $game['platforms'] ) ? $game['platforms'] : array();
        $genre               = isset( $game['genre'] ) ? $game['genre'] : '';
        $availability_label  = isset( $game['availability_label'] ) ? $game['availability_label'] : '';
        $availability_status = isset( $game['availability'] ) ? $game['availability'] : '';
        $excerpt             = isset( $game['excerpt'] ) ? $game['excerpt'] : '';
        ?>
        <article class="jlg-ge-card" data-post-id="<?php echo esc_attr( $game['post_id'] ); ?>">
            <a class="jlg-ge-card__media" href="<?php echo esc_url( $permalink ); ?>">
                <?php if ( $cover_url ) : ?>
                    <img src="<?php echo esc_url( $cover_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
                <?php else : ?>
                    <span class="jlg-ge-card__placeholder"><?php esc_html_e( 'Visuel indisponible', 'notation-jlg' ); ?></span>
                <?php endif; ?>
                <?php if ( $score_display !== '' ) : ?>
                    <span class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $score_classes ) ) ); ?>" style="--jlg-ge-score-color: <?php echo esc_attr( $score_color ); ?>;">
                        <?php echo esc_html( $score_display ); ?>
                        <?php if ( $has_score ) : ?>
                            <span class="jlg-ge-card__score-outof">/10</span>
                        <?php endif; ?>
                    </span>
                <?php endif; ?>
            </a>
            <div class="jlg-ge-card__body">
                <h3 class="jlg-ge-card__title">
                    <a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
                </h3>
                <?php if ( $excerpt !== '' ) : ?>
                    <p class="jlg-ge-card__excerpt"><?php echo esc_html( $excerpt ); ?></p>
                <?php endif; ?>
                <dl class="jlg-ge-card__meta">
                    <?php if ( $release_display !== '' ) : ?>
                        <div class="jlg-ge-card__meta-row">
                            <dt><?php esc_html_e( 'Sortie', 'notation-jlg' ); ?></dt>
                            <dd><?php echo esc_html( $release_display ); ?></dd>
                        </div>
                    <?php endif; ?>
                    <?php if ( $developer !== '' ) : ?>
                        <div class="jlg-ge-card__meta-row">
                            <dt><?php esc_html_e( 'Développeur', 'notation-jlg' ); ?></dt>
                            <dd><?php echo esc_html( $developer ); ?></dd>
                        </div>
                    <?php endif; ?>
                    <?php if ( $publisher !== '' ) : ?>
                        <div class="jlg-ge-card__meta-row">
                            <dt><?php esc_html_e( 'Éditeur', 'notation-jlg' ); ?></dt>
                            <dd><?php echo esc_html( $publisher ); ?></dd>
                        </div>
                    <?php endif; ?>
                </dl>
                <div class="jlg-ge-card__badges">
                    <?php foreach ( array_slice( $platforms, 0, 4 ) as $platform_label ) : ?>
                        <span class="jlg-ge-badge jlg-ge-badge--platform"><?php echo esc_html( $platform_label ); ?></span>
                    <?php endforeach; ?>
                    <?php if ( $genre !== '' ) : ?>
                        <span class="jlg-ge-badge jlg-ge-badge--genre"><?php echo esc_html( $genre ); ?></span>
                    <?php endif; ?>
                    <?php if ( $availability_label !== '' ) : ?>
                        <span class="jlg-ge-badge jlg-ge-badge--availability jlg-ge-badge--availability-<?php echo esc_attr( $availability_status ); ?>">
                            <?php echo esc_html( $availability_label ); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
</div>
<?php
$current_page = isset( $pagination['current'] ) ? (int) $pagination['current'] : 1;
$total_pages  = isset( $pagination['total'] ) ? (int) $pagination['total'] : 0;

if ( $total_pages > 1 ) :
    $prev_page = max( 1, $current_page - 1 );
    $next_page = min( $total_pages, $current_page + 1 );
    ?>
    <nav class="jlg-ge-pagination" data-role="pagination" aria-label="<?php esc_attr_e( 'Navigation des pages du Game Explorer', 'notation-jlg' ); ?>">
        <button type="button" class="jlg-ge-page jlg-ge-page--prev" data-page="<?php echo esc_attr( $prev_page ); ?>" <?php disabled( $current_page <= 1 ); ?>>
            <?php esc_html_e( 'Précédent', 'notation-jlg' ); ?>
        </button>
        <ul>
            <?php
            for ( $page = 1; $page <= $total_pages; $page++ ) :
                $is_active = ( $page === $current_page );
                ?>
                <li>
                    <button type="button" data-page="<?php echo esc_attr( $page ); ?>" class="<?php echo esc_attr( $is_active ? 'is-active' : '' ); ?>">
                        <?php echo esc_html( $page ); ?>
                    </button>
                </li>
            <?php endfor; ?>
        </ul>
        <button type="button" class="jlg-ge-page jlg-ge-page--next" data-page="<?php echo esc_attr( $next_page ); ?>" <?php disabled( $current_page >= $total_pages ); ?>>
            <?php esc_html_e( 'Suivant', 'notation-jlg' ); ?>
        </button>
    </nav>
<?php endif; ?>

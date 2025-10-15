<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$title                 = isset( $title ) ? (string) $title : '';
$entries               = isset( $entries ) && is_array( $entries ) ? $entries : array();
$has_entries           = ! empty( $has_entries ) && ! empty( $entries );
$show_best_badge       = ! empty( $show_best_badge );
$highlight_badge_label = isset( $highlight_badge_label ) ? (string) $highlight_badge_label : '';
$empty_message         = isset( $empty_message ) ? (string) $empty_message : '';
$active_index          = isset( $active_index ) ? (int) $active_index : 0;

if ( function_exists( 'mb_substr' ) ) {
    $empty_message = mb_substr( $empty_message, 0, 180 );
} else {
    $empty_message = substr( $empty_message, 0, 180 );
}
$empty_message = trim( $empty_message );

$component_id = function_exists( 'wp_unique_id' ) ? wp_unique_id( 'jlg-platform-breakdown-' ) : 'jlg-platform-breakdown-' . uniqid();
$tablist_id   = $component_id . '-tabs';
$has_badge    = $show_best_badge && $has_entries && $highlight_badge_label !== '';
?>

<div class="jlg-platform-breakdown" id="<?php echo esc_attr( $component_id ); ?>" data-has-badge="<?php echo esc_attr( $has_badge ? 'true' : 'false' ); ?>">
    <?php if ( $title !== '' ) : ?>
    <header class="jlg-platform-breakdown__header">
        <h2 class="jlg-platform-breakdown__title"><?php echo esc_html( $title ); ?></h2>
    </header>
    <?php endif; ?>

    <?php if ( $has_entries ) : ?>
        <div class="jlg-platform-breakdown__body">
            <div class="jlg-platform-breakdown__tablist" role="tablist" aria-label="<?php echo esc_attr__( 'Comparer les plateformes', 'notation-jlg' ); ?>" id="<?php echo esc_attr( $tablist_id ); ?>">
                <?php
                foreach ( $entries as $index => $entry ) :
                    $entry_id     = isset( $entry['id'] ) ? sanitize_html_class( (string) $entry['id'] ) : 'entry-' . $index;
                    $tab_id       = $component_id . '-tab-' . $entry_id;
                    $panel_id     = $component_id . '-panel-' . $entry_id;
                    $label        = isset( $entry['label'] ) ? (string) $entry['label'] : '';
                    $is_active    = ! empty( $entry['is_active'] );
                    $is_highlight = ! empty( $entry['is_highlighted'] );
                    ?>
                    <button
                        type="button"
                        id="<?php echo esc_attr( $tab_id ); ?>"
                        class="jlg-platform-breakdown__tab<?php echo $is_active ? ' is-active' : ''; ?>"
                        role="tab"
                        aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
                        aria-controls="<?php echo esc_attr( $panel_id ); ?>"
                        tabindex="<?php echo $is_active ? '0' : '-1'; ?>"
                        data-target-panel="<?php echo esc_attr( $panel_id ); ?>"
                    >
                        <span class="jlg-platform-breakdown__tab-label"><?php echo esc_html( $label ); ?></span>
                        <?php if ( $is_highlight && $highlight_badge_label !== '' ) : ?>
                            <span class="jlg-platform-breakdown__tab-badge"><?php echo esc_html( $highlight_badge_label ); ?></span>
                        <?php endif; ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="jlg-platform-breakdown__panels">
                <?php
                foreach ( $entries as $index => $entry ) :
                    $entry_id     = isset( $entry['id'] ) ? sanitize_html_class( (string) $entry['id'] ) : 'entry-' . $index;
                    $tab_id       = $component_id . '-tab-' . $entry_id;
                    $panel_id     = $component_id . '-panel-' . $entry_id;
                    $label        = isset( $entry['label'] ) ? (string) $entry['label'] : '';
                    $performance  = isset( $entry['performance'] ) ? (string) $entry['performance'] : '';
                    $comment      = isset( $entry['comment'] ) ? (string) $entry['comment'] : '';
                    $is_active    = ! empty( $entry['is_active'] );
                    $is_highlight = ! empty( $entry['is_highlighted'] );
                    ?>
                    <section
                        id="<?php echo esc_attr( $panel_id ); ?>"
                        class="jlg-platform-breakdown__panel<?php echo $is_active ? ' is-active' : ''; ?>"
                        role="tabpanel"
                        aria-labelledby="<?php echo esc_attr( $tab_id ); ?>"
                        <?php echo $is_active ? '' : ' hidden'; ?>
                    >
                        <header class="jlg-platform-breakdown__panel-header">
                            <h3 class="jlg-platform-breakdown__panel-title"><?php echo esc_html( $label ); ?></h3>
                            <?php if ( $is_highlight && $highlight_badge_label !== '' ) : ?>
                                <span class="jlg-platform-breakdown__panel-badge"><?php echo esc_html( $highlight_badge_label ); ?></span>
                            <?php endif; ?>
                        </header>
                        <?php if ( $performance !== '' ) : ?>
                            <p class="jlg-platform-breakdown__metric"><?php echo wp_kses_post( $performance ); ?></p>
                        <?php endif; ?>
                        <?php if ( $comment !== '' ) : ?>
                            <p class="jlg-platform-breakdown__comment"><?php echo wp_kses_post( $comment ); ?></p>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>
            </div>
        </div>
    <?php elseif ( $empty_message !== '' ) : ?>
        <p class="jlg-platform-breakdown__empty" role="status"><?php echo esc_html( $empty_message ); ?></p>
    <?php endif; ?>
</div>

<?php
/**
 * Template pour la notation express.
 *
 * Variables attendues :
 * - $score : tableau contenant les clés `has_score`, `display`, `max_display`, `ratio`, `aria_label`.
 * - $badge : tableau contenant `visible` et `label`.
 * - $cta   : tableau contenant `visible`, `label`, `url`, `rel`, `target`.
 * - $extra_classes : liste de classes supplémentaires à appliquer sur le wrapper.
 * - $is_placeholder : booléen indiquant si la note doit être remplacée par un rappel de configuration.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$score_data = array();
if ( isset( $score ) && is_array( $score ) ) {
    $score_data = $score;
} elseif ( isset( $args['score'] ) && is_array( $args['score'] ) ) {
    $score_data = $args['score'];
}
$has_score        = ! empty( $score_data['has_score'] );
$score_display    = isset( $score_data['display'] ) ? (string) $score_data['display'] : '';
$score_max_label  = isset( $score_data['max_display'] ) ? (string) $score_data['max_display'] : '';
$score_ratio      = isset( $score_data['ratio'] ) ? (float) $score_data['ratio'] : 0.0;
$score_ratio      = max( 0.0, min( 1.0, $score_ratio ) );
$score_aria_label = isset( $score_data['aria_label'] ) ? (string) $score_data['aria_label'] : '';

$badge_data = array();
if ( isset( $badge ) && is_array( $badge ) ) {
    $badge_data = $badge;
} elseif ( isset( $args['badge'] ) && is_array( $args['badge'] ) ) {
    $badge_data = $args['badge'];
}
$badge_label   = isset( $badge_data['label'] ) ? (string) $badge_data['label'] : '';
$badge_visible = ! empty( $badge_data['visible'] ) && $badge_label !== '';

$cta_data = array();
if ( isset( $cta ) && is_array( $cta ) ) {
    $cta_data = $cta;
} elseif ( isset( $args['cta'] ) && is_array( $args['cta'] ) ) {
    $cta_data = $args['cta'];
}
$cta_visible = ! empty( $cta_data['visible'] );
$cta_label   = isset( $cta_data['label'] ) ? (string) $cta_data['label'] : '';
$cta_url     = isset( $cta_data['url'] ) ? (string) $cta_data['url'] : '';
$cta_rel     = isset( $cta_data['rel'] ) ? (string) $cta_data['rel'] : '';
$cta_target  = isset( $cta_data['target'] ) ? (string) $cta_data['target'] : '';

$extra_classes_string = isset( $extra_classes ) ? (string) $extra_classes : '';
$extra_classes_list   = $extra_classes_string !== '' ? preg_split( '/\s+/', $extra_classes_string ) : array();
$extra_classes_list   = is_array( $extra_classes_list ) ? $extra_classes_list : array();

$wrapper_classes = array( 'notation-jlg-express-rating' );

if ( $has_score ) {
    $wrapper_classes[] = 'notation-jlg-express-rating--is-ready';
}

if ( $badge_visible ) {
    $wrapper_classes[] = 'notation-jlg-express-rating--has-badge';
}

if ( $cta_visible ) {
    $wrapper_classes[] = 'notation-jlg-express-rating--has-cta';
}

foreach ( $extra_classes_list as $class_name ) {
    $class_name = sanitize_html_class( (string) $class_name );
    if ( $class_name !== '' ) {
        $wrapper_classes[] = $class_name;
    }
}

$wrapper_classes = array_unique( array_filter( $wrapper_classes ) );
$wrapper_class   = implode( ' ', $wrapper_classes );

$progress_style  = $has_score ? sprintf( '--jlg-express-progress:%s%%;', number_format( $score_ratio * 100, 2, '.', '' ) ) : '';
$style_attribute = $progress_style !== '' ? ' style="' . esc_attr( $progress_style ) . '"' : '';

$cta_target_attribute = $cta_target !== '' ? ' target="' . esc_attr( $cta_target ) . '"' : '';
$cta_rel_attribute    = $cta_rel !== '' ? ' rel="' . esc_attr( $cta_rel ) . '"' : '';
$score_aria_attribute = $score_aria_label !== '' ? ' aria-label="' . esc_attr( $score_aria_label ) . '"' : '';

?>
<div class="<?php echo esc_attr( $wrapper_class ); ?>"<?php echo $style_attribute; ?> data-score-ready="<?php echo $has_score ? '1' : '0'; ?>">
    <div class="notation-jlg-express-rating__header">
        <div class="notation-jlg-express-rating__score"<?php echo $score_aria_attribute; ?>>
            <?php if ( $has_score ) : ?>
                <span class="notation-jlg-express-rating__value"><?php echo esc_html( $score_display ); ?></span>
                <?php if ( $score_max_label !== '' ) : ?>
                    <span class="notation-jlg-express-rating__separator" aria-hidden="true">/</span>
                    <span class="notation-jlg-express-rating__max"><?php echo esc_html( $score_max_label ); ?></span>
                <?php endif; ?>
                <span class="notation-jlg-express-rating__gauge" aria-hidden="true">
                    <span class="notation-jlg-express-rating__gauge-fill"></span>
                </span>
            <?php else : ?>
                <span class="notation-jlg-express-rating__placeholder">
                    <?php esc_html_e( 'Saisissez une note pour activer la prévisualisation.', 'notation-jlg' ); ?>
                </span>
            <?php endif; ?>
        </div>
        <?php if ( $badge_visible ) : ?>
            <div class="notation-jlg-express-rating__badge" role="status">
                <span class="notation-jlg-express-rating__badge-label"><?php echo esc_html( $badge_label ); ?></span>
            </div>
        <?php endif; ?>
    </div>
    <?php if ( $cta_visible && $cta_label !== '' && $cta_url !== '' ) : ?>
        <div class="notation-jlg-express-rating__actions">
            <a class="notation-jlg-express-rating__cta" href="<?php echo esc_url( $cta_url ); ?>"<?php echo $cta_target_attribute; ?><?php echo $cta_rel_attribute; ?>>
                <?php echo esc_html( $cta_label ); ?>
            </a>
        </div>
    <?php endif; ?>
</div>

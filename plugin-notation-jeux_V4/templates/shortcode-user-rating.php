<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<?php
$is_interaction_disabled = $has_voted;
?>
<div class="jlg-user-rating-block
<?php
if ( $has_voted ) {
        echo esc_attr( 'has-voted' );}
?>
">
    <div class="jlg-user-rating-title"><?php esc_html_e( 'Votre avis nous intéresse !', 'notation-jlg' ); ?></div>
    <div
        class="jlg-user-rating-stars"
        data-post-id="<?php echo esc_attr( $post_id ); ?>"
        role="radiogroup"
        aria-label="<?php echo esc_attr__( 'Choisissez une note', 'notation-jlg' ); ?>"
        <?php echo $is_interaction_disabled ? 'aria-disabled="true"' : ''; ?>
    >
        <?php for ( $i = 1; $i <= 5; $i++ ) :
            $is_selected      = $has_voted && $i <= $user_vote;
            $is_checked_radio = $has_voted && intval( $user_vote ) === $i;
            ?>
            <button
                type="button"
                class="jlg-user-star<?php echo $is_selected ? ' selected' : ''; ?>"
                data-value="<?php echo esc_attr( $i ); ?>"
                role="radio"
                aria-checked="<?php echo $is_checked_radio ? 'true' : 'false'; ?>"
                <?php echo $is_interaction_disabled ? 'aria-disabled="true" disabled="disabled"' : ''; ?>
                aria-label="<?php
                    /* translators: 1: Selected rating value. 2: Maximum possible rating. */
                    echo esc_attr( sprintf( __( 'Attribuer %1$s sur %2$s', 'notation-jlg' ), number_format_i18n( $i ), number_format_i18n( 5 ) ) );
                ?>"
            >★</button>
        <?php endfor; ?>
    </div>
    <div class="jlg-user-rating-summary">
        <?php
        /* translators: Abbreviation meaning that the user rating is not available. */
        $average_display = ! empty( $avg_rating ) ? number_format_i18n( floatval( $avg_rating ), 2 ) : __( 'N/A', 'notation-jlg' );
        $votes_display   = ! empty( $count ) ? intval( $count ) : 0;
        /* translators: 1: Average user rating value. 2: Maximum possible rating. 3: Number of user votes. */
        $summary_template = __(
            'Note moyenne : <strong class="jlg-user-rating-avg-value" aria-live="polite" aria-atomic="true">%1$s</strong> sur %2$s (<span class="jlg-user-rating-count-value">%3$s</span> votes)',
            'notation-jlg'
        );

        echo wp_kses(
            sprintf(
                $summary_template,
                esc_html( $average_display ),
                esc_html( number_format_i18n( 5 ) ),
                esc_html( number_format_i18n( $votes_display ) )
            ),
            array(
                'strong' => array(
                    'class'       => array(),
                    'aria-live'   => array(),
                    'aria-atomic' => array(),
                ),
                'span'   => array( 'class' => array() ),
            )
        );
        ?>
    </div>
    <div class="jlg-rating-message" role="status" aria-live="polite" aria-atomic="true">
    <?php
    if ( $has_voted ) {
                esc_html_e( 'Merci pour votre vote !', 'notation-jlg' );}
        ?>
    </div>
</div>

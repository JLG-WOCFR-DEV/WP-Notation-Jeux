<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="jlg-user-rating-block 
<?php
if ( $has_voted ) {
	echo esc_attr( 'has-voted' );}
?>
">
    <div class="jlg-user-rating-title"><?php esc_html_e( 'Votre avis nous intéresse !', 'notation-jlg' ); ?></div>
    <div class="jlg-user-rating-stars" role="radiogroup" aria-label="<?php esc_attr_e( 'Sélectionnez une note', 'notation-jlg' ); ?>" data-post-id="<?php echo esc_attr( $post_id ); ?>">
        <?php for ( $i = 1; $i <= 5; $i++ ) :
            $is_selected     = ( $has_voted && $i <= $user_vote );
            $is_aria_checked = ( $has_voted && $i === $user_vote );
            ?>
            <button
                type="button"
                role="radio"
                class="jlg-user-star<?php echo $is_selected ? esc_attr( ' selected' ) : ''; ?>"
                data-value="<?php echo esc_attr( $i ); ?>"
                aria-checked="<?php echo $is_aria_checked ? 'true' : 'false'; ?>"
                aria-label="<?php echo esc_attr( sprintf( __( 'Donner %1$s sur 5', 'notation-jlg' ), number_format_i18n( $i ) ) ); ?>"
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
            'Note moyenne : <strong class="jlg-user-rating-avg-value">%1$s</strong> sur %2$s (<span class="jlg-user-rating-count-value">%3$s</span> votes)',
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
                'strong' => array( 'class' => array() ),
                'span'   => array( 'class' => array() ),
            )
        );
        ?>
    </div>
    <div class="jlg-rating-message" aria-live="polite">
    <?php
    if ( $has_voted ) {
		esc_html_e( 'Merci pour votre vote !', 'notation-jlg' );}
	?>
    </div>
</div>

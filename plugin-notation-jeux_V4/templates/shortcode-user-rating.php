<?php
if ( ! defined( 'ABSPATH' ) ) {
        exit;
}
?>

<?php
$requires_login_option = ! empty( $options['user_rating_requires_login'] );
$is_logged_in          = isset( $is_logged_in ) ? (bool) $is_logged_in : ( function_exists( 'is_user_logged_in' ) ? is_user_logged_in() : false );
$login_required        = isset( $login_required ) ? (bool) $login_required : ( $requires_login_option && ! $is_logged_in );
$login_url             = isset( $login_url ) && is_string( $login_url ) ? trim( $login_url ) : '';

if ( $login_required && $login_url === '' && function_exists( 'wp_login_url' ) ) {
        $permalink = function_exists( 'get_permalink' ) ? get_permalink( $post_id ) : '';
        $login_url = wp_login_url( $permalink );
}

$login_message_text = __( 'Connectez-vous pour voter.', 'notation-jlg' );
$login_link_label   = __( 'Se connecter', 'notation-jlg' );
$login_message_html = '';

if ( $login_required ) {
        if ( $login_url !== '' ) {
                $login_message_html = sprintf(
                        '<span class="jlg-user-rating-login-text">%s</span> <a class="jlg-user-rating-login-link" href="%s">%s</a>',
                        esc_html( $login_message_text ),
                        esc_url( $login_url ),
                        esc_html( $login_link_label )
                );
        } else {
                $login_message_html = esc_html( $login_message_text );
        }
}

$is_interaction_disabled = $has_voted || $login_required;

if ( ! is_array( $rating_breakdown ) ) {
        $rating_breakdown = array();
}

$normalized_breakdown = array();

for ( $i = 1; $i <= 5; $i++ ) {
        $key   = array_key_exists( $i, $rating_breakdown ) ? $i : (string) $i;
        $value = isset( $rating_breakdown[ $key ] ) && is_numeric( $rating_breakdown[ $key ] ) ? intval( $rating_breakdown[ $key ] ) : 0;

        $normalized_breakdown[ $i ] = max( 0, $value );
}

$total_breakdown_votes  = array_sum( $normalized_breakdown );
$vote_singular_template = __( '%s vote', 'notation-jlg' );
$vote_plural_template   = __( '%s votes', 'notation-jlg' );
$progress_template      = __( '%1$s : %2$s (%3$s%%)', 'notation-jlg' );
$meter_max_value        = max( $total_breakdown_votes, 1 );
?>
<?php
$block_classes = array( 'jlg-user-rating-block' );

if ( $has_voted ) {
        $block_classes[] = 'has-voted';
}

if ( $login_required ) {
        $block_classes[] = 'requires-login';
}

$block_attributes = '';

if ( $login_required ) {
        $block_attributes .= ' data-requires-login="true"';
}

if ( $login_required && $login_url !== '' ) {
        $block_attributes .= ' data-login-url="' . esc_attr( esc_url( $login_url ) ) . '"';
}
?>
<div class="<?php echo esc_attr( implode( ' ', $block_classes ) ); ?>"<?php echo $block_attributes; ?>>
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
    <div
        class="jlg-user-rating-breakdown"
        role="group"
        aria-live="polite"
        aria-label="<?php echo esc_attr__( 'Répartition des votes', 'notation-jlg' ); ?>"
        data-vote-singular="<?php echo esc_attr( $vote_singular_template ); ?>"
        data-vote-plural="<?php echo esc_attr( $vote_plural_template ); ?>"
        data-progress-template="<?php echo esc_attr( $progress_template ); ?>"
        data-total-votes="<?php echo esc_attr( $total_breakdown_votes ); ?>"
    >
        <ul class="jlg-user-rating-breakdown-list" role="list">
            <?php for ( $star = 5; $star >= 1; $star-- ) :
                $count_for_star = isset( $normalized_breakdown[ $star ] ) ? $normalized_breakdown[ $star ] : 0;
                $percent_value  = $total_breakdown_votes > 0 ? ( $count_for_star / $total_breakdown_votes ) * 100 : 0;
                $percent_label  = $total_breakdown_votes > 0 ? number_format_i18n( $percent_value, 1 ) : number_format_i18n( 0 );
                $star_label     = sprintf( _n( '%s étoile', '%s étoiles', $star, 'notation-jlg' ), number_format_i18n( $star ) );
                $count_label    = sprintf( _n( '%s vote', '%s votes', $count_for_star, 'notation-jlg' ), number_format_i18n( $count_for_star ) );
                ?>
                <li class="jlg-user-rating-breakdown-item" role="listitem" data-stars="<?php echo esc_attr( $star ); ?>">
                    <span class="jlg-user-rating-breakdown-label">
                        <span class="jlg-user-rating-breakdown-star"><?php echo esc_html( $star_label ); ?></span>
                        <span class="jlg-user-rating-breakdown-count" data-count="<?php echo esc_attr( $count_for_star ); ?>">
                            <?php echo esc_html( $count_label ); ?>
                        </span>
                    </span>
                    <div
                        class="jlg-user-rating-breakdown-meter"
                        role="meter"
                        aria-valuemin="0"
                        aria-valuemax="<?php echo esc_attr( $meter_max_value ); ?>"
                        aria-valuenow="<?php echo esc_attr( $count_for_star ); ?>"
                        aria-label="<?php echo esc_attr( sprintf( $progress_template, $star_label, $count_label, $percent_label ) ); ?>"
                        data-percent="<?php echo esc_attr( $percent_value ); ?>"
                        data-star-label="<?php echo esc_attr( $star_label ); ?>"
                    >
                        <span class="jlg-user-rating-breakdown-track">
                            <span class="jlg-user-rating-breakdown-fill" style="width: <?php echo esc_attr( $percent_value ); ?>%;"></span>
                        </span>
                    </div>
                </li>
            <?php endfor; ?>
        </ul>
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
    <?php
    $message_content = '';

    if ( $has_voted ) {
            $message_content = esc_html__( 'Merci pour votre vote !', 'notation-jlg' );
    } elseif ( $login_required && $login_message_html !== '' ) {
            $message_content = $login_message_html;
    }
    ?>
    <div class="jlg-rating-message" role="status" aria-live="polite" aria-atomic="true">
        <?php echo wp_kses_post( $message_content ); ?>
    </div>
</div>

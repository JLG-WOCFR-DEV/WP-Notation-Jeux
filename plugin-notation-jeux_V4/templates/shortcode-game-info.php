<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="jlg-game-info-box">
    <h3><?php echo esc_html( $titre ); ?></h3>
    <dl>
        <?php foreach ( $champs_a_afficher as $key => $data ) : ?>
            <dt><?php echo esc_html( $data['label'] ); ?></dt>
            <dd>
                <?php
                if ( is_array( $data['value'] ) ) {
                    $wrapper_class = $key === 'plateformes' ? 'platforms-list' : 'jlg-game-info-list';
                    echo '<div class="' . esc_attr( $wrapper_class ) . '">';
                    foreach ( $data['value'] as $item ) {
                        echo '<span>' . esc_html( $item ) . '</span>';
                    }
                    echo '</div>';
                } elseif ( $key === 'date_sortie' && $data['value'] !== '' ) {
                    echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $data['value'] ) ) );
                } else {
                    echo esc_html( $data['value'] );
                }
                ?>
            </dd>
        <?php endforeach; ?>
    </dl>
</div>

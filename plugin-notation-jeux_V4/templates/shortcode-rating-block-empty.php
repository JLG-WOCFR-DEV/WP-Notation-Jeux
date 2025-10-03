<?php
/**
 * Placeholder template displayed when no rating data is available.
 *
 * @package notation-jlg
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="jlg-rating-block-empty notice notice-info">
    <p>
        <?php
        echo esc_html__(
            'Aucune note n’est disponible pour le moment. Utilisez la metabox « Notation JLG » pour saisir les notes du test.',
            'notation-jlg'
        );
        ?>
    </p>
</div>

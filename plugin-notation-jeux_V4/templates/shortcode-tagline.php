<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$has_fr       = ! empty( $tagline_fr );
$has_en       = ! empty( $tagline_en );
$default_lang = $has_fr ? 'fr' : 'en';
?>

<div class="jlg-tagline-block">
    <?php if ( $has_fr && $has_en ) : ?>
        <div class="jlg-tagline-flags">
            <?php $is_fr_active = ( $default_lang === 'fr' ); ?>
            <button
                type="button"
                class="jlg-lang-flag<?php echo $is_fr_active ? ' active' : ''; ?>"
                data-lang="fr"
                aria-pressed="<?php echo $is_fr_active ? 'true' : 'false'; ?>"
                aria-label="<?php echo esc_attr__( 'Français', 'notation-jlg' ); ?>"
            >
                <img src="<?php echo esc_url( JLG_NOTATION_PLUGIN_URL . 'assets/flags/fr.svg' ); ?>" alt="">
            </button>
            <?php $is_en_active = ( $default_lang === 'en' ); ?>
            <button
                type="button"
                class="jlg-lang-flag<?php echo $is_en_active ? ' active' : ''; ?>"
                data-lang="en"
                aria-pressed="<?php echo $is_en_active ? 'true' : 'false'; ?>"
                aria-label="<?php echo esc_attr__( 'English', 'notation-jlg' ); ?>"
            >
                <img src="<?php echo esc_url( JLG_NOTATION_PLUGIN_URL . 'assets/flags/gb.svg' ); ?>" alt="">
            </button>
        </div>
    <?php endif; ?>

    <?php if ( $has_fr ) : ?>
        <div class="jlg-tagline-text" data-lang="fr"<?php echo $default_lang === 'fr' ? ' aria-hidden="false"' : ' hidden aria-hidden="true"'; ?>>
            "<?php echo wp_kses_post( $tagline_fr ); ?>"
        </div>
    <?php endif; ?>
    <?php if ( $has_en ) : ?>
        <div class="jlg-tagline-text" data-lang="en"<?php echo $default_lang === 'en' ? ' aria-hidden="false"' : ' hidden aria-hidden="true"'; ?>>
            "<?php echo wp_kses_post( $tagline_en ); ?>"
        </div>
    <?php endif; ?>
</div>

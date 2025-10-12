<?php
$rawg_api_key    = isset( $variables['rawg_api_key'] ) ? sanitize_text_field( $variables['rawg_api_key'] ) : '';
$rawg_skip       = ! empty( $variables['rawg_skip'] );
$rawg_attributes = $rawg_skip ? ' aria-disabled="true" disabled="disabled"' : '';
$is_active       = ! empty( $variables['is_active'] );
?>
<div class="jlg-onboarding-step<?php echo $is_active ? ' is-active' : ''; ?>" data-step="4">
    <fieldset class="jlg-onboarding-fieldset">
        <legend><?php esc_html_e( 'Connectez votre compte RAWG', 'notation-jlg' ); ?></legend>
        <p><?php esc_html_e( 'L’API RAWG enrichit vos fiches avec des jaquettes, genres et studios. Ajoutez votre clé pour profiter des synchronisations automatiques.', 'notation-jlg' ); ?></p>
        <label class="jlg-onboarding-option">
            <span>
                <strong><?php esc_html_e( 'Clé API RAWG', 'notation-jlg' ); ?></strong>
                <small><?php esc_html_e( 'Collez la clé générée depuis votre compte RAWG.io (au moins 10 caractères).', 'notation-jlg' ); ?></small>
                <input type="text" name="rawg_api_key" value="<?php echo esc_attr( $rawg_api_key ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Ex : 1a2b3c4d5e…', 'notation-jlg' ); ?>"<?php echo $rawg_attributes; ?> />
            </span>
        </label>
        <label class="jlg-onboarding-option">
            <input type="checkbox" name="rawg_skip" value="1" <?php checked( $rawg_skip ); ?> />
            <span>
                <strong><?php esc_html_e( 'Je fournirai la clé plus tard', 'notation-jlg' ); ?></strong>
                <small><?php esc_html_e( 'Vous pourrez revenir sur l’assistant depuis le menu Notation JLG pour finaliser l’intégration RAWG.', 'notation-jlg' ); ?></small>
            </span>
        </label>
    </fieldset>

    <p><?php esc_html_e( 'Vous êtes prêts ! Cliquez sur « Terminer la configuration » pour sauvegarder vos choix.', 'notation-jlg' ); ?></p>
</div>

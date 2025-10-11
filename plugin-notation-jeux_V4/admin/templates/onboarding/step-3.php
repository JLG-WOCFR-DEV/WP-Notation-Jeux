<?php
$presets        = isset( $variables['presets'] ) && is_array( $variables['presets'] ) ? $variables['presets'] : array();
$current_preset = isset( $variables['current_preset'] ) ? sanitize_key( $variables['current_preset'] ) : 'signature';
$current_theme  = isset( $variables['current_theme'] ) ? sanitize_key( $variables['current_theme'] ) : 'dark';
?>
<div class="jlg-onboarding-step" data-step="3">
    <fieldset class="jlg-onboarding-fieldset">
        <legend><?php esc_html_e( 'Choisissez un préréglage visuel', 'notation-jlg' ); ?></legend>
        <p><?php esc_html_e( 'Sélectionnez une base graphique pour accélérer la mise en forme de vos widgets.', 'notation-jlg' ); ?></p>
        <div class="jlg-onboarding-options" role="radiogroup" aria-label="<?php esc_attr_e( 'Préréglages visuels disponibles', 'notation-jlg' ); ?>">
            <?php foreach ( $presets as $slug => $preset ) :
                $preset_key  = sanitize_key( $slug );
                $label       = isset( $preset['label'] ) ? $preset['label'] : $preset_key;
                $description = isset( $preset['description'] ) ? $preset['description'] : '';
                if ( $preset_key === '' ) {
                    continue;
                }
                ?>
                <label class="jlg-onboarding-option">
                    <input type="radio" name="visual_preset" value="<?php echo esc_attr( $preset_key ); ?>" <?php checked( $preset_key, $current_preset ); ?> />
                    <span>
                        <strong><?php echo esc_html( $label ); ?></strong>
                        <?php if ( $description ) : ?>
                            <small><?php echo esc_html( $description ); ?></small>
                        <?php endif; ?>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <fieldset class="jlg-onboarding-fieldset">
        <legend><?php esc_html_e( 'Préférence de thème', 'notation-jlg' ); ?></legend>
        <p><?php esc_html_e( 'Définissez le thème global par défaut. Il restera possible de le surcharger dans les réglages.', 'notation-jlg' ); ?></p>
        <div class="jlg-onboarding-options" role="radiogroup" aria-label="<?php esc_attr_e( 'Thèmes disponibles', 'notation-jlg' ); ?>">
            <label class="jlg-onboarding-option">
                <input type="radio" name="visual_theme" value="dark" <?php checked( 'dark', $current_theme ); ?> />
                <span>
                    <strong><?php esc_html_e( 'Sombre', 'notation-jlg' ); ?></strong>
                    <small><?php esc_html_e( 'Idéal pour un contraste marqué et une immersion forte.', 'notation-jlg' ); ?></small>
                </span>
            </label>
            <label class="jlg-onboarding-option">
                <input type="radio" name="visual_theme" value="light" <?php checked( 'light', $current_theme ); ?> />
                <span>
                    <strong><?php esc_html_e( 'Clair', 'notation-jlg' ); ?></strong>
                    <small><?php esc_html_e( 'Parfait pour les chartes éditoriales lumineuses.', 'notation-jlg' ); ?></small>
                </span>
            </label>
        </div>
    </fieldset>
</div>

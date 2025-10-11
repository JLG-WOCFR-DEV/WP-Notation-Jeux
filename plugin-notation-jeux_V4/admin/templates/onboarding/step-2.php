<?php
$modules          = isset( $variables['modules'] ) && is_array( $variables['modules'] ) ? $variables['modules'] : array();
$selected_modules = isset( $variables['selected_modules'] ) && is_array( $variables['selected_modules'] ) ? array_map( 'sanitize_key', $variables['selected_modules'] ) : array();
?>
<div class="jlg-onboarding-step" data-step="2">
    <fieldset class="jlg-onboarding-fieldset">
        <legend><?php esc_html_e( 'Activez les modules complémentaires', 'notation-jlg' ); ?></legend>
        <p><?php esc_html_e( 'Sélectionnez les fonctionnalités qui enrichiront vos dossiers de tests. Vous pourrez les modifier à tout moment.', 'notation-jlg' ); ?></p>
        <div class="jlg-onboarding-options" role="group" aria-label="<?php esc_attr_e( 'Modules de notation JLG', 'notation-jlg' ); ?>">
            <?php foreach ( $modules as $module ) :
                $option_key   = sanitize_key( $module['option_key'] ?? '' );
                $label        = isset( $module['label'] ) ? $module['label'] : $option_key;
                $description  = isset( $module['description'] ) ? $module['description'] : '';
                if ( $option_key === '' ) {
                    continue;
                }
                $is_checked = in_array( $option_key, $selected_modules, true );
                ?>
                <label class="jlg-onboarding-option">
                    <input type="checkbox" name="modules[]" value="<?php echo esc_attr( $option_key ); ?>" <?php checked( $is_checked ); ?> />
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
</div>

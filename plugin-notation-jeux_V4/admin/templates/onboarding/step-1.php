<?php
$available_post_types = isset( $variables['available_post_types'] ) && is_array( $variables['available_post_types'] ) ? $variables['available_post_types'] : array();
$selected_post_types  = isset( $variables['selected_post_types'] ) && is_array( $variables['selected_post_types'] ) ? array_map( 'sanitize_key', $variables['selected_post_types'] ) : array( 'post' );
?>
<div class="jlg-onboarding-step is-active" data-step="1">
    <fieldset class="jlg-onboarding-fieldset">
        <legend><?php esc_html_e( 'Quels contenus souhaitez-vous noter ?', 'notation-jlg' ); ?></legend>
        <p><?php esc_html_e( 'Choisissez les types de contenus qui afficheront les modules de notation (vous pourrez ajuster plus tard).', 'notation-jlg' ); ?></p>
        <div class="jlg-onboarding-options" role="group" aria-label="<?php esc_attr_e( 'Types de contenus disponibles', 'notation-jlg' ); ?>">
            <?php foreach ( $available_post_types as $post_type ) :
                $slug  = sanitize_key( $post_type['slug'] ?? '' );
                $label = isset( $post_type['label'] ) ? $post_type['label'] : $slug;
                if ( $slug === '' ) {
                    continue;
                }
                $is_checked = in_array( $slug, $selected_post_types, true );
                ?>
                <label class="jlg-onboarding-option">
                    <input type="checkbox" name="allowed_post_types[]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $is_checked ); ?> />
                    <span>
                        <strong><?php echo esc_html( $label ); ?></strong>
                        <small><?php esc_html_e( 'Activation de la notation pour ce type de contenu.', 'notation-jlg' ); ?></small>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>
</div>

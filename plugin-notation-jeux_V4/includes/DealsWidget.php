<?php

namespace JLG\Notation;

use WP_Post;
use WP_Widget;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Widget d'affichage des deals affiliés configurés dans la metabox du plugin.
 */
class DealsWidget extends WP_Widget {

    private const DEFAULT_EMPTY_MESSAGE = 'Aucune offre disponible pour le moment.';

    /**
     * Initialise le widget et enregistre son descriptif.
     */
    public function __construct() {
        parent::__construct(
            'jlg_deals_widget',
            __( 'Notation JLG : Deals & disponibilités', 'notation-jlg' ),
            array(
                'description' => __( 'Affiche les offres affiliées configurées dans le module Deals.', 'notation-jlg' ),
            )
        );
    }

    /**
     * Affiche le widget sur le front.
     *
     * @param array $args     Arguments fournis par WordPress (before_widget, after_widget, etc.).
     * @param array $instance Valeurs stockées pour l'instance courante.
     * @return void
     */
    public function widget( $args, $instance ) {
        $settings = $this->parse_instance_settings( $instance );

        $options = Helpers::get_plugin_options();
        if ( empty( $options['deals_enabled'] ) ) {
            return;
        }

        $post_id = get_the_ID();
        $post    = $post_id ? get_post( $post_id ) : null;

        if ( ! $post instanceof WP_Post ) {
            return;
        }

        $post_id = (int) $post->ID;

        $deals = Helpers::get_deals_for_post( $post_id, $options );
        if ( empty( $deals ) && ! $settings['display_empty_message'] ) {
            return;
        }

        $rel_attribute = isset( $options['deals_button_rel'] ) ? sanitize_text_field( $options['deals_button_rel'] ) : '';
        $rel_attribute = trim( $rel_attribute );

        $disclaimer = isset( $options['deals_disclaimer'] ) ? (string) $options['deals_disclaimer'] : '';
        $disclaimer = $disclaimer !== '' ? wp_strip_all_tags( $disclaimer, false ) : '';
        $disclaimer = sanitize_textarea_field( $disclaimer );

        echo Frontend::get_template_html(
            'widget-deals',
            array(
                'widget_args'           => $args,
                'title'                 => $settings['title'],
                'deals'                 => $deals,
                'rel_attribute'         => $rel_attribute,
                'disclaimer'            => $disclaimer,
                'display_empty_message' => $settings['display_empty_message'],
                'empty_message'         => $settings['empty_message'],
            )
        );
    }

    /**
     * Affiche le formulaire de configuration du widget dans l'administration.
     *
     * @param array $instance Valeurs stockées pour l'instance courante.
     * @return void
     */
    public function form( $instance ) {
        $settings = $this->parse_instance_settings( $instance );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Titre :', 'notation-jlg' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $settings['title'] ); ?>">
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked( $settings['display_empty_message'] ); ?> id="<?php echo esc_attr( $this->get_field_id( 'display_empty_message' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'display_empty_message' ) ); ?>">
            <label for="<?php echo esc_attr( $this->get_field_id( 'display_empty_message' ) ); ?>"><?php esc_html_e( 'Afficher un message lorsque le module ne retourne aucune offre.', 'notation-jlg' ); ?></label>
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'empty_message' ) ); ?>"><?php esc_html_e( 'Message vide :', 'notation-jlg' ); ?></label>
            <textarea class="widefat" rows="3" id="<?php echo esc_attr( $this->get_field_id( 'empty_message' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'empty_message' ) ); ?>"><?php echo esc_textarea( $settings['empty_message'] ); ?></textarea>
        </p>
        <?php
    }

    /**
     * Nettoie les paramètres lorsqu'une instance du widget est enregistrée.
     *
     * @param array $new_instance Nouvelles valeurs fournies par WordPress.
     * @param array $old_instance Anciennes valeurs stockées.
     * @return array Paramètres nettoyés à persister.
     */
    public function update( $new_instance, $old_instance ) {
        unset( $old_instance );

        $settings = $this->parse_instance_settings( $new_instance );

        return array(
            'title'                 => $settings['title'],
            'display_empty_message' => $settings['display_empty_message'],
            'empty_message'         => $settings['empty_message'],
        );
    }

    /**
     * Normalise les paramètres d'une instance (formulaire admin ou valeurs par défaut).
     *
     * @param array|null $instance Valeurs brutes fournies par WordPress.
     * @return array{title:string,display_empty_message:bool,empty_message:string}
     */
    private function parse_instance_settings( $instance ) {
        if ( ! is_array( $instance ) ) {
            $instance = array();
        }

        $raw_title = isset( $instance['title'] ) && is_string( $instance['title'] ) ? $instance['title'] : '';
        $title     = $raw_title !== '' ? sanitize_text_field( wp_strip_all_tags( $raw_title ) ) : __( 'Bonnes affaires', 'notation-jlg' );

        $raw_display_empty = isset( $instance['display_empty_message'] ) ? $instance['display_empty_message'] : false;
        $display_empty     = ! empty( $raw_display_empty );

        $raw_empty_message = isset( $instance['empty_message'] ) && is_string( $instance['empty_message'] ) ? $instance['empty_message'] : '';
        if ( $raw_empty_message !== '' ) {
            $raw_empty_message = wp_strip_all_tags( $raw_empty_message, false );
        }
        $default_empty_message = __( self::DEFAULT_EMPTY_MESSAGE, 'notation-jlg' );
        $empty_message         = $raw_empty_message !== '' ? sanitize_textarea_field( $raw_empty_message ) : $default_empty_message;

        return array(
            'title'                 => $title,
            'display_empty_message' => $display_empty,
            'empty_message'         => $empty_message,
        );
    }
}

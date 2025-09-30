<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class JLG_Shortcode_Tagline {

    public function __construct() {
        add_shortcode( 'tagline_notation_jlg', array( $this, 'render' ) );
    }

    public function render( $atts = array(), $content = '', $shortcode_tag = '' ) {
        $allowed_types = JLG_Helpers::get_allowed_post_types();

        if ( ! is_singular( $allowed_types ) ) {
            return '';
        }

        $options = JLG_Helpers::get_plugin_options();
        if ( empty( $options['tagline_enabled'] ) ) {
            return '';
        }

        $tagline_fr = get_post_meta( get_the_ID(), '_jlg_tagline_fr', true );
        $tagline_en = get_post_meta( get_the_ID(), '_jlg_tagline_en', true );

        if ( empty( $tagline_fr ) && empty( $tagline_en ) ) {
            return '';
        }

        JLG_Frontend::mark_shortcode_rendered( $shortcode_tag ?: 'tagline_notation_jlg' );

        return JLG_Frontend::get_template_html(
            'shortcode-tagline',
            array(
				'options'    => $options,
				'tagline_fr' => $tagline_fr,
				'tagline_en' => $tagline_en,
			)
        );
    }
}

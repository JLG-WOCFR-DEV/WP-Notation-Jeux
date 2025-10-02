<?php
/**
 * Verify shortcode registration when WordPress loads the plugin for real.
 */
class ShortcodeRegistrationTest extends WP_UnitTestCase {
    public function test_all_shortcodes_are_registered_on_init(): void {
        do_action('init');

        $expected = array(
            'bloc_notation_jeu',
            'jlg_points_forts_faibles',
            'jlg_fiche_technique',
            'tagline_notation_jlg',
            'jlg_tableau_recap',
            'notation_utilisateurs_jlg',
            'jlg_bloc_complet',
            'bloc_notation_complet',
            'jlg_game_explorer',
        );

        foreach ($expected as $shortcode) {
            $this->assertTrue(shortcode_exists($shortcode), sprintf('Shortcode %s should be registered.', $shortcode));
        }
    }
}

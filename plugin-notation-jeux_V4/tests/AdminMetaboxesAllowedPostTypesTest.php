<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/admin/class-jlg-admin-metaboxes.php';

class AdminMetaboxesAllowedPostTypesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['jlg_test_filters'] = [];
        $GLOBALS['jlg_test_meta_boxes'] = [];
        $GLOBALS['jlg_test_posts'] = [];
        $GLOBALS['jlg_test_meta'] = [];
        $GLOBALS['jlg_test_meta_updates'] = [];
        $GLOBALS['jlg_test_transients'] = [];
        $_POST = [];

        \JLG\Notation\Helpers::flush_plugin_options_cache();
    }

    protected function tearDown(): void
    {
        unset(
            $GLOBALS['jlg_test_filters'],
            $GLOBALS['jlg_test_meta_boxes'],
            $GLOBALS['jlg_test_posts'],
            $GLOBALS['jlg_test_meta'],
            $GLOBALS['jlg_test_meta_updates'],
            $GLOBALS['jlg_test_transients']
        );
        $_POST = [];

        \JLG\Notation\Helpers::flush_plugin_options_cache();

        parent::tearDown();
    }

    public function test_register_metaboxes_respects_allowed_post_types(): void
    {
        add_filter('jlg_rated_post_types', static function ($types) {
            $types[] = 'jlg_review';

            return $types;
        });

        $post_id = 123;
        $post = new WP_Post([
            'ID'        => $post_id,
            'post_type' => 'jlg_review',
        ]);

        $metaboxes = new \JLG\Notation\Admin\Metaboxes();
        $metaboxes->register_metaboxes('jlg_review', $post);

        $this->assertCount(2, $GLOBALS['jlg_test_meta_boxes']);
        $this->assertSame('notation_jlg_metabox', $GLOBALS['jlg_test_meta_boxes'][0]['id']);
        $this->assertSame('jlg_review', $GLOBALS['jlg_test_meta_boxes'][0]['screen']);
        $this->assertSame('jlg_review', $GLOBALS['jlg_test_meta_boxes'][1]['screen']);
    }

    public function test_save_meta_data_persists_notes_and_details_for_allowed_types(): void
    {
        add_filter('jlg_rated_post_types', static function ($types) {
            $types[] = 'jlg_review';

            return $types;
        });

        $post_id = 456;
        $GLOBALS['jlg_test_posts'][$post_id] = new WP_Post([
            'ID'        => $post_id,
            'post_type' => 'jlg_review',
        ]);
        $GLOBALS['jlg_test_meta'][$post_id] = [];

        $definitions     = \JLG\Notation\Helpers::get_rating_category_definitions();
        $first_meta_key  = $definitions[0]['meta_key'] ?? '_note_gameplay';
        $second_meta_key = $definitions[1]['meta_key'] ?? '_note_graphismes';

        $_POST = [
            'jlg_notation_nonce' => 'nonce',
            $first_meta_key      => '9.5',
            $second_meta_key     => '8.0',
            'jlg_details_nonce'  => 'nonce',
            'jlg_game_title'     => 'Custom Review Game',
            'jlg_developpeur'    => 'Studio Test',
            'jlg_editeur'        => 'Publisher Test',
            'jlg_date_sortie'    => '2024-05-01',
            'jlg_version'        => '1.0',
            'jlg_pegi'           => 'PEGI 16',
            'jlg_temps_de_jeu'   => '20h',
            'jlg_cover_image_url'=> 'https://example.com/cover.jpg',
            'jlg_tagline_fr'     => 'Un jeu exceptionnel',
            'jlg_tagline_en'     => 'An exceptional game',
            'jlg_points_forts'   => "Gameplay solide\nUnivers riche",
            'jlg_points_faibles' => "Quelques bugs\nMenus confus",
            'jlg_plateformes'    => ['PC', 'Xbox One'],
        ];

        $metaboxes = new \JLG\Notation\Admin\Metaboxes();
        $metaboxes->save_meta_data($post_id);

        $saved_meta = $GLOBALS['jlg_test_meta'][$post_id];

        $this->assertSame(9.5, $saved_meta[$first_meta_key]);
        $this->assertSame(8.0, $saved_meta[$second_meta_key]);
        $this->assertSame('Custom Review Game', $saved_meta['_jlg_game_title']);
        $this->assertSame('Studio Test', $saved_meta['_jlg_developpeur']);
        $this->assertSame('Publisher Test', $saved_meta['_jlg_editeur']);
        $this->assertSame('2024-05-01', $saved_meta['_jlg_date_sortie']);
        $this->assertSame('1.0', $saved_meta['_jlg_version']);
        $this->assertSame('PEGI 16', $saved_meta['_jlg_pegi']);
        $this->assertSame('20h', $saved_meta['_jlg_temps_de_jeu']);
        $this->assertSame('https://example.com/cover.jpg', $saved_meta['_jlg_cover_image_url']);
        $this->assertSame('Un jeu exceptionnel', $saved_meta['_jlg_tagline_fr']);
        $this->assertSame('An exceptional game', $saved_meta['_jlg_tagline_en']);
        $this->assertSame(['PC', 'Xbox One'], $saved_meta['_jlg_plateformes']);
    }
}

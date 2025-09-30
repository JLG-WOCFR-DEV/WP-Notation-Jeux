<?php

use PHPUnit\Framework\TestCase;

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data, $options = 0, $depth = 512) {
        return json_encode($data, $options, $depth);
    }
}

if (!function_exists('_n')) {
    function _n($single, $plural, $number, $domain = 'default') {
        unset($domain);

        return (int) $number === 1 ? $single : $plural;
    }
}

require_once __DIR__ . '/../includes/class-jlg-helpers.php';
require_once __DIR__ . '/../includes/shortcodes/class-jlg-shortcode-game-explorer.php';
require_once __DIR__ . '/../includes/class-jlg-frontend.php';

class ShortcodeGameExplorerCountTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->resetTestGlobals();
    }

    protected function tearDown(): void
    {
        $this->resetTestGlobals();

        parent::tearDown();
    }

    public function test_renders_singular_label_with_formatted_count(): void
    {
        $output = $this->renderTemplateWithTotal(1);

        $this->assertMatchesRegularExpression(
            '/<div class="jlg-ge-count">\s*1 jeu\s*<\/div>/',
            $output,
            'The singular label should be rendered when there is only one game.'
        );
    }

    public function test_renders_plural_label_with_formatted_count(): void
    {
        $output = $this->renderTemplateWithTotal(2);

        $this->assertMatchesRegularExpression(
            '/<div class="jlg-ge-count">\s*2 jeux\s*<\/div>/',
            $output,
            'The plural label should be rendered when there are multiple games.'
        );
    }

    public function test_uses_number_format_i18n_for_large_counts(): void
    {
        $output = $this->renderTemplateWithTotal(12345);

        $this->assertStringContainsString(
            '12,345 jeux',
            $output,
            'The game count should be formatted using number_format_i18n().'
        );
    }

    public function test_build_filters_snapshot_retains_existing_structure(): void
    {
        $this->registerPost(101, 'Alpha Quest');
        $this->registerPost(202, 'Beta Strike');

        $this->setPostMeta(101, [
            '_jlg_game_title'   => 'Alpha Quest',
            '_jlg_date_sortie'  => '2023-02-14',
            '_jlg_developpeur'  => 'Studio Alpha',
            '_jlg_editeur'      => 'Publisher A',
            '_jlg_plateformes'  => ['PC', 'PlayStation 5'],
        ]);

        $this->setPostMeta(202, [
            '_jlg_game_title'   => 'Beta Strike',
            '_jlg_date_sortie'  => '2022-11-10',
            '_jlg_developpeur'  => 'Studio Beta',
            '_jlg_editeur'      => 'Publisher B',
            '_jlg_plateformes'  => ['PC'],
        ]);

        $this->assignTerms(101, [
            ['term_id' => 11, 'name' => 'Action', 'slug' => 'action'],
        ]);

        $this->assignTerms(202, [
            ['term_id' => 22, 'name' => 'Adventure', 'slug' => 'adventure'],
        ]);

        $this->primeRatedPosts([101, 202]);

        $snapshot = $this->invokeBuildSnapshot();

        $expected = [
            'posts' => [
                101 => [
                    'letter'           => 'A',
                    'category_ids'     => [11],
                    'category_slugs'   => ['action'],
                    'primary_genre'    => 'Action',
                    'platform_labels'  => ['PC', 'PlayStation 5'],
                    'platform_slugs'   => ['pc', 'playstation-5'],
                    'developer'        => 'Studio Alpha',
                    'publisher'        => 'Publisher A',
                    'release_iso'      => '2023-02-14',
                    'availability'     => 'available',
                    'search_haystack'  => 'alpha quest studio alpha publisher a action pc playstation 5',
                ],
                202 => [
                    'letter'           => 'B',
                    'category_ids'     => [22],
                    'category_slugs'   => ['adventure'],
                    'primary_genre'    => 'Adventure',
                    'platform_labels'  => ['PC'],
                    'platform_slugs'   => ['pc'],
                    'developer'        => 'Studio Beta',
                    'publisher'        => 'Publisher B',
                    'release_iso'      => '2022-11-10',
                    'availability'     => 'available',
                    'search_haystack'  => 'beta strike studio beta publisher b adventure pc',
                ],
            ],
            'letters_map'    => ['A' => true, 'B' => true],
            'categories_map' => [11 => 'Action', 22 => 'Adventure'],
            'platforms_map'  => ['pc' => 'PC', 'playstation-5' => 'PlayStation 5'],
        ];

        $this->assertSame($expected, $snapshot, 'Snapshot output should remain unchanged after metadata caching refactor.');
    }

    private function renderTemplateWithTotal(int $total): string
    {
        return JLG_Frontend::get_template_html('shortcode-game-explorer', [
            'total_items' => $total,
            'atts' => [
                'posts_per_page' => 12,
            ],
        ]);
    }

    private function resetTestGlobals(): void
    {
        $GLOBALS['jlg_test_posts'] = [];
        $GLOBALS['jlg_test_meta'] = [];
        $GLOBALS['jlg_test_terms'] = [];
        $GLOBALS['jlg_test_transients'] = [];

        if (class_exists(JLG_Shortcode_Game_Explorer::class)) {
            JLG_Shortcode_Game_Explorer::clear_filters_snapshot();
        }
    }

    private function registerPost(int $post_id, string $title): void
    {
        $GLOBALS['jlg_test_posts'][$post_id] = new WP_Post([
            'ID'          => $post_id,
            'post_type'   => 'post',
            'post_status' => 'publish',
            'post_title'  => $title,
        ]);
    }

    private function setPostMeta(int $post_id, array $meta): void
    {
        foreach ($meta as $key => $value) {
            $GLOBALS['jlg_test_meta'][$post_id][$key] = $value;
        }
    }

    private function assignTerms(int $post_id, array $terms): void
    {
        $prepared_terms = [];

        foreach ($terms as $term) {
            $prepared_terms[] = (object) [
                'term_id' => $term['term_id'],
                'name'    => $term['name'],
                'slug'    => $term['slug'],
            ];
        }

        $GLOBALS['jlg_test_terms'][$post_id]['category'] = $prepared_terms;
    }

    private function primeRatedPosts(array $post_ids): void
    {
        set_transient('jlg_rated_post_ids_v1', $post_ids);
    }

    private function invokeBuildSnapshot(): array
    {
        $this->resetSnapshotCache();

        $method = new ReflectionMethod(JLG_Shortcode_Game_Explorer::class, 'build_filters_snapshot');
        $method->setAccessible(true);

        return $method->invoke(null);
    }

    private function resetSnapshotCache(): void
    {
        $reflection = new ReflectionClass(JLG_Shortcode_Game_Explorer::class);

        if ($reflection->hasProperty('filters_snapshot')) {
            $property = $reflection->getProperty('filters_snapshot');
            $property->setAccessible(true);
            $property->setValue(null, null);
        }
    }
}


<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/Shortcodes/GameExplorer.php';
require_once __DIR__ . '/../includes/Frontend.php';
require_once __DIR__ . '/../includes/Helpers.php';

final class GameExplorerQueryCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetEnvironment();
        $this->configureOptions();
        $this->registerFixtures();
        $this->primeSnapshot($this->buildSnapshotWithPosts());
    }

    protected function tearDown(): void
    {
        $this->resetEnvironment();
        parent::tearDown();
    }

    public function test_query_results_cached_and_reused(): void
    {
        \JLG\Notation\Shortcodes\GameExplorer::clear_filters_snapshot();
        $this->primeSnapshot($this->buildSnapshotWithPosts());

        $atts    = \JLG\Notation\Shortcodes\GameExplorer::get_default_atts();
        $request = [
            'orderby' => 'date',
            'order'   => 'DESC',
            'paged'   => 1,
        ];

        $contextOne = \JLG\Notation\Shortcodes\GameExplorer::get_render_context($atts, $request);

        $this->assertSame('miss', $contextOne['cache_status']['query'] ?? null, 'First request should miss the query cache.');
        $this->assertSame(2, $contextOne['total_items'], 'Initial request should return both registered games.');

        $transients  = array_keys($GLOBALS['jlg_test_transients'] ?? []);
        $cache_keys = array_filter(
            $transients,
            static function ($key) {
                return strpos((string) $key, 'jlg_ge_query_') === 0;
            }
        );

        $this->assertNotEmpty($cache_keys, 'Query cache should populate a transient key after the first render.');

        $contextTwo = \JLG\Notation\Shortcodes\GameExplorer::get_render_context($atts, $request);

        $this->assertSame('hit', $contextTwo['cache_status']['query'] ?? null, 'Second request should reuse the cached query payload.');
        $this->assertSame($contextOne['total_items'], $contextTwo['total_items'], 'Cached result should expose the same total.');
        $this->assertSame($contextOne['games'], $contextTwo['games'], 'Cached games collection should match the initial render.');
    }

    public function test_cache_version_bumped_when_snapshot_cleared(): void
    {
        $initial_version = get_option('jlg_ge_query_cache_version', 1);

        \JLG\Notation\Shortcodes\GameExplorer::clear_filters_snapshot();

        $bumped_version = get_option('jlg_ge_query_cache_version', 0);

        $this->assertGreaterThan(
            $initial_version,
            $bumped_version,
            'Clearing the snapshot should bump the query cache version to invalidate existing entries.'
        );
    }

    private function configureOptions(): void
    {
        $defaults = \JLG\Notation\Helpers::get_default_settings();
        $defaults['game_explorer_posts_per_page'] = 2;
        $defaults['game_explorer_filters']        = \JLG\Notation\Helpers::get_default_game_explorer_filters();
        $defaults['game_explorer_score_position'] = \JLG\Notation\Helpers::normalize_game_explorer_score_position('');

        $GLOBALS['jlg_test_options']['notation_jlg_settings'] = $defaults;
        $GLOBALS['jlg_test_options']['jlg_platforms_list']    = [];

        \JLG\Notation\Helpers::flush_plugin_options_cache();
    }

    private function registerFixtures(): void
    {
        $this->registerPost(101, 'Alpha Quest', 'Alpha content for caching test.', '2023-01-01 10:00:00');
        $this->registerPost(202, 'Beta Strike', 'Beta content for caching test.', '2023-01-05 11:30:00');

        $this->setMeta(101, [
            '_jlg_average_score'   => 8.5,
            '_jlg_cover_image_url' => 'https://example.com/alpha.jpg',
            '_jlg_date_sortie'     => '2023-02-14',
            '_jlg_developpeur'     => 'Studio Alpha',
            '_jlg_editeur'         => 'Publisher A',
            '_jlg_plateformes'     => ['PC', 'PlayStation 5'],
        ]);

        $this->setMeta(202, [
            '_jlg_average_score'   => 7.2,
            '_jlg_cover_image_url' => '',
            '_jlg_date_sortie'     => '2022-11-10',
            '_jlg_developpeur'     => 'Studio Beta',
            '_jlg_editeur'         => 'Publisher B',
            '_jlg_plateformes'     => ['PC'],
        ]);

        $GLOBALS['jlg_test_terms'] = [
            101 => [
                'category' => [
                    ['term_id' => 11, 'slug' => 'action', 'name' => 'Action'],
                ],
            ],
            202 => [
                'category' => [
                    ['term_id' => 11, 'slug' => 'action', 'name' => 'Action'],
                ],
            ],
        ];
    }

    private function resetEnvironment(): void
    {
        $GLOBALS['jlg_test_posts']       = [];
        $GLOBALS['jlg_test_meta']        = [];
        $GLOBALS['jlg_test_terms']       = [];
        $GLOBALS['jlg_test_options']     = [];
        $GLOBALS['jlg_test_transients']  = [];
        $GLOBALS['jlg_test_meta_cache_calls'] = [];
        $GLOBALS['jlg_test_term_cache_calls'] = [];
        $_POST    = [];
        $_REQUEST = [];

        $this->resetSnapshot();
        \JLG\Notation\Helpers::flush_plugin_options_cache();
    }

    private function resetSnapshot(): void
    {
        $reflection = new ReflectionClass(\JLG\Notation\Shortcodes\GameExplorer::class);
        foreach ( ['filters_snapshot', 'query_cache_version'] as $property_name ) {
            if ( $reflection->hasProperty($property_name) ) {
                $property = $reflection->getProperty($property_name);
                $property->setAccessible(true);
                $property->setValue(null, null);
            }
        }
    }

    private function registerPost(int $post_id, string $title, string $content, string $post_date): void
    {
        $GLOBALS['jlg_test_posts'][$post_id] = new WP_Post([
            'ID'            => $post_id,
            'post_type'     => 'post',
            'post_status'   => 'publish',
            'post_title'    => $title,
            'post_content'  => $content,
            'post_date'     => $post_date,
            'post_date_gmt' => $post_date,
        ]);
    }

    private function setMeta(int $post_id, array $meta): void
    {
        if ( ! isset($GLOBALS['jlg_test_meta'][$post_id]) ) {
            $GLOBALS['jlg_test_meta'][$post_id] = [];
        }

        $GLOBALS['jlg_test_meta'][$post_id] = array_merge($GLOBALS['jlg_test_meta'][$post_id], $meta);
    }

    private function primeSnapshot(array $snapshot): void
    {
        $reflection = new ReflectionClass(\JLG\Notation\Shortcodes\GameExplorer::class);
        $property   = $reflection->getProperty('filters_snapshot');
        $property->setAccessible(true);
        $property->setValue(null, $snapshot);

        $this->hydrateIndexMetaFromSnapshot($snapshot);
    }

    private function hydrateIndexMetaFromSnapshot(array $snapshot): void
    {
        if ( ! isset($snapshot['posts']) || ! is_array($snapshot['posts']) ) {
            return;
        }

        $index_keys = [
            'letter'        => '_jlg_ge_letter',
            'developer'     => '_jlg_ge_developer_key',
            'publisher'     => '_jlg_ge_publisher_key',
            'availability'  => '_jlg_ge_availability',
            'release_year'  => '_jlg_ge_release_year',
            'search_index'  => '_jlg_ge_search_index',
            'platform_index'=> '_jlg_ge_platform_index',
        ];

        foreach ( $snapshot['posts'] as $post_id => $post_meta ) {
            if ( ! isset($GLOBALS['jlg_test_meta'][$post_id]) ) {
                $GLOBALS['jlg_test_meta'][$post_id] = [];
            }

            if ( isset($post_meta['index_meta']) && is_array($post_meta['index_meta']) ) {
                foreach ( $index_keys as $key => $meta_key ) {
                    if ( isset($post_meta['index_meta'][$key]) ) {
                        $GLOBALS['jlg_test_meta'][$post_id][$meta_key] = $post_meta['index_meta'][$key];
                    }
                }
            }
        }
    }

    private function buildSnapshotWithPosts(): array
    {
        return [
            'posts' => [
                101 => [
                    'letter'           => 'A',
                    'category_ids'     => [11],
                    'category_slugs'   => ['action'],
                    'primary_genre'    => 'Action',
                    'platform_labels'  => ['PC', 'PlayStation 5'],
                    'platform_slugs'   => ['pc', 'playstation-5'],
                    'developer'        => 'Studio Alpha',
                    'developer_key'    => 'studio alpha',
                    'publisher'        => 'Publisher A',
                    'publisher_key'    => 'publisher a',
                    'release_iso'      => '2023-02-14',
                    'release_year'     => 2023,
                    'availability'     => 'available',
                    'search_index'     => ' alpha quest studio alpha publisher a action pc playstation 5 ',
                    'popularity'       => 0,
                    'index_meta'       => [
                        'letter'        => 'A',
                        'developer'     => 'studio alpha',
                        'publisher'     => 'publisher a',
                        'availability'  => 'available',
                        'release_year'  => '2023',
                        'search_index'  => ' alpha quest studio alpha publisher a action pc playstation 5 ',
                        'platform_index'=> '|pc|playstation-5|',
                    ],
                ],
                202 => [
                    'letter'           => 'B',
                    'category_ids'     => [11],
                    'category_slugs'   => ['action'],
                    'primary_genre'    => 'Action',
                    'platform_labels'  => ['PC'],
                    'platform_slugs'   => ['pc'],
                    'developer'        => 'Studio Beta',
                    'developer_key'    => 'studio beta',
                    'publisher'        => 'Publisher B',
                    'publisher_key'    => 'publisher b',
                    'release_iso'      => '2022-11-10',
                    'release_year'     => 2022,
                    'availability'     => 'available',
                    'search_index'     => ' beta strike studio beta publisher b action pc ',
                    'popularity'       => 0,
                    'index_meta'       => [
                        'letter'        => 'B',
                        'developer'     => 'studio beta',
                        'publisher'     => 'publisher b',
                        'availability'  => 'available',
                        'release_year'  => '2022',
                        'search_index'  => ' beta strike studio beta publisher b action pc ',
                        'platform_index'=> '|pc|',
                    ],
                ],
            ],
            'letters_map'    => ['A' => true, 'B' => true],
            'categories_map' => [11 => 'Action'],
            'platforms_map'  => ['pc' => 'PC', 'playstation-5' => 'PlayStation 5'],
            'developers_map' => ['studio alpha' => 'Studio Alpha', 'studio beta' => 'Studio Beta'],
            'publishers_map' => ['publisher a' => 'Publisher A', 'publisher b' => 'Publisher B'],
            'search_tokens'  => [
                'alpha' => 1,
                'quest' => 1,
                'studio' => 2,
                'publisher' => 2,
                'a' => 1,
                'action' => 2,
                'pc' => 2,
                'playstation' => 1,
                '5' => 1,
                'beta' => 1,
                'strike' => 1,
                'b' => 1,
            ],
            'years'          => [
                'min'     => 2022,
                'max'     => 2023,
                'buckets' => [
                    2022 => 1,
                    2023 => 1,
                ],
            ],
        ];
    }
}

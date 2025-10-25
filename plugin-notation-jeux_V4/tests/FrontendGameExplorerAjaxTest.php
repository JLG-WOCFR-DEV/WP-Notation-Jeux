<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/Shortcodes/GameExplorer.php';

if (!function_exists('get_permalink')) {
    function get_permalink($post_id)
    {
        return 'https://example.com/?p=' . (int) $post_id;
    }
}

if (!function_exists('get_the_excerpt')) {
    function get_the_excerpt($post_id = null)
    {
        $post_id = $post_id === null ? ($GLOBALS['post']->ID ?? 0) : (int) $post_id;
        $meta = $GLOBALS['jlg_test_meta'][$post_id]['_jlg_excerpt'] ?? '';
        if (is_string($meta) && $meta !== '') {
            return $meta;
        }

        $post = $GLOBALS['jlg_test_posts'][$post_id] ?? null;
        if ($post instanceof WP_Post && isset($post->post_content)) {
            return wp_trim_words(wp_strip_all_tags($post->post_content), 20, '…');
        }

        return '';
    }
}

if (!function_exists('wp_trim_words')) {
    function wp_trim_words($text, $num_words = 55, $more = null)
    {
        $more = $more === null ? '…' : $more;
        $words = preg_split('/\s+/', (string) $text, -1, PREG_SPLIT_NO_EMPTY);
        if (count($words) <= $num_words) {
            return implode(' ', $words);
        }

        return implode(' ', array_slice($words, 0, $num_words)) . $more;
    }
}

if (!function_exists('get_the_post_thumbnail_url')) {
    function get_the_post_thumbnail_url($post_id, $size = 'thumbnail')
    {
        $meta = $GLOBALS['jlg_test_meta'][$post_id]['_jlg_thumbnail'] ?? '';

        return is_string($meta) ? $meta : '';
    }
}

if (!function_exists('get_post_time')) {
    function get_post_time($format, $gmt = 0, $post = null)
    {
        if ($post === null) {
            $post = $GLOBALS['post'] ?? null;
        }

        if ($post instanceof WP_Post) {
            $date = $gmt ? ($post->post_date_gmt ?? $post->post_date ?? '') : ($post->post_date ?? '');
            if (is_string($date) && $date !== '') {
                $timestamp = strtotime($date);
                if ($timestamp !== false) {
                    return $format === 'U' ? $timestamp : date($format, $timestamp);
                }
            }
        }

        $now = time();

        return $format === 'U' ? $now : date($format, $now);
    }
}

if (!function_exists('update_meta_cache')) {
    function update_meta_cache($meta_type, $object_ids)
    {
        if (!isset($GLOBALS['jlg_test_meta_cache_calls'])) {
            $GLOBALS['jlg_test_meta_cache_calls'] = [];
        }

        $GLOBALS['jlg_test_meta_cache_calls'][] = [
            (string) $meta_type,
            array_values(array_map('intval', (array) $object_ids)),
        ];

        return true;
    }
}

if (!function_exists('update_object_term_cache')) {
    function update_object_term_cache($object_ids, $object_type, $taxonomies = null)
    {
        if (!isset($GLOBALS['jlg_test_term_cache_calls'])) {
            $GLOBALS['jlg_test_term_cache_calls'] = [];
        }

        if ($taxonomies === null) {
            $taxonomies = [];
        }

        if (!is_array($taxonomies)) {
            $taxonomies = [$taxonomies];
        }

        $GLOBALS['jlg_test_term_cache_calls'][] = [
            array_values(array_map('intval', (array) $object_ids)),
            (string) $object_type,
            array_values(array_map('strval', $taxonomies)),
        ];

        return true;
    }
}

if (!function_exists('get_the_terms')) {
    function get_the_terms($post_id, $taxonomy)
    {
        $post_id = (int) $post_id;
        $taxonomy = (string) $taxonomy;
        $terms_store = $GLOBALS['jlg_test_terms'][$post_id][$taxonomy] ?? [];

        $terms = [];

        foreach ($terms_store as $term) {
            if (is_array($term)) {
                $term = (object) $term;
            }

            if (is_object($term)) {
                $terms[] = $term;
            }
        }

        return $terms;
    }
}

if (!function_exists('wp_reset_postdata')) {
    function wp_reset_postdata()
    {
        unset($GLOBALS['post']);
    }
}

if (!function_exists('esc_attr_e')) {
    function esc_attr_e($text, $domain = 'default')
    {
        echo esc_attr($text);
    }
}

if (!function_exists('disabled')) {
    function disabled($disabled, $current = true, $echo = true)
    {
        $result = ($disabled == $current) ? 'disabled="disabled"' : '';

        if ($echo) {
            echo $result;

            return null;
        }

        return $result;
    }
}

class FrontendGameExplorerAjaxTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetEnvironment();
    }

    protected function tearDown(): void
    {
        $this->resetEnvironment();
        parent::tearDown();
    }

    public function test_handle_game_explorer_sort_sanitizes_request_and_returns_counts(): void
    {
        $this->configureOptions();
        $this->primeSnapshot($this->buildSnapshotWithPosts());

        $this->registerPost(101, 'Alpha Quest', 'Alpha content for the first test post.', '2023-01-01 10:00:00');
        $this->registerPost(202, 'Beta Strike', 'Beta content for the second test post.', '2023-01-05 11:30:00');

        $this->setMeta(101, [
            '_jlg_average_score'   => 8.6,
            '_jlg_cover_image_url' => 'https://example.com/alpha.jpg',
            '_jlg_date_sortie'     => '2023-02-14',
            '_jlg_developpeur'     => 'Studio Alpha',
            '_jlg_editeur'         => 'Publisher A',
            '_jlg_plateformes'     => ['PC', 'PlayStation 5'],
        ]);
        $this->setMeta(202, [
            '_jlg_average_score'   => 7.4,
            '_jlg_cover_image_url' => '',
            '_jlg_date_sortie'     => '2022-11-10',
            '_jlg_developpeur'     => 'Studio Beta',
            '_jlg_editeur'         => 'Publisher B',
            '_jlg_plateformes'     => ['PC'],
        ]);

        $GLOBALS['jlg_test_terms'] = [
            101 => [
                'category' => [
                    ['term_id' => 11, 'slug' => 'action'],
                ],
            ],
            202 => [
                'category' => [
                    ['term_id' => 11, 'slug' => 'action'],
                ],
            ],
        ];

        $_POST = [
            'nonce'          => 'nonce-jlg_game_explorer',
            'container_id'   => ' invalid<container>',
            'posts_per_page' => '-3',
            'columns'        => '0',
            'filters'        => $this->getDefaultFiltersString(),
            'orderby'        => 'score;DROP TABLE',
            'order'          => 'downwards',
            'letter'         => [' alpha '],
            'category'       => [' 11 '],
            'platform'       => ['pc'],
            'developer'      => [' Studio '],
            'publisher'      => ['<b>Publisher</b>'],
            'availability'   => ['<script>'],
            'search'         => ['Alpha'],
            'paged'          => '-5',
        ];

        $frontend = new \JLG\Notation\Frontend();

        try {
            $frontend->handle_game_explorer_sort();
            $this->fail('Expected WP_Send_Json_Exception to be thrown.');
        } catch (WP_Send_Json_Exception $exception) {
            $this->assertTrue($exception->success, 'Ajax handler should respond with success payload.');
            $this->assertIsArray($exception->data, 'Ajax payload should be an array.');
            $this->assertArrayHasKey('state', $exception->data);

            $state = $exception->data['state'];
            $this->assertSame('date', $state['orderby'], 'Invalid orderby should fall back to date.');
            $this->assertSame('DESC', $state['order'], 'Invalid order should fall back to DESC.');
            $this->assertSame('', $state['letter'], 'Array letter input should sanitize to empty string.');
            $this->assertSame('', $state['category'], 'Array category input should sanitize to empty string.');
            $this->assertSame('', $state['platform'], 'Array platform input should sanitize to empty string.');
            $this->assertSame('', $state['developer'] ?? '', 'Array developer input should sanitize to empty string.');
            $this->assertSame('', $state['publisher'] ?? '', 'Array publisher input should sanitize to empty string.');
            $this->assertSame('', $state['availability'], 'Invalid availability should be dropped.');
            $this->assertSame('', $state['search'], 'Array search input should sanitize to empty string.');
            $this->assertSame(1, $state['paged'], 'Negative paged values should resolve to page 1.');
            $this->assertSame(1, $state['total_pages'], 'Total pages should reflect sanitized pagination.');
            $this->assertSame(2, $state['total_items'], 'Total items should match the prepared snapshot.');
        }
    }

    public function test_handle_game_explorer_sort_returns_message_when_snapshot_empty(): void
    {
        $this->configureOptions();
        $this->primeSnapshot([
            'posts'          => [],
            'letters_map'    => [],
            'categories_map' => [],
            'platforms_map'  => [],
            'developers_map' => [],
            'publishers_map' => [],
        ]);

        $_POST = [
            'nonce'   => 'nonce-jlg_game_explorer',
            'orderby' => 'invalid',
            'order'   => 'ascending',
            'paged'   => '0',
        ];

        $frontend = new \JLG\Notation\Frontend();

        try {
            $frontend->handle_game_explorer_sort();
            $this->fail('Expected WP_Send_Json_Exception to be thrown.');
        } catch (WP_Send_Json_Exception $exception) {
            $this->assertTrue($exception->success, 'Ajax handler should report success even when no games exist.');
            $this->assertIsArray($exception->data, 'Ajax payload should be an array.');
            $this->assertArrayHasKey('html', $exception->data);
            $this->assertStringContainsString('Aucun test noté', $exception->data['html']);

            $state = $exception->data['state'];
            $this->assertSame('date', $state['orderby'], 'Invalid orderby should fall back to date.');
            $this->assertSame('DESC', $state['order'], 'Invalid order should fall back to DESC.');
            $this->assertSame(0, $state['total_items'], 'No items should be reported when the snapshot is empty.');
            $this->assertSame(0, $state['total_pages'], 'Total pages should be zero when there are no items.');
            $this->assertSame(1, $state['paged'], 'Paged should default to 1 when input is zero.');
        }
    }

    public function test_get_render_context_provides_filters_when_no_matches(): void
    {
        $this->configureOptions();
        $this->primeSnapshot($this->buildSnapshotWithPosts());

        $atts = \JLG\Notation\Shortcodes\GameExplorer::get_default_atts();
        $request = [
            'orderby'      => 'date',
            'order'        => 'DESC',
            'letter'       => 'Z',
            'category'     => '',
            'platform'     => '',
            'availability' => '',
            'search'       => '',
            'paged'        => 1,
        ];

        $context = \JLG\Notation\Shortcodes\GameExplorer::get_render_context($atts, $request);

        $this->assertSame([], $context['games'], 'No games should be returned for an unmatched letter filter.');
        $this->assertSame(0, $context['total_items'], 'Total items should be zero when no posts match.');
        $this->assertNotEmpty($context['categories_list'], 'Categories should still be provided when no games match.');
        $this->assertSame([
            ['value' => '11', 'label' => 'Action'],
        ], $context['categories_list'], 'Categories list should reflect snapshot data.');
        $this->assertNotEmpty($context['platforms_list'], 'Platforms should still be provided when no games match.');
        $this->assertSame([
            ['value' => 'pc', 'label' => 'PC'],
            ['value' => 'playstation-5', 'label' => 'PlayStation 5'],
        ], $context['platforms_list'], 'Platforms list should reflect snapshot data.');
        $this->assertSame([
            'available'  => esc_html__('Disponible', 'notation-jlg'),
            'upcoming'   => esc_html__('À venir', 'notation-jlg'),
            'unknown'    => esc_html__('À confirmer', 'notation-jlg'),
        ], $context['availability_options'], 'Availability options should remain available for the filters.');
        $this->assertNotEmpty($context['years_list'], 'Years list should be exposed when year filters are enabled.');
        $this->assertSame('2023', $context['years_list'][0]['value']);
        $this->assertSame(1, $context['years_list'][0]['count']);
        $this->assertSame('<p>' . esc_html__('Aucun jeu ne correspond à vos filtres actuels.', 'notation-jlg') . '</p>', $context['message']);
    }

    public function test_namespaced_request_parameters_are_isolated_between_instances(): void
    {
        $this->configureOptions();
        $this->primeSnapshot($this->buildSnapshotWithPosts());

        $attsOne = \JLG\Notation\Shortcodes\GameExplorer::get_default_atts();
        $attsOne['id'] = 'first-explorer';

        $attsTwo = \JLG\Notation\Shortcodes\GameExplorer::get_default_atts();
        $attsTwo['id'] = 'second-explorer';

        $request = [
            'letter__first-explorer'   => 'A',
            'orderby__first-explorer'  => 'title',
            'order__first-explorer'    => 'ASC',
            'letter__second-explorer'  => 'B',
            'category__second-explorer'=> '11',
        ];

        $contextOne = \JLG\Notation\Shortcodes\GameExplorer::get_render_context($attsOne, $request);
        $contextTwo = \JLG\Notation\Shortcodes\GameExplorer::get_render_context($attsTwo, $request);

        $this->assertSame('A', $contextOne['current_filters']['letter']);
        $this->assertSame('', $contextOne['current_filters']['category']);
        $this->assertSame('title', $contextOne['sort_key']);
        $this->assertSame('ASC', $contextOne['sort_order']);

        $this->assertSame('B', $contextTwo['current_filters']['letter']);
        $this->assertSame('11', $contextTwo['current_filters']['category']);
        $this->assertSame('date', $contextTwo['sort_key']);
        $this->assertSame('DESC', $contextTwo['sort_order']);
    }

    public function test_handle_game_explorer_sort_injects_cover_image_markup(): void
    {
        $this->configureOptions();
        $this->primeSnapshot($this->buildSnapshotWithPosts());

        $this->registerPost(101, 'Alpha Quest', 'Alpha content for the cover test.', '2023-01-01 10:00:00');
        $this->registerPost(202, 'Beta Strike', 'Beta content for the cover test.', '2023-01-05 11:30:00');

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

        $response = $this->dispatchExplorerAjax([
            'nonce'          => 'nonce-jlg_game_explorer',
            'container_id'   => 'cover-test',
            'posts_per_page' => '6',
            'columns'        => '3',
            'filters'        => $this->getDefaultFiltersString(),
            'orderby'        => 'date',
            'order'          => 'DESC',
            'paged'          => '1',
        ]);

        $this->assertArrayHasKey('html', $response);
        $this->assertStringContainsString('https://example.com/alpha.jpg', $response['html']);
        $this->assertStringContainsString('Visuel indisponible', $response['html']);
    }

    public function test_handle_game_explorer_sort_filters_by_developer(): void
    {
        $this->configureOptions();
        $this->primeSnapshot($this->buildSnapshotWithPosts());

        $this->registerPost(101, 'Alpha Quest', 'Alpha content for the developer filter.', '2023-01-01 10:00:00');
        $this->registerPost(202, 'Beta Strike', 'Beta content for the developer filter.', '2023-01-05 11:30:00');

        $this->setMeta(101, [
            '_jlg_average_score'   => 8.6,
            '_jlg_cover_image_url' => 'https://example.com/alpha.jpg',
            '_jlg_date_sortie'     => '2023-02-14',
            '_jlg_developpeur'     => 'Studio Alpha',
            '_jlg_editeur'         => 'Publisher A',
            '_jlg_plateformes'     => ['PC', 'PlayStation 5'],
        ]);
        $this->setMeta(202, [
            '_jlg_average_score'   => 7.4,
            '_jlg_cover_image_url' => 'https://example.com/beta.jpg',
            '_jlg_date_sortie'     => '2022-11-10',
            '_jlg_developpeur'     => 'Studio Beta',
            '_jlg_editeur'         => 'Publisher B',
            '_jlg_plateformes'     => ['PC'],
        ]);

        $response = $this->dispatchExplorerAjax([
            'nonce'          => 'nonce-jlg_game_explorer',
            'container_id'   => 'developer-filter',
            'posts_per_page' => '6',
            'columns'        => '3',
            'filters'        => $this->getDefaultFiltersString(),
            'orderby'        => 'date',
            'order'          => 'DESC',
            'developer'      => 'Studio Alpha',
            'paged'          => '1',
        ]);

        $this->assertArrayHasKey('state', $response);
        $this->assertSame('Studio Alpha', $response['state']['developer'] ?? null);
        $this->assertSame(1, $response['state']['total_items'] ?? 0);
        $this->assertArrayHasKey('config', $response);
        $this->assertSame('Studio Alpha', $response['config']['state']['developer'] ?? null);
        $this->assertStringContainsString('Studio Alpha', $response['html'] ?? '');
        $this->assertStringNotContainsString('Studio Beta', $response['html'] ?? '');
    }

    public function test_handle_game_explorer_sort_filters_by_year(): void
    {
        $this->configureOptions();
        $this->primeSnapshot($this->buildSnapshotWithPosts());

        $this->registerPost(101, 'Alpha Quest', 'Alpha content for the year filter.', '2023-01-01 10:00:00');
        $this->registerPost(202, 'Beta Strike', 'Beta content for the year filter.', '2023-01-05 11:30:00');

        $this->setMeta(101, [
            '_jlg_average_score'   => 8.2,
            '_jlg_cover_image_url' => 'https://example.com/alpha-year.jpg',
            '_jlg_date_sortie'     => '2023-02-14',
            '_jlg_developpeur'     => 'Studio Alpha',
            '_jlg_editeur'         => 'Publisher A',
            '_jlg_plateformes'     => ['PC', 'PlayStation 5'],
        ]);

        $this->setMeta(202, [
            '_jlg_average_score'   => 7.1,
            '_jlg_cover_image_url' => 'https://example.com/beta-year.jpg',
            '_jlg_date_sortie'     => '2022-11-10',
            '_jlg_developpeur'     => 'Studio Beta',
            '_jlg_editeur'         => 'Publisher B',
            '_jlg_plateformes'     => ['PC'],
        ]);

        $response = $this->dispatchExplorerAjax([
            'nonce'          => 'nonce-jlg_game_explorer',
            'container_id'   => 'year-filter',
            'posts_per_page' => '6',
            'columns'        => '3',
            'filters'        => $this->getDefaultFiltersString(),
            'orderby'        => 'date',
            'order'          => 'DESC',
            'year'           => '2023',
            'paged'          => '1',
        ]);

        $this->assertArrayHasKey('state', $response);
        $this->assertSame('2023', $response['state']['year'] ?? null);
        $this->assertSame(1, $response['state']['total_items'] ?? 0);
        $this->assertArrayHasKey('config', $response);
        $this->assertSame('2023', $response['config']['state']['year'] ?? null);
        $this->assertStringContainsString('Alpha Quest', $response['html'] ?? '');
        $this->assertStringNotContainsString('Beta Strike', $response['html'] ?? '');
    }

    public function test_handle_game_explorer_sort_filters_by_min_score(): void
    {
        $this->configureOptions();
        $this->primeSnapshot($this->buildSnapshotWithPosts());

        $this->registerPost(101, 'Alpha Quest', 'Alpha content for the score filter.', '2023-01-01 10:00:00');
        $this->registerPost(202, 'Beta Strike', 'Beta content for the score filter.', '2023-01-05 11:30:00');

        $this->setMeta(101, [
            '_jlg_average_score'   => 8.6,
            '_jlg_cover_image_url' => 'https://example.com/alpha-score.jpg',
            '_jlg_date_sortie'     => '2023-02-14',
            '_jlg_developpeur'     => 'Studio Alpha',
            '_jlg_editeur'         => 'Publisher A',
            '_jlg_plateformes'     => ['PC', 'PlayStation 5'],
        ]);

        $this->setMeta(202, [
            '_jlg_average_score'   => 7.4,
            '_jlg_cover_image_url' => 'https://example.com/beta-score.jpg',
            '_jlg_date_sortie'     => '2022-11-10',
            '_jlg_developpeur'     => 'Studio Beta',
            '_jlg_editeur'         => 'Publisher B',
            '_jlg_plateformes'     => ['PC'],
        ]);

        $response = $this->dispatchExplorerAjax([
            'nonce'          => 'nonce-jlg_game_explorer',
            'container_id'   => 'score-filter',
            'posts_per_page' => '6',
            'columns'        => '3',
            'filters'        => $this->getDefaultFiltersString(),
            'orderby'        => 'date',
            'order'          => 'DESC',
            'score'          => '8',
            'paged'          => '1',
        ]);

        $this->assertArrayHasKey('state', $response);
        $this->assertSame('8', $response['state']['score'] ?? null);
        $this->assertSame(1, $response['state']['total_items'] ?? 0);
        $this->assertArrayHasKey('config', $response);
        $this->assertSame('8', $response['config']['state']['score'] ?? null);
        $this->assertSame(10, $response['config']['meta']['scores']['max'] ?? null);
        $this->assertStringContainsString('Alpha Quest', $response['html'] ?? '');
        $this->assertStringNotContainsString('Beta Strike', $response['html'] ?? '');
    }

    public function test_handle_game_explorer_sort_supports_accent_insensitive_search(): void
    {
        $this->configureOptions();

        $snapshot = [
            'posts' => [
                301 => [
                    'letter'        => 'E',
                    'category_ids'  => [21],
                    'category_slugs'=> ['aventure'],
                    'primary_genre' => 'Aventure',
                    'platform_labels' => ['PC'],
                    'platform_slugs'  => ['pc'],
                    'developer'     => 'Studio Élan',
                    'developer_key' => 'studio elan',
                    'publisher'     => 'Éditions Futur',
                    'publisher_key' => 'editions futur',
                    'release_iso'   => '2024-03-10',
                    'release_year'  => 2024,
                    'availability'  => 'upcoming',
                    'search_index'  => ' epopee legende studio elan editions futur aventure pc ',
                    'popularity'    => 0,
                    'index_meta'    => [
                        'letter'        => 'E',
                        'developer'     => 'studio elan',
                        'publisher'     => 'editions futur',
                        'availability'  => 'upcoming',
                        'release_year'  => '2024',
                        'search_index'  => ' epopee legende studio elan editions futur aventure pc ',
                        'platform_index'=> '|pc|',
                    ],
                ],
                302 => [
                    'letter'        => 'C',
                    'category_ids'  => [21],
                    'category_slugs'=> ['aventure'],
                    'primary_genre' => 'Aventure',
                    'platform_labels' => ['PC'],
                    'platform_slugs'  => ['pc'],
                    'developer'     => 'Chrono Team',
                    'developer_key' => 'chrono team',
                    'publisher'     => 'Publisher C',
                    'publisher_key' => 'publisher c',
                    'release_iso'   => '2023-09-15',
                    'release_year'  => 2023,
                    'availability'  => 'available',
                    'search_index'  => ' chroniques brulees chrono team publisher c aventure pc ',
                    'popularity'    => 0,
                    'index_meta'    => [
                        'letter'        => 'C',
                        'developer'     => 'chrono team',
                        'publisher'     => 'publisher c',
                        'availability'  => 'available',
                        'release_year'  => '2023',
                        'search_index'  => ' chroniques brulees chrono team publisher c aventure pc ',
                        'platform_index'=> '|pc|',
                    ],
                ],
            ],
            'letters_map'    => ['E' => true, 'C' => true],
            'categories_map' => [21 => 'Aventure'],
            'platforms_map'  => ['pc' => 'PC'],
            'developers_map' => ['studio elan' => 'Studio Élan', 'chrono team' => 'Chrono Team'],
            'publishers_map' => ['editions futur' => 'Éditions Futur', 'publisher c' => 'Publisher C'],
            'search_tokens'  => [
                'epopee'   => 1,
                'legende'  => 1,
                'studio'   => 1,
                'elan'     => 1,
                'editions' => 1,
                'futur'    => 1,
                'chroniques'=> 1,
                'brulees'  => 1,
            ],
            'years'          => [
                'min'     => 2023,
                'max'     => 2024,
                'buckets' => [
                    2023 => 1,
                    2024 => 1,
                ],
            ],
        ];

        $this->primeSnapshot($snapshot);

        $this->registerPost(301, 'Épopée Légendaire', 'Une grande aventure héroïque.', '2024-01-01 10:00:00');
        $this->registerPost(302, 'Chroniques Brûlées', 'Un récit alternatif.', '2023-08-10 09:00:00');

        $GLOBALS['jlg_test_meta'][301]['_jlg_average_score'] = 9.1;
        $GLOBALS['jlg_test_meta'][301]['_jlg_date_sortie'] = '2024-03-10';
        $GLOBALS['jlg_test_meta'][301]['_jlg_developpeur'] = 'Studio Élan';
        $GLOBALS['jlg_test_meta'][301]['_jlg_editeur'] = 'Éditions Futur';
        $GLOBALS['jlg_test_meta'][301]['_jlg_plateformes'] = ['PC'];

        $GLOBALS['jlg_test_meta'][302]['_jlg_average_score'] = 7.0;
        $GLOBALS['jlg_test_meta'][302]['_jlg_date_sortie'] = '2023-09-15';
        $GLOBALS['jlg_test_meta'][302]['_jlg_developpeur'] = 'Chrono Team';
        $GLOBALS['jlg_test_meta'][302]['_jlg_editeur'] = 'Publisher C';
        $GLOBALS['jlg_test_meta'][302]['_jlg_plateformes'] = ['PC'];

        $response = $this->dispatchExplorerAjax([
            'nonce'          => 'nonce-jlg_game_explorer',
            'container_id'   => 'accent-search',
            'posts_per_page' => '6',
            'columns'        => '3',
            'filters'        => $this->getDefaultFiltersString(),
            'orderby'        => 'date',
            'order'          => 'DESC',
            'search'         => 'épopée',
            'paged'          => '1',
        ]);

        $this->assertSame('épopée', $response['state']['search'] ?? '');
        $this->assertSame(1, $response['state']['total_items'] ?? 0);
        $this->assertStringContainsString('Épopée Légendaire', $response['html'] ?? '');
        $this->assertStringNotContainsString('Chroniques Brûlées', $response['html'] ?? '');
        $this->assertContains('epopee', $response['config']['suggestions']['search'] ?? []);
    }

    public function test_handle_game_explorer_sort_supports_multi_term_search(): void
    {
        $this->configureOptions();
        $this->primeSnapshot($this->buildSnapshotWithPosts());

        $this->registerPost(101, 'Alpha Quest', 'Alpha content for the developer filter.', '2023-01-01 10:00:00');
        $this->registerPost(202, 'Beta Strike', 'Beta content for the developer filter.', '2023-01-05 11:30:00');

        $GLOBALS['jlg_test_meta'][101]['_jlg_average_score'] = 8.6;
        $GLOBALS['jlg_test_meta'][101]['_jlg_date_sortie'] = '2023-02-14';
        $GLOBALS['jlg_test_meta'][101]['_jlg_developpeur'] = 'Studio Alpha';
        $GLOBALS['jlg_test_meta'][101]['_jlg_editeur'] = 'Publisher A';
        $GLOBALS['jlg_test_meta'][101]['_jlg_plateformes'] = ['PC', 'PlayStation 5'];

        $GLOBALS['jlg_test_meta'][202]['_jlg_average_score'] = 7.4;
        $GLOBALS['jlg_test_meta'][202]['_jlg_date_sortie'] = '2022-11-10';
        $GLOBALS['jlg_test_meta'][202]['_jlg_developpeur'] = 'Studio Beta';
        $GLOBALS['jlg_test_meta'][202]['_jlg_editeur'] = 'Publisher B';
        $GLOBALS['jlg_test_meta'][202]['_jlg_plateformes'] = ['PC'];

        $response = $this->dispatchExplorerAjax([
            'nonce'          => 'nonce-jlg_game_explorer',
            'container_id'   => 'multi-term-search',
            'posts_per_page' => '6',
            'columns'        => '3',
            'filters'        => $this->getDefaultFiltersString(),
            'orderby'        => 'date',
            'order'          => 'DESC',
            'search'         => 'alpha quest',
            'paged'          => '1',
        ]);

        $this->assertSame(1, $response['state']['total_items'] ?? 0);
        $this->assertStringContainsString('Alpha Quest', $response['html'] ?? '');
        $this->assertStringNotContainsString('Beta Strike', $response['html'] ?? '');
    }

    public function test_handle_game_explorer_sort_orders_by_popularity_then_date(): void
    {
        $this->configureOptions();

        $snapshot = [
            'posts' => [
                501 => [
                    'letter'        => 'G',
                    'category_ids'  => [11],
                    'category_slugs'=> ['action'],
                    'primary_genre' => 'Action',
                    'platform_labels' => ['PC'],
                    'platform_slugs'  => ['pc'],
                    'developer'     => 'Studio Gamma',
                    'developer_key' => 'studio gamma',
                    'publisher'     => 'Publisher G',
                    'publisher_key' => 'publisher g',
                    'release_iso'   => '2023-05-20',
                    'release_year'  => 2023,
                    'availability'  => 'available',
                    'search_index'  => ' gamma horizon studio gamma publisher g action pc ',
                    'popularity'    => 50,
                    'index_meta'    => [
                        'letter'        => 'G',
                        'developer'     => 'studio gamma',
                        'publisher'     => 'publisher g',
                        'availability'  => 'available',
                        'release_year'  => '2023',
                        'search_index'  => ' gamma horizon studio gamma publisher g action pc ',
                        'platform_index'=> '|pc|',
                    ],
                ],
                502 => [
                    'letter'        => 'D',
                    'category_ids'  => [11],
                    'category_slugs'=> ['action'],
                    'primary_genre' => 'Action',
                    'platform_labels' => ['PC'],
                    'platform_slugs'  => ['pc'],
                    'developer'     => 'Studio Delta',
                    'developer_key' => 'studio delta',
                    'publisher'     => 'Publisher D',
                    'publisher_key' => 'publisher d',
                    'release_iso'   => '2024-01-10',
                    'release_year'  => 2024,
                    'availability'  => 'available',
                    'search_index'  => ' delta shift studio delta publisher d action pc ',
                    'popularity'    => 75,
                    'index_meta'    => [
                        'letter'        => 'D',
                        'developer'     => 'studio delta',
                        'publisher'     => 'publisher d',
                        'availability'  => 'available',
                        'release_year'  => '2024',
                        'search_index'  => ' delta shift studio delta publisher d action pc ',
                        'platform_index'=> '|pc|',
                    ],
                ],
                503 => [
                    'letter'        => 'O',
                    'category_ids'  => [11],
                    'category_slugs'=> ['action'],
                    'primary_genre' => 'Action',
                    'platform_labels' => ['PC'],
                    'platform_slugs'  => ['pc'],
                    'developer'     => 'Studio Omega',
                    'developer_key' => 'studio omega',
                    'publisher'     => 'Publisher O',
                    'publisher_key' => 'publisher o',
                    'release_iso'   => '2022-12-01',
                    'release_year'  => 2022,
                    'availability'  => 'available',
                    'search_index'  => ' omega flash studio omega publisher o action pc ',
                    'popularity'    => 10,
                    'index_meta'    => [
                        'letter'        => 'O',
                        'developer'     => 'studio omega',
                        'publisher'     => 'publisher o',
                        'availability'  => 'available',
                        'release_year'  => '2022',
                        'search_index'  => ' omega flash studio omega publisher o action pc ',
                        'platform_index'=> '|pc|',
                    ],
                ],
            ],
            'letters_map'    => ['G' => true, 'D' => true, 'O' => true],
            'categories_map' => [11 => 'Action'],
            'platforms_map'  => ['pc' => 'PC'],
            'developers_map' => ['studio gamma' => 'Studio Gamma', 'studio delta' => 'Studio Delta', 'studio omega' => 'Studio Omega'],
            'publishers_map' => ['publisher g' => 'Publisher G', 'publisher d' => 'Publisher D', 'publisher o' => 'Publisher O'],
            'search_tokens'  => ['gamma' => 1, 'horizon' => 1, 'delta' => 1, 'shift' => 1, 'omega' => 1, 'flash' => 1],
            'years'          => [
                'min'     => 2022,
                'max'     => 2024,
                'buckets' => [
                    2022 => 1,
                    2023 => 1,
                    2024 => 1,
                ],
            ],
        ];

        $this->primeSnapshot($snapshot);

        $this->registerPost(501, 'Gamma Horizon', 'Gamma content.', '2023-05-20 09:00:00');
        $this->registerPost(502, 'Delta Shift', 'Delta content.', '2024-01-10 11:00:00');
        $this->registerPost(503, 'Omega Flash', 'Omega content.', '2022-12-01 12:00:00');

        $GLOBALS['jlg_test_meta'][501]['_jlg_user_rating_count'] = 50;
        $GLOBALS['jlg_test_meta'][502]['_jlg_user_rating_count'] = 75;
        $GLOBALS['jlg_test_meta'][503]['_jlg_user_rating_count'] = 10;

        $response = $this->dispatchExplorerAjax([
            'nonce'          => 'nonce-jlg_game_explorer',
            'container_id'   => 'popularity-order',
            'posts_per_page' => '6',
            'columns'        => '3',
            'filters'        => $this->getDefaultFiltersString(),
            'orderby'        => 'popularity|DESC',
            'order'          => 'DESC',
            'paged'          => '1',
        ]);

        $this->assertSame('popularity', $response['state']['orderby'] ?? '');
        $this->assertSame('DESC', $response['state']['order'] ?? '');
        $html = $response['html'] ?? '';
        $this->assertNotEmpty($html);
        $gamma_position = strpos($html, 'Gamma Horizon');
        $delta_position = strpos($html, 'Delta Shift');
        $omega_position = strpos($html, 'Omega Flash');

        $this->assertNotFalse($gamma_position, 'Gamma Horizon should appear in the markup.');
        $this->assertNotFalse($delta_position, 'Delta Shift should appear in the markup.');
        $this->assertNotFalse($omega_position, 'Omega Flash should appear in the markup.');

        $this->assertLessThan(
            $gamma_position,
            $delta_position,
            'Most popular title should appear first in the markup.'
        );
        $this->assertLessThan(
            $omega_position,
            $gamma_position,
            'Second most popular title should appear before the least popular entry.'
        );
    }

    public function test_score_position_modifier_reflects_configuration(): void
    {
        $this->configureOptions();
        $this->primeSnapshot($this->buildSnapshotWithPosts());

        $this->registerPost(101, 'Alpha Quest', 'Alpha content for the position test.', '2023-01-01 10:00:00');
        $this->registerPost(202, 'Beta Strike', 'Beta content for the position test.', '2023-01-05 11:30:00');

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
            '_jlg_cover_image_url' => 'https://example.com/beta.jpg',
            '_jlg_date_sortie'     => '2022-11-10',
            '_jlg_developpeur'     => 'Studio Beta',
            '_jlg_editeur'         => 'Publisher B',
            '_jlg_plateformes'     => ['PC'],
        ]);

        $basePost = [
            'nonce'          => 'nonce-jlg_game_explorer',
            'container_id'   => 'position-test',
            'posts_per_page' => '6',
            'columns'        => '3',
            'filters'        => $this->getDefaultFiltersString(),
            'orderby'        => 'date',
            'order'          => 'DESC',
            'paged'          => '1',
        ];

        $defaultResponse = $this->dispatchExplorerAjax($basePost);
        $this->assertArrayHasKey('html', $defaultResponse);
        $this->assertStringContainsString('jlg-ge-card__score--bottom-right', $defaultResponse['html']);
        $this->assertSame('bottom-right', $defaultResponse['config']['atts']['score_position'] ?? null);

        $GLOBALS['jlg_test_options']['notation_jlg_settings']['game_explorer_score_position'] = 'top-left';
        \JLG\Notation\Helpers::flush_plugin_options_cache();

        $topLeftResponse = $this->dispatchExplorerAjax($basePost);
        $this->assertArrayHasKey('html', $topLeftResponse);
        $this->assertStringContainsString('jlg-ge-card__score--top-left', $topLeftResponse['html']);
        $this->assertSame('top-left', $topLeftResponse['config']['atts']['score_position'] ?? null);

        $overridePost = $basePost;
        $overridePost['score_position'] = 'middle-right';

        $overrideResponse = $this->dispatchExplorerAjax($overridePost);
        $this->assertArrayHasKey('html', $overrideResponse);
        $this->assertStringContainsString('jlg-ge-card__score--middle-right', $overrideResponse['html']);
        $this->assertSame('middle-right', $overrideResponse['config']['atts']['score_position'] ?? null);
    }

    private function dispatchExplorerAjax(array $post): array
    {
        $_POST = $post;
        $frontend = new \JLG\Notation\Frontend();
        $payload = null;

        try {
            $frontend->handle_game_explorer_sort();
            $this->fail('Expected WP_Send_Json_Exception to be thrown.');
        } catch (WP_Send_Json_Exception $exception) {
            $this->assertTrue($exception->success, 'Ajax handler should respond with success payload.');
            $this->assertIsArray($exception->data, 'Ajax payload should be an array.');
            $payload = $exception->data;
        } finally {
            $_POST = [];
        }

        $this->assertIsArray($payload, 'Ajax payload should not be null.');
        $this->assertArrayHasKey('config', $payload);
        $this->assertIsArray($payload['config']);

        return $payload;
    }

    public function test_snapshot_cleared_after_relevant_meta_update(): void
    {
        $this->configureOptions();
        $this->registerPost(777, 'Gamma Horizon', 'Content body', '2023-01-10 09:00:00');

        $this->primeSnapshot($this->buildSnapshotWithPosts());
        set_transient('jlg_game_explorer_snapshot_v1', ['cached' => true]);

        \JLG\Notation\Shortcodes\GameExplorer::maybe_clear_filters_snapshot_for_meta(0, 777, '_jlg_developpeur', 'Studio Gamma');

        $this->assertFalse(get_transient('jlg_game_explorer_snapshot_v1'), 'Transient cache should be cleared after meta update.');

        $reflection = new ReflectionClass(\JLG\Notation\Shortcodes\GameExplorer::class);
        $property = $reflection->getProperty('filters_snapshot');
        $property->setAccessible(true);

        $this->assertNull($property->getValue(), 'Static snapshot cache should be reset after meta update.');
    }

    public function test_build_filters_snapshot_preloads_caches_without_changing_output(): void
    {
        $this->configureOptions();
        $this->registerPost(101, 'Alpha Quest', 'Alpha content for the first test post.', '2023-01-01 10:00:00');
        $this->registerPost(202, 'Beta Strike', 'Beta content for the second test post.', '2023-01-05 11:30:00');

        $this->setMeta(101, [
            '_jlg_game_title'  => 'Alpha Quest',
            '_jlg_developpeur' => 'Studio Alpha',
            '_jlg_editeur'     => 'Publisher A',
            '_jlg_date_sortie' => '2023-02-14',
            '_jlg_plateformes' => ['PC', 'PlayStation 5'],
        ]);

        $this->setMeta(202, [
            '_jlg_game_title'  => 'Beta Strike',
            '_jlg_developpeur' => 'Studio Beta',
            '_jlg_editeur'     => 'Publisher B',
            '_jlg_date_sortie' => '2022-11-10',
            '_jlg_plateformes' => ['PC'],
        ]);

        $GLOBALS['jlg_test_terms'] = [
            101 => [
                'category' => [
                    ['term_id' => 11, 'name' => 'Action', 'slug' => 'action'],
                ],
            ],
            202 => [
                'category' => [
                    ['term_id' => 11, 'name' => 'Action', 'slug' => 'action'],
                ],
            ],
        ];

        $GLOBALS['jlg_test_meta_cache_calls'] = [];
        $GLOBALS['jlg_test_term_cache_calls'] = [];

        set_transient('jlg_rated_post_ids_v1', [101, '202', 'not-a-number']);

        $reflection = new ReflectionMethod(\JLG\Notation\Shortcodes\GameExplorer::class, 'build_filters_snapshot');
        $reflection->setAccessible(true);

        $snapshot = $reflection->invoke(null);

        $this->assertSame($this->buildSnapshotWithPosts(), $snapshot, 'Primed snapshot should match expected output.');

        $this->assertNotEmpty($GLOBALS['jlg_test_meta_cache_calls'], 'Meta cache priming should occur before building the snapshot.');
        $this->assertCount(1, $GLOBALS['jlg_test_meta_cache_calls'], 'Meta cache priming should occur in a single batched call for two posts.');
        $this->assertSame('post', $GLOBALS['jlg_test_meta_cache_calls'][0][0]);
        $this->assertSame([101, 202], $GLOBALS['jlg_test_meta_cache_calls'][0][1]);

        $this->assertNotEmpty($GLOBALS['jlg_test_term_cache_calls'], 'Term cache priming should occur before building the snapshot.');
        $this->assertCount(1, $GLOBALS['jlg_test_term_cache_calls'], 'Term cache priming should occur in a single batched call for two posts.');
        $this->assertSame([101, 202], $GLOBALS['jlg_test_term_cache_calls'][0][0]);
        $this->assertSame('post', $GLOBALS['jlg_test_term_cache_calls'][0][1]);
        $this->assertSame(['category'], $GLOBALS['jlg_test_term_cache_calls'][0][2]);
    }

    private function configureOptions(): void
    {
        $defaults = \JLG\Notation\Helpers::get_default_settings();
        $defaults['game_explorer_posts_per_page'] = 2;
        $defaults['game_explorer_filters'] = \JLG\Notation\Helpers::get_default_game_explorer_filters();
        $defaults['game_explorer_score_position'] = \JLG\Notation\Helpers::normalize_game_explorer_score_position('');

        $GLOBALS['jlg_test_options']['notation_jlg_settings'] = $defaults;
        $GLOBALS['jlg_test_options']['jlg_platforms_list'] = [];
        \JLG\Notation\Helpers::flush_plugin_options_cache();
    }

    private function registerPost(int $post_id, string $title, string $content, string $post_date, string $post_type = 'post'): void
    {
        $GLOBALS['jlg_test_posts'][$post_id] = new WP_Post([
            'ID'            => $post_id,
            'post_type'     => $post_type,
            'post_status'   => 'publish',
            'post_title'    => $title,
            'post_content'  => $content,
            'post_date'     => $post_date,
            'post_date_gmt' => $post_date,
        ]);
    }

    private function setMeta(int $post_id, array $meta): void
    {
        if (!isset($GLOBALS['jlg_test_meta'][$post_id])) {
            $GLOBALS['jlg_test_meta'][$post_id] = [];
        }

        $GLOBALS['jlg_test_meta'][$post_id] = array_merge($GLOBALS['jlg_test_meta'][$post_id], $meta);
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

    public function test_build_filters_snapshot_primes_custom_post_type_term_cache_without_extra_calls(): void
    {
        $this->configureOptions();

        $GLOBALS['jlg_test_options']['notation_jlg_settings']['allowed_post_types'] = ['post', 'jlg_review'];
        \JLG\Notation\Helpers::flush_plugin_options_cache();

        $this->registerPost(101, 'Alpha Quest', 'Alpha content for the first test post.', '2023-01-01 10:00:00', 'post');
        $this->registerPost(303, 'Custom Saga', 'Custom content for the second test post.', '2024-03-15 09:30:00', 'jlg_review');

        $bulk_post_ids = [];
        for ($i = 0; $i < 100; $i++) {
            $post_id = 400 + $i;
            $bulk_post_ids[] = $post_id;
            $this->registerPost($post_id, 'Bulk Post ' . $post_id, 'Bulk content ' . $post_id, '2024-01-01 08:00:00', 'post');
        }

        $this->setMeta(101, [
            '_jlg_game_title'  => 'Alpha Quest',
            '_jlg_developpeur' => 'Studio Alpha',
            '_jlg_editeur'     => 'Publisher A',
            '_jlg_date_sortie' => '2023-02-14',
            '_jlg_plateformes' => ['PC', 'PlayStation 5'],
        ]);

        $this->setMeta(303, [
            '_jlg_game_title'  => 'Custom Saga',
            '_jlg_developpeur' => 'Studio Custom',
            '_jlg_editeur'     => 'Publisher C',
            '_jlg_date_sortie' => '2024-05-21',
            '_jlg_plateformes' => ['Nintendo Switch'],
        ]);

        $GLOBALS['jlg_test_terms'] = [
            101 => [
                'category' => [
                    ['term_id' => 11, 'name' => 'Action', 'slug' => 'action'],
                ],
            ],
            303 => [
                'category' => [
                    ['term_id' => 12, 'name' => 'Adventure', 'slug' => 'adventure'],
                ],
            ],
        ];

        $GLOBALS['jlg_test_meta_cache_calls'] = [];
        $GLOBALS['jlg_test_term_cache_calls'] = [];

        $rated_post_ids = array_merge([101], $bulk_post_ids, [303]);
        set_transient('jlg_rated_post_ids_v1', $rated_post_ids);

        $reflection = new ReflectionMethod(\JLG\Notation\Shortcodes\GameExplorer::class, 'build_filters_snapshot');
        $reflection->setAccessible(true);

        $snapshot = $reflection->invoke(null);

        $this->assertArrayHasKey('posts', $snapshot);
        $this->assertArrayHasKey(101, $snapshot['posts']);
        $this->assertArrayHasKey(303, $snapshot['posts']);

        $this->assertNotEmpty($GLOBALS['jlg_test_term_cache_calls'], 'Term cache priming should occur with custom post types.');

        $post_type_calls = array_values(array_filter(
            $GLOBALS['jlg_test_term_cache_calls'],
            static function (array $call): bool {
                return $call[1] === 'post';
            }
        ));

        $custom_type_calls = array_values(array_filter(
            $GLOBALS['jlg_test_term_cache_calls'],
            static function (array $call): bool {
                return $call[1] === 'jlg_review';
            }
        ));

        $this->assertNotEmpty($post_type_calls, 'At least one term cache priming call should occur for standard posts.');
        $this->assertCount(1, $custom_type_calls, 'Custom post types should be primed exactly once.');

        $combined_post_ids = [];

        foreach ($post_type_calls as $post_cache_call) {
            $this->assertSame(['category'], $post_cache_call[2], 'Post term cache priming should target the category taxonomy.');
            $this->assertLessThanOrEqual(100, count($post_cache_call[0]), 'Post term cache priming should respect the batching size.');
            $combined_post_ids = array_merge($combined_post_ids, $post_cache_call[0]);
        }

        $this->assertSame(['category'], $custom_type_calls[0][2], 'Custom post type priming should target the category taxonomy.');
        $this->assertSame([303], $custom_type_calls[0][0], 'Custom post type priming should include the expected post IDs.');

        $expected_post_ids = array_merge([101], $bulk_post_ids);
        sort($expected_post_ids);

        $combined_post_ids = array_values(array_map('intval', $combined_post_ids));
        sort($combined_post_ids);

        $this->assertSame($expected_post_ids, $combined_post_ids, 'Post term cache priming should cover all rated post IDs.');
    }

    private function getDefaultFiltersString(): string
    {
        return implode(',', \JLG\Notation\Helpers::get_default_game_explorer_filters());
    }

    private function resetEnvironment(): void
    {
        $GLOBALS['jlg_test_posts'] = [];
        $GLOBALS['jlg_test_meta'] = [];
        $GLOBALS['jlg_test_terms'] = [];
        $GLOBALS['jlg_test_options'] = [];
        $GLOBALS['jlg_test_transients'] = [];
        $GLOBALS['jlg_test_current_post_id'] = 0;
        $GLOBALS['jlg_test_meta_cache_calls'] = [];
        $GLOBALS['jlg_test_term_cache_calls'] = [];
        $_POST = [];
        $_REQUEST = [];
        $this->resetFrontendStatics();
        $this->resetSnapshot();
        \JLG\Notation\Helpers::flush_plugin_options_cache();
    }

    private function resetFrontendStatics(): void
    {
        $reflection = new ReflectionClass(\JLG\Notation\Frontend::class);
        $defaults = [
            'shortcode_errors'      => [],
            'instance'              => null,
            'shortcode_rendered'    => false,
            'assets_enqueued'       => false,
            'deferred_styles_hooked'=> false,
            'rendered_shortcodes'   => [],
        ];

        foreach ($defaults as $property => $value) {
            if ($reflection->hasProperty($property)) {
                $property_reflection = $reflection->getProperty($property);
                $property_reflection->setAccessible(true);
                $property_reflection->setValue(null, $value);
            }
        }
    }

    private function resetSnapshot(): void
    {
        $reflection = new ReflectionClass(\JLG\Notation\Shortcodes\GameExplorer::class);
        if ($reflection->hasProperty('filters_snapshot')) {
            $property = $reflection->getProperty('filters_snapshot');
            $property->setAccessible(true);
            $property->setValue(null, null);
        }
    }

    private function primeSnapshot(array $snapshot): void
    {
        $reflection = new ReflectionClass(\JLG\Notation\Shortcodes\GameExplorer::class);
        $property = $reflection->getProperty('filters_snapshot');
        $property->setAccessible(true);
        $property->setValue(null, $snapshot);

        $this->hydrateIndexMetaFromSnapshot($snapshot);
    }

    private function hydrateIndexMetaFromSnapshot(array $snapshot): void
    {
        if (!isset($snapshot['posts']) || !is_array($snapshot['posts'])) {
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

        foreach ($snapshot['posts'] as $post_id => $post_meta) {
            if (!isset($GLOBALS['jlg_test_meta'][$post_id])) {
                $GLOBALS['jlg_test_meta'][$post_id] = [];
            }

            if (!isset($post_meta['index_meta']) || !is_array($post_meta['index_meta'])) {
                continue;
            }

            foreach ($index_keys as $field => $meta_key) {
                if (!array_key_exists($field, $post_meta['index_meta'])) {
                    continue;
                }

                $value = $post_meta['index_meta'][$field];

                if ($value === '' || $value === null) {
                    continue;
                }

                $GLOBALS['jlg_test_meta'][$post_id][$meta_key] = $value;
            }
        }
    }
}

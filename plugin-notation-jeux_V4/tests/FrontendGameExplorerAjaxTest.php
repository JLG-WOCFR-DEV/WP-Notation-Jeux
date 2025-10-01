<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/shortcodes/class-jlg-shortcode-game-explorer.php';

if (!class_exists('WP_Query')) {
    class WP_Query
    {
        public $args;
        public $posts = [];
        public $post_count = 0;
        public $current_post = -1;
        public $max_num_pages = 0;

        public function __construct($args = [])
        {
            $this->args = is_array($args) ? $args : [];
            $post_ids = isset($this->args['post__in']) && is_array($this->args['post__in'])
                ? array_values(array_map('intval', $this->args['post__in']))
                : [];

            $posts = [];
            foreach ($post_ids as $post_id) {
                if (isset($GLOBALS['jlg_test_posts'][$post_id])) {
                    $posts[] = $GLOBALS['jlg_test_posts'][$post_id];
                }
            }

            $paged = isset($this->args['paged']) ? max(1, (int) $this->args['paged']) : 1;
            $per_page = isset($this->args['posts_per_page']) ? (int) $this->args['posts_per_page'] : count($posts);
            if ($per_page <= 0) {
                $per_page = max(1, count($posts));
            }

            $total_items = count($posts);
            $this->max_num_pages = $per_page > 0 ? (int) ceil($total_items / $per_page) : 0;
            if ($this->max_num_pages < 1 && $total_items > 0) {
                $this->max_num_pages = 1;
            }

            $offset = ($paged - 1) * $per_page;
            if ($offset < 0) {
                $offset = 0;
            }

            $this->posts = array_slice($posts, $offset, $per_page);
            $this->post_count = count($this->posts);
        }

        public function have_posts()
        {
            return ($this->current_post + 1) < $this->post_count;
        }

        public function the_post()
        {
            if (!$this->have_posts()) {
                return false;
            }

            $this->current_post++;
            $GLOBALS['post'] = $this->posts[$this->current_post];

            return $GLOBALS['post'];
        }
    }
}

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

        $GLOBALS['jlg_test_meta'] = [
            101 => [
                '_jlg_average_score'   => 8.6,
                '_jlg_cover_image_url' => 'https://example.com/alpha.jpg',
                '_jlg_date_sortie'     => '2023-02-14',
                '_jlg_developpeur'     => 'Studio Alpha',
                '_jlg_editeur'         => 'Publisher A',
                '_jlg_plateformes'     => ['PC', 'PlayStation 5'],
            ],
            202 => [
                '_jlg_average_score'   => 7.4,
                '_jlg_cover_image_url' => '',
                '_jlg_date_sortie'     => '2022-11-10',
                '_jlg_developpeur'     => 'Studio Beta',
                '_jlg_editeur'         => 'Publisher B',
                '_jlg_plateformes'     => ['PC'],
            ],
        ];

        $_POST = [
            'nonce'          => 'nonce-jlg_game_explorer',
            'container_id'   => ' invalid<container>',
            'posts_per_page' => '-3',
            'columns'        => '0',
            'filters'        => 'letter,category,platform,availability,search',
            'orderby'        => 'score;DROP TABLE',
            'order'          => 'downwards',
            'letter'         => [' alpha '],
            'category'       => [' 11 '],
            'platform'       => ['pc'],
            'availability'   => ['<script>'],
            'search'         => ['Alpha'],
            'paged'          => '-5',
        ];

        $frontend = new JLG_Frontend();

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
        ]);

        $_POST = [
            'nonce'   => 'nonce-jlg_game_explorer',
            'orderby' => 'invalid',
            'order'   => 'ascending',
            'paged'   => '0',
        ];

        $frontend = new JLG_Frontend();

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

        $atts = JLG_Shortcode_Game_Explorer::get_default_atts();
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

        $context = JLG_Shortcode_Game_Explorer::get_render_context($atts, $request);

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
        $this->assertSame('<p>' . esc_html__('Aucun jeu ne correspond à vos filtres actuels.', 'notation-jlg') . '</p>', $context['message']);
    }

    public function test_namespaced_request_parameters_are_isolated_between_instances(): void
    {
        $this->configureOptions();
        $this->primeSnapshot($this->buildSnapshotWithPosts());

        $attsOne = JLG_Shortcode_Game_Explorer::get_default_atts();
        $attsOne['id'] = 'first-explorer';

        $attsTwo = JLG_Shortcode_Game_Explorer::get_default_atts();
        $attsTwo['id'] = 'second-explorer';

        $request = [
            'letter__first-explorer'   => 'A',
            'orderby__first-explorer'  => 'title',
            'order__first-explorer'    => 'ASC',
            'letter__second-explorer'  => 'B',
            'category__second-explorer'=> '11',
        ];

        $contextOne = JLG_Shortcode_Game_Explorer::get_render_context($attsOne, $request);
        $contextTwo = JLG_Shortcode_Game_Explorer::get_render_context($attsTwo, $request);

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

        $GLOBALS['jlg_test_meta'] = [
            101 => [
                '_jlg_average_score'   => 8.5,
                '_jlg_cover_image_url' => 'https://example.com/alpha.jpg',
                '_jlg_date_sortie'     => '2023-02-14',
                '_jlg_developpeur'     => 'Studio Alpha',
                '_jlg_editeur'         => 'Publisher A',
                '_jlg_plateformes'     => ['PC', 'PlayStation 5'],
            ],
            202 => [
                '_jlg_average_score'   => 7.2,
                '_jlg_cover_image_url' => '',
                '_jlg_date_sortie'     => '2022-11-10',
                '_jlg_developpeur'     => 'Studio Beta',
                '_jlg_editeur'         => 'Publisher B',
                '_jlg_plateformes'     => ['PC'],
            ],
        ];

        $response = $this->dispatchExplorerAjax([
            'nonce'          => 'nonce-jlg_game_explorer',
            'container_id'   => 'cover-test',
            'posts_per_page' => '6',
            'columns'        => '3',
            'filters'        => 'letter,category,platform,availability,search',
            'orderby'        => 'date',
            'order'          => 'DESC',
            'paged'          => '1',
        ]);

        $this->assertArrayHasKey('html', $response);
        $this->assertStringContainsString('https://example.com/alpha.jpg', $response['html']);
        $this->assertStringContainsString('Visuel indisponible', $response['html']);
    }

    public function test_score_position_modifier_reflects_configuration(): void
    {
        $this->configureOptions();
        $this->primeSnapshot($this->buildSnapshotWithPosts());

        $this->registerPost(101, 'Alpha Quest', 'Alpha content for the position test.', '2023-01-01 10:00:00');
        $this->registerPost(202, 'Beta Strike', 'Beta content for the position test.', '2023-01-05 11:30:00');

        $GLOBALS['jlg_test_meta'] = [
            101 => [
                '_jlg_average_score'   => 8.5,
                '_jlg_cover_image_url' => 'https://example.com/alpha.jpg',
                '_jlg_date_sortie'     => '2023-02-14',
                '_jlg_developpeur'     => 'Studio Alpha',
                '_jlg_editeur'         => 'Publisher A',
                '_jlg_plateformes'     => ['PC', 'PlayStation 5'],
            ],
            202 => [
                '_jlg_average_score'   => 7.2,
                '_jlg_cover_image_url' => 'https://example.com/beta.jpg',
                '_jlg_date_sortie'     => '2022-11-10',
                '_jlg_developpeur'     => 'Studio Beta',
                '_jlg_editeur'         => 'Publisher B',
                '_jlg_plateformes'     => ['PC'],
            ],
        ];

        $basePost = [
            'nonce'          => 'nonce-jlg_game_explorer',
            'container_id'   => 'position-test',
            'posts_per_page' => '6',
            'columns'        => '3',
            'filters'        => 'letter,category,platform,availability,search',
            'orderby'        => 'date',
            'order'          => 'DESC',
            'paged'          => '1',
        ];

        $defaultResponse = $this->dispatchExplorerAjax($basePost);
        $this->assertArrayHasKey('html', $defaultResponse);
        $this->assertStringContainsString('jlg-ge-card__score--bottom-right', $defaultResponse['html']);
        $this->assertSame('bottom-right', $defaultResponse['config']['atts']['score_position'] ?? null);

        $GLOBALS['jlg_test_options']['notation_jlg_settings']['game_explorer_score_position'] = 'top-left';
        JLG_Helpers::flush_plugin_options_cache();

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
        $frontend = new JLG_Frontend();
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

        JLG_Shortcode_Game_Explorer::maybe_clear_filters_snapshot_for_meta(0, 777, '_jlg_developpeur', 'Studio Gamma');

        $this->assertFalse(get_transient('jlg_game_explorer_snapshot_v1'), 'Transient cache should be cleared after meta update.');

        $reflection = new ReflectionClass(JLG_Shortcode_Game_Explorer::class);
        $property = $reflection->getProperty('filters_snapshot');
        $property->setAccessible(true);

        $this->assertNull($property->getValue(), 'Static snapshot cache should be reset after meta update.');
    }

    public function test_build_filters_snapshot_preloads_caches_without_changing_output(): void
    {
        $this->configureOptions();
        $this->registerPost(101, 'Alpha Quest', 'Alpha content for the first test post.', '2023-01-01 10:00:00');
        $this->registerPost(202, 'Beta Strike', 'Beta content for the second test post.', '2023-01-05 11:30:00');

        $GLOBALS['jlg_test_meta'][101] = [
            '_jlg_game_title'    => 'Alpha Quest',
            '_jlg_developpeur'   => 'Studio Alpha',
            '_jlg_editeur'       => 'Publisher A',
            '_jlg_date_sortie'   => '2023-02-14',
            '_jlg_plateformes'   => ['PC', 'PlayStation 5'],
        ];

        $GLOBALS['jlg_test_meta'][202] = [
            '_jlg_game_title'    => 'Beta Strike',
            '_jlg_developpeur'   => 'Studio Beta',
            '_jlg_editeur'       => 'Publisher B',
            '_jlg_date_sortie'   => '2022-11-10',
            '_jlg_plateformes'   => ['PC'],
        ];

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

        $reflection = new ReflectionMethod(JLG_Shortcode_Game_Explorer::class, 'build_filters_snapshot');
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
        $defaults = JLG_Helpers::get_default_settings();
        $defaults['game_explorer_posts_per_page'] = 2;
        $defaults['game_explorer_filters'] = 'letter,category,platform,availability,search';
        $defaults['game_explorer_score_position'] = JLG_Helpers::normalize_game_explorer_score_position('');

        $GLOBALS['jlg_test_options']['notation_jlg_settings'] = $defaults;
        $GLOBALS['jlg_test_options']['jlg_platforms_list'] = [];
        JLG_Helpers::flush_plugin_options_cache();
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
                    'publisher'        => 'Publisher A',
                    'release_iso'      => '2023-02-14',
                    'availability'     => 'available',
                    'search_haystack'  => 'alpha quest studio alpha publisher a action pc playstation 5',
                ],
                202 => [
                    'letter'           => 'B',
                    'category_ids'     => [11],
                    'category_slugs'   => ['action'],
                    'primary_genre'    => 'Action',
                    'platform_labels'  => ['PC'],
                    'platform_slugs'   => ['pc'],
                    'developer'        => 'Studio Beta',
                    'publisher'        => 'Publisher B',
                    'release_iso'      => '2022-11-10',
                    'availability'     => 'available',
                    'search_haystack'  => 'beta strike studio beta publisher b action pc',
                ],
            ],
            'letters_map'    => ['A' => true, 'B' => true],
            'categories_map' => [11 => 'Action'],
            'platforms_map'  => ['pc' => 'PC', 'playstation-5' => 'PlayStation 5'],
        ];
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
        JLG_Helpers::flush_plugin_options_cache();
    }

    private function resetFrontendStatics(): void
    {
        $reflection = new ReflectionClass(JLG_Frontend::class);
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
        $reflection = new ReflectionClass(JLG_Shortcode_Game_Explorer::class);
        if ($reflection->hasProperty('filters_snapshot')) {
            $property = $reflection->getProperty('filters_snapshot');
            $property->setAccessible(true);
            $property->setValue(null, null);
        }
    }

    private function primeSnapshot(array $snapshot): void
    {
        $reflection = new ReflectionClass(JLG_Shortcode_Game_Explorer::class);
        $property = $reflection->getProperty('filters_snapshot');
        $property->setAccessible(true);
        $property->setValue(null, $snapshot);
    }
}

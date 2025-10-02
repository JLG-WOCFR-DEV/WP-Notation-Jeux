<?php

use PHPUnit\Framework\TestCase;

if (!class_exists('WP_Query')) {
    class WP_Query
    {
        public $args;
        public $max_num_pages = 1;

        public function __construct($args = [])
        {
            $this->args = $args;
        }

        public function have_posts()
        {
            return false;
        }

        public function the_post()
        {
            // No-op in tests.
        }
    }
}

if (!function_exists('get_posts')) {
    function get_posts($args)
    {
        return [];
    }
}

if (!function_exists('get_query_var')) {
    function get_query_var($var, $default = 0)
    {
        return $default;
    }
}

if (!function_exists('add_query_arg')) {
    function add_query_arg($args, $url = '')
    {
        $base = $url === '' ? 'https://example.com/' : $url;
        $query = http_build_query($args);

        return $base . (strpos($base, '?') === false ? '?' : '&') . $query;
    }
}

if (!function_exists('remove_query_arg')) {
    function remove_query_arg($key, $url)
    {
        return $url;
    }
}

if (!function_exists('paginate_links')) {
    function paginate_links($args = [])
    {
        return '';
    }
}

if (!function_exists('get_pagenum_link')) {
    function get_pagenum_link($id)
    {
        return 'https://example.com/page/' . (int) $id;
    }
}

class ShortcodeSummarySortingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['wpdb'] = new class {
            public $postmeta = 'wp_postmeta';

            public function prepare($query, ...$args)
            {
                return $query;
            }

            public function get_col($query)
            {
                return [101, 202];
            }
        };

        $GLOBALS['jlg_test_meta'] = [
            101 => [
                '_note_cat1' => 8,
                '_jlg_developpeur' => 'Studio A',
                '_jlg_editeur' => 'Publisher A',
            ],
            202 => [
                '_note_cat1' => 6,
                '_jlg_developpeur' => 'Studio B',
                '_jlg_editeur' => 'Publisher B',
            ],
        ];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['wpdb'], $GLOBALS['jlg_test_meta']);
        parent::tearDown();
    }

    public function test_sorting_by_developer_uses_meta_arguments()
    {
        $context = \JLG\Notation\Shortcodes\SummaryDisplay::get_render_context([], [
            'orderby' => 'meta__jlg_developpeur',
            'order'   => 'ASC',
        ]);

        $this->assertInstanceOf(WP_Query::class, $context['query']);
        $this->assertSame('meta_value', $context['query']->args['orderby']);
        $this->assertSame('_jlg_developpeur', $context['query']->args['meta_key']);
        $this->assertSame('CHAR', $context['query']->args['meta_type']);
        $this->assertSame('meta__jlg_developpeur', $context['orderby']);
        $this->assertSame('ASC', $context['order']);
    }

    public function test_sorting_alias_for_note_maps_to_average_score_meta()
    {
        $context = \JLG\Notation\Shortcodes\SummaryDisplay::get_render_context([], [
            'orderby' => 'note',
        ]);

        $this->assertInstanceOf(WP_Query::class, $context['query']);
        $this->assertSame('meta_value_num', $context['query']->args['orderby']);
        $this->assertSame('_jlg_average_score', $context['query']->args['meta_key']);
        $this->assertSame('meta_value_num', $context['query']->args['orderby']);
        $this->assertSame('average_score', $context['orderby']);
    }

    public function test_render_context_uses_filtered_post_types()
    {
        $previous_filters = $GLOBALS['jlg_test_filters'] ?? null;

        add_filter('jlg_rated_post_types', function ($types) {
            $types[] = 'game_review';
            $types[] = 'post';
            $types[] = 'game_review';

            return $types;
        });

        try {
            $context = \JLG\Notation\Shortcodes\SummaryDisplay::get_render_context([], []);
        } finally {
            if ($previous_filters === null) {
                unset($GLOBALS['jlg_test_filters']);
            } else {
                $GLOBALS['jlg_test_filters'] = $previous_filters;
            }
        }

        $this->assertInstanceOf(WP_Query::class, $context['query']);
        $this->assertSame(['post', 'game_review'], $context['query']->args['post_type']);
    }

    public function test_namespaced_request_parameters_are_scoped_to_each_table()
    {
        $attsOne = \JLG\Notation\Shortcodes\SummaryDisplay::get_default_atts();
        $attsOne['id'] = 'table-one';

        $attsTwo = \JLG\Notation\Shortcodes\SummaryDisplay::get_default_atts();
        $attsTwo['id'] = 'table-two';

        $request = [
            'orderby__table-one'      => 'note',
            'order__table-one'        => 'ASC',
            'letter_filter__table-two'=> 'C',
            'cat_filter__table-two'   => '123',
        ];

        $contextOne = \JLG\Notation\Shortcodes\SummaryDisplay::get_render_context($attsOne, $request);
        $contextTwo = \JLG\Notation\Shortcodes\SummaryDisplay::get_render_context($attsTwo, $request);

        $this->assertSame('average_score', $contextOne['orderby']);
        $this->assertSame('ASC', $contextOne['order']);
        $this->assertSame('', $contextOne['letter_filter']);
        $this->assertSame(0, $contextOne['cat_filter']);

        $this->assertSame('C', $contextTwo['letter_filter']);
        $this->assertSame(123, $contextTwo['cat_filter']);
        $this->assertSame('date', $contextTwo['orderby']);
        $this->assertSame('DESC', $contextTwo['order']);
    }

    public function test_render_includes_screen_reader_label_for_category_filter()
    {
        $atts = \JLG\Notation\Shortcodes\SummaryDisplay::get_default_atts();
        $atts['id'] = 'summary-accessibility';

        $context = \JLG\Notation\Shortcodes\SummaryDisplay::get_render_context($atts, []);

        $html = \JLG\Notation\Frontend::get_template_html('shortcode-summary-display', $context);

        $table_id     = $context['atts']['id'] ?? $atts['id'];
        $expected_for = '<label for="' . esc_attr($table_id . '_cat_filter') . '" class="screen-reader-text">';

        $this->assertStringContainsString($expected_for, $html);
        $this->assertStringContainsString(esc_html__('Filtrer par catégorie', 'notation-jlg'), $html);
    }

    public function test_letter_filter_buttons_submit_namespaced_values()
    {
        $atts = \JLG\Notation\Shortcodes\SummaryDisplay::get_default_atts();
        $atts['id'] = 'summary-prefix';

        $context = \JLG\Notation\Shortcodes\SummaryDisplay::get_render_context($atts, []);
        $html    = \JLG\Notation\Frontend::get_template_html('shortcode-summary-display', $context);

        $this->assertMatchesRegularExpression(
            '/<button[^>]*type="submit"[^>]*name="letter_filter__summary-prefix"[^>]*value=""/i',
            $html,
            'The "All" button should submit the namespaced letter_filter parameter.'
        );

        $this->assertMatchesRegularExpression(
            '/<button[^>]*type="submit"[^>]*name="letter_filter__summary-prefix"[^>]*value="#"/i',
            $html,
            'Numeric buttons should include the namespaced submit attributes.'
        );
    }
}

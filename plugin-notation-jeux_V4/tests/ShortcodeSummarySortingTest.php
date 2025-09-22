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
        $context = JLG_Shortcode_Summary_Display::get_render_context([], [
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
        $context = JLG_Shortcode_Summary_Display::get_render_context([], [
            'orderby' => 'note',
        ]);

        $this->assertInstanceOf(WP_Query::class, $context['query']);
        $this->assertSame('meta_value_num', $context['query']->args['orderby']);
        $this->assertSame('_jlg_average_score', $context['query']->args['meta_key']);
        $this->assertSame('meta_value_num', $context['query']->args['orderby']);
        $this->assertSame('average_score', $context['orderby']);
    }
}

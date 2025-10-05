<?php

use PHPUnit\Framework\TestCase;

if (!function_exists('_x')) {
    function _x($text, $context, $domain = 'default')
    {
        unset($context, $domain);

        return (string) $text;
    }
}

if (!function_exists('_n')) {
    function _n($single, $plural, $number, $domain = 'default')
    {
        unset($domain);

        return $number === 1 ? $single : $plural;
    }
}

if (!function_exists('paginate_links')) {
    function paginate_links($args = [])
    {
        unset($args);

        return '<nav class="pagination">1</nav>';
    }
}

if (!function_exists('get_edit_post_link')) {
    function get_edit_post_link($post_id)
    {
        return 'https://example.com/wp-admin/post.php?action=edit&post=' . (int) $post_id;
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink($post_id)
    {
        return 'https://example.com/?p=' . (int) $post_id;
    }
}

if (!function_exists('get_the_category')) {
    function get_the_category($post_id)
    {
        $categories = $GLOBALS['jlg_test_categories'] ?? [];

        return $categories[$post_id] ?? [];
    }
}

if (!function_exists('wp_reset_postdata')) {
    function wp_reset_postdata()
    {
        unset($GLOBALS['post'], $GLOBALS['jlg_test_current_post_id']);
    }
}

require_once __DIR__ . '/../includes/Admin/Menu.php';

class AdminPostsListInsightsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $_GET = [];
        $GLOBALS['jlg_test_posts'] = [];
        $GLOBALS['jlg_test_meta'] = [];
        $GLOBALS['jlg_test_categories'] = [];
        $GLOBALS['jlg_test_transients'] = [];

        update_option('notation_jlg_settings', \JLG\Notation\Helpers::get_default_settings());
        \JLG\Notation\Helpers::flush_plugin_options_cache();
    }

    public function test_get_posts_list_tab_content_includes_insights_cards(): void
    {
        $posts = [
            101 => new WP_Post([
                'ID' => 101,
                'post_title' => 'Test A',
                'post_date' => '2024-01-10 10:00:00',
            ]),
            102 => new WP_Post([
                'ID' => 102,
                'post_title' => 'Test B',
                'post_date' => '2024-01-12 12:00:00',
            ]),
            103 => new WP_Post([
                'ID' => 103,
                'post_title' => 'Test C',
                'post_date' => '2024-01-15 15:00:00',
            ]),
        ];

        $GLOBALS['jlg_test_posts'] = $posts;

        $GLOBALS['jlg_test_categories'][101] = [(object) ['name' => 'Action']];
        $GLOBALS['jlg_test_categories'][102] = [(object) ['name' => 'RPG']];
        $GLOBALS['jlg_test_categories'][103] = [(object) ['name' => 'Switch']];

        update_post_meta(101, '_jlg_average_score', 8.5);
        update_post_meta(102, '_jlg_average_score', 6.0);
        update_post_meta(103, '_jlg_average_score', 9.0);

        update_post_meta(101, '_jlg_plateformes', ['PC', 'PlayStation 5']);
        update_post_meta(102, '_jlg_plateformes', 'PC');
        update_post_meta(103, '_jlg_plateformes', ['Nintendo Switch']);

        set_transient('jlg_rated_post_ids_v1', array_keys($posts));

        $menu = new \JLG\Notation\Admin\Menu();

        $reflection = new ReflectionClass($menu);
        $method = $reflection->getMethod('get_posts_list_tab_content');
        $method->setAccessible(true);

        $html = $method->invoke($menu);

        $this->assertStringContainsString('Synthèse des notes', $html);
        $this->assertStringContainsString('Score moyen', $html);
        $this->assertStringContainsString('7.8', $html);
        $this->assertStringContainsString('Médiane', $html);
        $this->assertStringContainsString('Nintendo Switch', $html);
        $this->assertStringContainsString('Classement par plateforme', $html);
    }
}

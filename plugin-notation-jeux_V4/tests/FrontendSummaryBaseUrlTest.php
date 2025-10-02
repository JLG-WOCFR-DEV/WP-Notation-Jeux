<?php

$colonnes = [];
$colonnes_disponibles = [];
$atts = [];
$orderby = '';
$order = '';
$cat_filter = 0;
$letter_filter = '';
$genre_filter = '';
$error_message = '';
$base_url = '';

ob_start();
require_once __DIR__ . '/../templates/summary-table-fragment.php';
ob_end_clean();

use PHPUnit\Framework\TestCase;

class FrontendSummaryBaseUrlTest extends TestCase
{
    public function test_root_url_without_trailing_slash_generates_front_link()
    {
        $frontend = new \JLG\Notation\Frontend();

        $reflection = new ReflectionClass(\JLG\Notation\Frontend::class);
        $method = $reflection->getMethod('sanitize_internal_url');
        $method->setAccessible(true);

        $base_url = $method->invoke($frontend, 'https://public.example');

        $this->assertSame('https://public.example/', $base_url, 'Base URL should normalize to the public domain with a trailing slash.');

        ob_start();
        jlg_print_sortable_header(
            'note',
            [
                'label'    => 'Note',
                'sortable' => true,
                'sort'     => [
                    'key' => 'note',
                ],
            ],
            'date',
            'DESC',
            'table-test',
            [
                'base_url' => $base_url,
            ]
        );
        $output = ob_get_clean();

        $this->assertNotFalse(
            strpos($output, "href=\"https://public.example/?orderby=note&order=ASC#table-test\""),
            'Sortable header should link to the front domain.'
        );
    }

    public function test_root_url_with_query_is_normalized_and_remains_on_public_domain()
    {
        $frontend = new \JLG\Notation\Frontend();

        $reflection = new ReflectionClass(\JLG\Notation\Frontend::class);
        $method = $reflection->getMethod('sanitize_internal_url');
        $method->setAccessible(true);

        $base_url = $method->invoke($frontend, 'https://public.example?foo=bar');

        $this->assertSame('https://public.example/?foo=bar', $base_url, 'Base URL should normalize root URLs with query strings.');

        ob_start();
        jlg_print_sortable_header(
            'note',
            [
                'label'    => 'Note',
                'sortable' => true,
                'sort'     => [
                    'key' => 'note',
                ],
            ],
            'date',
            'DESC',
            'table-test',
            [
                'base_url' => $base_url,
            ]
        );
        $output = ob_get_clean();

        $this->assertNotFalse(
            strpos($output, "href=\"https://public.example/?foo=bar&orderby=note&order=ASC#table-test\""),
            'Sortable header should keep links on the public domain.'
        );
    }

    public function test_www_alias_is_canonicalized_to_home_url()
    {
        $frontend = new \JLG\Notation\Frontend();

        $reflection = new ReflectionClass(\JLG\Notation\Frontend::class);
        $method = $reflection->getMethod('sanitize_internal_url');
        $method->setAccessible(true);

        $base_url = $method->invoke($frontend, 'https://www.public.example/path/?foo=bar');

        $this->assertSame(
            'https://public.example/path/?foo=bar',
            $base_url,
            'Base URL should collapse benign host aliases to the canonical home URL.'
        );
    }

    public function test_home_url_with_custom_port_is_preserved()
    {
        $frontend = new \JLG\Notation\Frontend();

        $reflection = new ReflectionClass(\JLG\Notation\Frontend::class);
        $method = $reflection->getMethod('sanitize_internal_url');
        $method->setAccessible(true);

        $previous_base = $GLOBALS['wp_test_home_url_base'];
        $GLOBALS['wp_test_home_url_base'] = 'https://public.example:8443';

        try {
            $base_url = $method->invoke($frontend, 'https://public.example:8443/custom/path');
        } finally {
            $GLOBALS['wp_test_home_url_base'] = $previous_base;
        }

        $this->assertSame(
            'https://public.example:8443/custom/path',
            $base_url,
            'Base URL should keep the custom port defined in the home URL.'
        );
    }

    public function test_relative_paths_are_normalized_with_leading_slash()
    {
        $frontend = new \JLG\Notation\Frontend();

        $reflection = new ReflectionClass(\JLG\Notation\Frontend::class);
        $method = $reflection->getMethod('sanitize_internal_url');
        $method->setAccessible(true);

        $base_url = $method->invoke($frontend, 'games/');

        $this->assertSame(
            'https://public.example/games/',
            $base_url,
            'Relative paths should be normalized against the public home URL.'
        );
    }
}

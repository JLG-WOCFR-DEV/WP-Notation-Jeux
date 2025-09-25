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
        $frontend = new JLG_Frontend();

        $reflection = new ReflectionClass(JLG_Frontend::class);
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
}

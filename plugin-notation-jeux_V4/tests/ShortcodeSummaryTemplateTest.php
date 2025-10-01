<?php

use PHPUnit\Framework\TestCase;

if (!function_exists('wp_dropdown_categories')) {
    function wp_dropdown_categories($args = array())
    {
        $defaults = array(
            'name' => 'cat_filter',
            'id' => '',
            'class' => '',
        );

        $args = array_merge($defaults, is_array($args) ? $args : array());

        printf(
            '<select name="%s" id="%s" class="%s"></select>',
            esc_attr($args['name']),
            esc_attr($args['id']),
            esc_attr($args['class'])
        );

        return '';
    }
}

class ShortcodeSummaryTemplateTest extends TestCase
{
    public function test_active_letter_button_sets_aria_pressed_attribute()
    {
        $atts = JLG_Shortcode_Summary_Display::get_default_atts();
        $atts['id'] = 'table-test';
        $atts['posts_per_page'] = 5;
        $atts['letter_filter'] = 'C';

        $query = null;
        $paged = 1;
        $orderby = 'date';
        $order = 'DESC';
        $colonnes = array('titre');
        $colonnes_disponibles = array();
        $cat_filter = 0;
        $letter_filter = 'C';
        $genre_filter = '';
        $error_message = '';
        $request_prefix = '';
        $request_keys = array();

        $templateFilter = function ($path, $template_name, $args, $located_template, $plugin_template_path) {
            unset($path, $template_name, $args, $located_template, $plugin_template_path);

            return __DIR__ . '/fixtures/empty-template.php';
        };

        add_filter('jlg_frontend_template_path', $templateFilter, 10, 5);

        try {
            ob_start();
            require dirname(__DIR__) . '/templates/shortcode-summary-display.php';
            $output = ob_get_clean();
        } finally {
            remove_filter('jlg_frontend_template_path', $templateFilter, 10);
        }

        $this->assertMatchesRegularExpression(
            '/<button[^>]*data-letter="C"[^>]*aria-pressed="true"/i',
            $output,
            'The active letter should expose aria-pressed="true".'
        );

        $this->assertMatchesRegularExpression(
            '/<button[^>]*data-letter=""[^>]*aria-pressed="false"/i',
            $output,
            'The "All" button should expose aria-pressed="false" when another letter is active.'
        );

        $this->assertMatchesRegularExpression(
            '/<button[^>]*type="submit"[^>]*name="letter_filter"[^>]*value=""/i',
            $output,
            'The "All" button should submit the letter_filter query parameter.'
        );

        $this->assertMatchesRegularExpression(
            '/<button[^>]*type="submit"[^>]*name="letter_filter"[^>]*value="C"/i',
            $output,
            'Letter buttons should include the corresponding submit name/value attributes.'
        );
    }
}

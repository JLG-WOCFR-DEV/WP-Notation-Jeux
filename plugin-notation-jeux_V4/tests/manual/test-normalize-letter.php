<?php
// Manual test script for normalize_letter compatibility.

define('ABSPATH', __DIR__);

if (!function_exists('remove_accents')) {
    function remove_accents($string) {
        if (!is_string($string)) {
            return '';
        }

        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
        if ($transliterated === false) {
            return $string;
        }

        return $transliterated;
    }
}

if (!function_exists('wp_strtoupper')) {
    function wp_strtoupper($string) {
        if (function_exists('mb_strtoupper')) {
            return mb_strtoupper($string, 'UTF-8');
        }

        return strtoupper($string);
    }
}

require_once dirname(__DIR__, 2) . '/includes/shortcodes/class-jlg-shortcode-game-explorer.php';

class \JLG\Notation\Shortcodes\GameExplorer_Test extends \JLG\Notation\Shortcodes\GameExplorer {
    public static function normalize($value) {
        return self::normalize_letter($value);
    }
}

$inputs = [
    'Éclair',
    ' 42Beta',
    '中華',
    'game',
    '',
];

foreach ($inputs as $input) {
    echo json_encode(['input' => $input, 'normalized' => \JLG\Notation\Shortcodes\GameExplorer_Test::normalize($input)], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}

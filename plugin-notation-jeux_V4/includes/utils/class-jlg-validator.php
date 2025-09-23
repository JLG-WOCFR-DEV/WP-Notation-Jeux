<?php
if (!defined('ABSPATH')) exit;

class JLG_Validator {
    private static $allowed_pegi_values = ['3', '7', '12', '16', '18'];

    public static function is_valid_score($score, $allow_empty = true) {
        if (($score === '' || $score === null) && $allow_empty) {
            return true;
        }
        
        if (!is_numeric($score)) {
            return false;
        }
        
        $numeric_score = floatval($score);
        return $numeric_score >= 0 && $numeric_score <= 10;
    }

    public static function sanitize_score($score) {
        if (!self::is_valid_score($score)) {
            return null;
        }
        
        if ($score === '' || $score === null) {
            return '';
        }
        
        return round(floatval($score), 1);
    }

    public static function validate_notation_data($post_data) {
        $sanitized_data = [];
        $errors = [];

        foreach (['cat1', 'cat2', 'cat3', 'cat4', 'cat5', 'cat6'] as $key) {
            $field_key = '_note_' . $key;
            if (isset($post_data[$field_key])) {
                $score = $post_data[$field_key];
                if (self::is_valid_score($score)) {
                    $sanitized_data[$field_key] = self::sanitize_score($score);
                } else {
                    $errors[$field_key] = 'Score invalide';
                }
            }
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'sanitized_data' => $sanitized_data
        ];
    }

    public static function sanitize_platforms($platforms) {
        if (!is_array($platforms)) {
            return [];
        }

        $allowed_platforms = [];

        if (class_exists('JLG_Admin_Platforms')) {
            $platform_manager = JLG_Admin_Platforms::get_instance();
            if ($platform_manager && method_exists($platform_manager, 'get_platform_names')) {
                $platform_names = $platform_manager->get_platform_names();
                if (is_array($platform_names)) {
                    $allowed_platforms = array_map('sanitize_text_field', array_values($platform_names));
                }
            }
        }

        if (empty($allowed_platforms)) {
            $allowed_platforms = [
                'PC', 'PlayStation 5', 'Xbox Series S/X', 'Nintendo Switch 2',
                'Nintendo Switch', 'PlayStation 4', 'Xbox One'
            ];
        }

        $sanitized = array_map('sanitize_text_field', $platforms);
        return array_values(array_intersect($sanitized, $allowed_platforms));
    }

    public static function sanitize_genres($genres, $primary = '') {
        $registered = class_exists('JLG_Helpers') ? JLG_Helpers::get_registered_genres() : [];

        if (empty($registered) || !is_array($registered)) {
            return [];
        }

        if (!is_array($genres)) {
            if (is_string($genres)) {
                $genres = preg_split('/[,;|]/', $genres);
            } else {
                $genres = [];
            }
        }

        $allowed_slugs = array_keys($registered);
        $selected = [];

        foreach ($genres as $value) {
            if (is_array($value)) {
                $value = implode(' ', $value);
            }

            $slug = sanitize_title($value);

            if ($slug === '' || !in_array($slug, $allowed_slugs, true)) {
                continue;
            }

            if (!in_array($slug, $selected, true)) {
                $selected[] = $slug;
            }
        }

        if (empty($selected)) {
            return [];
        }

        $primary_slug = sanitize_title($primary);

        if ($primary_slug === '' || !in_array($primary_slug, $selected, true)) {
            $primary_slug = $selected[0];
        }

        return [
            'selected' => $selected,
            'primary' => $primary_slug,
        ];
    }

    public static function validate_date($date, $allow_empty = true) {
        if ($date === '' || $date === null) {
            return $allow_empty;
        }

        $date_time = DateTime::createFromFormat('Y-m-d', $date);

        return $date_time instanceof DateTime && $date_time->format('Y-m-d') === $date;
    }

    public static function sanitize_date($date) {
        if (!self::validate_date($date, false)) {
            return null;
        }

        $date_time = DateTime::createFromFormat('Y-m-d', $date);

        return $date_time ? $date_time->format('Y-m-d') : null;
    }

    public static function get_allowed_pegi_values() {
        return self::$allowed_pegi_values;
    }

    private static function normalize_pegi_value($pegi) {
        if ($pegi === '' || $pegi === null) {
            return null;
        }

        $normalized = strtoupper(trim($pegi));
        $normalized = str_replace('PEGI', '', $normalized);
        $normalized = str_replace('+', '', $normalized);
        $normalized = preg_replace('/[^0-9]/', '', $normalized);

        if ($normalized === '') {
            return null;
        }

        return in_array($normalized, self::$allowed_pegi_values, true) ? $normalized : null;
    }

    public static function validate_pegi($pegi, $allow_empty = true) {
        if ($pegi === '' || $pegi === null) {
            return $allow_empty;
        }

        return self::normalize_pegi_value($pegi) !== null;
    }

    public static function sanitize_pegi($pegi) {
        $normalized = self::normalize_pegi_value($pegi);

        if ($normalized === null) {
            return null;
        }

        return 'PEGI ' . $normalized;
    }
}

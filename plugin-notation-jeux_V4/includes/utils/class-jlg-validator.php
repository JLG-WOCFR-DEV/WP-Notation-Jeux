<?php
if (!defined('ABSPATH')) exit;

class JLG_Validator {
    
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
        
        $allowed_platforms = [
            'PC', 'PlayStation 5', 'Xbox Series S/X', 'Nintendo Switch 2', 
            'Nintendo Switch', 'PlayStation 4', 'Xbox One'
        ];
        
        $sanitized = array_map('sanitize_text_field', $platforms);
        return array_intersect($sanitized, $allowed_platforms);
    }
}
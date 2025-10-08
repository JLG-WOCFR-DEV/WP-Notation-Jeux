<?php
/**
 * Custom walker enabling multi-select support for category dropdowns.
 *
 * @package JLG_Notation
 */

namespace JLG\Notation\Admin;

use Walker_CategoryDropdown;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Extends the core walker to properly handle arrays of selected term IDs.
 */
class MultiSelectCategoryDropdownWalker extends Walker_CategoryDropdown {

    /**
     * Starts the element output.
     *
     * @param string   $output            Used to append additional content (passed by reference).
     * @param \WP_Term $data_object       Category data object.
     * @param int      $depth             Depth of category. Used for padding.
     * @param array    $args              Uses 'selected', 'show_count', and 'value_field' keys, if they exist.
     * @param int      $current_object_id Optional. ID of the current category. Default 0.
     */
    public function start_el( &$output, $data_object, $depth = 0, $args = array(), $current_object_id = 0 ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        $category = $data_object;

        $pad = str_repeat( '&nbsp;', $depth * 3 );

        /** This filter is documented in wp-includes/category-template.php */
        $cat_name = apply_filters( 'list_cats', $category->name, $category );

        if ( isset( $args['value_field'] ) && isset( $category->{$args['value_field']} ) ) {
            $value_field = $args['value_field'];
        } else {
            $value_field = 'term_id';
        }

        $value = (string) $category->{$value_field};

        $output .= "\t<option class=\"level-$depth\" value=\"" . esc_attr( $value ) . '"';

        $selected_values = $this->normalize_selected_values( $args['selected'] ?? array() );

        if ( in_array( $value, $selected_values, true ) ) {
            $output .= ' selected="selected"';
        }

        $output .= '>';
        $output .= $pad . $cat_name;

        if ( ! empty( $args['show_count'] ) ) {
            $output .= '&nbsp;&nbsp;(' . number_format_i18n( $category->count ) . ')';
        }

        $output .= "</option>\n";
    }

    /**
     * Normalizes the selected argument into an array of string values.
     *
     * @param mixed $selected Selected value(s).
     *
     * @return string[]
     */
    private function normalize_selected_values( $selected ) {
        if ( is_array( $selected ) ) {
            return array_map( 'strval', $selected );
        }

        if ( '' === $selected || null === $selected ) {
            return array();
        }

        return array( (string) $selected );
    }
}

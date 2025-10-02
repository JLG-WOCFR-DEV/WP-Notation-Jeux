(function ($) {
    'use strict';

    function isTransparent(value) {
        return typeof value === 'string' && value.toLowerCase().trim() === 'transparent';
    }

    function initColorPicker($inputs) {
        if (!$.fn.wpColorPicker || !$inputs.length) {
            return;
        }

        $inputs.each(function () {
            var $field = $(this);

            if ($field.data('jlgColorPickerInit')) {
                return;
            }

            $field.data('jlgColorPickerInit', true);

            var allowTransparent = String($field.data('allow-transparent') || '').toLowerCase() === 'true';
            var defaultColor = $field.data('default-color');
            var initialValue = $field.val();
            var pickerOptions = {};

            if (defaultColor && !isTransparent(defaultColor)) {
                pickerOptions.defaultColor = defaultColor;
            }

            pickerOptions.change = function (event, ui) {
                if (allowTransparent && (!ui || !ui.color)) {
                    $field.val('transparent');
                    return;
                }

                if (ui && ui.color) {
                    $field.val(ui.color.toString());
                }
            };

            pickerOptions.clear = function () {
                if (allowTransparent) {
                    $field.val('transparent');
                } else {
                    $field.val('');
                }

                $field.trigger('change');
            };

            $field.wpColorPicker(pickerOptions);

            if (allowTransparent) {
                var $wrapper = $field.closest('.wp-picker-container');

                if (isTransparent(initialValue)) {
                    $field.val('transparent');
                    $field.trigger('change');
                }

                $wrapper.on('click', '.wp-picker-clear', function () {
                    $field.val('transparent').trigger('change');
                });

                $field.on('input', function () {
                    var current = $field.val();
                    var picker = $field.data('wp-color-picker');

                    if (isTransparent(current) && picker) {
                        picker.color = false;
                    }
                });
            }
        });
    }

    $(function () {
        initColorPicker($('.jlg-color-picker'));
    });
})(jQuery);

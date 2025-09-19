(function ($) {
    'use strict';

    function getSettings() {
        if (window.jlgPlatformsOrder && typeof window.jlgPlatformsOrder === 'object') {
            return window.jlgPlatformsOrder;
        }

        return {
            listSelector: '#platforms-list',
            positionSelector: '.jlg-platform-position',
            handleSelector: '.jlg-sort-handle',
            rowSelector: 'tr[data-key]',
            inputSelector: 'input[name="platform_order[]"]',
            placeholderClass: 'jlg-sortable-placeholder'
        };
    }

    function ensurePlaceholderStyle(className) {
        if (!className) {
            return;
        }

        var styleId = 'jlg-platforms-sortable-style';

        if (document.getElementById(styleId)) {
            return;
        }

        var style = document.createElement('style');
        style.id = styleId;
        style.type = 'text/css';
        style.appendChild(document.createTextNode(
            '.' + className + ' td {\n' +
            '    border: 2px dashed #2271b1;\n' +
            '    background: #f0f6fc;\n' +
            '}\n' +
            '.jlg-sorting {\n' +
            '    opacity: 0.8;\n' +
            '}\n'
        ));

        document.head.appendChild(style);
    }

    function syncOrder($list, settings) {
        var rowSelector = settings.rowSelector || 'tr';
        var positionSelector = settings.positionSelector || '.jlg-platform-position';
        var inputSelector = settings.inputSelector || 'input[name="platform_order[]"]';

        $list.find(rowSelector).each(function (index) {
            var $row = $(this);
            var key = $row.data('key') || '';

            if (positionSelector) {
                $row.find(positionSelector).text(index + 1);
            }

            if (!key) {
                return;
            }

            var $input = $row.find(inputSelector);

            if (!$input.length) {
                $input = $('<input>', {
                    type: 'hidden',
                    name: 'platform_order[]'
                }).appendTo($row);
            }

            $input.val(key);
        });
    }

    $(function () {
        var settings = getSettings();
        var $list = $(settings.listSelector);

        if (!$list.length || typeof $list.sortable !== 'function') {
            return;
        }

        ensurePlaceholderStyle(settings.placeholderClass);

        var handleSelector = settings.handleSelector || false;
        var rowSelector = settings.rowSelector || '> tr';
        var placeholderClass = settings.placeholderClass || 'jlg-sortable-placeholder';

        $list.sortable({
            axis: 'y',
            handle: handleSelector,
            items: rowSelector,
            placeholder: placeholderClass,
            tolerance: 'pointer',
            helper: function (event, ui) {
                ui.children().each(function () {
                    var $cell = $(this);
                    $cell.width($cell.width());
                });
                return ui;
            },
            start: function (event, ui) {
                ui.placeholder.height(ui.item.outerHeight());
                ui.item.addClass('jlg-sorting');
            },
            sort: function () {
                syncOrder($list, settings);
            },
            update: function () {
                syncOrder($list, settings);
            },
            stop: function (event, ui) {
                ui.item.removeClass('jlg-sorting');
                syncOrder($list, settings);
            }
        });

        if (typeof $list.disableSelection === 'function') {
            $list.disableSelection();
        }

        syncOrder($list, settings);
    });
})(jQuery);

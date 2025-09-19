(function ($) {
    'use strict';

    function getSettings() {
        if (window.jlgPlatformsOrder && typeof window.jlgPlatformsOrder === 'object') {
            return window.jlgPlatformsOrder;
        }

        return {
            listSelector: '#platforms-list',
            positionSelector: '.jlg-platform-position',
            handleSelector: '.jlg-sort-handle'
        };
    }

    function refreshPositions($list, selector) {
        $list.find('tr').each(function (index) {
            $(this).find(selector).text(index + 1);
        });
    }

    $(function () {
        var settings = getSettings();
        var $list = $(settings.listSelector);

        if (!$list.length || typeof $list.sortable !== 'function') {
            return;
        }

        var positionSelector = settings.positionSelector || '.jlg-platform-position';
        var handleSelector = settings.handleSelector || undefined;

        $list.sortable({
            axis: 'y',
            handle: handleSelector,
            placeholder: 'jlg-sortable-placeholder',
            helper: function (event, ui) {
                ui.children().each(function () {
                    $(this).width($(this).width());
                });
                return ui;
            },
            update: function () {
                refreshPositions($list, positionSelector);
            }
        });

        if (typeof $list.disableSelection === 'function') {
            $list.disableSelection();
        }

        refreshPositions($list, positionSelector);
    });
})(jQuery);

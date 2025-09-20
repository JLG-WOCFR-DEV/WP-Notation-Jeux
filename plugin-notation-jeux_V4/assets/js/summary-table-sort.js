jQuery(document).ready(function($) {
    if (typeof jlgSummarySort === 'undefined') {
        return;
    }

    function parseUrlParameters(href) {
        var url = new URL(href, window.location.href);
        var params = {};

        url.searchParams.forEach(function(value, key) {
            params[key] = value;
        });

        return { url: url, params: params };
    }

    function updateHistory(url) {
        if (typeof window.history.pushState === 'function') {
            window.history.pushState({}, '', url);
        }
    }

    function showError($wrapper, message) {
        var $content = $wrapper.find('.jlg-summary-content');

        if ($content.length) {
            var $paragraph = $('<p>', {
                'class': 'jlg-summary-error',
                text: message,
            });
            $content.empty().append($paragraph);
        }
    }

    function updateState($wrapper, state) {
        if (!state) {
            return;
        }

        if (state.orderby) {
            $wrapper.attr('data-orderby', state.orderby);
        }
        if (state.order) {
            $wrapper.attr('data-order', state.order);
        }
        if (state.paged) {
            $wrapper.attr('data-paged', state.paged);
        }
        if (typeof state.cat_filter !== 'undefined') {
            $wrapper.attr('data-cat-filter', state.cat_filter);
        }

        var $form = $wrapper.find('.jlg-summary-filters form');
        if ($form.length) {
            if (state.orderby) {
                $form.find('input[name="orderby"]').val(state.orderby);
            }
            if (state.order) {
                $form.find('input[name="order"]').val(state.order);
            }
            if (typeof state.cat_filter !== 'undefined') {
                $form.find('select[name="cat_filter"]').val(state.cat_filter);
            }
        }
    }

    function performAjax($wrapper, params, historyUrl, options) {
        var ajaxUrl = jlgSummarySort.ajax_url;

        if (!ajaxUrl) {
            return;
        }

        var settings = options || {};
        var shouldUpdateHistory = settings.updateHistory !== false;

        var currentRequest = $wrapper.data('ajaxRequest');
        if (currentRequest && typeof currentRequest.abort === 'function') {
            currentRequest.abort();
        }

        var targetUrl = historyUrl || window.location.href;

        var currentOrderby = $wrapper.data('orderby') || 'date';
        var currentOrder = ($wrapper.data('order') || 'DESC').toString().toUpperCase();
        var orderby = params.orderby || currentOrderby;
        var order = (params.order || currentOrder).toString().toUpperCase();
        var shouldResetPage = (orderby !== currentOrderby) || (order !== currentOrder);

        if (shouldResetPage && targetUrl) {
            try {
                var targetUrlObj = new URL(targetUrl, window.location.href);
                targetUrlObj.searchParams.delete('paged');
                targetUrl = targetUrlObj.href;
            } catch (error) {
                // Ignore URL parsing errors and fallback to original targetUrl.
            }
        }

        var requestData = {
            action: 'jlg_summary_sort',
            nonce: jlgSummarySort.nonce,
            posts_per_page: $wrapper.data('postsPerPage'),
            layout: $wrapper.data('layout'),
            categorie: $wrapper.data('categorie') || '',
            colonnes: $wrapper.data('colonnes') || '',
            table_id: $wrapper.attr('id'),
            current_url: targetUrl,
        };

        var paged;
        if (shouldResetPage) {
            paged = 1;
            params.paged = 1;
        } else if (typeof params.paged !== 'undefined' && params.paged !== '') {
            paged = params.paged;
        } else {
            paged = $wrapper.data('paged') || 1;
        }
        var catFilter = params.cat_filter;

        requestData.orderby = orderby;
        requestData.order = order;
        requestData.paged = parseInt(paged, 10);
        if (!requestData.paged || requestData.paged < 1) {
            requestData.paged = 1;
        }

        if (typeof catFilter === 'undefined' || catFilter === '') {
            catFilter = $wrapper.data('catFilter');
        }

        requestData.cat_filter = parseInt(catFilter, 10);
        if (isNaN(requestData.cat_filter)) {
            requestData.cat_filter = 0;
        }

        $wrapper.addClass('jlg-summary-loading');

        var jqXHR = $.ajax({
            url: ajaxUrl,
            method: 'POST',
            data: requestData,
        });

        $wrapper.data('ajaxRequest', jqXHR);

        jqXHR.done(function(response) {
            if (!response || !response.success || !response.data) {
                showError($wrapper, jlgSummarySort.strings.genericError);
                return;
            }

            var html = response.data.html || '';
            var $content = $wrapper.find('.jlg-summary-content');

            if ($content.length) {
                $content.html(html);
            }

            updateState($wrapper, response.data.state || {});

            if (shouldUpdateHistory && targetUrl) {
                updateHistory(targetUrl);
            }
        }).fail(function(_, textStatus) {
            if (textStatus === 'abort') {
                return;
            }
            showError($wrapper, jlgSummarySort.strings.genericError);
        }).always(function() {
            $wrapper.removeClass('jlg-summary-loading');
            $wrapper.removeData('ajaxRequest');
        });
    }

    $('.jlg-summary-wrapper').each(function() {
        var $wrapper = $(this);

        $wrapper.on('click', 'th.sortable a, .jlg-pagination a', function(event) {
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.which === 2) {
                return;
            }

            event.preventDefault();

            var href = $(this).attr('href');
            if (!href) {
                return;
            }

            var parsed = parseUrlParameters(href);
            performAjax($wrapper, parsed.params, parsed.url.href);
        });

        $wrapper.on('submit', '.jlg-summary-filters form', function(event) {
            event.preventDefault();

            var $form = $(this);
            var params = {};

            $.each($form.serializeArray(), function(_, field) {
                if (field.name) {
                    params[field.name] = field.value;
                }
            });

            params.paged = 1;

            var action = $form.attr('action') || window.location.href;
            var url = new URL(action, window.location.href);

            Object.keys(params).forEach(function(key) {
                url.searchParams.set(key, params[key]);
            });

            if ($wrapper.attr('id')) {
                url.hash = $wrapper.attr('id');
            }

            performAjax($wrapper, params, url.href);
        });
    });

    window.addEventListener('popstate', function() {
        var href = window.location.href;
        var parsed = parseUrlParameters(href);
        var hash = parsed.url.hash || '';
        var $wrapper;

        if (hash) {
            try {
                $wrapper = $(hash);
            } catch (error) {
                $wrapper = $();
            }

            if (!$wrapper || !$wrapper.length) {
                var id = hash.replace(/^#/, '');
                if (id) {
                    $wrapper = $('#' + id);
                }
            }
        }

        if (!$wrapper || !$wrapper.length) {
            $wrapper = $('.jlg-summary-wrapper').first();
        }

        if (!$wrapper || !$wrapper.length) {
            return;
        }

        var currentRequest = $wrapper.data('ajaxRequest');
        if (currentRequest) {
            if (typeof currentRequest.state === 'function' && currentRequest.state() === 'pending') {
                return;
            }

            if (typeof currentRequest.readyState !== 'undefined' && currentRequest.readyState !== 4) {
                return;
            }
        }

        performAjax($wrapper, parsed.params, parsed.url.href, { updateHistory: false });
    });
});

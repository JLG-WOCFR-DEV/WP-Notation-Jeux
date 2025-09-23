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

    function getCurrentState($wrapper) {
        return {
            orderby: ($wrapper.attr('data-orderby') || 'date').toString(),
            order: ($wrapper.attr('data-order') || 'DESC').toString().toUpperCase(),
            paged: parseInt($wrapper.attr('data-paged'), 10) || 1,
            cat_filter: ($wrapper.attr('data-cat-filter') || '').toString(),
            letter_filter: ($wrapper.attr('data-letter-filter') || '').toString(),
            genre_filter: ($wrapper.attr('data-genre-filter') || '').toString(),
        };
    }

    function applyStateToUrl(url, state) {
        var keys = ['orderby', 'order', 'paged', 'cat_filter', 'letter_filter', 'genre_filter'];

        keys.forEach(function(key) {
            url.searchParams.delete(key);
        });

        keys.forEach(function(key) {
            if (!state) {
                return;
            }

            var value = state[key];

            if (key === 'paged') {
                var parsed = parseInt(value, 10);
                if (!parsed || parsed <= 1) {
                    return;
                }
                value = parsed;
            }

            if (key === 'cat_filter' && (value === '0' || value === 0)) {
                return;
            }

            if (value === null || typeof value === 'undefined' || value === '') {
                return;
            }

            url.searchParams.set(key, value);
        });

        return url;
    }

    function buildUrlFromState($wrapper, state) {
        var url = new URL(window.location.href);
        url.hash = '';
        applyStateToUrl(url, state);

        if ($wrapper.attr('id')) {
            url.hash = $wrapper.attr('id');
        }

        return url.href;
    }

    function updateState($wrapper, state) {
        if (!state) {
            return;
        }

        if (state.orderby) {
            $wrapper.attr('data-orderby', state.orderby);
            $wrapper.data('orderby', state.orderby);
        }
        if (state.order) {
            var orderValue = state.order.toString().toUpperCase();
            $wrapper.attr('data-order', orderValue);
            $wrapper.data('order', orderValue);
        }
        if (typeof state.paged !== 'undefined') {
            var pagedValue = parseInt(state.paged, 10);
            if (!pagedValue || pagedValue < 1) {
                pagedValue = 1;
            }
            $wrapper.attr('data-paged', pagedValue);
            $wrapper.data('paged', pagedValue);
        }
        if (typeof state.cat_filter !== 'undefined') {
            var catValue = state.cat_filter;
            if (catValue === null) {
                catValue = '';
            }
            catValue = catValue.toString();
            $wrapper.attr('data-cat-filter', catValue);
            $wrapper.data('catFilter', catValue);
        }
        if (typeof state.letter_filter !== 'undefined') {
            var letterValue = state.letter_filter;
            if (letterValue === null) {
                letterValue = '';
            }
            letterValue = letterValue.toString();
            $wrapper.attr('data-letter-filter', letterValue);
            $wrapper.data('letterFilter', letterValue);
        }
        if (typeof state.genre_filter !== 'undefined') {
            var genreValue = state.genre_filter;
            if (genreValue === null) {
                genreValue = '';
            }
            genreValue = genreValue.toString();
            $wrapper.attr('data-genre-filter', genreValue);
            $wrapper.data('genreFilter', genreValue);
        }

        var $form = $wrapper.find('.jlg-summary-filters form');
        if ($form.length) {
            if (state.orderby) {
                $form.find('input[name="orderby"]').val(state.orderby);
            }
            if (state.order) {
                $form.find('input[name="order"]').val(state.order.toString().toUpperCase());
            }
            if (typeof state.cat_filter !== 'undefined') {
                $form.find('select[name="cat_filter"]').val($wrapper.attr('data-cat-filter'));
            }
            if (typeof state.letter_filter !== 'undefined') {
                $form.find('input[name="letter_filter"]').val($wrapper.attr('data-letter-filter'));
            }
            if (typeof state.genre_filter !== 'undefined') {
                var genreValueAttr = $wrapper.attr('data-genre-filter');
                var $genreSelect = $form.find('select[name="genre_filter"]');

                if ($genreSelect.length) {
                    $genreSelect.val(genreValueAttr);
                } else {
                    $form.find('input[name="genre_filter"]').val(genreValueAttr);
                }
            }
        }

        var $letterButtons = $wrapper.find('.jlg-summary-letter-filter [data-letter]');
        if ($letterButtons.length) {
            var activeLetter = $wrapper.attr('data-letter-filter') || '';
            $letterButtons.each(function() {
                var $button = $(this);
                var buttonLetter = ($button.attr('data-letter') || '').toString();
                if (buttonLetter === activeLetter) {
                    $button.addClass('is-active');
                } else {
                    $button.removeClass('is-active');
                }
            });
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

        var currentState = getCurrentState($wrapper);
        var paramsCopy = $.extend({}, params || {});

        var orderby = paramsCopy.orderby || currentState.orderby;
        var order = (paramsCopy.order || currentState.order).toString().toUpperCase();

        var catFilter = paramsCopy.hasOwnProperty('cat_filter') ? paramsCopy.cat_filter : currentState.cat_filter;
        if (catFilter === null || typeof catFilter === 'undefined') {
            catFilter = '';
        }
        catFilter = catFilter.toString();

        var letterFilter = paramsCopy.hasOwnProperty('letter_filter') ? paramsCopy.letter_filter : currentState.letter_filter;
        if (letterFilter === null || typeof letterFilter === 'undefined') {
            letterFilter = '';
        }
        letterFilter = letterFilter.toString();

        var genreFilter = paramsCopy.hasOwnProperty('genre_filter') ? paramsCopy.genre_filter : currentState.genre_filter;
        if (genreFilter === null || typeof genreFilter === 'undefined') {
            genreFilter = '';
        }
        genreFilter = genreFilter.toString();

        var normalizedStateCat = currentState.cat_filter === null || typeof currentState.cat_filter === 'undefined'
            ? ''
            : currentState.cat_filter.toString();
        var normalizedStateLetter = currentState.letter_filter === null || typeof currentState.letter_filter === 'undefined'
            ? ''
            : currentState.letter_filter.toString();
        var normalizedStateGenre = currentState.genre_filter === null || typeof currentState.genre_filter === 'undefined'
            ? ''
            : currentState.genre_filter.toString();

        var shouldResetPage = (orderby !== currentState.orderby)
            || (order !== currentState.order)
            || (catFilter !== normalizedStateCat)
            || (letterFilter !== normalizedStateLetter)
            || (genreFilter !== normalizedStateGenre);

        var paged = paramsCopy.hasOwnProperty('paged') ? paramsCopy.paged : currentState.paged;
        if (shouldResetPage) {
            paged = 1;
        }
        paged = parseInt(paged, 10);
        if (!paged || paged < 1) {
            paged = 1;
        }

        var targetState = {
            orderby: orderby,
            order: order,
            paged: paged,
            cat_filter: catFilter,
            letter_filter: letterFilter,
            genre_filter: genreFilter,
        };

        var targetUrl;
        if (historyUrl) {
            try {
                var normalizedUrl = new URL(historyUrl, window.location.href);
                normalizedUrl.hash = '';
                applyStateToUrl(normalizedUrl, targetState);
                if ($wrapper.attr('id')) {
                    normalizedUrl.hash = $wrapper.attr('id');
                }
                targetUrl = normalizedUrl.href;
            } catch (error) {
                targetUrl = buildUrlFromState($wrapper, targetState);
            }
        } else {
            targetUrl = buildUrlFromState($wrapper, targetState);
        }

        var postsPerPage = $wrapper.data('postsPerPage');
        if (typeof postsPerPage === 'undefined') {
            postsPerPage = $wrapper.attr('data-posts-per-page');
        }

        var requestData = {
            action: 'jlg_summary_sort',
            nonce: jlgSummarySort.nonce,
            posts_per_page: postsPerPage,
            layout: $wrapper.data('layout'),
            categorie: $wrapper.data('categorie') || '',
            colonnes: $wrapper.data('colonnes') || '',
            table_id: $wrapper.attr('id'),
            current_url: targetUrl,
            orderby: orderby,
            order: order,
            paged: paged,
            cat_filter: 0,
            letter_filter: letterFilter,
            genre_filter: genreFilter,
        };

        var catFilterInt = parseInt(catFilter, 10);
        if (!isNaN(catFilterInt)) {
            requestData.cat_filter = catFilterInt;
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
            var nextState = $.extend({}, getCurrentState($wrapper), parsed.params);
            performAjax($wrapper, nextState, parsed.url.href);
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

            var nextState = $.extend({}, getCurrentState($wrapper), params);
            var url = buildUrlFromState($wrapper, nextState);

            performAjax($wrapper, nextState, url);
        });

        $wrapper.on('click', '.jlg-summary-letter-filter [data-letter]', function(event) {
            event.preventDefault();

            var letter = ($(this).attr('data-letter') || '').toString();
            var currentState = getCurrentState($wrapper);

            if (letter === currentState.letter_filter && letter !== '') {
                letter = '';
            }

            var nextState = $.extend({}, currentState, {
                letter_filter: letter,
                paged: 1,
            });

            var $form = $wrapper.find('.jlg-summary-filters form');
            if ($form.length) {
                $form.find('input[name="letter_filter"]').val(letter);
            }

            var url = buildUrlFromState($wrapper, nextState);

            performAjax($wrapper, nextState, url);
        });

        $wrapper.on('change', 'select[name="genre_filter"]', function() {
            var genre = $(this).val() || '';
            var currentState = getCurrentState($wrapper);

            var nextState = $.extend({}, currentState, {
                genre_filter: genre,
                paged: 1,
            });

            var url = buildUrlFromState($wrapper, nextState);

            performAjax($wrapper, nextState, url);
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

        var nextState = $.extend({}, getCurrentState($wrapper), parsed.params);
        performAjax($wrapper, nextState, parsed.url.href, { updateHistory: false });
    });
});

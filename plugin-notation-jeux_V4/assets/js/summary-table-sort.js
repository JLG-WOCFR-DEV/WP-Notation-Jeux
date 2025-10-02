jQuery(document).ready(function($) {
    if (typeof jlgSummarySort === 'undefined') {
        return;
    }

    var REQUEST_KEYS = ['orderby', 'order', 'paged', 'cat_filter', 'letter_filter', 'genre_filter'];

    function getRequestPrefix($wrapper) {
        return ($wrapper.attr('data-request-prefix') || '').toString();
    }

    function getRequestKey($wrapper, key) {
        var prefix = getRequestPrefix($wrapper);

        return prefix ? key + '__' + prefix : key;
    }

    function resolveBaseKey($wrapper, key) {
        if (!key) {
            return null;
        }

        var prefix = getRequestPrefix($wrapper);

        if (prefix) {
            var suffix = '__' + prefix;
            if (key.slice(-suffix.length) === suffix) {
                return key.slice(0, -suffix.length);
            }

            if (key.indexOf('__') !== -1) {
                return null;
            }
        }

        return key;
    }

    function normalizeParams($wrapper, params) {
        var normalized = {};

        Object.keys(params || {}).forEach(function(name) {
            var baseKey = resolveBaseKey($wrapper, name);
            if (!baseKey) {
                return;
            }

            normalized[baseKey] = params[name];
        });

        return normalized;
    }

    function parseUrlParameters($wrapper, href) {
        var url = new URL(href, window.location.href);
        var rawParams = {};

        url.searchParams.forEach(function(value, key) {
            rawParams[key] = value;
        });

        return { url: url, params: normalizeParams($wrapper, rawParams) };
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

    function sendSummaryRequest(restPayload, legacyPayload) {
        var restUrl = (jlgSummarySort && jlgSummarySort.restUrl) ? jlgSummarySort.restUrl : '';
        var restNonce = (jlgSummarySort && jlgSummarySort.restNonce) ? jlgSummarySort.restNonce : '';
        var restPath = (jlgSummarySort && jlgSummarySort.restPath) ? jlgSummarySort.restPath : '';
        var controller = null;
        var promise;

        if (restUrl && restNonce) {
            var payload = $.extend(true, {}, restPayload || {});

            if (typeof window.AbortController === 'function') {
                controller = new window.AbortController();
            }

            if (window.wp && window.wp.apiFetch) {
                var apiFetchArgs = {
                    path: restPath || restUrl,
                    method: 'POST',
                    data: payload,
                    headers: {
                        'X-WP-Nonce': restNonce,
                    },
                };

                if (controller) {
                    apiFetchArgs.signal = controller.signal;
                }

                promise = window.wp.apiFetch(apiFetchArgs).then(function(data) {
                    return { success: true, data: data };
                }).catch(function(error) {
                    if (error && error.name === 'AbortError') {
                        throw error;
                    }

                    var message = (error && error.data && error.data.message)
                        ? String(error.data.message)
                        : jlgSummarySort.strings.genericError;

                    return { success: false, data: { message: message } };
                });
            } else if (typeof window.fetch === 'function') {
                var fetchOptions = {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': restNonce,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload),
                };

                if (controller) {
                    fetchOptions.signal = controller.signal;
                }

                promise = window.fetch(restUrl, fetchOptions).then(function(response) {
                    return response.json().catch(function() {
                        return {};
                    }).then(function(body) {
                        if (!response.ok) {
                            var message = body && body.message
                                ? String(body.message)
                                : jlgSummarySort.strings.genericError;

                            return { success: false, data: { message: message } };
                        }

                        return { success: true, data: body };
                    });
                }).catch(function(error) {
                    if (error && error.name === 'AbortError') {
                        throw error;
                    }

                    return { success: false, data: { message: jlgSummarySort.strings.genericError } };
                });
            }
        }

        if (!promise) {
            var fallbackData = $.extend(
                {
                    action: 'jlg_summary_sort',
                    nonce: jlgSummarySort.nonce,
                },
                legacyPayload || restPayload || {}
            );

            var jqXHR = $.ajax({
                url: jlgSummarySort.ajax_url,
                method: 'POST',
                data: fallbackData,
            });

            controller = {
                abort: function() {
                    jqXHR.abort();
                }
            };

            promise = new Promise(function(resolve, reject) {
                jqXHR.done(function(response) {
                    resolve(response);
                }).fail(function(_, textStatus) {
                    if (textStatus === 'abort') {
                        var abortError = new Error('AbortError');
                        abortError.name = 'AbortError';
                        reject(abortError);
                        return;
                    }

                    resolve({ success: false, data: { message: jlgSummarySort.strings.genericError } });
                });
            });
        }

        return {
            promise: promise,
            abort: function() {
                if (controller && typeof controller.abort === 'function') {
                    controller.abort();
                }
            }
        };
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

    function applyStateToUrl($wrapper, url, state) {
        REQUEST_KEYS.forEach(function(key) {
            url.searchParams.delete(key);
            var namespaced = getRequestKey($wrapper, key);
            if (namespaced !== key) {
                url.searchParams.delete(namespaced);
            }
        });

        REQUEST_KEYS.forEach(function(key) {
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

            url.searchParams.set(getRequestKey($wrapper, key), value);
        });

        return url;
    }

    function buildUrlFromState($wrapper, state) {
        var url = new URL(window.location.href);
        url.hash = '';
        applyStateToUrl($wrapper, url, state);

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
                var orderbyName = getRequestKey($wrapper, 'orderby');
                $form.find('input[name="orderby"]').val(state.orderby);
                if (orderbyName !== 'orderby') {
                    $form.find('input[name="' + orderbyName + '"]').val(state.orderby);
                }
            }
            if (state.order) {
                var orderName = getRequestKey($wrapper, 'order');
                var resolvedOrder = state.order.toString().toUpperCase();
                $form.find('input[name="order"]').val(resolvedOrder);
                if (orderName !== 'order') {
                    $form.find('input[name="' + orderName + '"]').val(resolvedOrder);
                }
            }
            if (typeof state.cat_filter !== 'undefined') {
                var catName = getRequestKey($wrapper, 'cat_filter');
                var catValueAttr = $wrapper.attr('data-cat-filter');
                $form.find('select[name="cat_filter"]').val(catValueAttr);
                if (catName !== 'cat_filter') {
                    $form.find('select[name="' + catName + '"]').val(catValueAttr);
                }
            }
            if (typeof state.letter_filter !== 'undefined') {
                var letterName = getRequestKey($wrapper, 'letter_filter');
                var letterValueAttr = $wrapper.attr('data-letter-filter');
                $form.find('input[name="letter_filter"]').val(letterValueAttr);
                if (letterName !== 'letter_filter') {
                    $form.find('input[name="' + letterName + '"]').val(letterValueAttr);
                }
            }
            if (typeof state.genre_filter !== 'undefined') {
                var genreValueAttr = $wrapper.attr('data-genre-filter');
                var $genreSelect = $form.find('select[name="genre_filter"]');

                if ($genreSelect.length) {
                    $genreSelect.val(genreValueAttr);
                } else {
                    $form.find('input[name="genre_filter"]').val(genreValueAttr);
                }

                var genreName = getRequestKey($wrapper, 'genre_filter');
                if (genreName !== 'genre_filter') {
                    $form.find('select[name="' + genreName + '"]').val(genreValueAttr);
                    $form.find('input[name="' + genreName + '"]').val(genreValueAttr);
                }
            }
        }

        var $letterButtons = $wrapper.find('.jlg-summary-letter-filter [data-letter]');
        if ($letterButtons.length) {
            var activeLetter = $wrapper.attr('data-letter-filter') || '';
            $letterButtons.each(function() {
                var $button = $(this);
                var buttonLetter = ($button.attr('data-letter') || '').toString();
                var isActive = buttonLetter === activeLetter;

                $button.toggleClass('is-active', isActive);

                if (isActive) {
                    $button.attr('aria-pressed', 'true');
                } else {
                    $button.attr('aria-pressed', 'false');
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
                applyStateToUrl($wrapper, normalizedUrl, targetState);
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
            posts_per_page: postsPerPage,
            layout: $wrapper.data('layout'),
            categorie: $wrapper.data('categorie') || '',
            colonnes: $wrapper.data('colonnes') || '',
            table_id: $wrapper.attr('id'),
            current_url: targetUrl,
        };

        requestData[getRequestKey($wrapper, 'orderby')] = orderby;
        requestData[getRequestKey($wrapper, 'order')] = order;
        requestData[getRequestKey($wrapper, 'paged')] = paged;

        var catFilterKey = getRequestKey($wrapper, 'cat_filter');
        var catFilterInt = parseInt(catFilter, 10);
        if (!isNaN(catFilterInt)) {
            requestData[catFilterKey] = catFilterInt;
        } else if (catFilter !== '') {
            requestData[catFilterKey] = catFilter;
        }

        requestData[getRequestKey($wrapper, 'letter_filter')] = letterFilter;
        requestData[getRequestKey($wrapper, 'genre_filter')] = genreFilter;

        $wrapper.addClass('jlg-summary-loading');

        var restPayload = $.extend({}, requestData);
        var requestHandle = sendSummaryRequest(restPayload, requestData);

        $wrapper.data('ajaxRequest', requestHandle);

        requestHandle.promise.then(function(response) {
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
        }).catch(function(error) {
            if (error && error.name === 'AbortError') {
                return;
            }
            showError($wrapper, jlgSummarySort.strings.genericError);
        }).then(function() {
            $wrapper.removeClass('jlg-summary-loading');
            $wrapper.removeData('ajaxRequest');
        });
    }

    $('.jlg-summary-wrapper').each(function() {
        var $wrapper = $(this);
        var lastSubmitter = null;

        $wrapper.on('click', 'th.sortable a, .jlg-pagination a', function(event) {
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.which === 2) {
                return;
            }

            event.preventDefault();

            var href = $(this).attr('href');
            if (!href) {
                return;
            }

            var parsed = parseUrlParameters($wrapper, href);
            var nextState = $.extend({}, getCurrentState($wrapper), parsed.params);
            performAjax($wrapper, nextState, parsed.url.href);
        });

        $wrapper.on('click', '.jlg-summary-filters form button[type="submit"]', function() {
            lastSubmitter = this;
        });

        $wrapper.on('submit', '.jlg-summary-filters form', function(event) {
            event.preventDefault();

            var $form = $(this);
            var nativeEvent = event.originalEvent || {};
            var submitter = nativeEvent.submitter || lastSubmitter || null;
            lastSubmitter = null;
            var letterKey = getRequestKey($wrapper, 'letter_filter');
            var requestedLetter = null;

            if (submitter) {
                var $submitter = $(submitter);
                var submitterName = ($submitter.attr('name') || '').toString();
                if (submitterName === letterKey) {
                    requestedLetter = ($submitter.val() || '').toString();
                }
            }

            var $letterInput = $form.find('input[name="' + letterKey + '"]');
            if (!$letterInput.length && letterKey !== 'letter_filter') {
                $letterInput = $form.find('input[name="letter_filter"]');
            }

            if (requestedLetter === null) {
                if ($letterInput.length) {
                    requestedLetter = ($letterInput.val() || '').toString();
                } else {
                    requestedLetter = '';
                }
            }

            var currentState = getCurrentState($wrapper);
            if (submitter && requestedLetter === currentState.letter_filter && requestedLetter !== '') {
                requestedLetter = '';
            }

            if ($letterInput.length) {
                $letterInput.val(requestedLetter);
            }

            var params = {};

            $.each($form.serializeArray(), function(_, field) {
                if (field.name) {
                    params[field.name] = field.value;
                }
            });

            params = normalizeParams($wrapper, params);
            params.letter_filter = requestedLetter;

            params.paged = 1;

            var nextState = $.extend({}, getCurrentState($wrapper), params);
            updateState($wrapper, {
                letter_filter: requestedLetter,
                paged: nextState.paged,
            });
            var url = buildUrlFromState($wrapper, nextState);

            performAjax($wrapper, nextState, url);
        });

        $wrapper.on('change', 'select[name^="genre_filter"]', function() {
            var genre = $(this).val() || '';
            var currentState = getCurrentState($wrapper);

            var nextState = $.extend({}, currentState, {
                genre_filter: genre,
                paged: 1,
            });

            var url = buildUrlFromState($wrapper, nextState);

            performAjax($wrapper, nextState, url);
        });
        updateState($wrapper, getCurrentState($wrapper));
    });

    window.addEventListener('popstate', function() {
        var href = window.location.href;
        var url = new URL(href, window.location.href);
        var hash = url.hash || '';
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

        var parsed = parseUrlParameters($wrapper, href);

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

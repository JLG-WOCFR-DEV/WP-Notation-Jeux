(function() {
    const l10n = window.jlgGameExplorerL10n || {};
    const ajaxUrl = l10n.ajaxUrl || window.ajaxurl || '';
    const nonce = l10n.nonce || '';
    const strings = l10n.strings || {};

    const REQUEST_KEYS = ['orderby', 'order', 'letter', 'category', 'platform', 'developer', 'publisher', 'availability', 'search', 'paged'];
    const activeRequestControllers = new WeakMap();
    const FOCUSABLE_SELECTOR = 'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])';
    const MOBILE_BREAKPOINT = 768;
    const MOBILE_MEDIA_QUERY = '(max-width: 767px)';

    const DEFAULT_DEBOUNCE_DELAY = 250;

    function cloneState(state) {
        if (!state || typeof state !== 'object') {
            return {};
        }

        try {
            return JSON.parse(JSON.stringify(state));
        } catch (error) {
            return { ...state };
        }
    }

    function getRequestKeys(config) {
        if (!config || !config.request || !config.request.keys) {
            return {};
        }

        return config.request.keys;
    }

    function buildBaseState(config) {
        const initialState = (config && config.initialState && typeof config.initialState === 'object')
            ? config.initialState
            : {};
        const baseState = Object.assign({}, initialState);

        if (typeof baseState.orderby !== 'string' || baseState.orderby === '') {
            baseState.orderby = 'date';
        }

        if (typeof baseState.order !== 'string' || baseState.order === '') {
            baseState.order = 'DESC';
        }
        baseState.order = baseState.order.toUpperCase() === 'ASC' ? 'ASC' : 'DESC';

        if (typeof baseState.paged !== 'number' || baseState.paged < 1) {
            baseState.paged = 1;
        }

        REQUEST_KEYS.forEach((key) => {
            if (typeof baseState[key] === 'undefined') {
                baseState[key] = '';
            }
        });

        return baseState;
    }

    function normalizeStateValue(key, value, baseState) {
        if (value === null || typeof value === 'undefined') {
            return baseState[key];
        }

        if (key === 'paged') {
            const parsed = parseInt(value, 10);
            if (!Number.isInteger(parsed) || parsed < 1) {
                return baseState.paged || 1;
            }
            return parsed;
        }

        if (key === 'order') {
            const upperValue = value.toString().toUpperCase();
            return upperValue === 'ASC' ? 'ASC' : 'DESC';
        }

        if (key === 'orderby') {
            const normalized = value.toString().trim().toLowerCase();
            const allowed = ['date', 'score', 'title', 'popularity'];
            if (allowed.includes(normalized)) {
                return normalized;
            }

            return typeof baseState.orderby === 'string' && baseState.orderby !== ''
                ? baseState.orderby
                : 'date';
        }

        return value.toString();
    }

    function parseStateFromUrl(config, url) {
        let resolvedUrl = url;
        if (!resolvedUrl) {
            try {
                resolvedUrl = new URL(window.location.href);
            } catch (error) {
                return {};
            }
        }

        const params = {};
        resolvedUrl.searchParams.forEach((paramValue, paramKey) => {
            params[paramKey] = paramValue;
        });

        const keys = getRequestKeys(config);
        const normalized = {};

        REQUEST_KEYS.forEach((key) => {
            const namespaced = keys[key];
            if (namespaced && namespaced !== key && Object.prototype.hasOwnProperty.call(params, namespaced)) {
                normalized[key] = params[namespaced];
                return;
            }

            if (Object.prototype.hasOwnProperty.call(params, key)) {
                normalized[key] = params[key];
            }
        });

        return normalized;
    }

    function applyStateFromUrl(config, urlState) {
        const baseState = buildBaseState(config);
        const nextState = Object.assign({}, config.state || {});
        let changed = false;

        REQUEST_KEYS.forEach((key) => {
            const hasUrlValue = urlState && Object.prototype.hasOwnProperty.call(urlState, key);
            const nextValue = hasUrlValue
                ? normalizeStateValue(key, urlState[key], baseState)
                : baseState[key];

            if (typeof nextValue === 'undefined') {
                return;
            }

            if (nextState[key] !== nextValue) {
                changed = true;
                nextState[key] = nextValue;
            } else if (key === 'paged' || key === 'order' || key === 'orderby') {
                nextState[key] = normalizeStateValue(key, nextValue, baseState);
            }
        });

        config.state = nextState;

        return changed;
    }

    function applyStateToUrl(config, url, state) {
        if (!url || typeof url.searchParams === 'undefined') {
            return url;
        }

        const keys = getRequestKeys(config);

        REQUEST_KEYS.forEach((key) => {
            url.searchParams.delete(key);
            const namespaced = keys[key];
            if (namespaced && namespaced !== key) {
                url.searchParams.delete(namespaced);
            }
        });

        REQUEST_KEYS.forEach((key) => {
            if (!state) {
                return;
            }

            let value = state[key];

            if (key === 'paged') {
                const parsed = parseInt(value, 10);
                if (!Number.isInteger(parsed) || parsed <= 1) {
                    return;
                }
                value = parsed;
            }

            if (value === null || typeof value === 'undefined' || value === '') {
                return;
            }

            const paramName = keys[key] || key;
            url.searchParams.set(paramName, value);
        });

        return url;
    }

    function buildUrlFromState(container, config) {
        let url;
        try {
            url = new URL(window.location.href);
        } catch (error) {
            return null;
        }

        url.hash = '';
        applyStateToUrl(config, url, config.state);

        if (container && container.id) {
            url.hash = container.id;
        }

        return url.href;
    }

    function updateHistoryState(url, options = {}) {
        if (!url) {
            return;
        }

        const history = window.history;
        if (!history) {
            return;
        }

        const href = typeof url === 'string' ? url : (url && url.href);
        if (typeof href !== 'string' || href === '' || href === window.location.href) {
            return;
        }

        const replace = options.replace === true;
        if (replace && typeof history.replaceState === 'function') {
            history.replaceState({}, '', href);
            return;
        }

        if (typeof history.pushState === 'function') {
            history.pushState({}, '', href);
        }
    }

    function focusFirstElement(root) {
        if (!root) {
            return false;
        }

        const focusable = root.querySelector(FOCUSABLE_SELECTOR);
        if (focusable && typeof focusable.focus === 'function') {
            focusable.focus();
            return true;
        }

        if (typeof root.focus === 'function') {
            if (!root.hasAttribute('tabindex')) {
                root.setAttribute('tabindex', '-1');
                const handleBlur = () => {
                    if (root.getAttribute('tabindex') === '-1') {
                        root.removeAttribute('tabindex');
                    }
                    root.removeEventListener('blur', handleBlur);
                };
                root.addEventListener('blur', handleBlur);
            }

            root.focus();
            return true;
        }

        return false;
    }

    function debounce(fn, delay = DEFAULT_DEBOUNCE_DELAY) {
        let timeoutId;
        return function debounced(...args) {
            const context = this;
            if (timeoutId) {
                clearTimeout(timeoutId);
            }
            timeoutId = setTimeout(() => {
                timeoutId = null;
                fn.apply(context, args);
            }, delay);
        };
    }

    function computeRequestKey(prefix, key) {
        return prefix ? key + '__' + prefix : key;
    }

    function ensureRequestConfig(container, config) {
        const datasetPrefix = (container.dataset.requestPrefix || '').toString();
        const request = (config.request && typeof config.request === 'object') ? config.request : {};
        const prefix = (typeof request.prefix === 'string' && request.prefix !== '')
            ? request.prefix
            : (datasetPrefix || '');
        const keys = (request.keys && typeof request.keys === 'object') ? { ...request.keys } : {};

        REQUEST_KEYS.forEach((key) => {
            if (typeof keys[key] !== 'string' || keys[key] === '') {
                keys[key] = computeRequestKey(prefix, key);
            }
        });

        config.request = {
            prefix,
            keys,
        };

        container.dataset.requestPrefix = prefix;
    }

    function getRequestKey(config, key) {
        if (!config.request || !config.request.keys) {
            return key;
        }

        return config.request.keys[key] || key;
    }

    function parseConfig(container) {
        const raw = container.dataset.config || '{}';
        let parsed;

        try {
            parsed = JSON.parse(raw);
        } catch (error) {
            parsed = {};
        }

        if (!parsed || typeof parsed !== 'object') {
            parsed = {};
        }

        parsed.atts = parsed.atts || {};
        parsed.state = parsed.state || {};

        if (!parsed.state.orderby) {
            parsed.state.orderby = 'date';
        }
        if (!parsed.state.order) {
            parsed.state.order = 'DESC';
        }
        if (typeof parsed.state.paged !== 'number' || parsed.state.paged < 1) {
            parsed.state.paged = 1;
        }

        parsed.state.letter = parsed.state.letter || '';
        parsed.state.category = parsed.state.category || '';
        parsed.state.platform = parsed.state.platform || '';
        parsed.state.developer = parsed.state.developer || '';
        parsed.state.publisher = parsed.state.publisher || '';
        parsed.state.availability = parsed.state.availability || '';
        parsed.state.search = parsed.state.search || '';

        parsed.atts.id = parsed.atts.id || container.id || ('jlg-game-explorer-' + Math.random().toString(36).slice(2));
        parsed.atts.posts_per_page = parsed.atts.posts_per_page || parseInt(container.dataset.postsPerPage || '12', 10);
        parsed.atts.columns = parsed.atts.columns || parseInt(container.dataset.columns || '3', 10);
        parsed.atts.filters = parsed.atts.filters || '';
        parsed.atts.categorie = parsed.atts.categorie || '';
        parsed.atts.plateforme = parsed.atts.plateforme || '';
        parsed.atts.lettre = parsed.atts.lettre || '';
        parsed.atts.score_position = parsed.atts.score_position || 'bottom-right';

        const totalItems = parseInt(container.dataset.totalItems || '0', 10);
        if (Number.isInteger(totalItems)) {
            parsed.state.total_items = totalItems;
        }

        ensureRequestConfig(container, parsed);

        return parsed;
    }

    function writeConfig(container, config) {
        try {
            container.dataset.config = JSON.stringify(config);
        } catch (error) {
            // Ignore serialization errors silently.
        }
    }

    function resolveSortValue(select, orderby, order) {
        if (!select) {
            return null;
        }

        const upperOrder = (order || '').toUpperCase();
        for (const option of select.options) {
            const value = option.value || '';
            if (!value.includes('|')) {
                continue;
            }
            const parts = value.split('|');
            if (parts[0] === orderby && parts[1] === upperOrder) {
                return value;
            }
        }

        return null;
    }

    function updateCount(container, state) {
        const node = container.querySelector('.jlg-ge-count');
        if (!node) {
            return;
        }

        const total = state && typeof state.total_items === 'number' ? state.total_items : 0;
        const singular = strings.countSingular || '%d jeu';
        const plural = strings.countPlural || '%d jeux';
        const template = total <= 1 ? singular : plural;

        node.textContent = template.replace('%d', total);
        container.dataset.totalItems = String(total);
    }

    function updateActiveFilters(container, config, refs) {
        const state = config.state;
        const letterButtons = container.querySelectorAll('.jlg-ge-letter-nav button[data-letter]');
        letterButtons.forEach((button) => {
            const value = button.getAttribute('data-letter') || '';
            const isActive = value === state.letter || (value === '' && state.letter === '');
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        if (refs.sortSelect) {
            const resolved = resolveSortValue(refs.sortSelect, state.orderby, state.order);
            if (resolved !== null) {
                refs.sortSelect.value = resolved;
            }
        }

        if (refs.categorySelect) {
            refs.categorySelect.value = state.category || '';
        }

        if (refs.platformSelect) {
            refs.platformSelect.value = state.platform || '';
        }

        if (refs.developerSelect) {
            refs.developerSelect.value = state.developer || '';
        }

        if (refs.publisherSelect) {
            refs.publisherSelect.value = state.publisher || '';
        }

        if (refs.availabilitySelect) {
            refs.availabilitySelect.value = state.availability || '';
        }

        if (refs.searchInput) {
            refs.searchInput.value = state.search || '';
        }
    }

    function setResultsBusyState(resultsNode, isBusy) {
        if (!resultsNode) {
            return;
        }

        resultsNode.setAttribute('aria-busy', isBusy ? 'true' : 'false');
    }

    function focusUpdatedResults(refs) {
        if (!refs || !refs.resultsNode) {
            return;
        }

        const { resultsNode } = refs;
        const focusable = resultsNode.querySelector(FOCUSABLE_SELECTOR);
        if (focusable && typeof focusable.focus === 'function') {
            focusable.focus();
            return;
        }

        if (!resultsNode.hasAttribute('tabindex')) {
            resultsNode.setAttribute('tabindex', '-1');
            const removeTabIndex = () => {
                if (resultsNode.getAttribute('tabindex') === '-1') {
                    resultsNode.removeAttribute('tabindex');
                }
                resultsNode.removeEventListener('blur', removeTabIndex);
            };
            resultsNode.addEventListener('blur', removeTabIndex);
        }

        if (typeof resultsNode.focus === 'function') {
            resultsNode.focus();
        }
    }

    function setFiltersPanelExpanded(container, refs, expanded, options = {}) {
        if (!refs || !refs.filtersPanel || !refs.filtersToggle) {
            return;
        }

        const state = refs.state || (refs.state = {});
        const force = options.force === true;
        const nextExpanded = !!expanded;

        if (!force && state.filtersExpanded === nextExpanded) {
            return;
        }

        state.filtersExpanded = nextExpanded;

        const panel = refs.filtersPanel;
        const toggle = refs.filtersToggle;
        const wrapper = refs.filtersWrapper;
        const backdrop = refs.filtersBackdrop;

        const expandedValue = nextExpanded ? 'true' : 'false';

        toggle.setAttribute('aria-expanded', expandedValue);
        toggle.dataset.expanded = expandedValue;

        panel.setAttribute('aria-hidden', nextExpanded ? 'false' : 'true');
        panel.dataset.expanded = expandedValue;
        panel.classList.toggle('is-collapsed', !nextExpanded);

        if (wrapper) {
            wrapper.classList.toggle('is-expanded', nextExpanded);
            wrapper.dataset.expanded = expandedValue;
        }

        if (backdrop) {
            const showBackdrop = nextExpanded && !!state.isMobile;
            backdrop.classList.toggle('is-active', showBackdrop);
            backdrop.setAttribute('aria-hidden', showBackdrop ? 'false' : 'true');
            backdrop.dataset.active = showBackdrop ? 'true' : 'false';
        }

        if (nextExpanded) {
            if (state.isMobile && options.autoFocus !== false) {
                focusFirstElement(panel);
            }
            return;
        }

        if (options.skipFocus === true) {
            return;
        }

        if (options.focusTarget === 'results') {
            return;
        }

        if (typeof toggle.focus === 'function') {
            toggle.focus();
        }
    }

    function collapseFiltersForMobile(container, refs, options = {}) {
        if (!refs || !refs.state || !refs.state.isMobile) {
            return;
        }

        if (!refs.state.filtersExpanded) {
            return;
        }

        setFiltersPanelExpanded(container, refs, false, Object.assign({ skipFocus: true }, options));
    }

    function setupFiltersPanel(container, refs) {
        if (!refs) {
            return;
        }

        const panel = refs.filtersPanel;
        const toggle = refs.filtersToggle;
        if (!panel || !toggle) {
            return;
        }

        const state = refs.state || (refs.state = {});
        let panelId = panel.id;
        if (typeof panelId !== 'string' || panelId === '') {
            panelId = (container.id || 'jlg-game-explorer') + '-filters';
            panel.id = panelId;
        }

        toggle.setAttribute('aria-controls', panelId);
        toggle.setAttribute('aria-expanded', 'true');
        toggle.dataset.expanded = 'true';

        panel.setAttribute('aria-hidden', 'false');
        panel.dataset.expanded = 'true';

        if (refs.filtersWrapper) {
            refs.filtersWrapper.dataset.expanded = 'true';
        }

        if (refs.filtersBackdrop) {
            refs.filtersBackdrop.dataset.active = 'false';
        }

        const applyMobileState = (isMobile) => {
            state.isMobile = !!isMobile;

            if (refs.filtersWrapper) {
                refs.filtersWrapper.classList.toggle('is-mobile', state.isMobile);
            }

            if (refs.filtersBackdrop) {
                refs.filtersBackdrop.classList.toggle('is-mobile', state.isMobile);
            }

            if (state.isMobile) {
                setFiltersPanelExpanded(container, refs, false, { force: true, skipFocus: true });
            } else {
                setFiltersPanelExpanded(container, refs, true, { force: true, skipFocus: true });
            }
        };

        let mediaQuery;
        if (typeof window.matchMedia === 'function') {
            mediaQuery = window.matchMedia(MOBILE_MEDIA_QUERY);
        }

        if (mediaQuery) {
            applyMobileState(mediaQuery.matches);
            const handleChange = (event) => {
                applyMobileState(event.matches);
            };

            if (typeof mediaQuery.addEventListener === 'function') {
                mediaQuery.addEventListener('change', handleChange);
            } else if (typeof mediaQuery.addListener === 'function') {
                mediaQuery.addListener(handleChange);
            }

            state.mobileMediaQuery = mediaQuery;
        } else {
            applyMobileState(window.innerWidth < MOBILE_BREAKPOINT);
        }

        toggle.addEventListener('click', (event) => {
            event.preventDefault();
            const next = !state.filtersExpanded;
            setFiltersPanelExpanded(container, refs, next);
        });

        if (refs.filtersBackdrop) {
            refs.filtersBackdrop.addEventListener('click', () => {
                if (!state.isMobile) {
                    return;
                }
                setFiltersPanelExpanded(container, refs, false);
            });
        }

        panel.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && state.isMobile && state.filtersExpanded) {
                setFiltersPanelExpanded(container, refs, false);
            }
        });
    }

    function updatePaginationAccessibility(refs) {
        if (!refs || !refs.resultsNode) {
            return;
        }

        const pagination = refs.resultsNode.querySelector('[data-role="pagination"]');
        if (!pagination) {
            return;
        }

        const buttons = pagination.querySelectorAll('button[data-page]');
        buttons.forEach((button) => {
            const isControlButton = button.classList.contains('jlg-ge-page--prev')
                || button.classList.contains('jlg-ge-page--next');

            if (isControlButton) {
                if (button.disabled) {
                    button.setAttribute('aria-disabled', 'true');
                } else {
                    button.removeAttribute('aria-disabled');
                }
                button.removeAttribute('aria-current');
                return;
            }

            if (button.classList.contains('is-active') || button.disabled) {
                button.setAttribute('aria-current', 'page');
            } else {
                button.removeAttribute('aria-current');
            }
        });
    }

    function bindPagination(container, config, refs) {
        if (!refs.resultsNode) {
            return;
        }

        const pagination = refs.resultsNode.querySelector('[data-role="pagination"]');
        if (!pagination) {
            return;
        }

        const buttons = pagination.querySelectorAll('button[data-page]');
        buttons.forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                if (button.disabled) {
                    return;
                }

                const target = parseInt(button.getAttribute('data-page') || '0', 10);
                if (!Number.isInteger(target) || target < 1 || target === config.state.paged) {
                    return;
                }

                config.state.paged = target;
                writeConfig(container, config);
                updateActiveFilters(container, config, refs);
                refreshResults(container, config, refs);
            });
        });

        updatePaginationAccessibility(refs);
    }

    function refreshResults(container, config, refs, options = {}) {
        if (!ajaxUrl) {
            return;
        }

        ensureRequestConfig(container, config);

        const refreshOptions = (options && typeof options === 'object') ? options : {};
        const shouldUpdateHistory = refreshOptions.updateHistory !== false;
        const replaceHistory = refreshOptions.replace === true
            || refreshOptions.replaceState === true
            || refreshOptions.replaceHistory === true;

        const loadingText = strings.loading || 'Loading…';
        if (refs.resultsNode) {
            refs.resultsNode.dataset.loadingText = loadingText;
            setResultsBusyState(refs.resultsNode, true);
        }

        container.classList.add('is-loading');

        const payload = new FormData();
        payload.set('action', 'jlg_game_explorer_sort');
        payload.set('nonce', nonce);
        payload.set('container_id', config.atts.id || container.id);
        payload.set('posts_per_page', config.atts.posts_per_page);
        payload.set('columns', config.atts.columns);
        payload.set('filters', config.atts.filters || '');
        payload.set('score_position', config.atts.score_position || 'bottom-right');
        payload.set('categorie', config.atts.categorie || '');
        payload.set('plateforme', config.atts.plateforme || '');
        payload.set('lettre', config.atts.lettre || '');
        payload.set(getRequestKey(config, 'orderby'), config.state.orderby);
        payload.set(getRequestKey(config, 'order'), config.state.order);
        payload.set(getRequestKey(config, 'letter'), config.state.letter);
        payload.set(getRequestKey(config, 'category'), config.state.category);
        payload.set(getRequestKey(config, 'platform'), config.state.platform);
        payload.set(getRequestKey(config, 'developer'), config.state.developer);
        payload.set(getRequestKey(config, 'publisher'), config.state.publisher);
        payload.set(getRequestKey(config, 'availability'), config.state.availability);
        payload.set(getRequestKey(config, 'search'), config.state.search);
        payload.set(getRequestKey(config, 'paged'), config.state.paged);

        const previousController = activeRequestControllers.get(container);
        if (previousController) {
            previousController.abort();
        }

        const requestController = new AbortController();
        activeRequestControllers.set(container, requestController);

        fetch(ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: payload,
            signal: requestController.signal,
        })
            .then((response) => response.json())
            .then((data) => {
                if (requestController.signal.aborted) {
                    return;
                }
                if (!data || !data.success || !data.data) {
                    throw new Error('Invalid response');
                }

                const responseData = data.data;
                if (refs.resultsNode) {
                    if (responseData.html) {
                        refs.resultsNode.innerHTML = responseData.html;
                    } else {
                        const empty = strings.noResults || 'Aucun résultat.';
                        refs.resultsNode.innerHTML = '<p>' + empty + '</p>';
                    }
                    setResultsBusyState(refs.resultsNode, false);
                    updatePaginationAccessibility(refs);
                }

                if (responseData.state) {
                    config.state = Object.assign({}, config.state, responseData.state);
                }

                if (responseData.config && typeof responseData.config === 'object') {
                    if (responseData.config.atts && typeof responseData.config.atts === 'object') {
                        config.atts = Object.assign({}, config.atts, responseData.config.atts);
                    }

                    if (responseData.config.request && typeof responseData.config.request === 'object') {
                        config.request = Object.assign({}, config.request, responseData.config.request);
                    }
                }

                writeConfig(container, config);
                updateCount(container, config.state);

                updateActiveFilters(container, config, refs);
                bindPagination(container, config, refs);

                if (shouldUpdateHistory) {
                    const url = buildUrlFromState(container, config);
                    updateHistoryState(url, { replace: replaceHistory });
                }

                const focusHandler = () => {
                    focusUpdatedResults(refs);
                };
                if (typeof window.requestAnimationFrame === 'function') {
                    window.requestAnimationFrame(focusHandler);
                } else {
                    setTimeout(focusHandler, 0);
                }
            })
            .catch((error) => {
                const isAbortError = (typeof DOMException !== 'undefined'
                    && error instanceof DOMException
                    && error.name === 'AbortError')
                    || (error && error.name === 'AbortError');
                if (isAbortError) {
                    return;
                }
                if (refs.resultsNode) {
                    const errorMessage = strings.genericError || 'Une erreur est survenue.';
                    refs.resultsNode.innerHTML = '<p>' + errorMessage + '</p>';
                    setResultsBusyState(refs.resultsNode, false);
                }
            })
            .finally(() => {
                const storedController = activeRequestControllers.get(container);
                if (storedController === requestController) {
                    activeRequestControllers.delete(container);
                }
                if (refs.resultsNode) {
                    setResultsBusyState(refs.resultsNode, false);
                }
                container.classList.remove('is-loading');
            });
    }

    function initExplorer(container) {
        const config = parseConfig(container);
        ensureRequestConfig(container, config);

        const hasInitialState = config.initialState && typeof config.initialState === 'object';
        config.initialState = hasInitialState ? cloneState(config.initialState) : cloneState(config.state);

        const defaultState = cloneState(config.initialState);

        const initialUrlState = parseStateFromUrl(config);
        const stateChangedFromUrl = applyStateFromUrl(config, initialUrlState);

        writeConfig(container, config);

        const refs = {
            resultsNode: container.querySelector('[data-role="results"]'),
            sortSelect: container.querySelector('[data-role="sort"]'),
            categorySelect: container.querySelector('[data-role="category"]'),
            platformSelect: container.querySelector('[data-role="platform"]'),
            developerSelect: container.querySelector('[data-role="developer"]'),
            publisherSelect: container.querySelector('[data-role="publisher"]'),
            availabilitySelect: container.querySelector('[data-role="availability"]'),
            searchInput: container.querySelector('[data-role="search"]'),
            resetButton: container.querySelector('[data-role="reset"]'),
            filtersWrapper: container.querySelector('[data-role="filters-wrapper"]'),
            filtersPanel: container.querySelector('[data-role="filters-panel"]'),
            filtersToggle: container.querySelector('[data-role="filters-toggle"]'),
            filtersBackdrop: container.querySelector('[data-role="filters-backdrop"]'),
            filtersForm: container.querySelector('[data-role="filters-form"]'),
        };

        refs.state = {
            filtersExpanded: true,
            isMobile: false,
        };

        container.__jlgGameExplorer = { config, refs };

        setupFiltersPanel(container, refs);

        if (refs.resultsNode && strings.loading) {
            refs.resultsNode.dataset.loadingText = strings.loading;
        }

        if (refs.resetButton && strings.reset) {
            refs.resetButton.textContent = strings.reset;
        }

        if (refs.sortSelect) {
            refs.sortSelect.addEventListener('change', () => {
                const value = refs.sortSelect.value || '';
                const parts = value.split('|');
                if (parts.length === 2) {
                    config.state.orderby = parts[0];
                    config.state.order = parts[1].toUpperCase();
                }
                config.state.paged = 1;
                writeConfig(container, config);
                updateActiveFilters(container, config, refs);
                refreshResults(container, config, refs);
            });
            const initialValue = resolveSortValue(refs.sortSelect, config.state.orderby, config.state.order);
            if (initialValue !== null) {
                refs.sortSelect.value = initialValue;
            }
        }

        const letterButtons = container.querySelectorAll('.jlg-ge-letter-nav button[data-letter]');
        letterButtons.forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                if (button.disabled) {
                    return;
                }
                const value = button.getAttribute('data-letter') || '';
                config.state.letter = value;
                config.state.paged = 1;
                writeConfig(container, config);
                updateActiveFilters(container, config, refs);
                refreshResults(container, config, refs);
            });
        });

        if (refs.categorySelect) {
            refs.categorySelect.addEventListener('change', () => {
                config.state.category = refs.categorySelect.value || '';
                config.state.paged = 1;
                writeConfig(container, config);
                collapseFiltersForMobile(container, refs);
                refreshResults(container, config, refs);
            });
        }

        if (refs.platformSelect) {
            refs.platformSelect.addEventListener('change', () => {
                config.state.platform = refs.platformSelect.value || '';
                config.state.paged = 1;
                writeConfig(container, config);
                collapseFiltersForMobile(container, refs);
                refreshResults(container, config, refs);
            });
        }

        if (refs.developerSelect) {
            refs.developerSelect.addEventListener('change', () => {
                config.state.developer = refs.developerSelect.value || '';
                config.state.paged = 1;
                writeConfig(container, config);
                collapseFiltersForMobile(container, refs);
                refreshResults(container, config, refs);
            });
        }

        if (refs.publisherSelect) {
            refs.publisherSelect.addEventListener('change', () => {
                config.state.publisher = refs.publisherSelect.value || '';
                config.state.paged = 1;
                writeConfig(container, config);
                collapseFiltersForMobile(container, refs);
                refreshResults(container, config, refs);
            });
        }

        if (refs.availabilitySelect) {
            refs.availabilitySelect.addEventListener('change', () => {
                config.state.availability = refs.availabilitySelect.value || '';
                config.state.paged = 1;
                writeConfig(container, config);
                collapseFiltersForMobile(container, refs);
                refreshResults(container, config, refs);
            });
        }

        if (refs.searchInput) {
            const scheduleSearchRefresh = debounce(() => {
                refreshResults(container, config, refs);
            }, DEFAULT_DEBOUNCE_DELAY);
            const handleSearchUpdate = () => {
                const newValue = refs.searchInput.value || '';
                if (newValue === config.state.search) {
                    return;
                }

                config.state.search = newValue;
                config.state.paged = 1;
                writeConfig(container, config);
                scheduleSearchRefresh();
            };

            refs.searchInput.addEventListener('input', handleSearchUpdate);
            refs.searchInput.addEventListener('change', handleSearchUpdate);
        }

        if (refs.resetButton) {
            refs.resetButton.addEventListener('click', (event) => {
                if (event && typeof event.preventDefault === 'function') {
                    event.preventDefault();
                }
                config.state = Object.assign({}, defaultState);
                config.state.paged = 1;
                writeConfig(container, config);
                updateActiveFilters(container, config, refs);
                collapseFiltersForMobile(container, refs);
                refreshResults(container, config, refs);
            });
        }

        if (refs.filtersForm) {
            refs.filtersForm.addEventListener('submit', (event) => {
                if (event && typeof event.preventDefault === 'function') {
                    event.preventDefault();
                }

                if (refs.categorySelect) {
                    config.state.category = refs.categorySelect.value || '';
                }

                if (refs.platformSelect) {
                    config.state.platform = refs.platformSelect.value || '';
                }

                if (refs.developerSelect) {
                    config.state.developer = refs.developerSelect.value || '';
                }

                if (refs.publisherSelect) {
                    config.state.publisher = refs.publisherSelect.value || '';
                }

                if (refs.availabilitySelect) {
                    config.state.availability = refs.availabilitySelect.value || '';
                }

                if (refs.searchInput) {
                    config.state.search = refs.searchInput.value || '';
                }

                config.state.paged = 1;
                writeConfig(container, config);
                collapseFiltersForMobile(container, refs);
                refreshResults(container, config, refs);
            });
        }

        updateActiveFilters(container, config, refs);
        updateCount(container, config.state);
        bindPagination(container, config, refs);

        if (stateChangedFromUrl) {
            refreshResults(container, config, refs, { updateHistory: false, replace: true });
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const explorers = document.querySelectorAll('.jlg-game-explorer');
        explorers.forEach((container) => {
            initExplorer(container);
        });
    });

    window.addEventListener('popstate', () => {
        let url;
        try {
            url = new URL(window.location.href);
        } catch (error) {
            return;
        }

        const explorers = document.querySelectorAll('.jlg-game-explorer');
        explorers.forEach((container) => {
            if (!container || !container.__jlgGameExplorer) {
                return;
            }

            const { config, refs } = container.__jlgGameExplorer;
            ensureRequestConfig(container, config);
            const urlState = parseStateFromUrl(config, url);
            const stateChanged = applyStateFromUrl(config, urlState);

            writeConfig(container, config);
            updateActiveFilters(container, config, refs);

            if (stateChanged) {
                refreshResults(container, config, refs, { updateHistory: false, replace: true });
            }
        });
    });
})();

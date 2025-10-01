(function() {
    const l10n = window.jlgGameExplorerL10n || {};
    const ajaxUrl = l10n.ajaxUrl || window.ajaxurl || '';
    const nonce = l10n.nonce || '';
    const strings = l10n.strings || {};

    const REQUEST_KEYS = ['orderby', 'order', 'letter', 'category', 'platform', 'availability', 'search', 'paged'];
    const FOCUSABLE_SELECTOR = 'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])';

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

        if (isBusy) {
            resultsNode.setAttribute('aria-busy', 'true');
        } else {
            resultsNode.removeAttribute('aria-busy');
        }
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
    }

    function refreshResults(container, config, refs) {
        if (!ajaxUrl) {
            return;
        }

        ensureRequestConfig(container, config);

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
        payload.set(getRequestKey(config, 'availability'), config.state.availability);
        payload.set(getRequestKey(config, 'search'), config.state.search);
        payload.set(getRequestKey(config, 'paged'), config.state.paged);

        fetch(ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: payload,
        })
            .then((response) => response.json())
            .then((data) => {
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

                const focusHandler = () => {
                    focusUpdatedResults(refs);
                };
                if (typeof window.requestAnimationFrame === 'function') {
                    window.requestAnimationFrame(focusHandler);
                } else {
                    setTimeout(focusHandler, 0);
                }
            })
            .catch(() => {
                if (refs.resultsNode) {
                    const errorMessage = strings.genericError || 'Une erreur est survenue.';
                    refs.resultsNode.innerHTML = '<p>' + errorMessage + '</p>';
                    setResultsBusyState(refs.resultsNode, false);
                }
            })
            .finally(() => {
                if (refs.resultsNode) {
                    setResultsBusyState(refs.resultsNode, false);
                }
                container.classList.remove('is-loading');
            });
    }

    function initExplorer(container) {
        const config = parseConfig(container);
        writeConfig(container, config);

        const refs = {
            resultsNode: container.querySelector('[data-role="results"]'),
            sortSelect: container.querySelector('[data-role="sort"]'),
            categorySelect: container.querySelector('[data-role="category"]'),
            platformSelect: container.querySelector('[data-role="platform"]'),
            availabilitySelect: container.querySelector('[data-role="availability"]'),
            searchInput: container.querySelector('[data-role="search"]'),
            resetButton: container.querySelector('[data-role="reset"]'),
        };

        if (refs.resultsNode && strings.loading) {
            refs.resultsNode.dataset.loadingText = strings.loading;
        }

        if (refs.resetButton && strings.reset) {
            refs.resetButton.textContent = strings.reset;
        }

        const defaultState = JSON.parse(JSON.stringify(config.state));

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
                refreshResults(container, config, refs);
            });
        }

        if (refs.platformSelect) {
            refs.platformSelect.addEventListener('change', () => {
                config.state.platform = refs.platformSelect.value || '';
                config.state.paged = 1;
                writeConfig(container, config);
                refreshResults(container, config, refs);
            });
        }

        if (refs.availabilitySelect) {
            refs.availabilitySelect.addEventListener('change', () => {
                config.state.availability = refs.availabilitySelect.value || '';
                config.state.paged = 1;
                writeConfig(container, config);
                refreshResults(container, config, refs);
            });
        }

        if (refs.searchInput) {
            const handleSearchUpdate = () => {
                const newValue = refs.searchInput.value || '';
                if (newValue === config.state.search) {
                    return;
                }

                config.state.search = newValue;
                config.state.paged = 1;
                writeConfig(container, config);
                refreshResults(container, config, refs);
            };

            refs.searchInput.addEventListener('input', handleSearchUpdate);
            refs.searchInput.addEventListener('change', handleSearchUpdate);
        }

        if (refs.resetButton) {
            refs.resetButton.addEventListener('click', () => {
                config.state = Object.assign({}, defaultState);
                config.state.paged = 1;
                writeConfig(container, config);
                updateActiveFilters(container, config, refs);
                refreshResults(container, config, refs);
            });
        }

        updateActiveFilters(container, config, refs);
        updateCount(container, config.state);
        bindPagination(container, config, refs);
    }

    document.addEventListener('DOMContentLoaded', () => {
        const explorers = document.querySelectorAll('.jlg-game-explorer');
        explorers.forEach((container) => {
            initExplorer(container);
        });
    });
})();

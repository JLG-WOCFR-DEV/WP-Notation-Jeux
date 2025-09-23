(function() {
    const l10n = window.jlgGameExplorerL10n || {};
    const ajaxUrl = l10n.ajaxUrl || window.ajaxurl || '';
    const nonce = l10n.nonce || '';
    const strings = l10n.strings || {};

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

        const totalItems = parseInt(container.dataset.totalItems || '0', 10);
        if (Number.isInteger(totalItems)) {
            parsed.state.total_items = totalItems;
        }

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

        const loadingText = strings.loading || 'Loading…';
        if (refs.resultsNode) {
            refs.resultsNode.dataset.loadingText = loadingText;
        }

        container.classList.add('is-loading');

        const payload = new FormData();
        payload.append('action', 'jlg_game_explorer_sort');
        payload.append('nonce', nonce);
        payload.append('container_id', config.atts.id || container.id);
        payload.append('posts_per_page', config.atts.posts_per_page);
        payload.append('columns', config.atts.columns);
        payload.append('filters', config.atts.filters || '');
        payload.append('categorie', config.atts.categorie || '');
        payload.append('plateforme', config.atts.plateforme || '');
        payload.append('lettre', config.atts.lettre || '');
        payload.append('orderby', config.state.orderby);
        payload.append('order', config.state.order);
        payload.append('letter', config.state.letter);
        payload.append('category', config.state.category);
        payload.append('platform', config.state.platform);
        payload.append('availability', config.state.availability);
        payload.append('search', config.state.search);
        payload.append('paged', config.state.paged);

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
                }

                if (responseData.state) {
                    config.state = Object.assign({}, config.state, responseData.state);
                    writeConfig(container, config);
                    updateCount(container, responseData.state);
                }

                updateActiveFilters(container, config, refs);
                bindPagination(container, config, refs);
            })
            .catch(() => {
                if (refs.resultsNode) {
                    const errorMessage = strings.genericError || 'Une erreur est survenue.';
                    refs.resultsNode.innerHTML = '<p>' + errorMessage + '</p>';
                }
            })
            .finally(() => {
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

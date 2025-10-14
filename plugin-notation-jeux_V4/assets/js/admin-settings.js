(function () {
    'use strict';

    var enhancedSections = [];
    var panelsByMode = {};
    var currentMode = '';
    var restSettings = {};
    var modeButtons = [];
    var filterEvaluator = null;

    function parsePreviewSnapshot() {
        var script = document.getElementById('jlg-settings-preview-snapshot');
        if (!script) {
            return {};
        }

        try {
            return JSON.parse(script.textContent || script.innerText || '{}');
        } catch (error) {
            console.warn('JLG Notation: unable to parse preview snapshot', error);
            return {};
        }
    }

    function getAdminData() {
        var fromWindow = window.jlgAdminSettingsData || {};
        var preview = Object.assign({}, parsePreviewSnapshot(), fromWindow.preview || {});

        return {
            sections: Array.isArray(fromWindow.sections) ? fromWindow.sections : [],
            panels: typeof fromWindow.panels === 'object' && fromWindow.panels !== null ? fromWindow.panels : {},
            dependencies: Array.isArray(fromWindow.dependencies) ? fromWindow.dependencies : [],
            preview: preview,
            modes: typeof fromWindow.modes === 'object' && fromWindow.modes !== null ? fromWindow.modes : {},
            activeMode: typeof fromWindow.activeMode === 'string' ? fromWindow.activeMode : '',
            rest: typeof fromWindow.rest === 'object' && fromWindow.rest !== null ? fromWindow.rest : {},
            i18n: fromWindow.i18n || {}
        };
    }

    function enhanceSectionsLayout() {
        enhancedSections = [];

        var form = document.querySelector('.jlg-settings-form');
        if (!form) {
            return;
        }

        var headings = Array.prototype.slice.call(form.querySelectorAll('h2'));
        if (!headings.length) {
            return;
        }

        headings.forEach(function (heading) {
            if (!heading || heading.closest('.jlg-settings-section')) {
                return;
            }

            var parent = heading.parentNode;
            if (!parent) {
                return;
            }

            var sectionWrapper = document.createElement('section');
            sectionWrapper.className = 'jlg-settings-section';

            var siblings = [];
            var walker = heading.nextElementSibling;
            while (walker && walker.tagName && walker.tagName.toLowerCase() !== 'h2') {
                siblings.push(walker);
                walker = walker.nextElementSibling;
            }

            var titleText = (heading.textContent || '').trim();
            parent.insertBefore(sectionWrapper, heading);
            sectionWrapper.appendChild(heading);

            siblings.forEach(function (node) {
                sectionWrapper.appendChild(node);
            });

            heading.classList.add('jlg-settings-section__heading');

            var toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.className = 'jlg-settings-section__toggle';
            toggle.setAttribute('aria-expanded', 'true');
            toggle.innerHTML = '<span class="jlg-settings-section__title">' + titleText + '</span>';

            heading.textContent = '';
            heading.appendChild(toggle);

            toggle.addEventListener('click', function () {
                var isExpanded = toggle.getAttribute('aria-expanded') === 'true';
                toggle.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
                sectionWrapper.classList.toggle('is-collapsed', isExpanded);
            });

            var searchIndex = (titleText + ' ' + sectionWrapper.textContent).toLowerCase();
            sectionWrapper.setAttribute('data-search-index', searchIndex);

            enhancedSections.push({
                wrapper: sectionWrapper,
                toggle: toggle,
                heading: heading,
                title: titleText,
                id: ''
            });
        });
    }

    function setSectionCollapsed(sectionWrapper, collapsed) {
        if (!sectionWrapper) {
            return;
        }

        sectionWrapper.classList.toggle('is-collapsed', collapsed);
        var toggle = sectionWrapper.querySelector('.jlg-settings-section__toggle');
        if (toggle) {
            toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        }
    }

    function assignSectionAnchors(sections) {
        if (!sections.length) {
            return;
        }

        var headings = document.querySelectorAll('.jlg-settings-form h2');
        if (!headings.length) {
            return;
        }

        sections.forEach(function (section, index) {
            var heading = headings[index];
            if (!heading || !section || !section.id) {
                return;
            }

            var anchorId = 'section-' + section.id;
            heading.id = anchorId;
            heading.classList.add('jlg-settings-heading');

            var matched = enhancedSections.find(function (entry) {
                return entry.heading === heading;
            });

            if (matched) {
                matched.id = section.id;
                if (matched.wrapper) {
                    matched.wrapper.setAttribute('data-section-id', section.id);
                }
            }
        });
    }

    function setupTocInteraction() {
        var tocLinks = Array.prototype.slice.call(document.querySelectorAll('.jlg-settings-toc__link'));
        if (!tocLinks.length) {
            return;
        }

        tocLinks.forEach(function (link) {
            link.addEventListener('click', function (event) {
                var href = link.getAttribute('href');
                if (!href || href.charAt(0) !== '#') {
                    return;
                }

                var target = document.querySelector(href);
                if (!target) {
                    return;
                }

                event.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });

                if (history.replaceState) {
                    history.replaceState(null, '', href);
                }
            });
        });

        var headings = Array.prototype.slice.call(document.querySelectorAll('.jlg-settings-heading'));
        if (!headings.length || typeof IntersectionObserver === 'undefined') {
            return;
        }

        var linkMap = new Map();
        tocLinks.forEach(function (link) {
            var sectionId = link.getAttribute('data-section-id');
            if (!sectionId) {
                return;
            }

            linkMap.set('section-' + sectionId, link);
        });

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }

                var activeLink = linkMap.get(entry.target.id);
                if (!activeLink) {
                    return;
                }

                tocLinks.forEach(function (link) {
                    link.classList.remove('is-active');
                });
                activeLink.classList.add('is-active');
            });
        }, { rootMargin: '-40% 0px -55% 0px', threshold: [0, 0.5, 1] });

        headings.forEach(function (heading) {
            observer.observe(heading);
        });
    }

    function setupToolbarButtons() {
        var collapseAll = document.querySelector('[data-collapse-all]');
        var expandAll = document.querySelector('[data-expand-all]');

        if (collapseAll) {
            collapseAll.addEventListener('click', function () {
                enhancedSections.forEach(function (entry) {
                    if (entry.wrapper && entry.wrapper.hasAttribute('hidden')) {
                        return;
                    }
                    setSectionCollapsed(entry.wrapper, true);
                });
            });
        }

        if (expandAll) {
            expandAll.addEventListener('click', function () {
                enhancedSections.forEach(function (entry) {
                    if (entry.wrapper && entry.wrapper.hasAttribute('hidden')) {
                        return;
                    }
                    setSectionCollapsed(entry.wrapper, false);
                });
            });
        }
    }

    function setupSearchFilter(i18n) {
        var input = document.querySelector('[data-settings-filter]');
        if (!input) {
            return;
        }

        var emptyState = document.querySelector('[data-filter-empty]');
        var handler = function () {
            var query = (input.value || '').toLowerCase().trim();
            var matches = 0;

            enhancedSections.forEach(function (entry) {
                if (!entry || !entry.wrapper) {
                    return;
                }

                if (entry.wrapper.hasAttribute('hidden')) {
                    entry.wrapper.classList.remove('is-filtered-out');
                    return;
                }

                var searchIndex = entry.wrapper.getAttribute('data-search-index') || '';
                var isMatch = query === '' || searchIndex.indexOf(query) !== -1;

                if (isMatch) {
                    entry.wrapper.classList.remove('is-filtered-out');
                    setSectionCollapsed(entry.wrapper, false);
                    matches += 1;
                } else {
                    entry.wrapper.classList.add('is-filtered-out');
                }
            });

            if (!emptyState) {
                return;
            }

            if (query !== '' && matches === 0) {
                emptyState.textContent = (i18n && i18n.filterNoResult) || 'Aucun réglage ne correspond à votre recherche.';
                emptyState.hidden = false;
            } else {
                emptyState.hidden = true;
            }
        };

        input.addEventListener('input', handler);
        filterEvaluator = handler;
    }

    function getControllerValue(controller) {
        if (!controller) {
            return '';
        }

        var tagName = controller.tagName ? controller.tagName.toLowerCase() : '';

        if (tagName === 'input') {
            var type = controller.getAttribute('type');
            if (type === 'checkbox') {
                return controller.checked ? '1' : '0';
            }

            return controller.value;
        }

        if (tagName === 'select' || tagName === 'textarea') {
            return controller.value;
        }

        return controller.value || '';
    }

    function isDependencyMet(controller, comparison, expectedValue) {
        if (!controller) {
            return false;
        }

        var value = getControllerValue(controller);
        var comparisonType = comparison || 'equals';
        var expected = expectedValue != null ? String(expectedValue) : '1';

        switch (comparisonType) {
            case 'equals':
                return value === expected;
            case 'not_equals':
                return value !== expected;
            case 'checked':
                return controller.checked === true;
            default:
                return value === expected;
        }
    }

    function createDependencyManager(dependencies, defaultMessage) {
        if (!dependencies.length) {
            return {
                evaluate: function () {}
            };
        }

        var controllersBound = new Set();

        function evaluate() {
            var states = new Map();

            dependencies.forEach(function (dependency) {
                if (!dependency || !dependency.controller || !Array.isArray(dependency.targets)) {
                    return;
                }

                var controller = document.getElementById(dependency.controller);
                var isActive = isDependencyMet(controller, dependency.comparison, dependency.expectedValue);

                dependency.targets.forEach(function (targetId) {
                    if (!states.has(targetId)) {
                        states.set(targetId, []);
                    }

                    states.get(targetId).push({
                        active: isActive,
                        message: isActive ? '' : (dependency.message || defaultMessage)
                    });
                });
            });

            states.forEach(function (entries, targetId) {
                var target = document.getElementById(targetId);
                if (!target) {
                    return;
                }

                var row = target.closest('tr');
                if (!row) {
                    return;
                }

                var shouldEnable = entries.every(function (entry) {
                    return entry.active;
                });

                target.disabled = !shouldEnable;

                if (shouldEnable) {
                    row.classList.remove('jlg-setting-disabled');
                    var existing = row.querySelector('.jlg-settings-dependency-notice');
                    if (existing) {
                        existing.remove();
                    }
                } else {
                    row.classList.add('jlg-setting-disabled');
                    var notice = row.querySelector('.jlg-settings-dependency-notice');
                    var messageSource = entries.find(function (entry) {
                        return !entry.active && entry.message;
                    });
                    var messageText = messageSource && messageSource.message ? messageSource.message : defaultMessage;

                    if (!notice) {
                        notice = document.createElement('p');
                        notice.className = 'description jlg-settings-dependency-notice';
                        var cell = row.querySelector('td');
                        if (cell) {
                            cell.appendChild(notice);
                        }
                    }

                    if (notice) {
                        notice.textContent = messageText;
                    }
                }
            });
        }

        dependencies.forEach(function (dependency) {
            if (!dependency || !dependency.controller) {
                return;
            }

            var controller = document.getElementById(dependency.controller);
            if (!controller || controllersBound.has(controller)) {
                return;
            }

            controllersBound.add(controller);

            var handler = function () {
                evaluate();
            };

            controller.addEventListener('change', handler);
            controller.addEventListener('input', handler);
        });

        evaluate();

        return {
            evaluate: evaluate
        };
    }

    function buildPanelMap(panels) {
        var map = {};

        if (!panels || typeof panels !== 'object') {
            return map;
        }

        Object.keys(panels).forEach(function (mode) {
            var panel = panels[mode];
            if (!panel || !Array.isArray(panel.sections)) {
                return;
            }

            map[mode] = panel.sections.reduce(function (accumulator, section) {
                if (section && section.id) {
                    accumulator.push(section.id);
                }
                return accumulator;
            }, []);
        });

        return map;
    }

    function applyMode(mode) {
        if (!mode) {
            return;
        }

        var allowedIds = panelsByMode[mode];

        if (!Array.isArray(allowedIds)) {
            allowedIds = [];
        }

        var allowAll = allowedIds.length === 0;
        var allowedSet = new Set(allowedIds);

        enhancedSections.forEach(function (entry) {
            if (!entry || !entry.wrapper) {
                return;
            }

            var sectionId = entry.id || entry.wrapper.getAttribute('data-section-id') || '';
            var isAllowed = allowAll || allowedSet.has(sectionId);

            if (isAllowed) {
                entry.wrapper.removeAttribute('hidden');
                entry.wrapper.classList.remove('is-mode-hidden');
            } else {
                entry.wrapper.setAttribute('hidden', 'hidden');
                entry.wrapper.classList.add('is-mode-hidden');
            }
        });

        var tocItems = document.querySelectorAll('.jlg-settings-toc__item');
        Array.prototype.forEach.call(tocItems, function (item) {
            if (!item) {
                return;
            }

            var link = item.querySelector('.jlg-settings-toc__link');
            var sectionId = link ? link.getAttribute('data-section-id') : '';
            var isAllowed = allowAll || allowedSet.has(sectionId);

            if (isAllowed) {
                item.removeAttribute('hidden');
            } else {
                item.setAttribute('hidden', 'hidden');
            }
        });

        modeButtons.forEach(function (button) {
            if (!button) {
                return;
            }

            var buttonMode = button.getAttribute('data-settings-mode');
            var isActive = buttonMode === mode;

            button.classList.toggle('button-primary', isActive);
            button.classList.toggle('button-secondary', !isActive);
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        currentMode = mode;

        if (typeof filterEvaluator === 'function') {
            filterEvaluator();
        }
    }

    function persistModePreference(mode) {
        if (!restSettings || !restSettings.url || !window.wp || !window.wp.apiFetch) {
            return Promise.resolve();
        }

        return window.wp.apiFetch({
            url: restSettings.url,
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': restSettings.nonce || ''
            },
            body: JSON.stringify({ mode: mode })
        }).catch(function (error) {
            console.warn('JLG Notation: unable to persist settings view mode', error);
            return null;
        });
    }

    function setupModeToggle(panels, activeMode, rest, modesMetadata) {
        var container = document.querySelector('[data-settings-modes]');
        panelsByMode = buildPanelMap(panels);
        restSettings = rest || {};

        if (!container) {
            return;
        }

        modeButtons = Array.prototype.slice.call(container.querySelectorAll('[data-settings-mode]'));

        if (!modeButtons.length) {
            container.setAttribute('hidden', 'hidden');
            return;
        }

        modeButtons.forEach(function (button) {
            var mode = button.getAttribute('data-settings-mode');
            var metadata = modesMetadata && modesMetadata[mode] ? modesMetadata[mode] : null;

            if (metadata && metadata.description) {
                button.setAttribute('title', metadata.description);
            }

            button.addEventListener('click', function () {
                if (!mode || mode === currentMode) {
                    return;
                }

                applyMode(mode);
                persistModePreference(mode);
            });
        });

        var fallbackMode = Object.keys(panelsByMode)[0] || 'expert';
        var initialMode = panelsByMode[activeMode] ? activeMode : fallbackMode;

        applyMode(initialMode);
    }

    function setupPreview(previewData, i18n) {
        var previewRoot = document.querySelector('[data-theme-preview]');
        if (!previewRoot) {
            return;
        }

        var surface = previewRoot.querySelector('[data-preview-surface]');
        if (!surface) {
            return;
        }

        var badge = surface.querySelector('[data-preview-theme-indicator]');
        var contrastNode = surface.querySelector('[data-preview-contrast]');
        var switches = Array.prototype.slice.call(previewRoot.querySelectorAll('.jlg-theme-preview__switch'));

        var state = Object.assign({}, previewData);
        var initialTheme = state.visual_theme === 'light' ? 'light' : 'dark';
        var activeTheme = initialTheme;

        var watchedFields = [
            'visual_theme',
            'visual_preset',
            'dark_bg_color',
            'dark_bg_color_secondary',
            'dark_text_color',
            'dark_border_color',
            'light_bg_color',
            'light_bg_color_secondary',
            'light_text_color',
            'light_border_color',
            'score_gradient_1',
            'score_gradient_2',
            'color_low',
            'color_mid',
            'color_high',
            'text_glow_enabled',
            'text_glow_color_mode',
            'text_glow_custom_color',
            'circle_glow_enabled',
            'circle_glow_color_mode',
            'circle_glow_custom_color'
        ];

        function readField(id) {
            var field = document.getElementById(id);
            if (!field) {
                return state[id];
            }

            if (field.type === 'checkbox') {
                return field.checked ? '1' : '0';
            }

            return field.value;
        }

        function refreshState() {
            watchedFields.forEach(function (fieldId) {
                state[fieldId] = readField(fieldId);
            });
        }

        function getThemeConfig(theme) {
            return {
                bg: state[theme + '_bg_color'] || '',
                bgSecondary: state[theme + '_bg_color_secondary'] || '',
                text: state[theme + '_text_color'] || '',
                border: state[theme + '_border_color'] || ''
            };
        }

        function resolveNeonColor(modeKey, customKey) {
            var mode = (state[modeKey] || '').toString();
            if (mode === 'custom' && state[customKey]) {
                return state[customKey];
            }

            return state.color_high || state.score_gradient_2 || '#22c55e';
        }

        function parseHexColor(hex) {
            if (typeof hex !== 'string') {
                return null;
            }

            var normalized = hex.trim().replace('#', '');
            if (normalized.length === 3) {
                normalized = normalized.split('').map(function (char) {
                    return char + char;
                }).join('');
            }

            if (normalized.length !== 6) {
                return null;
            }

            var r = parseInt(normalized.substr(0, 2), 16);
            var g = parseInt(normalized.substr(2, 2), 16);
            var b = parseInt(normalized.substr(4, 2), 16);

            if (Number.isNaN(r) || Number.isNaN(g) || Number.isNaN(b)) {
                return null;
            }

            return { r: r, g: g, b: b };
        }

        function luminance(component) {
            var value = component / 255;
            return value <= 0.03928 ? value / 12.92 : Math.pow((value + 0.055) / 1.055, 2.4);
        }

        function computeContrastRatio(bgColor, textColor) {
            var bg = parseHexColor(bgColor);
            var text = parseHexColor(textColor);

            if (!bg || !text) {
                return null;
            }

            var bgL = 0.2126 * luminance(bg.r) + 0.7152 * luminance(bg.g) + 0.0722 * luminance(bg.b);
            var textL = 0.2126 * luminance(text.r) + 0.7152 * luminance(text.g) + 0.0722 * luminance(text.b);

            var lighter = Math.max(bgL, textL);
            var darker = Math.min(bgL, textL);

            if (darker === 0 && lighter === 0) {
                return null;
            }

            return (lighter + 0.05) / (darker + 0.05);
        }

        function getContrastMessage(ratio) {
            var ratioText = ratio ? ratio.toFixed(2) + ':1' : '';
            if (!ratio) {
                return { text: '', level: 'unknown' };
            }

            if (ratio >= 7) {
                return {
                    text: ((i18n && i18n.contrastExcellent) || 'Contraste AAA') + ' (' + ratioText + ')',
                    level: 'pass',
                };
            }

            if (ratio >= 4.5) {
                return {
                    text: ((i18n && i18n.contrastGood) || 'Contraste AA') + ' (' + ratioText + ')',
                    level: 'pass',
                };
            }

            if (ratio >= 3) {
                return {
                    text: ((i18n && i18n.contrastWarning) || 'Contraste limite pour les grands textes') + ' (' + ratioText + ')',
                    level: 'warn',
                };
            }

            return {
                text: ((i18n && i18n.contrastFail) || 'Contraste insuffisant pour les textes courants') + ' (' + ratioText + ')',
                level: 'fail',
            };
        }

        function updateContrast() {
            if (!contrastNode) {
                return;
            }

            var themeConfig = getThemeConfig(activeTheme);
            var bgColor = themeConfig.bg || (activeTheme === 'light' ? '#ffffff' : '#0f172a');
            var textColor = themeConfig.text || (activeTheme === 'light' ? '#111827' : '#f8fafc');

            var ratio = computeContrastRatio(bgColor, textColor);
            var message = getContrastMessage(ratio);

            contrastNode.textContent = message.text;
            contrastNode.setAttribute('data-contrast-level', message.level);
        }

        function applyTheme() {
            refreshState();

            var themeConfig = getThemeConfig(activeTheme);
            if (themeConfig.bg) {
                surface.style.setProperty('--jlg-preview-bg', themeConfig.bg);
            }
            if (themeConfig.bgSecondary) {
                surface.style.setProperty('--jlg-preview-bg-secondary', themeConfig.bgSecondary);
            }
            if (themeConfig.text) {
                surface.style.setProperty('--jlg-preview-text', themeConfig.text);
            }
            if (themeConfig.border) {
                surface.style.setProperty('--jlg-preview-border', themeConfig.border);
            }

            if (state.score_gradient_1) {
                surface.style.setProperty('--jlg-preview-gradient-start', state.score_gradient_1);
            }
            if (state.score_gradient_2) {
                surface.style.setProperty('--jlg-preview-gradient-end', state.score_gradient_2);
            }
            if (state.color_low) {
                surface.style.setProperty('--jlg-preview-score-low', state.color_low);
            }
            if (state.color_mid) {
                surface.style.setProperty('--jlg-preview-score-mid', state.color_mid);
            }
            if (state.color_high) {
                surface.style.setProperty('--jlg-preview-score-high', state.color_high);
            }

            var preset = (state.visual_preset || 'signature').toString();
            surface.setAttribute('data-preset', preset);

            var neonColor = resolveNeonColor('text_glow_color_mode', 'text_glow_custom_color');
            if (neonColor) {
                surface.style.setProperty('--jlg-preview-glow', neonColor);
            }

            var textGlowActive = state.text_glow_enabled === '1';
            var circleGlowActive = state.circle_glow_enabled === '1';

            surface.classList.toggle('no-text-glow', !textGlowActive);
            surface.classList.toggle('no-circle-glow', !circleGlowActive);

            if (badge) {
                var badgeLabel = activeTheme === 'light'
                    ? previewRoot.getAttribute('data-label-light')
                    : previewRoot.getAttribute('data-label-dark');
                if (badgeLabel) {
                    badge.textContent = badgeLabel;
                }
            }

            switches.forEach(function (button) {
                var theme = button.getAttribute('data-preview-theme');
                if (!theme) {
                    return;
                }

                if (theme === activeTheme) {
                    button.classList.add('is-active');
                } else {
                    button.classList.remove('is-active');
                }
            });

            updateContrast();
        }

        switches.forEach(function (button) {
            button.addEventListener('click', function () {
                var theme = button.getAttribute('data-preview-theme');
                if (!theme) {
                    return;
                }

                activeTheme = theme;
                applyTheme();
            });
        });

        watchedFields.forEach(function (fieldId) {
            var field = document.getElementById(fieldId);
            if (!field) {
                return;
            }

            var handler = function () {
                if (fieldId === 'visual_theme') {
                    var value = readField('visual_theme');
                    if (value === 'light' || value === 'dark') {
                        activeTheme = value;
                    }
                }

                applyTheme();
            };

            field.addEventListener('change', handler);
            field.addEventListener('input', handler);
        });

        applyTheme();
    }

    document.addEventListener('DOMContentLoaded', function () {
        var data = getAdminData();
        var defaultMessage = data.i18n && data.i18n.dependencyInactive
            ? data.i18n.dependencyInactive
            : "Activez l’option associée pour modifier ce réglage.";

        enhanceSectionsLayout();
        assignSectionAnchors(data.sections || []);
        setupTocInteraction();
        setupToolbarButtons();
        setupModeToggle(data.panels || {}, data.activeMode || '', data.rest || {}, data.modes || {});
        setupSearchFilter(data.i18n || {});
        if (currentMode) {
            applyMode(currentMode);
        }
        createDependencyManager(data.dependencies || [], defaultMessage);
        setupPreview(data.preview || {}, data.i18n || {});
    });
})();

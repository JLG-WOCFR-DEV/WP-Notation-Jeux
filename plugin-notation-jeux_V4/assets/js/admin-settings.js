(function () {
    'use strict';

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
            dependencies: Array.isArray(fromWindow.dependencies) ? fromWindow.dependencies : [],
            preview: preview,
            i18n: fromWindow.i18n || {}
        };
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

    function setupPreview(previewData) {
        var previewRoot = document.querySelector('[data-theme-preview]');
        if (!previewRoot) {
            return;
        }

        var surface = previewRoot.querySelector('[data-preview-surface]');
        if (!surface) {
            return;
        }

        var badge = surface.querySelector('[data-preview-theme-indicator]');
        var switches = Array.prototype.slice.call(previewRoot.querySelectorAll('.jlg-theme-preview__switch'));

        var state = Object.assign({}, previewData);
        var initialTheme = state.visual_theme === 'light' ? 'light' : 'dark';
        var activeTheme = initialTheme;

        var watchedFields = [
            'visual_theme',
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

        assignSectionAnchors(data.sections || []);
        setupTocInteraction();
        createDependencyManager(data.dependencies || [], defaultMessage);
        setupPreview(data.preview || {});
    });
})();

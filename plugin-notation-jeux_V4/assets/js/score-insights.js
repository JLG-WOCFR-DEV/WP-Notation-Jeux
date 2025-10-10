(function () {
    var global = typeof window !== 'undefined' ? window : {};
    var announcer = global.jlgLiveAnnouncer;
    if (!announcer || typeof announcer.announce !== 'function') {
        return;
    }

    var l10n = global.jlgScoreInsightsL10n || {};
    var UPDATED_SINGULAR = typeof l10n.updatedSingular === 'string' && l10n.updatedSingular !== ''
        ? l10n.updatedSingular
        : 'Score Insights mis à jour — %d test analysé';
    var UPDATED_PLURAL = typeof l10n.updatedPlural === 'string' && l10n.updatedPlural !== ''
        ? l10n.updatedPlural
        : 'Score Insights mis à jour — %d tests analysés';
    var UPDATED_ZERO = typeof l10n.updatedZero === 'string' && l10n.updatedZero !== ''
        ? l10n.updatedZero
        : 'Score Insights mis à jour — aucun test disponible';

    function parseTotal(value) {
        if (value === null || typeof value === 'undefined') {
            return null;
        }

        var numeric = Number(value);
        if (!Number.isFinite(numeric) || numeric < 0) {
            return null;
        }

        return Math.max(0, Math.round(numeric));
    }

    function formatMessage(total) {
        if (total === null) {
            return '';
        }

        if (total === 0) {
            return UPDATED_ZERO;
        }

        if (total === 1) {
            return UPDATED_SINGULAR.replace('%d', '1');
        }

        return UPDATED_PLURAL.replace('%d', String(total));
    }

    function announceForElement(element, explicitTotal) {
        if (!element) {
            return;
        }

        var total = typeof explicitTotal === 'number'
            ? explicitTotal
            : parseTotal(element.getAttribute('data-total-reviews'));

        if (total === null) {
            return;
        }

        var message = formatMessage(total);
        if (message === '') {
            return;
        }

        var last = element.getAttribute('data-last-announced-total');
        if (last !== null && parseInt(last, 10) === total) {
            return;
        }

        element.setAttribute('data-last-announced-total', String(total));
        announcer.announce(message, { context: 'score-insights', id: element.id || null });
    }

    function observeElement(element) {
        if (!global.MutationObserver || !element) {
            return;
        }

        var observer = new MutationObserver(function (mutations) {
            for (var i = 0; i < mutations.length; i++) {
                var mutation = mutations[i];
                if (mutation.type === 'attributes' && mutation.attributeName === 'data-total-reviews') {
                    announceForElement(element);
                    break;
                }
            }
        });

        observer.observe(element, { attributes: true, attributeFilter: ['data-total-reviews'] });
        element.__jlgScoreInsightsObserver = observer;
    }

    function init() {
        var sections = global.document ? global.document.querySelectorAll('.jlg-score-insights[data-total-reviews]') : [];
        if (!sections || !sections.length) {
            return;
        }

        for (var i = 0; i < sections.length; i++) {
            var section = sections[i];
            announceForElement(section);
            observeElement(section);
        }
    }

    if (global.document && typeof global.document.addEventListener === 'function') {
        global.document.addEventListener('DOMContentLoaded', init);
    }

    if (typeof global.addEventListener === 'function') {
        global.addEventListener('jlg:score-insights:updated', function (event) {
            if (!event || !event.detail) {
                return;
            }

            var detail = event.detail;
            var element = detail.element || null;
            var total = typeof detail.total === 'number' ? detail.total : null;

            if (element) {
                if (typeof total === 'number') {
                    element.setAttribute('data-total-reviews', String(total));
                }
                announceForElement(element, total);
                return;
            }

            if (typeof total === 'number' && global.document) {
                var candidates = global.document.querySelectorAll('.jlg-score-insights');
                for (var i = 0; i < candidates.length; i++) {
                    candidates[i].setAttribute('data-total-reviews', String(total));
                    announceForElement(candidates[i], total);
                }
            }
        });
    }
})();

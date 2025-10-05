(function (root, factory) {
    if (typeof module === 'object' && module.exports) {
        module.exports = factory();
    } else {
        var utils = factory();
        root.jlgBlocks = root.jlgBlocks || {};
        root.jlgBlocks.ratingMetaUtils = utils;
    }
})(typeof self !== 'undefined' ? self : this, function () {
    var BADGE_VALUES = ['auto', 'force-on', 'force-off'];

    function normalizeScore(value, scoreMax) {
        if (value === undefined || value === null) {
            return null;
        }

        var candidate = value;
        if (typeof candidate === 'string') {
            candidate = candidate.trim();
            if (candidate === '') {
                return null;
            }
            if (candidate.indexOf(',') !== -1 && candidate.indexOf('.') === -1) {
                candidate = candidate.replace(',', '.');
            }
        }

        if (candidate === '' || candidate === null) {
            return null;
        }

        if (typeof candidate === 'string') {
            candidate = Number(candidate);
        }

        if (typeof candidate !== 'number' || !isFinite(candidate)) {
            return null;
        }

        var rounded = Math.round(candidate * 10) / 10;
        if (rounded < 0) {
            rounded = 0;
        }

        var max = typeof scoreMax === 'number' && isFinite(scoreMax) ? scoreMax : null;
        if (max !== null && max > 0 && rounded > max) {
            rounded = max;
        }

        return rounded;
    }

    function normalizeBadgeOverride(value) {
        if (typeof value !== 'string') {
            return 'auto';
        }

        var normalized = value.trim().toLowerCase();
        if (BADGE_VALUES.indexOf(normalized) === -1) {
            return 'auto';
        }

        return normalized;
    }

    function shallowEqual(a, b) {
        if (a === b) {
            return true;
        }

        if (!a || !b) {
            return !a && !b;
        }

        var keysA = Object.keys(a);
        var keysB = Object.keys(b);

        if (keysA.length !== keysB.length) {
            return false;
        }

        for (var i = 0; i < keysA.length; i += 1) {
            var key = keysA[i];
            if (!Object.prototype.hasOwnProperty.call(b, key)) {
                return false;
            }
            if (a[key] !== b[key]) {
                return false;
            }
        }

        return true;
    }

    function buildPreviewMeta(meta, categoryDefinitions, badgeMetaKey, scoreMax) {
        var result = {};
        var source = meta && typeof meta === 'object' ? meta : {};
        var definitions = Array.isArray(categoryDefinitions) ? categoryDefinitions : [];
        var max = typeof scoreMax === 'number' && isFinite(scoreMax) ? scoreMax : null;

        for (var i = 0; i < definitions.length; i += 1) {
            var definition = definitions[i];
            if (!definition || typeof definition !== 'object') {
                continue;
            }

            var metaKey = definition.metaKey || definition.meta_key || '';
            if (!metaKey || typeof metaKey !== 'string') {
                continue;
            }

            var value = source[metaKey];
            var normalizedScore = normalizeScore(value, max);
            if (normalizedScore !== null) {
                result[metaKey] = normalizedScore;
            }
        }

        if (typeof badgeMetaKey === 'string' && badgeMetaKey) {
            var badgeValue = source[badgeMetaKey];
            if (badgeValue !== undefined) {
                result[badgeMetaKey] = normalizeBadgeOverride(badgeValue);
            }
        }

        return result;
    }

    function updatePreviewMeta(current, key, value) {
        var next = {};
        if (current && typeof current === 'object') {
            for (var k in current) {
                if (Object.prototype.hasOwnProperty.call(current, k)) {
                    next[k] = current[k];
                }
            }
        }

        if (value === null || value === undefined || value === '') {
            if (key && Object.prototype.hasOwnProperty.call(next, key)) {
                delete next[key];
            }
            return next;
        }

        if (key) {
            next[key] = value;
        }

        return next;
    }

    return {
        normalizeScore: normalizeScore,
        normalizeBadgeOverride: normalizeBadgeOverride,
        shallowEqual: shallowEqual,
        buildPreviewMeta: buildPreviewMeta,
        updatePreviewMeta: updatePreviewMeta,
    };
});

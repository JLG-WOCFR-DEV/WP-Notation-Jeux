const test = require('node:test');
const assert = require('node:assert/strict');

const utils = require('../../assets/js/blocks/rating-meta-utils.js');

test('normalizeScore clamps and rounds values', () => {
    assert.equal(utils.normalizeScore('9,44', 10), 9.4);
    assert.equal(utils.normalizeScore('11.0', 10), 10);
    assert.equal(utils.normalizeScore(-1, 10), 0);
    assert.equal(utils.normalizeScore('', 10), null);
    assert.equal(utils.normalizeScore('not-a-number', 10), null);
});

test('normalizeBadgeOverride validates known values', () => {
    assert.equal(utils.normalizeBadgeOverride('force-on'), 'force-on');
    assert.equal(utils.normalizeBadgeOverride('FORCE-OFF'), 'force-off');
    assert.equal(utils.normalizeBadgeOverride('unknown'), 'auto');
});

test('buildPreviewMeta keeps only allowed keys and sanitizes', () => {
    const meta = {
        '_note_gameplay': '9.8',
        '_note_graphismes': 7.234,
        '_unknown': 12,
        '_jlg_rating_badge_override': 'force-off',
    };

    const definitions = [
        { metaKey: '_note_gameplay' },
        { metaKey: '_note_graphismes' },
    ];

    const result = utils.buildPreviewMeta(meta, definitions, '_jlg_rating_badge_override', 10);

    assert.deepEqual(result, {
        '_note_gameplay': 9.8,
        '_note_graphismes': 7.2,
        '_jlg_rating_badge_override': 'force-off',
    });
});

test('updatePreviewMeta merges values and removes when nullish', () => {
    const current = {
        '_note_gameplay': 8.5,
        '_jlg_rating_badge_override': 'force-on',
    };

    const updated = utils.updatePreviewMeta(current, '_note_graphismes', 7.5);
    assert.deepEqual(updated, {
        '_note_gameplay': 8.5,
        '_jlg_rating_badge_override': 'force-on',
        '_note_graphismes': 7.5,
    });

    const cleared = utils.updatePreviewMeta(updated, '_note_gameplay', null);
    assert.deepEqual(cleared, {
        '_jlg_rating_badge_override': 'force-on',
        '_note_graphismes': 7.5,
    });
});

test('shallowEqual detects simple equality', () => {
    assert.equal(utils.shallowEqual({ a: 1, b: 2 }, { a: 1, b: 2 }), true);
    assert.equal(utils.shallowEqual({ a: 1 }, { a: 2 }), false);
    assert.equal(utils.shallowEqual(null, null), true);
    assert.equal(utils.shallowEqual({ a: 1 }, null), false);
});

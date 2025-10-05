(function (wp, blocksHelpers) {
    if (!wp || !blocksHelpers || !blocksHelpers.BlockPreview) {
        return;
    }

    var registerBlockType = wp.blocks && wp.blocks.registerBlockType ? wp.blocks.registerBlockType : null;
    if (!registerBlockType) {
        return;
    }

    var __ = wp.i18n.__;
    var sprintf = wp.i18n.sprintf || function () {
        var args = Array.prototype.slice.call(arguments);
        if (!args.length) {
            return '';
        }

        var template = args.shift();
        return template.replace(/%s/g, function () {
            return args.shift();
        });
    };
    var blockEditor = wp.blockEditor || wp.editor || {};
    var InspectorControls = blockEditor.InspectorControls || function (props) {
        return wp.element.createElement(wp.element.Fragment, null, props.children);
    };
    var PanelBody = wp.components.PanelBody;
    var ToggleControl = wp.components.ToggleControl;
    var SelectControl = wp.components.SelectControl;
    var RangeControl = wp.components.RangeControl || null;
    var TextControl = wp.components.TextControl || null;
    var Notice = wp.components.Notice || null;
    var ColorPalette = (blockEditor && blockEditor.ColorPalette) || wp.components.ColorPalette;
    var PanelColorSettings = blockEditor.PanelColorSettings || blockEditor.__experimentalPanelColorSettings;
    var useBlockPropsHook = blockEditor.useBlockProps;
    var createElement = wp.element.createElement;
    var Fragment = wp.element.Fragment;
    var useEffect = wp.element.useEffect;
    var useMemo = wp.element.useMemo;
    var useSelect = wp.data && typeof wp.data.useSelect === 'function' ? wp.data.useSelect : null;
    var useDispatch = wp.data && typeof wp.data.useDispatch === 'function' ? wp.data.useDispatch : null;
    var PostPicker = blocksHelpers.PostPicker;
    var BlockPreview = blocksHelpers.BlockPreview;
    var ratingMetaUtils = blocksHelpers.ratingMetaUtils;

    if (!ratingMetaUtils && typeof window !== 'undefined' && window.jlgBlocks && window.jlgBlocks.ratingMetaUtils) {
        ratingMetaUtils = window.jlgBlocks.ratingMetaUtils;
    }

    var useBlockProps = typeof useBlockPropsHook === 'function'
        ? useBlockPropsHook
        : function (extraProps) {
              var props = extraProps || {};
              if (!props.className) {
                  props.className = 'notation-jlg-block';
              }
              return props;
          };

    function createAccentColorControl(attributes, setAttributes) {
        var colorValue = (attributes && attributes.accentColor) || '';

        if (PanelColorSettings) {
            return createElement(PanelColorSettings, {
                title: __('Couleurs', 'notation-jlg'),
                colorSettings: [
                    {
                        value: colorValue,
                        onChange: function (value) {
                            setAttributes({ accentColor: value || '' });
                        },
                        label: __('Couleur d\'accent', 'notation-jlg'),
                    },
                ],
            });
        }

        return createElement(
            PanelBody,
            { title: __('Couleur d\'accent', 'notation-jlg'), initialOpen: false },
            ColorPalette
                ? createElement(ColorPalette, {
                      value: colorValue,
                      onChange: function (value) {
                          setAttributes({ accentColor: value || '' });
                      },
                  })
                : null
        );
    }

    function normalizeScore(value, scoreMax) {
        if (ratingMetaUtils && typeof ratingMetaUtils.normalizeScore === 'function') {
            return ratingMetaUtils.normalizeScore(value, scoreMax);
        }

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
        if (ratingMetaUtils && typeof ratingMetaUtils.normalizeBadgeOverride === 'function') {
            return ratingMetaUtils.normalizeBadgeOverride(value);
        }

        if (typeof value !== 'string') {
            return 'auto';
        }

        var normalized = value.trim().toLowerCase();
        if (['auto', 'force-on', 'force-off'].indexOf(normalized) === -1) {
            return 'auto';
        }

        return normalized;
    }

    function shallowEqual(a, b) {
        if (ratingMetaUtils && typeof ratingMetaUtils.shallowEqual === 'function') {
            return ratingMetaUtils.shallowEqual(a, b);
        }

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
        if (ratingMetaUtils && typeof ratingMetaUtils.buildPreviewMeta === 'function') {
            return ratingMetaUtils.buildPreviewMeta(meta, categoryDefinitions, badgeMetaKey, scoreMax);
        }

        var source = meta && typeof meta === 'object' ? meta : {};
        var definitions = Array.isArray(categoryDefinitions) ? categoryDefinitions : [];
        var result = {};

        for (var i = 0; i < definitions.length; i += 1) {
            var definition = definitions[i];
            if (!definition || typeof definition !== 'object') {
                continue;
            }

            var metaKey = definition.metaKey || definition.meta_key || '';
            if (!metaKey || typeof metaKey !== 'string') {
                continue;
            }

            var normalizedScore = normalizeScore(source[metaKey], scoreMax);
            if (normalizedScore !== null) {
                result[metaKey] = normalizedScore;
            }
        }

        if (typeof badgeMetaKey === 'string' && badgeMetaKey && Object.prototype.hasOwnProperty.call(source, badgeMetaKey)) {
            result[badgeMetaKey] = normalizeBadgeOverride(source[badgeMetaKey]);
        }

        return result;
    }

    function updatePreviewMeta(current, key, value) {
        if (ratingMetaUtils && typeof ratingMetaUtils.updatePreviewMeta === 'function') {
            return ratingMetaUtils.updatePreviewMeta(current, key, value);
        }

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

    registerBlockType('notation-jlg/rating-block', {
        edit: function (props) {
            var attributes = props.attributes || {};
            var setAttributes = typeof props.setAttributes === 'function' ? props.setAttributes : function () {};
            var blockProps = useBlockProps({ className: 'notation-jlg-rating-block-editor' });
            var colorControl = createAccentColorControl(attributes, setAttributes);

            var ratingSettings = window.jlgRatingBlockSettings || {};
            var categoryDefinitions = Array.isArray(ratingSettings.categoryDefinitions)
                ? ratingSettings.categoryDefinitions
                : [];
            var badgeMetaKey = typeof ratingSettings.badgeOverrideMetaKey === 'string'
                ? ratingSettings.badgeOverrideMetaKey
                : '_jlg_rating_badge_override';
            var badgeOptions = Array.isArray(ratingSettings.badgeOptions) && ratingSettings.badgeOptions.length
                ? ratingSettings.badgeOptions
                : [
                      { value: 'auto', label: __('Automatique (seuil global)', 'notation-jlg') },
                      { value: 'force-on', label: __('Toujours afficher', 'notation-jlg') },
                      { value: 'force-off', label: __('Toujours masquer', 'notation-jlg') },
                  ];
            var scoreMaxSetting = typeof ratingSettings.scoreMax === 'number' && isFinite(ratingSettings.scoreMax)
                ? ratingSettings.scoreMax
                : 10;
            var scoreMax = scoreMaxSetting > 0 ? scoreMaxSetting : 10;

            var metaState = useSelect
                ? useSelect(
                      function (select) {
                          var editor = select('core/editor');
                          if (!editor || typeof editor.getEditedPostAttribute !== 'function') {
                              return {};
                          }

                          var postMeta = editor.getEditedPostAttribute('meta');
                          return postMeta && typeof postMeta === 'object' ? postMeta : {};
                      },
                      []
                  )
                : {};

            var currentPostId = useSelect
                ? useSelect(
                      function (select) {
                          var editor = select('core/editor');
                          if (!editor || typeof editor.getCurrentPostId !== 'function') {
                              return 0;
                          }

                          return editor.getCurrentPostId() || 0;
                      },
                      []
                  )
                : 0;

            var dispatch = useDispatch ? useDispatch('core/editor') : null;
            var editPost = dispatch && typeof dispatch.editPost === 'function' ? dispatch.editPost : null;

            var storePreviewMeta = useMemo(
                function () {
                    return buildPreviewMeta(metaState, categoryDefinitions, badgeMetaKey, scoreMax);
                },
                [metaState, categoryDefinitions, badgeMetaKey, scoreMax]
            );

            var storePreviewMetaWithDefaults = useMemo(
                function () {
                    var next = {};
                    if (storePreviewMeta && typeof storePreviewMeta === 'object') {
                        Object.keys(storePreviewMeta).forEach(function (key) {
                            next[key] = storePreviewMeta[key];
                        });
                    }

                    if (badgeMetaKey && !Object.prototype.hasOwnProperty.call(next, badgeMetaKey)) {
                        next[badgeMetaKey] = 'auto';
                    }

                    return next;
                },
                [storePreviewMeta, badgeMetaKey]
            );

            var currentPreviewMeta = attributes.previewMeta && typeof attributes.previewMeta === 'object'
                ? attributes.previewMeta
                : {};

            useEffect(
                function () {
                    if (!shallowEqual(currentPreviewMeta, storePreviewMetaWithDefaults)) {
                        setAttributes({ previewMeta: storePreviewMetaWithDefaults });
                    }
                },
                [currentPreviewMeta, storePreviewMetaWithDefaults]
            );

            var previewMeta = shallowEqual(currentPreviewMeta, storePreviewMetaWithDefaults)
                ? currentPreviewMeta
                : storePreviewMetaWithDefaults;

            var selectedPostId = attributes.postId || 0;
            var isForeignPost = selectedPostId && currentPostId && selectedPostId !== currentPostId;
            var controlsDisabled = isForeignPost || typeof editPost !== 'function';

            var sliderMax = scoreMax > 0 ? scoreMax : 10;

            var categoryControls = categoryDefinitions
                .map(function (definition) {
                    if (!definition || typeof definition !== 'object') {
                        return null;
                    }

                    var metaKey = definition.metaKey || definition.meta_key || '';
                    if (!metaKey) {
                        return null;
                    }

                    var label = definition.label || metaKey;
                    var rawValue = Object.prototype.hasOwnProperty.call(previewMeta, metaKey)
                        ? previewMeta[metaKey]
                        : null;
                    var numericValue = typeof rawValue === 'number' ? rawValue : null;

                    var handleChange = function (nextValue) {
                        var normalized = normalizeScore(nextValue, sliderMax);
                        var metaUpdate = {};
                        metaUpdate[metaKey] = normalized === null ? null : normalized;

                        if (typeof editPost === 'function') {
                            editPost({ meta: metaUpdate });
                        }

                        setAttributes({
                            previewMeta: updatePreviewMeta(previewMeta, metaKey, normalized === null ? null : normalized),
                        });
                    };

                    if (RangeControl) {
                        return createElement(RangeControl, {
                            key: metaKey,
                            label: label,
                            value: numericValue,
                            min: 0,
                            max: sliderMax,
                            step: 0.1,
                            allowReset: true,
                            withInputField: true,
                            disabled: controlsDisabled,
                            help: sliderMax ? sprintf(__('Note sur %s', 'notation-jlg'), sliderMax) : undefined,
                            onChange: function (nextValue) {
                                handleChange(nextValue);
                            },
                        });
                    }

                    if (TextControl) {
                        return createElement(TextControl, {
                            key: metaKey,
                            type: 'number',
                            label: label,
                            value: numericValue === null ? '' : String(numericValue),
                            min: 0,
                            max: sliderMax,
                            step: 0.1,
                            disabled: controlsDisabled,
                            help: sliderMax ? sprintf(__('Note sur %s', 'notation-jlg'), sliderMax) : undefined,
                            onChange: function (nextValue) {
                                var parsed = nextValue === '' ? null : Number(nextValue);
                                if (Number.isNaN(parsed)) {
                                    parsed = null;
                                }
                                handleChange(parsed);
                            },
                        });
                    }

                    return null;
                })
                .filter(function (control) {
                    return control !== null;
                });

            var badgeValue = typeof previewMeta[badgeMetaKey] === 'string'
                ? previewMeta[badgeMetaKey]
                : 'auto';

            var badgeControl = badgeMetaKey
                ? createElement(SelectControl, {
                      key: badgeMetaKey,
                      label: __('Badge « Coup de cœur »', 'notation-jlg'),
                      value: badgeValue,
                      options: badgeOptions,
                      disabled: controlsDisabled,
                      help: __('Forcer l\'affichage ou le masquage du badge pour ce test.', 'notation-jlg'),
                      onChange: function (nextValue) {
                          var normalized = normalizeBadgeOverride(nextValue);

                          if (typeof editPost === 'function') {
                              var metaUpdate = {};
                              metaUpdate[badgeMetaKey] = normalized;
                              editPost({ meta: metaUpdate });
                          }

                          setAttributes({
                              previewMeta: updatePreviewMeta(previewMeta, badgeMetaKey, normalized),
                          });
                      },
                  })
                : null;

            var ratingPanelChildren = [];

            if (isForeignPost && Notice) {
                ratingPanelChildren.push(
                    createElement(
                        Notice,
                        { status: 'warning', isDismissible: false, key: 'jlg-rating-notice' },
                        __('Les notes ne peuvent être modifiées que pour l\'article en cours.', 'notation-jlg')
                    )
                );
            }

            if (categoryControls.length) {
                ratingPanelChildren = ratingPanelChildren.concat(categoryControls);
            } else {
                ratingPanelChildren.push(
                    createElement(
                        'p',
                        { className: 'jlg-rating-no-categories', key: 'no-categories' },
                        __('Aucune catégorie de notation n\'est disponible.', 'notation-jlg')
                    )
                );
            }

            if (badgeControl) {
                ratingPanelChildren.push(badgeControl);
            }

            return createElement(
                Fragment,
                null,
                createElement(
                    InspectorControls,
                    null,
                    createElement(
                        PanelBody,
                        { title: __('Source et affichage', 'notation-jlg'), initialOpen: true },
                        createElement(PostPicker, {
                            value: attributes.postId || 0,
                            onChange: function (value) {
                                setAttributes({ postId: value || 0 });
                            },
                        }),
                        createElement(SelectControl, {
                            label: __('Disposition du score', 'notation-jlg'),
                            value: attributes.scoreLayout || 'text',
                            options: [
                                { value: 'text', label: __('Texte', 'notation-jlg') },
                                { value: 'circle', label: __('Cercle', 'notation-jlg') },
                            ],
                            onChange: function (value) {
                                setAttributes({ scoreLayout: value || 'text' });
                            },
                        }),
                        createElement(SelectControl, {
                            label: __('Affichage du score', 'notation-jlg'),
                            value: attributes.scoreDisplay || 'absolute',
                            options: [
                                { value: 'absolute', label: __('Valeur absolue', 'notation-jlg') },
                                { value: 'percent', label: __('Pourcentage', 'notation-jlg') },
                            ],
                            onChange: function (value) {
                                setAttributes({ scoreDisplay: value || 'absolute' });
                            },
                        }),
                        createElement(ToggleControl, {
                            label: __('Afficher les animations', 'notation-jlg'),
                            checked: typeof attributes.showAnimations === 'boolean' ? attributes.showAnimations : true,
                            onChange: function (value) {
                                setAttributes({ showAnimations: !!value });
                            },
                        })
                    ),
                    createElement(
                        PanelBody,
                        { title: __('Notes et badge', 'notation-jlg'), initialOpen: false },
                        ratingPanelChildren
                    ),
                    colorControl
                ),
                createElement(
                    'div',
                    blockProps,
                    createElement(BlockPreview, {
                        block: 'notation-jlg/rating-block',
                        attributes: {
                            postId: attributes.postId || 0,
                            scoreLayout: attributes.scoreLayout || 'text',
                            scoreDisplay: attributes.scoreDisplay || 'absolute',
                            showAnimations: typeof attributes.showAnimations === 'boolean' ? attributes.showAnimations : true,
                            accentColor: attributes.accentColor || '',
                            previewMeta: previewMeta,
                        },
                        label: __('Bloc de notation', 'notation-jlg'),
                    })
                )
            );
        },
        save: function () {
            return null;
        },
    });
})(window.wp, window.jlgBlocks || {});

(function (wp) {
    if (!wp || !wp.element || !wp.components || !wp.data || !wp.i18n) {
        return;
    }

    var createElement = wp.element.createElement;
    var Fragment = wp.element.Fragment;
    var useState = wp.element.useState;
    var useEffect = wp.element.useEffect;
    var useMemo = wp.element.useMemo;
    var useRef = wp.element.useRef;
    var useSelect = wp.data.useSelect;
    var __ = wp.i18n.__;
    var ComboboxControl = wp.components.ComboboxControl;
    var SelectControl = wp.components.SelectControl;
    var Placeholder = wp.components.Placeholder;
    var Spinner = wp.components.Spinner;
    var ButtonGroup = wp.components.ButtonGroup;
    var Button = wp.components.Button;
    var Tooltip = wp.components.Tooltip;
    var ServerSideRender = wp.serverSideRender;
    var decodeEntities = wp.htmlEntities && wp.htmlEntities.decodeEntities ? wp.htmlEntities.decodeEntities : function (value) {
        return value;
    };

    var settings = window.jlgBlockEditorSettings || {};
    var allowedTypes = Array.isArray(settings.allowedPostTypes) ? settings.allowedPostTypes : [];
    if (!allowedTypes.length) {
        allowedTypes = [
            {
                slug: 'post',
                label: __('Articles', 'notation-jlg'),
            },
        ];
    }

    var defaultPostType = allowedTypes[0] ? allowedTypes[0].slug : 'post';
    var defaultPerPage = settings.postsQueryPerPage || 20;

    var PostPicker = function PostPicker(props) {
        var value = props.value || 0;
        var onChange = typeof props.onChange === 'function' ? props.onChange : function () {};
        var label = props.label || __('Article cible', 'notation-jlg');
        var hasMultipleTypes = allowedTypes.length > 1;
        var _useState = useState(defaultPostType);
        var postType = _useState[0];
        var setPostType = _useState[1];
        var _useState2 = useState('');
        var search = _useState2[0];
        var setSearch = _useState2[1];

        useEffect(
            function () {
                if (!value) {
                    setSearch('');
                    return;
                }

                setSearch(String(value));
            },
            [value]
        );

        var currentSelection = useSelect(
            function (select) {
                if (!value) {
                    return { record: null, type: null };
                }

                var core = select('core');
                if (!core || !core.getEntityRecord) {
                    return { record: null, type: null };
                }

                var selectedType = null;
                var selectedRecord = null;

                allowedTypes.some(function (type) {
                    var record = core.getEntityRecord('postType', type.slug, value);
                    if (record) {
                        selectedType = type.slug;
                        selectedRecord = record;
                        return true;
                    }
                    return false;
                });

                return { record: selectedRecord, type: selectedType };
            },
            [value, allowedTypes]
        );

        useEffect(
            function () {
                if (!value) {
                    return;
                }

                if (currentSelection.type && currentSelection.type !== postType) {
                    setPostType(currentSelection.type);
                }
            },
            [value, currentSelection.type, postType]
        );

        var queryArgs = useMemo(function () {
            return {
                per_page: defaultPerPage,
                orderby: 'date',
                order: 'desc',
                status: 'publish',
                search: search,
            };
        }, [search]);

        var data = useSelect(
            function (select) {
                var core = select('core');
                var records = core && core.getEntityRecords ? core.getEntityRecords('postType', postType, queryArgs) : [];
                var isResolving = false;

                if (select('core/data') && select('core/data').isResolving) {
                    isResolving = select('core/data').isResolving('core', 'getEntityRecords', ['postType', postType, queryArgs]);
                }

                return {
                    records: records,
                    isResolving: isResolving,
                };
            },
            [postType, queryArgs]
        );

        var records = data.records || [];
        var isResolving = data.isResolving && (!records || !records.length);

        var options = useMemo(
            function () {
                if (!records || !records.length) {
                    return [];
                }

                return records.map(function (post) {
                    var labelText = post && post.title && post.title.rendered ? post.title.rendered : '';
                    if (!labelText && post && post.slug) {
                        labelText = post.slug;
                    }
                    if (!labelText && post && post.id) {
                        labelText = '#' + post.id;
                    }
                    return {
                        value: String(post.id),
                        label: decodeEntities(labelText),
                    };
                });
            },
            [records]
        );

        var resolvedOptions = useMemo(
            function () {
                if (!value) {
                    return options;
                }

                var stringValue = String(value);
                var hasSelected = options.some(function (option) {
                    return option.value === stringValue;
                });

                if (hasSelected) {
                    return options;
                }

                var selectedRecord = currentSelection.record;
                if (!selectedRecord) {
                    return options;
                }

                var labelText = selectedRecord && selectedRecord.title && selectedRecord.title.rendered ? selectedRecord.title.rendered : '';
                if (!labelText && selectedRecord && selectedRecord.slug) {
                    labelText = selectedRecord.slug;
                }
                if (!labelText && selectedRecord && selectedRecord.id) {
                    labelText = '#' + selectedRecord.id;
                }

                return options.concat([
                    {
                        value: stringValue,
                        label: decodeEntities(labelText),
                    },
                ]);
            },
            [options, value, currentSelection.record]
        );

        var helpText = '';
        if (isResolving) {
            helpText = __('Chargement…', 'notation-jlg');
        } else if (!resolvedOptions.length) {
            helpText = __('Aucun élément trouvé.', 'notation-jlg');
        }

        return createElement(
            'div',
            { className: 'notation-jlg-post-picker' },
            hasMultipleTypes &&
                createElement(SelectControl, {
                    label: __('Type de contenu', 'notation-jlg'),
                    value: postType,
                    options: allowedTypes.map(function (type) {
                        return {
                            value: type.slug,
                            label: type.label,
                        };
                    }),
                    onChange: function (nextType) {
                        setPostType(nextType);
                    },
                }),
            createElement(
                Fragment,
                null,
                createElement(ComboboxControl, {
                    label: label,
                    value: value ? String(value) : '',
                    options: resolvedOptions,
                    onFilterValueChange: function (nextSearch) {
                        setSearch(nextSearch || '');
                    },
                    onChange: function (newValue) {
                        var parsed = parseInt(newValue, 10);
                        if (!newValue || isNaN(parsed)) {
                            onChange(0);
                            return;
                        }
                        onChange(parsed);
                    },
                    allowReset: true,
                    help: helpText,
                }),
                isResolving && createElement(Spinner, null)
            )
        );
    };

    var ensureDynamicPreviewReady = function ensureDynamicPreviewReady(root) {
        if (!root || !root.querySelectorAll) {
            return;
        }

        var animatedElements = root.querySelectorAll('.jlg-animate:not(.is-in-view), .animate-in:not(.is-visible)');

        if (!animatedElements.length) {
            return;
        }

        animatedElements.forEach(function (element) {
            if (element.classList.contains('jlg-animate')) {
                element.classList.add('is-in-view');
            }
            if (element.classList.contains('animate-in')) {
                element.classList.add('is-visible');
            }
        });
    };

    var deviceViewports = [
        { id: 'desktop', label: __('Bureau', 'notation-jlg'), width: 1280, icon: 'desktop' },
        { id: 'tablet', label: __('Tablette', 'notation-jlg'), width: 960, icon: 'tablet' },
        { id: 'mobile', label: __('Mobile', 'notation-jlg'), width: 420, icon: 'smartphone' },
    ];

    var sendAnalyticsEvent = function sendAnalyticsEvent(eventName, payload) {
        if (!eventName || typeof window === 'undefined') {
            return;
        }

        var detail = {
            event: eventName,
            payload: payload || {},
            timestamp: Date.now(),
        };

        try {
            if (window.jlgAnalytics && typeof window.jlgAnalytics.track === 'function') {
                window.jlgAnalytics.track(eventName, detail.payload);
            }
        } catch (error) {
            // Silence tracking errors to avoid blocking the editor experience.
        }

        try {
            if (Array.isArray(window.dataLayer)) {
                window.dataLayer.push({
                    event: eventName,
                    payload: detail.payload,
                    timestamp: detail.timestamp,
                    source: 'notation-jlg-blocks',
                });
            }
        } catch (error) {
            // Ignore dataLayer issues silently.
        }

        if (typeof window.dispatchEvent === 'function' && typeof window.CustomEvent === 'function') {
            try {
                window.dispatchEvent(new window.CustomEvent('notationJLGAnalytics', { detail: detail }));
            } catch (error) {
                // Ignore CustomEvent incompatibilities.
            }
        }
    };

    var useAnalyticsAttributeSetter = function useAnalyticsAttributeSetter(blockName, attributes, setAttributes) {
        var latestAttributesRef = useRef(attributes);

        useEffect(
            function () {
                latestAttributesRef.current = attributes;
            },
            [attributes]
        );

        return useMemo(
            function () {
                if (typeof setAttributes !== 'function') {
                    return function () {};
                }

                return function (nextAttributes) {
                    var payload;

                    if (nextAttributes && typeof nextAttributes === 'object') {
                        var previousAttributes = latestAttributesRef.current || {};
                        var changes = [];

                        Object.keys(nextAttributes).forEach(function (key) {
                            var nextValue = nextAttributes[key];
                            var previousValue = previousAttributes ? previousAttributes[key] : undefined;

                            if (previousValue !== nextValue) {
                                changes.push({
                                    attribute: key,
                                    previous: previousValue,
                                    next: nextValue,
                                });
                            }
                        });

                        if (changes.length) {
                            payload = {
                                block: blockName,
                                changes: changes,
                            };
                            sendAnalyticsEvent('notation_jlg_block_attribute_update', payload);
                        }
                    }

                    setAttributes(nextAttributes);
                };
            },
            [blockName, setAttributes]
        );
    };

    var BlockPreview = function BlockPreview(props) {
        var blockName = props.block;
        var attributes = props.attributes || {};
        var label = props.label || __('Prévisualisation du bloc', 'notation-jlg');
        var containerRef = useRef(null);
        var _useState3 = useState('desktop');
        var device = _useState3[0];
        var setDevice = _useState3[1];

        var currentDevice = useMemo(
            function () {
                return deviceViewports.find(function (item) {
                    return item.id === device;
                }) || deviceViewports[0];
            },
            [device]
        );

        if (!ServerSideRender) {
            return createElement(
                Placeholder,
                { label: label },
                __('La prévisualisation n\'est pas disponible dans cet environnement.', 'notation-jlg')
            );
        }

        useEffect(
            function () {
                var node = containerRef.current;
                if (!node) {
                    return;
                }

                var rafId = window.requestAnimationFrame(function () {
                    ensureDynamicPreviewReady(node);
                });

                var observer = new MutationObserver(function () {
                    ensureDynamicPreviewReady(node);
                });

                observer.observe(node, { childList: true, subtree: true });

                return function () {
                    if (rafId) {
                        window.cancelAnimationFrame(rafId);
                    }
                    observer.disconnect();
                };
            },
            [blockName, JSON.stringify(attributes)]
        );

        var handleDeviceChange = useMemo(
            function () {
                return function (nextDevice) {
                    if (nextDevice === device) {
                        return;
                    }

                    setDevice(nextDevice);
                    sendAnalyticsEvent('notation_jlg_block_preview_device_change', {
                        block: blockName,
                        device: nextDevice,
                    });
                };
            },
            [device, blockName]
        );

        var ButtonsContainer = ButtonGroup
            ? ButtonGroup
            : function (props) {
                  return createElement(
                      'div',
                      {
                          className:
                              typeof props === 'object' && props && props.className
                                  ? props.className
                                  : 'notation-jlg-block-preview__toolbar-group',
                      },
                      props && props.children
                  );
              };

        var DeviceButton = Button
            ? Button
            : function (props) {
                  return createElement(
                      'button',
                      {
                          type: 'button',
                          className:
                              'notation-jlg-block-preview__button' + (props && props.isPressed ? ' is-active' : ''),
                          onClick: props && props.onClick ? props.onClick : function () {},
                          'aria-pressed': props && props['aria-pressed'],
                      },
                      props && props.label ? props.label : ''
                  );
              };

        return createElement(
            'div',
            {
                className: 'notation-jlg-block-preview',
                ref: containerRef,
                'data-device': currentDevice.id,
                style: {
                    '--jlg-preview-width': currentDevice && currentDevice.width ? currentDevice.width + 'px' : '100%',
                },
            },
            createElement(
                'div',
                { className: 'notation-jlg-block-preview__toolbar' },
                createElement(
                    ButtonsContainer,
                    null,
                    deviceViewports.map(function (viewport) {
                        var button = createElement(DeviceButton, {
                            key: viewport.id,
                            icon: viewport.icon,
                            isPressed: viewport.id === currentDevice.id,
                            onClick: function () {
                                handleDeviceChange(viewport.id);
                            },
                            label: viewport.label,
                            'aria-pressed': viewport.id === currentDevice.id,
                        });

                        return Tooltip
                            ? createElement(
                                  Tooltip,
                                  { text: viewport.label, position: 'top' },
                                  button
                              )
                            : button;
                    })
                )
            ),
            createElement(
                'div',
                { className: 'notation-jlg-block-preview__frame' },
                createElement(ServerSideRender, { block: blockName, attributes: attributes })
            )
        );
    };

    window.jlgBlocks = window.jlgBlocks || {};
    window.jlgBlocks.PostPicker = PostPicker;
    window.jlgBlocks.BlockPreview = BlockPreview;
    window.jlgBlocks.ensureDynamicPreviewReady = ensureDynamicPreviewReady;
    window.jlgBlocks.useAnalyticsAttributeSetter = useAnalyticsAttributeSetter;
    window.jlgBlocks.sendAnalyticsEvent = sendAnalyticsEvent;
})(window.wp);

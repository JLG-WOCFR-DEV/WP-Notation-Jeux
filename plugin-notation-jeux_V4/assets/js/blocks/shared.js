(function (wp) {
    if (!wp || !wp.element || !wp.components || !wp.data || !wp.i18n) {
        return;
    }

    var createElement = wp.element.createElement;
    var Fragment = wp.element.Fragment;
    var useState = wp.element.useState;
    var useMemo = wp.element.useMemo;
    var useEffect = wp.element.useEffect;
    var useRef = wp.element.useRef;
    var useSelect = wp.data.useSelect;
    var __ = wp.i18n.__;
    var ComboboxControl = wp.components.ComboboxControl;
    var SelectControl = wp.components.SelectControl;
    var Placeholder = wp.components.Placeholder;
    var Spinner = wp.components.Spinner;
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

        var helpText = '';
        if (isResolving) {
            helpText = __('Chargement…', 'notation-jlg');
        } else if (!options.length) {
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
                    options: options,
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

    var BlockPreview = function BlockPreview(props) {
        var blockName = props.block;
        var attributes = props.attributes || {};
        var label = props.label || __('Prévisualisation du bloc', 'notation-jlg');
        var containerRef = useRef(null);

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

        return createElement(
            'div',
            { className: 'notation-jlg-block-preview', ref: containerRef },
            createElement(ServerSideRender, { block: blockName, attributes: attributes })
        );
    };

    window.jlgBlocks = window.jlgBlocks || {};
    window.jlgBlocks.PostPicker = PostPicker;
    window.jlgBlocks.BlockPreview = BlockPreview;
    window.jlgBlocks.ensureDynamicPreviewReady = ensureDynamicPreviewReady;
})(window.wp);

(function (wp, blocksHelpers) {
    if (!wp || !blocksHelpers || !blocksHelpers.BlockPreview) {
        return;
    }

    var registerBlockType = wp.blocks && wp.blocks.registerBlockType ? wp.blocks.registerBlockType : null;
    if (!registerBlockType) {
        return;
    }

    var __ = wp.i18n.__;
    var blockEditor = wp.blockEditor || wp.editor || {};
    var InspectorControls = blockEditor.InspectorControls || function (props) {
        return wp.element.createElement(wp.element.Fragment, null, props.children);
    };
    var useBlockPropsHook = blockEditor.useBlockProps;
    var PanelBody = wp.components.PanelBody;
    var RangeControl = wp.components.RangeControl;
    var SelectControl = wp.components.SelectControl;
    var CheckboxControl = wp.components.CheckboxControl;
    var TextControl = wp.components.TextControl;
    var createElement = wp.element.createElement;
    var Fragment = wp.element.Fragment;
    var BlockPreview = blocksHelpers.BlockPreview;
    var useAnalyticsAttributeSetter = blocksHelpers.useAnalyticsAttributeSetter;

    var useBlockProps = typeof useBlockPropsHook === 'function'
        ? useBlockPropsHook
        : function (extraProps) {
              var props = extraProps || {};
              if (!props.className) {
                  props.className = 'notation-jlg-block';
              }
              return props;
          };

    var columnOptions = [
        { value: 'titre', label: __('Titre du jeu', 'notation-jlg') },
        { value: 'date', label: __('Date', 'notation-jlg') },
        { value: 'note', label: __('Note moyenne', 'notation-jlg') },
        { value: 'developpeur', label: __('Développeur', 'notation-jlg') },
        { value: 'editeur', label: __('Éditeur', 'notation-jlg') },
    ];

    registerBlockType('notation-jlg/summary-display', {
        edit: function (props) {
            var attributes = props.attributes || {};
            var baseSetAttributes = typeof props.setAttributes === 'function' ? props.setAttributes : function () {};
            var setAttributes = useAnalyticsAttributeSetter
                ? useAnalyticsAttributeSetter('notation-jlg/summary-display', attributes, baseSetAttributes)
                : baseSetAttributes;
            var columns = Array.isArray(attributes.columns) && attributes.columns.length
                ? attributes.columns
                : ['titre', 'date', 'note'];
            var blockProps = useBlockProps({ className: 'notation-jlg-summary-display-block' });

            var toggleColumn = function (value, isChecked) {
                var current = Array.isArray(attributes.columns) ? attributes.columns.slice() : columns.slice();
                var next;

                if (isChecked) {
                    if (current.indexOf(value) === -1) {
                        current.push(value);
                    }
                    next = current;
                } else {
                    next = current.filter(function (item) {
                        return item !== value;
                    });
                    if (!next.length) {
                        return;
                    }
                }

                setAttributes({ columns: next });
            };

            return createElement(
                Fragment,
                null,
                createElement(
                    InspectorControls,
                    null,
                    createElement(
                        PanelBody,
                        { title: __('Pagination et mise en page', 'notation-jlg'), initialOpen: true },
                        createElement(RangeControl, {
                            label: __('Nombre d\'éléments par page', 'notation-jlg'),
                            value: attributes.postsPerPage || 12,
                            min: 1,
                            max: 50,
                            onChange: function (value) {
                                var parsed = parseInt(value, 10);
                                if (isNaN(parsed) || parsed < 1) {
                                    parsed = 12;
                                }
                                setAttributes({ postsPerPage: parsed });
                            },
                        }),
                        createElement(SelectControl, {
                            label: __('Disposition', 'notation-jlg'),
                            value: attributes.layout || 'table',
                            options: [
                                { value: 'table', label: __('Tableau', 'notation-jlg') },
                                { value: 'grid', label: __('Grille', 'notation-jlg') },
                            ],
                            onChange: function (value) {
                                setAttributes({ layout: value });
                            },
                        })
                    ),
                    createElement(
                        PanelBody,
                        { title: __('Colonnes affichées', 'notation-jlg'), initialOpen: false },
                        columnOptions.map(function (option) {
                            var checked = columns.indexOf(option.value) !== -1;
                            return createElement(CheckboxControl, {
                                key: option.value,
                                label: option.label,
                                checked: checked,
                                onChange: function (isChecked) {
                                    toggleColumn(option.value, isChecked);
                                },
                            });
                        })
                    ),
                    createElement(
                        PanelBody,
                        { title: __('Filtres initiaux', 'notation-jlg'), initialOpen: false },
                        createElement(TextControl, {
                            label: __('Catégorie (slug)', 'notation-jlg'),
                            value: attributes.category || '',
                            onChange: function (value) {
                                setAttributes({ category: value || '' });
                            },
                        }),
                        createElement(TextControl, {
                            label: __('Filtre lettre', 'notation-jlg'),
                            value: attributes.letterFilter || '',
                            onChange: function (value) {
                                setAttributes({ letterFilter: value || '' });
                            },
                            help: __('Utilisez une lettre ou le symbole # pour regrouper chiffres et symboles.', 'notation-jlg'),
                        }),
                        createElement(TextControl, {
                            label: __('Filtre genre', 'notation-jlg'),
                            value: attributes.genreFilter || '',
                            onChange: function (value) {
                                setAttributes({ genreFilter: value || '' });
                            },
                        })
                    )
                ),
                createElement(
                    'div',
                    blockProps,
                    createElement(BlockPreview, {
                        block: 'notation-jlg/summary-display',
                        attributes: {
                            postsPerPage: attributes.postsPerPage || 12,
                            layout: attributes.layout || 'table',
                            columns: attributes.columns || columns,
                            category: attributes.category || '',
                            letterFilter: attributes.letterFilter || '',
                            genreFilter: attributes.genreFilter || '',
                        },
                        label: __('Tableau récapitulatif', 'notation-jlg'),
                    })
                )
            );
        },
        save: function () {
            return null;
        },
    });
})(window.wp, window.jlgBlocks || {});

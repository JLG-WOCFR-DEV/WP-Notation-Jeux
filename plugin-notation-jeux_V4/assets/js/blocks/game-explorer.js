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

    var useBlockProps = typeof useBlockPropsHook === 'function'
        ? useBlockPropsHook
        : function (extraProps) {
              var props = extraProps || {};
              if (!props.className) {
                  props.className = 'notation-jlg-block';
              }
              return props;
          };

    var filterOptions = [
        { value: 'letter', label: __('Filtre lettre', 'notation-jlg') },
        { value: 'category', label: __('Filtre catégorie', 'notation-jlg') },
        { value: 'platform', label: __('Filtre plateforme', 'notation-jlg') },
        { value: 'availability', label: __('Disponibilité', 'notation-jlg') },
    ];

    var sortOptions = [
        { value: 'date|DESC', label: __('Plus récents', 'notation-jlg') },
        { value: 'date|ASC', label: __('Plus anciens', 'notation-jlg') },
        { value: 'score|DESC', label: __('Meilleures notes', 'notation-jlg') },
        { value: 'score|ASC', label: __('Notes les plus basses', 'notation-jlg') },
        { value: 'title|ASC', label: __('Titre (A-Z)', 'notation-jlg') },
        { value: 'title|DESC', label: __('Titre (Z-A)', 'notation-jlg') },
    ];

    registerBlockType('notation-jlg/game-explorer', {
        edit: function (props) {
            var attributes = props.attributes || {};
            var setAttributes = typeof props.setAttributes === 'function' ? props.setAttributes : function () {};
            var filters = Array.isArray(attributes.filters) ? attributes.filters.slice() : ['letter', 'category', 'platform', 'availability'];
            var blockProps = useBlockProps({ className: 'notation-jlg-game-explorer-block' });

            var toggleFilter = function (value, isChecked) {
                var current = Array.isArray(attributes.filters) ? attributes.filters.slice() : filters.slice();
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
                }

                setAttributes({ filters: next });
            };

            return createElement(
                Fragment,
                null,
                createElement(
                    InspectorControls,
                    null,
                    createElement(
                        PanelBody,
                        { title: __('Configuration du listing', 'notation-jlg'), initialOpen: true },
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
                        createElement(RangeControl, {
                            label: __('Colonnes', 'notation-jlg'),
                            value: attributes.columns || 3,
                            min: 1,
                            max: 4,
                            onChange: function (value) {
                                var parsed = parseInt(value, 10);
                                if (isNaN(parsed) || parsed < 1) {
                                    parsed = 3;
                                }
                                setAttributes({ columns: parsed });
                            },
                        }),
                        createElement(SelectControl, {
                            label: __('Tri par défaut', 'notation-jlg'),
                            value: attributes.sort || 'date|DESC',
                            options: sortOptions,
                            onChange: function (value) {
                                setAttributes({ sort: value || 'date|DESC' });
                            },
                        })
                    ),
                    createElement(
                        PanelBody,
                        { title: __('Extrait', 'notation-jlg'), initialOpen: false },
                        createElement(SelectControl, {
                            label: __('Mode d\'extrait', 'notation-jlg'),
                            value: attributes.excerptMode || 'short',
                            options: [
                                { value: 'short', label: __('Extrait court', 'notation-jlg') },
                                { value: 'full', label: __('Extrait complet', 'notation-jlg') },
                                { value: 'none', label: __('Masquer l\'extrait', 'notation-jlg') },
                            ],
                            help: __('Choisissez comment afficher le résumé des tests dans chaque carte.', 'notation-jlg'),
                            onChange: function (value) {
                                var normalized = typeof value === 'string' && value ? value : 'short';
                                setAttributes({ excerptMode: normalized });
                            },
                        }),
                        (attributes.excerptMode || 'short') === 'short'
                            ? createElement(RangeControl, {
                                  label: __('Longueur (mots)', 'notation-jlg'),
                                  value: attributes.excerptLength || 24,
                                  min: 5,
                                  max: 80,
                                  step: 1,
                                  onChange: function (value) {
                                      var parsed = parseInt(value, 10);
                                      if (isNaN(parsed) || parsed < 1) {
                                          parsed = 24;
                                      }
                                      setAttributes({ excerptLength: parsed });
                                  },
                              })
                            : null
                    ),
                    createElement(
                        PanelBody,
                        { title: __('Filtres disponibles', 'notation-jlg'), initialOpen: false },
                        filterOptions.map(function (option) {
                            var checked = filters.indexOf(option.value) !== -1;
                            return createElement(CheckboxControl, {
                                key: option.value,
                                label: option.label,
                                checked: checked,
                                onChange: function (isChecked) {
                                    toggleFilter(option.value, isChecked);
                                },
                            });
                        })
                    ),
                    createElement(
                        PanelBody,
                        { title: __('Pré-filtrage', 'notation-jlg'), initialOpen: false },
                        createElement(TextControl, {
                            label: __('Catégorie (slug)', 'notation-jlg'),
                            value: attributes.category || '',
                            onChange: function (value) {
                                setAttributes({ category: value || '' });
                            },
                        }),
                        createElement(TextControl, {
                            label: __('Plateforme (slug)', 'notation-jlg'),
                            value: attributes.platform || '',
                            onChange: function (value) {
                                setAttributes({ platform: value || '' });
                            },
                        }),
                        createElement(TextControl, {
                            label: __('Lettre', 'notation-jlg'),
                            value: attributes.letter || '',
                            onChange: function (value) {
                                setAttributes({ letter: value || '' });
                            },
                        })
                    )
                ),
                createElement(
                    'div',
                    blockProps,
                    createElement(BlockPreview, {
                        block: 'notation-jlg/game-explorer',
                        attributes: {
                            postsPerPage: attributes.postsPerPage || 12,
                            columns: attributes.columns || 3,
                            filters: attributes.filters || filters,
                            category: attributes.category || '',
                            platform: attributes.platform || '',
                            letter: attributes.letter || '',
                            sort: attributes.sort || 'date|DESC',
                            excerptMode: attributes.excerptMode || 'short',
                            excerptLength: attributes.excerptLength || 24,
                        },
                        label: __('Game Explorer', 'notation-jlg'),
                    })
                )
            );
        },
        save: function () {
            return null;
        },
    });
})(window.wp, window.jlgBlocks || {});

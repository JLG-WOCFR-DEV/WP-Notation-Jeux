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
    var TextControl = wp.components.TextControl;
    var CheckboxControl = wp.components.CheckboxControl;
    var createElement = wp.element.createElement;
    var Fragment = wp.element.Fragment;
    var PostPicker = blocksHelpers.PostPicker;
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

    var fieldOptions = [
        { value: 'developpeur', label: __('Développeur', 'notation-jlg') },
        { value: 'editeur', label: __('Éditeur', 'notation-jlg') },
        { value: 'date_sortie', label: __('Date de sortie', 'notation-jlg') },
        { value: 'version', label: __('Version', 'notation-jlg') },
        { value: 'pegi', label: __('PEGI', 'notation-jlg') },
        { value: 'temps_de_jeu', label: __('Temps de jeu', 'notation-jlg') },
        { value: 'plateformes', label: __('Plateformes', 'notation-jlg') },
    ];

    registerBlockType('notation-jlg/game-info', {
        edit: function (props) {
            var attributes = props.attributes || {};
            var baseSetAttributes = typeof props.setAttributes === 'function' ? props.setAttributes : function () {};
            var setAttributes = useAnalyticsAttributeSetter
                ? useAnalyticsAttributeSetter('notation-jlg/game-info', attributes, baseSetAttributes)
                : baseSetAttributes;
            var fields = Array.isArray(attributes.fields) ? attributes.fields.slice() : [];
            if (!fields.length) {
                fields = fieldOptions.map(function (option) {
                    return option.value;
                });
            }

            var blockProps = useBlockProps({ className: 'notation-jlg-game-info-block' });

            var toggleField = function (value, isChecked) {
                var current = Array.isArray(attributes.fields) ? attributes.fields.slice() : fieldOptions.map(function (option) {
                    return option.value;
                });
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

                setAttributes({ fields: next });
            };

            return createElement(
                Fragment,
                null,
                createElement(
                    InspectorControls,
                    null,
                    createElement(
                        PanelBody,
                        { title: __('Source des données', 'notation-jlg'), initialOpen: true },
                        createElement(PostPicker, {
                            value: attributes.postId || 0,
                            onChange: function (value) {
                                setAttributes({ postId: value || 0 });
                            },
                        }),
                        createElement(TextControl, {
                            label: __('Titre personnalisé', 'notation-jlg'),
                            value: attributes.title || '',
                            onChange: function (value) {
                                setAttributes({ title: value || '' });
                            },
                        })
                    ),
                    createElement(
                        PanelBody,
                        { title: __('Champs affichés', 'notation-jlg'), initialOpen: false },
                        fieldOptions.map(function (option) {
                            var checked = fields.indexOf(option.value) !== -1;
                            return createElement(CheckboxControl, {
                                key: option.value,
                                label: option.label,
                                checked: checked,
                                onChange: function (isChecked) {
                                    toggleField(option.value, isChecked);
                                },
                            });
                        }),
                        createElement(
                            'p',
                            { className: 'description' },
                            __('Décochez les éléments que vous ne souhaitez pas afficher.', 'notation-jlg')
                        )
                    )
                ),
                createElement(
                    'div',
                    blockProps,
                    createElement(BlockPreview, {
                        block: 'notation-jlg/game-info',
                        attributes: {
                            postId: attributes.postId || 0,
                            title: attributes.title || '',
                            fields: attributes.fields || fields,
                        },
                        label: __('Fiche technique', 'notation-jlg'),
                    })
                )
            );
        },
        save: function () {
            return null;
        },
    });
})(window.wp, window.jlgBlocks || {});

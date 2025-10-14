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
    var PanelBody = wp.components.PanelBody;
    var TextControl = wp.components.TextControl;
    var ToggleControl = wp.components.ToggleControl;
    var useBlockPropsHook = blockEditor.useBlockProps;
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

    registerBlockType('notation-jlg/express-rating', {
        edit: function (props) {
            var attributes = props.attributes || {};
            var baseSetAttributes = typeof props.setAttributes === 'function' ? props.setAttributes : function () {};
            var setAttributes = useAnalyticsAttributeSetter
                ? useAnalyticsAttributeSetter('notation-jlg/express-rating', attributes, baseSetAttributes)
                : baseSetAttributes;
            var blockProps = useBlockProps({ className: 'notation-jlg-express-rating-block-editor' });

            return createElement(
                Fragment,
                null,
                createElement(
                    InspectorControls,
                    null,
                    createElement(
                        PanelBody,
                        { title: __('Notation express', 'notation-jlg'), initialOpen: true },
                        createElement(TextControl, {
                            label: __('Note affichée', 'notation-jlg'),
                            type: 'text',
                            inputMode: 'decimal',
                            value: attributes.scoreValue || '',
                            onChange: function (value) {
                                setAttributes({ scoreValue: value || '' });
                            },
                            help: __('Saisissez la note finale (ex. 8.5).', 'notation-jlg'),
                        }),
                        createElement(TextControl, {
                            label: __('Barème', 'notation-jlg'),
                            type: 'text',
                            inputMode: 'decimal',
                            value: attributes.scoreMax || '',
                            onChange: function (value) {
                                setAttributes({ scoreMax: value || '' });
                            },
                            help: __('Valeur maximale affichée (ex. 10).', 'notation-jlg'),
                        })
                    ),
                    createElement(
                        PanelBody,
                        { title: __('Badge', 'notation-jlg'), initialOpen: false },
                        createElement(ToggleControl, {
                            label: __('Afficher le badge', 'notation-jlg'),
                            checked: !!attributes.showBadge,
                            onChange: function (value) {
                                setAttributes({ showBadge: !!value });
                            },
                        }),
                        createElement(TextControl, {
                            label: __('Libellé du badge', 'notation-jlg'),
                            value: attributes.badgeLabel || '',
                            onChange: function (value) {
                                setAttributes({ badgeLabel: value || '' });
                            },
                            help: __('Optionnel. Exemple : « Coup de cœur ».', 'notation-jlg'),
                            disabled: !attributes.showBadge,
                        })
                    ),
                    createElement(
                        PanelBody,
                        { title: __('Bouton d’appel à l’action', 'notation-jlg'), initialOpen: false },
                        createElement(TextControl, {
                            label: __('Texte du bouton', 'notation-jlg'),
                            value: attributes.ctaLabel || '',
                            onChange: function (value) {
                                setAttributes({ ctaLabel: value || '' });
                            },
                        }),
                        createElement(TextControl, {
                            label: __('URL du bouton', 'notation-jlg'),
                            type: 'url',
                            value: attributes.ctaUrl || '',
                            onChange: function (value) {
                                setAttributes({ ctaUrl: value || '' });
                            },
                            help: __('Utilisez une URL absolue (https://…).', 'notation-jlg'),
                        }),
                        createElement(TextControl, {
                            label: __('Attribut rel', 'notation-jlg'),
                            value: attributes.ctaRel || '',
                            onChange: function (value) {
                                setAttributes({ ctaRel: value || '' });
                            },
                            help: __('Optionnel. Exemple : sponsored noopener.', 'notation-jlg'),
                        }),
                        createElement(ToggleControl, {
                            label: __('Ouvrir dans un nouvel onglet', 'notation-jlg'),
                            checked: !!attributes.ctaNewTab,
                            onChange: function (value) {
                                setAttributes({ ctaNewTab: !!value });
                            },
                        })
                    )
                ),
                createElement(
                    'div',
                    blockProps,
                    createElement(BlockPreview, {
                        block: 'notation-jlg/express-rating',
                        attributes: attributes,
                        label: __('Prévisualisation « Notation express »', 'notation-jlg'),
                    })
                )
            );
        },
        save: function () {
            return null;
        },
    });
})(window.wp, window.jlgBlocks || {});

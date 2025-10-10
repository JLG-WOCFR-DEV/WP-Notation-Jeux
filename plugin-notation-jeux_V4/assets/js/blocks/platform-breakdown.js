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
    var ToggleControl = wp.components.ToggleControl;
    var TextControl = wp.components.TextControl;
    var TextareaControl = wp.components.TextareaControl;
    var Fragment = wp.element.Fragment;
    var createElement = wp.element.createElement;
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

    registerBlockType('notation-jlg/platform-breakdown', {
        edit: function (props) {
            var attributes = props.attributes || {};
            var baseSetAttributes = typeof props.setAttributes === 'function' ? props.setAttributes : function () {};
            var setAttributes = useAnalyticsAttributeSetter
                ? useAnalyticsAttributeSetter('notation-jlg/platform-breakdown', attributes, baseSetAttributes)
                : baseSetAttributes;
            var blockProps = useBlockProps({ className: 'jlg-platform-breakdown jlg-platform-breakdown--preview' });

            return createElement(
                Fragment,
                null,
                createElement(
                    InspectorControls,
                    null,
                    createElement(
                        PanelBody,
                        { title: __('Données de la review', 'notation-jlg'), initialOpen: true },
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
                            help: __('Facultatif. Affiché au-dessus du comparatif.', 'notation-jlg'),
                        })
                    ),
                    createElement(
                        PanelBody,
                        { title: __('Affichage', 'notation-jlg'), initialOpen: false },
                        createElement(ToggleControl, {
                            label: __('Afficher le badge « meilleure expérience »', 'notation-jlg'),
                            checked: attributes.showBestBadge !== false,
                            onChange: function (value) {
                                setAttributes({ showBestBadge: !!value });
                            },
                        }),
                        createElement(TextControl, {
                            label: __('Libellé du badge', 'notation-jlg'),
                            value: attributes.highlightBadgeLabel || '',
                            onChange: function (value) {
                                setAttributes({ highlightBadgeLabel: value || '' });
                            },
                        }),
                        createElement(TextareaControl, {
                            label: __('Message vide', 'notation-jlg'),
                            value: attributes.emptyMessage || '',
                            onChange: function (value) {
                                setAttributes({ emptyMessage: value || '' });
                            },
                            help: __('S’affiche lorsque aucun comparatif plateforme n’est encore disponible.', 'notation-jlg'),
                        })
                    )
                ),
                createElement(
                    'div',
                    blockProps,
                    createElement(BlockPreview, {
                        block: 'notation-jlg/platform-breakdown',
                        attributes: {
                            postId: attributes.postId || 0,
                            title: attributes.title || '',
                            showBestBadge: attributes.showBestBadge !== false,
                            highlightBadgeLabel: attributes.highlightBadgeLabel || '',
                            emptyMessage: attributes.emptyMessage || '',
                        },
                        label: __('Comparatif plateformes', 'notation-jlg'),
                    })
                )
            );
        },
        save: function () {
            return null;
        },
    });
})(window.wp, window.jlgBlocks || {});

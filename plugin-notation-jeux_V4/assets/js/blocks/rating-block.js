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
    var ToggleControl = wp.components.ToggleControl;
    var SelectControl = wp.components.SelectControl;
    var ColorPalette = (blockEditor && blockEditor.ColorPalette) || wp.components.ColorPalette;
    var PanelColorSettings = blockEditor.PanelColorSettings || blockEditor.__experimentalPanelColorSettings;
    var useBlockPropsHook = blockEditor.useBlockProps;
    var createElement = wp.element.createElement;
    var Fragment = wp.element.Fragment;
    var PostPicker = blocksHelpers.PostPicker;
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

    registerBlockType('notation-jlg/rating-block', {
        edit: function (props) {
            var attributes = props.attributes || {};
            var setAttributes = typeof props.setAttributes === 'function' ? props.setAttributes : function () {};
            var blockProps = useBlockProps({ className: 'notation-jlg-rating-block-editor' });

            var colorControl = PanelColorSettings
                ? createElement(PanelColorSettings, {
                      title: __('Couleurs', 'notation-jlg'),
                      colorSettings: [
                          {
                              value: attributes.accentColor || '',
                              onChange: function (value) {
                                  setAttributes({ accentColor: value || '' });
                              },
                              label: __('Couleur d\'accent', 'notation-jlg'),
                          },
                      ],
                  })
                : createElement(
                      PanelBody,
                      { title: __('Couleur d\'accent', 'notation-jlg'), initialOpen: false },
                      ColorPalette
                          ? createElement(ColorPalette, {
                                value: attributes.accentColor || '',
                                onChange: function (value) {
                                    setAttributes({ accentColor: value || '' });
                                },
                            })
                          : null
                  );

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
                        createElement(ToggleControl, {
                            label: __('Afficher les animations', 'notation-jlg'),
                            checked: typeof attributes.showAnimations === 'boolean' ? attributes.showAnimations : true,
                            onChange: function (value) {
                                setAttributes({ showAnimations: !!value });
                            },
                        })
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
                            showAnimations: typeof attributes.showAnimations === 'boolean' ? attributes.showAnimations : true,
                            accentColor: attributes.accentColor || '',
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

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
    var SelectControl = wp.components.SelectControl;
    var TextControl = wp.components.TextControl;
    var ColorPalette = (blockEditor && blockEditor.ColorPalette) || wp.components.ColorPalette;
    var PanelColorSettings = blockEditor.PanelColorSettings || blockEditor.__experimentalPanelColorSettings;
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

    registerBlockType('notation-jlg/all-in-one', {
        edit: function (props) {
            var attributes = props.attributes || {};
            var setAttributes = typeof props.setAttributes === 'function' ? props.setAttributes : function () {};
            var blockProps = useBlockProps({ className: 'notation-jlg-all-in-one-block' });

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
                            label: __('Style visuel', 'notation-jlg'),
                            value: attributes.style || 'moderne',
                            options: [
                                { value: 'moderne', label: __('Moderne', 'notation-jlg') },
                                { value: 'classique', label: __('Classique', 'notation-jlg') },
                                { value: 'compact', label: __('Compact', 'notation-jlg') },
                            ],
                            onChange: function (value) {
                                setAttributes({ style: value || 'moderne' });
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
                            label: __('Afficher la notation', 'notation-jlg'),
                            checked: typeof attributes.showRating === 'boolean' ? attributes.showRating : true,
                            onChange: function (value) {
                                setAttributes({ showRating: !!value });
                            },
                        }),
                        createElement(ToggleControl, {
                            label: __('Afficher les points forts/faibles', 'notation-jlg'),
                            checked: typeof attributes.showProsCons === 'boolean' ? attributes.showProsCons : true,
                            onChange: function (value) {
                                setAttributes({ showProsCons: !!value });
                            },
                        }),
                        createElement(ToggleControl, {
                            label: __('Afficher la tagline', 'notation-jlg'),
                            checked: typeof attributes.showTagline === 'boolean' ? attributes.showTagline : true,
                            onChange: function (value) {
                                setAttributes({ showTagline: !!value });
                            },
                        }),
                        createElement(ToggleControl, {
                            label: __('Afficher le verdict', 'notation-jlg'),
                            checked: typeof attributes.showVerdict === 'boolean' ? attributes.showVerdict : true,
                            onChange: function (value) {
                                setAttributes({ showVerdict: !!value });
                            },
                        }),
                        createElement(ToggleControl, {
                            label: __('Afficher le badge “Recommandé”', 'notation-jlg'),
                            checked: typeof attributes.showEditorBadge === 'boolean' ? attributes.showEditorBadge : true,
                            onChange: function (value) {
                                setAttributes({ showEditorBadge: !!value });
                            },
                        })
                    ),
                    colorControl,
                    createElement(
                        PanelBody,
                        { title: __('Titres personnalisés', 'notation-jlg'), initialOpen: false },
                        createElement(TextControl, {
                            label: __('Titre Points forts', 'notation-jlg'),
                            value: attributes.prosTitle || '',
                            onChange: function (value) {
                                setAttributes({ prosTitle: value || '' });
                            },
                        }),
                        createElement(TextControl, {
                            label: __('Titre Points faibles', 'notation-jlg'),
                            value: attributes.consTitle || '',
                            onChange: function (value) {
                                setAttributes({ consTitle: value || '' });
                            },
                        })
                    ),
                    createElement(
                        PanelBody,
                        { title: __('Bouton d\'action', 'notation-jlg'), initialOpen: false },
                        createElement(TextControl, {
                            label: __('Texte du bouton', 'notation-jlg'),
                            value: attributes.ctaLabel || '',
                            onChange: function (value) {
                                setAttributes({ ctaLabel: value || '' });
                            },
                            help: __('Laissez vide pour utiliser le texte défini dans la métabox.', 'notation-jlg'),
                        }),
                        createElement(TextControl, {
                            label: __('URL du bouton', 'notation-jlg'),
                            type: 'url',
                            value: attributes.ctaUrl || '',
                            onChange: function (value) {
                                setAttributes({ ctaUrl: value || '' });
                            },
                            help: __('Utilisez une URL absolue (https://...) ou laissez vide pour utiliser la métabox.', 'notation-jlg'),
                        }),
                        createElement(TextControl, {
                            label: __('Attribut role', 'notation-jlg'),
                            value: attributes.ctaRole || '',
                            onChange: function (value) {
                                setAttributes({ ctaRole: value || '' });
                            },
                            help: __('Par défaut : button', 'notation-jlg'),
                        }),
                        createElement(TextControl, {
                            label: __('Attribut rel', 'notation-jlg'),
                            value: attributes.ctaRel || '',
                            onChange: function (value) {
                                setAttributes({ ctaRel: value || '' });
                            },
                            help: __('Par défaut : nofollow sponsored', 'notation-jlg'),
                        })
                    )
                ),
                createElement(
                    'div',
                    blockProps,
                    createElement(BlockPreview, {
                        block: 'notation-jlg/all-in-one',
                        attributes: {
                            postId: attributes.postId || 0,
                            showRating: typeof attributes.showRating === 'boolean' ? attributes.showRating : true,
                            showProsCons: typeof attributes.showProsCons === 'boolean' ? attributes.showProsCons : true,
                            showTagline: typeof attributes.showTagline === 'boolean' ? attributes.showTagline : true,
                            showVerdict: typeof attributes.showVerdict === 'boolean' ? attributes.showVerdict : true,
                            showEditorBadge: typeof attributes.showEditorBadge === 'boolean' ? attributes.showEditorBadge : true,
                            style: attributes.style || 'moderne',
                            scoreDisplay: attributes.scoreDisplay || 'absolute',
                            accentColor: attributes.accentColor || '',
                            prosTitle: attributes.prosTitle || '',
                            consTitle: attributes.consTitle || '',
                            ctaLabel: attributes.ctaLabel || '',
                            ctaUrl: attributes.ctaUrl || '',
                            ctaRole: attributes.ctaRole || '',
                            ctaRel: attributes.ctaRel || '',
                        },
                        label: __('Bloc tout-en-un', 'notation-jlg'),
                    })
                )
            );
        },
        save: function () {
            return null;
        },
    });
})(window.wp, window.jlgBlocks || {});

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
    var TextControl = wp.components.TextControl;
    var TextareaControl = wp.components.TextareaControl;
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

    registerBlockType('notation-jlg/rating-block', {
        edit: function (props) {
            var attributes = props.attributes || {};
            var setAttributes = typeof props.setAttributes === 'function' ? props.setAttributes : function () {};
            var blockProps = useBlockProps({ className: 'notation-jlg-rating-block-editor' });
            var colorControl = createAccentColorControl(attributes, setAttributes);

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
                            label: __('Preset visuel', 'notation-jlg'),
                            value: attributes.visualPreset || 'inherit',
                            options: [
                                { value: 'inherit', label: __('Suivre les réglages globaux', 'notation-jlg') },
                                { value: 'signature', label: __('Signature – Dégradé dynamique', 'notation-jlg') },
                                { value: 'minimal', label: __('Minimal – Interface épurée', 'notation-jlg') },
                                { value: 'editorial', label: __('Éditorial – Contraste fort', 'notation-jlg') },
                            ],
                            onChange: function (value) {
                                setAttributes({ visualPreset: value || 'inherit' });
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
                        createElement(SelectControl, {
                            label: __('Thème de prévisualisation', 'notation-jlg'),
                            value: attributes.previewTheme || 'auto',
                            options: [
                                { value: 'auto', label: __('Selon les réglages du site', 'notation-jlg') },
                                { value: 'dark', label: __('Forcer le thème sombre', 'notation-jlg') },
                                { value: 'light', label: __('Forcer le thème clair', 'notation-jlg') },
                            ],
                            onChange: function (value) {
                                setAttributes({ previewTheme: value || 'auto' });
                            },
                        }),
                        createElement(SelectControl, {
                            label: __('Animations (aperçu)', 'notation-jlg'),
                            value: attributes.previewAnimations || 'inherit',
                            options: [
                                { value: 'inherit', label: __('Suivre la configuration globale', 'notation-jlg') },
                                { value: 'enabled', label: __('Toujours activer', 'notation-jlg') },
                                { value: 'disabled', label: __('Toujours désactiver', 'notation-jlg') },
                            ],
                            onChange: function (value) {
                                setAttributes({ previewAnimations: value || 'inherit' });
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
                        { title: __('Carte verdict', 'notation-jlg'), initialOpen: false },
                        createElement(SelectControl, {
                            label: __('Affichage du verdict', 'notation-jlg'),
                            value: attributes.showVerdict || 'inherit',
                            options: [
                                { value: 'inherit', label: __('Suivre les réglages du module', 'notation-jlg') },
                                { value: 'show', label: __('Toujours afficher (si disponible)', 'notation-jlg') },
                                { value: 'hide', label: __('Masquer la carte verdict', 'notation-jlg') },
                            ],
                            onChange: function (value) {
                                setAttributes({ showVerdict: value || 'inherit' });
                            },
                        }),
                        createElement(TextareaControl, {
                            label: __('Résumé personnalisé', 'notation-jlg'),
                            value: attributes.verdictSummary || '',
                            onChange: function (value) {
                                setAttributes({ verdictSummary: value || '' });
                            },
                            help: __('Laissez vide pour reprendre le résumé saisi dans la métabox.', 'notation-jlg'),
                            rows: 3,
                        }),
                        createElement(TextControl, {
                            label: __('Texte du bouton verdict', 'notation-jlg'),
                            value: attributes.verdictCtaLabel || '',
                            onChange: function (value) {
                                setAttributes({ verdictCtaLabel: value || '' });
                            },
                            help: __('Par défaut : « Lire le test complet ».', 'notation-jlg'),
                        }),
                        createElement(TextControl, {
                            label: __('URL du bouton verdict', 'notation-jlg'),
                            type: 'url',
                            value: attributes.verdictCtaUrl || '',
                            onChange: function (value) {
                                setAttributes({ verdictCtaUrl: value || '' });
                            },
                            help: __('Utilisez une URL absolue ou laissez vide pour pointer vers l’article.', 'notation-jlg'),
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
                            scoreDisplay: attributes.scoreDisplay || 'absolute',
                            showVerdict: attributes.showVerdict || 'inherit',
                            verdictSummary: attributes.verdictSummary || '',
                            verdictCtaLabel: attributes.verdictCtaLabel || '',
                            verdictCtaUrl: attributes.verdictCtaUrl || '',
                            showAnimations: typeof attributes.showAnimations === 'boolean' ? attributes.showAnimations : true,
                            accentColor: attributes.accentColor || '',
                            previewTheme: attributes.previewTheme || 'auto',
                            previewAnimations: attributes.previewAnimations || 'inherit',
                            visualPreset: attributes.visualPreset || 'inherit',
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

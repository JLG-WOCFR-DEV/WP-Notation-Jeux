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
    var SelectControl = wp.components.SelectControl;
    var TextControl = wp.components.TextControl;
    var RangeControl = wp.components.RangeControl;
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

    var timeRangeOptions = [
        { value: 'all', label: __('Toutes les périodes', 'notation-jlg') },
        { value: 'last_30_days', label: __('30 derniers jours', 'notation-jlg') },
        { value: 'last_90_days', label: __('90 derniers jours', 'notation-jlg') },
        { value: 'last_365_days', label: __('12 derniers mois', 'notation-jlg') }
    ];

    registerBlockType('notation-jlg/score-insights', {
        edit: function (props) {
            var attributes = props.attributes || {};
            var setAttributes = typeof props.setAttributes === 'function' ? props.setAttributes : function () {};
            var blockProps = useBlockProps({ className: 'notation-jlg-score-insights-block' });

            var safeLimit = typeof attributes.platformLimit === 'number' ? attributes.platformLimit : 5;
            if (safeLimit < 1) {
                safeLimit = 1;
            }
            if (safeLimit > 10) {
                safeLimit = 10;
            }

            return createElement(
                Fragment,
                null,
                createElement(
                    InspectorControls,
                    null,
                    createElement(
                        PanelBody,
                        { title: __('Données affichées', 'notation-jlg'), initialOpen: true },
                        createElement(SelectControl, {
                            label: __('Période analysée', 'notation-jlg'),
                            value: attributes.timeRange || 'all',
                            options: timeRangeOptions,
                            onChange: function (value) {
                                setAttributes({ timeRange: value || 'all' });
                            }
                        }),
                        createElement(TextControl, {
                            label: __('Filtrer par plateforme (slug)', 'notation-jlg'),
                            value: attributes.platform || '',
                            onChange: function (value) {
                                setAttributes({ platform: value || '' });
                            },
                            help: __('Laissez vide pour toutes les plateformes ou utilisez le slug enregistré dans Notation → Plateformes.', 'notation-jlg')
                        }),
                        createElement(RangeControl, {
                            label: __('Nombre de plateformes affichées', 'notation-jlg'),
                            value: safeLimit,
                            min: 1,
                            max: 10,
                            onChange: function (value) {
                                var parsed = parseInt(value, 10);
                                if (isNaN(parsed)) {
                                    parsed = 5;
                                }
                                if (parsed < 1) {
                                    parsed = 1;
                                }
                                if (parsed > 10) {
                                    parsed = 10;
                                }
                                setAttributes({ platformLimit: parsed });
                            }
                        })
                    ),
                    createElement(
                        PanelBody,
                        { title: __('Présentation', 'notation-jlg'), initialOpen: false },
                        createElement(TextControl, {
                            label: __('Titre personnalisé', 'notation-jlg'),
                            value: attributes.title || '',
                            onChange: function (value) {
                                setAttributes({ title: value || '' });
                            },
                            placeholder: __('Score Insights', 'notation-jlg')
                        })
                    )
                ),
                createElement(
                    'div',
                    blockProps,
                    createElement(BlockPreview, {
                        block: 'notation-jlg/score-insights',
                        attributes: {
                            title: attributes.title || '',
                            timeRange: attributes.timeRange || 'all',
                            platform: attributes.platform || '',
                            platformLimit: safeLimit
                        },
                        label: __('Score Insights', 'notation-jlg')
                    })
                )
            );
        },
        save: function () {
            return null;
        }
    });
})(window.wp, window.jlgBlocks || {});

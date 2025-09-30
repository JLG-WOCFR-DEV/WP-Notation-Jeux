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
    var useBlockPropsHook = blockEditor.useBlockProps;
    var createElement = wp.element.createElement;
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

    registerBlockType('notation-jlg/pros-cons', {
        edit: function (props) {
            var attributes = props.attributes || {};
            var blockProps = useBlockProps({ className: 'notation-jlg-pros-cons-block' });

            return createElement(
                'div',
                blockProps,
                createElement(BlockPreview, {
                    block: 'notation-jlg/pros-cons',
                    attributes: attributes,
                    label: __('Points forts / faibles', 'notation-jlg'),
                })
            );
        },
        save: function () {
            return null;
        },
    });
})(window.wp, window.jlgBlocks || {});

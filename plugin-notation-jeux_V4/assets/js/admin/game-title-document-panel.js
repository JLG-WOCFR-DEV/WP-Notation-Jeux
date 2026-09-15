(function (wp) {
    if (!wp || !wp.plugins || !wp.element || !wp.components || !wp.data || !wp.i18n) {
        return;
    }

    var registerPlugin = wp.plugins.registerPlugin;
    var PluginDocumentSettingPanel =
        (wp.editPost && wp.editPost.PluginDocumentSettingPanel) ||
        (wp.editor && wp.editor.PluginDocumentSettingPanel);
    var PluginSidebar =
        (wp.editPost && wp.editPost.PluginSidebar) ||
        (wp.editor && wp.editor.PluginSidebar);
    var PluginSidebarMoreMenuItem =
        (wp.editPost && wp.editPost.PluginSidebarMoreMenuItem) ||
        (wp.editor && wp.editor.PluginSidebarMoreMenuItem);

    if (typeof registerPlugin !== 'function' || !PluginDocumentSettingPanel) {
        return;
    }

    var createElement = wp.element.createElement;
    var Fragment = wp.element.Fragment;
    var useSelect = wp.data.useSelect;
    var useDispatch = wp.data.useDispatch;
    var TextControl = wp.components.TextControl;
    var __ = wp.i18n.__;

    var GameTitleControl = function GameTitleControl(props) {
        var inputId = props.inputId || 'jlg_game_title';
        var meta = useSelect(function (select) {
            var editor = select('core/editor');
            if (!editor || typeof editor.getEditedPostAttribute !== 'function') {
                return {};
            }

            return editor.getEditedPostAttribute('meta') || {};
        }, []);

        var dispatch = useDispatch('core/editor');
        var editPost = dispatch && typeof dispatch.editPost === 'function' ? dispatch.editPost : null;
        var value = typeof meta._jlg_game_title === 'string' ? meta._jlg_game_title : '';

        return createElement(TextControl, {
            id: inputId,
            label: __('Nom du jeu', 'notation-jlg'),
            value: value,
            onChange: function (nextValue) {
                if (!editPost) {
                    return;
                }

                editPost({
                    meta: {
                        _jlg_game_title: typeof nextValue === 'string' ? nextValue : '',
                    },
                });
            },
            help: __(
                'Utilisé dans les tableaux, widgets et données structurées. Ce champ est dans le chrome Gutenberg (hors iframe).',
                'notation-jlg'
            ),
        });
    };

    var GameTitlePlugin = function GameTitlePlugin() {
        var children = [
            createElement(
                PluginDocumentSettingPanel,
                {
                    name: 'notation-jlg-game-title',
                    title: __('Notation JLG', 'notation-jlg'),
                    className: 'notation-jlg-game-title-panel',
                },
                createElement(GameTitleControl, { inputId: 'jlg_game_title' })
            ),
        ];

        if (PluginSidebar) {
            children.push(
                createElement(
                    PluginSidebar,
                    {
                        name: 'notation-jlg-game-title-sidebar',
                        title: __('Notation JLG', 'notation-jlg'),
                        icon: 'star-filled',
                    },
                    createElement(GameTitleControl, { inputId: 'jlg_game_title_sidebar' })
                )
            );
        }

        if (PluginSidebarMoreMenuItem) {
            children.push(
                createElement(
                    PluginSidebarMoreMenuItem,
                    {
                        target: 'notation-jlg-game-title-sidebar',
                        icon: 'star-filled',
                    },
                    __('Nom du jeu', 'notation-jlg')
                )
            );
        }

        return createElement(Fragment, null, children);
    };

    registerPlugin('notation-jlg-game-title', {
        render: GameTitlePlugin,
    });
})(window.wp);

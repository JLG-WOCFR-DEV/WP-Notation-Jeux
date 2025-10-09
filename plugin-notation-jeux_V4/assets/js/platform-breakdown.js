(function () {
    function activateTab(container, tab, shouldFocus) {
        var tabs = Array.prototype.slice.call(container.querySelectorAll('.jlg-platform-breakdown__tab'));
        var panels = Array.prototype.slice.call(container.querySelectorAll('.jlg-platform-breakdown__panel'));
        if (!tab || tabs.length === 0 || panels.length === 0) {
            return;
        }

        tabs.forEach(function (item) {
            var controls = item.getAttribute('data-target-panel');
            var target = controls ? container.querySelector('#' + controls) : null;
            var isActive = item === tab;

            item.classList.toggle('is-active', isActive);
            item.setAttribute('aria-selected', isActive ? 'true' : 'false');
            item.setAttribute('tabindex', isActive ? '0' : '-1');

            if (target) {
                target.classList.toggle('is-active', isActive);
                if (isActive) {
                    target.removeAttribute('hidden');
                } else {
                    target.setAttribute('hidden', '');
                }
            }
        });

        if (shouldFocus) {
            tab.focus({ preventScroll: true });
        }
    }

    function handleKeydown(event, container) {
        var key = event.key || event.code;
        if (!key) {
            return;
        }

        var tabs = Array.prototype.slice.call(container.querySelectorAll('.jlg-platform-breakdown__tab'));
        if (tabs.length === 0) {
            return;
        }

        var current = event.currentTarget;
        var currentIndex = tabs.indexOf(current);
        if (currentIndex === -1) {
            return;
        }

        var nextIndex = currentIndex;
        if (key === 'ArrowRight' || key === 'ArrowDown') {
            nextIndex = (currentIndex + 1) % tabs.length;
            event.preventDefault();
        } else if (key === 'ArrowLeft' || key === 'ArrowUp') {
            nextIndex = (currentIndex - 1 + tabs.length) % tabs.length;
            event.preventDefault();
        } else if (key === 'Home') {
            nextIndex = 0;
            event.preventDefault();
        } else if (key === 'End') {
            nextIndex = tabs.length - 1;
            event.preventDefault();
        }

        if (nextIndex !== currentIndex) {
            activateTab(container, tabs[nextIndex], true);
        }
    }

    function init(container) {
        if (!container || container.getAttribute('data-jlg-platform-init') === 'true') {
            return;
        }

        var tabs = container.querySelectorAll('.jlg-platform-breakdown__tab');
        if (!tabs.length) {
            return;
        }

        var activeTab = container.querySelector('.jlg-platform-breakdown__tab.is-active') || tabs[0];
        activateTab(container, activeTab);

        Array.prototype.forEach.call(tabs, function (tab) {
            tab.addEventListener('click', function (event) {
                event.preventDefault();
                activateTab(container, tab, true);
            });

            tab.addEventListener('keydown', function (event) {
                handleKeydown(event, container);
            });
        });

        container.setAttribute('data-jlg-platform-init', 'true');
    }

    function boot() {
        var containers = document.querySelectorAll('.jlg-platform-breakdown');
        Array.prototype.forEach.call(containers, init);
    }

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        setTimeout(boot, 0);
    } else {
        document.addEventListener('DOMContentLoaded', boot);
    }
})();

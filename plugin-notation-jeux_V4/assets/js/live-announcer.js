(function () {
    var global = typeof window !== 'undefined' ? window : {};
    if (!global || global.jlgLiveAnnouncer) {
        return;
    }

    var documentRef = global.document;
    if (!documentRef) {
        return;
    }

    var l10n = global.jlgLiveAnnouncerL10n || {};
    var DISMISS_LABEL = typeof l10n.dismissLabel === 'string' && l10n.dismissLabel !== ''
        ? l10n.dismissLabel
        : 'Fermer';
    var HIDE_ANNOUNCEMENT_LABEL = typeof l10n.hideAnnouncementLabel === 'string' && l10n.hideAnnouncementLabel !== ''
        ? l10n.hideAnnouncementLabel
        : '';
    var LIVE_REGION_ID = 'jlg-live-announcer-region';
    var TOAST_ID = 'jlg-live-announcer-toast';
    var ANNOUNCE_EVENT = 'jlg:live-announcer:announcement';
    var liveRegionNode = null;
    var toastNode = null;
    var hideTimer = null;

    function createLiveRegion() {
        if (liveRegionNode) {
            return liveRegionNode;
        }

        var region = documentRef.createElement('div');
        region.id = LIVE_REGION_ID;
        region.setAttribute('role', 'status');
        region.setAttribute('aria-live', 'polite');
        region.className = 'jlg-live-announcer-sr jlg-visually-hidden';
        documentRef.body.appendChild(region);
        liveRegionNode = region;
        return liveRegionNode;
    }

    function handleDismiss(event) {
        if (event && typeof event.preventDefault === 'function') {
            event.preventDefault();
        }
        hideToast(true);
    }

    function createToast() {
        if (toastNode) {
            return toastNode;
        }

        var toast = documentRef.createElement('div');
        toast.id = TOAST_ID;
        toast.className = 'jlg-live-toast';
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('role', 'status');

        var message = documentRef.createElement('div');
        message.className = 'jlg-live-toast__message';
        toast.appendChild(message);

        var dismissButton = documentRef.createElement('button');
        dismissButton.type = 'button';
        dismissButton.className = 'jlg-live-toast__dismiss';
        dismissButton.setAttribute('aria-label', DISMISS_LABEL);
        dismissButton.textContent = '×';
        dismissButton.addEventListener('click', handleDismiss);
        toast.appendChild(dismissButton);

        documentRef.body.appendChild(toast);
        toastNode = toast;
        return toastNode;
    }

    function hideToast(isManual) {
        if (hideTimer) {
            clearTimeout(hideTimer);
            hideTimer = null;
        }

        if (!toastNode) {
            return;
        }

        toastNode.classList.remove('is-visible');
        if (isManual && liveRegionNode) {
            liveRegionNode.textContent = HIDE_ANNOUNCEMENT_LABEL;
            if (HIDE_ANNOUNCEMENT_LABEL === '') {
                liveRegionNode.textContent = '';
            }
        }
    }

    function showToast(message, duration) {
        if (!toastNode) {
            createToast();
        }
        if (!toastNode) {
            return;
        }

        var messageNode = toastNode.querySelector('.jlg-live-toast__message');
        if (messageNode) {
            messageNode.textContent = message || '';
        }

        toastNode.classList.add('is-visible');

        if (hideTimer) {
            clearTimeout(hideTimer);
        }

        var timeout = typeof duration === 'number' && duration > 0 ? duration : 4000;
        hideTimer = setTimeout(function () {
            hideToast(false);
        }, timeout);
    }

    function ensureInfrastructure() {
        if (!documentRef || !documentRef.body) {
            return false;
        }

        createLiveRegion();
        createToast();
        return true;
    }

    function announce(message, options) {
        if (typeof message !== 'string' || message.trim() === '') {
            return;
        }

        if (!ensureInfrastructure()) {
            return;
        }

        var normalized = message.trim();
        liveRegionNode.textContent = normalized;
        showToast(normalized, options && typeof options.duration === 'number' ? options.duration : undefined);

        try {
            var eventDetail = {
                message: normalized,
                options: options || {},
            };
            var announceEvent = new global.CustomEvent(ANNOUNCE_EVENT, { detail: eventDetail });
            documentRef.dispatchEvent(announceEvent);
        } catch (error) {
            // Ignore CustomEvent errors silently (IE compatibility not targeted).
        }
    }

    global.jlgLiveAnnouncer = {
        announce: announce,
        hide: hideToast,
        ensure: ensureInfrastructure,
    };
})();

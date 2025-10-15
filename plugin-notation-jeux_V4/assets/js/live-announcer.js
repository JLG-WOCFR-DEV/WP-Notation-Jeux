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
    var externalConfig = global.jlgLiveAnnouncerConfig;
    if (!externalConfig || typeof externalConfig !== 'object') {
        externalConfig = l10n.config && typeof l10n.config === 'object' ? l10n.config : {};
    }
    var defaultPoliteness = parsePoliteness(
        typeof externalConfig.defaultPoliteness === 'string'
            ? externalConfig.defaultPoliteness
            : 'polite'
    );
    var defaultDuration = typeof externalConfig.defaultDuration === 'number' && externalConfig.defaultDuration >= 0
        ? externalConfig.defaultDuration
        : 4000;
    var isEnabled = typeof externalConfig.enabled === 'boolean' ? externalConfig.enabled : true;
    var LIVE_REGION_ID = 'jlg-live-announcer-region';
    var TOAST_ID = 'jlg-live-announcer-toast';
    var ANNOUNCE_EVENT = 'jlg:live-announcer:announcement';
    var liveRegionNode = null;
    var toastNode = null;
    var hideTimer = null;
    var focusRestoreTarget = null;

    function parsePoliteness(value) {
        return value === 'assertive' ? 'assertive' : 'polite';
    }

    function createLiveRegion() {
        if (liveRegionNode) {
            return liveRegionNode;
        }

        var region = documentRef.createElement('div');
        region.id = LIVE_REGION_ID;
        region.setAttribute('role', 'status');
        region.setAttribute('aria-live', defaultPoliteness);
        region.className = 'jlg-live-announcer-sr jlg-visually-hidden screen-reader-text';
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
        toast.setAttribute('aria-live', defaultPoliteness);
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
        if (liveRegionNode) {
            liveRegionNode.textContent = '';
        }

        if (isManual && liveRegionNode) {
            liveRegionNode.textContent = HIDE_ANNOUNCEMENT_LABEL;
            if (HIDE_ANNOUNCEMENT_LABEL === '') {
                liveRegionNode.textContent = '';
            }
        }

        if (isManual) {
            restoreFocusTarget();
        }
    }

    function showToast(message, options) {
        if (!options.toast) {
            hideToast(false);
            return;
        }

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

        toastNode.setAttribute('aria-live', options.politeness);
        toastNode.classList.add('is-visible');

        if (hideTimer) {
            clearTimeout(hideTimer);
        }

        if (options.duration === 0) {
            return;
        }

        hideTimer = setTimeout(function () {
            hideToast(false);
        }, options.duration);
    }

    function ensureInfrastructure() {
        if (!documentRef || !documentRef.body) {
            return false;
        }

        createLiveRegion();
        createToast();
        return true;
    }

    function normalizeOptions(options) {
        var normalized = {
            politeness: defaultPoliteness,
            duration: defaultDuration,
            toast: true,
            restoreFocus: null,
            metadata: {}
        };

        if (typeof options === 'string') {
            normalized.politeness = parsePoliteness(options);
            return normalized;
        }

        if (!options || typeof options !== 'object') {
            return normalized;
        }

        var level = options.politeness || options.level;
        if (typeof level === 'string' && level !== '') {
            normalized.politeness = parsePoliteness(level);
        }

        if (typeof options.duration === 'number' && options.duration >= 0) {
            normalized.duration = options.duration;
        }

        if (typeof options.toast === 'boolean') {
            normalized.toast = options.toast;
        }

        if (typeof options.restoreFocus === 'object' && options.restoreFocus !== null && typeof options.restoreFocus.focus === 'function') {
            normalized.restoreFocus = options.restoreFocus;
        } else if (typeof options.restoreFocus === 'string' && options.restoreFocus !== '' && documentRef) {
            var candidate = documentRef.querySelector(options.restoreFocus);
            if (candidate && typeof candidate.focus === 'function') {
                normalized.restoreFocus = candidate;
            }
        }

        var metadata = {};
        Object.keys(options).forEach(function (key) {
            if (key === 'politeness' || key === 'level' || key === 'duration' || key === 'toast' || key === 'restoreFocus') {
                return;
            }

            metadata[key] = options[key];
        });

        normalized.metadata = metadata;

        return normalized;
    }

    function rememberFocusTarget(options) {
        focusRestoreTarget = null;

        if (options.restoreFocus) {
            focusRestoreTarget = options.restoreFocus;
            return;
        }

        if (!documentRef) {
            return;
        }

        var activeElement = documentRef.activeElement;

        if (!activeElement || activeElement === documentRef.body) {
            return;
        }

        if (toastNode && toastNode.contains(activeElement)) {
            return;
        }

        if (typeof activeElement.focus === 'function') {
            focusRestoreTarget = activeElement;
        }
    }

    function restoreFocusTarget() {
        if (!focusRestoreTarget) {
            return;
        }

        if (!documentRef || !documentRef.body || !documentRef.body.contains(focusRestoreTarget)) {
            focusRestoreTarget = null;
            return;
        }

        try {
            focusRestoreTarget.focus();
        } catch (error) {
            // Ignore focus errors (element might be detached).
        }

        focusRestoreTarget = null;
    }

    function announce(message, options) {
        if (typeof message !== 'string' || message.trim() === '') {
            return;
        }

        if (!isEnabled || !ensureInfrastructure()) {
            return;
        }

        var normalized = message.trim();
        var normalizedOptions = normalizeOptions(options);

        rememberFocusTarget(normalizedOptions);

        liveRegionNode.setAttribute('aria-live', normalizedOptions.politeness);
        liveRegionNode.textContent = normalized;

        showToast(normalized, normalizedOptions);

        try {
            var eventDetail = {
                message: normalized,
                options: normalizedOptions.metadata,
                settings: {
                    politeness: normalizedOptions.politeness,
                    duration: normalizedOptions.duration,
                    toast: normalizedOptions.toast
                }
            };
            var announceEvent = new global.CustomEvent(ANNOUNCE_EVENT, { detail: eventDetail });
            documentRef.dispatchEvent(announceEvent);
        } catch (error) {
            // Ignore CustomEvent errors silently (IE compatibility not targeted).
        }
    }

    function setEnabled(value) {
        var nextState = !!value;

        if (nextState === isEnabled) {
            return;
        }

        isEnabled = nextState;

        if (!isEnabled) {
            hideToast(false);
            if (liveRegionNode) {
                liveRegionNode.textContent = '';
            }
        }
    }

    function configureRuntime(config) {
        if (!config || typeof config !== 'object') {
            return;
        }

        if (typeof config.defaultPoliteness === 'string') {
            defaultPoliteness = parsePoliteness(config.defaultPoliteness);
        }

        if (typeof config.defaultDuration === 'number' && config.defaultDuration >= 0) {
            defaultDuration = config.defaultDuration;
        }

        if (typeof config.enabled === 'boolean') {
            setEnabled(config.enabled);
        }
    }

    global.jlgLiveAnnouncer = {
        announce: announce,
        hide: hideToast,
        ensure: ensureInfrastructure,
        setEnabled: setEnabled,
        isEnabled: function () {
            return isEnabled;
        },
        configure: configureRuntime
    };
})();

(function (window, document) {
    'use strict';

    var config = window.jlgOnboarding || {};
    var telemetryConfig = config.telemetry || {};
    var debugEvents = telemetryConfig && telemetryConfig.debug ? [] : null;

    if (typeof window !== 'undefined') {
        window.jlgOnboardingTracker = Object.assign({}, window.jlgOnboardingTracker || {}, {
            getDebugEvents: function () {
                if (!debugEvents) {
                    return [];
                }

                return debugEvents.slice();
            },
            resetDebugEvents: function () {
                if (debugEvents) {
                    debugEvents.length = 0;
                }
            },
        });
    }

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
        } else {
            callback();
        }
    }

    function getMessage(key, fallback) {
        if (config && config.i18n && Object.prototype.hasOwnProperty.call(config.i18n, key)) {
            return config.i18n[key];
        }
        return fallback;
    }

    function sendTrackingEvent(eventName, payload) {
        if (typeof eventName !== 'string' || eventName === '') {
            return;
        }

        var details = payload && typeof payload === 'object' ? Object.assign({}, payload) : {};
        var status = details.status === 'error' ? 'error' : 'success';
        details.status = status;

        if (debugEvents) {
            debugEvents.push({
                event: eventName,
                payload: Object.assign({}, details),
            });
        }

        if (!telemetryConfig || !telemetryConfig.endpoint || !telemetryConfig.action || !telemetryConfig.nonce) {
            return;
        }

        try {
            var body = new window.FormData();
            body.append('action', telemetryConfig.action);
            body.append('nonce', telemetryConfig.nonce);
            body.append('event', eventName);
            body.append('payload', JSON.stringify(details));

            window.fetch(telemetryConfig.endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                body: body,
            }).catch(function () {
                // Ignore network errors to keep the onboarding flow smooth.
            });
        } catch (error) {
            void error; // eslint-disable-line no-void
        }
    }

    ready(function () {
        var form = document.getElementById('jlg-onboarding-form');
        if (!form) {
            return;
        }

        var steps = Array.prototype.slice.call(form.querySelectorAll('.jlg-onboarding-step'));
        if (!steps.length) {
            return;
        }

        var feedback = form.querySelector('.jlg-onboarding-feedback');
        var prevButton = form.querySelector('.jlg-onboarding-prev');
        var nextButton = form.querySelector('.jlg-onboarding-next');
        var submitButton = form.querySelector('.jlg-onboarding-submit');
        var hiddenStepInput = form.querySelector('#jlg-onboarding-current-step');
        var progressItems = Array.prototype.slice.call(document.querySelectorAll('.jlg-onboarding-progress__item'));
        var stepCount = parseInt(config.stepCount, 10);
        if (!stepCount || stepCount < steps.length) {
            stepCount = steps.length;
        }

        var currentIndex = 0;
        var stepStartedAt = null;
        if (hiddenStepInput) {
            var initialStep = parseInt(hiddenStepInput.value, 10);
            if (!isNaN(initialStep) && initialStep >= 1 && initialStep <= steps.length) {
                currentIndex = initialStep - 1;
            }
        }

        function setFeedback(message) {
            if (!feedback) {
                return;
            }

            feedback.textContent = message || '';
            feedback.style.display = message ? 'block' : 'none';
        }

        function focusFirstInteractive(stepElement) {
            if (!stepElement) {
                return;
            }

            var focusable = stepElement.querySelector('input, select, textarea, button');
            if (focusable && typeof focusable.focus === 'function') {
                focusable.focus();
            }
        }

        function toggleStepVisibility(index) {
            steps.forEach(function (step, position) {
                if (position === index) {
                    step.classList.add('is-active');
                } else {
                    step.classList.remove('is-active');
                }
            });
        }

        function updateNavigation(index) {
            if (index <= 0) {
                prevButton.style.display = 'none';
            } else {
                prevButton.style.display = '';
            }

            if (index >= steps.length - 1) {
                nextButton.style.display = 'none';
                submitButton.style.display = '';
            } else {
                nextButton.style.display = '';
                submitButton.style.display = 'none';
            }

            if (hiddenStepInput) {
                hiddenStepInput.value = String(index + 1);
            }

            progressItems.forEach(function (item, position) {
                if (position === index) {
                    item.classList.add('is-current');
                    item.classList.remove('is-complete');
                } else if (position < index) {
                    item.classList.add('is-complete');
                    item.classList.remove('is-current');
                } else {
                    item.classList.remove('is-current');
                    item.classList.remove('is-complete');
                }
            });
        }

        function hasCheckedInputs(selector) {
            var inputs = form.querySelectorAll(selector);
            for (var i = 0; i < inputs.length; i += 1) {
                if (inputs[i].checked) {
                    return true;
                }
            }
            return false;
        }

        function validateStep(index) {
            var result = {
                valid: true,
                code: 'valid',
                message: '',
            };

            switch (index) {
                case 0:
                    if (!hasCheckedInputs('input[name="allowed_post_types[]"]')) {
                        result.valid = false;
                        result.code = 'missing_post_type';
                        result.message = getMessage('selectPostType', 'Sélectionnez au moins un type de contenu.');
                        return result;
                    }
                    break;
                case 1:
                    if (!hasCheckedInputs('input[name="modules[]"]')) {
                        result.valid = false;
                        result.code = 'missing_module';
                        result.message = getMessage('moduleReminder', 'Choisissez au moins un module optionnel.');
                        return result;
                    }
                    break;
                case 2:
                    if (!hasCheckedInputs('input[name="visual_preset"]')) {
                        result.valid = false;
                        result.code = 'missing_visual_preset';
                        result.message = getMessage('selectPreset', 'Choisissez un préréglage visuel.');
                        return result;
                    }
                    if (!hasCheckedInputs('input[name="visual_theme"]')) {
                        result.valid = false;
                        result.code = 'missing_visual_theme';
                        result.message = getMessage('selectPreset', 'Choisissez un thème visuel.');
                        return result;
                    }
                    break;
                case 3:
                    var skip = form.querySelector('input[name="rawg_skip"]');
                    var input = form.querySelector('input[name="rawg_api_key"]');
                    var skipChecked = skip ? skip.checked : false;
                    var value = input ? String(input.value || '').trim() : '';
                    if (!skipChecked && value.length < 10) {
                        result.valid = false;
                        result.code = 'missing_rawg_key';
                        result.message = getMessage('missingRawgKey', 'Indiquez une clé RAWG ou cochez la case pour la fournir plus tard.');
                        return result;
                    }
                    break;
                default:
                    break;
            }

            return result;
        }

        function goToStep(index) {
            if (index < 0) {
                index = 0;
            }
            if (index > steps.length - 1) {
                index = steps.length - 1;
            }

            var previousIndex = currentIndex;
            var now = Date.now();

            if (previousIndex !== index && previousIndex >= 0 && previousIndex < steps.length && stepStartedAt !== null) {
                var duration = Math.max(0, (now - stepStartedAt) / 1000);
                sendTrackingEvent('step_leave', {
                    step: previousIndex + 1,
                    duration: duration,
                    status: 'success',
                    direction: index > previousIndex ? 'forward' : 'backward',
                });
            }

            currentIndex = index;

            if (stepStartedAt === null || previousIndex !== currentIndex) {
                stepStartedAt = now;
                sendTrackingEvent('step_enter', {
                    step: currentIndex + 1,
                    status: 'success',
                    direction: previousIndex < currentIndex ? 'forward' : (previousIndex > currentIndex ? 'backward' : 'initial'),
                });
            }

            toggleStepVisibility(currentIndex);
            updateNavigation(currentIndex);
            setFeedback('');
            focusFirstInteractive(steps[currentIndex]);
        }

        function handleNext() {
            var validationResult = validateStep(currentIndex);
            if (!validationResult.valid) {
                setFeedback(validationResult.message);
                sendTrackingEvent('validation', {
                    step: currentIndex + 1,
                    status: 'error',
                    feedback_code: validationResult.code,
                    feedback_message: validationResult.message,
                });
                return;
            }

            sendTrackingEvent('validation', {
                step: currentIndex + 1,
                status: 'success',
                feedback_code: 'valid',
            });

            goToStep(currentIndex + 1);
        }

        function handlePrev() {
            goToStep(currentIndex - 1);
        }

        if (nextButton) {
            nextButton.addEventListener('click', function (event) {
                event.preventDefault();
                handleNext();
            });
        }

        if (prevButton) {
            prevButton.addEventListener('click', function (event) {
                event.preventDefault();
                handlePrev();
            });
        }

        form.addEventListener('submit', function (event) {
            var validationResult = validateStep(currentIndex);
            if (!validationResult.valid) {
                event.preventDefault();
                setFeedback(validationResult.message);
                sendTrackingEvent('validation', {
                    step: currentIndex + 1,
                    status: 'error',
                    feedback_code: validationResult.code,
                    feedback_message: validationResult.message,
                });
                sendTrackingEvent('submission', {
                    step: currentIndex + 1,
                    status: 'error',
                    feedback_code: validationResult.code,
                    feedback_message: validationResult.message,
                });
                return;
            }

            sendTrackingEvent('validation', {
                step: currentIndex + 1,
                status: 'success',
                feedback_code: 'valid',
            });

            if (currentIndex >= 0 && currentIndex < steps.length && stepStartedAt !== null) {
                var duration = Math.max(0, (Date.now() - stepStartedAt) / 1000);
                sendTrackingEvent('step_leave', {
                    step: currentIndex + 1,
                    duration: duration,
                    status: 'success',
                    direction: 'complete',
                    reason: 'submission',
                });
            }

            sendTrackingEvent('submission', {
                step: currentIndex + 1,
                status: 'success',
                feedback_code: 'submitted',
            });
        });

        var rawgSkip = form.querySelector('input[name="rawg_skip"]');
        function syncRawgInputState() {
            var rawgInput = form.querySelector('input[name="rawg_api_key"]');
            if (!rawgInput) {
                return;
            }
            if (rawgSkip && rawgSkip.checked) {
                rawgInput.setAttribute('aria-disabled', 'true');
                rawgInput.setAttribute('disabled', 'disabled');
            } else {
                rawgInput.removeAttribute('aria-disabled');
                rawgInput.removeAttribute('disabled');
            }
        }

        if (rawgSkip) {
            rawgSkip.addEventListener('change', function () {
                syncRawgInputState();
            });
            syncRawgInputState();
        }

        goToStep(currentIndex);
    });
})(window, document);

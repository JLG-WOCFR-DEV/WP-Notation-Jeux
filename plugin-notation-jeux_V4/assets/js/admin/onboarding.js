(function (window, document) {
    'use strict';

    var config = window.jlgOnboarding || {};

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
            switch (index) {
                case 0:
                    if (!hasCheckedInputs('input[name="allowed_post_types[]"]')) {
                        return getMessage('selectPostType', 'Sélectionnez au moins un type de contenu.');
                    }
                    break;
                case 1:
                    if (!hasCheckedInputs('input[name="modules[]"]')) {
                        return getMessage('moduleReminder', 'Choisissez au moins un module optionnel.');
                    }
                    break;
                case 2:
                    if (!hasCheckedInputs('input[name="visual_preset"]')) {
                        return getMessage('selectPreset', 'Choisissez un préréglage visuel.');
                    }
                    if (!hasCheckedInputs('input[name="visual_theme"]')) {
                        return getMessage('selectPreset', 'Choisissez un thème visuel.');
                    }
                    break;
                case 3:
                    var skip = form.querySelector('input[name="rawg_skip"]');
                    var input = form.querySelector('input[name="rawg_api_key"]');
                    var skipChecked = skip ? skip.checked : false;
                    var value = input ? String(input.value || '').trim() : '';
                    if (!skipChecked && value.length < 10) {
                        return getMessage('missingRawgKey', 'Indiquez une clé RAWG ou cochez la case pour la fournir plus tard.');
                    }
                    break;
                default:
                    break;
            }
            return '';
        }

        function goToStep(index) {
            if (index < 0) {
                index = 0;
            }
            if (index > steps.length - 1) {
                index = steps.length - 1;
            }

            currentIndex = index;
            toggleStepVisibility(currentIndex);
            updateNavigation(currentIndex);
            setFeedback('');
            focusFirstInteractive(steps[currentIndex]);
        }

        function handleNext() {
            var validationMessage = validateStep(currentIndex);
            if (validationMessage) {
                setFeedback(validationMessage);
                return;
            }
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
            var validationMessage = validateStep(currentIndex);
            if (validationMessage) {
                event.preventDefault();
                setFeedback(validationMessage);
            }
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

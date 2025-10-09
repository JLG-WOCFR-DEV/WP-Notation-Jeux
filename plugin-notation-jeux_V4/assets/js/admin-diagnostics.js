(function () {
    'use strict';

    function sendRawgPing(button, resultNode) {
        if (!window.ajaxurl) {
            return;
        }

        var action = button.getAttribute('data-action');
        var nonce = button.getAttribute('data-nonce');

        if (!action || !nonce) {
            return;
        }

        var formData = new window.FormData();
        formData.append('action', action);
        formData.append('nonce', nonce);

        button.disabled = true;
        button.classList.add('is-busy');

        if (resultNode) {
            resultNode.textContent = '⏳ ' + (button.getAttribute('data-progress-label') || 'Test en cours…');
        }

        window.fetch(window.ajaxurl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData,
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (payload) {
                if (resultNode) {
                    if (payload && payload.success && payload.data && payload.data.message) {
                        resultNode.textContent = '✅ ' + payload.data.message;
                    } else if (payload && payload.data && payload.data.message) {
                        resultNode.textContent = '⚠️ ' + payload.data.message;
                    } else {
                        resultNode.textContent = '⚠️ Action terminée.';
                    }
                }
            })
            .catch(function (error) {
                if (resultNode) {
                    resultNode.textContent = '❌ ' + (error && error.message ? error.message : 'Erreur réseau.');
                }
            })
            .finally(function () {
                button.disabled = false;
                button.classList.remove('is-busy');
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var pingButton = document.querySelector('[data-rawg-ping]');
        if (!pingButton) {
            return;
        }

        var resultNode = document.querySelector('.jlg-diagnostics__ping-result');

        pingButton.addEventListener('click', function () {
            if (pingButton.disabled) {
                return;
            }

            sendRawgPing(pingButton, resultNode);
        });
    });
})();


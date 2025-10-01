jQuery(document).ready(function($) {
    var ratingMessages = (typeof jlgUserRatingL10n !== 'undefined') ? jlgUserRatingL10n : {};
    var successMessage = ratingMessages.successMessage || 'Merci pour votre vote !';
    var genericErrorMessage = ratingMessages.genericErrorMessage || 'Erreur. Veuillez réessayer.';

    function getTranslatedAlreadyVotedMessage() {
        if (ratingMessages.alreadyVotedMessage) {
            return ratingMessages.alreadyVotedMessage;
        }

        if (typeof window !== 'undefined' && window.wp && window.wp.i18n && typeof window.wp.i18n.__ === 'function') {
            return window.wp.i18n.__('Vous avez déjà voté !', 'notation-jlg');
        }

        return 'Vous avez déjà voté !';
    }

    var alreadyVotedMessage = getTranslatedAlreadyVotedMessage();

    function normalizeForComparison(text) {
        if (typeof text !== 'string') {
            return '';
        }

        var normalized = text.toLowerCase().trim().replace(/[!¡?¿.,;:]/g, '');

        if (typeof normalized.normalize === 'function') {
            normalized = normalized.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }

        return normalized;
    }

    function containsIndicator(message, indicator) {
        var escapedIndicator = indicator.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        var pattern = new RegExp('(^|\\s|\\-|\\/)' + escapedIndicator + '($|\\s|\\-|\\/)', 'i');

        return pattern.test(message);
    }

    function isAlreadyVotedMessage(message) {
        if (!message || typeof message !== 'string') {
            return false;
        }

        var normalizedMessage = normalizeForComparison(message);
        var normalizedReference = normalizeForComparison(alreadyVotedMessage);

        if (normalizedReference && normalizedMessage === normalizedReference) {
            return true;
        }

        var alreadyIndicators = ['deja', 'already', 'ya', 'ja', 'gia', 'bereits', 'schon', 'уже'];
        var hasAlreadyIndicator = alreadyIndicators.some(function(indicator) {
            return containsIndicator(normalizedMessage, indicator);
        });

        if (!hasAlreadyIndicator) {
            return false;
        }

        var voteRoots = ['vot', 'stem', 'abstimm', 'abgestimm', 'голос'];

        return voteRoots.some(function(root) {
            return normalizedMessage.indexOf(root) !== -1;
        });
    }

    var starSelector = '.jlg-user-star';
    var starsContainerSelector = '.jlg-user-rating-stars';

    function canInteract(ratingBlock) {
        return !(ratingBlock.hasClass('has-voted') || ratingBlock.hasClass('is-loading'));
    }

    function highlightStars(star) {
        var ratingBlock = star.closest('.jlg-user-rating-block');
        if (!canInteract(ratingBlock)) {
            return;
        }

        star.add(star.prevAll()).addClass('hover');
        star.nextAll().removeClass('hover');
    }

    function clearHighlights(container) {
        container.children(starSelector).removeClass('hover');
    }

    // Effet de survol des étoiles
    $(starSelector).on('mouseover', function() {
        highlightStars($(this));
    });

    // Mise en évidence au focus pour les interactions clavier
    $(starSelector).on('focus', function() {
        highlightStars($(this));
    });

    // Retire les mises en évidence lorsque le focus quitte l'étoile
    $(starSelector).on('blur', function() {
        clearHighlights($(this).closest(starsContainerSelector));
    });

    // Annule l'effet de survol quand la souris quitte le bloc
    $(starsContainerSelector).on('mouseleave', function() {
        clearHighlights($(this));
    });

    // Gestion des interactions clavier
    $(starSelector).on('keydown', function(event) {
        if (event.key === 'Enter' || event.key === ' ' || event.key === 'Spacebar') {
            event.preventDefault();
            $(this).trigger('click');
        }
    });

    // Au clic sur une étoile
    $(starSelector).on('click', function() {
        var star = $(this);
        var ratingBlock = star.closest('.jlg-user-rating-block');
        var postId = star.parent().data('post-id');
        var rating = star.data('value');

        if (!canInteract(ratingBlock)) {
            return;
        }

        ratingBlock.addClass('is-loading');

        $.ajax({
            url: jlg_rating_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'jlg_rate_post',
                post_id: postId,
                rating: rating,
                nonce: jlg_rating_ajax.nonce,
                token: jlg_rating_ajax.token
            },
            success: function(response) {
                ratingBlock.removeClass('is-loading');

                var responseData = response && response.data ? response.data : {};
                var errorMessage = responseData.message || genericErrorMessage;

                if (response.success) {
                    ratingBlock.addClass('has-voted');

                    if (typeof responseData.new_average !== 'undefined') {
                        ratingBlock.find('.jlg-user-rating-avg-value').text(responseData.new_average);
                    }

                    if (typeof responseData.new_count !== 'undefined') {
                        ratingBlock.find('.jlg-user-rating-count-value').text(responseData.new_count);
                    }

                    ratingBlock.find('.jlg-rating-message').text(successMessage).show();

                    var allStars = ratingBlock.find(starSelector);
                    allStars.removeClass('selected').attr('aria-checked', 'false');
                    star.add(star.prevAll()).addClass('selected');
                    star.attr('aria-checked', 'true');
                    clearHighlights(star.closest(starsContainerSelector));
                } else {
                    ratingBlock.find('.jlg-rating-message').text(errorMessage).show();

                    if (isAlreadyVotedMessage(errorMessage)) {
                        ratingBlock.addClass('has-voted');
                    } else {
                        ratingBlock.removeClass('has-voted');
                        var allStars = ratingBlock.find(starSelector);
                        allStars.removeClass('selected').attr('aria-checked', 'false');
                    }
                    clearHighlights(star.closest(starsContainerSelector));
                }
            },
            error: function() {
                ratingBlock.removeClass('is-loading has-voted');
                ratingBlock.find('.jlg-rating-message').text(genericErrorMessage).show();
                var allStars = ratingBlock.find(starSelector);
                allStars.removeClass('selected').attr('aria-checked', 'false');
                clearHighlights(star.closest(starsContainerSelector));
            }
        });
    });
});

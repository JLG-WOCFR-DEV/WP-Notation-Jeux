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

    function updateRatingState(ratingBlock, ratingValue) {
        var stars = ratingBlock.find('.jlg-user-star');

        stars.each(function() {
            var starButton = $(this);
            var starValue = parseInt(starButton.data('value'), 10);
            var isSelected = ratingValue && starValue <= ratingValue;
            var isChecked = ratingValue && starValue === ratingValue;

            starButton.toggleClass('selected', !!isSelected);
            starButton.attr('aria-checked', isChecked ? 'true' : 'false');
        });
    }

    function initializeAriaStates() {
        $('.jlg-user-rating-block').each(function() {
            var ratingBlock = $(this);
            var checkedStar = ratingBlock.find('.jlg-user-star[aria-checked="true"]').last();

            if (!checkedStar.length) {
                checkedStar = ratingBlock.find('.jlg-user-star.selected').last();
            }

            var ratingValue = checkedStar.length ? parseInt(checkedStar.data('value'), 10) : null;
            updateRatingState(ratingBlock, ratingValue);

            ratingBlock.find('.jlg-user-rating-avg-value').attr({
                'aria-live': 'polite',
                'aria-atomic': 'true'
            });
        });
    }

    initializeAriaStates();

    // Effet de survol des étoiles
    $('.jlg-user-star').on('mouseover', function() {
        var ratingBlock = $(this).closest('.jlg-user-rating-block');
        if (ratingBlock.hasClass('has-voted') || ratingBlock.hasClass('is-loading')) {
            return;
        }
        
        var currentStar = $(this);
        currentStar.add(currentStar.prevAll()).addClass('hover');
        currentStar.nextAll().removeClass('hover');
    });

    // Annule l'effet de survol quand la souris quitte le bloc
    $('.jlg-user-rating-stars').on('mouseleave', function() {
        $(this).children('.jlg-user-star').removeClass('hover');
    });

    function submitRating() {
        var star = $(this);
        var ratingBlock = star.closest('.jlg-user-rating-block');
        var postId = star.parent().data('post-id');
        var rating = star.data('value');

        if (ratingBlock.hasClass('is-loading') || ratingBlock.hasClass('has-voted')) {
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
                        var avgValueElement = ratingBlock.find('.jlg-user-rating-avg-value');
                        avgValueElement.text(responseData.new_average);
                    }

                    if (typeof responseData.new_count !== 'undefined') {
                        ratingBlock.find('.jlg-user-rating-count-value').text(responseData.new_count);
                    }

                    ratingBlock.find('.jlg-rating-message').text(successMessage).show();

                    updateRatingState(ratingBlock, parseInt(rating, 10));
                    ratingBlock.find('.jlg-user-star').removeClass('hover');
                } else {
                    ratingBlock.find('.jlg-rating-message').text(errorMessage).show();

                    if (isAlreadyVotedMessage(errorMessage)) {
                        ratingBlock.addClass('has-voted');
                    } else {
                        ratingBlock.removeClass('has-voted');
                        updateRatingState(ratingBlock, null);
                    }
                    ratingBlock.find('.jlg-user-star').removeClass('hover');
                }
            },
            error: function() {
                ratingBlock.removeClass('is-loading has-voted');
                ratingBlock.find('.jlg-rating-message').text(genericErrorMessage).show();
                updateRatingState(ratingBlock, null);
                ratingBlock.find('.jlg-user-star').removeClass('hover');
            }
        });
    }

    // Au clic sur une étoile
    $('.jlg-user-star').on('click', submitRating);

    // Activation au clavier (Espace ou Entrée)
    $('.jlg-user-star').on('keydown', function(event) {
        if (event.key === ' ' || event.key === 'Enter' || event.key === 'Spacebar') {
            event.preventDefault();
            submitRating.call(this, event);
        }
    });
});

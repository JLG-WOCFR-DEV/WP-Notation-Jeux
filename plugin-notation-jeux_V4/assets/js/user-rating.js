jQuery(document).ready(function($) {
    var ratingMessages = (typeof jlgUserRatingL10n !== 'undefined') ? jlgUserRatingL10n : {};
    var successMessage = ratingMessages.successMessage || 'Merci pour votre vote !';
    var genericErrorMessage = ratingMessages.genericErrorMessage || 'Erreur. Veuillez réessayer.';

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

    // Au clic sur une étoile
    $('.jlg-user-star').on('click', function() {
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

                if (response.success) {
                    ratingBlock.addClass('has-voted');
                    
                    ratingBlock.find('.jlg-user-rating-avg-value').text(response.data.new_average);
                    ratingBlock.find('.jlg-user-rating-count-value').text(response.data.new_count);
                    
                    ratingBlock.find('.jlg-rating-message').text(successMessage).show();

                    star.siblings().removeClass('selected');
                    star.add(star.prevAll()).addClass('selected');
                } else {
                    ratingBlock.addClass('has-voted');
                    ratingBlock.find('.jlg-rating-message').text(response.data.message).show();
                }
            },
            error: function() {
                ratingBlock.removeClass('is-loading');
                ratingBlock.find('.jlg-rating-message').text(genericErrorMessage).show();
            }
        });
    });
});
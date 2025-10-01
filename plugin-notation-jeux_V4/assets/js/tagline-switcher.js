jQuery(document).ready(function($) {
    var KEY_ENTER = 13;
    var KEY_SPACE = 32;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    var toggleTagline = function($trigger) {
        var selectedLang = $trigger.data('lang');
        var $taglineBlock = $trigger.closest('.jlg-tagline-block');

        if ($trigger.hasClass('active')) {
            return;
        }

        var $triggers = $taglineBlock.find('.jlg-lang-flag');
        $triggers.removeClass('active').attr('aria-pressed', 'false');
        $trigger.addClass('active').attr('aria-pressed', 'true');

        $taglineBlock.find('.jlg-tagline-text').each(function() {
            var $tagline = $(this);
            var matchesSelection = $tagline.data('lang') === selectedLang;

            $tagline.stop(true, true);

            if (matchesSelection) {
                $tagline.attr('aria-hidden', 'false').removeAttr('hidden');

                if (reduceMotion) {
                    $tagline.show();
                } else {
                    $tagline.hide().fadeIn(300);
                }
            } else {
                $tagline.attr('aria-hidden', 'true').attr('hidden', 'hidden').hide();
            }
        });
    };

    $('.jlg-tagline-flags').each(function() {
        var $container = $(this);

        $container.on('click', '.jlg-lang-flag', function(event) {
            event.preventDefault();
            toggleTagline($(this));
        });

        $container.on('keydown', '.jlg-lang-flag', function(event) {
            if (event.which !== KEY_ENTER && event.which !== KEY_SPACE) {
                return;
            }

            event.preventDefault();
            toggleTagline($(this));
        });
    });
});

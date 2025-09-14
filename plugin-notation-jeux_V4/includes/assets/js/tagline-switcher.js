jQuery(document).ready(function($) {
    $('.jlg-tagline-flags .jlg-lang-flag').on('click', function() {
        var selectedLang = $(this).data('lang');
        var taglineBlock = $(this).closest('.jlg-tagline-block');

        if ($(this).hasClass('active')) {
            return;
        }

        taglineBlock.find('.jlg-lang-flag').removeClass('active');
        $(this).addClass('active');

        taglineBlock.find('.jlg-tagline-text').hide();
        taglineBlock.find('.jlg-tagline-text[data-lang="' + selectedLang + '"]').fadeIn(300);
    });
});
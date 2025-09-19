jQuery(document).ready(function($) {
    // Recherche de jeu
    $('#jlg-api-search-button').on('click', function() {
        var searchInput = $('#jlg-api-search-input');
        var resultsDiv = $('#jlg-api-search-results');
        var searchTerm = searchInput.val();
        var button = $(this);

        var ajaxEndpoint = '';
        if (typeof jlg_admin_ajax !== 'undefined' && jlg_admin_ajax.ajax_url) {
            ajaxEndpoint = jlg_admin_ajax.ajax_url;
        } else if (typeof ajaxurl !== 'undefined') {
            ajaxEndpoint = ajaxurl;
        }

        if (!ajaxEndpoint) {
            resultsDiv
                .empty()
                .append(
                    $('<p>')
                        .css('color', 'red')
                        .text('Configuration AJAX invalide.')
                );
            return;
        }

        var nonce = (typeof jlg_admin_ajax !== 'undefined' && jlg_admin_ajax.nonce) ? jlg_admin_ajax.nonce : '';

        if (!nonce) {
            resultsDiv
                .empty()
                .append(
                    $('<p>')
                        .css('color', 'red')
                        .text('Nonce de sécurité manquant. Actualisez la page.')
                );
            return;
        }

        if (searchTerm.length < 3) {
            resultsDiv
                .empty()
                .append(
                    $('<p>')
                        .css('color', 'red')
                        .text('Veuillez entrer au moins 3 caractères.')
                );
            return;
        }

        button.text('Recherche...').prop('disabled', true);
        resultsDiv.text('Chargement...');

        $.ajax({
            url: ajaxEndpoint,
            type: 'POST',
            data: {
                action: 'jlg_search_rawg_games',
                nonce: nonce,
                search: searchTerm,
            },
            success: function(response) {
                button.text('Rechercher').prop('disabled', false);

                if (response === '-1') {
                    resultsDiv
                        .empty()
                        .append(
                            $('<p>')
                                .css('color', 'red')
                                .text('Vérification de sécurité échouée. Actualisez la page.')
                        );
                    return;
                }

                if (response.success) {
                    var games = [];

                    if (Array.isArray(response.data)) {
                        games = response.data;
                    } else if (response.data && Array.isArray(response.data.games)) {
                        games = response.data.games;
                    }

                    if (!games.length && response.data && response.data.message) {
                        resultsDiv
                            .empty()
                            .append(
                                $('<p>').text(response.data.message)
                            );
                        resultsDiv.data('games', []);
                        return;
                    }
                    var list = $('<ul>').css({
                        'list-style': 'disc',
                        'padding-left': '20px'
                    });
                    games.forEach(function(game, index) {
                        var year = game.release_date ? new Date(game.release_date).getFullYear() : 'N/A';
                        var listItem = $('<li>');
                        var name = game.name ? String(game.name) : '';
                        listItem.append($('<strong>').text(name));
                        listItem.append(document.createTextNode(' (' + year + ') '));
                        var buttonSelect = $('<button>')
                            .attr('type', 'button')
                            .addClass('button button-small jlg-select-game')
                            .attr('data-index', index)
                            .text('Choisir');
                        listItem.append(buttonSelect);
                        list.append(listItem);
                    });
                    resultsDiv.empty().append(list);
                    resultsDiv.data('games', games);
                } else {
                    var errorMessage = '';
                    if (response && response.data) {
                        errorMessage = String(response.data);
                    }
                    resultsDiv
                        .empty()
                        .append(
                            $('<p>')
                                .css('color', 'red')
                                .text(errorMessage)
                        );
                }
            },
            error: function() {
                button.text('Rechercher').prop('disabled', false);
                resultsDiv
                    .empty()
                    .append(
                        $('<p>')
                            .css('color', 'red')
                            .text('Erreur de communication.')
                    );
            }
        });
    });

    // Clic sur le bouton "Choisir" pour remplir les champs
    $(document).on('click', '.jlg-select-game', function() {
        var index = $(this).data('index');
        var resultsDiv = $('#jlg-api-search-results');
        var gameData = resultsDiv.data('games')[index];

        if (gameData) {
            $('#jlg_developpeur').val(gameData.developers);
            $('#jlg_editeur').val(gameData.publishers);
            $('#jlg_date_sortie').val(gameData.release_date);
            $('#jlg_cover_image_url').val(gameData.cover_image);
            if (gameData.pegi) {
                $('#jlg_pegi').val(gameData.pegi);
            }

            // Gère les cases à cocher des plateformes
            $('input[name="jlg_plateformes[]"]').prop('checked', false);
            gameData.platforms.forEach(function(platformName) {
                $('input[name="jlg_plateformes[]"]').each(function() {
                    var checkboxVal = $(this).val().toLowerCase();
                    var platformApiName = platformName.toLowerCase();
                    
                    var match = false;
                    if (checkboxVal.includes('switch 2') && platformApiName.includes('nintendo switch 2')) {
                        match = true;
                    } else if (checkboxVal === 'nintendo switch' && platformApiName.includes('nintendo switch') && !platformApiName.includes('2')) {
                        match = true;
                    } else if (platformApiName.includes(checkboxVal.split(' ')[0])) {
                        match = true;
                    }

                    if(match) {
                        $(this).prop('checked', true);
                    }
                });
            });
            resultsDiv
                .empty()
                .append(
                    $('<p>')
                        .css({
                            color: 'green',
                            'font-weight': 'bold'
                        })
                        .text('Fiche technique remplie !')
                );
        }
    });
});

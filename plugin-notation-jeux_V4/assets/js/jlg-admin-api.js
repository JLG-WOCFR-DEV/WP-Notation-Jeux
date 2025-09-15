jQuery(document).ready(function($) {
    // Recherche de jeu
    $('#jlg-api-search-button').on('click', function() {
        var searchInput = $('#jlg-api-search-input');
        var resultsDiv = $('#jlg-api-search-results');
        var searchTerm = searchInput.val();

        if (searchTerm.length < 3) {
            resultsDiv.html('<p style="color:red;">Veuillez entrer au moins 3 caractères.</p>');
            return;
        }

        $(this).text('Recherche...').prop('disabled', true);
        resultsDiv.html('Chargement...');

        $.ajax({
            url: ajaxurl, // variable globale de WordPress
            type: 'POST',
            data: {
                action: 'jlg_search_rawg_games',
                nonce: jlg_admin_ajax.nonce,
                search: searchTerm,
            },
            success: function(response) {
                $('#jlg-api-search-button').text('Rechercher').prop('disabled', false);

                if (response.success) {
                    var games = [];

                    if (Array.isArray(response.data)) {
                        games = response.data;
                    } else if (response.data && Array.isArray(response.data.games)) {
                        games = response.data.games;
                    }

                    if (!games.length && response.data && response.data.message) {
                        resultsDiv.html('<p>' + response.data.message + '</p>');
                        resultsDiv.data('games', []);
                        return;
                    }
                    var html = '<ul style="list-style: disc; padding-left: 20px;">';
                    games.forEach(function(game, index) {
                        var year = game.release_date ? new Date(game.release_date).getFullYear() : 'N/A';
                        html += `<li>
                            <strong>${game.name}</strong> (${year})
                            <button type="button" class="button button-small jlg-select-game" data-index="${index}">Choisir</button>
                        </li>`;
                    });
                    html += '</ul>';
                    resultsDiv.html(html);
                    resultsDiv.data('games', games);
                } else {
                    resultsDiv.html('<p style="color:red;">' + response.data + '</p>');
                }
            },
            error: function() {
                $('#jlg-api-search-button').text('Rechercher').prop('disabled', false);
                resultsDiv.html('<p style="color:red;">Erreur de communication.</p>');
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
            resultsDiv.html('<p style="color:green; font-weight:bold;">Fiche technique remplie !</p>');
        }
    });
});